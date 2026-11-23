<?php

namespace App\Services;

use App\Models\Project;
use App\Contracts\ProjectChartServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProjectChartService implements ProjectChartServiceInterface
{
    /**
     * الحصول على بيانات المخططات للمشروع (آخر 30 يوم + بيانات البنود)
     */
    public function getProjectChartData(Project $project): array
    {
        $startDate = Carbon::today()->subDays(30)->format('Y-m-d');
        $endDate   = Carbon::today()->format('Y-m-d');

        $dailyProgress = DB::table('daily_progress')
            ->join('project_items', 'daily_progress.project_item_id', '=', 'project_items.id')
            ->where('project_items.project_id', $project->id)
            ->whereBetween('daily_progress.progress_date', [$startDate, $endDate])
            ->whereNull('daily_progress.deleted_at')
            ->select(
                DB::raw('DATE(daily_progress.progress_date) as date'),
                DB::raw('SUM(daily_progress.quantity) as total_quantity')
            )
            ->groupBy(DB::raw('DATE(daily_progress.progress_date)'))
            ->orderBy('date')
            ->get();

        $project->loadMissing(['items.workItem', 'subprojects']);

        $workItemsFlat         = [];
        $completionPercentages = [];
        $weightedRatios        = [];
        $plannedWeightedRatios = [];
        $workItemQuantities    = []; // for grouped-view aggregation
        $groupedData           = [];

        $projectStartDate = $project->start_date ? Carbon::parse($project->start_date) : Carbon::today();
        $projectEndDate   = $project->end_date ? Carbon::parse($project->end_date) : Carbon::today()->addMonth();
        $todayObj         = Carbon::today();
        $weeklyHolidays   = $project->weekly_holidays ? explode(',', $project->weekly_holidays) : [];

        $allItems = $project->items->filter(fn($item) => $item->workItem);

        $itemsBySubproject = $allItems->groupBy(
            fn($item) => $item->subproject_name ?? __('general.without_subproject')
        );

        foreach ($itemsBySubproject as $subprojectName => $subprojectItems) {
            $groupedData[$subprojectName] = ['items' => [], 'labels' => []];

            foreach ($subprojectItems as $item) {
                $label = $item->workItem->name;
                if (!empty($item->notes)) {
                    $label .= ' (' . $item->notes . ')';
                }

                $workItemsFlat[] = $label;

                $percentage = $item->total_quantity > 0
                    ? round(($item->completed_quantity / $item->total_quantity) * 100, 2)
                    : 0;

                $completionPercentages[] = $percentage;
                $weightedRatios[]        = round($percentage, 2);

                $itemStartDate       = $item->start_date ? Carbon::parse($item->start_date) : $projectStartDate;
                $dailyPlannedQty     = $item->estimated_daily_qty ?? 0;
                $plannedTotalQty     = 0;

                if ($todayObj->gte($itemStartDate)) {
                    $workingDays     = $this->calculateWorkingDays($itemStartDate, $todayObj, $weeklyHolidays);
                    $plannedTotalQty = $dailyPlannedQty > 0
                        ? min($workingDays * $dailyPlannedQty, $item->total_quantity)
                        : 0;
                }

                $plannedProgress = $item->total_quantity > 0
                    ? min(100, round(($plannedTotalQty / $item->total_quantity) * 100, 2))
                    : 0;

                $plannedWeightedRatios[] = round($plannedProgress, 2);

                // Store raw quantities keyed by base work item name (no notes/subproject)
                $workItemQuantities[] = [
                    'name'               => $item->workItem->name,
                    'completed_quantity' => (float) $item->completed_quantity,
                    'total_quantity'     => (float) $item->total_quantity,
                    'planned_quantity'   => (float) $plannedTotalQty,
                ];

                $groupedLabel = $subprojectName . ' - ' . $label;
                $groupedData[$subprojectName]['items'][]  = [
                    'label'           => $groupedLabel,
                    'percentage'      => $percentage,
                    'planned_progress' => $plannedProgress,
                ];
                $groupedData[$subprojectName]['labels'][] = $groupedLabel;
            }
        }

        $weeklyProgress = DB::table('daily_progress')
            ->join('project_items', 'daily_progress.project_item_id', '=', 'project_items.id')
            ->where('project_items.project_id', $project->id)
            ->whereBetween('daily_progress.progress_date', [$startDate, $endDate])
            ->whereNull('daily_progress.deleted_at')
            ->select(
                DB::raw('DAYOFWEEK(daily_progress.progress_date) as day_of_week'),
                DB::raw('SUM(daily_progress.quantity) as total_quantity')
            )
            ->groupBy(DB::raw('DAYOFWEEK(daily_progress.progress_date)'))
            ->orderBy('day_of_week')
            ->get()
            ->pluck('total_quantity', 'day_of_week')
            ->toArray();

        $weeklyProgressArray = [];
        for ($i = 1; $i <= 7; $i++) {
            $weeklyProgressArray[] = (float) ($weeklyProgress[$i] ?? 0);
        }

        $dayNames = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];

        return [
            'daily' => $dailyProgress->map(fn($item) => [
                'date'     => Carbon::parse($item->date)->format('Y-m-d'),
                'quantity' => (float) $item->total_quantity,
            ])->toArray(),
            'work_items'              => $workItemsFlat,
            'completion_percentages'  => $completionPercentages,
            'weighted_ratios'         => $weightedRatios,
            'planned_weighted_ratios' => $plannedWeightedRatios,
            'weekly_progress'         => $weeklyProgressArray,
            'day_names'               => $dayNames,
            'work_item_quantities'    => $workItemQuantities,
        ];
    }

    /**
     * بيانات المخططات للمشاريع الفرعية
     */
    public function getSubprojectsChartData(Project $project, $subprojectsWithItems): array
    {
        $labels                = [];
        $completionPercentages = [];
        $weightedRatios        = [];
        $plannedWeightedRatios = [];

        $projectStartDate     = $project->start_date ? Carbon::parse($project->start_date) : Carbon::today();
        $projectEndDate       = $project->end_date ? Carbon::parse($project->end_date) : Carbon::today()->addMonth();
        $todayObj             = Carbon::today();
        $weeklyHolidays       = $project->weekly_holidays ? explode(',', $project->weekly_holidays) : [];
        $withoutSubprojectName = __('general.without_subproject');

        foreach ($subprojectsWithItems as $subproject) {
            $isWithoutSubproject = ($subproject->id === null && $subproject->name === $withoutSubprojectName);

            $subprojectItems = $isWithoutSubproject
                ? $project->items->filter(fn($item) => empty($item->subproject_name))
                : $project->items->where('subproject_name', $subproject->name);

            $measurableItems = $subprojectItems->filter(fn($item) => $item->is_measurable ?? false);

            if ($measurableItems->isEmpty()) {
                continue;
            }

            $totalQuantity     = (float) $measurableItems->sum(fn($it) => max(0, (float) ($it->total_quantity ?? 0)));
            $completedQuantity = (float) $measurableItems->sum(fn($it) => min(max(0, (float) ($it->completed_quantity ?? 0)), max(0, (float) ($it->total_quantity ?? 0))));
            $progress          = $totalQuantity > 0 ? round(($completedQuantity / $totalQuantity) * 100, 2) : 0;

            $plannedTotalQty = 0;
            foreach ($measurableItems as $item) {
                $itemStartDate   = $item->start_date ? Carbon::parse($item->start_date) : $projectStartDate;
                $dailyPlannedQty = $item->estimated_daily_qty ?? 0;

                if ($todayObj->gte($itemStartDate)) {
                    $workingDays     = $this->calculateWorkingDays($itemStartDate, $todayObj, $weeklyHolidays);
                    $plannedTotalQty += $dailyPlannedQty > 0
                        ? min($workingDays * $dailyPlannedQty, $item->total_quantity)
                        : 0;
                }
            }

            $plannedProgress = $totalQuantity > 0
                ? min(100, round(($plannedTotalQty / $totalQuantity) * 100, 2))
                : 0;

            $labels[]                = $subproject->name;
            $completionPercentages[] = $progress;
            $weightedRatios[]        = round($progress, 2);
            $plannedWeightedRatios[] = round($plannedProgress, 2);
        }

        return [
            'labels'                  => $labels,
            'completion_percentages'  => $completionPercentages,
            'weighted_ratios'         => $weightedRatios,
            'planned_weighted_ratios' => $plannedWeightedRatios,
        ];
    }

    /**
     * بيانات المخططات للفئات
     */
    public function getCategoriesChartData(Project $project): array
    {
        $project->load(['items.workItem.category', 'subprojects']);

        $categoriesData = [];
        foreach ($project->items as $item) {
            if ($item->workItem && $item->workItem->category) {
                $categoryName = $item->workItem->category->name;
                if (!isset($categoriesData[$categoryName])) {
                    $categoriesData[$categoryName] = ['total_quantity' => 0, 'completed_quantity' => 0, 'items' => []];
                }
                $categoriesData[$categoryName]['total_quantity']     += $item->total_quantity;
                $categoriesData[$categoryName]['completed_quantity']  += $item->completed_quantity;
                $categoriesData[$categoryName]['items'][]             = $item;
            }
        }

        $uncategorized = $project->items->filter(fn($item) => !$item->workItem || !$item->workItem->category);
        if ($uncategorized->isNotEmpty()) {
            $categoriesData[__('general.uncategorized')] = [
                'total_quantity'     => $uncategorized->sum('total_quantity'),
                'completed_quantity' => $uncategorized->sum('completed_quantity'),
                'items'              => $uncategorized->values()->all(),
            ];
        }

        $projectStartDate = $project->start_date ? Carbon::parse($project->start_date) : Carbon::today();
        $projectEndDate   = $project->end_date ? Carbon::parse($project->end_date) : Carbon::today()->addMonth();
        $todayObj         = Carbon::today();
        $weeklyHolidays   = $project->weekly_holidays ? explode(',', $project->weekly_holidays) : [];

        $labels = $completionPercentages = $weightedRatios = $plannedWeightedRatios = [];

        foreach ($categoriesData as $categoryName => $data) {
            $labels[] = $categoryName;
            $progress = $data['total_quantity'] > 0
                ? round(($data['completed_quantity'] / $data['total_quantity']) * 100, 2)
                : 0;
            $completionPercentages[] = $progress;
            $weightedRatios[]        = round($progress, 2);

            $plannedTotalQty   = 0;
            $categoryTotalQty  = $data['total_quantity'];

            foreach ($data['items'] as $item) {
                $itemStartDate   = $item->start_date ? Carbon::parse($item->start_date) : $projectStartDate;
                $dailyPlannedQty = $item->estimated_daily_qty ?? 0;

                if ($todayObj->gte($itemStartDate)) {
                    $workingDays     = $this->calculateWorkingDays($itemStartDate, $todayObj, $weeklyHolidays);
                    $plannedTotalQty += $dailyPlannedQty > 0
                        ? min($workingDays * $dailyPlannedQty, $item->total_quantity)
                        : 0;
                }
            }

            $plannedProgress         = $categoryTotalQty > 0
                ? min(100, round(($plannedTotalQty / $categoryTotalQty) * 100, 2))
                : 0;
            $plannedWeightedRatios[] = round($plannedProgress, 2);
        }

        return [
            'labels'                  => $labels,
            'completion_percentages'  => $completionPercentages,
            'weighted_ratios'         => $weightedRatios,
            'planned_weighted_ratios' => $plannedWeightedRatios,
        ];
    }

    /**
     * بيانات المخططات لبنود كل مشروع فرعي
     */
    public function getSubprojectItemsChartData(Project $project, $subprojectsWithItems): array
    {
        $projectStartDate = $project->start_date ? Carbon::parse($project->start_date) : Carbon::today();
        $projectEndDate   = $project->end_date ? Carbon::parse($project->end_date) : Carbon::today()->addMonth();
        $todayObj         = Carbon::today();
        $weeklyHolidays   = $project->weekly_holidays ? explode(',', $project->weekly_holidays) : [];
        $dataBySubproject = [];

        foreach ($subprojectsWithItems as $subproject) {
            $subprojectItems = $project->items->where('subproject_name', $subproject->name)
                ->filter(fn($item) => $item->workItem);

            [$labels, $completionPercentages, $weightedRatios, $plannedWeightedRatios, $isMeasurable] = $this->buildItemChartArrays(
                $subprojectItems, $projectStartDate, $todayObj, $weeklyHolidays
            );

            $dataBySubproject[$subproject->name] = [
                'labels'                  => $labels,
                'completion_percentages'  => $completionPercentages,
                'weighted_ratios'         => $weightedRatios,
                'planned_weighted_ratios' => $plannedWeightedRatios,
                'is_measurable'           => $isMeasurable,
            ];
        }

        $itemsWithoutSubproject = $project->items->whereNull('subproject_name')->filter(fn($i) => $i->workItem);
        if ($itemsWithoutSubproject->isNotEmpty()) {
            [$labels, $completionPercentages, $weightedRatios, $plannedWeightedRatios, $isMeasurable] = $this->buildItemChartArrays(
                $itemsWithoutSubproject, $projectStartDate, $todayObj, $weeklyHolidays
            );
            $dataBySubproject[__('general.without_subproject')] = [
                'labels'                  => $labels,
                'completion_percentages'  => $completionPercentages,
                'weighted_ratios'         => $weightedRatios,
                'planned_weighted_ratios' => $plannedWeightedRatios,
                'is_measurable'           => $isMeasurable,
            ];
        }

        return $dataBySubproject;
    }

    /**
     * بيانات المخططات لبنود كل فئة
     */
    public function getCategoryItemsChartData(Project $project): array
    {
        $project->load(['items.workItem.category']);

        $projectStartDate = $project->start_date ? Carbon::parse($project->start_date) : Carbon::today();
        $projectEndDate   = $project->end_date ? Carbon::parse($project->end_date) : Carbon::today()->addMonth();
        $todayObj         = Carbon::today();
        $weeklyHolidays   = $project->weekly_holidays ? explode(',', $project->weekly_holidays) : [];

        $itemsByCategory = $project->items->groupBy(
            fn($item) => $item->workItem && $item->workItem->category
                ? $item->workItem->category->name
                : __('general.uncategorized')
        );

        $dataByCategory = [];
        foreach ($itemsByCategory as $categoryName => $items) {
            $labels = $completionPercentages = $weightedRatios = $plannedWeightedRatios = [];

            foreach ($items as $item) {
                if (!$item->workItem) continue;

                $label = $item->workItem->name;
                if (!empty($item->subproject_name)) $label .= ' [' . $item->subproject_name . ']';
                if (!empty($item->notes))           $label .= ' (' . $item->notes . ')';
                $labels[] = $label;

                $percentage = $item->total_quantity > 0
                    ? round(($item->completed_quantity / $item->total_quantity) * 100, 2)
                    : 0;
                $completionPercentages[] = $percentage;
                $weightedRatios[]        = round($percentage, 2);

                $itemStartDate   = $item->start_date ? Carbon::parse($item->start_date) : $projectStartDate;
                $dailyPlannedQty = $item->estimated_daily_qty ?? 0;
                $plannedTotalQty = 0;

                if ($todayObj->gte($itemStartDate)) {
                    $workingDays     = $this->calculateWorkingDays($itemStartDate, $todayObj, $weeklyHolidays);
                    $plannedTotalQty = $dailyPlannedQty > 0
                        ? min($workingDays * $dailyPlannedQty, $item->total_quantity)
                        : 0;
                }

                $plannedProgress         = $item->total_quantity > 0
                    ? min(100, round(($plannedTotalQty / $item->total_quantity) * 100, 2))
                    : 0;
                $plannedWeightedRatios[] = round($plannedProgress, 2);
            }

            $dataByCategory[$categoryName] = [
                'labels'                  => $labels,
                'completion_percentages'  => $completionPercentages,
                'weighted_ratios'         => $weightedRatios,
                'planned_weighted_ratios' => $plannedWeightedRatios,
            ];
        }

        return $dataByCategory;
    }

    /**
     * Helper: بناء مصفوفات بيانات المخطط لمجموعة بنود
     */
    private function buildItemChartArrays($items, $projectStartDate, $todayObj, array $weeklyHolidays): array
    {
        $labels = $completionPercentages = $weightedRatios = $plannedWeightedRatios = $isMeasurable = [];

        foreach ($items as $item) {
            $label = $item->workItem->name;
            if (!empty($item->notes)) $label .= ' (' . $item->notes . ')';
            $labels[] = $label;

            $isMeasurable[] = $item->is_measurable ?? false;

            $percentage = $item->total_quantity > 0
                ? round(($item->completed_quantity / $item->total_quantity) * 100, 2)
                : 0;
            $completionPercentages[] = $percentage;
            $weightedRatios[]        = round($percentage, 2);

            $itemStartDate   = $item->start_date ? Carbon::parse($item->start_date) : $projectStartDate;
            $dailyPlannedQty = $item->estimated_daily_qty ?? 0;
            $plannedTotalQty = 0;

            if ($todayObj->gte($itemStartDate)) {
                $workingDays     = $this->calculateWorkingDays($itemStartDate, $todayObj, $weeklyHolidays);
                $plannedTotalQty = $dailyPlannedQty > 0
                    ? min($workingDays * $dailyPlannedQty, $item->total_quantity)
                    : 0;
            }

            $plannedProgress         = $item->total_quantity > 0
                ? min(100, round(($plannedTotalQty / $item->total_quantity) * 100, 2))
                : 0;
            $plannedWeightedRatios[] = round($plannedProgress, 2);
        }

        return [$labels, $completionPercentages, $weightedRatios, $plannedWeightedRatios, $isMeasurable];
    }

    /**
     * حساب عدد أيام العمل الفعلية بين تاريخين (مع استثناء العطلات الأسبوعية)
     * O(1) لأسابيع كاملة + O(≤6) للباقي بدل حلقة يوم بيوم.
     */
    public function calculateWorkingDays(Carbon $startDate, Carbon $endDate, array $weeklyHolidays = []): int
    {
        if ($startDate->gt($endDate)) {
            return 0;
        }

        $holidays = array_values(array_unique(array_map('intval', $weeklyHolidays)));
        $totalDays = $startDate->diffInDays($endDate) + 1;

        if ($holidays === []) {
            return $totalDays;
        }

        $workingPerWeek = 7 - count($holidays);
        $fullWeeks = intdiv($totalDays, 7);
        $workingDays = $fullWeeks * $workingPerWeek;

        $currentDate = $startDate->copy()->addDays($fullWeeks * 7);
        while ($currentDate->lte($endDate)) {
            if (!in_array($currentDate->dayOfWeek, $holidays, true)) {
                $workingDays++;
            }
            $currentDate->addDay();
        }

        return $workingDays;
    }

    /**
     * معلومات حالة المشروع مع الألوان والأيقونات
     */
    public function getProjectStatusInfo(string $status): array
    {
        $statuses = [
            'active'    => ['color' => 'success', 'icon' => 'play-circle',  'message' => __('general.active')],
            'completed' => ['color' => 'primary',  'icon' => 'check-circle', 'message' => __('general.completed')],
            'pending'   => ['color' => 'warning',  'icon' => 'clock',        'message' => __('general.pending')],
            'suspended' => ['color' => 'danger',   'icon' => 'pause-circle', 'message' => __('general.suspended')],
        ];

        return $statuses[$status] ?? [
            'color'   => 'secondary',
            'icon'    => 'question-circle',
            'message' => __('general.unknown'),
        ];
    }
}
