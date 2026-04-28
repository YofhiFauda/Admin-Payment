{{-- ══════════════════════════════════════════════════ --}}
{{-- PEMBAGIAN CABANG                                  --}}
{{-- Selector ini HARUS sinkron dengan distribution.js --}}
{{-- form-pengajuan/distribution.js menggunakan:       --}}
{{--   .branch-pill      → data-id, data-name          --}}
{{--   .method-btn       → data-method                 --}}
{{--   #distribution-list (container row allocations)  --}}
{{--   #distribution-hidden-inputs                     --}}
{{--   #percent-warning                                --}}
{{--   #summary-billing-section (toggle via JS)        --}}
{{-- ══════════════════════════════════════════════════ --}}
<div class="bg-white border border-slate-100 rounded-3xl p-6 md:p-8 mb-8 md:mb-10 shadow-sm">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <label class="block text-xs font-bold text-slate-400 uppercase mb-1 tracking-wider">Pembagian Cabang</label>
            <p class="text-[10px] md:text-xs text-slate-400">Pilih cabang yang akan menanggung biaya ini</p>
        </div>

        {{-- Metode Distribusi — pakai class 'method-btn' dan data-method (sesuai distribution.js) --}}
        <div class="flex bg-slate-100 rounded-xl p-1 text-[10px] md:text-xs font-bold self-start md:self-center">
            <button type="button" data-method="equal"
                class="method-btn px-4 py-2 rounded-lg bg-white shadow text-slate-700 transition-all">Bagi
                Rata</button>
            <button type="button" data-method="percent"
                class="method-btn px-4 py-2 rounded-lg text-slate-500 transition-all hover:text-slate-700">Persentase</button>
            <button type="button" data-method="manual"
                class="method-btn px-4 py-2 rounded-lg text-slate-500 transition-all hover:text-slate-700">Manual</button>
        </div>
    </div>

    {{-- Branch Pills — pakai data-id dan data-name (sesuai distribution.js) --}}
    <div class="flex flex-wrap gap-2 md:gap-3 mb-8" id="branch-pills-container">
        @foreach($branches as $branch)
            <button type="button"
                data-id="{{ $branch->id }}"
                data-name="{{ $branch->name }}"
                class="branch-pill px-3 md:px-4 py-1.5 md:py-2 rounded-full text-[10px] md:text-xs font-bold transition-all border border-slate-200 bg-white text-slate-600 cursor-pointer hover:border-emerald-300">
                {{ $branch->name }}
            </button>
        @endforeach
    </div>

    {{-- Distribution List — id="distribution-list" (sesuai distribution.js) --}}
    <div id="distribution-list" class="space-y-3">
        {{-- Dynamic Content via JS —  kosong saat tidak ada cabang dipilih --}}
    </div>

    {{-- Warning persentase --}}
    <p id="percent-warning" class="mt-3 text-red-500 font-bold text-[10px] md:text-xs hidden"></p>

    @error('branches')
        <p class="mt-2 text-red-500 font-bold text-[10px] md:text-xs">{{ $message }}</p>
    @enderror
</div>
