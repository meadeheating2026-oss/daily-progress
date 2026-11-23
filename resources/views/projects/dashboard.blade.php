@extends('layouts.app')

@section('title', __('general.project_dashboard'))

@section('content')

@if(!$project)
<div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle"></i> {{ __('general.project_not_found') }}
</div>
@php
return;
@endphp
@endif
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/project-dashboard.css') }}">
@endpush
<div class="d-flex flex-wrap align-items-center justify-content-end gap-2 mb-3 no-print">
    <button type="button" class="btn btn-sm btn-outline-secondary bg-white" data-bs-toggle="modal" data-bs-target="#componentsModal">
        <i class="fas fa-sliders-h me-1 text-primary"></i>{{ __('general.customize_view') }}
    </button>
    <button type="button" class="btn btn-sm btn-outline-secondary bg-white no-print" id="printDashboardBtn">
        <i class="fas fa-print me-1 text-primary"></i>{{ __('general.print') }}
    </button>
    <button type="button" class="btn btn-sm btn-outline-primary bg-white no-print" id="exportPdfBtn">
        <i class="fas fa-file-pdf me-1"></i>Export PDF
    </button>
    <button type="button" class="btn btn-sm btn-outline-secondary bg-white" data-bs-toggle="modal" data-bs-target="#calculationFormulasModal">
        <i class="fas fa-calculator me-1 text-primary"></i>{{ __('general.calculation_formulas') }}
    </button>
