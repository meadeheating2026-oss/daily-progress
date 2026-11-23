@extends('layouts.app')
@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('dashboard') }}" class="text-muted text-decoration-none">
            {{ __('general.dashboard') }}
        </a>
    </li>
    <li class="breadcrumb-item">
        <a href="{{ route('projects.index') }}" class="text-muted text-decoration-none">
            {{ __('general.projects') }}
        </a>
    </li>
    <li class="breadcrumb-item active">{{ __('general.archived_projects') }}</li>
@endsection
@section('title', __('general.archived_projects'))

@section('content')
    <div class="m-2 d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <h5 class="mb-0">
                <i class="fas fa-box-archive me-2 text-secondary"></i>
                {{ __('general.archived_projects') }}
            </h5>
            <span class="badge bg-secondary rounded-pill px-3 py-2">{{ $projects->total() }}</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('projects.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-project-diagram me-1"></i> {{ __('general.all_projects') }}
            </a>
            @can('projects-list')
                <a href="{{ route('projects.drafts') }}" class="btn btn-outline-warning">
                    <i class="fas fa-file-alt me-1"></i> {{ __('general.drafts') }}
                </a>
            @endcan
        </div>
    </div>

    @if($projects->isEmpty() && !request()->hasAny(['search', 'status', 'type', 'client']))
        <div class="card border-0 rounded-4 shadow-sm">
            <div class="card-body text-center py-5">
                <div class="mb-3">
                    <div class="d-inline-flex p-4 rounded-circle bg-light text-muted">
                        <i class="fas fa-box-archive" style="font-size: 3.5rem;"></i>
                    </div>
                </div>
                <h5 class="text-muted fw-bold">{{ __('general.no_archived_projects') }}</h5>
                <p class="text-muted mb-4">{{ __('general.no_archived_projects_description') }}</p>
                <a href="{{ route('projects.index') }}" class="btn btn-primary px-4 py-2">
                    <i class="fas fa-arrow-right me-1"></i> {{ __('general.all_projects') }}
                </a>
            </div>
        </div>
    @else
        {{-- Filters --}}
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 20px;">
            <div class="card-body p-4">
                <form method="GET"
                      action="{{ route('projects.archived') }}"
                      id="archivedFilterForm"
                      data-server-filter="{{ ($projects->hasPages() || request()->hasAny(['search', 'status', 'type', 'client'])) ? '1' : '0' }}">
                    <div class="row g-3 align-items-end">
                        {{-- Search --}}
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <label class="form-label text-muted small mb-2">
                                <i class="fas fa-search me-2"></i>{{ __('general.search') }}
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0" style="border-radius: 12px 0 0 12px;">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" 
                                       id="projectSearch" 
                                       name="search"
                                       value="{{ request('search') }}"
                                       class="form-control border-0 bg-light" 
                                       placeholder="{{ __('general.search_by_name_or_client') }}"
                                       style="border-radius: 0 12px 12px 0;">
                            </div>
                        </div>

                        {{-- Status Filter --}}
                        <div class="col-lg-2 col-md-6 col-sm-12">
                            <label class="form-label text-muted small mb-2">
                                <i class="fas fa-filter me-2"></i>{{ __('general.status') }}
                            </label>
                            <select id="projectStatusFilter" name="status" class="form-select border-0 bg-light" style="border-radius: 12px;">
                                <option value="all">{{ __('general.all_statuses') }}</option>
                                <option value="active" @selected(request('status') === 'active')>{{ __('general.active') }}</option>
                                <option value="completed" @selected(request('status') === 'completed')>{{ __('general.completed') }}</option>
                                <option value="pending" @selected(request('status') === 'pending')>{{ __('general.pending') }}</option>
                            </select>
                        </div>

                        {{-- Project Type Filter --}}
                        <div class="col-lg-3 col-md-6 col-sm-12">
                            <label class="form-label text-muted small mb-2">
                                <i class="fas fa-tag me-2"></i>{{ __('general.type_of_project') }}
                            </label>
                            <select id="projectTypeFilter" name="type" class="form-select border-0 bg-light" style="border-radius: 12px;">
                                <option value="all">{{ __('general.all_types') }}</option>
                                @foreach(($filterTypes ?? collect()) as $type)
                                    <option value="{{ $type->name }}" @selected(request('type') == $type->name || request('type') == $type->id)>{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Client Filter --}}
                        <div class="col-lg-2 col-md-6 col-sm-12">
                            <label class="form-label text-muted small mb-2">
                                <i class="fas fa-building me-2"></i>{{ __('projects.client') }}
                            </label>
                            <select id="projectClientFilter" name="client" class="form-select border-0 bg-light" style="border-radius: 12px;">
                                <option value="all">{{ __('general.all_clients') }}</option>
                                @foreach(($filterClients ?? collect()) as $client)
                                    <option value="{{ $client->name }}" @selected(request('client') == $client->name || request('client') == $client->id)>{{ $client->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Clear Filters --}}
                        <div class="col-lg-1 col-md-6 col-sm-12">
                            <button type="button" 
                                    id="clearFilters" 
                                    class="btn btn-outline-secondary w-100" 
                                    title="{{ __('general.clear_filters') }}"
                                    style="border-radius: 12px;">
                                <i class="fas fa-times"></i>
                            </button>
                            <div class="text-center mt-2">
                                <small class="text-muted">
                                    <span id="filterResults">{{ $projects->total() }}</span> 
                                    <span>{{ __('general.results') }}</span>
                                </small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <style>
            .project-card {
                --status: #6b7280;
                --status-soft: rgba(107, 114, 128, 0.12);
                cursor: pointer;
                border-radius: 14px;
                border: 1px solid rgba(15, 23, 42, 0.08);
                background: #fff;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
                transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
                overflow: hidden;
            }
            .project-card[data-project-status="active"] {
                --status: #10b981;
                --status-soft: rgba(16, 185, 129, 0.12);
            }
            .project-card[data-project-status="pending"] {
                --status: #f59e0b;
                --status-soft: rgba(245, 158, 11, 0.14);
            }
            .project-card[data-project-status="completed"] {
                --status: #64748b;
                --status-soft: rgba(100, 116, 139, 0.12);
            }
            .project-card:hover {
                transform: translateY(-3px);
                border-color: color-mix(in srgb, var(--status) 35%, transparent);
                box-shadow: 0 8px 20px -4px rgba(15, 23, 42, 0.08), 0 0 0 1px color-mix(in srgb, var(--status) 20%, transparent);
            }
            .project-card .status-strip {
                height: 4px;
                background: #64748b;
                width: 100%;
            }
            .project-card .card-body {
                padding: 1rem 1.1rem 0.85rem;
            }
            .project-card .project-title {
                font-size: 0.95rem;
                font-weight: 600;
                color: #0f172a;
                margin: 0;
                letter-spacing: -0.01em;
            }
            .project-card .project-client {
                font-size: 0.78rem;
                color: #64748b;
            }
            .project-card .status-badge {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.2rem 0.55rem;
                border-radius: 9999px;
                font-size: 0.7rem;
                font-weight: 600;
                background: var(--status-soft);
                color: var(--status);
            }
            .project-card .archive-badge {
                display: inline-flex;
                align-items: center;
                gap: 0.3rem;
                padding: 0.2rem 0.5rem;
                border-radius: 9999px;
                font-size: 0.68rem;
                font-weight: 600;
                background: rgba(100, 116, 139, 0.15);
                color: #475569;
            }
            .project-card .progress-row {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                margin: 0.75rem 0 0.65rem;
            }
            .project-card .project-progress-text {
                font-size: 0.82rem;
                font-weight: 700;
                color: #0f172a;
                min-width: 2.2rem;
                text-align: right;
            }
            [dir="rtl"] .project-card .project-progress-text {
                text-align: left;
            }
            .project-card .progress {
                flex: 1;
                height: 6px;
                background: #f1f5f9;
                border-radius: 9999px;
                overflow: hidden;
            }
            .project-card .progress-bar {
                background: var(--status);
                border-radius: 9999px;
            }
            .project-card .card-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 0.4rem 0.6rem;
                margin-top: 0.4rem;
                font-size: 0.75rem;
                color: #475569;
            }
            .project-card .meta-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.3rem;
                background: #f8fafc;
                padding: 0.18rem 0.5rem;
                border-radius: 6px;
                border: 1px solid rgba(15, 23, 42, 0.04);
            }
            .project-card .meta-chip button {
                background: none;
                border: none;
                padding: 0;
                color: inherit;
                font-size: inherit;
                cursor: pointer;
            }
            .project-card .meta-chip button:hover {
                color: #2563eb;
                text-decoration: underline;
            }
            .project-card .date-row {
                display: flex;
                justify-content: space-between;
                font-size: 0.72rem;
                color: #94a3b8;
                margin-top: 0.65rem;
                padding-top: 0.55rem;
                border-top: 1px dashed rgba(15, 23, 42, 0.08);
            }
            .project-card .card-footer {
                padding: 0.55rem 1.1rem;
                background: #fafbfc;
                border-top: 1px solid rgba(15, 23, 42, 0.06);
            }
            .project-card .btn-details {
                font-size: 0.76rem;
                font-weight: 500;
                color: #475569;
                background: transparent;
                border: 1px solid rgba(15, 23, 42, 0.12);
                border-radius: 6px;
                padding: 0.25rem 0.6rem;
                transition: all 0.15s ease;
            }
            .project-card .btn-details:hover {
                background: #fff;
                color: #0f172a;
                border-color: rgba(15, 23, 42, 0.25);
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
            }
            .project-card .action-btn {
                width: 28px;
                height: 28px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 6px;
                color: #64748b;
                background: transparent;
                border: none;
                font-size: 0.8rem;
                transition: all 0.15s ease;
                text-decoration: none;
            }
            .project-card .action-btn:hover {
                background: rgba(15, 23, 42, 0.06);
                color: #0f172a;
            }
            .dark-mode .project-card {
                background: #1e293b;
                border-color: rgba(255, 255, 255, 0.08);
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
            }
            .dark-mode .project-card .project-title,
            .dark-mode .project-card .project-progress-text { color: #f1f5f9; }
            .dark-mode .project-card .project-client,
            .dark-mode .project-card .date-row { color: #94a3b8; }
            .dark-mode .project-card .meta-chip {
                background: rgba(255, 255, 255, 0.04);
                color: #cbd5e1;
                border-color: rgba(255, 255, 255, 0.06);
            }
            .dark-mode .project-card .progress { background: rgba(255, 255, 255, 0.08); }
            .dark-mode .project-card .card-footer {
                background: #172033;
                border-top-color: rgba(255, 255, 255, 0.06);
            }
            .dark-mode .project-card .btn-details {
                color: #cbd5e1;
                border-color: rgba(255, 255, 255, 0.15);
            }
            .dark-mode .project-card .action-btn:hover {
                background: rgba(255, 255, 255, 0.08);
                color: #f1f5f9;
            }
        </style>

        {{-- Projects Grid --}}
        <div class="row g-4" id="projectsGrid">
            @forelse($projects as $project)
                @php
                    $subprojectNamesWithItems = $project->items
                        ->pluck('subproject_name')
                        ->filter(fn ($name) => filled($name))
                        ->unique()
                        ->values()
                        ->all();

                    $subprojectsWithItemsCount = $project->subprojects
                        ->filter(fn ($subproject) => in_array($subproject->name, $subprojectNamesWithItems, true))
                        ->count();
                    if ($project->items->contains(fn ($item) => empty($item->subproject_name))) {
                        $subprojectsWithItemsCount++;
                    }

                    $progress = $project->overall_progress ?? 0;
                    $itemsCount = $project->items_count ?? $project->items->count();
                    $employeesCount = $project->employees_count ?? 0;
                    $statusLabel = match($project->status) {
                        'active' => __('general.active'),
                        'completed' => __('general.completed'),
                        'pending' => __('general.pending'),
                        default => $project->status,
                    };
                @endphp
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="card h-100 project-card"
                         data-project-id="{{ $project->id }}"
                         data-project-name="{{ $project->name }}"
                         data-project-description="{{ $project->description ?? '' }}"
                         data-project-client="{{ $project->client->name }}"
                         data-project-status="{{ $project->status }}"
                         data-project-type="{{ $project->type ? $project->type->name : __('general.not_specified') }}"
                         data-project-start-date="{{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('d-m-Y') : '' }}"
                         data-project-end-date="{{ $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('d-m-Y') : __('general.in_progress') }}"
                         data-project-working-zone="{{ $project->working_zone ?? '' }}"
                         data-project-working-days="{{ $project->working_days ?? '' }}"
                         data-project-daily-hours="{{ $project->daily_work_hours ?? '' }}"
                         data-project-weekly-holidays="{{ $project->weekly_holidays ?? '' }}"
                         data-project-items-count="{{ $itemsCount }}"
                         data-project-subprojects-count="{{ $subprojectsWithItemsCount }}"
                         data-project-employees-count="{{ $employeesCount }}"
                         data-project-progress="{{ $progress }}"
                         data-project-created="{{ $project->created_at ? \Carbon\Carbon::parse($project->created_at)->format('d-m-Y') : '' }}">

                        <div class="status-strip"></div>

                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="flex-grow-1 min-w-0">
                                    <h6 class="project-title text-truncate" title="{{ $project->name }}">{{ $project->name }}</h6>
                                    <div class="project-client text-truncate mt-1">
                                        <i class="fas fa-building me-1 opacity-75"></i>{{ $project->client->name }}
                                    </div>
                                </div>
                                <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                                    <span class="archive-badge">
                                        <i class="fas fa-box-archive me-1"></i>{{ __('general.archive') }}
                                    </span>
                                    <span class="status-badge">{{ $statusLabel }}</span>
                                </div>
                            </div>

                            <div class="progress-row">
                                <span class="project-progress-text">{{ round($progress) }}%</span>
                                <div class="progress">
                                    <div class="progress-bar"
                                        role="progressbar"
                                        style="width: {{ min(100, $progress) }}%;"
                                        aria-valuenow="{{ $progress }}"
                                        aria-valuemin="0"
                                        aria-valuemax="100">
                                    </div>
                                </div>
                            </div>

                            <div class="card-meta">
                                <span class="meta-chip">
                                    <i class="fas fa-tag opacity-60"></i>
                                    {{ $project->type ? $project->type->name : __('general.not_specified') }}
                                </span>
                                <span class="meta-chip">
                                    <i class="fas fa-list-ul opacity-60"></i>
                                    {{ $itemsCount }} {{ __('general.items') }}
                                </span>
                                @if($subprojectsWithItemsCount > 0)
                                    <span class="meta-chip">
                                        <i class="fas fa-sitemap opacity-60"></i>
                                        <button type="button"
                                                class="view-subprojects-btn"
                                                data-project-id="{{ $project->id }}"
                                                onclick="event.stopPropagation();">
                                            {{ $subprojectsWithItemsCount }} {{ __('general.subprojects') }}
                                        </button>
                                    </span>
                                @endif
                            </div>

                            <div class="date-row">
                                <span>
                                    <i class="far fa-calendar me-1"></i>
                                    {{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('d-m-Y') : '—' }}
                                </span>
                                @if($project->archived_at)
                                    <span class="text-secondary" title="{{ __('general.archived_at') }}">
                                        <i class="fas fa-box-archive me-1"></i>
                                        {{ \Carbon\Carbon::parse($project->archived_at)->format('d-m-Y') }}
                                    </span>
                                @else
                                    <span>
                                        <i class="far fa-calendar-check me-1"></i>
                                        {{ $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('d-m-Y') : __('general.in_progress') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-details view-details-btn" data-project-id="{{ $project->id }}">
                                {{ __('general.view_details') }}
                            </button>
                            <div class="d-flex gap-1">
                                @can('projects-view')
                                    <a href="{{ route('projects.dashboard', $project->id) }}"
                                        class="action-btn"
                                        title="{{ __('general.view') }}"
                                        onclick="event.stopPropagation();">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    {{-- Charts (Gantt) --}}
                                    <a href="{{ route('projects.gantt', $project->id) }}"
                                        class="action-btn"
                                        style="color:#a78bfa;"
                                        title="Gantt Chart"
                                        onclick="event.stopPropagation();">
                                        <i class="fas fa-chart-bar"></i>
                                    </a>
                                    {{-- Progress --}}
                                    <a href="{{ route('projects.progress', $project->id) }}"
                                        class="action-btn"
                                        style="color:#34d399;"
                                        title="Progress"
                                        onclick="event.stopPropagation();">
                                        <i class="fas fa-tasks"></i>
                                    </a>
                                @endcan

                                @can('projects-edit')
                                    {{-- Unarchive Project --}}
                                    <form action="{{ route('projects.unarchive', $project) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('{{ __('general.confirm_unarchive_project') }}')"
                                          onclick="event.stopPropagation();">
                                        @csrf
                                        <button type="submit"
                                            class="action-btn"
                                            style="color:#0284c7;"
                                            title="{{ __('general.unarchive_project') }}">
                                            <i class="fas fa-box-open"></i>
                                        </button>
                                    </form>
                                @endcan

                                @can('projects-delete')
                                    {{-- Delete (Soft Delete → Recycle Bin) --}}
                                    <form action="{{ route('projects.destroy', $project) }}"
                                          method="POST"
                                          class="d-inline delete-project-form"
                                          onsubmit="return confirm('هل تريد نقل المشروع إلى سلة المهملات؟')"
                                          onclick="event.stopPropagation();">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="action-btn"
                                            style="color:#f87171;"
                                            title="حذف (سلة المهملات)">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <div class="alert alert-info border-0 shadow-sm" style="border-radius: 20px; padding: 2rem;">
                        <i class="fas fa-search fa-3x mb-3 text-muted"></i>
                        <h5 class="text-muted mb-2">لا توجد مشاريع مطابقة للبحث</h5>
                        <p class="text-muted mb-0">جرب البحث بكلمات مختلفة أو غير الفلاتر</p>
                    </div>
                </div>
            @endforelse
        </div>

        @if($projects->hasPages())
            <div class="d-flex justify-content-center mt-4 mb-2">
                {{ $projects->links('pagination::bootstrap-5') }}
            </div>
        @endif
    @endif

    {{-- Project Details Modal --}}
    <div class="modal fade" id="projectDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h4 class="modal-title" id="modal_project_name">
                        <i class="fas fa-project-diagram me-2"></i>
                        {{ __('general.project_details') }}
                    </h4>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="projectDetailsContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">{{ __('general.loading') }}...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>{{ __('general.close') }}
                    </button>
                    <div id="modalActionButtons" class="d-flex gap-2"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Subprojects Modal --}}
    <div class="modal fade" id="subprojectsModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content" style="border-radius: 20px; overflow: hidden;">
                <div class="modal-header bg-gradient text-white" 
                     style="background: linear-gradient(135deg, var(--primary) 0%, var(--purple) 100%);">
                    <h4 class="modal-title" id="subprojectsModalTitle">
                        <i class="fas fa-sitemap me-2"></i>
                        {{ __('general.subprojects') }}
                    </h4>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="subprojectsContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">{{ __('general.loading') }}...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 12px;">
                        <i class="fas fa-times me-1"></i>{{ __('general.close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let currentProjectId = null;
            
            const translations = {
                active: '{{ __('general.active') }}',
                completed: '{{ __('general.completed') }}',
                pending: '{{ __('general.pending') }}',
                progress: '{{ __('general.progress') }}',
                basicInfo: '{{ __('general.basic_information') }}',
                statistics: '{{ __('general.statistics') }}',
                typeOfProject: '{{ __('general.type_of_project') }}',
                startDate: '{{ __('projects.start_date') }}',
                endDate: '{{ __('projects.end_date') }}',
                workingZone: '{{ __('general.working_zone') }}',
                createdAt: '{{ __('general.created_at') }}',
                notSpecified: '{{ __('general.not_specified') }}',
                inProgress: '{{ __('general.in_progress') }}',
                items: '{{ __('general.items') }}',
                subprojects: '{{ __('general.subprojects') }}',
                employees: '{{ __('general.employees') }}',
                workingDays: '{{ __('general.working_days') }}',
                dailyWorkHours: '{{ __('general.daily_work_hours') }}',
                hours: '{{ __('general.hours') }}',
                description: '{{ __('general.description') }}',
                view: '{{ __('general.view') }}',
                edit: '{{ __('general.edit') }}',
                progressReport: '{{ __('general.progress_report') }}',
                ganttChart: '{{ __('general.gantt_chart') }}',
                unarchive: '{{ __('general.unarchive_project') }}'
            };
            
            // Handle project card clicks and view details button
            document.querySelectorAll('.project-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    if (e.target.closest('a') || e.target.closest('button') || e.target.closest('form')) {
                        return;
                    }
                    showProjectDetails(this);
                });
            });

            document.querySelectorAll('.view-details-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const card = this.closest('.project-card');
                    showProjectDetails(card);
                });
            });

            function showProjectDetails(card) {
                const projectId = card.dataset.projectId;
                currentProjectId = projectId;
                
                const projectName = card.dataset.projectName;
                const projectClient = card.dataset.projectClient;
                const projectStatus = card.dataset.projectStatus;
                const projectType = card.dataset.projectType;
                const projectStartDate = card.dataset.projectStartDate;
                const projectEndDate = card.dataset.projectEndDate;
                const projectWorkingZone = card.dataset.projectWorkingZone;
                const projectWorkingDays = card.dataset.projectWorkingDays;
                const projectDailyHours = card.dataset.projectDailyHours;
                const projectItemsCount = card.dataset.projectItemsCount;
                const projectSubprojectsCount = card.dataset.projectSubprojectsCount;
                const projectEmployeesCount = card.dataset.projectEmployeesCount;
                const projectProgress = parseFloat(card.dataset.projectProgress) || 0;
                const projectCreated = card.dataset.projectCreated;
                const projectDescription = card.dataset.projectDescription;

                document.getElementById('modal_project_name').innerHTML = `
                    <i class="fas fa-project-diagram me-2"></i>${projectName}
                `;

                let statusBadgeClass = 'bg-secondary';
                let statusText = projectStatus;
                if (projectStatus === 'active') {
                    statusBadgeClass = 'bg-success';
                    statusText = translations.active;
                } else if (projectStatus === 'completed') {
                    statusBadgeClass = 'bg-info';
                    statusText = translations.completed;
                } else if (projectStatus === 'pending') {
                    statusBadgeClass = 'bg-warning text-dark';
                    statusText = translations.pending;
                }

                let contentHtml = `
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card h-100 border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="card-title text-primary mb-3">
                                        <i class="fas fa-info-circle me-2"></i>${translations.basicInfo}
                                    </h6>
                                    <table class="table table-borderless table-sm mb-0">
                                        <tr>
                                            <td class="text-muted" style="width: 40%;">${translations.typeOfProject}:</td>
                                            <td class="fw-semibold">${projectType}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">${translations.startDate}:</td>
                                            <td class="fw-semibold">${projectStartDate || translations.notSpecified}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">${translations.endDate}:</td>
                                            <td class="fw-semibold">${projectEndDate}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">${translations.workingZone}:</td>
                                            <td class="fw-semibold">${projectWorkingZone || translations.notSpecified}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">${translations.createdAt}:</td>
                                            <td class="fw-semibold">${projectCreated || translations.notSpecified}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100 border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="card-title text-primary mb-3">
                                        <i class="fas fa-chart-pie me-2"></i>${translations.statistics}
                                    </h6>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">${translations.progress}:</span>
                                            <span class="fw-bold">${Math.round(projectProgress)}%</span>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-success" style="width: ${Math.min(100, projectProgress)}%;"></div>
                                        </div>
                                    </div>
                                    <table class="table table-borderless table-sm mb-0">
                                        <tr>
                                            <td class="text-muted" style="width: 40%;">${translations.items}:</td>
                                            <td class="fw-semibold">${projectItemsCount}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">${translations.subprojects}:</td>
                                            <td class="fw-semibold">${projectSubprojectsCount}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">${translations.employees}:</td>
                                            <td class="fw-semibold">${projectEmployeesCount}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                if (projectDescription) {
                    contentHtml += `
                        <div class="mt-4">
                            <h6 class="text-primary mb-2">
                                <i class="fas fa-align-left me-2"></i>${translations.description}
                            </h6>
                            <p class="text-muted mb-0 bg-light p-3 rounded">${projectDescription}</p>
                        </div>
                    `;
                }

                document.getElementById('projectDetailsContent').innerHTML = contentHtml;

                const actionButtonsHtml = `
                    <a href="/projects/${projectId}/dashboard" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-eye me-1"></i>${translations.view}
                    </a>
                    <a href="/projects/${projectId}/gantt" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-chart-bar me-1"></i>${translations.ganttChart}
                    </a>
                    <a href="/projects/${projectId}/progress" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-tasks me-1"></i>${translations.progressReport}
                    </a>
                `;
                document.getElementById('modalActionButtons').innerHTML = actionButtonsHtml;

                const modal = new bootstrap.Modal(document.getElementById('projectDetailsModal'));
                modal.show();
            }

            // Subprojects click
            document.querySelectorAll('.view-subprojects-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const projectId = this.dataset.projectId;
                    const subprojectsModal = new bootstrap.Modal(document.getElementById('subprojectsModal'));
                    subprojectsModal.show();

                    fetch(`/projects/${projectId}/subprojects`)
                        .then(response => response.json())
                        .then(data => {
                            let html = '<div class="list-group">';
                            if (data.subprojects && data.subprojects.length > 0) {
                                data.subprojects.forEach(sub => {
                                    html += `
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><i class="fas fa-folder me-2 text-primary"></i>${sub.name}</span>
                                            <span class="badge bg-primary rounded-pill">${sub.items_count || 0} ${translations.items}</span>
                                        </div>
                                    `;
                                });
                            } else {
                                html += '<div class="text-center text-muted py-3">لا توجد مشاريع فرعية</div>';
                            }
                            html += '</div>';
                            document.getElementById('subprojectsContent').innerHTML = html;
                        })
                        .catch(() => {
                            document.getElementById('subprojectsContent').innerHTML = '<div class="alert alert-danger">حدث خطأ أثناء تحميل البيانات</div>';
                        });
                });
            });

            // Filter logic (hybrid: 0ms DOM filter for <=1 page, SQL server filter for 10,000+ projects)
            const filterForm = document.getElementById('archivedFilterForm');
            const searchInput = document.getElementById('projectSearch');
            const statusFilter = document.getElementById('projectStatusFilter');
            const typeFilter = document.getElementById('projectTypeFilter');
            const clientFilter = document.getElementById('projectClientFilter');
            const clearBtn = document.getElementById('clearFilters');
            const resultsSpan = document.getElementById('filterResults');
            const useServerFilter = filterForm && filterForm.dataset.serverFilter === '1';

            function submitArchivedServerForm() {
                if (!filterForm) return;
                const params = new URLSearchParams();
                if (searchInput && searchInput.value.trim()) params.set('search', searchInput.value.trim());
                if (statusFilter && statusFilter.value !== 'all') params.set('status', statusFilter.value);
                if (typeFilter && typeFilter.value !== 'all') params.set('type', typeFilter.value);
                if (clientFilter && clientFilter.value !== 'all') params.set('client', clientFilter.value);
                const qs = params.toString();
                window.location.href = qs ? `${filterForm.action}?${qs}` : filterForm.action;
            }

            if (filterForm) {
                filterForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    submitArchivedServerForm();
                });
            }

            if (useServerFilter) {
                let debounceTimer;
                if (searchInput) {
                    searchInput.addEventListener('input', function() {
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(submitArchivedServerForm, 350);
                    });
                }
                if (statusFilter) statusFilter.addEventListener('change', submitArchivedServerForm);
                if (typeFilter) typeFilter.addEventListener('change', submitArchivedServerForm);
                if (clientFilter) clientFilter.addEventListener('change', submitArchivedServerForm);
                if (clearBtn) {
                    clearBtn.addEventListener('click', function() {
                        window.location.href = filterForm.action;
                    });
                }
            } else {
                function applyFilters() {
                    const searchVal = (searchInput ? searchInput.value : '').toLowerCase().trim();
                    const statusVal = (statusFilter ? statusFilter.value : 'all');
                    const typeVal = (typeFilter ? typeFilter.value : 'all');
                    const clientVal = (clientFilter ? clientFilter.value : 'all');

                    let visibleCount = 0;
                    document.querySelectorAll('#projectsGrid .col-lg-4').forEach(col => {
                        const card = col.querySelector('.project-card');
                        if (!card) return;

                        const name = (card.dataset.projectName || '').toLowerCase();
                        const client = (card.dataset.projectClient || '').toLowerCase();
                        const status = (card.dataset.projectStatus || '');
                        const type = (card.dataset.projectType || '');

                        const matchSearch = !searchVal || name.includes(searchVal) || client.includes(searchVal);
                        const matchStatus = statusVal === 'all' || status === statusVal;
                        const matchType = typeVal === 'all' || type === typeVal;
                        const matchClient = clientVal === 'all' || card.dataset.projectClient === clientVal;

                        if (matchSearch && matchStatus && matchType && matchClient) {
                            col.style.display = '';
                            visibleCount++;
                        } else {
                            col.style.display = 'none';
                        }
                    });

                    if (resultsSpan) {
                        resultsSpan.textContent = visibleCount;
                    }
                }

                if (searchInput) searchInput.addEventListener('input', applyFilters);
                if (statusFilter) statusFilter.addEventListener('change', applyFilters);
                if (typeFilter) typeFilter.addEventListener('change', applyFilters);
                if (clientFilter) clientFilter.addEventListener('change', applyFilters);

                if (clearBtn) {
                    clearBtn.addEventListener('click', function() {
                        if (searchInput) searchInput.value = '';
                        if (statusFilter) statusFilter.value = 'all';
                        if (typeFilter) typeFilter.value = 'all';
                        if (clientFilter) clientFilter.value = 'all';
                        applyFilters();
                    });
                }
            }
        });
    </script>
    @endpush
@endsection
