{{-- /**
 * ═══════════════════════════════════════════════════════════════
 *  PATCH untuk index.blade.php @push('scripts')
 *  
 *  ✅ Bug #4: allTransactions tidak accessible dari outer scope
 *
 *  INSTRUKSI:
 *  Cari dan ganti bagian-bagian berikut di file index.blade.php
 * ═══════════════════════════════════════════════════════════════
 */ --}}

@extends('layouts.app')

@section('page-title', 'Data Riwayat Transaksi')

@section('content')
    {{-- Main Content Card --}}
    <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-100">
        {{-- Header Toolbar --}}
        <div class="p-3 sm:p-4 md:p-5 border-b border-gray-100 flex flex-col gap-3 lg:flex-row lg:gap-4 lg:justify-between lg:items-center">
            <div class="flex flex-col md:flex-row gap-3 flex-1">
                {{-- Search --}}
                <div class="relative w-full md:w-64 lg:w-80">
                    <i data-lucide="search" class="absolute left-3 sm:left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                    <input type="text"
                        id="instant-search"
                        value="{{ request('search') }}"
                        placeholder="Cari invoice, nama, vendor..."
                        autocomplete="off"
                        class="w-full pl-9 sm:pl-10 pr-9 sm:pr-10 py-2 sm:py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-300 transition-all placeholder:text-gray-400">
                    <button type="button" id="search-clear" class="absolute right-3 top-1/2 -translate-y-1/2 hidden p-0.5 rounded-md hover:bg-gray-200 transition-colors" title="Hapus pencarian">
                        <i data-lucide="x" class="w-3.5 h-3.5 text-gray-400"></i>
                    </button>
                </div>

                @if(auth()->user()->role !== 'teknisi')
                    {{-- Date Range --}}
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-1.5 bg-gray-50 border border-gray-200 rounded-xl px-2.5 py-2 focus-within:ring-2 focus-within:ring-blue-100 focus-within:border-blue-300 transition-all">
                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-gray-400"></i>
                            <input type="date" id="filter-start-date" value="{{ request('start_date') }}" title="Mulai" class="text-[11px] font-bold text-gray-600 bg-transparent outline-none cursor-pointer">
                            <span class="text-gray-300 text-[10px]">—</span>
                            <input type="date" id="filter-end-date" value="{{ request('end_date') }}" title="Selesai" class="text-[11px] font-bold text-gray-600 bg-transparent outline-none cursor-pointer">
                        </div>
                    </div>

                    {{-- Branch Filter --}}
                    <div class="flex items-center gap-2">
                        <select id="filter-branch-id" title="Cabang" class="text-[11px] font-bold text-gray-600 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-300 transition-all cursor-pointer">
                            <option value="all">Semua Cabang</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Category Filter --}}
                    <div class="flex items-center gap-2">
                        <select id="filter-category" title="Kategori" class="text-[11px] font-bold text-gray-600 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-300 transition-all cursor-pointer">
                            <option value="all">Semua Kategori</option>
                            @foreach($categories as $key => $val)
                                <option value="{{ $key }}" {{ request('category') == $key ? 'selected' : '' }}>{{ $val }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            {{-- Type Filter --}}
            <div class="flex items-center gap-2 sm:gap-3 w-full lg:w-auto overflow-x-auto scrollbar-hide py-1">
                @php $currentType = request('type', 'all'); @endphp
                <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => null])) }}"
                    class="px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-lg sm:rounded-xl text-xs font-bold transition-all whitespace-nowrap border
                    {{ $currentType === 'all' ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-slate-500 border-slate-200 hover:bg-slate-50' }}">
                    Semua
                </a>
                <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => 'rembush'])) }}"
                    class="px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-lg sm:rounded-xl text-xs font-bold transition-all whitespace-nowrap border
                    {{ $currentType === 'rembush' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-500 border-slate-200 hover:bg-indigo-50' }}">
                    <i data-lucide="receipt" class="w-3 h-3 inline mr-0.5 sm:mr-1"></i>Rembush
                </a>
                <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => 'pengajuan'])) }}"
                    class="px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-lg sm:rounded-xl text-xs font-bold transition-all whitespace-nowrap border
                    {{ $currentType === 'pengajuan' ? 'bg-teal-600 text-white border-teal-600' : 'bg-white text-slate-500 border-slate-200 hover:bg-teal-50' }}">
                    <i data-lucide="shopping-bag" class="w-3 h-3 inline mr-0.5 sm:mr-1"></i>Pengajuan
                </a>
            </div>
        </div>

        {{-- Active Filters Indicator --}}
        <div id="active-filters-bar" class="px-3 sm:px-5 py-2 border-b border-gray-50 bg-gray-50/30 flex flex-wrap items-center gap-2 hidden">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mr-1">Filter Aktif:</span>
            <div id="active-filters-chips" class="flex flex-wrap items-center gap-2"></div>
            <button type="button" id="clear-all-filters" class="text-[10px] font-bold text-red-500 hover:text-red-600 ml-auto transition-colors">Hapus Semua</button>
        </div>

        {{-- Status Tabs --}}
        <div id="search-results-container">
        <div class="tabs-scroll-container px-3 sm:px-5 pt-1 sm:pt-2 overflow-x-auto scrollbar-hide">
            <div class="flex items-center gap-0.5 sm:gap-1 md:gap-2 min-w-max border-b border-gray-100">
                @php
                    $tabs = [
                        'all'       => ['label' => 'All',      'count' => $stats['count']],
                        'pending'   => ['label' => 'Pending',  'count' => $stats['pending']],
                        'auto-reject'     => ['label' => 'Auto Reject', 'count' => $stats['auto_reject'] ?? 0],
                        'flagged'         => ['label' => 'Flagged',     'count' => $stats['flagged'] ?? 0],
                        'waiting_payment' => ['label' => 'Waiting Payment', 'count' => $stats['waiting_payment'] ?? 0],
                        'approved'  => ['label' => 'Approved', 'count' => $stats['approved'] ?? 0],
                        'completed' => ['label' => 'Paid',     'count' => $stats['completed']],
                        'rejected'  => ['label' => 'Rejected', 'count' => $stats['rejected']],
                    ];
                    $currentStatus = request('status', 'all');
                @endphp

                @foreach($tabs as $key => $tab)
                    <a href="{{ route('transactions.index', ['status' => $key === 'all' ? null : $key, 'search' => request('search')]) }}"
                       class="relative px-2.5 sm:px-3 md:px-4 py-2.5 sm:py-3 text-[11px] sm:text-xs md:text-sm font-medium transition-all whitespace-nowrap {{ $currentStatus === $key ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                        {{ $tab['label'] }}
                        <span class="ml-0.5 sm:ml-1 text-[10px] sm:text-xs opacity-70 status-count" data-status="{{ $key }}">({{ $tab['count'] }})</span>
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
                        <th class="px-4 py-4 text-center w-10">No.</th>
                        <th class="px-5 py-4">Nama Pengaju</th>
                        <th class="px-5 py-4">Jenis</th>
                        <th class="px-5 py-4">Kategori</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">Tanggal</th>
                        <th class="px-5 py-4">Nominal</th>
                        <th class="px-5 py-4 text-center">Tindakan</th>
                    </tr>
                </thead>
                <tbody id="desktop-tbody" class="divide-y divide-gray-50 text-sm text-gray-600 transition-all duration-300">
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
        <div id="mobile-container" class="lg:hidden divide-y divide-gray-50 transition-all duration-300">
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
        <div class="p-3 sm:p-4 md:p-5 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-2 sm:gap-0">
            <p class="text-[11px] sm:text-xs text-gray-500 font-medium order-2 sm:order-1">
                Showing <span id="showing-from">0</span> - <span id="showing-to">0</span> of <span id="total-records">0</span> transactions
            </p>
            <div id="pagination-container" class="flex items-center gap-1 sm:gap-2 order-1 sm:order-2">
                {{-- Will be populated by JavaScript --}}
            </div>
        </div>
        </div>{{-- end #search-results-container --}}
    </div>

@push('modals')
    {{-- VIEW DETAIL MODAL --}}
    <div id="view-modal"
         class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black/30 backdrop-blur-md p-0 sm:p-4 opacity-0 transition-all duration-300"
         role="dialog"
         aria-modal="true"
         aria-labelledby="view-modal-title">
        <div class="bg-white rounded-none sm:rounded-2xl shadow-2xl max-w-2xl w-full h-[100dvh] sm:h-auto sm:max-h-[90vh] flex flex-col overflow-hidden transform scale-95 transition-all duration-300 overscroll-contain"
             id="view-modal-content">

            <div id="view-loading" class="p-12 text-center w-full flex flex-col items-center justify-center min-h-[50vh]">
                <div class="w-10 h-10 border-4 border-slate-200 border-t-blue-500 rounded-full animate-spin mx-auto mb-4"></div>
                <p class="text-sm text-slate-400 font-medium">Memuat detail...</p>
            </div>

            <div id="view-body" class="flex-col flex-auto min-h-0 w-full" style="display: none;">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-white z-10 shrink-0">
                    <div>
                        <h3 class="text-lg font-extrabold text-slate-900" id="view-modal-title">Detail Pengajuan</h3>
                        <p class="text-xs text-slate-400 font-medium mt-0.5" id="v-invoice"></p>
                    </div>
                    <button onclick="closeViewModal()"
                        class="p-2 hover:bg-slate-100 rounded-xl transition-colors text-slate-400 hover:text-slate-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="p-6 space-y-6 overflow-y-auto grow min-h-0 overscroll-contain">
                    <div class="flex items-center gap-2 flex-wrap" id="v-badges"></div>
                    
                    {{-- ✅ Revisi Banner (Pengajuan yang direvisi Management) --}}
                    <div id="v-revision-banner" class="hidden"></div>

                    {{-- ✅ UPDATED: Foto dengan Click-to-Zoom --}}
                    <div id="v-image-wrap" class="hidden">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Foto Nota / Referensi</label>
                        <div id="v-image-wrapper" 
                             class="border-2 border-emerald-200 rounded-2xl p-2 bg-emerald-50/50 flex justify-center relative overflow-hidden cursor-pointer hover:border-emerald-400 transition-colors group"
                             title="Klik untuk memperbesar">
                            <img id="v-image" src="" class="max-h-48 object-contain rounded-xl" alt="Nota">
                            
                            {{-- Preview Badge --}}
                            <div class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm rounded-full px-3 py-1.5 shadow-lg flex items-center gap-1.5 pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity">
                                <i data-lucide="expand" class="w-3.5 h-3.5 text-emerald-600"></i>
                                <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Perbesar</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="v-fields"></div>
                    <div id="v-items-wrap" class="hidden">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Daftar Barang</label>
                        {{-- Table Container untuk Rembush --}}
                        <div id="v-items-table-container" class="border border-slate-100 rounded-xl overflow-hidden hidden">
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
                        {{-- Div Container untuk Pengajuan (Cards Grid) --}}
                        <div id="v-items-div-container" class="hidden flex-col"></div>
                    </div>
                    <div id="v-specs-wrap" class="hidden">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Spesifikasi</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3" id="v-specs"></div>
                    </div>
                    
                    {{-- ✅ Summary Area (Keterangan Global & Total Estimasi) --}}
                    <div id="v-summary-wrap" class="hidden mt-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div id="v-summary-desc-wrap" class="md:col-span-2 bg-slate-50 border border-slate-200 rounded-xl p-4 hidden">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Keterangan Global</label>
                                <p class="text-xs font-medium text-slate-700 whitespace-pre-wrap leading-relaxed" id="v-summary-desc"></p>
                            </div>
                            <div id="v-summary-total-wrap" class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 flex flex-col justify-center shadow-sm">
                                <label class="block text-[10px] font-bold text-blue-800/60 uppercase tracking-wider mb-1">Total Estimasi</label>
                                <p class="text-lg md:text-xl font-black text-blue-700 tracking-tight flex items-baseline" id="v-summary-total"></p>
                            </div>
                        </div>
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

                    <!-- Riwayat Pembayaran (Payment History) -->
                    <div id="v-payment-history-wrap" class="hidden mt-6 pt-6 border-t border-slate-100 bg-white">
                        <h4 class="text-lg font-black text-slate-800 mb-6">Riwayat Pembayaran</h4>

                        <!-- Main Card Container -->
                        <div class="bg-slate-100 border border-slate-100 rounded-3xl shadow-sm p-5 sm:p-6">
                            <div class="space-y-10 relative before:absolute before:left-[7px] before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-100">
                                
                                <!-- Step 1: Penyerahan / Upload -->
                                <div class="relative pl-8">
                                    <div class="absolute left-0 top-1.5 w-3.5 h-3.5 bg-blue-600 rounded-full border-2 border-white shadow-[0_0_0_1px_rgba(37,99,235,0.2)]"></div>
                                    
                                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                        <div class="space-y-1.5">
                                            <h5 id="v-pay-step1-title" class="text-xs font-black text-slate-800 uppercase tracking-widest">BUKTI TRANSFER DIUNGGAH</h5>
                                            
                                            <div class="flex items-center gap-1.5 px-3 py-1 bg-slate-50 text-slate-500 rounded-full w-fit border border-slate-100">
                                                <i data-lucide="calendar" class="w-3 h-3"></i>
                                                <span id="v-pay-step1-at" class="text-[10px] font-black uppercase tracking-tight"></span>
                                            </div>
                                            
                                            <p class="text-xs text-slate-400 font-medium">
                                                Oleh: <span id="v-pay-step1-by" class="font-bold text-slate-600"></span> 
                                                <span class="mx-1 opacity-50">•</span> 
                                                Role: <span id="v-pay-step1-role" class="font-bold text-slate-600"></span>
                                            </p>
                                        </div>

                                        <!-- Action Button Step 1 -->
                                        <div id="v-pay-step1-action-wrap"></div>
                                    </div>
                                </div>

                                <!-- Step 2: Penerimaan -->
                                <div class="relative pl-8" id="v-pay-step2-wrap">
                                    <div class="absolute left-0 top-1.5 w-3.5 h-3.5 bg-emerald-500 rounded-full border-2 border-white shadow-[0_0_0_1px_rgba(16,185,129,0.2)]"></div>
                                    
                                    <div class="space-y-1.5">
                                        <h5 class="text-xs font-black text-slate-800 uppercase tracking-widest">PEMBAYARAN DITERIMA</h5>
                                        
                                        <div class="flex items-center gap-1.5 px-3 py-1 bg-slate-50 text-slate-500 rounded-full w-fit border border-slate-100">
                                            <i data-lucide="calendar" class="w-3 h-3"></i>
                                            <span id="v-pay-step2-at" class="text-[10px] font-black uppercase tracking-tight"></span>
                                        </div>
                                        
                                        <p class="text-xs text-slate-400 font-medium">
                                            Oleh: <span id="v-pay-step2-by" class="font-bold text-slate-600"></span> 
                                            <span class="mx-1 opacity-50">•</span> 
                                            Role: <span id="v-pay-step2-role" class="font-bold text-slate-600"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Black Summary Box -->
                            <div class="mt-8 bg-[#0F172A] rounded-2xl p-6 text-white relative overflow-hidden group shadow-xl">
                                <!-- Subtle Grid Background -->
                                <div class="absolute inset-0  pointer-events-none" style="background-image: radial-gradient(#000000 1px, #000000  1px); background-size: 20px 20px;"></div>
                                
                                <div class="relative z-10 space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-1">
                                            <span class="block text-[9px] font-black text-slate-500 uppercase tracking-[0.2em]">TOTAL DIBAYARKAN</span>
                                            <div id="v-pay-summary-amount" class="text-3xl sm:text-4xl font-black text-emerald-400 tracking-tighter"></div>
                                            <div id="v-pay-summary-discrepancy" class="text-[10px] font-bold mt-1 px-2 py-0.5 rounded-lg hidden uppercase tracking-wider"></div>
                                        </div>
                                        <div id="v-pay-method-wrap" class="space-y-1 md:text-right hidden">
                                            <span class="block text-[9px] font-black text-slate-500 uppercase tracking-[0.2em]">METODE PENCAIRAN</span>
                                            <div id="v-pay-summary-method" class="text-lg font-black text-white tracking-tight uppercase"></div>
                                            <div id="v-pay-summary-account" class="text-[10px] font-bold text-slate-400 mt-1 leading-relaxed"></div>
                                        </div>
                                    </div>

                                    <div class="pt-6 border-t border-slate-800 flex items-center gap-2">
                                        <div class="w-6 h-6 bg-emerald-500/10 rounded-lg flex items-center justify-center">
                                            <i data-lucide="sparkles" class="w-3.5 h-3.5 text-emerald-400"></i>
                                        </div>
                                        <p class="text-[10px] font-bold text-slate-400 tracking-wide">
                                            Otomatis diverifikasi & divalidasi oleh <span class="text-white">AI</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="v-actions" class="hidden pt-2 border-t border-slate-100">
                        <button id="v-btn-reset"
                            onclick="submitApproval('pending')"
                            class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold text-xs transition-all border border-slate-200">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Reset ke Pending
                        </button>
                    </div>
                </div>

                <div class="p-6 border-t border-gray-100 bg-slate-50/50 shrink-0">
                    <button onclick="closeViewModal()"
                        class="w-full py-3 bg-slate-800 text-white rounded-xl font-bold text-sm hover:bg-slate-700 transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('modals')
    {{-- ✅ IMAGE VIEWER MODAL (Fullscreen Zoom) --}}
    <div id="image-viewer"
         class="fixed inset-0 bg-black/90 backdrop-blur-md hidden items-center justify-center z-[9999] p-4 sm:p-10 overscroll-contain"
         role="dialog" 
         aria-modal="true" 
         aria-labelledby="viewer-title">

        {{-- Container margin sisi 4 --}}
        <div class="w-full h-full max-w-4xl bg-white rounded-2xl flex flex-col p-4 sm:p-8 shadow-2xl relative overflow-hidden" id="viewer-card">

            {{-- Header & Close Button --}}
            <div class="flex justify-between items-center shrink-0 mb-6 border-b border-slate-100 pb-4">
                <div>
                    <h3 class="text-sm sm:text-base font-black text-slate-800 uppercase tracking-widest" id="viewer-header-title">PREVIEW FOTO</h3>
                    <p id="viewer-title" class="text-[10px] text-slate-400 font-bold mt-1 uppercase tracking-wider">Klik di luar gambar ruang ini atau X untuk menutup</p>
                </div>
                <button id="close-viewer"
                        type="button"
                        onclick="closeImageViewer()"
                        class="w-10 h-10 sm:w-12 sm:h-12 flex items-center justify-center rounded-2xl bg-slate-100 hover:bg-red-50 text-slate-500 hover:text-red-500 transition-all active:scale-95"
                        aria-label="Tutup preview">
                    <i data-lucide="x" class="w-5 h-5 sm:w-6 sm:h-6"></i>
                </button>
            </div>

            {{-- Gambar Wrapper dengan Background Grid/Dots ala Preview Image --}}
            <div class="w-full flex-1 flex justify-center items-center bg-slate-50 rounded-2xl overflow-hidden relative border-2 border-slate-100 p-2 sm:p-4">
                <div class="absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(#000 1px, transparent 1px); background-size: 20px 20px;"></div>
                <img id="viewer-image"
                     src=""
                     class="relative z-10 max-w-full max-h-full object-contain drop-shadow-2xl rounded-lg"
                     alt="Preview foto referensi" />
            </div>
        </div>
    </div>
@endpush

@push('modals')
    {{-- REJECT MODAL --}}
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
@endpush

@push('modals')
    {{-- OVERRIDE MODAL --}}
    <div id="override-modal"
         class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] flex items-center justify-center opacity-0 transition-all duration-300">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 transform scale-95 transition-all duration-300">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-orange-100 rounded-xl">
                        <i data-lucide="alert-circle" class="w-5 h-5 text-orange-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Request Override</h3>
                        <p class="text-xs text-slate-500">Nota: <strong id="override-modal-invoice"></strong></p>
                    </div>
                </div>
                <form id="override-form" method="POST" action="">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-600 mb-2">
                            Alasan Override <span class="text-red-500">*</span>
                        </label>
                        <textarea name="override_reason" rows="3" required placeholder="Jelaskan mengapa AI salah..."
                            class="w-full border border-orange-200 rounded-xl p-3 text-sm outline-none focus:ring-2 focus:ring-orange-100 focus:border-orange-300 resize-none"></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="closeOverrideModal()"
                            class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-600 font-bold text-xs uppercase tracking-wider hover:bg-slate-200 transition-all border border-slate-200">
                            Batal
                        </button>
                        <button type="submit" id="btnSubmitOverride"
                            class="flex-1 py-3 rounded-xl bg-orange-600 text-white font-bold text-xs uppercase tracking-wider hover:bg-orange-700 transition-all shadow-lg shadow-orange-600/20">
                            Kirim Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush

@push('modals')
    {{-- FORCE APPROVE MODAL --}}
    <div id="force-approve-modal"
         class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] flex items-center justify-center opacity-0 transition-all duration-300">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 transform scale-95 transition-all duration-300">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-rose-100 rounded-xl">
                        <i data-lucide="shield-alert" class="w-5 h-5 text-rose-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Force Approve</h3>
                        <p class="text-xs text-slate-500">Nota: <strong id="force-approve-modal-invoice"></strong></p>
                    </div>
                </div>
                <form id="force-approve-form" method="POST" action="">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-600 mb-2">
                            Alasan Rekonsiliasi <span class="text-red-500">*</span>
                        </label>
                        <textarea name="force_approve_reason" rows="3" required placeholder="Alasan mengapa disetujui meski nilai beda..."
                            class="w-full border border-rose-200 rounded-xl p-3 text-sm outline-none focus:ring-2 focus:ring-rose-100 focus:border-rose-300 resize-none"></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="closeForceApproveModal()"
                            class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-600 font-bold text-xs uppercase tracking-wider hover:bg-slate-200 transition-all border border-slate-200">
                            Batal
                        </button>
                        <button type="submit" id="btnSubmitForce"
                            class="flex-1 py-3 rounded-xl bg-rose-600 text-white font-bold text-xs uppercase tracking-wider hover:bg-rose-700 transition-all shadow-lg shadow-rose-600/20">
                            Force Approve
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush

