/**
 * Shared configuration, mutable state and cached DOM references.
 *
 * `state` is exported as a live object binding and must never be reassigned,
 * only mutated, so every module keeps pointing at the same instance.
 */

export const config = {
    searchDebounceMs: 300,
    maxSearchResults: 20
};

export const state = {
    workItems: [],
    project: null,
    projectItems: [],
    itemCounter: 0,
    searchTimeout: null,
    viewMode: 'flat', // 'flat' or 'grouped'
    autoSaveInterval: null,
    lastAutoSaveTime: null,
    // Subproject header edits, keyed by name. They only exist in the group header,
    // which every view switch throws away and rebuilds.
    subprojectEdits: new Map(),
    // Subproject names in display order. Kept in sync with group-header dragging
    // and with sort_order loaded from the database.
    subprojectOrder: [],
    // Flat-view row ids in display order. Grouped view must not overwrite this;
    // switching back to "عادي" restores it.
    flatItemOrder: []
};

export const elements = {
    form: document.getElementById('projectForm'),
    searchInput: document.getElementById('work-item-search'),
    searchResults: document.getElementById('search-results'),
    searchResultsList: document.getElementById('search-results-list'),
    searchLoading: document.getElementById('search-loading'),
    closeSearch: document.getElementById('close-search'),
    resultsCount: document.getElementById('results-count'),
    itemsFilter: document.getElementById('items-filter'),
    resetFilter: document.getElementById('reset-filter'),
    selectAllItems: document.getElementById('selectAllItems'),
    container: document.getElementById('selected-items-container'),
    startDate: document.getElementById('start_date'),
    endDate: document.getElementById('end_date'),
    weeklyHolidaysInput: document.getElementById('weekly-holidays-input'),
    description: document.getElementById('description'),
    charCount: document.getElementById('char-count'),
    emptyState: document.getElementById('empty-state')
};
