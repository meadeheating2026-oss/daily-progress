<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\WorkItem;
use App\Models\DailyProgress;
use App\Models\ProjectItem;
use App\Models\Employee;
use App\Models\ProjectType;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Project::query()
                ->where('is_draft', false)
                ->where('is_archived', false);

            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->filled('employee_id')) {
                $query->whereHas('employees', function ($q) use ($request) {
                    $q->where('employees.id', $request->employee_id);
                });
            }

            if ($request->filled('type_id')) {
                $query->where('project_type_id', $request->type_id);
            }

            if ($request->filled('project_id')) {
                $query->where('id', $request->project_id);
            }

            if ($request->filled('item_id')) {
                $query->whereHas('items', function ($q) use ($request) {
                    $q->where('work_item_id', $request->item_id);
                });
            }

            if ($request->filled('date_range') && is_numeric($request->date_range)) {
                $query->where('created_at', '>=', now()->subDays((int) $request->date_range));
            }

            // 1. Single SQL GROUP BY query for status breakdown & total count (O(1) memory even at 10,000+ projects)
            $statusRows = (clone $query)
                ->selectRaw('status, COUNT(*) as cnt')
                ->groupBy('status')
                ->pluck('cnt', 'status');

            $activeCount    = (int) ($statusRows['active'] ?? 0);
            $completedCount = (int) ($statusRows['completed'] ?? 0);
            $pendingCount   = (int) ($statusRows['pending'] ?? 0);
            $projectsCount  = (int) $statusRows->sum();
            $projectStatusData = [$activeCount, $completedCount, $pendingCount];

            // 2. Single SQL covering-index aggregate for overall filtered progress (zero Eloquent hydration)
            $agg = ProjectItem::query()
                ->whereIn('project_id', (clone $query)->select('projects.id'))
                ->where('is_measurable', true)
                ->selectRaw('COALESCE(SUM(total_quantity), 0) as total_qty, COALESCE(SUM(completed_quantity), 0) as completed_qty')
                ->first();

            $progress = ($agg && (float) $agg->total_qty > 0)
                ? (int) round(((float) $agg->completed_qty / (float) $agg->total_qty) * 100)
                : 0;

            // 3. Load only the latest 30 matching projects with eager-loaded subprojects & minimal item columns (zero N+1)
            $projects = (clone $query)
                ->select(['id', 'name', 'start_date', 'end_date', 'status', 'created_at'])
                ->with([
                    'subprojects:id,project_id,name,weight,sort_order',
                    'items:id,project_id,subproject_name,total_quantity,completed_quantity,is_measurable',
                ])
                ->latest('created_at')
                ->limit(30)
                ->get()
                ->map(function ($project) {
                    $project->progress = $project->overall_progress;
                    $project->total_quantity = $project->items->sum('total_quantity');
                    return $project;
                });

            // Build chart datasets directly from the already-loaded collection (zero extra DB queries)
            $labels  = [];
            $data    = [];
            $planned = [];
            $actual  = [];
            $today   = Carbon::today();

            foreach ($projects as $project) {
                $actualPct = (int) round($project->progress ?? 0);

                $plannedPct = 0;
                if ($project->start_date && $project->end_date) {
                    $startDate = Carbon::parse($project->start_date);
                    $endDate   = Carbon::parse($project->end_date);
                    if ($today->greaterThanOrEqualTo($endDate)) {
                        $plannedPct = 100;
                    } elseif ($today->greaterThanOrEqualTo($startDate)) {
                        $totalDays  = max(1, $startDate->diffInDays($endDate));
                        $daysPassed = $startDate->diffInDays($today);
                        $plannedPct = (int) min(100, round(($daysPassed / $totalDays) * 100));
                    }
                }

                $labels[]  = $project->name;
                $data[]    = $actualPct;
                $planned[] = $plannedPct;
                $actual[]  = $actualPct;
            }

            $projectProgressData = ['labels' => $labels, 'data' => $data];
            $plannedVsActualData = ['labels' => $labels, 'planned' => $planned, 'actual' => $actual];

            // 4. Short-cached global KPI counters & lightweight base-query filter lists
            $globalCounts = Cache::remember('dashboard_global_counts_v1', 60, function () {
                return [
                    'itemsCount'       => WorkItem::count(),
                    'dailyFormsCount'  => DailyProgress::distinct('progress_date')->count('progress_date'),
                    'teamMembersCount' => Employee::count(),
                ];
            });

            $itemsCount       = $globalCounts['itemsCount'];
            $dailyFormsCount  = $globalCounts['dailyFormsCount'];
            $teamMembersCount = $globalCounts['teamMembersCount'];

            if ($request->ajax()) {
                return response()->json([
                    'itemsCount'          => $itemsCount,
                    'dailyFormsCount'     => $dailyFormsCount,
                    'projectsCount'       => $projectsCount,
                    'progress'            => $progress,
                    'projectProgressData' => $projectProgressData,
                    'plannedVsActualData' => $plannedVsActualData,
                    'projectStatusData'   => $projectStatusData,
                    'projects'            => $projects->map(fn($project) => [
                        'id'         => $project->id,
                        'name'       => $project->name,
                        'start_date' => $project->start_date,
                        'end_date'   => $project->end_date,
                        'progress'   => $project->progress,
                        'status'     => $project->status,
                    ]),
                ]);
            }

            $filterDropdowns = Cache::remember('dashboard_filter_lists_v1', 60, function () {
                return [
                    'teamMembers'  => DB::table('employees')->select('id', 'name')->orderBy('name')->get(),
                    'projectTypes' => DB::table('project_types')->select('id', 'name')->orderBy('name')->get(),
                    'allItems'     => DB::table('work_items')->select('id', 'name')->orderBy('name')->get(),
                    'allProjects'  => DB::table('projects')
                        ->whereNull('deleted_at')
                        ->where('is_draft', false)
                        ->where('is_archived', false)
                        ->select('id', 'name')
                        ->orderByDesc('id')
                        ->limit(500)
                        ->get(),
                ];
            });

            $teamMembers  = $filterDropdowns['teamMembers'];
            $projectTypes = $filterDropdowns['projectTypes'];
            $allItems     = $filterDropdowns['allItems'];
            $allProjects  = $filterDropdowns['allProjects'];

            return view('dashboard', compact(
                'progress',
                'itemsCount',
                'dailyFormsCount',
                'projectsCount',
                'projectProgressData',
                'projectStatusData',
                'projects',
                'teamMembers',
                'projectTypes',
                'allProjects',
                'allItems',
                'request',
                'plannedVsActualData',
                'teamMembersCount'
            ));
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'error'   => true,
                    'message' => 'Error occurred while filtering: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Error occurred: ' . $e->getMessage());
        }
    }
}
