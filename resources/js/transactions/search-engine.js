import { getActiveSearchValue } from './utils.js';
import { renderDesktopTable, renderMobileCards } from './rendering.js';
import { Config } from './config.js';

/**
 * ═══════════════════════════════════════════════════════════════
 * HYBRID SEARCH ENGINE - Auto-Adaptive
 * Client-side untuk < 5k records | Server-side untuk ≥ 5k records
 * ═══════════════════════════════════════════════════════════════
 */

export const SearchEngine = (function () {
    // State
    let mode = null; // 'client' or 'server'
    let allTransactions = [];
    let filteredTransactions = [];
    let currentPage = 1;
    let totalRecords = 0;
    let totalPages = 0;
    let isLoading = false;
    let isFirstLoad = true;
    let searchTimer = null;
    let abortController = null;

    // ═══════════════════════════════════════════════════════════════
    // INITIAL LOAD - Auto-detect mode
    // ═══════════════════════════════════════════════════════════════
    async function loadData() {
        if (isLoading) {
            console.warn('[SearchEngine] Already loading, skipping...');
            return Promise.resolve();
        }

        isLoading = true;
        if (typeof NProgress !== 'undefined') NProgress.start();

        if (isFirstLoad) {
            renderSkeletons();
        }

        try {
            // First, check dataset size
            const countUrl = buildUrl(Config.endpoints.transactions.count);
            const countResponse = await fetch(countUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!countResponse.ok) throw new Error(`HTTP ${countResponse.status}`);
            const { count } = await countResponse.json();

            totalRecords = count;

            // Auto-select mode based on dataset size
            if (count < Config.ui.searchThreshold) {
                mode = 'client';
                console.log(`[SearchEngine] Using CLIENT-SIDE mode (${count} records)`);
                await loadClientSideData();
            } else {
                mode = 'server';
                console.log(`[SearchEngine] Using SERVER-SIDE mode (${count} records)`);
                await loadServerSideData();
            }

            return Promise.resolve();
        } catch (error) {
            console.error('[SearchEngine] Failed to load:', error);
            showToast('Gagal memuat data', 'error');
            renderPage([]);
            return Promise.reject(error);
        } finally {
            isLoading = false;
            isFirstLoad = false;
            if (typeof NProgress !== 'undefined') NProgress.done();
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // CLIENT-SIDE MODE - Load all data once
    // ═══════════════════════════════════════════════════════════════
    async function loadClientSideData() {
        const url = buildUrl(Config.endpoints.transactions.searchData);
        console.log('[SearchEngine] Fetching all data:', url);

        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        allTransactions = await response.json();

        // Re-apply search if active
        const currentQuery = getActiveSearchValue();
        if (currentQuery) {
            filterClientSide(currentQuery);
        } else {
            filteredTransactions = [...allTransactions];
        }

        // Adjust page if out of bounds
        totalPages = Math.ceil(filteredTransactions.length / Config.ui.itemsPerPage);
        if (currentPage > totalPages && totalPages > 0) {
            currentPage = totalPages;
        }

        renderPage();
        updateStats();
    }

    // ═══════════════════════════════════════════════════════════════
    // SERVER-SIDE MODE - Fetch page by page
    // ═══════════════════════════════════════════════════════════════
    async function loadServerSideData() {
        if (abortController) abortController.abort();
        abortController = new AbortController();

        // --- TAMBAHAN: Efek Loading Ringan ---
        const desktopTbody = document.getElementById('desktop-tbody');
        const mobileContainer = document.getElementById('mobile-container');

        // Tambahkan class opacity agar tidak terlihat seperti refresh total
        if (desktopTbody) desktopTbody.classList.add('opacity-40', 'transition-opacity');
        if (mobileContainer) mobileContainer.classList.add('opacity-40', 'transition-opacity');

        if (typeof NProgress !== 'undefined') NProgress.start();

        // Synchronize search inputs value
        const searchVal = getActiveSearchValue();

        const url = buildUrl(Config.endpoints.transactions.search, {
            page: currentPage,
            per_page: Config.ui.itemsPerPage,
            search: searchVal
        });

        try {
            const response = await fetch(url, {
                signal: abortController.signal,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const result = await response.json();

            filteredTransactions = result.data;
            totalRecords = result.total;
            totalPages = result.last_page;
            currentPage = result.current_page;

            renderPage();
            updateStats();
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('[SearchEngine] Failed:', error);
            }
        } finally {
            // --- TAMBAHAN: Kembalikan Opacity ---
            if (desktopTbody) desktopTbody.classList.remove('opacity-40');
            if (mobileContainer) mobileContainer.classList.remove('opacity-40');
            if (typeof NProgress !== 'undefined') NProgress.done();
        }
    }


    // ═══════════════════════════════════════════════════════════════
    // BUILD URL with filters
    // ═══════════════════════════════════════════════════════════════
    function buildUrl(endpoint, extraParams = {}) {
        const params = new URLSearchParams();

        // Search Query - Get from any available input
        const searchVal = getActiveSearchValue();
        if (searchVal) params.set('search', searchVal);

        // Inherit top-level filters from URL (Type & Status)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('type')) params.set('type', urlParams.get('type'));
        if (urlParams.has('status')) params.set('status', urlParams.get('status'));

        // Date Range
        const startDateEl = document.getElementById('filter-start-date');
        const endDateEl = document.getElementById('filter-end-date');
        if (startDateEl?.value) params.set('start_date', startDateEl.value);
        if (endDateEl?.value) params.set('end_date', endDateEl.value);

        // Branch (Array)
        const branchCheckboxes = document.querySelectorAll('input[name="branch_id[]"]:checked');
        branchCheckboxes.forEach(cb => params.append('branch_id[]', cb.value));

        // Category (Array)
        const categoryCheckboxes = document.querySelectorAll('input[name="category[]"]:checked');
        categoryCheckboxes.forEach(cb => params.append('category[]', cb.value));

        // Add extra params (like page)
        Object.entries(extraParams).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '' && key !== 'search') {
                params.set(key, value);
            }
        });

        return endpoint + '?' + params.toString();
    }


    // ═══════════════════════════════════════════════════════════════
    // SEARCH - Adaptive based on mode
    // ═══════════════════════════════════════════════════════════════
    function search(query, resetPage = true) {
        clearTimeout(searchTimer);

        if (resetPage) currentPage = 1;

        if (mode === 'client') {
            filterClientSide(query);
            renderPage();
            updateStats();
        } else {
            // Jangan panggil renderSkeletons() di sini! 
            // Biarkan loadServerSideData yang menangani visual loadingnya.
            searchTimer = setTimeout(() => {
                loadServerSideData();
            }, 300); // Debounce 300ms
        }
    }

    // Client-side filter algorithm
    function filterClientSide(query) {
        if (!query || query.trim() === '') {
            filteredTransactions = [...allTransactions];
        } else {
            const searchTerm = query.toLowerCase().trim();
            const terms = searchTerm.split(/\s+/);

            filteredTransactions = allTransactions.filter(transaction => {
                return terms.every(term => transaction.search_text.includes(term));
            });
        }

        totalPages = Math.ceil(filteredTransactions.length / Config.ui.itemsPerPage);
    }

    // ═══════════════════════════════════════════════════════════════
    // RENDER PAGE - Works for both modes
    // ═══════════════════════════════════════════════════════════════
    function renderPage() {
        let pageData;

        if (mode === 'client') {
            // Slice from filtered array
            const startIndex = (currentPage - 1) * Config.ui.itemsPerPage;
            const endIndex = startIndex + Config.ui.itemsPerPage;
            pageData = filteredTransactions.slice(startIndex, endIndex);
        } else {
            // Use data from server response
            pageData = filteredTransactions;
        }

        const startIndex = (currentPage - 1) * Config.ui.itemsPerPage;

        renderDesktopTable(pageData, startIndex);
        renderMobileCards(pageData, startIndex);
        renderPagination();
        updateShowingText();

        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function updateShowingText() {
        const startIndex = (currentPage - 1) * Config.ui.itemsPerPage;
        const from = filteredTransactions.length > 0 ? startIndex + 1 : 0;
        const to = Math.min(startIndex + Config.ui.itemsPerPage,
            mode === 'client' ? filteredTransactions.length : totalRecords);
        const total = mode === 'client' ? filteredTransactions.length : totalRecords;

        const elFrom = document.getElementById('showing-from');
        const elTo = document.getElementById('showing-to');
        const elTotal = document.getElementById('total-records');

        if (elFrom) elFrom.textContent = from;
        if (elTo) elTo.textContent = to;
        if (elTotal) elTotal.textContent = total;
    }

    // ═══════════════════════════════════════════════════════════════
    // STATS UPDATE - Fetch from server
    // ═══════════════════════════════════════════════════════════════
    async function updateStats() {
        const url = buildUrl(Config.endpoints.transactions.stats, { status: 'all' });

        try {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) return;

            const stats = await response.json();
            const statuses = ['all', 'pending', 'approved', 'completed', 'rejected', 'waiting_payment', 'flagged', 'auto_reject'];

            statuses.forEach(status => {
                const el = document.querySelector(`.status-count[data-status="${status}"]`);
                if (el && stats[status] !== undefined) {
                    el.textContent = `(${stats[status]})`;
                }
            });
        } catch (error) {
            console.error('[Stats] Update failed:', error);
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // PAGINATION
    // ═══════════════════════════════════════════════════════════════
    function renderPagination() {
        const container = document.getElementById('pagination-container');
        if (!container) return;

        const pages = mode === 'client' ? totalPages : totalPages;

        if (pages <= 1) {
            container.innerHTML = '';
            return;
        }

        const isMobile = window.innerWidth < 640;
        const maxVisible = isMobile ? 3 : 5;

        let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
        let endPage = Math.min(pages, startPage + maxVisible - 1);

        if (endPage - startPage < maxVisible - 1) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }

        let html = '';

        // Previous
        html += `<button onclick="SearchEngine.goToPage(${currentPage - 1})" 
                        ${currentPage === 1 ? 'disabled' : ''} 
                        class="px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg border border-gray-200 text-xs sm:text-sm font-medium ${currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'}">
                        <span class="hidden sm:inline">Prev</span>
                        <i data-lucide="chevron-left" class="w-3.5 h-3.5 sm:hidden"></i>
                    </button>`;

        // First page + ellipsis
        if (startPage > 1) {
            html += `<button onclick="SearchEngine.goToPage(1)" 
                            class="px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg border border-gray-200 text-xs sm:text-sm font-medium hover:bg-gray-50">1</button>`;
            if (startPage > 2) html += `<span class="text-xs text-gray-400 px-0.5">…</span>`;
        }

        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            html += `<button onclick="SearchEngine.goToPage(${i})" 
                            class="px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg border text-xs sm:text-sm font-medium ${i === currentPage ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-200 hover:bg-gray-50'}">
                            ${i}
                        </button>`;
        }

        // Last page + ellipsis
        if (endPage < pages) {
            if (endPage < pages - 1) html += `<span class="text-xs text-gray-400 px-0.5">…</span>`;
            html += `<button onclick="SearchEngine.goToPage(${pages})" 
                            class="px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg border border-gray-200 text-xs sm:text-sm font-medium hover:bg-gray-50">${pages}</button>`;
        }

        // Next
        html += `<button onclick="SearchEngine.goToPage(${currentPage + 1})" 
                        ${currentPage === pages ? 'disabled' : ''} 
                        class="px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg border border-gray-200 text-xs sm:text-sm font-medium ${currentPage === pages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'}">
                        <span class="hidden sm:inline">Next</span>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5 sm:hidden"></i>
                    </button>`;

        container.innerHTML = html;
        if (typeof lucide !== 'undefined') lucide.createIcons({ root: container });
    }

    function goToPage(page) {
        const pages = mode === 'client' ? totalPages : totalPages;
        if (page < 1 || page > pages) return;

        currentPage = page;

        if (mode === 'client') {
            renderPage();
        } else {
            loadServerSideData();
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // SKELETON LOADERS
    // ═══════════════════════════════════════════════════════════════
    function renderSkeletons() {
        const tbody = document.getElementById('desktop-tbody');
        const container = document.getElementById('mobile-container');
        document.getElementById('table-no-results')?.classList.add('hidden');
        document.getElementById('mobile-no-results')?.classList.add('hidden');

        if (tbody) {
            tbody.innerHTML = Array(6).fill(`
                <tr class="animate-pulse bg-white border-b border-gray-50">
                    <td class="px-4 py-4 hidden xl:table-cell"><div class="h-4 bg-slate-200 rounded w-6 mx-auto"></div></td>
                    <td class="px-5 py-4"><div class="flex gap-3 items-center"><div class="w-8 h-8 rounded-full bg-slate-200 shrink-0"></div><div><div class="h-4 bg-slate-200 rounded w-28 mb-1.5"></div><div class="h-3 bg-slate-100 rounded w-16"></div></div></div></td>
                    <td class="px-5 py-4"><div class="h-6 bg-slate-100 rounded-lg w-20 border border-slate-200"></div></td>
                    <td class="px-5 py-4 hidden lg:table-cell"><div class="h-4 bg-slate-200 rounded w-24"></div></td>
                    <td class="px-5 py-4"><div class="h-6 bg-slate-100 rounded-full w-24 border border-slate-200"></div></td>
                    <td class="px-5 py-4 hidden xl:table-cell"><div class="h-4 bg-slate-200 rounded w-20"></div></td>
                    <td class="px-5 py-4"><div class="h-5 bg-slate-200 rounded w-24"></div></td>
                    <td class="px-5 py-4"><div class="flex justify-center gap-1"><div class="w-8 h-8 rounded-lg bg-slate-200 shrink-0"></div><div class="w-8 h-8 rounded-lg bg-slate-200 shrink-0"></div></div></td>
                </tr>
            `).join('');
        }

        if (container) {
            container.innerHTML = Array(4).fill(`
                <div class="p-3 sm:p-4 animate-pulse bg-white border-b border-gray-100">
                    <div class="flex justify-between items-start gap-2 mb-2"><div class="flex items-center gap-2.5"><div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-slate-200 shrink-0"></div><div><div class="h-3.5 bg-slate-200 rounded w-20 sm:w-24 mb-1.5"></div><div class="h-2.5 bg-slate-100 rounded w-14 sm:w-16"></div></div></div><div class="h-5 bg-slate-100 rounded-md w-14 sm:w-16 border border-slate-200"></div></div>
                    <div class="ml-[42px] sm:ml-[48px]"><div class="h-2.5 bg-slate-100 rounded w-40 sm:w-48 mb-2"></div>
                    <div class="h-5 bg-slate-200 rounded w-28 sm:w-32 mb-2"></div>
                    <div class="flex gap-1.5"><div class="h-7 sm:h-8 bg-slate-100 rounded-lg w-16 sm:w-20 border border-slate-200"></div><div class="h-7 sm:h-8 bg-slate-100 rounded-lg w-14 sm:w-16 border border-slate-200"></div></div></div>
                </div>
            `).join('');
        }
    }


    function addTransaction(transaction) {
        if (mode === 'client') {
            if (!allTransactions.some(t => t.id === transaction.id)) {
                allTransactions.unshift(transaction);
                const query = document.getElementById('instant-search')?.value?.trim() || '';
                search(query);
            }
        } else {
            // Server-side: just reload current page
            loadServerSideData();
        }
    }

    function updateTransaction(transaction) {
        if (mode === 'client') {
            const index = allTransactions.findIndex(t => t.id === transaction.id);
            if (index !== -1) {
                allTransactions[index] = transaction;
                const query = document.getElementById('instant-search')?.value?.trim() || '';
                search(query, false);
            } else {
                addTransaction(transaction);
            }
        } else {
            loadServerSideData();
        }
    }

    function deleteTransaction(id) {
        if (mode === 'client') {
            allTransactions = allTransactions.filter(t => t.id != id);
            const query = document.getElementById('instant-search')?.value?.trim() || '';
            search(query, false);
        } else {
            loadServerSideData();
        }
    }

    // Public API
    return {
        init: loadData,
        search: search,
        goToPage: goToPage,
        refresh: loadData,
        getAll: () => mode === 'client' ? allTransactions : filteredTransactions,
        getFiltered: () => filteredTransactions,
        addTransaction: addTransaction,
        updateTransaction: updateTransaction,
        deleteTransaction: deleteTransaction,
        getMode: () => mode
    };
})();

// Assign to window for global access (so HTML attributes like onclick can find it)
window.SearchEngine = SearchEngine;
