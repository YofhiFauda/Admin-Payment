@extends('layouts.app')

@section('page-title', 'Edit Pengajuan')

@section('content')
    {{-- Form Container --}}
    <div class="bg-white shadow-sm border border-slate-100 p-3 pt-6 md:p-8 lg:p-10">
    {{-- <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-6 md:p-8 lg:p-10"> --}}

        {{-- Header --}}
        <div class="mb-8 md:mb-10 flex items-center gap-4">
            <a href="{{ route('transactions.index') }}"
               class="w-8 h-8 flex items-center justify-center rounded-full border border-slate-200 text-slate-400 hover:text-emerald-600 hover:border-emerald-200 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div class="flex-1">
                <div class="flex items-center gap-3 flex-wrap">
                    <h2 class="text-lg md:text-xl font-extrabold text-slate-800">Edit Pengajuan Beli</h2>
                </div>
                <p class="text-xs md:text-sm text-slate-400 mt-1">
                    Perbarui data pengajuan — Ref: <span class="font-bold text-teal-600">{{ $transaction->invoice_number }}</span>
                </p>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════
        VERSION SWITCHER COMPONENT
        Tambahkan di BAGIAN ATAS edit-pengajuan.blade.php
        (Setelah header, sebelum form)
        ═══════════════════════════════════════════════════════════════ --}}
    
        {{-- ═══════════════════════════════════════════════════════════════
        READ-ONLY BANNER — Admin (hanya bisa lihat, tidak bisa edit)
        & Proteksi Status Selesai
        ═══════════════════════════════════════════════════════════════ --}}

        @if($isReadOnly ?? false)
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 md:p-5 mb-6 flex items-start gap-3">
                <div class="w-9 h-9 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-triangle w-5 h-5 text-amber-600">
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                        <path d="M12 9v4"></path>
                        <path d="M12 17h.01"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-amber-900 mb-0.5">
                        @if($isAdminOnlyBranch ?? false)
                            Mode Edit Terbatas (Admin)
                        @elseif($isSettlement ?? false)
                            Transaksi Dikunci (Menunggu Pelunasan)
                        @elseif($transaction->status === 'completed')
                            Transaksi Dikunci (Selesai)
                        @else
                            Mode Hanya Baca (Review)
                        @endif
                    </h4>
                    <p class="text-xs leading-relaxed text-amber-700">
                        @if($isAdminOnlyBranch ?? false)
                            Anda masuk dengan peran <strong>Admin</strong>. Anda diperbolehkan mengubah <strong>Pembagian Cabang</strong> serta meninjau <strong>perbandingan versi</strong> pengajuan. Data barang tetap terkunci.
                        @elseif($isSettlement ?? false)
                            @if(Auth::user()->isAdmin())
                                Transaksi sudah masuk fase <strong>Pelunasan</strong>. Halaman ini difungsikan untuk meninjau perbandingan versi pengajuan; perubahan data sudah tidak dapat dilakukan.
                            @else
                                Transaksi ini sudah memasuki tahap <strong>Pelunasan</strong>. Data tidak dapat diubah untuk menjaga konsistensi finansial.
                            @endif
                        @elseif($transaction->status === 'completed')
                            Transaksi ini sudah berstatus <strong>Selesai</strong>. Seluruh data telah dikunci dan tidak dapat diubah kembali.
                        @else
                            @if(Auth::user()->isAdmin())
                                Halaman ini difungsikan secara khusus untuk meninjau perbandingan versi pengajuan; modifikasi data tidak dapat dilakukan pada mode ini.
                            @else
                                Halaman ini dalam mode <strong>Hanya Baca</strong> (Read-Only). Modifikasi data tidak diperbolehkan pada tahap ini.
                            @endif
                        @endif
                    </p>
                </div>
            </div>
        @endif

        @if($transaction->isPengajuan() && ($transaction->hasBeenEditedByManagement() || Auth::user()->isAdmin() || ($isWaitingPayment ?? false) || ($isSettlement ?? false)))
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-2xl p-5 md:p-6 mb-6 shadow-sm">
                
                {{-- Header Info --}}
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-5">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="git-branch" class="w-5 h-5 text-blue-600"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-800 mb-1" id="version-header-title">
                                @if($isSettlement ?? false)
                                    Peninjauan Transaksi (Fase Pelunasan)
                                @elseif($isAdminOnlyBranch ?? false)
                                    Peninjauan Transaksi (Mode Admin — Edit Terbatas)
                                @else
                                    Pengajuan Telah Direvisi oleh Management
                                @endif
                            </h3>
                            <p class="text-xs text-slate-500" id="version-header-subtitle">
                                @if($transaction->hasBeenEditedByManagement())
                                    Terakhir diedit oleh <span class="font-bold text-blue-600">{{ $transaction->editor->name ?? 'N/A' }}</span>
                                    pada {{ $transaction->edited_at ? $transaction->edited_at->format('d M Y, H:i') : '-' }}
                                    <span class="inline-block bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-[10px] font-bold ml-1">
                                        Revisi ke-{{ $transaction->revision_count }}
                                    </span>
                                @else
                                    Menampilkan perbandingan antara data asli teknisi dan penyesuaian management.
                                @endif
                            </p>
                        </div>
                    </div>
                    
                    {{-- Version Toggle Switch --}}
                    <div class="flex bg-white rounded-xl p-1.5 shadow-sm border border-slate-200 self-start md:self-center">
                        <button type="button" 
                            id="btn-version-original" 
                            data-version="original"
                            class="version-toggle-btn px-4 py-2 rounded-lg text-xs font-bold transition-all bg-blue-500 text-white shadow-sm">
                            <i data-lucide="user" class="w-3.5 h-3.5 inline-block mr-1.5"></i>
                            Versi Pengaju
                        </button>
                        <button type="button" 
                            id="btn-version-management" 
                            data-version="management"
                            class="version-toggle-btn px-4 py-2 rounded-lg text-xs font-bold transition-all text-slate-600 hover:text-slate-800">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 inline-block mr-1.5"></i>
                            Versi Management
                        </button>
                    </div>
                </div>
        
                {{-- Legend --}}
                <div class="flex flex-wrap gap-3 text-[10px] md:text-xs">
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 bg-emerald-100 border border-emerald-300 rounded"></div>
                        <span class="text-slate-600 font-medium">Ditambahkan</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 bg-amber-100 border border-amber-300 rounded"></div>
                        <span class="text-slate-600 font-medium">Diubah</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 bg-red-100 border border-red-300 rounded"></div>
                        <span class="text-slate-600 font-medium">Dihapus</span>
                    </div>
                </div>
        
                {{-- Hidden Data untuk JS --}}
                <script id="version-data" type="application/json">
                    {!! json_encode([
                        'original' => $transaction->getOriginalVersion(),
                        'management' => $transaction->getManagementVersion(),
                        'changes' => $transaction->getItemChanges(),
                        'global_changes' => [
                            'dpp_lainnya' => $transaction->dpp_lainnya > 0,
                            'tax_amount' => $transaction->tax_amount > 0,
                            'biaya_layanan_1' => $transaction->biaya_layanan_1 > 0
                        ],
                        'is_settlement' => (bool) ($isSettlement ?? false),
                        'is_admin_only_branch' => (bool) ($isAdminOnlyBranch ?? false)
                    ]) !!}
                </script>
            </div>
