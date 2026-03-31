@extends('layouts.app')

@section('page-title', 'Form Reimbursement')

@section('content')
    <div class="max-w-8xl mx-auto px-1 md:px-4 lg:px-6 py-1 lg:py-6">

        {{-- Form Container --}}
        <div class="bg-white rounded-[1rem] md:rounded-[2rem] shadow-sm border border-slate-100 p-3 md:p-8 lg:p-10">
            
            {{-- Header --}}
            <div class="mb-8 md:mb-10 flex items-center gap-4">
                <a href="{{ route('transactions.create') }}" class="w-8 h-8 flex items-center justify-center rounded-full border border-slate-200 text-slate-400 hover:text-emerald-600 hover:border-emerald-200 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-slate-800 tracking-tight">Form Reimbursement</h1>
                    <p class="text-xs md:text-sm text-slate-400 mt-1">Lengkapi data klaim pengeluaran Anda dengan presisi</p>
                </div>
            </div>

            <form method="POST" action="{{ route('rembush.store') }}" id="transaction-form" enctype="multipart/form-data">
                @csrf
                @if ($errors->any())
                    <div class="mb-8 md:mb-10 bg-red-50 border border-red-200 text-red-600 rounded-xl p-4 md:p-5 text-xs md:text-sm">
                        <strong class="font-bold">Terjadi Kesalahan:</strong>
                        <ul class="list-disc pl-5 mt-2 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <input type="hidden" name="type" value="rembush">
                @if(isset($uploadId))
                    <input type="hidden" id="upload-id" value="{{ $uploadId }}">
                @endif
                <input type="hidden" name="amount" id="form-total-amount" value="{{ old('amount', 0) }}">
                
                {{-- ══════════════════════════════════ --}}
                {{-- 1. FOTO REFERENSI --}}
                {{-- ══════════════════════════════════ --}}
                <div class="mb-8 md:mb-10">
                    <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-3 tracking-wider">
                        Foto Referensi 
                        @if(isset($base64) || isset($filePath))
                            <span class="text-emerald-500">(Dari Upload Sebelumnya)</span>
                        @else
                            <span class="text-slate-400">(Opsional)</span>
                        @endif
                    </label>
                    
                    {{-- ✅ CONDITIONAL: Show EITHER photo preview OR empty state --}}
                    @if((isset($base64) && str_contains($mime, 'image')) || (isset($filePath) && $filePath))
                        {{-- ═══ PHOTO PREVIEW (Base64 or File Path) ═══ --}}
                        <div class="border-2 border-emerald-200 rounded-2xl p-2 bg-emerald-50/50 flex justify-center relative overflow-hidden cursor-pointer hover:border-emerald-400 transition-colors group"
                            id="ref-photo-wrapper"
                            title="Klik untuk memperbesar">
                            
                            @if(isset($base64) && isset($mime) && str_contains($mime, 'image'))
                                {{-- ✅ PRIORITAS 1: DATA URI (Base64) - Instant Preview --}}
                                <img src="data:{{ $mime }};base64,{{ $base64 }}"
                                    class="w-auto h-48 md:h-64 object-contain rounded-xl shadow-sm" 
                                    alt="Preview Foto Referensi" 
                                    id="ref-photo-img"/>
                            @elseif(isset($filePath) && $filePath)
                                {{-- ⚠️ FALLBACK: Storage URL (bisa 404 jika symlink belum dibuat) --}}
                                <img src="{{ Storage::url($filePath) }}" 
                                    class="w-auto h-48 md:h-64 object-contain rounded-xl shadow-sm" 
                                    alt="Preview Foto Referensi"
                                    id="ref-photo-img"
                                    onerror="this.parentElement.innerHTML='<div class=\'text-red-500 text-sm\'>❌ Gagal memuat foto</div>'" />
                            @endif
                            
                            {{-- Preview Badge --}}
                            <div class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm rounded-full px-3 py-1.5 shadow-lg flex items-center gap-1.5 pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity">
                                <i data-lucide="expand" class="w-3.5 h-3.5 text-emerald-600"></i>
                                <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Perbesar</span>
                            </div>
                            
                            {{-- Success Indicator --}}
                            <div class="absolute bottom-3 left-3 bg-emerald-500/90 backdrop-blur-sm rounded-full px-2.5 py-1 shadow-lg flex items-center gap-1.5">
                                <i data-lucide="check-circle" class="w-3 h-3 text-white"></i>
                                <span class="text-[9px] font-bold text-white uppercase tracking-wider">Foto Terupload</span>
                            </div>
                        </div>

                        {{-- Tips Jika Ada Foto --}}
                        <div class="mt-4 bg-blue-50 border border-blue-100/50 rounded-xl p-3 md:p-4 flex gap-3 items-start">
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
                        {{-- ═══ EMPTY STATE (No Photo) ═══ --}}
                        <div class="border-2 border-dashed border-slate-200 rounded-2xl p-8 md:p-12 bg-slate-50/50 flex flex-col items-center justify-center text-slate-400">
                            <i data-lucide="image" class="w-10 h-10 md:w-12 md:h-12 mb-3 opacity-30"></i>
                            <span class="text-xs md:text-sm font-medium mb-1">Tidak ada foto referensi</span>
                            <span class="text-[10px] md:text-xs text-slate-300">Pengajuan dapat diproses tanpa foto referensi</span>
                        </div>

                        {{-- Tips Jika Tidak Ada Foto --}}
                        <div class="mt-4 bg-amber-50 border border-amber-100/50 rounded-xl p-3 md:p-4 flex gap-3 items-start">
                            <div class="bg-amber-100 p-1.5 md:p-2 rounded-lg text-amber-500 shrink-0">
                                <i data-lucide="lightbulb" class="w-4 h-4 md:w-5 md:h-5"></i>
                            </div>
                            <div>
                                <h4 class="text-[9px] md:text-[10px] font-bold text-amber-800 uppercase tracking-wider mb-1">Tips</h4>
                                <p class="text-[11px] md:text-xs text-amber-600 leading-relaxed">
                                    Jika memiliki foto/screenshot barang yang ingin dibeli, upload terlebih dahulu di halaman sebelumnya. 
                                    Foto referensi membantu mempercepat proses verifikasi.
                                </p>
                            </div>
                        </div>
                    @endif

                    {{-- Alert Tips --}}
                    <div class="mt-4 bg-orange-50 border border-orange-100/50 rounded-xl p-3 md:p-4 flex gap-3 md:gap-4 items-start">
                        <div class="bg-orange-100 p-1.5 md:p-2 rounded-lg text-orange-500 shrink-0">
                            <i data-lucide="shield-alert" class="w-4 h-4 md:w-5 md:h-5"></i>
                        </div>
                        <div>
                            <h4 class="text-[9px] md:text-[10px] font-bold text-orange-800 uppercase tracking-wider mb-1">Tips Penting</h4>
                            <p class="text-[11px] md:text-xs text-orange-600 leading-relaxed">Segera foto nota setelah transaksi. Nota yang lecek atau tinta pudar berisiko tinggi ditolak oleh sistem verifikasi admin.</p>
                        </div>
                    </div>
                </div>

                {{-- 2. MAIN INFO FIELDS --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6 mb-8 md:mb-10">

                    <div>
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Nama Vendor</label>
                        <input type="text" name="customer" id="customer" value="{{ old('customer', '') }}"
                            placeholder="Opsional (Diisi otomatis oleh sistem nanti)"
                            class="w-full border border-slate-200 rounded-xl p-3 md:p-3.5 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 outline-none transition-all" />
                    </div>
                    
                    <div>
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Tanggal Transaksi</label>
                        <input type="date" name="date" id="date" required value="{{ old('date',now()->format('Y-m-d')) }}"
                            class="w-full border border-slate-200 rounded-xl p-3 md:p-3.5 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 outline-none transition-all" />
                    </div>
                    
                    {{-- Kategori (Baru ditambahkan) --}}
                    <div>
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Kategori</label>
                        <div class="relative">
                            <select name="category" id="category" required
                                class="w-full appearance-none bg-white border border-slate-200 rounded-xl p-3 md:p-3.5 pr-10 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 outline-none transition-all">
                                <option value="" disabled {{ old('category') ? '' : 'selected' }}>Pilih kategori...</option>
                                @foreach(\App\Models\Transaction::CATEGORIES as $key => $label)
                                    <option value="{{ $key }}" {{ old('category') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-3 md:right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>

                    {{-- Metode Pencairan (Baru ditambahkan) --}}
                    <div>
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Metode Pencairan</label>
                        <div class="relative">
                            <select name="payment_method" id="payment_method" required
                                class="w-full appearance-none bg-white border border-slate-200 rounded-xl p-3 md:p-3.5 pr-10 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 outline-none transition-all">
                                <option value="" disabled {{ old('payment_method') ? '' : 'selected' }}>Pilih metode pembayaran...</option>
                                @foreach(\App\Models\Transaction::PAYMENT_METHODS as $key => $label)
                                    <option value="{{ $key }}" {{ old('payment_method') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-3 md:right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>

                    {{-- Form Rekening/E-Wallet khusus Transfer Penjual --}}
                    <div id="bank_details_section" class="md:col-span-2 hidden bg-blue-50/50 border border-blue-100/50 rounded-2xl p-4 md:p-5 mt-2">
                        <div class="flex items-center gap-2 mb-4">
                            <i data-lucide="landmark" class="w-4 h-4 text-blue-500"></i>
                            <h4 class="text-xs font-bold text-blue-800 uppercase tracking-wider">Informasi Rekening / E-Wallet Penjual</h4>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[10px] md:text-xs font-bold text-blue-700 uppercase mb-2 tracking-wider">Nama Bank / E-Wallet <span class="text-red-500">*</span></label>
                                <input type="text" name="bank_name" id="bank_name" placeholder="Misal: BCA, OVO" value="{{ old('bank_name') }}"
                                    class="w-full border border-blue-200 rounded-xl p-3 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-blue-300 outline-none transition-all bg-white uppercase" />
                            </div>
                            <div>
                                <label class="block text-[10px] md:text-xs font-bold text-blue-700 uppercase mb-2 tracking-wider">Atas Nama Rekening <span class="text-red-500">*</span></label>
                                <input type="text" name="account_name" id="account_name" placeholder="Atas nama" value="{{ old('account_name') }}"
                                    class="w-full border border-blue-200 rounded-xl p-3 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-blue-300 outline-none transition-all bg-white uppercase" />
                            </div>
                            <div>
                                <label class="block text-[10px] md:text-xs font-bold text-blue-700 uppercase mb-2 tracking-wider">Nomor Rekening <span class="text-red-500">*</span></label>
                                <input type="text" name="account_number" id="account_number" placeholder="Nomor rekening / No HP" value="{{ old('account_number') }}"
                                    class="w-full border border-blue-200 rounded-xl p-3 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-blue-300 outline-none transition-all bg-white" />
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Keterangan</label>
                        <textarea name="description" id="description" rows="2" placeholder="Nota pembelian dari..."
                            class="w-full border border-slate-200 rounded-xl p-3 md:p-3.5 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 outline-none resize-none transition-all">{{ old('description') }}</textarea>
                    </div>
                </div>

                {{-- 3. DAFTAR BARANG --}}
                <div class="mb-8 md:mb-10">
                    <div class="flex justify-between items-center mb-4">
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wider">Daftar Barang</label>
                        <button type="button" id="add-item-btn" class="flex items-center gap-1.5 bg-emerald-50 text-emerald-600 px-3 py-1.5 rounded-lg text-[10px] md:text-xs font-bold hover:bg-emerald-100 transition-colors uppercase tracking-wider">
                            <i data-lucide="plus" class="w-3 h-3 md:w-3.5 md:h-3.5"></i> Tambah Baris
                        </button>
                    </div>

                    <div class="border border-slate-100 rounded-2xl overflow-hidden shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs md:text-sm whitespace-nowrap" id="items-table">
                                <thead class="text-[9px] md:text-[10px] text-slate-400 font-bold uppercase bg-slate-50 border-b border-slate-100 tracking-wider">
                                    <tr>
                                        <th class="p-3 md:p-4 w-10 text-center">No</th>
                                        <th class="p-3 md:p-4 min-w-[120px]">Nama Barang</th>
                                        <th class="p-3 md:p-4 w-20">Qty</th>
                                        <th class="p-3 md:p-4 w-24">Satuan</th>
                                        <th class="p-3 md:p-4 w-32">Harga Satuan</th>
                                        <th class="p-3 md:p-4 w-32">Total</th>
                                        <th class="p-3 md:p-4 min-w-[120px]">Deskripsi</th>
                                        <th class="p-3 md:p-4 w-10 text-center"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100" id="items-tbody">
                                </tbody>
                            </table>
                        </div>
                        
                        {{-- Baris Total --}}
                        <div class="bg-slate-900 px-4 md:px-6 py-4 md:py-5 flex justify-between items-center">
                            <span class="text-[9px] md:text-[11px] font-bold uppercase tracking-[0.15em] text-slate-400">Total Nominal</span>
                            <span class="text-xl md:text-2xl font-black text-emerald-400 tracking-tight" id="display-total-items">Rp 0</span>
                        </div>
                    </div>
                </div>

                {{-- 4. PEMBAGIAN CABANG --}}
                <div class="mb-12">
                    <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Pembagian Cabang</label>
                    
                    {{-- Branch Pills --}}
                    <div class="flex flex-wrap gap-2 mb-6" id="branch-pills-container">
                        @foreach($branches as $branch)
                            <button type="button" data-branch-id="{{ $branch->id }}" data-branch-name="{{ $branch->name }}" 
                                class="branch-pill px-3 md:px-4 py-1.5 md:py-2 rounded-full text-[10px] md:text-xs font-bold transition-all border border-slate-200 text-slate-500 hover:bg-slate-50">
                                {{ $branch->name }}
                            </button>
                        @endforeach
                    </div>

                    <div class="border border-slate-100 rounded-2xl p-4 md:p-6" id="allocation-container" style="display: none;">
                        <div class="flex flex-col md:flex-row justify-between md:items-center gap-4 border-b border-slate-100 pb-4 mb-4">
                            <span class="text-[9px] md:text-[10px] font-bold text-slate-500 uppercase tracking-wider">Metode Distribusi</span>
                            
                            {{-- Toggles --}}
                            <div class="flex bg-slate-100 p-1 rounded-lg w-fit">
                                <button type="button" data-mode="equal" class="alloc-mode-btn bg-white shadow-sm text-slate-800 px-3 md:px-4 py-1.5 rounded text-[10px] font-bold transition-colors">Bagi Rata</button>
                                <button type="button" data-mode="percent" class="alloc-mode-btn text-slate-400 px-3 md:px-4 py-1.5 rounded text-[10px] font-bold transition-colors hover:text-slate-600">Persentase</button>
                                <button type="button" data-mode="manual" class="alloc-mode-btn text-slate-400 px-3 md:px-4 py-1.5 rounded text-[10px] font-bold transition-colors hover:text-slate-600">Manual</button>
                            </div>
                        </div>

                        {{-- Active Branches Allocation Inputs/Display --}}
                        <div class="space-y-3" id="active-branches-list">
                        </div>
                            
                        {{-- Hidden inputs temp container --}}
                        <div id="branch-hidden-inputs"></div>

                        @error('branches')
                            <p class="mt-2 text-red-500 font-bold text-[10px] md:text-xs">{{ $message }}</p>
                            <p id="percent-warning" class="mt-2 text-red-500 font-bold text-[10px] md:text-xs hidden"></p>
                        @enderror
                    </div>
                </div>

                {{-- Divider --}}
                <div class="relative flex justify-center items-center mb-8">
                    <div class="w-full h-px bg-slate-100 absolute"></div>
                    <span class="bg-white px-4 relative z-10 text-[9px] md:text-[10px] font-bold text-slate-300 uppercase tracking-[0.2em]">Summary Billing</span>
                </div>

                {{-- 5. SUMMARY BILLING --}}
                <div class="bg-[#1a1c23] rounded-[1rem] md:rounded-3xl p-4 md:p-8 lg:p-10 text-white relative overflow-hidden shadow-xl">
                    {{-- Decorative circle --}}
                    <div class="absolute -right-20 -top-20 w-64 h-64 bg-white/[0.02] rounded-full pointer-events-none"></div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12 mb-8 md:mb-10 relative z-10">
                        {{-- Left Side: Total --}}
                        <div>
                            <span class="text-[9px] md:text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Total Pengajuan</span>
                            <div class="text-3xl md:text-4xl lg:text-5xl font-black text-emerald-400 mb-4 md:mb-6 tracking-tight" id="final-total">Rp 0</div>
                            <div class="flex flex-wrap gap-2">
                                <span class="bg-white/10 text-slate-300 px-3 py-1 md:py-1.5 rounded-full text-[9px] md:text-[10px] font-bold uppercase tracking-wider" id="summary-mode-badge">Metode: Bagi Rata</span>
                                <span class="bg-white/10 text-slate-300 px-3 py-1 md:py-1.5 rounded-full text-[9px] md:text-[10px] font-bold uppercase tracking-wider" id="summary-count-badge">0 Cabang</span>
                            </div>
                        </div>
                        
                        {{-- Right Side: Details --}}
                        <div class="md:border-l border-white/10 md:pl-8 lg:pl-12 flex flex-col justify-center">
                            <span class="text-[9px] md:text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-4">Rincian Distribusi Cabang</span>
                            <div class="space-y-3" id="summary-branches-list">
                                <div class="text-xs text-slate-500 italic">Pilih cabang terlebih dahulu...</div>
                            </div>
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <button type="submit" id="submit-btn"
                        class="w-full relative z-10 bg-emerald-500 hover:bg-emerald-400
                            disabled:bg-slate-700 disabled:text-slate-500 text-white font-bold
                            py-4 md:py-5 rounded-xl transition-all
                            shadow-[0_8px_20px_-6px_rgba(16,185,129,0.4)] disabled:shadow-none
                            text-xs md:text-sm uppercase tracking-wider
                            cursor-pointer disabled:cursor-not-allowed
                            flex items-center justify-center gap-2">
                        <span id="submit-text">Kirim Pengajuan Rembush</span>
                        <svg id="submit-spinner" class="animate-spin h-4 w-4 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                    </button>
                </div>
            </form>
        </div>{{-- END: Form Container Card --}}
    </div>{{-- END: Max-width Container --}}
    
   {{-- ══════════════════════════════════════════════════ --}}
    {{-- IMAGE VIEWER MODAL                                --}}
    {{-- hidden → flex saat dibuka via JS                 --}}
    {{-- ══════════════════════════════════════════════════ --}}
    <div id="image-viewer"
         class="fixed inset-0 bg-black/75 backdrop-blur-sm hidden items-center justify-center z-50 p-6"
         role="dialog" 
         aria-modal="true" 
         aria-labelledby="viewer-title">

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
            <p id="viewer-title" class="text-center text-white/40 text-[10px] mt-3 font-medium tracking-wide select-none">
                Klik di luar gambar atau tekan ESC untuk menutup
            </p>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>

{{-- ── SCRIPTS ─────────────────────── --}}
@push('scripts')
    <script>
        {{-- Pass aiData ke JS via window variable --}}
        window._aiData = @json($aiData ?? []);
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {

        // ═══════════════════════════════════════
        // IMAGE VIEWER MODAL
        // ═══════════════════════════════════════
        const imageViewer  = document.getElementById('image-viewer');
        const viewerImage  = document.getElementById('viewer-image');
        const closeViewer  = document.getElementById('close-viewer');
        const refWrapper   = document.getElementById('ref-photo-wrapper');
        let lastFocusedElement = null;

        function openViewer(src) {
            lastFocusedElement = document.activeElement;
            viewerImage.src = src;
            imageViewer.classList.remove('hidden');
            imageViewer.classList.add('flex');
            
            requestAnimationFrame(() => {
                imageViewer.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons({ root: imageViewer });
                }
                
                setTimeout(() => {
                    if (closeViewer) closeViewer.focus();
                }, 50);
            });
        }

        function closeViewerFn() {
            if (document.activeElement && imageViewer.contains(document.activeElement)) {
                document.activeElement.blur();
            }
            
            imageViewer.classList.add('hidden');
            imageViewer.classList.remove('flex');
            document.body.style.overflow = '';
            imageViewer.setAttribute('aria-hidden', 'true');
            
            setTimeout(() => { 
                viewerImage.src = '';
                if (lastFocusedElement && lastFocusedElement.focus) {
                    lastFocusedElement.focus();
                }
            }, 200);
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

        // Initialize aria-hidden
        if (imageViewer) imageViewer.setAttribute('aria-hidden', 'true');

        // ─────────────────────────────────────────────
        // STATE
        // ─────────────────────────────────────────────
        let items          = [];
        let currentMethod  = 'equal';
        let selectedBranches = [];
        let totalAmount    = 0;

        // ─────────────────────────────────────────────
        // SELECTORS
        // ─────────────────────────────────────────────
        const aiData    = window._aiData || {};
        const aiStatus  = aiData.status ?? '';

        const itemsTbody            = document.getElementById('items-tbody');
        const addItemBtn            = document.getElementById('add-item-btn');
        const displayTotalItems     = document.getElementById('display-total-items');
        const formTotalAmount       = document.getElementById('form-total-amount');

        const branchPills           = document.querySelectorAll('.branch-pill');
        const methodBtns            = document.querySelectorAll('.alloc-mode-btn');
        const allocationContainer   = document.getElementById('allocation-container');
        const activeBranchesList    = document.getElementById('active-branches-list');
        const percentWarning        = document.getElementById('percent-warning');
        const hiddenInputsContainer = document.getElementById('branch-hidden-inputs');

        const finalTotal            = document.getElementById('final-total');
        const summaryModeBadge      = document.getElementById('summary-mode-badge');
        const summaryCountBadge     = document.getElementById('summary-count-badge');
        const summaryBranchesList   = document.getElementById('summary-branches-list');
        const submitBtn             = document.getElementById('submit-btn');

        // ─────────────────────────────────────────────
        // HELPERS
        // ─────────────────────────────────────────────
        function esc(str) {
            return String(str ?? '').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }
        function parseNumber(str) {
            return parseInt(String(str ?? '').replace(/[^0-9]/g, '')) || 0;
        }
        function formatRupiah(num) {
            return 'Rp ' + Math.round(num || 0).toLocaleString('id-ID');
        }

        // ─────────────────────────────────────────────
        // AI AUTOFILL
        // ─────────────────────────────────────────────
        if (aiStatus === 'completed') {
            const fillField = (sel, val) => {
                const node = document.querySelector(sel);
                if (node && val != null && val !== '') node.value = val;
            };
            fillField('[name="customer"]', aiData.nama_toko || aiData.customer || '');
            fillField('[name="date"]',     aiData.tanggal   || aiData.date     || '');
            if (Array.isArray(aiData.items) && aiData.items.length > 0) {
                items = aiData.items.map(i => ({
                    name:  i.nama_barang || i.name  || '',
                    qty:   parseInt(i.qty)          || 1,
                    unit:  (i.satuan || i.unit || 'pcs').toLowerCase(),
                    price: parseInt(i.harga_satuan  || i.price) || 0,
                    desc:  i.deskripsi_kalimat || i.desc || '',
                }));
            }
        }
        if (items.length === 0) {
            items = [{ name: '', qty: 1, unit: 'pcs', price: 0, desc: '' }];
        }

        // Setup initial default branch pills based on AI.
        setTimeout(() => {
            let defaultBranchesToClick = [];

            if (aiData && aiData.branches && aiData.branches.length > 0) {
                // Collect branch ids from AI response
                defaultBranchesToClick = aiData.branches.map(b => b.branch_id);
            }

            // Click the branch pills
            defaultBranchesToClick.forEach(branchId => {
                const btn = document.querySelector(`.branch-pill[data-branch-id="${branchId}"]`);
                if (btn) btn.click();
            });

        }, 300);

        // ─────────────────────────────────────────────
        // TOGGLE BANK DETAILS
        // ─────────────────────────────────────────────
        const paymentMethodSelect = document.getElementById('payment_method');
        const bankDetailsSection = document.getElementById('bank_details_section');
        const bankInputs = bankDetailsSection.querySelectorAll('input');

        function toggleBankDetails() {
            if (paymentMethodSelect.value === 'transfer_penjual') {
                bankDetailsSection.classList.remove('hidden');
                bankInputs.forEach(input => input.setAttribute('required', 'required'));
            } else {
                bankDetailsSection.classList.add('hidden');
                bankInputs.forEach(input => input.removeAttribute('required'));
            }
        }

        paymentMethodSelect.addEventListener('change', toggleBankDetails);
        // On init
        toggleBankDetails();

        // ─────────────────────────────────────────────
        // ENFORCE UPPERCASE FOR BANK DETAILS
        // ─────────────────────────────────────────────
        const bankNameInput = document.getElementById('bank_name');
        const accountNameInput = document.getElementById('account_name');

        if (bankNameInput) {
            bankNameInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        }

        if (accountNameInput) {
            accountNameInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        }

        // ─────────────────────────────────────────────
        // ITEMS — render
        // ─────────────────────────────────────────────
        function renderItems() {
            itemsTbody.innerHTML = '';
            totalAmount = 0;

            items.forEach((item, i) => {
                const rowTotal = (item.qty || 0) * (item.price || 0);
                totalAmount += rowTotal;

                const tr = document.createElement('tr');
                tr.className = 'text-slate-600 text-xs hover:bg-slate-50/50 transition-colors';
                tr.dataset.idx = i;
                tr.innerHTML = `
                    <td class="p-3 md:p-4 text-center text-slate-400 font-medium">${i + 1}</td>
                    <td class="p-2 md:p-3">
                        <input type="text" name="items[${i}][name]" value="${esc(item.name)}"
                            placeholder="Nama item..."
                            class="item-field w-full bg-transparent border-0 border-b border-transparent
                                focus:border-emerald-400 focus:ring-0 px-2 py-1 outline-none transition-colors"
                            data-field="name">
                    </td>
                    <td class="p-2 md:p-3">
                        <input type="number" name="items[${i}][qty]" value="${item.qty}" min="1"
                            class="item-field w-full bg-transparent border-0 border-b border-transparent
                                focus:border-emerald-400 focus:ring-0 px-2 py-1 outline-none transition-colors"
                            data-field="qty">
                    </td>
                    <td class="p-2 md:p-3">
                        <input type="text" name="items[${i}][unit]" value="${esc(item.unit)}"
                            class="item-field w-full bg-transparent border-0 border-b border-transparent
                                focus:border-emerald-400 focus:ring-0 px-2 py-1 outline-none transition-colors text-slate-400"
                            data-field="unit">
                    </td>
                    <td class="p-2 md:p-3">
                        <input type="text" value="${formatRupiah(item.price)}"
                            class="item-price-display w-full bg-transparent border-0 border-b border-transparent
                                focus:border-emerald-400 focus:ring-0 px-2 py-1 outline-none transition-colors">
                        <input type="hidden" name="items[${i}][price]" value="${item.price}" class="item-price-hidden">
                    </td>
                    <td class="p-3 md:p-4 font-bold text-slate-800">${formatRupiah(rowTotal)}</td>
                    <td class="p-2 md:p-3">
                        <input type="text" name="items[${i}][desc]" value="${esc(item.desc)}"
                            placeholder="Catatan..."
                            class="item-field w-full bg-transparent border-0 border-b border-transparent
                                focus:border-emerald-400 focus:ring-0 px-2 py-1 outline-none transition-colors text-slate-400"
                            data-field="desc">
                    </td>
                    <td class="p-3 md:p-4 text-center">
                        <button type="button" class="item-delete text-slate-300 hover:text-red-500 transition-colors">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </td>`;
                itemsTbody.appendChild(tr);
            });

            displayTotalItems.textContent = formatRupiah(totalAmount);
            formTotalAmount.value         = totalAmount;
            finalTotal.textContent        = formatRupiah(totalAmount);

            if (typeof lucide !== 'undefined') lucide.createIcons();
            renderDistribution();
        }

        // ─────────────────────────────────────────────
        // ITEMS — event delegation
        // ─────────────────────────────────────────────
        itemsTbody.addEventListener('input', function (e) {
            const tr = e.target.closest('tr[data-idx]');
            if (!tr) return;
            const idx = parseInt(tr.dataset.idx);

            if (e.target.classList.contains('item-field')) {
                items[idx][e.target.dataset.field] = e.target.value;
                if (e.target.dataset.field === 'qty') renderItems();
            }
            if (e.target.classList.contains('item-price-display')) {
                const raw = parseNumber(e.target.value);
                items[idx].price = raw;
                tr.querySelector('.item-price-hidden').value = raw;
            }
        });

        itemsTbody.addEventListener('blur', function (e) {
            if (e.target.classList.contains('item-price-display')) {
                const tr = e.target.closest('tr[data-idx]');
                if (!tr) return;
                items[parseInt(tr.dataset.idx)].price = parseNumber(e.target.value);
                renderItems();
            }
        }, true);

        itemsTbody.addEventListener('click', function (e) {
            const btn = e.target.closest('.item-delete');
            if (!btn) return;
            const tr = btn.closest('tr[data-idx]');
            if (!tr) return;
            items.splice(parseInt(tr.dataset.idx), 1);
            renderItems();
        });

        addItemBtn.addEventListener('click', () => {
            items.push({ name: '', qty: 1, unit: 'pcs', price: 0, desc: '' });
            renderItems();
        });

        // ─────────────────────────────────────────────
        // BRANCH PILLS
        // ─────────────────────────────────────────────
        branchPills.forEach(pill => {
            pill.addEventListener('click', function () {
                const id   = this.dataset.branchId;
                const name = this.dataset.branchName;
                const idx  = selectedBranches.findIndex(b => b.id === id);

                if (idx > -1) {
                    selectedBranches.splice(idx, 1);
                    this.classList.remove('bg-emerald-500', 'text-white', 'border-emerald-500');
                    this.classList.add('border-slate-200', 'text-slate-500');
                } else {
                    selectedBranches.push({ id, name, value: 0, percent: 0 });
                    this.classList.remove('border-slate-200', 'text-slate-500');
                    this.classList.add('bg-emerald-500', 'text-white', 'border-emerald-500');
                }

                allocationContainer.style.display = selectedBranches.length > 0 ? 'block' : 'none';
                renderDistribution();
            });
        });

        // ─────────────────────────────────────────────
        // METHOD BUTTONS
        // ─────────────────────────────────────────────
        methodBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                currentMethod = this.dataset.mode;
                methodBtns.forEach(b => {
                    b.classList.remove('bg-white', 'shadow-sm', 'text-slate-800');
                    b.classList.add('text-slate-400');
                });
                this.classList.add('bg-white', 'shadow-sm', 'text-slate-800');
                this.classList.remove('text-slate-400');
                const labels = { equal: 'Bagi Rata', percent: 'Persentase', manual: 'Manual' };
                summaryModeBadge.textContent = 'Metode: ' + (labels[currentMethod] || '');
                renderDistribution();
            });
        });

        // ─────────────────────────────────────────────
        // DISTRIBUTION INPUTS — event delegation
        // ─────────────────────────────────────────────
        activeBranchesList.addEventListener('input', function (e) {
            if (e.target.classList.contains('dist-pct')) {
                const idx = parseInt(e.target.dataset.idx);
                selectedBranches[idx].percent = parseFloat(e.target.value) || 0;
                selectedBranches[idx].value   = totalAmount > 0
                    ? Math.round((totalAmount * selectedBranches[idx].percent) / 100) : 0;
                updateHiddenInputs();
                updateSummaryList();
                validateAndToggleSubmit();
            }
            if (e.target.classList.contains('dist-manual')) {
                const idx = parseInt(e.target.dataset.idx);
                const raw = parseNumber(e.target.value);
                e.target.value = raw > 0 ? raw.toLocaleString('id-ID') : '';
                selectedBranches[idx].value   = raw;
                selectedBranches[idx].percent = totalAmount > 0
                    ? parseFloat(((raw / totalAmount) * 100).toFixed(2)) : 0;
                updateHiddenInputs();
                updateSummaryList();
                validateAndToggleSubmit();
            }
        });

        // ─────────────────────────────────────────────
        // RENDER DISTRIBUTION
        // ─────────────────────────────────────────────
        function renderDistribution() {
            activeBranchesList.innerHTML  = '';
            summaryBranchesList.innerHTML = '';
            if (hiddenInputsContainer) hiddenInputsContainer.innerHTML = '';

            summaryCountBadge.textContent = `${selectedBranches.length} Cabang`;

            if (selectedBranches.length === 0) {
                summaryBranchesList.innerHTML = `<div class="text-xs text-slate-500 italic">Pilih cabang terlebih dahulu...</div>`;
                validateAndToggleSubmit();
                return;
            }

            selectedBranches.forEach((branch, idx) => {
                if (currentMethod === 'equal') {
                    branch.percent = parseFloat((100 / selectedBranches.length).toFixed(2));
                    branch.value   = totalAmount > 0 ? Math.round(totalAmount / selectedBranches.length) : 0;
                } else if (currentMethod === 'percent') {
                    branch.value = totalAmount > 0
                        ? Math.round((totalAmount * (branch.percent || 0)) / 100) : 0;
                } else if (currentMethod === 'manual') {
                    branch.percent = totalAmount > 0
                        ? parseFloat(((branch.value / totalAmount) * 100).toFixed(2)) : 0;
                }

                let inputHtml = '';
                if (currentMethod === 'equal') {
                    inputHtml = `<span class="text-emerald-500 font-bold text-sm">${formatRupiah(branch.value)}</span>`;
                } else if (currentMethod === 'percent') {
                    inputHtml = `
                        <div class="flex items-center gap-2">
                            <input type="number" step="0.1" min="0" max="100"
                                value="${branch.percent || 0}"
                                class="dist-pct w-16 text-right text-sm border border-slate-200 rounded-lg
                                    px-2 py-1.5 focus:ring-2 focus:ring-emerald-300 focus:border-emerald-400 outline-none"
                                data-idx="${idx}">
                            <span class="text-xs font-bold text-slate-400">%</span>
                            <span class="text-emerald-500 font-bold text-sm w-24 text-right">${formatRupiah(branch.value)}</span>
                        </div>`;
                } else if (currentMethod === 'manual') {
                    const displayVal = branch.value > 0 ? branch.value.toLocaleString('id-ID') : '';
                    inputHtml = `
                        <div class="flex items-center gap-1">
                            <span class="text-xs text-slate-400 font-bold">Rp</span>
                            <input type="text" value="${displayVal}" placeholder="0"
                                class="dist-manual w-28 text-right text-sm border border-slate-200 rounded-lg
                                    px-2 py-1.5 focus:ring-2 focus:ring-emerald-300 focus:border-emerald-400 outline-none"
                                data-idx="${idx}">
                        </div>`;
                }

                const row = document.createElement('div');
                row.className = 'flex justify-between items-center text-xs md:text-sm bg-white p-3 rounded-xl border border-slate-100';
                row.innerHTML = `
                    <span class="text-slate-600 font-medium flex items-center gap-2">
                        <i data-lucide="building-2" class="w-3.5 h-3.5 text-slate-400"></i>
                        ${esc(branch.name)}
                    </span>
                    <div>${inputHtml}</div>`;
                activeBranchesList.appendChild(row);
            });

            if (typeof lucide !== 'undefined') lucide.createIcons();
            updateHiddenInputs();
            updateSummaryList();
            validateAndToggleSubmit();
        }

        function updateHiddenInputs() {
            if (!hiddenInputsContainer) return;
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
            selectedBranches.forEach(branch => {
                const pct = totalAmount > 0
                    ? ((branch.value / totalAmount) * 100).toFixed(1)
                    : (branch.percent || 0).toFixed(1);
                summaryBranchesList.insertAdjacentHTML('beforeend', `
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-300">${esc(branch.name)}</span>
                        <div class="text-right">
                            <div class="text-emerald-400 font-bold">${formatRupiah(branch.value)}</div>
                            <div class="text-[9px] text-slate-500">${pct}%</div>
                        </div>
                    </div>`);
            });
        }

        function validateAndToggleSubmit() {
            let isValid = true;

            if (selectedBranches.length === 0) {
                isValid = false;
                if (percentWarning) percentWarning.classList.add('hidden');
            } else if (currentMethod === 'percent') {
                const totalPct = selectedBranches.reduce((s, b) => s + (parseFloat(b.percent) || 0), 0);
                if (Math.abs(totalPct - 100) > 0.5) {
                    isValid = false;
                    if (percentWarning) {
                        percentWarning.textContent = `⚠ Total persen ${totalPct.toFixed(1)}% — harus 100%`;
                        percentWarning.classList.remove('hidden');
                    }
                } else {
                    if (percentWarning) percentWarning.classList.add('hidden');
                }
            } else {
                if (percentWarning) percentWarning.classList.add('hidden');
            }

            submitBtn.disabled = !isValid;
            if (isValid) {
                submitBtn.classList.remove('bg-slate-700', 'text-slate-500');
                submitBtn.classList.add('bg-emerald-500');
            } else {
                submitBtn.classList.add('bg-slate-700', 'text-slate-500');
                submitBtn.classList.remove('bg-emerald-500');
            }
        }

        document.getElementById('transaction-form').addEventListener('submit', function (e) {
            if (submitBtn.disabled) { e.preventDefault(); return; }
            submitBtn.disabled = true;
            document.getElementById('submit-text').textContent = 'Memproses...';
            document.getElementById('submit-spinner').classList.remove('hidden');
        });

        renderItems();
    });
    </script>
@endpush
@endsection