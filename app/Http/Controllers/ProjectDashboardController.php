<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ItemStatus;
use App\Services\ProjectChartService;
use App\Services\ProjectSubprojectService;
use App\Repositories\ProjectRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\DailyProgress;

class ProjectDashboardController extends Controller
{
    public function __construct(
        protected ProjectRepository $projectRepository,
        protected ProjectChartService $chartService,
        protected ProjectSubprojectService $subprojectService,
    ) {}

    /**
     * عرض لوحة تحكم المشروع
     */
    public function dashboard(Project $project, Request $request)
    {
        $project->load([
            'subprojects',
            'items.workItem.category',
            'items.subproject',
            'client',
            'employees' => fn($q) => $q->withSum([
                'dailyProgress as project_progress_sum' => fn($dq) => $dq->whereHas(
                    'projectItem',
                    fn($pq) => $pq->where('project_id', $project->id)
                ),
            ], 'quantity'),
            'type',
        ]);

        $subprojectsWithItems = $this->subprojectService->getSubprojectsWithItems($project);
        $itemsBySubproject    = $this->buildItemsBySubproject($project);
        $hierarchicalData     = $this->buildHierarchicalData($project, $subprojectsWithItems);

        $overallProgress       = $project->overall_progress;
        $totalItems            = $project->items->count();
        $totalEmployees        = $project->employees->count();
        $today                 = Carbon::today();
        $projectStartDate      = $project->start_date ? Carbon::parse($project->start_date) : $today;
        $projectEndDate        = $project->end_date ? Carbon::parse($project->end_date) : $today->copy()->addMonth();
        $daysPassed            = $today->gte($projectStartDate) ? $projectStartDate->diffInDays($today) : 0;
        $daysRemaining         = $today->lte($projectEndDate) ? $today->diffInDays($projectEndDate) : 0;

        $plannedProgressUntilToday = $this->calculatePlannedProgress($project, $subprojectsWithItems);
        $actualProgressUntilToday  = $overallProgress;
        $progressDifference        = $actualProgressUntilToday - $plannedProgressUntilToday;

        $recentProgress = $this->getRecentProgress($project);

        $chartData               = $this->chartService->getProjectChartData($project);
        $subprojectsChartData    = $this->chartService->getSubprojectsChartData($project, $subprojectsWithItems);
        $categoriesChartData     = $this->chartService->getCategoriesChartData($project);
        $subprojectItemsChartData = $this->chartService->getSubprojectItemsChartData($project, $subprojectsWithItems);
        $categoryItemsChartData  = $this->chartService->getCategoryItemsChartData($project);
        $projectStatus           = $this->chartService->getProjectStatusInfo($project->status);
        $itemStatuses            = ItemStatus::active()->ordered()->get();

        return view('projects.dashboard', compact(
            'project', 'itemsBySubproject', 'hierarchicalData', 'overallProgress',
            'totalItems', 'totalEmployees', 'daysPassed', 'daysRemaining',
            'recentProgress', 'chartData', 'subprojectsChartData', 'categoriesChartData',
            'subprojectItemsChartData', 'categoryItemsChartData', 'projectStatus',
            'plannedProgressUntilToday', 'actualProgressUntilToday', 'progressDifference',
            'today', 'subprojectsWithItems', 'itemStatuses'
        ));
    }

