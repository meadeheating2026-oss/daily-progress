<?php

namespace App\Repositories;

use App\Models\Issue;
use App\Contracts\IssueRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class IssueRepository implements IssueRepositoryInterface
{
    /**
     * جلب كل الـ Issues مع إمكانية الفلترة
     */
    public function getAll(array $filters = []): Collection
    {
        $query = Issue::with(['project', 'reporter', 'assignee', 'attachments', 'comments.user'])
            ->latest();

        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        return $query->get();
    }

    /**
     * جلب Issue واحد مع العلاقات
     */
    public function findWithRelations(int $id): Issue
    {
        return Issue::with([
            'project',
            'reporter',
            'assignee',
            'attachments',
            'comments.user',
        ])->findOrFail($id);
    }

    /**
     * إنشاء Issue جديد
     */
    public function create(array $data): Issue
    {
        return Issue::create($data);
    }

    /**
     * تحديث Issue
     */
    public function update(Issue $issue, array $data): bool
    {
        return $issue->update($data);
    }

    /**
     * حذف Issue (soft delete)
     */
    public function delete(Issue $issue): bool
    {
        return $issue->delete();
    }

    /**
     * تحديث حالة Issue
     */
    public function updateStatus(Issue $issue, string $status): bool
    {
        return $issue->update(['status' => $status]);
    }

    /**
     * جلب الإحصائيات
     */
    public function getStatistics(): array
    {
        return [
            'total'       => Issue::count(),
            'new'         => Issue::where('status', Issue::STATUS_NEW)->count(),
            'in_progress' => Issue::where('status', Issue::STATUS_IN_PROGRESS)->count(),
            'testing'     => Issue::where('status', Issue::STATUS_TESTING)->count(),
            'closed'      => Issue::where('status', Issue::STATUS_CLOSED)->count(),
            'high'        => Issue::where('priority', Issue::PRIORITY_HIGH)->count(),
            'urgent'      => Issue::where('priority', Issue::PRIORITY_URGENT)->count(),
        ];
    }

    /**
     * جلب الـ Issues مجمعة حسب الحالة (للـ Kanban)
     */
    public function getGroupedByStatus(array $filters = []): array
    {
        $issues = $this->getAll($filters);

        return [
            Issue::STATUS_NEW         => $issues->where('status', Issue::STATUS_NEW)->values(),
            Issue::STATUS_IN_PROGRESS => $issues->where('status', Issue::STATUS_IN_PROGRESS)->values(),
            Issue::STATUS_TESTING     => $issues->where('status', Issue::STATUS_TESTING)->values(),
            Issue::STATUS_CLOSED      => $issues->where('status', Issue::STATUS_CLOSED)->values(),
        ];
    }
}
