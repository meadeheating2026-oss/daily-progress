<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectSubprojectService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProjectSubprojectController extends Controller
{
    public function __construct(
        protected ProjectSubprojectService $subprojectService,
    ) {}

    /**
     * جلب المشاريع الفرعية مع نسبة الإنجاز
     */
    public function getSubprojects(Project $project)
    {
        try {
            $project->load([
                'items' => fn($q) => $q->with(['workItem' => fn($q) => $q->withTrashed()]),
                'subprojects',
            ]);

            $subprojectsWithItems = $this->subprojectService->getSubprojectsWithItems($project);
            $withoutSubprojectName = __('general.without_subproject');

            $today            = Carbon::today();
            $projectStartDate = $project->start_date ? Carbon::parse($project->start_date) : $today;
            $projectEndDate   = $project->end_date ? Carbon::parse($project->end_date) : $today->copy()->addMonth();
            $weeklyHolidays   = $project->weekly_holidays ? explode(',', $project->weekly_holidays) : [];

            $subprojects = $subprojectsWithItems->map(function ($subproject) use (
                $project, $withoutSubprojectName, $today, $projectStartDate, $projectEndDate, $weeklyHolidays
            ) {
                $isVirtual = ($subproject->name === $withoutSubprojectName && ($subproject->id === null || !isset($subproject->id)));

                $allItems = $isVirtual
                    ? $project->items->filter(fn($item) => empty($item->subproject_name))->values()
                    : $project->items->where('subproject_name', $subproject->name)->values();

                $measurableItems   = $allItems->filter(fn($item) => $item->is_measurable ?? false);
                $totalQuantity     = $measurableItems->sum('total_quantity');
                $completedQuantity = $measurableItems->sum('completed_quantity');
                $progress          = $totalQuantity > 0 ? round(($completedQuantity / $totalQuantity) * 100, 2) : 0;

                // نسبة الإنجاز بحد أقصى 100%
                $completedQuantityUnder100 = $measurableItems->sum(function ($item) {
                    $itemProgress = ($item->total_quantity ?? 0) > 0
                        ? (($item->completed_quantity ?? 0) / $item->total_quantity) * 100
                        : 0;
                    return $itemProgress > 100 ? $item->total_quantity : $item->completed_quantity ?? 0;
                });

                $progressUnder100 = $totalQuantity > 0
                    ? round(($completedQuantityUnder100 / $totalQuantity) * 100, 2)
                    : 0;

                // التقدم المخطط
                $plannedTotalQty = 0;
                foreach ($measurableItems as $item) {
                    $itemStartDate   = $item->start_date ? Carbon::parse($item->start_date) : $projectStartDate;
                    $dailyPlannedQty = $item->estimated_daily_qty ?? 0;

                    if ($today->gte($itemStartDate)) {
                        $workingDays      = $this->calculateWorkingDays($itemStartDate, $today, $weeklyHolidays);
                        $plannedTotalQty += $dailyPlannedQty > 0
                            ? min($workingDays * $dailyPlannedQty, $item->total_quantity)
                            : 0;
                    }
                }

                $plannedProgress = $totalQuantity > 0
                    ? min(100, round(($plannedTotalQty / $totalQuantity) * 100, 2))
                    : 0;

                $itemsList = $allItems->map(function ($item) {
                    try {
                        $workItem = $item->workItem;
                        return [
                            'id'                 => $item->id,
                            'name'               => $workItem ? $workItem->name : ($item->item_label ?? 'Unknown Item'),
                            'is_measurable'      => $item->is_measurable ?? false,
                            'total_quantity'     => $item->total_quantity ?? 0,
                            'completed_quantity' => $item->completed_quantity ?? 0,
                            'unit'               => $workItem ? ($workItem->unit ?? '') : '',
                            'progress'           => ($item->total_quantity ?? 0) > 0
                                ? round((($item->completed_quantity ?? 0) / $item->total_quantity) * 100, 2)
                                : 0,
                        ];
                    } catch (\Exception $e) {
                        Log::error('Error processing item in subproject', ['item_id' => $item->id ?? null, 'error' => $e->getMessage()]);
                        return null;
                    }
                })->filter()->values();

                $weight = (float) ($subproject->weight ?? 0);

                return [
                    'id'                             => $subproject->id ?? null,
                    'name'                           => $subproject->name ?? $withoutSubprojectName,
                    'weight'                         => $weight,
                    'progress'                       => $progress,
                    'progress_under_100'             => $progressUnder100,
                    'weighted_progress'              => round($progress * ($weight / 100), 2),
                    'weighted_progress_under_100'    => round($progressUnder100 * ($weight / 100), 2),
                    'planned_progress'               => $plannedProgress,
                    'total_quantity'                 => $totalQuantity,
                    'completed_quantity'             => $completedQuantity,
                    'completed_quantity_under_100'   => $completedQuantityUnder100,
                    'planned_total_quantity'         => $plannedTotalQty,
                    'start_date'                     => $subproject->start_date?->format('Y-m-d'),
                    'end_date'                       => $subproject->end_date?->format('Y-m-d'),
                    'items'                          => $itemsList,
                ];
            })->values();

            return response()->json([
                'success'     => true,
                'subprojects' => $subprojects,
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading subprojects', [
                'project_id' => $project->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error'   => 'Error loading subprojects: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تحديث وزن مشروع فرعي واحد
     */
    public function updateSubprojectWeight(Request $request, Project $project, $subprojectId)
    {
        $request->validate([
            'weight' => 'required|numeric|min:0|max:100',
        ]);

        $subproject         = $project->subprojects()->findOrFail($subprojectId);
        $subproject->weight = $request->weight;
        $subproject->save();

        $items             = $subproject->projectItems;
        $totalQuantity     = $items->sum('total_quantity');
        $completedQuantity = $items->sum('completed_quantity');
        $progress          = $totalQuantity > 0 ? round(($completedQuantity / $totalQuantity) * 100, 2) : 0;
        $weightedProgress  = $progress * ($request->weight / 100);

        return response()->json([
            'success'    => true,
            'message'    => __('general.weight_updated_successfully'),
            'subproject' => [
                'id'               => $subproject->id,
                'name'             => $subproject->name,
                'weight'           => $subproject->weight,
                'progress'         => $progress,
                'weighted_progress' => round($weightedProgress, 2),
            ],
        ]);
    }

    /**
     * تحديث أوزان جميع المشاريع الفرعية دفعة واحدة
     */
    public function updateAllSubprojectsWeight(Request $request, Project $project)
    {
        $request->validate([
            'weights'   => 'required|array',
            'weights.*' => 'required|numeric|min:0|max:100',
        ]);

        $weights     = $request->weights;
        $totalWeight = array_sum($weights);

        if (abs($totalWeight - 100) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => __('general.total_weights_must_equal_100') . ': ' . number_format($totalWeight, 2) . '%',
            ], 422);
        }

        $subprojects  = $this->subprojectService->getSubprojectsWithItems($project);
        $updatedCount = 0;

        foreach ($subprojects as $subproject) {
            if (isset($weights[$subproject->id])) {
                $subproject->weight = $weights[$subproject->id];
                $subproject->save();
                $updatedCount++;
            }
        }

        return response()->json([
            'success'       => true,
            'message'       => __('general.all_weights_updated_successfully') . ' (' . $updatedCount . ' ' . __('general.subprojects') . ')',
            'updated_count' => $updatedCount,
        ]);
    }

    /**
     * حساب عدد أيام العمل بين تاريخين (مع استثناء الإجازات الأسبوعية)
     */
    private function calculateWorkingDays(Carbon $startDate, Carbon $endDate, array $weeklyHolidays = []): int
    {
        if ($startDate->gt($endDate)) return 0;

        $workingDays = 0;
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            if (!in_array($currentDate->dayOfWeek, $weeklyHolidays)) {
                $workingDays++;
            }
            $currentDate->addDay();
        }

        return $workingDays;
    }
}
