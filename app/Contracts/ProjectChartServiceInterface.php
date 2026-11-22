<?php

namespace App\Contracts;

use App\Models\Project;

/**
 * Interface for project chart/visualization data generation
 */
interface ProjectChartServiceInterface
{
    /**
     * بيانات المخططات الأساسية للمشروع
     */
    public function getProjectChartData(Project $project): array;

    /**
     * بيانات مخططات المشاريع الفرعية
     */
    public function getSubprojectsChartData(Project $project, $subprojectsWithItems): array;

    /**
     * بيانات مخططات الفئات
     */
    public function getCategoriesChartData(Project $project): array;

    /**
     * بيانات مخططات بنود المشاريع الفرعية
     */
    public function getSubprojectItemsChartData(Project $project, $subprojectsWithItems): array;

    /**
     * بيانات مخططات بنود الفئات
     */
    public function getCategoryItemsChartData(Project $project): array;

    /**
     * معلومات حالة المشروع (لون، أيقونة، نص)
     */
    public function getProjectStatusInfo(string $status): array;
}
