<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Client;
use App\Models\WorkItem;
use App\Models\Employee;
use App\Models\ProjectTemplate;
use App\Models\ProjectType;
use App\Models\WorkItemCategory;
use App\Models\ItemStatus;
use App\Models\ProjectItem;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Services\ProjectService;
use App\Repositories\ProjectRepository;
use App\Repositories\EmployeeRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProjectController extends Controller
{
    public function __construct(
        protected ProjectService $projectService,
        protected ProjectRepository $projectRepository,
        protected EmployeeRepository $employeeRepository,
    ) {}

    /**
     * عرض قائمة المشاريع النشطة
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isPrivileged = $user->hasRole('admin') || $user->hasRole('manager');

        $filters = [
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'type'   => $request->input('type'),
            'client' => $request->input('client'),
        ];

        $projects = $this->projectRepository->paginateFiltered(
            $filters,
            $isPrivileged ? null : $user->id,
            false,
            false,
            24
        );

        // Single conditional SQL pass for drafts & archived counts
        $countQuery = Project::query();
        if (!$isPrivileged) {
            $countQuery->whereHas('employees', fn($q) => $q->where('user_id', $user->id));
        }
        $counts = $countQuery->selectRaw('
            SUM(CASE WHEN is_draft = 1 AND is_archived = 0 THEN 1 ELSE 0 END) as drafts_cnt,
            SUM(CASE WHEN is_archived = 1 THEN 1 ELSE 0 END) as archived_cnt
        ')->first();

        $draftsCount   = (int) ($counts->drafts_cnt ?? 0);
        $archivedCount = (int) ($counts->archived_cnt ?? 0);

        $filterTypes   = ProjectType::select('id', 'name')->orderBy('name')->get();
        $filterClients = Client::select('id', 'name')->orderBy('name')->get();

        return view('projects.index', compact('projects', 'draftsCount', 'archivedCount', 'filterTypes', 'filterClients'));
    }

    /**
     * عرض قائمة المسودات
     */
    public function drafts(Request $request)
    {
        $user = Auth::user();
        $isPrivileged = $user->hasRole('admin') || $user->hasRole('manager');

        $drafts = $this->projectRepository->paginateFiltered(
            ['search' => $request->input('search')],
            $isPrivileged ? null : $user->id,
            true,
            false,
            25
        );

        return view('projects.drafts', compact('drafts'));
    }

    /**
     * نموذج إنشاء مشروع جديد
     */
    public function create()
    {
        $clients       = Client::select('id', 'name')->orderBy('name')->get();
        $workItems     = WorkItem::select('id', 'name', 'unit', 'category_id', 'description')->with('category:id,name')->orderBy('name')->get();
        $employees     = $this->employeeRepository->getAll();
        $templates     = ProjectTemplate::select('id', 'name', 'description')->withCount('items')->orderBy('name')->get();
        $projectTypes  = ProjectType::select('id', 'name')->orderBy('name')->get();
        $categories    = WorkItemCategory::select('id', 'name')->orderBy('name')->get();
        $projectItems  = collect([]);
        $subprojects   = collect([]);

        return view('projects.create', compact(
            'clients', 'workItems', 'employees', 'templates',
            'projectTypes', 'categories', 'projectItems', 'subprojects'
        ));
    }

    /**
     * حفظ مشروع جديد
     */
    public function store(StoreProjectRequest $request)
    {
        try {
            $project = $this->projectService->createProject($request->projectData());

            $message = $request->savingAsDraft()
                ? __('general.draft_saved_successfully')
                : __('general.project_created_successfully');

            return redirect()->route('projects.show', $project)->with('success', $message);

        } catch (\Exception $e) {
            return back()->withInput()->with('error', __('general.error_creating_project') . ': ' . $e->getMessage());
        }
    }

    /**
     * عرض تفاصيل المشروع
     */
    public function show(Project $project)
    {
        $project = $this->projectRepository->getProjectWithProgress($project);

        $project->loadMissing([
            'subprojects',
            'items.workItem.category',
            'items.subproject',
            'client',
            'employees',
            'type',
        ]);

        $recentDailyProgress = $project->dailyProgress()
            ->with(['employee:id,name', 'projectItem.workItem:id,name,unit'])
            ->orderByDesc('progress_date')
            ->orderByDesc('daily_progress.id')
            ->limit(10)
            ->get();

        $dailyProgressCount = $project->dailyProgress()->count();

        $itemsBySubproject = $project->items->groupBy(
            fn($item) => $item->subproject_name ?? __('general.without_subproject')
        );

        $overallProgress = $project->overall_progress;

        // جلب المشاريع الفرعية التي لها بنود (عبر Service)
        $subprojectService    = app(\App\Services\ProjectSubprojectService::class);
        $subprojectsWithItems = $subprojectService->getSubprojectsWithItems($project);

        return view('projects.show', compact(
            'project',
            'itemsBySubproject',
            'overallProgress',
            'subprojectsWithItems',
            'recentDailyProgress',
            'dailyProgressCount'
        ));
    }

    /**
     * نموذج تعديل المشروع
     */
    public function edit(Project $project)
    {
        $project->load(['items.workItem.category', 'employees', 'subprojects']);

        $clients      = Client::select('id', 'name')->orderBy('name')->get();
        $workItems    = WorkItem::select('id', 'name', 'unit', 'category_id', 'description')->with('category:id,name')->orderBy('name')->get();
        $employees    = $this->employeeRepository->getAll();
        $templates    = ProjectTemplate::select('id', 'name', 'description')->withCount('items')->orderBy('name')->get();
        $projectTypes = ProjectType::select('id', 'name')->orderBy('name')->get();
        $categories   = WorkItemCategory::select('id', 'name')->orderBy('name')->get();
        $projectItems = $project->items;
        $subprojects  = $project->subprojects;

        Log::info('===== PROJECT EDIT =====', [
            'project_id' => $project->id,
            'items_count' => $projectItems->count(),
        ]);

        return view('projects.edit', compact(
            'project', 'projectItems', 'subprojects', 'clients',
            'workItems', 'employees', 'templates', 'projectTypes', 'categories'
        ));
    }

    /**
     * تحديث المشروع
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        try {
            $wasDraft  = (bool) $project->is_draft;
            $validated = $request->projectData();

            Log::info('===== PROJECT UPDATE REQUEST =====', [
                'project_id'  => $project->id,
                'items_count' => count($validated['items'] ?? []),
                'as_draft'    => $validated['save_as_draft'],
            ]);

            $this->projectService->updateProject($project, $validated);

            // مسح كاش تقارير التقدم لهذا المشروع
            $cacheKeys = Cache::get('project_progress_cache_keys', []);
            foreach ($cacheKeys as $key) {
                if (str_contains($key, "project_progress_{$project->id}_")) {
                    Cache::forget($key);
                }
            }

            $message = match (true) {
                $validated['save_as_draft'] => __('general.draft_saved_successfully'),
                $wasDraft                   => __('general.draft_published_successfully'),
                default                     => __('general.project_updated_successfully'),
            };

            // إذا ضغط "حفظ وإغلاق" يروح للداشبورد، وإلا يفضل في صفحة التعديل
            if ($request->input('redirect_to') === 'show') {
                return redirect()->route('projects.show', $project)->with('success', $message);
            }

            return redirect()->route('projects.edit', $project)->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Project update failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->withInput()->with('error', __('general.error_updating_project') . ': ' . $e->getMessage());
        }
    }

    /**
     * حذف المشروع (Soft Delete)
     */
    public function destroy(Project $project)
    {
        try {
            $this->projectService->deleteProject($project);
            return redirect()->route('projects.index')->with('success', __('general.project_deleted_successfully'));
        } catch (\Exception $e) {
            return back()->with('error', __('general.error_deleting_project') . ': ' . $e->getMessage());
        }
    }

    /**
     * نشر المسودة كمشروع فعلي
     */
    public function publish(Project $project)
    {
        $result = $this->projectService->publishDraft($project);

        if (!$result['success']) {
            return back()->with('error', implode('<br>', $result['errors']));
        }

        return redirect()->route('projects.show', $project)
            ->with('success', __('general.project_published_successfully'));
    }

    /**
     * نسخ المشروع
     */
    public function copy(Request $request, Project $project)
    {
        $request->validate(['name' => 'required|string|max:255']);

        try {
            $newProject = $this->projectService->copyProject($project, $request->name);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success'      => true,
                    'message'      => __('general.project_copied_successfully'),
                    'redirect_url' => route('projects.edit', $newProject),
                ]);
            }

            return redirect()->route('projects.edit', $newProject)
                ->with('success', __('general.project_copied_successfully'));

        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('general.error_copying_project') . ': ' . $e->getMessage(),
                ], 500);
            }
            return back()->with('error', __('general.error_copying_project') . ': ' . $e->getMessage());
        }
    }

    /**
     * حفظ المشروع كقالب
     */
    public function saveAsTemplate(Request $request, Project $project)
    {
        $request->validate([
            'template_name' => 'required|string|max:255',
        ]);

        try {
            $templateService = app(\App\Services\TemplateService::class);
            $template = $templateService->createFromProject($project, $request->template_name);

            return redirect()->route('project-templates.show', $template)
                ->with('success', __('general.template_saved_successfully'));

        } catch (\Exception $e) {
            return back()->with('error', __('general.error_saving_template') . ': ' . $e->getMessage());
        }
    }

    /**
     * تحديث حالة بند في المشروع
     */
    public function updateItemStatus(Request $request, Project $project, ProjectItem $projectItem)
    {
        $request->validate([
            'item_status_id' => 'nullable|exists:item_statuses,id',
        ]);

        if ($projectItem->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => __('general.item_not_belongs_to_project'),
            ], 403);
        }

        $projectItem->update(['item_status_id' => $request->item_status_id ?: null]);
        $projectItem->load('itemStatus');

        return response()->json([
            'success' => true,
            'message' => __('general.item_status_updated_successfully'),
            'item'    => [
                'id'             => $projectItem->id,
                'item_status_id' => $projectItem->item_status_id,
                'item_status'    => $projectItem->itemStatus ? [
                    'id'    => $projectItem->itemStatus->id,
                    'name'  => $projectItem->itemStatus->name,
                    'color' => $projectItem->itemStatus->color,
                    'icon'  => $projectItem->itemStatus->icon,
                ] : null,
            ],
        ]);
    }

    /**
     * جلب بيانات بنود المشروع (JSON للـ AJAX)
     */
    public function getItemsData(Project $project)
    {
        $project->load(['items.workItem.category', 'subprojects']);

        return response()->json([
            'success' => true,
            'items'   => $project->items->map(fn($item) => [
                'id'                 => $item->id,
                'work_item_id'       => $item->work_item_id,
                'name'               => $item->workItem->name ?? '',
                'total_quantity'     => $item->total_quantity,
                'completed_quantity' => $item->completed_quantity,
                'subproject_name'    => $item->subproject_name,
                'unit'               => $item->workItem->unit ?? '',
            ]),
        ]);
    }

    /**
     * جلب تفاصيل قالب (JSON)
     */
    public function getTemplate($templateId)
    {
        try {
            $template = ProjectTemplate::with('items.workItem')->findOrFail($templateId);
            return response()->json(['success' => true, 'template' => $template]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    /**
     * جلب تفاصيل بند عمل (JSON)
     */
    public function getWorkItem($workItemId)
    {
        try {
            $workItem = WorkItem::findOrFail($workItemId);
            return response()->json(['success' => true, 'workItem' => $workItem]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    /**
     * عرض قائمة المشاريع المؤرشفة
     */
    public function archived(Request $request)
    {
        $user = Auth::user();
        $isPrivileged = $user->hasRole('admin') || $user->hasRole('manager');

        $filters = [
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'type'   => $request->input('type'),
            'client' => $request->input('client'),
        ];

        $projects = $this->projectRepository->paginateFiltered(
            $filters,
            $isPrivileged ? null : $user->id,
            false,
            true,
            24
        );

        $filterTypes   = ProjectType::select('id', 'name')->orderBy('name')->get();
        $filterClients = Client::select('id', 'name')->orderBy('name')->get();

        return view('projects.archived', compact('projects', 'filterTypes', 'filterClients'));
    }

    /**
     * أرشفة المشروع
     */
    public function archive(Project $project)
    {
        try {
            $this->projectRepository->archive($project);
            return redirect()->back()->with('success', __('general.project_archived_successfully'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('general.error_archiving_project') . ': ' . $e->getMessage());
        }
    }

    /**
     * استعادة المشروع من الأرشيف
     */
    public function unarchive(Project $project)
    {
        try {
            $this->projectRepository->unarchive($project);
            return redirect()->back()->with('success', __('general.project_unarchived_successfully'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('general.error_unarchiving_project') . ': ' . $e->getMessage());
        }
    }
}
