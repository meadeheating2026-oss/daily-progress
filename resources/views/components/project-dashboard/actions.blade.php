
<div class="container-fluid pt-4">
    <div class="actions-section">
        <h4 class="gradient-text fw-bold mb-3">
            <i class="fas fa-cogs me-2"></i>{{ __('general.actions') }}
        </h4>
        <div class="d-flex flex-wrap">
            <a href="{{ route('projects.progress', $project->id) }}" class="btn btn-success action-btn text-white">
                <i class="fas fa-tasks me-1"></i>{{ __('general.progress_report') }}
            </a>

            <a href="{{ route('projects.gantt', $project->id) }}" class="btn action-btn text-white" style="background-color: #6366f1; border-color: #6366f1;">
                <i class="fas fa-chart-bar me-1"></i>{{ __('general.gantt_chart') }}
            </a>

            @can('projects-edit')
            <a href="{{ route('projects.edit', $project->id) }}" class="btn btn-warning action-btn">
                <i class="fas fa-edit me-1"></i>{{ __('general.edit_project') }}
            </a>
            @endcan

            @can('projects-delete')
            <button type="button" class="btn btn-danger action-btn" data-bs-toggle="modal" data-bs-target="#deleteProjectModal">
                <i class="fas fa-trash me-1"></i>{{ __('general.delete_project') }}
            </button>
            @endcan
        </div>
    </div>
</div>

