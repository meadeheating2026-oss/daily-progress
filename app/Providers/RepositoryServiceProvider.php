<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\ProjectRepositoryInterface;
use App\Contracts\ProjectServiceInterface;
use App\Contracts\ProjectChartServiceInterface;
use App\Contracts\ProjectItemServiceInterface;
use App\Contracts\ProjectProgressServiceInterface;
use App\Contracts\IssueRepositoryInterface;
use App\Repositories\ProjectRepository;
use App\Repositories\IssueRepository;
use App\Services\ProjectService;
use App\Services\ProjectChartService;
use App\Services\ProjectItemService;
use App\Services\ProjectProgressService;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register interface-to-implementation bindings
     */
    public function register(): void
    {
        // Repositories
        $this->app->bind(
            ProjectRepositoryInterface::class,
            ProjectRepository::class
        );
        
        $this->app->bind(
            IssueRepositoryInterface::class,
            IssueRepository::class
        );

        // Services
        $this->app->bind(
            ProjectServiceInterface::class,
            ProjectService::class
        );

        $this->app->bind(
            ProjectItemServiceInterface::class,
            ProjectItemService::class
        );

        $this->app->bind(
            ProjectProgressServiceInterface::class,
            ProjectProgressService::class
        );

        $this->app->bind(
            ProjectChartServiceInterface::class,
            ProjectChartService::class
        );
    }
}
