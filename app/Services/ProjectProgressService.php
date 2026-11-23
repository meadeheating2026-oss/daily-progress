<?php

namespace App\Services;

use App\Contracts\ProjectProgressServiceInterface;
use App\Models\Project;

class ProjectProgressService implements ProjectProgressServiceInterface
{
    /**
     * يحسب نسبة الإنجاز الإجمالية للمشروع الأساسي:
     * 1) البنود القابلة للقياس فقط (is_measurable = true) مع سقف 100% لكل بند: min(completed_quantity, total_quantity).
     * 2) إذا كان المشروع مقسماً إلى مشاريع فرعية:
     *    - إذا كانت جميع الأوزان = 0% -> يعيد 0% (مع إظهار تنبيه للمستخدم بضرورة تحديد الأوزان).
     *    - إذا تم تحديد الأوزان -> مجموع (نسبة إنجاز كل مشروع فرعي × وزنه / 100).
     * 3) إذا كان المشروع بدون مشاريع فرعية إطلاقاً -> يحسب من مجموع كميات بنوده القابلة للقياس مباشرة.
     */
    public function calculateOverallProgress(Project $project): float
    {
        $items = $project->relationLoaded('items') ? $project->items : $project->items()->get();
        $subprojects = $project->relationLoaded('subprojects') ? $project->subprojects : $project->subprojects()->get();

        $measurableItems = $items->filter(fn($item) => (bool) ($item->is_measurable ?? false));
        if ($measurableItems->isEmpty()) {
            return 0.0;
        }

        $subprojectNamesWithItems = $items
            ->pluck('subproject_name')
            ->filter(fn($name) => !empty($name))
            ->unique()
            ->values()
            ->all();

        $hasSubprojects = !empty($subprojectNamesWithItems) || $subprojects->isNotEmpty();

        if ($hasSubprojects) {
            $activeSubprojects = $subprojects->filter(function ($sp) use ($subprojectNamesWithItems, $items) {
                if (in_array($sp->name, $subprojectNamesWithItems, true)) {
                    return true;
                }
                if ($sp->name === __('general.without_subproject')) {
                    return $items->contains(fn($it) => empty($it->subproject_name));
                }
                return false;
            });

            $totalWeight = (float) $activeSubprojects->sum(fn($sp) => (float) ($sp->weight ?? 0));

            // إذا لم يتم تحديد أوزان المشاريع الفرعية بعد (كلها 0%) تكون النسبة 0% مع تنبيه المستخدم
            if ($totalWeight <= 0) {
                return 0.0;
            }

            $overallProgress = 0.0;
            $withoutName = __('general.without_subproject');

            foreach ($activeSubprojects as $subproject) {
                $weight = (float) ($subproject->weight ?? 0);
                if ($weight <= 0) {
                    continue;
                }

                $subItems = ($subproject->name === $withoutName)
                    ? $measurableItems->filter(fn($it) => empty($it->subproject_name))
                    : $measurableItems->filter(fn($it) => $it->subproject_name === $subproject->name);

                if ($subItems->isEmpty()) {
                    continue;
                }

                $subTotalQty = (float) $subItems->sum(fn($it) => max(0, (float) ($it->total_quantity ?? 0)));
                $subCappedCompleted = (float) $subItems->sum(function ($it) {
                    $tot = max(0, (float) ($it->total_quantity ?? 0));
                    $comp = max(0, (float) ($it->completed_quantity ?? 0));
                    return min($comp, $tot);
                });

                $subProgress = $subTotalQty > 0 ? ($subCappedCompleted / $subTotalQty) * 100.0 : 0.0;
                $overallProgress += $subProgress * ($weight / 100.0);
            }

            return round($overallProgress, 2);
        }

        // مشروع عادي بدون مشاريع فرعية
        $totalQuantity = (float) $measurableItems->sum(fn($it) => max(0, (float) ($it->total_quantity ?? 0)));
        if ($totalQuantity <= 0) {
            return 0.0;
        }

        $cappedCompleted = (float) $measurableItems->sum(function ($it) {
            $tot = max(0, (float) ($it->total_quantity ?? 0));
            $comp = max(0, (float) ($it->completed_quantity ?? 0));
            return min($comp, $tot);
        });

        return round(($cappedCompleted / $totalQuantity) * 100.0, 2);
    }
}