@push('modals')
    {{-- PAYMENT UPLOAD MODAL --}}
    <div id="payment-modal"
         class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] flex items-center justify-center opacity-0 transition-all duration-300 overflow-y-auto pt-10 pb-10">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 transform scale-95 transition-all duration-300 mt-10 mb-auto">
            
            <div id="payment-loading" class="p-12 text-center w-full flex flex-col items-center justify-center min-h-[50vh]">
                <div class="w-10 h-10 border-4 border-slate-200 border-t-cyan-500 rounded-full animate-spin mx-auto mb-4"></div>
                <p class="text-sm text-slate-400 font-medium">Memuat detail...</p>
            </div>

            <div id="payment-body" class="p-6 hidden">
                <div class="flex flex-col gap-1 mb-6 border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-cyan-100 rounded-xl">
                            <i data-lucide="image" class="w-5 h-5 text-cyan-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900" id="payment-modal-title">Upload Bukti Transfer/Cash</h3>
                            <p class="text-xs text-slate-500">Nota: <strong id="payment-modal-invoice"></strong></p>
                        </div>
                    </div>
                </div>

                {{-- INSERT DETAILS HERE --}}
                <div id="p-detail-container" class="mb-6 space-y-4 border-b border-slate-100 pb-6 hidden">
                    <div class="flex items-center gap-2 flex-wrap" id="p-badges"></div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="p-fields"></div>
                    
                    <div id="p-items-wrap" class="hidden">
                         <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Daftar Barang</label>
                         {{-- Table Container untuk Rembush --}}
                         <div id="p-items-table-container" class="bg-slate-50 border border-slate-200 rounded-xl overflow-hidden hidden">
                             <table class="w-full text-xs text-left">
                                 <thead class="bg-slate-100/50 text-slate-500 font-bold uppercase tracking-wider">
                                     <tr>
                                         <th class="px-3 py-2">Nama Barang</th>
                                         <th class="px-3 py-2 text-center">Qty</th>
                                         <th class="px-3 py-2">Satuan</th>
                                         <th class="px-3 py-2 text-right">Harga Sat.</th>
                                         <th class="px-3 py-2 text-right">Total</th>
                                     </tr>
                                 </thead>
                                 <tbody id="p-items-tbody" class="divide-y divide-slate-100"></tbody>
                             </table>
                         </div>
                         {{-- Div Container untuk Pengajuan (Cards Grid) --}}
                         <div id="p-items-div-container" class="hidden flex-col"></div>
                    </div>

                    <div id="p-branches-wrap" class="hidden">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider mt-4">Pembagian Cabang</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="p-branches"></div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 mb-4 flex flex-col justify-center shadow-sm">
                    <label class="block text-[10px] font-bold text-blue-800/60 uppercase tracking-wider mb-1">Tagihan Pembayaran</label>
                    <p class="text-lg md:text-xl font-black text-blue-700 tracking-tight flex items-baseline" id="payment-modal-amount"></p>
                </div>

                <form id="payment-form" method="POST" action="" enctype="multipart/form-data">
                    @csrf
                    
                    {{-- DYNAMIC FILE INPUT --}}
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-600 mb-2" id="payment-modal-label">
                            Unggah Foto / Screenshot <span class="text-red-500">*</span>
                        </label>
                        <input type="file" name="file" id="payment_file_input" required accept=".jpg,.jpeg,.png,.pdf"
                            class="w-full border border-cyan-200 rounded-xl p-2 text-sm outline-none focus:ring-2 focus:ring-cyan-100 focus:border-cyan-300 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100 transition-all cursor-pointer bg-white">
                        <p class="mt-1 text-[11px] text-slate-400 font-medium" id="payment-modal-help">Format: JPG, PNG, PDF. Max 2MB.</p>
                    </div>

                    {{-- TRANSFER FIELDS (Hidden by default) --}}
                    <div id="transfer-fields" class="hidden space-y-4 mb-5 border-t border-slate-100 pt-4">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1 uppercase tracking-wider">Metode Transfer</label>
                            <div id="transfer-method-badge" class="inline-block px-2 py-1 bg-slate-100 text-slate-600 text-[10px] font-bold rounded">TRANSFER</div>
                        </div>

                        {{-- Bank Account Selection --}}
                        <div id="saved-accounts-container" class="hidden">
                            <label class="block text-xs font-bold text-indigo-600 mb-1.5">Pilih Rekening Tersimpan</label>
                            <select id="saved_bank_account" onchange="autoFillBankAccount(this)" 
                                class="w-full border-2 border-indigo-100 rounded-xl p-2.5 text-sm font-bold text-slate-700 outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-300 transition-all bg-indigo-50/30">
                                <option value="">-- Pilih Rekening --</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Bank Tujuan <span class="text-red-500">*</span></label>
                            <input type="text" name="rekening_bank" id="transfer_bank" placeholder="Contoh: BCA / Mandiri / GoPay" required
                                class="w-full border border-slate-200 rounded-lg p-2.5 text-sm outline-none focus:ring-2 focus:ring-cyan-100 focus:border-cyan-300">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5">Nomor Rekening <span class="text-red-500">*</span></label>
                                <input type="text" name="rekening_nomor" id="transfer_nomor" placeholder="Contoh: 0987654321" required
                                    inputmode="numeric"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                    class="w-full border border-slate-200 rounded-lg p-2.5 text-sm outline-none focus:ring-2 focus:ring-cyan-100 focus:border-cyan-300">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5">Atas Nama <span class="text-red-500">*</span></label>
                                <input type="text" name="rekening_nama" id="transfer_nama" placeholder="Contoh: Nama Pemilik" required
                                    class="w-full border border-slate-200 rounded-lg p-2.5 text-sm outline-none focus:ring-2 focus:ring-cyan-100 focus:border-cyan-300">
                            </div>
                        </div>
                        <div id="transfer-profile-alert" class="hidden text-[10px] text-emerald-600 bg-emerald-50 border border-emerald-100 p-2 rounded-lg flex items-start gap-1.5 mt-2">
                            <i data-lucide="info" class="w-3.5 h-3.5 flex-shrink-0 mt-0.5"></i>
                            <span>Rekening ini akan disimpan ke dalam Profil Teknisi untuk transaksi berikutnya.</span>
                        </div>
                    </div>

                    {{-- CASH FIELDS (Hidden by default) --}}
                    <div id="cash-fields" class="hidden space-y-4 mb-5 border-t border-slate-100 pt-4">
                        <div class="p-3 bg-amber-50 border border-amber-100 rounded-lg flex items-start gap-2">
                            <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600 mt-0.5"></i>
                            <div class="text-[11px] text-amber-800 font-medium">
                                Pastikan foto yang diunggah menampilkan wajah <strong>Teknisi</strong> dan <strong>Uang Tunai</strong> secara jelas sebagai bukti penyerahan.
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Catatan Tambahan (Opsional)</label>
                            <textarea name="catatan" id="cash_catatan" rows="2" placeholder="Cth: Uang diserahkan ke teknisi A..."
                                class="w-full border border-slate-200 rounded-lg p-2 text-sm outline-none focus:ring-2 focus:ring-cyan-100 focus:border-cyan-300 resize-none"></textarea>
                        </div>
                    </div>

                    {{-- PENGAJUAN INVOICE FIELDS (Hidden by default) --}}
                    <div id="pengajuan-invoice-fields" class="hidden space-y-4 mb-5 border-t border-slate-100 pt-4">
                        <div class="p-3 bg-teal-50 border border-teal-100 rounded-lg flex items-start gap-2 mb-4">
                            <i data-lucide="info" class="w-4 h-4 text-teal-600 mt-0.5"></i>
                            <div class="text-[11px] text-teal-800 font-medium">
                                Pilih cabang <strong>Sumber Dana</strong> dan masukkan nominal yang dibayarkan. Cabang yang tidak dipilih otomatis <strong class="text-red-600">berhutang</strong>.
                            </div>
                        </div>

                        {{-- Multi Sumber Dana Section --}}
                        <div class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-sm">
                            <div class="px-5 py-4 border-b border-slate-50">
                                <label class="text-xs font-black text-slate-800 uppercase tracking-widest">Rincian Sumber Dana <span class="text-red-500">*</span></label>
                            </div>
                            <div id="p_sumber_dana_container" class="p-5 space-y-4">
                                {{-- Dynamically populated by JS --}}
                            </div>

                            <div id="p_sumber_dana_total" class="px-6 py-5 bg-slate-50/50 border-t border-slate-100 flex justify-between items-center hidden">
                                <div class="space-y-1">
                                    <span class="block text-xs font-black text-slate-800 uppercase tracking-widest leading-none">Total Sumber Dana</span>
                                    <div id="p_sumber_dana_diff" class="text-[10px] font-bold tracking-tight"></div>
                                </div>
                                <span id="p_sumber_dana_total_value" class="text-2xl font-black text-teal-600 tracking-tighter">Rp 0</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5">Ongkir</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                    <input type="text" name="ongkir" id="p_ongkir" placeholder="0"
                                        class="nominal-input w-full pl-8 pr-3 py-2.5 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-300">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5">Diskon Pengiriman</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                    <input type="text" name="diskon_pengiriman" id="p_diskon_pengiriman" placeholder="0"
                                        class="nominal-input w-full pl-8 pr-3 py-2.5 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-300">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5">Voucher Diskon</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                    <input type="text" name="voucher_diskon" id="p_voucher_diskon" placeholder="0"
                                        class="nominal-input w-full pl-8 pr-3 py-2.5 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-300">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5">Biaya Layanan 1</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                    <input type="text" name="biaya_layanan_1" id="p_biaya_layanan_1" placeholder="0"
                                        class="nominal-input w-full pl-8 pr-3 py-2.5 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-300">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5">Biaya Layanan 2</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                    <input type="text" name="biaya_layanan_2" id="p_biaya_layanan_2" placeholder="0"
                                        class="nominal-input w-full pl-8 pr-3 py-2.5 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-300">
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Catatan (Opsional)</label>
                            <textarea name="catatan" id="p_catatan" rows="2" placeholder="Cth: Pembayaran via Invoice..."
                                class="w-full border border-slate-200 rounded-lg p-2 text-sm outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-300 resize-none"></textarea>
                        </div>

                        {{-- Debt Preview --}}
                        <div id="p_debt_preview" class="hidden border border-red-100 rounded-2xl overflow-hidden mt-6 shadow-sm">
                            <div class="bg-red-50/50 px-4 py-3.5 border-b border-red-100 flex items-center gap-2.5">
                                <div class="w-6 h-6 rounded-full bg-white flex items-center justify-center shadow-sm">
                                    <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-red-600"></i>
                                </div>
                                <span class="text-[11px] font-black text-red-700 uppercase tracking-widest">Preview Hutang Otomatis</span>
                            </div>
                            <div id="p_debt_preview_list" class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4 bg-white/50"></div>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button type="button" onclick="closePaymentModal()"
                            class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-600 font-bold text-xs uppercase tracking-wider hover:bg-slate-200 transition-all border border-slate-200">
                            Batal
                        </button>
                        <button type="submit" id="btnSubmitPayment"
                            class="flex-1 py-3 rounded-xl bg-cyan-600 text-white font-bold text-xs uppercase tracking-wider hover:bg-cyan-700 transition-all shadow-lg shadow-cyan-600/20 relative">
                            <span id="btnSubmitPaymentText">Upload & Simpan</span>
                            <i data-lucide="loader-2" class="w-4 h-4 animate-spin absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 hidden" id="btnSubmitPaymentLoader"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush

@push('modals')
    {{-- Toast Container --}}
    <div id="toast-container" class="fixed top-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"></div>
