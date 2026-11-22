<?php

namespace App\Contracts;

use App\Models\Project;

interface ProjectProgressServiceInterface
{
    /**
     * حساب الإنجاز الإجمالي للمشروع
     */
    public function calculateOverallProgress(Project $project): float;
}
