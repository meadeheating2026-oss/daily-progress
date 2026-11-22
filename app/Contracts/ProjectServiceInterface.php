<?php

namespace App\Contracts;

use App\Models\Project;

/**
 * Interface for core project business logic
 */
interface ProjectServiceInterface
{
    /**
     * إنشاء مشروع جديد (أو مسودة)
     */
    public function createProject(array $validated): Project;

    /**
     * تحديث مشروع موجود
     */
    public function updateProject(Project $project, array $validated): Project;

    /**
     * حذف مشروع (Soft Delete)
     */
    public function deleteProject(Project $project): bool;

    /**
     * نشر مسودة كمشروع فعلي
     */
    public function publishDraft(Project $project): array;

    /**
     * نسخ مشروع بكامل بياناته
     */
    public function copyProject(Project $project, string $newName): Project;
}
