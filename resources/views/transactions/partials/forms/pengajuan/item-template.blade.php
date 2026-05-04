{{-- ITEM TEMPLATE FOR JS --}}
<div class="item-card bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow"
    data-index="__INDEX__">
    {{-- Header (Clickable for expand/collapse) --}}
    <div
        class="item-header bg-slate-50 px-5 py-4 cursor-pointer flex items-center justify-between border-b border-slate-100">
        <div class="flex items-center gap-3">
            <div
                class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 font-bold text-xs item-number">
                __NUM__</div>
            <div>
                <h4 class="font-bold text-slate-700 text-sm item-title">Barang Baru</h4>
                <p class="text-[10px] item-subtitle text-slate-400">Rp 0 x 1</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button type="button"
                class="btn-remove-item text-red-500 hover:bg-red-50 p-2 rounded-lg transition-colors hidden"
                title="Hapus Barang">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
            </button>
            <i data-lucide="chevron-down"
                class="w-5 h-5 text-slate-400 transition-transform duration-200 icon-collapse"></i>
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
                    <label
                        class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Nama
                        Barang/Jasa <span class="text-red-500">*</span></label>
                    <input type="hidden" name="items[__INDEX__][master_item_id]" class="input-master-id">
                    <input type="text" name="items[__INDEX__][customer]" required autocomplete="off"
                        class="input-customer w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all"
                        placeholder="Ketik nama barang... (Autocomplete Cerdas)">

                    <!-- Autocomplete Dropdown -->
                    <div
                        class="autocomplete-dropdown hidden absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                        <ul class="autocomplete-list text-sm"></ul>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label
                        class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Informasi
                        Vendor</label>
                    <input type="text" name="items[__INDEX__][vendor]"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all"
                        placeholder="Contoh: Toko Komputer Jaya">
                </div>

                {{-- Link Barang/Referensi (WAJIB) --}}
                <div class="md:col-span-2">
                    <label
                        class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Link
                        Barang/Referensi <span class="text-red-500">*</span></label>
                    <input type="url" name="items[__INDEX__][link]"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all"
                        placeholder="https://tokopedia.link/...">
                </div>
            </div>
        </div>

        {{-- 3. SPESIFIKASI --}}
        <div class="mt-8">
            <label
                class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-4 tracking-wider">Spesifikasi
                Barang</label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 md:gap-6">
                <div><label
                        class="block text-[10px] md:text-[11px] mb-2 font-bold text-slate-500 uppercase tracking-wider">Merk</label><input
                        type="text" name="items[__INDEX__][specs][merk]"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-400 transition-all"
                        placeholder="Contoh: Xpon CDATA"></div>
                <div><label
                        class="block text-[10px] md:text-[11px] mb-2 font-bold text-slate-500 uppercase tracking-wider">Tipe
                        / Seri</label><input type="text" name="items[__INDEX__][specs][tipe]"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-400 transition-all"
                        placeholder="Contoh: FD512XW-R460"></div>
                <div><label
                        class="block text-[10px] md:text-[11px] mb-2 font-bold text-slate-500 uppercase tracking-wider">Ukuran</label><input
                        type="text" name="items[__INDEX__][specs][ukuran]"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-400 transition-all"
                        placeholder="Contoh: 30x30"></div>
                <div><label
                        class="block text-[10px] md:text-[11px] mb-2 font-bold text-slate-500 uppercase tracking-wider">Warna</label><input
                        type="text" name="items[__INDEX__][specs][warna]"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-400 transition-all"
                        placeholder="Contoh: Putih"></div>
            </div>
        </div>

        {{-- 4 & 5. ALASAN & HARGA --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">

            {{-- Alasan Pembelian --}}
            <div>
                <label
                    class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-3 tracking-wider">Alasan
                    Pembelian / Kategori</label><span class="text-red-500">*</span>
                <div class="relative">
                    <select name="items[__INDEX__][category]" required
                        class="input-reason w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all appearance-none">
                        <option value="">— Pilih alasan —</option>
                        @foreach($pengajuanCategories as $cat)
                            <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    <i data-lucide="chevron-down"
                        class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
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
                <label
                    class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-4 tracking-wider">Estimasi
                    Biaya</label>
                <div class="space-y-4">

                    {{-- Price Index Reference Info --}}
                    <div class="price-ref-box hidden bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 text-xs">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="text-blue-600 font-bold uppercase tracking-wider text-[10px]">📊
                                Referensi Harga</span>
                            <span class="price-ref-source text-blue-400 text-[10px]"></span>
                        </div>
                        <div class="space-y-2 text-center">
                            <button type="button"
                                class="btn-fill-avg w-full bg-blue-100 hover:bg-blue-200 text-blue-700 font-bold rounded-lg py-2 transition text-xs flex items-center justify-between px-4">
                                <span class="text-[10px] text-blue-500 uppercase font-black tracking-widest">Rata-rata
                                    (AVG)</span>
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
                    <div
                        class="price-anomaly-warning hidden bg-red-50 border border-red-200 rounded-xl px-4 py-2.5 flex items-center gap-2 text-xs text-red-700">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span class="price-warning-text font-semibold">Harga melebihi referensi maksimum! Owner
                            akan diberitahu.</span>
                    </div>

                    {{-- Estimasi Harga Satuan --}}
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Estimasi
                            Harga Satuan *</label>
                        <div class="relative">
                            <span
                                class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">Rp</span>
                            <input type="text" required
                                class="input-price-display w-full bg-white border border-slate-200 rounded-xl pl-12 pr-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all"
                                value="">
                            <input type="hidden" name="items[__INDEX__][estimated_price]" class="input-price-hidden"
                                value="0">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Jumlah
                            *</label>
                        <input type="number" name="items[__INDEX__][quantity]" value="1" required min="1"
                            class="input-qty w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-400 transition-all">
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