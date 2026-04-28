{{-- Footer / Pagination --}}
<div
    class="p-3 sm:p-4 md:p-5 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-2 sm:gap-0">
    <p class="text-[11px] sm:text-xs text-gray-500 font-medium order-2 sm:order-1">
        Menampilkan <span id="showing-from">0</span> - <span id="showing-to">0</span> dari <span
            id="total-records">0</span> transaksi
    </p>
    <div class="flex items-center gap-2 order-1 sm:order-2">
        @if(auth()->user()->role !== 'teknisi')
            {{-- Export Button --}}
            <button type="button" id="btn-open-export" onclick="openExportModal()"
                class="flex items-center gap-2 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm shadow-emerald-600/20 active:scale-95">
                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                <span class="hidden sm:inline">Export Excel</span>
                <span class="sm:hidden">Export</span>
            </button>
        @endif
        <div id="pagination-container" class="flex items-center gap-1 sm:gap-2">
            {{-- Will be populated by JavaScript --}}
        </div>
    </div>
</div>