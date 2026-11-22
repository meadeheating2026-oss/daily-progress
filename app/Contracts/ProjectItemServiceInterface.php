<?php

namespace App\Contracts;

use App\Models\Project;

interface ProjectItemServiceInterface
{
    /**
     * إنشاء بنود جديدة لمشروع
     */
    public function createProjectItems(Project $project, array $items, array $validated): array;

    /**
     * تحديث/مزامنة البنود الحالية لمشروع
     */
    public function syncProjectItems(Project $project, array $items, array $validated): array;

    /**
     * تحديث العلاقات (الاعتماديات) بين البنود
     */
    public function updateItemPredecessors(array $items, array $itemMapping): void;

    /**
     * إعادة ترتيب البنود
     */
    public function reorderItems(Project $project, array $itemOrders): void;
}
