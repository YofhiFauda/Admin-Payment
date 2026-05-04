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
        <button type="button" id="btn-add-item"
            class="flex items-center justify-center gap-2 bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white border border-emerald-200 hover:border-emerald-600 px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-sm">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Tambah Barang
        </button>
    </div>

    <div id="items-container" class="space-y-6">
        {{-- Item Cards will be injected here via JS --}}
    </div>

    {{-- Global Total Estimate (Replaces the specific sidebar) --}}
    <div
        class="mt-6 bg-slate-50 p-5 md:p-6 rounded-2xl border border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <span class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Estimasi
                Biaya Keseluruhan</span>
            <p class="text-xs text-slate-500">Total estimasi dari seluruh barang di atas</p>
        </div>
        <div
            class="text-left sm:text-right w-full sm:w-auto bg-white border border-slate-200 px-6 py-4 rounded-xl shadow-sm">
            <div id="total-estimate-global" class="text-xl md:text-2xl font-black text-emerald-600">Rp 0</div>
            <input type="hidden" name="estimated_price" id="form-total-estimated-price" value="0">
        </div>
    </div>
</div>