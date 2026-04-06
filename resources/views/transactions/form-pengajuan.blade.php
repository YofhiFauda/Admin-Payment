@extends('layouts.app')
@section('page-title', 'Form Pengajuan Beli')
@section('content')
    {{-- Form Container --}}
    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-6 md:p-8 lg:p-10">

        {{-- Header --}}
        <div class="mb-8 md:mb-10 flex items-center gap-4">
            <a href="{{ route('transactions.create') }}" 
               class="w-8 h-8 flex items-center justify-center rounded-full border border-slate-200 text-slate-400 hover:text-emerald-600 hover:border-emerald-200 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <h2 class="text-lg md:text-xl font-extrabold text-slate-800">Form Pengajuan Beli</h2>
                <p class="text-xs md:text-sm text-slate-400 mt-1">Lengkapi data barang/jasa yang ingin diajukan</p>
            </div>
        </div>

        <form method="POST" action="{{ route('pengajuan.store') }}" id="pengajuan-form" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="type" value="pengajuan">
            
            {{-- Container untuk input tersembunyi distribusi (PENTING) --}}
            <div id="distribution-hidden-inputs"></div>

            {{-- 1. FOTO REFERENSI --}}
            <div class="mb-8 md:mb-10">
                <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-3 tracking-wider">
                    Foto Referensi 
                    @if(in_array(auth()->user()->role, ['owner', 'atasan']))
                        <span class="text-slate-400 font-normal ml-1">(Opsional)</span>
                    @else
                        <span class="text-red-500 font-normal ml-1">(Wajib)</span>
                    @endif
                </label>
                
                <div class="relative border-2 border-dashed border-slate-200 rounded-2xl p-6 bg-slate-50/50 flex flex-col items-center justify-center text-slate-500 max-w-6xl mx-auto hover:bg-slate-100 hover:border-emerald-300 transition-colors cursor-pointer min-h-[200px]" id="photo-upload-container">
                    {{-- Placeholder --}}
                    <div id="photo-placeholder" class="flex flex-col items-center justify-center">
                        <i data-lucide="upload-cloud" class="w-10 h-10 mb-2 text-slate-400"></i>
                        <span class="text-sm font-bold text-slate-700 mb-1" id="photo-name-display">Pilih Foto (Klik atau Drag)</span>
                        <span class="text-[10px] text-slate-400">Format: JPG, PNG. Maksimal 1MB.</span>
                    </div>

                    {{-- Preview --}}
                    <div id="photo-preview-wrapper" class="hidden absolute inset-0 w-full h-full rounded-2xl overflow-hidden group">
                        <img id="photo-preview-img" src="" class="w-full h-full object-contain bg-slate-100" alt="Preview">
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center text-white backdrop-blur-[2px]">
                            <i data-lucide="refresh-cw" class="w-8 h-8 mb-2"></i>
                            <span class="text-xs font-black uppercase tracking-widest">Ganti Foto</span>
                        </div>
                    </div>

                    <input type="file" name="file" id="reference_photo" accept="image/jpeg,image/png,image/jpg" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                        {{ in_array(auth()->user()->role, ['owner', 'atasan']) ? '' : 'required' }}>
                </div>
            </div>

            {{-- ══════════════════════════════════ --}}
            {{-- 2. DAFTAR BARANG (DYNAMIC) --}}
            {{-- ══════════════════════════════════ --}}
            <div class="mb-8 md:mb-10">
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider">
                            Daftar Barang
                        </label>
                        <p class="text-[10px] md:text-xs text-slate-400 mt-1">Tambahkan satu atau lebih barang yang diajukan</p>
                    </div>
                    <button type="button" id="btn-add-item" class="flex items-center justify-center gap-2 bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white border border-emerald-200 hover:border-emerald-600 px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-sm">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Tambah Barang
                    </button>
                </div>

                <div id="items-container" class="space-y-6">
                    {{-- Item Cards will be injected here via JS --}}
                </div>
                
                {{-- Global Total Estimate (Replaces the specific sidebar) --}}
                <div class="mt-6 bg-slate-50 p-5 md:p-6 rounded-2xl border border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <span class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Estimasi Biaya Keseluruhan</span>
                        <p class="text-xs text-slate-500">Total estimasi dari seluruh barang di atas</p>
                    </div>
                    <div class="text-left sm:text-right w-full sm:w-auto bg-white border border-slate-200 px-6 py-4 rounded-xl shadow-sm">
                        <div id="total-estimate-global" class="text-xl md:text-2xl font-black text-emerald-600">Rp 0</div>
                        <input type="hidden" name="estimated_price" id="form-total-estimated-price" value="0">
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════ --}}
            {{-- 3. DISTRIBUSI CABANG (FULL WIDTH CARD) --}}
            {{-- ══════════════════════════════════ --}}
            <div class="bg-white border border-slate-100 rounded-3xl p-6 md:p-8 mb-8 md:mb-10 shadow-sm">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1 tracking-wider">Pembagian Cabang</label>
                        <p class="text-[10px] md:text-xs text-slate-400">Pilih cabang yang akan menanggung biaya ini</p>
                    </div>
                    
                    {{-- Metode Distribusi --}}
                    <div class="flex bg-slate-100 rounded-xl p-1 text-[10px] md:text-xs font-bold self-start md:self-center">
                        <button type="button" data-method="equal" class="method-btn px-4 py-2 rounded-lg bg-white shadow-sm text-slate-700 transition-all">Bagi Rata</button>
                        <button type="button" data-method="percent" class="method-btn px-4 py-2 rounded-lg text-slate-500 transition-all hover:text-slate-700">Persentase</button>
                        <button type="button" data-method="manual" class="method-btn px-4 py-2 rounded-lg text-slate-500 transition-all hover:text-slate-700">Manual</button>
                    </div>
                </div>

                {{-- Branch Pills --}}
                <div class="flex flex-wrap gap-2 md:gap-3 mb-8" id="branch-pills-container">
                    @foreach($branches as $branch)
                        <button type="button"
                            data-id="{{ $branch->id }}"
                            data-name="{{ $branch->name }}"
                            class="branch-pill px-4 py-2.5 rounded-full text-xs font-bold border border-slate-200 bg-white text-slate-600 hover:bg-teal-50 hover:border-teal-300 transition-all cursor-pointer">
                            {{ $branch->name }}
                        </button>
                    @endforeach
                </div>

                {{-- Distribution Detail Card --}}
                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-5 md:p-6">
                    <div id="distribution-list" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Dynamic Content via JS --}}
                    </div>
                    <div id="percent-warning" class="text-red-500 text-xs mt-4 hidden font-bold flex items-center gap-1.5">
                        <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                        ⚠ Total persen harus 100%
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════ --}}
            {{-- SUMMARY BILLING (FULL WIDTH BLACK CARD) --}}
            {{-- ══════════════════════════════════ --}}
            <div id="summary-billing-section" class="bg-[#1a1c23] rounded-[2rem] p-6 md:p-8 lg:p-10 text-white relative overflow-hidden shadow-xl hidden">
                <div class="absolute -right-20 -top-20 w-64 h-64 bg-white/[0.02] rounded-full pointer-events-none"></div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 mb-8 md:mb-10 relative z-10">
                    {{-- Left Side: Total --}}
                    <div>
                        <span class="text-[9px] md:text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Total Pengajuan</span>
                        <div class="text-3xl md:text-4xl lg:text-5xl font-black text-emerald-400 mb-4 md:mb-6 tracking-tight" id="summary-total">Rp 0</div>
                        <div class="flex flex-wrap gap-2">
                            <span class="bg-white/10 text-slate-300 px-3 py-1.5 rounded-full text-[9px] md:text-[10px] font-bold uppercase tracking-wider border border-white/5" id="summary-method">Metode: -</span>
                            <span class="bg-white/10 text-slate-300 px-3 py-1.5 rounded-full text-[9px] md:text-[10px] font-bold uppercase tracking-wider border border-white/5" id="summary-branch-count">0 Cabang</span>
                        </div>
                    </div>
                    
                    {{-- Right Side: Details --}}
                    <div class="lg:border-l border-white/10 lg:pl-12 flex flex-col justify-center">
                        <span class="text-[9px] md:text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-4">Rincian Distribusi Cabang</span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3 max-h-48 overflow-y-auto pr-2 custom-scrollbar" id="summary-branches-list">
                            <!-- Dynamic Content -->
                        </div>
                    </div>
                </div>

                {{-- Submit Button --}}
                <button type="submit" id="summary-submit" disabled
                    class="w-full relative z-10 bg-emerald-500 hover:bg-emerald-400 disabled:bg-slate-700 disabled:text-slate-500 text-white font-bold py-4 md:py-5 rounded-2xl transition-all shadow-[0_8px_20px_-6px_rgba(16,185,129,0.4)] disabled:shadow-none text-xs md:text-sm uppercase tracking-wider cursor-pointer disabled:cursor-not-allowed flex items-center justify-center gap-2">
                    <span id="submit-text">Kirim Pengajuan</span>
                    <svg id="submit-spinner" class="animate-spin h-5 w-5 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                </button>
            </div>

            @if($errors->any())
                <div class="mt-4 bg-red-50 border border-red-100 rounded-xl p-4 hidden" id="fallback-error-msg">
                    <div class="flex items-start gap-2">
                        <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 mt-0.5 shrink-0"></i>
                        <div class="text-xs text-red-600 font-medium">
                            @foreach($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </form>
    </div>

    {{-- Toast Container --}}
    <div id="toast-container" class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 pointer-events-none"></div>

@push('modals')
    {{-- ══════════════════════════════════════════════════ --}}
    {{-- IMAGE VIEWER MODAL                                --}}
    {{-- hidden → flex saat dibuka via JS                 --}}
    {{-- ══════════════════════════════════════════════════ --}}
    <div id="image-viewer"
         class="fixed inset-0 bg-black/90 backdrop-blur-md hidden items-center justify-center z-[9999] p-4 sm:p-10 overscroll-contain"
         role="dialog" aria-modal="true" aria-label="Preview foto referensi">

        {{-- Container margin sisi 4 --}}
        <div class="w-full h-full max-w-4xl bg-white rounded-2xl flex flex-col p-4 sm:p-8 shadow-2xl relative overflow-hidden" id="viewer-card">

            {{-- Header & Close Button --}}
            <div class="flex justify-between items-center shrink-0 mb-6 border-b border-slate-100 pb-4">
                <div>
                    <h3 class="text-sm sm:text-base font-black text-slate-800 uppercase tracking-widest" id="viewer-header-title">PREVIEW FOTO</h3>
                    <p class="text-[10px] text-slate-400 font-bold mt-1 uppercase tracking-wider" id="viewer-title">Klik di luar gambar ruang ini atau X untuk menutup</p>
                </div>
                <button id="close-viewer"
                        type="button"
                        class="w-10 h-10 sm:w-12 sm:h-12 flex items-center justify-center rounded-2xl bg-slate-100 hover:bg-red-50 text-slate-500 hover:text-red-500 transition-all active:scale-95"
                        aria-label="Tutup preview">
                    <i data-lucide="x" class="w-5 h-5 sm:w-6 sm:h-6"></i>
                </button>
            </div>

            {{-- Gambar Wrapper dengan Background Grid/Dots --}}
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

    {{-- ITEM TEMPLATE FOR JS --}}
    <template id="item-template">
        <div class="item-card bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow" data-index="__INDEX__">
            {{-- Header (Clickable for expand/collapse) --}}
            <div class="item-header bg-slate-50 px-5 py-4 cursor-pointer flex items-center justify-between border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 font-bold text-xs item-number">__NUM__</div>
                    <div>
                        <h4 class="font-bold text-slate-700 text-sm item-title">Barang Baru</h4>
                        <p class="text-[10px] item-subtitle text-slate-400">Rp 0 x 1</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="btn-remove-item text-red-500 hover:bg-red-50 p-2 rounded-lg transition-colors hidden" title="Hapus Barang">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-slate-400 transition-transform duration-200 icon-collapse"></i>
                </div>
            </div>

            {{-- Body (Collapsible) --}}
            <div class="item-body p-5 md:p-6 space-y-8">
                
                {{-- 2. INFORMASI BARANG --}}
                <div>
                    <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-4 tracking-wider">
                        Informasi Barang / Jasa
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Nama Barang/Jasa *</label>
                            <input type="text" name="items[__INDEX__][customer]" required
                                class="input-customer w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all"
                                placeholder="Contoh: Router Mikrotik">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Informasi Vendor</label>
                            <input type="text" name="items[__INDEX__][vendor]"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all"
                                placeholder="Contoh: Toko Komputer Jaya">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Link Barang/Referensi</label>
                            <input type="url" name="items[__INDEX__][link]"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all"
                                placeholder="https://tokopedia.link/...">
                        </div>
                    </div>
                </div>

                {{-- 3. SPESIFIKASI --}}
                <div class="mt-8">
                    <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-4 tracking-wider">Spesifikasi Barang</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 md:gap-6">
                        <div><label class="block text-[10px] md:text-[11px] mb-2 font-bold text-slate-500 uppercase tracking-wider">Merk</label><input type="text" name="items[__INDEX__][specs][merk]" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-400 transition-all" placeholder="Contoh: Xpon CDATA"></div>
                        <div><label class="block text-[10px] md:text-[11px] mb-2 font-bold text-slate-500 uppercase tracking-wider">Tipe / Seri</label><input type="text" name="items[__INDEX__][specs][tipe]" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-400 transition-all" placeholder="Contoh: FD512XW-R460"></div>
                        <div><label class="block text-[10px] md:text-[11px] mb-2 font-bold text-slate-500 uppercase tracking-wider">Ukuran</label><input type="text" name="items[__INDEX__][specs][ukuran]" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-400 transition-all" placeholder="Contoh: 30x30"></div>
                        <div><label class="block text-[10px] md:text-[11px] mb-2 font-bold text-slate-500 uppercase tracking-wider">Warna</label><input type="text" name="items[__INDEX__][specs][warna]" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-400 transition-all" placeholder="Contoh: Putih"></div>
                    </div>
                </div>

                {{-- 4 & 5. ALASAN & HARGA --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
                    
                    {{-- Alasan Pembelian --}}
                    <div>
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-3 tracking-wider">Alasan Pembelian / Kategori *</label>
                        <div class="relative">
                            <select name="items[__INDEX__][category]" required class="input-reason w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all appearance-none">
                                <option value="">— Pilih alasan —</option>
                                @foreach($pengajuanCategories as $cat)
                                    <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                        <div class="mt-4 keterangan-container">
                            <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">
                                Keterangan <span class="keterangan-required text-red-500 hidden">*</span>
                            </label>
                            <textarea name="items[__INDEX__][description]" rows="2"
                                class="input-desc w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all"
                                placeholder="Tambahkan detail/alasan tambahan..."></textarea>
                        </div>
                    </div>

                    {{-- Estimasi Biaya --}}
                    <div class="bg-slate-50 p-4 md:p-5 rounded-xl border border-slate-100">
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-4 tracking-wider">Estimasi Biaya</label>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Estimasi Harga Satuan *</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">Rp</span>
                                    <input type="text" required class="input-price-display w-full bg-white border border-slate-200 rounded-xl pl-12 pr-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all" value="">
                                    <input type="hidden" name="items[__INDEX__][estimated_price]" class="input-price-hidden" value="0">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Jumlah *</label>
                                <input type="number" name="items[__INDEX__][quantity]" value="1" required min="1" class="input-qty w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all">
                            </div>
                            <div class="pt-2 border-t border-slate-200 flex justify-between items-center">
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Subtotal</span>
                                <span class="text-sm md:text-base font-bold text-emerald-600 item-subtotal">Rp 0</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </template>

    <script>
        lucide.createIcons();
    </script>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        
        // ═══════════════════════════════════════
        // TOAST NOTIFICATIONS
        // ═══════════════════════════════════════
        function showToast(message, type = 'error') {
            const container = document.getElementById('toast-container');
            if (!container) return;

            let bgColors = 'bg-red-50 border-red-200 text-red-800';
            let accentClasses = 'bg-red-500';
            let iconBody = '<i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-red-500"></i>';

            if (type === 'success') {
                bgColors = 'bg-emerald-50 border-emerald-200 text-emerald-800';
                accentClasses = 'bg-emerald-500';
                iconBody = '<i data-lucide="check-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-emerald-500"></i>';
            } else if (type === 'info') {
                bgColors = 'bg-blue-50 border-blue-200 text-blue-800';
                accentClasses = 'bg-blue-500';
                iconBody = '<i data-lucide="info" class="w-4 h-4 mt-0.5 flex-shrink-0 text-blue-500"></i>';
            }

            const toast = document.createElement('div');
            toast.className = `relative flex items-start gap-3 p-4 rounded-xl shadow-lg border text-sm font-bold transform transition-all duration-300 translate-x-[120%] opacity-0 overflow-hidden ${bgColors}`;
            toast.innerHTML = `
                <div class="absolute left-0 top-0 bottom-0 w-1 ${accentClasses}"></div>
                ${iconBody}
                <div class="flex-1 right-0 text-xs">${message}</div>
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
            }, 5000);
        }

        // Show validation errors via toast if they exist
        @if($errors->any())
            @foreach($errors->all() as $error)
                setTimeout(() => {
                    showToast("{{ $error }}", 'error');
                }, {{ $loop->index * 300 }}); // stagger multiple toasts
            @endforeach
        @endif

        // ═══════════════════════════════════════
        // IMAGE VIEWER MODAL
        // ═══════════════════════════════════════
        const imageViewer  = document.getElementById('image-viewer');
        const viewerImage  = document.getElementById('viewer-image');
        const closeViewer  = document.getElementById('close-viewer');
        const refWrapper   = document.getElementById('ref-photo-wrapper');

        function openViewer(src) {
            viewerImage.src = src;
            imageViewer.classList.remove('hidden');
            imageViewer.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeViewerFn() {
            imageViewer.classList.add('hidden');
            imageViewer.classList.remove('flex');
            document.body.style.overflow = '';
            setTimeout(() => { viewerImage.src = ''; }, 200);
        }

        if (refWrapper) {
            refWrapper.addEventListener('click', function () {
                const img = this.querySelector('img');
                if (img) openViewer(img.src);
            });
        }
        if (closeViewer) { closeViewer.addEventListener('click', function(e) { e.stopPropagation(); closeViewerFn(); }); }
        if (imageViewer) { imageViewer.addEventListener('click', function(e) { if (e.target === imageViewer) closeViewerFn(); }); }
        document.addEventListener('keydown', function(e) { if (e.key === 'Escape' && !imageViewer.classList.contains('hidden')) closeViewerFn(); });

        // ═══════════════════════════════════════
        // PHOTO UPLOAD UI LOGIC
        // ═══════════════════════════════════════
        const photoContainer = document.getElementById('photo-upload-container');
        const photoInput = document.getElementById('reference_photo');
        const photoDisplay = document.getElementById('photo-name-display');
        const photoPlaceholder = document.getElementById('photo-placeholder');
        const previewWrapper = document.getElementById('photo-preview-wrapper');
        const previewImg = document.getElementById('photo-preview-img');

        if (photoContainer && photoInput) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                photoContainer.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                photoContainer.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                photoContainer.addEventListener(eventName, unhighlight, false);
            });

            function highlight(e) {
                photoContainer.classList.add('bg-slate-100', 'border-emerald-400');
            }

            function unhighlight(e) {
                photoContainer.classList.remove('bg-slate-100', 'border-emerald-400');
            }

            photoContainer.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                let dt = e.dataTransfer;
                let files = dt.files;
                if (files.length) {
                    photoInput.files = files;
                    updatePhotoDisplay();
                }
            }

            photoInput.addEventListener('change', updatePhotoDisplay);

            function updatePhotoDisplay() {
                if (photoInput.files && photoInput.files[0]) {
                    const file = photoInput.files[0];
                    
                    // Validasi ukuran (1MB)
                    if (file.size > 1024 * 1024) {
                        showToast('Ukuran file terlalu besar (Maks. 1MB)', 'error');
                        photoInput.value = '';
                        resetPhotoUI();
                        return;
                    }

                    photoDisplay.textContent = file.name;
                    photoDisplay.classList.remove('text-slate-700');
                    photoDisplay.classList.add('text-emerald-600');
                    photoContainer.querySelector('i').classList.add('text-emerald-500');
                    photoContainer.querySelector('i').classList.remove('text-slate-400', 'opacity-50');

                    // Create preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        previewWrapper.classList.remove('hidden');
                        photoPlaceholder.classList.add('hidden');
                        photoContainer.classList.remove('border-dashed');
                        photoContainer.classList.add('border-solid', 'border-emerald-200');
                    }
                    reader.readAsDataURL(file);

                } else {
                    resetPhotoUI();
                }
            }

            function resetPhotoUI() {
                photoDisplay.textContent = 'Pilih Foto (Klik atau Drag)';
                photoDisplay.classList.add('text-slate-700');
                photoDisplay.classList.remove('text-emerald-600');
                const icon = photoPlaceholder.querySelector('i');
                if(icon) {
                    icon.classList.remove('text-emerald-500');
                    icon.classList.add('text-slate-400');
                }
                
                previewWrapper.classList.add('hidden');
                photoPlaceholder.classList.remove('hidden');
                photoContainer.classList.add('border-dashed');
                photoContainer.classList.remove('border-solid', 'border-emerald-200');
                previewImg.src = '';
            }
        }

        // ═══════════════════════════════════════
        // DYNAMIC ITEMS LOGIC
        // ═══════════════════════════════════════
        const itemsContainer = document.getElementById('items-container');
        const btnAddItem = document.getElementById('btn-add-item');
        const itemTemplate = document.getElementById('item-template').innerHTML;
        const formTotalInput = document.getElementById('form-total-estimated-price');
        const globalTotalDisplay = document.getElementById('total-estimate-global');
        
        // ═══════════════════════════════════════
        // DISTRIBUTION VARIABLES & ELEMENTS
        // ═══════════════════════════════════════
        const branchPills       = document.querySelectorAll('.branch-pill');
        const methodBtns        = document.querySelectorAll('.method-btn');
        const distributionList  = document.getElementById('distribution-list');
        const hiddenInputsContainer = document.getElementById('distribution-hidden-inputs');
        const percentWarning    = document.getElementById('percent-warning');
        
        const summarySection    = document.getElementById('summary-billing-section');
        const summaryTotal      = document.getElementById('summary-total');
        const summaryMethod     = document.getElementById('summary-method');
        const summaryBranchCount = document.getElementById('summary-branch-count');
        const summaryBranchesList = document.getElementById('summary-branches-list');
        const summarySubmit     = document.getElementById('summary-submit');
        
        let selectedBranches = [];
        let currentMethod    = 'equal';
        let itemCounter = 0;

        // Helper functions
        function parseRupiah(str) { return parseInt((str || '').toString().replace(/\D/g, '') || '0'); }
        function formatRupiah(num) { return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }

        // Core dynamic logic
        function updateGlobalTotal() {
            let total = 0;
            const itemCards = itemsContainer.querySelectorAll('.item-card');
            
            itemCards.forEach(card => {
                const priceInput = card.querySelector('.input-price-hidden');
                const qtyInput = card.querySelector('.input-qty');
                const subtotalDisplay = card.querySelector('.item-subtotal');
                const titleDisplay = card.querySelector('.item-title');
                const subtitleDisplay = card.querySelector('.item-subtitle');
                const customerInput = card.querySelector('.input-customer');
                
                const price = parseRupiah(priceInput.value);
                const qty = parseInt(qtyInput.value) || 1;
                const subtotal = price * qty;
                total += subtotal;
                
                if(subtotalDisplay) subtotalDisplay.textContent = 'Rp ' + formatRupiah(subtotal);
                
                if(titleDisplay && customerInput) {
                    titleDisplay.textContent = customerInput.value || 'Barang Baru';
                }
                if(subtitleDisplay) {
                    subtitleDisplay.textContent = `Rp ${formatRupiah(price)} x ${qty}`;
                }
            });

            formTotalInput.value = total;
            globalTotalDisplay.textContent = 'Rp ' + formatRupiah(total);
            
            renderDistribution();
        }

        function addItem() {
            const tempDiv = document.createElement('div');
            let html = itemTemplate.replace(/__INDEX__/g, itemCounter).replace(/__NUM__/g, itemCounter + 1);
            tempDiv.innerHTML = html;
            const newCard = tempDiv.firstElementChild;
            
            setupItemCardEvents(newCard);
            
            // Expand newly added card and collapse others
            const existingCards = itemsContainer.querySelectorAll('.item-card');
            existingCards.forEach(card => {
                const body = card.querySelector('.item-body');
                const icon = card.querySelector('.icon-collapse');
                if(body && !body.classList.contains('hidden')) {
                    body.classList.add('hidden');
                    if(icon) icon.classList.add('rotate-180');
                }
            });

            itemsContainer.appendChild(newCard);
            itemCounter++;
            
            updateRemoveButtons();
            
            lucide.createIcons({ root: newCard });
            
            setTimeout(() => {
                const input = newCard.querySelector('.input-customer');
                if(input) input.focus();
                
                if (itemCounter > 1) {
                    newCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 100);
        }

        function setupItemCardEvents(card) {
            const priceDisp = card.querySelector('.input-price-display');
            const priceHid = card.querySelector('.input-price-hidden');
            const qtyInput = card.querySelector('.input-qty');
            const customerInput = card.querySelector('.input-customer');
            
            if(priceDisp) {
                priceDisp.addEventListener('input', function() {
                    let raw = parseRupiah(this.value);
                    this.value = raw > 0 ? formatRupiah(raw) : '';
                    priceHid.value = raw;
                    updateGlobalTotal();
                });
            }
            if(qtyInput) qtyInput.addEventListener('input', updateGlobalTotal);
            if(customerInput) customerInput.addEventListener('input', updateGlobalTotal);
            
            const reasonSelect = card.querySelector('.input-reason');
            const descInput = card.querySelector('.input-desc');
            const reqSpan = card.querySelector('.keterangan-required');
            
            if(reasonSelect && descInput && reqSpan) {
                reasonSelect.addEventListener('change', function() {
                    if (this.value === 'lainnya') {
                        descInput.required = true;
                        reqSpan.classList.remove('hidden');
                    } else {
                        descInput.required = false;
                        reqSpan.classList.add('hidden');
                    }
                });
            }

            const header = card.querySelector('.item-header');
            const body = card.querySelector('.item-body');
            const iconCollapse = card.querySelector('.icon-collapse');
            
            if(header && body && iconCollapse) {
                header.addEventListener('click', function(e) {
                    if(e.target.closest('.btn-remove-item')) return;
                    
                    body.classList.toggle('hidden');
                    const isHidden = body.classList.contains('hidden');
                    if(isHidden) {
                        iconCollapse.classList.add('rotate-180');
                    } else {
                        iconCollapse.classList.remove('rotate-180');
                    }
                });
            }

            const btnRemove = card.querySelector('.btn-remove-item');
            if(btnRemove) {
                btnRemove.addEventListener('click', function(e) {
                    card.classList.add('opacity-0', 'scale-95');
                    setTimeout(() => {
                        card.remove();
                        updateItemNumbers();
                        updateGlobalTotal();
                        updateRemoveButtons();
                    }, 300);
                });
            }
        }

        function updateItemNumbers() {
            const cards = itemsContainer.querySelectorAll('.item-card');
            cards.forEach((card, idx) => {
                const numBox = card.querySelector('.item-number');
                if(numBox) numBox.textContent = idx + 1;
            });
        }
        
        function updateRemoveButtons() {
            const cards = itemsContainer.querySelectorAll('.item-card');
            cards.forEach((card, idx) => {
                const btn = card.querySelector('.btn-remove-item');
                if(btn) {
                    if(cards.length <= 1) {
                        btn.classList.add('hidden');
                    } else {
                        btn.classList.remove('hidden');
                    }
                }
            });
        }

        btnAddItem.addEventListener('click', addItem);
        
        // Add initial first item
        addItem();


        // ─────────────────────────────
        // EVENT LISTENERS: BRANCH PILLS & METHODS
        // ─────────────────────────────
        branchPills.forEach(btn => {
            btn.addEventListener('click', function () {
                const id   = this.dataset.id;
                const name = this.dataset.name;
                const index = selectedBranches.findIndex(b => b.id == id);

                if (index > -1) {
                    selectedBranches.splice(index, 1);
                    this.classList.remove('bg-emerald-500', 'text-white', 'border-emerald-500', 'shadow-md');
                    this.classList.add('bg-white', 'text-slate-600', 'border-slate-200');
                } else {
                    selectedBranches.push({ id, name, value: 0, percent: 0 });
                    this.classList.remove('bg-white', 'text-slate-600', 'border-slate-200');
                    this.classList.add('bg-emerald-500', 'text-white', 'border-emerald-500', 'shadow-md');
                }
                renderDistribution();
            });
        });

        methodBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                methodBtns.forEach(b => {
                    b.classList.remove('bg-white', 'shadow', 'text-slate-700');
                    b.classList.add('text-slate-500');
                });
                this.classList.remove('text-slate-500');
                this.classList.add('bg-white', 'shadow', 'text-slate-700');
                currentMethod = this.dataset.method;
                renderDistribution();
            });
        });

        // ─────────────────────────────
        // DISTRIBUTION LOGIC
        // ─────────────────────────────
        function renderDistribution() {
            distributionList.innerHTML = '';
            
            if (selectedBranches.length === 0) {
                summarySection.classList.add('hidden');
                summarySubmit.disabled = true;
                percentWarning.classList.add('hidden');
                return;
            }

            summarySection.classList.remove('hidden');
            const totalAmount = parseInt(formTotalInput.value) || 0;

            selectedBranches.forEach((branch, idx) => {
                if (currentMethod === 'equal') {
                    branch.percent = parseFloat((100 / selectedBranches.length).toFixed(2));
                    branch.value   = totalAmount > 0 ? Math.round(totalAmount / selectedBranches.length) : 0;
                } else if (currentMethod === 'percent') {
                    branch.value = totalAmount > 0 ? Math.round((totalAmount * (branch.percent || 0)) / 100) : 0;
                } else if (currentMethod === 'manual') {
                    branch.percent = totalAmount > 0 ? parseFloat(((branch.value / totalAmount) * 100).toFixed(2)) : 0;
                }

                let inputHtml = '';
                if (currentMethod === 'equal') {
                    inputHtml = `<div class="font-bold text-emerald-600">Rp ${formatRupiah(branch.value)}</div>`;
                } else if (currentMethod === 'percent') {
                    inputHtml = `
                        <div class="flex items-center gap-2">
                            <input type="number" 
                                class="dist-input-percent w-20 text-right text-sm border border-slate-200 rounded-lg px-2 py-1 focus:ring-2 focus:ring-emerald-500 outline-none" 
                                data-index="${idx}" 
                                value="${branch.percent || 0}" 
                                min="0" max="100">
                            <span class="text-xs font-bold text-slate-400">%</span>
                            <span class="text-emerald-500 font-bold text-sm w-32 text-right">Rp ${formatRupiah(branch.value)}</span>
                        </div>
                    `;
                } else if (currentMethod === 'manual') {
                    const displayVal = branch.value > 0 ? formatRupiah(branch.value) : '';
                    inputHtml = `
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 font-bold">Rp</span>
                            <input type="text" 
                                class="dist-input-manual w-32 text-right text-sm border border-slate-200 rounded-lg pl-8 pr-3 py-1 focus:ring-2 focus:ring-emerald-500 outline-none" 
                                data-index="${idx}" 
                                value="${displayVal}" placeholder="0">
                        </div>
                    `;
                }

                const rowHtml = `
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between bg-white rounded-xl border border-slate-200 px-4 py-3 gap-3">
                        <div class="font-bold sm:font-medium text-slate-700 flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                            ${branch.name}
                        </div>
                        <div class="flex justify-end">${inputHtml}</div>
                    </div>
                `;
                distributionList.insertAdjacentHTML('beforeend', rowHtml);
            });

            const methodLabels = { 'equal': 'BAGI RATA', 'percent': 'PERSENTASE', 'manual': 'MANUAL' };
            summaryMethod.textContent = 'METODE: ' + (methodLabels[currentMethod] || '-');
            
            updateHiddenInputs();
            updateSummaryList();
            validateAndSubmit();
        }

        function updateHiddenInputs() {
            hiddenInputsContainer.innerHTML = '';
            selectedBranches.forEach((branch, idx) => {
                hiddenInputsContainer.insertAdjacentHTML('beforeend', `
                    <input type="hidden" name="branches[${idx}][branch_id]"          value="${branch.id}">
                    <input type="hidden" name="branches[${idx}][allocation_amount]"  value="${Math.round(branch.value || 0)}">
                    <input type="hidden" name="branches[${idx}][allocation_percent]" value="${branch.percent || 0}">
                `);
            });
        }

        function updateSummaryList() {
            summaryBranchesList.innerHTML = '';
            const totalAmount = parseInt(formTotalInput.value) || 0;

            selectedBranches.forEach(branch => {
                const pct = totalAmount > 0
                    ? ((branch.value / totalAmount) * 100).toFixed(1)
                    : (branch.percent || 0).toFixed(1);
                    
                const summaryRow = `
                    <div class="flex justify-between items-start text-sm border-b border-white/10 pb-3 pt-3 px-2 last:border-0 last:pb-0">
                        <div class="flex flex-col">
                            <span class="text-slate-300 font-medium">${branch.name}</span>
                            <span class="text-[10px] text-emerald-400/70 mt-0.5">${pct}%</span>
                        </div>
                        <span class="text-emerald-400 font-bold">Rp ${formatRupiah(branch.value)}</span>
                    </div>
                `;
                summaryBranchesList.insertAdjacentHTML('beforeend', summaryRow);
            });

            summaryTotal.textContent = 'Rp ' + formatRupiah(totalAmount);
            summaryBranchCount.textContent = selectedBranches.length + ' CABANG';
        }

        distributionList.addEventListener('input', function(e) {
            const index = e.target.dataset.index;
            if (index === undefined) return;
            const totalAmount = parseInt(formTotalInput.value) || 0;

            if (e.target.classList.contains('dist-input-percent')) {
                const val = parseFloat(e.target.value) || 0;
                selectedBranches[index].percent = val;
                selectedBranches[index].value = totalAmount > 0 ? Math.round((totalAmount * val) / 100) : 0;
                const siblingSpan = e.target.parentElement.querySelector('.text-emerald-500');
                if (siblingSpan) siblingSpan.textContent = 'Rp ' + formatRupiah(selectedBranches[index].value);
            }
            if (e.target.classList.contains('dist-input-manual')) {
                const raw = parseRupiah(e.target.value);
                e.target.value = raw > 0 ? formatRupiah(raw) : '';
                selectedBranches[index].value = raw;
                if (totalAmount > 0) {
                    selectedBranches[index].percent = parseFloat(((raw / totalAmount) * 100).toFixed(2));
                }
            }

            updateHiddenInputs();
            updateSummaryList();
            validateAndSubmit();
        });

        function validateAndSubmit() {
            let isValid = true;
            const totalAmount = parseInt(formTotalInput.value) || 0;
            
            // Validation 1: Branch Method Percent
            if (currentMethod === 'percent') {
                const totalPercent = selectedBranches.reduce((sum, b) => sum + (parseFloat(b.percent)||0), 0);
                if (Math.abs(totalPercent - 100) > 0.1) {
                    isValid = false;
                    percentWarning.classList.remove('hidden');
                    percentWarning.textContent = `⚠ Total persen saat ini ${totalPercent}%. Harus 100%`;
                } else {
                    percentWarning.classList.add('hidden');
                }
            } else {
                percentWarning.classList.add('hidden');
            }
            
            // Validation 2: Items Total Amount > 0
            if (totalAmount <= 0) isValid = false;

            summarySubmit.disabled = !isValid;
        }

        document.getElementById('pengajuan-form').addEventListener('submit', function(e) {
            const totalAmount = parseInt(formTotalInput.value) || 0;
            if(totalAmount <= 0) {
                e.preventDefault();
                alert('Total estimasi tidak boleh Rp 0. Silakan isi harga barang.');
                return;
            }
            
            if (summarySubmit.disabled) {
                e.preventDefault();
                return;
            }
            summarySubmit.disabled = true;
            document.getElementById('submit-text').textContent = 'Memproses...';
            document.getElementById('submit-spinner').classList.remove('hidden');
        });

    });
    </script>
    @endpush
@endsection