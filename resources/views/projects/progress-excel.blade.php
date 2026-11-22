<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>{{ __('general.progress_report') }} - {{ $project->name }}</title>
    <style>
        table {
            border-collapse: collapse;
            font-family: Arial, Tahoma, 'Segoe UI', sans-serif;
            font-size: 9pt;
            width: 100%;
        }
        th, td {
            border: 1px solid #475569;
            padding: 5px 6px;
            vertical-align: middle;
            text-align: center;
        }
        .text-start { text-align: left; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }

        .report-title {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            padding: 8px;
            background-color: #213c7a;
            color: #ffffff;
            border: 1px solid #121c30;
        }
        .report-subtitle {
            font-size: 11pt;
            font-weight: bold;
            text-align: center;
            padding: 6px;
            background-color: #eaeff9;
            color: #121c30;
            border: 1px solid #cdd5e1;
        }
        .report-meta {
            font-size: 9pt;
            text-align: center;
            padding: 4px;
            color: #36435c;
            background-color: #f7f8fb;
            border: 1px solid #e2e7ee;
        }

        /* 2-tier Table Headers — KON Work Plan Palette */
        .sl-header,
        .activity-header,
        .totalQty-header,
        .unit-header,
        .start-date-header,
        .target-header,
        .progress-start-date-header,
        .progress-end-date-header {
            background-color: #213c7a;
            font-weight: bold;
            color: #ffffff;
        }
        .activity-header { text-align: start; }
        .prev-header { background-color: #244482; color: #ffffff; font-weight: bold; }
        .curr-header { background-color: #16596e; color: #ffffff; font-weight: bold; }
        .comp-header { background-color: #176647; color: #ffffff; font-weight: bold; }
        .remaining-header { background-color: #7c5218; color: #ffffff; font-weight: bold; }

        .sub-planned,
        .sub-actual,
        .sub-remaining {
            background-color: #2d4f9c;
            font-weight: bold;
            color: #ffffff;
        }

        /* Subproject Header Row */
        .subproject-header-row td {
            background-color: #213c7a !important;
            color: #ffffff !important;
            font-weight: bold;
            border: 1px solid #121c30;
        }

        /* Data cells — clean KON surfaces with subtle group distinction */
        .sl-cell { background-color: #ffffff; color: #121c30; }
        .activity-cell { background-color: #ffffff; color: #121c30; text-align: left; }
        .quantity-cell { background-color: #ffffff; color: #121c30; mso-number-format:"\#\,\#\#0\.00"; }
        .unit-cell { background-color: #ffffff; color: #36435c; }
        .planned-cell { background-color: #f3f6fc; color: #121c30; mso-number-format:"\#\,\#\#0\.00"; }
        .planned-percent-cell { background-color: #f3f6fc; color: #121c30; }
        .actual-cell { background-color: #eef8f3; color: #121c30; mso-number-format:"\#\,\#\#0\.00"; }
        .actual-percent-cell { background-color: #eef8f3; color: #121c30; }
        .remaining-planned-cell { background-color: #fdf7ec; color: #121c30; mso-number-format:"\#\,\#\#0\.00"; }
        .remaining-planned-percent-cell { background-color: #fdf7ec; color: #121c30; }
        .remaining-cell { background-color: #fdf7ec; color: #121c30; mso-number-format:"\#\,\#\#0\.00"; }
        .remaining-percent-cell { background-color: #fdf7ec; color: #121c30; }
        .start-date-cell,
        .target-date-cell,
        .progress-start-date-cell,
        .progress-end-date-cell {
            background-color: #f7f8fb;
            color: #36435c;
        }

        .measurable-check {
            color: #1b7f55;
            font-weight: bold;
            margin-left: 4px;
        }
        .below-expected { background-color: #fbe8e5 !important; color: #bb3428; }
        .above-expected { background-color: #e3f3eb !important; color: #1b7f55; }
    </style>
</head>
<body>
    <table>
        <!-- Report Header Block -->
        <tr>
            <th colspan="24" class="report-title">
                {{ __('general.progress_report') }} — {{ $project->name }}
            </th>
        </tr>
        <tr>
            <th colspan="24" class="report-subtitle">
                @if($project->client){{ __('projects.client') }}: {{ $project->client->name }} | @endif
                {{ __('general.overall_progress') }}: {{ number_format($projectProgress ?? 0, 2) }}% |
                عرض التقدم حتى: {{ \Carbon\Carbon::parse($asOfDate ?? now())->format('d-M-Y') }}
            </th>
        </tr>
        <tr>
            <td colspan="24" class="report-meta">
                {{ __('general.print_date') }}: {{ now()->format('d-M-Y H:i') }}
            </td>
        </tr>
        <tr>
            <td colspan="24" style="height: 10px; border: none; background: #fff;"></td>
        </tr>

        <!-- Table 2-Tier Header -->
        <thead>
            <tr>
                <th rowspan="2" class="sl-header">SL</th>
                <th rowspan="2" class="activity-header">{{ __('general.activity_description') }}</th>
                <th rowspan="2" class="totalQty-header">{{ __('general.total_quantity') }}</th>
                <th rowspan="2" class="unit-header">{{ __('general.unit') }}</th>
                <th colspan="4" class="prev-header">{{ __('general.previous') }}</th>
                <th colspan="4" class="curr-header">{{ __('general.current') }}</th>
                <th colspan="4" class="comp-header">{{ __('general.completed') }}</th>
                <th colspan="4" class="remaining-header">{{ __('general.remaining') }}</th>
                <th rowspan="2" class="start-date-header">{{ __('start_date') }}</th>
                <th rowspan="2" class="target-header">{{ __('end_date') }}</th>
                <th rowspan="2" class="progress-start-date-header">Progress Start Date</th>
                <th rowspan="2" class="progress-end-date-header">Progress End Date</th>
            </tr>
            <tr>
                <!-- Previous -->
                <th class="sub-planned">{{ __('general.planned_qty') }}</th>
                <th class="sub-planned">{{ __('general.planned_percent') }}</th>
                <th class="sub-actual">{{ __('general.actual_qty') }}</th>
                <th class="sub-actual">{{ __('general.actual_percent') }}</th>

                <!-- Current -->
                <th class="sub-planned">{{ __('general.planned_qty') }}</th>
                <th class="sub-planned">{{ __('general.planned_percent') }}</th>
                <th class="sub-actual">{{ __('general.actual_qty') }}</th>
                <th class="sub-actual">{{ __('general.actual_percent') }}</th>

                <!-- Completed -->
                <th class="sub-planned">{{ __('general.planned_qty') }}</th>
                <th class="sub-planned">{{ __('general.planned_percent') }}</th>
                <th class="sub-actual">{{ __('general.actual_qty') }}</th>
                <th class="sub-actual">{{ __('general.actual_percent') }}</th>

                <!-- Remaining -->
                <th class="sub-planned">{{ __('general.planned_qty') }}</th>
                <th class="sub-planned">{{ __('general.planned_percent') }}</th>
                <th class="sub-remaining">{{ __('general.qty_remaining') }}</th>
                <th class="sub-remaining">{{ __('general.percent_remaining') }}</th>
            </tr>
        </thead>
        <tbody>
            @if(!empty($isSubprojectView))
                {{-- Grouped by Subproject View --}}
                @foreach (($groupedBySubproject ?? collect()) as $subGroup)
                    <!-- Subproject Header Row -->
                    <tr class="subproject-header-row">
                        <td colspan="2" class="text-start fw-bold" style="background-color: #1e3a5f; color: #ffffff;">
                            {{ $subGroup->subproject_name }} ({{ $subGroup->items_count }} {{ __('general.items') }})
                        </td>
                        <td class="fw-bold" style="background-color: #1e3a5f; color: #ffffff;">{{ number_format($subGroup->total_quantity, 2) }}</td>
                        <td style="background-color: #1e3a5f; color: #ffffff;"></td>
                        <!-- Previous -->
                        <td class="fw-bold" style="background-color: #1e3a5f; color: #ffffff;">{{ number_format($subGroup->planned_until_from_date, 2) }}</td>
                        <td class="fw-bold" style="background-color: #1e3a5f; color: #ffffff;">{{ $subGroup->planned_until_from_date_percentage }}%</td>
                        <td class="fw-bold" style="background-color: #1e3a5f; color: #ffffff;">{{ number_format($subGroup->previous_progress, 2) }}</td>
                        <td class="fw-bold" style="background-color: #1e3a5f; color: #ffffff;">{{ $subGroup->previous_progress_percentage }}%</td>
                        <!-- Current -->
                        <td class="fw-bold" style="background-color: #1e3a5f; color: #ffffff;">{{ number_format($subGroup->planned_today, 2) }}</td>
                        <td class="fw-bold" style="background-color: #1e3a5f; color: #ffffff;">{{ $subGroup->planned_today_percentage }}%</td>
                        <td class="fw-bold" style="background-color: #1e3a5f; color: #ffffff;">{{ number_format($subGroup->current_progress, 2) }}</td>
                        <td class="fw-bold" style="background-color: #1e3a5f; color: #ffffff;">{{ $subGroup->current_progress_percentage }}%</td>
                        <!-- Completed -->
                        <td class="fw-bold" style="background-color: #1e3a5f; color: #ffffff;">{{ number_format($subGroup->planned_total_quantity, 2) }}</td>
                        <td class="fw-bold" style="background-color: #1e3a5f; color: #ffffff;">{{ $subGroup->planned_total_percentage }}%</td>
                        <td class="fw-bold" style="background-color: #1e3a5f; color: #ffffff;">{{ number_format($subGroup->total_completed, 2) }}</td>
                        <td class="fw-bold" style="background-color: #1e3a5f; color: #ffffff;">{{ $subGroup->total_completed_percentage }}%</td>
                        <!-- Remaining -->
                        <td class="fw-bold" style="background-color: #1e3a5f; color: #ffffff;">{{ number_format($subGroup->remaining_planned, 2) }}</td>
                        <td class="fw-bold" style="background-color: #1e3a5f; color: #ffffff;">{{ $subGroup->remaining_planned_percentage }}%</td>
                        <td class="fw-bold" style="background-color: #1e3a5f; color: #ffffff;">{{ number_format($subGroup->remaining, 2) }}</td>
                        <td class="fw-bold" style="background-color: #1e3a5f; color: #ffffff;">{{ $subGroup->remaining_percentage }}%</td>
                        <td colspan="4" style="background-color: #1e3a5f; color: #ffffff;"></td>
                    </tr>

                    <!-- Items Under Subproject -->
                    @foreach ($subGroup->items as $index => $item)
                        <tr>
                            <td class="sl-cell">{{ $index + 1 }}</td>
                            <td class="activity-cell">
                                {{ $item->workItem->name ?? '' }}
                                @if(!empty($item->is_measurable))
                                    <span class="measurable-check">&#10004;</span>
                                @endif
                                @if(!empty($item->notes))
                                    <br><small style="color: #0284c7;">({{ $item->notes }})</small>
                                @endif
                            </td>
                            <td class="quantity-cell">{{ number_format($item->total_quantity, 2) }}</td>
                            <td class="unit-cell">{{ strtoupper($item->workItem->unit ?? '') }}</td>

                            <!-- Previous -->
                            <td class="planned-cell">{{ number_format($item->planned_until_from_date, 2) }}</td>
                            <td class="planned-percent-cell">{{ $item->planned_until_from_date_percentage }}%</td>
                            <td class="actual-cell {{ $item->previous_qty_class }}">{{ number_format($item->previous_progress, 2) }}</td>
                            <td class="actual-percent-cell {{ $item->previous_qty_class }}">{{ $item->previous_progress_percentage }}%</td>

                            <!-- Current -->
                            <td class="planned-cell">{{ number_format($item->planned_today, 2) }}</td>
                            <td class="planned-percent-cell">{{ $item->planned_today_percentage }}%</td>
                            <td class="actual-cell {{ $item->current_qty_class }}">{{ number_format($item->current_progress, 2) }}</td>
                            <td class="actual-percent-cell {{ $item->current_qty_class }}">{{ $item->current_progress_percentage }}%</td>

                            <!-- Completed -->
                            <td class="planned-cell">{{ number_format($item->planned_total_quantity, 2) }}</td>
                            <td class="planned-percent-cell">{{ $item->planned_total_percentage }}%</td>
                            <td class="actual-cell {{ $item->completed_qty_class }}">{{ number_format($item->total_completed, 2) }}</td>
                            <td class="actual-percent-cell {{ $item->completed_qty_class }}">{{ $item->total_completed_percentage }}%</td>

                            <!-- Remaining -->
                            <td class="remaining-planned-cell">{{ number_format($item->remaining_planned ?? 0, 2) }}</td>
                            <td class="remaining-planned-percent-cell">{{ $item->remaining_planned_percentage ?? 0 }}%</td>
                            <td class="remaining-cell {{ $item->remaining_qty_class }}">{{ number_format($item->remaining, 2) }}</td>
                            <td class="remaining-percent-cell {{ $item->remaining_qty_class }}">{{ $item->remaining_percentage }}%</td>

                            <!-- Dates -->
                            <td class="start-date-cell">{{ $item->start_date ? \Carbon\Carbon::parse($item->start_date)->format('d-M-y') : __('general.na') }}</td>
                            <td class="target-date-cell">{{ $item->end_date ? \Carbon\Carbon::parse($item->end_date)->format('d-M-y') : (!empty($item->planned_end_date) ? \Carbon\Carbon::parse($item->planned_end_date)->format('d-M-y') : __('general.na')) }}</td>
                            <td class="progress-start-date-cell">{{ $item->progress_start_date ? \Carbon\Carbon::parse($item->progress_start_date)->format('d-M-y') : __('general.na') }}</td>
                            <td class="progress-end-date-cell">{{ $item->progress_end_date ? \Carbon\Carbon::parse($item->progress_end_date)->format('d-M-y') : __('general.na') }}</td>
                        </tr>
                    @endforeach
                @endforeach
            @elseif(!empty($isGroupedView))
                {{-- Grouped Duplicate Items View --}}
                @foreach (($groupedItems ?? collect()) as $index => $item)
                    <tr>
                        <td class="sl-cell">{{ $index + 1 }}</td>
                        <td class="activity-cell">
                            {{ $item->workItem->name ?? '' }}
                            @if(!empty($item->is_measurable))
                                <span class="measurable-check">&#10004;</span>
                            @endif
                            @if(!empty($item->is_grouped) && ($item->items_count ?? 1) > 1)
                                <small style="color: #64748b;">({{ $item->items_count }} {{ __('general.grouped') }})</small>
                            @endif
                        </td>
                        <td class="quantity-cell">{{ number_format($item->total_quantity, 2) }}</td>
                        <td class="unit-cell">{{ strtoupper($item->workItem->unit ?? '') }}</td>

                        <!-- Previous -->
                        <td class="planned-cell">{{ number_format($item->planned_until_from_date, 2) }}</td>
                        <td class="planned-percent-cell">{{ $item->planned_until_from_date_percentage }}%</td>
                        <td class="actual-cell {{ $item->previous_qty_class }}">{{ number_format($item->previous_progress, 2) }}</td>
                        <td class="actual-percent-cell {{ $item->previous_qty_class }}">{{ $item->previous_progress_percentage }}%</td>

                        <!-- Current -->
                        <td class="planned-cell">{{ number_format($item->planned_today, 2) }}</td>
                        <td class="planned-percent-cell">{{ $item->planned_today_percentage }}%</td>
                        <td class="actual-cell {{ $item->current_qty_class }}">{{ number_format($item->current_progress, 2) }}</td>
                        <td class="actual-percent-cell {{ $item->current_qty_class }}">{{ $item->current_progress_percentage }}%</td>

                        <!-- Completed -->
                        <td class="planned-cell">{{ number_format($item->planned_total_quantity, 2) }}</td>
                        <td class="planned-percent-cell">{{ $item->planned_total_percentage }}%</td>
                        <td class="actual-cell {{ $item->completed_qty_class }}">{{ number_format($item->total_completed, 2) }}</td>
                        <td class="actual-percent-cell {{ $item->completed_qty_class }}">{{ $item->total_completed_percentage }}%</td>

                        <!-- Remaining -->
                        <td class="remaining-planned-cell">{{ number_format($item->remaining_planned ?? 0, 2) }}</td>
                        <td class="remaining-planned-percent-cell">{{ $item->remaining_planned_percentage ?? 0 }}%</td>
                        <td class="remaining-cell {{ $item->remaining_qty_class }}">{{ number_format($item->remaining, 2) }}</td>
                        <td class="remaining-percent-cell {{ $item->remaining_qty_class }}">{{ $item->remaining_percentage }}%</td>

                        <!-- Dates -->
                        <td class="start-date-cell">{{ $item->start_date ? \Carbon\Carbon::parse($item->start_date)->format('d-M-y') : __('general.na') }}</td>
                        <td class="target-date-cell">{{ $item->end_date ? \Carbon\Carbon::parse($item->end_date)->format('d-M-y') : (!empty($item->planned_end_date) ? \Carbon\Carbon::parse($item->planned_end_date)->format('d-M-y') : __('general.na')) }}</td>
                        <td class="progress-start-date-cell">{{ $item->progress_start_date ? \Carbon\Carbon::parse($item->progress_start_date)->format('d-M-y') : __('general.na') }}</td>
                        <td class="progress-end-date-cell">{{ $item->progress_end_date ? \Carbon\Carbon::parse($item->progress_end_date)->format('d-M-y') : __('general.na') }}</td>
                    </tr>
                @endforeach
            @else
                {{-- Individual Items View --}}
                @foreach ($project->items as $index => $item)
                    <tr>
                        <td class="sl-cell">{{ $index + 1 }}</td>
                        <td class="activity-cell">
                            {{ $item->workItem->name ?? '' }}
                            @if(!empty($item->is_measurable))
                                <span class="measurable-check">&#10004;</span>
                            @endif
                            @if(!empty($item->subproject_name) || !empty($item->notes))
                                <br>
                                @if(!empty($item->subproject_name))
                                    <small style="color: #64748b;">[{{ $item->subproject_name }}]</small>
                                @endif
                                @if(!empty($item->notes))
                                    <small style="color: #0284c7;">({{ $item->notes }})</small>
                                @endif
                            @endif
                        </td>
                        <td class="quantity-cell">{{ number_format($item->total_quantity, 2) }}</td>
                        <td class="unit-cell">{{ strtoupper($item->workItem->unit ?? '') }}</td>

                        <!-- Previous -->
                        <td class="planned-cell">{{ number_format($item->planned_until_from_date, 2) }}</td>
                        <td class="planned-percent-cell">{{ $item->planned_until_from_date_percentage }}%</td>
                        <td class="actual-cell {{ $item->previous_qty_class }}">{{ number_format($item->previous_progress, 2) }}</td>
                        <td class="actual-percent-cell {{ $item->previous_qty_class }}">{{ $item->previous_progress_percentage }}%</td>

                        <!-- Current -->
                        <td class="planned-cell">{{ number_format($item->planned_today, 2) }}</td>
                        <td class="planned-percent-cell">{{ $item->planned_today_percentage }}%</td>
                        <td class="actual-cell {{ $item->current_qty_class }}">{{ number_format($item->current_progress, 2) }}</td>
                        <td class="actual-percent-cell {{ $item->current_qty_class }}">{{ $item->current_progress_percentage }}%</td>

                        <!-- Completed -->
                        <td class="planned-cell">{{ number_format($item->planned_total_quantity, 2) }}</td>
                        <td class="planned-percent-cell">{{ $item->planned_total_percentage }}%</td>
                        <td class="actual-cell {{ $item->completed_qty_class }}">{{ number_format($item->total_completed, 2) }}</td>
                        <td class="actual-percent-cell {{ $item->completed_qty_class }}">{{ $item->total_completed_percentage }}%</td>

                        <!-- Remaining -->
                        <td class="remaining-planned-cell">{{ number_format($item->remaining_planned ?? 0, 2) }}</td>
                        <td class="remaining-planned-percent-cell">{{ $item->remaining_planned_percentage ?? 0 }}%</td>
                        <td class="remaining-cell {{ $item->remaining_qty_class }}">{{ number_format($item->remaining, 2) }}</td>
                        <td class="remaining-percent-cell {{ $item->remaining_qty_class }}">{{ $item->remaining_percentage }}%</td>

                        <!-- Dates -->
                        <td class="start-date-cell">{{ $item->start_date ? \Carbon\Carbon::parse($item->start_date)->format('d-M-y') : __('general.na') }}</td>
                        <td class="target-date-cell">{{ $item->end_date ? \Carbon\Carbon::parse($item->end_date)->format('d-M-y') : (!empty($item->planned_end_date) ? \Carbon\Carbon::parse($item->planned_end_date)->format('d-M-y') : __('general.na')) }}</td>
                        <td class="progress-start-date-cell">{{ $item->progress_start_date ? \Carbon\Carbon::parse($item->progress_start_date)->format('d-M-y') : __('general.na') }}</td>
                        <td class="progress-end-date-cell">{{ $item->progress_end_date ? \Carbon\Carbon::parse($item->progress_end_date)->format('d-M-y') : __('general.na') }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
</body>
</html>
