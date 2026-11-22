<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectItem;
use App\Http\Requests\ProjectProgressRequest;
use App\Services\ProjectSubprojectService;
use App\Services\ProjectChartService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Rap2hpoutre\FastExcel\FastExcel;

class ProjectProgressController extends Controller
{
    public function __construct(
        protected ProjectSubprojectService $subprojectService,
        protected ProjectChartService $chartService,
    ) {}

    /**
     * عرض صفحة التقدم اليومي للمشروع
     */
    public function progress(Project $project, ProjectProgressRequest $request)
    {
        try {
            return view('projects.progress', $this->buildProgressViewData($project, $request));
        } catch (\Exception $e) {
            Log::error('Error in project progress report', [
                'project_id' => $project->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'حدث خطأ أثناء تحميل التقرير. الرجاء المحاولة مرة أخرى.');
        }
    }

    /**
     * نسخة الطباعة المنفصلة لتقرير التقدم
     */
    public function progressPrint(Project $project, ProjectProgressRequest $request)
    {
        try {
            $data = $this->buildProgressViewData($project, $request, forPrint: true);
            $data['isGroupedView'] = $request->boolean('grouped');
            $data['isSubprojectView'] = $request->boolean('subproject_view');
            $data['showNotes'] = $request->boolean('show_notes');
            $data['printItems'] = match(true) {
                $data['isGroupedView']     => $data['groupedItems'],
                $data['isSubprojectView']  => null, // handled separately in the view
                default                    => $data['project']->items,
            };
            $data['colors'] = $this->sanitizePrintColors($request->input('colors'));

            return view('projects.progress-print', $data);
        } catch (\Exception $e) {
            Log::error('Error in project progress print', [
                'project_id' => $project->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'حدث خطأ أثناء تحميل صفحة الطباعة. الرجاء المحاولة مرة أخرى.');
        }
    }

    /**
     * Shared progress payload for screen and print views.
     */
    protected function buildProgressViewData(Project $project, ProjectProgressRequest $request, bool $forPrint = false): array
    {
        $fromDate = $request->getFromDate()->format('Y-m-d');
        $toDate   = $request->getToDate()->format('Y-m-d');
        $asOfDate = $request->getAsOfDate()->format('Y-m-d');

        $data = $this->rememberProgressData($project, $fromDate, $toDate, $asOfDate);
        $data['project']      = $this->applyFilters($data['project'], $request);
        $data['groupedItems'] = $this->groupDuplicateItems($data['project']->items);
        $data['groupedBySubproject'] = $this->groupItemsBySubproject($data['project']->items);
        $data['asOfDate']     = $asOfDate;

        if (!$forPrint) {
            $data['categories']  = \App\Models\WorkItemCategory::orderBy('name')->get();
            $data['subprojects'] = $this->subprojectService->getSubprojectsWithItems($project);
        }

        return $data;
    }

    /**
     * @return array<string, string>
     */
    protected function sanitizePrintColors(mixed $colors): array
    {
        if (is_string($colors)) {
            $colors = json_decode($colors, true);
        }

        if (!is_array($colors)) {
            return [];
        }

        return collect($colors)
            ->filter(fn($value, $key) => is_string($key) && is_string($value) && preg_match('/^#[0-9A-Fa-f]{3,8}$/', $value))
            ->all();
    }

    /**
     * Cache progress metrics via the file store (not database) to avoid
     * max_allowed_packet failures when serializing large Eloquent graphs.
     * Falls back to a fresh calculation if cache read/write fails.
     */
    protected function rememberProgressData(Project $project, string $fromDate, string $toDate, string $asOfDate): array
    {
        $cacheKey = "project_progress_{$project->id}_{$fromDate}_{$toDate}_{$asOfDate}";
        $store    = Cache::store('file');

        try {
            $cached = $store->get($cacheKey);
            if (is_array($cached) && isset($cached['item_metrics'])) {
                return $this->hydrateProgressFromCache($project, $cached, $fromDate, $toDate, $asOfDate);
            }
        } catch (\Throwable $e) {
            Log::warning('Progress cache read failed, recalculating', [
                'project_id' => $project->id,
                'error'      => $e->getMessage(),
            ]);
        }

        $data = $this->calculateProjectProgress($project, $fromDate, $toDate, $asOfDate);

        try {
            $store->put($cacheKey, $this->extractCacheableProgress($data), now()->addMinutes(30));
        } catch (\Throwable $e) {
            Log::warning('Progress cache write failed', [
                'project_id' => $project->id,
                'error'      => $e->getMessage(),
            ]);
        }

        return $data;
    }

    /**
     * Persist only lightweight metrics — never the Eloquent Project graph.
     */
    protected function extractCacheableProgress(array $data): array
    {
        $itemMetrics = [];
        foreach ($data['project']->items as $item) {
            $itemMetrics[$item->id] = [
                'previous_progress'                   => $item->previous_progress,
                'current_progress'                    => $item->current_progress,
                'period_progress'                     => $item->period_progress,
                'total_completed'                     => $item->total_completed,
                'remaining'                           => $item->remaining,
                'progress_start_date'                 => $item->progress_start_date,
                'progress_end_date'                   => $item->progress_end_date,
                'planned_until_from_date'             => $item->planned_until_from_date,
                'planned_today'                       => $item->planned_today,
                'planned_total_quantity'              => $item->planned_total_quantity,
                'remaining_planned'                   => $item->remaining_planned,
                'previous_progress_percentage'        => $item->previous_progress_percentage,
                'current_progress_percentage'         => $item->current_progress_percentage,
                'total_completed_percentage'          => $item->total_completed_percentage,
                'remaining_percentage'                => $item->remaining_percentage,
                'remaining_planned_percentage'        => $item->remaining_planned_percentage,
                'planned_until_from_date_percentage'  => $item->planned_until_from_date_percentage,
                'planned_today_percentage'            => $item->planned_today_percentage,
                'planned_total_percentage'            => $item->planned_total_percentage,
                'previous_qty_class'                  => $item->previous_qty_class,
                'previous_qty_icon'                   => $item->previous_qty_icon,
                'previous_qty_color'                  => $item->previous_qty_color,
                'current_qty_class'                   => $item->current_qty_class,
                'current_qty_icon'                    => $item->current_qty_icon,
                'current_qty_color'                   => $item->current_qty_color,
                'completed_qty_class'                 => $item->completed_qty_class,
                'completed_qty_icon'                  => $item->completed_qty_icon,
                'completed_qty_color'                 => $item->completed_qty_color,
                'remaining_qty_class'                 => $item->remaining_qty_class,
                'remaining_qty_icon'                  => $item->remaining_qty_icon,
                'remaining_qty_color'                 => $item->remaining_qty_color,
            ];
        }

        return [
            'item_metrics'     => $itemMetrics,
            'projectProgress'  => $data['projectProgress'],
            'fromDate'         => $data['fromDate'],
            'toDate'           => $data['toDate'],
            'projectStartDate' => $data['projectStartDate']?->toDateString(),
            'projectEndDate'   => $data['projectEndDate']?->toDateString(),
            'isTodayHoliday'   => $data['isTodayHoliday'],
        ];
    }

    /**
     * Reload project/items fresh and re-apply cached metrics.
     */
    protected function hydrateProgressFromCache(
        Project $project,
        array $cached,
        string $fromDate,
        string $toDate,
        string $asOfDate
    ): array {
        $project->load([
            'client',
            'items' => fn($q) => $q->with('workItem.category'),
        ]);

        $metrics = $cached['item_metrics'];
        foreach ($project->items as $item) {
            if (!isset($metrics[$item->id])) {
                continue;
            }
            foreach ($metrics[$item->id] as $key => $value) {
                $item->{$key} = $value;
            }
        }

        return [
            'project'          => $project,
            'projectProgress'  => $cached['projectProgress'],
            'fromDate'         => $fromDate,
            'toDate'           => $toDate,
            'projectStartDate' => !empty($cached['projectStartDate'])
                ? Carbon::parse($cached['projectStartDate'])
                : ($project->start_date ? Carbon::parse($project->start_date) : Carbon::today()),
            'projectEndDate'   => !empty($cached['projectEndDate'])
                ? Carbon::parse($cached['projectEndDate'])
                : ($project->end_date ? Carbon::parse($project->end_date) : Carbon::today()->addMonth()),
            'isTodayHoliday'   => $cached['isTodayHoliday'] ?? false,
            'asOfDate'         => $asOfDate,
        ];
    }

    /**
     * تصدير بيانات التقدم إلى Excel بنفس التنسيق والألوان
     */
    public function export(Project $project, ProjectProgressRequest $request)
    {
        try {
            $data = $this->buildProgressViewData($project, $request, forPrint: true);
            $data['isGroupedView'] = $request->boolean('grouped');
            $data['isSubprojectView'] = $request->has('subproject_view') 
                ? $request->boolean('subproject_view') 
                : ($data['project']->items->some(fn($i) => filled($i->subproject_name)) && !$data['isGroupedView']);
            $data['showNotes'] = $request->boolean('show_notes');

            $fileName = 'progress_report_' . $project->id . '_' . Carbon::now()->format('Y-m-d') . '.xls';

            $viewContent = view('projects.progress-excel', $data)->render();

            return response($viewContent, 200, [
                'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                'Cache-Control'       => 'max-age=0, no-cache, no-store, must-revalidate',
                'Pragma'              => 'public',
            ]);

        } catch (\Exception $e) {
            Log::error('Error exporting progress to Excel', [
                'project_id' => $project->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return back()->with('error', __('general.error_exporting') . ': ' . $e->getMessage());
        }
    }

    /**
     * حساب بيانات التقدم للمشروع بكفاءة
     */
    protected function calculateProjectProgress(Project $project, string $fromDate, string $toDate, ?string $asOfDate = null): array
    {
        $asOfDateObj = $asOfDate ? Carbon::parse($asOfDate) : Carbon::today();
        $today       = $asOfDateObj->format('Y-m-d');

        $project->load([
            'client',
            'items' => fn($q) => $q->with('workItem.category'),
        ]);

        $itemIds = $project->items->pluck('id');

        $progressData = collect();
        if ($itemIds->isNotEmpty()) {
            $progressData = DB::table('daily_progress')
                ->select('project_item_id')
                ->selectRaw(
                    'SUM(CASE WHEN progress_date < ? THEN quantity ELSE 0 END) as previous_progress',
                    [$today]
                )
                ->selectRaw(
                    'SUM(CASE WHEN progress_date = ? THEN quantity ELSE 0 END) as current_progress',
                    [$today]
                )
                ->selectRaw(
                    'SUM(CASE WHEN progress_date <= ? THEN quantity ELSE 0 END) as total_completed',
                    [$toDate]
                )
                ->selectRaw(
                    'SUM(CASE WHEN progress_date BETWEEN ? AND ? THEN quantity ELSE 0 END) as period_progress',
                    [$fromDate, $toDate]
                )
                ->selectRaw('MIN(progress_date) as progress_start_date')
                ->selectRaw('MAX(progress_date) as progress_end_date')
                ->whereIn('project_item_id', $itemIds)
                ->whereNull('deleted_at')
                ->groupBy('project_item_id')
                ->get()
                ->keyBy('project_item_id');
        }

        $projectStartDate = $project->start_date ? Carbon::parse($project->start_date) : Carbon::today();
        $projectEndDate   = $project->end_date ? Carbon::parse($project->end_date) : Carbon::today()->addMonth();
        $fromDateObj      = Carbon::parse($fromDate);
        $toDateObj        = Carbon::parse($toDate);
        $todayObj         = $asOfDateObj->copy();
        $weeklyHolidays   = $project->weekly_holidays
            ? array_values(array_unique(array_map('intval', explode(',', $project->weekly_holidays))))
            : [];

        $project->items->each(function ($item) use ($project, $progressData, $projectStartDate, $projectEndDate, $fromDateObj, $toDateObj, $todayObj, $weeklyHolidays) {
            $progress = $progressData->get($item->id);

            $item->previous_progress  = $progress ? max((float) $progress->previous_progress, 0) : 0;
            $item->current_progress   = $progress ? max((float) $progress->current_progress, 0) : 0;
            $item->period_progress    = $progress ? max((float) $progress->period_progress, 0) : 0;
            $item->total_completed    = $item->previous_progress + $item->current_progress;
            $item->remaining          = $item->total_quantity - $item->total_completed;

            $item->progress_start_date = $progress && $progress->progress_start_date
                ? Carbon::parse($progress->progress_start_date)->format('Y-m-d')
                : null;
            $item->progress_end_date = $progress && $progress->progress_end_date
                ? Carbon::parse($progress->progress_end_date)->format('Y-m-d')
                : null;

            $itemStartDate      = $item->start_date ? Carbon::parse($item->start_date) : $projectStartDate;
            $itemEndDate        = $item->end_date ? Carbon::parse($item->end_date) : $projectEndDate;
            $dailyPlannedQty    = $item->estimated_daily_qty ?? 0;

            // Previous Planned
            $yesterdayObj = $todayObj->copy()->subDay();
            if ($yesterdayObj->lt($itemStartDate)) {
                $item->planned_until_from_date = 0;
            } else {
                $workingDays = $this->chartService->calculateWorkingDays($itemStartDate, $yesterdayObj, $weeklyHolidays);
                $item->planned_until_from_date = $dailyPlannedQty > 0
                    ? min($workingDays * $dailyPlannedQty, $item->total_quantity)
                    : 0;
            }

            // Current Planned
            $isWorkingDay     = !in_array($todayObj->dayOfWeek, $weeklyHolidays, true);
            $isAfterStartDate = $todayObj->gte($itemStartDate);
            $isBeforeEndDate  = $todayObj->lte($itemEndDate);
            $item->planned_today = ($isWorkingDay && $isAfterStartDate && $isBeforeEndDate) ? $dailyPlannedQty : 0;

            // Completed Planned
            if ($toDateObj->lt($itemStartDate)) {
                $item->planned_total_quantity = 0;
            } else {
                $workingDaysToDate = $this->chartService->calculateWorkingDays($itemStartDate, $toDateObj, $weeklyHolidays);
                $item->planned_total_quantity = $dailyPlannedQty > 0
                    ? min($workingDaysToDate * $dailyPlannedQty, $item->total_quantity)
                    : 0;
            }

            $item->remaining_planned = $item->total_quantity - $item->planned_total_quantity;

            // Percentages
            $tq = $item->total_quantity;
            $item->previous_progress_percentage         = $tq > 0 ? round(($item->previous_progress / $tq) * 100, 2) : 0;
            $item->current_progress_percentage          = $tq > 0 ? round(($item->current_progress / $tq) * 100, 2) : 0;
            $item->total_completed_percentage           = $tq > 0 ? round(($item->total_completed / $tq) * 100, 2) : 0;
            $item->remaining_percentage                 = $tq > 0 ? round(($item->remaining / $tq) * 100, 2) : 0;
            $item->remaining_planned_percentage         = $tq > 0 ? round(($item->remaining_planned / $tq) * 100, 2) : 0;
            $item->planned_until_from_date_percentage   = $tq > 0 ? min(round(($item->planned_until_from_date / $tq) * 100, 2), 100) : 0;
            $item->planned_today_percentage             = $tq > 0 ? min(round(($item->planned_today / $tq) * 100, 2), 100) : 0;
            $item->planned_total_percentage             = $tq > 0 ? min(round(($item->planned_total_quantity / $tq) * 100, 2), 100) : 0;

            // Performance comparison helpers
            $item->previous_qty_class  = $this->comparisonClass($item->previous_progress, $item->planned_until_from_date);
            $item->previous_qty_icon   = $this->comparisonIcon($item->previous_progress, $item->planned_until_from_date);
            $item->previous_qty_color  = $this->comparisonColor($item->previous_progress, $item->planned_until_from_date);

            $item->current_qty_class   = $this->comparisonClass($item->current_progress, $item->planned_today);
            $item->current_qty_icon    = $this->comparisonIcon($item->current_progress, $item->planned_today);
            $item->current_qty_color   = $this->comparisonColor($item->current_progress, $item->planned_today);

            $item->completed_qty_class = $this->comparisonClass($item->total_completed, $item->planned_total_quantity);
            $item->completed_qty_icon  = $this->comparisonIcon($item->total_completed, $item->planned_total_quantity);
            $item->completed_qty_color = $this->comparisonColor($item->total_completed, $item->planned_total_quantity);

            // Remaining: أقل متبقي = أفضل
            $item->remaining_qty_class = $this->comparisonClassReversed($item->remaining, $item->remaining_planned);
            $item->remaining_qty_icon  = $this->comparisonIconReversed($item->remaining, $item->remaining_planned);
            $item->remaining_qty_color = $this->comparisonColorReversed($item->remaining, $item->remaining_planned);
        });

        $measurableItems      = $project->items->filter(fn($item) => $item->is_measurable ?? false);
        $totalProjectQty      = $measurableItems->sum('total_quantity');
        $totalCompletedQty    = $measurableItems->sum('total_completed');
        $projectProgress      = $totalProjectQty > 0
            ? min(round(($totalCompletedQty / $totalProjectQty) * 100, 2), 100)
            : 0;
        $weeklyHolidaysArr    = $project->weekly_holidays
            ? array_values(array_unique(array_map('intval', explode(',', $project->weekly_holidays))))
            : [];
        $isTodayHoliday       = in_array(Carbon::today()->dayOfWeek, $weeklyHolidaysArr, true);

        return [
            'project'          => $project,
            'projectProgress'  => $projectProgress,
            'fromDate'         => $fromDate,
            'toDate'           => $toDate,
            'projectStartDate' => $projectStartDate,
            'isTodayHoliday'   => $isTodayHoliday,
            'projectEndDate'   => $projectEndDate,
        ];
    }

    /**
     * تطبيق الفلاتر على بنود المشروع
     */
    private function applyFilters($project, $request)
    {
        $items = $project->items;

        if ($request->filled('category_id')) {
            $items = $items->filter(fn($item) => $item->workItem && $item->workItem->category_id == $request->category_id);
        }

        if ($request->filled('subproject_name')) {
            $name  = $request->subproject_name;
            $items = $items->filter(fn($item) => $name === 'null' ? empty($item->subproject_name) : $item->subproject_name == $name);
        }

        if ($request->filled('start_date_from')) {
            $d = Carbon::parse($request->start_date_from);
            $items = $items->filter(fn($i) => $i->start_date && Carbon::parse($i->start_date)->gte($d));
        }

        if ($request->filled('start_date_to')) {
            $d = Carbon::parse($request->start_date_to);
            $items = $items->filter(fn($i) => $i->start_date && Carbon::parse($i->start_date)->lte($d));
        }

        if ($request->filled('end_date_from')) {
            $d = Carbon::parse($request->end_date_from);
            $items = $items->filter(fn($i) => ($i->end_date ?? $i->planned_end_date) && Carbon::parse($i->end_date ?? $i->planned_end_date)->gte($d));
        }

        if ($request->filled('end_date_to')) {
            $d = Carbon::parse($request->end_date_to);
            $items = $items->filter(fn($i) => ($i->end_date ?? $i->planned_end_date) && Carbon::parse($i->end_date ?? $i->planned_end_date)->lte($d));
        }

        if ($request->filled('remaining_performance')) {
            $filter = $request->remaining_performance;
            $items = $items->filter(function ($item) use ($filter) {
                $status = match (true) {
                    $item->remaining < $item->remaining_planned => 'above',
                    $item->remaining > $item->remaining_planned => 'below',
                    default => 'equal',
                };
                return $status === $filter;
            });
        }

        if ($request->filled('completed_status')) {
            $filter = $request->completed_status;
            $items = $items->filter(function ($item) use ($filter) {
                $total     = (float) ($item->total_quantity ?? 0);
                $completed = (float) ($item->previous_progress ?? 0) + (float) ($item->current_progress ?? 0);
                if ($total == 0) return $filter === 'not_started';
                return match ($filter) {
                    'completed'   => $completed >= $total,
                    'in_progress' => $completed > 0 && $completed < $total,
                    'not_started' => $completed <= 0,
                    default       => true,
                };
            });
        }

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $items  = $items->filter(fn($item) => $item->workItem && str_contains(strtolower($item->workItem->name), $search));
        }

        $project->setRelation('items', $items->values());
        return $project;
    }

    /**
     * تجميع الأصناف المكررة حسب الاسم + الوحدة فقط (بدون اعتماد على المشروع الفرعي)
     */
    protected function groupDuplicateItems($items)
    {
        return $items->groupBy(function ($item) {
            $name = strtolower(trim($item->workItem->name ?? ''));
            $unit = strtolower(trim($item->workItem->unit ?? ''));

            return $name . '|' . $unit;
        })->map(function ($group) {
            $first = $group->first();
            $tq = (float) $group->sum('total_quantity');

            $merged = new \stdClass();
            $merged->workItem = $first->workItem;
            $merged->subproject_name = null;
            $merged->notes = null;
            $merged->items_count = $group->count();
            $merged->is_grouped = $group->count() > 1;
            $merged->is_measurable = (bool) ($first->is_measurable ?? false);

            $merged->total_quantity = $tq;
            $merged->previous_progress = (float) $group->sum('previous_progress');
            $merged->current_progress = (float) $group->sum('current_progress');
            $merged->total_completed = (float) $group->sum('total_completed');
            $merged->remaining = (float) $group->sum('remaining');
            $merged->planned_until_from_date = (float) $group->sum('planned_until_from_date');
            $merged->planned_today = (float) $group->sum('planned_today');
            $merged->planned_total_quantity = (float) $group->sum('planned_total_quantity');
            $merged->remaining_planned = (float) $group->sum('remaining_planned');

            $merged->previous_progress_percentage = $tq > 0 ? round(($merged->previous_progress / $tq) * 100, 2) : 0;
            $merged->current_progress_percentage = $tq > 0 ? round(($merged->current_progress / $tq) * 100, 2) : 0;
            $merged->total_completed_percentage = $tq > 0 ? round(($merged->total_completed / $tq) * 100, 2) : 0;
            $merged->remaining_percentage = $tq > 0 ? round(($merged->remaining / $tq) * 100, 2) : 0;
            $merged->remaining_planned_percentage = $tq > 0 ? round(($merged->remaining_planned / $tq) * 100, 2) : 0;
            $merged->planned_until_from_date_percentage = $tq > 0 ? min(round(($merged->planned_until_from_date / $tq) * 100, 2), 100) : 0;
            $merged->planned_today_percentage = $tq > 0 ? min(round(($merged->planned_today / $tq) * 100, 2), 100) : 0;
            $merged->planned_total_percentage = $tq > 0 ? min(round(($merged->planned_total_quantity / $tq) * 100, 2), 100) : 0;

            $merged->previous_qty_class = $this->comparisonClass($merged->previous_progress, $merged->planned_until_from_date);
            $merged->previous_qty_icon = $this->comparisonIcon($merged->previous_progress, $merged->planned_until_from_date);
            $merged->previous_qty_color = $this->comparisonColor($merged->previous_progress, $merged->planned_until_from_date);

            $merged->current_qty_class = $this->comparisonClass($merged->current_progress, $merged->planned_today);
            $merged->current_qty_icon = $this->comparisonIcon($merged->current_progress, $merged->planned_today);
            $merged->current_qty_color = $this->comparisonColor($merged->current_progress, $merged->planned_today);

            $merged->completed_qty_class = $this->comparisonClass($merged->total_completed, $merged->planned_total_quantity);
            $merged->completed_qty_icon = $this->comparisonIcon($merged->total_completed, $merged->planned_total_quantity);
            $merged->completed_qty_color = $this->comparisonColor($merged->total_completed, $merged->planned_total_quantity);

            $merged->remaining_qty_class = $this->comparisonClassReversed($merged->remaining, $merged->remaining_planned);
            $merged->remaining_qty_icon = $this->comparisonIconReversed($merged->remaining, $merged->remaining_planned);
            $merged->remaining_qty_color = $this->comparisonColorReversed($merged->remaining, $merged->remaining_planned);

            $startDates = $group->pluck('start_date')->filter()->sort()->values();
            $endDates = $group->map(fn($i) => $i->end_date ?? $i->planned_end_date)->filter()->sort()->values();
            $progressStarts = $group->pluck('progress_start_date')->filter()->sort()->values();
            $progressEnds = $group->pluck('progress_end_date')->filter()->sort()->values();

            $merged->start_date = $startDates->first();
            $merged->end_date = $endDates->last();
            $merged->planned_end_date = null;
            $merged->progress_start_date = $progressStarts->first();
            $merged->progress_end_date = $progressEnds->last();

            return $merged;
        })->values();
    }

    /**
     * تجميع البنود حسب المشروع الفرعي مع حساب إجماليات كل مجموعة
     */
    protected function groupItemsBySubproject($items): \Illuminate\Support\Collection
    {
        $withoutLabel = __('general.without_subproject');

        return $items->groupBy(fn($item) => $item->subproject_name ?: $withoutLabel)
            ->map(function ($group, $subprojectName) {
                // تجميع وحساب الإجماليات فقط من البنود القابلة للقياس (is_measurable = true)
                $measurableGroup = $group->filter(fn($item) => (bool) ($item->is_measurable ?? false));
                $tq = (float) $measurableGroup->sum('total_quantity');

                $summary = new \stdClass();
                $summary->subproject_name          = $subprojectName;
                $summary->items                    = $group->values();
                $summary->items_count              = $group->count();
                $summary->total_quantity           = $tq;
                $summary->previous_progress        = (float) $measurableGroup->sum('previous_progress');
                $summary->current_progress         = (float) $measurableGroup->sum('current_progress');
                $summary->total_completed          = (float) $measurableGroup->sum('total_completed');
                $summary->remaining                = (float) $measurableGroup->sum('remaining');
                $summary->planned_until_from_date  = (float) $measurableGroup->sum('planned_until_from_date');
                $summary->planned_today            = (float) $measurableGroup->sum('planned_today');
                $summary->planned_total_quantity   = (float) $measurableGroup->sum('planned_total_quantity');
                $summary->remaining_planned        = (float) $measurableGroup->sum('remaining_planned');

                $summary->previous_progress_percentage       = $tq > 0 ? round(($summary->previous_progress / $tq) * 100, 2) : 0;
                $summary->current_progress_percentage        = $tq > 0 ? round(($summary->current_progress / $tq) * 100, 2) : 0;
                $summary->total_completed_percentage         = $tq > 0 ? round(($summary->total_completed / $tq) * 100, 2) : 0;
                $summary->remaining_percentage               = $tq > 0 ? round(($summary->remaining / $tq) * 100, 2) : 0;
                $summary->remaining_planned_percentage       = $tq > 0 ? round(($summary->remaining_planned / $tq) * 100, 2) : 0;
                $summary->planned_until_from_date_percentage = $tq > 0 ? min(round(($summary->planned_until_from_date / $tq) * 100, 2), 100) : 0;
                $summary->planned_today_percentage           = $tq > 0 ? min(round(($summary->planned_today / $tq) * 100, 2), 100) : 0;
                $summary->planned_total_percentage           = $tq > 0 ? min(round(($summary->planned_total_quantity / $tq) * 100, 2), 100) : 0;

                $summary->previous_qty_class  = $this->comparisonClass($summary->previous_progress, $summary->planned_until_from_date);
                $summary->previous_qty_icon   = $this->comparisonIcon($summary->previous_progress, $summary->planned_until_from_date);
                $summary->previous_qty_color  = $this->comparisonColor($summary->previous_progress, $summary->planned_until_from_date);
                $summary->current_qty_class   = $this->comparisonClass($summary->current_progress, $summary->planned_today);
                $summary->current_qty_icon    = $this->comparisonIcon($summary->current_progress, $summary->planned_today);
                $summary->current_qty_color   = $this->comparisonColor($summary->current_progress, $summary->planned_today);
                $summary->completed_qty_class = $this->comparisonClass($summary->total_completed, $summary->planned_total_quantity);
                $summary->completed_qty_icon  = $this->comparisonIcon($summary->total_completed, $summary->planned_total_quantity);
                $summary->completed_qty_color = $this->comparisonColor($summary->total_completed, $summary->planned_total_quantity);
                $summary->remaining_qty_class = $this->comparisonClassReversed($summary->remaining, $summary->remaining_planned);
                $summary->remaining_qty_icon  = $this->comparisonIconReversed($summary->remaining, $summary->remaining_planned);
                $summary->remaining_qty_color = $this->comparisonColorReversed($summary->remaining, $summary->remaining_planned);

                return $summary;
            })->values();
    }

    /* ─────────────────── Performance Comparison Helpers ─────────────────── */

    private function comparisonClass(float $actual, float $planned): string
    {
        return match (true) {
            $actual > $planned => 'above-expected',
            $actual < $planned => 'below-expected',
            default            => 'equal-expected',
        };
    }

    private function comparisonIcon(float $actual, float $planned): string
    {
        return match (true) {
            $actual > $planned => 'fa-arrow-up',
            $actual < $planned => 'fa-arrow-down',
            default            => 'fa-equals',
        };
    }

    private function comparisonColor(float $actual, float $planned): string
    {
        return match (true) {
            $actual > $planned => 'text-success',
            $actual < $planned => 'text-danger',
            default            => 'text-warning',
        };
    }

    private function comparisonClassReversed(float $actual, float $planned): string
    {
        return match (true) {
            $actual < $planned => 'above-expected',
            $actual > $planned => 'below-expected',
            default            => 'equal-expected',
        };
    }

    private function comparisonIconReversed(float $actual, float $planned): string
    {
        return match (true) {
            $actual < $planned => 'fa-arrow-up',
            $actual > $planned => 'fa-arrow-down',
            default            => 'fa-equals',
        };
    }

    private function comparisonColorReversed(float $actual, float $planned): string
    {
        return match (true) {
            $actual < $planned => 'text-success',
            $actual > $planned => 'text-danger',
            default            => 'text-warning',
        };
    }
}
