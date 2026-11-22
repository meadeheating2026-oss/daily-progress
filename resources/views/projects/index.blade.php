@extends('layouts.app')
@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('dashboard') }}" class="text-muted text-decoration-none">
            {{ __('general.dashboard') }}
        </a>
    </li>
@endsection
@section('title', __('projects.list'))

@section('content')
    <div class="m-2 d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <h5 class="mb-0">{{ __('projects.list') }}</h5>

            
            @can('projects-list')
                <a href="{{ route('projects.drafts') }}" class="btn btn-sm btn-outline-warning">
                    <i class="fas fa-file-alt me-1"></i>
                    {{ __('general.drafts') }}
                    @if (isset($draftsCount) && $draftsCount > 0)
                        <span class="badge bg-warning text-dark ms-1">{{ $draftsCount }}</span>
                    @endif
                </a>
                <a href="{{ route('projects.archived') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-box-archive me-1"></i>
                    {{ __('general.archive') }}
                    @if (isset($archivedCount) && $archivedCount > 0)
                        <span class="badge bg-secondary ms-1">{{ $archivedCount }}</span>
                    @endif
                </a>
            @endcan
        </div>
        @can('projects-create')
            <a href="{{ route('projects.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> {{ __('projects.new') }}
            </a>
        @endcan
    </div>

    
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 20px;">
        <div class="card-body p-4">
            <div class="row g-3 align-items-end">
                
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
                               class="form-control border-0 bg-light" 
                               placeholder="{{ __('general.search_by_name_or_client') }}"
                               style="border-radius: 0 12px 12px 0;">
                    </div>
                </div>

                
                <div class="col-lg-2 col-md-6 col-sm-12">
                    <label class="form-label text-muted small mb-2">
                        <i class="fas fa-filter me-2"></i>{{ __('general.status') }}
                    </label>
                    <select id="projectStatusFilter" class="form-select border-0 bg-light" style="border-radius: 12px;">
                        <option value="all">{{ __('general.all_statuses') }}</option>
                        <option value="active">{{ __('general.active') }}</option>
                        <option value="completed">{{ __('general.completed') }}</option>
                        <option value="pending">{{ __('general.pending') }}</option>
                    </select>
                </div>

                
                <div class="col-lg-3 col-md-6 col-sm-12">
                    <label class="form-label text-muted small mb-2">
                        <i class="fas fa-tag me-2"></i>{{ __('general.type_of_project') }}
                    </label>
                    <select id="projectTypeFilter" class="form-select border-0 bg-light" style="border-radius: 12px;">
                        <option value="all">{{ __('general.all_types') }}</option>
                        @php
                            $uniqueTypes = $projects->pluck('type')->filter()->unique('id');
                        @endphp
                        @foreach($uniqueTypes as $type)
                            @if($type)
                                <option value="{{ $type->name }}">{{ $type->name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                
                <div class="col-lg-2 col-md-6 col-sm-12">
                    <label class="form-label text-muted small mb-2">
                        <i class="fas fa-building me-2"></i>{{ __('projects.client') }}
                    </label>
                    <select id="projectClientFilter" class="form-select border-0 bg-light" style="border-radius: 12px;">
                        <option value="all">{{ __('general.all_clients') }}</option>
                        @php
                            $uniqueClients = $projects->pluck('client')->filter()->unique('id');
                        @endphp
                        @foreach($uniqueClients as $client)
                            @if($client)
                                <option value="{{ $client->name }}">{{ $client->name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                
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
                            <span id="filterResults">{{ $projects->count() }}</span> 
                            <span>{{ __('general.results') }}</span>
                        </small>
                    </div>
                </div>
            </div>
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
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }
        .project-card .status-strip {
            height: 3px;
            width: 100%;
            background: var(--status);
        }
        .project-card .card-body {
            padding: 0.9rem 1rem 0.75rem;
        }
        .project-card .project-title {
            font-size: 0.95rem;
            font-weight: 650;
            color: #0f172a;
            letter-spacing: -0.01em;
            line-height: 1.3;
            margin: 0;
        }
        .project-card .project-client {
            font-size: 0.75rem;
            color: #64748b;
        }
        .project-card .status-badge {
            font-size: 0.68rem;
            font-weight: 600;
            padding: 0.28rem 0.55rem;
            border-radius: 999px;
            background: var(--status-soft);
            color: var(--status);
        }
        .project-card .progress-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 0.85rem 0 0.7rem;
        }
        .project-card .project-progress-text {
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
            min-width: 2.6rem;
            letter-spacing: -0.02em;
            line-height: 1;
        }
        .project-card .progress {
            flex: 1;
            height: 7px;
            border-radius: 999px;
            background: #f1f5f9;
            overflow: hidden;
        }
        .project-card .progress-bar {
            border-radius: 999px;
            background: var(--status) !important;
        }
        .project-card .card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            margin-bottom: 0.65rem;
        }
        .project-card .meta-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.7rem;
            color: #475569;
            background: #f8fafc;
            border: 1px solid #eef2f7;
            border-radius: 8px;
            padding: 0.22rem 0.5rem;
            line-height: 1.2;
        }
        .project-card .meta-chip button {
            all: unset;
            cursor: pointer;
            color: var(--primary);
            font-weight: 600;
        }
        .project-card .meta-chip button:hover {
            text-decoration: underline;
        }
        .project-card .date-row {
            display: flex;
            justify-content: space-between;
            gap: 0.5rem;
            font-size: 0.7rem;
            color: #94a3b8;
        }
        .project-card .card-footer {
            background: #fafbfc;
            border-top: 1px solid #f1f5f9;
            padding: 0.55rem 0.85rem;
        }
        .project-card .btn-details {
            font-size: 0.72rem;
            font-weight: 600;
            padding: 0.28rem 0.7rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #334155;
        }
        .project-card .btn-details:hover {
            border-color: rgba(var(--primary-rgb), 0.35);
            color: var(--primary);
            background: rgba(var(--primary-rgb), 0.04);
        }
        .project-card .action-btn {
            width: 30px;
            height: 30px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid transparent;
            color: #64748b;
            background: transparent;
            font-size: 0.78rem;
            text-decoration: none;
            transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
        }
        .project-card .action-btn:hover {
            background: #fff;
            border-color: #e2e8f0;
            color: var(--primary);
        }
        .dark-mode .project-card {
            background: #1e293b;
            border-color: rgba(255, 255, 255, 0.08);
            box-shadow: none;
        }
        .dark-mode .project-card .project-title,
        .dark-mode .project-card .project-progress-text { color: #f1f5f9; }
        .dark-mode .project-card .project-client,
        .dark-mode .project-card .date-row { color: #94a3b8; }
        .dark-mode .project-card .meta-chip {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.08);
            color: #cbd5e1;
        }
        .dark-mode .project-card .progress { background: rgba(255, 255, 255, 0.08); }
        .dark-mode .project-card .card-footer {
            background: rgba(0, 0, 0, 0.15);
            border-top-color: rgba(255, 255, 255, 0.06);
        }
        .dark-mode .project-card .btn-details {
            background: transparent;
            border-color: rgba(255, 255, 255, 0.12);
            color: #e2e8f0;
        }
        .dark-mode .project-card .action-btn:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.1);
            color: #fff;
        }
    </style>

    <div class="row g-3 m-2 projects-grid">
        @foreach ($projects as $project)
            @php
                $subprojectNamesWithItems = $project->items
                    ->pluck('subproject_name')
                    ->filter()
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
                            <span class="status-badge flex-shrink-0">{{ $statusLabel }}</span>
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
                            <span>
                                <i class="far fa-calendar-check me-1"></i>
                                {{ $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('d-m-Y') : __('general.in_progress') }}
                            </span>
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
                                    title="Gantt Chart"
                                    onclick="event.stopPropagation();">
                                    <i class="fas fa-chart-bar"></i>
                                </a>
                                {{-- Progress --}}
                                <a href="{{ route('projects.progress', $project->id) }}"
                                    class="action-btn"
                                    title="Progress"
                                    onclick="event.stopPropagation();">
                                    <i class="fas fa-tasks"></i>
                                </a>
                            @endcan
                            @can('projects-edit')
                                <a href="{{ route('projects.edit', $project) }}"
                                    class="action-btn"
                                    title="{{ __('general.edit') }}"
                                    onclick="event.stopPropagation();">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @endcan
                            <button type="button"
                                class="action-btn copy-project-btn"
                                title="{{ __('general.copy_project') }}"
                                data-project-id="{{ $project->id }}"
                                data-project-name="{{ $project->name }}"
                                onclick="event.stopPropagation();">
                                <i class="fas fa-copy"></i>
                            </button>
                            @can('projects-edit')
                                {{-- Archive Project --}}
                                <form action="{{ route('projects.archive', $project) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('{{ __('general.confirm_archive_project') }}')"
                                      onclick="event.stopPropagation();">
                                    @csrf
                                    <button type="submit"
                                        class="action-btn"
                                        style="color:#64748b;"
                                        title="{{ __('general.archive_project') }}">
                                        <i class="fas fa-box-archive"></i>
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
        @endforeach
    </div>

    
    <div class="modal fade" id="saveAsTemplateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('general.save_as_template') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('general.template_name') }}</label>
                        <input type="text" class="form-control" id="modal_template_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('general.description') }}</label>
                        <textarea class="form-control" id="modal_template_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('general.cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="saveTemplateBtn">{{ __('general.save_template') }}</button>
                </div>
            </div>
        </div>
    </div>

    
    <div class="modal fade" id="copyProjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('general.copy_project') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('general.project_name') }}</label>
                        <input type="text" class="form-control" id="copy_project_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('general.cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="copyProjectBtn">{{ __('general.copy_project') }}</button>
                </div>
            </div>
        </div>
    </div>

    
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
                    <div id="modalActionButtons" class="d-flex gap-2">
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

    
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let currentProjectId = null;
            
            // Store translations in variables
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
                ganttChart: '{{ __('general.gantt_chart') }}'
            };
            
            // Handle project card clicks and view details button
            document.querySelectorAll('.project-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    // Don't trigger if clicking on action links/forms (except view-details)
                    if (e.target.closest('a, form, button:not(.view-details-btn)')) {
                        return;
                    }

                    const projectId = card.getAttribute('data-project-id');
                    if (!projectId) return;

                    // Get all project data from data attributes
                    const projectData = {
                        id: projectId,
                        name: card.getAttribute('data-project-name'),
                        description: card.getAttribute('data-project-description'),
                        client: card.getAttribute('data-project-client'),
                        status: card.getAttribute('data-project-status'),
                        type: card.getAttribute('data-project-type'),
                        startDate: card.getAttribute('data-project-start-date'),
                        endDate: card.getAttribute('data-project-end-date'),
                        workingZone: card.getAttribute('data-project-working-zone'),
                        workingDays: card.getAttribute('data-project-working-days'),
                        dailyHours: card.getAttribute('data-project-daily-hours'),
                        weeklyHolidays: card.getAttribute('data-project-weekly-holidays'),
                        itemsCount: card.getAttribute('data-project-items-count'),
                        subprojectsCount: card.getAttribute('data-project-subprojects-count'),
                        employeesCount: card.getAttribute('data-project-employees-count'),
                        progress: card.getAttribute('data-project-progress'),
                        created: card.getAttribute('data-project-created')
                    };

                    fillProjectDetailsModal(projectData, translations);

                    const modalEl = document.getElementById('projectDetailsModal');
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                });
            });

            // Prevent leftover backdrops / body lock after closing
            ['projectDetailsModal', 'subprojectsModal', 'saveAsTemplateModal', 'copyProjectModal'].forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;
                el.addEventListener('hidden.bs.modal', function() {
                    if (document.querySelectorAll('.modal.show').length === 0) {
                        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                        document.body.classList.remove('modal-open');
                        document.body.style.removeProperty('overflow');
                        document.body.style.removeProperty('padding-right');
                    }
                });
            });
            
            // Function to fill project details modal
            function fillProjectDetailsModal(data, t) {
                // Update modal title
                document.getElementById('modal_project_name').innerHTML = 
                    '<i class="fas fa-project-diagram me-2"></i>' + data.name;
                
                // Get status badge HTML
                let statusBadge = '';
                switch(data.status) {
                    case 'active':
                        statusBadge = '<span class="badge bg-success px-3 py-2"><i class="fas fa-check-circle me-1"></i>' + t.active + '</span>';
                        break;
                    case 'completed':
                        statusBadge = '<span class="badge bg-secondary px-3 py-2"><i class="fas fa-check-double me-1"></i>' + t.completed + '</span>';
                        break;
                    case 'pending':
                        statusBadge = '<span class="badge bg-warning text-dark px-3 py-2"><i class="fas fa-clock me-1"></i>' + t.pending + '</span>';
                        break;
                }
                
                // Calculate progress color
                const progress = parseFloat(data.progress) || 0;
                let progressColor = 'bg-danger';
                if (progress >= 75) progressColor = 'bg-success';
                else if (progress >= 50) progressColor = 'bg-info';
                else if (progress >= 25) progressColor = 'bg-warning';
                
                // Build modal content
                const content = `
                    <div class="row g-4">
                        
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="mb-2">${data.name}</h5>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-building me-2"></i>${data.client}
                                    </p>
                                </div>
                                ${statusBadge}
                            </div>
                        </div>
                        
                        
                        <div class="col-12">
                            <div class="card border-0 bg-light">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">
                                            <i class="fas fa-chart-line me-2 text-primary"></i>
                                            ${t.progress}
                                        </h6>
                                        <h4 class="mb-0 fw-bold text-primary">${progress}%</h4>
                                    </div>
                                    <div class="progress" style="height: 20px; border-radius: 10px;">
                                        <div class="progress-bar ${progressColor}" 
                                             role="progressbar" 
                                             style="width: ${progress}%" 
                                             aria-valuenow="${progress}" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        
                        <div class="col-md-6">
                            <div class="card border-0 h-100">
                                <div class="card-header bg-white border-bottom">
                                    <h6 class="mb-0">
                                        <i class="fas fa-info-circle me-2 text-primary"></i>
                                        ${t.basicInfo}
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <small class="text-muted d-block mb-1">
                                            <i class="fas fa-tag me-2"></i>${t.typeOfProject}
                                        </small>
                                        <div class="fw-semibold">${data.type || t.notSpecified}</div>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted d-block mb-1">
                                            <i class="fas fa-calendar-alt me-2"></i>${t.startDate}
                                        </small>
                                        <div class="fw-semibold">${data.startDate || t.notSpecified}</div>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted d-block mb-1">
                                            <i class="fas fa-calendar-check me-2"></i>${t.endDate}
                                        </small>
                                        <div class="fw-semibold">${data.endDate || t.inProgress}</div>
                                    </div>
                                    ${data.workingZone ? `
                                    <div class="mb-3">
                                        <small class="text-muted d-block mb-1">
                                            <i class="fas fa-map-marker-alt me-2"></i>${t.workingZone}
                                        </small>
                                        <div class="fw-semibold">${data.workingZone}</div>
                                    </div>
                                    ` : ''}
                                    ${data.created ? `
                                    <div class="mb-3">
                                        <small class="text-muted d-block mb-1">
                                            <i class="fas fa-calendar-plus me-2"></i>${t.createdAt}
                                        </small>
                                        <div class="fw-semibold">${data.created}</div>
                                    </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                        
                        
                        <div class="col-md-6">
                            <div class="card border-0 h-100">
                                <div class="card-header bg-white border-bottom">
                                    <h6 class="mb-0">
                                        <i class="fas fa-chart-bar me-2 text-primary"></i>
                                        ${t.statistics}
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <div class="text-center p-3 bg-light rounded">
                                                <i class="fas fa-list-ul fa-2x text-primary mb-2"></i>
                                                <h4 class="mb-0 fw-bold">${data.itemsCount || 0}</h4>
                                                <small class="text-muted">${t.items}</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-center p-3 bg-light rounded">
                                                <i class="fas fa-sitemap fa-2x text-success mb-2"></i>
                                                <h4 class="mb-0 fw-bold">${data.subprojectsCount || 0}</h4>
                                                <small class="text-muted">${t.subprojects}</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-center p-3 bg-light rounded">
                                                <i class="fas fa-users fa-2x text-info mb-2"></i>
                                                <h4 class="mb-0 fw-bold">${data.employeesCount || 0}</h4>
                                                <small class="text-muted">${t.employees}</small>
                                            </div>
                                        </div>
                                        ${data.workingDays ? `
                                        <div class="col-6">
                                            <div class="text-center p-3 bg-light rounded">
                                                <i class="fas fa-calendar-week fa-2x text-warning mb-2"></i>
                                                <h4 class="mb-0 fw-bold">${data.workingDays}</h4>
                                                <small class="text-muted">${t.workingDays}</small>
                                            </div>
                                        </div>
                                        ` : ''}
                                    </div>
                                    ${data.dailyHours ? `
                                    <div class="mt-3 p-3 bg-light rounded">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted">
                                                <i class="fas fa-clock me-2"></i>${t.dailyWorkHours}
                                            </span>
                                            <span class="fw-bold">${data.dailyHours} ${t.hours}</span>
                                        </div>
                                    </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                        
                        
                        ${data.description ? `
                        <div class="col-12">
                            <div class="card border-0">
                                <div class="card-header bg-white border-bottom">
                                    <h6 class="mb-0">
                                        <i class="fas fa-align-left me-2 text-primary"></i>
                                        ${t.description}
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-0">${data.description}</p>
                                </div>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                `;
                
                // Update modal content
                document.getElementById('projectDetailsContent').innerHTML = content;
                
                // Update action buttons
                const actionButtons = document.getElementById('modalActionButtons');
                let buttonsHtml = '';
                
                // Check permissions and add buttons
                @php
                    $canView = auth()->user()->can('projects-view');
                    $canEdit = auth()->user()->can('projects-edit');
                    $canProgress = auth()->user()->can('projects-progress');
                    $canGantt = auth()->user()->can('projects-gantt');
                @endphp
                
                @if($canView)
                    buttonsHtml += `<a href="/projects/${data.id}/dashboard" class="btn btn-primary"><i class="fas fa-eye me-1"></i>${t.view}</a>`;
                @endif
                @if($canEdit)
                    buttonsHtml += `<a href="/projects/${data.id}/edit" class="btn btn-success"><i class="fas fa-edit me-1"></i>${t.edit}</a>`;
                @endif
                @if($canProgress)
                    buttonsHtml += `<a href="/projects/${data.id}/progress" class="btn btn-info"><i class="fas fa-chart-bar me-1"></i>${t.progressReport}</a>`;
                @endif
                @if($canGantt)
                    buttonsHtml += `<a href="/projects/${data.id}/gantt" class="btn btn-outline-primary"><i class="fas fa-chart-gantt me-1"></i>${t.ganttChart}</a>`;
                @endif
                
                actionButtons.innerHTML = buttonsHtml;
            }
            
            // Handle save as template button clicks
            document.querySelectorAll('.save-as-template-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    currentProjectId = this.getAttribute('data-project-id');
                    const projectName = this.getAttribute('data-project-name');
                    
                    // Pre-fill template name with project name
                    document.getElementById('modal_template_name').value = projectName + ' - Template';
                    
                    // Show modal
                    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('saveAsTemplateModal'));
                    modal.show();
                });
            });
            
            // Handle copy project button clicks
            document.querySelectorAll('.copy-project-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    currentProjectId = this.getAttribute('data-project-id');
                    const projectName = this.getAttribute('data-project-name');
                    
                    // Pre-fill project name
                    document.getElementById('copy_project_name').value = projectName + ' - Copy';
                    
                    // Show modal
                    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('copyProjectModal'));
                    modal.show();
                });
            });
            
            // Handle save template
            document.getElementById('saveTemplateBtn').addEventListener('click', function() {
                const templateName = document.getElementById('modal_template_name').value;
                const templateDescription = document.getElementById('modal_template_description').value;
                
                if (!templateName.trim()) {
                    alert('{{ __('general.template_name_required') }}');
                    return;
                }
                
                if (!currentProjectId) {
                    alert('{{ __('general.error_occurred') }}');
                    return;
                }
                
                // Create form data
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('template_name', templateName);
                formData.append('template_description', templateDescription);
                
                // Show loading
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>{{ __('general.saving') }}...';
                this.disabled = true;
                
                // Send request
                fetch(`/projects/${currentProjectId}/save-as-template`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    alert('{{ __('general.template_created_successfully') }}');
                    
                    // Reset form
                    document.getElementById('modal_template_name').value = '';
                    document.getElementById('modal_template_description').value = '';
                    
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('saveAsTemplateModal'));
                    modal.hide();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('{{ __('general.error_occurred') }}');
                })
                .finally(() => {
                    // Reset button
                    this.innerHTML = '{{ __('general.save_template') }}';
                    this.disabled = false;
                });
            });
            
            // Handle copy project
            document.getElementById('copyProjectBtn').addEventListener('click', function() {
                const projectName = document.getElementById('copy_project_name').value;
                
                if (!projectName.trim()) {
                    alert('{{ __('general.project_name_required') }}');
                    return;
                }
                
                if (!currentProjectId) {
                    alert('{{ __('general.error_occurred') }}');
                    return;
                }
                
                // Show loading
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>{{ __('general.copying') }}...';
                this.disabled = true;
                
                // Send request
                fetch(`/projects/${currentProjectId}/copy`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        name: projectName
                    })
                })
                .then(response => {
                    // Check if response is JSON
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json().then(data => ({ json: true, data }));
                    } else {
                        // If redirect, follow it
                        return { json: false, redirect: response.url || response.redirected };
                    }
                })
                .then(result => {
                    if (result.json) {
                        const data = result.data;
                        if (data.success) {
                            // Reset form
                            document.getElementById('copy_project_name').value = '';
                            
                            // Close modal
                            const modal = bootstrap.Modal.getInstance(document.getElementById('copyProjectModal'));
                            modal.hide();
                            
                            // Redirect to edit page or reload
                            if (data.redirect_url) {
                                window.location.href = data.redirect_url;
                            } else {
                                window.location.reload();
                            }
                        } else {
                            alert(data.message || '{{ __('general.error_occurred') }}');
                            // Reset button
                            this.innerHTML = '{{ __('general.copy_project') }}';
                            this.disabled = false;
                        }
                    } else {
                        // Handle redirect response
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('{{ __('general.error_occurred') }}');
                    // Reset button
                    this.innerHTML = '{{ __('general.copy_project') }}';
                    this.disabled = false;
                });
            });
        });
        
        // Handle subprojects button clicks
        document.querySelectorAll('.view-subprojects-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const projectId = this.getAttribute('data-project-id');
                if (!projectId) return;
                
                currentProjectId = projectId;
                loadSubprojects(projectId);
                
                const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('subprojectsModal'));
                modal.show();
            });
        });
        
        // Load subprojects data
        function loadSubprojects(projectId) {
            const content = document.getElementById('subprojectsContent');
            content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">{{ __('general.loading') }}...</span></div></div>';
            
            fetch(`/api/projects/${projectId}/subprojects`, {
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
                console.log('Subprojects data received:', data);
                
                // Handle both formats: direct array or object with success property
                let subprojectsArray = null;
                
                if (Array.isArray(data)) {
                    // Data is directly an array
                    subprojectsArray = data;
                } else if (data.success && Array.isArray(data.subprojects)) {
                    // Data is an object with success and subprojects
                    subprojectsArray = data.subprojects;
                } else if (Array.isArray(data.subprojects)) {
                    // Data is an object with subprojects array
                    subprojectsArray = data.subprojects;
                }
                
                if (subprojectsArray && subprojectsArray.length > 0) {
                    console.log('Rendering', subprojectsArray.length, 'subprojects');
                    renderSubprojects(subprojectsArray);
                } else {
                    console.warn('No subprojects found or invalid data:', data);
                    content.innerHTML = '<div class="alert alert-warning text-center">{{ __('general.no_subprojects_found') }}</div>';
                }
            })
            .catch(error => {
                console.error('Error loading subprojects:', error);
                console.error('Error details:', error.message);
                content.innerHTML = '<div class="alert alert-danger text-center">{{ __('general.error_loading_subprojects') }}<br><small>' + error.message + '</small></div>';
            });
        }
        
        // Render subprojects - دالة منفصلة لعرض subprojects في صفحة /projects
        function renderSubprojects(subprojects) {
            console.log('renderSubprojects called with:', subprojects);
            
            const content = document.getElementById('subprojectsContent');
            
            // Validate input
            if (!content) {
                console.error('subprojectsContent element not found!');
                return;
            }
            
            // Check if subprojects is valid array
            if (!Array.isArray(subprojects)) {
                console.error('subprojects is not an array:', subprojects);
                content.innerHTML = '<div class="alert alert-danger text-center" style="border-radius: 15px;">{{ __('general.error_loading_subprojects') }}</div>';
                return;
            }
            
            if (subprojects.length === 0) {
                console.log('No subprojects to render');
                content.innerHTML = '<div class="alert alert-info text-center" style="border-radius: 15px;">{{ __('general.no_subprojects_found') }}</div>';
                return;
            }
            
            console.log('Rendering', subprojects.length, 'subprojects');
            
            // Calculate total weighted progress
            const totalWeightedProgress = subprojects.reduce((sum, sp) => sum + (sp.weighted_progress || 0), 0);
            const totalProgress = subprojects.reduce((sum, sp) => sum + (sp.progress || 0), 0);
            
            // Calculate total weighted progress excluding items > 100%
            const totalWeightedProgressUnder100 = subprojects.reduce((sum, sp) => sum + (sp.weighted_progress_under_100 || 0), 0);
            const totalProgressUnder100 = subprojects.reduce((sum, sp) => sum + (sp.progress_under_100 || 0), 0);
            
            let html = `
                <div class="row g-4 mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm" style="border-radius: 15px; background: linear-gradient(135deg, var(--primary) 0%, var(--purple) 100%);">
                            <div class="card-body text-white p-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-1"><i class="fas fa-chart-line me-2"></i>{{ __('general.total_weighted_progress') }}</h5>
                                        <p class="mb-0 text-white-50">{{ __('general.total_weighted_progress_description') }}</p>
                                    </div>
                                    <div class="text-end d-flex align-items-center gap-3">
                                        <div>
                                            <div class="mb-2">
                                                <b class="mb-0 fw-bold" >${totalProgress.toFixed(2)}%</b>/
                                                <b class="mb-0 fw-bold" style="">${totalWeightedProgress.toFixed(2)}%</b>
                                            </div>
                                            <div class="border-top pt-2" style="border-color: rgba(255,255,255,0.3) !important;">
                                                <small class="text-white-50 d-block mb-1">( :: {{ __('general.scalable_ratio_not_included_above_100') }} ::)</small>
                                                <b class="mb-0 fw-bold">${totalProgressUnder100.toFixed(2)}%</b>/
                                                <b class="mb-0 fw-bold" style="font-size: 2.5rem;">${totalWeightedProgressUnder100.toFixed(2)}%</b>
                                            </div>
                                        </div>
                                        <button type="button" 
                                                class="btn btn-light btn-sm update-all-weights-btn"
                                                style="border-radius: 10px; font-weight: 600; white-space: nowrap;"
                                                onclick="updateAllSubprojectsWeights()">
                                            <i class="fas fa-save me-1"></i>{{ __('general.update_all_weights') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row g-3">
            `;
            
            subprojects.forEach(subproject => {
                const progress = subproject.progress || 0;
                const progressUnder100 = subproject.progress_under_100 || 0;
                const weight = subproject.weight || 0;
                const weightedProgress = subproject.weighted_progress || 0;
                const weightedProgressUnder100 = subproject.weighted_progress_under_100 || 0;
                
                let progressColor = 'bg-danger';
                if (progress >= 75) progressColor = 'bg-success';
                else if (progress >= 50) progressColor = 'bg-info';
                else if (progress >= 25) progressColor = 'bg-warning';
                
                let progressUnder100Color = 'bg-danger';
                if (progressUnder100 >= 75) progressUnder100Color = 'bg-success';
                else if (progressUnder100 >= 50) progressUnder100Color = 'bg-info';
                else if (progressUnder100 >= 25) progressUnder100Color = 'bg-warning';
                
                html += `
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100" style="border-radius: 15px;">
                            <div class="card-header bg-white border-0 pb-2" style="border-radius: 15px 15px 0 0;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold">
                                        <i class="fas fa-sitemap me-2 text-primary"></i>
                                        ${subproject.name}
                                    </h6>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small">
                                            <i class="fas fa-tasks me-1"></i>{{ __(' progress') }}
                                        </span>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="text-muted small">${progress.toFixed(2)}%</span>
                                            ${progressUnder100 !== progress ? `
                                                <span class="fw-bold ">(${progressUnder100.toFixed(2)}%)</span>
                                            ` : ''}
                                        </div>
                                    </div>
                                
                                  
                                </div>
                                
                                
                                <div class="row g-3 mb-3">
                                    <div class="col-6">
                                        <div class="p-3 bg-light rounded-3">
                                            <div class="d-flex align-items-center text-muted mb-1">
                                                <i class="fas fa-weight me-2"></i>
                                                <small class="fw-semibold">{{ __('general.weight') }}</small>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <input type="number" 
                                                       class="form-control form-control-sm weight-input" 
                                                       data-subproject-id="${subproject.id}"
                                                       value="${weight}"
                                                       min="0"
                                                       max="100"
                                                       step="0.1"
                                                       style="border-radius: 8px; border: 2px solidrgb(64, 160, 255);"
                                                       onchange="this.style.borderColor='#28a745'">
                                                <span class="text-muted small">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-3 bg-light rounded-3">
                                            <div class="d-flex align-items-center text-muted mb-1">
                                                <i class="fas fa-chart-bar me-2"></i>
                                                <small class="fw-semibold">{{ __('general.weighted_progress') }}</small>
                                            </div>
                                        
                                        </div>
                                    </div>
                                </div>
                                
                                
                                <div class="row g-2">
                                    <div class="col-6">
                                        <small class="text-muted d-block mb-1">
                                            <i class="fas fa-list-ul me-1"></i>{{ __('general.total') }}
                                        </small>
                                        <div class="fw-semibold">${subproject.total_quantity || 0}</div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block mb-1">
                                            <i class="fas fa-check-circle me-1"></i>{{ __('general.completed') }}
                                        </small>
                                        <div class="fw-semibold">${subproject.completed_quantity || 0}</div>
                                    </div>
                                </div>
                                
                                ${subproject.items && subproject.items.length > 0 ? `
                                <div class="mt-4 border-top pt-3">
                                    ${(() => {
                                        // Separate measurable and non-measurable items
                                        const measurableItems = subproject.items.filter(item => item.is_measurable ?? false);
                                        const nonMeasurableItems = subproject.items.filter(item => !(item.is_measurable ?? false));
                                        const totalItems = subproject.items.length;
                                        
                                        const accordionId = `itemsAccordion_${subproject.id}`;
                                        
                                        return `
                                            <div class="accordion" id="${accordionId}">
                                                <div class="accordion-item" style="border-radius: 10px; border: 1px solid #e9ecef;">
                                                    <h2 class="accordion-header" id="heading_${accordionId}">
                                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_${accordionId}" aria-expanded="false" aria-controls="collapse_${accordionId}" style="border-radius: 10px;">
                                                            <i class="fas fa-list-ul me-2 text-primary"></i>
                                                            <span class="fw-semibold">{{ __('general.items') }}</span>
                                                            <span class="badge bg-primary ms-2">${totalItems}</span>
                                                            ${measurableItems.length > 0 && nonMeasurableItems.length > 0 ? `
                                                                <span class="badge bg-success ms-2">${measurableItems.length} {{ __('general.measurable') }}</span>
                                                                <span class="badge bg-secondary ms-2">${nonMeasurableItems.length} {{ __('general.not_measurable') }}</span>
                                                            ` : ''}
                                                        </button>
                                                    </h2>
                                                    <div id="collapse_${accordionId}" class="accordion-collapse collapse" aria-labelledby="heading_${accordionId}" data-bs-parent="#${accordionId}">
                                                        <div class="accordion-body p-3">
                                                            ${measurableItems.length > 0 ? `
                                                                <div class="mb-3">
                                                                    ${nonMeasurableItems.length > 0 ? `<h6 class="mb-2 text-success"><i class="fas fa-check-circle me-1"></i>{{ __('general.measurable_items') }}</h6>` : ''}
                                                                    <div class="list-group" style="border-radius: 10px;">
                                                                        ${measurableItems.map(item => {
                                                                            const itemProgress = parseFloat(item.progress || 0);
                                                                            const progressColor = itemProgress >= 75 ? 'success' : 
                                                                                                  itemProgress >= 50 ? 'info' : 
                                                                                                  itemProgress >= 25 ? 'warning' : 'danger';
                                                                            
                                                                            return `
                                                                                <div class="list-group-item" style="border-radius: 8px; margin-bottom: 8px; border: 1px solid #e9ecef;">
                                                                                    <div class="d-flex justify-content-between align-items-start">
                                                                                        <div class="flex-grow-1">
                                                                                            <div class="d-flex align-items-center mb-1">
                                                                                                <span class="fw-semibold">${item.name}</span>
                                                                                            </div>
                                                                                            <div class="small text-muted mb-2">
                                                                                                <span>{{ __('general.completed') }}: ${parseFloat(item.completed_quantity || 0).toFixed(2)} / ${parseFloat(item.total_quantity || 0).toFixed(2)} ${item.unit}</span>
                                                                                            </div>
                                                                                            <div class="progress" style="height: 6px; border-radius: 5px;">
                                                                                                <div class="progress-bar bg-${progressColor}" 
                                                                                                     role="progressbar" 
                                                                                                     style="width: ${itemProgress}%" 
                                                                                                     aria-valuenow="${itemProgress}" 
                                                                                                     aria-valuemin="0" 
                                                                                                     aria-valuemax="100">
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="text-end ms-2">
                                                                                            <span class="badge bg-${progressColor}">${itemProgress.toFixed(1)}%</span>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            `;
                                                                        }).join('')}
                                                                    </div>
                                                                </div>
                                                            ` : ''}
                                                            
                                                            ${nonMeasurableItems.length > 0 ? `
                                                                <div>
                                                                    ${measurableItems.length > 0 ? `<h6 class="mb-2 text-secondary"><i class="fas fa-info-circle me-1"></i>{{ __('general.non_measurable_items') }}</h6>` : ''}
                                                                    <div class="list-group" style="border-radius: 10px;">
                                                                        ${nonMeasurableItems.map(item => {
                                                                            return `
                                                                                <div class="list-group-item bg-light" style="border-radius: 8px; margin-bottom: 8px; border: 1px solid #e9ecef;">
                                                                                    <div class="d-flex justify-content-between align-items-start">
                                                                                        <div class="flex-grow-1">
                                                                                            <div class="d-flex align-items-center mb-1">
                                                                                                <span class="fw-semibold">${item.name}</span>
                                                                                                <span class="badge bg-secondary ms-2" style="">
                                                                                                    <i class="fas fa-info-circle me-1"></i>{{ __('general.not_measurable') }}
                                                                                                </span>
                                                                                            </div>
                                                                                            <div class="small text-muted">
                                                                                                <i class="fas fa-exclamation-triangle me-1 text-warning"></i>
                                                                                                {{ __('general.not_included_in_progress_calculation') }}
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            `;
                                                                        }).join('')}
                                                                    </div>
                                                                </div>
                                                            ` : ''}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        `;
                                    })()}
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            content.innerHTML = html;
        }
        
        // Update subproject weight
        function updateSubprojectWeight(subprojectId) {
            const input = document.querySelector(`.weight-input[data-subproject-id="${subprojectId}"]`);
            if (!input || !currentProjectId) return;
            
            const weight = parseFloat(input.value);
            if (isNaN(weight) || weight < 0) {
                alert('{{ __('general.weight_must_be_positive') }}');
                return;
            }
            
            // Calculate total of all weights
            const allWeightInputs = document.querySelectorAll('.weight-input');
            let totalWeight = 0;
            allWeightInputs.forEach(wInput => {
                const wValue = parseFloat(wInput.value) || 0;
                totalWeight += wValue;
            });
            
            // Check if total equals 100
            if (Math.abs(totalWeight - 100) > 0.01) { // Allow small floating point differences
                alert('{{ __('general.total_weights_must_equal_100') }}: ' + totalWeight.toFixed(2) + '%');
                return;
            }
            
            const btn = document.querySelector(`.update-weight-btn[data-subproject-id="${subprojectId}"]`);
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>{{ __('general.updating') }} <b>...</b>';
            btn.disabled = true;
            
            fetch(`/api/projects/${currentProjectId}/subprojects/${subprojectId}/update-weight`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ weight: weight })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the weighted progress in the UI
                    const card = input.closest('.card');
                    const weightedProgressElement = card.querySelector('.fw-bold.text-primary');
                    if (weightedProgressElement && data.subproject) {
                        weightedProgressElement.textContent = data.subproject.weighted_progress.toFixed(2) + '%';
                    }
                    
                    // Reset input border
                    input.style.borderColor = '#e9ecef';
                    
                    // Show success message
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success alert-dismissible fade show';
                    alert.style.borderRadius = '10px';
                    const successMsg = data.message || '{{ __('general.weight_updated_successfully') }}';
                    alert.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + successMsg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                    card.querySelector('.card-body').insertBefore(alert, card.querySelector('.card-body').firstChild);
                    
                    setTimeout(() => {
                        alert.remove();
                    }, 3000);
                    
                    // Recalculate total weighted progress
                    loadSubprojects(currentProjectId);
                } else {
                    alert(data.message || '{{ __('general.error_occurred') }}');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ __('general.error_occurred') }}');
            })
            .finally(() => {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            });
        }
        
        // Update all subprojects weights
        function updateAllSubprojectsWeights() {
            if (!currentProjectId) {
                alert('{{ __('general.error_occurred') }}');
                return;
            }
            
            // Get all weight inputs
            const allWeightInputs = document.querySelectorAll('.weight-input');
            const weights = {};
            let totalWeight = 0;
            
            // Collect all weights
            allWeightInputs.forEach(input => {
                const subprojectId = input.getAttribute('data-subproject-id');
                const weight = parseFloat(input.value) || 0;
                if (subprojectId) {
                    weights[subprojectId] = weight;
                    totalWeight += weight;
                }
            });
            
            // Validate total equals 100
            if (Math.abs(totalWeight - 100) > 0.01) {
                alert('{{ __('general.total_weights_must_equal_100') }}: ' + totalWeight.toFixed(2) + '%');
                return;
            }
            
            // Get button and show loading
            const btn = document.querySelector('.update-all-weights-btn');
            if (!btn) return;
            
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>{{ __('general.updating') }}...';
            btn.disabled = true;
            
            // Send request
            fetch(`/api/projects/${currentProjectId}/subprojects/update-all-weights`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ weights: weights })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reset all input borders
                    allWeightInputs.forEach(input => {
                        input.style.borderColor = '#e9ecef';
                    });
                    
                    // Show success message
                    const content = document.getElementById('subprojectsContent');
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success alert-dismissible fade show';
                    alert.style.borderRadius = '10px';
                    alert.style.marginBottom = '1rem';
                    const successMsg = data.message || '{{ __('general.all_weights_updated_successfully') }}';
                    alert.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + successMsg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                    content.insertBefore(alert, content.firstChild);
                    
                    setTimeout(() => {
                        alert.remove();
                    }, 3000);
                    
                    // Reload subprojects to update weighted progress
                    loadSubprojects(currentProjectId);
                } else {
                    alert(data.message || '{{ __('general.error_occurred') }}');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ __('general.error_occurred') }}');
            })
            .finally(() => {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            });
        }
        
        // Make functions globally available
        window.updateSubprojectWeight = updateSubprojectWeight;
        window.updateAllSubprojectsWeights = updateAllSubprojectsWeights;
    </script>

    
    <script src="{{ asset('js/projects-filter.js') }}"></script>
@endsection

