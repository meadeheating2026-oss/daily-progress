/**
 * Project Form Manager
 * Clean and Simple Implementation with Bootstrap
 */

import { config, state, elements } from './project-form/state.js';
import {
    describeCycle,
    maxEndDate,
    scheduleItems,
    workingDaysBetween
} from './project-form/scheduler.js';
import { debounce, escapeAttr, escapeHtml, formatDate, parseLocalDate, showNotification, subprojectKey } from './project-form/utils.js';

(function () {
    'use strict';

    // ========================================
    // Initialize
    // ========================================
    function init() {
        // Load data from server if available
        if (typeof window.projectFormData !== 'undefined') {
            state.workItems = window.projectFormData.workItems || [];
            state.project = window.projectFormData.project || null;
            state.projectItems = window.projectFormData.projectItems || [];
            state.subprojects = window.projectFormData.subprojects || [];
            initSubprojectOrderFromState();
        }

        initEventListeners();
        initPredecessorLazyLoad();
        initWeeklyHolidays();
        initCharCounter();
        initSortable();
        loadExistingItems();
        updateEmptyState();

        // Calculate working days on page load
        updateWeeklyHolidays();

        // Start auto-save for drafts (only for new projects)
        startAutoSave();
    }

    // ========================================
    // Event Listeners
    // ========================================
    function initEventListeners() {
        // Form submit validation
        if (elements.form) {
            elements.form.addEventListener('submit', handleFormSubmit);
            elements.form.addEventListener('keydown', handleEnterKey);
        }

        // Search
        if (elements.searchInput) {
            elements.searchInput.addEventListener('input', debounce(handleSearch, config.searchDebounceMs));
        }

        // Close search
        if (elements.closeSearch) {
            elements.closeSearch.addEventListener('click', hideSearchResults);
        }

        // Filter
        if (elements.itemsFilter) {
            elements.itemsFilter.addEventListener('input', function () {
                // Clear any existing timeout
                if (state.searchTimeout) {
                    clearTimeout(state.searchTimeout);
                }
                // Debounce the search for better performance
                state.searchTimeout = setTimeout(filterItems, 150);
            });
            // Also trigger on paste and change events
            elements.itemsFilter.addEventListener('paste', function () {
                setTimeout(filterItems, 150);
            });
            elements.itemsFilter.addEventListener('change', filterItems);
        }

        if (elements.resetFilter) {
            elements.resetFilter.addEventListener('click', resetFilter);
        }

        // Select all
        if (elements.selectAllItems) {
            elements.selectAllItems.addEventListener('change', toggleSelectAll);
        }

        // Bulk execute button
        document.getElementById('bulk-execute-btn')?.addEventListener('click', executeBulkAction);

        // Move to subproject modal confirm button
        document.getElementById('confirmMoveBtn')?.addEventListener('click', function () {
            if (!pendingMoveRows || pendingMoveRows.length === 0) {
                showNotification('warning', 'لا توجد بنود محددة للنقل');
                return;
            }

            const subprojectSelect = document.getElementById('subprojectSelect');
            const newSubprojectInput = document.getElementById('newSubprojectName');

            let selectedSubproject = '';

            // Check if a subproject is selected from dropdown
            if (subprojectSelect && subprojectSelect.value) {
                selectedSubproject = subprojectSelect.value.trim();
            }
            // Check if a new subproject name is entered
            else if (newSubprojectInput && newSubprojectInput.value.trim()) {
                selectedSubproject = newSubprojectInput.value.trim();
            }

            if (!selectedSubproject) {
                showNotification('warning', 'الرجاء اختيار مشروع فرعي أو إدخال اسم جديد');
                return;
            }

            // Execute the move (pass category name if exists)
            executeBulkMove(pendingMoveRows, selectedSubproject, pendingMoveCategoryName);

            // Hide modal
            const modalElement = document.getElementById('moveToSubprojectModal');
            if (modalElement && typeof bootstrap !== 'undefined') {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
            } else if (modalElement) {
                // Fallback hide
                modalElement.style.display = 'none';
                modalElement.classList.remove('show');
            }
        });

        // Clear pending rows and reset modal when modal is hidden
        const moveModal = document.getElementById('moveToSubprojectModal');
        if (moveModal) {
            moveModal.addEventListener('hidden.bs.modal', function () {
                pendingMoveRows = null;
                pendingMoveCategoryName = null;
                pendingMoveSelectAllCheckbox = null;
                pendingMoveBulkActionSelect = null;

                // Reset modal title
                const modalTitle = document.querySelector('#moveToSubprojectModalLabel');
                if (modalTitle) {
                    modalTitle.innerHTML = '<i class="fas fa-folder-open me-2"></i> نقل البنود إلى مشروع فرعي';
                }
            });
        }

        // Date changes
        if (elements.startDate) {
            elements.startDate.addEventListener('change', calculateAllDates);
        }

        // Weekly holidays
        document.querySelectorAll('.holiday-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                updateWeeklyHolidays();
                calculateAllDates();
            });
        });

        // Event delegation for dynamically added items
        // This handles all input/select changes in item rows
        if (elements.container) {
            elements.container.addEventListener('change', function (e) {
                const target = e.target;
                const row = target.closest('tr[data-item-id]');

                if (row && (
                    target.matches('.predecessor-select') ||
                    target.matches('select[name*="[dependency_type]"]') ||
                    target.matches('input[name*="[lag]"]') ||
                    target.matches('.duration-input') ||
                    target.matches('.estimated-daily-qty')
                )) {
                    scheduleRecalc();
                }
            });

            elements.container.addEventListener('input', function (e) {
                const target = e.target;
                const row = target.closest('tr[data-item-id]');
                if (!row) return;

                if (target.matches('.subproject-input')) {
                    updateSubprojectDatalist(target);
                    return;
                }

                if (
                    target.matches('input[name*="[lag]"]') ||
                    target.matches('.duration-input') ||
                    target.matches('.total-quantity') ||
                    target.matches('.estimated-daily-qty')
                ) {
                    // Duration is derived from the quantities unless typed directly.
                    if (target.matches('.total-quantity') || target.matches('.estimated-daily-qty')) {
                        recalculateRowDuration(row);
                    }

                    scheduleRecalc();
                }
            });

            elements.container.addEventListener('focusin', function (e) {
                if (e.target.matches?.('.subproject-input')) {
                    updateSubprojectDatalist(e.target);
                }
            });

            elements.container.addEventListener('click', function (e) {
                const row = e.target.closest?.('tr[data-item-id]');
                if (!row) return;

                if (e.target.closest('.remove-btn')) {
                    if (confirm('هل أنت متأكد من حذف هذا البند؟')) {
                        row.remove();
                        updateItemOrders();
                        calculateAllDates();
                        updateEmptyState();
                        regroupIfNeeded();
                        showNotification('info', 'تم الحذف بنجاح');
                    }
                    return;
                }

                if (e.target.closest('.duplicate-btn')) {
                    duplicateItem(row, workItemForRow(row));
                }
            });
        }

        // Template checkboxes
        document.querySelectorAll('.template-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                if (this.checked) {
                    const templateData = JSON.parse(this.getAttribute('data-template'));

                    // Check if it's a draft or a template
                    if (templateData.type === 'draft') {
                        loadDraftItems(templateData.id);
                    } else {
                        loadTemplateItems(templateData.id);
                    }
                }
            });
        });

        // View mode toggle buttons
        const flatViewBtn = document.getElementById('flat-view-btn');
        const groupedViewBtn = document.getElementById('grouped-view-btn');

        if (flatViewBtn) {
            flatViewBtn.addEventListener('click', function () {
                switchToFlatView();
            });
        }

        if (groupedViewBtn) {
            groupedViewBtn.addEventListener('click', function () {
                switchToGroupedView();
            });
        }

        // Click outside to close search
        document.addEventListener('click', function (e) {
            if (!elements.searchInput?.contains(e.target) &&
                !elements.searchResults?.contains(e.target)) {
                hideSearchResults();
            }
        });
    }

    // ========================================
    // Weekly Holidays
    // ========================================
    function initWeeklyHolidays() {
        // First, sync from hidden input to checkboxes (for edit mode)
        syncHolidaysFromInput();

        // Then update the hidden input from checkboxes
        updateWeeklyHolidays();
    }

    function syncHolidaysFromInput() {
        // Get the current value from hidden input
        const currentValue = elements.weeklyHolidaysInput?.value || '';

        if (!currentValue) return;

        // Parse the comma-separated values
        const selectedDays = currentValue.split(',').map(v => v.trim()).filter(v => v);

        // Update checkboxes to match
        document.querySelectorAll('.holiday-checkbox').forEach(checkbox => {
            const isSelected = selectedDays.includes(checkbox.value);
            checkbox.checked = isSelected;
        });

    }

    function updateWeeklyHolidays() {
        const checkboxes = document.querySelectorAll('.holiday-checkbox:checked');
        const values = Array.from(checkboxes).map(cb => cb.value);

        if (elements.weeklyHolidaysInput) {
            elements.weeklyHolidaysInput.value = values.join(',');
        }

        // Calculate working days automatically (7 days - holidays)
        const totalDays = 7;
        const holidaysCount = checkboxes.length;
        const workingDays = totalDays - holidaysCount;

        const workingDaysInput = document.getElementById('working_days');
        if (workingDaysInput) {
            workingDaysInput.value = workingDays;
        }
    }

    // ========================================
    // Character Counter
    // ========================================
    function initCharCounter() {
        if (elements.description && elements.charCount) {
            const updateCounter = () => {
                const length = elements.description.value.length;
                elements.charCount.textContent = length;
            };

            elements.description.addEventListener('input', updateCounter);
            updateCounter();
        }
    }

    // ========================================
    // Search Functionality
    // ========================================
    async function handleSearch() {
        const query = elements.searchInput.value.trim();

        if (query.length === 0) {
            hideSearchResults();
            return;
        }

        showLoading(true);
        showSearchResults();

        try {
            const response = await fetch(`/work-items/search-ajax?q=${encodeURIComponent(query)}`);
            const data = await response.json();

            renderSearchResults(data.results || []);
        } catch (error) {
            console.error('Search error:', error);
            showSearchError();
        } finally {
            showLoading(false);
        }
    }

    function renderSearchResults(results) {
        elements.searchResultsList.innerHTML = '';

        if (results.length === 0) {
            elements.searchResultsList.innerHTML = `
                <div class="list-group-item text-center text-muted">
                    <i class="fas fa-search me-2"></i>
                    لا توجد نتائج
                </div>
            `;
            elements.resultsCount.textContent = '0 نتائج';
            return;
        }

        results.forEach(item => {
            const div = document.createElement('div');
            div.className = 'list-group-item list-group-item-action';
            div.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${escapeHtml(item.name)}</strong>
                        <span class="badge bg-secondary ms-2">${escapeHtml(item.unit)}</span>
                        ${item.category ? `<br><small class="text-muted"><i class="fas fa-folder me-1"></i>${escapeHtml(item.category)}</small>` : ''}
                        ${item.description ? `<br><small class="text-muted fst-italic">${escapeHtml(item.description)}</small>` : ''}
                    </div>
                    <button type="button" class="btn btn-sm btn-primary add-item-btn">
                        <i class="fas fa-plus me-1"></i>
                        إضافة
                    </button>
                </div>
            `;

            const addBtn = div.querySelector('.add-item-btn');
            addBtn.addEventListener('click', function () {
                addWorkItem(item);
            });

            elements.searchResultsList.appendChild(div);
        });

        elements.resultsCount.textContent = `${results.length} نتائج`;
    }

    function showSearchResults() {
        if (elements.searchResults) {
            elements.searchResults.style.display = 'block';
        }
    }

    function hideSearchResults() {
        if (elements.searchResults) {
            elements.searchResults.style.display = 'none';
        }
    }

    function showLoading(show) {
        if (elements.searchLoading) {
            elements.searchLoading.style.display = show ? 'block' : 'none';
        }
    }

    function showSearchError() {
        elements.searchResultsList.innerHTML = `
            <div class="list-group-item text-center text-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                حدث خطأ في البحث
            </div>
        `;
    }

    // ========================================
    // Add Work Item
    // ========================================
    function addWorkItem(item) {
        // Add to workItems array if not exists
        if (!state.workItems.find(i => i.id == item.id)) {
            state.workItems.push(item);
        }

        state.itemCounter++;
        const rowId = `item_${state.itemCounter}_${Date.now()}`;

        const row = createItemRow(rowId, item);
        elements.container.appendChild(row);

        updateItemOrders();
        calculateAllDates();
        regroupIfNeeded();
        hideSearchResults();
        elements.searchInput.value = '';
        updateEmptyState();

        showNotification('success', `تم إضافة: ${item.name}`);
    }

    // ========================================
    // Create Item Row
    // ========================================
    function createItemRow(rowId, item, existingData = null) {
        const tr = document.createElement('tr');
        tr.dataset.itemId = rowId;
        tr.dataset.workItemId = item.id;

        const rowNumber = existingData?.item_order != null
            ? parseInt(existingData.item_order, 10)
            : elements.container.querySelectorAll('tr[data-item-id]').length;

        // Build the ID input separately to avoid template string issues
        const itemIdInput = existingData?.id
            ? `<input type="hidden" name="items[${rowId}][id]" value="${existingData.id}">`
            : '';

        tr.innerHTML = `
            <td class="text-center">
                <input type="checkbox" class="form-check-input item-checkbox">
            </td>
            <td class="text-center drag-handle" style="cursor: move;">
                <i class="fas fa-grip-vertical text-muted"></i>
                <input type="hidden" name="items[${rowId}][item_order]" value="${rowNumber}">
            </td>
            <td class="text-center">
                <span class="badge bg-primary">${rowNumber + 1}</span>
            </td>
            <td>
                <input type="hidden" name="items[${rowId}][work_item_id]" value="${item.id}">
                ${itemIdInput}
                <strong>${escapeHtml(item.name)}</strong>
                ${item.category ? `<br><small class="text-muted"><i class="fas fa-folder me-1"></i>${escapeHtml(item.category)}</small>` : ''}
                ${item.unit ? `<br><small class="text-muted"><i class="fas fa-ruler me-1"></i>${escapeHtml(item.unit)}</small>` : ''}
            </td>
            <td>
                <input type="text" 
                       name="items[${rowId}][subproject_name]" 
                       class="form-control form-control-sm subproject-input" 
                       placeholder="اختر أو أدخل مشروع فرعي"
                       list="subproject-list-${rowId}"
                       value="${escapeAttr(existingData?.subproject_name)}">
                <datalist id="subproject-list-${rowId}">
                </datalist>
            </td>
            <td>
                <input type="text" name="items[${rowId}][notes]" class="form-control form-control-sm" value="${escapeAttr(existingData?.notes)}">
            </td>
            <td class="text-center">
                <input type="checkbox" name="items[${rowId}][is_measurable]" class="form-check-input" value="1" ${existingData?.is_measurable !== undefined ? (existingData.is_measurable ? 'checked' : '') : 'checked'}>
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="items[${rowId}][total_quantity]" 
                       class="form-control form-control-sm total-quantity" 
                       value="${existingData?.total_quantity || 0}">
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="items[${rowId}][estimated_daily_qty]" 
                       class="form-control form-control-sm estimated-daily-qty" 
                       value="${existingData?.estimated_daily_qty || 0}">
            </td>
            <td>
                <input type="number" step="1" min="0" name="items[${rowId}][duration]" 
                       class="form-control form-control-sm duration-input" 
                       value="${existingData?.duration || 1}">
            </td>
            <td>
                <select name="items[${rowId}][predecessor]" class="form-select form-select-sm predecessor-select" data-predecessor-stale="1">
                    <option value="">بدون</option>
                </select>
            </td>
            <td>
                <select name="items[${rowId}][dependency_type]" class="form-select form-select-sm">
                    <option value="end_to_start" ${(existingData?.dependency_type || 'end_to_start') === 'end_to_start' ? 'selected' : ''}>بعد الانتهاء</option>
                    <option value="start_to_start" ${existingData?.dependency_type === 'start_to_start' ? 'selected' : ''}>بعد البداية</option>
                </select>
            </td>
            <td>
                <input type="number" step="1" name="items[${rowId}][lag]" 
                       class="form-control form-control-sm lag-input" 
                       value="${existingData?.lag || 0}">
            </td>
            <td>
                <input type="date" name="items[${rowId}][start_date]" 
                       class="form-control form-control-sm item-start-date" 
                       value="${existingData?.start_date || ''}" 
                       readonly>
            </td>
            <td>
                <input type="date" name="items[${rowId}][end_date]" 
                       class="form-control form-control-sm item-end-date" 
                       value="${existingData?.end_date || ''}" 
                       readonly>
            </td>
            <td class="text-center">
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-success duplicate-btn" title="نسخ">
                        <i class="fas fa-copy"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger remove-btn" title="حذف">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;

        return tr;
    }

    // Rows are cloned and re-inserted whenever the view is rebuilt, so everything a
    // row reacts to is handled by delegation on the container in initEventListeners.
    // Binding per row as well would run each handler twice and leave freshly cloned
    // rows dead whenever the re-attach lookup failed.
    function recalculateRowDuration(row) {
        const total = parseFloat(row.querySelector('.total-quantity')?.value || 0);
        const daily = parseFloat(row.querySelector('.estimated-daily-qty')?.value || 0);
        const duration = row.querySelector('.duration-input');

        if (duration && total > 0 && daily > 0) {
            duration.value = Math.ceil(total / daily);
        }
    }

    /**
     * A row can reference a work item that is no longer in the catalogue (soft deleted
     * but still used by the project), so fall back to what the row itself carries.
     */
    function workItemForRow(row) {
        const workItemId = row.dataset.workItemId;

        return state.workItems.find(item => item.id == workItemId)
            || { id: workItemId, name: row.querySelector('td:nth-child(4) strong')?.textContent || '' };
    }

    // ========================================
    // Subproject Management
    // ========================================
    function updateSubprojectDatalist(inputElement) {
        // Get all unique subproject names from all rows
        const subprojects = new Set();

        document.querySelectorAll('.subproject-input').forEach(input => {
            const value = input.value.trim();
            if (value && value !== inputElement.value.trim()) {
                subprojects.add(value);
            }
        });

        // Update the datalist
        const datalistId = inputElement.getAttribute('list');
        const datalist = document.getElementById(datalistId);

        if (datalist) {
            datalist.innerHTML = '';
            subprojects.forEach(subproject => {
                const option = document.createElement('option');
                option.value = subproject;
                datalist.appendChild(option);
            });
        }
    }

    function getUniqueSubprojects() {
        const subprojects = new Set();

        document.querySelectorAll('.subproject-input').forEach(input => {
            const value = input.value.trim();
            if (value) {
                subprojects.add(value);
            }
        });

        return Array.from(subprojects);
    }

    // ========================================
    // Duplicate Item
    // ========================================
    function insertDuplicate(originalRow, item) {
        const isMeasurable = originalRow.querySelector('input[name*="[is_measurable]"]');

        state.itemCounter++;
        const newRowId = `item_${state.itemCounter}_${Date.now()}`;
        const newRow = createItemRow(newRowId, item, {
            total_quantity: originalRow.querySelector('.total-quantity')?.value || 0,
            estimated_daily_qty: originalRow.querySelector('.estimated-daily-qty')?.value || 0,
            duration: originalRow.querySelector('.duration-input')?.value || 1,
            notes: originalRow.querySelector('[name*="[notes]"]')?.value || '',
            is_measurable: isMeasurable ? isMeasurable.checked : true,
            subproject_name: originalRow.querySelector('.subproject-input')?.value || '',
            lag: originalRow.querySelector('.lag-input')?.value || 0,
            dependency_type: originalRow.querySelector('select[name*="[dependency_type]"]')?.value || 'end_to_start'
        });

        originalRow.insertAdjacentElement('afterend', newRow);

        return newRow;
    }

    function duplicateItem(originalRow, item) {
        insertDuplicate(originalRow, item);
        updateItemOrders();
        calculateAllDates();

        showNotification('success', `تم نسخ: ${item.name}`);
    }

    /**
     * A row appended straight to the container while the grouped view is open would
     * sit next to the group rows instead of inside one, so the groups are rebuilt.
     */
    function regroupIfNeeded() {
        if (state.viewMode === 'grouped') {
            renderGroupedView();
        }
    }

    /**
     * Sort rows by the live item_order inputs. That value is rewritten on every
     * drag/add/delete, so it is the single source of truth across view switches.
     */
    function sortRowsByItemOrder(rows) {
        return Array.from(rows).sort((a, b) => {
            const orderA = parseInt(a.querySelector('input[name*="[item_order]"]')?.value ?? '999999', 10);
            const orderB = parseInt(b.querySelector('input[name*="[item_order]"]')?.value ?? '999999', 10);
            return orderA - orderB;
        });
    }

    function captureFlatItemOrder(rows = null) {
        const list = rows
            ? Array.from(rows)
            : Array.from(elements.container?.querySelectorAll('tr[data-item-id]') || []);
        state.flatItemOrder = list.map(row => row.dataset.itemId).filter(Boolean);
    }

    /**
     * Grouped view must not renumber every row (that would replace the flat order
     * with the subproject grouping). Only drop deleted ids and append new ones.
     */
    function syncFlatItemOrderAfterMutation() {
        const rows = Array.from(elements.container?.querySelectorAll('tr[data-item-id]') || []);
        const existingIds = new Set(rows.map(row => row.dataset.itemId));

        state.flatItemOrder = (state.flatItemOrder || []).filter(id => existingIds.has(id));
        const known = new Set(state.flatItemOrder);

        let maxOrder = -1;
        rows.forEach(row => {
            const order = parseInt(row.querySelector('input[name*="[item_order]"]')?.value ?? '-1', 10);
            if (!Number.isNaN(order)) {
                maxOrder = Math.max(maxOrder, order);
            }
        });

        rows.forEach(row => {
            const id = row.dataset.itemId;
            if (!id || known.has(id)) return;

            maxOrder += 1;
            const orderInput = row.querySelector('input[name*="[item_order]"]');
            const badge = row.querySelector('.badge');
            if (orderInput) orderInput.value = maxOrder;
            if (badge) badge.textContent = maxOrder + 1;

            const prevId = row.previousElementSibling?.dataset?.itemId;
            const prevIndex = prevId ? state.flatItemOrder.indexOf(prevId) : -1;
            if (prevIndex !== -1) {
                state.flatItemOrder.splice(prevIndex + 1, 0, id);
            } else {
                state.flatItemOrder.push(id);
            }
            known.add(id);
        });
    }

    function initSubprojectOrderFromState() {
        const named = (state.subprojects || [])
            .slice()
            .sort((a, b) => ((a.sort_order ?? 0) - (b.sort_order ?? 0)) || String(a.name).localeCompare(String(b.name)))
            .map(sp => sp.name)
            .filter(Boolean);

        state.subprojectOrder = named;
    }

    function syncSubprojectOrderFromDom() {
        const names = [];
        document.querySelectorAll('.category-group-row .category-header').forEach(header => {
            const name = header.dataset.category;
            if (name && name !== 'بدون مشروع فرعي') {
                names.push(name);
            }
        });
        state.subprojectOrder = names;
    }

    function ensureSubprojectInOrder(name) {
        if (!name || name === 'بدون مشروع فرعي') return;
        if (!state.subprojectOrder.includes(name)) {
            state.subprojectOrder.push(name);
        }
    }

    function sortGroupEntries(entries) {
        return entries.sort((a, b) => {
            const indexOf = name => {
                if (name === 'بدون مشروع فرعي') return Number.MAX_SAFE_INTEGER;
                const idx = state.subprojectOrder.indexOf(name);
                return idx === -1 ? Number.MAX_SAFE_INTEGER - 1 : idx;
            };
            return indexOf(a[0]) - indexOf(b[0]);
        });
    }

    /**
     * After an item is dropped into another group's tbody, mirror the destination
     * name into the row's subproject field so the next regroup keeps it there.
     */
    function syncSubprojectNameAfterItemMove(evt) {
        const row = evt.item;
        const tbody = row?.closest?.('.sortable-category-items');
        if (!tbody) return;

        const categoryName = tbody.dataset.category || '';
        const input = row.querySelector('.subproject-input');
        if (!input) return;

        input.value = categoryName === 'بدون مشروع فرعي' ? '' : categoryName;
        if (input.value) ensureSubprojectInOrder(input.value);
    }

    // ========================================
    // Update Functions
    // ========================================
    function syncFlatItemOrderFromGroupDrag(tbody) {
        if (!state.flatItemOrder || state.flatItemOrder.length === 0) return;

        const groupRows = Array.from(tbody.querySelectorAll('tr[data-item-id]'));
        const groupRowIds = groupRows.map(row => row.dataset.itemId).filter(Boolean);

        if (groupRowIds.length < 2) return;

        const slots = [];
        groupRowIds.forEach(id => {
            const idx = state.flatItemOrder.indexOf(id);
            if (idx !== -1) {
                slots.push(idx);
            }
        });

        slots.sort((a, b) => a - b);

        groupRowIds.forEach((id, i) => {
            if (i < slots.length) {
                state.flatItemOrder[slots[i]] = id;
            }
        });

        updateItemOrdersFromFlatOrder();
    }

    function updateItemOrdersFromFlatOrder() {
        if (!state.flatItemOrder) return;

        const allRows = elements.container.querySelectorAll('tr[data-item-id]');
        allRows.forEach(row => {
            const id = row.dataset.itemId;
            const globalIndex = state.flatItemOrder.indexOf(id);
            if (globalIndex !== -1) {
                const orderInput = row.querySelector('input[name*="item_order"]');
                if (orderInput) orderInput.value = globalIndex;
            }
        });

        updatePredecessors();
    }

    function updateItemOrders() {
        if (state.viewMode === 'grouped') {
            updateItemOrdersFromFlatOrder();
            return;
        }

        const rows = elements.container.querySelectorAll('tr[data-item-id]');

        rows.forEach((row, index) => {
            const orderInput = row.querySelector('input[name*="item_order"]');
            if (orderInput) orderInput.value = index;
        });

        captureFlatItemOrder(rows);
        updatePredecessors();
    }

    // Every predecessor select can list every other row, so eagerly filling them all
    // costs N*(N-1) option nodes. Instead each select holds only its blank and its
    // selected option, and the full list is built for a single select on first use.
    function getPredecessorChoices() {
        const choices = [];

        elements.container.querySelectorAll('tr[data-item-id]').forEach((row, index) => {
            const itemName = row.querySelector('td:nth-child(4) strong')?.textContent || 'Item';
            choices.push({ id: row.dataset.itemId, label: `${index + 1}. ${itemName}` });
        });

        return choices;
    }

    function hasOption(select, value) {
        return Array.from(select.options).some(option => option.value === value);
    }

    function populatePredecessorSelect(select) {
        const currentValue = select.value;
        const ownRowId = select.closest('tr[data-item-id]')?.dataset.itemId;
        const fragment = document.createDocumentFragment();

        fragment.appendChild(new Option('بدون', ''));

        getPredecessorChoices().forEach(choice => {
            if (choice.id !== ownRowId) {
                fragment.appendChild(new Option(choice.label, choice.id));
            }
        });

        select.innerHTML = '';
        select.appendChild(fragment);
        select.value = currentValue;
        delete select.dataset.predecessorStale;
    }

    // Assigning select.value only works when the matching option exists, which is no
    // longer guaranteed while a select is still collapsed.
    function setPredecessorValue(select, rowId) {
        if (!select) return false;

        const value = rowId || '';

        if (value && !hasOption(select, value)) {
            const label = getPredecessorChoices().find(choice => choice.id === value)?.label;
            if (!label) return false;
            select.appendChild(new Option(label, value));
        }

        select.value = value;
        return select.value === value;
    }

    function updatePredecessors() {
        const labels = new Map(getPredecessorChoices().map(choice => [choice.id, choice.label]));

        elements.container.querySelectorAll('.predecessor-select').forEach(select => {
            const currentValue = select.value;

            select.innerHTML = '';
            select.appendChild(new Option('بدون', ''));

            // Drop values whose row no longer exists, matching the previous rebuild.
            if (currentValue && labels.has(currentValue)) {
                select.appendChild(new Option(labels.get(currentValue), currentValue));
                select.value = currentValue;
            }

            select.dataset.predecessorStale = '1';
        });
    }

    function initPredecessorLazyLoad() {
        if (!elements.container) return;

        const expandOnDemand = event => {
            const select = event.target.closest?.('.predecessor-select');
            if (select?.dataset.predecessorStale) {
                populatePredecessorSelect(select);
            }
        };

        elements.container.addEventListener('mousedown', expandOnDemand);
        elements.container.addEventListener('focusin', expandOnDemand);
        elements.container.addEventListener('keydown', expandOnDemand);
    }

    function updateEmptyState() {
        if (!elements.emptyState) return;

        const hasItems = elements.container.children.length > 0;
        elements.emptyState.classList.toggle('d-none', hasItems);
    }

    // ========================================
    // Filter Items
    // ========================================
    function filterItems() {
        const query = elements.itemsFilter.value.trim();
        if (!query) {
            // If query is empty, show all rows and category groups
            const rows = elements.container.querySelectorAll('tr[data-item-id]');
            rows.forEach(row => row.style.display = '');
            const categoryGroups = elements.container.querySelectorAll('.category-group-row');
            categoryGroups.forEach(group => group.style.display = '');
            return;
        }

        const queryLower = query.toLowerCase();
        const rows = elements.container.querySelectorAll('tr[data-item-id]');
        let visibleCount = 0;

        rows.forEach(row => {
            // Search in specific columns: name, category, unit, subproject, notes
            const nameCell = row.querySelector('td:nth-child(4)'); // Item name column
            const subprojectInput = row.querySelector('input[name*="[subproject_name]"]');
            const notesInput = row.querySelector('input[name*="[notes]"]');

            let searchableText = '';

            // Get text from name cell (includes name, category, unit)
            if (nameCell) {
                // Get all text content including nested elements
                const nameText = nameCell.innerText || nameCell.textContent || '';
                searchableText += nameText.toLowerCase();
            }

            // Get subproject name
            if (subprojectInput && subprojectInput.value) {
                searchableText += ' ' + subprojectInput.value.toLowerCase();
            }

            // Get notes
            if (notesInput && notesInput.value) {
                searchableText += ' ' + notesInput.value.toLowerCase();
            }

            // Normalize whitespace and search
            searchableText = searchableText.replace(/\s+/g, ' ').trim();

            // Check if query matches (supports both Arabic and English)
            const matches = searchableText.includes(queryLower);
            row.style.display = matches ? '' : 'none';

            if (matches) {
                visibleCount++;
            }
        });

        // If in grouped view, show/hide category groups based on visible items
        if (state.viewMode === 'grouped') {
            const categoryGroups = elements.container.querySelectorAll('.category-group-row');
            categoryGroups.forEach(group => {
                const groupRows = group.querySelectorAll('tr[data-item-id]');
                const hasVisibleRows = Array.from(groupRows).some(row => row.style.display !== 'none');
                group.style.display = hasVisibleRows ? '' : 'none';
            });
        }
    }

    function resetFilter() {
        elements.itemsFilter.value = '';
        const rows = elements.container.querySelectorAll('tr[data-item-id]');
        rows.forEach(row => row.style.display = '');
    }

    function toggleSelectAll() {
        const checkboxes = elements.container.querySelectorAll('.item-checkbox');
        checkboxes.forEach(cb => cb.checked = elements.selectAllItems.checked);
    }

    // ========================================
    // Bulk Actions Execute
    // ========================================
    function executeBulkAction() {
        const actionSelect = document.getElementById('bulk-action-select');
        const action = actionSelect?.value;

        if (!action) {
            showNotification('warning', 'الرجاء اختيار عملية أولاً');
            return;
        }

        const selectedRows = getSelectedRows();

        if (selectedRows.length === 0) {
            showNotification('warning', 'الرجاء تحديد بنود أولاً');
            return;
        }

        // Execute the selected action
        switch (action) {
            case 'delete':
                bulkDelete(selectedRows);
                break;
            case 'duplicate':
                bulkDuplicate(selectedRows);
                break;
            case 'move':
                bulkMove(selectedRows);
                break;
            case 'export':
                bulkExport(selectedRows);
                break;
            default:
                showNotification('warning', 'عملية غير معروفة');
        }

        // Reset select
        if (actionSelect) {
            actionSelect.value = '';
        }
    }

    function getSelectedRows() {
        const selected = [];

        // Get from flat view
        const flatCheckboxes = elements.container.querySelectorAll('.item-checkbox:checked');
        flatCheckboxes.forEach(checkbox => {
            const row = checkbox.closest('tr[data-item-id]');
            if (row) selected.push(row);
        });

        // Get from grouped view
        const groupedCheckboxes = document.querySelectorAll('.sortable-category-items .item-checkbox:checked');
        groupedCheckboxes.forEach(checkbox => {
            const row = checkbox.closest('tr[data-item-id]');
            if (row && !selected.includes(row)) {
                selected.push(row);
            }
        });

        return selected;
    }

    function bulkDelete(selectedRows) {
        if (!confirm(`هل أنت متأكد من حذف ${selectedRows.length} بند؟`)) {
            return;
        }

        selectedRows.forEach(row => row.remove());

        updateItemOrders();
        calculateAllDates();
        updateEmptyState();
        regroupIfNeeded();

        if (elements.selectAllItems) {
            elements.selectAllItems.checked = false;
        }

        showNotification('success', `تم حذف ${selectedRows.length} بند بنجاح`);
    }

    function bulkDuplicate(selectedRows) {
        selectedRows.forEach(row => insertDuplicate(row, workItemForRow(row)));

        updateItemOrders();
        calculateAllDates();
        regroupIfNeeded();

        if (elements.selectAllItems) {
            elements.selectAllItems.checked = false;
        }

        showNotification('success', `تم نسخ ${selectedRows.length} بند بنجاح`);
    }

    // Store selected rows for bulk move operation
    let pendingMoveRows = null;
    let pendingMoveCategoryName = null; // For subproject bulk move
    let pendingMoveSelectAllCheckbox = null; // For resetting after subproject move
    let pendingMoveBulkActionSelect = null; // For resetting after subproject move

    function bulkMove(selectedRows) {
        // Store rows for later use
        pendingMoveRows = selectedRows;
        pendingMoveCategoryName = null; // Clear category name for regular bulk move
        pendingMoveSelectAllCheckbox = null; // Clear for regular bulk move
        pendingMoveBulkActionSelect = null; // Clear for regular bulk move

        // Get unique subprojects
        const subprojects = getUniqueSubprojects();

        // Update modal with item count
        const itemsCountEl = document.getElementById('moveItemsCount');
        if (itemsCountEl) {
            itemsCountEl.textContent = selectedRows.length;
        }

        // Reset modal title to default
        const modalTitle = document.querySelector('#moveToSubprojectModalLabel');
        if (modalTitle) {
            modalTitle.innerHTML = '<i class="fas fa-folder-open me-2"></i> نقل البنود إلى مشروع فرعي';
        }

        // Populate subprojects select
        const subprojectSelect = document.getElementById('subprojectSelect');
        const newSubprojectInput = document.getElementById('newSubprojectName');

        if (subprojectSelect) {
            // Clear existing options except the first one
            subprojectSelect.innerHTML = '<option value="">-- مشروع جديد --</option>';

            // Add existing subprojects
            subprojects.forEach(subproject => {
                const option = document.createElement('option');
                option.value = subproject;
                option.textContent = subproject;
                subprojectSelect.appendChild(option);
            });
        }

        // Clear new subproject input
        if (newSubprojectInput) {
            newSubprojectInput.value = '';
        }

        // Show modal using Bootstrap
        const modalElement = document.getElementById('moveToSubprojectModal');
        if (modalElement && typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        } else if (modalElement) {
            // Fallback if Bootstrap is not available
            modalElement.style.display = 'block';
            modalElement.classList.add('show');
        }
    }

    function executeBulkMove(selectedRows, subprojectName, fromCategoryName = null) {
        if (!subprojectName || !subprojectName.trim()) {
            showNotification('warning', 'الرجاء اختيار مشروع فرعي أو إدخال اسم جديد');
            return;
        }

        const trimmedName = subprojectName.trim();

        selectedRows.forEach(row => {
            const subprojectInput = row.querySelector('.subproject-input');
            if (subprojectInput) {
                subprojectInput.value = trimmedName;
            }
        });

        if (elements.selectAllItems) {
            elements.selectAllItems.checked = false;
        }

        const successMessage = fromCategoryName
            ? `✅ تم نقل ${selectedRows.length} بند من "${fromCategoryName}" إلى "${trimmedName}"`
            : `تم نقل ${selectedRows.length} بند إلى "${trimmedName}"`;
        showNotification('success', successMessage);

        // If moving from a subproject category, re-render grouped view and reset UI elements
        if (fromCategoryName && typeof renderGroupedView === 'function') {
            setTimeout(() => renderGroupedView(), 300);

            // Reset select all checkbox and bulk action select if they exist
            if (pendingMoveSelectAllCheckbox) {
                pendingMoveSelectAllCheckbox.checked = false;
            }
            if (pendingMoveBulkActionSelect) {
                pendingMoveBulkActionSelect.value = '';
            }
        }

        // Clear pending rows and UI elements
        pendingMoveRows = null;
        pendingMoveCategoryName = null;
        pendingMoveSelectAllCheckbox = null;
        pendingMoveBulkActionSelect = null;
    }

    function bulkExport(selectedRows) {
        const exportData = [];

        selectedRows.forEach((row, index) => {
            const workItemId = row.dataset.workItemId;
            const workItem = state.workItems.find(item => item.id == workItemId);

            exportData.push({
                '#': index + 1,
                'اسم الصنف': workItem?.name || '',
                'الوحدة': workItem?.unit || '',
                'المشروع الفرعي': row.querySelector('.subproject-input')?.value || '',
                'الملاحظات': row.querySelector('[name*="[notes]"]')?.value || '',
                'الكمية الإجمالية': row.querySelector('.total-quantity')?.value || '',
                'الكمية اليومية': row.querySelector('.estimated-daily-qty')?.value || '',
                'المدة': row.querySelector('.duration-input')?.value || '',
                'تاريخ البداية': row.querySelector('.item-start-date')?.value || '',
                'تاريخ النهاية': row.querySelector('.item-end-date')?.value || ''
            });
        });

        const csv = convertToCSV(exportData);
        downloadCSV(csv, `selected_items_${Date.now()}.csv`);

        if (elements.selectAllItems) {
            elements.selectAllItems.checked = false;
        }

        showNotification('success', `تم تصدير ${selectedRows.length} بند`);
    }

    function convertToCSV(data) {
        if (data.length === 0) return '';

        const headers = Object.keys(data[0]);
        const csvRows = [];

        csvRows.push(headers.join(','));

        data.forEach(row => {
            const values = headers.map(header => {
                const value = row[header] || '';
                return `"${value}"`;
            });
            csvRows.push(values.join(','));
        });

        return csvRows.join('\n');
    }

    function downloadCSV(csv, filename) {
        const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);

        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // ========================================
    // Calculate Dates (DOM glue around scheduler.js)
    // ========================================
    function calculateAllDates() {
        const startDate = elements.startDate?.value;
        if (!startDate) return;

        const projectStart = parseLocalDate(startDate);
        if (!projectStart) return;

        const weeklyHolidays = getWeeklyHolidays();
        const itemsData = new Map();

        elements.container.querySelectorAll('tr[data-item-id]').forEach(row => {
            itemsData.set(row.dataset.itemId, {
                row,
                duration: parseInt(row.querySelector('.duration-input')?.value || 1),
                predecessorId: row.querySelector('.predecessor-select')?.value || null,
                dependencyType: row.querySelector('select[name*="[dependency_type]"]')?.value || 'end_to_start',
                lag: parseInt(row.querySelector('.lag-input')?.value || 0),
                startDate: null,
                endDate: null,
                calculated: false
            });
        });

        const { cycle, unresolvable, overflow } = scheduleItems(itemsData, projectStart, weeklyHolidays);

        if (cycle.length > 0) {
            const names = describeCycle(itemsData, cycle, (_id, data) =>
                data?.row?.querySelector('td:nth-child(4) strong')?.textContent || _id
            );
            showNotification('error', '⚠️ تبعية دائرية! البنود: ' + names.join(' → '));
            console.error('🔴 Cannot calculate dates due to circular dependency', names);
            return;
        }

        if (unresolvable.length > 0) {
            showNotification('warning', `⚠️ ${unresolvable.length} بند له سابق مفقود ولن تُحسب تواريخه`);
        }

        if (overflow) {
            showNotification('error', '⚠️ خطأ في حساب التواريخ - المدة كبيرة جداً');
        }

        itemsData.forEach(updateRowDates);

        const latest = maxEndDate(itemsData);
        if (latest && elements.endDate) {
            elements.endDate.value = formatDate(latest);
        }

        if (state.viewMode === 'grouped') {
            updateSubprojectTotals();
        }
    }

    // Typing fires an event per keystroke and `change` fires again on blur, so the
    // whole pass is coalesced instead of running once per event.
    const scheduleRecalc = debounce(calculateAllDates, 150);

    function getWeeklyHolidays() {
        const value = elements.weeklyHolidaysInput?.value || '';
        return value.split(',').map(v => parseInt(v)).filter(v => !isNaN(v));
    }

    function updateRowDates(data) {
        if (!data.row) return;

        const startInput = data.row.querySelector('.item-start-date');
        const endInput = data.row.querySelector('.item-end-date');

        if (startInput && data.startDate) {
            startInput.value = formatDate(data.startDate);
        }

        if (endInput && data.endDate) {
            endInput.value = formatDate(data.endDate);
        }
    }

    // ========================================
    // Sortable
    // ========================================
    let sortables = [];

    /**
     * Every instance stays bound to its element until it is destroyed, so creating a
     * new one on each view switch left the old ones running and fired `onEnd` twice.
     * The flat instance also has to go while grouped: its direct children are then
     * the group rows, and dragging an item inside a group would drag the whole group.
     */
    function destroySortables() {
        sortables.forEach(sortable => sortable.destroy());
        sortables = [];
    }

    function initSortable() {
        if (typeof Sortable === 'undefined' || !elements.container) return;

        destroySortables();
        sortables.push(Sortable.create(elements.container, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: () => {
                updateItemOrders();
                calculateAllDates();
            }
        }));
    }

    // ========================================
    // Load Existing Items (Edit Mode)
    // ========================================
    function loadExistingItems() {
        if (!state.projectItems || state.projectItems.length === 0) {
            return;
        }

        // Create a mapping from old project_item.id to new rowId
        let oldToNewMapping = new Map();

        const sortedProjectItems = state.projectItems.slice().sort((a, b) =>
            ((a.item_order ?? 0) - (b.item_order ?? 0))
        );

        sortedProjectItems.forEach(projectItem => {
            const workItem = projectItem.work_item;
            if (!workItem) return;

            // The catalogue only carries live work items, but a project can still use
            // a soft-deleted one; register it so later lookups by row resolve.
            if (!state.workItems.some(existing => existing.id == workItem.id)) {
                state.workItems.push(workItem);
            }

            // Use the same naming convention as create mode: item_X_timestamp
            state.itemCounter++;
            const rowId = `item_${state.itemCounter}_${Date.now()}`;

            // Store mapping for predecessor resolution
            // Convert ID to number to ensure consistency
            const itemId = parseInt(projectItem.id);
            oldToNewMapping.set(itemId, rowId);

            const row = createItemRow(rowId, workItem, {
                id: projectItem.id, // 🔑 Keep the original project_item ID for updates
                total_quantity: projectItem.total_quantity,
                estimated_daily_qty: projectItem.estimated_daily_qty,
                duration: projectItem.duration,
                notes: projectItem.notes,
                is_measurable: projectItem.is_measurable !== undefined ? projectItem.is_measurable : true,
                subproject_name: projectItem.subproject_name,
                lag: projectItem.lag,
                dependency_type: projectItem.dependency_type,
                start_date: projectItem.start_date,
                end_date: projectItem.end_date,
                item_order: projectItem.item_order ?? 0
            });

            if (projectItem.subproject_name) {
                ensureSubprojectInOrder(projectItem.subproject_name);
            }

            // Store the old ID in dataset for predecessor resolution
            if (projectItem.predecessor) {
                row.dataset.pendingPredecessor = projectItem.predecessor;
                row.dataset.oldPredecessorId = projectItem.predecessor;
            }

            // Store the old project_item.id for tracking
            row.dataset.oldProjectItemId = projectItem.id;

            elements.container.appendChild(row);
        });

        captureFlatItemOrder();

        // Store mapping in a closure for setTimeout
        const mapping = oldToNewMapping;

        setTimeout(() => {
            // FIRST: Update all predecessor selects with current items
            updatePredecessors();

            // SECOND: Resolve predecessor IDs from old project_item.id to new rowId
            const rows = elements.container.querySelectorAll('tr[data-pending-predecessor]');

            rows.forEach(row => {
                const oldPredecessorIdStr = row.dataset.oldPredecessorId;
                const oldPredecessorId = parseInt(oldPredecessorIdStr);
                const select = row.querySelector('.predecessor-select');

                if (select && oldPredecessorId && !isNaN(oldPredecessorId)) {
                    const newPredecessorRowId = mapping.get(oldPredecessorId);

                    if (newPredecessorRowId) {
                        setPredecessorValue(select, newPredecessorRowId);
                    }

                    delete row.dataset.pendingPredecessor;
                    delete row.dataset.oldPredecessorId;
                }
            });

            // THIRD: Recalculate dates - this will overwrite existing dates with calculated ones
            updateItemOrders();
            calculateAllDates();
        }, 100);

        updateEmptyState();
    }

    // ========================================
    // Template & Draft Loading
    // ========================================
    async function loadTemplateItems(templateId) {
        try {
            // Show loading notification
            showNotification('info', 'جاري تحميل بيانات القالب...');

            const response = await fetch(`/project-templates/${templateId}/data`);
            const data = await response.json();

            if (data.items && Array.isArray(data.items)) {
                const itemCount = data.items.length;
                const currentItemCount = elements.container.querySelectorAll('tr[data-item-id]').length;

                // Confirmation message
                let confirmMessage = `سيتم إضافة ${itemCount} بند من القالب`;

                if (currentItemCount > 0) {
                    confirmMessage += `\n\nيوجد حالياً ${currentItemCount} بند في المشروع.`;
                    confirmMessage += `\nسيتم إضافة البنود الجديدة إلى القائمة الموجودة.`;
                }

                confirmMessage += '\n\nهل تريد المتابعة؟';

                // Ask for confirmation
                if (!confirm(confirmMessage)) {
                    // Uncheck the checkbox if user cancels
                    const checkbox = document.querySelector(`.template-checkbox[value="${templateId}"]`);
                    if (checkbox) {
                        checkbox.checked = false;
                    }
                    showNotification('info', 'تم إلغاء العملية');
                    return;
                }

                // Load items with all template data
                let addedCount = 0;
                // Create mapping: work_item_id -> rowId (for predecessor resolution)
                const workItemToRowIdMap = new Map();
                // Store predecessor relationships: rowId -> predecessor work_item_id
                const predecessorMappings = new Map();

                data.items.forEach(templateItem => {
                    if (templateItem.work_item) {
                        // Add item with existing data from template (like loadDraftItems)
                        const item = templateItem.work_item;
                        state.itemCounter++;
                        const rowId = `item_${state.itemCounter}_${Date.now()}`;

                        // Store mapping
                        workItemToRowIdMap.set(templateItem.work_item_id, rowId);

                        // Store predecessor relationship if exists (predecessor is work_item_id)
                        if (templateItem.predecessor) {
                            predecessorMappings.set(rowId, templateItem.predecessor);
                        }

                        const row = createItemRow(rowId, item, {
                            total_quantity: templateItem.total_quantity || templateItem.default_quantity || 0,
                            estimated_daily_qty: templateItem.estimated_daily_qty || 0,
                            duration: templateItem.duration || 1,
                            notes: templateItem.notes || '',
                            subproject_name: templateItem.subproject_name || '',
                            lag: templateItem.lag || 0,
                            dependency_type: templateItem.dependency_type || 'end_to_start',
                            start_date: templateItem.start_date || '',
                            end_date: templateItem.end_date || '',
                            is_measurable: templateItem.is_measurable !== undefined ? templateItem.is_measurable : true,
                            item_label: templateItem.item_label || '',
                            daily_quantity: templateItem.daily_quantity || '',
                            shift: templateItem.shift || ''
                        });

                        elements.container.appendChild(row);
                        addedCount++;
                    }
                });

                // Update predecessors dropdowns first
                updateItemOrders();
                updatePredecessors();

                // Now set predecessor values (convert work_item_id to rowId)
                predecessorMappings.forEach((predecessorWorkItemId, currentRowId) => {
                    const predecessorRowId = workItemToRowIdMap.get(predecessorWorkItemId);
                    if (predecessorRowId) {
                        const currentRow = document.querySelector(`tr[data-item-id="${currentRowId}"]`);
                        if (currentRow) {
                            const predecessorSelect = currentRow.querySelector('.predecessor-select');
                            if (predecessorSelect) {
                                setPredecessorValue(predecessorSelect, predecessorRowId);
                                // Trigger change event to update dependencies
                                predecessorSelect.dispatchEvent(new Event('change'));
                            }
                        }
                    }
                });

                calculateAllDates();
                updateEmptyState();
                regroupIfNeeded();

                // Apply template settings (working_days, daily_work_hours, weekly_holidays)
                if (data.working_days) {
                    const workingDaysInput = document.getElementById('working_days');
                    if (workingDaysInput) {
                        workingDaysInput.value = data.working_days;
                    }
                }

                if (data.daily_work_hours) {
                    const dailyWorkHoursInput = document.getElementById('daily_work_hours');
                    if (dailyWorkHoursInput) {
                        dailyWorkHoursInput.value = data.daily_work_hours;
                    }
                }

                if (data.weekly_holidays) {
                    const weeklyHolidaysInput = document.getElementById('weekly-holidays-input');
                    if (weeklyHolidaysInput) {
                        weeklyHolidaysInput.value = data.weekly_holidays;
                        // Update checkboxes
                        const holidaysArray = data.weekly_holidays.split(',');
                        document.querySelectorAll('.holiday-checkbox').forEach(checkbox => {
                            checkbox.checked = holidaysArray.includes(checkbox.value);
                        });
                    }
                }

                // Apply project_type_id if available
                if (data.project_type_id) {
                    const projectTypeSelect = document.getElementById('project_type_id');
                    if (projectTypeSelect) {
                        projectTypeSelect.value = data.project_type_id;
                    }
                }

                showNotification('success', `✅ تم إضافة ${addedCount} بند من القالب بنجاح`);
            } else {
                showNotification('warning', 'القالب فارغ - لا توجد بنود للإضافة');

                // Uncheck the checkbox
                const checkbox = document.querySelector(`.template-checkbox[value="${templateId}"]`);
                if (checkbox) {
                    checkbox.checked = false;
                }
            }
        } catch (error) {
            console.error('Template loading error:', error);
            showNotification('error', 'حدث خطأ في تحميل القالب');

            // Uncheck the checkbox on error
            const checkbox = document.querySelector(`.template-checkbox[value="${templateId}"]`);
            if (checkbox) {
                checkbox.checked = false;
            }
        }
    }

    async function loadDraftItems(draftId) {
        try {
            // Show loading notification
            showNotification('info', 'جاري تحميل بيانات المسودة...');

            const response = await fetch(`/projects/${draftId}/items-data`);

            if (!response.ok) {
                throw new Error('Failed to load draft items');
            }

            const data = await response.json();

            if (data.items && Array.isArray(data.items)) {
                const itemCount = data.items.length;
                const currentItemCount = elements.container.querySelectorAll('tr[data-item-id]').length;

                // Confirmation message
                let confirmMessage = `سيتم إضافة ${itemCount} بند من المسودة`;

                if (currentItemCount > 0) {
                    confirmMessage += `\n\nيوجد حالياً ${currentItemCount} بند في المشروع.`;
                    confirmMessage += `\nسيتم إضافة البنود الجديدة إلى القائمة الموجودة.`;
                }

                confirmMessage += '\n\nهل تريد المتابعة؟';

                // Ask for confirmation
                if (!confirm(confirmMessage)) {
                    // Uncheck the checkbox if user cancels
                    const checkbox = document.querySelector(`.template-checkbox[value="${draftId}"]`);
                    if (checkbox) {
                        checkbox.checked = false;
                    }
                    showNotification('info', 'تم إلغاء العملية');
                    return;
                }

                // Load items
                let addedCount = 0;
                data.items.forEach(projectItem => {
                    if (projectItem.work_item) {
                        // Add item with existing data
                        const item = projectItem.work_item;
                        state.itemCounter++;
                        const rowId = `item_${state.itemCounter}_${Date.now()}`;

                        const row = createItemRow(rowId, item, {
                            total_quantity: projectItem.total_quantity,
                            estimated_daily_qty: projectItem.estimated_daily_qty,
                            duration: projectItem.duration,
                            notes: projectItem.notes,
                            subproject_name: projectItem.subproject_name,
                            lag: projectItem.lag,
                            dependency_type: projectItem.dependency_type
                        });

                        elements.container.appendChild(row);
                        addedCount++;
                    }
                });

                updateItemOrders();
                calculateAllDates();
                updateEmptyState();
                regroupIfNeeded();

                showNotification('success', `✅ تم تحميل ${addedCount} بند من المسودة بنجاح`);
            } else {
                showNotification('warning', 'المسودة فارغة - لا توجد بنود للإضافة');

                // Uncheck the checkbox
                const checkbox = document.querySelector(`.template-checkbox[value="${draftId}"]`);
                if (checkbox) {
                    checkbox.checked = false;
                }
            }
        } catch (error) {
            console.error('Draft loading error:', error);
            showNotification('error', 'حدث خطأ في تحميل المسودة');

            // Uncheck the checkbox on error
            const checkbox = document.querySelector(`.template-checkbox[value="${draftId}"]`);
            if (checkbox) {
                checkbox.checked = false;
            }
        }
    }

    // ========================================
    // View Mode Switching (Safe Implementation)
    // ========================================
    function switchToFlatView() {
        if (state.viewMode === 'flat') return;

        state.viewMode = 'flat';

        // Update button states
        document.getElementById('flat-view-btn')?.classList.add('active');
        document.getElementById('grouped-view-btn')?.classList.remove('active');

        // Render flat view
        renderFlatView();

        // Re-initialize sortable
        initSortable();

        showNotification('success', 'العرض العادي مفعّل ✅');
    }

    function switchToGroupedView() {
        if (state.viewMode === 'grouped') return;

        captureFlatItemOrder();
        state.viewMode = 'grouped';

        // Update button states
        document.getElementById('grouped-view-btn')?.classList.add('active');
        document.getElementById('flat-view-btn')?.classList.remove('active');

        // Render grouped view
        renderGroupedView();

        showNotification('success', 'العرض حسب الفئات مفعّل ✅');
    }

    // ========================================
    // Grouped View Rendering
    // ========================================
    function renderGroupedView() {
        if (!elements.container) return;

        // Keep the pre-group item_order; grouping is display-only.
        const rows = sortRowsByItemOrder(elements.container.querySelectorAll('tr[data-item-id]'));

        if (rows.length === 0) {
            return;
        }

        rows.forEach(row => {
            const name = row.querySelector('.subproject-input')?.value?.trim();
            if (name) ensureSubprojectInOrder(name);
        });

        const groupedItems = groupItemsByCategory(rows);

        elements.container.innerHTML = '';

        const sortedGroups = sortGroupEntries(Array.from(groupedItems.entries()));

        sortedGroups.forEach(([categoryName, categoryRows]) => {
            const categoryGroup = createCategoryGroup(categoryName, categoryRows);
            elements.container.appendChild(categoryGroup);
        });

        initSortableForGroups();
    }

    function groupItemsByCategory(rows) {
        // ✅ Don't sort - preserve DOM order (rows are already in correct order)
        // Use Map to preserve insertion order
        const groups = new Map();

        rows.forEach(row => {
            // Get subproject name from input, or use "غير مصنف"
            const subprojectInput = row.querySelector('.subproject-input');
            const subprojectName = subprojectInput?.value.trim() || 'بدون مشروع فرعي';

            if (!groups.has(subprojectName)) {
                groups.set(subprojectName, []);
            }

            groups.get(subprojectName).push(row);
        });

        return groups;
    }

    function calculateSubprojectTotals(rows) {
        let totalQty = 0;
        let startDate = null;
        let endDate = null;
        let duration = 0;
        let units = new Map(); // Track unit frequencies

        rows.forEach(row => {
            // Sum total quantities
            const qtyInput = row.querySelector('.total-quantity');
            if (qtyInput) {
                totalQty += parseFloat(qtyInput.value || 0);
            }

            // Track units from work items
            const workItemId = row.dataset.workItemId;
            const workItem = state.workItems.find(item => item.id == workItemId);
            if (workItem && workItem.unit) {
                const currentCount = units.get(workItem.unit) || 0;
                units.set(workItem.unit, currentCount + 1);
            }

            // Get min start date
            const startInput = row.querySelector('.item-start-date');
            if (startInput && startInput.value) {
                if (!startDate || startInput.value < startDate) {
                    startDate = startInput.value;
                }
            }

            // Get max end date
            const endInput = row.querySelector('.item-end-date');
            if (endInput && endInput.value) {
                if (!endDate || endInput.value > endDate) {
                    endDate = endInput.value;
                }
            }
        });

        // An item's duration counts working days, so the group total has to skip the
        // weekly holidays too, otherwise a one day item reads as two.
        if (startDate && endDate) {
            duration = workingDaysBetween(startDate, endDate, getWeeklyHolidays());
        }

        // Determine the most common unit
        let commonUnit = '';
        let maxCount = 0;
        units.forEach((count, unit) => {
            if (count > maxCount) {
                maxCount = count;
                commonUnit = unit;
            }
        });

        // If multiple units exist, show all of them
        let unitDisplay = commonUnit;
        if (units.size > 1) {
            unitDisplay = Array.from(units.keys()).join('/');
        }

        return {
            totalQty,
            unit: unitDisplay,
            startDate,
            endDate,
            duration
        };
    }

    function updateSubprojectTotals() {
        // Get all category groups
        document.querySelectorAll('.category-group-row').forEach(groupRow => {
            const categoryHeader = groupRow.querySelector('.category-header');
            if (!categoryHeader) return;

            const categoryId = subprojectKey(categoryHeader.dataset.category);
            const tbody = groupRow.querySelector('.sortable-category-items');

            if (!tbody) return;

            const rows = Array.from(tbody.querySelectorAll('tr[data-item-id]'));
            const totals = calculateSubprojectTotals(rows);

            // Update UI - Duration
            const datesSpan = groupRow.querySelector(`.subproject-dates-${categoryId}`);
            if (datesSpan) {
                datesSpan.textContent = totals.duration > 0 ? `${totals.duration} يوم` : '-';
            }

            // Update UI - Start Date Display
            const startDisplay = groupRow.querySelector(`.subproject-start-display-${categoryId}`);
            if (startDisplay) {
                startDisplay.textContent = totals.startDate || '-';
            }

            // Update UI - End Date Display
            const endDisplay = groupRow.querySelector(`.subproject-end-display-${categoryId}`);
            if (endDisplay) {
                endDisplay.textContent = totals.endDate || '-';
            }

            // The dates have no visible field of their own, so they are kept here for
            // the submit to pick up.
            const startDateInput = groupRow.querySelector(`.subproject-start-date-${categoryId}`);
            if (startDateInput) {
                startDateInput.value = totals.startDate || '';
            }

            const endDateInput = groupRow.querySelector(`.subproject-end-date-${categoryId}`);
            if (endDateInput) {
                endDateInput.value = totals.endDate || '';
            }
        });
    }

    function createCategoryGroup(categoryName, rows) {
        // Create group container
        const groupDiv = document.createElement('tr');
        groupDiv.className = 'category-group-row';
        const categoryId = subprojectKey(categoryName);

        // Calculate subproject totals
        const subprojectTotals = calculateSubprojectTotals(rows);
        subprojectTotals.weight = 1;

        // A value the user already saved outranks the one derived from the items, and
        // an edit they have not saved yet outranks both.
        const savedSubproject = state.subprojects?.find(sp => sp.name === categoryName);
        if (savedSubproject) {
            if (savedSubproject.unit !== null && savedSubproject.unit !== undefined) {
                subprojectTotals.unit = savedSubproject.unit;
            }
            if (savedSubproject.total_quantity !== null && savedSubproject.total_quantity !== undefined) {
                subprojectTotals.totalQty = parseFloat(savedSubproject.total_quantity);
            }
            if (savedSubproject.weight !== null && savedSubproject.weight !== undefined) {
                subprojectTotals.weight = parseFloat(savedSubproject.weight);
            }
        }

        const pendingEdits = state.subprojectEdits.get(categoryName);
        if (pendingEdits) {
            if (pendingEdits.unit !== undefined) subprojectTotals.unit = pendingEdits.unit;
            if (pendingEdits.totalQty !== undefined) subprojectTotals.totalQty = parseFloat(pendingEdits.totalQty) || 0;
            if (pendingEdits.weight !== undefined) subprojectTotals.weight = parseFloat(pendingEdits.weight) || 1;
        }

        groupDiv.innerHTML = `
            <td colspan="14" class="p-0">
                <div class="category-group">
                    <div class="category-header" data-category="${escapeAttr(categoryName)}">
                        <div class="d-flex align-items-center gap-3 flex-grow-1">
                            <span class="category-drag-handle text-white-50" style="cursor: grab;" title="سحب لإعادة ترتيب المشروع الفرعي">
                                <i class="fas fa-grip-vertical"></i>
                            </span>
                            <div class="category-title">
                                <span class="category-icon">${getCategoryIcon(categoryName)}</span>
                                <span class="fw-bold">${escapeHtml(categoryName)}</span>
                            </div>
                            
                            <div class="category-data-inline d-flex gap-3 align-items-center">
                                <div class="data-item">
                                    <i class="fas fa-list text-white-50 me-1"></i>
                                    <strong>${rows.length}</strong> بند
                                </div>
                                <div class="vr bg-white opacity-25"></div>
                                <div class="data-item">
                                    <i class="fas fa-boxes text-white-50 me-1"></i>
                                    <span class="text-white-50 small">الكمية:</span>
                                    <input type="number" 
                                           step="0.01" 
                                           min="0" 
                                           class="form-control form-control-sm d-inline-block subproject-quantity-input-${categoryId}" 
                                           style="width: 100px; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; font-weight: bold;"
                                           value="${subprojectTotals.totalQty.toFixed(2)}"
                                           data-category-id="${categoryId}">
                                    <input type="text" 
                                           class="form-control form-control-sm d-inline-block subproject-unit-input-${categoryId}" 
                                           style="width: 80px; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; font-weight: bold; margin-left: 5px;"
                                           placeholder="الوحدة"
                                           value="${escapeAttr(subprojectTotals.unit)}"
                                           data-category-id="${categoryId}">
                                </div>
                                <div class="vr bg-white opacity-25"></div>
                                <div class="data-item" title="من ${subprojectTotals.startDate || '-'} إلى ${subprojectTotals.endDate || '-'}">
                                    <i class="fas fa-calendar-alt text-white-50 me-1"></i>
                                    <span class="text-white-50 small">المدة:</span>
                                    <strong class="subproject-dates-${categoryId}">${subprojectTotals.duration > 0 ? subprojectTotals.duration + ' يوم' : '-'}</strong>
                                </div>
                                <div class="vr bg-white opacity-25"></div>
                                <div class="data-item">
                                    <i class="fas fa-calendar-check text-white-50 me-1"></i>
                                    <span class="text-white-50 small">من:</span>
                                    <strong class="subproject-start-display-${categoryId}">${subprojectTotals.startDate || '-'}</strong>
                                    <span class="text-white-50 small ms-2">إلى:</span>
                                    <strong class="subproject-end-display-${categoryId}">${subprojectTotals.endDate || '-'}</strong>
                                </div>
                                <div class="vr bg-white opacity-25"></div>
                                <div class="data-item">
                                    <i class="fas fa-weight text-white-50 me-1"></i>
                                    <span class="text-white-50 small">الوزن:</span>
                                    <input type="number" 
                                           step="0.01" 
                                           min="0" 
                                           class="form-control form-control-sm d-inline-block subproject-weight-input-${categoryId}" 
                                           style="width: 80px; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; font-weight: bold;"
                                           value="${subprojectTotals.weight}"
                                           data-category-id="${categoryId}">
                                </div>
                            </div>
                        </div>
                        
                        <span class="category-toggle ms-3">
                            <i class="fas fa-chevron-down"></i>
                        </span>
                    </div>
                    
                    <!-- The dates have no visible field, and these are read (not posted)
                         by collectSubprojects, which rebuilds the posted inputs. -->
                    <input type="hidden" class="subproject-start-date-${categoryId}" value="${escapeAttr(subprojectTotals.startDate)}">
                    <input type="hidden" class="subproject-end-date-${categoryId}" value="${escapeAttr(subprojectTotals.endDate)}">
                    
                    <div class="category-body">
                        <!-- Bulk Actions Toolbar for Subproject -->
                        <div class="p-2 bg-light border-bottom d-flex align-items-center gap-2 flex-wrap">
                            <div class="form-check">
                                <input class="form-check-input subproject-select-all" 
                                       type="checkbox" 
                                       id="selectAll_${categoryId}"
                                       data-category="${escapeAttr(categoryName)}">
                                <label class="form-check-label small" for="selectAll_${categoryId}">
                                    <strong>تحديد الكل</strong>
                                </label>
                            </div>
                            
                            <div class="vr"></div>
                            
                            <select class="form-select form-select-sm subproject-bulk-action" style="width: auto; min-width: 180px;" data-category="${escapeAttr(categoryName)}">
                                <option value="">-- عمليات جماعية --</option>
                                <option value="delete">🗑️ حذف الكل</option>
                                <option value="duplicate">📋 نسخ الكل</option>
                                <option value="move">📁 نقل لمشروع فرعي آخر</option>
                                <option value="export">📊 تصدير (CSV)</option>
                                <option value="clear-notes">🧹 مسح الملاحظات</option>
                                <option value="set-duration">⏱️ تعيين مدة موحدة</option>
                            </select>
                            
                            <button type="button" 
                                    class="btn btn-sm btn-primary subproject-bulk-execute" 
                                    data-category="${escapeAttr(categoryName)}"
                                    title="تنفيذ العملية">
                                <i class="fas fa-play"></i>
                                تنفيذ
                            </button>
                            
                            <span class="ms-auto text-muted small">
                                <span class="selected-count-${categoryId}">0</span> محدد
                            </span>
                        </div>
                        
                        <table class="table table-bordered table-hover align-middle category-items-table mb-0">
                            <tbody class="sortable-category-items" data-category="${escapeAttr(categoryName)}">
                            </tbody>
                        </table>
                    </div>
                </div>
            </td>
        `;

        // ✅ Don't sort - preserve DOM order (rows are already in correct order)
        // Add items to category body
        const tbody = groupDiv.querySelector('.sortable-category-items');
        rows.forEach((row, index) => {
            // Save predecessor, dependency type, and lag values before cloning
            const predecessorSelect = row.querySelector('.predecessor-select');
            const dependencyTypeSelect = row.querySelector('select[name*="[dependency_type]"]');
            const lagInput = row.querySelector('.lag-input');

            const predecessorValue = predecessorSelect?.value || '';
            const dependencyTypeValue = dependencyTypeSelect?.value || 'end_to_start';
            const lagValue = lagInput?.value || '0';

            // Clone the row
            const clonedRow = row.cloneNode(true);
            tbody.appendChild(clonedRow);

            // Restore values after cloning
            const newRow = tbody.lastChild;
            const newPredecessorSelect = newRow.querySelector('.predecessor-select');
            const newDependencyTypeSelect = newRow.querySelector('select[name*="[dependency_type]"]');
            const newLagInput = newRow.querySelector('.lag-input');

            if (newPredecessorSelect) setPredecessorValue(newPredecessorSelect, predecessorValue);
            if (newDependencyTypeSelect) newDependencyTypeSelect.value = dependencyTypeValue;
            if (newLagInput) newLagInput.value = lagValue;

            // Listeners live on the container, so the clone is already wired up.

            // ✅ Preserve original item_order (don't change it when grouping)
            // The item_order should remain as it was in flat view
            const originalOrderInput = row.querySelector('input[name*="[item_order]"]');
            if (originalOrderInput) {
                const newOrderInput = newRow.querySelector('input[name*="[item_order]"]');
                if (newOrderInput) {
                    newOrderInput.value = originalOrderInput.value;
                }
            }
        });

        // Start collapsed by default
        const body = groupDiv.querySelector('.category-body');
        const chevron = groupDiv.querySelector('.category-toggle i');
        if (body) body.classList.add('collapsed');
        const headerEl = groupDiv.querySelector('.category-header');
        if (headerEl) headerEl.classList.add('collapsed');
        if (chevron) chevron.className = 'fas fa-chevron-right';

        // Add collapse/expand functionality
        const header = groupDiv.querySelector('.category-header');
        header.addEventListener('click', function (e) {
            // Don't toggle if clicking on inputs, buttons, or the group drag handle
            if (
                e.target.tagName === 'INPUT' ||
                e.target.tagName === 'BUTTON' ||
                e.target.closest('input') ||
                e.target.closest('button') ||
                e.target.closest('.category-drag-handle')
            ) {
                return;
            }
            toggleCategoryGroup(this);
        });

        // Attach bulk actions event listeners
        attachSubprojectBulkActions(groupDiv, categoryName, categoryId);

        [
            ['totalQty', `.subproject-quantity-input-${categoryId}`],
            ['unit', `.subproject-unit-input-${categoryId}`],
            ['weight', `.subproject-weight-input-${categoryId}`]
        ].forEach(([field, selector]) => {
            const input = groupDiv.querySelector(selector);
            if (!input) return;

            // The header doubles as a collapse toggle.
            input.addEventListener('click', e => e.stopPropagation());
            input.addEventListener('input', () => {
                const edits = state.subprojectEdits.get(categoryName) || {};
                edits[field] = input.value;
                state.subprojectEdits.set(categoryName, edits);
            });
        });

        return groupDiv;
    }

    // ========================================
    // Subproject Bulk Actions
    // ========================================
    function attachSubprojectBulkActions(groupDiv, categoryName, categoryId) {
        const selectAllCheckbox = groupDiv.querySelector('.subproject-select-all');
        const bulkActionSelect = groupDiv.querySelector('.subproject-bulk-action');
        const executeBtn = groupDiv.querySelector('.subproject-bulk-execute');
        const tbody = groupDiv.querySelector('.sortable-category-items');

        // Select All functionality
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function () {
                const checkboxes = tbody.querySelectorAll('.item-checkbox');
                checkboxes.forEach(cb => cb.checked = this.checked);
                updateSubprojectSelectedCount(categoryId, tbody);
            });
        }

        // Update count when individual checkboxes change
        tbody.addEventListener('change', function (e) {
            if (e.target.classList.contains('item-checkbox')) {
                updateSubprojectSelectedCount(categoryId, tbody);

                // Update select all state
                const allCheckboxes = tbody.querySelectorAll('.item-checkbox');
                const checkedCheckboxes = tbody.querySelectorAll('.item-checkbox:checked');
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = allCheckboxes.length > 0 && allCheckboxes.length === checkedCheckboxes.length;
                }
            }
        });

        // Execute bulk action
        if (executeBtn) {
            executeBtn.addEventListener('click', function () {
                const action = bulkActionSelect?.value;

                if (!action) {
                    showNotification('warning', 'الرجاء اختيار عملية أولاً');
                    return;
                }

                const selectedRows = Array.from(tbody.querySelectorAll('.item-checkbox:checked'))
                    .map(cb => cb.closest('tr[data-item-id]'))
                    .filter(row => row !== null);

                if (selectedRows.length === 0) {
                    showNotification('warning', `الرجاء تحديد بنود من "${categoryName}" أولاً`);
                    return;
                }

                executeSubprojectBulkAction(action, selectedRows, categoryName, tbody, selectAllCheckbox, bulkActionSelect);
            });
        }
    }

    function updateSubprojectSelectedCount(categoryId, tbody) {
        const countSpan = document.querySelector(`.selected-count-${categoryId}`);
        if (countSpan) {
            const checkedCount = tbody.querySelectorAll('.item-checkbox:checked').length;
            countSpan.textContent = checkedCount;
        }
    }

    function executeSubprojectBulkAction(action, selectedRows, categoryName, tbody, selectAllCheckbox, bulkActionSelect) {
        switch (action) {
            case 'delete':
                if (!confirm(`هل أنت متأكد من حذف ${selectedRows.length} بند من "${categoryName}"؟`)) {
                    return;
                }
                selectedRows.forEach(row => row.remove());
                updateItemOrders();
                calculateAllDates();
                updateEmptyState();
                regroupIfNeeded();
                showNotification('success', `✅ تم حذف ${selectedRows.length} بند من "${categoryName}"`);
                break;

            case 'duplicate':
                selectedRows.forEach(row => insertDuplicate(row, workItemForRow(row)));
                updateItemOrders();
                calculateAllDates();
                regroupIfNeeded();
                showNotification('success', `✅ تم نسخ ${selectedRows.length} بند في "${categoryName}"`);
                break;

            case 'move':
                // Store rows, category name, and UI elements for modal
                pendingMoveRows = selectedRows;
                pendingMoveCategoryName = categoryName;
                pendingMoveSelectAllCheckbox = selectAllCheckbox;
                pendingMoveBulkActionSelect = bulkActionSelect;

                // Get unique subprojects excluding current category
                const subprojects = getUniqueSubprojects().filter(sp => sp !== categoryName);

                // Update modal with item count and category info
                const itemsCountEl = document.getElementById('moveItemsCount');
                if (itemsCountEl) {
                    itemsCountEl.textContent = selectedRows.length;
                }

                // Update modal title to show source category
                const modalTitle = document.querySelector('#moveToSubprojectModalLabel');
                if (modalTitle) {
                    modalTitle.innerHTML = `<i class="fas fa-folder-open me-2"></i> نقل البنود من "${categoryName}" إلى مشروع فرعي`;
                }

                // Populate subprojects select
                const subprojectSelect = document.getElementById('subprojectSelect');
                const newSubprojectInput = document.getElementById('newSubprojectName');

                if (subprojectSelect) {
                    // Clear existing options except the first one
                    subprojectSelect.innerHTML = '<option value="">-- مشروع جديد --</option>';

                    // Add existing subprojects (excluding current category)
                    subprojects.forEach(subproject => {
                        const option = document.createElement('option');
                        option.value = subproject;
                        option.textContent = subproject;
                        subprojectSelect.appendChild(option);
                    });
                }

                // Clear new subproject input
                if (newSubprojectInput) {
                    newSubprojectInput.value = '';
                }

                // Show modal using Bootstrap
                const modalElement = document.getElementById('moveToSubprojectModal');
                if (modalElement && typeof bootstrap !== 'undefined') {
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                } else if (modalElement) {
                    // Fallback if Bootstrap is not available
                    modalElement.style.display = 'block';
                    modalElement.classList.add('show');
                }

                // Don't reset select and checkbox here - will be done after move is confirmed
                return; // Return early, actual move will happen in modal confirm handler

            case 'export':
                bulkExport(selectedRows);
                showNotification('success', `✅ تم تصدير ${selectedRows.length} بند من "${categoryName}"`);
                break;

            case 'clear-notes':
                if (!confirm(`هل تريد مسح الملاحظات من ${selectedRows.length} بند في "${categoryName}"؟`)) {
                    return;
                }
                selectedRows.forEach(row => {
                    const notesInput = row.querySelector('[name*="[notes]"]');
                    if (notesInput) {
                        notesInput.value = '';
                    }
                });
                showNotification('success', `✅ تم مسح الملاحظات من ${selectedRows.length} بند`);
                break;

            case 'set-duration':
                const duration = prompt(`تعيين مدة موحدة لـ ${selectedRows.length} بند في "${categoryName}":\n\nأدخل المدة بالأيام:`);
                if (duration !== null && !isNaN(duration) && parseInt(duration) > 0) {
                    selectedRows.forEach(row => {
                        const durationInput = row.querySelector('.duration-input');
                        if (durationInput) {
                            durationInput.value = parseInt(duration);
                        }
                    });
                    calculateAllDates();
                    showNotification('success', `✅ تم تعيين المدة ${duration} يوم لـ ${selectedRows.length} بند`);
                }
                break;
        }

        // Reset selection
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
        if (bulkActionSelect) {
            bulkActionSelect.value = '';
        }

        // Update selected count
        const categoryId = subprojectKey(categoryName);
        updateSubprojectSelectedCount(categoryId, tbody);
    }

    function toggleCategoryGroup(headerElement) {
        // Find the category-body element within the same category-group
        const categoryGroup = headerElement.closest('.category-group');
        const body = categoryGroup.querySelector('.category-body');
        const toggle = headerElement.querySelector('.category-toggle i');

        if (!body) return;

        body.classList.toggle('collapsed');
        headerElement.classList.toggle('collapsed');

        if (body.classList.contains('collapsed')) {
            toggle.className = 'fas fa-chevron-right';
        } else {
            toggle.className = 'fas fa-chevron-down';
        }
    }

    function getCategoryIcon(categoryName) {
        // Default icon for subprojects
        if (categoryName === 'بدون مشروع فرعي') {
            return '📋';
        }

        // Return folder icon for all subprojects
        return '📁';
    }

    function initSortableForGroups() {
        if (typeof Sortable === 'undefined' || !elements.container) return;

        destroySortables();

        // Reorder entire subproject groups by their headers.
        sortables.push(Sortable.create(elements.container, {
            handle: '.category-drag-handle',
            draggable: '.category-group-row',
            animation: 150,
            onEnd: () => {
                syncSubprojectOrderFromDom();
            }
        }));

        // Items within their respective subproject groups (cannot cross groups)
        document.querySelectorAll('.sortable-category-items').forEach(tbody => {
            const categoryKey = tbody.dataset.category || 'default';
            sortables.push(Sortable.create(tbody, {
                group: 'subproject_' + categoryKey,
                handle: '.drag-handle',
                animation: 150,
                onEnd: () => {
                    syncFlatItemOrderFromGroupDrag(tbody);
                    calculateAllDates();
                    if (state.viewMode === 'grouped') {
                        updateSubprojectTotals();
                    }
                }
            }));
        });
    }

    function renderFlatView() {
        if (!elements.container) return;

        const allRows = [];
        const rowsById = new Map();

        const categoryGroups = document.querySelectorAll('.category-group-row');

        if (categoryGroups.length > 0) {
            categoryGroups.forEach(categoryGroup => {
                categoryGroup.querySelectorAll('.sortable-category-items tr[data-item-id]').forEach(row => {
                    allRows.push(row);
                    rowsById.set(row.dataset.itemId, row);
                });
            });
            syncSubprojectOrderFromDom();
        } else {
            elements.container.querySelectorAll('tr[data-item-id]').forEach(row => {
                allRows.push(row);
                rowsById.set(row.dataset.itemId, row);
            });
        }

        document.querySelectorAll('#selected-items-container > tr[data-item-id]').forEach(row => {
            if (!rowsById.has(row.dataset.itemId)) {
                allRows.push(row);
                rowsById.set(row.dataset.itemId, row);
            }
        });

        const seen = new Set();
        const sortedRows = [];

        (state.flatItemOrder || []).forEach(id => {
            const row = rowsById.get(id);
            if (row && !seen.has(id)) {
                sortedRows.push(row);
                seen.add(id);
            }
        });

        sortRowsByItemOrder(allRows.filter(row => !seen.has(row.dataset.itemId))).forEach(row => {
            sortedRows.push(row);
            seen.add(row.dataset.itemId);
        });

        elements.container.innerHTML = '';

        sortedRows.forEach((row, index) => {
            const predecessorSelect = row.querySelector('.predecessor-select');
            const dependencyTypeSelect = row.querySelector('select[name*="[dependency_type]"]');
            const lagInput = row.querySelector('.lag-input');

            const predecessorValue = predecessorSelect?.value || '';
            const dependencyTypeValue = dependencyTypeSelect?.value || 'end_to_start';
            const lagValue = lagInput?.value || '0';

            const clonedRow = row.cloneNode(true);
            elements.container.appendChild(clonedRow);

            const newRow = elements.container.lastChild;
            const newPredecessorSelect = newRow.querySelector('.predecessor-select');
            const newDependencyTypeSelect = newRow.querySelector('select[name*="[dependency_type]"]');
            const newLagInput = newRow.querySelector('.lag-input');

            if (newPredecessorSelect) setPredecessorValue(newPredecessorSelect, predecessorValue);
            if (newDependencyTypeSelect) newDependencyTypeSelect.value = dependencyTypeValue;
            if (newLagInput) newLagInput.value = lagValue;

            const orderInput = newRow.querySelector('input[name*="[item_order]"]');
            if (orderInput) {
                orderInput.value = index;
            }
        });

        captureFlatItemOrder(elements.container.querySelectorAll('tr[data-item-id]'));
        updatePredecessors();
    }

    // ========================================
    // Subprojects Payload
    // ========================================
    /**
     * Build one record per subproject that still has items.
     *
     * The grouped header is the only place the manual quantity, unit and weight
     * live, so when it is not rendered (flat view) the record falls back to what
     * was loaded from the database and then to what the items add up to.
     * Order follows the dragged group headers when available, else state.subprojectOrder.
     */
    function collectSubprojects() {
        const groupsByName = new Map();
        document.querySelectorAll('.category-group-row').forEach(groupRow => {
            const name = groupRow.querySelector('.category-header')?.dataset.category;
            if (name) groupsByName.set(name, groupRow);
        });

        const rowsByName = new Map();
        document.querySelectorAll('tr[data-item-id]').forEach(row => {
            const name = row.querySelector('.subproject-input')?.value?.trim();
            if (!name) return;
            if (!rowsByName.has(name)) rowsByName.set(name, []);
            rowsByName.get(name).push(row);
            ensureSubprojectInOrder(name);
        });

        // Prefer the visual group order; fall back to the remembered name list.
        const orderedNames = [];
        if (groupsByName.size > 0) {
            document.querySelectorAll('.category-group-row .category-header').forEach(header => {
                const name = header.dataset.category;
                if (name && name !== 'بدون مشروع فرعي' && rowsByName.has(name) && !orderedNames.includes(name)) {
                    orderedNames.push(name);
                }
            });
        }

        state.subprojectOrder.forEach(name => {
            if (rowsByName.has(name) && !orderedNames.includes(name)) {
                orderedNames.push(name);
            }
        });

        rowsByName.forEach((_rows, name) => {
            if (!orderedNames.includes(name)) orderedNames.push(name);
        });

        state.subprojectOrder = orderedNames.slice();

        return orderedNames.map((name, sortOrder) => {
            const rows = rowsByName.get(name) || [];
            const key = subprojectKey(name);
            const group = groupsByName.get(name);
            const totals = calculateSubprojectTotals(rows);
            const saved = state.subprojects?.find(subproject => subproject.name === name);
            const pending = state.subprojectEdits.get(name) || {};

            const fromHeader = selector => group?.querySelector(selector)?.value;
            const quantity = fromHeader(`.subproject-quantity-input-${key}`) ?? pending.totalQty;
            const unit = fromHeader(`.subproject-unit-input-${key}`) ?? pending.unit;
            const weight = fromHeader(`.subproject-weight-input-${key}`) ?? pending.weight;

            return {
                name,
                start_date: fromHeader(`.subproject-start-date-${key}`) || totals.startDate || '',
                end_date: fromHeader(`.subproject-end-date-${key}`) || totals.endDate || '',
                total_quantity: quantity === '' ? 0 : (quantity ?? saved?.total_quantity ?? totals.totalQty),
                unit: unit ?? saved?.unit ?? totals.unit ?? '',
                weight: (weight === '' || weight == null) ? (saved?.weight ?? 1) : weight,
                sort_order: sortOrder
            };
        });
    }

    function cleanEmptySubprojects() {
        elements.form.querySelectorAll('.subproject-payload').forEach(input => input.remove());

        const fragment = document.createDocumentFragment();

        collectSubprojects().forEach((subproject, index) => {
            Object.entries(subproject).forEach(([field, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.className = 'subproject-payload';
                input.name = `subprojects[${index}][${field}]`;
                input.value = value ?? '';
                fragment.appendChild(input);
            });
        });

        elements.form.appendChild(fragment);
    }

    // ========================================
    // Form Submit Validation
    // ========================================
    function handleFormSubmit(e) {
        cleanEmptySubprojects();

        const isDraft = e.submitter?.name === 'save_as_draft';
        const ok = isDraft ? validateDraftSubmit(e) : validateFullSubmit(e);

        // Only strip after validation succeeds; removing `name` early would leave the
        // selects nameless if the user cancels the confirm and keeps editing.
        if (ok !== false) {
            stripEmptyPredecessors();
        }

        return ok;
    }

    function stripEmptyPredecessors() {
        elements.form?.querySelectorAll('.predecessor-select').forEach(select => {
            if (!select.value) {
                select.removeAttribute('name');
            }
        });
    }

    function validateDraftSubmit(e) {
        const errors = [];

        // Only check project name
        const projectName = document.getElementById('project-name-input')?.value?.trim();
        if (!projectName) {
            errors.push('• اسم المشروع مطلوب');
        }

        if (errors.length > 0) {
            e.preventDefault();
            showValidationErrors('لا يمكن حفظ المسودة', errors);
            return false;
        }

        // Show confirmation for draft
        const itemsCount = elements.container.querySelectorAll('tr[data-item-id]').length;
        const confirmMsg = `حفظ كمسودة:\n\n` +
            `📝 الاسم: ${projectName}\n` +
            `📦 عدد البنود: ${itemsCount}\n\n` +
            `هل تريد المتابعة؟`;

        if (!confirm(confirmMsg)) {
            e.preventDefault();
            return false;
        }
        return true;
    }
    function validateFullSubmit(e) {
        const errors = [];
        const projectName = document.getElementById('project-name-input')?.value?.trim();
        if (!projectName) {
            errors.push('• اسم المشروع مطلوب');
        }

        const clientId = document.getElementById('client-id-select')?.value;
        if (!clientId) {
            errors.push('• العميل مطلوب');
        }

        const startDate = elements.startDate?.value;
        if (!startDate) {
            errors.push('• تاريخ البداية مطلوب');
        }

        const workingZone = document.getElementById('working_zone')?.value?.trim();
        if (!workingZone) {
            errors.push('• منطقة العمل مطلوبة');
        }

        const projectTypeId = document.getElementById('project_type_id')?.value;
        if (!projectTypeId) {
            errors.push('• نوع المشروع مطلوب');
        }

        // Check items
        const itemsCount = elements.container.querySelectorAll('tr[data-item-id]').length;
        if (itemsCount === 0) {
            errors.push('• يجب إضافة بند واحد على الأقل للمشروع');
        }

        // Check employees
        const selectedEmployees = document.querySelectorAll('input[name="employees[]"]:checked').length;
        if (selectedEmployees === 0) {
            errors.push('• يجب اختيار موظف واحد على الأقل');
        }

        // If there are errors, prevent submission
        if (errors.length > 0) {
            e.preventDefault();
            showValidationErrors('لا يمكن حفظ المشروع', errors);
            return false;
        }

        // Show confirmation with summary
        const endDate = elements.endDate?.value || 'يحسب تلقائياً';
        const client = document.querySelector('#client-id-select option:checked')?.text || '';

        const confirmMsg = `✅ تأكيد حفظ المشروع:\n\n` +
            `📝 الاسم: ${projectName}\n` +
            `👤 العميل: ${client}\n` +
            `📅 من: ${startDate} إلى: ${endDate}\n` +
            `📦 عدد البنود: ${itemsCount}\n` +
            `👥 عدد الموظفين: ${selectedEmployees}\n` +
            `📍 منطقة العمل: ${workingZone}\n\n` +
            `هل تريد المتابعة؟`;

        if (!confirm(confirmMsg)) {
            e.preventDefault();
            return false;
        }

        // Show loading message
        showNotification('info', '⏳ جاري حفظ المشروع...');

        return true;
    }

    function showValidationErrors(title, errors) {
        const errorsList = errors.join('\n');
        const message = `❌ ${title}\n\nالحقول المطلوبة التي لم يتم ملؤها:\n\n${errorsList}\n\nالرجاء إكمال البيانات المطلوبة.`;

        alert(message);

        // Also show as notification
        showNotification('error', `${title} - يرجى إكمال الحقول المطلوبة`);
    }

    // ========================================
    // Handle Enter Key Navigation
    // ========================================
    function handleEnterKey(e) {
        // Check if Enter key was pressed
        if (e.key !== 'Enter' && e.keyCode !== 13) {
            return;
        }

        const target = e.target;
        const tagName = target.tagName.toLowerCase();

        // Allow Enter in textareas and for submit buttons
        if (tagName === 'textarea' ||
            tagName === 'button' ||
            target.type === 'submit') {
            return true;
        }

        // Prevent form submission
        e.preventDefault();

        // Move to next input
        moveToNextInput(target);
        return false;
    }

    function moveToNextInput(currentElement) {
        // Get all focusable elements in the form
        const focusableElements = Array.from(
            elements.form.querySelectorAll(
                'input:not([type="hidden"]):not([disabled]):not([readonly]), ' +
                'select:not([disabled]), ' +
                'textarea:not([disabled]), ' +
                'button:not([disabled])'
            )
        );

        // Find current element index
        const currentIndex = focusableElements.indexOf(currentElement);

        if (currentIndex === -1 || currentIndex === focusableElements.length - 1) {
            return;
        }

        // Get next element
        const nextElement = focusableElements[currentIndex + 1];

        if (nextElement) {
            nextElement.focus();

            // Select text in input fields
            if (nextElement.tagName === 'INPUT' &&
                nextElement.type !== 'checkbox' &&
                nextElement.type !== 'radio' &&
                nextElement.type !== 'date') {
                try {
                    nextElement.select();
                } catch (e) {
                    // Ignore error
                }
            }
        }
    }

    // ========================================
    // Auto-save للمسودة
    // ========================================
    function startAutoSave() {
        // Don't auto-save if we're editing an existing project
        if (state.project && state.project.id) {
            return;
        }

        // Auto-save every 2 minutes
        state.autoSaveInterval = setInterval(() => {
            const itemsCount = elements.container?.querySelectorAll('tr[data-item-id]').length || 0;
            const projectName = document.getElementById('project-name-input')?.value?.trim();

            // Only auto-save if there's a name and at least one item
            if (projectName && itemsCount > 0) {
                saveDraftAjax();
            }
        }, 120000); // 2 minutes = 120000ms

    }

    function saveDraftAjax() {
        // Prevent multiple saves at the same time
        const now = Date.now();
        if (state.lastAutoSaveTime && (now - state.lastAutoSaveTime) < 5000) {
            return;
        }

        state.lastAutoSaveTime = now;

        cleanEmptySubprojects();

        const formData = new FormData(elements.form);
        formData.set('save_as_draft', '1');

        // Drop empty predecessors from the payload only — mutating the selects'
        // name attributes would break the form the user is still editing.
        for (const key of [...formData.keys()]) {
            if (key.includes('[predecessor]') && !formData.get(key)) {
                formData.delete(key);
            }
        }

        // Don't show loading notification for auto-save
        fetch(elements.form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success || data.redirect_url) {
                    showNotification('success', '💾 تم الحفظ التلقائي');

                    // Update project ID if this was a new project
                    if (data.project_id && !state.project?.id) {
                        if (!state.project) state.project = {};
                        state.project.id = data.project_id;
                    }
                } else {
                    console.error('❌ Auto-save failed:', data);
                }
            })
            .catch(error => {
                console.error('❌ Auto-save error:', error);
                // Don't show error notification for auto-save failures
            });
    }

    function stopAutoSave() {
        if (state.autoSaveInterval) {
            clearInterval(state.autoSaveInterval);
            state.autoSaveInterval = null;
        }
    }

    // ========================================
    // Initialize on DOM Ready
    // ========================================
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();