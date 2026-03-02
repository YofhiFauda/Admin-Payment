@extends('layouts.app')

@section('page-title', 'Data Riwayat Transaksi')

@section('content')

    {{-- Main Content Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        {{-- Header Toolbar --}}
        <div class="p-5 border-b border-gray-100 flex flex-col md:flex-row gap-4 justify-between items-center">
            {{-- Search --}}
            <div class="relative w-full md:w-64 lg:w-96">
                <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                <input type="text"
                    id="instant-search"
                    value="{{ request('search') }}"
                    placeholder="Cari invoice, nama, vendor, tanggal..."
                    autocomplete="off"
                    class="w-full pl-10 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-300 transition-all placeholder:text-gray-400">
                <button type="button" id="search-clear" class="absolute right-3 top-1/2 -translate-y-1/2 hidden p-0.5 rounded-md hover:bg-gray-200 transition-colors" title="Hapus pencarian">
                    <i data-lucide="x" class="w-3.5 h-3.5 text-gray-400"></i>
                </button>
            </div>

            {{-- Type Filter + Actions --}}
            <div class="flex items-center gap-3 w-full md:w-auto overflow-x-auto pb-1 md:pb-0 scrollbar-hide">
                @php $currentType = request('type', 'all'); @endphp
                <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => null])) }}"
                    class="px-3 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap border
                    {{ $currentType === 'all' ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-slate-500 border-slate-200 hover:bg-slate-50' }}">
                    Semua
                </a>
                <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => 'rembush'])) }}"
                    class="px-3 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap border
                    {{ $currentType === 'rembush' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-500 border-slate-200 hover:bg-indigo-50' }}">
                    <i data-lucide="receipt" class="w-3 h-3 inline mr-1"></i>Rembush
                </a>
                <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => 'pengajuan'])) }}"
                    class="px-3 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap border
                    {{ $currentType === 'pengajuan' ? 'bg-teal-600 text-white border-teal-600' : 'bg-white text-slate-500 border-slate-200 hover:bg-teal-50' }}">
                    <i data-lucide="shopping-bag" class="w-3 h-3 inline mr-1"></i>Pengajuan
                </a>
            </div>
        </div>

        {{-- Status Tabs --}}
        <div id="search-results-container">
        <div class="px-5 pt-2 overflow-x-auto">
            <div class="flex items-center gap-2 min-w-max border-b border-gray-100">
                @php
                    $tabs = [
                        'all'       => ['label' => 'All',      'count' => $stats['count']],
                        'pending'   => ['label' => 'Pending',  'count' => $stats['pending']],
                        'approved'  => ['label' => 'Approved', 'count' => $stats['approved'] ?? 0],
                        'completed' => ['label' => 'Paid',     'count' => $stats['completed']],
                        'rejected'  => ['label' => 'Rejected', 'count' => $stats['rejected']],
                    ];
                    $currentStatus = request('status', 'all');
                @endphp

                @foreach($tabs as $key => $tab)
                    <a href="{{ route('transactions.index', ['status' => $key === 'all' ? null : $key, 'search' => request('search')]) }}"
                       class="relative px-4 py-3 text-sm font-medium transition-all {{ $currentStatus === $key ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                        {{ $tab['label'] }}
                        <span class="ml-1 text-xs opacity-70 status-count" data-status="{{ $key }}">({{ $tab['count'] }})</span>
                        @if($currentStatus === $key)
                            <div class="absolute bottom-0 left-0 w-full h-[2px] bg-blue-600 rounded-t-full"></div>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Table View --}}
        <div class="hidden lg:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-100 text-xs uppercase tracking-wider text-gray-400 font-semibold bg-gray-50/50">
                        <th class="px-5 py-4">Nama Pengaju</th>
                        <th class="px-5 py-4">Jenis</th>
                        <th class="px-5 py-4">Kategori</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">Tanggal</th>
                        <th class="px-5 py-4">Nominal</th>
                        <th class="px-5 py-4 text-center">Tindakan</th>
                    </tr>
                </thead>
                <tbody id="desktop-tbody" class="divide-y divide-gray-50 text-sm text-gray-600">
                    {{-- Will be populated by JavaScript --}}
                </tbody>
            </table>
            {{-- No results message --}}
            <div id="table-no-results" class="hidden px-6 py-20 text-center">
                <div class="flex flex-col items-center justify-center opacity-40">
                    <div class="p-4 bg-gray-50 rounded-full mb-4">
                        <i data-lucide="search" class="w-8 h-8 text-gray-400"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-900">Tidak Ditemukan</h3>
                    <p class="text-xs text-gray-500 mt-1">Tidak ada transaksi yang cocok dengan pencarian "<span id="no-result-query"></span>"</p>
                </div>
            </div>
        </div>

        {{-- Mobile/Tablet Card View --}}
        <div id="mobile-container" class="lg:hidden divide-y divide-gray-50">
            {{-- Will be populated by JavaScript --}}
        </div>

        {{-- No results message (mobile) --}}
        <div id="mobile-no-results" class="hidden lg:hidden p-12 text-center">
            <div class="flex flex-col items-center justify-center opacity-40">
                <i data-lucide="search" class="w-12 h-12 text-gray-300 mb-3"></i>
                <h3 class="text-sm font-bold text-gray-900">Tidak Ditemukan</h3>
                <p class="text-xs text-gray-500">Tidak ada transaksi yang cocok dengan pencarian "<span id="mobile-no-result-query"></span>"</p>
            </div>
        </div>

        {{-- Footer / Pagination --}}
        <div class="p-5 border-t border-gray-100 flex items-center justify-between">
            <p class="text-xs text-gray-500 font-medium">
                Showing <span id="showing-from">0</span> - <span id="showing-to">0</span> of <span id="total-records">0</span> transactions
            </p>
            <div id="pagination-container" class="flex items-center gap-2">
                {{-- Will be populated by JavaScript --}}
            </div>
        </div>
        </div>{{-- end #search-results-container --}}
    </div>

    {{-- VIEW DETAIL MODAL - Same as before --}}
    <div id="view-modal"
         class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-md p-4 opacity-0 transition-all duration-300"
         aria-hidden="true">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto transform scale-95 transition-all duration-300"
             id="view-modal-content">

            <div id="view-loading" class="p-12 text-center">
                <div class="w-10 h-10 border-4 border-slate-200 border-t-blue-500 rounded-full animate-spin mx-auto mb-4"></div>
                <p class="text-sm text-slate-400 font-medium">Memuat detail...</p>
            </div>

            <div id="view-body" class="hidden">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white z-10 rounded-t-2xl">
                    <div>
                        <h3 class="text-lg font-extrabold text-slate-900" id="v-title">Detail Transaksi</h3>
                        <p class="text-xs text-slate-400 font-medium mt-0.5" id="v-invoice"></p>
                    </div>
                    <button onclick="closeViewModal()"
                        class="p-2 hover:bg-slate-100 rounded-xl transition-colors text-slate-400 hover:text-slate-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="p-6 space-y-6">
                    <div class="flex items-center gap-2 flex-wrap" id="v-badges"></div>
                    <div id="v-image-wrap" class="hidden">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Foto Nota / Referensi</label>
                        <div class="border-2 border-dashed border-slate-200 rounded-2xl p-2 bg-slate-50/50 flex justify-center">
                            <img id="v-image" src="" class="max-h-48 object-contain rounded-xl" alt="Nota">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="v-fields"></div>
                    <div id="v-items-wrap" class="hidden">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Daftar Barang</label>
                        <div class="border border-slate-100 rounded-xl overflow-hidden">
                            <table class="w-full text-xs">
                                <thead class="bg-slate-50 text-[9px] text-slate-400 font-bold uppercase tracking-wider">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Nama</th>
                                        <th class="px-3 py-2 text-center">Qty</th>
                                        <th class="px-3 py-2 text-left">Satuan</th>
                                        <th class="px-3 py-2 text-right">Harga</th>
                                        <th class="px-3 py-2 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="v-items-tbody" class="divide-y divide-slate-50"></tbody>
                            </table>
                        </div>
                    </div>
                    <div id="v-specs-wrap" class="hidden">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Spesifikasi</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3" id="v-specs"></div>
                    </div>
                    <div id="v-branches-wrap" class="hidden">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Pembagian Cabang</label>
                        <div class="space-y-2" id="v-branches"></div>
                    </div>
                    <div id="v-rejection-wrap" class="hidden">
                        <div class="bg-red-50 border border-red-100 rounded-xl p-4">
                            <p class="text-[10px] font-bold text-red-400 uppercase tracking-wider mb-1">Alasan Penolakan</p>
                            <p class="text-sm text-red-700 font-medium" id="v-rejection"></p>
                        </div>
                    </div>
                    <div id="v-waiting-owner" class="hidden bg-amber-50 border border-amber-200 rounded-xl p-4">
                        <div class="flex items-center gap-2 text-amber-700">
                            <i data-lucide="clock" class="w-4 h-4"></i>
                            <p class="text-xs font-bold">Menunggu persetujuan dari Owner (nominal ≥ Rp 1.000.000)</p>
                        </div>
                    </div>
                    <div id="v-reviewer-wrap" class="hidden items-center gap-2 text-xs text-slate-400 pt-2 border-t border-slate-100">
                        <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                        <span>Direview oleh <strong id="v-reviewer" class="text-slate-600"></strong> pada <span id="v-reviewed-at"></span></span>
                    </div>
                    <div id="v-actions" class="hidden pt-2 border-t border-slate-100">
                        <button id="v-btn-reset"
                            onclick="submitApproval('pending')"
                            class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold text-xs transition-all border border-slate-200">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Reset ke Pending
                        </button>
                    </div>
                </div>

                <div class="p-6 border-t border-gray-100 bg-slate-50/50 rounded-b-2xl">
                    <button onclick="closeViewModal()"
                        class="w-full py-3 bg-slate-800 text-white rounded-xl font-bold text-sm hover:bg-slate-700 transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- REJECT MODAL - Same as before --}}
    <div id="reject-modal"
         class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] flex items-center justify-center opacity-0 transition-all duration-300">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 transform scale-95 transition-all duration-300">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-red-100 rounded-xl">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-red-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Tolak Nota</h3>
                        <p class="text-xs text-slate-500">Nota: <strong id="reject-modal-invoice"></strong></p>
                    </div>
                </div>
                <form id="reject-form" method="POST" action="">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="rejected">
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-600 mb-2">
                            Alasan Penolakan <span class="text-red-500">*</span>
                        </label>
                        <textarea name="rejection_reason" rows="3" required placeholder="Tuliskan alasan penolakan..."
                            class="w-full border border-red-200 rounded-xl p-3 text-sm outline-none focus:ring-2 focus:ring-red-100 focus:border-red-300 resize-none"></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="closeRejectModal()"
                            class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-600 font-bold text-xs uppercase tracking-wider hover:bg-slate-200 transition-all border border-slate-200">
                            Batal
                        </button>
                        <button type="submit"
                            class="flex-1 py-3 rounded-xl bg-red-600 text-white font-bold text-xs uppercase tracking-wider hover:bg-red-700 transition-all shadow-lg shadow-red-600/20">
                            Konfirmasi Tolak
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Toast Container --}}
    <div id="toast-container" class="fixed top-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"></div>

