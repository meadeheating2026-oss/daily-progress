@php
    $activeLocale = $currentLocale ?? session('locale', app()->getLocale());
@endphp
<header class="kon-topbar">
    <button type="button" class="kon-btn ghost ico kon-burger" onclick="toggleSidebar()" title="{{ __('general.toggle_sidebar') }}" aria-label="{{ __('general.toggle_sidebar') }}">
        <i class="fas fa-bars"></i>
    </button>

    <div class="kon-crumbs">
        @hasSection('breadcrumb')
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    @yield('breadcrumb')
                    @hasSection('title')
                        <li class="breadcrumb-item active" aria-current="page">@yield('title')</li>
                    @endif
                </ol>
            </nav>
        @else
            <b>@yield('title', __('general.system_name'))</b>
        @endif
    </div>

    <div class="kon-top-r">
        @can('dailyprogress-create')
            <a class="kon-btn pri" href="{{ route('daily-progress.create') }}" title="{{ __('general.daily_progress') }}">
                <i class="fas fa-calendar-day"></i>
                <span class="kon-top-label">{{ __('general.daily_progress') }}</span>
            </a>
        @endcan

        <button type="button" id="darkModeToggle" class="kon-btn ghost ico" title="Toggle Dark Mode" aria-label="Toggle Dark Mode">
            <i class="fas fa-moon" id="darkModeIcon"></i>
        </button>

        <div class="kon-lang" role="group" aria-label="Language">
            <a href="{{ route('lang.switch', 'ar') }}" class="{{ $activeLocale == 'ar' ? 'active' : '' }}">ع</a>
            <a href="{{ route('lang.switch', 'en') }}" class="{{ $activeLocale == 'en' ? 'active' : '' }}">EN</a>
        </div>

        <div class="dropdown">
            <button type="button" class="kon-btn ghost ico" id="languageDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="More languages" aria-label="More languages">
                <i class="fas fa-globe"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
                <li>
                    <a class="dropdown-item {{ $activeLocale == 'ar' ? 'active' : '' }}" href="{{ route('lang.switch', 'ar') }}">
                        العربية
                    </a>
                </li>
                <li>
                    <a class="dropdown-item {{ $activeLocale == 'en' ? 'active' : '' }}" href="{{ route('lang.switch', 'en') }}">
                        English
                    </a>
                </li>
                <li>
                    <a class="dropdown-item {{ $activeLocale == 'ur' ? 'active' : '' }}" href="{{ route('lang.switch', 'ur') }}">
                        اردو
                    </a>
                </li>
                <li>
                    <a class="dropdown-item {{ $activeLocale == 'hi' ? 'active' : '' }}" href="{{ route('lang.switch', 'hi') }}">
                        हिन्दी
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>
