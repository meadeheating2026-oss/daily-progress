<?php

namespace App\Repositories;

use App\Models\DailyProgress;
use App\Models\Project;
use App\Models\Employee;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DailyProgressRepository
{
    /**
     * جلب التقدم اليومي مع الفلترة الكاملة
     */
    public function getFilteredQuery(array $filters = [])
    {
        $query = DailyProgress::with([
            'project',
            'projectItem.dailyProgresses',
            'projectItem.workItem.category',
            'projectItem.subproject',
            'employee',
        ]);

        // استبعاد المسودات والمشاريع المؤرشفة
        $query->whereHas('project', fn($q) => $q->where('is_draft', false)->where('is_archived', false));

        // فلترة حسب الموظف
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        // فلترة التاريخ
        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
            $query->whereBetween('progress_date', [$filters['from_date'], $filters['to_date']]);
        } elseif (!empty($filters['from_date'])) {
            $query->whereDate('progress_date', '>=', $filters['from_date']);
        } elseif (!empty($filters['to_date'])) {
            $query->whereDate('progress_date', '<=', $filters['to_date']);
        } elseif (!empty($filters['progress_date'])) {
            $query->whereDate('progress_date', $filters['progress_date']);
        } elseif (empty($filters['view_all'])) {
            $query->whereDate('progress_date', today());
        }

        // فلترة المشروع
        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        return $query;
    }

    /**
     * جلب التقدم اليومي المنفذ اليوم لمشروع معين
     */
    public function getExecutedToday(int $projectId): Collection
    {
        return DailyProgress::where('project_id', $projectId)
            ->whereDate('progress_date', today())
            ->with(['projectItem.workItem', 'employee'])
            ->get();
    }

    /**
     * إنشاء سجل تقدم يومي جديد
     */
    public function create(array $data): DailyProgress
    {
        return DailyProgress::create($data);
    }

    /**
     * تحديث سجل تقدم يومي
     */
    public function update(DailyProgress $dailyProgress, array $data): bool
    {
        return $dailyProgress->update($data);
    }

    /**
     * حذف سجل تقدم يومي (soft delete)
     */
    public function delete(DailyProgress $dailyProgress): bool
    {
        return $dailyProgress->delete();
    }

    /**
     * جلب إجمالي التقدم اليومي لمشروع في فترة زمنية
     */
    public function getSummaryForProject(int $projectId, string $fromDate, string $toDate): array
    {
        return DB::table('daily_progress')
            ->join('project_items', 'daily_progress.project_item_id', '=', 'project_items.id')
            ->where('project_items.project_id', $projectId)
            ->whereBetween('daily_progress.progress_date', [$fromDate, $toDate])
            ->whereNull('daily_progress.deleted_at')
            ->select(
                DB::raw('DATE(daily_progress.progress_date) as date'),
                DB::raw('SUM(daily_progress.quantity) as total_quantity')
            )
            ->groupBy(DB::raw('DATE(daily_progress.progress_date)'))
            ->orderBy('date')
            ->get()
            ->toArray();
    }
}