</div>
<div class="project-dashboard">
  
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h2 mb-2"><i class="fas fa-chart-line me-2"></i>{{ __('general.project_dashboard') }}</h1>
                    <p class="mb-0 text-muted">{{ $project->name }}@if($project->client) - {{ $project->client->name }}@endif</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="status-badge bg-{{ $projectStatus['color'] }}">
                        <i class="fas fa-{{ $projectStatus['icon'] }} me-1"></i>
                        {{ $projectStatus['message'] }}
                    </span>
                    <div class="mt-2 text-muted">
                        <small><i class="fas fa-sync-alt me-1"></i>{{ __('general.last_updated') }}:
                            {{ now()->format('d/m/Y H:i') }}</small>
                    </div>

                </div>
            </div>
        </div>

        <div class="container-fluid py-4" id="dashboardSectionsContainer">
            <div class="row g-4 mb-5 dashboard-component" data-component="statistics-cards">
                <div class="col-12 d-flex justify-content-between align-items-center mb-2 no-print">
                    <h5 class="gradient-text fw-bold mb-0"><i class="fas fa-chart-pie me-2"></i>{{ __('general.statistics_cards') }}</h5>
                    <button type="button" class="btn btn-sm btn-outline-secondary section-print-btn" data-section="statistics-cards">
                        <i class="fas fa-print me-1"></i>{{ __('general.print') }}
                    </button>
                </div>
                <div class="col-xl-4 col-md-6">
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
                                <div class="progress-bar bg-primary" style="width: {{ min($overallProgress, 100) }}%">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon" style="background: rgba(71, 85, 105, 0.10); color: #334155;">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <div class="ms-3">
                                <h3 class="fw-bold mb-0">{{ abs($daysPassed) }}</h3>
                                <p class="text-muted mb-0">{{ __('general.days_passed') }}</p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="progress" style="height: 6px;">
                                @php
                                $totalDays = $daysPassed + $daysRemaining;
                                $passedPercentage = $totalDays > 0 ? min(100, max(0, ($daysPassed / $totalDays) * 100)) : 0;
                                @endphp
                                <div class="progress-bar" style="width: {{ $passedPercentage }}%; background-color: #475569;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="ms-3">
                                <h3 class="fw-bold mb-0">{{ $totalEmployees }}</h3>
                                <p class="text-muted mb-0">{{ __('general.total_employees') }}</p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-primary" style="width: 100%; opacity: 0.75;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <!-- Total Weighted Progress Section -->
        @if($project->subprojects->count() > 0)
        <div class="row g-4 mb-5 dashboard-component" data-component="total-weighted-progress">
            <div class="col-12">
                <div class="d-flex justify-content-end mb-2 no-print">
                    <button type="button" class="btn btn-sm btn-outline-secondary section-print-btn" data-section="total-weighted-progress">
                        <i class="fas fa-print me-1"></i>{{ __('general.print') }}
                    </button>
                </div>
                <div class="stat-card" style="margin-bottom: 0;">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h5 class="mb-1 fw-bold text-dark"><i class="fas fa-chart-line me-2 text-primary"></i>{{ __('general.total_weighted_progress') }}</h5>
                            <p class="mb-0 text-muted small">{{ __('general.total_weighted_progress_description') }}</p>
                        </div>
                    </div>
                    <div id="totalWeightedProgressDisplay" class="w-100">
                        <div class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">{{ __('general.loading') }}...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="row g-4 mb-5 dashboard-component" data-component="bar-chart">
            <div class="col-lg-12">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="gradient-text fw-bold mb-0">
                            <i class="fas fa-chart-bar me-2"></i>{{ __('general.project_progress_overview') }}
                        </h4>
                        <div class="d-flex gap-2">
                            <button type="button" id="toggleChartGroupedView" class="btn btn-sm btn-primary">
                                <i class="fas fa-layer-group me-1"></i><span id="toggleChartGroupedText"></span>
                            </button>
                            <button type="button" id="selectAllItems" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-check-double me-1"></i>{{ __('general.select_all') }}
                            </button>
                            <button type="button" id="deselectAllItems" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>{{ __('general.deselect_all') }}
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary no-print section-print-btn" data-section="bar-chart">
                                <i class="fas fa-print me-1"></i>{{ __('general.print') }}
                            </button>
                        </div>
                    </div>
                    
                    <!-- Items Filter -->
                    <div class="mb-4 p-3 rounded" style="background-color: #f8f9fa; border: 1px solid #dee2e6;">
                        <label class="form-label fw-semibold mb-2">
                            <i class="fas fa-filter me-2"></i>{{ __('general.filter_items') }}
                        </label>
                        <div id="itemsFilterContainer" class="d-flex flex-wrap gap-3" style="max-height: 150px; overflow-y: auto;">
                            <!-- Checkboxes will be generated by JavaScript -->
                        </div>
                    </div>
                    
                    <div class="chart-container">
                        <canvas id="progressChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5 dashboard-component" data-component="subprojects-chart">
            <div class="col-lg-12">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h4 class="gradient-text fw-bold mb-0">
                            <i class="fas fa-sitemap me-2"></i>{{ __('general.subprojects_progress') }}
                        </h4>
                        <button type="button" class="btn btn-sm btn-outline-secondary no-print section-print-btn" data-section="subprojects-chart">
                            <i class="fas fa-print me-1"></i>{{ __('general.print') }}
                        </button>
                    </div>
                    <p class="text-muted small mb-4" style="font-size: 0.75rem;">
                        Progress = (Completed Quantity / Total Quantity) × 100 | Planned Progress = (Planned Total Quantity / Total Quantity) × 100
                    </p>
                    <div class="chart-container">
                        <canvas id="subprojectsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5 dashboard-component" data-component="categories-chart">
            <div class="col-lg-12">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h4 class="gradient-text fw-bold mb-0">
                            <i class="fas fa-tags me-2"></i>{{ __('general.categories_progress') }}
                        </h4>
                        <button type="button" class="btn btn-sm btn-outline-secondary no-print section-print-btn" data-section="categories-chart">
                            <i class="fas fa-print me-1"></i>{{ __('general.print') }}
                        </button>
                    </div>
                    <p class="text-muted small mb-4" style="font-size: 0.75rem;">
                        Progress = (Completed Quantity / Total Quantity) × 100 | Planned Progress = (Planned Total Quantity / Total Quantity) × 100
                    </p>
                    <div class="chart-container">
                        <canvas id="categoriesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5 dashboard-component" data-component="subproject-items-chart">
            <div class="col-lg-12">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="gradient-text fw-bold mb-0">
                            <i class="fas fa-list me-2"></i>{{ __('general.items_by_subproject') }}
                        </h4>
                        <div class="d-flex align-items-center gap-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="showAllSubprojectItems" checked>
                                <label class="form-check-label small" for="showAllSubprojectItems">
                                    {{ __('general.show_all') }}
                                </label>
                            </div>
                        <select id="subprojectSelect" class="form-select form-select-sm" style="width: auto;">
                            <option value="">{{ __('general.select_subproject') }}</option>
                            @if(isset($subprojectsWithItems))
                                @foreach($subprojectsWithItems as $subproject)
                                    <option value="{{ $subproject->name }}">{{ $subproject->name }}</option>
                                @endforeach
                            @else
                                @foreach($project->subprojects as $subproject)
                                    <option value="{{ $subproject->name }}">{{ $subproject->name }}</option>
                                @endforeach
                                @if($project->items->whereNull('subproject_name')->isNotEmpty())
                                    <option value="{{ __('general.without_subproject') }}">{{ __('general.without_subproject') }}</option>
                                @endif
                            @endif
                        </select>
                            <button type="button" class="btn btn-sm btn-outline-secondary no-print section-print-btn" data-section="subproject-items-chart">
                                <i class="fas fa-print me-1"></i>{{ __('general.print') }}
                            </button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="subprojectItemsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5 dashboard-component" data-component="category-items-chart">
            <div class="col-lg-12">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="gradient-text fw-bold mb-0">
                            <i class="fas fa-folder me-2"></i>{{ __('general.items_by_category') }}
                        </h4>
                        <div class="d-flex align-items-center gap-2">
                        <select id="categorySelect" class="form-select form-select-sm" style="width: auto;">
                            <option value="">{{ __('general.select_category') }}</option>
                            @php
                                $categories = $project->items->map(function($item) {
                                    return $item->workItem && $item->workItem->category 
                                        ? $item->workItem->category->name 
                                        : null;
                                })->filter()->unique()->sort();
                            @endphp
                            @foreach($categories as $categoryName)
                                <option value="{{ $categoryName }}">{{ $categoryName }}</option>
                            @endforeach
                            @if($project->items->filter(function($item) { return !$item->workItem || !$item->workItem->category; })->isNotEmpty())
                                <option value="{{ __('general.uncategorized') }}">{{ __('general.uncategorized') }}</option>
                            @endif
                        </select>
                            <button type="button" class="btn btn-sm btn-outline-secondary no-print section-print-btn" data-section="category-items-chart">
                                <i class="fas fa-print me-1"></i>{{ __('general.print') }}
                            </button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="categoryItemsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5 dashboard-component" data-component="hierarchical-view">
            <div class="col-12">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="gradient-text fw-bold mb-0">
                            <i class="fas fa-sitemap me-2"></i>{{ __('general.hierarchical_view') }}
                        </h4>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="text" id="searchItems" class="form-control form-control-sm d-inline-block" style="width: 200px;" placeholder="{{ __('general.search') }}...">
                            <span class="badge bg-primary ms-2">{{ $totalItems }} {{ __('general.items') }}</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary no-print section-print-btn" data-section="hierarchical-view">
                                <i class="fas fa-print me-1"></i>{{ __('general.print') }}
                            </button>
                        </div>
                    </div>

                    <div class="accordion accordion-flush" id="hierarchicalAccordion">
                        @if(isset($hierarchicalData) && count($hierarchicalData) > 0)
                        @foreach($hierarchicalData as $subprojectName => $subprojectData)
                        @php
                        $subprojectId = 'subproject-' . str_replace(' ', '-', $subprojectName);
                        $subproject = $subprojectData['subproject'];
                        @endphp
                        <div class="accordion-item mb-2 border rounded hierarchical-subproject" data-subproject-name="{{ strtolower($subprojectName) }}">
                            <h2 class="accordion-header" id="heading{{ $loop->index }}">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $loop->index }}" aria-expanded="false">
                                    <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                        <div>
                                            <i class="fas fa-folder-open me-2 text-primary"></i>
                                            <strong>{{ $subprojectName }}</strong>
                                            @if($subproject && $subproject->weight)
                                            <span class="badge ms-2" style="background: rgba(33, 60, 122, 0.10); color: #213c7a; border: 1px solid rgba(33, 60, 122, 0.2);">{{ $subproject->weight }}%</span>
                                            @endif
                                        </div>
                                        <div class="text-end">
                                            <div class="small text-muted mb-1">
                                                {{ number_format($subprojectData['progress'], 1) }}% {{ __('general.completed') }}
                                            </div>
                                            <div class="progress" style="height: 6px; width: 150px;">
                                                <div class="progress-bar @if($subprojectData['progress'] >= 80) bg-success @else bg-primary @endif"
                                                    style="width: {{ $subprojectData['progress'] }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse{{ $loop->index }}" class="accordion-collapse collapse" data-bs-parent="#hierarchicalAccordion">
                                <div class="accordion-body">
                                    @foreach($subprojectData['categories'] as $categoryName => $categoryData)
                                    <div class="card mb-3 hierarchical-category" data-category-name="{{ strtolower($categoryName) }}">
                                        <div class="card-header bg-light">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <i class="fas fa-tag me-2 text-secondary"></i>
                                                    <strong>{{ $categoryName }}</strong>
                                                    <span class="badge bg-secondary ms-2">{{ $categoryData['count'] }} {{ __('general.items') }}</span>
                                                </div>
                                                <div class="text-end">
                                                    <small class="text-muted">{{ number_format($categoryData['progress'], 1) }}%</small>
                                                    <div class="progress" style="height: 4px; width: 100px; margin-top: 2px;">
                                                        <div class="progress-bar bg-primary" style="width: {{ $categoryData['progress'] }}%"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                @foreach($categoryData['items'] as $item)
                                                @php
                                                $completionPercentage = $item->total_quantity > 0
                                                ? ($item->completed_quantity / $item->total_quantity) * 100
                                                : 0;
                                                @endphp
                                                <div class="col-md-6 mb-3 item-card"
                                                    data-item-name="{{ strtolower($item->workItem->name ?? '') }}"
                                                    data-item-notes="{{ strtolower($item->notes ?? '') }}"
                                                    data-item-unit="{{ strtolower($item->workItem->unit ?? '') }}"
                                                    data-category-name="{{ strtolower($categoryName) }}">
                                                    <div class="border rounded p-3">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <div class="flex-grow-1">
                                                                <div class="fw-bold text-dark">{{ $item->workItem->name ?? '-' }}</div>
                                                                @if($item->subproject_name ?? null)
                                                                <small class="text-primary d-block"><i class="fas fa-folder-open me-1"></i>{{ $item->subproject_name }}</small>
                                                                @endif
                                                                @if($item->workItem->unit ?? null)
                                                                <small class="text-muted d-block"><i class="fas fa-ruler me-1"></i>{{ $item->workItem->unit }}</small>
                                                                @endif
                                                                @if($item->notes ?? null)
                                                                <small class="text-muted d-block"><i class="fas fa-sticky-note me-1"></i>{{ Str::limit($item->notes, 30) }}</small>
                                                                @endif
                                                            </div>
                                                            <span class="fw-bold text-primary ms-2">{{ number_format($completionPercentage, 1) }}%</span>
                                                        </div>
                                                        <div class="progress mb-2" style="height: 10px; border-radius: 5px;">
                                                            <div class="progress-bar @if ($completionPercentage >= 80) bg-success @else bg-primary @endif"
                                                                role="progressbar" style="width: {{ $completionPercentage }}%">
                                                            </div>
                                                        </div>
                                                        <div class="d-flex justify-content-between text-muted small">
                                                            <span><i class="fas fa-check-circle text-success me-1"></i>{{ number_format($item->completed_quantity, 2) }}</span>
                                                            <span><i class="fas fa-clock text-secondary me-1"></i>{{ number_format($item->remaining_quantity, 2) }}</span>
                                                            <span><i class="fas fa-list text-primary me-1"></i>{{ number_format($item->total_quantity, 2) }}</span>
                                                        </div>
                                                        @if($item->start_date && $item->end_date)
                                                        <div class="mt-2 pt-2 border-top">
                                                            <small class="text-muted">
                                                                <i class="fas fa-calendar me-1"></i>
                                                                {{ \Carbon\Carbon::parse($item->start_date)->format('d/m/Y') }} -
                                                                {{ \Carbon\Carbon::parse($item->end_date)->format('d/m/Y') }}
                                                            </small>
                                                        </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endforeach
                        @else
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">{{ __('general.no_items_found') }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5 dashboard-component" data-component="work-items-progress">
            <div class="col-12">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="gradient-text fw-bold mb-0">
                            <i class="fas fa-list-check me-2"></i>{{ __('general.work_items_progress') }}
                        </h4>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" id="toggleWorkItemsGroupedView" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-layer-group me-1"></i><span id="toggleWorkItemsGroupedText">{{ __('general.group_by_item') }}</span>
                            </button>
                            <span class="badge bg-primary" id="workItemsCount">{{ $totalItems }} {{ __('general.items') }}</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary no-print section-print-btn" data-section="work-items-progress">
                                <i class="fas fa-print me-1"></i>{{ __('general.print') }}
                            </button>
                        </div>
                    </div>

                    <div class="card mb-4 bg-light">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">{{ __('general.search') }}</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" id="workItemsSearch" class="form-control form-control-sm"
                                            placeholder="{{ __('general.search_by_name_unit_category') }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">{{ __('general.subproject') }}</label>
                                    <select id="workItemsSubprojectFilter" class="form-select form-select-sm">
                                        <option value="">{{ __('general.all_subprojects') }}</option>
                                        @if(isset($subprojectsWithItems))
                                            @foreach($subprojectsWithItems as $subproject)
                                                <option value="{{ $subproject->name }}">{{ $subproject->name }}</option>
                                            @endforeach
                                        @else
                                            @foreach($project->subprojects as $subproject)
                                                <option value="{{ $subproject->name }}">{{ $subproject->name }}</option>
                                            @endforeach
                                            @if($project->items->whereNull('subproject_name')->isNotEmpty())
                                                <option value="{{ __('general.without_subproject') }}">{{ __('general.without_subproject') }}</option>
                                            @endif
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">{{ __('general.category') }}</label>
                                    <select id="workItemsCategoryFilter" class="form-select form-select-sm">
                                        <option value="">{{ __('general.all_categories') }}</option>
                                        @php
                                            $categories = $project->items->map(function($item) {
                                                return $item->workItem && $item->workItem->category 
                                                    ? $item->workItem->category->name 
                                                    : null;
                                            })->filter()->unique()->sort();
                                        @endphp
                                        @foreach($categories as $categoryName)
                                            <option value="{{ $categoryName }}">{{ $categoryName }}</option>
                                        @endforeach
                                        <option value="uncategorized">{{ __('general.uncategorized') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted">{{ __('general.status') }}</label>
                                    <select id="workItemsStatusFilter" class="form-select form-select-sm">
                                        <option value="">{{ __('general.all') }}</option>
                                        <option value="completed">{{ __('general.completed') }}</option>
                                        <option value="in_progress">{{ __('general.in_progress') }}</option>
                                        <option value="pending">{{ __('general.pending') }}</option>
                                        <option value="delayed">{{ __('general.delayed') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="showPlannedProgress" checked>
                                        <label class="form-check-label" for="showPlannedProgress">
                                            <i class="fas fa-chart-line me-1"></i>{{ __('general.show_planned_progress') }}
                                        </label>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small mb-1"><i class="fas fa-tags me-1"></i>{{ __('general.filter_by_status') }}</label>
                                        <select id="itemStatusFilter" class="form-select form-select-sm">
                                            <option value="">{{ __('general.all_statuses') }}</option>
                                            @foreach($itemStatuses ?? [] as $status)
                                                <option value="{{ $status->id }}">{{ $status->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="button" id="clearWorkItemsFilters" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>{{ __('general.clear_filters') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion" id="accordionWorkItems">
                        <div class="accordion-item border-0">
                            <h2 class="accordion-header" id="headingWorkItems">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWorkItems" aria-expanded="false" aria-controls="collapseWorkItems">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chevron-down me-2"></i>{{ __('general.view_work_items') }}
                                    </h5>
                                </button>
                            </h2>
                            <div id="collapseWorkItems" class="accordion-collapse collapse" aria-labelledby="headingWorkItems" data-bs-parent="#accordionWorkItems">
                                <div class="accordion-body">
                                    <!-- Normal View -->
                                    <div class="row" id="workItemsContainer">
                        @foreach ($project->items as $item)
                        @php
                        $completionPercentage =
                        $item->total_quantity > 0
                        ? ($item->completed_quantity / $item->total_quantity) * 100
                        : 0;
                        
                        $today = \Carbon\Carbon::today();
                        $endDate = $item->end_date ? \Carbon\Carbon::parse($item->end_date) : null;
                        $startDate = $item->start_date ? \Carbon\Carbon::parse($item->start_date) : null;
                        
                        $itemStatus = 'pending';
                        if ($completionPercentage >= 100) {
                            $itemStatus = 'completed';
                        } elseif ($startDate && $today->lt($startDate)) {
                            $itemStatus = 'pending';
                        } elseif ($endDate && $today->gt($endDate) && $completionPercentage < 100) {
                            $itemStatus = 'delayed';
                        } elseif ($completionPercentage > 0 && $completionPercentage < 100) {
                            $itemStatus = 'in_progress';
                        }
                        
                        $plannedProgressUntilToday = 0;
                        if ($startDate && $endDate) {
                            $totalDays = $startDate->diffInDays($endDate);
                            if ($totalDays > 0) {
                                if ($today->greaterThanOrEqualTo($endDate)) {
                                    $plannedProgressUntilToday = 100;
                                } elseif ($today->greaterThanOrEqualTo($startDate)) {
                                    $daysPassed = $startDate->diffInDays($today);
                                    $plannedProgressUntilToday = min(100, round(($daysPassed / $totalDays) * 100, 2));
                                }
                            }
                        }
                        
                        $progressDifference = $completionPercentage - $plannedProgressUntilToday;
                        @endphp
                        <div class="col-md-6 mb-4 work-item-card" 
                             data-item-name="{{ strtolower($item->workItem->name ?? '') }}"
                             data-subproject="{{ $item->subproject_name ?? __('general.without_subproject') }}"
                             data-category="{{ $item->workItem && $item->workItem->category ? strtolower($item->workItem->category->name) : 'uncategorized' }}"
                             data-status="{{ $itemStatus }}"
                             data-item-id="{{ $item->id }}"
                             data-item-status-id="{{ $item->item_status_id ?? '' }}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <div class="fw-bold text-dark">{{ $item->workItem->name }}</div>
                                    @if($item->workItem->category ?? null)
                                    <small class="text-muted d-block"><i class="fas fa-folder me-1"></i>{{ $item->workItem->category->name }}</small>
                                    @endif
                                    @if($item->subproject_name ?? null)
                                    <small class="text-muted d-block"><i class="fas fa-sitemap me-1"></i>{{ $item->subproject_name }}</small>
                                    @else
                                    <small class="text-muted d-block"><i class="fas fa-sitemap me-1"></i>{{ __('general.without_subproject') }}</small>
                                    @endif
                                    @if($item->workItem->unit ?? null)
                                    <small class="text-muted d-block"><i class="fas fa-ruler me-1"></i>{{ $item->workItem->unit }}</small>
                                    @endif
                                    @if($item->notes ?? null)
                                    <small class="text-muted d-block"><i class="fas fa-sticky-note me-1"></i>{{ Str::limit($item->notes, 30) }}</small>
                                    @endif
                                </div>
                                <br>
                                <div class="text-end">
                                    <span class="fw-bold text-primary d-block">{{ number_format($completionPercentage, 1) }}%</span>
                                    <span class="badge bg-{{ $itemStatus === 'completed' ? 'success' : ($itemStatus === 'in_progress' ? 'primary' : ($itemStatus === 'delayed' ? 'danger' : 'warning')) }} badge-sm">
                                        {{ __('general.' . $itemStatus) }}
                                    </span>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-1"><i class="fas fa-tags me-1"></i>{{ __('general.item_status') }}</label>
                                <select class="form-select form-select-sm item-status-select" 
                                        data-item-id="{{ $item->id }}"
                                        data-project-id="{{ $project->id }}">
                                    <option value="">{{ __('general.no_status') }}</option>
                                    @foreach($itemStatuses ?? [] as $status)
                                        <option value="{{ $status->id }}" 
                                                {{ ($item->item_status_id == $status->id) ? 'selected' : '' }}
                                                data-color="{{ $status->color }}"
                                                data-icon="{{ $status->icon }}">
                                            {{ $status->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="item-status-loading d-none mt-1">
                                    <small class="text-muted"><i class="fas fa-spinner fa-spin me-1"></i>{{ __('general.saving') }}...</small>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">
                                        <i class="fas fa-check-circle me-1 text-success"></i>{{ __('general.actual_progress') }}
                                    </small>
                                    <small class="fw-bold text-primary">{{ number_format($completionPercentage, 1) }}%</small>
                                </div>
                                <div class="progress" style="height: 10px; border-radius: 8px;">
                                    <div class="progress-bar @if ($completionPercentage >= 80) bg-success @else bg-primary @endif"
                                        role="progressbar" style="width: {{ min($completionPercentage, 100) }}%">
                                    </div>
                                </div>
                            </div>
                            <div class="planned-progress-section">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-check me-1 text-secondary"></i>{{ __('general.planned_progress') }}
                                    </small>
                                    <small class="fw-bold text-secondary">{{ number_format($plannedProgressUntilToday, 1) }}%</small>
                                </div>
                                <div class="progress" style="height: 10px; border-radius: 8px;">
                                    <div class="progress-bar" 
                                         role="progressbar" 
                                         style="width: {{ min($plannedProgressUntilToday, 100) }}%; background-color: #64748b;">
                                    </div>
                                </div>
                                @if($progressDifference != 0)
                                <div class="mt-1 text-center">
                                    <small class="badge 
                                        @if($progressDifference > 0) bg-success
                                        @else bg-danger
                                        @endif badge-sm">
                                        @if($progressDifference > 0)
                                            <i class="fas fa-arrow-up me-1"></i>{{ __('general.ahead_by') }} {{ number_format(abs($progressDifference), 1) }}%
                                        @else
                                            <i class="fas fa-arrow-down me-1"></i>{{ __('general.behind_by') }} {{ number_format(abs($progressDifference), 1) }}%
                                        @endif
                                    </small>
                                </div>
                                @else
                                <div class="mt-1 text-center">
                                    <small class="badge bg-secondary badge-sm">
                                        <i class="fas fa-equals me-1"></i>{{ __('general.on_track') }}
                                    </small>
                                </div>
                                @endif
                            </div>
                            <div class="d-flex justify-content-between mt-2 text-muted small">
                                <span>{{ number_format($item->completed_quantity) }}
                                    {{ $item->workItem->unit }}</span>
                                <span>{{ number_format($item->remaining_quantity) }}
                                    {{ $item->workItem->unit }}</span>
                                <span>{{ number_format($item->total_quantity) }}
                                    {{ $item->workItem->unit }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>

                                    <!-- Grouped View -->
                                    <div class="row" id="workItemsGroupedContainer" style="display: none;">
                                        <!-- Will be populated by JavaScript -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5 dashboard-component" data-component="recent-activity">
            <div class="col-12">
                <div class="stat-card">
                    <div class="d-flex justify-content-end no-print mb-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary section-print-btn" data-section="recent-activity">
                            <i class="fas fa-print me-1"></i>{{ __('general.print') }}
                        </button>
                    </div>
                    <div class="accordion" id="accordionRecentActivity">
                        <div class="accordion-item border-0">
                            <h2 class="accordion-header" id="headingRecentActivity">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRecentActivity" aria-expanded="false" aria-controls="collapseRecentActivity">
                                    <h4 class="gradient-text fw-bold mb-0">
                                        <i class="fas fa-history me-2"></i>{{ __('general.daily_progress_record') }}
                                    </h4>
                                </button>
                            </h2>
                            <div id="collapseRecentActivity" class="accordion-collapse collapse" aria-labelledby="headingRecentActivity" data-bs-parent="#accordionRecentActivity">
                                <div class="accordion-body">
                                    <!-- Filters -->
                                    <div class="mb-4 p-3 bg-light rounded">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label small mb-1">
                                                    <i class="fas fa-search me-1"></i>{{ __('general.search') }}
                                                </label>
                                                <input type="text" id="dailyProgressSearch" class="form-control form-control-sm" placeholder="{{ __('general.search_by_employee_or_item') }}...">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small mb-1">
                                                    <i class="fas fa-calendar-alt me-1"></i>{{ __('general.from_date') }}
                                                </label>
                                                <input type="date" id="dailyProgressFromDate" class="form-control form-control-sm">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small mb-1">
                                                    <i class="fas fa-calendar-alt me-1"></i>{{ __('general.to_date') }}
                                                </label>
                                                <input type="date" id="dailyProgressToDate" class="form-control form-control-sm">
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <button type="button" id="clearDailyProgressFilters" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-times me-1"></i>{{ __('general.clear_filters') }}
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="timeline" id="dailyProgressTimeline">
                        @foreach ($recentProgress as $date => $progresses)
                        <div class="timeline-item" data-date="{{ $date }}" data-progresses="{{ json_encode($progresses->map(function($p) { return ['employee_name' => $p->employee->name ?? '', 'item_name' => $p->projectItem->workItem->name ?? '', 'quantity' => $p->quantity, 'unit' => $p->projectItem->workItem->unit ?? '']; })->toArray()) }}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span
                                    class="fw-bold text-dark">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</span>
                                <span class="badge bg-light text-dark">
                                    {{ $progresses->sum('quantity') }} {{ __('general.units') }}
                                </span>
                            </div>
                            @foreach ($progresses as $progress)
                            <div class="progress-item d-flex align-items-center mb-2 p-2 bg-light rounded" 
                                 data-employee="{{ strtolower($progress->employee->name ?? '') }}" 
                                 data-item="{{ strtolower($progress->projectItem->workItem->name ?? '') }}">
                                <div class="flex-shrink-0">
                                    @if($progress->employee ?? null)
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($progress->employee->name) }}&background=213c7a&color=fff"
                                        alt="{{ $progress->employee->name }}" class="employee-avatar">
                                    @else
                                    <img src="https://ui-avatars.com/api/?name=Unknown&background=6c757d&color=fff"
                                        alt="Unknown" class="employee-avatar">
                                    @endif
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="fw-medium">{{ $progress->employee->name ?? 'موظف غير معروف' }}</div>
                                    <div class="fw-semibold text-dark">{{ $progress->projectItem->workItem->name ?? '-' }}</div>
                                    @if($progress->projectItem->workItem->category ?? null)
                                    <small class="text-muted d-block"><i class="fas fa-folder me-1"></i>{{ $progress->projectItem->workItem->category->name }}</small>
                                    @endif
                                    @if($progress->projectItem->notes ?? null)
                                    <small class="text-muted d-block"><i class="fas fa-sticky-note me-1"></i>{{ Str::limit($progress->projectItem->notes, 25) }}</small>
                                    @endif
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-primary">{{ $progress->quantity }}</div>
                                    <small
                                        class="text-muted">{{ $progress->projectItem->workItem->unit ?? '' }}</small>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5 dashboard-component" data-component="timeline">
            <div class="col-12">
                <div class="stat-card">
                    <div class="d-flex justify-content-end no-print mb-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary section-print-btn" data-section="timeline">
                            <i class="fas fa-print me-1"></i>{{ __('general.print') }}
                        </button>
                    </div>
                    <div class="accordion" id="accordionTimeline">
                        <div class="accordion-item border-0">
                            <h2 class="accordion-header" id="headingTimeline">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTimeline" aria-expanded="false" aria-controls="collapseTimeline">
                                    <h4 class="gradient-text fw-bold mb-0">
                                        <i class="fas fa-project-diagram me-2"></i>{{ __('general.project_timeline') }}
                                    </h4>
                                </button>
                            </h2>
                            <div id="collapseTimeline" class="accordion-collapse collapse" aria-labelledby="headingTimeline" data-bs-parent="#accordionTimeline">
                                <div class="accordion-body">
                                    <div class="timeline">
                        @if($project->start_date)
                        <div class="timeline-item">
                            <div class="fw-bold text-primary">{{ __('general.start_date') }}</div>
                            <div class="text-muted">
                                {{ \Carbon\Carbon::parse($project->start_date)->format('d/m/Y') }}
                            </div>
                            <small class="text-success">{{ __('general.project_kickoff') }}</small>
                        </div>
                        @endif

                        <div class="timeline-item">
                            <div class="fw-bold text-primary">{{ __('general.current_date') }}</div>
                            <div class="text-muted">{{ now()->format('d/m/Y') }}</div>
                            <small class="text-info">{{ __('general.progress') }}:
                                {{ number_format($overallProgress, 1) }}%</small>
                        </div>

                        @if ($project->end_date)
                        <div class="timeline-item">
                            <div class="fw-bold text-primary">{{ __('general.end_date') }}</div>
                            <div class="text-muted">
                                {{ \Carbon\Carbon::parse($project->end_date)->format('d/m/Y') }}
                            </div>
                            <small class="text-warning">{{ __('general.expected_completion') }}</small>
                        </div>
                        @endif
                    </div>

                    <div class="mt-4 p-3 bg-light rounded">
                        <h6 class="fw-bold mb-3">{{ __('general.project_information') }}</h6>
                        <div class="row">
                            <div class="col-6 mb-2">
                                <small class="text-muted">{{ __('general.client') }}:</small>
                                <div class="fw-medium">{{ $project->client->name ?? __('general.not_specified') }}</div>
                            </div>
                            <div class="col-6 mb-2">
                                <small class="text-muted">{{ __('general.working_zone') }}:</small>
                                <div class="fw-medium">{{ $project->working_zone }}</div>
                            </div>
                            <div class="col-6 mb-2">
                                <small class="text-muted">{{ __('general.status') }}:</small>
                                <div class="fw-medium text-capitalize">{{ $project->status }}</div>
                            </div>
                            <div class="col-6 mb-2">
                                <small class="text-muted">{{ __('general.project_duration') }}:</small>
                                <div class="fw-medium">{{ $daysPassed + $daysRemaining }}
                                    {{ __('general.days') }}
                                </div>
                            </div>
                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5 dashboard-component" data-component="client-info">
            <div class="col-12">
                <div class="client-info-card">
                    <div class="d-flex justify-content-end no-print mb-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary section-print-btn" data-section="client-info">
                            <i class="fas fa-print me-1"></i>{{ __('general.print') }}
                        </button>
                    </div>
                    <div class="accordion" id="accordionClientInfo">
                        <div class="accordion-item border-0">
                            <h2 class="accordion-header" id="headingClientInfo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseClientInfo" aria-expanded="false" aria-controls="collapseClientInfo">
                                    <h4 class="gradient-text fw-bold mb-0">
                                        <i class="fas fa-building me-2"></i>{{ __('general.client_information') }}
                                    </h4>
                                </button>
                            </h2>
                            <div id="collapseClientInfo" class="accordion-collapse collapse" aria-labelledby="headingClientInfo" data-bs-parent="#accordionClientInfo">
                                <div class="accordion-body">
                                    @if($project->client)
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <div class="client-avatar mx-auto mb-2">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($project->client->name) }}&background=213c7a&color=fff&size=80"
                                    alt="{{ $project->client->name }}" class="rounded-circle" width="80"
                                    height="80">
                            </div>
                            <h5 class="fw-bold">{{ $project->client->name }}</h5>
                        </div>
                        <div class="col-md-8">
                            <div class="client-details">
                                <div class="mb-2">
                                    <small class="text-muted">{{ __('general.contact_person') }}:</small>
                                    <div class="fw-medium">
                                        {{ $project->client->contact_person ?? __('general.not_specified') }}
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">{{ __('general.email') }}:</small>
                                    <div class="fw-medium">
                                        {{ $project->client->email ?? __('general.not_specified') }}
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">{{ __('general.phone') }}:</small>
                                    <div class="fw-medium">
                                        {{ $project->client->phone ?? __('general.not_specified') }}
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">{{ __('general.address') }}:</small>
                                    <div class="fw-medium">
                                        {{ $project->client->address ?? __('general.not_specified') }}
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">{{ __('general.projects_count') }}:</small>
                                    <div class="fw-medium">
                                        {{ $project->client->projects_count ?? $project->client->projects()->count() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="fas fa-building fa-2x text-muted mb-2"></i>
                        <p class="text-muted">{{ __('general.no_client_assigned') }}</p>
                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5 dashboard-component" data-component="employees-info">
            <div class="col-12">
                <div class="employees-info-card">
                    <div class="d-flex justify-content-end no-print mb-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary section-print-btn" data-section="employees-info">
                            <i class="fas fa-print me-1"></i>{{ __('general.print') }}
                        </button>
                    </div>
                    <div class="accordion" id="accordionTeamMembers">
                        <div class="accordion-item border-0">
                            <h2 class="accordion-header" id="headingTeamMembers">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTeamMembers" aria-expanded="false" aria-controls="collapseTeamMembers">
                                    <h4 class="gradient-text fw-bold mb-0">
                                        <i class="fas fa-users me-2"></i>{{ __('general.team_members') }}
                                    </h4>
                                </button>
                            </h2>
                            <div id="collapseTeamMembers" class="accordion-collapse collapse" aria-labelledby="headingTeamMembers" data-bs-parent="#accordionTeamMembers">
                                <div class="accordion-body">
                                    <div class="employee-performance-list">
                        @forelse($project->employees as $employee)
                        @php
                        $employeeProgress = (float) ($employee->project_progress_sum ?? 0);

                        $performancePercentage =
                        $employeeProgress > 0 ? min(100, ($employeeProgress / 1000) * 100) : 0;
                        @endphp
                        <div class="employee-performance">
                            <div class="flex-shrink-0 me-3">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($employee->name) }}&background=213c7a&color=fff"
                                    alt="{{ $employee->name }}" class="employee-avatar">
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">{{ $employee->name }}</span>
                                    <span class="badge bg-primary">{{ $employeeProgress }}
                                        {{ __('general.units') }}</span>
                                </div>
                                <div class="performance-bar">
                                    <div class="performance-fill bg-success"
                                        style="width: {{ $performancePercentage }}%"></div>
                                </div>
                                <small
                                    class="text-muted">{{ $employee->position ?? __('general.employee') }}</small>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-3">
                            <i class="fas fa-users fa-2x text-muted mb-2"></i>
                            <p class="text-muted">{{ __('general.no_employees_assigned') }}</p>
                        </div>
                        @endforelse
                    </div>

                                    <div class="mt-3 pt-3 border-top">
                                        @can('employees-list')
                                        <a href="{{ route('employees.index') }}" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-external-link-alt me-1"></i>{{ __('general.manage_employees') }}
                                        </a>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
</div>

<div class="modal fade" id="componentsModal" tabindex="-1" aria-labelledby="componentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content shadow border-0" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header bg-primary text-white px-4 py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="kon-cust-header-icon">
                        <i class="fas fa-sliders-h fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="componentsModalLabel">
                            {{ __('general.customize_view') }}
                        </h5>
                        <small style="color: rgba(255,255,255,0.82) !important;">{{ __('general.customize_view_desc') }}</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <span class="badge bg-light text-dark border px-3 py-2 fw-semibold">
                        <i class="fas fa-grip-vertical me-1 text-primary"></i> {{ __('general.drag_to_reorder') }}
                    </span>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllComponents">
                            <i class="fas fa-check-double me-1"></i>{{ __('general.select_all') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllComponents">
                            <i class="fas fa-times me-1"></i>{{ __('general.deselect_all') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="resetComponentsOrder">
                            <i class="fas fa-undo me-1 text-primary"></i>{{ __('general.reset_order') }}
                        </button>
                    </div>
                </div>

                <div class="customize-sections-list customize-sections-grid" id="customizeSectionsList">
                    <div class="list-group-item d-flex align-items-center justify-content-between customize-item" data-id="statistics-cards">
                        <div class="d-flex align-items-center gap-3">
                            <div class="drag-handle" title="{{ __('general.drag_to_reorder') }}">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <div class="section-icon-badge">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <div>
                                <span class="section-title">{{ __('general.statistics_cards') }}</span>
                            </div>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input component-checkbox" type="checkbox" role="switch" value="statistics-cards" id="check-statistics" checked style="width: 2.35em; height: 1.25em; cursor: pointer;">
                        </div>
                    </div>

                    <div class="list-group-item d-flex align-items-center justify-content-between customize-item" data-id="total-weighted-progress">
                        <div class="d-flex align-items-center gap-3">
                            <div class="drag-handle" title="{{ __('general.drag_to_reorder') }}">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <div class="section-icon-badge badge-emerald">
                                <i class="fas fa-gauge-high"></i>
                            </div>
                            <div>
                                <span class="section-title">{{ __('general.total_weighted_progress') }}</span>
                            </div>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input component-checkbox" type="checkbox" role="switch" value="total-weighted-progress" id="check-total-weighted-progress" checked style="width: 2.35em; height: 1.25em; cursor: pointer;">
                        </div>
                    </div>

                    <div class="list-group-item d-flex align-items-center justify-content-between customize-item" data-id="bar-chart">
                        <div class="d-flex align-items-center gap-3">
                            <div class="drag-handle" title="{{ __('general.drag_to_reorder') }}">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <div class="section-icon-badge">
                                <i class="fas fa-chart-column"></i>
                            </div>
                            <div>
                                <span class="section-title">{{ __('general.progress_chart') }}</span>
                            </div>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input component-checkbox" type="checkbox" role="switch" value="bar-chart" id="check-bar-chart" checked style="width: 2.35em; height: 1.25em; cursor: pointer;">
                        </div>
                    </div>

                    <div class="list-group-item d-flex align-items-center justify-content-between customize-item" data-id="subprojects-chart">
                        <div class="d-flex align-items-center gap-3">
                            <div class="drag-handle" title="{{ __('general.drag_to_reorder') }}">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <div class="section-icon-badge">
                                <i class="fas fa-diagram-project"></i>
                            </div>
                            <div>
                                <span class="section-title">{{ __('general.subprojects_progress') }}</span>
                            </div>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input component-checkbox" type="checkbox" role="switch" value="subprojects-chart" id="check-subprojects-chart" checked style="width: 2.35em; height: 1.25em; cursor: pointer;">
                        </div>
                    </div>

                    <div class="list-group-item d-flex align-items-center justify-content-between customize-item" data-id="categories-chart">
                        <div class="d-flex align-items-center gap-3">
                            <div class="drag-handle" title="{{ __('general.drag_to_reorder') }}">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <div class="section-icon-badge">
                                <i class="fas fa-tags"></i>
                            </div>
                            <div>
                                <span class="section-title">{{ __('general.categories_progress') }}</span>
                            </div>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input component-checkbox" type="checkbox" role="switch" value="categories-chart" id="check-categories-chart" checked style="width: 2.35em; height: 1.25em; cursor: pointer;">
                        </div>
                    </div>

                    <div class="list-group-item d-flex align-items-center justify-content-between customize-item" data-id="subproject-items-chart">
                        <div class="d-flex align-items-center gap-3">
                            <div class="drag-handle" title="{{ __('general.drag_to_reorder') }}">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <div class="section-icon-badge">
                                <i class="fas fa-layer-group"></i>
                            </div>
                            <div>
                                <span class="section-title">{{ __('general.items_by_subproject') }}</span>
                            </div>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input component-checkbox" type="checkbox" role="switch" value="subproject-items-chart" id="check-subproject-items-chart" checked style="width: 2.35em; height: 1.25em; cursor: pointer;">
                        </div>
                    </div>

                    <div class="list-group-item d-flex align-items-center justify-content-between customize-item" data-id="category-items-chart">
                        <div class="d-flex align-items-center gap-3">
                            <div class="drag-handle" title="{{ __('general.drag_to_reorder') }}">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <div class="section-icon-badge badge-slate">
                                <i class="fas fa-folder-tree"></i>
                            </div>
                            <div>
                                <span class="section-title">{{ __('general.items_by_category') }}</span>
                            </div>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input component-checkbox" type="checkbox" role="switch" value="category-items-chart" id="check-category-items-chart" checked style="width: 2.35em; height: 1.25em; cursor: pointer;">
                        </div>
                    </div>

                    <div class="list-group-item d-flex align-items-center justify-content-between customize-item" data-id="hierarchical-view">
                        <div class="d-flex align-items-center gap-3">
                            <div class="drag-handle" title="{{ __('general.drag_to_reorder') }}">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <div class="section-icon-badge">
                                <i class="fas fa-sitemap"></i>
                            </div>
                            <div>
                                <span class="section-title">{{ __('general.hierarchical_view') }}</span>
                            </div>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input component-checkbox" type="checkbox" role="switch" value="hierarchical-view" id="check-hierarchical" checked style="width: 2.35em; height: 1.25em; cursor: pointer;">
                        </div>
                    </div>

                    <div class="list-group-item d-flex align-items-center justify-content-between customize-item" data-id="work-items-progress">
                        <div class="d-flex align-items-center gap-3">
                            <div class="drag-handle" title="{{ __('general.drag_to_reorder') }}">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <div class="section-icon-badge badge-emerald">
                                <i class="fas fa-list-check"></i>
                            </div>
                            <div>
                                <span class="section-title">{{ __('general.work_items_progress') }}</span>
                            </div>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input component-checkbox" type="checkbox" role="switch" value="work-items-progress" id="check-work-items" checked style="width: 2.35em; height: 1.25em; cursor: pointer;">
                        </div>
                    </div>

                    <div class="list-group-item d-flex align-items-center justify-content-between customize-item" data-id="recent-activity">
                        <div class="d-flex align-items-center gap-3">
                            <div class="drag-handle" title="{{ __('general.drag_to_reorder') }}">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <div class="section-icon-badge badge-emerald">
                                <i class="fas fa-clock-rotate-left"></i>
                            </div>
                            <div>
                                <span class="section-title">{{ __('general.daily_progress_record') }}</span>
                            </div>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input component-checkbox" type="checkbox" role="switch" value="recent-activity" id="check-activity" checked style="width: 2.35em; height: 1.25em; cursor: pointer;">
                        </div>
                    </div>

                    <div class="list-group-item d-flex align-items-center justify-content-between customize-item" data-id="timeline">
                        <div class="d-flex align-items-center gap-3">
                            <div class="drag-handle" title="{{ __('general.drag_to_reorder') }}">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <div class="section-icon-badge badge-slate">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div>
                                <span class="section-title">{{ __('general.project_timeline') }}</span>
                            </div>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input component-checkbox" type="checkbox" role="switch" value="timeline" id="check-timeline" checked style="width: 2.35em; height: 1.25em; cursor: pointer;">
                        </div>
                    </div>

                    <div class="list-group-item d-flex align-items-center justify-content-between customize-item" data-id="client-info">
                        <div class="d-flex align-items-center gap-3">
                            <div class="drag-handle" title="{{ __('general.drag_to_reorder') }}">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <div class="section-icon-badge badge-slate">
                                <i class="fas fa-building"></i>
                            </div>
                            <div>
                                <span class="section-title">{{ __('general.client_information') }}</span>
                            </div>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input component-checkbox" type="checkbox" role="switch" value="client-info" id="check-client" checked style="width: 2.35em; height: 1.25em; cursor: pointer;">
                        </div>
                    </div>

                    <div class="list-group-item d-flex align-items-center justify-content-between customize-item" data-id="employees-info">
                        <div class="d-flex align-items-center gap-3">
                            <div class="drag-handle" title="{{ __('general.drag_to_reorder') }}">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <div class="section-icon-badge badge-slate">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <span class="section-title">{{ __('general.team_members') }}</span>
                            </div>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input component-checkbox" type="checkbox" role="switch" value="employees-info" id="check-employees" checked style="width: 2.35em; height: 1.25em; cursor: pointer;">
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer justify-content-between px-4 py-3">
                <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">{{ __('general.close') }}</button>
                <button type="button" class="btn btn-primary px-4 fw-semibold" id="saveComponentsSettings">
                    <i class="fas fa-check me-1"></i>{{ __('general.save') }}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Calculation Formulas -->
<div class="modal fade" id="calculationFormulasModal" tabindex="-1" aria-labelledby="calculationFormulasModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="calculationFormulasModalLabel">
                    <i class="fas fa-calculator me-2"></i>{{ __('general.calculation_formulas') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="p-4 bg-light rounded d-none">
                    <h6 class="fw-bold mb-3">{{ __('general.weighted_ratio_calculation') }}</h6>
                    <p class="mb-2"><strong>{{ __('general.formula') }}:</strong></p>
                    <p class="mb-3 text-muted">
                        <code>Weighted Ratio = Progress Percentage × (Subproject Weight / 100)</code>
                    </p>
                    <p class="mb-2"><strong>{{ __('general.where') }}:</strong></p>
                    <ul class="mb-4">
                        <li><code>Progress Percentage = (Completed Quantity / Total Quantity) × 100</code></li>
                        <li><code>Subproject Weight</code> = الوزن المخصص للمشروع الفرعي (يجب أن يكون مجموع الأوزان = 100%)</li>
                    </ul>

                    <h6 class="fw-bold mb-3 mt-4">{{ __('general.planned_weighted_ratio_calculation') }}</h6>
                    <p class="mb-2"><strong>{{ __('general.formula') }}:</strong></p>
                    <p class="mb-3 text-muted">
                        <code>Planned Weighted Ratio = Planned Progress Percentage × (Subproject Weight / 100)</code>
                    </p>
                    <p class="mb-2"><strong>{{ __('general.where') }}:</strong></p>
                    <ul class="mb-4">
                        <li><code>Planned Progress Percentage = (Planned Total Quantity / Total Quantity) × 100</code></li>
                        <li><code>Planned Total Quantity = min(Working Days Until Today × Estimated Daily Quantity, Total Quantity)</code></li>
                        <li><code>Working Days Until Today</code> = عدد أيام العمل من تاريخ بداية البند حتى اليوم (باستثناء أيام الإجازة الأسبوعية)</li>
                        <li><code>Estimated Daily Quantity</code> = الكمية المتوقعة يومياً (estimated_daily_qty)</li>
                    </ul>

                    <h6 class="fw-bold mb-3 mt-4">{{ __('general.total_weighted_progress') }}</h6>
                    <p class="mb-2"><strong>{{ __('general.formula') }}:</strong></p>
                    <p class="mb-3 text-muted">
                        <code>Total Weighted Progress = Σ(Weighted Progress of all Subprojects)</code>
                    </p>
                    <p class="mb-2 text-muted">
                        <small>{{ __('general.note') }}: يتم جمع جميع النسب المرجحة للمشاريع الفرعية للحصول على التقدم الكلي المرجح للمشروع.</small>
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('general.close') }}</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const workItems = @json($chartData['work_items']);
        const completionPercentages = @json($chartData['completion_percentages']);
        const weightedRatios = @json($chartData['weighted_ratios'] ?? []);
        const plannedWeightedRatios = @json($chartData['planned_weighted_ratios'] ?? []);
        const weeklyProgress = @json($chartData['weekly_progress']);
        const workItemQuantities = @json($chartData['work_item_quantities'] ?? []);

        // ── Build grouped data by summing completed/total quantities per item name ──
        function buildGroupedData() {
            const agg = {};
            workItemQuantities.forEach(q => {
                const key = q.name.trim().toLowerCase();
                if (!agg[key]) {
                    agg[key] = { name: q.name, completed: 0, total: 0, planned: 0 };
                }
                agg[key].completed += q.completed_quantity;
                agg[key].total     += q.total_quantity;
                agg[key].planned   += q.planned_quantity;
            });
            const labels = [], actual = [], planned = [];
            Object.values(agg).forEach(g => {
                labels.push(g.name);
                actual.push( g.total > 0 ? Math.round((g.completed / g.total) * 10000) / 100 : 0);
                planned.push(g.total > 0 ? Math.round(Math.min(g.planned / g.total, 1) * 10000) / 100 : 0);
            });
            return { labels, actual, planned };
        }

        const grouped = buildGroupedData();

        let isGroupedView = true;
        let currentLabels                = grouped.labels.slice();
        let currentWeightedRatios        = grouped.actual.slice();
        let currentPlannedWeightedRatios = grouped.planned.slice();

        // Button starts in grouped state
        const toggleChartGroupedText = document.getElementById('toggleChartGroupedText');
        if (toggleChartGroupedText) toggleChartGroupedText.textContent = '{{ __('general.flat_view') }}';

        const itemsFilterContainer = document.getElementById('itemsFilterContainer');
        if (itemsFilterContainer && currentLabels.length > 0) {
            currentLabels.forEach((item, index) => {
                const checkboxDiv = document.createElement('div');
                checkboxDiv.className = 'form-check';
                checkboxDiv.innerHTML = `
                    <input class="form-check-input item-filter-checkbox" type="checkbox"
                           value="${index}" id="itemFilter${index}" checked>
                    <label class="form-check-label" for="itemFilter${index}">
                        ${item}
                    </label>
                `;
                itemsFilterContainer.appendChild(checkboxDiv);
            });
        } else if (itemsFilterContainer) {
            itemsFilterContainer.innerHTML = '<p class="text-muted mb-0">{{ __('general.no_items_found') }}</p>';
        }

        function drawDataLabels(chart) {
            const ctx = chart.ctx;
            ctx.save();
            ctx.font = 'bold 12px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            
            chart.data.datasets.forEach((dataset, datasetIndex) => {
                const meta = chart.getDatasetMeta(datasetIndex);
                meta.data.forEach((bar, index) => {
                    const data = dataset.data[index];
                    if (data !== null && data !== undefined && bar && bar.height > 0) {
                        const x = bar.x;
                        const y = bar.y - 15;
                        const text = data.toFixed(1) + '%';
                        const textMetrics = ctx.measureText(text);
                        const textWidth = textMetrics.width;
                        
                        ctx.fillStyle = 'rgba(0, 0, 0, 0.7)';
                        ctx.fillRect(x - textWidth/2 - 4, y - 9, textWidth + 8, 18);
                        
                        ctx.fillStyle = '#fff';
                        ctx.fillText(text, x, y);
                    }
                });
            });
            ctx.restore();
        }

        const progressCtx = document.getElementById('progressChart').getContext('2d');
        let progressChart = new Chart(progressCtx, {
            type: 'bar',
            data: {
                labels: currentLabels,
                datasets: [
                    {
                        label: '{{ __('general.progress') }}',
                        data: currentWeightedRatios,
                        backgroundColor: 'rgba(33, 60, 122, 0.88)',
                        borderColor: '#213c7a',
                        borderWidth: 1,
                        borderRadius: 6
                    },
                    {
                        label: '{{ __('general.planned_progress') }}',
                        data: currentPlannedWeightedRatios,
                        backgroundColor: 'rgba(148, 163, 184, 0.48)',
                        borderColor: '#64748b',
                        borderWidth: 1,
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        enabled: true
                    }
                },
                animation: {
                    onComplete: function() {
                        drawDataLabels(this);
                    }
                },
                scales: {
                    x: {
                        grouped: true
                    },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });


        function updateProgressChart() {
            if (!progressChart || currentLabels.length === 0) {
                return;
            }
            
            const checkedBoxes = document.querySelectorAll('.item-filter-checkbox:checked');
            const selectedIndices = Array.from(checkedBoxes).map(cb => parseInt(cb.value));
            
            if (selectedIndices.length === 0) {
                progressChart.data.labels = [];
                progressChart.data.datasets[0].data = [];
                progressChart.data.datasets[1].data = [];
                progressChart.update('none');
                setTimeout(() => {
                    drawDataLabels(progressChart);
                }, 50);
                return;
            }
            
            const filteredWorkItems = selectedIndices.map(idx => currentLabels[idx]).filter(item => item !== undefined);
            const filteredWeightedRatios = selectedIndices.map(idx => currentWeightedRatios[idx]).filter(ratio => ratio !== undefined);
            const filteredPlannedWeightedRatios = selectedIndices.map(idx => currentPlannedWeightedRatios[idx]).filter(ratio => ratio !== undefined);
            
            progressChart.data.labels = filteredWorkItems;
            progressChart.data.datasets[0].data = filteredWeightedRatios;
            progressChart.data.datasets[1].data = filteredPlannedWeightedRatios;
            
            progressChart.update('none'); // 'none' لتجنب animation
            setTimeout(() => {
                drawDataLabels(progressChart);
            }, 50);
        }
        
        function toggleGroupedView() {
            isGroupedView = !isGroupedView;
            const toggleText = document.getElementById('toggleChartGroupedText');
            const toggleBtn  = document.getElementById('toggleChartGroupedView');

            if (isGroupedView) {
                // Use proper quantity-based aggregation (same logic as Work Items section)
                const g = buildGroupedData();
                currentLabels                = g.labels;
                currentWeightedRatios        = g.actual;
                currentPlannedWeightedRatios = g.planned;

                if (toggleText) toggleText.textContent = '{{ __('general.flat_view') }}';
                if (toggleBtn)  { toggleBtn.classList.remove('btn-outline-primary'); toggleBtn.classList.add('btn-primary'); }
            } else {
                currentLabels                = [...workItems];
                currentWeightedRatios        = [...weightedRatios];
                currentPlannedWeightedRatios = [...plannedWeightedRatios];

                if (toggleText) toggleText.textContent = '{{ __('general.grouped_view') }}';
                if (toggleBtn)  { toggleBtn.classList.remove('btn-primary'); toggleBtn.classList.add('btn-outline-primary'); }
            }

            rebuildCheckboxes();
            updateProgressChart();
        }
        
        function rebuildCheckboxes() {
            const itemsFilterContainer = document.getElementById('itemsFilterContainer');
            if (!itemsFilterContainer) return;
            
            itemsFilterContainer.innerHTML = '';
            
            if (currentLabels && currentLabels.length > 0) {
                currentLabels.forEach((item, index) => {
                    const checkboxDiv = document.createElement('div');
                    checkboxDiv.className = 'form-check';
                    checkboxDiv.innerHTML = `
                        <input class="form-check-input item-filter-checkbox" type="checkbox" 
                               value="${index}" id="itemFilter${index}" checked>
                        <label class="form-check-label" for="itemFilter${index}">
                            ${item}
                        </label>
                    `;
                    itemsFilterContainer.appendChild(checkboxDiv);
                });
                
                document.querySelectorAll('.item-filter-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', updateProgressChart);
                });
            } else {
                itemsFilterContainer.innerHTML = '<p class="text-muted mb-0">{{ __('general.no_items_found') }}</p>';
            }
        }
        
        document.getElementById('toggleChartGroupedView')?.addEventListener('click', toggleGroupedView);

        document.querySelectorAll('.item-filter-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateProgressChart);
        });

        document.getElementById('selectAllItems')?.addEventListener('click', function() {
            document.querySelectorAll('.item-filter-checkbox').forEach(checkbox => {
                checkbox.checked = true;
            });
            updateProgressChart();
        });

        document.getElementById('deselectAllItems')?.addEventListener('click', function() {
            document.querySelectorAll('.item-filter-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            updateProgressChart();
        });

        const subprojectsData = @json($subprojectsChartData);
        const subprojectsCtx = document.getElementById('subprojectsChart').getContext('2d');
        const subprojectsChart = new Chart(subprojectsCtx, {
            type: 'bar',
            data: {
                labels: subprojectsData.labels || [],
                datasets: [
                    {
                        label: '{{ __('general.progress') }}',
                        data: subprojectsData.weighted_ratios || [],
                        backgroundColor: 'rgba(33, 60, 122, 0.88)',
                        borderColor: '#213c7a',
                        borderWidth: 1,
                        borderRadius: 6
                    },
                    {
                        label: '{{ __('general.planned_progress') }}',
                        data: subprojectsData.planned_weighted_ratios || [],
                        backgroundColor: 'rgba(148, 163, 184, 0.48)',
                        borderColor: '#64748b',
                        borderWidth: 1,
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        enabled: true
                    }
                },
                animation: {
                    onComplete: function() {
                        drawDataLabels(this);
                    }
                },
                scales: {
                    x: {
                        grouped: true
                    },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });

        setTimeout(() => {
            drawDataLabels(subprojectsChart);
        }, 100);

        const categoriesData = @json($categoriesChartData);
        const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
        const categoriesChart = new Chart(categoriesCtx, {
            type: 'bar',
            data: {
                labels: categoriesData.labels || [],
                datasets: [
                    {
                        label: '{{ __('general.progress') }}',
                        data: categoriesData.weighted_ratios || [],
                        backgroundColor: 'rgba(33, 60, 122, 0.88)',
                        borderColor: '#213c7a',
                        borderWidth: 1,
                        borderRadius: 6
                    },
                    {
                        label: '{{ __('general.planned_progress') }}',
                        data: categoriesData.planned_weighted_ratios || [],
                        backgroundColor: 'rgba(148, 163, 184, 0.48)',
                        borderColor: '#64748b',
                        borderWidth: 1,
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    x: {
                        grouped: true
                    },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });

        @if($project->subprojects->count() > 0)
        function loadTotalWeightedProgress() {
            const displayElement = document.getElementById('totalWeightedProgressDisplay');
            if (!displayElement) return;

            fetch(`/api/projects/{{ $project->id }}/subprojects`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                let subprojects = null;
                
                if (Array.isArray(data)) {
                    subprojects = data;
                } else if (data.success && Array.isArray(data.subprojects)) {
                    subprojects = data.subprojects;
                } else if (Array.isArray(data.subprojects)) {
                    subprojects = data.subprojects;
                }
                
                if (subprojects && subprojects.length > 0) {
                    
                    const actualProgressLabel = '{{ __('general.actual_progress') }}';
                    const plannedProgressLabel = '{{ __('general.planned_progress') }}';
                    const aheadByLabel = '{{ __('general.ahead_by') }}';
                    const behindByLabel = '{{ __('general.behind_by') }}';
                    const onTrackLabel = '{{ __('general.on_track') }}';
                    
                    // Use overallProgress from backend (same as /projects page) - ensures consistency
                    const totalProgressPercentage = {{ $overallProgress ?? 0 }};
                    
                    // Check if there are weighted subprojects (same logic as Project model)
                    const hasWeightedSubprojects = subprojects.some(sp => (sp.weight || 0) > 0);
                    
                    let totalPlannedProgressPercentage = 0;
                    let totalQuantity = 0;
                    let totalCompletedQuantity = 0;
                    let totalPlannedQuantity = 0;
                    
                    if (hasWeightedSubprojects) {
                        // Weighted calculation for planned progress (same as Project->getOverallProgressAttribute)
                        let overallPlannedProgress = 0;
                        
                        subprojects.forEach(subproject => {
                            const weight = subproject.weight || 0;
                            if (weight <= 0) return;
                            
                            const subTotalQty = subproject.total_quantity || 0;
                            const subPlannedQty = subproject.planned_total_quantity || 0;
                            
                            const subPlannedProgress = subTotalQty > 0 ? (subPlannedQty / subTotalQty) * 100 : 0;
                            
                            overallPlannedProgress += subPlannedProgress * (weight / 100);
                        });
                        
                        totalPlannedProgressPercentage = Math.round(overallPlannedProgress * 10) / 10;
                        
                        // For display purposes, calculate totals
                        totalQuantity = subprojects.reduce((sum, sp) => sum + (sp.total_quantity || 0), 0);
                        totalCompletedQuantity = subprojects.reduce((sum, sp) => sum + (sp.completed_quantity_under_100 || sp.completed_quantity || 0), 0);
                        totalPlannedQuantity = subprojects.reduce((sum, sp) => sum + (sp.planned_total_quantity || 0), 0);
                    } else {
                        // Simple calculation (Classic Mode - same as Project model fallback)
                        totalQuantity = subprojects.reduce((sum, sp) => sum + (sp.total_quantity || 0), 0);
                        totalCompletedQuantity = subprojects.reduce((sum, sp) => sum + (sp.completed_quantity_under_100 || sp.completed_quantity || 0), 0);
                        totalPlannedQuantity = subprojects.reduce((sum, sp) => sum + (sp.planned_total_quantity || 0), 0);
                        
                        totalPlannedProgressPercentage = totalQuantity > 0 ? Math.round((totalPlannedQuantity / totalQuantity) * 100 * 10) / 10 : 0;
                    }
                    
                    const progressDifference = totalCompletedQuantity - totalPlannedQuantity;
                    const differenceIcon = progressDifference > 0 ? 'fa-arrow-up' : progressDifference < 0 ? 'fa-arrow-down' : 'fa-equals';
                    const differenceColor = progressDifference > 0 ? 'text-success' : progressDifference < 0 ? 'text-danger' : 'text-secondary';
                    const actualClamped = Math.min(Math.max(totalProgressPercentage, 0), 100);
                    const plannedClamped = Math.min(Math.max(totalPlannedProgressPercentage, 0), 100);
                    const actualLabelClass = actualClamped >= 52 ? 'on-dark' : 'on-light';
                    const plannedLabelClass = plannedClamped >= 52 ? 'on-dark' : 'on-light';
                    
                    const formatNumber = (num) => {
                        return num.toLocaleString('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 0 });
                    };
                    
                    displayElement.innerHTML = `
                        <div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="p-3 rounded-3 h-100" style="background: #ffffff; border: 1px solid #e2e8f0; border-inline-start: 4px solid #16803c;">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-check-circle fa-lg text-success"></i>
                                                <span class="text-muted fw-semibold">${actualProgressLabel}</span>
                                            </div>
                                            <span class="fw-bold" style="font-size: 2rem; color: #0f172a; line-height: 1.1;">${actualClamped.toFixed(1)}%</span>
                                        </div>
                                        <div class="kon-progress-track" role="progressbar" aria-valuenow="${actualClamped}" aria-valuemin="0" aria-valuemax="100">
                                            <div class="kon-progress-fill fill-actual" style="width: ${actualClamped}%;"></div>
                                            <span class="kon-progress-label ${actualLabelClass}">${actualClamped.toFixed(1)}%</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="p-3 rounded-3 h-100" style="background: #ffffff; border: 1px solid #e2e8f0; border-inline-start: 4px solid #213c7a;">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-calendar-check fa-lg text-primary"></i>
                                                <span class="text-muted fw-semibold">${plannedProgressLabel}</span>
                                            </div>
                                            <span class="fw-bold" style="font-size: 2rem; color: #0f172a; line-height: 1.1;">${plannedClamped.toFixed(1)}%</span>
                                        </div>
                                        <div class="kon-progress-track" role="progressbar" aria-valuenow="${plannedClamped}" aria-valuemin="0" aria-valuemax="100">
                                            <div class="kon-progress-fill fill-planned" style="width: ${plannedClamped}%;"></div>
                                            <span class="kon-progress-label ${plannedLabelClass}">${plannedClamped.toFixed(1)}%</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="py-2 px-3 rounded-3 text-center" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                        <div class="d-flex align-items-center justify-content-center gap-2">
                                            <i class="fas ${differenceIcon} ${differenceColor}"></i>
                                            <span class="fw-semibold small" style="color: #334155;">
                                                ${formatNumber(Math.abs(progressDifference))} 
                                                ${progressDifference > 0 ? aheadByLabel : progressDifference < 0 ? behindByLabel : onTrackLabel}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    updateProjectProgressBar(totalProgressPercentage);
                } else {
                    displayElement.innerHTML = '<div class="text-muted">{{ __('general.no_subprojects_found') }}</div>';
                }
            })
            .catch(error => {
                console.error('Error loading total weighted progress:', error);
                displayElement.innerHTML = '<div class="text-danger">{{ __('general.error_loading_data') }}</div>';
            });
        }
        
        function updateProjectProgressBar(progressValue) {
            const workItemsCard = document.querySelector('.stat-card .fa-tasks');
            if (workItemsCard) {
                const statCard = workItemsCard.closest('.stat-card');
                const progressBar = statCard.querySelector('.progress-bar.bg-primary');
                if (progressBar) {
                    progressBar.style.width = Math.min(progressValue, 100) + '%';
                }
            }
        }
        
        loadTotalWeightedProgress();
        
        function updateHierarchicalViewProgress() {
            fetch(`/api/projects/{{ $project->id }}/subprojects`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                let subprojects = null;
                
                if (Array.isArray(data)) {
                    subprojects = data;
                } else if (data.success && Array.isArray(data.subprojects)) {
                    subprojects = data.subprojects;
                } else if (Array.isArray(data.subprojects)) {
                    subprojects = data.subprojects;
                }
                
                if (subprojects && subprojects.length > 0) {
                    subprojects.forEach(subproject => {
                        const subprojectName = subproject.name;
                        const accordionItems = document.querySelectorAll('#hierarchicalAccordion .accordion-item');
                        
                        accordionItems.forEach(item => {
                            const subprojectNameElement = item.querySelector('strong');
                            if (subprojectNameElement && subprojectNameElement.textContent.trim() === subprojectName) {
                                const progressText = item.querySelector('.small.text-muted.mb-1');
                                if (progressText) {
                                    progressText.textContent = subproject.progress_under_100.toFixed(1) + '% {{ __('general.completed') }}';
                                }
                                
                                const progressBar = item.querySelector('.progress-bar');
                                if (progressBar) {
                                    const progressValue = Math.min(subproject.progress_under_100, 100);
                                    progressBar.style.width = progressValue + '%';
                                    
                                    progressBar.className = 'progress-bar ' + 
                                        (progressValue >= 80 ? 'bg-success' : 'bg-primary');
                                }
                            }
                        });
                    });
                }
            })
            .catch(error => {
                console.error('Error loading hierarchical view progress:', error);
            });
        }
        
        updateHierarchicalViewProgress();
        @endif

        const subprojectItemsData = @json($subprojectItemsChartData);
        let subprojectItemsChart = null;
        const subprojectItemsCtx = document.getElementById('subprojectItemsChart').getContext('2d');
        
        function updateSubprojectItemsChart(subprojectName, showAll = true) {
            if (subprojectItemsChart) {
                subprojectItemsChart.destroy();
            }
            
            if (!subprojectName || !subprojectItemsData[subprojectName]) {
                subprojectItemsChart = new Chart(subprojectItemsCtx, {
                    type: 'bar',
                    data: {
                        labels: [],
                        datasets: []
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
                return;
            }
            
            const data = subprojectItemsData[subprojectName];
            
            let filteredLabels = [];
            let filteredWeightedRatios = [];
            let filteredPlannedWeightedRatios = [];
            
            if (showAll) {
                filteredLabels = data.labels || [];
                filteredWeightedRatios = data.weighted_ratios || [];
                filteredPlannedWeightedRatios = data.planned_weighted_ratios || [];
            } else {
                const isMeasurable = data.is_measurable || [];
                filteredLabels = (data.labels || []).filter((label, index) => isMeasurable[index]);
                filteredWeightedRatios = (data.weighted_ratios || []).filter((ratio, index) => isMeasurable[index]);
                filteredPlannedWeightedRatios = (data.planned_weighted_ratios || []).filter((ratio, index) => isMeasurable[index]);
            }
            
            subprojectItemsChart = new Chart(subprojectItemsCtx, {
                type: 'bar',
                data: {
                    labels: filteredLabels,
                    datasets: [
                        {
                            label: '{{ __('general.progress') }}',
                            data: filteredWeightedRatios,
                            backgroundColor: 'rgba(33, 60, 122, 0.88)',
                            borderColor: '#213c7a',
                            borderWidth: 1,
                            borderRadius: 6
                        },
                        {
                            label: '{{ __('general.planned_progress') }}',
                            data: filteredPlannedWeightedRatios,
                            backgroundColor: 'rgba(148, 163, 184, 0.48)',
                            borderColor: '#64748b',
                            borderWidth: 1,
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        x: {
                            grouped: true
                        },
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        }
        
        const subprojectSelect = document.getElementById('subprojectSelect');
        const showAllSubprojectItems = document.getElementById('showAllSubprojectItems');
        
        if (subprojectSelect) {
            subprojectSelect.addEventListener('change', function(e) {
                const showAll = showAllSubprojectItems ? showAllSubprojectItems.checked : true;
                updateSubprojectItemsChart(e.target.value, showAll);
            });
        }

        if (showAllSubprojectItems) {
            showAllSubprojectItems.addEventListener('change', function(e) {
                const subprojectName = subprojectSelect ? subprojectSelect.value : '';
                if (subprojectName) {
                    updateSubprojectItemsChart(subprojectName, e.target.checked);
                }
            });
        }

        const categoryItemsData = @json($categoryItemsChartData);
        let categoryItemsChart = null;
        const categoryItemsCtx = document.getElementById('categoryItemsChart').getContext('2d');
        
        function updateCategoryItemsChart(categoryName) {
            if (categoryItemsChart) {
                categoryItemsChart.destroy();
            }
            
            if (!categoryName || !categoryItemsData[categoryName]) {
                categoryItemsChart = new Chart(categoryItemsCtx, {
                    type: 'bar',
                    data: {
                        labels: [],
                        datasets: []
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
                return;
            }
            
            const data = categoryItemsData[categoryName];
            categoryItemsChart = new Chart(categoryItemsCtx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: '{{ __('general.progress') }}',
                            data: data.weighted_ratios || [],
                            backgroundColor: 'rgba(33, 60, 122, 0.88)',
                            borderColor: '#213c7a',
                            borderWidth: 1,
                            borderRadius: 6
                        },
                        {
                            label: '{{ __('general.planned_progress') }}',
                            data: data.planned_weighted_ratios || [],
                            backgroundColor: 'rgba(148, 163, 184, 0.48)',
                            borderColor: '#64748b',
                            borderWidth: 1,
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        x: {
                            grouped: true
                        },
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        }
        
        const categorySelect = document.getElementById('categorySelect');
        if (categorySelect) {
            categorySelect.addEventListener('change', function(e) {
                updateCategoryItemsChart(e.target.value);
            });
        }

        setInterval(() => {
            console.log('Auto-refresh dashboard data');
        }, 30000);
    });

    (function() {
        const DEFAULT_ORDER = [
            'statistics-cards',
            'total-weighted-progress',
            'bar-chart',
            'subprojects-chart',
            'categories-chart',
            'subproject-items-chart',
            'category-items-chart',
            'hierarchical-view',
            'work-items-progress',
            'recent-activity',
            'timeline',
            'client-info',
            'employees-info'
        ];

        const STORAGE_KEY_ORDER = 'dashboard_components_order';
        const STORAGE_KEY_VISIBILITY = 'dashboard_components_visibility';
        let modalSortable = null;

        function getSavedOrder() {
            try {
                const saved = JSON.parse(localStorage.getItem(STORAGE_KEY_ORDER) || '[]');
                if (Array.isArray(saved) && saved.length > 0) {
                    const savedSet = new Set(saved);
                    const fullOrder = [...saved];
                    DEFAULT_ORDER.forEach(id => {
                        if (!savedSet.has(id)) {
                            fullOrder.push(id);
                        }
                    });
                    return fullOrder;
                }
            } catch (e) {
                console.error('Error reading saved order:', e);
            }
            return [...DEFAULT_ORDER];
        }

        function getSavedVisibility() {
            try {
                const saved = JSON.parse(localStorage.getItem(STORAGE_KEY_VISIBILITY) || '{}');
                if (typeof saved === 'object' && saved !== null) {
                    return saved;
                }
            } catch (e) {
                console.error('Error reading saved visibility:', e);
            }
            return {};
        }

        function applyDashboardOrder(order) {
            const container = document.getElementById('dashboardSectionsContainer');
            if (!container || !Array.isArray(order)) return;
            order.forEach(key => {
                const el = container.querySelector(`:scope > [data-component="${key}"]`);
                if (el) {
                    container.appendChild(el);
                }
            });
        }

        function applyDashboardVisibility(visibility) {
            const container = document.getElementById('dashboardSectionsContainer');
            if (!container) return;
            const items = container.querySelectorAll(':scope > [data-component]');
            items.forEach(el => {
                const key = el.getAttribute('data-component');
                if (key && visibility[key] === false) {
                    el.classList.add('hidden');
                } else {
                    el.classList.remove('hidden');
                }
            });
        }

        function syncModalList(order, visibility) {
            const listContainer = document.getElementById('customizeSectionsList');
            if (!listContainer) return;

            // Reorder items in the modal list
            order.forEach(id => {
                const item = listContainer.querySelector(`.customize-item[data-id="${id}"]`);
                if (item) {
                    listContainer.appendChild(item);
                }
            });

            // Set checkbox checked state & visual dim state
            listContainer.querySelectorAll('.customize-item').forEach(item => {
                const id = item.getAttribute('data-id');
                const cb = item.querySelector('.component-checkbox');
                if (cb) {
                    cb.checked = (visibility[id] !== false);
                    item.classList.toggle('is-off', !cb.checked);
                    if (!cb.hasAttribute('data-vis-bound')) {
                        cb.setAttribute('data-vis-bound', '1');
                        cb.addEventListener('change', () => item.classList.toggle('is-off', !cb.checked));
                    }
                }
            });
        }

        function initModalSortable() {
            const listContainer = document.getElementById('customizeSectionsList');
            if (!listContainer || typeof Sortable === 'undefined') return;
            if (modalSortable) {
                modalSortable.destroy();
            }
            modalSortable = Sortable.create(listContainer, {
                animation: 200,
                handle: '.drag-handle, .customize-item',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag'
            });
        }

        function saveSettings() {
            const listContainer = document.getElementById('customizeSectionsList');
            if (!listContainer) return;

            const items = listContainer.querySelectorAll('.customize-item');
            const newOrder = [];
            const newVisibility = {};

            items.forEach(item => {
                const id = item.getAttribute('data-id');
                if (id) {
                    newOrder.push(id);
                    const cb = item.querySelector('.component-checkbox');
                    newVisibility[id] = cb ? cb.checked : true;
                }
            });

            localStorage.setItem(STORAGE_KEY_ORDER, JSON.stringify(newOrder));
            localStorage.setItem(STORAGE_KEY_VISIBILITY, JSON.stringify(newVisibility));

            applyDashboardOrder(newOrder);
            applyDashboardVisibility(newVisibility);

            // Resize charts to adjust smoothly
            setTimeout(() => {
                window.dispatchEvent(new Event('resize'));
            }, 100);
        }

        const searchInput = document.getElementById('searchItems');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase().trim();
                const accordionItems = document.querySelectorAll('#hierarchicalAccordion .hierarchical-subproject');

                accordionItems.forEach(subprojectEl => {
                    const subprojectName = subprojectEl.getAttribute('data-subproject-name') || '';
                    const categoryEls = subprojectEl.querySelectorAll('.hierarchical-category');
                    let subprojectHasMatch = !searchTerm || subprojectName.includes(searchTerm);

                    categoryEls.forEach(categoryEl => {
                        const categoryName = categoryEl.getAttribute('data-category-name') || '';
                        const itemCards = categoryEl.querySelectorAll('.item-card');
                        let categoryHasMatch = !searchTerm || categoryName.includes(searchTerm);

                        itemCards.forEach(card => {
                            const itemName = card.getAttribute('data-item-name') || '';
                            const itemNotes = card.getAttribute('data-item-notes') || '';
                            const itemUnit = card.getAttribute('data-item-unit') || '';
                            const matches = !searchTerm
                                || itemName.includes(searchTerm)
                                || itemNotes.includes(searchTerm)
                                || itemUnit.includes(searchTerm)
                                || categoryName.includes(searchTerm)
                                || subprojectName.includes(searchTerm);

                            card.style.display = matches ? '' : 'none';
                            if (matches && searchTerm) {
                                categoryHasMatch = true;
                            }
                        });

                        // If category name matched, show all its items
                        if (searchTerm && categoryName.includes(searchTerm)) {
                            itemCards.forEach(card => { card.style.display = ''; });
                            categoryHasMatch = true;
                        }

                        categoryEl.style.display = categoryHasMatch ? '' : 'none';
                        if (categoryHasMatch) {
                            subprojectHasMatch = true;
                        }
                    });

                    // If subproject name matched, show all categories/items under it
                    if (searchTerm && subprojectName.includes(searchTerm)) {
                        categoryEls.forEach(categoryEl => {
                            categoryEl.style.display = '';
                            categoryEl.querySelectorAll('.item-card').forEach(card => {
                                card.style.display = '';
                            });
                        });
                        subprojectHasMatch = true;
                    }

                    subprojectEl.style.display = subprojectHasMatch ? '' : 'none';

                    // Expand matching accordion sections while searching
                    const collapseEl = subprojectEl.querySelector('.accordion-collapse');
                    const buttonEl = subprojectEl.querySelector('.accordion-button');
                    if (collapseEl && buttonEl) {
                        if (searchTerm && subprojectHasMatch) {
                            collapseEl.classList.add('show');
                            buttonEl.classList.remove('collapsed');
                            buttonEl.setAttribute('aria-expanded', 'true');
                        } else if (!searchTerm) {
                            collapseEl.classList.remove('show');
                            buttonEl.classList.add('collapsed');
                            buttonEl.setAttribute('aria-expanded', 'false');
                        }
                    }
                });
            });
        }

        function initializeDashboard() {
            const currentOrder = getSavedOrder();
            const currentVisibility = getSavedVisibility();

            applyDashboardOrder(currentOrder);
            applyDashboardVisibility(currentVisibility);
            syncModalList(currentOrder, currentVisibility);
            initModalSortable();

            const modalEl = document.getElementById('componentsModal');
            if (modalEl) {
                modalEl.addEventListener('show.bs.modal', function() {
                    const order = getSavedOrder();
                    const visibility = getSavedVisibility();
                    syncModalList(order, visibility);
                    setTimeout(initModalSortable, 50);
                });
            }

            const saveButton = document.getElementById('saveComponentsSettings');
            if (saveButton && !saveButton.hasAttribute('data-listener-attached')) {
                saveButton.setAttribute('data-listener-attached', 'true');
                saveButton.addEventListener('click', function() {
                    saveSettings();
                    const modalElement = document.getElementById('componentsModal');
                    if (modalElement) {
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) {
                            modal.hide();
                        } else {
                            const bsModal = new bootstrap.Modal(modalElement);
                            bsModal.hide();
                        }
                    }
                });
            }

            const selectAllBtn = document.getElementById('selectAllComponents');
            if (selectAllBtn && !selectAllBtn.hasAttribute('data-listener-attached')) {
                selectAllBtn.setAttribute('data-listener-attached', 'true');
                selectAllBtn.addEventListener('click', function() {
                    document.querySelectorAll('#customizeSectionsList .component-checkbox').forEach(cb => {
                        cb.checked = true;
                        cb.closest('.customize-item')?.classList.remove('is-off');
                    });
                });
            }

            const deselectAllBtn = document.getElementById('deselectAllComponents');
            if (deselectAllBtn && !deselectAllBtn.hasAttribute('data-listener-attached')) {
                deselectAllBtn.setAttribute('data-listener-attached', 'true');
                deselectAllBtn.addEventListener('click', function() {
                    document.querySelectorAll('#customizeSectionsList .component-checkbox').forEach(cb => {
                        cb.checked = false;
                        cb.closest('.customize-item')?.classList.add('is-off');
                    });
                });
            }

            const resetOrderBtn = document.getElementById('resetComponentsOrder');
            if (resetOrderBtn && !resetOrderBtn.hasAttribute('data-listener-attached')) {
                resetOrderBtn.setAttribute('data-listener-attached', 'true');
                resetOrderBtn.addEventListener('click', function() {
                    const defaultVisibility = {};
                    DEFAULT_ORDER.forEach(id => { defaultVisibility[id] = true; });
                    syncModalList(DEFAULT_ORDER, defaultVisibility);
                    initModalSortable();
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeDashboard);
        } else {
            setTimeout(initializeDashboard, 100);
        }
    })();

    (function() {
        const workItemsSearch = document.getElementById('workItemsSearch');
        const workItemsSubprojectFilter = document.getElementById('workItemsSubprojectFilter');
        const workItemsCategoryFilter = document.getElementById('workItemsCategoryFilter');
        const workItemsStatusFilter = document.getElementById('workItemsStatusFilter');
        const itemStatusFilter = document.getElementById('itemStatusFilter');
        const clearFiltersBtn = document.getElementById('clearWorkItemsFilters');
        const workItemsContainer = document.getElementById('workItemsContainer');
        const workItemsCount = document.getElementById('workItemsCount');

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        let cachedItems = null;
        function getItems() {
            if (!cachedItems) {
                cachedItems = Array.from(document.querySelectorAll('.work-item-card'));
            }
            return cachedItems;
        }

        function filterWorkItems() {
            const searchTerm = (workItemsSearch?.value || '').toLowerCase().trim();
            const subprojectFilter = workItemsSubprojectFilter?.value || '';
            const categoryFilter = workItemsCategoryFilter?.value || '';
            const statusFilter = workItemsStatusFilter?.value || '';
            const itemStatusIdFilter = itemStatusFilter?.value || '';

            const items = getItems();
            let visibleCount = 0;

            items.forEach(item => {
                const itemName = item.getAttribute('data-item-name') || '';
                const itemSubproject = item.getAttribute('data-subproject') || '';
                const itemCategory = item.getAttribute('data-category') || '';
                const itemStatus = item.getAttribute('data-status') || '';
                const itemStatusId = item.getAttribute('data-item-status-id') || '';
                
                const itemText = item.textContent.toLowerCase();

                const matchesSearch = !searchTerm || 
                    itemName.includes(searchTerm) ||
                    itemText.includes(searchTerm) ||
                    itemSubproject.toLowerCase().includes(searchTerm) ||
                    itemCategory.includes(searchTerm);

                let matchesSubproject = true;
                if (subprojectFilter) {
                    const withoutSubprojectValue = '{{ __('general.without_subproject') }}';
                    if (subprojectFilter === withoutSubprojectValue || subprojectFilter === 'بدون فرعي' || subprojectFilter === 'Without Subproject') {
                        matchesSubproject = (!itemSubproject || itemSubproject === '' || 
                                           itemSubproject === 'uncategorized' || 
                                           itemSubproject === withoutSubprojectValue ||
                                           itemSubproject === 'بدون فرعي' ||
                                           itemSubproject === 'Without Subproject');
                    } else {
                        matchesSubproject = (itemSubproject === subprojectFilter.toLowerCase() || 
                                           itemSubproject === subprojectFilter);
                    }
                }

                let matchesCategory = true;
                if (categoryFilter) {
                    if (categoryFilter === 'uncategorized') {
                        matchesCategory = (itemCategory === 'uncategorized' || itemCategory === '');
                    } else {
                        matchesCategory = (itemCategory === categoryFilter.toLowerCase() || 
                                         itemCategory === categoryFilter);
                    }
                }

                const matchesStatus = !statusFilter || itemStatus === statusFilter;

                let matchesItemStatus = true;
                if (itemStatusIdFilter) {
                    if (itemStatusIdFilter === 'none' || itemStatusIdFilter === '') {
                        matchesItemStatus = (!itemStatusId || itemStatusId === '');
                    } else {
                        matchesItemStatus = (itemStatusId === itemStatusIdFilter);
                    }
                }

                if (matchesSearch && matchesSubproject && matchesCategory && matchesStatus && matchesItemStatus) {
                    item.style.display = '';
                    item.style.opacity = '1';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                    item.style.opacity = '0';
                }
            });

            if (workItemsCount) {
                const totalItems = items.length;
                if (visibleCount === totalItems) {
                    workItemsCount.textContent = totalItems + ' {{ __('general.items') }}';
                    workItemsCount.className = 'badge bg-primary';
                } else {
                    workItemsCount.textContent = visibleCount + ' / ' + totalItems + ' {{ __('general.items') }}';
                    workItemsCount.className = 'badge bg-info';
                }
            }
        }

        if (workItemsSearch) {
            workItemsSearch.addEventListener('input', debounce(filterWorkItems, 150));
            workItemsSearch.addEventListener('paste', debounce(filterWorkItems, 150));
        }
        
        if (workItemsSubprojectFilter) {
            workItemsSubprojectFilter.addEventListener('change', filterWorkItems);
        }
        
        if (workItemsCategoryFilter) {
            workItemsCategoryFilter.addEventListener('change', filterWorkItems);
        }
        
        if (workItemsStatusFilter) {
            workItemsStatusFilter.addEventListener('change', filterWorkItems);
        }
        
        if (itemStatusFilter) {
            itemStatusFilter.addEventListener('change', filterWorkItems);
        }
        
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function() {
                if (workItemsSearch) workItemsSearch.value = '';
                if (workItemsSubprojectFilter) workItemsSubprojectFilter.value = '';
                if (workItemsCategoryFilter) workItemsCategoryFilter.value = '';
                if (workItemsStatusFilter) workItemsStatusFilter.value = '';
                if (itemStatusFilter) itemStatusFilter.value = '';
                filterWorkItems();
            });
        }

        const observer = new MutationObserver(() => {
            cachedItems = null;
        });
        if (workItemsContainer) {
            observer.observe(workItemsContainer, { childList: true, subtree: true });
        }
        
        const showPlannedProgressCheckbox = document.getElementById('showPlannedProgress');
        if (showPlannedProgressCheckbox) {
            const plannedProgressSections = document.querySelectorAll('.planned-progress-section');
            
            function togglePlannedProgress() {
                plannedProgressSections.forEach(section => {
                    if (showPlannedProgressCheckbox.checked) {
                        section.style.display = '';
                    } else {
                        section.style.display = 'none';
                    }
                });
            }
            
            showPlannedProgressCheckbox.addEventListener('change', togglePlannedProgress);
            
            togglePlannedProgress();
        }
    })();

    (function() {
        const toggleGroupedViewBtn = document.getElementById('toggleWorkItemsGroupedView');
        const toggleGroupedText = document.getElementById('toggleWorkItemsGroupedText');
        const workItemsContainer = document.getElementById('workItemsContainer');
        const workItemsGroupedContainer = document.getElementById('workItemsGroupedContainer');
        let isGroupedView = false;

        function getItemsData() {
            const items = [];
            @foreach ($project->items as $item)
            @php
            $completionPercentage = $item->total_quantity > 0
                ? ($item->completed_quantity / $item->total_quantity) * 100
                : 0;
            @endphp
            items.push({
                name: @json($item->workItem->name ?? ''),
                category: @json($item->workItem && $item->workItem->category ? $item->workItem->category->name : 'uncategorized'),
                unit: @json($item->workItem->unit ?? ''),
                completed_quantity: {{ $item->completed_quantity }},
                remaining_quantity: {{ $item->remaining_quantity }},
                total_quantity: {{ $item->total_quantity }},
                completion_percentage: {{ $completionPercentage }},
                status: '{{ $item->total_quantity > 0 && $item->completed_quantity >= $item->total_quantity ? "completed" : ($item->completed_quantity > 0 ? "in_progress" : "pending") }}'
            });
            @endforeach
            return items;
        }

        const statusTranslations = {
            'completed': '{{ __('general.completed') }}',
            'in_progress': '{{ __('general.in_progress') }}',
            'pending': '{{ __('general.pending') }}',
            'delayed': '{{ __('general.delayed') }}'
        };
        
        function getStatusText(status) {
            return statusTranslations[status] || status;
        }

        // Group by work item name + unit only (ignores subproject completely)
        function groupItems(items) {
            const grouped = {};
            
            items.forEach(item => {
                const key = `${item.name}_${item.unit}`.toLowerCase();
                
                if (!grouped[key]) {
                    grouped[key] = {
                        name: item.name,
                        unit: item.unit,
                        category: item.category,
                        completed_quantity: 0,
                        remaining_quantity: 0,
                        total_quantity: 0,
                        items_count: 0,
                        status: item.status
                    };
                }
                
                grouped[key].completed_quantity += item.completed_quantity;
                grouped[key].remaining_quantity += item.remaining_quantity;
                grouped[key].total_quantity += item.total_quantity;
                grouped[key].items_count += 1;
            });

            Object.keys(grouped).forEach(key => {
                const group = grouped[key];
                group.completion_percentage = group.total_quantity > 0 
                    ? (group.completed_quantity / group.total_quantity) * 100 
                    : 0;
                
                if (group.completion_percentage >= 100) {
                    group.status = 'completed';
                } else if (group.completion_percentage > 0) {
                    group.status = 'in_progress';
                } else {
                    group.status = 'pending';
                }
            });

            return Object.values(grouped);
        }

        function renderGroupedView() {
            const items = getItemsData();
            const grouped = groupItems(items);
            
            workItemsGroupedContainer.innerHTML = '';
            
            if (grouped.length === 0) {
                workItemsGroupedContainer.innerHTML = '<div class="col-12 text-center py-4"><p class="text-muted">{{ __('general.no_items_found') }}</p></div>';
                return;
            }

            grouped.forEach(group => {
                const card = document.createElement('div');
                card.className = 'col-md-6 mb-4 grouped-item-card';
                card.setAttribute('data-item-name', group.name.toLowerCase());
                card.setAttribute('data-category', group.category.toLowerCase());
                card.setAttribute('data-status', group.status);
                
                const statusColors = {
                    'completed': 'success',
                    'in_progress': 'primary',
                    'delayed': 'danger',
                    'pending': 'warning'
                };
                const statusColor = statusColors[group.status] || 'secondary';
                
                const progressColor = group.completion_percentage >= 80 ? 'success' : 'primary';

                card.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <div class="fw-bold text-dark">${group.name}</div>
                            ${group.category && group.category !== 'uncategorized' ? 
                                `<small class="text-muted d-block"><i class="fas fa-folder me-1"></i>${group.category}</small>` : ''}
                            ${group.unit ? 
                                `<small class="text-muted d-block"><i class="fas fa-ruler me-1"></i>${group.unit}</small>` : ''}
                            <small class="text-muted d-block"><i class="fas fa-list me-1"></i>{{ __('general.items') }}: ${group.items_count}</small>
                        </div>
                        <div class="text-end">
                            <span class="fw-bold text-primary d-block">${group.completion_percentage.toFixed(1)}%</span>
                            <span class="badge bg-${statusColor} badge-sm">
                                ${getStatusText(group.status)}
                            </span>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted">
                                <i class="fas fa-check-circle me-1 text-success"></i>التقدم الفعلي
                            </small>
                            <small class="fw-bold text-success">${group.completion_percentage.toFixed(1)}%</small>
                        </div>
                        <div class="progress" style="height: 10px; border-radius: 8px;">
                            <div class="progress-bar bg-${progressColor}" 
                                 role="progressbar" 
                                 style="width: ${Math.min(group.completion_percentage, 100)}%">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-2 text-muted small">
                        <span><i class="fas fa-check-circle text-success me-1"></i>${group.completed_quantity.toFixed(2)} ${group.unit}</span>
                        <span><i class="fas fa-clock text-warning me-1"></i>${group.remaining_quantity.toFixed(2)} ${group.unit}</span>
                        <span><i class="fas fa-list text-info me-1"></i>${group.total_quantity.toFixed(2)} ${group.unit}</span>
                    </div>
                `;
                
                workItemsGroupedContainer.appendChild(card);
            });

            const countBadge = document.getElementById('workItemsCount');
            if (countBadge) {
                countBadge.textContent = grouped.length + ' {{ __('general.items') }} (مجمعة)';
            }
        }

        if (toggleGroupedViewBtn) {
            toggleGroupedViewBtn.addEventListener('click', function() {
                isGroupedView = !isGroupedView;
                
                if (isGroupedView) {
                    workItemsContainer.style.display = 'none';
                    workItemsGroupedContainer.style.display = 'flex';
                    toggleGroupedText.textContent = '{{ __('general.show_individual_items') }}';
                    renderGroupedView();
                } else {
                    workItemsContainer.style.display = 'flex';
                    workItemsGroupedContainer.style.display = 'none';
                    toggleGroupedText.textContent = '{{ __('general.group_by_item') }}';
                    const countBadge = document.getElementById('workItemsCount');
                    if (countBadge) {
                        const totalItems = document.querySelectorAll('.work-item-card').length;
                        countBadge.textContent = totalItems + ' {{ __('general.items') }}';
                    }
                }
            });
        }
    })();
</script>

<!-- Modal for Update All Subproject Weights -->
<div class="modal fade" id="updateAllWeightsModal" tabindex="-1" aria-labelledby="updateAllWeightsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateAllWeightsModalLabel">
                    <i class="fas fa-balance-scale me-2"></i>{{ __('general.update_all_weights') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">{{ __('general.update_all_weights_description') }}</p>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('general.subproject') }}</th>
                                <th class="text-end" style="width: 200px;">{{ __('general.weight') }} (%)</th>
                            </tr>
                        </thead>
                        <tbody id="weightsTableBody">
                            @foreach($hierarchicalData ?? [] as $subprojectName => $subprojectData)
                            @php
                                $subproject = $subprojectData['subproject'] ?? null;
                            @endphp
                            @if($subproject)
                            <tr>
                                <td>
                                    <strong>{{ $subprojectName }}</strong>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <input type="number" 
                                               class="form-control form-control-sm weight-input-dashboard" 
                                               data-subproject-id="{{ $subproject->id }}"
                                               value="{{ $subproject->weight ?? 0 }}"
                                               min="0"
                                               max="100"
                                               step="0.1"
                                               onchange="updateWeightInput(this)">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <th>{{ __('general.total') }}</th>
                                <th class="text-end">
                                    <span id="totalWeightDisplay" class="fw-bold">0%</span>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div id="weightValidationMessage" class="alert alert-warning mt-3" style="display: none;">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span id="weightValidationText"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('general.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="confirmUpdateAllWeightsBtn">
                    <i class="fas fa-save me-1"></i>{{ __('general.update_all_weights') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const updateAllWeightsModal = new bootstrap.Modal(document.getElementById('updateAllWeightsModal'));
    const confirmUpdateAllWeightsBtn = document.getElementById('confirmUpdateAllWeightsBtn');
    const totalWeightDisplay = document.getElementById('totalWeightDisplay');
    const weightValidationMessage = document.getElementById('weightValidationMessage');
    const weightValidationText = document.getElementById('weightValidationText');

    function updateTotalWeight() {
        const allWeightInputs = document.querySelectorAll('.weight-input-dashboard');
        let totalWeight = 0;
        
        allWeightInputs.forEach(input => {
            const weight = parseFloat(input.value) || 0;
            totalWeight += weight;
        });
        
        totalWeightDisplay.textContent = totalWeight.toFixed(2) + '%';
        
        if (Math.abs(totalWeight - 100) > 0.01) {
            weightValidationMessage.style.display = 'block';
            weightValidationText.textContent = '{{ __('general.total_weights_must_equal_100') }}: ' + totalWeight.toFixed(2) + '%';
            weightValidationMessage.className = 'alert alert-warning mt-3';
            confirmUpdateAllWeightsBtn.disabled = true;
        } else {
            weightValidationMessage.style.display = 'none';
            confirmUpdateAllWeightsBtn.disabled = false;
        }
    }

    window.updateWeightInput = function(input) {
        const weight = parseFloat(input.value) || 0;
        if (weight < 0) input.value = 0;
        if (weight > 100) input.value = 100;
        
        input.style.borderColor = weight > 0 ? '#28a745' : '#e9ecef';
        
        updateTotalWeight();
    };

    const modalElement = document.getElementById('updateAllWeightsModal');
    if (modalElement) {
        modalElement.addEventListener('show.bs.modal', function() {
            setTimeout(() => {
                updateTotalWeight();
                const inputs = modalElement.querySelectorAll('.weight-input-dashboard');
                inputs.forEach(input => {
                    input.addEventListener('input', updateTotalWeight);
                    input.addEventListener('change', updateTotalWeight);
                });
            }, 100);
        });
    }

    document.querySelectorAll('.weight-input-dashboard').forEach(input => {
        input.addEventListener('input', updateTotalWeight);
        input.addEventListener('change', updateTotalWeight);
    });

    if (confirmUpdateAllWeightsBtn) {
        confirmUpdateAllWeightsBtn.addEventListener('click', function() {
            const allWeightInputs = document.querySelectorAll('.weight-input-dashboard');
            const weights = {};
            
            allWeightInputs.forEach(input => {
                const subprojectId = input.getAttribute('data-subproject-id');
                const weight = parseFloat(input.value) || 0;
                if (subprojectId) {
                    weights[subprojectId] = weight;
                }
            });

            const totalWeight = Object.values(weights).reduce((sum, w) => sum + w, 0);
            if (Math.abs(totalWeight - 100) > 0.01) {
                alert('{{ __('general.total_weights_must_equal_100') }}: ' + totalWeight.toFixed(2) + '%');
                return;
            }

            confirmUpdateAllWeightsBtn.disabled = true;
            confirmUpdateAllWeightsBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>{{ __('general.updating') }}...';

            fetch('{{ route("projects.update-all-subprojects-weight", $project) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    weights: weights
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message || '{{ __('general.all_weights_updated_successfully') }}');
                    updateAllWeightsModal.hide();
                    location.reload();
                } else {
                    alert(data.message || '{{ __('general.error_occurred') }}');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ __('general.error_occurred') }}: ' + error.message);
            })
            .finally(() => {
                confirmUpdateAllWeightsBtn.disabled = false;
                confirmUpdateAllWeightsBtn.innerHTML = '<i class="fas fa-save me-1"></i>{{ __('general.update_all_weights') }}';
            });
        });
    }

    (function() {
        function initItemStatusAutoSave() {
            const statusSelects = document.querySelectorAll('.item-status-select');
            
            if (statusSelects.length === 0) {
                console.log('No item status selects found');
                return;
            }
            
            console.log('Found ' + statusSelects.length + ' item status selects');
            
            statusSelects.forEach(select => {
                if (select.hasAttribute('data-listener-attached')) {
                    return;
                }
                
                select.setAttribute('data-listener-attached', 'true');
                
                select.dataset.originalValue = select.value || '';
                
                select.addEventListener('change', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const itemId = this.dataset.itemId;
                    const projectId = this.dataset.projectId;
                    const statusId = this.value || null;
                    const card = this.closest('.work-item-card');
                    const loadingDiv = card ? card.querySelector('.item-status-loading') : null;
                    const originalValue = this.dataset.originalValue || '';
                    
                    console.log('Status changed:', {
                        itemId: itemId,
                        projectId: projectId,
                        statusId: statusId,
                        originalValue: originalValue
                    });
                    
                    if (!itemId || !projectId) {
                        console.error('Missing itemId or projectId');
                        this.value = originalValue;
                        return;
                    }
                    
                    if (loadingDiv) {
                        loadingDiv.classList.remove('d-none');
                    }
                    this.disabled = true;
                    
                    const url = `/projects/${projectId}/items/${itemId}/status`;
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    
                    console.log('Sending request to:', url);
                    
                    fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            item_status_id: statusId
                        })
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        if (!response.ok) {
                            return response.json().then(err => {
                                throw new Error(err.message || 'Request failed');
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Response data:', data);
                        if (data.success) {
                            if (card) {
                                card.setAttribute('data-item-status-id', data.item.item_status_id || '');
                            }
                            
                            this.dataset.originalValue = statusId || '';
                            
                            if (window.showToast) {
                                showToast(data.message || '{{ __('general.item_status_updated_successfully') }}', 'success');
                            } else {
                                alert(data.message || '{{ __('general.item_status_updated_successfully') }}');
                            }
                        } else {
                            this.value = originalValue || '';
                            const errorMsg = data.message || '{{ __('general.error_updating_status') }}';
                            if (window.showToast) {
                                showToast(errorMsg, 'error');
                            } else {
                                alert(errorMsg);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error updating status:', error);
                        this.value = originalValue || '';
                        const errorMsg = error.message || '{{ __('general.error_updating_status') }}';
                        if (window.showToast) {
                            showToast(errorMsg, 'error');
                        } else {
                            alert(errorMsg);
                        }
                    })
                    .finally(() => {
                        if (loadingDiv) {
                    loadingDiv.classList.add('d-none');
                        }
                        this.disabled = false;
                    });
                });
            });
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initItemStatusAutoSave);
        } else {
            setTimeout(initItemStatusAutoSave, 100);
        }
    })();
});
</script>

@php
    $konLogoPath = public_path('KON_logo.png');
    $konLogoBase64 = file_exists($konLogoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($konLogoPath)) : null;
@endphp

<!-- jsPDF + html2canvas for PDF export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
// ══════════════════════════════════════════════════════════════════════════════
//  KON PDF Export Utility
// ══════════════════════════════════════════════════════════════════════════════
(function () {

    const PROJECT_CODE = @json($project->name);
    const PROJECT_NAME = @json($project->client->name ?? '');
    const PRINT_DATE   = '{{ now()->format("d/m/Y H:i") }}';
    const BRAND        = 'KON';
    const LOGO_BASE64  = @json($konLogoBase64);

    // ── Loader overlay ────────────────────────────────────────────────────────
    function showLoader(msg) {
        let el = document.getElementById('kon-pdf-loader');
        if (!el) {
            el = document.createElement('div');
            el.id = 'kon-pdf-loader';
            el.style.cssText = [
                'position:fixed','top:0','left:0','width:100%','height:100%',
                'background:rgba(0,0,0,.55)','display:flex','flex-direction:column',
                'align-items:center','justify-content:center','z-index:99999',
                'color:#fff','font-family:Segoe UI,sans-serif'
            ].join(';');
            el.innerHTML = `
                <div style="background:#1e293b;border-radius:12px;padding:2rem 3rem;text-align:center;min-width:260px">
                    ${LOGO_BASE64 ? `<img src="${LOGO_BASE64}" alt="KON" style="height:40px;max-width:180px;margin-bottom:.8rem;object-fit:contain;display:inline-block;">` : '<div style="font-size:2rem;font-weight:900;color:#4361ee;letter-spacing:3px;margin-bottom:.5rem">KON</div>'}
                    <div id="kon-pdf-msg" style="font-size:.95rem;margin-bottom:1rem">Preparing PDF…</div>
                    <div style="width:100%;background:#334155;border-radius:99px;height:6px;overflow:hidden">
                        <div id="kon-pdf-bar" style="width:0%;height:100%;background:linear-gradient(90deg,#4361ee,#7c3aed);transition:width .3s"></div>
                    </div>
                </div>`;
            document.body.appendChild(el);
        } else {
            document.getElementById('kon-pdf-msg').textContent = msg || 'Preparing PDF…';
            document.getElementById('kon-pdf-bar').style.width = '0%';
            el.style.display = 'flex';
        }
        return el;
    }
    function setProgress(pct) {
        const bar = document.getElementById('kon-pdf-bar');
        if (bar) bar.style.width = pct + '%';
    }
    function hideLoader() {
        const el = document.getElementById('kon-pdf-loader');
        if (el) el.style.display = 'none';
    }

    // ── Build a dedicated single chart offscreen element for PDF ─────────────
    function buildSingleChartEl(subTitle, labels, progressData, plannedData) {
        const RENDER_W = 1600;
        const wrap = document.createElement('div');
        wrap.style.cssText = 'position:fixed;top:0;left:-9999px;width:' + RENDER_W + 'px;background:#fff;padding:0;box-sizing:border-box;overflow:visible;z-index:-1;font-family:Segoe UI,Tahoma,sans-serif;';

        const card = document.createElement('div');
        card.style.cssText = 'background:#fff;border-radius:6px;border:1px solid #e2e8f0;padding:10px;';

        if (subTitle) {
            const h = document.createElement('h5');
            h.style.cssText = 'font-size:15px;font-weight:700;color:#1e293b;margin-bottom:10px;';
            h.textContent = subTitle;
            card.appendChild(h);
        }

        const cv = document.createElement('canvas');
        cv.width = 1500;
        cv.height = Math.max(350, Math.min(600, labels.length * 45));
        const ctx = cv.getContext('2d');

        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: '{{ __('general.progress') }}',
                        data: progressData,
                        backgroundColor: 'rgba(33, 60, 122, 0.88)',
                        borderColor: '#213c7a',
                        borderWidth: 1,
                        borderRadius: 6
                    },
                    {
                        label: '{{ __('general.planned_progress') }}',
                        data: plannedData,
                        backgroundColor: 'rgba(148, 163, 184, 0.48)',
                        borderColor: '#64748b',
                        borderWidth: 1,
                        borderRadius: 6
                    }
                ]
            },
            options: {
                animation: false,
                responsive: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: { font: { size: 14 } }
                    }
                },
                scales: {
                    x: { ticks: { font: { size: 12, weight: 'bold' } } },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            font: { size: 12 },
                            callback: v => v + '%'
                        }
                    }
                }
            }
        });

        const img = document.createElement('img');
        img.src = cv.toDataURL('image/png');
        img.style.cssText = 'display:block;width:100%;height:auto;';
        chart.destroy();

        card.appendChild(img);
        wrap.appendChild(card);
        document.body.appendChild(wrap);

        return { wrap, captureEl: card };
    }

    // ── Build a hidden render container ──────────────────────────────────────
    function buildRenderEl(sourceEl) {
        const RENDER_W = 1600;

        const wrap = document.createElement('div');
        wrap.style.cssText = 'position:fixed;top:0;left:-9999px;width:' + RENDER_W + 'px;background:#fff;padding:0;box-sizing:border-box;overflow:visible;z-index:-1;font-family:Segoe UI,Tahoma,sans-serif;font-size:14px;';

        const clone = sourceEl.cloneNode(true);

        // Swap live canvas → static PNG
        const canvases    = sourceEl.querySelectorAll('canvas');
        const cloneCanvas = clone.querySelectorAll('canvas');
        canvases.forEach(function (cv, i) {
            try {
                const img = document.createElement('img');
                img.src = cv.toDataURL('image/png');
                img.style.cssText = 'display:block;width:100%;height:auto;';
                const par = cloneCanvas[i] && cloneCanvas[i].parentElement;
                if (par) { par.style.height = 'auto'; par.style.minHeight = '0'; }
                if (cloneCanvas[i]) cloneCanvas[i].replaceWith(img);
            } catch (e) {}
        });

        // Remove buttons / inputs / no-print / form-check
        clone.querySelectorAll('.no-print,.section-print-btn,button,.btn,select,input,.form-check,.item-status-loading,.card.mb-4.bg-light').forEach(function (e) { e.remove(); });

        // Remove redundant section headings & headers (since jsPDF already renders the clean section title at top)
        // and remove .gradient-text elements that cause purple box artifacts in html2canvas
        clone.querySelectorAll('.gradient-text, .accordion-header').forEach(function (e) {
            const headerRow = e.closest('.d-flex.justify-content-between, .accordion-header');
            if (headerRow) {
                headerRow.remove();
            } else {
                e.remove();
            }
        });

        // Expand all accordions
        clone.querySelectorAll('.accordion-collapse').forEach(function (e) {
            e.classList.add('show');
            e.style.cssText = 'display:block!important;height:auto!important;overflow:visible!important;';
        });
        clone.querySelectorAll('.accordion-button').forEach(function (e) {
            e.classList.remove('collapsed');
            e.setAttribute('aria-expanded', 'true');
        });

        // Style cards cleanly
        clone.querySelectorAll('.stat-card, .client-info-card, .employees-info-card, .card').forEach(function(c) {
            c.style.boxShadow = 'none';
            c.style.border = '1px solid #e2e8f0';
            c.style.borderRadius = '8px';
            c.style.marginBottom = '10px';
        });

        wrap.appendChild(clone);
        document.body.appendChild(wrap);
        return { wrap, captureEl: clone };
    }

    // ── Expand sections for PDF (handles multiple subprojects & categories) ──
    function expandSectionsForPdf(sections) {
        const expanded = [];
        const allSpData = @json($subprojectItemsChartData);
        const allCatData = @json($categoryItemsChartData);
        const showAllSubprojects = document.getElementById('showAllSubprojectItems')?.checked !== false;

        sections.forEach(function(sec) {
            if (sec.key === 'subproject-items-chart') {
                const sel = document.getElementById('subprojectSelect');
                const selectedVal = sel ? sel.value : '';

                if (selectedVal && allSpData[selectedVal]) {
                    const d = allSpData[selectedVal];
                    let labels = d.labels || [];
                    let actual = d.weighted_ratios || [];
                    let planned = d.planned_weighted_ratios || [];
                    if (!showAllSubprojects && d.is_measurable) {
                        labels  = labels.filter((_,i) => d.is_measurable[i]);
                        actual  = actual.filter((_,i) => d.is_measurable[i]);
                        planned = planned.filter((_,i) => d.is_measurable[i]);
                    }
                    expanded.push({
                        title: '{{ __('general.items_by_subproject') }} - ' + selectedVal,
                        key: 'custom-chart',
                        chartData: { subTitle: selectedVal, labels, actual, planned }
                    });
                } else {
                    let count = 0;
                    Object.keys(allSpData).forEach(function(spKey) {
                        const d = allSpData[spKey];
                        let labels = d.labels || [];
                        let actual = d.weighted_ratios || [];
                        let planned = d.planned_weighted_ratios || [];
                        if (!labels.length) return;

                        if (!showAllSubprojects && d.is_measurable) {
                            labels  = labels.filter((_,i) => d.is_measurable[i]);
                            actual  = actual.filter((_,i) => d.is_measurable[i]);
                            planned = planned.filter((_,i) => d.is_measurable[i]);
                        }
                        count++;
                        expanded.push({
                            title: '{{ __('general.items_by_subproject') }} - ' + spKey,
                            key: 'custom-chart',
                            chartData: { subTitle: spKey, labels, actual, planned }
                        });
                    });
                    if (count === 0) {
                        expanded.push(sec);
                    }
                }
            } else if (sec.key === 'category-items-chart') {
                const sel = document.getElementById('categorySelect');
                const selectedVal = sel ? sel.value : '';

                if (selectedVal && allCatData[selectedVal]) {
                    const d = allCatData[selectedVal];
                    expanded.push({
                        title: '{{ __('general.items_by_category') }} - ' + selectedVal,
                        key: 'custom-chart',
                        chartData: { subTitle: selectedVal, labels: d.labels || [], actual: d.weighted_ratios || [], planned: d.planned_weighted_ratios || [] }
                    });
                } else {
                    let count = 0;
                    Object.keys(allCatData).forEach(function(catKey) {
                        const d = allCatData[catKey];
                        const labels = d.labels || [];
                        if (!labels.length) return;
                        count++;
                        expanded.push({
                            title: '{{ __('general.items_by_category') }} - ' + catKey,
                            key: 'custom-chart',
                            chartData: { subTitle: catKey, labels, actual: d.weighted_ratios || [], planned: d.planned_weighted_ratios || [] }
                        });
                    });
                    if (count === 0) {
                        expanded.push(sec);
                    }
                }
            } else {
                expanded.push(sec);
            }
        });

        return expanded;
    }

    // ── Core: capture element → PDF pages (Multi-page Full-Width) ─────────────
    async function exportToPdf(sections, filename) {
        const { jsPDF } = window.jspdf;

        // A4 Landscape in mm
        const PAGE_W   = 297;
        const PAGE_H   = 210;
        const MARGIN   = 6;    // mm (Slim margins)
        const HEADER_H = 22;   // mm (Header stripe)
        const FOOTER_H = 7;    // mm (Footer stripe)
        const CONTENT_GAP = 2.7; // mm (~10px margin between header and top of content)
        const CONTENT_W = PAGE_W - MARGIN * 2; // 285 mm (Full Page Width)

        const pdf = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
        pdf.setFont('helvetica');

        let pageNum = 1;
        const totalSections = sections.length;

        function drawPageChrome() {
            // ── Header ────────────────────────────────────────────────────────
            pdf.setFillColor(67, 97, 238);
            pdf.rect(0, 0, PAGE_W, HEADER_H, 'F');

            // KON Logo or Brand text (left)
            if (LOGO_BASE64) {
                const logoH = 18; // mm (significantly enlarged & crisp)
                const logoW = logoH * (800 / 300); // aspect ratio 48 mm
                const logoY = (HEADER_H - logoH) / 2; // vertically centered in header
                try {
                    pdf.addImage(LOGO_BASE64, 'PNG', MARGIN, logoY, logoW, logoH, undefined, 'SLOW');
                } catch (e) {
                    pdf.setTextColor(255, 255, 255);
                    pdf.setFontSize(16);
                    pdf.setFont('helvetica', 'bold');
                    pdf.text(BRAND, MARGIN, (HEADER_H / 2) + 2.5);
                }
            } else {
                pdf.setTextColor(255, 255, 255);
                pdf.setFontSize(16);
                pdf.setFont('helvetica', 'bold');
                pdf.text(BRAND, MARGIN, (HEADER_H / 2) + 2.5);
            }

            // Project code & name (centre)
            pdf.setTextColor(255, 255, 255);
            pdf.setFontSize(11);
            pdf.setFont('helvetica', 'bold');
            const codeText = PROJECT_CODE + (PROJECT_NAME ? '  |  ' + PROJECT_NAME : '');
            const codeW = pdf.getTextWidth(codeText);
            pdf.text(codeText, (PAGE_W - codeW) / 2, (HEADER_H / 2) + 1.8);

            // Date (right)
            pdf.setFontSize(9);
            pdf.setFont('helvetica', 'normal');
            const dateW = pdf.getTextWidth(PRINT_DATE);
            pdf.text(PRINT_DATE, PAGE_W - MARGIN - dateW, (HEADER_H / 2) + 1.8);

            // ── Footer ───────────────────────────────────────────────────────
            pdf.setFillColor(248, 250, 252);
            pdf.rect(0, PAGE_H - FOOTER_H, PAGE_W, FOOTER_H, 'F');

            // Thin top border on footer
            pdf.setDrawColor(226, 232, 240);
            pdf.setLineWidth(0.3);
            pdf.line(0, PAGE_H - FOOTER_H, PAGE_W, PAGE_H - FOOTER_H);

            // Left footer: Website & Email
            pdf.setTextColor(100, 116, 139);
            pdf.setFontSize(8);
            pdf.setFont('helvetica', 'normal');
            const footerInfoText = 'kon-uae.com   |   info@kon-uae.com';
            pdf.text(footerInfoText, MARGIN, PAGE_H - (FOOTER_H / 2) + 1.2);

            // Right footer: Page number
            const pageText = 'Page ' + pageNum;
            const pgW = pdf.getTextWidth(pageText);
            pdf.text(pageText, PAGE_W - MARGIN - pgW, PAGE_H - (FOOTER_H / 2) + 1.2);

            pdf.setTextColor(0, 0, 0);
            pdf.setFont('helvetica', 'normal');
        }

        // First page chrome
        drawPageChrome();

        const contentTop = HEADER_H + CONTENT_GAP; // Starts with 10px gap below header
        let cursorY = contentTop;

        for (let s = 0; s < sections.length; s++) {
            const sec = sections[s];
            const { el, title, key, chartData } = sec;
            setProgress(Math.round(((s + 1) / totalSections) * 85));

            // Build off-screen render
            let built;
            if (key === 'custom-chart' && chartData) {
                built = buildSingleChartEl(chartData.subTitle, chartData.labels, chartData.actual, chartData.planned);
            } else if (key === 'work-items-progress' || key === 'hierarchical-view' || key === 'recent-activity') {
                built = buildTableEl(el, key);
            } else {
                built = buildRenderEl(el);
            }

            const wrapEl = built.wrap || built;
            const target = built.captureEl || built.wrap || built;

            // Wait for layout
            await new Promise(function(r) { setTimeout(r, 60); });

            // Capture with html2canvas at scale 2
            let canvas;
            let cutPoints = [];
            try {
                canvas = await html2canvas(target, {
                    scale: 2,
                    useCORS: true,
                    logging: false,
                    backgroundColor: '#ffffff',
                    allowTaint: true,
                    width: target.offsetWidth || 1600,
                    windowWidth: 1600
                });

                // Calculate element/row bottom boundaries in canvas pixels to prevent splitting rows across pages
                const targetRect = target.getBoundingClientRect();
                const scaleY = targetRect.height > 0 ? (canvas.height / targetRect.height) : 2;
                const cutElements = target.querySelectorAll('tr, .sub-title, .cat-title, thead, .card, .timeline-item, .item-card');
                cutElements.forEach(function (el) {
                    const r = el.getBoundingClientRect();
                    const bottomPx = Math.round((r.bottom - targetRect.top) * scaleY);
                    if (bottomPx > 0 && bottomPx <= canvas.height) {
                        cutPoints.push(bottomPx);
                    }
                });
                cutPoints.push(canvas.height);
                cutPoints.sort((a, b) => a - b);
            } finally {
                if (document.body.contains(wrapEl)) document.body.removeChild(wrapEl);
            }

            const mmPerPx = CONTENT_W / canvas.width;
            const totalImgMmH = canvas.height * mmPerPx;
            const LABEL_H = 5; // mm for section title
            const isTableSection = (key === 'work-items-progress' || key === 'hierarchical-view' || key === 'recent-activity');

            if (!isTableSection) {
                // ── CHARTS & SUMMARY CARDS: Must stay 100% intact on a single page, never split ──
                const requiredH = LABEL_H + totalImgMmH;
                const spaceLeft = (PAGE_H - FOOTER_H) - cursorY;

                if (cursorY > contentTop && requiredH > spaceLeft) {
                    pdf.addPage('a4', 'landscape');
                    pageNum++;
                    drawPageChrome();
                    cursorY = contentTop;
                }

                // Section Title Header
                pdf.setFontSize(10);
                pdf.setFont('helvetica', 'bold');
                pdf.setTextColor(67, 97, 238);
                pdf.text(title, MARGIN, cursorY + 3.5);
                cursorY += LABEL_H;

                // Subtle divider line
                pdf.setDrawColor(200, 210, 240);
                pdf.setLineWidth(0.3);
                pdf.line(MARGIN, cursorY, PAGE_W - MARGIN, cursorY);

                const maxAvailH = (PAGE_H - FOOTER_H) - cursorY;
                let drawH = totalImgMmH;
                if (drawH > maxAvailH) {
                    drawH = maxAvailH;
                }

                const imgData = canvas.toDataURL('image/jpeg', 0.95);
                pdf.addImage(imgData, 'JPEG', MARGIN, cursorY, CONTENT_W, drawH, '', 'FAST');
                cursorY += drawH;

            } else {
                // ── DATA TABLES: Can paginate across multiple pages without cutting any row in half ──
                const spaceLeft = (PAGE_H - FOOTER_H) - cursorY;
                if (cursorY > contentTop && spaceLeft < 30) {
                    pdf.addPage('a4', 'landscape');
                    pageNum++;
                    drawPageChrome();
                    cursorY = contentTop;
                }

                // Section Title Header
                pdf.setFontSize(10);
                pdf.setFont('helvetica', 'bold');
                pdf.setTextColor(67, 97, 238);
                pdf.text(title, MARGIN, cursorY + 3.5);
                cursorY += LABEL_H;

                // Subtle divider line
                pdf.setDrawColor(200, 210, 240);
                pdf.setLineWidth(0.3);
                pdf.line(MARGIN, cursorY, PAGE_W - MARGIN, cursorY);

                let srcY = 0; // px
                while (srcY < canvas.height) {
                    const availMmH = (PAGE_H - FOOTER_H) - cursorY;
                    if (availMmH <= 10) {
                        pdf.addPage('a4', 'landscape');
                        pageNum++;
                        drawPageChrome();
                        cursorY = contentTop;
                        continue;
                    }

                    const maxSlicePxH = Math.floor(availMmH / mmPerPx);
                    let slicePxH = Math.min(canvas.height - srcY, maxSlicePxH);

                    // Find the largest row boundary (cutPoint) that fits in this page slice
                    if (cutPoints.length > 0 && (srcY + maxSlicePxH < canvas.height)) {
                        let bestCut = -1;
                        for (let i = 0; i < cutPoints.length; i++) {
                            if (cutPoints[i] > srcY && cutPoints[i] <= srcY + maxSlicePxH) {
                                bestCut = cutPoints[i];
                            } else if (cutPoints[i] > srcY + maxSlicePxH) {
                                break;
                            }
                        }

                        if (bestCut > srcY) {
                            slicePxH = bestCut - srcY;
                        } else {
                            // No complete row fits in the remaining space; move to a fresh page immediately
                            if (cursorY > contentTop + 5) {
                                pdf.addPage('a4', 'landscape');
                                pageNum++;
                                drawPageChrome();
                                cursorY = contentTop;
                                continue;
                            }
                        }
                    }

                    const sliceMmH = slicePxH * mmPerPx;

                    // Slice sub-canvas
                    const sliceCanvas = document.createElement('canvas');
                    sliceCanvas.width = canvas.width;
                    sliceCanvas.height = slicePxH;
                    const sliceCtx = sliceCanvas.getContext('2d');
                    sliceCtx.fillStyle = '#ffffff';
                    sliceCtx.fillRect(0, 0, sliceCanvas.width, sliceCanvas.height);
                    sliceCtx.drawImage(
                        canvas,
                        0, srcY, canvas.width, slicePxH,
                        0, 0, canvas.width, slicePxH
                    );

                    const sliceData = sliceCanvas.toDataURL('image/jpeg', 0.95);
                    pdf.addImage(sliceData, 'JPEG', MARGIN, cursorY, CONTENT_W, sliceMmH, '', 'FAST');

                    srcY += slicePxH;
                    cursorY += sliceMmH;

                    if (srcY < canvas.height) {
                        pdf.addPage('a4', 'landscape');
                        pageNum++;
                        drawPageChrome();
                        cursorY = contentTop;
                    }
                }
            }
        }

        setProgress(100);
        pdf.save(filename);
    }

    // ── Build a clean HTML table render for data-heavy sections ──────────────
    function buildTableEl(sectionEl, sectionKey) {
        const RENDER_W = 1600;
        const wrap = document.createElement('div');
        wrap.style.cssText = [
            'position:fixed','top:0','left:-9999px',
            'width:' + RENDER_W + 'px',
            'background:#ffffff','padding:0','margin:0',
            'box-sizing:border-box','overflow:visible','z-index:-1',
            'font-family:Segoe UI,Tahoma,Geneva,Verdana,sans-serif',
            'font-size:13px','color:#1e293b'
        ].join(';');

        const style = document.createElement('style');
        style.textContent = `
            * { box-sizing:border-box; margin:0; padding:0; font-family:'Segoe UI',-apple-system,BlinkMacSystemFont,Roboto,'Helvetica Neue',Arial,sans-serif; }
            table { width:100%; border-collapse:collapse; font-size:13px; margin:0; padding:0; background:#fff; border:1px solid #e2e8f0; }
            th { background:#3b82f6; color:#ffffff; padding:7px 9px; text-align:left; font-weight:700; font-size:13px; letter-spacing:0.3px; border-bottom:2px solid #2563eb; }
            td { padding:6px 9px; border-bottom:1px solid #e2e8f0; color:#1e293b; vertical-align:middle; font-size:13px; }
            tr:last-child td { border-bottom:none; }
            tr:nth-child(even) td { background-color:#f8fafc; }
            .progress-cell { width:160px; min-width:160px; }
            .pbar-wrap { background:#e2e8f0; border-radius:4px; height:9px; overflow:hidden; width:100%; margin-bottom:2px; }
            .pbar { height:9px; border-radius:4px; }
            .pbar-success { background:#10b981; }
            .pbar-primary { background:#3b82f6; }
            .pbar-warning { background:#f59e0b; }
            .pbar-danger  { background:#ef4444; }
            .pbar-info    { background:#06b6d4; }
            .sub-title { font-size:13.5px; font-weight:700; color:#ffffff; background:linear-gradient(90deg,#1e293b,#334155); padding:6px 10px; margin:0; border-radius:4px 4px 0 0; display:flex; justify-content:space-between; align-items:center; }
            .cat-title { font-size:12px; font-weight:700; color:#1e293b; padding:5px 8px; background:#f1f5f9; border-left:4px solid #3b82f6; margin:0; display:flex; justify-content:space-between; align-items:center; }
            .badge { display:inline-block; padding:2px 7px; border-radius:10px; font-size:11px; font-weight:700; color:#fff; }
            .badge-success { background:#10b981; }
            .badge-primary { background:#3b82f6; }
            .badge-warning { background:#f59e0b; color:#000; }
            .badge-danger  { background:#ef4444; }
            .badge-info    { background:#06b6d4; }
            .badge-secondary { background:#64748b; }
            .item-sub { font-size:11px; color:#64748b; margin-top:1px; display:flex; flex-wrap:wrap; gap:5px; }
            .item-tag { display:inline-block; background:#f1f5f9; padding:1px 4px; border-radius:3px; }
        `;
        wrap.appendChild(style);

        function pbarClass(pct) {
            if (pct >= 80) return 'pbar-success';
            if (pct >= 50) return 'pbar-primary';
            if (pct >= 30) return 'pbar-warning';
            return 'pbar-danger';
        }
        function pbar(pct) {
            return '<div class="pbar-wrap"><div class="pbar ' + pbarClass(pct) + '" style="width:' + Math.min(pct, 100) + '%"></div></div>'
                 + '<div style="font-size:11px;font-weight:600;color:#475569">' + pct.toFixed(1) + '%</div>';
        }

        const container = document.createElement('div');

        if (sectionKey === 'work-items-progress') {
            // ── Work Items: flat table ────────────────────────────────────
            const items = sectionEl.querySelectorAll('.work-item-card');
            const tbl = document.createElement('table');
            tbl.innerHTML = `<thead><tr>
                <th style="width:40px;text-align:center">#</th>
                <th>Work Item</th>
                <th style="width:70px;text-align:center">Unit</th>
                <th style="width:170px">Quantities (Comp / Rem / Tot)</th>
                <th style="width:150px">Actual Progress</th>
                <th style="width:150px">Planned Progress</th>
                <th style="width:110px;text-align:center">Status</th>
            </tr></thead>`;
            const tbody = document.createElement('tbody');
            let idx = 1;
            items.forEach(function(card) {
                const name     = card.querySelector('.fw-bold.text-dark')?.innerText?.trim() || card.dataset.itemName || '';
                const subproj  = card.dataset.subproject || '';
                const catEl    = card.querySelector('.fa-folder')?.parentElement;
                const cat      = catEl ? catEl.innerText.trim() : (card.dataset.category || '');
                const unitEl   = card.querySelector('.fa-ruler')?.parentElement;
                const unit     = unitEl ? unitEl.innerText.trim() : '';
                const notesEl  = card.querySelector('.fa-sticky-note')?.parentElement;
                const notes    = notesEl ? notesEl.innerText.trim() : '';
                const status   = card.dataset.status || '';

                // Progress bars
                const actualBar  = card.querySelector('.progress-bar:first-child') || card.querySelectorAll('.progress-bar')[0];
                const actualPct  = actualBar ? parseFloat(actualBar.style.width) || 0 : 0;
                const plannedBar = card.querySelector('.planned-progress-section .progress-bar');
                const plannedPct = plannedBar ? (parseFloat(plannedBar.style.width) || 0) : 0;

                // Quantities
                const qSpans = card.querySelectorAll('.d-flex.justify-content-between.mt-2.text-muted.small span');
                const comp = qSpans[0]?.innerText?.trim() || '';
                const rem  = qSpans[1]?.innerText?.trim() || '';
                const tot  = qSpans[2]?.innerText?.trim() || '';

                const statusBadge = {
                    completed:  '<span class="badge badge-success">Completed</span>',
                    in_progress:'<span class="badge badge-primary">In Progress</span>',
                    pending:    '<span class="badge badge-warning">Pending</span>',
                    delayed:    '<span class="badge badge-danger">Delayed</span>'
                }[status] || '<span class="badge badge-secondary">' + status + '</span>';

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="text-align:center;color:#64748b;font-weight:600">${idx++}</td>
                    <td>
                        <strong style="color:#0f172a;font-size:13.5px">${name}</strong>
                        <div class="item-sub">
                            ${subproj ? `<span class="item-tag"><i class="fas fa-sitemap"></i> ${subproj}</span>` : ''}
                            ${cat ? `<span class="item-tag"><i class="fas fa-folder"></i> ${cat}</span>` : ''}
                            ${notes ? `<span class="item-tag">${notes}</span>` : ''}
                        </div>
                    </td>
                    <td style="text-align:center;font-weight:500">${unit}</td>
                    <td>
                        <div style="font-size:12px;color:#1e293b">
                            <span style="color:#10b981;font-weight:600">${comp}</span> / 
                            <span style="color:#f59e0b;font-weight:600">${rem}</span> / 
                            <span style="color:#3b82f6;font-weight:600">${tot}</span>
                        </div>
                    </td>
                    <td class="progress-cell">${pbar(actualPct)}</td>
                    <td class="progress-cell">${pbar(plannedPct)}</td>
                    <td style="text-align:center">${statusBadge}</td>
                `;
                tbody.appendChild(tr);
            });
            tbl.appendChild(tbody);
            container.appendChild(tbl);

        } else if (sectionKey === 'hierarchical-view') {
            // ── Hierarchical View: grouped by subproject → category → items ─
            const subprojectEls = sectionEl.querySelectorAll('.hierarchical-subproject');
            subprojectEls.forEach(function(spEl) {
                const spTitleEl = spEl.querySelector('strong');
                const spName = spTitleEl ? spTitleEl.innerText.trim() : (spEl.dataset.subprojectName || '');
                const spWeight = spEl.querySelector('.accordion-header .badge')?.innerText?.trim() || '';
                const spProgress = spEl.querySelector('.small.text-muted')?.innerText?.trim() || '';

                const spHead = document.createElement('div');
                spHead.className = 'sub-title';
                spHead.innerHTML = `<span><i class="fas fa-folder-open"></i> ${spName} ${spWeight ? `<span class="badge badge-info ms-2">${spWeight}</span>` : ''}</span><span>${spProgress}</span>`;
                container.appendChild(spHead);

                const categoryEls = spEl.querySelectorAll('.hierarchical-category');
                categoryEls.forEach(function(catEl) {
                    const catTitleEl = catEl.querySelector('strong');
                    const catName = catTitleEl ? catTitleEl.innerText.trim() : (catEl.dataset.categoryName || '');
                    const catCount = catEl.querySelector('.badge.bg-secondary')?.innerText?.trim() || '';
                    const catProgress = catEl.querySelector('.card-header .text-end')?.innerText?.trim() || '';

                    const catHead = document.createElement('div');
                    catHead.className = 'cat-title';
                    catHead.innerHTML = `<span><i class="fas fa-tag"></i> ${catName} ${catCount ? `<span class="badge badge-secondary ms-2">${catCount}</span>` : ''}</span><span>${catProgress}</span>`;
                    container.appendChild(catHead);

                    const rows = catEl.querySelectorAll('.item-card, .hierarchical-item-row, tr[data-item-id]');
                    if (!rows.length) return;

                    const tbl = document.createElement('table');
                    tbl.innerHTML = `<thead><tr>
                        <th style="width:40px;text-align:center">#</th>
                        <th>Item Name</th>
                        <th style="width:70px;text-align:center">Unit</th>
                        <th style="width:110px;text-align:center">Completed Qty</th>
                        <th style="width:110px;text-align:center">Remaining Qty</th>
                        <th style="width:110px;text-align:center">Total Qty</th>
                        <th style="width:150px">Progress</th>
                    </tr></thead>`;
                    const tbody = document.createElement('tbody');
                    let itemIdx = 1;
                    rows.forEach(function(row) {
                        const name = row.querySelector('.fw-bold.text-dark')?.innerText?.trim() || row.dataset.itemName || '-';
                        const unitEl = row.querySelector('.fa-ruler')?.parentElement;
                        const unit = unitEl ? unitEl.innerText.trim() : (row.dataset.itemUnit || '-');
                        const notesEl = row.querySelector('.fa-sticky-note')?.parentElement;
                        const notes = notesEl ? notesEl.innerText.trim() : '';
                        const datesEl = row.querySelector('.fa-calendar')?.parentElement;
                        const dates = datesEl ? datesEl.innerText.trim() : '';

                        const bar = row.querySelector('.progress-bar');
                        const pct = bar ? parseFloat(bar.style.width) || 0 : 0;

                        const qSpans = row.querySelectorAll('.d-flex.justify-content-between.text-muted.small span');
                        const comp = qSpans[0]?.innerText?.trim() || '';
                        const rem  = qSpans[1]?.innerText?.trim() || '';
                        const tot  = qSpans[2]?.innerText?.trim() || '';

                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td style="text-align:center;color:#64748b;font-weight:600">${itemIdx++}</td>
                            <td>
                                <strong style="color:#0f172a;font-size:13.5px">${name}</strong>
                                ${notes ? `<div style="font-size:11px;color:#64748b;margin-top:2px">${notes}</div>` : ''}
                                ${dates ? `<div style="font-size:10px;color:#94a3b8;margin-top:2px">${dates}</div>` : ''}
                            </td>
                            <td style="text-align:center">${unit}</td>
                            <td style="text-align:center;font-weight:600;color:#10b981">${comp}</td>
                            <td style="text-align:center;font-weight:600;color:#f59e0b">${rem}</td>
                            <td style="text-align:center;font-weight:600;color:#3b82f6">${tot}</td>
                            <td class="progress-cell">${pbar(pct)}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                    tbl.appendChild(tbody);
                    container.appendChild(tbl);
                });
            });

        } else if (sectionKey === 'recent-activity') {
            // ── Recent Activity / Daily Progress Record ────────────────────
            const timelineItems = sectionEl.querySelectorAll('.timeline-item');
            timelineItems.forEach(function(tlItem) {
                const date = tlItem.querySelector('.fw-bold.text-dark')?.innerText?.trim() || tlItem.dataset.date || '';
                const totalBadge = tlItem.querySelector('.badge')?.innerText?.trim() || '';

                const dateHead = document.createElement('div');
                dateHead.className = 'sub-title';
                dateHead.innerHTML = `<span><i class="fas fa-calendar-day"></i> ${date}</span><span>${totalBadge}</span>`;
                container.appendChild(dateHead);

                const progItems = tlItem.querySelectorAll('.progress-item');
                if (!progItems.length) return;

                const tbl = document.createElement('table');
                tbl.innerHTML = `<thead><tr>
                    <th style="width:40px;text-align:center">#</th>
                    <th style="width:220px">Employee</th>
                    <th>Work Item</th>
                    <th style="width:110px;text-align:center">Quantity</th>
                    <th style="width:80px;text-align:center">Unit</th>
                </tr></thead>`;
                const tbody = document.createElement('tbody');
                let pIdx = 1;
                progItems.forEach(function(pItem) {
                    const empName = pItem.querySelector('.fw-medium')?.innerText?.trim() || 'Unknown';
                    const itemName = pItem.querySelector('.fw-semibold.text-dark')?.innerText?.trim() || '-';
                    const cat = pItem.querySelector('.fa-folder')?.parentElement?.innerText?.trim() || '';
                    const notes = pItem.querySelector('.fa-sticky-note')?.parentElement?.innerText?.trim() || '';
                    const qty = pItem.querySelector('.fw-bold.text-primary')?.innerText?.trim() || '0';
                    const unit = pItem.querySelector('.text-muted')?.innerText?.trim() || '';

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td style="text-align:center;color:#64748b;font-weight:600">${pIdx++}</td>
                        <td><strong style="color:#1e293b;font-size:13.5px">${empName}</strong></td>
                        <td>
                            <div style="font-weight:600;color:#0f172a">${itemName}</div>
                            <div class="item-sub">
                                ${cat ? `<span class="item-tag"><i class="fas fa-folder"></i> ${cat}</span>` : ''}
                                ${notes ? `<span class="item-tag">${notes}</span>` : ''}
                            </div>
                        </td>
                        <td style="text-align:center;font-weight:700;color:#3b82f6;font-size:14px">${qty}</td>
                        <td style="text-align:center">${unit}</td>
                    `;
                    tbody.appendChild(tr);
                });
                tbl.appendChild(tbody);
                container.appendChild(tbl);
            });
        }

        wrap.appendChild(container);
        document.body.appendChild(wrap);
        return { wrap, captureEl: container };
    }

    // ── Helper: get visible sections list (respects Customize View settings) ─
    function getVisibleSections() {
        const STORAGE_KEY_VISIBILITY = 'dashboard_components_visibility';
        const STORAGE_KEY_ORDER = 'dashboard_components_order';
        let settings = {};
        try { settings = JSON.parse(localStorage.getItem(STORAGE_KEY_VISIBILITY) || '{}'); } catch (e) {}

        const defaultOrder = [
            'statistics-cards','total-weighted-progress',
            'bar-chart','subprojects-chart','categories-chart',
            'subproject-items-chart','category-items-chart',
            'hierarchical-view','work-items-progress',
            'recent-activity','timeline','client-info','employees-info'
        ];
        let savedOrder = [];
        try { savedOrder = JSON.parse(localStorage.getItem(STORAGE_KEY_ORDER) || '[]'); } catch (e) {}
        let order = (Array.isArray(savedOrder) && savedOrder.length > 0) ? savedOrder : defaultOrder;
        // Ensure any new components not in saved order are appended
        defaultOrder.forEach(id => { if (!order.includes(id)) order.push(id); });

        const sections = [];
        order.forEach(function (key) {
            // If not in settings at all, default to visible
            if (settings[key] === false) return;
            const el = document.querySelector('[data-component="' + key + '"]');
            if (!el || el.classList.contains('hidden')) return;
            const heading = el.querySelector('h4, h5, h6');
            const title = heading ? heading.innerText.replace(/[\n\r]+/g, ' ').trim() : key;
            sections.push({ el, title, key });
        });
        return sections;
    }

    // ── Full-dashboard Print button ───────────────────────────────────────────
    const printBtn = document.getElementById('printDashboardBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            let settings = {};
            let order = [];
            try { settings = JSON.parse(localStorage.getItem('dashboard_components_visibility') || '{}'); } catch (e) {}
            try { order = JSON.parse(localStorage.getItem('dashboard_components_order') || '[]'); } catch (e) {}
            const url = new URL('{{ route("projects.dashboard.print", $project->id) }}', window.location.origin);
            url.searchParams.set('components', JSON.stringify(settings));
            if (order.length) {
                url.searchParams.set('order', JSON.stringify(order));
            }
            window.open(url.toString(), '_blank');
        });
    }

    // ── Export PDF button (new dedicated button) ──────────────────────────────
    const exportPdfBtn = document.getElementById('exportPdfBtn');
    if (exportPdfBtn) {
        exportPdfBtn.addEventListener('click', async function () {
            const rawSections = getVisibleSections();
            if (!rawSections.length) return;
            const sections = expandSectionsForPdf(rawSections);
            showLoader('Building PDF — please wait…');
            try {
                const filename = BRAND + '_' + PROJECT_CODE.replace(/[^a-zA-Z0-9_\-]/g, '_') + '_dashboard.pdf';
                await exportToPdf(sections, filename);
            } catch (err) {
                console.error('PDF export error:', err);
                alert('PDF export failed. Please try again.');
            } finally {
                hideLoader();
            }
        });
    }

    // ── Per-section Print buttons ─────────────────────────────────────────────
    document.querySelectorAll('.section-print-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const sectionName = this.dataset.section;
            const el = document.querySelector('[data-component="' + sectionName + '"]');
            if (!el) return;

            const styles = Array.from(document.querySelectorAll('link[rel="stylesheet"], style'))
                .map(function (node) { return node.outerHTML; })
                .join('\n');

            const commonHead = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>Print</title>${styles}<style>
                @media print { .no-print { display:none!important; } body { margin:0; } }
                body { background:#fff; padding:20px; font-family:sans-serif; }
                img { max-width:100%; }
                .print-chart-block { page-break-inside: avoid; margin-bottom: 40px; }
                .print-chart-title { font-weight: bold; font-size: 1.1rem; margin-bottom: 10px; }
            </style></head><body>`;

            // ── Helper: render all keys from a data object as charts and return HTML ──
            function renderAllChartsAsHTML(allData, chartConfig, titlePrefix) {
                return new Promise(function(resolve) {
                    const keys = Object.keys(allData);
                    if (!keys.length) { resolve(''); return; }

                    let html = '';
                    let done = 0;
                    const results = new Array(keys.length);

                    keys.forEach(function(key, idx) {
                        const offscreen = document.createElement('canvas');
                        offscreen.width = 1100;
                        offscreen.height = 400;
                        const ctx = offscreen.getContext('2d');
                        const d = allData[key];
                        const cfg = chartConfig(d, key);
                        const chart = new Chart(ctx, cfg);

                        // Chart.js renders synchronously on offscreen canvas
                        results[idx] = `<div class="print-chart-block">
                            <div class="print-chart-title">${titlePrefix}: ${key}</div>
                            <img src="${offscreen.toDataURL('image/png')}" style="width:100%;height:auto;display:block;">
                        </div>`;
                        chart.destroy();
                        done++;
                        if (done === keys.length) {
                            resolve(results.join(''));
                        }
                    });
                });
            }

            // ── subproject-items-chart: print all subprojects if none selected ──
            if (sectionName === 'subproject-items-chart') {
                const sel = document.getElementById('subprojectSelect');
                const selectedVal = sel ? sel.value : '';
                const showAll = document.getElementById('showAllSubprojectItems')?.checked !== false;

                if (!selectedVal) {
                    // No subproject chosen → print all
                    const allData = @json($subprojectItemsChartData);
                    renderAllChartsAsHTML(allData, function(d, key) {
                        let labels = d.labels || [];
                        let actual = d.weighted_ratios || [];
                        let planned = d.planned_weighted_ratios || [];
                        if (!showAll && d.is_measurable) {
                            labels  = labels.filter((_,i) => d.is_measurable[i]);
                            actual  = actual.filter((_,i) => d.is_measurable[i]);
                            planned = planned.filter((_,i) => d.is_measurable[i]);
                        }
                        return {
                            type: 'bar',
                            data: {
                                labels: labels,
                                datasets: [
                                    { label: '{{ __('general.progress') }}', data: actual, backgroundColor: 'rgba(33,60,122,0.88)', borderColor: '#213c7a', borderWidth: 1, borderRadius: 6 },
                                    { label: '{{ __('general.planned_progress') }}', data: planned, backgroundColor: 'rgba(148,163,184,0.48)', borderColor: '#64748b', borderWidth: 1, borderRadius: 6 }
                                ]
                            },
                            options: { animation: false, responsive: false, plugins: { legend: { display: true, position: 'top' } }, scales: { y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } } } }
                        };
                    }, '{{ __('general.items_by_subproject') }}').then(function(chartsHtml) {
                        const win = window.open('', '_blank', 'width=1200,height=800');
                        if (!win) { alert('Please allow popups for this site to enable printing.'); return; }
                        win.document.write(commonHead + chartsHtml + '</body></html>');
                        win.document.close();
                        setTimeout(function() { win.focus(); win.print(); }, 800);
                    });
                    return;
                }
            }

            // ── category-items-chart: print all categories if none selected ──
            if (sectionName === 'category-items-chart') {
                const sel = document.getElementById('categorySelect');
                const selectedVal = sel ? sel.value : '';

                if (!selectedVal) {
                    const allData = @json($categoryItemsChartData);
                    renderAllChartsAsHTML(allData, function(d) {
                        return {
                            type: 'bar',
                            data: {
                                labels: d.labels || [],
                                datasets: [
                                    { label: '{{ __('general.progress') }}', data: d.weighted_ratios || [], backgroundColor: 'rgba(33,60,122,0.88)', borderColor: '#213c7a', borderWidth: 1, borderRadius: 6 },
                                    { label: '{{ __('general.planned_progress') }}', data: d.planned_weighted_ratios || [], backgroundColor: 'rgba(148,163,184,0.48)', borderColor: '#64748b', borderWidth: 1, borderRadius: 6 }
                                ]
                            },
                            options: { animation: false, responsive: false, plugins: { legend: { display: true, position: 'top' } }, scales: { y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } } } }
                        };
                    }, '{{ __('general.items_by_category') }}').then(function(chartsHtml) {
                        const win = window.open('', '_blank', 'width=1200,height=800');
                        if (!win) { alert('Please allow popups for this site to enable printing.'); return; }
                        win.document.write(commonHead + chartsHtml + '</body></html>');
                        win.document.close();
                        setTimeout(function() { win.focus(); win.print(); }, 800);
                    });
                    return;
                }
            }

            // ── Default: print the section as-is (with canvas → img) ──
            const clone = el.cloneNode(true);
            const canvases = el.querySelectorAll('canvas');
            const cloneCanvases = clone.querySelectorAll('canvas');
            canvases.forEach(function (canvas, i) {
                try {
                    const dataUrl = canvas.toDataURL('image/png');
                    const img = document.createElement('img');
                    img.src = dataUrl;
                    img.style.cssText = 'width:100%;height:auto;display:block;';
                    if (cloneCanvases[i] && cloneCanvases[i].parentNode) {
                        cloneCanvases[i].parentNode.replaceChild(img, cloneCanvases[i]);
                    }
                } catch (e) {
                    // canvas tainted (CORS) — leave as-is
                }
            });

            const printWin = window.open('', '_blank', 'width=1200,height=800');
            if (!printWin) {
                alert('Please allow popups for this site to enable printing.');
                return;
            }

            printWin.document.write(commonHead);
            printWin.document.write(clone.outerHTML);
            printWin.document.write('</body></html>');
            printWin.document.close();

            setTimeout(function () {
                printWin.focus();
                printWin.print();
            }, 800);
        });
    });

}());
// ─────────────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function() {
    const dailyProgressSearch = document.getElementById('dailyProgressSearch');
    const dailyProgressFromDate = document.getElementById('dailyProgressFromDate');
    const dailyProgressToDate = document.getElementById('dailyProgressToDate');
    const clearDailyProgressFilters = document.getElementById('clearDailyProgressFilters');
    const dailyProgressTimeline = document.getElementById('dailyProgressTimeline');

    function filterDailyProgress() {
        const searchTerm = (dailyProgressSearch?.value || '').toLowerCase();
        const fromDate = dailyProgressFromDate?.value || '';
        const toDate = dailyProgressToDate?.value || '';

        const timelineItems = dailyProgressTimeline?.querySelectorAll('.timeline-item') || [];

        timelineItems.forEach(timelineItem => {
            const date = timelineItem.getAttribute('data-date');
            const progressItems = timelineItem.querySelectorAll('.progress-item');
            let shouldShowTimelineItem = true;
            let hasVisibleItems = false;

            if (fromDate && date < fromDate) {
                shouldShowTimelineItem = false;
            }
            if (toDate && date > toDate) {
                shouldShowTimelineItem = false;
            }

            if (!shouldShowTimelineItem) {
                timelineItem.style.display = 'none';
                return;
            }

            progressItems.forEach(progressItem => {
                const employeeName = (progressItem.getAttribute('data-employee') || '').toLowerCase();
                const itemName = (progressItem.getAttribute('data-item') || '').toLowerCase();
                const matchesSearch = !searchTerm || 
                    employeeName.includes(searchTerm) || 
                    itemName.includes(searchTerm);

                if (matchesSearch) {
                    progressItem.style.display = '';
                    hasVisibleItems = true;
                } else {
                    progressItem.style.display = 'none';
                }
            });

            if (hasVisibleItems) {
                timelineItem.style.display = '';
            } else {
                timelineItem.style.display = 'none';
            }
        });
    }

    if (dailyProgressSearch) {
        dailyProgressSearch.addEventListener('input', filterDailyProgress);
    }
    if (dailyProgressFromDate) {
        dailyProgressFromDate.addEventListener('change', filterDailyProgress);
    }
    if (dailyProgressToDate) {
        dailyProgressToDate.addEventListener('change', filterDailyProgress);
    }
    if (clearDailyProgressFilters) {
        clearDailyProgressFilters.addEventListener('click', function() {
            if (dailyProgressSearch) dailyProgressSearch.value = '';
            if (dailyProgressFromDate) dailyProgressFromDate.value = '';
            if (dailyProgressToDate) dailyProgressToDate.value = '';
            filterDailyProgress();
        });
    }

});
</script>
@endsection
