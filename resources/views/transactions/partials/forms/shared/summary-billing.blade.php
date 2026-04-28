{{-- ══════════════════════════════════ --}}
{{-- SUMMARY BILLING --}}
{{-- ══════════════════════════════════ --}}
<div id="summary-billing-section" class="bg-[#1a1c23] rounded-[1rem] md:rounded-3xl p-4 md:p-8 lg:p-10 text-white relative overflow-hidden shadow-xl hidden">
    {{-- Decorative circle --}}
    <div class="absolute -right-20 -top-20 w-64 h-64 bg-white/[0.02] rounded-full pointer-events-none"></div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12 mb-8 md:mb-10 relative z-10">
        {{-- Left Side: Total --}}
        <div>
            <span class="text-[9px] md:text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Total
                Estimasi</span>
            <div class="text-3xl md:text-4xl lg:text-5xl font-black text-emerald-400 mb-4 md:mb-6 tracking-tight"
                id="summary-total">Rp 0</div>
            <div class="flex flex-wrap gap-2">
                <span
                    class="bg-white/10 text-slate-300 px-3 py-1 md:py-1.5 rounded-full text-[9px] md:text-[10px] font-bold uppercase tracking-wider"
                    id="summary-method">Metode: -</span>
                <span
                    class="bg-white/10 text-slate-300 px-3 py-1 md:py-1.5 rounded-full text-[9px] md:text-[10px] font-bold uppercase tracking-wider"
                    id="summary-branch-count">0 Cabang</span>
            </div>
        </div>

        {{-- Right Side: Details --}}
        <div class="md:border-l border-white/10 md:pl-8 lg:pl-12 flex flex-col justify-center">
            <span
                class="text-[9px] md:text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-4">Rincian
                Distribusi Cabang</span>
            <div class="space-y-3" id="summary-branches-list">
                <div class="text-xs text-slate-500 italic">Pilih cabang terlebih dahulu...</div>
            </div>
        </div>
    </div>

    {{-- Submit Button --}}
    <button type="submit" id="summary-submit" disabled
        class="w-full relative z-10 bg-emerald-500 hover:bg-emerald-400
                            disabled:bg-slate-700 disabled:text-slate-500 text-white font-bold
                            py-4 md:py-5 rounded-xl transition-all
                            shadow-[0_8px_20px_-6px_rgba(16,185,129,0.4)] disabled:shadow-none
                            text-xs md:text-sm uppercase tracking-wider
                            cursor-pointer disabled:cursor-not-allowed
                            flex items-center justify-center gap-2">
        <span id="submit-text">Kirim Form Transaksi</span>
        <svg id="submit-spinner" class="animate-spin h-4 w-4 hidden" xmlns="http://www.w3.org/2000/svg" fill="none"
            viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>
    </button>
</div>
