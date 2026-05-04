{{-- 3. DAFTAR BARANG --}}
<div class="mb-8 md:mb-10">
    <div class="flex justify-between items-center mb-4">
        <label
            class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wider">Daftar
            Barang</label>
        <button type="button" id="add-item-btn"
            class="flex items-center gap-1.5 bg-emerald-50 text-emerald-600 px-3 py-1.5 rounded-lg text-[10px] md:text-xs font-bold hover:bg-emerald-100 transition-colors uppercase tracking-wider">
            <i data-lucide="plus" class="w-3 h-3 md:w-3.5 md:h-3.5"></i> Tambah Baris
        </button>
    </div>

    <div class="border border-slate-100 rounded-2xl overflow-hidden shadow-sm">
        {{-- Desktop Table View --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap" id="items-table">
                <thead
                    class="text-[10px] text-slate-400 font-bold uppercase bg-slate-50 border-b border-slate-100 tracking-wider">
                    <tr>
                        <th class="p-4 w-10 text-center">No</th>
                        <th class="p-4 min-w-[120px]">Nama Barang</th>
                        <th class="p-4 w-20">Qty</th>
                        <th class="p-4 w-24">Satuan</th>
                        <th class="p-4 w-32 text-right">Harga Satuan</th>
                        <th class="p-4 w-32 text-right">Total</th>
                        <th class="p-4 min-w-[120px]">Deskripsi</th>
                        <th class="p-4 w-10 text-center"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" id="items-tbody">
                </tbody>
            </table>
        </div>

        {{-- Mobile Cards View --}}
        <div class="md:hidden divide-y divide-slate-100 bg-white" id="items-cards">
            <!-- Cards will be rendered here -->
        </div>

        {{-- Baris Total --}}
        <div class="bg-slate-900 px-4 md:px-6 py-4 md:py-5 flex justify-between items-center">
            <span
                class="text-[9px] md:text-[11px] font-bold uppercase tracking-[0.15em] text-slate-400">Total
                Nominal</span>
            <span class="text-xl md:text-2xl font-black text-emerald-400 tracking-tight"
                id="display-total-items">Rp 0</span>
        </div>
    </div>
</div>