    /**
     * عرض نسخة الطباعة من لوحة التحكم
     */
    public function dashboardPrint(Project $project, Request $request)
    {
        $project->load([
            'subprojects',
            'items.workItem.category',
            'items.subproject',
            'client',
            'employees' => fn($q) => $q->withSum([
                'dailyProgress as project_progress_sum' => fn($dq) => $dq->whereHas(
                    'projectItem',
                    fn($pq) => $pq->where('project_id', $project->id)
                ),
            ], 'quantity'),
            'type',
        ]);

        $visibleComponents = $request->get('components', []);
        if (is_string($visibleComponents)) {
            $visibleComponents = json_decode($visibleComponents, true) ?? [];
        }

        $defaultOrder = [
            'statistics-cards', 'total-weighted-progress',
            'bar-chart', 'subprojects-chart', 'categories-chart',
            'subproject-items-chart', 'category-items-chart',
            'hierarchical-view', 'work-items-progress',
            'recent-activity', 'timeline', 'client-info', 'employees-info',
        ];
        $orderParam = $request->get('order', []);
        if (is_string($orderParam)) {
            $orderParam = json_decode($orderParam, true) ?? [];
        }
        $sectionsOrder = is_array($orderParam) && count($orderParam) > 0 ? $orderParam : $defaultOrder;
        // Append any IDs not already present (future-proofing)
        foreach ($defaultOrder as $id) {
            if (!in_array($id, $sectionsOrder)) {
                $sectionsOrder[] = $id;
            }
        }

        $subprojectsWithItems  = $this->subprojectService->getSubprojectsWithItems($project);
        $itemsBySubproject     = $this->buildItemsBySubproject($project);
        $hierarchicalData      = $this->buildHierarchicalData($project, $subprojectsWithItems);

        $overallProgress  = $project->overall_progress;
        $totalItems       = $project->items->count();
        $totalEmployees   = $project->employees->count();
        $today            = Carbon::today();
        $projectStartDate = $project->start_date ? Carbon::parse($project->start_date) : $today;
        $projectEndDate   = $project->end_date ? Carbon::parse($project->end_date) : $today->copy()->addMonth();
        $daysPassed       = $today->gte($projectStartDate) ? $projectStartDate->diffInDays($today) : 0;
        $daysRemaining    = $today->lte($projectEndDate) ? $today->diffInDays($projectEndDate) : 0;

        // حساب التقدم المخطط البسيط للطباعة
        $plannedProgressUntilToday = 0;
        if ($project->start_date && $project->end_date) {
            $totalDays = $projectStartDate->diffInDays($projectEndDate);
            if ($totalDays > 0) {
                if ($today->greaterThanOrEqualTo($projectEndDate)) {
                    $plannedProgressUntilToday = 100;
                } elseif ($today->greaterThanOrEqualTo($projectStartDate)) {
                    $plannedProgressUntilToday = min(100, round(($daysPassed / $totalDays) * 100, 2));
                }
            }
        }

        $actualProgressUntilToday = $overallProgress;
        $progressDifference       = $actualProgressUntilToday - $plannedProgressUntilToday;

        $recentProgress = $this->getRecentProgress($project);

        $chartData                = $this->chartService->getProjectChartData($project);
        $subprojectsChartData     = $this->chartService->getSubprojectsChartData($project, $subprojectsWithItems);
        $categoriesChartData      = $this->chartService->getCategoriesChartData($project);
        $subprojectItemsChartData = $this->chartService->getSubprojectItemsChartData($project, $subprojectsWithItems);
        $categoryItemsChartData   = $this->chartService->getCategoryItemsChartData($project);
        $projectStatus            = $this->chartService->getProjectStatusInfo($project->status);
        $itemStatuses             = ItemStatus::active()->ordered()->get();

        [$totalWeightedProgress, $totalPlannedWeightedProgress] = $this->calculateWeightedProgressForPrint(
            $project, $subprojectsWithItems
        );

        return view('projects.dashboard-print', compact(
            'project', 'itemsBySubproject', 'hierarchicalData', 'overallProgress',
            'totalItems', 'totalEmployees', 'daysPassed', 'daysRemaining',
            'recentProgress', 'chartData', 'subprojectsChartData', 'categoriesChartData',
            'subprojectItemsChartData', 'categoryItemsChartData', 'projectStatus',
            'plannedProgressUntilToday', 'actualProgressUntilToday', 'progressDifference',
            'today', 'subprojectsWithItems', 'itemStatuses', 'visibleComponents',
            'totalWeightedProgress', 'totalPlannedWeightedProgress', 'sectionsOrder'
        ));
    }

    /* ──────────────────────────── Private Helpers ──────────────────────────── */

    private function buildItemsBySubproject(Project $project): \Illuminate\Support\Collection
    {
        return $project->items->groupBy(
            fn($item) => $item->subproject_name ?? __('general.without_subproject')
        );
    }

