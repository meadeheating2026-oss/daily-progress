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
@endsection
@section('title', __('general.progress_report') . ' - ' . $project->name)

@section('content')
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <div>
                <h2 class="mb-1">{{ __('general.progress_report') }}</h2>
                <h5 class="text-muted">
                    {{ $project->name }} - 
                    @if(isset($asOfDate) && $asOfDate != \Carbon\Carbon::today()->format('Y-m-d'))
                        عرض التقدم حتى: {{ \Carbon\Carbon::parse($asOfDate)->format('d-M-Y') }}
                    @else
                        {{ \Carbon\Carbon::now()->format('d-M-Y') }}
                    @endif
                </h5>
            </div>
            <div>
                <button type="button" id="printProgressBtn" class="btn btn-info me-2">
                    <i class="fas fa-print me-2"></i>{{ __('general.print') }}
                </button>
                <a href="{{ route('projects.progress.export', ['project' => $project->id, 'from_date' => $fromDate, 'to_date' => $toDate, 'as_of_date' => request('as_of_date', \Carbon\Carbon::today()->format('Y-m-d'))]) }}"
                    id="exportExcelBtn"
                    class="btn btn-success me-2">
                    <i class="fas fa-file-excel me-2"></i>{{ __('general.export_excel') }}
                </a>
                <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>{{ __('general.back') }}
                </a>
            </div>
        </div>

        
        <form method="GET" action="{{ route('projects.progress', $project->id) }}"
            class="row g-3 align-items-end mb-4 no-print">
            
            
            <input type="hidden" name="from_date" value="{{ request('from_date', $fromDate) }}">
            <input type="hidden" name="to_date" value="{{ request('to_date', $toDate) }}">

            
            <div class="col-md-2">
                <label class="form-label fw-semibold">
                    <i class="fas fa-calendar-alt me-1"></i>عرض التقدم حتى تاريخ
                </label>
                <input type="date" name="as_of_date" class="form-control" 
                       value="{{ request('as_of_date', \Carbon\Carbon::today()->format('Y-m-d')) }}"
                       title="اختر التاريخ الذي تريد عرض التقدم عنده (افتراضي: اليوم)">
            </div>

            
            <div class="col-md-2">
                <label class="form-label fw-semibold">{{ __('general.category') }}</label>
                <select name="category_id" class="form-select">
                    <option value="">{{ __('general.all') }}</option>
                    @foreach($categories ?? [] as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            
            <div class="col-md-2">
                <label class="form-label fw-semibold">المشروع الفرعي</label>
                <select name="subproject_name" class="form-select">
                    <option value="">{{ __('general.all') }}</option>
                    @foreach($subprojects ?? [] as $subproject)
                        <option value="{{ $subproject->name }}" {{ request('subproject_name') == $subproject->name ? 'selected' : '' }}>
                            {{ $subproject->name }}
                        </option>
                    @endforeach
                    <option value="null" {{ request('subproject_name') == 'null' ? 'selected' : '' }}>بدون مشروع فرعي</option>
                </select>
            </div>

            
            <div class="col-md-2">
                <label class="form-label fw-semibold">{{ __('start_date') }} من</label>
                <input type="date" name="start_date_from" class="form-control" 
                       value="{{ request('start_date_from') }}">
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold">{{ __('start_date') }} إلى</label>
                <input type="date" name="start_date_to" class="form-control" 
                       value="{{ request('start_date_to') }}">
            </div>

            
            <div class="col-md-2">
                <label class="form-label fw-semibold">{{ __('end_date') }} من</label>
                <input type="date" name="end_date_from" class="form-control" 
                       value="{{ request('end_date_from') }}">
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold">{{ __('end_date') }} إلى</label>
                <input type="date" name="end_date_to" class="form-control" 
                       value="{{ request('end_date_to') }}">
            </div>

            
            <div class="col-md-2">
                <label class="form-label fw-semibold">أداء المتبقي</label>
                <select name="remaining_performance" class="form-select">
                    <option value="">{{ __('general.all') }}</option>
                    <option value="below" {{ request('remaining_performance') == 'above' ? 'selected' : '' }}>أقل من المتوقع</option>
                    <option value="equal" {{ request('remaining_performance') == 'equal' ? 'selected' : '' }}>متساوي</option>
                    <option value="above" {{ request('remaining_performance') == 'below' ? 'selected' : '' }}>أكبر من المتوقع</option>
                </select>
            </div>

            
            <div class="col-md-2">
                <label class="form-label fw-semibold">{{ __('general.completed') }}</label>
                <select name="completed_status" class="form-select">
                    <option value="">{{ __('general.all') }}</option>
                    <option value="completed" {{ request('completed_status') == 'completed' ? 'selected' : '' }}>مكتمل</option>
                    <option value="in_progress" {{ request('completed_status') == 'in_progress' ? 'selected' : '' }}>قيد التنفيذ</option>
                    <option value="not_started" {{ request('completed_status') == 'not_started' ? 'selected' : '' }}>لم يبدأ</option>
                </select>
            </div>

            
            <div class="col-md-2">
                <label class="form-label fw-semibold">{{ __('general.search') }}</label>
                <input type="text" name="search" class="form-control" 
                       placeholder="{{ __('general.item_name') }}" 
                       value="{{ request('search') }}">
            </div>

            
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-1"></i>{{ __('general.filter') }}
                </button>
                <a href="{{ route('projects.progress', $project->id) }}" class="btn btn-secondary">
                    <i class="fas fa-undo me-1"></i>{{ __('general.reset') }}
                </a>
            </div>
        </form>

        
        <div class="mb-3 no-print d-flex flex-wrap gap-2">
            <button type="button" id="toggleSubprojectNotes" class="btn btn-outline-info">
                <i class="fas fa-eye me-1"></i>Show items by subproject and notes
            </button>
            <button type="button" id="toggleGroupedDuplicates" class="btn btn-outline-primary">
                <i class="fas fa-layer-group me-1"></i>
                <span id="toggleGroupedDuplicatesText">{{ __('general.group_duplicate_items') }}</span>
            </button>
            <button type="button" id="toggleSubprojectView" class="btn btn-outline-success">
                <i class="fas fa-sitemap me-1"></i>
                <span id="toggleSubprojectViewText">{{ __('general.group_by_subproject')  }}</span>
            </button>
        </div>



  

        
        <div class="print-only print-report-header">
            <h3>{{ __('general.progress_report') }}</h3>
            <h4>{{ $project->name }}@if($project->client) — {{ $project->client->name }}@endif</h4>
            <div class="print-meta">
                <span>{{ \Carbon\Carbon::parse($asOfDate ?? now())->format('d-M-Y') }}</span>
                <span>{{ __('general.overall_progress') }}: {{ number_format($projectProgress ?? 0, 2) }}%</span>
                <span>{{ __('general.print_date') }}: {{ now()->format('d-M-Y H:i') }}</span>
                <span id="printViewModeLabel"></span>
            </div>
        </div>



        
        <div class="card printable-area">
            <div class="card-body p-0">
                <div class="table-responsive">
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
                                        <span class="badge bg-danger ms-2" style="font-size: 0.75rem;">OFF</span>
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
                        <tbody id="progressIndividualRows">
                            @foreach ($project->items as $index => $item)
                                @include('projects.partials.progress-item-row', ['item' => $item, 'index' => $index])
                            @endforeach
                        </tbody>
                        <tbody id="progressGroupedRows" class="is-print-hidden" style="display: none;">
                            @foreach (($groupedItems ?? collect()) as $index => $item)
                                @include('projects.partials.progress-item-row', ['item' => $item, 'index' => $index])
                            @endforeach
                        </tbody>
                        <tbody id="progressSubprojectRows" class="is-print-hidden" style="display: none;">
                            @foreach (($groupedBySubproject ?? collect()) as $subGroup)
                                {{-- Subproject header row --}}
                                <tr class="subproject-header-row" style="background-color: #1e3a5f; color: #fff;">
                                    <td colspan="2" class="fw-bold text-start ps-3" style="font-size: 0.9rem;">
                                        <i class="fas fa-sitemap me-2"></i>{{ $subGroup->subproject_name }}
                                        <span class="badge bg-light text-dark ms-2">{{ $subGroup->items_count }} {{ __('general.items') }}</span>
                                    </td>
                                    <td class="fw-bold text-center">{{ number_format($subGroup->total_quantity, 2) }}</td>
                                    <td></td>
                                    <td class="planned-cell fw-bold">{{ number_format($subGroup->planned_until_from_date, 2) }}</td>
                                    <td class="planned-cell fw-bold">{{ $subGroup->planned_until_from_date_percentage }}%</td>
                                    <td class="actual-cell fw-bold {{ $subGroup->previous_qty_class }}">{{ number_format($subGroup->previous_progress, 2) }}</td>
                                    <td class="actual-cell fw-bold {{ $subGroup->previous_qty_class }}">{{ $subGroup->previous_progress_percentage }}%</td>
                                    <td class="planned-cell fw-bold">{{ number_format($subGroup->planned_today, 2) }}</td>
                                    <td class="planned-cell fw-bold">{{ $subGroup->planned_today_percentage }}%</td>
                                    <td class="actual-cell fw-bold {{ $subGroup->current_qty_class }}">{{ number_format($subGroup->current_progress, 2) }}</td>
                                    <td class="actual-cell fw-bold {{ $subGroup->current_qty_class }}">{{ $subGroup->current_progress_percentage }}%</td>
                                    <td class="planned-cell fw-bold">{{ number_format($subGroup->planned_total_quantity, 2) }}</td>
                                    <td class="planned-cell fw-bold">{{ $subGroup->planned_total_percentage }}%</td>
                                    <td class="actual-cell fw-bold {{ $subGroup->completed_qty_class }}">{{ number_format($subGroup->total_completed, 2) }}</td>
                                    <td class="actual-cell fw-bold {{ $subGroup->completed_qty_class }}">{{ $subGroup->total_completed_percentage }}%</td>
                                    <td class="planned-cell fw-bold">{{ number_format($subGroup->remaining_planned, 2) }}</td>
                                    <td class="planned-cell fw-bold">{{ $subGroup->remaining_planned_percentage }}%</td>
                                    <td class="remaining-cell fw-bold {{ $subGroup->remaining_qty_class }}">{{ number_format($subGroup->remaining, 2) }}</td>
                                    <td class="remaining-cell fw-bold {{ $subGroup->remaining_qty_class }}">{{ $subGroup->remaining_percentage }}%</td>
                                    <td colspan="4"></td>
                                </tr>
                                {{-- Items under this subproject --}}
                                @foreach ($subGroup->items as $index => $item)
                                    @include('projects.partials.progress-item-row', ['item' => $item, 'index' => $index])
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        
        <div class="no-print mb-4 p-3 border rounded">
            <h5>{{ __('general.customize_column_colors') }}</h5>
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted">{{ __('general.header_colors') }}</h6>
                    <table class="table table-borderless table-sm">
                        <tbody>
                            <tr>
                                <td><label>{{ __('general.sl_header') }}</label></td>
                                <td><input type="color" id="slHeaderColor" value="#fde68a"
                                        class="form-control form-control-color p-0"></td>
                                <td><label>{{ __('general.activity_header') }}</label></td>
                                <td><input type="color" id="activityHeaderColor" value="#fcd34d"
                                        class="form-control form-control-color p-0"></td>
                            </tr>
                            <tr>
                                <td><label>{{ __('general.previous_header') }}</label></td>
                                <td><input type="color" id="prevHeaderColor" value="#60a5fa"
                                        class="form-control form-control-color p-0"></td>
                                <td><label>{{ __('general.current_header') }}</label></td>
                                <td><input type="color" id="currHeaderColor" value="#a78bfa"
                                        class="form-control form-control-color p-0"></td>
                            </tr>
                            <tr>
                                <td><label>{{ __('general.completed_header') }}</label></td>
                                <td><input type="color" id="compHeaderColor" value="#fbbf24"
                                        class="form-control form-control-color p-0"></td>
                                <td><label>{{ __('general.remaining_header') }}</label></td>
                                <td><input type="color" id="remainingHeaderColor" value="#9ca3af"
                                        class="form-control form-control-color p-0"></td>
                            </tr>
                            <tr>
                                <td><label>{{ __('general.start_date') }}</label></td>
                                <td><input type="color" id="startDateHeaderColor" value="#34d399"
                                        class="form-control form-control-color p-0"></td>
                                <td><label>{{ __('general.target_header') }}</label></td>
                                <td><input type="color" id="targetHeaderColor" value="#f472b6"
                                        class="form-control form-control-color p-0"></td>
                            </tr>
                            <tr>
                                <td><label>Progress Start Date</label></td>
                                <td><input type="color" id="progressStartDateHeaderColor" value="#10b981"
                                        class="form-control form-control-color p-0"></td>
                                <td><label>Progress End Date</label></td>
                                <td><input type="color" id="progressEndDateHeaderColor" value="#3b82f6"
                                        class="form-control form-control-color p-0"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted">{{ __('general.cell_colors') }}</h6>
                    <table class="table table-borderless table-sm">
                        <tbody>
                            <tr>
                                <td><label>{{ __('general.planned_cells') }}</label></td>
                                <td><input type="color" id="plannedColor" value="#fef9c3"
                                        class="form-control form-control-color p-0"></td>
                            </tr>
                            <tr>
                                <td><label>{{ __('general.actual_cells') }}</label></td>
                                <td><input type="color" id="actualColor" value="#dcfce7"
                                        class="form-control form-control-color p-0"></td>
                            </tr>
                            <tr>
                                <td><label>{{ __('general.sl_cell') }}</label></td>
                                <td><input type="color" id="slColor" value="#fef3c7"
                                        class="form-control form-control-color p-0"></td>
                                <td><label>{{ __('general.activity_cell') }}</label></td>
                                <td><input type="color" id="activityColor" value="#fce7f3"
                                        class="form-control form-control-color p-0"></td>
                            </tr>
                            <tr>
                                <td><label>{{ __('general.total_quantity_cell') }}</label></td>
                                <td><input type="color" id="totalQtyColor" value="#e0f2fe"
                                        class="form-control form-control-color p-0"></td>
                                <td><label>{{ __('general.unit_cell') }}</label></td>
                                <td><input type="color" id="unitColor" value="#d1fae5"
                                        class="form-control form-control-color p-0"></td>
                            </tr>
                            <tr>
                                <td><label>{{ __('general.remaining_cell') }}</label></td>
                                <td><input type="color" id="remainingColor" value="#f3f4f6"
                                        class="form-control form-control-color p-0"></td>
                                <td><label>{{ __('general.start_date') }}</label></td>
                                <td><input type="color" id="startDateColor" value="#d1fae5"
                                        class="form-control form-control-color p-0"></td>
                            </tr>
                            <tr>
                                <td><label>{{ __('general.target_cell') }}</label></td>
                                <td><input type="color" id="targetColor" value="#fde68a"
                                        class="form-control form-control-color p-0"></td>
                                <td><label>Progress Start Date</label></td>
                                <td><input type="color" id="progressStartDateColor" value="#d1fae5"
                                        class="form-control form-control-color p-0"></td>
                            </tr>
                            <tr>
                                <td><label>Progress End Date</label></td>
                                <td><input type="color" id="progressEndDateColor" value="#dbeafe"
                                        class="form-control form-control-color p-0"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm;
            }

            html,
            body {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                background: #fff !important;
                color: #000 !important;
                font-size: 8px;
                line-height: 1.15;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            .no-print,
            footer,
            .alert {
                display: none !important;
            }

            .print-only {
                display: block !important;
            }

            .print-report-header {
                text-align: center;
                margin: 0 0 8px 0;
                color: #000 !important;
            }

            .print-report-header h3 {
                font-size: 14px;
                font-weight: 700;
                margin: 0 0 2px 0;
            }

            .print-report-header h4 {
                font-size: 11px;
                font-weight: 600;
                margin: 0 0 4px 0;
            }

            .print-report-header .print-meta {
                display: flex;
                justify-content: center;
                flex-wrap: wrap;
                gap: 4px 14px;
                font-size: 8px;
            }

            .print-report-header .print-meta span:empty {
                display: none;
            }

            .card,
            .printable-area,
            .card-body {
                border: none !important;
                box-shadow: none !important;
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            .table-responsive {
                display: block !important;
                overflow: visible !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            .progress-table,
            table.table.progress-table,
            .table-responsive .progress-table,
            .table-responsive .table.progress-table {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                table-layout: fixed !important;
                border-collapse: collapse !important;
                white-space: normal !important;
                font-size: 6.5px !important;
                page-break-inside: auto;
            }

            .progress-table col.col-sl { width: 2.4%; }
            .progress-table col.col-activity { width: 13%; }
            .progress-table col.col-qty { width: 4%; }
            .progress-table col.col-unit { width: 3%; }
            .progress-table col.col-metric { width: 3.5%; }
            .progress-table col.col-date { width: 5.15%; }

            .progress-table th,
            .progress-table td,
            table.table.progress-table th,
            table.table.progress-table td {
                padding: 2px 1px !important;
                border: 0.4px solid #111 !important;
                text-align: center !important;
                vertical-align: middle !important;
                word-wrap: break-word !important;
                overflow-wrap: anywhere !important;
                white-space: normal !important;
                overflow: visible !important;
                text-overflow: clip !important;
                color: #111 !important;
                font-size: 6.5px !important;
                line-height: 1.2 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            .progress-table thead {
                display: table-header-group !important;
            }

            .progress-table tbody.is-print-hidden {
                display: none !important;
            }

            .progress-table tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            .progress-table tbody tr:hover {
                transform: none !important;
                box-shadow: none !important;
                background: transparent !important;
            }

            .table-header th {
                font-weight: 700 !important;
            }

            .activity-cell {
                text-align: start !important;
                font-size: 6.5px;
            }

            .progress-table .d-flex {
                display: block !important;
            }

            .progress-table .fas {
                font-size: 6px;
            }

            .progress-table .badge {
                font-size: 5.5px !important;
                padding: 1px 3px !important;
                border: 0.4px solid #111;
                color: #111 !important;
                background: #e5e7eb !important;
            }

            .above-expected,
            .below-expected,
            .equal-expected,
            .planned-cell,
            .actual-cell,
            .remaining-cell {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
        }

        
        .print-only {
            display: none;
        }

        .progress-table th,
        .progress-table td {
            border: 1px solid #d1d5db;
            text-align: center;
            padding: 8px 6px;
            font-size: 0.85rem;
            word-wrap: break-word;
            white-space: normal;
            overflow-wrap: break-word;
        }

        .progress-table tbody tr:hover {
            transform: scale(1.01);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .working-zone-display {
            padding: 12px 15px;
            border-radius: 8px;
            border-left: 4px solid #1e40af;
        }

        .activity-cell {
            text-align: right;
            padding-right: 10px;
            word-wrap: break-word;
            white-space: normal;
            min-width: 150px;
        }

        .activity-cell .item-name {
            font-weight: 500;
        }

        .activity-cell .item-extra-info {
            margin-top: 4px;
            padding-top: 4px;
            border-top: 1px dashed #dee2e6;
        }

        .activity-cell .item-extra-info small {
            font-size: 0.75rem;
            line-height: 1.4;
        }

        .above-expected {
            background-color: #dcfce7 !important;
        }

        .below-expected {
            background-color: #fee2e2 !important;
        }

        

        .key-color {
            width: 20px;
            height: 20px;
            display: inline-block;
            border-radius: 4px;
        }

        
        .table-header {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        
        .planned-cell {
            background-color: #fef9c3;
        }

        .actual-cell {
            background-color: #dcfce7;
        }

        
        @media (max-width: 992px) {
            .progress-table {
                font-size: 0.75rem;
            }

            .progress-table th,
            .progress-table td {
                padding: 4px 3px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            applyColors();

            document.querySelectorAll('input[type="color"]').forEach(input => {
                input.addEventListener('input', applyColors);
            });

            const toggleButton = document.getElementById('toggleSubprojectNotes');
            let isShowing = false;

            if (toggleButton) {
                toggleButton.addEventListener('click', function() {
                    const extraInfoElements = document.querySelectorAll('#progressIndividualRows .item-extra-info');
                    isShowing = !isShowing;

                    if (isShowing) {
                        extraInfoElements.forEach(el => {
                            if (el.textContent.trim()) {
                                el.style.display = 'block';
                            }
                        });
                        toggleButton.innerHTML = '<i class="fas fa-eye-slash me-1"></i>Hide items by subproject and notes';
                        toggleButton.classList.remove('btn-outline-info');
                        toggleButton.classList.add('btn-info');
                    } else {
                        extraInfoElements.forEach(el => {
                            el.style.display = 'none';
                        });
                        toggleButton.innerHTML = '<i class="fas fa-eye me-1"></i>Show items by subproject and notes';
                        toggleButton.classList.remove('btn-info');
                        toggleButton.classList.add('btn-outline-info');
                    }
                });
            }

            const toggleGroupedBtn = document.getElementById('toggleGroupedDuplicates');
            const toggleGroupedText = document.getElementById('toggleGroupedDuplicatesText');
            const individualRows = document.getElementById('progressIndividualRows');
            const groupedRows = document.getElementById('progressGroupedRows');
            const subprojectRows = document.getElementById('progressSubprojectRows');
            const toggleSubprojectBtn = document.getElementById('toggleSubprojectView');
            const toggleSubprojectText = document.getElementById('toggleSubprojectViewText');
            // 'individual' | 'grouped' | 'subproject'
            let viewMode = 'individual';

            function setViewMode(mode) {
                viewMode = mode;

                // hide all first
                if (individualRows)  individualRows.style.display  = 'none';
                if (groupedRows)     groupedRows.style.display     = 'none';
                if (subprojectRows)  subprojectRows.style.display  = 'none';

                // reset all buttons
                if (toggleGroupedBtn) {
                    toggleGroupedBtn.classList.remove('btn-primary');
                    toggleGroupedBtn.classList.add('btn-outline-primary');
                    toggleGroupedText.textContent = '{{ __('general.group_duplicate_items') }}';
                }
                if (toggleSubprojectBtn) {
                    toggleSubprojectBtn.classList.remove('btn-success');
                    toggleSubprojectBtn.classList.add('btn-outline-success');
                    toggleSubprojectText.textContent = '{{ __('general.group_by_subproject')  }}';
                }
                if (toggleButton) toggleButton.disabled = false;

                if (mode === 'individual') {
                    if (individualRows) individualRows.style.display = '';
                } else if (mode === 'grouped') {
                    if (groupedRows) groupedRows.style.display = '';
                    if (toggleGroupedBtn) {
                        toggleGroupedBtn.classList.remove('btn-outline-primary');
                        toggleGroupedBtn.classList.add('btn-primary');
                        toggleGroupedText.textContent = '{{ __('general.show_individual_items') }}';
                    }
                    if (toggleButton) toggleButton.disabled = true;
                } else if (mode === 'subproject') {
                    if (subprojectRows) subprojectRows.style.display = '';
                    if (toggleSubprojectBtn) {
                        toggleSubprojectBtn.classList.remove('btn-outline-success');
                        toggleSubprojectBtn.classList.add('btn-success');
                        toggleSubprojectText.textContent = '{{ __('general.show_individual_items') ?? 'Show Individual Items' }}';
                    }
                    if (toggleButton) toggleButton.disabled = true;
                }

                syncPrintVisibility();
                applyColors();
            }

            // Keep is-print-hidden in sync
            function syncPrintVisibility() {
                if (individualRows)  individualRows.classList.toggle('is-print-hidden',  viewMode !== 'individual');
                if (groupedRows)     groupedRows.classList.toggle('is-print-hidden',     viewMode !== 'grouped');
                if (subprojectRows)  subprojectRows.classList.toggle('is-print-hidden',  viewMode !== 'subproject');

                const modeLabel = document.getElementById('printViewModeLabel');
                if (modeLabel) {
                    modeLabel.textContent = viewMode === 'grouped'
                        ? '{{ __('general.group_duplicate_items') }}'
                        : (viewMode === 'subproject' ? '{{ __('general.group_by_subproject')  }}' : '');
                }
            }

            if (toggleGroupedBtn) {
                toggleGroupedBtn.addEventListener('click', function() {
                    setViewMode(viewMode === 'grouped' ? 'individual' : 'grouped');
                });
            }

            if (toggleSubprojectBtn) {
                toggleSubprojectBtn.addEventListener('click', function() {
                    setViewMode(viewMode === 'subproject' ? 'individual' : 'subproject');
                });
            }

            // Init
            setViewMode('individual');

            let wasDarkMode = false;

            function preparePrint() {
                const html = document.documentElement;
                if (!html.classList.contains('is-printing')) {
                    wasDarkMode = html.classList.contains('dark-mode');
                }
                html.classList.add('is-printing');
                html.classList.remove('dark-mode');
                syncPrintVisibility();
            }

            function restoreAfterPrint() {
                const html = document.documentElement;
                html.classList.remove('is-printing');
                if (wasDarkMode) {
                    html.classList.add('dark-mode');
                }
            }

            window.addEventListener('beforeprint', preparePrint);
            window.addEventListener('afterprint', restoreAfterPrint);

            const printBtn = document.getElementById('printProgressBtn');
            if (printBtn) {
                printBtn.addEventListener('click', function() {
                    const url = new URL('{{ route('projects.progress.print', $project) }}', window.location.origin);
                    const form = document.querySelector('form[action*="progress"]');

                    if (form) {
                        new FormData(form).forEach((value, key) => {
                            if (value !== null && String(value).trim() !== '') {
                                url.searchParams.set(key, String(value));
                            }
                        });
                    }

                    new URLSearchParams(window.location.search).forEach((value, key) => {
                        if (value && !url.searchParams.has(key)) {
                            url.searchParams.set(key, value);
                        }
                    });

                    if (viewMode === 'grouped') {
                        url.searchParams.set('grouped', '1');
                    }
                    if (viewMode === 'subproject') {
                        url.searchParams.set('subproject_view', '1');
                    }
                    if (isShowing) {
                        url.searchParams.set('show_notes', '1');
                    }

                    const colorValues = {};
                    document.querySelectorAll('input[type="color"][id]').forEach(input => {
                        colorValues[input.id] = input.value;
                    });
                    if (Object.keys(colorValues).length) {
                        url.searchParams.set('colors', JSON.stringify(colorValues));
                    }

                    window.open(url.toString(), '_blank');
                });
            }

            const exportExcelBtn = document.getElementById('exportExcelBtn');
            if (exportExcelBtn) {
                exportExcelBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const url = new URL('{{ route('projects.progress.export', $project) }}', window.location.origin);
                    const form = document.querySelector('form[action*="progress"]');

                    if (form) {
                        new FormData(form).forEach((value, key) => {
                            if (value !== null && String(value).trim() !== '') {
                                url.searchParams.set(key, String(value));
                            }
                        });
                    }

                    new URLSearchParams(window.location.search).forEach((value, key) => {
                        if (value && !url.searchParams.has(key)) {
                            url.searchParams.set(key, value);
                        }
                    });

                    if (viewMode === 'grouped') {
                        url.searchParams.set('grouped', '1');
                    }
                    if (viewMode === 'subproject') {
                        url.searchParams.set('subproject_view', '1');
                    }
                    if (isShowing) {
                        url.searchParams.set('show_notes', '1');
                    }

                    window.location.href = url.toString();
                });
            }
        });

        function applyColors() {
            const colors = {
                sl: document.getElementById('slColor')?.value || '#fef3c7',
                activity: document.getElementById('activityColor')?.value || '#fce7f3',
                totalQty: document.getElementById('totalQtyColor')?.value || '#e0f2fe',
                unit: document.getElementById('unitColor')?.value || '#d1fae5',
                planned: document.getElementById('plannedColor')?.value || '#fef9c3',
                actual: document.getElementById('actualColor')?.value || '#dcfce7',
                remaining: document.getElementById('remainingColor')?.value || '#f3f4f6',
                startDate: document.getElementById('startDateColor')?.value || '#d1fae5',
                target: document.getElementById('targetColor')?.value || '#fde68a',
                progressStartDate: document.getElementById('progressStartDateColor')?.value || '#d1fae5',
                progressEndDate: document.getElementById('progressEndDateColor')?.value || '#dbeafe'
            };

            const headerColors = {
                sl: document.getElementById('slHeaderColor')?.value || '#fde68a',
                activity: document.getElementById('activityHeaderColor')?.value || '#fcd34d',
                prev: document.getElementById('prevHeaderColor')?.value || '#60a5fa',
                curr: document.getElementById('currHeaderColor')?.value || '#a78bfa',
                comp: document.getElementById('compHeaderColor')?.value || '#fbbf24',
                remaining: document.getElementById('remainingHeaderColor')?.value || '#9ca3af',
                startDate: document.getElementById('startDateHeaderColor')?.value || '#34d399',
                target: document.getElementById('targetHeaderColor')?.value || '#f472b6',
                progressStartDate: document.getElementById('progressStartDateHeaderColor')?.value || '#10b981',
                progressEndDate: document.getElementById('progressEndDateHeaderColor')?.value || '#3b82f6'
            };

            document.querySelectorAll('.sl-cell').forEach(el => el.style.backgroundColor = colors.sl);
            document.querySelectorAll('.activity-cell').forEach(el => el.style.backgroundColor = colors.activity);
            document.querySelectorAll('.quantity-cell, .totalQty-header').forEach(el => el.style.backgroundColor = colors.totalQty);
            document.querySelectorAll('.unit-cell, .unit-header').forEach(el => el.style.backgroundColor = colors.unit);
            document.querySelectorAll('.planned-cell, .planned-header').forEach(el => el.style.backgroundColor = colors.planned);
            document.querySelectorAll('.actual-cell, .actual-header').forEach(el => el.style.backgroundColor = colors.actual);
            document.querySelectorAll('.remaining-cell').forEach(el => el.style.backgroundColor = colors.remaining);
            document.querySelectorAll('.start-date-cell').forEach(el => el.style.backgroundColor = colors.startDate);
            document.querySelectorAll('.target-date-cell').forEach(el => el.style.backgroundColor = colors.target);
            document.querySelectorAll('.progress-start-date-cell').forEach(el => el.style.backgroundColor = colors.progressStartDate);
            document.querySelectorAll('.progress-end-date-cell').forEach(el => el.style.backgroundColor = colors.progressEndDate);

            document.querySelectorAll('.sl-header').forEach(el => el.style.backgroundColor = headerColors.sl);
            document.querySelectorAll('.activity-header').forEach(el => el.style.backgroundColor = headerColors.activity);
            document.querySelectorAll('.prev-header').forEach(el => el.style.backgroundColor = headerColors.prev);
            document.querySelectorAll('.curr-header').forEach(el => el.style.backgroundColor = headerColors.curr);
            document.querySelectorAll('.comp-header').forEach(el => el.style.backgroundColor = headerColors.comp);
            document.querySelectorAll('.remaining-header').forEach(el => el.style.backgroundColor = headerColors.remaining);
            document.querySelectorAll('.start-date-header').forEach(el => el.style.backgroundColor = headerColors.startDate);
            document.querySelectorAll('.target-header').forEach(el => el.style.backgroundColor = headerColors.target);
            document.querySelectorAll('.progress-start-date-header').forEach(el => el.style.backgroundColor = headerColors.progressStartDate);
            document.querySelectorAll('.progress-end-date-header').forEach(el => el.style.backgroundColor = headerColors.progressEndDate);
        }
    </script>
@endsection
