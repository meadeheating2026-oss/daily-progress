<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Subproject;

class ProjectSubprojectService
{
    /**
     * جلب المشاريع الفرعية التي لها بنود فعلية فقط، مع ضمان وجود سجل فعلي في جدول subprojects
     * لأي مشروع فرعي مستخدم في البنود حتى يتمكن المستخدم من حفظ وزنه.
     */
    public function getSubprojectsWithItems(Project $project)
    {
        $items = $project->relationLoaded('items') ? $project->items : $project->items()->get();

        $subprojectNamesWithItems = $items
            ->pluck('subproject_name')
            ->filter(fn($name) => !empty($name))
            ->unique()
            ->values()
            ->all();

        $hasItemsWithoutSubproject = $items->contains(fn($item) => empty($item->subproject_name));
        $withoutSubprojectName = __('general.without_subproject');

        // إذا كان بالمشروع مشاريع فرعية وبعض البنود بدون اسم مشروع فرعي، أنشئ/اجلب سجل لها ليقبل وزناً
        if (!empty($subprojectNamesWithItems) && $hasItemsWithoutSubproject) {
            $subprojectNamesWithItems[] = $withoutSubprojectName;
        }

        if (empty($subprojectNamesWithItems)) {
            return collect();
        }

        $existingByName = ($project->relationLoaded('subprojects') ? $project->subprojects : $project->subprojects()->get())
            ->keyBy('name');

        $createdAny = false;
        foreach ($subprojectNamesWithItems as $spName) {
            if (!$existingByName->has($spName)) {
                $created = Subproject::firstOrCreate(
                    ['project_id' => $project->id, 'name' => $spName],
                    ['weight' => 0]
                );
                $existingByName->put($spName, $created);
                $createdAny = true;
            }
        }

        if ($createdAny) {
            $project->unsetRelation('subprojects');
            $project->load('subprojects');
        }

        return ($project->relationLoaded('subprojects') ? $project->subprojects : $project->subprojects()->get())
            ->filter(fn($sp) => in_array($sp->name, $subprojectNamesWithItems, true))
            ->values();
    }
}
