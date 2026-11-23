@extends('layouts.print')

@section('title', __('general.project_dashboard') . ' - ' . $project->name)

@php
// Helper to check if component is visible
$isComponentVisible = function($component, $visibleComponents) {
    // Default to true if not specified (show all components)
    return !isset($visibleComponents[$component]) || $visibleComponents[$component] === true;
};
@endphp

@section('content')
<style>
    .project-dashboard {
        font-family: "Plex Arabic", "Segoe UI", Tahoma, sans-serif;
    }
    .dashboard-header {
        background: #213c7a;
        color: #ffffff;
        padding: 1.5rem;
        border-radius: 10px;
        margin-bottom: 2rem;
    }
    .dashboard-header h1, .dashboard-header p, .dashboard-header small {
        color: #ffffff !important;
    }
    .stat-card {
        border: 1px solid #e2e7ee;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        background: #fff;
    }
    .stat-icon {
        width: 54px;
        height: 54px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        margin-bottom: 1rem;
    }
    .stat-icon.bg-primary { background-color: #eaeff9 !important; color: #213c7a !important; }
    .stat-icon.bg-warning { background-color: #fbf1df !important; color: #a96a10 !important; }
    .stat-icon.bg-info { background-color: #e8eef9 !important; color: #2d4f9c !important; }
    .stat-icon.bg-success { background-color: #e3f3eb !important; color: #1b7f55 !important; }
    .stat-icon.bg-danger { background-color: #fbe8e5 !important; color: #bb3428 !important; }
    .table-responsive {
        margin-bottom: 2rem;
    }
    .print-table {
        font-size: 0.9rem;
    }
    @media print {
        @page {
            size: A4 landscape;
            margin: 1cm;
        }
        .no-print {
            display: none !important;
        }
        body {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .stat-card {
            page-break-inside: avoid;
        }
    }
</style>

<div class="project-dashboard">
    <div class="dashboard-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h3 mb-2">{{ __('general.project_dashboard') }}</h1>
                <p class="mb-0">{{ $project->name }}@if($project->client) - {{ $project->client->name }}@endif</p>
            </div>
            <div class="col-md-4 text-md-end">
                <span class="badge bg-{{ $projectStatus['color'] }}">
                    <i class="fas fa-{{ $projectStatus['icon'] }} me-1"></i>
                    {{ $projectStatus['message'] }}
                </span>
                <div class="mt-2">
                    <small>{{ __('general.last_updated') }}: {{ now()->format('d/m/Y H:i') }}</small>
                </div>
            </div>
        </div>
    </div>


    @php
        $sectionsOrder = $sectionsOrder ?? [
            'statistics-cards', 'total-weighted-progress',
            'bar-chart', 'subprojects-chart', 'categories-chart',
            'subproject-items-chart', 'category-items-chart',
            'hierarchical-view', 'work-items-progress',
            'recent-activity', 'timeline', 'client-info', 'employees-info',
        ];
    @endphp

    @foreach($sectionsOrder as $sectionId)
        @if(!$isComponentVisible($sectionId, $visibleComponents ?? []))
            @continue
        @endif

        @switch($sectionId)

            @case('statistics-cards')
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div class="ms-3">
                                <h3 class="fw-bold mb-0">{{ $totalItems }}</h3>
                                <p class="text-muted mb-0">{{ __('general.work_items') }}</p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-primary" style="width: {{ min($overallProgress, 100) }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <div class="ms-3">
                                <h3 class="fw-bold mb-0">{{ abs($daysPassed) }}</h3>
                                <p class="text-muted mb-0">{{ __('general.days_passed') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-success bg-opacity-10 text-success">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="ms-3">
                                <h3 class="fw-bold mb-0">{{ number_format($overallProgress, 1) }}%</h3>
                                <p class="text-muted mb-0">{{ __('general.overall_progress') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-info bg-opacity-10 text-info">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="ms-3">
                                <h3 class="fw-bold mb-0">{{ $totalEmployees }}</h3>
                                <p class="text-muted mb-0">{{ __('general.total_employees') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <div class="ms-3">
                                <h3 class="fw-bold mb-0">{{ $daysRemaining }}</h3>
                                <p class="text-muted mb-0">{{ __('general.days_remaining') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @break

            @case('total-weighted-progress')
            <div class="row mb-4">
                <div class="col-12">
                    <div class="stat-card">
                        <h5 class="fw-bold mb-3"><i class="fas fa-chart-line me-2"></i>{{ __('general.total_weighted_progress') }}</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('general.actual_weighted') }}:</strong></p>
                                <div class="progress mb-2" style="height:14px;">
                                    <div class="progress-bar bg-primary" style="width:{{ min($totalWeightedProgress ?? 0, 100) }}%"></div>
                                </div>
                                <small class="text-muted">{{ number_format($totalWeightedProgress ?? 0, 2) }}%</small>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('general.planned_weighted') }}:</strong></p>
                                <div class="progress mb-2" style="height:14px;">
                                    <div class="progress-bar bg-warning" style="width:{{ min($totalPlannedWeightedProgress ?? 0, 100) }}%"></div>
                                </div>
                                <small class="text-muted">{{ number_format($totalPlannedWeightedProgress ?? 0, 2) }}%</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @break

            @case('bar-chart')
            <div class="row mb-4">
                <div class="col-12">
                    <div class="stat-card">
                        <h5 class="fw-bold mb-3"><i class="fas fa-chart-bar me-2"></i>{{ __('general.project_progress_overview') }}</h5>
                        @if(isset($chartData['work_items']) && count($chartData['work_items']) > 0)
                            @php $barChartHeight = max(300, count($chartData['work_items']) * 35); @endphp
                            <div style="position:relative; height:{{ $barChartHeight }}px; width:100%;">
                                <canvas id="printProgressChart"></canvas>
                            </div>
                        @else
                            <p class="text-muted">{{ __('general.no_items_found') }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @break

            @case('subprojects-chart')
            <div class="row mb-4">
                <div class="col-12">
                    <div class="stat-card">
                        <h5 class="fw-bold mb-3"><i class="fas fa-sitemap me-2"></i>{{ __('general.subprojects_progress') }}</h5>
                        @if(isset($subprojectsChartData['labels']) && count($subprojectsChartData['labels']) > 0)
                            @php $subChartHeight = max(300, count($subprojectsChartData['labels']) * 35); @endphp
                            <div style="position:relative; height:{{ $subChartHeight }}px; width:100%;">
                                <canvas id="printSubprojectsChart"></canvas>
                            </div>
                        @else
                            <p class="text-muted">{{ __('general.no_subprojects_found') }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @break

            @case('categories-chart')
            <div class="row mb-4">
                <div class="col-12">
                    <div class="stat-card">
                        <h5 class="fw-bold mb-3"><i class="fas fa-tags me-2"></i>{{ __('general.categories_progress') }}</h5>
                        @if(isset($categoriesChartData['labels']) && count($categoriesChartData['labels']) > 0)
                            @php $catChartHeight = max(300, count($categoriesChartData['labels']) * 35); @endphp
                            <div style="position:relative; height:{{ $catChartHeight }}px; width:100%;">
                                <canvas id="printCategoriesChart"></canvas>
                            </div>
                        @else
                            <p class="text-muted">{{ __('general.no_items_found') }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @break

            @case('subproject-items-chart')
            <div class="row mb-4">
                <div class="col-12">
                    <div class="stat-card">
                        <h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>{{ __('general.items_by_subproject') }}</h5>
                        @if(isset($subprojectItemsChartData) && count($subprojectItemsChartData) > 0)
                            @foreach($subprojectItemsChartData as $spKey => $data)
                                @if(isset($data['labels']) && count($data['labels']) > 0)
                                    @php
                                        $spItemChartHeight = max(250, count($data['labels']) * 35);
                                        $spChartId = 'printSpItemChart_' . $loop->index;
                                    @endphp
                                    <h6 class="fw-bold mt-3 mb-2">{{ $spKey }}</h6>
                                    <div style="position:relative; height:{{ $spItemChartHeight }}px; width:100%; margin-bottom:1.5rem;">
                                        <canvas id="{{ $spChartId }}"
                                                data-labels="{{ json_encode($data['labels']) }}"
                                                data-progress="{{ json_encode($data['weighted_ratios'] ?? $data['completion_percentages'] ?? []) }}"
                                                data-planned="{{ json_encode($data['planned_weighted_ratios'] ?? []) }}">
                                        </canvas>
                                    </div>
                                @endif
                            @endforeach
                        @else
                            <p class="text-muted">{{ __('general.no_items_found') }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @break

            @case('category-items-chart')
            <div class="row mb-4">
                <div class="col-12">
                    <div class="stat-card">
                        <h5 class="fw-bold mb-3"><i class="fas fa-folder me-2"></i>{{ __('general.items_by_category') }}</h5>
                        @if(isset($categoryItemsChartData) && count($categoryItemsChartData) > 0)
                            @foreach($categoryItemsChartData as $catKey => $data)
                                @if(isset($data['labels']) && count($data['labels']) > 0)
                                    @php
                                        $catItemChartHeight = max(250, count($data['labels']) * 35);
                                        $catChartId = 'printCatItemChart_' . $loop->index;
                                    @endphp
                                    <h6 class="fw-bold mt-3 mb-2">{{ $catKey }}</h6>
                                    <div style="position:relative; height:{{ $catItemChartHeight }}px; width:100%; margin-bottom:1.5rem;">
                                        <canvas id="{{ $catChartId }}"
                                                data-labels="{{ json_encode($data['labels']) }}"
                                                data-progress="{{ json_encode($data['weighted_ratios'] ?? $data['completion_percentages'] ?? []) }}"
                                                data-planned="{{ json_encode($data['planned_weighted_ratios'] ?? []) }}">
                                        </canvas>
                                    </div>
                                @endif
                            @endforeach
                        @else
                            <p class="text-muted">{{ __('general.no_items_found') }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @break

            @case('hierarchical-view')
            <div class="row mb-4">
                <div class="col-12">
                    <div class="stat-card">
                        <h5 class="fw-bold mb-3"><i class="fas fa-sitemap me-2"></i>{{ __('general.hierarchical_view') }}</h5>
                        @if(isset($hierarchicalData) && count($hierarchicalData) > 0)
                            @foreach($hierarchicalData as $subprojectName => $subprojectData)
                                <div class="mb-4 border-bottom pb-3">
                                    <h6 class="fw-bold">
                                        <i class="fas fa-folder-open me-2"></i>{{ $subprojectName }}
                                        @if(isset($subprojectData['subproject']) && $subprojectData['subproject']->weight)
                                            <span class="badge bg-info ms-2">{{ $subprojectData['subproject']->weight }}%</span>
                                        @endif
                                    </h6>
                                    <div class="ms-4 mt-2">
                                        <p class="small text-muted mb-2">{{ number_format($subprojectData['progress'], 1) }}% {{ __('general.completed') }}</p>
                                        <div class="progress mb-3" style="height: 8px;">
                                            <div class="progress-bar bg-primary" style="width: {{ $subprojectData['progress'] }}%"></div>
                                        </div>
                                        @foreach($subprojectData['categories'] as $categoryName => $categoryData)
                                            <div class="mb-3">
                                                <h6 class="small fw-bold">{{ $categoryName }} <span class="badge bg-secondary">{{ $categoryData['count'] }} {{ __('general.items') }}</span></h6>
                                                <div class="ms-3">
                                                    @foreach($categoryData['items'] as $item)
                                                        @php
                                                        $completionPercentage = $item->total_quantity > 0
                                                            ? ($item->completed_quantity / $item->total_quantity) * 100 : 0;
                                                        @endphp
                                                        <div class="mb-2 small">
                                                            <strong>{{ $item->workItem->name ?? '-' }}</strong>
                                                            @if($item->notes)<span class="text-muted">({{ $item->notes }})</span>@endif
                                                            - {{ number_format($completionPercentage, 1) }}%
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p class="text-muted">{{ __('general.no_items_found') }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @break

            @case('work-items-progress')
            <div class="row mb-4">
                <div class="col-12">
                    <div class="stat-card">
                        <h5 class="fw-bold mb-3"><i class="fas fa-list-check me-2"></i>{{ __('general.work_items_progress') }}</h5>
                        <div class="row">
                            @foreach ($project->items as $item)
                                @php
                                $completionPercentage = $item->total_quantity > 0
                                    ? ($item->completed_quantity / $item->total_quantity) * 100 : 0;
                                @endphp
                                <div class="col-md-6 mb-3">
                                    <div class="border rounded p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <strong>{{ $item->workItem->name ?? '-' }}</strong>
                                                @if($item->notes)<small class="text-muted d-block">({{ $item->notes }})</small>@endif
                                            </div>
                                            <span class="fw-bold text-primary">{{ number_format($completionPercentage, 1) }}%</span>
                                        </div>
                                        <div class="progress mb-2" style="height: 10px;">
                                            <div class="progress-bar bg-primary" style="width: {{ $completionPercentage }}%"></div>
                                        </div>
                                        <div class="small text-muted">
                                            {{ __('general.completed') }}: {{ number_format($item->completed_quantity, 2) }} /
                                            {{ __('general.total') }}: {{ number_format($item->total_quantity, 2) }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @break

            @case('recent-activity')
            <div class="row mb-4">
                <div class="col-12">
                    <div class="stat-card">
                        <h5 class="fw-bold mb-3"><i class="fas fa-history me-2"></i>{{ __('general.daily_progress_record') }}</h5>
                        @if(isset($recentProgress) && $recentProgress->count() > 0)
                            @foreach ($recentProgress as $date => $progresses)
                                <div class="mb-4 border-bottom pb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</span>
                                        <span class="badge bg-light text-dark">{{ $progresses->sum('quantity') }} {{ __('general.units') }}</span>
                                    </div>
                                    @foreach ($progresses as $progress)
                                        <div class="d-flex align-items-center mb-2 p-2 bg-light rounded">
                                            <div class="flex-grow-1 ms-3">
                                                <div class="fw-medium">{{ $progress->employee->name ?? __('general.unknown') }}</div>
                                                <div class="fw-semibold text-dark">{{ $progress->projectItem->workItem->name ?? '-' }}</div>
                                                @if($progress->projectItem->workItem->category ?? null)
                                                    <small class="text-muted d-block"><i class="fas fa-folder me-1"></i>{{ $progress->projectItem->workItem->category->name }}</small>
                                                @endif
                                                @if($progress->projectItem->notes ?? null)
                                                    <small class="text-muted d-block"><i class="fas fa-sticky-note me-1"></i>{{ Str::limit($progress->projectItem->notes, 30) }}</small>
                                                @endif
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-bold text-primary">{{ $progress->quantity }}</div>
                                                <small class="text-muted">{{ $progress->projectItem->workItem->unit ?? '' }}</small>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        @else
                            <p class="text-muted">{{ __('general.no_items_found') }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @break

            @case('timeline')
            <div class="row mb-4">
                <div class="col-12">
                    <div class="stat-card">
                        <h5 class="fw-bold mb-3"><i class="fas fa-project-diagram me-2"></i>{{ __('general.project_timeline') }}</h5>
                        <div class="mb-4">
                            @if($project->start_date)
                                <div class="mb-3 pb-3 border-bottom">
                                    <div class="fw-bold text-primary">{{ __('general.start_date') }}</div>
                                    <div class="text-muted">{{ \Carbon\Carbon::parse($project->start_date)->format('d/m/Y') }}</div>
                                    <small class="text-success">{{ __('general.project_kickoff') }}</small>
                                </div>
                            @endif
                            <div class="mb-3 pb-3 border-bottom">
                                <div class="fw-bold text-primary">{{ __('general.current_date') }}</div>
                                <div class="text-muted">{{ now()->format('d/m/Y') }}</div>
                                <small class="text-info">{{ __('general.progress') }}: {{ number_format($overallProgress, 1) }}%</small>
                            </div>
                            @if ($project->end_date)
                                <div class="mb-3 pb-3 border-bottom">
                                    <div class="fw-bold text-primary">{{ __('general.end_date') }}</div>
                                    <div class="text-muted">{{ \Carbon\Carbon::parse($project->end_date)->format('d/m/Y') }}</div>
                                    <small class="text-warning">{{ __('general.expected_completion') }}</small>
                                </div>
                            @endif
                        </div>
                        <div class="mt-4 p-3 bg-light rounded">
                            <h6 class="fw-bold mb-3">{{ __('general.project_information') }}</h6>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <small class="text-muted">{{ __('general.client') }}:</small>
                                    <div class="fw-medium">{{ $project->client->name ?? __('general.not_specified') }}</div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <small class="text-muted">{{ __('general.working_zone') }}:</small>
                                    <div class="fw-medium">{{ $project->working_zone ?? __('general.not_specified') }}</div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <small class="text-muted">{{ __('general.status') }}:</small>
                                    <div class="fw-medium text-capitalize">{{ $project->status }}</div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <small class="text-muted">{{ __('general.project_duration') }}:</small>
                                    <div class="fw-medium">{{ $daysPassed + $daysRemaining }} {{ __('general.days') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @break

            @case('client-info')
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="stat-card">
                        <h5 class="fw-bold mb-3"><i class="fas fa-building me-2"></i>{{ __('general.client_information') }}</h5>
                        @if($project->client)
                            <p class="mb-2"><strong>{{ __('general.client_name') }}:</strong> {{ $project->client->name }}</p>
                            @if($project->client->contact_person)
                                <p class="mb-2"><strong>{{ __('general.contact_person') }}:</strong> {{ $project->client->contact_person }}</p>
                            @endif
                            @if($project->client->phone)
                                <p class="mb-2"><strong>{{ __('general.phone') }}:</strong> {{ $project->client->phone }}</p>
                            @endif
                            @if($project->client->email)
                                <p class="mb-2"><strong>{{ __('general.email') }}:</strong> {{ $project->client->email }}</p>
                            @endif
                            @if($project->client->address)
                                <p class="mb-2"><strong>{{ __('general.address') }}:</strong> {{ $project->client->address }}</p>
                            @endif
                        @else
                            <p class="text-muted">{{ __('general.no_client_assigned') }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @break

            @case('employees-info')
            <div class="row mb-4">
                <div class="col-12">
                    <div class="stat-card">
                        <h5 class="fw-bold mb-3"><i class="fas fa-users me-2"></i>{{ __('general.team_members') }}</h5>
                        @forelse($project->employees as $employee)
                            @php
                            $employeeProgress = (float) ($employee->project_progress_sum ?? 0);
                            @endphp
                            <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                <div class="ms-3">
                                    <div class="fw-bold">{{ $employee->name }}</div>
                                    <div class="small text-muted">{{ $employee->position ?? __('general.employee') }}</div>
                                    <div class="mt-2">
                                        <span class="badge bg-primary">{{ $employeeProgress }} {{ __('general.units') }}</span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted">{{ __('general.no_employees_assigned') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
            @break

        @endswitch
    @endforeach

</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    // â”€â”€ Ù…Ø³Ø§Ø¹Ø¯Ø©: Ø±Ø³Ù… Ø§Ù„Ù‚ÙŠÙ… Ù ÙˆÙ‚ Ø§Ù„Ø£Ø¹Ù…Ø¯Ø© â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    function drawDataLabels(chart) {
        const ctx = chart.ctx;
        ctx.save();
        ctx.font = 'bold 11px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        chart.data.datasets.forEach(function (dataset, datasetIndex) {
            const meta = chart.getDatasetMeta(datasetIndex);
            meta.data.forEach(function (bar, index) {
                const data = dataset.data[index];
                if (data !== null && data !== undefined && bar && bar.height > 0) {
                    const x = bar.x;
                    const y = bar.y - 14;
                    const text = parseFloat(data).toFixed(1) + '%';
                    const tw = ctx.measureText(text).width;
                    ctx.fillStyle = 'rgba(0,0,0,0.65)';
                    ctx.fillRect(x - tw / 2 - 3, y - 8, tw + 6, 16);
                    ctx.fillStyle = '#fff';
                    ctx.fillText(text, x, y);
                }
            });
        });
        ctx.restore();
    }

    // â”€â”€ Ø¥Ø¹Ø¯Ø§Ø¯Ø§Øª Ù…Ø´ØªØ±ÙƒØ© Ù„Ù„Ù€ charts â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    function makeChartOptions(animate) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            animation: animate ? {
                duration: 600,
                onComplete: function () { drawDataLabels(this); }
            } : false,
            plugins: {
                legend: { display: true, position: 'top' },
                tooltip: { enabled: false }
            },
            scales: {
                x: { grouped: true },
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: { callback: function (v) { return v + '%'; } }
                }
            }
        };
    }

    function makeDatasets(progress, planned) {
        return [
            {
                label: '{{ __('general.progress') }}',
                data: progress,
                backgroundColor: 'rgba(255, 99, 71, 0.7)',
                borderColor: 'rgba(255, 99, 71, 1)',
                borderWidth: 1,
                borderRadius: 0
            },
            {
                label: '{{ __('general.planned_progress') }}',
                data: planned,
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1,
                borderRadius: 0
            }
        ];
    }

    var allCharts = [];

    // â”€â”€ 1. Bar Chart (work items) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    var progressCanvas = document.getElementById('printProgressChart');
    if (progressCanvas) {
        var c = new Chart(progressCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: @json($chartData['work_items'] ?? []),
                datasets: makeDatasets(
                    @json($chartData['weighted_ratios'] ?? []),
                    @json($chartData['planned_weighted_ratios'] ?? [])
                )
            },
            options: makeChartOptions(true)
        });
        allCharts.push(c);
    }

    // â”€â”€ 2. Subprojects Chart â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    var subprojectsCanvas = document.getElementById('printSubprojectsChart');
    if (subprojectsCanvas) {
        var c2 = new Chart(subprojectsCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: @json($subprojectsChartData['labels'] ?? []),
                datasets: makeDatasets(
                    @json($subprojectsChartData['weighted_ratios'] ?? []),
                    @json($subprojectsChartData['planned_weighted_ratios'] ?? [])
                )
            },
            options: makeChartOptions(true)
        });
        allCharts.push(c2);
    }

    // â”€â”€ 3. Categories Chart â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    var categoriesCanvas = document.getElementById('printCategoriesChart');
    if (categoriesCanvas) {
        var c3 = new Chart(categoriesCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: @json($categoriesChartData['labels'] ?? []),
                datasets: makeDatasets(
                    @json($categoriesChartData['weighted_ratios'] ?? []),
                    @json($categoriesChartData['planned_weighted_ratios'] ?? [])
                )
            },
            options: makeChartOptions(true)
        });
        allCharts.push(c3);
    }

    // â”€â”€ 4. Subproject-Items Charts (dynamic per subproject) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.querySelectorAll('[id^="printSpItemChart_"]').forEach(function (canvas) {
        try {
            var labels   = JSON.parse(canvas.dataset.labels   || '[]');
            var progress = JSON.parse(canvas.dataset.progress || '[]');
            var planned  = JSON.parse(canvas.dataset.planned  || '[]');
            var c4 = new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: { labels: labels, datasets: makeDatasets(progress, planned) },
                options: makeChartOptions(true)
            });
            allCharts.push(c4);
        } catch (e) { /* skip */ }
    });

    // â”€â”€ 5. Category-Items Charts (dynamic per category) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.querySelectorAll('[id^="printCatItemChart_"]').forEach(function (canvas) {
        try {
            var labels   = JSON.parse(canvas.dataset.labels   || '[]');
            var progress = JSON.parse(canvas.dataset.progress || '[]');
            var planned  = JSON.parse(canvas.dataset.planned  || '[]');
            var c5 = new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: { labels: labels, datasets: makeDatasets(progress, planned) },
                options: makeChartOptions(true)
            });
            allCharts.push(c5);
        } catch (e) { /* skip */ }
    });

    // â”€â”€ ØªØ£Ø®ÙŠØ± Ø§Ù„Ø·Ø¨Ø§Ø¹Ø© Ø­ØªÙ‰ ØªÙ†ØªÙ‡ÙŠ Ø±Ø³Ù… Ø¬Ù…ÙŠØ¹ Ø§Ù„Ù€ charts â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // Ù†Ø³ØªØ®Ø¯Ù… beforeAutoPrint hook Ø§Ù„Ù…Ø¯Ø¹ÙˆÙ… Ù…Ù† layouts/print.blade.php
    window.beforeAutoPrint = function () {
        // Ù†Ø¹Ø·ÙŠ ÙˆÙ‚Øª ÙƒØ§Ù Ù  Ù„Ù„Ù€ animation (700ms) Ø«Ù… Ù†Ø·Ø¨Ø¹
        return new Promise(function (resolve) {
            setTimeout(resolve, 700);
        });
    };

})();
</script>
@endsection
