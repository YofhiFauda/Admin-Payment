{{-- Mobile/Tablet Card View --}}
<div id="mobile-container" class="md:hidden divide-y divide-gray-50 transition-all duration-300">
    {{-- Will be populated by JavaScript --}}
</div>

{{-- No results message (mobile) --}}
<div id="mobile-no-results" class="hidden md:hidden p-12 text-center">
    <div class="flex flex-col items-center justify-center opacity-40">
        <i data-lucide="search" class="w-12 h-12 text-gray-300 mb-3"></i>
        <h3 class="text-sm font-bold text-gray-900">Tidak Ditemukan</h3>
        <p class="text-xs text-gray-500">Tidak ada transaksi yang cocok dengan pencarian "<span
                id="mobile-no-result-query"></span>"</p>
    </div>
</div>