<?php

namespace App\Repositories;

use App\Models\Project;
use App\Contracts\ProjectRepositoryInterface;
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
            ->with($this->indexRelations())
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
        // البحث عن المشاريع المرتبطة بالموظف الذي user_id = $userId
        return Project::whereHas('employees', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->where('is_draft', $isDraft)
            ->where('is_archived', $isArchived)
            ->with($this->indexRelations())
            ->withCount(['items', 'employees'])
            ->latest($isArchived ? 'archived_at' : 'created_at')
            ->get();
    }

    /**
     * علاقات خفيفة لقائمة المشاريع — تكفي لحساب التقدم وعدّ الفروع بدون N+1.
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
            'dailyProgress.employee'
        ]);
    }
}
