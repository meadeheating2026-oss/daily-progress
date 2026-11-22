<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectChartService;
use App\Services\ProjectSubprojectService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProjectGanttController extends Controller
{
    public function __construct(
        protected ProjectChartService $chartService,
        protected ProjectSubprojectService $subprojectService,
    ) {}

    /**
     * عرض صفحة Gantt Chart
     */
    public function ganttChart(Project $project)
    {
        $this->authorize('view', $project);

        $project->load(['client', 'type']);

        return view('projects.gantt', compact('project'));
    }

    /**
     * جلب بيانات Gantt Chart بصيغة JSON
     */
    public function ganttData(Project $project)
    {
        $this->authorize('view', $project);

        try {
            $items = $project->items()
                ->with(['workItem.category', 'predecessorItem.workItem'])
                ->orderBy('item_order')
                ->get();

            // حساب الكميات المكتملة في query واحد
            $completedQuantities = DB::table('daily_progress')
                ->select('project_item_id', DB::raw('SUM(quantity) as completed'))
                ->whereIn('project_item_id', $items->pluck('id'))
                ->whereNull('deleted_at')
                ->groupBy('project_item_id')
                ->pluck('completed', 'project_item_id');

            $tasks = $items->map(function ($item) use ($project, $completedQuantities) {
                $completedQuantity  = $completedQuantities->get($item->id, 0);
                $progressPercentage = $item->total_quantity > 0
                    ? min(round(($completedQuantity / $item->total_quantity) * 100, 2), 100)
                    : 0;

                $status = $this->calculateItemStatus($item, $progressPercentage);

                $predecessorInfo = null;
                if ($item->predecessor && $item->predecessorItem) {
                    $predecessorInfo = [
                        'id'   => $item->predecessorItem->id,
                        'name' => $item->predecessorItem->workItem->name ?? 'N/A',
                        'type' => $item->dependency_type ?? 'FS',
                        'lag'  => $item->lag ?? 0,
                    ];
                }

                return [
                    'id'                 => $item->id,
                    'name'               => $item->workItem->name ?? 'N/A',
                    'description'        => $item->notes,
                    'start_date'         => $item->start_date ?? $project->start_date,
                    'end_date'           => $item->end_date ?? $project->end_date,
                    'duration'           => $item->duration ?? 0,
                    'total_quantity'     => (float) $item->total_quantity,
                    'completed_quantity' => (float) $completedQuantity,
                    'progress'           => $progressPercentage,
                    'status'             => $status,
                    'unit'               => strtoupper($item->workItem->unit ?? ''),
                    'subproject'         => $item->subproject_name,
                    'predecessor'        => $predecessorInfo,
                    'work_item'          => [
                        'id'       => $item->workItem->id ?? null,
                        'category' => $item->workItem->category->name ?? null,
                        'notes'    => $item->workItem->description ?? null,
                    ],
                ];
            });

            return response()->json([
                'success' => true,
                'project' => [
                    'id'         => $project->id,
                    'name'       => $project->name,
                    'start_date' => $project->start_date,
                    'end_date'   => $project->end_date,
                    'status'     => $project->status,
                ],
                'tasks' => $tasks,
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading Gantt data', [
                'project_id' => $project->id,
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل بيانات Gantt Chart',
            ], 500);
        }
    }

    /**
     * حساب حالة البند بناءً على التقدم والتواريخ
     */
    private function calculateItemStatus($item, float $progressPercentage): string
    {
        $today     = Carbon::today();
        $endDate   = $item->end_date ? Carbon::parse($item->end_date) : null;
        $startDate = $item->start_date ? Carbon::parse($item->start_date) : null;

        if ($progressPercentage >= 100) return 'completed';
        if ($startDate && $today->lt($startDate)) return 'pending';
        if ($endDate && $today->gt($endDate) && $progressPercentage < 100) return 'delayed';
        if ($progressPercentage > 0 && $progressPercentage < 100) return 'in_progress';

        return 'pending';
    }
}