    private function buildHierarchicalData(Project $project, $subprojectsWithItems): array
    {
        $hierarchicalData      = [];
        $withoutSubprojectName = __('general.without_subproject');

        foreach ($subprojectsWithItems as $subproject) {
            $isVirtual      = ($subproject->name === $withoutSubprojectName);
            $subprojectItems = $isVirtual
                ? $project->items->filter(fn($item) => empty($item->subproject_name))->values()
                : $project->items->where('subproject_name', $subproject->name)->values();

            if ($subprojectItems->isEmpty()) continue;

            $totalQuantity     = $subprojectItems->sum('total_quantity');
            $completedQuantity = $subprojectItems->sum('completed_quantity');
            $progress          = $totalQuantity > 0 ? round(($completedQuantity / $totalQuantity) * 100, 2) : 0;

            $itemsByCategory  = $subprojectItems->groupBy(
                fn($item) => $item->workItem->category->name ?? __('general.uncategorized')
            );

            $categoriesData = [];
            foreach ($itemsByCategory as $categoryName => $categoryItems) {
                $catTotal     = $categoryItems->sum('total_quantity');
                $catCompleted = $categoryItems->sum('completed_quantity');
                $catProgress  = $catTotal > 0 ? round(($catCompleted / $catTotal) * 100, 2) : 0;
                $categoriesData[$categoryName] = [
                    'name'               => $categoryName,
                    'items'              => $categoryItems->values(),
                    'progress'           => $catProgress,
                    'total_quantity'     => $catTotal,
                    'completed_quantity' => $catCompleted,
                    'count'              => $categoryItems->count(),
                ];
            }

            $hierarchicalData[$subproject->name] = [
                'subproject'         => $subproject,
                'progress'           => $progress,
                'total_quantity'     => $totalQuantity,
                'completed_quantity' => $completedQuantity,
                'weight'             => $subproject->weight ?? 0,
                'categories'         => $categoriesData,
            ];
        }

        return $hierarchicalData;
    }

