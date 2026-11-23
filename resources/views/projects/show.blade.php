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
    <li class="breadcrumb-item active">{{ __('general.project_details') }}</li>
@endsection

@section('title', __('general.project_details') . ' - ' . $project->name)

@push('styles')
<style>
    .kon-details-page .kon-section-card {
        background: var(--surface, #ffffff);
        border: 1px solid var(--line, #e2e8f0);
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .kon-details-page .kon-section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--line, #e2e8f0);
        flex-wrap: wrap;
    }

    .kon-details-page .kon-section-title {
        font-size: 1.02rem;
        font-weight: 700;
        color: var(--ink, #0f172a);
        margin: 0;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .kon-details-page .kon-section-title i {
        color: var(--brand, #213c7a);
    }

    .kon-details-page .kon-kpi-card {
        background: var(--surface, #ffffff);
        border: 1px solid var(--line, #e2e8f0);
        border-radius: 14px;
        padding: 1.15rem 1.25rem;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }

    .kon-details-page .kon-kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 11px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }

    .kon-details-page .kon-kpi-value {
        font-size: 1.65rem;
        font-weight: 800;
        color: var(--ink, #0f172a);
        line-height: 1.15;
    }

    .kon-details-page .kon-kpi-label {
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--ink-3, #64748b);
    }

    .kon-details-page .kon-meta-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .kon-details-page .kon-meta-list li {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.65rem 0;
        border-bottom: 1px dashed var(--line, #e2e8f0);
        font-size: 0.88rem;
    }

    .kon-details-page .kon-meta-list li:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .kon-details-page .kon-meta-key {
        color: var(--ink-3, #64748b);
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
    }

    .kon-details-page .kon-meta-val {
        color: var(--ink, #0f172a);
        font-weight: 700;
        text-align: end;
    }

    .kon-details-page .kon-chart-box {
        position: relative;
        height: 300px;
        width: 100%;
    }

    .kon-details-page .kon-milestone-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
    }

    @media (max-width: 991.98px) {
        .kon-details-page .kon-milestone-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575.98px) {
        .kon-details-page .kon-milestone-grid {
            grid-template-columns: 1fr;
        }
    }

    .kon-details-page .kon-milestone-card {
        background: var(--surface-2, #f8fafc);
        border: 1px solid var(--line, #e2e8f0);
        border-inline-start: 4px solid var(--brand, #213c7a);
        border-radius: 12px;
        padding: 1rem 1.1rem;
    }

    .kon-details-page .kon-milestone-card.is-done {
        border-inline-start-color: var(--good, #16803c);
    }

    .kon-details-page .kon-milestone-card.is-current {
        background: rgba(33, 60, 122, 0.04);
        border-color: rgba(33, 60, 122, 0.25);
        border-inline-start-color: var(--brand, #213c7a);
    }

    .kon-details-page .kon-details-table th {
        background: var(--surface-2, #f8fafc);
        color: var(--ink-2, #334155);
        font-size: 0.8rem;
        font-weight: 700;
        padding: 0.75rem 0.9rem;
        border-bottom: 1px solid var(--line-2, #cbd5e1);
        white-space: nowrap;
    }

    .kon-details-page .kon-details-table td {
        padding: 0.8rem 0.9rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--line, #e2e8f0);
        font-size: 0.875rem;
    }

    .kon-details-page .kon-emp-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.35rem 0.7rem;
        border-radius: 999px;
        background: var(--surface-2, #f8fafc);
        border: 1px solid var(--line, #e2e8f0);
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--ink-2, #334155);
    }

    .kon-details-page .kon-emp-avatar {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: var(--brand, #213c7a);
        color: #ffffff;
        font-size: 0.68rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
</style>
@endpush

@section('content')
@php
    $now = \Carbon\Carbon::today();
    $startDate = $project->start_date ? \Carbon\Carbon::parse($project->start_date)->startOfDay() : null;
    $endDate = $project->end_date ? \Carbon\Carbon::parse($project->end_date)->startOfDay() : null;

    $totalDurationDays = ($startDate && $endDate) ? max(0, (int) $startDate->diffInDays($endDate)) : null;
    $daysPassed = $startDate ? ($now->greaterThan($startDate) ? (int) $startDate->diffInDays($now) : 0) : null;
    $daysRemaining = $endDate ? ($endDate->greaterThan($now) ? (int) $now->diffInDays($endDate) : 0) : null;
    $isOverdue = $endDate && $now->greaterThan($endDate) && $project->status !== 'completed';

    $totalItemsCount = $project->items->count();
    $completedItemsCount = $project->items->filter(fn($it) => $it->completion_percentage >= 100)->count();
    $inProgressItemsCount = $project->items->filter(fn($it) => $it->completion_percentage > 0 && $it->completion_percentage < 100)->count();
    $notStartedItemsCount = max(0, $totalItemsCount - $completedItemsCount - $inProgressItemsCount);

    $clampedOverall = min(100, max(0, (float) $overallProgress));
    $overallLabelClass = $clampedOverall >= 52 ? 'on-dark' : 'on-light';

    // Map item ID -> sequential index (1..N) for accurate Predecessor display
    $itemIndexById = [];
    foreach ($project->items->values() as $idx => $pItem) {
        $itemIndexById[$pItem->id] = $idx + 1;
    }

    // Build chart data: Subprojects if available, else grouped by WorkItem name
    $overviewLabels = [];
    $overviewActual = [];
    $overviewRemaining = [];

    if ($project->subprojects->count() > 0) {
        foreach ($itemsBySubproject as $subName => $subItems) {
            $measSub = $subItems->filter(fn($it) => (bool) ($it->is_measurable ?? false));
            $totQ = (float) $measSub->sum(fn($it) => max(0, (float) ($it->total_quantity ?? 0)));
            $compQ = (float) $measSub->sum(fn($it) => min(max(0, (float) ($it->completed_quantity ?? 0)), max(0, (float) ($it->total_quantity ?? 0))));
            $pct = $totQ > 0 ? round(($compQ / $totQ) * 100, 1) : 0;
            $overviewLabels[] = \Illuminate\Support\Str::limit($subName, 26);
            $overviewActual[] = $pct;
            $overviewRemaining[] = round(max(0, 100 - $pct), 1);
        }
    } else {
        $groupedItems = $project->items->filter(fn($it) => (bool) ($it->is_measurable ?? false))->groupBy(fn($it) => $it->workItem->name ?? '-')->take(12);
        foreach ($groupedItems as $wName => $wItems) {
            $totQ = (float) $wItems->sum(fn($it) => max(0, (float) ($it->total_quantity ?? 0)));
            $compQ = (float) $wItems->sum(fn($it) => min(max(0, (float) ($it->completed_quantity ?? 0)), max(0, (float) ($it->total_quantity ?? 0))));
            $pct = $totQ > 0 ? round(($compQ / $totQ) * 100, 1) : 0;
            $overviewLabels[] = \Illuminate\Support\Str::limit($wName, 26);
            $overviewActual[] = $pct;
            $overviewRemaining[] = round(max(0, 100 - $pct), 1);
        }
    }

    $weightStatus = $project->subprojects_weight_status;

    $statusBadgeClass = match($project->status) {
        'active' => 'bg-success',
        'completed' => 'bg-primary',
        'pending' => 'bg-warning',
        default => 'bg-secondary',
    };
    $statusLabel = match($project->status) {
        'active' => __('general.active'),
        'completed' => __('general.completed'),
        'pending' => __('general.pending'),
        default => __('general.suspended'),
    };
@endphp

<div class="kon-details-page">
    @if($weightStatus['has_subprojects'] && ($weightStatus['is_zero'] || $weightStatus['is_incomplete']))
        <div class="alert alert-warning d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 rounded-3">
            <div class="fw-semibold small">
                <i class="fas fa-exclamation-triangle me-2"></i>
                @if($weightStatus['is_zero'])
                    لم يتم تحديد أوزان المشاريع الفرعية بعد (المجموع 0%) — تظهر نسبة إنجاز المشروع الأساسي 0% لحين توزيع الأوزان بمجموع 100%.
                @else
                    مجموع أوزان المشاريع الفرعية حالياً ({{ $weightStatus['total_weight'] }}%) — يلزم استكمال الأوزان لتصبح 100%.
                @endif
            </div>
            <a href="{{ route('projects.index') }}" class="btn btn-sm btn-warning fw-bold">تحديد الأوزان</a>
        </div>
    @endif

    <!-- Top Header Summary Card -->
    <div class="kon-section-card p-3 p-md-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-lg-7">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                    @if($project->type)
                        <span class="badge bg-light text-dark border">
                            <i class="fas fa-tag me-1 text-primary"></i>{{ $project->type->name }}
                        </span>
                    @endif
                    @if($project->working_zone)
                        <span class="badge bg-light text-dark border">
                            <i class="fas fa-location-dot me-1 text-secondary"></i>{{ $project->working_zone }}
                        </span>
                    @endif
                    @if($project->is_draft)
                        <span class="badge bg-warning">مسودة</span>
                    @endif
                </div>
                <h1 class="h4 fw-bold mb-1" style="color: var(--ink, #0f172a);">{{ $project->name }}</h1>
                @if($project->description)
                    <p class="text-muted small mb-0">{{ $project->description }}</p>
                @endif
            </div>

            <div class="col-lg-5">
                <div class="d-flex flex-wrap justify-content-lg-end gap-2 mb-3 no-print">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                        <i class="fas fa-print me-1 text-primary"></i>{{ __('general.print') }}
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#saveAsTemplateModal">
                        <i class="fas fa-layer-group me-1"></i>{{ __('general.save_as_template') }}
                    </button>
                </div>
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1 small">
                        <span class="fw-semibold text-muted">{{ __('general.overall_progress') }}</span>
                        <span class="fw-bold" style="color: var(--brand, #213c7a);">{{ number_format($clampedOverall, 1) }}%</span>
                    </div>
                    <div class="kon-progress-track" role="progressbar" aria-valuenow="{{ $clampedOverall }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="kon-progress-fill fill-actual" style="width: {{ $clampedOverall }}%;"></div>
                        <span class="kon-progress-label {{ $overallLabelClass }}">{{ number_format($clampedOverall, 1) }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4 KPI Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="kon-kpi-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <div class="kon-kpi-label mb-1">{{ __('general.work_items') }}</div>
                        <div class="kon-kpi-value">{{ $totalItemsCount }}</div>
                    </div>
                    <div class="kon-kpi-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-list-check"></i>
                    </div>
                </div>
                <div class="small text-muted">
                    <span class="text-success fw-semibold">{{ $completedItemsCount }}</span> مكتمل •
                    <span class="text-primary fw-semibold">{{ $inProgressItemsCount }}</span> جاري
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="kon-kpi-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <div class="kon-kpi-label mb-1">{{ __('general.overall_progress') }}</div>
                        <div class="kon-kpi-value">{{ number_format($clampedOverall, 1) }}%</div>
                    </div>
                    <div class="kon-kpi-icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar bg-success" style="width: {{ $clampedOverall }}%"></div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="kon-kpi-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <div class="kon-kpi-label mb-1">{{ __('general.days_passed') }}</div>
                        <div class="kon-kpi-value">
                            {{ $daysPassed !== null ? $daysPassed : '—' }}
                        </div>
                    </div>
                    <div class="kon-kpi-icon" style="background: rgba(71, 85, 105, 0.10); color: #334155;">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="small text-muted">
                    @if($totalDurationDays !== null)
                        من إجمالي <strong>{{ $totalDurationDays }}</strong> {{ __('general.days') }}
                    @else
                        {{ __('general.not_specified') }}
                    @endif
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="kon-kpi-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <div class="kon-kpi-label mb-1">{{ __('general.days_remaining') }}</div>
                        <div class="kon-kpi-value {{ $isOverdue ? 'text-danger' : '' }}">
                            {{ $daysRemaining !== null ? $daysRemaining : '—' }}
                        </div>
                    </div>
                    <div class="kon-kpi-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
                <div class="small {{ $isOverdue ? 'text-danger fw-semibold' : 'text-muted' }}">
                    @if($isOverdue)
                        تجاوز تاريخ الانتهاء المحدد
                    @elseif($endDate)
                        حتى {{ $endDate->format('Y-m-d') }}
                    @else
                        {{ __('general.not_specified') }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Project Info, Client Info & Team Row -->
    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="kon-section-card h-100">
                <div class="kon-section-head">
                    <h2 class="kon-section-title">
                        <i class="fas fa-circle-info"></i>
                        <span>{{ __('general.project_information') }}</span>
                    </h2>
                </div>
                <div class="p-3">
                    <ul class="kon-meta-list">
                        <li>
                            <span class="kon-meta-key"><i class="fas fa-play text-success"></i>{{ __('general.start_date') }}</span>
                            <span class="kon-meta-val">{{ $startDate ? $startDate->format('Y-m-d') : __('general.not_specified') }}</span>
                        </li>
                        <li>
                            <span class="kon-meta-key"><i class="fas fa-flag-checkered text-primary"></i>{{ __('general.end_date') }}</span>
                            <span class="kon-meta-val">{{ $endDate ? $endDate->format('Y-m-d') : __('general.not_specified') }}</span>
                        </li>
                        <li>
                            <span class="kon-meta-key"><i class="fas fa-hourglass-half text-secondary"></i>{{ __('general.project_duration') }}</span>
                            <span class="kon-meta-val">
                                {{ $totalDurationDays !== null ? $totalDurationDays . ' ' . __('general.days') : __('general.not_specified') }}
                            </span>
                        </li>
                        <li>
                            <span class="kon-meta-key"><i class="fas fa-location-dot text-secondary"></i>{{ __('general.working_zone') }}</span>
                            <span class="kon-meta-val">{{ $project->working_zone ?: __('general.not_specified') }}</span>
                        </li>
                        @if($project->daily_work_hours)
                        <li>
                            <span class="kon-meta-key"><i class="fas fa-business-time text-secondary"></i>ساعات العمل اليومية</span>
                            <span class="kon-meta-val">{{ $project->daily_work_hours }} س</span>
                        </li>
                        @endif
                        <li>
                            <span class="kon-meta-key"><i class="fas fa-sitemap text-primary"></i>المشاريع الفرعية</span>
                            <span class="kon-meta-val">{{ $project->subprojects->count() }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="kon-section-card h-100">
                <div class="kon-section-head">
                    <h2 class="kon-section-title">
                        <i class="fas fa-building"></i>
                        <span>{{ __('general.client_information') }}</span>
                    </h2>
                </div>
                <div class="p-3">
                    @if($project->client)
                        <div class="d-flex align-items-center gap-3 mb-3 pb-2 border-bottom">
                            <div class="kon-kpi-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="fw-bold text-truncate" style="color: var(--ink, #0f172a);">{{ $project->client->name }}</div>
                                @if($project->client->contact_person)
                                    <div class="small text-muted text-truncate">{{ $project->client->contact_person }}</div>
                                @endif
                            </div>
                        </div>
                        <ul class="kon-meta-list">
                            <li>
                                <span class="kon-meta-key"><i class="fas fa-phone text-success"></i>{{ __('general.phone') }}</span>
                                <span class="kon-meta-val" dir="ltr">{{ $project->client->phone ?: __('general.not_specified') }}</span>
                            </li>
                            <li>
                                <span class="kon-meta-key"><i class="fas fa-envelope text-primary"></i>{{ __('general.email') }}</span>
                                <span class="kon-meta-val text-truncate" style="max-width: 190px;">{{ $project->client->email ?: __('general.not_specified') }}</span>
                            </li>
                            <li>
                                <span class="kon-meta-key"><i class="fas fa-map-marker-alt text-secondary"></i>{{ __('general.address') }}</span>
                                <span class="kon-meta-val">{{ $project->client->address ?: __('general.not_specified') }}</span>
                            </li>
                        </ul>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-building fa-2x mb-2 opacity-50"></i>
                            <p class="mb-0 small">{{ __('general.no_client_assigned') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="kon-section-card h-100">
                <div class="kon-section-head">
                    <h2 class="kon-section-title">
                        <i class="fas fa-users"></i>
                        <span>{{ __('general.team_members') }}</span>
                    </h2>
                    <span class="badge bg-primary">{{ $project->employees->count() }}</span>
                </div>
                <div class="p-3">
                    @if($project->employees->count() > 0)
                        <div class="d-flex flex-wrap gap-2" style="max-height: 195px; overflow-y: auto;">
                            @foreach($project->employees as $emp)
                                <span class="kon-emp-chip" title="{{ $emp->position ?? '' }}">
                                    <span class="kon-emp-avatar">{{ mb_substr($emp->name, 0, 1) }}</span>
                                    <span>{{ $emp->name }}</span>
                                </span>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-users fa-2x mb-2 opacity-50"></i>
                            <p class="mb-0 small">{{ __('general.no_employees_assigned') }}</p>
                        </div>
                    @endif

                    @if($project->subprojects->count() > 0)
                        <div class="mt-3 pt-3 border-top">
                            <div class="small fw-bold text-muted mb-2">أوزان المشاريع الفرعية:</div>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($project->subprojects as $sp)
                                    <span class="badge bg-light text-dark border">
                                        {{ $sp->name }} @if($sp->weight)<strong class="text-primary">({{ $sp->weight }}%)</strong>@endif
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="kon-section-card h-100">
                <div class="kon-section-head">
                    <h2 class="kon-section-title">
                        <i class="fas fa-chart-bar"></i>
                        <span>{{ __('general.project_progress_overview') }}</span>
                    </h2>
                    <span class="small text-muted">
                        {{ $project->subprojects->count() > 0 ? 'حسب المشروع الفرعي' : 'حسب بنود العمل' }}
                    </span>
                </div>
                <div class="p-3">
                    @if(count($overviewLabels) > 0)
                        <div class="kon-chart-box">
                            <canvas id="detailsProgressChart"></canvas>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">لا توجد بنود لعرض الرسم البياني</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="kon-section-card h-100">
                <div class="kon-section-head">
                    <h2 class="kon-section-title">
                        <i class="fas fa-chart-pie"></i>
                        <span>{{ __('general.progress_distribution') }}</span>
                    </h2>
                </div>
                <div class="p-3 d-flex flex-column justify-content-between">
                    <div class="kon-chart-box" style="height: 235px;">
                        <canvas id="detailsDonutChart"></canvas>
                    </div>
                    <div class="d-flex justify-content-around text-center border-top pt-3 mt-2 small">
                        <div>
                            <div class="fw-bold text-success">{{ $completedItemsCount }}</div>
                            <div class="text-muted">مكتمل</div>
                        </div>
                        <div>
                            <div class="fw-bold text-primary">{{ $inProgressItemsCount }}</div>
                            <div class="text-muted">قيد التنفيذ</div>
                        </div>
                        <div>
                            <div class="fw-bold text-secondary">{{ $notStartedItemsCount }}</div>
                            <div class="text-muted">لم يبدأ</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Timeline Milestones -->
    <div class="kon-section-card mb-4">
        <div class="kon-section-head">
            <h2 class="kon-section-title">
                <i class="fas fa-project-diagram"></i>
                <span>{{ __('general.project_timeline') }}</span>
            </h2>
            <span class="small text-muted">{{ __('general.key_dates_milestones') }}</span>
        </div>
        <div class="p-3">
            @php
                $midDate = ($startDate && $endDate)
                    ? $startDate->copy()->addDays((int) floor($startDate->diffInDays($endDate) / 2))
                    : null;
            @endphp
            <div class="kon-milestone-grid">
                <div class="kon-milestone-card is-done">
                    <div class="small text-muted fw-semibold mb-1">{{ __('general.start_date') }}</div>
                    <div class="fw-bold fs-6" style="color: var(--ink, #0f172a);">{{ $startDate ? $startDate->format('Y-m-d') : __('general.not_specified') }}</div>
                    <div class="small text-success mt-1"><i class="fas fa-check-circle me-1"></i>{{ __('general.project_kickoff') }}</div>
                </div>

                <div class="kon-milestone-card">
                    <div class="small text-muted fw-semibold mb-1">{{ __('general.mid_point') }}</div>
                    <div class="fw-bold fs-6" style="color: var(--ink, #0f172a);">{{ $midDate ? $midDate->format('Y-m-d') : __('general.not_specified') }}</div>
                    <div class="small text-muted mt-1">{{ __('general.expected_50_completion') }}</div>
                </div>

                <div class="kon-milestone-card is-current">
                    <div class="small text-primary fw-semibold mb-1">{{ __('general.current_date') }}</div>
                    <div class="fw-bold fs-6" style="color: var(--ink, #0f172a);">{{ $now->format('Y-m-d') }}</div>
                    <div class="small text-primary fw-semibold mt-1">{{ __('general.progress') }}: {{ number_format($clampedOverall, 1) }}%</div>
                </div>

                <div class="kon-milestone-card">
                    <div class="small text-muted fw-semibold mb-1">{{ __('general.end_date') }}</div>
                    <div class="fw-bold fs-6" style="color: var(--ink, #0f172a);">{{ $endDate ? $endDate->format('Y-m-d') : __('general.not_specified') }}</div>
                    <div class="small text-muted mt-1">{{ __('general.expected_completion') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Full-Width Work Items Summary Table with Instant Filter -->
    <div class="kon-section-card mb-4">
        <div class="kon-section-head">
            <div class="d-flex align-items-center gap-2">
                <h2 class="kon-section-title">
                    <i class="fas fa-table-list"></i>
                    <span>{{ __('general.work_items_summary') }}</span>
                </h2>
                <span class="badge bg-primary" id="detailsItemsBadge">{{ $totalItemsCount }} {{ __('general.items') }}</span>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2 no-print">
                @if($project->subprojects->count() > 0)
                    <select id="detailsSubprojectFilter" class="form-select form-select-sm" style="width: auto; min-width: 170px;">
                        <option value="">كل المشاريع الفرعية</option>
                        @foreach($itemsBySubproject->keys() as $spKey)
                            <option value="{{ strtolower($spKey) }}">{{ $spKey }}</option>
                        @endforeach
                    </select>
                @endif
                <div class="input-group input-group-sm" style="width: 230px;">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="detailsItemSearch" class="form-control" placeholder="ابحث في البنود أو الملاحظات...">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table kon-details-table mb-0" id="detailsItemsTable">
                <thead>
                    <tr>
                        <th style="width: 50px;" class="text-center">#</th>
                        <th>{{ __('general.work_item') }}</th>
                        @if($project->subprojects->count() > 0)
                            <th>المشروع الفرعي</th>
                        @endif
                        <th>{{ __('general.predecessor') }}</th>
                        <th class="text-center">الكمية (منفذ / إجمالي)</th>
                        <th class="text-center">{{ __('general.expected_daily_progress') }}</th>
                        <th style="min-width: 175px;">{{ __('general.progress') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($project->items->values() as $idx => $item)
                        @php
                            $rowNum = $idx + 1;
                            $pct = min(100, max(0, (float) $item->completion_percentage));
                            $predItem = $item->predecessor ? $project->items->firstWhere('id', $item->predecessor) : null;
                            $predNum = ($predItem && isset($itemIndexById[$predItem->id])) ? $itemIndexById[$predItem->id] : null;
                            $subName = $item->subproject_name ?? __('general.without_subproject');
                        @endphp
                        <tr class="details-item-row"
                            data-subproject="{{ strtolower($subName) }}"
                            data-search="{{ strtolower(($item->workItem->name ?? '') . ' ' . ($item->notes ?? '') . ' ' . ($item->workItem->category->name ?? '') . ' ' . $subName) }}">
                            <td class="text-center fw-bold text-muted">{{ $rowNum }}</td>
                            <td>
                                <div class="fw-bold" style="color: var(--ink, #0f172a);">
                                    {{ $item->workItem->name ?? '-' }}
                                    @if($item->item_label)
                                        <span class="badge bg-light text-primary border ms-1">{{ $item->item_label }}</span>
                                    @endif
                                </div>
                                <div class="d-flex flex-wrap gap-2 small text-muted mt-1">
                                    @if($item->workItem->category ?? null)
                                        <span><i class="fas fa-folder me-1"></i>{{ $item->workItem->category->name }}</span>
                                    @endif
                                    @if($item->notes)
                                        <span><i class="fas fa-sticky-note me-1"></i>{{ \Illuminate\Support\Str::limit($item->notes, 45) }}</span>
                                    @endif
                                    @if($item->start_date && $item->end_date)
                                        <span><i class="fas fa-calendar me-1"></i>{{ \Carbon\Carbon::parse($item->start_date)->format('Y-m-d') }} ← {{ \Carbon\Carbon::parse($item->end_date)->format('Y-m-d') }}</span>
                                    @endif
                                </div>
                            </td>

                            @if($project->subprojects->count() > 0)
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $subName }}</span>
                                </td>
                            @endif

                            <td>
                                @if($predItem)
                                    <div class="d-inline-flex align-items-center gap-1 px-2 py-1 rounded border bg-light small">
                                        @if($predNum)
                                            <span class="badge bg-primary">{{ $predNum }}</span>
                                        @endif
                                        <span class="fw-semibold text-dark">{{ \Illuminate\Support\Str::limit($predItem->workItem->name ?? '-', 28) }}</span>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        {{ $item->dependency_type === 'start_to_start' ? __('general.start_to_start') : __('general.end_to_start') }}
                                        @if($item->lag != 0)
                                            • {{ __('general.lag') }}: {{ $item->lag }} {{ __('general.days') }}
                                        @endif
                                    </small>
                                @else
                                    <span class="text-muted small">{{ __('general.none') }}</span>
                                @endif
                            </td>

                            <td class="text-center">
                                <span class="fw-bold text-success">{{ number_format($item->completed_quantity, 2) }}</span>
                                <span class="text-muted">/</span>
                                <span class="fw-semibold">{{ number_format($item->total_quantity, 2) }}</span>
                                <small class="text-muted d-block">{{ $item->workItem->unit ?? '' }} (متبقي: {{ number_format($item->remaining_quantity, 2) }})</small>
                            </td>

                            <td class="text-center">
                                @if($item->estimated_daily_qty)
                                    <span class="badge bg-light text-dark border">
                                        {{ number_format($item->estimated_daily_qty, 2) }} {{ $item->workItem->unit ?? '' }}/{{ __('general.day') }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td>
                                <div class="kon-progress-track" style="height: 22px;">
                                    <div class="kon-progress-fill {{ $pct >= 80 ? 'fill-actual' : 'fill-planned' }}" style="width: {{ $pct }}%;"></div>
                                    <span class="kon-progress-label {{ $pct >= 52 ? 'on-dark' : 'on-light' }}" style="font-size: 0.74rem;">{{ number_format($pct, 1) }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $project->subprojects->count() ? 7 : 6 }}" class="text-center py-4 text-muted">
                                لا توجد بنود مضافة لهذا المشروع بعد.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Latest Daily Progress Records -->
    <div class="kon-section-card mb-4">
        <div class="kon-section-head">
            <div class="d-flex align-items-center gap-2">
                <h2 class="kon-section-title">
                    <i class="fas fa-clock-rotate-left"></i>
                    <span>{{ __('general.daily_progress_record') }}</span>
                </h2>
                <span class="badge bg-secondary">{{ $dailyProgressCount }} {{ __('general.records') }}</span>
            </div>
            <div class="d-flex gap-2 no-print">
                <a href="{{ route('projects.progress', $project->id) }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-table me-1"></i>{{ __('general.progress_report') }}
                </a>
                @can('dailyprogress-create')
                    <a href="{{ route('daily-progress.create', ['project_id' => $project->id]) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i>{{ __('general.add_progress') }}
                    </a>
                @endcan
            </div>
        </div>

        <div class="table-responsive">
            <table class="table kon-details-table mb-0">
                <thead>
                    <tr>
                        <th>{{ __('general.date') }}</th>
                        <th>الموظف</th>
                        <th>{{ __('general.work_item') }}</th>
                        <th class="text-center">{{ __('general.quantity') }}</th>
                        <th class="text-center">مساهمة السجل من البند</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentDailyProgress as $progress)
                        @php
                            $itemTotalQty = (float) ($progress->projectItem->total_quantity ?? 0);
                            $contribPct = $itemTotalQty > 0 ? min(100, ($progress->quantity / $itemTotalQty) * 100) : 0;
                        @endphp
                        <tr>
                            <td class="fw-bold">{{ \Carbon\Carbon::parse($progress->progress_date)->format('Y-m-d') }}</td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="fas fa-user me-1 text-primary"></i>{{ $progress->employee->name ?? 'غير محدد' }}
                                </span>
                            </td>
                            <td class="fw-semibold">{{ $progress->projectItem->workItem->name ?? '-' }}</td>
                            <td class="text-center fw-bold text-primary">
                                {{ number_format($progress->quantity, 2) }}
                                <small class="text-muted">{{ $progress->projectItem->workItem->unit ?? '' }}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $contribPct >= 10 ? 'bg-success' : 'bg-primary' }}">
                                    {{ number_format($contribPct, 1) }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                لا توجد سجلات تقدم يومي مسجلة لهذا المشروع حتى الآن.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Save As Template Modal -->
<div class="modal fade" id="saveAsTemplateModal" tabindex="-1" aria-labelledby="saveAsTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="saveAsTemplateForm" action="{{ route('projects.save-as-template', $project) }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white" id="saveAsTemplateModalLabel">
                        <i class="fas fa-layer-group me-2"></i>{{ __('general.save_as_template') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="template_name" class="form-label fw-semibold">{{ __('general.template_name') }}</label>
                        <input type="text" class="form-control" id="template_name" name="template_name"
                               value="{{ $project->name }} - Template" required>
                    </div>
                    <div class="mb-3">
                        <label for="template_description" class="form-label fw-semibold">{{ __('general.description') }}</label>
                        <textarea class="form-control" id="template_description" name="template_description" rows="3"
                                  placeholder="{{ __('general.template_description_placeholder') }}">{{ $project->description }}</textarea>
                    </div>
                    <div class="alert alert-info py-2 small mb-0">
                        <i class="fas fa-info-circle me-1"></i>{{ __('general.template_save_info') }}
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        {{ __('general.cancel') }}
                    </button>
                    <button type="submit" class="btn btn-primary" id="saveTemplateBtn">
                        <i class="fas fa-save me-1"></i>{{ __('general.save_template') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Overview Bar Chart
    const barCanvas = document.getElementById('detailsProgressChart');
    if (barCanvas && typeof Chart !== 'undefined') {
        const labels = @json($overviewLabels);
        const actualData = @json($overviewActual);
        const remainingData = @json($overviewRemaining);

        new Chart(barCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'المنجز (%)',
                        data: actualData,
                        backgroundColor: 'rgba(33, 60, 122, 0.88)',
                        borderColor: '#213c7a',
                        borderWidth: 1,
                        borderRadius: 6,
                        maxBarThickness: 38
                    },
                    {
                        label: 'المتبقي (%)',
                        data: remainingData,
                        backgroundColor: 'rgba(148, 163, 184, 0.35)',
                        borderColor: '#94a3b8',
                        borderWidth: 1,
                        borderRadius: 6,
                        maxBarThickness: 38
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: ctx => `${ctx.dataset.label}: ${ctx.parsed.y}%`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { callback: v => v + '%' }
                    }
                }
            }
        });
    }

    // 2. Distribution Doughnut Chart
    const donutCanvas = document.getElementById('detailsDonutChart');
    if (donutCanvas && typeof Chart !== 'undefined') {
        new Chart(donutCanvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['مكتمل', 'قيد التنفيذ', 'لم يبدأ'],
                datasets: [{
                    data: [{{ $completedItemsCount }}, {{ $inProgressItemsCount }}, {{ $notStartedItemsCount }}],
                    backgroundColor: ['#16803c', '#213c7a', '#cbd5e1'],
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '66%',
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // 3. Instant Work Items Table Search & Subproject Filter
    const searchInput = document.getElementById('detailsItemSearch');
    const subFilter = document.getElementById('detailsSubprojectFilter');
    const rows = Array.from(document.querySelectorAll('#detailsItemsTable .details-item-row'));
    const badge = document.getElementById('detailsItemsBadge');
    const totalCount = rows.length;

    function filterDetailsTable() {
        const q = (searchInput?.value || '').toLowerCase().trim();
        const sub = (subFilter?.value || '').toLowerCase().trim();
        let visible = 0;

        rows.forEach(row => {
            const matchSub = !sub || (row.dataset.subproject || '') === sub;
            const matchQ = !q || (row.dataset.search || '').includes(q);
            const show = matchSub && matchQ;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (badge) {
            badge.textContent = visible === totalCount
                ? `${totalCount} {{ __('general.items') }}`
                : `${visible} / ${totalCount} {{ __('general.items') }}`;
        }
    }

    searchInput?.addEventListener('input', filterDetailsTable);
    subFilter?.addEventListener('change', filterDetailsTable);

    // 4. Save As Template AJAX Form
    const form = document.getElementById('saveAsTemplateForm');
    const submitBtn = document.getElementById('saveTemplateBtn');
    const modal = document.getElementById('saveAsTemplateModal');

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const templateName = document.getElementById('template_name')?.value?.trim();
            if (!templateName) {
                alert('{{ __('general.please_enter_template_name') }}');
                return;
            }

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>{{ __('general.saving') }}';
            }

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(modal)?.hide();
                    alert('{{ __('general.template_saved_successfully') }}');
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                    }
                } else {
                    alert(data.message || '{{ __('general.unknown_error_occurred') }}');
                }
            })
            .catch(err => {
                alert('{{ __('general.connection_error') }}: ' + err.message);
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>{{ __('general.save_template') }}';
                }
            });
        });
    }
});
</script>
@endpush
