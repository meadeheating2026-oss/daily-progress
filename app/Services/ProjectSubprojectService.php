<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Subproject;

class ProjectSubprojectService
{
    /**
     * ✅ جلب المشاريع الفرعية التي لها بنود فعلية فقط
     * يضيف مشروع فرعي افتراضي "بدون فرعي" إذا كانت هناك بنود بدون تصنيف فرعي
     */
    public function getSubprojectsWithItems(Project $project)
    {
        // أسماء المشاريع الفرعية التي لها بنود
        $subprojectNamesWithItems = $project->items()
            ->whereNotNull('subproject_name')
            ->distinct()
            ->pluck('subproject_name')
            ->toArray();

        // تصفية المشاريع الفرعية المرتبطة بالمشروع
        $subprojects = $project->subprojects->filter(
            fn($subproject) => in_array($subproject->name, $subprojectNamesWithItems)
        );

        // إضافة مشروع فرعي افتراضي للبنود بدون تصنيف
        $itemsWithoutSubproject = $project->items()->whereNull('subproject_name')->exists();

        if ($itemsWithoutSubproject) {
            $withoutSubprojectName = __('general.without_subproject');
            $virtualSubproject = new Subproject([
                'id'             => null,
                'project_id'     => $project->id,
                'name'           => $withoutSubprojectName,
                'start_date'     => null,
                'end_date'       => null,
                'total_quantity' => 0,
                'unit'           => null,
                'description'    => null,
                'weight'         => 0,
            ]);
            $subprojects->push($virtualSubproject);
        }

        return $subprojects;
    }
}
