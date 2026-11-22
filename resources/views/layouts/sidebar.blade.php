<aside class="kon-rail" id="sidebarMenu">
    <div class="kon-rail-logo">
        <a href="{{ url('/') }}" title="{{ __('general.system_name') }}">
            <img src="{{ asset('img/kon-logo.png') }}" alt="{{ __('general.system_name') }}">
        </a>
        <button type="button" class="kon-rail-toggle desktop-only" onclick="toggleSidebar()" title="{{ __('general.toggle_sidebar') }}" aria-label="{{ __('general.toggle_sidebar') }}">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <nav aria-label="{{ __('general.menu') }}">
        @can('dashboard-view')
            <a class="kon-nav-a {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                href="{{ route('dashboard') }}" title="{{ __('general.dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                <i class="fas fa-home"></i>
                <span>{{ __('general.dashboard') }}</span>
            </a>
        @endcan

        @can('projects-list')
            <a class="kon-nav-a {{ (request()->routeIs('projects.*') && !request()->routeIs('projects.archived')) ? 'active' : '' }}"
                href="{{ route('projects.index') }}" title="{{ __('general.projects') }}" @if(request()->routeIs('projects.*') && !request()->routeIs('projects.archived')) aria-current="page" @endif>
                <i class="fas fa-folder"></i>
                <span>{{ __('general.projects') }}</span>
            </a>
            <a class="kon-nav-a {{ request()->routeIs('projects.archived') ? 'active' : '' }}"
                href="{{ route('projects.archived') }}" title="{{ __('general.archived_projects') }}" @if(request()->routeIs('projects.archived')) aria-current="page" @endif>
                <i class="fas fa-box-archive"></i>
                <span>{{ __('general.archived_projects') }}</span>
            </a>
        @endcan

        @can('issues-list')
            <a class="kon-nav-a {{ request()->routeIs('issues.*') ? 'active' : '' }}"
                href="{{ route('issues.index') }}" title="{{ __('general.issues_management') }}" @if(request()->routeIs('issues.*')) aria-current="page" @endif>
                <i class="fas fa-triangle-exclamation"></i>
                <span>{{ __('general.issues') }}</span>
            </a>
        @endcan

        @can('dailyprogress-list')
            <a class="kon-nav-a {{ request()->routeIs('daily-progress.index') ? 'active' : '' }}"
                href="{{ route('daily-progress.index') }}" title="{{ __('general.daily_progress') }}" @if(request()->routeIs('daily-progress.index')) aria-current="page" @endif>
                <i class="fas fa-list-check"></i>
                <span>{{ __('general.daily_progress') }}</span>
            </a>
        @endcan

        @can('dailyprogress-create')
            <a class="kon-nav-a {{ request()->routeIs('daily-progress.create') ? 'active' : '' }}"
                href="{{ route('daily-progress.create') }}" title="{{ __('general.create_daily_progress') }}" @if(request()->routeIs('daily-progress.create')) aria-current="page" @endif>
                <i class="fas fa-plus-circle"></i>
                <span>{{ __('general.create_daily_progress') }}</span>
            </a>
        @endcan

        @if(auth()->user()?->can('users-list') || auth()->user()?->can('employees-list') || auth()->user()?->can('categories-list') || auth()->user()?->can('items-list') || auth()->user()?->can('item-statuses-list') || auth()->user()?->can('project-templates-list') || auth()->user()?->can('project-types-list'))
            <div class="kon-nav-sec">{{ __('general.menu') }}</div>
        @endif

        @can('users-list')
            <a class="kon-nav-a {{ request()->routeIs('clients.*') ? 'active' : '' }}"
                href="{{ route('clients.index') }}" title="{{ __('general.clients') }}" @if(request()->routeIs('clients.*')) aria-current="page" @endif>
                <i class="fas fa-users"></i>
                <span>{{ __('general.clients') }}</span>
            </a>
        @endcan

        @can('employees-list')
            <a class="kon-nav-a {{ request()->routeIs('employees.*') ? 'active' : '' }}"
                href="{{ route('employees.index') }}" title="{{ __('general.employees') }}" @if(request()->routeIs('employees.*')) aria-current="page" @endif>
                <i class="fas fa-user-tie"></i>
                <span>{{ __('general.employees') }}</span>
            </a>
        @endcan

        @can('categories-list')
            <a class="kon-nav-a {{ request()->routeIs('categories.*') ? 'active' : '' }}"
                href="{{ route('categories.index') }}" title="{{ __('general.categories') }}" @if(request()->routeIs('categories.*')) aria-current="page" @endif>
                <i class="fas fa-layer-group"></i>
                <span>{{ __('general.categories') }}</span>
            </a>
        @endcan

        @can('items-list')
            <a class="kon-nav-a {{ request()->routeIs('work-items.*') ? 'active' : '' }}"
                href="{{ route('work-items.index') }}" title="{{ __('general.work_items') }}" @if(request()->routeIs('work-items.*')) aria-current="page" @endif>
                <i class="fas fa-tasks"></i>
                <span>{{ __('general.work_items') }}</span>
            </a>
        @endcan

        @can('item-statuses-list')
            <a class="kon-nav-a {{ request()->routeIs('item-statuses.*') ? 'active' : '' }}"
                href="{{ route('item-statuses.index') }}" title="{{ __('general.item_statuses') }}" @if(request()->routeIs('item-statuses.*')) aria-current="page" @endif>
                <i class="fas fa-tags"></i>
                <span>{{ __('general.item_statuses') }}</span>
            </a>
        @endcan

        @can('project-templates-list')
            <a class="kon-nav-a {{ request()->routeIs('project-templates.*') ? 'active' : '' }}"
                href="{{ route('project-templates.index') }}" title="{{ __('general.project_templates') }}" @if(request()->routeIs('project-templates.*')) aria-current="page" @endif>
                <i class="fas fa-file-alt"></i>
                <span>{{ __('general.project_templates') }}</span>
            </a>
        @endcan

        @can('project-types-list')
            <a class="kon-nav-a {{ request()->routeIs('project_types.*') ? 'active' : '' }}"
                href="{{ route('project_types.index') }}" title="{{ __('general.project__type') }}" @if(request()->routeIs('project_types.*')) aria-current="page" @endif>
                <i class="fa-solid fa-diagram-next"></i>
                <span>{{ __('general.project__type') }}</span>
            </a>
        @endcan

        @if(auth()->user()?->can('activity-logs-list') || auth()->user()?->can('recycle-bin-list') || auth()->user()?->can('backup-create'))
            <div class="kon-nav-sec">{{ __('general.system_name') }}</div>
        @endif

        @can('activity-logs-list')
            <a class="kon-nav-a {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}"
                href="{{ route('activity-logs.index') }}" title="{{ __('activity-logs.activity_logs_nav') }}" @if(request()->routeIs('activity-logs.*')) aria-current="page" @endif>
                <i class="fas fa-history"></i>
                <span>{{ __('activity-logs.activity_logs_nav') }}</span>
            </a>
        @endcan

        @can('recycle-bin-list')
            <a class="kon-nav-a {{ request()->routeIs('recycle-bin.*') ? 'active' : '' }}"
                href="{{ route('recycle-bin.index') }}" title="{{ __('general.recycle_bin_title') }}" @if(request()->routeIs('recycle-bin.*')) aria-current="page" @endif>
                <i class="fas fa-trash-alt"></i>
                <span>{{ __('general.recycle_bin_title') }}</span>
            </a>
        @endcan

        @can('backup-create')
            <a class="kon-nav-a {{ request()->routeIs('backup.*') ? 'active' : '' }}"
                href="{{ route('backup.index') }}" title="{{ __('general.backup_restore') }}" @if(request()->routeIs('backup.*')) aria-current="page" @endif>
                <i class="fas fa-database"></i>
                <span>{{ __('general.backup_restore') }}</span>
            </a>
        @endcan
    </nav>

    @auth
        <div class="kon-rail-foot">
            <div class="kon-who">
                <span class="kon-av" aria-hidden="true">{{ mb_substr(trim(Auth::user()->name ?? 'U'), 0, 1) }}</span>
                <div class="kon-who-info">
                    <b>{{ Auth::user()->name }}</b>
                    <small>{{ Auth::user()->email }}</small>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="kon-logout-btn" title="{{ __('general.logout') }}" aria-label="{{ __('general.logout') }}">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </form>
            </div>
        </div>
    @endauth
</aside>
