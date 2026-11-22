@extends('layouts.auth')

@section('content')
@php
    $activeLocale = session('locale', app()->getLocale());
@endphp
<div class="kon-auth">
    <div class="kon-auth-l">
        <div class="d-flex justify-content-between align-items-center" style="max-width: 400px; width: 100%;">
            <img class="logo" src="{{ asset('img/kon-logo.png') }}" alt="{{ __('general.system_name') }}">
            @if (Route::has('lang.switch'))
                <div class="kon-lang" role="group" aria-label="Language">
                    <a href="{{ route('lang.switch', 'ar') }}" class="{{ $activeLocale == 'ar' ? 'active' : '' }}">ع</a>
                    <a href="{{ route('lang.switch', 'en') }}" class="{{ $activeLocale == 'en' ? 'active' : '' }}">EN</a>
                </div>
            @endif
        </div>

        <h1>{{ $activeLocale == 'ar' ? 'تسجيل الدخول' : 'Sign in' }}</h1>
        <p class="lead">{{ $activeLocale == 'ar' ? 'تتبع تقدم مشاريعك بكفاءة واحترافية.' : 'Track your projects progress efficiently and professionally.' }}</p>

        <form method="POST" action="{{ route('login') }}" id="loginForm" novalidate>
            @csrf

            <label class="kon-fl" for="email">
                <span>{{ $activeLocale == 'ar' ? 'البريد الإلكتروني' : 'Email' }}</span>
                <input id="email"
                       type="email"
                       class="form-control ltr @error('email') is-invalid @enderror"
                       name="email"
                       value="{{ old('email') }}"
                       placeholder="name@kon-uae.com"
                       required
                       autocomplete="email"
                       autofocus>
                @error('email')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </label>

            <label class="kon-fl" for="password">
                <span>{{ $activeLocale == 'ar' ? 'كلمة المرور' : 'Password' }}</span>
                <div class="kon-pw-wrap">
                    <input id="password"
                           type="password"
                           class="form-control @error('password') is-invalid @enderror"
                           name="password"
                           required
                           autocomplete="current-password">
                    <button type="button" class="kon-pw-toggle" id="togglePassword" aria-label="Toggle password visibility">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
                @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </label>

            <div class="d-flex justify-content-between align-items-center my-1">
                <div class="form-check m-0">
                    <input class="form-check-input"
                           type="checkbox"
                           name="remember"
                           id="remember"
                           {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="remember" style="font-size: 13px; color: var(--ink-2);">
                        {{ $activeLocale == 'ar' ? 'تذكرني' : 'Remember me' }}
                    </label>
                </div>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" style="font-size: 13px; text-decoration: none;">
                        {{ $activeLocale == 'ar' ? 'نسيت كلمة المرور؟' : 'Forgot your password?' }}
                    </a>
                @endif
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 mt-1" id="loginSubmitBtn">
                <span class="btn-text">{{ $activeLocale == 'ar' ? 'دخول' : 'Sign in' }}</span>
                <span class="btn-loading d-none">
                    <span class="spinner-border spinner-border-sm me-1"></span>
                    {{ $activeLocale == 'ar' ? 'جاري التحميل...' : 'Signing in...' }}
                </span>
            </button>
        </form>

        <footer class="kon-auth-foot">
            © {{ date('Y') }} {{ $activeLocale == 'ar' ? 'كن للمقاولات العامة ذ.م.م — جميع الحقوق محفوظة.' : 'KON General Contracting L.L.C — All rights reserved.' }}
        </footer>
    </div>

    <div class="kon-auth-r">
        <div class="pattern"></div>

        <div class="demo" aria-hidden="true">
            <div class="demo-row">
                <span>{{ $activeLocale == 'ar' ? 'تقدم المشاريع' : 'Projects' }}</span>
                <span class="segbar"><i style="--c:#4cc38d; width:92%"></i></span>
                <b>92%</b>
            </div>
            <div class="demo-row">
                <span>{{ $activeLocale == 'ar' ? 'الإنجاز اليومي' : 'Daily progress' }}</span>
                <span class="segbar"><i style="--c:#7f9fff; width:74%"></i></span>
                <b>74%</b>
            </div>
            <div class="demo-row">
                <span>{{ $activeLocale == 'ar' ? 'بنود الأعمال' : 'Work items' }}</span>
                <span class="segbar"><i style="--c:#e5a94a; width:58%"></i></span>
                <b>58%</b>
            </div>
            <div class="demo-row">
                <span>{{ $activeLocale == 'ar' ? 'المتابعة والتقارير' : 'Reports' }}</span>
                <span class="segbar"><i style="--c:#f08cc1; width:45%"></i></span>
                <b>45%</b>
            </div>
        </div>

        <h2>{{ $activeLocale == 'ar' ? 'إدارة متقدمة ومتابعة دقيقة لتقدم كل مشروع.' : 'Clear visibility into every project and daily progress.' }}</h2>
        <p>{{ $activeLocale == 'ar' ? 'المشاريع، بنود الأعمال، الإنجاز اليومي، الفرق والموظفين والتقارير في مكان واحد.' : 'Projects, work items, daily progress, teams and reports in one unified place.' }}</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    const submitBtn = document.getElementById('loginSubmitBtn');

    if (togglePassword && password && toggleIcon) {
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            toggleIcon.classList.toggle('fa-eye', type === 'password');
            toggleIcon.classList.toggle('fa-eye-slash', type === 'text');
        });
    }

    if (form && submitBtn) {
        form.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.querySelector('.btn-text')?.classList.add('d-none');
            submitBtn.querySelector('.btn-loading')?.classList.remove('d-none');
        });
    }
});
</script>
@endsection
