<?php

namespace App\Services;

use App\Contracts\ProjectProgressServiceInterface;
use App\Models\Project;

class ProjectProgressService implements ProjectProgressServiceInterface
{
    public function calculateOverallProgress(Project $project): float
    {
        // ✅ فلترة البنود القابلة للقياس فقط
        $measurableItems = $project->items->filter(function ($item) {
            return $item->is_measurable ?? false;
        });

        // التحقق من وجود مشاريع فرعية بأوزان
        $subprojects = $project->subprojects;
        $hasWeightedSubprojects = $subprojects->where('weight', '>', 0)->isNotEmpty();

        if ($hasWeightedSubprojects) {
            $overallProgress = 0;
            $totalWeight = 0;

            foreach ($subprojects as $subproject) {
                // Get items for this subproject
                $subItems = $measurableItems->filter(function ($item) use ($subproject) {
                    return $item->subproject_name === $subproject->name;
                });

                if ($subItems->isEmpty()) {
                    continue;
                }

                $subTotalQty = $subItems->sum('total_quantity');
                $subCompletedQty = $subItems->sum('completed_quantity');

                $subProgress = $subTotalQty > 0 
                    ? ($subCompletedQty / $subTotalQty) * 100 
                    : 0;

                $overallProgress += $subProgress * ($subproject->weight / 100);
                $totalWeight += $subproject->weight;
            }
            
            return round($overallProgress, 1);
        }

        // Fallback to simple calculation (Classic Mode)
        $totalQuantity = $measurableItems->sum('total_quantity');
        $completedQuantity = $measurableItems->sum('completed_quantity');

        if ($totalQuantity <= 0) {
            return 0;
        }

        return round(($completedQuantity / $totalQuantity) * 100, 1);
    }
}
