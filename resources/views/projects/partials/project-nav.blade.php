@php
    // IMPORTANT: Never read $project from Blade view scope!
    // Child views with @foreach($projects as $project) leak the last loop item into layout scope.
    // Always resolve strictly from the active Route parameter or explicit ?project_id= query param.
    $navProject = null;
    $singleProjectRoutes = [
        'projects.dashboard',
        'projects.show',
        'projects.progress',
        'projects.gantt',
        'projects.edit',
    ];

    if (request()->routeIs(...$singleProjectRoutes)) {
        $routeParam = request()->route('project');
        if ($routeParam instanceof \App\Models\Project && $routeParam->exists) {
            $routeParam->loadMissing('client:id,name');
            $navProject = $routeParam;
        } elseif (is_numeric($routeParam)) {
            $navProject = \App\Models\Project::with('client:id,name')
                ->select('id', 'name', 'status', 'client_id', 'is_draft', 'is_archived')
                ->find((int) $routeParam);
        }
    } elseif (request()->routeIs('daily-progress.*', 'issues.*') && request()->filled('project_id') && is_numeric(request('project_id'))) {
        $navProject = \App\Models\Project::with('client:id,name')
            ->select('id', 'name', 'status', 'client_id', 'is_draft', 'is_archived')
            ->find((int) request('project_id'));
    }
@endphp

