@extends('layouts.print')

@section('title', __('general.progress_report') . ' - ' . $project->name)

@php
    $c = fn(string $key, string $fallback) => $colors[$key] ?? $fallback;
    $asOfLabel = \Carbon\Carbon::parse($asOfDate ?? now())->format('d-M-Y');
@endphp

@section('styles')
<style>
    @page {
        size: A4 landscape;
        margin: 8mm;
    }

    body {
        background: #fff;
        color: #111;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .print-toolbar {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        margin-bottom: 16px;
    }

    .print-report-header {
        text-align: center;
        margin-bottom: 10px;
    }

    .print-report-header h1 {
        font-size: 18px;
        font-weight: 700;
        color: #213c7a;
        margin: 0 0 4px;
    }

    .print-report-header h2 {
        font-size: 14px;
        font-weight: 600;
        color: #121c30;
        margin: 0 0 6px;
    }

    .print-meta {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 4px 16px;
        font-size: 11px;
        color: #374151;
    }

    .progress-table {
        width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
        font-size: 7px;
    }

    .progress-table col.col-sl { width: 2.4%; }
    .progress-table col.col-activity { width: 13%; }
    .progress-table col.col-qty { width: 4%; }
    .progress-table col.col-unit { width: 3%; }
    .progress-table col.col-metric { width: 3.5%; }
    .progress-table col.col-date { width: 5.15%; }

    .progress-table th,
    .progress-table td {
        border: 0.4px solid #111;
        text-align: center;
        vertical-align: middle;
        padding: 2px 1px;
        word-wrap: break-word;
        overflow-wrap: anywhere;
        white-space: normal;
        color: #111;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .progress-table thead {
        display: table-header-group;
    }

    .progress-table tr {
        page-break-inside: avoid;
    }

    .activity-cell {
        text-align: start;
    }

    .progress-table .d-flex {
        display: block;
    }

    .progress-table .badge {
        font-size: 6px;
        border: 0.4px solid #111;
    }

    /* KON Work Plan Print / PDF Palette */
    .progress-table thead tr:first-child th {
        background: #213c7a !important;
        background-color: #213c7a !important;
        color: #ffffff !important;
        border-color: #365294 !important;
        font-weight: 700 !important;
    }
    .progress-table thead th.prev-header { background: #244482 !important; background-color: #244482 !important; color: #ffffff !important; }
    .progress-table thead th.curr-header { background: #16596e !important; background-color: #16596e !important; color: #ffffff !important; }
    .progress-table thead th.comp-header { background: #176647 !important; background-color: #176647 !important; color: #ffffff !important; }
    .progress-table thead th.remaining-header { background: #7c5218 !important; background-color: #7c5218 !important; color: #ffffff !important; }

    .progress-table thead tr:nth-child(2) th {
        background: #2d4f9c !important;
        background-color: #2d4f9c !important;
        color: #ffffff !important;
        border-color: #4a6cc0 !important;
        font-weight: 600 !important;
    }

    .sl-cell, .activity-cell, .quantity-cell, .unit-cell { background: #ffffff !important; background-color: #ffffff !important; }
    .prev-planned, .prev-planned-percent, .prev-actual { background: #f3f6fc !important; background-color: #f3f6fc !important; }
    .curr-planned, .curr-planned-percent, .curr-actual { background: #eef8f9 !important; background-color: #eef8f9 !important; }
    .comp-planned, .comp-planned-percent, .comp-actual { background: #eef8f3 !important; background-color: #eef8f3 !important; }
    .remaining-planned, .remaining-planned-percent, .remaining-cell { background: #fdf7ec !important; background-color: #fdf7ec !important; }
    .start-date-cell, .target-date-cell, .progress-start-date-cell, .progress-end-date-cell {
        background: #f7f8fb !important;
        background-color: #f7f8fb !important;
        color: #36435c !important;
        white-space: nowrap !important;
    }

    .above-expected { background: #e3f3eb !important; background-color: #e3f3eb !important; color: #1b7f55 !important; }
    .below-expected { background: #fbe8e5 !important; background-color: #fbe8e5 !important; color: #bb3428 !important; }

    .subproject-header-row td {
        background-color: #213c7a !important;
        background: #213c7a !important;
        color: #ffffff !important;
        font-weight: 700 !important;
    }

    @media print {
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        .print-toolbar {
            display: none !important;
        }

        .container-fluid {
            padding: 0 !important;
        }

        .progress-table th,
        .progress-table td {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        .progress-table thead tr:first-child th {
            background: #213c7a !important;
            background-color: #213c7a !important;
            color: #ffffff !important;
            border-color: #365294 !important;
        }
        .progress-table thead th.prev-header { background: #244482 !important; background-color: #244482 !important; color: #ffffff !important; }
        .progress-table thead th.curr-header { background: #16596e !important; background-color: #16596e !important; color: #ffffff !important; }
        .progress-table thead th.comp-header { background: #176647 !important; background-color: #176647 !important; color: #ffffff !important; }
        .progress-table thead th.remaining-header { background: #7c5218 !important; background-color: #7c5218 !important; color: #ffffff !important; }

        .progress-table thead tr:nth-child(2) th {
            background: #2d4f9c !important;
            background-color: #2d4f9c !important;
            color: #ffffff !important;
            border-color: #4a6cc0 !important;
        }

        .sl-cell, .activity-cell, .quantity-cell, .unit-cell { background: #ffffff !important; background-color: #ffffff !important; }
        .prev-planned, .prev-planned-percent, .prev-actual { background: #f3f6fc !important; background-color: #f3f6fc !important; }
        .curr-planned, .curr-planned-percent, .curr-actual { background: #eef8f9 !important; background-color: #eef8f9 !important; }
        .comp-planned, .comp-planned-percent, .comp-actual { background: #eef8f3 !important; background-color: #eef8f3 !important; }
        .remaining-planned, .remaining-planned-percent, .remaining-cell { background: #fdf7ec !important; background-color: #fdf7ec !important; }
        .start-date-cell, .target-date-cell, .progress-start-date-cell, .progress-end-date-cell {
            background: #f7f8fb !important;
            background-color: #f7f8fb !important;
            color: #36435c !important;
        }

        .above-expected { background: #e3f3eb !important; background-color: #e3f3eb !important; color: #1b7f55 !important; }
        .below-expected { background: #fbe8e5 !important; background-color: #fbe8e5 !important; color: #bb3428 !important; }

        .subproject-header-row td {
            background-color: #213c7a !important;
            background: #213c7a !important;
            color: #ffffff !important;
            font-weight: 700 !important;
        }
    }
</style>
@endsection

@section('content')
    <div class="print-toolbar no-print">
        <button type="button" class="btn btn-info btn-sm" onclick="window.print()">
            <i class="fas fa-print me-1"></i>{{ __('general.print') }}
        </button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="window.close()">
            <i class="fas fa-times me-1"></i>{{ __('general.close') }}
        </button>
    </div>

    <div class="print-report-header">
        <h1>{{ __('general.progress_report') }}</h1>
        <h2>{{ $project->name }}@if($project->client) — {{ $project->client->name }}@endif</h2>
        <div class="print-meta">
            <span>{{ $asOfLabel }}</span>
            <span>{{ __('general.overall_progress') }}: {{ number_format($projectProgress ?? 0, 2) }}%</span>
            <span>{{ __('general.print_date') }}: {{ now()->format('d-M-Y H:i') }}</span>
            @if(!empty($isGroupedView))
                <span>{{ __('general.group_duplicate_items') }}</span>
            @elseif(!empty($isSubprojectView))
                <span>{{ __('general.group_by_subproject')  }}</span>
            @endif
        </div>
    </div>

    <table class="table table-bordered mb-0 progress-table">
        <colgroup>
            <col class="col-sl">
            <col class="col-activity">
            <col class="col-qty">
            <col class="col-unit">
            @for ($i = 0; $i < 16; $i++)
                <col class="col-metric">
            @endfor
            <col class="col-date">
            <col class="col-date">
            <col class="col-date">
            <col class="col-date">
        </colgroup>
        <thead>
            <tr class="table-header">
                <th rowspan="2" class="sl-header">SL</th>
                <th rowspan="2" class="activity-header">{{ __('general.activity_description') }}</th>
                <th rowspan="2" class="totalQty-header">{{ __('general.total_quantity') }}</th>
                <th rowspan="2" class="unit-header">{{ __('general.unit') }}</th>
                <th colspan="4" class="prev-header">{{ __('general.previous') }}</th>
                <th colspan="4" class="curr-header">
                    {{ __('general.current') }}
                    @if ($isTodayHoliday ?? false)
                        <span class="badge bg-danger ms-2">OFF</span>
                    @endif
                </th>
                <th colspan="4" class="comp-header">{{ __('general.completed') }}</th>
                <th colspan="4" class="remaining-header">{{ __('general.remaining') }}</th>
                <th rowspan="2" class="start-date-header">{{ __('start_date') }}</th>
                <th rowspan="2" class="target-header">{{ __('end_date') }}</th>
                <th rowspan="2" class="progress-start-date-header">Progress Start Date</th>
                <th rowspan="2" class="progress-end-date-header">Progress End Date</th>
            </tr>
            <tr class="table-header">
                <th class="planned-cell">{{ __('general.planned_qty') }}</th>
                <th class="planned-cell">{{ __('general.planned_percent') }}</th>
                <th class="actual-cell">{{ __('general.actual_qty') }}</th>
                <th class="actual-cell">{{ __('general.actual_percent') }}</th>
                <th class="planned-cell">{{ __('general.planned_qty') }}</th>
                <th class="planned-cell">{{ __('general.planned_percent') }}</th>
                <th class="actual-cell">{{ __('general.actual_qty') }}</th>
                <th class="actual-cell">{{ __('general.actual_percent') }}</th>
                <th class="planned-cell">{{ __('general.planned_qty') }}</th>
                <th class="planned-cell">{{ __('general.planned_percent') }}</th>
                <th class="actual-cell">{{ __('general.actual_qty') }}</th>
                <th class="actual-cell">{{ __('general.actual_percent') }}</th>
                <th class="planned-cell">{{ __('general.planned_qty') }}</th>
                <th class="planned-cell">{{ __('general.planned_percent') }}</th>
                <th class="remaining-cell">{{ __('general.qty_remaining') }}</th>
                <th class="remaining-cell">{{ __('general.percent_remaining') }}</th>
            </tr>
        </thead>
        <tbody>
            @if(!empty($isSubprojectView))
                @foreach (($groupedBySubproject ?? collect()) as $subGroup)
                    <tr style="background-color: #1e3a5f; color: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                        <td colspan="2" style="font-weight:700; text-align:start; padding-left:6px; background:#1e3a5f; color:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact;">
                            {{ $subGroup->subproject_name }}
                            <span style="font-size:6px; margin-left:4px;">({{ $subGroup->items_count }} {{ __('general.items') }})</span>
                        </td>
                        <td style="font-weight:700; background:#1e3a5f; color:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact;">{{ number_format($subGroup->total_quantity, 2) }}</td>
                        <td style="background:#1e3a5f;"></td>
                        <td style="font-weight:700; background:#1e3a5f; color:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact;">{{ number_format($subGroup->planned_until_from_date, 2) }}</td>
                        <td style="font-weight:700; background:#1e3a5f; color:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact;">{{ $subGroup->planned_until_from_date_percentage }}%</td>
                        <td style="font-weight:700; background:#1e3a5f; color:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact;">{{ number_format($subGroup->previous_progress, 2) }}</td>
                        <td style="font-weight:700; background:#1e3a5f; color:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact;">{{ $subGroup->previous_progress_percentage }}%</td>
                        <td style="font-weight:700; background:#1e3a5f; color:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact;">{{ number_format($subGroup->planned_today, 2) }}</td>
                        <td style="font-weight:700; background:#1e3a5f; color:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact;">{{ $subGroup->planned_today_percentage }}%</td>
                        <td style="font-weight:700; background:#1e3a5f; color:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact;">{{ number_format($subGroup->current_progress, 2) }}</td>
                        <td style="font-weight:700; background:#1e3a5f; color:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact;">{{ $subGroup->current_progress_percentage }}%</td>
                        <td style="font-weight:700; background:#1e3a5f; color:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact;">{{ number_format($subGroup->planned_total_quantity, 2) }}</td>
                        <td style="font-weight:700; background:#1e3a5f; color:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact;">{{ $subGroup->planned_total_percentage }}%</td>
                        <td style="font-weight:700; background:#1e3a5f; color:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact;">{{ number_format($subGroup->total_completed, 2) }}</td>
                        <td style="font-weight:700; background:#1e3a5f; color:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact;">{{ $subGroup->total_completed_percentage }}%</td>
                        <td style="font-weight:700; background:#1e3a5f; color:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact;">{{ number_format($subGroup->remaining_planned, 2) }}</td>
                        <td style="font-weight:700; background:#1e3a5f; color:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact;">{{ $subGroup->remaining_planned_percentage }}%</td>
                        <td style="font-weight:700; background:#1e3a5f; color:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact;">{{ number_format($subGroup->remaining, 2) }}</td>
                        <td style="font-weight:700; background:#1e3a5f; color:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact;">{{ $subGroup->remaining_percentage }}%</td>
                        <td colspan="4" style="background:#1e3a5f;"></td>
                    </tr>
                    @foreach ($subGroup->items as $index => $item)
                        @include('projects.partials.progress-item-row', [
                            'item'          => $item,
                            'index'         => $index,
                            'showExtraInfo' => !empty($showNotes),
                        ])
                    @endforeach
                @endforeach
            @else
                @forelse ($printItems as $index => $item)
                    @include('projects.partials.progress-item-row', [
                        'item'          => $item,
                        'index'         => $index,
                        'showExtraInfo' => !empty($showNotes),
                    ])
                @empty
                    <tr>
                        <td colspan="24">{{ __('general.no_data') }}</td>
                    </tr>
                @endforelse
            @endif
        </tbody>
    </table>
@endsection
