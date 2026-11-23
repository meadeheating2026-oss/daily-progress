<?php

namespace App\Repositories;

use App\Models\Project;
use App\Contracts\ProjectRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProjectRepository implements ProjectRepositoryInterface
{
    public function findById(int $id): ?Project
    {
        return Project::find($id);
    }

    public function findByIdWithRelations(int $id, array $relations = []): ?Project
    {
        return Project::with($relations)->find($id);
    }

    public function getAllActive(): Collection
    {
        return Project::where('is_draft', false)
            ->where('is_archived', false)
            ->with($this->indexRelations())
            ->withCount(['items', 'employees'])
            ->latest()
            ->get();
    }

    public function getAllDrafts(): Collection
    {
        return Project::where('is_draft', true)
            ->where('is_archived', false)
            ->with(['client:id,name', 'type:id,name'])
            ->withCount(['items', 'employees'])
            ->latest()
            ->get();
    }

    public function getAllArchived(): Collection
    {
        return Project::where('is_archived', true)
            ->with($this->indexRelations())
            ->withCount(['items', 'employees'])
            ->latest('archived_at')
            ->get();
    }

    public function getByUserId(int $userId, bool $isDraft = false, bool $isArchived = false): Collection
    {
        return Project::whereHas('employees', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->where('is_draft', $isDraft)
            ->where('is_archived', $isArchived)
            ->with($isDraft ? ['client:id,name', 'type:id,name'] : $this->indexRelations())
            ->withCount(['items', 'employees'])
            ->latest($isArchived ? 'archived_at' : 'created_at')
            ->get();
    }

    /**
     * High-scale paginated & SQL-filtered project query (scales cleanly to 10,000+ projects).
     */
    public function paginateFiltered(
        array $filters = [],
        ?int $userId = null,
        bool $isDraft = false,
        bool $isArchived = false,
        int $perPage = 24
    ): LengthAwarePaginator {
        $query = Project::query()
            ->where('is_draft', $isDraft)
            ->where('is_archived', $isArchived);

        if ($userId !== null) {
            $query->whereHas('employees', function (Builder $q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        if (!empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhereHas('client', fn(Builder $cq) => $cq->where('name', 'like', "%{$term}%"));
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['type']) && $filters['type'] !== 'all') {
            if (is_numeric($filters['type'])) {
                $query->where('project_type_id', (int) $filters['type']);
            } else {
                $query->whereHas('type', fn(Builder $tq) => $tq->where('name', $filters['type']));
            }
        }

        if (!empty($filters['client']) && $filters['client'] !== 'all') {
            if (is_numeric($filters['client'])) {
                $query->where('client_id', (int) $filters['client']);
            } else {
                $query->whereHas('client', fn(Builder $cq) => $cq->where('name', $filters['client']));
            }
        }

        return $query
            ->with($isDraft ? ['client:id,name', 'type:id,name'] : $this->indexRelations())
            ->withCount(['items', 'employees'])
            ->latest($isArchived ? 'archived_at' : 'created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Lightweight relations for project cards — zero N+1.
     */
    private function indexRelations(): array
    {
        return [
            'client:id,name',
            'type:id,name',
            'subprojects:id,project_id,name,weight,sort_order',
            'items:id,project_id,subproject_name,total_quantity,completed_quantity,is_measurable',
        ];
    }

    public function create(array $data): Project
    {
        return Project::create($data);
    }

    public function update(Project $project, array $data): bool
    {
        return $project->update($data);
    }

    public function delete(Project $project): bool
    {
        return $project->delete();
    }

    public function archive(Project $project): bool
    {
        return $project->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);
    }

    public function unarchive(Project $project): bool
    {
        return $project->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);
    }

    public function syncEmployees(Project $project, array $employeeIds): void
    {
        $project->employees()->sync($employeeIds);
    }

    public function getProjectWithProgress(Project $project): Project
    {
        return $project->load([
            'client',
            'items.workItem.category',
            'items' => fn($q) => $q->withSum('dailyProgress', 'quantity'),
            'items.dailyProgress' => fn($q) => $q->orderBy('progress_date', 'desc')->limit(5)->with('employee'),
            'dailyProgress.employee',
        ]);
    }
}
