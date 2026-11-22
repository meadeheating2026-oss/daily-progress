<?php

namespace App\Contracts;

use App\Models\Issue;
use Illuminate\Support\Collection;

/**
 * Interface for Issue Repository operations
 */
interface IssueRepositoryInterface
{
    public function getAll(array $filters = []): Collection;

    public function findWithRelations(int $id): Issue;

    public function create(array $data): Issue;

    public function update(Issue $issue, array $data): bool;

    public function delete(Issue $issue): bool;

    public function updateStatus(Issue $issue, string $status): bool;

    public function getStatistics(): array;

    public function getGroupedByStatus(array $filters = []): array;
}
