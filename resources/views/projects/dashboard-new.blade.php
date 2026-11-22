@extends('layouts.app')

@section('title', __('general.project_dashboard'))

@section('content')
    @include('components.project-dashboard.styles')

    <div class="project-dashboard">
        <div class="dashboard-container">
            @include('components.project-dashboard.header', ['project' => $project, 'projectStatus' => $projectStatus])

            @include('components.project-dashboard.actions', ['project' => $project])

            @include('components.project-dashboard.stats-cards', [
                'totalItems' => $totalItems,
                'overallProgress' => $overallProgress,
                'daysPassed' => $daysPassed,
                'daysRemaining' => $daysRemaining,
                'totalEmployees' => $totalEmployees
            ])

            
            <div class="container-fluid">
                <div class="row g-4 mb-5">
                    @include('components.project-dashboard.client-info', ['project' => $project])

                    @include('components.project-dashboard.team-members', ['project' => $project])
                </div>
            </div>

            <div class="container-fluid">
                @include('components.project-dashboard.charts', [
                    'chartData' => $chartData,
                    'overallProgress' => $overallProgress
                ])

                @include('components.project-dashboard.work-items-progress', [
                    'project' => $project,
                    'totalItems' => $totalItems
                ])

                
                <div class="row g-4">
                    @include('components.project-dashboard.recent-activity', ['recentProgress' => $recentProgress])

                    @include('components.project-dashboard.project-timeline', [
                        'project' => $project,
                        'overallProgress' => $overallProgress,
                        'daysPassed' => $daysPassed,
                        'daysRemaining' => $daysRemaining
                    ])
                </div>
            </div>
        </div>
    </div>
@endsection