@endpush

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

        /* ── Branch Tag Truncation Tooltip ── */
        .branch-more-wrap {
            position: relative;
            display: inline-flex;
        }
        .branch-more-wrap .branch-tooltip {
            visibility: hidden;
            opacity: 0;
            position: absolute;
            bottom: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%);
            background: #1e293b;
            color: #f8fafc;
            font-size: 10px;
            font-weight: 600;
            line-height: 1.4;
            padding: 8px 12px;
            border-radius: 10px;
            white-space: nowrap;
            z-index: 50;
            pointer-events: none;
            transition: opacity 0.2s ease, visibility 0.2s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .branch-more-wrap .branch-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: #1e293b;
        }
        .branch-more-wrap:hover .branch-tooltip {
            visibility: visible;
            opacity: 1;
        }
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
    // IMAGE VIEWER MODAL
    // ═══════════════════════════════════════════════════════════════
    
    const imageViewer  = document.getElementById('image-viewer');
    const viewerImage  = document.getElementById('viewer-image');
    const closeViewer  = document.getElementById('close-viewer');
    let lastFocusedElement = null; // Store element that triggered viewer

    function openImageViewer(src) {
        // Store currently focused element to return focus later
        lastFocusedElement = document.activeElement;
        
        // Set image source
        viewerImage.src = src;
        
        // Show modal FIRST (remove hidden class)
        imageViewer.classList.remove('hidden');
        imageViewer.classList.add('flex');
        
        // Set aria-hidden AFTER showing (important for timing)
        requestAnimationFrame(() => {
            imageViewer.setAttribute('aria-hidden', 'false');
            if (window.toggleBodyScroll) window.toggleBodyScroll(true);
            else document.body.style.overflow = 'hidden';
            
            // Reinit icons for close button
            if (typeof lucide !== 'undefined') {
                lucide.createIcons({ root: imageViewer });
            }
            
            // Focus close button AFTER everything is ready
            setTimeout(() => {
                if (closeViewer) closeViewer.focus();
            }, 50);
        });
    }

    function closeImageViewer() {
        // Remove focus from any element inside modal FIRST
        if (document.activeElement && imageViewer.contains(document.activeElement)) {
            document.activeElement.blur();
        }
        
        // Hide modal
        imageViewer.classList.add('hidden');
        imageViewer.classList.remove('flex');
        if (window.toggleBodyScroll) window.toggleBodyScroll(false);
        else document.body.style.overflow = '';
        
        // Set aria-hidden AFTER hiding
        imageViewer.setAttribute('aria-hidden', 'true');
        
        // Clear image and return focus
        setTimeout(() => { 
            viewerImage.src = '';
            
            // Return focus to element that opened the viewer
            if (lastFocusedElement && lastFocusedElement.focus) {
                lastFocusedElement.focus();
            }
        }, 200);
    }

    // Tombol X → tutup
    if (closeViewer) {
        closeViewer.addEventListener('click', function (e) {
            e.stopPropagation();
            closeImageViewer();
        });
    }

    // Klik backdrop (di luar viewer-card) → tutup
    if (imageViewer) {
        imageViewer.addEventListener('click', function (e) {
            if (e.target === imageViewer) closeImageViewer();
        });
    }

    // ESC → tutup
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !imageViewer.classList.contains('hidden')) {
            closeImageViewer();
        }
    });

    // ═══════════════════════════════════════════════════════════════
    // INSTANT SEARCH ENGINE - CLIENT SIDE
    // ═══════════════════════════════════════════════════════════════
    
    const SearchEngine = (function() {
        let allTransactions = [];
        let filteredTransactions = [];
        let currentPage = 1;
        const itemsPerPage = 20;
        let isLoading = false;
        let isFirstLoad = true; // Track first load for skeleton

        // ✅ CHANGE: Make loadData return Promise
        async function loadData() {
        if (isLoading) {
            console.warn('[SearchEngine] Already loading, skipping...');
            return Promise.resolve();
        }
        
        isLoading = true;
        if(typeof NProgress !== 'undefined') NProgress.start();
        
        if (isFirstLoad) {
            renderSkeletons();
        }
        
        try {
            const url = new URL(window.location.href);
            const params = new URLSearchParams(url.search);
            
            // Add date range to params
            const startDateEl = document.getElementById('filter-start-date');
            const endDateEl = document.getElementById('filter-end-date');
            if (startDateEl?.value) params.set('start_date', startDateEl.value);
            if (endDateEl?.value) params.set('end_date', endDateEl.value);

            // Add branch and category to params
            const branchIdEl = document.getElementById('filter-branch-id');
            const categoryEl = document.getElementById('filter-category');
            if (branchIdEl?.value && branchIdEl.value !== 'all') params.set('branch_id', branchIdEl.value);
            if (categoryEl?.value && categoryEl.value !== 'all') params.set('category', categoryEl.value);

            updateActiveFilters();

            console.log('[SearchEngine] Fetching data from:', '/transactions/search-data?' + params.toString());
            
            const response = await fetch('/transactions/search-data?' + params.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            allTransactions = await response.json();
            
            // Re-apply search filter if there is active query
            const currentQuery = document.getElementById('instant-search').value.trim();
            if (currentQuery) {
                const searchTerm = currentQuery.toLowerCase();
                const terms = searchTerm.split(/\s+/);
                filteredTransactions = allTransactions.filter(t => 
                    terms.every(term => t.search_text.includes(term))
                );
            } else {
                filteredTransactions = [...allTransactions];
            }
            
            console.log('[SearchEngine] Data loaded:', {
                total: allTransactions.length,
                filtered: filteredTransactions.length,
                stayingOnPage: currentPage
            });
            
            // Adjust currentPage if out of bounds after refresh
            const totalPages = Math.ceil(filteredTransactions.length / itemsPerPage);
            if (currentPage > totalPages && totalPages > 0) {
                currentPage = totalPages;
            }
            
            renderPage();
            updateStats();
            
            return Promise.resolve();
        } catch (error) {
            console.error('[SearchEngine] Failed to load:', error);
            showToast('Gagal memuat data', 'error');
            renderPage(); // Clear skeletons
            return Promise.reject(error);
        } finally {
            isLoading = false;
            isFirstLoad = false;
            if(typeof NProgress !== 'undefined') NProgress.done();
        }
    }

        function renderSkeletons() {
            const tbody = document.getElementById('desktop-tbody');
            const container = document.getElementById('mobile-container');
            document.getElementById('table-no-results').classList.add('hidden');
            document.getElementById('mobile-no-results').classList.add('hidden');
            
            tbody.innerHTML = Array(6).fill(`
                <tr class="animate-pulse bg-white border-b border-gray-50">
                    <td class="px-4 py-4"><div class="h-4 bg-slate-200 rounded w-6 mx-auto"></div></td>
                    <td class="px-5 py-4"><div class="flex gap-3 items-center"><div class="w-8 h-8 rounded-full bg-slate-200 shrink-0"></div><div><div class="h-4 bg-slate-200 rounded w-28 mb-1.5"></div><div class="h-3 bg-slate-100 rounded w-16"></div></div></div></td>
                    <td class="px-5 py-4"><div class="h-6 bg-slate-100 rounded-lg w-20 border border-slate-200"></div></td>
                    <td class="px-5 py-4"><div class="h-4 bg-slate-200 rounded w-24"></div></td>
                    <td class="px-5 py-4"><div class="h-6 bg-slate-100 rounded-full w-24 border border-slate-200"></div></td>
                    <td class="px-5 py-4"><div class="h-4 bg-slate-200 rounded w-20"></div></td>
                    <td class="px-5 py-4"><div class="h-5 bg-slate-200 rounded w-24"></div></td>
                    <td class="px-5 py-4"><div class="flex justify-center gap-1"><div class="w-8 h-8 rounded-lg bg-slate-200 shrink-0"></div><div class="w-8 h-8 rounded-lg bg-slate-200 shrink-0"></div></div></td>
                </tr>
            `).join('');

            container.innerHTML = Array(4).fill(`
                <div class="p-3 sm:p-4 animate-pulse bg-white border-b border-gray-100">
                    <div class="flex justify-between items-start gap-2 mb-2"><div class="flex items-center gap-2.5"><div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-slate-200 shrink-0"></div><div><div class="h-3.5 bg-slate-200 rounded w-20 sm:w-24 mb-1.5"></div><div class="h-2.5 bg-slate-100 rounded w-14 sm:w-16"></div></div></div><div class="h-5 bg-slate-100 rounded-md w-14 sm:w-16 border border-slate-200"></div></div>
                    <div class="ml-[42px] sm:ml-[48px]"><div class="h-2.5 bg-slate-100 rounded w-40 sm:w-48 mb-2"></div>
                    <div class="h-5 bg-slate-200 rounded w-28 sm:w-32 mb-2"></div>
                    <div class="flex gap-1.5"><div class="h-7 sm:h-8 bg-slate-100 rounded-lg w-16 sm:w-20 border border-slate-200"></div><div class="h-7 sm:h-8 bg-slate-100 rounded-lg w-14 sm:w-16 border border-slate-200"></div></div></div>
                </div>
            `).join('');
        }

        // Instant search algorithm (multi-field matching)
        function search(query, resetPage = true) {
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
            
            if (resetPage) {
                currentPage = 1; // Reset to first page
            }
            renderPage();
            updateStats();
        }

        // Render current page
        function renderPage() {
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const pageData = filteredTransactions.slice(startIndex, endIndex);
            
            renderDesktopTable(pageData, startIndex);
            renderMobileCards(pageData, startIndex);
            renderPagination();
            updateShowingText(startIndex, endIndex);
            
            // Re-init Lucide icons
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function renderDesktopTable(data, startIndex = 0) {
            const tbody = document.getElementById('desktop-tbody');
            const noResults = document.getElementById('table-no-results');
            
            if (data.length === 0) {
                tbody.innerHTML = '';
                noResults.classList.remove('hidden');
                const query = document.getElementById('instant-search').value;
                document.getElementById('no-result-query').textContent = query;
            } else {
                noResults.classList.add('hidden');
                tbody.innerHTML = data.map((t, i) => generateDesktopRow(t, startIndex + i + 1)).join('');
            }
        }

        function renderMobileCards(data, startIndex = 0) {
            const container = document.getElementById('mobile-container');
            const noResults = document.getElementById('mobile-no-results');
            
            if (data.length === 0) {
                container.innerHTML = '';
                noResults.classList.remove('hidden');
                const query = document.getElementById('instant-search').value;
                document.getElementById('mobile-no-result-query').textContent = query;
            } else {
                noResults.classList.add('hidden');
                container.innerHTML = data.map((t, i) => generateMobileCard(t, startIndex + i + 1)).join('');
            }
        }

        function renderPagination() {
            const container = document.getElementById('pagination-container');
            const totalPages = Math.ceil(filteredTransactions.length / itemsPerPage);
            
            if (totalPages <= 1) {
                container.innerHTML = '';
                return;
            }
            
            // Detect mobile viewport
            const isMobile = window.innerWidth < 640;
            let html = '';
            
            // Previous button
            html += `<button onclick="SearchEngine.goToPage(${currentPage - 1})" 
                        ${currentPage === 1 ? 'disabled' : ''} 
                        class="px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg border border-gray-200 text-xs sm:text-sm font-medium ${currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'}">
                        <span class="hidden sm:inline">Prev</span>
                        <i data-lucide="chevron-left" class="w-3.5 h-3.5 sm:hidden"></i>
                     </button>`;
            
            // Page numbers — fewer visible on mobile
            const maxVisible = isMobile ? 3 : 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
            let endPage = Math.min(totalPages, startPage + maxVisible - 1);
            
            if (endPage - startPage < maxVisible - 1) {
                startPage = Math.max(1, endPage - maxVisible + 1);
            }
            
            // Show first page + ellipsis if needed
            if (startPage > 1) {
                html += `<button onclick="SearchEngine.goToPage(1)" 
                            class="px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg border border-gray-200 text-xs sm:text-sm font-medium hover:bg-gray-50">
                            1
                         </button>`;
                if (startPage > 2) {
                    html += `<span class="text-xs text-gray-400 px-0.5">…</span>`;
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                html += `<button onclick="SearchEngine.goToPage(${i})" 
                            class="px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg border text-xs sm:text-sm font-medium ${i === currentPage ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-200 hover:bg-gray-50'}">
                            ${i}
                         </button>`;
            }
            
            // Show last page + ellipsis if needed
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    html += `<span class="text-xs text-gray-400 px-0.5">…</span>`;
                }
                html += `<button onclick="SearchEngine.goToPage(${totalPages})" 
                            class="px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg border border-gray-200 text-xs sm:text-sm font-medium hover:bg-gray-50">
                            ${totalPages}
                         </button>`;
            }
            
            // Next button
            html += `<button onclick="SearchEngine.goToPage(${currentPage + 1})" 
                        ${currentPage === totalPages ? 'disabled' : ''} 
                        class="px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg border border-gray-200 text-xs sm:text-sm font-medium ${currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'}">
                        <span class="hidden sm:inline">Next</span>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5 sm:hidden"></i>
                     </button>`;
            
            container.innerHTML = html;
            // Re-init icons for mobile arrow buttons
            if (typeof lucide !== 'undefined') lucide.createIcons({ root: container });
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

        // ═══════════════════════════════════════════════════════════════
        // Branch Tags Truncation — max 2 visible, rest in tooltip
        // ═══════════════════════════════════════════════════════════════
        function renderBranchTags(branches, maxVisible = 2) {
            if (!branches || branches.length === 0) return '';

            const icon = '<i data-lucide="git-branch" class="w-2.5 h-2.5 mr-0.5"></i>';

            const visibleTags = branches.slice(0, maxVisible).map(b =>
                `<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-600 border border-slate-200">${icon} ${b}</span>`
            ).join('');

            if (branches.length <= maxVisible) return visibleTags;

            const remaining = branches.length - maxVisible;
            const hiddenNames = branches.slice(maxVisible).join(', ');

            const tooltipStyle = 'visibility:hidden;opacity:0;position:absolute;bottom:calc(100% + 8px);left:50%;transform:translateX(-50%);background:#1e293b;color:#f8fafc;font-size:10px;font-weight:600;line-height:1.4;padding:8px 12px;border-radius:10px;white-space:nowrap;z-index:50;pointer-events:none;transition:opacity .2s ease,visibility .2s ease;box-shadow:0 4px 12px rgba(0,0,0,.15);';

            const moreBadge = `
                <span style="position:relative;display:inline-flex;"
                      onmouseenter="this.querySelector('.branch-tip').style.visibility='visible';this.querySelector('.branch-tip').style.opacity='1';"
                      onmouseleave="this.querySelector('.branch-tip').style.visibility='hidden';this.querySelector('.branch-tip').style.opacity='0';">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-50 text-blue-600 border border-blue-200 cursor-default">
                        +${remaining} lainnya
                    </span>
                    <span class="branch-tip" style="${tooltipStyle}">${hiddenNames}</span>
                </span>`;

            return visibleTags + moreBadge;
        }

        // Generate HTML for desktop row
        function generateDesktopRow(t, rowNum = '') {
            const statusBadge = {
                pending:   'bg-amber-50 text-amber-600 border-amber-200',
                approved:  'bg-blue-50 text-blue-600 border-blue-200',
                completed: 'bg-green-50 text-green-600 border-green-200',
                rejected:  'bg-red-50 text-red-600 border-red-200',
                waiting_payment: 'bg-cyan-50 text-cyan-700 border-cyan-200',
                flagged:   'bg-rose-50 text-rose-700 border-rose-200',
                'auto-reject': 'bg-pink-50 text-pink-700 border-pink-200',
                'Menunggu Konfirmasi Teknisi': 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200',
                'Sedang Diverifikasi AI': 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'Ditolak Teknisi': 'bg-red-50 text-red-700 border-red-200',
            };
            const statusLabel = {
                pending:   'Pending',
                approved:  'Menunggu Owner',
                completed: 'Selesai',
                rejected:  'Ditolak',
                waiting_payment: 'Menunggu Pembayaran',
                flagged:   'Flagged (Selisih)',
                'auto-reject': 'Auto Reject (AI)',
                'Menunggu Konfirmasi Teknisi': 'Menunggu Konfirmasi',
                'Sedang Diverifikasi AI': 'Proses AI',
                'Ditolak Teknisi': 'Ditolak Teknisi',
            };

            const aiBadgeHtml = generateAIBadge(t);
            const inlineActionsHtml = generateInlineActions(t);

            return `
                <tr class="hover:bg-blue-50/30 transition-all duration-200 group">
                    <td class="px-4 py-4 text-center">
                        <span class="text-xs font-bold text-slate-400">${rowNum}</span>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center text-xs font-bold text-slate-500 shrink-0">
                                ${(t.submitter_name ? t.submitter_name.charAt(0) : '?').toUpperCase()}
                            </div>
                            <div>
                                <div class="font-bold text-gray-900">${t.submitter_name || '-'}</div>
                                ${!t.submitter_has_telegram ? `
                                    <div class="flex items-center gap-1 mt-0.5">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-50 text-rose-600 border border-rose-100">
                                            <i data-lucide="bell-off" class="w-2.5 h-2.5 mr-0.5"></i> Telegram Belum Terdaftar
                                        </span>
                                    </div>
                                ` : ''}
                                <div class="text-[11px] text-gray-400 font-medium">${t.invoice_number}</div>
                                ${t.branches && t.branches.length > 0 ? `
                                    <div class="flex items-center gap-1 mt-1 flex-wrap">
                                        ${renderBranchTags(t.branches, 2)}
                                    </div>
                                ` : ''}
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
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border ${statusBadge[t.status] || 'bg-gray-50 text-gray-700 border-gray-200'}">
                                ${statusLabel[t.status] || t.status}
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
                        <div class="flex items-center justify-center gap-1 opacity-80 group-hover:opacity-100 transition-opacity">
                            <button type="button" onclick="openViewModal(${t.id})" title="Lihat Detail"
                                class="p-2 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 active:scale-95 transition-all outline-none">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            ${canManage ? `
                                ${buildEditButton(t, 'desktop')}
                                ${(!isAdmin) ? `
                                    <form action="/transactions/${t.id}" method="POST" onsubmit="return confirm('Apakah anda yakin ingin menghapus transaksi ${t.invoice_number}?')">
                                        <input type="hidden" name="_token" value="${csrfToken}">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" title="Hapus"
                                            class="p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 active:scale-95 transition-all outline-none">
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
        
        // ───────────────────────────────────────────────────
        // ✅ Edit button builder — enforces spec rules:
        //   - Pengajuan + status completed → sembunyikan
        //   - Admin → tampilkan dengan label "Lihat Versi" (read-only di server)
        // ───────────────────────────────────────────────────
        function buildEditButton(t, style = 'desktop') {
            const isCompleted = t.status === 'completed';
            
            // Tentukan label berdasar status Read-Only
            let label = 'Edit';
            if (t.type === 'pengajuan') {
                if (isAdmin) label = 'Lihat Versi (View Only)';
                else if (isCompleted) label = 'View Only';
            }

            // Selalu gunakan icon edit (pencil), namun fungsionalnya read-only di backend jika memenuhi syarat
            const icon  = 'pencil';

            if (style === 'desktop') {
                return `<a href="/transactions/${t.id}/edit" title="${label}"
                    class="p-2 rounded-lg text-slate-400 hover:text-amber-600 hover:bg-amber-50 active:scale-95 transition-all outline-none">
                    <i data-lucide="${icon}" class="w-4 h-4"></i>
                </a>`;
            } else {
                return `<a href="/transactions/${t.id}/edit"
                    class="flex items-center gap-1 px-2.5 py-1.5 bg-white border border-slate-200 text-slate-500 rounded-lg hover:text-amber-600 hover:border-amber-300 active:scale-95 transition-all text-[11px] font-semibold outline-none">
                    <i data-lucide="${icon}" class="w-3 h-3"></i> ${label}
                </a>`;
            }
        }

        function generateMobileCard(t, rowNum = '') {
            const mStatusBadge = {
                pending:   'bg-amber-50 text-amber-700 border-amber-200',
                approved:  'bg-blue-50 text-blue-700 border-blue-200',
                completed: 'bg-green-50 text-green-700 border-green-200',
                rejected:  'bg-red-50 text-red-700 border-red-200',
                waiting_payment: 'bg-cyan-50 text-cyan-700 border-cyan-200',
                flagged:   'bg-rose-50 text-rose-700 border-rose-200',
                'auto-reject': 'bg-pink-50 text-pink-700 border-pink-200',
                'Menunggu Konfirmasi Teknisi': 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200',
                'Sedang Diverifikasi AI': 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'Ditolak Teknisi': 'bg-red-50 text-red-700 border-red-200',
            };
            const mStatusLabel = {
                pending:   'Pending',
                approved:  'Menunggu Owner',
                completed: 'Selesai',
                rejected:  'Ditolak',
                waiting_payment: 'Menunggu Bayar',
                flagged:   'Flagged',
                'auto-reject': 'Auto Reject',
                'Menunggu Konfirmasi Teknisi': 'Konfirmasi',
                'Sedang Diverifikasi AI': 'Verifikasi AI',
                'Ditolak Teknisi': 'Ditolak',
            };

            const aiBadgeHtml = generateAIBadge(t);
            const mobileActionsHtml = generateMobileActions(t);

            // CRUD buttons — compact, same height as contextual actions
            const crudButtons = `
                <button type="button" onclick="openViewModal(${t.id})"
                    class="flex items-center gap-1 px-2.5 py-1.5 bg-white border border-slate-200 text-slate-500 rounded-lg hover:text-blue-600 hover:border-blue-300 active:scale-95 transition-all text-[11px] font-semibold outline-none">
                    <i data-lucide="eye" class="w-3 h-3"></i> Lihat
                </button>
                ${canManage ? `
                    ${buildEditButton(t, 'mobile')}
                    ${(!isAdmin) ? `
                        <form action="/transactions/${t.id}" method="POST" class="inline" onsubmit="return confirm('Hapus transaksi ${t.invoice_number}?')">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit"
                                class="flex items-center gap-1 px-2 py-1.5 bg-white border border-slate-200 text-slate-400 rounded-lg hover:text-red-500 hover:border-red-300 active:scale-95 transition-all text-[11px] font-semibold outline-none">
                                <i data-lucide="trash-2" class="w-3 h-3"></i>
                            </button>
                        </form>
                    ` : ''}
                ` : ''}
            `;

            return `
                <div class="tx-card px-3 sm:px-4 py-3 sm:py-3.5 border-b border-gray-100">

                    {{-- Row 1: Avatar · Name/Invoice · Badges --}}
                    <div class="flex items-start gap-2.5 mb-2">
                        <div class="relative shrink-0 mt-0.5">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-slate-200 to-slate-300 flex items-center justify-center text-xs font-bold text-slate-600">
                                ${(t.submitter_name ? t.submitter_name.charAt(0) : '?').toUpperCase()}
                            </div>
                            ${rowNum ? `<span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 flex items-center justify-center text-[7px] font-bold bg-slate-500 text-white rounded-full">${rowNum}</span>` : ''}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <h5 class="font-bold text-slate-800 text-[13px] leading-snug truncate">${t.submitter_name || '-'}</h5>
                                    <p class="text-[10px] font-medium text-slate-400 truncate">${t.invoice_number}</p>
                                </div>
                                <div class="flex flex-col items-end gap-0.5 shrink-0">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold tracking-wide border ${mStatusBadge[t.status] || 'bg-gray-50 text-gray-700 border-gray-200'}">
                                        ${mStatusLabel[t.status] || t.status}
                                    </span>
                                    ${aiBadgeHtml}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Row 2: Branch & Telegram badges --}}
                    ${((t.branches && t.branches.length > 0) || !t.submitter_has_telegram) ? `
                    <div class="flex items-center gap-1 flex-wrap mb-2 pl-10 sm:pl-11">
                        ${!t.submitter_has_telegram ? `<span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[8px] font-bold bg-rose-50 text-rose-600 border border-rose-100"><i data-lucide="bell-off" class="w-2 h-2"></i> No Telegram</span>` : ''}
                        ${t.branches && t.branches.length > 0 ? renderBranchTags(t.branches, 2) : ''}
                    </div>
                    ` : ''}

                    {{-- Row 3: Meta info (Type · Category · Date) --}}
                    <div class="flex items-center gap-1.5 flex-wrap text-[10px] text-slate-400 mb-2 pl-10 sm:pl-11">
                        ${t.type === 'pengajuan'
                            ? '<span class="inline-flex items-center gap-0.5 text-[9px] font-bold text-teal-600"><i data-lucide="shopping-bag" class="w-2 h-2"></i> Pengajuan</span>'
                            : '<span class="inline-flex items-center gap-0.5 text-[9px] font-bold text-indigo-600"><i data-lucide="receipt" class="w-2 h-2"></i> Rembush</span>'}
                        <span class="text-slate-300">/</span>
                        <span class="font-medium truncate">${t.category_label}</span>
                        <span class="text-slate-300">/</span>
                        <span class="font-medium">${t.created_at}</span>
                    </div>

                    {{-- Row 4: Nominal & Primary Actions --}}
                    <div class="flex items-center justify-between gap-2 pl-10 sm:pl-11 mb-2.5">
                        <p class="font-black text-slate-800 text-[15px] sm:text-base tracking-tight truncate">Rp ${t.formatted_amount}</p>
                        ${mobileActionsHtml ? `
                        <div class="flex items-center gap-1.5 shrink-0">
                            ${mobileActionsHtml}
                        </div>
                        ` : ''}
                    </div>

                    ${t.status === 'rejected' && t.rejection_reason ? `
                        <div class="mb-2.5 text-[10px] text-red-600 flex items-start gap-1.5 bg-red-50 px-2.5 py-2 rounded-lg border border-red-100 mx-0">
                            <i data-lucide="alert-circle" class="w-3 h-3 mt-px flex-shrink-0 text-red-400"></i>
                            <span class="line-clamp-2">${t.rejection_reason}</span>
                        </div>
                    ` : ''}

                    {{-- Row 5: Secondary Actions --}}
                    ${crudButtons.trim() !== '' ? `
                    <div class="flex items-center gap-1.5 flex-wrap pl-10 sm:pl-11">
                        ${crudButtons}
                    </div>
                    ` : ''}

                </div>
            `;
        }


        function generateAIBadge(t) {
            if (t.type !== 'rembush' || !['queued', 'pending', 'processing', 'completed', 'error'].includes(t.ai_status)) {
                return '';
            }

            const isHighConfidence = t.confidence && t.confidence > 70;
            const completedColor = isHighConfidence 
                ? 'bg-green-50 text-green-600 border-green-200' 
                : 'bg-orange-50 text-orange-600 border-orange-200';
            const completedLabel = isHighConfidence ? 'AI ✓ High' : (t.confidence ? 'AI ✓ Low' : 'AI ✓');

            const aiBadge = {
                queued:     { color: 'bg-gray-50 text-gray-600 border-gray-200', icon: 'clock', label: 'Antrian', pulse: false, title: 'Menunggu diproses' },
                pending:    { color: 'bg-gray-50 text-gray-600 border-gray-200', icon: 'clock', label: 'Pending', pulse: false, title: 'Menunggu upload selesai' },
                processing: { color: 'bg-purple-50 text-purple-600 border-purple-200', icon: 'loader-2', label: 'OCR...', pulse: true, title: 'Sedang memproses...' },
                completed:  { color: completedColor, icon: 'check-circle', label: completedLabel, pulse: false, title: `Selesai • Confidence: ${t.confidence ?? 0}%` },
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
            const isPengajuan = t.type === 'pengajuan';
            const canApprovePengajuan = isOwner || userRole === 'atasan';

            if (t.status === 'pending') {
                // For Pengajuan, only Atasan/Owner can approve
                if (isPengajuan && !canApprovePengajuan) {
                    return '';
                }

                const approveTitle = (!isPengajuan && t.effective_amount >= 1000000) ? 'Setujui (Menunggu Owner)' : 'Setujui';
                html = `
                    <div class="flex items-center gap-1 ml-1">
                        <button type="button" onclick="performStatusAction(${t.id}, 'approved', this)" title="${approveTitle}"
                            class="p-1.5 rounded-lg bg-green-50 text-green-600 hover:bg-green-600 hover:text-white border border-green-200 hover:border-green-600 active:scale-90 transition-all outline-none">
                            <i data-lucide="check" class="w-3 h-3"></i>
                        </button>
                        <button type="button" onclick="openRejectModal(${t.id}, '${t.invoice_number}')" title="Tolak"
                            class="p-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white border border-red-200 hover:border-red-600 active:scale-90 transition-all outline-none">
                            <i data-lucide="x" class="w-3 h-3"></i>
                        </button>
                    </div>
                `;
            } else if (isOwner && t.status === 'approved' && !isPengajuan) {
                const approveTitle = 'Approve Final';
                html = `
                    <div class="flex items-center gap-1 ml-1">
                        <button type="button" onclick="performStatusAction(${t.id}, 'approved', this)" title="${approveTitle}"
                            class="p-1.5 rounded-lg bg-green-50 text-green-600 hover:bg-green-600 hover:text-white border border-green-200 hover:border-green-600 active:scale-90 transition-all outline-none">
                            <i data-lucide="check" class="w-3 h-3"></i>
                        </button>
                        <button type="button" onclick="openRejectModal(${t.id}, '${t.invoice_number}')" title="Tolak"
                            class="p-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white border border-red-200 hover:border-red-600 active:scale-90 transition-all outline-none">
                            <i data-lucide="x" class="w-3 h-3"></i>
                        </button>
                    </div>
                `;
            }

            // New Action Buttons
            if (t.status === 'auto-reject' && (canManage || isOwner)) {
                html += `
                    <div class="flex items-center gap-1 ml-1">
                        <button type="button" onclick="openOverrideModal(${t.id}, '${t.invoice_number}')" title="Request Override"
                            class="p-1.5 rounded-lg bg-orange-50 text-orange-600 hover:bg-orange-600 hover:text-white border border-orange-200 hover:border-orange-600 active:scale-90 transition-all outline-none">
                            <i data-lucide="alert-circle" class="w-3 h-3"></i>
                        </button>
                    </div>
                `;
            } else if (t.status === 'waiting_payment' && canManage) {
                html += `
                    <div class="flex items-center gap-1 ml-1">
                        <button type="button" onclick="openPaymentModal(${t.id})" title="Proses Pembayaran"
                            class="p-1.5 rounded-lg bg-cyan-50 text-cyan-600 hover:bg-cyan-600 hover:text-white border border-cyan-200 hover:border-cyan-600 active:scale-90 transition-all outline-none">
                            <i data-lucide="upload-cloud" class="w-3 h-3"></i>
                        </button>
                    </div>
                `;
            } else if (t.status === 'flagged' && (canManage || isOwner)) {
                html += `
                    <div class="flex items-center gap-1 ml-1">
                        <button type="button" onclick="openForceApproveModal(${t.id}, '${t.invoice_number}')" title="Force Approve"
                            class="p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white border border-rose-200 hover:border-rose-600 active:scale-90 transition-all outline-none">
                            <i data-lucide="shield-alert" class="w-3 h-3"></i>
                        </button>
                    </div>
                `;
            } else if (t.status === 'Menunggu Konfirmasi Teknisi' && userRole === 'teknisi') {
                html += `
                    <div class="flex items-center gap-1 ml-1">
                        <button type="button" onclick="confirmCashPayment(${t.id}, 'terima')" title="Terima Uang"
                            class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white border border-emerald-200 hover:border-emerald-600 active:scale-90 transition-all outline-none">
                            <i data-lucide="check-circle" class="w-3 h-3"></i>
                        </button>
                        <button type="button" onclick="confirmCashPayment(${t.id}, 'tolak')" title="Tolak / Terdapat Kendala"
                            class="p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white border border-rose-200 hover:border-rose-600 active:scale-90 transition-all outline-none">
                            <i data-lucide="x-circle" class="w-3 h-3"></i>
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
            const isPengajuan = t.type === 'pengajuan';
            const canApprovePengajuan = isOwner || userRole === 'atasan';

            if (t.status === 'pending') {
                if (isPengajuan) {
                    if (canApprovePengajuan) {
                        showActions = true;
                        approveTitle = 'Setujui';
                    }
                } else {
                    showActions = true;
                    approveTitle = t.effective_amount >= 1000000 ? 'Setujui (Menunggu Owner)' : 'Setujui';
                }
            } else if (isOwner && t.status === 'approved' && !isPengajuan) {
                showActions = true;
                approveTitle = 'Approve Final';
            }

            let extraActionHtml = '';
            if (t.status === 'auto-reject' && (canManage || isOwner)) {
                extraActionHtml = `
                    <button type="button" onclick="openOverrideModal(${t.id}, '${t.invoice_number}')" title="Override"
                        class="flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg bg-orange-50 text-orange-700 hover:bg-orange-600 hover:text-white font-bold text-[11px] active:scale-95 transition-all border border-orange-200 hover:border-orange-600 outline-none">
                        <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i> <span class="hidden sm:inline">Override</span>
                    </button>
                `;
            } else if (t.status === 'waiting_payment' && canManage) {
                extraActionHtml = `
                    <button type="button" onclick="openPaymentModal(${t.id})" title="Upload Bukti"
                        class="flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg bg-cyan-50 text-cyan-700 hover:bg-cyan-600 hover:text-white font-bold text-[11px] active:scale-95 transition-all border border-cyan-200 hover:border-cyan-600 outline-none">
                        <i data-lucide="upload-cloud" class="w-3.5 h-3.5"></i> <span class="hidden sm:inline">Upload Bukti</span>
                    </button>
                `;
            } else if (t.status === 'flagged' && (canManage || isOwner)) {
                extraActionHtml = `
                    <button type="button" onclick="openForceApproveModal(${t.id}, '${t.invoice_number}')" title="Force Approve"
                        class="flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-600 hover:text-white font-bold text-[11px] active:scale-95 transition-all border border-rose-200 hover:border-rose-600 outline-none">
                        <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i> <span class="hidden sm:inline">Force Approve</span>
                    </button>
                `;
            } else if (t.status === 'Menunggu Konfirmasi Teknisi' && userRole === 'teknisi') {
                extraActionHtml = `
                    <button type="button" onclick="confirmCashPayment(${t.id}, 'terima')" title="Terima"
                        class="flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white font-bold text-[11px] active:scale-95 transition-all border border-emerald-200 hover:border-emerald-600 outline-none">
                        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                    </button>
                    <button type="button" onclick="confirmCashPayment(${t.id}, 'tolak')" title="Tolak"
                        class="flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-600 hover:text-white font-bold text-[11px] active:scale-95 transition-all border border-rose-200 hover:border-rose-600 outline-none">
                        <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                    </button>
                `;
            }

            if (!showActions && !extraActionHtml) return '';

            if (extraActionHtml) {
                return extraActionHtml;
            }

            return `
                <button type="button" onclick="performStatusAction(${t.id}, 'approved', this)" title="${approveTitle}"
                    class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white font-semibold text-[11px] active:scale-95 transition-all border border-emerald-200 hover:border-emerald-600 outline-none">
                    <i data-lucide="check" class="w-3.5 h-3.5"></i> <span class="hidden sm:inline">${approveTitle}</span>
                </button>
                <button type="button" onclick="openRejectModal(${t.id}, '${t.invoice_number}')" title="Tolak"
                    class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-600 hover:text-white font-semibold text-[11px] active:scale-95 transition-all border border-rose-200 hover:border-rose-600 outline-none">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i> <span class="hidden sm:inline">Tolak</span>
                </button>
            `;
        }


        function addTransaction(transaction) {
            if (!allTransactions.some(t => t.id === transaction.id)) {
                allTransactions.unshift(transaction);
                const query = document.getElementById('instant-search').value.trim();
                search(query);
            }
        }

        function updateTransaction(transaction) {
            const index = allTransactions.findIndex(t => t.id === transaction.id);
            if (index !== -1) {
                allTransactions[index] = transaction;
                const query = document.getElementById('instant-search').value.trim();
                search(query, false); // Don't reset page
            } else {
                addTransaction(transaction);
            }
        }

        // Public API
        return {
                    init: loadData,
                    search: search,
                    goToPage: goToPage,
                    getAll: () => allTransactions,
                    getFiltered: () => filteredTransactions,
                    addTransaction: addTransaction,
                    updateTransaction: updateTransaction,
                };
    })();

    // ═══════════════════════════════════════════════════════════════
    // INITIALIZATION
    // ═══════════════════════════════════════════════════════════════
    
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // ✅ Initialize aria-hidden=true for all modals (important for accessibility)
        const viewModal = document.getElementById('view-modal');
        const imageViewer = document.getElementById('image-viewer');
        const rejectModal = document.getElementById('reject-modal');
        
        if (viewModal) viewModal.setAttribute('aria-hidden', 'true');
        if (imageViewer) imageViewer.setAttribute('aria-hidden', 'true');
        if (rejectModal) rejectModal.setAttribute('aria-hidden', 'true');

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

        // Setup Date Range Filters
        const startDateInput = document.getElementById('filter-start-date');
        const endDateInput   = document.getElementById('filter-end-date');
        
        [startDateInput, endDateInput].forEach(el => {
            if (el) {
                el.addEventListener('change', function() {
                    SearchEngine.init(); // Re-loads data from server with date filters
                });
            }
        });

        // Setup Branch & Category Filters
        const branchInput = document.getElementById('filter-branch-id');
        const categoryInput = document.getElementById('filter-category');
        
        [branchInput, categoryInput].forEach(el => {
            if (el) {
                el.addEventListener('change', function() {
                    SearchEngine.init(); 
                });
            }
        });

        // Clear All Filters
        document.getElementById('clear-all-filters')?.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            if (startDateInput) startDateInput.value = '';
            if (endDateInput)   endDateInput.value = '';
            if (branchInput)    branchInput.value = 'all';
            if (categoryInput)  categoryInput.value = 'all';
            
            SearchEngine.init();
        });

        // --- Visual Indicator Helpers ---
        function updateActiveFilters() {
            const bar = document.getElementById('active-filters-bar');
            const chips = document.getElementById('active-filters-chips');
            if (!bar || !chips) return;

            chips.innerHTML = '';
            let activeCount = 0;

            // Branch
            if (branchInput && branchInput.value !== 'all') {
                const name = branchInput.options[branchInput.selectedIndex].text;
                chips.appendChild(createFilterChip('Cabang: ' + name, () => {
                    branchInput.value = 'all';
                    SearchEngine.init();
                }));
                branchInput.classList.add('bg-blue-50', 'border-blue-200', 'text-blue-700');
                activeCount++;
            } else if (branchInput) {
                branchInput.classList.remove('bg-blue-50', 'border-blue-200', 'text-blue-700');
            }

            // Category
            if (categoryInput && categoryInput.value !== 'all') {
                const name = categoryInput.options[categoryInput.selectedIndex].text;
                chips.appendChild(createFilterChip('Kategori: ' + name, () => {
                    categoryInput.value = 'all';
                    SearchEngine.init();
                }));
                categoryInput.classList.add('bg-blue-50', 'border-blue-200', 'text-blue-700');
                activeCount++;
            } else if (categoryInput) {
                categoryInput.classList.remove('bg-blue-50', 'border-blue-200', 'text-blue-700');
            }

            // Dates
            if (startDateInput && startDateInput.value) {
                activeCount++;
                startDateInput.classList.add('bg-blue-50', 'border-blue-200', 'text-blue-700');
            } else if (startDateInput) {
                startDateInput.classList.remove('bg-blue-50', 'border-blue-200', 'text-blue-700');
            }
            if (endDateInput && endDateInput.value) {
                activeCount++;
                endDateInput.classList.add('bg-blue-50', 'border-blue-200', 'text-blue-700');
            } else if (endDateInput) {
                endDateInput.classList.remove('bg-blue-50', 'border-blue-200', 'text-blue-700');
            }

            bar.classList.toggle('hidden', activeCount === 0);
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function createFilterChip(label, onClear) {
            const chip = document.createElement('div');
            chip.className = 'flex items-center gap-1.5 px-2 py-1 bg-white border border-gray-200 rounded-lg shadow-sm text-[10px] font-bold text-gray-600';
            chip.innerHTML = `
                <span>${label}</span>
                <button type="button" class="hover:text-red-500 transition-colors">
                    <i data-lucide="x" class="w-2.5 h-2.5"></i>
                </button>
            `;
            chip.querySelector('button').onclick = onClear;
            return chip;
        }

        // Attach updateActiveFilters to window so SearchEngine can call it
        window.updateActiveFilters = updateActiveFilters;

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

        // ═══════════════════════════════════════════════════════════════
        // REAL-TIME UPDATES (Laravel Echo)
        // ═══════════════════════════════════════════════════════════════
        if (typeof window.Echo !== 'undefined') {
            @if(auth()->user()->role === 'teknisi')
                const echoChannel = window.Echo.private('transactions.{{ auth()->id() }}');
            @else
                const echoChannel = window.Echo.private('transactions');
            @endif

            echoChannel.listen('.transaction.updated', (e) => {
                console.log('🔔 [REALTIME] Transaction Updated:', e);
                
                // Refresh data via AJAX
                if (typeof SearchEngine !== 'undefined' && typeof SearchEngine.loadData === 'function') {
                    SearchEngine.loadData();
                    
                    // Optional: Show a subtle toast for the update
                    const tx = e.transaction || e;
                    const invoice = tx.invoice_number || 'Transaksi';
                    const status = tx.status_label || tx.status || 'diperbarui';
                    
                    showToast(`
                        <div class="flex items-start gap-2">
                            <i data-lucide="refresh-cw" class="w-4 h-4 mt-0.5 flex-shrink-0 text-blue-600 animate-spin"></i>
                            <div>
                                <strong>Pembaruan Otomatis</strong><br>
                                <span class="text-[11px] opacity-90">${invoice}: ${status}</span>
                            </div>
                        </div>
                    `, 'info');
                }
            });

            console.log('📡 [REALTIME] Echo listener initialized on channel: ' + 
                ( @if(auth()->user()->role === 'teknisi') 'transactions.{{ auth()->id() }}' @else 'transactions' @endif )
            );
        }

        // --- Initial Load ---
        SearchEngine.init();
    });

    // Toast function
    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        let bgColors = 'bg-white border text-slate-800';
        let accentClasses = 'bg-blue-500';
        if (type === 'success') {
            bgColors = 'bg-emerald-50 border-emerald-200 text-emerald-800';
            accentClasses = 'bg-emerald-500';
        } else if (type === 'error') {
            bgColors = 'bg-red-50 border-red-200 text-red-800';
            accentClasses = 'bg-red-500';
        }

        const toast = document.createElement('div');
        toast.className = `relative flex items-center gap-3 px-4 py-3 rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.05)] text-sm font-medium transform transition-all duration-300 translate-x-[120%] opacity-0 overflow-hidden ${bgColors}`;
        toast.innerHTML = `
            <div class="absolute left-0 top-0 bottom-0 w-1 ${accentClasses}"></div>
            ${message}
        `;

        container.appendChild(toast);

        requestAnimationFrame(() => {
            toast.classList.remove('translate-x-[120%]', 'opacity-0');
            toast.classList.add('translate-x-0', 'opacity-100');
        });
        
        if (typeof lucide !== 'undefined') {
            lucide.createIcons({ root: toast });
        }

        setTimeout(() => {
            toast.classList.remove('translate-x-0', 'opacity-100');
            toast.classList.add('translate-x-[120%]', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    // ═══════════════════════════════════════════════════════════════
    // VIEW MODAL & OTHER FUNCTIONS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Helper to render application items with highlighting
     */
    function renderTransactionItemsCards(items, version, originalItems = []) {
        const purchaseReasons = {
            'rusak': 'Barang Rusak/Usang',
            'hilang': 'Barang Hilang',
            'upgrade': 'Upgrade/Peningkatan',
            'kebutuhan_baru': 'Kebutuhan Baru',
            'proyek': 'Keperluan Proyek',
            'lainnya': 'Lainnya'
        };

        return items.map((item, idx) => {
            const price = Number(item.estimated_price || 0);
            const qty = Number(item.quantity || 0);
            const total = price * qty;

            const originalItem = (version === 'management') ? (originalItems[idx] || null) : null;
            const isNew = version === 'management' && originalItems.length > 0 && idx >= originalItems.length;

            // Helper to check if a field has changed
            const isFieldChanged = (fieldName) => {
                if (version !== 'management' || isNew || !originalItem) return false;
                if (fieldName === 'specs') {
                    return JSON.stringify(item.specs) !== JSON.stringify(originalItem.specs);
                }
                return item[fieldName] != originalItem[fieldName];
            };

            const hasAnyChange = version === 'management' && originalItem && JSON.stringify(item) !== JSON.stringify(originalItem);

            const cardClass = isNew 
                ? 'border-emerald-200 bg-emerald-50/30' 
                : (hasAnyChange ? 'border-orange-200 bg-orange-50/30' : 'border-slate-200 bg-white');

            const fieldClass = (f) => isFieldChanged(f) 
                ? 'bg-orange-100/50 border-orange-300 ring-1 ring-orange-200' 
                : 'bg-white border-slate-200';

            const specsHTML = item.specs ? Object.entries({merk: 'Merk', tipe: 'Tipe/Seri', ukuran: 'Ukuran', warna: 'Warna'}).map(([key, label]) => {
                return item.specs[key] ? `
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">${label}</label>
                        <div class="${fieldClass('specs')} border rounded-lg px-3 py-2 text-xs font-medium text-slate-800">${item.specs[key]}</div>
                    </div>
                ` : '';
            }).join('') : '';

            return `
                <div class="border ${cardClass} rounded-2xl overflow-hidden shadow-sm mb-4 last:mb-0 transition-all duration-300">
                    <!-- Card Header -->
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-100/50 transition-colors" onclick="const body = this.nextElementSibling; body.classList.toggle('hidden'); this.querySelector('.icon-collapse').classList.toggle('rotate-180')">
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-full ${isNew ? 'bg-emerald-100 text-emerald-600' : (hasAnyChange ? 'bg-orange-100 text-orange-600' : 'bg-slate-200 text-slate-500')} flex items-center justify-center font-bold text-xs transition-colors">${idx + 1}</div>
                            <div>
                                <h4 class="font-bold text-slate-700 text-sm flex items-center gap-2">
                                    ${item.customer || '-'}
                                    ${isNew ? '<span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700 text-[9px] uppercase tracking-tighter font-black animate-pulse">Baru</span>' : ''}
                                    ${hasAnyChange ? '<span class="px-1.5 py-0.5 rounded bg-orange-100 text-orange-700 text-[9px] uppercase tracking-tighter font-black">Diedit</span>' : ''}
                                </h4>
                                <p class="text-[10px] text-slate-400">Rp ${price.toLocaleString('id-ID')} x ${qty}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-emerald-600 text-sm hidden sm:inline mr-2">Rp ${total.toLocaleString('id-ID')}</span>
                            <i data-lucide="chevron-down" class="w-5 h-5 text-slate-400 transition-transform duration-200 icon-collapse ${idx !== 0 ? 'rotate-180' : ''}"></i>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-4 sm:p-5 space-y-5 ${idx !== 0 ? 'hidden' : ''}">
                        <div>
                            <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Informasi Barang / Jasa</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div class="md:col-span-2">
                                    <span class="block text-[10px] text-slate-400">Vendor</span>
                                    <div class="${fieldClass('vendor')} border rounded-lg px-3 py-2 text-xs font-medium text-slate-800">${item.vendor || '-'}</div>
                                </div>
                                <div class="md:col-span-2">
                                    <span class="block text-[10px] text-slate-400">Link Rekomendasi</span>
                                    <div class="${fieldClass('link')} border rounded-lg px-3 py-2 text-xs">
                                        ${item.link ? `<a href="${item.link}" target="_blank" class="font-medium text-blue-600 hover:underline break-all">${item.link}</a>` : `<span class="font-medium text-slate-800">-</span>`}
                                    </div>
                                </div>
                            </div>
                        </div>

                        ${specsHTML ? `
                        <div>
                            <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Spesifikasi Barang</label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">${specsHTML}</div>
                        </div>` : ''}

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Alasan Pembelian</label>
                                <div class="${fieldClass('category')} border rounded-lg px-3 py-2 text-xs font-medium text-slate-800">
                                    ${item.category || purchaseReasons[item.purchase_reason] || item.purchase_reason || '-'}
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Keterangan</label>
                                <div class="${fieldClass('description')} border rounded-lg px-3 py-2 text-xs font-medium text-slate-800 text-wrap whitespace-pre-wrap">${item.description || '-'}</div>
                            </div>
                        </div>

                        <!-- Price & Qty highlights -->
                        <div class="grid grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                             <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Harga Satuan</label>
                                <div class="${fieldClass('estimated_price')} border rounded-lg px-3 py-2 text-xs font-bold text-slate-800">Rp ${price.toLocaleString('id-ID')}</div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Jumlah</label>
                                <div class="${fieldClass('quantity')} border rounded-lg px-3 py-2 text-xs font-bold text-slate-800">${qty}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function openViewModal(id) {        currentTransactionId = id;

        const modal      = document.getElementById('view-modal');
        const modalBox   = document.getElementById('view-modal-content');
        const loading    = document.getElementById('view-loading');
        const body       = document.getElementById('view-body');

        loading.innerHTML = `
            <div class="w-10 h-10 border-4 border-slate-200 border-t-blue-500 rounded-full animate-spin mx-auto mb-4"></div>
            <p class="text-sm text-slate-400 font-medium">Memuat detail...</p>`;
        loading.style.display = 'flex';
        loading.classList.remove('hidden');
        body.style.display = 'none';

        // Show modal first using style.display to avoid Tailwind hidden/flex conflict
        modal.style.display = 'flex';
        modal.classList.remove('hidden', 'opacity-0');
        if (window.toggleBodyScroll) window.toggleBodyScroll(true);
        else { document.documentElement.style.overflow = 'hidden'; document.body.style.overflow = 'hidden'; }
        
        // Then animate
        requestAnimationFrame(() => {
            modal.setAttribute('aria-hidden', 'false');
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
                loading.style.display = 'none';
                loading.classList.add('hidden');
                body.style.display = 'flex';
                body.style.flexDirection = 'column';
                
                // ✅ ATTACH CLICK EVENT TO IMAGE
                const imgWrapper = document.getElementById('v-image-wrapper');
                if (imgWrapper) {
                    // Remove previous listeners by cloning
                    const newWrapper = imgWrapper.cloneNode(true);
                    imgWrapper.parentNode.replaceChild(newWrapper, imgWrapper);
                    newWrapper.addEventListener('click', function() {
                        const img = document.getElementById('v-image');
                        if (img && img.src) {
                            openImageViewer(img.src);
                        }
                    });
                }
                
                if (typeof lucide !== 'undefined') lucide.createIcons();
                
                // Focus close button after content loads
                setTimeout(() => {
                    const closeBtn = modal.querySelector('button[onclick="closeViewModal()"]');
                    if (closeBtn) closeBtn.focus();
                }, 150);
            })
            .catch(err => {
                console.error(err);
                loading.innerHTML = '<p class="text-red-500 text-sm font-bold">Gagal memuat data. Coba lagi.</p>';
                loading.style.display = 'flex';
            });
    }

    function closeViewModal() {
        const modal    = document.getElementById('view-modal');
        const modalBox = document.getElementById('view-modal-content');
        
        // Remove focus from any element inside modal FIRST
        if (document.activeElement && modal.contains(document.activeElement)) {
            document.activeElement.blur();
        }
        
        // Unlock scroll immediately (before animation finishes)
        if (window.toggleBodyScroll) window.toggleBodyScroll(false);
        else { document.documentElement.style.overflow = ''; document.body.style.overflow = ''; }
        
        // Animate close
        modal.classList.add('opacity-0');
        modalBox.classList.remove('scale-100');
        modalBox.classList.add('scale-95');
        
        // After animation, hide
        setTimeout(() => {
            modal.style.display = 'none';
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
        }, 300);
    }

    document.getElementById('view-modal').addEventListener('click', e => {
        if (e.target.id === 'view-modal') closeViewModal();
    });

    function settleBranchDebt(debtId) {
        if (!confirm('Apakah hutang ini sudah dilunaskan antar cabang?')) return;
        
        const catatan = prompt('Catatan pelunasan (Opsional):');
        if (catatan === null) return; // User cancelled prompt

        fetch('/branch-debts/' + debtId + '/settle', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ notes: catatan })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast(res.message, 'success');
                // Refresh modal content to show updated status
                if (currentTransactionId) {
                    openViewModal(currentTransactionId);
                }
            } else {
                showToast(res.message, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Terjadi kesalahan jaringan.', 'error');
        });
    }

    function renderViewModal(d) {
        currentTransactionId = d.id;

        document.getElementById('view-modal-title').textContent = d.type === 'pengajuan' ? 'Detail Pengajuan' : 'Detail Reimbursement';
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

        // ✅ Revisi Banner untuk Pengajuan yang sudah diedit Management
        const revisBannerContainer = document.getElementById('v-revision-banner');
        if (revisBannerContainer) {
            if (d.type === 'pengajuan' && d.is_edited_by_management) {
                revisBannerContainer.innerHTML = `
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 flex flex-col gap-3">
                        <div class="flex items-start gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="git-branch" class="w-4 h-4 text-blue-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-slate-800">Direvisi oleh Management</p>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    Diedit oleh <strong class="text-blue-600">${d.editor_name || '-'}</strong>
                                    pada ${d.edited_at || '-'}
                                    &nbsp;<span class="inline-block bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-[10px] font-bold">Revisi ke-${d.revision_count}</span>
                                </p>
                            </div>
                        </div>
                        ${(d.items_snapshot && d.items_snapshot.length > 0) ? `
                        <div class="flex gap-2">
                            <button type="button" id="v-toggle-original"
                                onclick="toggleVersionInModal('original')"
                                class="flex-1 px-3 py-2 rounded-lg text-[11px] font-bold bg-white text-slate-600 border border-slate-200 shadow-sm transition-all hover:bg-slate-50">
                                <i data-lucide="user" class="w-3.5 h-3.5 inline mr-1"></i>Versi Pengaju (V1)
                            </button>
                            <button type="button" id="v-toggle-management"
                                onclick="toggleVersionInModal('management')"
                                class="flex-1 px-3 py-2 rounded-lg text-[11px] font-bold bg-blue-500 text-white shadow-sm transition-all">
                                <i data-lucide="shield-check" class="w-3.5 h-3.5 inline mr-1"></i>Versi Management (V${(d.revision_count || 0) + 1})
                            </button>
                        </div>` : ''}
                    </div>`;
                revisBannerContainer.classList.remove('hidden');

                // Store version data on modal for toggle function
                window._modalVersionData = {
                    original: d.items_snapshot || [],
                    management: d.items || [],
                    d: d
                };
                window._modalCurrentVersion = 'management'; // Default to management if edited
            } else {
                revisBannerContainer.innerHTML = '';
                revisBannerContainer.classList.add('hidden');
                window._modalVersionData = {
                    original: d.items_snapshot || [],
                    management: d.items || [],
                    d: d
                };
                window._modalCurrentVersion = 'original';
            }
            // Re-init lucide
            if (typeof lucide !== 'undefined') lucide.createIcons({ root: revisBannerContainer });
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
            // Untuk pengajuan, selalu tampilkan alasan utama di header jika items tidak ada atau single.
            // Jika multi-item, alasan tiap item ada di card-nya masing-masing.
            // Namun agar konsisten dengan permintaan user, kita tampilkan alasan utama/kategori di header.
            if (!d.items || d.items.length === 0) {
                addField('Nama Barang/Jasa',      d.customer, true);
                addField('Vendor',                d.vendor);
                addField('Alasan Pembelian',      d.purchase_reason_label);
                addField('Jumlah',                d.quantity);
                addField('Estimasi Harga Satuan', d.estimated_price ? 'Rp ' + Number(d.estimated_price).toLocaleString('id-ID') : null);
            } else {
                // Untuk multi-item, kita tetap tampilkan Alasan Pembelian utama di header agar tidak "kosong"
                addField('Alasan Pembelian Utama', d.purchase_reason_label);
            }
            
            // Note: Keterangan Global & Total Estimasi mapped later to v-summary-wrap

            // Invoice details for Pengajuan
            if (d.status === 'completed' && d.invoice_file_url) {
                // Build Multi Sumber Dana HTML
                let sumberDanaHtml = '';
                if (d.sumber_dana_data && d.sumber_dana_data.length > 0) {
                    const branchesLookup = {};
                    d.branches_raw.forEach(b => branchesLookup[b.id] = b.name);
                    
                    sumberDanaHtml = `
                    <div class="sm:col-span-2 mb-3">
                        <label class="block text-[9px] font-bold text-teal-600/60 uppercase mb-2">Sumber Dana Pembayaran</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                            ${d.sumber_dana_data.map(sd => `
                                <div class="bg-teal-50 border border-teal-100 rounded-lg p-2 flex justify-between items-center">
                                    <span class="text-xs font-bold text-slate-700">${branchesLookup[sd.branch_id] || 'Cabang ' + sd.branch_id}</span>
                                    <span class="text-xs font-bold text-teal-600">Rp ${Number(sd.amount).toLocaleString('id-ID')}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>`;
                } else {
                    // Fallback for old transactions
                    sumberDanaHtml = `
                        <div class="sm:col-span-2 bg-teal-50/50 border border-teal-100 rounded-xl px-4 py-3 mb-3">
                            <label class="block text-[9px] font-bold text-teal-600/60 uppercase mb-1">Sumber Dana</label>
                            <div class="text-sm font-bold text-slate-700">${d.sumber_dana_branch_name || '-'}</div>
                        </div>
                    `;
                }

                // Build Branch Debts HTML
                let debtsHtml = '';
                if (d.branch_debts && d.branch_debts.length > 0) {
                    debtsHtml = `
                    <div class="sm:col-span-2 mb-3">
                        <label class="block text-[9px] font-bold text-red-500 uppercase mb-2">Hutang Tersisa Antar Cabang</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            ${d.branch_debts.map(debt => `
                                <div class="bg-red-50 border border-red-100 rounded-lg p-3 ${debt.status === 'paid' ? 'opacity-50' : ''}">
                                    <div class="flex justify-between items-start mb-1">
                                        <div class="text-[11px]"><span class="font-bold text-red-600">${debt.debtor_branch_name}</span> berhutang ke <span class="font-bold text-slate-700">${debt.creditor_branch_name}</span></div>
                                        <div class="text-xs font-bold ${debt.status === 'paid' ? 'text-emerald-600' : 'text-red-600'}">Rp ${Number(debt.amount).toLocaleString('id-ID')}</div>
                                    </div>
                                    <div class="text-[10px] font-bold ${debt.status === 'paid' ? 'text-emerald-500' : 'text-red-400'} uppercase tracking-wider flex justify-between items-center mt-2">
                                        <span>Status: ${debt.status === 'paid' ? 'Lunas' : 'Belum Lunas'}</span>
                                        ${(debt.status === 'pending' && d.can_manage) ? `
                                        <button type="button" onclick="settleBranchDebt(${debt.id})" class="bg-red-100/50 border border-red-200 hover:bg-red-200 hover:border-red-300 text-red-700 px-2 py-1 rounded transition-colors active:scale-95">Mark as Lunas</button>
                                        ` : ''}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>`;
                }

                fieldsHtml += `
                    <div class="sm:col-span-2 mt-4 pt-4 border-t border-slate-100">
                        <label class="block text-[10px] font-bold text-teal-500 uppercase mb-3 tracking-widest">Detail Pembayaran Invoice</label>
                        
                        ${sumberDanaHtml}
                        ${debtsHtml}

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                            <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Ongkir</label>
                                <div class="text-sm font-bold text-slate-700">Rp ${Number(d.ongkir || 0).toLocaleString('id-ID')}</div>
                            </div>
                            <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Diskon Pengiriman</label>
                                <div class="text-sm font-bold text-slate-700">Rp ${Number(d.diskon_pengiriman || 0).toLocaleString('id-ID')}</div>
                            </div>
                            <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Voucher Diskon</label>
                                <div class="text-sm font-bold text-slate-700">Rp ${Number(d.voucher_diskon || 0).toLocaleString('id-ID')}</div>
                            </div>
                            <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Biaya Layanan 1</label>
                                <div class="text-sm font-bold text-slate-700">Rp ${Number(d.biaya_layanan_1 || 0).toLocaleString('id-ID')}</div>
                            </div>
                            <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Biaya Layanan 2</label>
                                <div class="text-sm font-bold text-slate-700">Rp ${Number(d.biaya_layanan_2 || 0).toLocaleString('id-ID')}</div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">File Invoice</label>
                            <a href="${d.invoice_file_url}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-xs font-bold text-teal-600 hover:bg-teal-50 transition-colors">
                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Lihat Invoice
                            </a>
                        </div>
                    </div>`;
            }
        }

        fieldsEl.innerHTML = fieldsHtml;

        // Populate Summary Wrap for Pengajuan
        const summaryWrap = document.getElementById('v-summary-wrap');
        const summaryDescWrap = document.getElementById('v-summary-desc-wrap');
        const summaryDesc = document.getElementById('v-summary-desc');
        const summaryTotalWrap = document.getElementById('v-summary-total-wrap');
        const summaryTotal = document.getElementById('v-summary-total');

        if (d.type === 'pengajuan') {
            summaryWrap.classList.remove('hidden');

            if (d.items && d.items.length > 0) {
                // Show Keterangan Global
                if (d.description) {
                    summaryDescWrap.classList.remove('hidden');
                    summaryDescWrap.classList.add('md:col-span-2');
                    summaryTotalWrap.className = 'bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 flex flex-col justify-center shadow-sm';
                    summaryDesc.textContent = d.description;
                } else {
                    summaryDescWrap.classList.add('hidden');
                    summaryTotalWrap.className = 'md:col-span-3 bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 flex flex-col justify-center shadow-sm';
                }
            } else {
                // If single item pengajuan (no items array), description isn't used as Keterangan Global based on previous logic
                summaryDescWrap.classList.add('hidden');
                summaryTotalWrap.className = 'md:col-span-3 bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 flex flex-col justify-center shadow-sm text-center items-center';
            }
            
            summaryTotal.textContent = d.amount ? 'Rp ' + Number(d.amount).toLocaleString('id-ID') : '-';
        } else {
            summaryWrap.classList.add('hidden');
        }

        const itemsWrap  = document.getElementById('v-items-wrap');
        const itemsTbody = document.getElementById('v-items-tbody');
        const itemsTableCont = document.getElementById('v-items-table-container');
        const itemsDivCont   = document.getElementById('v-items-div-container');
        
        if ((d.items && d.items.length > 0) || (d.items_snapshot && d.items_snapshot.length > 0)) {
            itemsWrap.classList.remove('hidden');
            
            if (d.type === 'pengajuan') {
                itemsTableCont.classList.add('hidden');
                itemsDivCont.classList.remove('hidden');
                
                // If it's edited, show the current version (management) by default
                const itemsToRender = window._modalCurrentVersion === 'management' ? d.items : (d.items_snapshot || d.items);
                itemsDivCont.innerHTML = renderTransactionItemsCards(itemsToRender, window._modalCurrentVersion, d.items_snapshot || []);
                
                if (typeof lucide !== 'undefined') lucide.createIcons({ root: itemsDivCont });
            } else {
                itemsDivCont.classList.add('hidden');
                itemsTableCont.classList.remove('hidden');
                
                let itemsHtml = d.items.map(item => `
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-3 py-2 text-slate-700 font-medium">${item.name || item.nama_barang || '-'}</td>
                        <td class="px-3 py-2 text-center">${item.qty || '-'}</td>
                        <td class="px-3 py-2">${item.unit || item.satuan || '-'}</td>
                        <td class="px-3 py-2 text-right">Rp ${Number(item.price || item.harga_satuan || 0).toLocaleString('id-ID')}</td>
                        <td class="px-3 py-2 text-right font-bold">Rp ${( (Number(item.qty) || 0) * (Number(item.price || item.harga_satuan) || 0) ).toLocaleString('id-ID')}</td>
                    </tr>`).join('');
                itemsTbody.innerHTML = itemsHtml;
            }
        } else {
            itemsWrap.classList.add('hidden');
        }

        const specsWrap = document.getElementById('v-specs-wrap');
        const specsEl   = document.getElementById('v-specs');
        if (d.type === 'pengajuan' && d.specs && Object.values(d.specs).some(v => v) && (!d.items || d.items.length === 0)) {
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

        // Trigger initial version & history render
        toggleVersionInModal(window._modalCurrentVersion);
    }

    // ─── Version Toggle in Detail Modal ────────────────────────────
    function toggleVersionInModal(version) {
        if (!window._modalVersionData) return;

        const items = version === 'original'
            ? window._modalVersionData.original
            : window._modalVersionData.management;

        window._modalCurrentVersion = version;

        // Update button states
        const btnOriginal   = document.getElementById('v-toggle-original');
        const btnManagement = document.getElementById('v-toggle-management');

        if (btnOriginal && btnManagement) {
            if (version === 'original') {
                btnOriginal.className   = 'flex-1 px-3 py-2 rounded-lg text-[11px] font-bold bg-blue-500 text-white shadow-sm transition-all';
                btnManagement.className = 'flex-1 px-3 py-2 rounded-lg text-[11px] font-bold text-slate-600 bg-white border border-slate-200 transition-all hover:bg-slate-50';
            } else {
                btnManagement.className = 'flex-1 px-3 py-2 rounded-lg text-[11px] font-bold bg-blue-500 text-white shadow-sm transition-all';
                btnOriginal.className   = 'flex-1 px-3 py-2 rounded-lg text-[11px] font-bold text-slate-600 bg-white border border-slate-200 transition-all hover:bg-slate-50';
            }
        }

        const itemsWrap      = document.getElementById('v-items-wrap');
        const itemsTbody     = document.getElementById('v-items-tbody');
        const itemsDivCont   = document.getElementById('v-items-div-container');
        const itemsTableCont = document.getElementById('v-items-table-container');

        if (items && items.length > 0) {
            itemsWrap.classList.remove('hidden');
            const label = version === 'original'
                ? '<span class="text-xs text-blue-600 font-bold ml-2">(Versi Pengaju)</span>'
                : '<span class="text-xs text-emerald-600 font-bold ml-2">(Versi Management)</span>';

            // Update section title
            const sectionLabel = itemsWrap.querySelector('label');
            if (sectionLabel) {
                sectionLabel.innerHTML = 'Daftar Barang' + label;
            }

            if (itemsDivCont && !itemsDivCont.classList.contains('hidden')) {
                // Render Cards (Pengajuan)
                itemsDivCont.innerHTML = renderTransactionItemsCards(items, version, window._modalVersionData.original);
                if (typeof lucide !== 'undefined') lucide.createIcons({ root: itemsDivCont });
            } else if (itemsTbody) {
                // Render Table (Rembush)
                itemsTbody.innerHTML = items.map(item => `
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-3 py-2 text-slate-700 font-medium">${item.customer || item.name || item.nama_barang || '-'}</td>
                        <td class="px-3 py-2 text-center">${item.quantity || item.qty || '-'}</td>
                        <td class="px-3 py-2">${item.unit || item.satuan || '-'}</td>
                        <td class="px-3 py-2 text-right">Rp ${Number(item.estimated_price || item.price || item.harga_satuan || 0).toLocaleString('id-ID')}</td>
                        <td class="px-3 py-2 text-right font-bold">Rp ${((Number(item.quantity || item.qty) || 0) * (Number(item.estimated_price || item.price || item.harga_satuan) || 0)).toLocaleString('id-ID')}</td>
                    </tr>`).join('');
            }
        } else {
            if (itemsWrap) itemsWrap.classList.add('hidden');
        }

        // ✅ Riwayat Pembayaran (Payment History)
        const payHistWrap = document.getElementById('v-payment-history-wrap');
        
        if (window._modalVersionData?.d?.is_paid && window._modalVersionData?.d?.type === 'rembush') {
            const hist = window._modalVersionData.d;
            payHistWrap.classList.remove('hidden');

            // Set Step 1 Info
            const step1Title = document.getElementById('v-pay-step1-title');
            if (hist.payment_type === 'Transfer') {
                step1Title.textContent = 'BUKTI TRANSFER DIUNGGAH';
            } else if (hist.payment_type === 'Tunai') {
                step1Title.textContent = 'PEMBAYARAN TUNAI DISERAHKAN';
            } else {
                step1Title.textContent = 'PEMBAYARAN DIPROSES';
            }

            document.getElementById('v-pay-step1-at').textContent = hist.payment_at || '-';
            document.getElementById('v-pay-step1-by').textContent = hist.paid_by_name || 'System';
            document.getElementById('v-pay-step1-role').textContent = hist.paid_by_role || 'Admin';

            // Action Button Step 1 (Lihat Bukti or Selesai Badge)
            const actionWrap1 = document.getElementById('v-pay-step1-action-wrap');
            if (hist.payment_type === 'Transfer' && hist.payment_proof_url) {
                actionWrap1.innerHTML = `
                    <button type="button" onclick="openImageViewer('${hist.payment_proof_url}', 'Bukti Pembayaran')" 
                        class="flex items-center gap-1.5 text-[10px] font-black text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-100 hover:bg-emerald-100 transition-all uppercase tracking-widest">
                        <i data-lucide="image" class="w-3.5 h-3.5"></i>
                        LIHAT BUKTI
                    </button>
                `;
            } else if (hist.payment_type === 'Tunai' && hist.payment_proof_url) {
                actionWrap1.innerHTML = `
                    <button type="button" onclick="openImageViewer('${hist.payment_proof_url}', 'Bukti Penyerahan')" 
                        class="flex items-center gap-1.5 text-[10px] font-black text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-100 hover:bg-emerald-100 transition-all uppercase tracking-widest">
                        <i data-lucide="image" class="w-3.5 h-3.5"></i>
                        LIHAT BUKTI
                    </button>
                `;
            } else if (hist.payment_type === 'Tunai') {
                actionWrap1.innerHTML = `
                    <div class="flex items-center gap-1.5 text-[10px] font-black text-slate-400 bg-white px-3 py-1.5 rounded-xl border border-slate-100 uppercase tracking-widest">
                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-slate-400"></i>
                        Selesai
                    </div>
                `;
            } else {
                actionWrap1.innerHTML = '';
            }

            // Set Step 2 Info (Penerimaan)
            const step2Wrap = document.getElementById('v-pay-step2-wrap');
            if (hist.status === 'completed' || hist.konfirmasi_at) {
                step2Wrap.classList.remove('hidden');
                document.getElementById('v-pay-step2-at').textContent = hist.konfirmasi_at || hist.payment_at || '-';
                document.getElementById('v-pay-step2-by').textContent = hist.konfirmasi_by_name || hist.recipient_name || '-';
                document.getElementById('v-pay-step2-role').textContent = hist.konfirmasi_by_role || hist.recipient_role || 'Teknisi';
            } else {
                step2Wrap.classList.add('hidden');
            }

            // Summary Box
            const summaryMethod = document.getElementById('v-pay-summary-method');
            const summaryAccount = document.getElementById('v-pay-summary-account');
            const methodWrap = document.getElementById('v-pay-method-wrap');

            if (hist.payment_method && methodWrap) {
                methodWrap.classList.remove('hidden');
                
                let methodLabel = hist.payment_method_label || hist.payment_method;
                let accountInfo = '';

                if (hist.payment_method === 'transfer_teknisi') {
                    methodLabel = 'Transfer ke Teknisi';
                    if (hist.submitter) {
                        const bank = hist.submitter.rekening_bank || '-';
                        const name = hist.submitter.rekening_nama || '-';
                        const number = hist.submitter.rekening_nomor || '-';
                        accountInfo = `${bank} • ${name}<br>${number}`;
                    }
                } else if (hist.payment_method === 'transfer_penjual') {
                    methodLabel = 'Transfer ke Penjual';
                    if (hist.specs) {
                        const bank = hist.specs.bank_name || '-';
                        const name = hist.specs.account_name || '-';
                        const number = hist.specs.account_number || '-';
                        accountInfo = `${bank} • ${name}<br>${number}`;
                    }
                } else if (hist.payment_method === 'cash') {
                    methodLabel = 'Tunai (Cash)';
                }

                if (summaryMethod) summaryMethod.innerHTML = methodLabel;
                if (summaryAccount) summaryAccount.innerHTML = accountInfo || (hist.payment_type || '-');
            } else if (methodWrap) {
                methodWrap.classList.add('hidden');
            }

            const summaryAmount = document.getElementById('v-pay-summary-amount');
            const summaryDiscrepancy = document.getElementById('v-pay-summary-discrepancy');

            // Set main amount (use actual_total if exists, else fallback to amount)
            const finalAmount = hist.actual_total || hist.amount || 0;
            summaryAmount.textContent = 'Rp ' + Number(finalAmount).toLocaleString('id-ID');

            // Set Discrepancy (Selisih)
            if (summaryDiscrepancy) {
                const selisih = Number(hist.selisih || 0);
                if (selisih !== 0) {
                    summaryDiscrepancy.classList.remove('hidden');
                    const absSelisih = Math.abs(selisih).toLocaleString('id-ID');
                    if (selisih > 0) {
                        // Surplus (Hemat/Saved) - Green
                        summaryDiscrepancy.innerHTML = `<i data-lucide="trending-down" class="w-2.5 h-2.5 inline mr-1"></i>Hemat Rp ${absSelisih}`;
                        summaryDiscrepancy.className = "text-[10px] font-bold mt-1 px-2 py-0.5 rounded-lg uppercase tracking-wider bg-emerald-500/20 text-emerald-400 border border-emerald-500/30";
                    } else {
                        // Deficit (Overspent) - Red
                        summaryDiscrepancy.innerHTML = `<i data-lucide="trending-up" class="w-2.5 h-2.5 inline mr-1"></i>Lebih Rp ${absSelisih}`;
                        summaryDiscrepancy.className = "text-[10px] font-bold mt-1 px-2 py-0.5 rounded-lg uppercase tracking-wider bg-rose-500/20 text-rose-400 border border-rose-500/30";
                    }
                } else {
                    summaryDiscrepancy.classList.add('hidden');
                }
            }

            // Re-init lucide icons
            if (typeof lucide !== 'undefined') lucide.createIcons({ root: payHistWrap });
        } else {
            if (payHistWrap) payHistWrap.classList.add('hidden');
        }
    }

    // ─── Central AJAX status update (no page reload) ───────────────
    function performStatusAction(id, status, triggerEl) {
        if (triggerEl) {
            triggerEl.disabled = true;
            triggerEl.innerHTML = '<i data-lucide="loader-2" class="w-3 h-3 animate-spin"></i>';
            if (typeof lucide !== 'undefined') lucide.createIcons({ root: triggerEl });
        }
        fetch(`/transactions/${id}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ status, _method: 'PATCH' }),
        })
        .then(r => r.json().catch(() => ({})))
        .then(data => {
            if (data.success) {
                showToast(`<div class="flex items-start gap-2"><i data-lucide="check-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-emerald-600"></i><div><strong class="text-emerald-800">Berhasil!</strong><br><span class="text-[11px] opacity-90 text-emerald-700">${data.message || 'Status berhasil diperbarui.'}</span></div></div>`, 'success');
                if (data.transaction) {
                    SearchEngine.updateTransaction(data.transaction);
                } else {
                    SearchEngine.init();
                }
            } else {
                throw new Error(data.message || 'Gagal memperbarui status');
            }
        })
        .catch(err => {
            console.error(err);
            showToast(`<div class="flex items-start gap-2"><i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i><div><strong>Gagal!</strong><br><span class="text-[11px] opacity-90">Coba lagi.</span></div></div>`, 'error');
            if (triggerEl) { triggerEl.disabled = false; }
        });
    }

    function submitApproval(status) {
        if (!currentTransactionId) return;
        if (status === 'pending' && !confirm('Reset status ke Pending?')) return;
        closeViewModal();
        performStatusAction(currentTransactionId, status, null);
    }

    function openRejectModal(transactionId, invoiceNumber) {
        const modal = document.getElementById('reject-modal');
        const inner = modal.querySelector('div');

        document.getElementById('reject-form').action      = '/transactions/' + transactionId + '/status';
        document.getElementById('reject-modal-invoice').textContent = invoiceNumber;

        // Show modal first
        modal.classList.remove('hidden');
        
        // Then set aria-hidden and animate
        requestAnimationFrame(() => {
            modal.setAttribute('aria-hidden', 'false');
            modal.classList.remove('opacity-0');
            inner.classList.remove('scale-95');
            inner.classList.add('scale-100');
        });
        
        // Focus textarea after animation
        setTimeout(() => {
            const textarea = modal.querySelector('textarea[name="rejection_reason"]');
            if (textarea) textarea.focus();
        }, 350);
    }

    function closeRejectModal() {
        const modal = document.getElementById('reject-modal');
        const inner = modal.querySelector('div');
        
        // Remove focus from any element inside modal FIRST
        if (document.activeElement && modal.contains(document.activeElement)) {
            document.activeElement.blur();
        }
        
        // Animate close
        modal.classList.add('opacity-0');
        inner.classList.remove('scale-100');
        inner.classList.add('scale-95');
        
        // After animation, hide and set aria-hidden
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            modal.querySelector('textarea').value = '';
        }, 300);
    }

    document.getElementById('reject-modal').addEventListener('click', e => {
        if (e.target.id === 'reject-modal') closeRejectModal();
    });

    // ─── OVERRIDE MODAL ─────────────────────────
    function openOverrideModal(transactionId, invoiceNumber) {
        const modal = document.getElementById('override-modal');
        const inner = modal.querySelector('div');

        document.getElementById('override-form').action = '/transactions/' + transactionId + '/override';
        document.getElementById('override-modal-invoice').textContent = invoiceNumber;

        modal.classList.remove('hidden');
        requestAnimationFrame(() => {
            modal.setAttribute('aria-hidden', 'false');
            modal.classList.remove('opacity-0');
            inner.classList.remove('scale-95');
            inner.classList.add('scale-100');
        });
        setTimeout(() => {
            const textarea = modal.querySelector('textarea[name="override_reason"]');
            if (textarea) textarea.focus();
        }, 350);
    }
    function closeOverrideModal() {
        const modal = document.getElementById('override-modal');
        const inner = modal.querySelector('div');
        if (document.activeElement && modal.contains(document.activeElement)) document.activeElement.blur();
        modal.classList.add('opacity-0');
        inner.classList.remove('scale-100');
        inner.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            document.getElementById('override-form').reset();
        }, 300);
    }
    document.getElementById('override-modal').addEventListener('click', e => {
        if (e.target.id === 'override-modal') closeOverrideModal();
    });

    // ─── FORCE APPROVE MODAL ────────────────────
    function openForceApproveModal(transactionId, invoiceNumber) {
        const modal = document.getElementById('force-approve-modal');
        const inner = modal.querySelector('div');

        document.getElementById('force-approve-form').action = '/transactions/' + transactionId + '/force-approve';
        document.getElementById('force-approve-modal-invoice').textContent = invoiceNumber;

        modal.classList.remove('hidden');
        requestAnimationFrame(() => {
            modal.setAttribute('aria-hidden', 'false');
            modal.classList.remove('opacity-0');
            inner.classList.remove('scale-95');
            inner.classList.add('scale-100');
        });
        setTimeout(() => {
            const textarea = modal.querySelector('textarea[name="force_approve_reason"]');
            if (textarea) textarea.focus();
        }, 350);
    }
    function closeForceApproveModal() {
        const modal = document.getElementById('force-approve-modal');
        const inner = modal.querySelector('div');
        if (document.activeElement && modal.contains(document.activeElement)) document.activeElement.blur();
        modal.classList.add('opacity-0');
        inner.classList.remove('scale-100');
        inner.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            document.getElementById('force-approve-form').reset();
        }, 300);
    }
    document.getElementById('force-approve-modal').addEventListener('click', e => {
        if (e.target.id === 'force-approve-modal') closeForceApproveModal();
    });

    // ─── PAYMENT MODAL ──────────────────────────
    function renderPaymentModalDetails(d) {
        const detailContainer = document.getElementById('p-detail-container');
        detailContainer.classList.remove('hidden');

        const statusColors = {
            pending:   'bg-amber-50 text-amber-600 border-amber-200',
            approved:  'bg-blue-50 text-blue-600 border-blue-200',
            completed: 'bg-green-50 text-green-600 border-green-200',
            rejected:  'bg-red-50 text-red-600 border-red-200',
        };
        const typeBg      = d.type === 'pengajuan' ? 'bg-teal-50 text-teal-600 border-teal-100' : 'bg-indigo-50 text-indigo-600 border-indigo-100';
        const typeIcon    = d.type === 'pengajuan' ? 'shopping-bag' : 'receipt';

        document.getElementById('p-badges').innerHTML = `
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border ${statusColors[d.status] || ''}">${d.status_label}</span>
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-bold border ${typeBg}">
                <i data-lucide="${typeIcon}" class="w-3 h-3"></i> ${d.type_label}
            </span>`;

        const fieldsEl = document.getElementById('p-fields');
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
        } else {
            if (!d.items || d.items.length === 0) {
                addField('Nama Barang/Jasa',      d.customer, true);
                addField('Vendor',                d.vendor);
                addField('Alasan Pembelian',      d.purchase_reason_label);
                addField('Jumlah',                d.quantity);
                addField('Estimasi Harga Satuan', d.estimated_price ? 'Rp ' + Number(d.estimated_price).toLocaleString('id-ID') : null);
            } else {
                // Selalu tampilkan alasan utama di header untuk Pengajuan
                addField('Alasan Pembelian Utama', d.purchase_reason_label);
            }
        }

        fieldsEl.innerHTML = fieldsHtml;

        const itemsWrap      = document.getElementById('p-items-wrap');
        const itemsTbody     = document.getElementById('p-items-tbody');
        const itemsTableCont = document.getElementById('p-items-table-container');
        const itemsDivCont   = document.getElementById('p-items-div-container');

        if ((d.items && d.items.length > 0) || (d.items_snapshot && d.items_snapshot.length > 0)) {
            itemsWrap.classList.remove('hidden');
            
            if (d.type === 'pengajuan') {
                if (itemsTableCont) itemsTableCont.classList.add('hidden');
                if (itemsDivCont) {
                    itemsDivCont.classList.remove('hidden');
                    const versionToUse = d.is_edited_by_management ? 'management' : 'original';
                    const itemsToRender = d.is_edited_by_management ? d.items : (d.items_snapshot || d.items);
                    
                    itemsDivCont.innerHTML = renderTransactionItemsCards(itemsToRender, versionToUse, d.items_snapshot || []);
                    if (typeof lucide !== 'undefined') lucide.createIcons({ root: itemsDivCont });
                }
            } else {
                if (itemsDivCont) itemsDivCont.classList.add('hidden');
                if (itemsTableCont) itemsTableCont.classList.remove('hidden');
                
                itemsTbody.innerHTML = d.items.map(item => `
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-3 py-2 text-slate-700 font-medium">${item.name || '-'}</td>
                        <td class="px-3 py-2 text-center">${item.qty || '-'}</td>
                        <td class="px-3 py-2">${item.unit || '-'}</td>
                        <td class="px-3 py-2 text-right">Rp ${Number(item.price || 0).toLocaleString('id-ID')}</td>
                        <td class="px-3 py-2 text-right font-bold">Rp ${( (Number(item.qty) || 0) * (Number(item.price) || 0) ).toLocaleString('id-ID')}</td>
                    </tr>`).join('');
            }
        } else {
            itemsWrap.classList.add('hidden');
        }



        const branchesWrap = document.getElementById('p-branches-wrap');
        const branchesEl   = document.getElementById('p-branches');
        const pSumberDana  = document.getElementById('p_sumber_dana');

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
            
            // Populate Sumber Dana for Pengajuan
            if (d.type === 'pengajuan') {
                const container = document.getElementById('p_sumber_dana_container');
                if (container) {
                    container.innerHTML = '';
                    d.branches_raw.forEach((b, idx) => {
                        const html = `
                            <div id="sd_card_${b.id}" class="sd-card p-4 bg-white border-2 border-slate-100 rounded-2xl hover:border-teal-400 transition-all duration-200">
                                <div class="flex items-center gap-4">
                                    <div class="relative flex items-center">
                                        <input type="checkbox" id="sd_check_${b.id}" class="sd-checkbox peer sr-only" value="${b.id}" data-alloc="${b.allocation_amount}" data-percent="${b.allocation_percent}" data-name="${b.name}">
                                        <label for="sd_check_${b.id}" class="w-8 h-8 border-2 border-slate-200 rounded-xl flex items-center justify-center cursor-pointer transition-all peer-checked:bg-teal-600 peer-checked:border-teal-600 peer-checked:[&_svg]:opacity-100 peer-checked:[&_i]:opacity-100 hover:border-teal-200">
                                            <i data-lucide="check" class="w-4 h-4 text-white opacity-0 transition-opacity"></i>
                                        </label>
                                    </div>

                                    <div class="flex-1">
                                        <label for="sd_check_${b.id}" class="block cursor-pointer">
                                            <div class="text-sm font-black text-slate-800 uppercase tracking-tight leading-none mb-1.5">${b.name}</div>
                                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider leading-none">Alokasi: Rp ${Number(b.allocation_amount).toLocaleString('id-ID')} (${b.allocation_percent}%)</div>
                                        </label>
                                    </div>

                                    <div class="w-44 relative">
                                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-black text-slate-400">Rp</span>
                                        <input type="text" id="sd_amount_${b.id}" name="sumber_dana[${idx}][amount]" disabled placeholder="0"
                                            class="sd-amount nominal-input w-full pl-10 pr-4 py-2.5 text-sm font-black text-slate-800 border-2 border-slate-100 rounded-xl outline-none focus:border-teal-400 focus:ring-4 focus:ring-teal-50 transition-all disabled:bg-slate-50/50 disabled:text-slate-300">
                                        <input type="hidden" id="sd_branch_${b.id}" name="sumber_dana[${idx}][branch_id]" value="${b.id}" disabled>
                                    </div>
                                    </div>
                                    <div id="sd_status_${b.id}" class="mt-3 text-right text-[10px] font-bold tracking-tight hidden"></div>
                                    </div>
                                    `;
                                    container.insertAdjacentHTML('beforeend', html);
                                    });

                                    // Re-attach nominal formatters for static and dynamic
                                    attachNominalFormatters();

                                    // Add event listeners
                                    document.querySelectorAll('.sd-checkbox').forEach(cb => {
                                    cb.addEventListener('change', function() {
                                    const id = this.value;
                                    const card = document.getElementById('sd_card_' + id);
                                    const amountInput = document.getElementById('sd_amount_' + id);
                                    const branchInput = document.getElementById('sd_branch_' + id);
                                    const alloc = parseInt(this.dataset.alloc);

                                    if (this.checked) {
                                    amountInput.disabled = false;
                                    branchInput.disabled = false;
                                    amountInput.value = formatNumber(alloc); 
                                    amountInput.required = true;
                                    card.classList.remove('border-slate-100');
                                    card.classList.add('border-teal-500', 'bg-teal-50/10');
                                    } else {
                                    amountInput.disabled = true;
                                    branchInput.disabled = true;
                                    amountInput.value = '';
                                    amountInput.required = false;
                                    card.classList.remove('border-teal-500', 'bg-teal-50/10');
                                    card.classList.add('border-slate-100');
                                    }
                                    calculateSumberDanaTotal(d.effective_amount);
                                    });
                                    });

                                    document.querySelectorAll('.sd-amount').forEach(inp => {
                                    inp.addEventListener('input', () => calculateSumberDanaTotal(d.effective_amount));
                                    });

                                    // Adjustment fields listeners
                                    ['p_ongkir', 'p_diskon_pengiriman', 'p_voucher_diskon', 'p_biaya_layanan_1', 'p_biaya_layanan_2'].forEach(id => {
                                        document.getElementById(id).addEventListener('input', () => calculateSumberDanaTotal(d.effective_amount));
                                    });
                    
                    // Initial calculation to set initial state (e.g. disable button if total mismatch)
                    calculateSumberDanaTotal(d.effective_amount);
                }
            }
        } else {
            branchesWrap.classList.add('hidden');
            // Ensure button is not disabled by previous pengajuan mismatch if current is not pengajuan
            document.getElementById('btnSubmitPayment').disabled = false;
            document.getElementById('btnSubmitPayment').classList.remove('opacity-50', 'cursor-not-allowed');
        }
        
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // NOMINAL FORMATTERS
    // ═══════════════════════════════════════════════════════════════
    function formatNumber(n) {
        if (!n) return '';
        return n.toString().replace(/\D/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    function unformatNumber(s) {
        if (!s) return 0;
        return parseInt(s.toString().replace(/\D/g, "")) || 0;
    }

    function attachNominalFormatters() {
        document.querySelectorAll('.nominal-input').forEach(inp => {
            // Remove existing to avoid double binding
            inp.removeEventListener('input', handleNominalInput);
            inp.addEventListener('input', handleNominalInput);
        });
    }

    function handleNominalInput(e) {
        let cursor = e.target.selectionStart;
        let originalLen = e.target.value.length;
        let formatted = formatNumber(e.target.value);
        e.target.value = formatted;

        // Adjust cursor position
        let newLen = formatted.length;
        e.target.setSelectionRange(cursor + (newLen - originalLen), cursor + (newLen - originalLen));
    }

    // Initial attach
    document.addEventListener('DOMContentLoaded', () => {
        attachNominalFormatters();
    });

    function calculateSumberDanaTotal(baseTotal) {
        const totalEl = document.getElementById('p_sumber_dana_total');
        const totalVal = document.getElementById('p_sumber_dana_total_value');
        const diffEl = document.getElementById('p_sumber_dana_diff');
        const debtPreview = document.getElementById('p_debt_preview');
        const debtList = document.getElementById('p_debt_preview_list');
        const btnSubmit = document.getElementById('btnSubmitPayment');
        
        // Final Total Calculation: base + ongkir + fees - discounts
        const ongkir      = unformatNumber(document.getElementById('p_ongkir').value);
        const diskon      = unformatNumber(document.getElementById('p_diskon_pengiriman').value);
        const voucher     = unformatNumber(document.getElementById('p_voucher_diskon').value);
        const layanan1    = unformatNumber(document.getElementById('p_biaya_layanan_1').value);
        const layanan2    = unformatNumber(document.getElementById('p_biaya_layanan_2').value);
        
        const finalTotalTarget = baseTotal + ongkir + layanan1 + layanan2 - diskon - voucher;

        totalEl.classList.remove('hidden');

        let total = 0;
        let creditors = {};
        let debtors = {};
        let branches = {};

        document.querySelectorAll('.sd-checkbox').forEach(cb => {
            const id = cb.value;
            const name = cb.dataset.name;
            const percent = parseFloat(cb.dataset.percent);
            
            // Recalculate allocation based on final target total
            const alloc = Math.round((finalTotalTarget * percent) / 100);
            
            const statusEl = document.getElementById('sd_status_' + id);
            const labelEl = document.querySelector(`label[for="sd_check_${id}"] div.text-slate-400`);
            
            if (labelEl) {
                labelEl.textContent = `Alokasi: Rp ${alloc.toLocaleString('id-ID')} (${percent}%)`;
            }
            
            branches[id] = { id, name, alloc };

            const paidValue = cb.checked ? document.getElementById('sd_amount_' + id).value : 0;
            const paid = unformatNumber(paidValue);
            total += paid;

            // Per-item status
            if (cb.checked) {
                if (paid > alloc) {
                    creditors[id] = paid - alloc;
                    statusEl.innerHTML = `<span class="text-teal-600">+ Lebih bayar Rp ${(paid - alloc).toLocaleString('id-ID')} (Menalangi)</span>`;
                    statusEl.classList.remove('hidden');
                } else if (paid < alloc) {
                    debtors[id] = alloc - paid;
                    statusEl.innerHTML = `<span class="text-red-500">- Kurang bayar Rp ${(alloc - paid).toLocaleString('id-ID')} (Berhutang)</span>`;
                    statusEl.classList.remove('hidden');
                } else {
                    statusEl.classList.add('hidden');
                }
            } else {
                debtors[id] = alloc;
                statusEl.innerHTML = `<span class="text-red-500">- Kurang bayar Rp ${alloc.toLocaleString('id-ID')} (Berhutang)</span>`;
                statusEl.classList.remove('hidden');
            }
        });
        totalVal.textContent = 'Rp ' + total.toLocaleString('id-ID');

        // Validation check vs total transaksi
        if (total !== finalTotalTarget) {
            totalVal.classList.remove('text-teal-600');
            totalVal.classList.add('text-red-500');
            diffEl.classList.remove('text-emerald-500');
            diffEl.classList.add('text-red-500');
            const diff = finalTotalTarget - total;
            diffEl.textContent = diff > 0 ? `Kurang Rp ${diff.toLocaleString('id-ID')} dari Total Tagihan` : `Kelebihan Rp ${Math.abs(diff).toLocaleString('id-ID')} dari Total Tagihan`;
            btnSubmit.disabled = true;
            btnSubmit.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            totalVal.classList.remove('text-red-500');
            totalVal.classList.add('text-teal-600');
            diffEl.classList.remove('text-red-500');
            diffEl.classList.add('text-emerald-500');
            diffEl.textContent = 'Nominal sesuai dengan nilai bayar transaksi';
            btnSubmit.disabled = false;
            btnSubmit.classList.remove('opacity-50', 'cursor-not-allowed');
        }        
        // Preview Debt
        const creditorIds = Object.keys(creditors);
        const debtorIds = Object.keys(debtors);

        if (creditorIds.length > 0 && debtorIds.length > 0) {
            debtPreview.classList.remove('hidden');
            let debtHtml = '';

            const totalExcess = Object.values(creditors).reduce((a,b)=>a+b,0);

            for (let debtorId of debtorIds) {
                const debtAmt = debtors[debtorId];
                let cardHtml = `
                    <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm hover:shadow-md transition-all">
                        <div class="flex justify-between items-start mb-1.5">
                            <div class="space-y-0.5">
                                <h4 class="text-sm font-black text-slate-800 uppercase tracking-tight">${branches[debtorId].name}</h4>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Total beban hutang</p>
                            </div>
                            <div class="px-2.5 py-1.5 bg-red-50/50 text-red-600 text-[11px] font-black rounded-lg border border-red-50">
                                Rp ${debtAmt.toLocaleString('id-ID')}
                            </div>
                        </div>

                        <div class="my-4 border-t border-slate-50 border-dashed"></div>

                        <div class="text-[9px] font-black text-slate-400 uppercase tracking-[0.15em] mb-3">Rincian Pembayaran Ke:</div>
                        <div class="space-y-3">
                `;

                for (let creditorId of creditorIds) {
                    const excess = creditors[creditorId];
                    const proportion = excess / totalExcess;
                    const finalAmt = Math.round(debtAmt * proportion);

                    if (finalAmt > 0) {
                        cardHtml += `
                            <div class="flex items-center justify-between group">
                                <div class="flex items-center gap-3">
                                    <div class="w-4 h-4 rounded-full bg-slate-50 flex items-center justify-center">
                                        <i data-lucide="arrow-right" class="w-2.5 h-2.5 text-slate-300 group-hover:text-teal-400 transition-colors"></i>
                                    </div>
                                    <span class="text-[11px] font-bold text-slate-600 group-hover:text-slate-800 transition-colors uppercase tracking-tight">${branches[creditorId].name}</span>
                                </div>
                                <span class="text-[11px] font-black text-slate-800 bg-slate-50/50 px-2 py-1 rounded-md">Rp ${finalAmt.toLocaleString('id-ID')}</span>
                            </div>
                        `;
                    }
                }

                cardHtml += `
                        </div>
                    </div>
                `;
                debtHtml += cardHtml;
            }
            debtList.innerHTML = debtHtml;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        } else {
            debtPreview.classList.add('hidden');
            debtList.innerHTML = '';
        }    }

    function openPaymentModal(id) {
        // Find transaction from the existing memory array
        const transaction = SearchEngine.getAll().find(x => x.id === id);
        if (!transaction) return;
        const transactionId = transaction.id;
        const invoiceNumber = transaction.invoice_number;
        const paymentMethod = transaction.payment_method;
        const amount = transaction.amount;
        const submitter = transaction.submitter || {};
        const specs = transaction.specs || {};
        const hasTelegram = transaction.submitter_has_telegram;

        const modal = document.getElementById('payment-modal');
        const inner = modal.querySelector('div');
        const loading = document.getElementById('payment-loading');
        const body = document.getElementById('payment-body');
        const submitBtn = document.getElementById('btnSubmitPayment');
        const submitBtnText = document.getElementById('btnSubmitPaymentText');

        // Form reset & display cleanups
        document.getElementById('payment-form').reset();
        document.getElementById('transfer-profile-alert').classList.add('hidden');
        document.getElementById('cash-fields').classList.add('hidden');
        document.getElementById('transfer-fields').classList.add('hidden');
        document.getElementById('pengajuan-invoice-fields').classList.add('hidden');
        document.getElementById('p-detail-container').classList.add('hidden');

        // Show loading, hide body
        loading.classList.remove('hidden');
        body.classList.add('hidden');

        // Reset Submit Button
        submitBtn.disabled = false;
        submitBtn.classList.remove('bg-slate-400', 'cursor-not-allowed', 'hover:bg-slate-400');
        submitBtn.classList.add('bg-cyan-600', 'hover:bg-cyan-700');
        submitBtnText.textContent = 'Upload & Simpan';

        const isPengajuan = transaction.type === 'pengajuan';

        // Check Telegram Registration (Only block for Cash/Transfer that requires tech confirmation)
        // For Pengajuan Invoice, we don't block because it's processed by management/vendor
        if (!hasTelegram && !isPengajuan) {
            submitBtn.disabled = true;
            submitBtn.classList.remove('bg-cyan-600', 'hover:bg-cyan-700');
            submitBtn.classList.add('bg-slate-400', 'cursor-not-allowed', 'hover:bg-slate-400');
            submitBtnText.textContent = 'Teknisi Belum Daftar Telegram';
            
            showToast(`<div class="flex items-start gap-2"><i data-lucide="bell-off" class="w-4 h-4 mt-0.5 flex-shrink-0 text-rose-600"></i><div><strong class="text-rose-800">Peringatan!</strong><br><span class="text-[11px] opacity-90 text-rose-700">Teknisi belum mendaftarkan Telegram. Pembayaran Cash/Transfer tidak dapat diproses hingga teknisi mendaftar via bot.</span></div></div>`, 'error');
        }

        let endpoint = '/api/v1/payment/cash/upload';
        
        // Populate display data
        document.getElementById('payment-modal-invoice').textContent = invoiceNumber;
        document.getElementById('payment-modal-amount').textContent = 'Rp ' + Number(amount).toLocaleString('id-ID');

        let form = document.getElementById('payment-form');
        form.querySelectorAll('.dyn-hidden').forEach(el => el.remove());
        
        // Add required hidden inputs
        const addHidden = (name, value) => {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = name; inp.value = value; inp.className = 'dyn-hidden';
            form.appendChild(inp);
        };

        addHidden('transaksi_id', transactionId);
        addHidden('upload_id', transaction.upload_id || ('txn_' + transactionId));
        addHidden('expected_nominal', amount); // used by transfer
        addHidden('teknisi_id', submitter.id || ''); // used by cash

        const bankInput = document.getElementById('transfer_bank');
        const nomorInput = document.getElementById('transfer_nomor');
        const namaInput = document.getElementById('transfer_nama');
        const paymentFileInput = document.getElementById('payment_file_input');
        const paymentLabel = document.getElementById('payment-modal-label');

        // Reset inputs and validation
        if (bankInput) { bankInput.required = false; bankInput.disabled = true; }
        if (nomorInput) { nomorInput.required = false; nomorInput.disabled = true; }
        if (namaInput) { namaInput.required = false; namaInput.disabled = true; }
        document.getElementById('cash_catatan').disabled = true;
        document.getElementById('p_catatan').disabled = true;
        ['p_ongkir', 'p_diskon_pengiriman', 'p_voucher_diskon', 'p_biaya_layanan_1', 'p_biaya_layanan_2'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.disabled = true;
        });

        paymentFileInput.name = 'file';

        if (isPengajuan) {
            endpoint = '/api/v1/payment/pengajuan/upload';
            document.getElementById('pengajuan-invoice-fields').classList.remove('hidden');
            document.getElementById('payment-modal-title').textContent = 'Upload Pembayaran Invoice';
            paymentFileInput.name = 'invoice_file';
            paymentFileInput.required = true;
            paymentLabel.innerHTML = 'Unggah Foto Invoice <span class="text-red-500">*</span>';
            
            // Enable Pengajuan fields
            document.getElementById('p_catatan').disabled = false;
            ['p_ongkir', 'p_diskon_pengiriman', 'p_voucher_diskon', 'p_biaya_layanan_1', 'p_biaya_layanan_2'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.disabled = false;
            });
        } else {
            document.getElementById('payment-modal-title').textContent = 'Upload Bukti Transfer/Cash';
            // Dynamic File Requirement (Mandatory for Transfer, Optional for Cash)
            const isTransfer = paymentMethod && paymentMethod.includes('transfer');
            paymentFileInput.required = isTransfer;
            if (paymentLabel) {
                paymentLabel.innerHTML = isTransfer 
                    ? 'Unggah Foto / Screenshot <span class="text-red-500">*</span>'
                    : 'Unggah Foto / Screenshot <span class="text-slate-400 font-normal">(Opsional)</span>';
            }

            if (isTransfer) {
                endpoint = '/api/v1/payment/transfer/upload';
                document.getElementById('transfer-fields').classList.remove('hidden');

                // Reset readonly state and styles first
                [bankInput, nomorInput, namaInput].forEach(el => {
                    el.disabled = false;
                    el.readOnly = false;
                    el.required = true; // Wajib diisi agar btnSubmit memvalidasi form
                    el.classList.remove('bg-slate-100', 'cursor-not-allowed');
                });

                // Fetch Saved Accounts for Technician
                if (paymentMethod === 'transfer_teknisi') {
                    const select = document.getElementById('saved_bank_account');
                    const container = document.getElementById('saved-accounts-container');
                    select.innerHTML = '<option value="">-- Pilih Rekening --</option>';
                    container.classList.add('hidden');

                    fetch(`/user-bank-accounts/${submitter.id}`)
                        .then(r => r.json())
                        .then(accounts => {
                            if (accounts.length > 0) {
                                container.classList.remove('hidden');
                                accounts.forEach(acc => {
                                    const opt = document.createElement('option');
                                    opt.value = JSON.stringify(acc);
                                    opt.textContent = `${acc.bank_name} - ${acc.account_number} (${acc.account_name})`;
                                    
                                    // Auto-select if matches specs (pre-selected by management)
                                    if (specs && specs.bank_account_id == acc.id) {
                                        opt.selected = true;
                                    }
                                    
                                    select.appendChild(opt);
                                });

                                // If an account was auto-selected, trigger the autofill display
                                if (select.value) {
                                    autoFillBankAccount(select);
                                }
                            }
                        });
                } else {
                    document.getElementById('saved-accounts-container').classList.add('hidden');
                }

                if (paymentMethod === 'transfer_teknisi') {
                    document.getElementById('transfer-method-badge').textContent = 'TRANSFER TEKNISI';

                    // WAJIB menggunakan Dropdown (Input manual dikunci)
                    [bankInput, nomorInput, namaInput].forEach(el => {
                        el.readOnly = true;
                        el.classList.add('bg-slate-100', 'cursor-not-allowed');
                    });

                    // Kosongkan secara default agar Admin WAJIB memilih rekening dari Dropdown
                    bankInput.value = '';
                    nomorInput.value = '';
                    namaInput.value = '';

                    document.getElementById('transfer-profile-alert').classList.remove('hidden');
                } else if (paymentMethod === 'transfer_penjual') {
                    document.getElementById('transfer-method-badge').textContent = 'TRANSFER PENJUAL (VENDOR)';
                    
                    // Set to Read-Only as per requirement
                    [bankInput, nomorInput, namaInput].forEach(el => {
                        el.readOnly = true;
                        el.classList.add('bg-slate-100', 'cursor-not-allowed');
                    });

                    bankInput.value = specs.bank_name || '';
                    nomorInput.value = specs.account_number || '';
                    namaInput.value = specs.account_name || '';
                }
            } else {
                document.getElementById('cash-fields').classList.remove('hidden');
                document.getElementById('cash_catatan').disabled = false;
            }
        }

        form.action = endpoint;

        // Show modal and start loading animation
        modal.classList.remove('hidden');
        requestAnimationFrame(() => {
            modal.setAttribute('aria-hidden', 'false');
            modal.classList.remove('opacity-0');
            inner.classList.remove('scale-95');
            inner.classList.add('scale-100');
        });

        // Fetch transaction details and show form
        fetch(`/transactions/${id}/detail-json`)
            .then(r => r.json())
            .then(d => {
                renderPaymentModalDetails(d);
                loading.classList.add('hidden');
                body.classList.remove('hidden');
            })
            .catch(err => {
                console.error(err);
                loading.innerHTML = '<p class="text-red-500 text-sm font-bold">Gagal memuat data. Coba lagi.</p>';
            });
    }

    function autoFillBankAccount(select) {
        if (!select.value) {
            document.getElementById('transfer_bank').value = '';
            document.getElementById('transfer_nomor').value = '';
            document.getElementById('transfer_nama').value = '';
            return;
        }
        const acc = JSON.parse(select.value);
        document.getElementById('transfer_bank').value = acc.bank_name;
        document.getElementById('transfer_nomor').value = acc.account_number;
        document.getElementById('transfer_nama').value = acc.account_name;
    }

    function closePaymentModal() {
        const modal = document.getElementById('payment-modal');
        const inner = modal.querySelector('div');
        if (document.activeElement && modal.contains(document.activeElement)) document.activeElement.blur();
        modal.classList.add('opacity-0');
        inner.classList.remove('scale-100');
        inner.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            document.getElementById('payment-form').reset();
            document.querySelectorAll('.dyn-hidden').forEach(e => e.remove());
        }, 300);
    }
    document.getElementById('payment-modal').addEventListener('click', e => {
        if (e.target.id === 'payment-modal') closePaymentModal();
    });

    // ─── INIT AJAX FORMS ────────────────────────────
    function bindAjaxForm(formId, closeModalFunc, successMsg) {
        const form = document.getElementById(formId);
        if (!form) return;
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            const submitText = submitBtn.querySelector('span') || submitBtn;
            const loader = submitBtn.querySelector('.animate-spin');
            
            const originalText = submitText.textContent;
            submitBtn.disabled = true;
            if (loader) {
                submitText.classList.add('opacity-0');
                loader.classList.remove('hidden');
            } else {
                submitText.textContent = 'Memproses...';
            }
            if (typeof NProgress !== 'undefined') NProgress.start();

            // Prepare Data
            const formData = new FormData(this);

            // Clean up nominal inputs before sending
            this.querySelectorAll('.nominal-input').forEach(inp => {
                if (inp.name && formData.has(inp.name)) {
                    // Use unformatNumber to remove non-digit characters
                    const rawValue = inp.value ? String(inp.value).replace(/\D/g, "") : "0";
                    formData.set(inp.name, rawValue || "0");
                }
            });

            // Append _method=PATCH if it's override/force-approve targeting standard updates? No, the PDF API might be POST. 
            // We'll let the HTML form method rule.

            fetch(this.action, {
                method: this.method || 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData // auto handles multipart if file exists
            })
            .then(async r => {
                const data = await r.json().catch(() => ({}));
                if (!r.ok) {
                    const error = new Error(data.message || 'Gagal memproses form');
                    error.errors = data.errors;
                    throw error;
                }
                return data;
            })
            .then(data => {
                closeModalFunc();
                showToast(`<div class="flex items-start gap-2"><i data-lucide="check-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-emerald-600"></i><div><strong class="text-emerald-800">Berhasil!</strong><br><span class="text-[11px] opacity-90 text-emerald-700">${successMsg || data.message || 'Aksi berhasil'}</span></div></div>`, 'success');
                if (data.transaction) {
                    SearchEngine.updateTransaction(data.transaction);
                } else {
                    SearchEngine.init();
                }
            })
            .catch(err => {
                console.error(err);
                let errorHtml = err.message || 'Terjadi kesalahan sistem.';
                if (err.errors) {
                    const errorList = Object.values(err.errors).flat();
                    if (errorList.length > 0) {
                        errorHtml = errorList.join('<br>');
                    }
                }
                showToast(`<div class="flex items-start gap-2"><i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-red-600"></i><div><strong class="text-red-800">Gagal!</strong><br><span class="text-[11px] opacity-90 text-red-700">${errorHtml}</span></div></div>`, 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                if (loader) {
                    submitText.classList.remove('opacity-0');
                    loader.classList.add('hidden');
                } else {
                    submitText.textContent = originalText;
                }
                if(typeof NProgress !== 'undefined') NProgress.done();
            });
        });
    }

    bindAjaxForm('override-form', closeOverrideModal, 'Override berhasil diajukan.');
    bindAjaxForm('force-approve-form', closeForceApproveModal, 'Transaksi berhasil di Force Approve.');
    bindAjaxForm('payment-form', closePaymentModal, 'Bukti Pembayaran berhasil diunggah.');

    // ─── TECHNICIAN CASH CONFIRMATION ───────────────
    let isConfirmingCash = false;
    window.confirmCashPayment = function(id, action) {
        if (isConfirmingCash) return;
        
        const allTx = SearchEngine.getAll();
        if (!allTx || allTx.length === 0) return;
        const t = allTx.find(x => x.id === id);
        if (!t) return;
        
        let msg = action === 'terima' ? `Konfirmasi terima uang CASH untuk invoice ${t.invoice_number}?` : `Tolak penerimaan CASH untuk invoice ${t.invoice_number} karena tidak sesuai?`;
        if (!confirm(msg)) return;

        let catatan = '';
        if (action === 'tolak') {
            catatan = prompt("Harap masukkan alasan penolakan:") || '';
        } else {
            catatan = prompt("Catatan (Opsional):") || '';
        }

        isConfirmingCash = true;
        if(typeof NProgress !== 'undefined') NProgress.start();

        const formData = new FormData();
        formData.append('transaksi_id', t.id);
        formData.append('upload_id', t.upload_id || `txn_${t.id}`);
        formData.append('teknisi_id', t.submitter?.id || '');
        formData.append('action', action);
        formData.append('catatan', catatan);

        fetch('/api/v1/payment/cash/konfirmasi', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(async r => {
            const data = await r.json().catch(() => ({}));
            if (!r.ok) throw new Error(data.message || 'Gagal mengirim konfirmasi.');
            return data;
        })
        .then(data => {
            showToast(`<div class="flex items-start gap-2"><i data-lucide="check-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-emerald-600"></i><div><strong class="text-emerald-800">Berhasil!</strong><br><span class="text-[11px] opacity-90 text-emerald-700">${data.message || 'Konfirmasi berhasil dikirim.'}</span></div></div>`, 'success');
            SearchEngine.init(); // Refresh grid
        })
        .catch(err => {
            console.error(err);
            showToast(`<div class="flex items-start gap-2"><i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-red-600"></i><div><strong class="text-red-800">Gagal!</strong><br><span class="text-[11px] opacity-90 text-red-700">${err.message || 'Terjadi kesalahan sistem.'}</span></div></div>`, 'error');
        })
        .finally(() => {
            isConfirmingCash = false;
            if(typeof NProgress !== 'undefined') NProgress.done();
        });
    }


    // Convert reject form to AJAX (no page reload)
    const rejectForm = document.getElementById('reject-form');
    if (rejectForm) {
        rejectForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const id    = this.action.split('/').at(-2); // extract transaction id from URL
            const reason = this.querySelector('textarea[name="rejection_reason"]')?.value || '';
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) { submitBtn.disabled = true; }
            if (typeof NProgress !== 'undefined') NProgress.start();

            fetch(`/transactions/${id}/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ status: 'rejected', rejection_reason: reason, _method: 'PATCH' }),
            })
            .then(r => r.json().catch(() => ({})))
            .then(data => {
                closeRejectModal();
                showToast(`<div class="flex items-start gap-2"><i data-lucide="check-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-emerald-600"></i><div><strong class="text-emerald-800">Berhasil!</strong><br><span class="text-[11px] opacity-90 text-emerald-700">Transaksi berhasil ditolak.</span></div></div>`, 'success');
                if (data.transaction) {
                    SearchEngine.updateTransaction(data.transaction);
                } else {
                    SearchEngine.init();
                }
            })
            .catch(err => {
                console.error(err);
                showToast(`<div class="flex items-start gap-2"><i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-red-600"></i><div><strong class="text-red-800">Gagal!</strong><br><span class="text-[11px] opacity-90 text-red-700">Gagal menolak transaksi. Coba lagi.</span></div></div>`, 'error');
                if (submitBtn) { submitBtn.disabled = false; }
            })
            .finally(() => {
                if(typeof NProgress !== 'undefined') NProgress.done();
            });
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // REALTIME EVENT HANDLERS - IMPROVED
    // ═══════════════════════════════════════════════════════════════

    let refreshTimer = null;
    const REFRESH_DEBOUNCE_MS = 500;

    function debouncedRefresh(eventName, data) {
        console.log(`🔔 [REVERB] Event received: ${eventName}`, data);
        
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(() => {
            console.log(`🔄 [REVERB] Refreshing grid after ${eventName}...`);
            SearchEngine.init().then(() => {
                console.log(`✅ [REVERB] Grid refreshed successfully`);
                
                // Re-init Lucide icons after DOM update
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }).catch(err => {
                console.error(`❌ [REVERB] Grid refresh failed:`, err);
            });
        }, REFRESH_DEBOUNCE_MS);
    }

    // ✅ Handler untuk TransactionUpdated event
    window.handleRealtimeTransactionUpdate = function(transaction) {
        console.log('📝 [REVERB] Transaction Updated:', transaction);
        if (transaction && transaction.id) {
            SearchEngine.updateTransaction(transaction);
        } else {
            debouncedRefresh('TransactionUpdated', transaction);
        }
    };

    // ✅ Handler untuk TransactionCreated event
    window.handleRealtimeTransactionCreation = function(transaction) {
        console.log('🆕 [REVERB] Transaction Created:', transaction);
        if (transaction && transaction.id) {
            SearchEngine.addTransaction(transaction);
        } else {
            debouncedRefresh('TransactionCreated', transaction);
        }
    };

    // ✅ Handler khusus untuk OCR status updates
    window.handleRealtimeOcrStatusUpdate = function(data) {
        console.log('🤖 [REVERB] OCR Status Updated:', data);
        
        // Immediate visual feedback
        const badge = document.querySelector(`.ai-status-badge[data-upload-id="${data.upload_id}"]`);
        if (badge) {
            badge.classList.add('opacity-50', 'animate-pulse');
        }
        
        // Then refresh grid
        if (data.transaction && data.transaction.id) {
            SearchEngine.updateTransaction(data.transaction);
        } else {
            debouncedRefresh('OcrStatusUpdated', data);
        }
    };


</script>
@endpush