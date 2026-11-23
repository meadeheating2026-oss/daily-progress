@extends('layouts.app')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('dashboard') }}" class="text-muted text-decoration-none">{{ __('general.dashboard') }}</a>
    </li>
    <li class="breadcrumb-item">
        <a href="{{ route('daily-progress.index') }}" class="text-muted text-decoration-none">{{ __('general.daily_progress') }}</a>
    </li>
    <li class="breadcrumb-item active">{{ __('general.daily_progress_title') }}</li>
@endsection

@section('title', __('general.daily_progress_title'))

@push('styles')
<style>
    .kon-dp-page .kon-dp-card {
        background: var(--surface, #ffffff);
        border: 1px solid var(--line, #e2e8f0);
        border-radius: 14px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
        overflow: hidden;
    }

    .kon-dp-page .kon-dp-header {
        background: var(--brand, #213c7a);
        color: #ffffff;
        padding: 1rem 1.35rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .kon-dp-page .kon-dp-header-title {
        margin: 0;
        font-size: 1.12rem;
        font-weight: 700;
        color: #ffffff !important;
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
    }

    .kon-dp-page .kon-dp-header-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.16);
        border: 1px solid rgba(255, 255, 255, 0.28);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #ffffff !important;
        font-size: 1rem;
    }

    .kon-dp-page .kon-dp-filter-panel {
        background: var(--surface-2, #f8fafc);
        border: 1px solid var(--line, #e2e8f0);
        border-radius: 12px;
        padding: 1.15rem 1.25rem;
    }

    .kon-dp-page .form-label {
        font-size: 0.86rem;
        font-weight: 700;
        color: var(--ink, #0f172a) !important;
        margin-bottom: 0.45rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }

    .kon-dp-page .form-label i {
        color: var(--brand, #213c7a);
    }

    /* High-contrast Select2 aligned pixel-for-pixel with form controls */
    .kon-dp-page .select2-container {
        width: 100% !important;
    }

    .kon-dp-page .select2-container--default .select2-selection--single {
        height: 42px !important;
        background-color: var(--surface, #ffffff) !important;
        border: 1px solid var(--line-2, #cbd5e1) !important;
        border-radius: 8px !important;
        padding: 0 0.85rem !important;
        display: flex !important;
        align-items: center !important;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .kon-dp-page .select2-container--default.select2-container--open .select2-selection--single,
    .kon-dp-page .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: var(--brand, #213c7a) !important;
        box-shadow: 0 0 0 3px rgba(33, 60, 122, 0.15) !important;
    }

    .kon-dp-page .select2-container--default .select2-selection--single .select2-selection__rendered {
        padding: 0 !important;
        padding-inline-end: 1.6rem !important;
        line-height: 1.4 !important;
        color: var(--ink, #0f172a) !important;
        font-weight: 600 !important;
        font-size: 0.9rem !important;
    }

    .kon-dp-page .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #64748b !important;
        font-weight: 500 !important;
    }

    .kon-dp-page .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100% !important;
        top: 0 !important;
        right: 10px !important;
    }

    [dir="rtl"] .kon-dp-page .select2-container--default .select2-selection--single .select2-selection__arrow {
        right: auto !important;
        left: 10px !important;
    }

    .kon-dp-page .kon-date-group .form-control,
    .kon-dp-page .kon-date-group .input-group-text {
        height: 42px;
    }

    .kon-dp-page .kon-date-group .form-control {
        font-weight: 600;
        color: var(--ink, #0f172a) !important;
    }

    .select2-dropdown {
        border: 1px solid var(--line-2, #cbd5e1) !important;
        border-radius: 10px !important;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12) !important;
        background-color: var(--surface, #ffffff) !important;
        overflow: hidden;
    }

    .select2-container--default .select2-search--dropdown .select2-search__field {
        border: 1px solid var(--line-2, #cbd5e1) !important;
        border-radius: 8px !important;
        padding: 0.45rem 0.75rem !important;
        color: var(--ink, #0f172a) !important;
        background-color: var(--surface, #ffffff) !important;
        outline: none !important;
    }

    .select2-container--default .select2-search--dropdown .select2-search__field:focus {
        border-color: var(--brand, #213c7a) !important;
        box-shadow: 0 0 0 3px rgba(33, 60, 122, 0.14) !important;
    }

    .select2-container--default .select2-results__option {
        padding: 8px 12px !important;
        font-size: 0.88rem !important;
        color: var(--ink, #0f172a) !important;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: var(--brand, #213c7a) !important;
        color: #ffffff !important;
    }

    /* Empty prompt before choosing project */
    .kon-dp-empty-prompt {
        border: 1.5px dashed var(--line-2, #cbd5e1);
        border-radius: 12px;
        background: var(--surface-2, #f8fafc);
        padding: 2rem 1.25rem;
        text-align: center;
        color: var(--ink-3, #64748b);
    }

    /* Items Table */
    .kon-dp-table {
        border: 1px solid var(--line, #e2e8f0);
        border-radius: 12px;
        overflow: hidden;
    }

    .kon-dp-table thead th {
        background: var(--brand, #213c7a) !important;
        color: #ffffff !important;
        font-weight: 700;
        font-size: 0.84rem;
        padding: 0.85rem 1rem;
        border: 0 !important;
        white-space: nowrap;
    }

    .kon-dp-table tbody td {
        padding: 0.9rem 1rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--line, #e2e8f0);
        background: var(--surface, #ffffff);
    }

    .kon-dp-table tbody tr:hover:not(.group-header) td {
        background: rgba(33, 60, 122, 0.025);
    }

    .kon-dp-table tr.group-header td {
        background: #1e293b !important;
        color: #ffffff !important;
        padding: 0.75rem 1rem !important;
        cursor: pointer;
        user-select: none;
        border-bottom: 1px solid #334155;
    }

    .kon-dp-sub-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.7rem;
        border-radius: 8px;
        background: rgba(33, 60, 122, 0.09);
        border: 1px solid rgba(33, 60, 122, 0.18);
        color: var(--brand, #213c7a);
        font-size: 0.8rem;
        font-weight: 700;
    }

    .kon-dp-meta-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.18rem 0.55rem;
        border-radius: 6px;
        font-size: 0.76rem;
        font-weight: 600;
        background: var(--surface-2, #f8fafc);
        border: 1px solid var(--line, #e2e8f0);
        color: var(--ink-2, #334155);
    }

    /* Quantity Input & Scoped Feedback Banners */
    .quantity-input {
        font-weight: 700 !important;
        text-align: center;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .quantity-input.border-success {
        border-color: #16803c !important;
        border-width: 2px !important;
    }

    .quantity-input.border-warning {
        border-color: #d97706 !important;
        border-width: 2px !important;
    }

    .quantity-input.border-danger {
        border-color: #dc2626 !important;
        border-width: 2px !important;
    }

    .kon-dp-feedback {
        font-size: 0.78rem;
        font-weight: 600;
        padding: 6px 10px;
        border-radius: 8px;
        margin-top: 6px;
        line-height: 1.4;
    }

    .kon-dp-feedback.is-success {
        background-color: #ecfdf5;
        color: #14532d;
        border-inline-start: 3px solid #16803c;
    }

    .kon-dp-feedback.is-warning {
        background-color: #fffbeb;
        color: #78350f;
        border-inline-start: 3px solid #d97706;
    }

    .kon-dp-feedback.is-danger {
        background-color: #fef2f2;
        color: #7f1d1d;
        border-inline-start: 3px solid #dc2626;
    }

    .kon-dp-feedback.is-info {
        background-color: #eff6ff;
        color: #1e3a8a;
        border-inline-start: 3px solid #213c7a;
    }
</style>
@endpush

@section('content')
<div class="container-fluid kon-dp-page">
    <div class="kon-dp-card">
        <div class="kon-dp-header">
            <h5 class="kon-dp-header-title">
                <span class="kon-dp-header-icon"><i class="fas fa-clipboard-check"></i></span>
                <span>{{ __('general.daily_progress_title') }}</span>
            </h5>
            @can('dailyprogress-list')
                <a href="{{ route('daily-progress.index') }}" class="btn btn-light btn-sm fw-semibold">
                    <i class="fas fa-arrow-right me-1"></i> {{ __('general.back_to_list') }}
                </a>
            @endcan
        </div>

        <div class="p-3 p-md-4">
            @if(session('warning'))
                <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    {!! session('warning') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('daily-progress.store') }}" method="POST" class="needs-validation" novalidate>
                @csrf

                <!-- Top Project / Subproject / Date Filter Panel -->
                <div class="kon-dp-filter-panel mb-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-5 col-md-6">
                            <label class="form-label" for="project_id">
                                <i class="fas fa-diagram-project"></i>
                                <span>{{ __('general.project') }}</span>
                                <span class="text-danger">*</span>
                            </label>
                            <select name="project_id" id="project_id" class="form-select" required>
                                <option value="">{{ __('general.select_project') }}</option>
                                @foreach ($projects as $projOption)
                                    <option value="{{ $projOption->id }}" {{ (string) old('project_id', request('project_id')) === (string) $projOption->id ? 'selected' : '' }}>
                                        {{ $projOption->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <label class="form-label" for="subproject_id">
                                <i class="fas fa-layer-group"></i>
                                <span>المشروع الفرعي</span>
                            </label>
                            <select name="subproject_id" id="subproject_id" class="form-select">
                                <option value="">جميع المشاريع الفرعية</option>
                            </select>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <label class="form-label" for="progress_date_input">
                                <i class="fas fa-calendar-day"></i>
                                <span>{{ __('general.date') }}</span>
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group kon-date-group">
                                <span class="input-group-text"><i class="fas fa-calendar-alt text-primary"></i></span>
                                <input type="date" id="progress_date_input" name="progress_date" class="form-control" value="{{ old('progress_date', date('Y-m-d')) }}" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State Prompt (shown until a project with items is selected) -->
                <div id="items_empty_prompt" class="kon-dp-empty-prompt mb-4">
                    <i class="fas fa-list-check fa-2x mb-2" style="color: var(--brand, #213c7a); opacity: 0.75;"></i>
                    <div class="fw-bold mb-1" style="color: var(--ink, #0f172a);">اختر المشروع لعرض بنود العمل</div>
                    <div class="small">بمجرد اختيار المشروع من القائمة أعلاه ستظهر جميع بنوده هنا لتسجيل الكميات المنفذة اليوم.</div>
                </div>

                <!-- Loaded Work Items Section -->
                <div class="mb-4" id="items_table_wrapper" style="display:none;">
                    <div class="row g-2 align-items-center mb-3">
                        <div class="col-lg-7">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" id="items_search" class="form-control" placeholder="ابحث في البنود (الاسم، الوحدة، الفئة، المشروع الفرعي، الملاحظات)...">
                                <button type="button" id="clear_search" class="btn btn-outline-secondary" title="مسح البحث">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <small class="text-primary fw-semibold mt-1 d-block" id="search_counter" style="display:none;"></small>
                        </div>
                        <div class="col-lg-5">
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-outline-primary active fw-semibold" id="view_flat">
                                    <i class="fas fa-list me-1"></i> عرض عادي
                                </button>
                                <button type="button" class="btn btn-outline-primary fw-semibold" id="view_grouped">
                                    <i class="fas fa-layer-group me-1"></i> تجميع حسب المشروع الفرعي
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive kon-dp-table">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('general.work_item') }}</th>
                                    <th style="width: 185px;" class="text-center">المشروع الفرعي</th>
                                    <th style="width: 220px;" class="text-center">الموقف الحالي (منفذ / إجمالي)</th>
                                    <th style="width: 245px;">{{ __('general.enter_quantity') }}</th>
                                </tr>
                            </thead>
                            <tbody id="items_table_body"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Notes -->
                <div class="mb-4">
                    <label class="form-label" for="dp_notes_input">
                        <i class="fas fa-sticky-note"></i>
                        <span>{{ __('general.notes') }}</span>
                    </label>
                    <textarea id="dp_notes_input" name="notes" class="form-control" rows="3" placeholder="أضف أي ملاحظات تنفيذية أو فنية تخص إنجاز اليوم...">{{ old('notes') }}</textarea>
                </div>

                <!-- Submit Footer -->
                <div class="d-flex justify-content-end pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-bold">
                        <i class="fas fa-save me-2"></i> {{ __('general.save_progress') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        const translations = {
            expectedDaily: '{{ __("general.expected_daily") }}',
            day: '{{ __("general.day") }}'
        };

        const isArabic = '{{ session("locale", app()->getLocale()) }}' === 'ar';
        const noResultsText = isArabic ? 'لا توجد نتائج مطابقة' : 'No results found';
        const searchingText = isArabic ? 'جاري البحث...' : 'Searching...';

        let currentViewMode = 'flat';
        let allItemsData = [];

        function escapeHtml(text) {
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return text ? String(text).replace(/[&<>"']/g, m => map[m]) : '';
        }

        function setupGroupToggle() {
            $(document).off('click', '.group-header').on('click', '.group-header', function(e) {
                e.preventDefault();
                const $header = $(this);
                let $groupItems = $();
                let $next = $header.next();

                while ($next.length && !$next.hasClass('group-header')) {
                    if ($next.hasClass('group-item')) {
                        $groupItems = $groupItems.add($next);
                    }
                    $next = $next.next();
                }

                if (!$groupItems.length) return;
                const $toggle = $header.find('.group-toggle');
                if ($groupItems.first().is(':visible')) {
                    $groupItems.hide();
                    $toggle.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                } else {
                    $groupItems.show();
                    $toggle.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                }
            });
        }

        setupGroupToggle();

        $('#project_id').select2({
            placeholder: '{{ __("general.select_project") }}',
            allowClear: true,
            width: '100%',
            dir: isArabic ? 'rtl' : 'ltr',
            language: {
                noResults: () => noResultsText,
                searching: () => searchingText
            },
            minimumResultsForSearch: 0
        });

        $('#subproject_id').select2({
            placeholder: 'جميع المشاريع الفرعية',
            allowClear: true,
            width: '100%',
            dir: isArabic ? 'rtl' : 'ltr',
            language: {
                noResults: () => noResultsText,
                searching: () => searchingText
            },
            minimumResultsForSearch: 0
        });

        $('#view_flat').on('click', function() {
            if (currentViewMode !== 'flat') {
                currentViewMode = 'flat';
                $('#view_flat').addClass('active');
                $('#view_grouped').removeClass('active');
                renderItems(getFilteredBySubproject(), 'flat');
            }
        });

        $('#view_grouped').on('click', function() {
            if (currentViewMode !== 'grouped') {
                currentViewMode = 'grouped';
                $('#view_grouped').addClass('active');
                $('#view_flat').removeClass('active');
                renderItems(getFilteredBySubproject(), 'grouped');
            }
        });

        function getFilteredBySubproject() {
            const subprojectId = $('#subproject_id').val();
            if (!subprojectId) return allItemsData;
            const selectedSubprojectName = $('#subproject_id').find('option:selected').text();
            return allItemsData.filter(function(item) {
                if (item.subproject_id) return item.subproject_id == subprojectId;
                if (item.subproject_name) return item.subproject_name === selectedSubprojectName;
                return false;
            });
        }

        $('#project_id').on('change', function() {
            const projectId = $(this).val();
            $('#items_search').val('');
            $('#search_counter').hide();

            if (projectId) {
                $.get('/api/projects/' + projectId + '/subprojects', function(response) {
                    $('#subproject_id').empty().append('<option value="">جميع المشاريع الفرعية</option>');
                    const subprojects = response.subprojects || response || [];
                    if (Array.isArray(subprojects) && subprojects.length > 0) {
                        $.each(subprojects, function(_, subproject) {
                            $('#subproject_id').append(
                                '<option value="' + (subproject.id || '') + '">' + escapeHtml(subproject.name || 'بدون اسم') + '</option>'
                            );
                        });
                    }
                    $('#subproject_id').trigger('change.select2');
                }).fail(function() {
                    $('#subproject_id').empty().append('<option value="">جميع المشاريع الفرعية</option>');
                });

                $('#items_empty_prompt').html('<div class="spinner-border spinner-border-sm text-primary mb-2"></div><div class="fw-semibold">جاري تحميل بنود المشروع...</div>').show();

                $.get('/api/project-items/' + projectId, function(data) {
                    allItemsData = Array.isArray(data) ? data : [];
                    $('#items_table_body').empty();
                    if (allItemsData.length > 0) {
                        $('#items_empty_prompt').hide();
                        $('#items_table_wrapper').show();
                        renderItems(allItemsData, currentViewMode);
                    } else {
                        $('#items_table_wrapper').hide();
                        $('#items_empty_prompt').html('<i class="fas fa-inbox fa-2x mb-2 text-muted"></i><div class="fw-bold">لا توجد بنود مضافة في هذا المشروع بعد</div>').show();
                    }
                }).fail(function() {
                    $('#items_table_wrapper').hide();
                    $('#items_empty_prompt').html('<i class="fas fa-triangle-exclamation fa-2x mb-2 text-danger"></i><div class="fw-bold text-danger">حدث خطأ أثناء تحميل البنود، يرجى المحاولة مرة أخرى.</div>').show();
                });
            } else {
                allItemsData = [];
                $('#items_table_wrapper').hide();
                $('#items_table_body').empty();
                $('#subproject_id').empty().append('<option value="">جميع المشاريع الفرعية</option>');
                $('#items_empty_prompt').html(
                    '<i class="fas fa-list-check fa-2x mb-2" style="color: var(--brand, #213c7a); opacity: 0.75;"></i>' +
                    '<div class="fw-bold mb-1" style="color: var(--ink, #0f172a);">اختر المشروع لعرض بنود العمل</div>' +
                    '<div class="small">بمجرد اختيار المشروع من القائمة أعلاه ستظهر جميع بنوده هنا لتسجيل الكميات المنفذة اليوم.</div>'
                ).show();
            }
        });

        $('#subproject_id').on('change', function() {
            if (allItemsData.length > 0) {
                renderItems(getFilteredBySubproject(), currentViewMode);
            }
        });

        if ($('#project_id').val()) {
            $('#project_id').trigger('change');
        }

        function renderItems(data, mode) {
            $('#items_table_body').empty();
            if (mode === 'grouped') {
                renderGroupedItems(data);
            } else {
                renderFlatItems(data);
            }
            setupItemInteractions();
            setupItemsSearch();
        }

        function renderFlatItems(data) {
            $.each(data, function(_, item) {
                $('#items_table_body').append(createItemRow(item));
            });
        }

        function renderGroupedItems(data) {
            const grouped = {};
            $.each(data, function(_, item) {
                const subName = item.subproject_name || 'بدون مشروع فرعي';
                if (!grouped[subName]) grouped[subName] = [];
                grouped[subName].push(item);
            });

            Object.keys(grouped).sort().forEach(function(groupName) {
                const items = grouped[groupName];
                const safeGroupName = escapeHtml(groupName);

                const groupHeader = `
                    <tr class="group-header" data-group="${safeGroupName}">
                        <td colspan="4">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-folder-open text-white"></i>
                                    <span class="fw-bold text-white">${safeGroupName}</span>
                                    <span class="badge bg-light text-dark ms-1">${items.length} بند</span>
                                </div>
                                <i class="fas fa-chevron-up group-toggle text-white"></i>
                            </div>
                        </td>
                    </tr>
                `;
                $('#items_table_body').append(groupHeader);

                $.each(items, function(_, item) {
                    const $row = $(createItemRow(item));
                    $row.addClass('group-item').attr('data-group', groupName);
                    $('#items_table_body').append($row);
                });
            });
        }

        function createItemRow(item) {
            const totalQty = parseFloat(item.total_quantity) || 0;
            const completedQty = parseFloat(item.completed_quantity) || 0;
            const remainingQty = Math.max(0, totalQty - completedQty);
            const todayExecuted = parseFloat(item.today_executed_quantity) || 0;
            const pct = totalQty > 0 ? Math.min(100, Math.max(0, (completedQty / totalQty) * 100)) : 0;
            const labelClass = pct >= 52 ? 'on-dark' : 'on-light';

            const wItem = item.work_item || {};
            const unit = escapeHtml(wItem.unit || '');
            const itemName = escapeHtml(wItem.name || '-');
            const categoryChip = wItem.category ? `<span class="kon-dp-meta-chip"><i class="fas fa-folder text-primary"></i>${escapeHtml(wItem.category.name)}</span>` : '';
            const unitChip = unit ? `<span class="kon-dp-meta-chip"><i class="fas fa-ruler text-secondary"></i>${unit}</span>` : '';
            const measurableChip = item.is_measurable
                ? `<span class="kon-dp-meta-chip" style="color:#16803c; border-color:rgba(22,128,60,0.25); background:rgba(22,128,60,0.07);"><i class="fas fa-check-circle"></i>قابل للقياس</span>`
                : `<span class="kon-dp-meta-chip text-muted"><i class="fas fa-minus-circle"></i>غير قابل للقياس</span>`;
            const estChip = item.estimated_daily_qty
                ? `<span class="kon-dp-meta-chip" style="color:#213c7a;"><i class="fas fa-gauge-high"></i>${translations.expectedDaily}: ${parseFloat(item.estimated_daily_qty).toFixed(2)} ${unit}/${translations.day}</span>`
                : '';
            const todayChip = todayExecuted > 0
                ? `<span class="badge bg-success"><i class="fas fa-check me-1"></i>منفذ اليوم: ${todayExecuted.toFixed(2)} ${unit}</span>`
                : '';
            const notesHtml = item.notes
                ? `<div class="small text-muted mt-1"><i class="fas fa-sticky-note me-1"></i>${escapeHtml(item.notes)}</div>`
                : '';

            const subprojectHtml = item.subproject_name
                ? `<span class="kon-dp-sub-badge"><i class="fas fa-sitemap"></i>${escapeHtml(item.subproject_name)}</span>`
                : `<span class="text-muted small">—</span>`;

            return `
                <tr class="dp-item-row" data-subproject="${escapeHtml(item.subproject_name || '')}">
                    <td>
                        <div class="fw-bold mb-1" style="color: var(--ink, #0f172a); font-size: 0.94rem;">${itemName}</div>
                        <div class="d-flex flex-wrap gap-1 align-items-center">
                            ${categoryChip}
                            ${unitChip}
                            ${measurableChip}
                            ${estChip}
                            ${todayChip}
                        </div>
                        ${notesHtml}
                    </td>
                    <td class="text-center">${subprojectHtml}</td>
                    <td>
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="fw-bold text-success">${completedQty.toFixed(2)}</span>
                            <span class="text-muted">/ ${totalQty.toFixed(2)} ${unit}</span>
                        </div>
                        <div class="kon-progress-track" style="height: 20px;">
                            <div class="kon-progress-fill ${pct >= 80 ? 'fill-actual' : 'fill-planned'}" style="width: ${pct.toFixed(1)}%;"></div>
                            <span class="kon-progress-label ${labelClass}" style="font-size: 0.72rem;">${pct.toFixed(1)}%</span>
                        </div>
                        <div class="small text-muted text-center mt-1">المتبقي: <strong>${remainingQty.toFixed(2)}</strong> ${unit}</div>
                    </td>
                    <td>
                        <div class="input-group">
                            <input type="number"
                                name="quantities[${item.id}]"
                                class="form-control quantity-input"
                                step="0.01"
                                min="0"
                                value="0"
                                data-remaining="${totalQty - completedQty}"
                                data-total="${totalQty}"
                                data-estimated="${item.estimated_daily_qty || 0}"
                                data-today-executed="${todayExecuted}"
                                data-item-name="${itemName}"
                                data-unit="${unit}">
                            ${unit ? `<span class="input-group-text fw-semibold small">${unit}</span>` : ''}
                        </div>
                        <div class="warning-text-${item.id} kon-dp-feedback is-warning" style="display:none;">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            <strong>تنبيه:</strong> الكمية تتجاوز المتبقي للبند
                        </div>
                        <div class="comparison-text-${item.id} kon-dp-feedback" style="display:none;"></div>
                    </td>
                </tr>
            `;
        }

        function setupItemInteractions() {
            $('.quantity-input').off('input').on('input', function() {
                const inputVal = parseFloat($(this).val()) || 0;
                const remaining = parseFloat($(this).data('remaining')) || 0;
                const totalQty = parseFloat($(this).data('total')) || 0;
                const estimated = parseFloat($(this).data('estimated')) || 0;
                const unit = $(this).data('unit') || '';
                const itemId = $(this).attr('name').match(/\d+/)[0];
                const warningText = $('.warning-text-' + itemId);
                const comparisonText = $('.comparison-text-' + itemId);

                $(this).removeClass('border-success border-danger border-warning');
                warningText.hide();
                comparisonText.hide().removeClass('is-success is-warning is-danger is-info');

                if (inputVal <= 0) return;

                let hasRemainingWarning = false;
                const newRemaining = remaining - inputVal;

                if (inputVal > remaining && remaining > 0) {
                    $(this).addClass('border-warning');
                    warningText.show();
                    hasRemainingWarning = true;
                }

                if (estimated > 0) {
                    const difference = inputVal - estimated;
                    const percentage = Math.abs((difference / estimated) * 100).toFixed(0);
                    let resultHtml = '';

                    if (inputVal > estimated) {
                        if (!hasRemainingWarning) $(this).addClass('border-success');
                        comparisonText.addClass('is-success');
                        resultHtml = `<i class="fas fa-arrow-up me-1"></i><strong>أعلى من المتوقع بـ ${percentage}%</strong><div class="small mt-1">المتبقي بعد الإدخال: ${Math.max(0, newRemaining).toFixed(2)} من ${totalQty.toFixed(2)} ${unit}</div>`;
                    } else if (inputVal < estimated) {
                        const achievedPercentage = ((inputVal / estimated) * 100).toFixed(0);
                        if (achievedPercentage < 50) {
                            if (!hasRemainingWarning) $(this).addClass('border-danger');
                            comparisonText.addClass('is-danger');
                            resultHtml = `<i class="fas fa-exclamation-circle me-1"></i><strong>أقل من المتوقع (${achievedPercentage}%)</strong><div class="small mt-1">المتوقع: ${estimated.toFixed(2)} ${unit} | المتبقي: ${Math.max(0, newRemaining).toFixed(2)} ${unit}</div>`;
                        } else {
                            if (!hasRemainingWarning) $(this).addClass('border-warning');
                            comparisonText.addClass('is-warning');
                            resultHtml = `<i class="fas fa-info-circle me-1"></i><strong>أقل من المتوقع بـ ${percentage}%</strong><div class="small mt-1">المتوقع: ${estimated.toFixed(2)} ${unit} | المتبقي: ${Math.max(0, newRemaining).toFixed(2)} ${unit}</div>`;
                        }
                    } else {
                        if (!hasRemainingWarning) $(this).addClass('border-success');
                        comparisonText.addClass('is-success');
                        resultHtml = `<i class="fas fa-check-circle me-1"></i><strong>مطابق للمعدل اليومي المتوقع</strong><div class="small mt-1">المتبقي: ${Math.max(0, newRemaining).toFixed(2)} من ${totalQty.toFixed(2)} ${unit}</div>`;
                    }

                    comparisonText.html(resultHtml).show();
                } else {
                    comparisonText.addClass('is-info');
                    comparisonText.html(
                        `<i class="fas fa-info-circle me-1"></i>المنفذ: ${inputVal.toFixed(2)} ${unit} | المتبقي: ${Math.max(0, newRemaining).toFixed(2)} من ${totalQty.toFixed(2)} ${unit}`
                    ).show();
                }
            });

            $('.quantity-input').off('keydown').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const inputs = $('.quantity-input:visible');
                    const idx = inputs.index(this);
                    if (idx + 1 < inputs.length) {
                        inputs[idx + 1].focus();
                        inputs[idx + 1].select();
                    } else {
                        $(this).blur();
                    }
                }
            });

            $('.quantity-input').off('focus').on('focus', function() {
                $(this).select();
            });
        }

        function setupItemsSearch() {
            $('#items_search').off('input').on('input', function() {
                const searchTerm = $(this).val().toLowerCase().trim();
                const rows = $('#items_table_body tr.dp-item-row');
                let visibleCount = 0;

                rows.each(function() {
                    const row = $(this);
                    const rowText = row.text().toLowerCase();
                    if (!searchTerm || rowText.includes(searchTerm)) {
                        row.show();
                        visibleCount++;
                    } else {
                        row.hide();
                    }
                });

                if (searchTerm) {
                    $('#search_counter').show().text(`تم العثور على ${visibleCount} بند`);
                } else {
                    $('#search_counter').hide();
                }
            });

            $('#clear_search').off('click').on('click', function() {
                $('#items_search').val('');
                $('#items_table_body tr.dp-item-row').show();
                $('#search_counter').hide();
                $('#items_search').focus();
            });
        }
    });
</script>
@endpush
