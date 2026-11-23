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
     * جلب المشاريع الفرعية مع نسبة الإنجاز الموحدة (سقف 100% لكل بند قابل للقياس)
     */
    public function getSubprojects(Project $project)
    {
        try {
            $project->load([
                'items' => fn($q) => $q->with(['workItem' => fn($q) => $q->withTrashed()]),
                'subprojects',
            ]);

            $subprojectsWithItems  = $this->subprojectService->getSubprojectsWithItems($project);
            $withoutSubprojectName = __('general.without_subproject');

            $today            = Carbon::today();
            $projectStartDate = $project->start_date ? Carbon::parse($project->start_date) : $today;
            $weeklyHolidays   = $project->weekly_holidays ? explode(',', $project->weekly_holidays) : [];

            $subprojects = $subprojectsWithItems->map(function ($subproject) use (
                $project, $withoutSubprojectName, $today, $projectStartDate, $weeklyHolidays
            ) {
                $isWithoutBucket = ($subproject->name === $withoutSubprojectName);

                $allItems = $isWithoutBucket
                    ? $project->items->filter(fn($item) => empty($item->subproject_name))->values()
                    : $project->items->where('subproject_name', $subproject->name)->values();

                $measurableItems = $allItems->filter(fn($item) => (bool) ($item->is_measurable ?? false));

                $totalQuantity = round((float) $measurableItems->sum(fn($it) => max(0, (float) ($it->total_quantity ?? 0))), 2);
                $rawCompletedQuantity = round((float) $measurableItems->sum(fn($it) => max(0, (float) ($it->completed_quantity ?? 0))), 2);

                // إيقاف مساهمة أي بند عند سقف 100% من كميته الكلية (حتى لا تغطي الزيادة في بند على بند آخر لم يُنفذ)
                $cappedCompletedQuantity = round((float) $measurableItems->sum(function ($item) {
                    $tot  = max(0, (float) ($item->total_quantity ?? 0));
                    $comp = max(0, (float) ($item->completed_quantity ?? 0));
                    return min($comp, $tot);
                }), 2);

                $progress = $totalQuantity > 0
                    ? round(($cappedCompletedQuantity / $totalQuantity) * 100, 2)
                    : 0.0;

                $rawProgress = $totalQuantity > 0
                    ? round(($rawCompletedQuantity / $totalQuantity) * 100, 2)
                    : 0.0;

                // التقدم المخطط (سقف 100% لكل بند)
                $plannedTotalQty = 0.0;
                foreach ($measurableItems as $item) {
                    $itemStartDate   = $item->start_date ? Carbon::parse($item->start_date) : $projectStartDate;
                    $dailyPlannedQty = (float) ($item->estimated_daily_qty ?? 0);

                    if ($today->gte($itemStartDate)) {
                        $workingDays      = $this->calculateWorkingDays($itemStartDate, $today, $weeklyHolidays);
                        $plannedTotalQty += $dailyPlannedQty > 0
                            ? min($workingDays * $dailyPlannedQty, max(0, (float) ($item->total_quantity ?? 0)))
                            : 0.0;
                    }
                }
                $plannedTotalQty = round($plannedTotalQty, 2);

                $plannedProgress = $totalQuantity > 0
                    ? min(100.0, round(($plannedTotalQty / $totalQuantity) * 100, 2))
                    : 0.0;

                $itemsList = $allItems->map(function ($item) {
                    try {
                        $workItem = $item->workItem;
                        $tot  = round(max(0, (float) ($item->total_quantity ?? 0)), 2);
                        $comp = round(max(0, (float) ($item->completed_quantity ?? 0)), 2);
                        $cappedComp = min($comp, $tot);
                        $itemPct = $tot > 0 ? round(($cappedComp / $tot) * 100, 2) : 0.0;
                        $rawPct  = $tot > 0 ? round(($comp / $tot) * 100, 2) : 0.0;

                        return [
                            'id'                      => $item->id,
                            'name'                    => $workItem ? $workItem->name : ($item->item_label ?? 'Unknown Item'),
                            'is_measurable'           => (bool) ($item->is_measurable ?? false),
                            'total_quantity'          => $tot,
                            'completed_quantity'      => $comp,
                            'capped_completed'        => $cappedComp,
                            'unit'                    => $workItem ? ($workItem->unit ?? '') : '',
                            'progress'                => $itemPct,
                            'raw_progress'            => $rawPct,
                        ];
                    } catch (\Exception $e) {
                        Log::error('Error processing item in subproject', ['item_id' => $item->id ?? null, 'error' => $e->getMessage()]);
                        return null;
                    }
                })->filter()->values();

                $weight = round((float) ($subproject->weight ?? 0), 2);
                $weightedProgress = round($progress * ($weight / 100), 2);
                $plannedWeightedProgress = round($plannedProgress * ($weight / 100), 2);

                return [
                    'id'                           => $subproject->id ?? null,
                    'name'                         => $subproject->name ?? $withoutSubprojectName,
                    'weight'                       => $weight,
                    'progress'                     => $progress,
                    'progress_under_100'           => $progress,
                    'raw_progress'                 => $rawProgress,
                    'weighted_progress'            => $weightedProgress,
                    'weighted_progress_under_100'  => $weightedProgress,
                    'planned_progress'             => $plannedProgress,
                    'planned_weighted_progress'    => $plannedWeightedProgress,
                    'total_quantity'               => $totalQuantity,
                    'completed_quantity'           => $cappedCompletedQuantity,
                    'raw_completed_quantity'       => $rawCompletedQuantity,
                    'completed_quantity_under_100' => $cappedCompletedQuantity,
                    'planned_total_quantity'       => $plannedTotalQty,
                    'start_date'                   => $subproject->start_date?->format('Y-m-d'),
                    'end_date'                     => $subproject->end_date?->format('Y-m-d'),
                    'items'                        => $itemsList,
                ];
            })->values();

            $totalWeight = round((float) $subprojects->sum('weight'), 2);
            $overallProgress = $totalWeight > 0
                ? round((float) $subprojects->sum('weighted_progress'), 2)
                : 0.0;
            $overallPlannedProgress = $totalWeight > 0
                ? round((float) $subprojects->sum('planned_weighted_progress'), 2)
                : 0.0;

            return response()->json([
                'success'                  => true,
                'subprojects'              => $subprojects,
                'total_weight'             => $totalWeight,
                'weights_is_zero'          => $totalWeight <= 0.0,
                'weights_is_complete'      => abs($totalWeight - 100.0) <= 0.01,
                'overall_progress'         => $overallProgress,
                'overall_planned_progress' => $overallPlannedProgress,
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
        $subproject->weight = round((float) $request->weight, 2);
        $subproject->save();

        $project->load(['items', 'subprojects']);
        $withoutSubprojectName = __('general.without_subproject');

        $subItems = ($subproject->name === $withoutSubprojectName)
            ? $project->items->filter(fn($it) => empty($it->subproject_name))
            : $project->items->where('subproject_name', $subproject->name);

        $measurable        = $subItems->filter(fn($it) => (bool) ($it->is_measurable ?? false));
        $totalQuantity     = (float) $measurable->sum(fn($it) => max(0, (float) ($it->total_quantity ?? 0)));
        $cappedCompleted   = (float) $measurable->sum(fn($it) => min(max(0, (float) ($it->completed_quantity ?? 0)), max(0, (float) ($it->total_quantity ?? 0))));
        $progress          = $totalQuantity > 0 ? round(($cappedCompleted / $totalQuantity) * 100, 2) : 0.0;
        $weightedProgress  = round($progress * ($subproject->weight / 100), 2);
        $overallProgress   = $project->overall_progress;

        return response()->json([
            'success'          => true,
            'message'          => __('general.weight_updated_successfully'),
            'overall_progress' => $overallProgress,
            'subproject'       => [
                'id'                => $subproject->id,
                'name'              => $subproject->name,
                'weight'            => $subproject->weight,
                'progress'          => $progress,
                'weighted_progress' => $weightedProgress,
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
        $totalWeight = array_sum(array_map('floatval', $weights));

        if (abs($totalWeight - 100) > 0.05) {
            return response()->json([
                'success' => false,
                'message' => __('general.total_weights_must_equal_100') . ': ' . number_format($totalWeight, 2) . '%',
            ], 422);
        }

        $subprojects  = $this->subprojectService->getSubprojectsWithItems($project);
        $updatedCount = 0;

        foreach ($subprojects as $subproject) {
            if ($subproject->id && array_key_exists($subproject->id, $weights)) {
                $subproject->weight = round((float) $weights[$subproject->id], 2);
                $subproject->save();
                $updatedCount++;
            }
        }

        $project->unsetRelation('subprojects');
        $project->load(['items', 'subprojects']);
        $overallProgress = $project->overall_progress;

        return response()->json([
            'success'          => true,
            'message'          => __('general.all_weights_updated_successfully') . ' (' . $updatedCount . ' ' . __('general.subprojects') . ')',
            'updated_count'    => $updatedCount,
            'overall_progress' => $overallProgress,
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