@if($navProject)
    @php
        $statusBadgeClass = match($navProject->status) {
            'active' => 'bg-success',
            'completed' => 'bg-primary',
            'pending' => 'bg-warning',
            default => 'bg-secondary',
        };
        $statusLabelText = match($navProject->status) {
            'active' => __('general.active'),
            'completed' => __('general.completed'),
            'pending' => __('general.pending'),
            default => $navProject->status,
        };
    @endphp
    <div class="kon-project-bar no-print" role="navigation" aria-label="Project quick navigation">
        <div class="kon-project-bar-id">
            <a href="{{ route('projects.index') }}" class="btn btn-sm btn-outline-secondary px-2 py-1" title="{{ __('general.all_projects') }}">
                <i class="fas fa-arrow-right"></i>
            </a>
            <span class="kon-project-bar-icon" aria-hidden="true">
                <i class="fas fa-diagram-project"></i>
            </span>
            <div class="min-w-0">
                <div class="d-flex align-items-center gap-2">
                    <span class="kon-project-bar-title" title="{{ $navProject->name }}">{{ $navProject->name }}</span>
                    <span class="badge {{ $statusBadgeClass }}">{{ $statusLabelText }}</span>
                </div>
                @if($navProject->relationLoaded('client') ? $navProject->client : null)
                    <div class="kon-project-bar-client">
                        <i class="fas fa-building me-1"></i>{{ $navProject->client->name }}
                    </div>
                @endif
            </div>
        </div>

        <div class="kon-project-bar-nav">
            @can('projects-view')
                <a href="{{ route('projects.dashboard', $navProject->id) }}"
                   class="kon-proj-tab {{ request()->routeIs('projects.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-chart-pie"></i>
                    <span>{{ __('general.project_dashboard') }}</span>
                </a>

                <a href="{{ route('projects.show', $navProject->id) }}"
                   class="kon-proj-tab {{ request()->routeIs('projects.show') ? 'active' : '' }}">
                    <i class="fas fa-list-ul"></i>
                    <span>{{ __('general.project_details') }}</span>
                </a>

                <a href="{{ route('projects.progress', $navProject->id) }}"
                   class="kon-proj-tab {{ request()->routeIs('projects.progress') ? 'active' : '' }}">
                    <i class="fas fa-tasks"></i>
                    <span>{{ __('general.progress_report') }}</span>
                </a>

                <a href="{{ route('projects.gantt', $navProject->id) }}"
                   class="kon-proj-tab {{ request()->routeIs('projects.gantt') ? 'active' : '' }}">
                    <i class="fas fa-chart-bar"></i>
                    <span>{{ __('general.gantt_chart') }}</span>
                </a>
            @endcan

            @can('dailyprogress-create')
                <a href="{{ route('daily-progress.create', ['project_id' => $navProject->id]) }}"
                   class="kon-proj-tab {{ request()->routeIs('daily-progress.create') ? 'active' : '' }}">
                    <i class="fas fa-plus-circle"></i>
                    <span>{{ __('general.add_progress') }}</span>
                </a>
            @endcan

            @can('issues-list')
                <a href="{{ route('issues.index', ['project_id' => $navProject->id]) }}"
                   class="kon-proj-tab {{ request()->routeIs('issues.*') ? 'active' : '' }}">
                    <i class="fas fa-triangle-exclamation"></i>
                    <span>{{ __('general.issues') }}</span>
                </a>
            @endcan

            @can('projects-edit')
                <a href="{{ route('projects.edit', $navProject->id) }}"
                   class="kon-proj-tab {{ request()->routeIs('projects.edit') ? 'active' : '' }}">
                    <i class="fas fa-pen-to-square"></i>
                    <span>{{ __('general.edit_project') }}</span>
                </a>
            @endcan

            @can('projects-delete')
                <button type="button"
                        class="kon-proj-tab kon-proj-tab-danger"
                        data-bs-toggle="modal"
                        data-bs-target="#globalDeleteProjectModal"
                        title="{{ __('general.delete_project') }}">
                    <i class="fas fa-trash-alt"></i>
                    <span>{{ __('general.delete_project') }}</span>
                </button>
            @endcan
        </div>
    </div>

    @can('projects-delete')
        <div class="modal fade no-print" id="globalDeleteProjectModal" tabindex="-1" aria-labelledby="globalDeleteProjectModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('projects.destroy', $navProject->id) }}" method="POST" id="globalDeleteProjectForm">
                        @csrf
                        @method('DELETE')
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title" id="globalDeleteProjectModalLabel">
                                <i class="fas fa-exclamation-triangle me-2"></i>{{ __('general.delete_project') }}
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="fw-semibold mb-3">{{ __('general.confirm_delete_project') }}</p>

                            <div class="mb-3">
                                <label class="form-label">
                                    {{ __('general.type_project_name_to_confirm') }}
                                    "<strong id="globalDeleteConfirmProjectName">{{ $navProject->name }}</strong>"
                                </label>
                                <input type="text" class="form-control" id="globalDeleteProjectNameInput" autocomplete="off" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">
                                    {{ __('general.type_random_number_to_confirm') }}
                                    "<strong id="globalDeleteConfirmRandomNumber"></strong>"
                                </label>
                                <input type="text" class="form-control" id="globalDeleteProjectRandomInput" inputmode="numeric" autocomplete="off" required>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('general.cancel') }}</button>
                            <button type="submit" class="btn btn-danger" id="globalConfirmDeleteProjectBtn" disabled>
                                <i class="fas fa-trash me-1"></i>{{ __('general.confirm_delete_action') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('globalDeleteProjectModal');
                if (!modal) return;

                const projectName = @json($navProject->name);
                const nameInput = document.getElementById('globalDeleteProjectNameInput');
                const randomInput = document.getElementById('globalDeleteProjectRandomInput');
                const randomDisplay = document.getElementById('globalDeleteConfirmRandomNumber');
                const confirmBtn = document.getElementById('globalConfirmDeleteProjectBtn');
                const form = document.getElementById('globalDeleteProjectForm');
                let expectedRandom = '';

                function validateConfirm() {
                    const ok = nameInput.value.trim() === projectName && randomInput.value.trim() === expectedRandom;
                    confirmBtn.disabled = !ok;
                }

                modal.addEventListener('show.bs.modal', function () {
                    expectedRandom = String(Math.floor(1000 + Math.random() * 9000));
                    randomDisplay.textContent = expectedRandom;
                    nameInput.value = '';
                    randomInput.value = '';
                    confirmBtn.disabled = true;
                });

                modal.addEventListener('shown.bs.modal', function () {
                    nameInput.focus();
                });

                nameInput.addEventListener('input', validateConfirm);
                randomInput.addEventListener('input', validateConfirm);
                form.addEventListener('submit', function (e) {
                    if (nameInput.value.trim() !== projectName || randomInput.value.trim() !== expectedRandom) {
                        e.preventDefault();
                    }
                });
            });
        </script>
    @endcan
@endif
