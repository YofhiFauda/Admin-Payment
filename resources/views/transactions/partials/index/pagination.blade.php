{{-- Footer / Pagination --}}
<div class="border-t border-gray-100 flex flex-col lg:flex-row items-center justify-between gap-4 lg:gap-0 bg-white rounded-b-xl min-w-0">
    {{-- Left Side: Per Page Dropdown + Showing Text --}}
    <div class="px-4 py-3 sm:px-5 sm:py-4 flex items-center justify-center sm:justify-between lg:justify-start w-full lg:w-auto order-2 lg:order-1">
        <div class="flex items-center gap-2">
            <p class="text-[11px] sm:text-xs text-gray-500 font-medium">
                Tampilkan
            </p>
            <div class="relative group">
                @php($currentPerPage = in_array((int) request('per_page', 20), [20, 50, 100], true) ? (int) request('per_page', 20) : 20)
                <select id="per-page-select"
                    class="appearance-none bg-gray-50/50 border border-gray-200 rounded-lg py-1.5 pl-3 pr-8 text-xs font-semibold text-gray-700 group-hover:bg-white group-hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer">
                    <option value="20" @selected($currentPerPage === 20)>20</option>
                    <option value="50" @selected($currentPerPage === 50)>50</option>
                    <option value="100" @selected($currentPerPage === 100)>100</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-gray-400 group-hover:text-gray-600 transition-colors">
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                </div>
            </div>
        </div>
        <p class="text-[11px] sm:text-xs text-gray-500 font-medium ml-4">
            Total <span id="total-records" class="font-bold text-gray-700">0</span> data
        </p>
    </div>

    {{-- Right Side: Pagination --}}
    <div class="px-4 py-3 sm:px-5 sm:py-4 w-full lg:w-auto max-w-full overflow-x-auto order-1 lg:order-2 text-center lg:text-right pb-2 lg:pb-0 [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
        <div id="pagination-container" class="inline-flex items-center justify-start gap-1 sm:gap-1.5">
            {{-- Will be populated by JavaScript --}}
        </div>
    </div>
</div>
