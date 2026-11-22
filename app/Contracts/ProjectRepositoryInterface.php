<?php

namespace App\Contracts;

use App\Models\Project;
use Illuminate\Support\Collection;

/**
 * Interface for Project Repository operations
 */
interface ProjectRepositoryInterface
{
    public function findById(int $id): ?Project;

    public function findByIdWithRelations(int $id, array $relations = []): ?Project;

    public function getAllActive(): Collection;

    public function getAllDrafts(): Collection;

    public function getAllArchived(): Collection;

    public function getByUserId(int $userId, bool $isDraft = false, bool $isArchived = false): Collection;

    public function create(array $data): Project;

    public function update(Project $project, array $data): bool;

    public function delete(Project $project): bool;

    public function archive(Project $project): bool;

    public function unarchive(Project $project): bool;

    public function syncEmployees(Project $project, array $employeeIds): void;

    public function getProjectWithProgress(Project $project): Project;
}
