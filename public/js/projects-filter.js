/**
 * Hybrid High-Scale Projects Filter (0ms local DOM filter for <=1 page, SQL server filter for 10,000+ projects)
 */
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('projectsFilterForm');
    const projectCards = document.querySelectorAll('.project-card');
    const projectsContainer = document.querySelector('.projects-grid');

    const searchInput = document.getElementById('projectSearch');
    const statusFilter = document.getElementById('projectStatusFilter');
    const typeFilter = document.getElementById('projectTypeFilter');
    const clientFilter = document.getElementById('projectClientFilter');
    const clearFiltersBtn = document.getElementById('clearFilters');
    const resultsCounter = document.getElementById('filterResults');

    const useServerFilter = filterForm && filterForm.dataset.serverFilter === '1';

    function submitCleanServerForm() {
        if (!filterForm) return;
        const params = new URLSearchParams();
        if (searchInput && searchInput.value.trim()) {
            params.set('search', searchInput.value.trim());
        }
        if (statusFilter && statusFilter.value && statusFilter.value !== 'all') {
            params.set('status', statusFilter.value);
        }
        if (typeFilter && typeFilter.value && typeFilter.value !== 'all') {
            params.set('type', typeFilter.value);
        }
        if (clientFilter && clientFilter.value && clientFilter.value !== 'all') {
            params.set('client', clientFilter.value);
        }
        const qs = params.toString();
        window.location.href = qs ? `${filterForm.action}?${qs}` : filterForm.action;
    }

    if (filterForm) {
        filterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            submitCleanServerForm();
        });
    }

    if (useServerFilter) {
        let debounceTimer;
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(submitCleanServerForm, 350);
            });
        }
        if (statusFilter) statusFilter.addEventListener('change', submitCleanServerForm);
        if (typeFilter) typeFilter.addEventListener('change', submitCleanServerForm);
        if (clientFilter) clientFilter.addEventListener('change', submitCleanServerForm);
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function () {
                window.location.href = filterForm.action;
            });
        }
        return;
    }

    // Instant 0ms client-side filter when all projects fit on a single page
    if (!projectCards.length) return;

    function filterProjects() {
        let visibleCount = 0;
        const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const statusValue = statusFilter ? statusFilter.value : 'all';
        const typeValue = typeFilter ? typeFilter.value : 'all';
        const clientValue = clientFilter ? clientFilter.value : 'all';

        projectCards.forEach(card => {
            const projectName = (card.getAttribute('data-project-name') || '').toLowerCase();
            const projectStatus = card.getAttribute('data-project-status') || '';
            const projectType = card.getAttribute('data-project-type') || '';
            const projectClient = (card.getAttribute('data-project-client') || '').toLowerCase();

            let shouldShow = true;
            if (searchTerm) {
                shouldShow = shouldShow && (projectName.includes(searchTerm) || projectClient.includes(searchTerm));
            }
            if (statusValue !== 'all') {
                shouldShow = shouldShow && (projectStatus === statusValue);
            }
            if (typeValue !== 'all') {
                shouldShow = shouldShow && (projectType.toLowerCase() === typeValue.toLowerCase());
            }
            if (clientValue !== 'all') {
                shouldShow = shouldShow && (projectClient.includes(clientValue.toLowerCase()));
            }

            const cardWrapper = card.closest('.col-lg-4, .col-lg-6, .col-md-6, .col-sm-12');
            if (!cardWrapper) return;

            cardWrapper.style.display = shouldShow ? '' : 'none';
            if (shouldShow) visibleCount++;
        });

        if (resultsCounter) {
            resultsCounter.textContent = visibleCount;
            let noResultsMsg = document.getElementById('noResultsMessage');
            if (visibleCount === 0) {
                if (!noResultsMsg && projectsContainer) {
                    const msgDiv = document.createElement('div');
                    msgDiv.id = 'noResultsMessage';
                    msgDiv.className = 'col-12 text-center py-5';
                    msgDiv.innerHTML = `
                        <div class="alert alert-info border-0 shadow-sm" style="border-radius: 20px; padding: 2rem;">
                            <i class="fas fa-search fa-3x mb-3 text-muted"></i>
                            <h5 class="text-muted mb-2">لا توجد مشاريع مطابقة للبحث</h5>
                            <p class="text-muted mb-0">جرب البحث بكلمات مختلفة أو غير الفلاتر</p>
                        </div>
                    `;
                    projectsContainer.appendChild(msgDiv);
                }
            } else if (noResultsMsg) {
                noResultsMsg.remove();
            }
        }
    }

    if (searchInput) searchInput.addEventListener('input', filterProjects);
    if (statusFilter) statusFilter.addEventListener('change', filterProjects);
    if (typeFilter) typeFilter.addEventListener('change', filterProjects);
    if (clientFilter) clientFilter.addEventListener('change', filterProjects);
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function () {
            if (searchInput) searchInput.value = '';
            if (statusFilter) statusFilter.value = 'all';
            if (typeFilter) typeFilter.value = 'all';
            if (clientFilter) clientFilter.value = 'all';
            filterProjects();
            if (searchInput) searchInput.focus();
        });
    }
});
