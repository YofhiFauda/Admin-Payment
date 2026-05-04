{{-- Table Desktop View --}}

<div class="show-on-laptop overflow-x-auto">
    <table class="w-full text-left border-collapse table-compact">
        <thead>
            <tr
                class="border-b border-gray-100 text-xs uppercase tracking-wider text-gray-400 font-semibold bg-gray-50/50 whitespace-nowrap">
                <th class="px-5 py-4 text-center w-10 hidden min-[1780px]:table-cell">No.</th>
                <th class="px-5 py-4">Nama Pengaju</th>
                <th class="px-5 py-4">Jenis</th>
                <th class="px-5 py-4 hidden min-[1440px]:table-cell">Kategori</th>
                <th class="px-5 py-4">Status</th>
                <th class="px-5 py-4 hidden min-[1580px]:table-cell">Tanggal</th>
                <th class="px-5 py-4">Nominal</th>
                <th class="px-5 py-4 text-center">Tindakan</th>
            </tr>
        </thead>
        <tbody id="desktop-tbody" class="divide-y divide-gray-50 text-sm text-gray-600 transition-all duration-300">
            {{-- Will be populated by JavaScript --}}
        </tbody>
    </table>
    {{-- No results message --}}
    <div id="table-no-results" class="hidden px-6 py-20 text-center">
        <div class="flex flex-col items-center justify-center opacity-40">
            <div class="p-4 bg-gray-50 rounded-full mb-4">
                <i data-lucide="search" class="w-8 h-8 text-gray-400"></i>
            </div>
            <h3 class="text-base font-bold text-gray-900">Tidak Ditemukan</h3>
            <p class="text-xs text-gray-500 mt-1">Tidak ada transaksi yang cocok dengan pencarian "<span
                    id="no-result-query"></span>"</p>
        </div>
    </div>
</div>