@endif

        <form method="POST" action="{{ route('transactions.update', $transaction->id) }}" id="pengajuan-form" 
            class="{{ ($isReadOnly ?? false) ? 'version-readonly' : '' }} {{ ($isAdminOnlyBranch ?? false) ? 'form-admin-restricted-mode' : '' }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="type" value="pengajuan">
            
            {{-- Container untuk input tersembunyi distribusi --}}
            <div id="distribution-hidden-inputs"></div>

            @php
                // Build array of items to render. Support old transactions without 'items' JSON data
                $itemsToRender = is_array($transaction->normalized_items) && count($transaction->normalized_items) > 0 
                    ? $transaction->normalized_items 
                    : [
                        [
                            'customer'        => $transaction->customer,
                            'vendor'          => $transaction->vendor,
                            'link'            => $transaction->link,
                            'description'     => $transaction->description,
                            'category'        => $transaction->category_label, // Use normalized label
                            'quantity'        => $transaction->quantity ?? 1,
                            'estimated_price' => $transaction->estimated_price ?? $transaction->amount ?? 0,
                            'specs'           => is_array($transaction->specs) ? $transaction->specs : []
                        ]
                    ];

                // Calculate Pre-Rendered Totals
                $phpItemsTotal = collect($itemsToRender)->sum(function($item) {
                    return ($item['quantity'] ?? 1) * ($item['estimated_price'] ?? 0);
                });
                $phpGrandTotal = $phpItemsTotal + ($transaction->dpp_lainnya ?? 0) + ($transaction->tax_amount ?? 0) + ($transaction->biaya_layanan_1 ?? 0);
            @endphp
            
            @php
                $isPdf = $transaction->file_path && strtolower(pathinfo($transaction->file_path, PATHINFO_EXTENSION)) === 'pdf';
            @endphp
            
            {{-- 1. FOTO REFERENSI --}}
            <div class="mb-8 md:mb-10">
                <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-3 tracking-wider">
                    Foto Referensi
                </label>
                
                @if($transaction->file_path)
                    <div id="ref-photo-wrapper" 
                         onclick="window.openImageViewer('{{ asset('storage/' . $transaction->file_path) }}', 'Foto Referensi', {{ $isPdf ? 'true' : 'false' }})"
                         tabindex="0" 
                         class="border-2 border-emerald-200 rounded-2xl p-2 bg-slate-50/50 flex justify-center relative overflow-hidden cursor-pointer hover:border-emerald-400 focus-within:ring-2 focus-within:ring-emerald-500 transition-colors group" 
                         title="Klik untuk memperbesar">
                        
                        @if($isPdf)
                            <div class="flex flex-col items-center justify-center py-10 px-6">
                                <div class="w-16 h-16 bg-emerald-100 rounded-2xl flex items-center justify-center text-emerald-600 mb-3 shadow-sm border border-emerald-200">
                                    <i data-lucide="file-text" class="w-8 h-8"></i>
                                </div>
                                <span class="text-xs font-black text-emerald-700 uppercase tracking-widest text-center">LIHAT DOKUMEN PDF</span>
                                <span class="text-[10px] text-emerald-400 font-bold mt-1 uppercase text-center truncate max-w-[200px]">{{ basename($transaction->file_path) }}</span>
                            </div>
                        @else
                            <img src="{{ asset('storage/' . $transaction->file_path) }}" class="max-h-48 object-contain rounded-xl" alt="Reference Photo">
                        @endif
                        
                        {{-- Preview Badge --}}
                        <div class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm rounded-full px-3 py-1.5 shadow-lg flex items-center gap-1.5 pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity">
                            <i data-lucide="{{ $isPdf ? 'eye' : 'zoom-in' }}" class="w-3.5 h-3.5 text-emerald-600"></i>
                            <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">{{ $isPdf ? 'Lihat PDF' : 'Preview Foto' }}</span>
                        </div>
                    </div>
                @else
                    <div class="relative border-2 border-dashed border-slate-200 rounded-2xl p-6 bg-slate-50/50 flex flex-col items-center justify-center text-slate-400 max-w-6xl mx-auto min-h-[100px]">
                        <i data-lucide="image-off" class="w-8 h-8 mb-2 opacity-20"></i>
                        <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Tidak ada foto referensi</span>
                    </div>
                @endif
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
                        <p class="text-[10px] md:text-xs text-slate-400 mt-1">Kelola barang yang diajukan</p>
                    </div>
                    @if(!($isAdminOnlyBranch ?? false))
                    <button type="button" id="btn-add-item" class="flex items-center justify-center gap-2 bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white border border-emerald-200 hover:border-emerald-600 px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-sm">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Tambah Barang
                    </button>
                    @endif
                </div>

                <div id="items-container" class="space-y-6">
                    @if(!$transaction->hasBeenEditedByManagement() && !Auth::user()->isAdmin())
                    {{-- Only pre-render items via PHP when version switcher is NOT active --}}
                    {{-- When version switcher is active, JS renderVersion() handles rendering --}}
                    @foreach($itemsToRender as $index => $item)
                        @php
                            $errPrefix = "items." . $index . ".";
                            $qty = old($errPrefix . 'quantity', $item['quantity'] ?? 1);
                            $price = old($errPrefix . 'estimated_price', $item['estimated_price'] ?? 0);
                            $subtotal = $qty * $price;
                            $specs = old($errPrefix . 'specs', $item['specs'] ?? []);
                        @endphp
                        <div class="item-card bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow" data-index="{{ $index }}">
                            {{-- Header (Clickable for expand/collapse) --}}
                            <div class="item-header bg-slate-50 px-4 py-6 cursor-pointer flex items-center justify-between border-b border-slate-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 font-bold text-xs item-number">{{ $index + 1 }}</div>
                                    <div>
                                        <h4 class="font-bold text-slate-700 text-sm item-title">{{ old($errPrefix . 'customer', $item['customer'] ?? 'Barang Tanpa Nama') }}</h4>
                                        <p class="text-[10px] item-subtitle text-slate-400">Rp {{ number_format($price, 0, ',', '.') }} x {{ $qty }}</p>
                                    </div>
                                </div>
                                <div class="item-header-actions flex items-center gap-3">
                                    {{-- Badge Container for JS --}}
                                    <div class="badge-mount"></div>
                                    
                                    <div class="flex items-center gap-2">
                                        @if(!($isAdminOnlyBranch ?? false))
                                        <button type="button" class="btn-remove-item text-red-500 hover:bg-red-50 p-2 rounded-lg transition-colors {{ count($itemsToRender) <= 1 ? 'hidden' : '' }}" title="Hapus Barang">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                        @endif
                                        <i data-lucide="chevron-down" class="w-5 h-5 text-slate-400 transition-transform duration-200 icon-collapse"></i>
                                    </div>
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
                                        <div class="md:col-span-2 relative">
                                            <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Nama Barang/Jasa *</label>
                                            <input type="hidden" name="items[{{ $index }}][master_item_id]" class="input-master-id" value="{{ old($errPrefix . 'master_item_id', $item['master_item_id'] ?? '') }}">
                                            <input type="text" name="items[{{ $index }}][customer]" required value="{{ old($errPrefix . 'customer', $item['customer'] ?? '') }}" autocomplete="off"
                                                class="input-customer w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all"
                                                placeholder="Ketik nama barang... (Autocomplete Cerdas)">
                                                
                                            <!-- Autocomplete Dropdown -->
                                            <div class="autocomplete-dropdown hidden absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                                                <ul class="autocomplete-list text-sm"></ul>
                                            </div>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Informasi Vendor</label>
                                            <input type="text" name="items[{{ $index }}][vendor]" value="{{ old($errPrefix . 'vendor', $item['vendor'] ?? '') }}"
                                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all"
                                                placeholder="Contoh: Toko Komputer Jaya">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Link Barang/Referensi</label>
                                            <input type="url" name="items[{{ $index }}][link]" value="{{ old($errPrefix . 'link', $item['link'] ?? '') }}"
                                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all"
                                                placeholder="https://tokopedia.link/...">
                                        </div>
                                    </div>
                                </div>

                                {{-- 3. SPESIFIKASI --}}
                                <div class="mt-8">
                                    <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-4 tracking-wider">Spesifikasi Barang</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 md:gap-6">
                                        <div><label class="block text-[10px] md:text-[11px] mb-2 font-bold text-slate-500 uppercase tracking-wider">Merk</label><input type="text" name="items[{{ $index }}][specs][merk]" value="{{ $specs['merk'] ?? '' }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-400 transition-all" placeholder="Contoh: Xpon CDATA"></div>
                                        <div><label class="block text-[10px] md:text-[11px] mb-2 font-bold text-slate-500 uppercase tracking-wider">Tipe / Seri</label><input type="text" name="items[{{ $index }}][specs][tipe]" value="{{ $specs['tipe'] ?? '' }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-400 transition-all" placeholder="Contoh: FD512XW-R460"></div>
                                        <div><label class="block text-[10px] md:text-[11px] mb-2 font-bold text-slate-500 uppercase tracking-wider">Ukuran</label><input type="text" name="items[{{ $index }}][specs][ukuran]" value="{{ $specs['ukuran'] ?? '' }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-400 transition-all" placeholder="Contoh: 30x30"></div>
                                        <div><label class="block text-[10px] md:text-[11px] mb-2 font-bold text-slate-500 uppercase tracking-wider">Warna</label><input type="text" name="items[{{ $index }}][specs][warna]" value="{{ $specs['warna'] ?? '' }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-400 transition-all" placeholder="Contoh: Putih"></div>
                                    </div>
                                </div>

                                {{-- 4 & 5. ALASAN & HARGA --}}
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
                                    
                                    {{-- Alasan Pembelian --}}
                                    <div>
                                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-3 tracking-wider">Alasan Pembelian / Kategori *</label>
                                        <div class="relative">
                                            <select name="items[{{ $index }}][category]" required class="input-reason w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all appearance-none">
                                                <option value="">— Pilih alasan —</option>
                                                @foreach($pengajuanCategories as $cat)
                                                    <option value="{{ $cat->name }}" {{ old($errPrefix . 'category', $item['category'] ?? '') == $cat->name ? 'selected' : '' }}>{{ $cat->name }}</option>
                                                @endforeach
                                            </select>
                                            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                                        </div>
                                        <div class="mt-4 keterangan-container">
                                            <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">
                                                Keterangan <span class="keterangan-required text-red-500 hidden">*</span>
                                            </label>
                                            <textarea name="items[{{ $index }}][description]" rows="2"
                                                class="input-desc w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all"
                                                placeholder="Tambahkan detail/alasan tambahan...">{{ old($errPrefix . 'description', $item['description'] ?? '') }}</textarea>
                                        </div>
                                    </div>

                                    {{-- Estimasi Biaya --}}
                                    <div class="bg-slate-50 p-4 md:p-5 rounded-xl border border-slate-100">
                                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-4 tracking-wider">Estimasi Biaya</label>
                                        <div class="space-y-4">
                                            {{-- Price Index Reference Info --}}
                                            <div class="price-ref-box hidden bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 text-xs">
                                                <div class="flex items-center justify-between gap-2 mb-2">
                                                    <span class="text-blue-600 font-bold uppercase tracking-wider text-[10px]">📊 Referensi Harga</span>
                                                    <span class="price-ref-source text-blue-400 text-[10px]"></span>
                                                </div>
                                                <div class="space-y-2 text-center">
                                                    <button type="button" class="btn-fill-avg w-full bg-blue-100 hover:bg-blue-200 text-blue-700 font-bold rounded-lg py-2 transition text-xs flex items-center justify-between px-4">
                                                        <span class="text-[10px] text-blue-500 uppercase font-black tracking-widest">Rata-rata (AVG)</span>
                                                        <span class="price-ref-avg font-black text-sm">-</span>
                                                    </button>
                                                    {{-- Hidden min/max for JS compatibility if needed --}}
                                                    <span class="price-ref-min hidden"></span>
                                                    <span class="price-ref-max hidden"></span>
                                                    <button type="button" class="btn-fill-min hidden"></button>
                                                    <button type="button" class="btn-fill-max hidden"></button>
                                                </div>
                                            </div>

                                            {{-- Warning badge jika harga melebihi max --}}
                                            <div class="price-anomaly-warning hidden bg-red-50 border border-red-200 rounded-xl px-4 py-2.5 flex items-center gap-2 text-xs text-red-700">
                                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                </svg>
                                                <span class="price-warning-text font-semibold">Harga melebihi referensi maksimum! Owner akan diberitahu.</span>
                                            </div>

                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Estimasi Harga Satuan *</label>
                                                <div class="relative">
                                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">Rp</span>
                                                    <input type="text" required class="input-price-display w-full bg-white border border-slate-200 rounded-xl pl-12 pr-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all" value="{{ $price > 0 ? number_format($price, 0, ',', '.') : '' }}">
                                                    <input type="hidden" name="items[{{ $index }}][estimated_price]" class="input-price-hidden" value="{{ $price }}">
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Jumlah *</label>
                                                <input type="number" name="items[{{ $index }}][quantity]" value="{{ $qty }}" required min="1" class="input-qty w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all">
                                            </div>
                                            <div class="pt-2 border-t border-slate-200 flex justify-between items-center">
                                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Subtotal</span>
                                                <span class="text-sm md:text-base font-bold text-emerald-600 item-subtotal">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    @endforeach
                    @endif {{-- end: !hasBeenEditedByManagement && !isAdmin --}}
                </div>
                
                {{-- Global Total Estimate (Replaces the specific sidebar) --}}
                <div class="mt-6 bg-slate-50 p-5 md:p-6 rounded-2xl border border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <span class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Estimasi Biaya Keseluruhan</span>
                        <p class="text-xs text-slate-500">Total estimasi dari seluruh barang di atas</p>
                    </div>
                    <div class="text-left sm:text-right w-full sm:w-auto bg-white border border-slate-200 px-6 py-4 rounded-xl shadow-sm">
                        <div id="total-estimate-global" class="text-xl md:text-2xl font-black text-emerald-600">Rp {{ number_format($phpItemsTotal, 0, ',', '.') }}</div>
                        <input type="hidden" name="estimated_price" id="form-total-estimated-price" value="{{ $phpItemsTotal }}">
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
                        @php
                            $isSelected = $transaction->branches->contains('id', $branch->id);
                        @endphp
                        <button type="button"
                            data-id="{{ $branch->id }}"
                            data-name="{{ $branch->name }}"
                            data-preselected="{{ $isSelected ? 'true' : 'false' }}"
                            @if($isSelected)
                                data-preset-percent="{{ $transaction->branches->find($branch->id)->pivot->allocation_percent ?? 0 }}"
                                data-preset-amount="{{ $transaction->branches->find($branch->id)->pivot->allocation_amount ?? 0 }}"
                            @endif
                            class="branch-pill px-4 py-2.5 rounded-full text-xs font-bold border transition-all cursor-pointer
                                {{ $isSelected
                                    ? 'bg-emerald-500 text-white border-emerald-500 shadow-md'
                                    : 'border-slate-200 bg-white text-slate-600 hover:bg-teal-50 hover:border-teal-300' }}">
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

            {{-- SUMMARY BILLING (FULL WIDTH BLACK CARD) --}}
            {{-- ══════════════════════════════════ --}}
            <div id="summary-billing-section" class="bg-[#1a1c23] rounded-[2rem] p-6 md:p-8 lg:p-10 text-white relative overflow-hidden shadow-xl">
                <div class="absolute -right-20 -top-20 w-64 h-64 bg-white/[0.02] rounded-full pointer-events-none"></div>

                {{-- DPP, PPN & Service Fee Inputs --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-10 relative z-10">
                    <div class="group global-field-container" data-field="dpp_lainnya">
                        <div class="flex items-center justify-between mb-2.5 ml-1">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider group-focus-within:text-emerald-400 transition-colors">
                                DPP Lainnya <span class="text-[9px] font-normal text-slate-500 lowercase">(biaya tambahan)</span>
                            </label>
                            <div class="global-badge-mount"></div>
                        </div>
                        <div class="relative field-input-wrapper">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-500">Rp</span>
                            <input type="text" id="input-dpp-lainnya-display" 
                                class="w-full bg-white/[0.03] border border-white/10 rounded-2xl pl-12 pr-4 py-4 text-sm font-bold text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 transition-all placeholder:text-slate-700" 
                                value="{{ $transaction->dpp_lainnya > 0 ? number_format($transaction->dpp_lainnya, 0, ',', '.') : '' }}"
                                placeholder="0">
                            <input type="hidden" name="dpp_lainnya" id="input-dpp-lainnya-hidden" value="{{ $transaction->dpp_lainnya ?? 0 }}">
                        </div>
                    </div>
                    <div class="group global-field-container" data-field="tax_amount">
                        <div class="flex items-center justify-between mb-2.5 ml-1">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider group-focus-within:text-emerald-400 transition-colors">
                                PPN <span class="text-[9px] font-normal text-slate-500 lowercase">(pajak pertambahan nilai)</span>
                            </label>
                            <div class="global-badge-mount"></div>
                        </div>
                        <div class="relative field-input-wrapper">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-500">Rp</span>
                            <input type="text" id="input-ppn-display" 
                                class="w-full bg-white/[0.03] border border-white/10 rounded-2xl pl-12 pr-4 py-4 text-sm font-bold text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 transition-all placeholder:text-slate-700" 
                                value="{{ $transaction->tax_amount > 0 ? number_format($transaction->tax_amount, 0, ',', '.') : '' }}"
                                placeholder="0">
                            <input type="hidden" name="tax_amount" id="input-ppn-hidden" value="{{ $transaction->tax_amount ?? 0 }}">
                        </div>
                    </div>
                    <div class="group global-field-container" data-field="biaya_layanan_1">
                        <div class="flex items-center justify-between mb-2.5 ml-1">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider group-focus-within:text-emerald-400 transition-colors">
                                Biaya Layanan 1 <span class="text-[9px] font-normal text-slate-500 lowercase">(service fee)</span>
                            </label>
                            <div class="global-badge-mount"></div>
                        </div>
                        <div class="relative field-input-wrapper">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-500">Rp</span>
                            <input type="text" id="input-layanan1-display" 
                                class="w-full bg-white/[0.03] border border-white/10 rounded-2xl pl-12 pr-4 py-4 text-sm font-bold text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 transition-all placeholder:text-slate-700" 
                                value="{{ $transaction->biaya_layanan_1 > 0 ? number_format($transaction->biaya_layanan_1, 0, ',', '.') : '' }}"
                                placeholder="0">
                            <input type="hidden" name="biaya_layanan_1" id="input-layanan1-hidden" value="{{ $transaction->biaya_layanan_1 ?? 0 }}">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 mb-8 md:mb-10 relative z-10">
                    {{-- Left Side: Total --}}
                    <div>
                        <span class="text-[9px] md:text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Total Pengajuan</span>
                        <div class="text-3xl md:text-4xl lg:text-5xl font-black text-emerald-400 mb-4 md:mb-6 tracking-tight" id="summary-total">Rp {{ number_format($phpGrandTotal, 0, ',', '.') }}</div>
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

                {{-- Submit Button (Hidden for Read-Only mode, unless Admin editing branches) --}}
                @if(!($isReadOnly ?? false) || ($isAdminOnlyBranch ?? false))
                <button type="submit" id="summary-submit" disabled
                    class="w-full relative z-10 bg-emerald-500 hover:bg-emerald-400 disabled:bg-slate-700 disabled:text-slate-500 text-white font-bold py-4 md:py-5 rounded-2xl transition-all shadow-[0_8px_20px_-6px_rgba(16,185,129,0.4)] disabled:shadow-none text-xs md:text-sm uppercase tracking-wider cursor-pointer disabled:cursor-not-allowed flex items-center justify-center gap-2">
                    <span id="submit-text">Simpan Perubahan</span>
                    <svg id="submit-spinner" class="animate-spin h-5 w-5 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                </button>
                @else
                {{-- Read-Only: Tampilkan tombol Kembali --}}
                <a href="{{ route('transactions.index') }}"
                    class="w-full relative z-10 bg-slate-600 hover:bg-slate-500 text-white font-bold py-4 md:py-5 rounded-2xl transition-all text-xs md:text-sm uppercase tracking-wider flex items-center justify-center gap-2">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    Kembali ke Daftar Transaksi
                </a>
                @endif
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
    
    {{-- ══════════════════════════════════════════════════ --}}
    {{-- IMAGE VIEWER MODAL                                --}}
    {{-- hidden → flex saat dibuka via JS                 --}}
    {{-- ══════════════════════════════════════════════════ --}}
    {{-- ✅ IMAGE/PDF VIEWER MODAL (Fullscreen Zoom) --}}
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
                    <h3 class="text-sm sm:text-base font-black text-slate-800 uppercase tracking-widest" id="viewer-header-title">PREVIEW DOKUMEN</h3>
                    <p id="viewer-title" class="text-[10px] text-slate-400 font-bold mt-1 uppercase tracking-wider">Klik di luar gambar atau X untuk menutup</p>
                </div>
                <button id="close-viewer"
                        type="button"
                        onclick="closeImageViewer()"
                        class="w-10 h-10 sm:w-12 sm:h-12 flex items-center justify-center rounded-2xl bg-slate-100 hover:bg-red-50 text-slate-500 hover:text-red-500 transition-all active:scale-95"
                        aria-label="Tutup preview">
                    <i data-lucide="x" class="w-5 h-5 sm:w-6 sm:h-6"></i>
                </button>
            </div>

            {{-- Gambar/PDF Wrapper dengan Background Grid/Dots --}}
            <div class="w-full flex-1 flex justify-center items-center bg-slate-50 rounded-2xl overflow-hidden relative border-2 border-slate-100 p-2 sm:p-4">
                <div class="absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(#000 1px, transparent 1px); background-size: 20px 20px;"></div>
                <img id="viewer-image"
                     src=""
                     class="relative z-10 max-w-full max-h-full object-contain drop-shadow-2xl rounded-lg"
                     alt="Preview foto referensi" />

                {{-- PDF Viewer Iframe --}}
                <iframe id="viewer-pdf" class="hidden relative z-10 w-full h-full rounded-lg border-0" src=""></iframe>
            </div>

            {{-- Footer for PDF Actions --}}
            <div id="viewer-footer" class="mt-6 flex justify-center hidden">
                <a id="viewer-pdf-link" href="#" target="_blank" class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-xl text-xs font-bold transition-all shadow-lg shadow-emerald-600/20 active:scale-95">
                    <i data-lucide="external-link" class="w-4 h-4"></i>
                    BUKA DI TAB BARU / DOWNLOAD
                </a>
            </div>
        </div>
    </div>

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
                <div class="item-header-actions flex items-center gap-3">
                    {{-- Badge Container --}}
                    <div class="badge-mount"></div>
                    
                    <div class="flex items-center gap-2">
                        <button type="button" class="btn-remove-item text-red-500 hover:bg-red-50 p-2 rounded-lg transition-colors hidden" title="Hapus Barang">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                        <i data-lucide="chevron-down" class="w-5 h-5 text-slate-400 transition-transform duration-200 icon-collapse"></i>
                    </div>
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
                        <div class="md:col-span-2 relative">
                            <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Nama Barang/Jasa *</label>
                            <input type="hidden" name="items[__INDEX__][master_item_id]" class="input-master-id">
                            <input type="text" name="items[__INDEX__][customer]" required autocomplete="off"
                                class="input-customer w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all"
                                placeholder="Ketik nama barang... (Autocomplete Cerdas)">
                                
                            <!-- Autocomplete Dropdown -->
                            <div class="autocomplete-dropdown hidden absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                                <ul class="autocomplete-list text-sm"></ul>
                            </div>
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
                            {{-- Price Index Reference Info --}}
                            <div class="price-ref-box hidden bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 text-xs">
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <span class="text-blue-600 font-bold uppercase tracking-wider text-[10px]">📊 Referensi Harga</span>
                                    <span class="price-ref-source text-blue-400 text-[10px]"></span>
                                </div>
                                <div class="space-y-2 text-center">
                                    <button type="button" class="btn-fill-avg w-full bg-blue-100 hover:bg-blue-200 text-blue-700 font-bold rounded-lg py-2 transition text-xs flex items-center justify-between px-4">
                                        <span class="text-[10px] text-blue-500 uppercase font-black tracking-widest">Rata-rata (AVG)</span>
                                        <span class="price-ref-avg font-black text-sm">-</span>
                                    </button>
                                    {{-- Hidden min/max for JS compatibility --}}
                                    <span class="price-ref-min hidden"></span>
                                    <span class="price-ref-max hidden"></span>
                                    <button type="button" class="btn-fill-min hidden"></button>
                                    <button type="button" class="btn-fill-max hidden"></button>
                                </div>
                            </div>

                            {{-- Warning badge jika harga melebihi max --}}
                            <div class="price-anomaly-warning hidden bg-red-50 border border-red-200 rounded-xl px-4 py-2.5 flex items-center gap-2 text-xs text-red-700">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span class="price-warning-text font-semibold">Harga melebihi referensi maksimum! Owner akan diberitahu.</span>
                            </div>

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

    {{-- Indicator Badge (Tampil di setiap item card) --}}
    {{-- Template untuk badge change type - akan diinjek via JS --}}
    <template id="change-badge-template">
        <div class="change-badge px-2.5 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider shadow-sm flex items-center gap-1.5 animate-in fade-in zoom-in duration-300">
            <i data-lucide="refresh-cw" class="w-3 h-3 badge-icon"></i>
            <span class="badge-text">Modified</span>
        </div>
    </template>
    </div>
@endsection



<style>
    /* Badge styles berdasarkan tipe perubahan */
    .change-badge {
        display: inline-flex;
        align-items: center;
        white-space: nowrap;
    }

    .change-badge.added {
        background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
        color: #059669;
        border: 1.5px solid #10b981;
    }
    
    .change-badge.modified {
        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        color: #d97706;
        border: 1.5px solid #fbbf24;
    }
    
    .change-badge.removed {
        background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        color: #dc2626;
        border: 1.5px solid #f87171;
    }

    /* Small screen adjustment */
    @media (max-width: 640px) {
        .change-badge {
            padding: 0.25rem 0.5rem;
            font-size: 8px;
        }
        .change-badge .badge-icon {
            width: 0.6rem;
            height: 0.6rem;
        }
        .item-header {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }
    }
    
    /* Highlight fields yang berubah */
    .field-changed {
        position: relative;
    }
    
    .field-changed::before {
        content: '';
        position: absolute;
        inset: -2px;
        background: linear-gradient(135deg, rgba(251, 191, 36, 0.15) 0%, rgba(245, 158, 11, 0.15) 100%);
        border: 2px solid #fbbf24;
        border-radius: 0.75rem;
        pointer-events: none;
        z-index: 0;
    }
    
    .field-changed input,
    .field-changed select,
    .field-changed textarea {
        position: relative;
        z-index: 1;
    }
    
    /* ✅ UI: Item Highlight — newly added or recently edited items */
    @keyframes item-highlight-pulse {
        0%   { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
        70%  { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
        100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    .item-card.item-newly-added {
        border-color: #10b981 !important;
        box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25), 0 4px 12px rgba(16, 185, 129, 0.15) !important;
        animation: item-highlight-pulse 1.5s ease-out;
    }

    .item-card.item-newly-added .item-header {
        background-color: #ecfdf5 !important;
        border-bottom-color: #a7f3d0 !important;
    }

    @keyframes item-edited-flash {
        0%   { border-color: #f59e0b; box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.3); }
        100% { border-color: #e2e8f0; box-shadow: none; }
    }

    .item-card.item-just-edited {
        animation: item-edited-flash 2s ease-out forwards;
    }

    /* Read-only mode untuk versi pengaju & Admin restricted */
    .version-readonly .item-body input,
    .version-readonly .item-body select,
    .version-readonly .item-body textarea,
    .version-readonly #summary-billing-section input:not([type="hidden"]) {
        cursor: not-allowed !important;
        pointer-events: none !important;
        background-color: rgba(255, 255, 255, 0.05) !important;
        color: #e2e8f0 !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    /* Admin restricted mode adjustment: allow interaction in branch section */
    .form-admin-restricted-mode .method-btn,
    .form-admin-restricted-mode .branch-pill,
    .form-admin-restricted-mode #distribution-list input,
    .form-admin-restricted-mode #summary-submit,
    .form-admin-restricted-mode #btn-version-original,
    .form-admin-restricted-mode #btn-version-management {
        pointer-events: auto !important;
        cursor: pointer !important;
        opacity: 1 !important;
        filter: grayscale(0) !important;
    }

    .form-admin-restricted-mode #distribution-list input {
        cursor: text !important;
    }


    .dist-row-grid {
        display: grid;
        grid-template-columns: 1fr 100px 140px;
        gap: 1rem;
        align-items: center;
        padding: 0.75rem 1rem;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        margin-bottom: 0.5rem;
        transition: all 0.2s ease;
    }

    .dist-row-grid:hover {
        border-color: #10b981;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    }


    /* Khusus untuk input di luar black card (bg putih) */
    .version-readonly .bg-white input,
    .version-readonly .bg-white select,
    .version-readonly .bg-white textarea {
        background-color: #f8fafc !important;
        color: #475569 !important;
        border-color: #e2e8f0 !important;
    }


    .version-readonly .btn-remove-item,
    .version-readonly #btn-add-item {
        display: none !important;
    }

</style>

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
            // Handle keyboard enter/space to open preview
            refWrapper.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    const img = this.querySelector('img');
                    if (img) openViewer(img.src);
                }
            });
        }
        if (closeViewer) { closeViewer.addEventListener('click', function(e) { e.stopPropagation(); closeViewerFn(); }); }
        if (imageViewer) { imageViewer.addEventListener('click', function(e) { if (e.target === imageViewer) closeViewerFn(); }); }
        if (imageViewer) {
            document.addEventListener('keydown', function(e) { 
                if (e.key === 'Escape' && !imageViewer.classList.contains('hidden')) closeViewerFn(); 
            });
        }

        // ═══════════════════════════════════════
        // INITIAL DATA LOAD
        // ═══════════════════════════════════════
    // ───────────────────────────────────────────────────
    // Helper functions
    // ───────────────────────────────────────────────────
    function parseRupiah(str) { return parseInt((str || '').toString().replace(/\D/g, '') || '0'); }
    function formatRupiah(num) { return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }

    @php
        $itemCount = count($itemsToRender);
        // When version switcher is active (management edited or admin), JS handles rendering
        // so PHP renders 0 items → itemCounter starts at 0
        $jsItemCount = ($transaction->hasBeenEditedByManagement() || Auth::user()->isAdmin()) ? 0 : $itemCount;
    @endphp
    let itemCounter = {{ $jsItemCount }};

    // ───────────────────────────────────────────────────
    // POPULATE SELECTED BRANCHES (Crucial for first render)
    // ───────────────────────────────────────────────────
    @php
        $selectedBranchData = $transaction->branches->map(function($b) {
            $pivot = $b->pivot ?? (object)[];
            return [
                'id' => (string) $b->id,
                'name' => $b->name,
                'percent' => (float) ($pivot->allocation_percent ?? 0),
                'value' => (float) ($pivot->allocation_amount ?? 0)
            ];
        })->values()->toArray();
    @endphp
    let selectedBranches = @json($selectedBranchData);
    const isAdminOnlyBranch = @json($isAdminOnlyBranch ?? false);
    const isReadOnly = @json($isReadOnly ?? false);
    const canEditDistribution = !isReadOnly || isAdminOnlyBranch;
    
    // Default method to manual if we have amounts, or percent if we have percents
    let currentMethod = 'equal';
    if (selectedBranches.length > 0) {
        const hasManualAmount = selectedBranches.some(b => b.value > 0);
        const hasPercent = selectedBranches.some(b => b.percent > 0 && b.percent % (100/selectedBranches.length) !== 0);
        if (hasManualAmount) currentMethod = 'manual';
        else if (hasPercent) currentMethod = 'percent';
    }


    const itemsContainer = document.getElementById('items-container');
    const btnAddItem = document.getElementById('btn-add-item');
    const itemTemplateEl = document.getElementById('item-template');
    const itemTemplate = itemTemplateEl ? itemTemplateEl.innerHTML : '';
    const formTotalInput = document.getElementById('form-total-estimated-price');
    const globalTotalDisplay = document.getElementById('total-estimate-global');
    
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

    function updateGlobalTotal() {
        let itemsTotal = 0;
        const itemCards = itemsContainer.querySelectorAll('.item-card');
        
        itemCards.forEach(card => {
            const priceInput = card.querySelector('.input-price-hidden');
            const qtyInput = card.querySelector('.input-qty');
            const subtotalDisplay = card.querySelector('.item-subtotal');
            const titleDisplay = card.querySelector('.item-title');
            const subtitleDisplay = card.querySelector('.item-subtitle');
            const customerInput = card.querySelector('.input-customer');
            
            // For Admin, priceInput might be disabled but we still need its value
            const price = parseRupiah(priceInput ? priceInput.value : '0');
            const qty = parseInt(qtyInput ? qtyInput.value : '1') || 1;
            const subtotal = price * qty;
            itemsTotal += subtotal;
            
            if(subtotalDisplay) subtotalDisplay.textContent = 'Rp ' + formatRupiah(subtotal);
            
            if(titleDisplay && customerInput) {
                titleDisplay.textContent = customerInput.value || 'Barang Baru';
            }
            if(subtitleDisplay) {
                subtitleDisplay.textContent = `Rp ${formatRupiah(price)} x ${qty}`;
            }
        });

        const dppInput = document.getElementById('input-dpp-lainnya-hidden');
        const ppnInput = document.getElementById('input-ppn-hidden');
        const lay1Input = document.getElementById('input-layanan1-hidden');

        const dppLainnya = dppInput ? parseRupiah(dppInput.value) : 0;
        const ppn = ppnInput ? parseRupiah(ppnInput.value) : 0;
        const layanan1 = lay1Input ? parseRupiah(lay1Input.value) : 0;
        const grandTotal = itemsTotal + dppLainnya + ppn + layanan1;

        if (formTotalInput) formTotalInput.value = grandTotal;
        if (globalTotalDisplay) globalTotalDisplay.textContent = 'Rp ' + formatRupiah(grandTotal);
        if (summaryTotal) summaryTotal.textContent = 'Rp ' + formatRupiah(grandTotal);
        
        // Pass total to distribution to avoid dependency on global input value state
        renderDistribution(grandTotal);
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
                triggerPriceCheck(card);
                // ✅ UI: Highlight edited item
                highlightEditedItem(card);
            });
            priceDisp.addEventListener('blur', () => checkPriceAnomaly(card));
        }
        if(qtyInput) {
            qtyInput.addEventListener('input', () => {
                updateGlobalTotal();
                // ✅ UI: Highlight edited item
                highlightEditedItem(card);
            });
        }

        if(customerInput) {
            // ─── Autocomplete Logic ───
            let debounceTimer;
            const dropdown = card.querySelector('.autocomplete-dropdown');
            const list = card.querySelector('.autocomplete-list');
            const masterIdInput = card.querySelector('.input-master-id');
            const reasonSelect = card.querySelector('.input-reason');

            if (dropdown && list) {
                customerInput.addEventListener('input', function() {
                    masterIdInput.value = ''; // Reset master ID 
                    const query = this.value.trim();
                    const category = reasonSelect ? reasonSelect.value : '';

                    clearTimeout(debounceTimer);
                    if (query.length < 2) {
                        dropdown.classList.add('hidden');
                        return;
                    }

                    debounceTimer = setTimeout(() => {
                        fetch(`/api/items/autocomplete?q=${encodeURIComponent(query)}&category=${encodeURIComponent(category)}`)
                            .then(res => res.json())
                            .then(data => {
                                list.innerHTML = '';
                                if (data.suggestions && data.suggestions.length > 0) {
                                    data.suggestions.forEach(item => {
                                        const li = document.createElement('li');
                                        li.className = 'px-4 py-3 hover:bg-slate-50 cursor-pointer border-b border-slate-100 transition-colors flex justify-between items-center';
                                        
                                        // Match Badge Color
                                        let badgeColor = 'bg-slate-100 text-slate-500';
                                        if(item.match_type === 'exact') badgeColor = 'bg-teal-100 text-teal-700';
                                        else if(item.match_type === 'high') badgeColor = 'bg-emerald-100 text-emerald-700';
                                        else if(item.match_type === 'medium') badgeColor = 'bg-blue-100 text-blue-700';

                                        li.innerHTML = `
                                            <div>
                                                <div class="font-bold text-slate-700">${item.display_name}</div>
                                                ${item.category ? `<div class="text-[10px] text-slate-400 mt-0.5">${item.category}</div>` : ''}
                                            </div>
                                            <span class="text-[10px] items-center px-2 py-0.5 rounded-md font-bold ${badgeColor}">${item.confidence}%</span>
                                        `;
                                        
                                        li.addEventListener('click', () => {
                                            customerInput.value = item.display_name;
                                            masterIdInput.value = item.id;
                                            dropdown.classList.add('hidden');
                                            
                                            // Provide Quick-Fill for categories
                                            if (reasonSelect && (!reasonSelect.value || reasonSelect.value === '') && item.category) {
                                                reasonSelect.value = item.category;
                                            }
                                            updateGlobalTotal();
                                            triggerPriceCheck(card); 
                                        });
                                        list.appendChild(li);
                                    });
                                    dropdown.classList.remove('hidden');
                                } else {
                                    dropdown.classList.add('hidden');
                                }
                            }).catch(err => {
                                dropdown.classList.add('hidden');
                            });
                    }, 150);
                    
                    updateGlobalTotal();
                    triggerPriceCheck(card);
                });
                
                // Hide autocomplete on click outside
                document.addEventListener('click', function(e) {
                    if (!card.contains(e.target)) {
                        dropdown.classList.add('hidden');
                    }
                });
            } else {
                customerInput.addEventListener('input', function() {
                    updateGlobalTotal();
                    triggerPriceCheck(card);
                });
            }
            
            customerInput.addEventListener('blur', () => checkPriceAnomaly(card));
        }
        
        const reasonSelect = card.querySelector('.input-reason');
        const descInput = card.querySelector('.input-desc');
        const reqSpan = card.querySelector('.keterangan-required');
        
        function checkKeterangan() {
            if (reasonSelect.value === 'lainnya') {
                descInput.required = true;
                reqSpan.classList.remove('hidden');
            } else {
                descInput.required = false;
                reqSpan.classList.add('hidden');
            }
            triggerPriceCheck(card);
        }
        
        if(reasonSelect && descInput && reqSpan) {
            reasonSelect.addEventListener('change', checkKeterangan);
            checkKeterangan(); // init
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

    // ─── Price Index Real-time Check ───
    const priceCheckDebounce = {};

    function triggerPriceCheck(card) {
        const index = card.dataset.index;
        if (priceCheckDebounce[index]) clearTimeout(priceCheckDebounce[index]);
        
        priceCheckDebounce[index] = setTimeout(() => {
            checkPriceAnomaly(card);
        }, 300);
    }

    async function checkPriceAnomaly(card) {
        const customerInput = card.querySelector('.input-customer');
        const masterIdInput = card.querySelector('.input-master-id');
        const itemName = customerInput ? customerInput.value : '';
        const masterId = masterIdInput ? masterIdInput.value : '';
        
        const unitPrice = parseRupiah(card.querySelector('.input-price-display').value);
        const reasonSelect = card.querySelector('.input-reason');
        const category  = reasonSelect ? reasonSelect.value : '';
        const warningDiv = card.querySelector('.price-anomaly-warning');
        const refBox = card.querySelector('.price-ref-box');

        if (!itemName || unitPrice <= 0 || itemName.length < 2) {
            if (refBox) refBox.classList.add('hidden');
            if (warningDiv) warningDiv.classList.add('hidden');
            return;
        }

        try {
            const res = await fetch('{{ route("price-index.check") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ item_name: itemName, unit_price: unitPrice, category: category, master_item_id: masterId })
            });
            const data = await res.json();
            
            if (data.has_reference) {
                if (refBox) {
                    refBox.classList.remove('hidden');
                    card.querySelector('.price-ref-min').textContent = data.formatted.min;
                    card.querySelector('.price-ref-max').textContent = data.formatted.max;
                    card.querySelector('.price-ref-avg').textContent = data.formatted.avg;
                    card.querySelector('.price-ref-source').textContent = data.is_manual ? '• Manual' : '• Auto';
                }
                
                if (data.is_anomaly && warningDiv) {
                    warningDiv.classList.remove('hidden');
                } else if (warningDiv) {
                    warningDiv.classList.add('hidden');
                }

                // Setup fill buttons
                const btnMin = card.querySelector('.btn-fill-min');
                const btnAvg = card.querySelector('.btn-fill-avg');
                const btnMax = card.querySelector('.btn-fill-max');
                
                const priceDisp = card.querySelector('.input-price-display');
                const priceHid = card.querySelector('.input-price-hidden');

                if (btnMin) btnMin.onclick = () => { priceDisp.value = formatRupiah(data.min_price); priceHid.value = data.min_price; updateGlobalTotal(); if(warningDiv) warningDiv.classList.add('hidden'); };
                if (btnAvg) btnAvg.onclick = () => { priceDisp.value = formatRupiah(data.avg_price); priceHid.value = data.avg_price; updateGlobalTotal(); if(warningDiv) warningDiv.classList.add('hidden'); };
                if (btnMax) btnMax.onclick = () => { priceDisp.value = formatRupiah(data.max_price); priceHid.value = data.max_price; updateGlobalTotal(); if(warningDiv) warningDiv.classList.add('hidden'); };

            } else {
                if (refBox) refBox.classList.add('hidden');
                if (warningDiv) warningDiv.classList.add('hidden');
            }
        } catch (e) {
            console.error('Price check failed', e);
        }
    }

    function updateItemNumbers() {
        const cards = itemsContainer.querySelectorAll('.item-card');
        cards.forEach((card, idx) => {
            const numBox = card.querySelector('.item-number');
            if(numBox) numBox.textContent = idx + 1;
        });
    }

    // ✅ UI: Highlight item that was just edited
    function highlightEditedItem(card) {
        if (!card) return;
        // Don't re-trigger if already newly-added (stronger highlight)
        if (card.classList.contains('item-newly-added')) return;
        card.classList.remove('item-just-edited');
        // Force reflow to restart animation
        void card.offsetWidth;
        card.classList.add('item-just-edited');
        // Clean up class after animation completes
        setTimeout(() => card.classList.remove('item-just-edited'), 2500);
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
        
        if (typeof lucide !== 'undefined') lucide.createIcons({ root: newCard });

        // ✅ UI: Highlight newly added item
        newCard.classList.add('item-newly-added');
        setTimeout(() => newCard.classList.remove('item-newly-added'), 3000);
        
        setTimeout(() => {
            const input = newCard.querySelector('.input-customer');
            if(input) input.focus();
            
            if (itemCounter > 1) {
                newCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, 100);
    }
    
    if (btnAddItem) btnAddItem.addEventListener('click', addItem);
    
    // ─── DPP, PPN & Service Fee Event Listeners ───
    const dppLainnyaDisp = document.getElementById('input-dpp-lainnya-display');
    const dppLainnyaHid  = document.getElementById('input-dpp-lainnya-hidden');
    const ppnDisp = document.getElementById('input-ppn-display');
    const ppnHid  = document.getElementById('input-ppn-hidden');
    const lay1Disp = document.getElementById('input-layanan1-display');
    const lay1Hid  = document.getElementById('input-layanan1-hidden');

    if (dppLainnyaDisp) {
        dppLainnyaDisp.addEventListener('input', function() {
            let raw = parseRupiah(this.value);
            this.value = raw > 0 ? formatRupiah(raw) : '';
            dppLainnyaHid.value = raw;
            updateGlobalTotal();
        });
    }
    if (ppnDisp) {
        ppnDisp.addEventListener('input', function() {
            let raw = parseRupiah(this.value);
            this.value = raw > 0 ? formatRupiah(raw) : '';
            ppnHid.value = raw;
            updateGlobalTotal();
        });
    }
    if (lay1Disp) {
        lay1Disp.addEventListener('input', function() {
            let raw = parseRupiah(this.value);
            this.value = raw > 0 ? formatRupiah(raw) : '';
            lay1Hid.value = raw;
            updateGlobalTotal();
        });
    }

    // ───────────────────────────────────────────────────
    // INITIAL POPULATION FALLBACK (If json failed)
    // ───────────────────────────────────────────────────
    if (selectedBranches.length === 0) {
        document.querySelectorAll('.branch-pill.bg-emerald-500').forEach(btn => {
            selectedBranches.push({
                id: btn.dataset.id,
                name: btn.dataset.name,
                percent: 0,
                value: 0
            });
        });
    }

    // Initialize preselected branches for existing items

        itemsContainer.querySelectorAll('.item-card').forEach(setupItemCardEvents);
        updateGlobalTotal();

        // ─────────────────────────────
        // EVENT LISTENERS: BRANCH PILLS
        // ─────────────────────────────
        branchPills.forEach(btn => {

            btn.addEventListener('click', function () {
                if (!canEditDistribution) return;

                const id   = this.dataset.id;
                const name = this.dataset.name;
                const index = selectedBranches.findIndex(b => b.id == id);

                if (index > -1) {
                    // Deselect
                    selectedBranches.splice(index, 1);
                    this.classList.remove('bg-emerald-500', 'text-white', 'border-emerald-500', 'shadow-md');
                    this.classList.add('bg-white', 'text-slate-600', 'border-slate-200');
                } else {
                    // Select
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
                if (!canEditDistribution) return;

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
        function renderDistribution(forcedTotal = null) {
            const totalAmount = forcedTotal !== null ? forcedTotal : (parseInt(formTotalInput.value) || 0);
            distributionList.innerHTML = '';

            // Selalu update label total di summary black card
            if (summaryTotal) summaryTotal.textContent = 'Rp ' + formatRupiah(totalAmount);

            if (selectedBranches.length === 0) {
                distributionList.innerHTML = `
                    <div class="p-8 text-center bg-slate-50 border-2 border-dashed border-slate-200 rounded-2xl">
                        <i data-lucide="info" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
                        <p class="text-xs text-slate-400 font-medium uppercase tracking-wider">Pilih cabang di atas untuk membagi biaya</p>
                    </div>
                `;
                if (typeof lucide !== 'undefined') lucide.createIcons();
                
                // Admin tetap bisa melihat summary billing kosong jika baru mulai
                const isAdminOnlyBranch = @json($isAdminOnlyBranch ?? false);
                if (isAdminOnlyBranch) {
                    summarySection.classList.remove('hidden');
                    summarySection.style.display = 'block';
                } else {
                    summarySection.classList.add('hidden');
                }
                summarySubmit.disabled = true;
                percentWarning.classList.add('hidden');
                return;
            }



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
                let percentDisplay = '';
                const disabledAttr = canEditDistribution ? '' : 'disabled';
                const pointerClass = canEditDistribution ? 'pointer-events-auto cursor-text' : 'pointer-events-none cursor-not-allowed';

                if (currentMethod === 'equal') {
                    percentDisplay = `<span class="text-xs font-bold text-slate-500">${branch.percent}%</span>`;
                    inputHtml = `<div class="font-bold text-emerald-600 text-right">Rp ${formatRupiah(branch.value)}</div>`;
                } else if (currentMethod === 'percent') {
                    percentDisplay = `
                        <div class="flex items-center gap-1 justify-center">
                            <input type="number"
                                ${disabledAttr}
                                class="dist-input-percent w-16 text-center text-xs font-bold border border-slate-200 rounded-lg px-1 py-1 focus:ring-2 focus:ring-emerald-500 outline-none ${pointerClass}"
                                data-index="${idx}"
                                value="${branch.percent || 0}"
                                min="0" max="100">
                            <span class="text-[10px] font-bold text-slate-400">%</span>
                        </div>
                    `;
                    inputHtml = `<div class="text-emerald-500 font-bold text-sm text-right">Rp ${formatRupiah(branch.value)}</div>`;
                } else if (currentMethod === 'manual') {
                    percentDisplay = `<span class="text-xs font-bold text-slate-500">${branch.percent}%</span>`;
                    const displayVal = branch.value > 0 ? formatRupiah(branch.value) : '';
                    inputHtml = `
                        <div class="relative flex justify-end">
                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-bold">Rp</span>
                            <input type="text"
                                ${disabledAttr}
                                class="dist-input-manual w-full max-w-[120px] text-right text-xs font-bold border border-slate-200 rounded-lg pl-7 pr-2 py-1 focus:ring-2 focus:ring-emerald-500 outline-none ${pointerClass}"
                                data-index="${idx}"
                                value="${displayVal}" placeholder="0">
                        </div>
                    `;
                }

                const rowHtml = `
                    <div class="dist-row-grid">
                        <div class="font-bold text-slate-700 flex items-center gap-2 truncate">
                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></div>
                            <span class="truncate">${branch.name}</span>
                        </div>
                        <div class="flex justify-center">${percentDisplay}</div>
                        <div class="flex justify-end">${inputHtml}</div>
                    </div>
                `;
                distributionList.insertAdjacentHTML('beforeend', rowHtml);
            });


            if (typeof lucide !== 'undefined') lucide.createIcons();

            const methodLabels = { 'equal': 'BAGI RATA', 'percent': 'PERSENTASE', 'manual': 'MANUAL' };
            summaryMethod.textContent = 'METODE: ' + (methodLabels[currentMethod] || '-');

            updateHiddenInputs();
            updateSummaryList();
            validateAndSubmit();

            // ✅ FIX: Always show summary section when branches are selected
            if (summarySection) {
                summarySection.classList.remove('hidden');
                summarySection.style.display = 'block';
            }
            
            // Re-enforce read-only state for Admin/Read-Only modes
            // NOTE: Only enforce on items section, NOT on distribution section
            // to prevent disabling freshly-rendered distribution inputs
            if (typeof enforceReadOnly === 'function') enforceReadOnly(true);
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

    if (distributionList) {
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
}


    function validateAndSubmit() {
        let isValid = true;
        const totalAmount = parseInt(formTotalInput.value) || 0;
        const totalAllocated = selectedBranches.reduce((sum, b) => sum + (parseFloat(b.value)||0), 0);

        if (selectedBranches.length > 0) {
            // 1. Check total nominal balance (Anti-manipulation)
            if (totalAmount > 0 && Math.abs(totalAllocated - totalAmount) > 2) {
                isValid = false;
                percentWarning.classList.remove('hidden');
                percentWarning.textContent = `⚠ Total alokasi (Rp ${formatRupiah(totalAllocated)}) tidak sesuai dengan total transaksi (Rp ${formatRupiah(totalAmount)})`;
            } else if (currentMethod === 'percent') {
                // 2. Check percent if in percent mode
                const totalPercent = selectedBranches.reduce((sum, b) => sum + (parseFloat(b.percent)||0), 0);
                if (Math.abs(totalPercent - 100) > 0.1) {
                    isValid = false;
                    percentWarning.classList.remove('hidden');
                    percentWarning.textContent = `⚠ Total persen saat ini ${totalPercent.toFixed(1)}%. Harus 100%`;
                } else {
                    percentWarning.classList.add('hidden');
                }
            } else {
                percentWarning.classList.add('hidden');
            }
        } else {
            percentWarning.classList.add('hidden');
        }

        if (totalAmount <= 0) isValid = false;

        summarySubmit.disabled = !isValid;
    }
    /**
     * ═══════════════════════════════════════════════════════════════
     *  VERSION SWITCHING & CHANGE HIGHLIGHTING LOGIC
     *  
     *  Tambahkan SETELAH existing JavaScript di edit-pengajuan.blade.php
     *  (di dalam document.addEventListener('DOMContentLoaded', ...))
     * ═══════════════════════════════════════════════════════════════
     */

    // ═══════════════════════════════════════
    // VERSION SWITCHING SYSTEM
    // ═══════════════════════════════════════
    const versionDataEl = document.getElementById('version-data');
    if (versionDataEl) {
        const versionData = JSON.parse(versionDataEl.textContent);
        const originalItems = versionData.original || [];
        const managementItems = versionData.management || [];
        const changes = versionData.changes || [];
        const globalChanges = versionData.global_changes || {};
        const isSettlement = versionData.is_settlement || false;
        const isAdminOnlyBranch = versionData.is_admin_only_branch || false;
        
        let currentVersion = 'original'; // Start with original version
        
        const btnOriginal = document.getElementById('btn-version-original');
        const btnManagement = document.getElementById('btn-version-management');
        const itemsContainerVersioned = document.getElementById('items-container');
        const versionTitle = document.getElementById('version-header-title');
        const versionSubtitle = document.getElementById('version-header-subtitle');
        
        // ───────────────────────────────────────────────────
        // Toggle Version Button Click
        // ───────────────────────────────────────────────────
        document.querySelectorAll('.version-toggle-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetVersion = this.dataset.version;
                
                if (targetVersion === currentVersion) return; // Already active
                
                // Update button states
                document.querySelectorAll('.version-toggle-btn').forEach(b => {
                    b.classList.remove('bg-blue-500', 'text-white', 'shadow-sm');
                    b.classList.add('text-slate-600', 'hover:text-slate-800');
                });
                
                this.classList.remove('text-slate-600', 'hover:text-slate-800');
                this.classList.add('bg-blue-500', 'text-white', 'shadow-sm');
                
                currentVersion = targetVersion;
                
                // Update Header Labels
                updateHeaderLabels(targetVersion);
                
                // Re-render items
                renderVersion(targetVersion);
                
                // Update global field highlights
                updateGlobalHighlights(targetVersion);
                
                // Re-init lucide icons
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            });
        });

        function updateHeaderLabels(version) {
            if (!versionTitle || !versionSubtitle) return;

            if (version === 'original') {
                versionTitle.textContent = 'Versi Pengaju (Original)';
                versionSubtitle.innerHTML = 'Menampilkan data awal yang dikirimkan oleh teknisi sebelum ada penyesuaian management.';
            } else {
                // Version Management
                if (isSettlement) {
                    versionTitle.innerHTML = 'Versi Management <span class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded text-[10px] ml-2">Transaksi Dikunci (Menunggu Pelunasan)</span>';
                    versionSubtitle.textContent = 'Data hasil penyesuaian akhir management. Transaksi dalam fase pelunasan.';
                } else if (isAdminOnlyBranch) {
                    versionTitle.innerHTML = 'Versi Management <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-[10px] ml-2">Mode Edit Terbatas (Admin)</span>';
                    versionSubtitle.textContent = 'Data hasil revisi management. Anda dapat mengubah pembagian cabang pada mode ini.';
                } else {
                    versionTitle.textContent = 'Versi Management (Terbaru)';
                    versionSubtitle.textContent = 'Menampilkan data yang telah disesuaikan oleh Management untuk proses pembayaran.';
                }
            }
        }

        function updateGlobalHighlights(version) {
            document.querySelectorAll('.global-field-container').forEach(container => {
                const fieldName = container.dataset.field;
                const mount = container.querySelector('.global-badge-mount');
                const wrapper = container.querySelector('.field-input-wrapper');
                
                // Clear previous states
                if (mount) mount.innerHTML = '';
                if (wrapper) wrapper.classList.remove('field-changed');

                if (version === 'management' && globalChanges[fieldName]) {
                    // Apply highlighting
                    if (wrapper) wrapper.classList.add('field-changed');
                    
                    // Add "Ditambahkan" badge for management version if field has value
                    const badgeTemplate = document.getElementById('change-badge-template');
                    if (badgeTemplate && mount) {
                        const badge = badgeTemplate.content.cloneNode(true).querySelector('.change-badge');
                        badge.classList.add('added');
                        badge.querySelector('.badge-text').textContent = 'Ditambahkan';
                        badge.querySelector('.badge-icon').setAttribute('data-lucide', 'plus-circle');
                        mount.appendChild(badge);
                    }
                }
            });
        }
        
        // ───────────────────────────────────────────────────
        // Render Version
        // ───────────────────────────────────────────────────
        function renderVersion(version) {
            const dataToRender = version === 'original' ? originalItems : managementItems;
            const isReadOnly = version === 'original' || @json($isAdminOnlyBranch ?? false);
            
            // Clear container
            itemsContainerVersioned.innerHTML = '';
            
            // Reset item counter
            itemCounter = 0;
            
            // Add readonly class if viewing original
            if (isReadOnly) {
                itemsContainerVersioned.classList.add('version-readonly');
                if (btnAddItem) btnAddItem.style.display = 'none';
            } else {
                itemsContainerVersioned.classList.remove('version-readonly');
                if (btnAddItem) btnAddItem.style.display = '';
            }
            
            // Render each item
            dataToRender.forEach((itemData, index) => {
                const tempDiv = document.createElement('div');
                let html = itemTemplate
                    .replace(/__INDEX__/g, itemCounter)
                    .replace(/__NUM__/g, itemCounter + 1);
                
                tempDiv.innerHTML = html;
                const newCard = tempDiv.firstElementChild;
                
                // Populate data
                populateItemCard(newCard, itemData, itemCounter);
                
                // Mark changes if viewing management version
                if (version === 'management') {
                    const change = changes.find(c => c.index === index);
                    if (change) {
                        markItemWithChanges(newCard, change);
                    }
                }
                
                // Setup events
                setupItemCardEvents(newCard);
                
                // Disable editing if readonly
                if (isReadOnly) {
                    disableItemCard(newCard);
                }
                
                itemsContainerVersioned.appendChild(newCard);
                itemCounter++;
            });
            
            // Update global total
            updateGlobalTotal();
            
            // Update remove buttons visibility
            updateRemoveButtons();

            // Re-enforce read-only state for Admin/Read-Only modes
            // Pass skipDistribution=true to avoid disabling freshly-rendered distribution inputs
            if (typeof enforceReadOnly === 'function') enforceReadOnly(true);
        }
        
        // ───────────────────────────────────────────────────
        // Populate Item Card with Data
        // ───────────────────────────────────────────────────
        function populateItemCard(card, data, index) {
            // Basic info
            const customerInput = card.querySelector('.input-customer');
            if (customerInput) customerInput.value = data.customer || '';
            
            const vendorInput = card.querySelector('input[name*="[vendor]"]');
            if (vendorInput) vendorInput.value = data.vendor || '';
            
            const linkInput = card.querySelector('input[name*="[link]"]');
            if (linkInput) linkInput.value = data.link || '';
            
            // Specs
            const specs = data.specs || {};
            const merkInput = card.querySelector('input[name*="[specs][merk]"]');
            if (merkInput) merkInput.value = specs.merk || '';
            
            const tipeInput = card.querySelector('input[name*="[specs][tipe]"]');
            if (tipeInput) tipeInput.value = specs.tipe || '';
            
            const ukuranInput = card.querySelector('input[name*="[specs][ukuran]"]');
            if (ukuranInput) ukuranInput.value = specs.ukuran || '';
            
            const warnaInput = card.querySelector('input[name*="[specs][warna]"]');
            if (warnaInput) warnaInput.value = specs.warna || '';
            
            // Purchase reason / Category
            const reasonSelect = card.querySelector('.input-reason');
            const catValue = data.category || data.purchase_reason;
            if (reasonSelect && catValue) {
                reasonSelect.value = catValue;
            }
            
            // Description
            const descInput = card.querySelector('.input-desc');
            if (descInput) descInput.value = data.description || '';
            
            // Pricing
            const qtyInput = card.querySelector('.input-qty');
            if (qtyInput) qtyInput.value = data.quantity || 1;
            
            const priceHidden = card.querySelector('.input-price-hidden');
            const priceDisplay = card.querySelector('.input-price-display');
            const price = parseInt(data.estimated_price) || 0;
            
            if (priceHidden) priceHidden.value = price;
            if (priceDisplay) priceDisplay.value = price > 0 ? formatRupiah(price) : '';
            
            // Update title and subtitle
            const titleEl = card.querySelector('.item-title');
            if (titleEl) titleEl.textContent = data.customer || 'Barang Tanpa Nama';
            
            const subtitleEl = card.querySelector('.item-subtitle');
            if (subtitleEl) {
                const qty = data.quantity || 1;
                subtitleEl.textContent = `Rp ${formatRupiah(price)} x ${qty}`;
            }
            
            // Update subtotal
            const subtotal = price * (data.quantity || 1);
            const subtotalEl = card.querySelector('.item-subtotal');
            if (subtotalEl) subtotalEl.textContent = 'Rp ' + formatRupiah(subtotal);
        }
        
        // ───────────────────────────────────────────────────
        // Mark Item with Changes (badges + highlights)
        // ───────────────────────────────────────────────────
        function markItemWithChanges(card, change) {
            const changeType = change.type; // 'added', 'modified', 'removed'
            
            // Add badge
            const badgeTemplate = document.getElementById('change-badge-template');
            if (badgeTemplate) {
                const badge = badgeTemplate.content.cloneNode(true).querySelector('.change-badge');
                badge.classList.add(changeType);
                
                const badgeText = badge.querySelector('.badge-text');
                const badgeIcon = badge.querySelector('.badge-icon');
                
                if (changeType === 'added') {
                    badgeText.textContent = 'Ditambahkan';
                    badgeIcon.setAttribute('data-lucide', 'plus-circle');
                } else if (changeType === 'modified') {
                    badgeText.textContent = 'Diubah';
                    badgeIcon.setAttribute('data-lucide', 'refresh-cw');
                } else if (changeType === 'removed') {
                    badgeText.textContent = 'Dihapus';
                    badgeIcon.setAttribute('data-lucide', 'minus-circle');
                }
                
                // Mount to the specific container in header
                const mount = card.querySelector('.badge-mount');
                if (mount) {
                    mount.innerHTML = ''; // prevent double
                    mount.appendChild(badge);
                }
            }
            // Add card class & Highlight
            if (changeType === 'added') {
                // Card Highlight Hijau untuk barang baru
                card.classList.add('ring-2', 'ring-emerald-400', 'border-emerald-400', 'shadow-[0_4px_12px_rgba(52,211,153,0.3)]');
                const header = card.querySelector('.item-header');
                if (header) {
                    header.classList.remove('bg-slate-50', 'border-slate-100');
                    header.classList.add('bg-emerald-50', 'border-emerald-200');
                }
            } else if (changeType === 'modified') {
                // Card Highlight Orange untuk barang yang diperbarui/diedit
                card.classList.add('ring-2', 'ring-amber-400', 'border-amber-400', 'shadow-[0_4px_12px_rgba(251,191,36,0.3)]');
                const header = card.querySelector('.item-header');
                if (header) {
                    header.classList.remove('bg-slate-50', 'border-slate-100');
                    header.classList.add('bg-amber-50', 'border-amber-200');
                }
            } else if (changeType === 'removed') {
                card.classList.add('opacity-50', 'grayscale');
            }
            
            // Highlight changed fields (only for modified)
            if (changeType === 'modified' && change.fields) {
                Object.keys(change.fields).forEach(fieldName => {
                    let selector = '';
                    
                    if (fieldName === 'customer') selector = '.input-customer';
                    else if (fieldName === 'vendor') selector = 'input[name*="[vendor]"]';
                    else if (fieldName === 'link') selector = 'input[name*="[link]"]';
                    else if (fieldName === 'description') selector = '.input-desc';
                    else if (fieldName === 'quantity') selector = '.input-qty';
                    else if (fieldName === 'estimated_price') selector = '.input-price-display';
                    else if (fieldName === 'purchase_reason' || fieldName === 'category') selector = '.input-reason';
                    else if (fieldName === 'specs') {
                        // Highlight all spec fields
                        card.querySelectorAll('input[name*="[specs]"]').forEach(inp => {
                            inp.closest('div')?.classList.add('field-changed');
                        });
                        return;
                    }
                    
                    if (selector) {
                        const field = card.querySelector(selector);
                        if (field) {
                            field.closest('div')?.classList.add('field-changed');
                        }
                    }
                });
            }
            
            // For removed items, add visual fade + strikethrough
            if (changeType === 'removed') {
                card.style.opacity = '0.6';
                card.querySelector('.item-title')?.classList.add('line-through');
            }
        }
        
        // ───────────────────────────────────────────────────
        // Disable Item Card (read-only mode)
        // ───────────────────────────────────────────────────
        function disableItemCard(card) {
            card.querySelectorAll('input, select, textarea').forEach(el => {
                if (el.type === 'hidden') return; // Preserve hidden data
                el.disabled = true;
                el.style.cursor = 'not-allowed';
                el.style.backgroundColor = '#f8fafc';
            });
            
            const removeBtn = card.querySelector('.btn-remove-item');
            if (removeBtn) removeBtn.style.display = 'none';
        }
        
        // ───────────────────────────────────────────────────
        // Initialize with correct default version
        // Business Rule BR-003: Default = Versi Management (jika ada revisi)
        // Admin dan role lain: default ke management version untuk langsung lihat hasil
        // ───────────────────────────────────────────────────
        const defaultVersion = managementItems.length > 0 ? 'management' : 'original';
        // ✅ FIX: Always call renderVersion here since PHP rendered 0 items
        // (PHP skips rendering when hasBeenEditedByManagement or isAdmin)
        renderVersion(defaultVersion);
        // Update active button state sesuai default
        if (defaultVersion === 'management') {
            document.querySelectorAll('.version-toggle-btn').forEach(b => {
                b.classList.remove('bg-blue-500', 'text-white', 'shadow-sm');
                b.classList.add('text-slate-600', 'hover:text-slate-800');
            });
            const mgmtBtn = document.getElementById('btn-version-management');
            if (mgmtBtn) {
                mgmtBtn.classList.remove('text-slate-600', 'hover:text-slate-800');
                mgmtBtn.classList.add('bg-blue-500', 'text-white', 'shadow-sm');
            }
        }
        
        // Initial labels and highlights
        updateHeaderLabels(defaultVersion);
        updateGlobalHighlights(defaultVersion);
    }

    // ═══════════════════════════════════════
    // ENHANCEMENT: Show Comparison Tooltip
    // ═══════════════════════════════════════
    function showFieldComparison(fieldEl, oldValue, newValue) {
        // Optional: Create tooltip showing old vs new value
        const tooltip = document.createElement('div');
        tooltip.className = 'absolute -top-16 left-0 bg-slate-800 text-white text-xs rounded-lg px-3 py-2 shadow-lg z-50 min-w-max';
        tooltip.innerHTML = `
            <div class="font-bold mb-1">Perubahan:</div>
            <div class="text-red-300"><s>${oldValue || '(kosong)'}</s></div>
            <div class="text-emerald-300">→ ${newValue || '(kosong)'}</div>
        `;
        
        fieldEl.style.position = 'relative';
        fieldEl.appendChild(tooltip);
        
        // Auto remove after 3s
        setTimeout(() => tooltip.remove(), 3000);
    }

    // FIX: Expand collapsed items if they have validation errors to prevent "invalid form control not focusable"
    const pengajuanForm = document.getElementById('pengajuan-form');
    if (pengajuanForm) {
        pengajuanForm.addEventListener('invalid', function(e) {
            const invalidField = e.target;
            const itemBody = invalidField.closest('.item-body');
            
            if (itemBody && itemBody.classList.contains('hidden')) {
                itemBody.classList.remove('hidden');
                const card = itemBody.closest('.item-card');
                if (card) {
                    const icon = card.querySelector('.icon-collapse');
                    if (icon) icon.classList.remove('rotate-180');
                    
                    setTimeout(() => card.scrollIntoView({ behavior: 'smooth', block: 'center' }), 100);
                }
            }
        }, true);

        pengajuanForm.addEventListener('submit', function(e) {
            const totalAmount = parseInt(formTotalInput.value) || 0;
            if(totalAmount <= 0) {
                e.preventDefault();
                alert('Total estimasi tidak boleh Rp 0. Silakan isi harga barang.');
                return;
            }

            if (summarySubmit && summarySubmit.disabled) {
                e.preventDefault();
                return;
            }
            if (summarySubmit) summarySubmit.disabled = true;
            
            const submitTextEl = document.getElementById('submit-text');
            const submitSpinnerEl = document.getElementById('submit-spinner');
            if (submitTextEl) submitTextEl.textContent = 'Memproses...';
            if (submitSpinnerEl) submitSpinnerEl.classList.remove('hidden');
        });
    }

    // ─────────────────────────────
    // INITIALIZE with existing data
    // ─────────────────────────────
    if (typeof lucide !== 'undefined') lucide.createIcons();
    // Re-render to show correct calculation with distributed values
    renderDistribution();
});
    // ═══════════════════════════════════════
    // READ-ONLY MODE: Disable all form inputs
    // (Triggered when isReadOnly = true via Blade)
    // ═══════════════════════════════════════
    function enforceReadOnly(skipDistribution = false) {
        const form = document.getElementById('pengajuan-form');
        if (!form) return;

        const isAdminOnlyBranch = @json($isAdminOnlyBranch ?? false);
        const isReadOnly = @json($isReadOnly ?? false);

        // If neither flag is true, we are in full edit mode (Management)
        if (!isReadOnly && !isAdminOnlyBranch) return;

        // Fetch elements locally
        const summarySection = document.getElementById('summary-billing-section');

        // Disable all form controls EXCEPT those allowed for Admin restricted edit
        form.querySelectorAll('input, select, textarea, button[type="button"]').forEach(el => {
            // NEVER disable hidden inputs
            if (el.type === 'hidden') return;

            // Version toggle always allowed
            if (el.id === 'btn-version-original' || el.id === 'btn-version-management') return; 
            
            // If Admin restricted mode, allow branch section and submit
            if (isAdminOnlyBranch) {
                const isBranchPill = el.classList.contains('branch-pill');
                const isMethodBtn = el.classList.contains('method-btn');
                const isDistInput = el.closest('#distribution-list');
                const isSubmit = el.id === 'summary-submit';

                // Whitelist only branch distribution fields and submit button
                if (isBranchPill || isMethodBtn || isDistInput || isSubmit) {
                    el.disabled = false;
                    el.removeAttribute('disabled');
                    el.style.pointerEvents = 'auto';
                    el.style.cursor = (isDistInput || el.tagName === 'INPUT') ? 'text' : 'pointer';
                    el.style.opacity = '1';
                    el.style.filter = 'grayscale(0)';
                    return;
                }
                
                // ✅ FIX: When called from renderDistribution (skipDistribution=true),
                // skip locking distribution-related elements to prevent them from being
                // disabled right after being freshly rendered
                if (skipDistribution) {
                    const isInDistSection = el.closest('#branch-pills-container') || el.closest('#distribution-list');
                    if (isInDistSection) return;
                }
            }

            // Global Lock (Settlement) OR Restricted Field (Admin)
            el.disabled = true;
            el.setAttribute('disabled', 'disabled');
            el.style.cursor = 'not-allowed';
            el.style.pointerEvents = 'none';
            
            // Optional: visual feedback for locked fields in admin mode
            if (isAdminOnlyBranch && !isReadOnly) {
                el.classList.add('bg-slate-50', 'text-slate-500');
            }
        });

        // Special case for summary section visibility
        if (summarySection) {
            summarySection.classList.remove('hidden');
            summarySection.style.display = 'block';
            
            // If it's a total lock (Settlement), block all pointer events in summary
            if (isReadOnly && !isAdminOnlyBranch) {
                summarySection.style.pointerEvents = 'none';
                summarySection.style.opacity = '0.8';
            } else {
                summarySection.style.pointerEvents = 'auto';
                summarySection.style.opacity = '1';
                
                // But for Admin mode, we specifically locked the financial inputs above via the loop
            }
        }

        // Block form submission ONLY if NOT allowed (Settlement phase for everyone)
        // If isAdminOnlyBranch is true, we ALLOW submission (for branches)
        if (isReadOnly && !isAdminOnlyBranch) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
            }, true);
        }

        // Hide action buttons that are strictly for Management
        const addItemBtn = document.getElementById('btn-add-item');
        if (addItemBtn) addItemBtn.style.display = 'none';
        
        const deleteItemBtns = document.querySelectorAll('.btn-remove-item');
        deleteItemBtns.forEach(btn => btn.style.display = 'none');
    }

    // Call it initially if either lock applies
    @if(($isReadOnly ?? false) || ($isAdminOnlyBranch ?? false))
        enforceReadOnly();
    @endif

    // ─── IMAGE/PDF VIEWER LOGIC ────────────────────────
    window.openImageViewer = function(src, title = null, forcePdf = false) {
        const viewer = document.getElementById('image-viewer');
        const vImg   = document.getElementById('viewer-image');
        const vPdf   = document.getElementById('viewer-pdf');
        const vTitle = document.getElementById('viewer-header-title');
        const vFooter = document.getElementById('viewer-footer');
        const vLink  = document.getElementById('viewer-pdf-link');

        if (!viewer) return;

        // Reset
        vImg.classList.add('hidden');
        vPdf.classList.add('hidden');
        vFooter.classList.add('hidden');
        vImg.src = '';
        vPdf.src = '';

        if (title) vTitle.textContent = title.toUpperCase();

        const isPdf = forcePdf || src.toLowerCase().endsWith('.pdf');

        if (isPdf) {
            vPdf.src = src;
            vPdf.classList.remove('hidden');
            vFooter.classList.remove('hidden');
            vLink.href = src;
        } else {
            vImg.src = src;
            vImg.classList.remove('hidden');
        }

        viewer.classList.remove('hidden');
        viewer.classList.add('flex');
        document.body.style.overflow = 'hidden';

        if (typeof lucide !== 'undefined') lucide.createIcons({ root: viewer });
    };

    window.closeImageViewer = function() {
        const viewer = document.getElementById('image-viewer');
        if (!viewer) return;

        viewer.classList.add('hidden');
        viewer.classList.remove('flex');
        document.body.style.overflow = '';
        
        document.getElementById('viewer-image').src = '';
        document.getElementById('viewer-pdf').src = '';
    };

    // Close on click outside or ESC
    document.getElementById('image-viewer')?.addEventListener('click', (e) => {
        if (e.target.id === 'image-viewer') window.closeImageViewer();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') window.closeImageViewer();
    });
</script>
@endpush