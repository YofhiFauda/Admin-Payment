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
            <input type="hidden" name="estimated_price" id="form-estimated-price" value="{{ old('estimated_price', 0) }}">
            <input type="hidden" name="has_reference_photo" value="{{ $filePath ? '1' : '0' }}">
            
            {{-- Container untuk input tersembunyi distribusi (PENTING) --}}
            <div id="distribution-hidden-inputs"></div>

            {{-- ══════════════════════════════════ --}}
            {{-- GRID 2 KOLOM (KIRI: FORM | KANAN: BIAYA) --}}
            {{-- ══════════════════════════════════ --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 mb-8 md:mb-10">
                
                {{-- 📍 KOLOM KIRI (UTAMA) --}}
                <div class="lg:col-span-8 space-y-10 md:space-y-14">
                    
                    {{-- 1. FOTO REFERENSI --}}
                    <div>
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-3 tracking-wider">
                            Foto Referensi 
                            @if(isset($base64) || isset($filePath))
                                <span class="text-emerald-500">(Dari Upload Sebelumnya)</span>
                            @else
                                <span class="text-slate-400">(Opsional)</span>
                            @endif
                        </label>
                        
                        @if((isset($base64) && isset($mime) && str_contains($mime, 'image')) || (isset($filePath) && $filePath))
                            <div class="border-2 border-emerald-200 rounded-2xl p-2 bg-emerald-50/50 flex justify-center relative overflow-hidden cursor-pointer hover:border-emerald-400 transition-colors group"
                                id="ref-photo-wrapper"
                                title="Klik untuk memperbesar">
                                
                                @if(isset($base64) && isset($mime) && str_contains($mime, 'image'))
                                    <img src="data:{{ $mime }};base64,{{ $base64 }}" 
                                        class="w-auto h-48 md:h-64 object-contain rounded-xl shadow-sm" 
                                        alt="Preview Foto Referensi" 
                                        id="ref-photo-img" />
                                @elseif(isset($filePath) && $filePath)
                                    <img src="{{ Storage::url($filePath) }}" 
                                        class="w-auto h-48 md:h-64 object-contain rounded-xl shadow-sm" 
                                        alt="Preview Foto Referensi"
                                        id="ref-photo-img"
                                        onerror="this.parentElement.innerHTML='<div class=\'text-red-500 text-sm\'>❌ Gagal memuat foto</div>'" />
                                @endif
                                
                                <div class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm rounded-full px-3 py-1.5 shadow-lg flex items-center gap-1.5 pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity">
                                    <i data-lucide="expand" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Perbesar</span>
                                </div>
                                <div class="absolute bottom-3 left-3 bg-emerald-500/90 backdrop-blur-sm rounded-full px-2.5 py-1 shadow-lg flex items-center gap-1.5">
                                    <i data-lucide="check-circle" class="w-3 h-3 text-white"></i>
                                    <span class="text-[9px] font-bold text-white uppercase tracking-wider">Foto Terupload</span>
                                </div>
                            </div>
                            <div class="mt-4 mb-4 bg-blue-50 border border-blue-100/50 rounded-xl p-3 md:p-4 flex gap-3 items-start">
                                <div class="bg-blue-100 p-1.5 md:p-2 rounded-lg text-blue-500 shrink-0">
                                    <i data-lucide="info" class="w-4 h-4 md:w-5 md:h-5"></i>        
                                </div>
                                <div>
                                    <h4 class="text-[9px] md:text-[10px] font-bold text-blue-800 uppercase tracking-wider mb-1">Foto Referensi</h4>
                                    <p class="text-[11px] md:text-xs text-blue-600 leading-relaxed">
                                        Foto ini akan disertakan dalam pengajuan sebagai referensi barang/jasa yang ingin dibeli. 
                                        Foto membantu admin memahami spesifikasi dengan lebih baik.
                                    </p>
                                </div>
                            </div>
                        @else
                            <div class="border-2 border-dashed border-slate-200 rounded-2xl p-8 md:p-12 bg-slate-50/50 flex flex-col items-center justify-center text-slate-400">
                                <i data-lucide="image" class="w-10 h-10 md:w-12 md:h-12 mb-3 opacity-30"></i>
                                <span class="text-xs md:text-sm font-medium mb-1">Tidak ada foto referensi</span>
                                <span class="text-[10px] md:text-xs text-slate-300">Pengajuan dapat diproses tanpa foto referensi</span>
                            </div>
                            <div class="mt-4 bg-amber-50 border border-amber-100/50 rounded-xl p-3 md:p-4 flex gap-3 items-start">
                                <div class="bg-amber-100 p-1.5 md:p-2 rounded-lg text-amber-500 shrink-0">
                                    <i data-lucide="lightbulb" class="w-4 h-4 md:w-5 md:h-5"></i>
                                </div>
                                <div>
                                    <h4 class="text-[9px] md:text-[10px] font-bold text-amber-800 uppercase tracking-wider mb-1">Tips</h4>
                                    <p class="text-[11px] md:text-xs text-amber-600 leading-relaxed">
                                        Jika memiliki foto/screenshot barang yang ingin dibeli, upload terlebih dahulu di halaman sebelumnya.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- 2. INFORMASI BARANG --}}
                    <div>
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-4 tracking-wider">
                            Informasi Barang / Jasa
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Nama Barang/Jasa *</label>
                                <input type="text" name="customer" value="{{ old('customer') }}" required
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all"
                                    placeholder="Contoh: Router Mikrotik">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Informasi Vendor</label>
                                <input type="text" name="vendor" value="{{ old('vendor') }}"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all"
                                    placeholder="Contoh: Toko Komputer Jaya">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Link Barang/Referensi</label>
                                <input type="url" name="link" value="{{ old('link') }}"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all"
                                    placeholder="https://tokopedia.link/...">
                            </div>
                        </div>
                    </div>

                    {{-- 3. SPESIFIKASI --}}
                    <div class="mt-10 md:mt-14">
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-5 tracking-wider">Spesifikasi Barang</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 md:gap-6">
                            <div><label class="block text-[10px] md:text-xs font-bold text-slate-500 uppercase mb-3 tracking-wider">Merk</label><input type="text" name="specs[merk]" value="{{ old('specs.merk') }}" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-5 py-4 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 focus:bg-white transition-all" placeholder="Contoh: Xpon CDATA"></div>
                            <div><label class="block text-[10px] md:text-xs font-bold text-slate-500 uppercase mb-3 tracking-wider">Tipe / Seri</label><input type="text" name="specs[tipe]" value="{{ old('specs.tipe') }}" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-5 py-4 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 focus:bg-white transition-all" placeholder="Contoh: FD512XW-R460"></div>
                            <div><label class="block text-[10px] md:text-xs font-bold text-slate-500 uppercase mb-3 tracking-wider">Ukuran</label><input type="text" name="specs[ukuran]" value="{{ old('specs.ukuran') }}" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-5 py-4 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 focus:bg-white transition-all" placeholder="Contoh: 30x30"></div>
                            <div><label class="block text-[10px] md:text-xs font-bold text-slate-500 uppercase mb-3 tracking-wider">Warna</label><input type="text" name="specs[warna]" value="{{ old('specs.warna') }}" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-5 py-4 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 focus:bg-white transition-all" placeholder="Contoh: Putih"></div>
                        </div>
                    </div>

                    {{-- 4. ALASAN --}}
                    <div>
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-3 mt-6 tracking-wider">Alasan Pembelian *</label>
                        <div class="relative">
                            <select name="purchase_reason" id="purchase-reason" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all appearance-none">
                                <option value="">— Pilih alasan —</option>
                                @foreach(\App\Models\Transaction::PURCHASE_REASONS as $key => $label)
                                    <option value="{{ $key }}" {{ old('purchase_reason') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                        <div class="mt-4" id="keterangan-container">
                            <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">
                                Keterangan <span id="keterangan-required" class="text-red-500 hidden">*</span>
                            </label>
                            <textarea name="description" id="purchase-description" rows="3"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all"
                                placeholder="Tambahkan detail/alasan tambahan jika diperlukan...">{{ old('description') }}</textarea>
                        </div>
                    </div>

                </div>

                {{-- 📍 KOLOM KANAN (SIDEBAR STICKY) --}}
                <div class="lg:col-span-4">
                    <div class="sticky top-8 space-y-6 md:space-y-8">
                        
                        {{-- 5. JUMLAH & HARGA --}}
                        <div class="bg-slate-50 p-5 md:p-6 rounded-2xl border border-slate-100">
                            <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-4 tracking-wider">Estimasi Biaya</label>
                            <div class="space-y-5">
                                <div>
                                    <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Jumlah *</label>
                                    <input type="number" name="quantity" value="{{ old('quantity', 1) }}" required min="1" id="input-quantity" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all">
                                </div>
                                <div>
                                    <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Estimasi Harga Satuan *</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">Rp</span>
                                        <input type="text" id="input-price-display" required class="w-full bg-white border border-slate-200 rounded-xl pl-12 pr-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all" value="{{ old('estimated_price') ? number_format(old('estimated_price'), 0, ',', '.') : '' }}">
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 bg-teal-50 border border-teal-100 rounded-xl p-4 flex justify-between items-center shadow-sm">
                                <span class="text-xs font-bold text-teal-700">Total Estimasi</span>
                                <div id="total-estimate" class="transition-transform duration-200 text-lg md:text-xl font-bold text-emerald-600">Rp 0</div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════ --}}
            {{-- 6. DISTRIBUSI CABANG (FULL WIDTH CARD) --}}
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
                <div class="mt-4 bg-red-50 border border-red-100 rounded-xl p-4">
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

    {{-- ══════════════════════════════════════════════════ --}}
    {{-- IMAGE VIEWER MODAL                                --}}
    {{-- hidden → flex saat dibuka via JS                 --}}
    {{-- ══════════════════════════════════════════════════ --}}
    <div id="image-viewer"
         class="fixed inset-0 bg-black/75 backdrop-blur-sm hidden items-center justify-center z-50 p-6"
         role="dialog" aria-modal="true" aria-label="Preview foto referensi">

        {{-- Card --}}
        <div class="relative max-w-3xl w-full" id="viewer-card">

            {{-- Tombol X — pojok kanan atas, di luar foto --}}
            <button id="close-viewer"
                    type="button"
                    class="absolute -top-4 -right-4 z-20 w-9 h-9 flex items-center justify-center rounded-full bg-white shadow-lg text-slate-600 hover:text-red-500 hover:scale-110 transition-all"
                    aria-label="Tutup preview">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>

            {{-- Gambar --}}
            <img id="viewer-image"
                 src=""
                 class="w-full max-h-[85vh] object-contain rounded-2xl shadow-2xl bg-white p-2"
                 alt="Preview foto referensi" />

            {{-- Hint --}}
            <p class="text-center text-white/40 text-[10px] mt-3 font-medium tracking-wide select-none">
                Klik di luar gambar atau tekan ESC untuk menutup
            </p>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        

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

        // Klik wrapper → buka modal
        if (refWrapper) {
            refWrapper.addEventListener('click', function () {
                const img = this.querySelector('img');
                if (img) openViewer(img.src);
            });
        }

        // Tombol X → tutup
        if (closeViewer) {
            closeViewer.addEventListener('click', function (e) {
                e.stopPropagation();
                closeViewerFn();
            });
        }

        // Klik backdrop (di luar viewer-card) → tutup
        if (imageViewer) {
            imageViewer.addEventListener('click', function (e) {
                if (e.target === imageViewer) closeViewerFn();
            });
        }

        // ESC → tutup
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !imageViewer.classList.contains('hidden')) {
                closeViewerFn();
            }
        });

        // ═══════════════════════════════════════
        // VARIABLES & SELECTORS
        // ═══════════════════════════════════════
        const priceDisplay      = document.getElementById('input-price-display');
        const priceHidden       = document.getElementById('form-estimated-price');
        const quantityInput     = document.getElementById('input-quantity');
        const totalEstimate     = document.getElementById('total-estimate');
        
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
        
        const purchaseReason    = document.getElementById('purchase-reason');
        const purchaseDesc      = document.getElementById('purchase-description');
        const keteranganReq     = document.getElementById('keterangan-required');

        function updateKeteranganValidation() {
            if (purchaseReason.value === 'lainnya') {
                purchaseDesc.required = true;
                keteranganReq.classList.remove('hidden');
            } else {
                purchaseDesc.required = false;
                keteranganReq.classList.add('hidden');
            }
        }

        purchaseReason.addEventListener('change', updateKeteranganValidation);
        updateKeteranganValidation(); // Initial check

        let selectedBranches = [];
        let currentMethod    = 'equal';

        // ─────────────────────────────
        // HELPER FUNCTIONS
        // ─────────────────────────────
        function parseRupiah(str) {
            return parseInt((str || '').replace(/\D/g, '') || '0');
        }

        function formatRupiah(num) {
            return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function getTotal() {
            const price = parseRupiah(priceHidden.value);
            const qty   = parseInt(quantityInput.value) || 1;
            return price * qty;
        }

        function updateTotalDisplay() {
            const total = getTotal();
            totalEstimate.textContent = 'Rp ' + formatRupiah(total);
            renderDistribution();
        }

        // ─────────────────────────────
        // EVENT LISTENERS: PRICE & QTY
        // ─────────────────────────────
        priceDisplay.addEventListener('input', function () {
            const raw = parseRupiah(this.value);
            this.value = raw > 0 ? formatRupiah(raw) : '';
            priceHidden.value = raw;
            updateTotalDisplay();
        });

        quantityInput.addEventListener('input', updateTotalDisplay);

        // ─────────────────────────────
        // EVENT LISTENERS: BRANCH PILLS
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

        // ─────────────────────────────
        // EVENT LISTENERS: METHODS
        // ─────────────────────────────
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
        // CORE LOGIC: RENDER DISTRIBUTION
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
            const totalAmount = getTotal();

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
                    <div class="flex justify-between items-center bg-white rounded-xl border border-slate-200 px-4 py-3">
                        <div class="font-medium text-slate-700 flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                            ${branch.name}
                        </div>
                        <div>${inputHtml}</div>
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
            const totalAmount = getTotal();

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
            const totalAmount = getTotal();

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

            summarySubmit.disabled = !isValid;
        }

        document.getElementById('pengajuan-form').addEventListener('submit', function(e) {
            if (summarySubmit.disabled) {
                e.preventDefault();
                return;
            }
            summarySubmit.disabled = true;
            document.getElementById('submit-text').textContent = 'Memproses...';
            document.getElementById('submit-spinner').classList.remove('hidden');
        });

        // Initialize
        updateTotalDisplay();
    });
    </script>
    @endpush
@endsection