@endsection

@section('styles')
    <style>
        .ai-status-badge {
            transition: all 0.2s ease-in-out;
        }
        .ai-status-badge:hover {
            transform: scale(1.05);
            z-index: 10;
        }
        @keyframes subtle-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .ai-status-badge.animate-pulse {
            animation: subtle-pulse 2s ease-in-out infinite;
        }
        #toast-container .flex.items-center.gap-3 {
            border-left: 4px solid transparent;
        }
        #toast-container .bg-emerald-600 { border-left-color: #34d399; }
        #toast-container .bg-red-600 { border-left-color: #f87171; }
        #toast-container .bg-blue-600 { border-left-color: #60a5fa; }
    </style>
@endsection

@push('scripts')
<script>
    const csrfToken = '{{ csrf_token() }}';
    let currentTransactionId = null;
    const userRole = '{{ Auth::user()->role }}';
    const canManage = {{ Auth::user()->canManageStatus() ? 'true' : 'false' }};
    const isOwner = {{ Auth::user()->isOwner() ? 'true' : 'false' }};
    const isAdmin = {{ Auth::user()->isAdmin() ? 'true' : 'false' }};

    // ═══════════════════════════════════════════════════════════════
    // INSTANT SEARCH ENGINE - CLIENT SIDE
    // ═══════════════════════════════════════════════════════════════
    
    const SearchEngine = (function() {
        let allTransactions = [];
        let filteredTransactions = [];
        let currentPage = 1;
        const itemsPerPage = 20;
        let isLoading = false;

        // Load all data from server
        async function loadData() {
            if (isLoading) return;
            isLoading = true;
            
            try {
                const url = new URL(window.location.href);
                const params = new URLSearchParams(url.search);
                
                const response = await fetch('/transactions/search-data?' + params.toString());
                allTransactions = await response.json();
                filteredTransactions = [...allTransactions];
                
                renderPage();
                updateStats();
            } catch (error) {
                console.error('Failed to load transactions:', error);
                showToast('Gagal memuat data', 'error');
            } finally {
                isLoading = false;
            }
        }

        // Instant search algorithm (multi-field matching)
        function search(query) {
            if (!query || query.trim() === '') {
                filteredTransactions = [...allTransactions];
            } else {
                const searchTerm = query.toLowerCase().trim();
                const terms = searchTerm.split(/\s+/); // Split by whitespace for multi-word search
                
                filteredTransactions = allTransactions.filter(transaction => {
                    // Check if ALL terms exist in search_text
                    return terms.every(term => transaction.search_text.includes(term));
                });
            }
            
            currentPage = 1; // Reset to first page
            renderPage();
            updateStats();
        }

        // Render current page
        function renderPage() {
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const pageData = filteredTransactions.slice(startIndex, endIndex);
            
            renderDesktopTable(pageData);
            renderMobileCards(pageData);
            renderPagination();
            updateShowingText(startIndex, endIndex);
            
            // Re-init Lucide icons
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function renderDesktopTable(data) {
            const tbody = document.getElementById('desktop-tbody');
            const noResults = document.getElementById('table-no-results');
            
            if (data.length === 0) {
                tbody.innerHTML = '';
                noResults.classList.remove('hidden');
                const query = document.getElementById('instant-search').value;
                document.getElementById('no-result-query').textContent = query;
            } else {
                noResults.classList.add('hidden');
                tbody.innerHTML = data.map(t => generateDesktopRow(t)).join('');
            }
        }

        function renderMobileCards(data) {
            const container = document.getElementById('mobile-container');
            const noResults = document.getElementById('mobile-no-results');
            
            if (data.length === 0) {
                container.innerHTML = '';
                noResults.classList.remove('hidden');
                const query = document.getElementById('instant-search').value;
                document.getElementById('mobile-no-result-query').textContent = query;
            } else {
                noResults.classList.add('hidden');
                container.innerHTML = data.map(t => generateMobileCard(t)).join('');
            }
        }

        function renderPagination() {
            const container = document.getElementById('pagination-container');
            const totalPages = Math.ceil(filteredTransactions.length / itemsPerPage);
            
            if (totalPages <= 1) {
                container.innerHTML = '';
                return;
            }
            
            let html = '';
            
            // Previous button
            html += `<button onclick="SearchEngine.goToPage(${currentPage - 1})" 
                        ${currentPage === 1 ? 'disabled' : ''} 
                        class="px-3 py-1.5 rounded-lg border border-gray-200 text-sm font-medium ${currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'}">
                        Prev
                     </button>`;
            
            // Page numbers (show max 5 pages)
            const maxVisible = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
            let endPage = Math.min(totalPages, startPage + maxVisible - 1);
            
            if (endPage - startPage < maxVisible - 1) {
                startPage = Math.max(1, endPage - maxVisible + 1);
            }
            
            for (let i = startPage; i <= endPage; i++) {
                html += `<button onclick="SearchEngine.goToPage(${i})" 
                            class="px-3 py-1.5 rounded-lg border text-sm font-medium ${i === currentPage ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-200 hover:bg-gray-50'}">
                            ${i}
                         </button>`;
            }
            
            // Next button
            html += `<button onclick="SearchEngine.goToPage(${currentPage + 1})" 
                        ${currentPage === totalPages ? 'disabled' : ''} 
                        class="px-3 py-1.5 rounded-lg border border-gray-200 text-sm font-medium ${currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'}">
                        Next
                     </button>`;
            
            container.innerHTML = html;
        }

        function goToPage(page) {
            const totalPages = Math.ceil(filteredTransactions.length / itemsPerPage);
            if (page < 1 || page > totalPages) return;
            currentPage = page;
            renderPage();
        }

        function updateShowingText(start, end) {
            document.getElementById('showing-from').textContent = filteredTransactions.length > 0 ? start + 1 : 0;
            document.getElementById('showing-to').textContent = Math.min(end, filteredTransactions.length);
            document.getElementById('total-records').textContent = filteredTransactions.length;
        }

        function updateStats() {
            // Update status tab counts
            const statuses = ['all', 'pending', 'approved', 'completed', 'rejected'];
            statuses.forEach(status => {
                const count = status === 'all' 
                    ? filteredTransactions.length 
                    : filteredTransactions.filter(t => t.status === status).length;
                
                const el = document.querySelector(`.status-count[data-status="${status}"]`);
                if (el) el.textContent = `(${count})`;
            });
        }

        // Generate HTML for desktop row
        function generateDesktopRow(t) {
            const statusBadge = {
                pending:   'bg-amber-50 text-amber-600 border-amber-200',
                approved:  'bg-blue-50 text-blue-600 border-blue-200',
                completed: 'bg-green-50 text-green-600 border-green-200',
                rejected:  'bg-red-50 text-red-600 border-red-200',
            };
            const statusLabel = {
                pending:   'Pending',
                approved:  'Menunggu Owner',
                completed: 'Selesai',
                rejected:  'Ditolak',
            };

            const aiBadgeHtml = generateAIBadge(t);
            const inlineActionsHtml = generateInlineActions(t);

            return `
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center text-xs font-bold text-slate-500 shrink-0">
                                ${t.submitter_name.charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <div class="font-bold text-gray-900">${t.submitter_name}</div>
                                <div class="text-[11px] text-gray-400 font-medium">${t.invoice_number}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        ${t.type === 'pengajuan' 
                            ? '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-bold bg-teal-50 text-teal-600 border border-teal-100"><i data-lucide="shopping-bag" class="w-3 h-3"></i> Pengajuan</span>'
                            : '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-600 border border-indigo-100"><i data-lucide="receipt" class="w-3 h-3"></i> Rembush</span>'}
                    </td>
                    <td class="px-5 py-4 text-gray-700 font-medium text-xs">
                        ${t.category_label}
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border ${statusBadge[t.status]}">
                                ${statusLabel[t.status]}
                            </span>
                            ${aiBadgeHtml}
                            ${inlineActionsHtml}
                        </div>
                    </td>
                    <td class="px-5 py-4 text-gray-500 font-medium text-xs">
                        ${t.created_at}
                    </td>
                    <td class="px-5 py-4 font-bold text-gray-900">
                        Rp ${t.formatted_amount}
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center justify-center gap-1">
                            <button type="button" onclick="openViewModal(${t.id})" title="Lihat Detail"
                                class="p-2 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-all">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            ${canManage ? `
                                <a href="/transactions/${t.id}/edit" title="Edit"
                                    class="p-2 rounded-lg text-slate-400 hover:text-amber-600 hover:bg-amber-50 transition-all">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </a>
                                ${!isAdmin ? `
                                    <form action="/transactions/${t.id}" method="POST" onsubmit="return confirm('Hapus transaksi ${t.invoice_number}?')">
                                        <input type="hidden" name="_token" value="${csrfToken}">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" title="Hapus"
                                            class="p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-all">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                ` : ''}
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
        }

        // Generate HTML for mobile card
        function generateMobileCard(t) {
            const mStatusBadge = {
                pending:   'bg-amber-50 text-amber-700 border-amber-200',
                approved:  'bg-blue-50 text-blue-700 border-blue-200',
                completed: 'bg-green-50 text-green-700 border-green-200',
                rejected:  'bg-red-50 text-red-700 border-red-200',
            };
            const mStatusLabel = {
                pending:   'Pending',
                approved:  'Menunggu Owner',
                completed: 'Selesai',
                rejected:  'Ditolak',
            };

            const aiBadgeHtml = generateAIBadge(t);
            const mobileActionsHtml = generateMobileActions(t);

            return `
                <div class="p-4 md:p-5 hover:bg-slate-50/50 transition-colors">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center text-sm font-bold text-slate-500 shrink-0">
                                ${t.submitter_name.charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <h5 class="font-bold text-slate-900 text-sm">${t.submitter_name}</h5>
                                <p class="text-[10px] font-bold text-blue-500 uppercase tracking-widest">${t.invoice_number}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide border ${mStatusBadge[t.status]}">
                            ${mStatusLabel[t.status]}
                        </span>
                        ${aiBadgeHtml}
                    </div>

                    ${mobileActionsHtml}

                    <div class="flex items-center gap-2 flex-wrap text-xs font-medium text-slate-500 mb-3 mt-2">
                        ${t.type === 'pengajuan' 
                            ? '<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-teal-50 text-teal-600"><i data-lucide="shopping-bag" class="w-2.5 h-2.5"></i> Pengajuan</span>'
                            : '<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-600"><i data-lucide="receipt" class="w-2.5 h-2.5"></i> Rembush</span>'}
                        <div class="w-1 h-1 rounded-full bg-slate-300"></div>
                        <span>${t.category_label}</span>
                        <div class="w-1 h-1 rounded-full bg-slate-300"></div>
                        <span>${t.created_at}</span>
                    </div>

                    <div class="mb-3">
                        <p class="font-black text-slate-900 text-lg tracking-tight">Rp ${t.formatted_amount}</p>
                        ${t.status === 'rejected' && t.rejection_reason ? `
                            <p class="text-xs text-red-500 mt-1.5 flex items-start gap-1.5 bg-red-50 p-2 rounded-lg border border-red-100">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5 mt-0.5 flex-shrink-0"></i>
                                ${t.rejection_reason}
                            </p>
                        ` : ''}
                    </div>

                    <div class="flex items-center gap-2 mt-2">
                        <button type="button" onclick="openViewModal(${t.id})"
                            class="flex items-center gap-1.5 px-3 py-2 bg-white border border-gray-200 text-slate-600 rounded-xl hover:text-blue-600 hover:border-blue-200 transition-all shadow-sm text-xs font-bold">
                            <i data-lucide="eye" class="w-3.5 h-3.5"></i> Lihat
                        </button>
                        ${canManage ? `
                            <a href="/transactions/${t.id}/edit"
                                class="flex items-center gap-1.5 px-3 py-2 bg-white border border-gray-200 text-slate-600 rounded-xl hover:text-amber-600 hover:border-amber-200 transition-all shadow-sm text-xs font-bold">
                                <i data-lucide="pencil" class="w-3.5 h-3.5"></i> Edit
                            </a>
                            ${!isAdmin ? `
                                <form action="/transactions/${t.id}" method="POST" onsubmit="return confirm('Hapus transaksi ${t.invoice_number}?')">
                                    <input type="hidden" name="_token" value="${csrfToken}">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit"
                                        class="flex items-center gap-1.5 px-3 py-2 bg-white border border-gray-200 text-slate-600 rounded-xl hover:text-red-600 hover:border-red-200 transition-all shadow-sm text-xs font-bold">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Hapus
                                    </button>
                                </form>
                            ` : ''}
                        ` : ''}
                    </div>
                </div>
            `;
        }

        function generateAIBadge(t) {
            if (t.type !== 'rembush' || !['queued', 'pending', 'processing', 'completed', 'error'].includes(t.ai_status)) {
                return '';
            }

            const aiBadge = {
                queued:     { color: 'bg-gray-50 text-gray-600 border-gray-200', icon: 'clock', label: 'Antrian', pulse: false, title: 'Menunggu diproses' },
                pending:    { color: 'bg-gray-50 text-gray-600 border-gray-200', icon: 'clock', label: 'Pending', pulse: false, title: 'Menunggu upload selesai' },
                processing: { color: 'bg-purple-50 text-purple-600 border-purple-200', icon: 'loader-2', label: 'OCR...', pulse: true, title: 'Sedang memproses...' },
                completed:  { color: 'bg-green-50 text-green-600 border-green-200', icon: 'check-circle', label: 'AI ✓', pulse: false, title: `Selesai • Confidence: ${t.confidence ?? 0}%` },
                error:      { color: 'bg-red-50 text-red-600 border-red-200', icon: 'alert-circle', label: 'AI ✗', pulse: false, title: 'Gagal • Silakan isi manual' },
            }[t.ai_status];

            return `
                <span class="ai-status-badge inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[9px] font-bold border ml-1 ${aiBadge.color} ${aiBadge.pulse ? 'animate-pulse' : ''}"
                    data-upload-id="${t.upload_id || ''}"
                    data-transaction-id="${t.id}"
                    data-status="${t.ai_status}"
                    title="${aiBadge.title}">
                    <i data-lucide="${aiBadge.icon}" class="w-2.5 h-2.5 ${aiBadge.pulse ? 'animate-spin' : ''}"></i>
                    ${aiBadge.label}
                    ${t.ai_status === 'completed' && t.confidence ? `<span class="ml-0.5 opacity-70">(${t.confidence}%)</span>` : ''}
                </span>
            `;
        }

        function generateInlineActions(t) {
            if (!canManage) return '';

            let html = '';
            if (!isOwner && t.status === 'pending') {
                const approveTitle = t.effective_amount >= 1000000 ? 'Setujui (Menunggu Owner)' : 'Setujui';
                html = `
                    <div class="flex items-center gap-1 ml-1">
                        <form method="POST" action="/transactions/${t.id}/status">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <input type="hidden" name="_method" value="PATCH">
                            <input type="hidden" name="status" value="approved">
                            <button type="submit" title="${approveTitle}"
                                class="p-1.5 rounded-lg bg-green-50 text-green-600 hover:bg-green-600 hover:text-white border border-green-200 hover:border-green-600 transition-all">
                                <i data-lucide="check" class="w-3 h-3"></i>
                            </button>
                        </form>
                        <button type="button" onclick="openRejectModal(${t.id}, '${t.invoice_number}')" title="Tolak"
                            class="p-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white border border-red-200 hover:border-red-600 transition-all">
                            <i data-lucide="x" class="w-3 h-3"></i>
                        </button>
                    </div>
                `;
            } else if (isOwner && ['pending', 'approved'].includes(t.status)) {
                const approveTitle = t.status === 'approved' ? 'Approve Final' : 'Setujui';
                html = `
                    <div class="flex items-center gap-1 ml-1">
                        <form method="POST" action="/transactions/${t.id}/status">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <input type="hidden" name="_method" value="PATCH">
                            <input type="hidden" name="status" value="approved">
                            <button type="submit" title="${approveTitle}"
                                class="p-1.5 rounded-lg bg-green-50 text-green-600 hover:bg-green-600 hover:text-white border border-green-200 hover:border-green-600 transition-all">
                                <i data-lucide="check" class="w-3 h-3"></i>
                            </button>
                        </form>
                        <button type="button" onclick="openRejectModal(${t.id}, '${t.invoice_number}')" title="Tolak"
                            class="p-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white border border-red-200 hover:border-red-600 transition-all">
                            <i data-lucide="x" class="w-3 h-3"></i>
                        </button>
                    </div>
                `;
            }
            return html;
        }

        function generateMobileActions(t) {
            if (!canManage) return '';

            let showActions = false;
            let approveTitle = 'Setujui';

            if (!isOwner && t.status === 'pending') {
                showActions = true;
                approveTitle = t.effective_amount >= 1000000 ? 'Setujui (Menunggu Owner)' : 'Setujui';
            } else if (isOwner && ['pending', 'approved'].includes(t.status)) {
                showActions = true;
                approveTitle = t.status === 'approved' ? 'Approve Final' : 'Setujui';
            }

            if (!showActions) return '';

            return `
                <div class="flex items-center gap-2 mt-2">
                    <form method="POST" action="/transactions/${t.id}/status" class="flex-1">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="_method" value="PATCH">
                        <input type="hidden" name="status" value="approved">
                        <button type="submit"
                            class="w-full flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-green-50 text-green-700 hover:bg-green-600 hover:text-white font-bold text-xs transition-all border border-green-200 hover:border-green-600">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i> ${approveTitle}
                        </button>
                    </form>
                    <button type="button" onclick="openRejectModal(${t.id}, '${t.invoice_number}')"
                        class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-red-50 text-red-700 hover:bg-red-600 hover:text-white font-bold text-xs transition-all border border-red-200 hover:border-red-600">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i> Tolak
                    </button>
                </div>
            `;
        }

        // Public API
        return {
            init: loadData,
            search: search,
            goToPage: goToPage,
        };
    })();

    // ═══════════════════════════════════════════════════════════════
    // INITIALIZATION
    // ═══════════════════════════════════════════════════════════════
    
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // Load data
        SearchEngine.init();

        // Setup instant search
        const searchInput = document.getElementById('instant-search');
        const clearBtn = document.getElementById('search-clear');
        
        let searchTimer = null;
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            clearBtn.classList.toggle('hidden', query.length === 0);
            
            // Debounce for performance
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                SearchEngine.search(query);
            }, 150); // Ultra-fast debounce for instant feel
        });

        clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            SearchEngine.search('');
            clearBtn.classList.add('hidden');
            searchInput.focus();
        });

        // Show session toasts
        @if(session('success'))
            showToast(`
                <div class="flex items-start gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                    <div><strong>Berhasil!</strong><br><span class="text-[11px] opacity-90">{{ session('success') }}</span></div>
                </div>
            `, 'success');
        @endif

        @if(session('error'))
            showToast(`
                <div class="flex items-start gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                    <div><strong>Gagal!</strong><br><span class="text-[11px] opacity-90">{{ session('error') }}</span></div>
                </div>
            `, 'error');
        @endif

        @if($errors->any())
            @foreach ($errors->all() as $error)
                showToast(`
                    <div class="flex items-start gap-2">
                        <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                        <div><strong>Error!</strong><br><span class="text-[11px] opacity-90">{{ $error }}</span></div>
                    </div>
                `, 'error');
            @endforeach
        @endif
    });

    // Toast function
    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        let bgColor = 'bg-blue-600';
        if (type === 'success') bgColor = 'bg-emerald-600';
        else if (type === 'error') bgColor = 'bg-red-600';

        const toast = document.createElement('div');
        toast.className = `flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-white text-sm font-medium transform transition-all duration-300 translate-y-[-20px] opacity-0 ${bgColor}`;
        toast.innerHTML = message;

        container.appendChild(toast);

        requestAnimationFrame(() => {
            toast.classList.remove('translate-y-[-20px]', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
        });
        
        if (typeof lucide !== 'undefined') {
            lucide.createIcons({ root: toast });
        }

        setTimeout(() => {
            toast.classList.remove('translate-y-0', 'opacity-100');
            toast.classList.add('translate-y-[-20px]', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    // ═══════════════════════════════════════════════════════════════
    // VIEW MODAL & OTHER FUNCTIONS (same as before)
    // ═══════════════════════════════════════════════════════════════
    
    function openViewModal(id) {
        currentTransactionId = id;

        const modal      = document.getElementById('view-modal');
        const modalBox   = document.getElementById('view-modal-content');
        const loading    = document.getElementById('view-loading');
        const body       = document.getElementById('view-body');

        loading.innerHTML = `
            <div class="w-10 h-10 border-4 border-slate-200 border-t-blue-500 rounded-full animate-spin mx-auto mb-4"></div>
            <p class="text-sm text-slate-400 font-medium">Memuat detail...</p>`;
        loading.classList.remove('hidden');
        body.classList.add('hidden');

        modal.classList.remove('hidden');
        requestAnimationFrame(() => {
            modal.classList.remove('opacity-0');
            modalBox.classList.remove('scale-95');
            modalBox.classList.add('scale-100');
        });

        fetch(`/transactions/${id}/detail-json`)
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(d => {
                renderViewModal(d);
                loading.classList.add('hidden');
                body.classList.remove('hidden');
                if (typeof lucide !== 'undefined') lucide.createIcons();
            })
            .catch(err => {
                console.error(err);
                loading.innerHTML = '<p class="text-red-500 text-sm font-bold">Gagal memuat data. Coba lagi.</p>';
            });
    }

    function closeViewModal() {
        const modal    = document.getElementById('view-modal');
        const modalBox = document.getElementById('view-modal-content');
        modal.classList.add('opacity-0');
        modalBox.classList.remove('scale-100');
        modalBox.classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    document.getElementById('view-modal').addEventListener('click', e => {
        if (e.target.id === 'view-modal') closeViewModal();
    });

    function renderViewModal(d) {
        currentTransactionId = d.id;

        document.getElementById('v-title').textContent   = d.type === 'pengajuan' ? 'Detail Pengajuan' : 'Detail Reimbursement';
        document.getElementById('v-invoice').textContent = d.invoice_number + ' • ' + d.created_at;

        const statusColors = {
            pending:   'bg-amber-50 text-amber-600 border-amber-200',
            approved:  'bg-blue-50 text-blue-600 border-blue-200',
            completed: 'bg-green-50 text-green-600 border-green-200',
            rejected:  'bg-red-50 text-red-600 border-red-200',
        };
        const typeBg      = d.type === 'pengajuan' ? 'bg-teal-50 text-teal-600 border-teal-100' : 'bg-indigo-50 text-indigo-600 border-indigo-100';
        const statusText  = d.status === 'approved' ? 'Menunggu Approve Owner' : d.status_label;
        const typeIcon    = d.type === 'pengajuan' ? 'shopping-bag' : 'receipt';

        document.getElementById('v-badges').innerHTML = `
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border ${statusColors[d.status] || ''}">${statusText}</span>
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-bold border ${typeBg}">
                <i data-lucide="${typeIcon}" class="w-3 h-3"></i> ${d.type_label}
            </span>`;

        const imgWrap = document.getElementById('v-image-wrap');
        if (d.image_url) {
            imgWrap.classList.remove('hidden');
            document.getElementById('v-image').src = d.image_url;
        } else {
            imgWrap.classList.add('hidden');
        }

        const fieldsEl = document.getElementById('v-fields');
        let fieldsHtml = '';

        const addField = (label, value, span2 = false) => {
            if (value === null || value === undefined || value === '') return;
            fieldsHtml += `
                <div class="${span2 ? 'sm:col-span-2' : ''}">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-wider">${label}</label>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800">${value}</div>
                </div>`;
        };

        addField('Pengaju', d.submitter?.name || '-');

        if (d.type === 'rembush') {
            addField('Nama Vendor',       d.customer);
            addField('Tanggal Transaksi', d.date);
            addField('Kategori',          d.category_label);
            addField('Metode Pencairan',  d.payment_method_label);
            addField('Keterangan',        d.description, true);
            addField('Total Nominal',     d.amount ? 'Rp ' + Number(d.amount).toLocaleString('id-ID') : null);
        } else {
            addField('Nama Barang/Jasa',      d.customer, true);
            addField('Vendor',                d.vendor);
            addField('Alasan Pembelian',      d.purchase_reason_label);
            addField('Jumlah',                d.quantity);
            addField('Estimasi Harga Satuan', d.estimated_price ? 'Rp ' + Number(d.estimated_price).toLocaleString('id-ID') : null);
            addField('Total Estimasi',        d.amount ? 'Rp ' + Number(d.amount).toLocaleString('id-ID') : null);
        }

        fieldsEl.innerHTML = fieldsHtml;

        const itemsWrap  = document.getElementById('v-items-wrap');
        const itemsTbody = document.getElementById('v-items-tbody');
        if (d.type === 'rembush' && d.items && d.items.length > 0) {
            itemsWrap.classList.remove('hidden');
            itemsTbody.innerHTML = d.items.map(item => `
                <tr class="hover:bg-slate-50/50">
                    <td class="px-3 py-2 text-slate-700 font-medium">${item.name || '-'}</td>
                    <td class="px-3 py-2 text-center">${item.qty || '-'}</td>
                    <td class="px-3 py-2">${item.unit || '-'}</td>
                    <td class="px-3 py-2 text-right">Rp ${Number(item.price || 0).toLocaleString('id-ID')}</td>
                    <td class="px-3 py-2 text-right font-bold">Rp ${( (Number(item.qty) || 0) * (Number(item.price) || 0) ).toLocaleString('id-ID')}</td>
                </tr>`).join('');
        } else {
            itemsWrap.classList.add('hidden');
        }

        const specsWrap = document.getElementById('v-specs-wrap');
        const specsEl   = document.getElementById('v-specs');
        if (d.type === 'pengajuan' && d.specs && Object.values(d.specs).some(v => v)) {
            specsWrap.classList.remove('hidden');
            const specLabels = { merk: 'Merk', tipe: 'Tipe/Seri', ukuran: 'Ukuran', warna: 'Warna' };
            specsEl.innerHTML = Object.entries(specLabels).map(([key, label]) => `
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-wider">${label}</label>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">${d.specs[key] || '-'}</div>
                </div>`).join('');
        } else {
            specsWrap.classList.add('hidden');
        }

        const branchesWrap = document.getElementById('v-branches-wrap');
        const branchesEl   = document.getElementById('v-branches');
        if (d.branches && d.branches.length > 0) {
            branchesWrap.classList.remove('hidden');
            branchesEl.innerHTML = d.branches.map(b => `
                <div class="flex items-center justify-between bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                    <span class="text-sm font-bold text-slate-700">${b.name}</span>
                    <div class="text-right">
                        <span class="text-xs font-bold text-slate-500">${b.percent}%</span>
                        <span class="text-xs text-slate-400 ml-2">(${b.amount})</span>
                    </div>
                </div>`).join('');
        } else {
            branchesWrap.classList.add('hidden');
        }

        const rejWrap = document.getElementById('v-rejection-wrap');
        if (d.status === 'rejected' && d.rejection_reason) {
            rejWrap.classList.remove('hidden');
            document.getElementById('v-rejection').textContent = d.rejection_reason;
        } else {
            rejWrap.classList.add('hidden');
        }

        const waitOwner = document.getElementById('v-waiting-owner');
        waitOwner.classList.toggle('hidden', d.status !== 'approved');

        const revWrap = document.getElementById('v-reviewer-wrap');
        if (d.reviewer) {
            revWrap.classList.remove('hidden');
            revWrap.classList.add('flex');
            document.getElementById('v-reviewer').textContent    = d.reviewer.name;
            document.getElementById('v-reviewed-at').textContent = d.reviewed_at;
        } else {
            revWrap.classList.add('hidden');
            revWrap.classList.remove('flex');
        }

        const actionsWrap = document.getElementById('v-actions');
        const btnReset    = document.getElementById('v-btn-reset');
        if (d.is_owner && d.status !== 'pending') {
            btnReset.classList.remove('hidden');
            actionsWrap.classList.remove('hidden');
        } else {
            btnReset.classList.add('hidden');
            actionsWrap.classList.add('hidden');
        }
    }

    function submitApproval(status) {
        if (!currentTransactionId) return;
        if (status === 'pending' && !confirm('Reset status ke Pending?')) return;

        const buttons = document.querySelectorAll('#v-actions button');
        buttons.forEach(b => b.disabled = true);

        fetch(`/transactions/${currentTransactionId}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ status, _method: 'PATCH' }),
        })
        .then(r => {
            if (r.redirected) { window.location.href = r.url; return; }
            window.location.reload();
        })
        .catch(err => {
            console.error(err);
            alert('Gagal mengubah status. Coba lagi.');
            buttons.forEach(b => b.disabled = false);
        });
    }

    function openRejectModal(transactionId, invoiceNumber) {
        const modal = document.getElementById('reject-modal');
        const inner = modal.querySelector('div');

        document.getElementById('reject-form').action      = '/transactions/' + transactionId + '/status';
        document.getElementById('reject-modal-invoice').textContent = invoiceNumber;

        modal.classList.remove('hidden');
        requestAnimationFrame(() => {
            modal.classList.remove('opacity-0');
            inner.classList.remove('scale-95');
            inner.classList.add('scale-100');
        });
    }

    function closeRejectModal() {
        const modal = document.getElementById('reject-modal');
        const inner = modal.querySelector('div');
        modal.classList.add('opacity-0');
        inner.classList.remove('scale-100');
        inner.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.querySelector('textarea').value = '';
        }, 300);
    }

    document.getElementById('reject-modal').addEventListener('click', e => {
        if (e.target.id === 'reject-modal') closeRejectModal();
    });

    // Realtime updates
    window.handleRealtimeTransactionUpdate = function(transaction) {
        SearchEngine.init(); // Reload data
    };
    
    window.handleRealtimeTransactionCreation = function(transaction) {
        SearchEngine.init(); // Reload data
    };

</script>
@endpush