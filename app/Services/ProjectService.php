<?php

namespace App\Services;

use App\Contracts\ProjectServiceInterface;
use App\Contracts\ProjectRepositoryInterface;
use App\Contracts\ProjectItemServiceInterface;
use App\Models\Project;
use App\Models\Subproject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProjectService implements ProjectServiceInterface
{
    protected ProjectRepositoryInterface $projectRepository;
    protected ProjectItemServiceInterface $itemService;

    public function __construct(
        ProjectRepositoryInterface $projectRepository,
        ProjectItemServiceInterface $itemService
    ) {
        $this->projectRepository = $projectRepository;
        $this->itemService = $itemService;
    }

    /**
     * The flag arrives as a real boolean from the form request, but tolerate the
     * raw "1"/"0" strings a direct caller might still pass.
     */
    protected function isDraft(array $validated): bool
    {
        return filter_var($validated['save_as_draft'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    public function createProject(array $validated): Project
    {
        return DB::transaction(function () use ($validated) {
            $isDraft = $this->isDraft($validated);

            // ✅ إنشاء المشروع بكل البيانات (سواء كان draft أم لا)
            // الفرق الوحيد هو قيمة is_draft
            $project = $this->projectRepository->create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'client_id' => $validated['client_id'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'status' => $validated['status'] ?? 'pending',
                'working_zone' => $validated['working_zone'] ?? null,
                'project_type_id' => $validated['project_type_id'] ?? null,
                'working_days' => $validated['working_days'] ?? 6,
                'daily_work_hours' => $validated['daily_work_hours'] ?? 8,
                'weekly_holidays' => $validated['weekly_holidays'] ?? '',
                'is_draft' => $isDraft,
            ]);

            // إضافة البنود
            if (!empty($validated['items'])) {
                $itemMapping = $this->itemService->createProjectItems($project, $validated['items'], $validated);
                $this->itemService->updateItemPredecessors($validated['items'], $itemMapping);
            }

            // ربط الموظفين
            if (!empty($validated['employees'])) {
                $this->projectRepository->syncEmployees($project, $validated['employees']);
            }

            // حفظ المشاريع الفرعية
            if (!empty($validated['subprojects'])) {
                $this->createSubprojects($project, $validated['subprojects']);
            }

            return $project;
        });
    }

    public function updateProject(Project $project, array $validated): Project
    {
        return DB::transaction(function () use ($project, $validated) {
            // ✅ المسودة تبقى مسودة ما لم يطلب المستخدم النشر صراحةً،
            // والمشروع المنشور لا يعود مسودة أبدًا
            $isDraft = array_key_exists('save_as_draft', $validated)
                ? $project->is_draft && $this->isDraft($validated)
                : (bool) $project->is_draft;

            // ✅ تحديث المشروع بكل البيانات
            $this->projectRepository->update($project, [
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'client_id' => $validated['client_id'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'status' => $validated['status'] ?? 'pending',
                'working_zone' => $validated['working_zone'] ?? null,
                'project_type_id' => $validated['project_type_id'] ?? null,
                'working_days' => $validated['working_days'] ?? 6,
                'daily_work_hours' => $validated['daily_work_hours'] ?? 8,
                'weekly_holidays' => $validated['weekly_holidays'] ?? '',
                'is_draft' => $isDraft,
            ]);

            // ✅ Smart Sync للبنود: يحافظ على daily_progress
            if (!empty($validated['items'])) {
                $itemMapping = $this->itemService->syncProjectItems($project, $validated['items'], $validated);
                $this->itemService->updateItemPredecessors($validated['items'], $itemMapping);
            } else {
                // إذا لم يتم إرسال بنود، احذف كل البنود القديمة
                // Note: assuming ProjectItemRepository is injected or accessible if needed, or we just rely on Project relationship:
                $project->items()->delete();
            }

            // تحديث الموظفين
            if (!empty($validated['employees'])) {
                $this->projectRepository->syncEmployees($project, $validated['employees']);
            }

            // تحديث المشاريع الفرعية
            if (!empty($validated['subprojects'])) {
                $this->updateSubprojects($project, $validated['subprojects']);
            }

            return $project->fresh();
        });
    }

    // (Methods تم نقلها إلى ProjectItemService)

    protected function createSubprojects(Project $project, array $subprojects): void
    {
        Log::info('===== CREATE SUBPROJECTS =====');
        Log::info('Subprojects data received:', $subprojects);
        
        // Get subproject names that actually have items
        $subprojectNamesWithItems = $project->items()->whereNotNull('subproject_name')
            ->distinct()
            ->pluck('subproject_name')
            ->filter()
            ->toArray();
        
        Log::info('Subprojects with items:', $subprojectNamesWithItems);
        
        foreach ($subprojects as $subprojectData) {
            if (!empty($subprojectData['name'])) {
                // Only create subproject if it has items
                if (!in_array($subprojectData['name'], $subprojectNamesWithItems)) {
                    Log::info('Skipping empty subproject:', ['name' => $subprojectData['name']]);
                    continue;
                }
                
                $dataToCreate = [
                    'project_id' => $project->id,
                    'name' => $subprojectData['name'],
                    'start_date' => $subprojectData['start_date'] ?? null,
                    'end_date' => $subprojectData['end_date'] ?? null,
                    'total_quantity' => $subprojectData['total_quantity'] ?? 0,
                    'unit' => $subprojectData['unit'] ?? null,
                    'weight' => $subprojectData['weight'] ?? 1,
                    'sort_order' => (int) ($subprojectData['sort_order'] ?? 0),
                ];
                
                Log::info('Creating subproject:', $dataToCreate);
                $created = Subproject::create($dataToCreate);
                Log::info('Subproject created:', $created->toArray());
            }
        }
    }

    protected function updateSubprojects(Project $project, array $subprojects): void
    {
        Log::info('===== UPDATE SUBPROJECTS =====');
        Log::info('Project ID: ' . $project->id);
        Log::info('Subprojects data received:', $subprojects);
        
        // Get subproject names that actually have items
        $subprojectNamesWithItems = $project->items()->whereNotNull('subproject_name')
            ->distinct()
            ->pluck('subproject_name')
            ->filter()
            ->toArray();
        
        Log::info('Subprojects with items:', $subprojectNamesWithItems);
        
        // Track which subprojects were submitted
        $submittedSubprojectNames = [];
        
        foreach ($subprojects as $subprojectData) {
            if (!empty($subprojectData['name'])) {
                $submittedSubprojectNames[] = $subprojectData['name'];
                
                // Only save subproject if it has items
                if (!in_array($subprojectData['name'], $subprojectNamesWithItems)) {
                    Log::info('Skipping empty subproject:', ['name' => $subprojectData['name']]);
                    
                    // Delete if it exists but now has no items
                    Subproject::where('project_id', $project->id)
                        ->where('name', $subprojectData['name'])
                        ->delete();
                    
                    continue;
                }
                
                // A field the form did not send is a field the user never saw, so it
                // keeps whatever is already stored instead of being reset.
                $dataToUpdate = [];
                foreach (['start_date', 'end_date', 'total_quantity', 'unit', 'weight', 'sort_order'] as $field) {
                    if (array_key_exists($field, $subprojectData)) {
                        $dataToUpdate[$field] = $subprojectData[$field];
                    }
                }
                
                Log::info('Updating/Creating subproject:', [
                    'name' => $subprojectData['name'],
                    'data' => $dataToUpdate
                ]);
                
                $subproject = Subproject::updateOrCreate(
                    [
                        'project_id' => $project->id,
                        'name' => $subprojectData['name']
                    ],
                    $dataToUpdate
                );
                
                Log::info('Subproject updated/created:', $subproject->toArray());
            }
        }
        
        // Delete subprojects that were not submitted but have no items
        $orphanedSubprojects = Subproject::where('project_id', $project->id)
            ->whereNotIn('name', $subprojectNamesWithItems)
            ->get();
        
        foreach ($orphanedSubprojects as $orphan) {
            Log::info('Deleting orphaned subproject:', ['name' => $orphan->name]);
            $orphan->delete();
        }
    }

    public function deleteProject(Project $project): bool
    {
        return DB::transaction(function () use ($project) {
            // حذف البنود والتقدم اليومي
            $project->items()->each(function ($item) {
                if (method_exists($item, 'dailyProgress')) {
                    $item->dailyProgress()->delete();
                }
                $item->delete();
            });

            return $this->projectRepository->delete($project);
        });
    }

    public function publishDraft(Project $project): array
    {
        $errors = [];

        if (!$project->is_draft) {
            $errors[] = __('general.project_is_not_draft');
            return ['success' => false, 'errors' => $errors];
        }

        // Validate required data
        if (!$project->client_id) $errors[] = __('general.client_required');
        if (!$project->start_date) $errors[] = __('general.start_date_required');
        if (!$project->end_date) $errors[] = __('general.end_date_required');
        if (!$project->project_type_id) $errors[] = __('general.project_type_required');
        if (!$project->working_zone) $errors[] = __('general.working_zone_required');
        if ($project->items->count() === 0) $errors[] = __('general.items_required');
        if ($project->employees->count() === 0) $errors[] = __('general.employees_required');

        if (count($errors) > 0) {
            return ['success' => false, 'errors' => $errors];
        }

        $this->projectRepository->update($project, ['is_draft' => false]);

        return ['success' => true];
    }

    public function copyProject(Project $project, string $newName): Project
    {
        return DB::transaction(function () use ($project, $newName) {
            // ✅ تحميل جميع العلاقات المطلوبة
            $project->load(['items', 'employees', 'subprojects']);

            $newProject = $this->projectRepository->create([
                'name' => $newName,
                'description' => $project->description,
                'client_id' => $project->client_id,
                'status' => 'pending',
                'project_type_id' => $project->project_type_id,
                'start_date' => $project->start_date,
                'end_date' => $project->end_date,
                'working_days' => $project->working_days ?? 6,
                'daily_work_hours' => $project->daily_work_hours ?? 8,
                'weekly_holidays' => $project->weekly_holidays ?? '',
                'working_zone' => $project->working_zone,
                'is_draft' => true
            ]);

            // ✅ نسخ البنود مع إنشاء mapping للعلاقات (predecessor)
            $itemMapping = []; // [old_item_id => new_item_id]
            
            if ($project->items && $project->items->count() > 0) {
                // ترتيب البنود حسب item_order للحفاظ على الترتيب
                $sortedItems = $project->items->sortBy('item_order');
                
                foreach ($sortedItems as $item) {
                    $newItem = $this->itemRepository->createItem($newProject, [
                        'work_item_id' => $item->work_item_id,
                        'total_quantity' => $item->total_quantity ?? 0,
                        'estimated_daily_qty' => $item->estimated_daily_qty ?? 0,
                        'duration' => $item->duration ?? 0, // ✅ نسخ duration
                        'completed_quantity' => 0,
                        'remaining_quantity' => $item->total_quantity ?? 0,
                        'predecessor' => null, // ✅ سيتم تحديثه لاحقًا بعد إنشاء جميع البنود
                        'dependency_type' => $item->dependency_type ?? 'end_to_start',
                        'lag' => $item->lag ?? 0,
                        'notes' => $item->notes,
                        'is_measurable' => $item->is_measurable ?? false,
                        'item_order' => $item->item_order ?? 0,
                        'subproject_name' => $item->subproject_name, // ✅ نسخ subproject_name
                        'start_date' => $item->start_date,
                        'end_date' => $item->end_date,
                        'planned_end_date' => $item->planned_end_date
                    ]);
                    
                    // ✅ حفظ mapping للعلاقات
                    $itemMapping[$item->id] = $newItem->id;
                }
                
                // ✅ تحديث العلاقات (predecessor) للبنود الجديدة
                foreach ($sortedItems as $oldItem) {
                    if ($oldItem->predecessor && isset($itemMapping[$oldItem->id]) && isset($itemMapping[$oldItem->predecessor])) {
                        $newItemId = $itemMapping[$oldItem->id];
                        $newPredecessorId = $itemMapping[$oldItem->predecessor];
                        $this->itemRepository->updateItemPredecessor($newItemId, $newPredecessorId);
                    }
                }
            }

            // ✅ نسخ الموظفين
            if ($project->employees && $project->employees->count() > 0) {
                $this->projectRepository->syncEmployees($newProject, $project->employees->pluck('id')->toArray());
            }

            // ✅ نسخ المشاريع الفرعية
            if ($project->subprojects && $project->subprojects->count() > 0) {
                foreach ($project->subprojects as $subproject) {
                    Subproject::create([
                        'project_id' => $newProject->id,
                        'name' => $subproject->name,
                        'start_date' => $subproject->start_date,
                        'end_date' => $subproject->end_date,
                        'total_quantity' => $subproject->total_quantity ?? 0,
                        'unit' => $subproject->unit,
                    ]);
                }
            }

            return $newProject;
        });
    }
}