    private function getRecentProgress(Project $project): \Illuminate\Support\Collection
    {
        return DailyProgress::whereHas('projectItem', fn($q) => $q->where('project_id', $project->id))
            ->with(['projectItem.workItem.category', 'employee'])
            ->orderBy('progress_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(150)
            ->get()
            ->groupBy(function ($progress) {
                $date = $progress->progress_date;
                return $date instanceof Carbon
                    ? $date->format('Y-m-d')
                    : Carbon::parse($date)->format('Y-m-d');
            });
    }

    private function calculatePlannedProgress(Project $project, $subprojectsWithItems): float
    {
        $today            = Carbon::today();
        $projectStartDate = $project->start_date ? Carbon::parse($project->start_date) : $today;
        $weeklyHolidays   = $project->weekly_holidays ? explode(',', $project->weekly_holidays) : [];

        if ($project->subprojects->count() > 0 && $project->subprojects->where('weight', '>', 0)->count() > 0) {
            // Weighted Logic
            $plannedProgress = 0;
            foreach ($subprojectsWithItems as $subproject) {
                if ($subproject->weight <= 0) continue;

                $measurableItems = $project->items
                    ->where('subproject_name', $subproject->name)
                    ->filter(fn($item) => $item->is_measurable ?? false);

                if ($measurableItems->isEmpty()) continue;

                $subTotalQty    = $measurableItems->sum('total_quantity');
                $subPlannedQty  = 0;

                foreach ($measurableItems as $item) {
                    $itemStartDate   = $item->start_date ? Carbon::parse($item->start_date) : $projectStartDate;
                    $dailyPlannedQty = $item->estimated_daily_qty ?? 0;

                    if ($today->gte($itemStartDate)) {
                        $workingDays   = $this->chartService->calculateWorkingDays($itemStartDate, $today, $weeklyHolidays);
                        $subPlannedQty += $dailyPlannedQty > 0
                            ? min($workingDays * $dailyPlannedQty, $item->total_quantity)
                            : 0;
                    }
                }

                $subPlannedProgress = $subTotalQty > 0 ? ($subPlannedQty / $subTotalQty) * 100 : 0;
                $plannedProgress   += $subPlannedProgress * ($subproject->weight / 100);
            }
            return round($plannedProgress, 2);
        }

        // Unweighted Logic
        $measurableItems  = $project->items->filter(fn($item) => $item->is_measurable ?? false);
        $totalQty         = $measurableItems->sum('total_quantity');
        $totalPlannedQty  = 0;

        foreach ($measurableItems as $item) {
            $itemStartDate   = $item->start_date ? Carbon::parse($item->start_date) : $projectStartDate;
            $dailyPlannedQty = $item->estimated_daily_qty ?? 0;

            if ($today->gte($itemStartDate)) {
                $workingDays     = $this->chartService->calculateWorkingDays($itemStartDate, $today, $weeklyHolidays);
                $totalPlannedQty += $dailyPlannedQty > 0
                    ? min($workingDays * $dailyPlannedQty, $item->total_quantity)
                    : 0;
            }
        }

        return $totalQty > 0 ? min(100, round(($totalPlannedQty / $totalQty) * 100, 2)) : 0;
    }

    private function calculateWeightedProgressForPrint(Project $project, $subprojectsWithItems): array
    {
        $today            = Carbon::today();
        $projectStartDate = $project->start_date ? Carbon::parse($project->start_date) : $today;
        $weeklyHolidays   = $project->weekly_holidays ? explode(',', $project->weekly_holidays) : [];

        $totalWeightedProgress        = null;
        $totalPlannedWeightedProgress = null;

        if ($project->subprojects->count() > 0 && $project->subprojects->where('weight', '>', 0)->count() > 0) {
            $totalWeightedProgress        = $project->overall_progress;
            $totalPlannedWeightedProgress = 0;

            foreach ($subprojectsWithItems as $subproject) {
                if ($subproject->weight <= 0) continue;

                $measurableItems = $project->items
                    ->where('subproject_name', $subproject->name)
                    ->filter(fn($item) => $item->is_measurable ?? false);

                if ($measurableItems->isEmpty()) continue;

                $subTotalQty   = $measurableItems->sum('total_quantity');
                $subPlannedQty = 0;

                foreach ($measurableItems as $item) {
                    $itemStartDate   = $item->start_date ? Carbon::parse($item->start_date) : $projectStartDate;
                    $dailyPlannedQty = $item->estimated_daily_qty ?? 0;

                    if ($today->gte($itemStartDate)) {
                        $workingDays   = $this->chartService->calculateWorkingDays($itemStartDate, $today, $weeklyHolidays);
                        $subPlannedQty += $dailyPlannedQty > 0
                            ? min($workingDays * $dailyPlannedQty, $item->total_quantity)
                            : 0;
                    }
                }

                $subPlannedProgress           = $subTotalQty > 0 ? ($subPlannedQty / $subTotalQty) * 100 : 0;
                $totalPlannedWeightedProgress += $subPlannedProgress * ($subproject->weight / 100);
            }

            $totalPlannedWeightedProgress = round($totalPlannedWeightedProgress, 2);
        } else {
            // Classic Mode
            $measurableItems  = $project->items->filter(fn($item) => $item->is_measurable ?? false);
            $totalQty         = $measurableItems->sum('total_quantity');
            $totalCompletedQty = $measurableItems->sum('completed_quantity');
            $totalPlannedQty  = 0;

            foreach ($measurableItems as $item) {
                $itemStartDate   = $item->start_date ? Carbon::parse($item->start_date) : $projectStartDate;
                $dailyPlannedQty = $item->estimated_daily_qty ?? 0;

                if ($today->gte($itemStartDate)) {
                    $workingDays     = $this->chartService->calculateWorkingDays($itemStartDate, $today, $weeklyHolidays);
                    $totalPlannedQty += $dailyPlannedQty > 0
                        ? min($workingDays * $dailyPlannedQty, $item->total_quantity)
                        : 0;
                }
            }

            $totalWeightedProgress        = $totalQty > 0 ? min(100, round(($totalCompletedQty / $totalQty) * 100, 2)) : 0;
            $totalPlannedWeightedProgress = $totalQty > 0 ? min(100, round(($totalPlannedQty / $totalQty) * 100, 2)) : 0;
        }

        return [$totalWeightedProgress, $totalPlannedWeightedProgress];
    }
}
