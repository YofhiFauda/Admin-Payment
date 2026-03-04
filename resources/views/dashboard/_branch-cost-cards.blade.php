@foreach($branchCostBreakdown as $branch)
<div class="dash-card overflow-hidden flex flex-col">
    {{-- Card Header: Branch Name --}}
    <div class="px-5 pt-4 pb-3">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center flex-shrink-0 shadow-md shadow-indigo-500/20">
                <i data-lucide="radio-tower" class="w-4 h-4 text-white"></i>
            </div>
            <h4 class="font-black text-base text-slate-900 leading-tight">{{ $branch->name }}</h4>
        </div>
        <p class="text-[11px] text-slate-400 pl-12">{{ $branch->categories->count() }} kategori</p>
    </div>

    {{-- Table --}}
    <div class="flex-1 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-y border-slate-100 bg-slate-50/80">
                    <th class="text-center px-3 py-2.5 text-[10px] font-bold text-slate-400 uppercase w-10">No</th>
                    <th class="text-left px-3 py-2.5 text-[10px] font-bold text-slate-400 uppercase">Kategori</th>
                    <th class="text-right px-3 py-2.5 text-[10px] font-bold text-slate-400 uppercase">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($branch->categories as $catName => $catTotal)
                <tr class="table-row {{ $loop->even ? 'bg-slate-50/50' : '' }}">
                    <td class="text-center px-3 py-2 text-xs text-slate-400 font-medium">{{ $loop->iteration }}</td>
                    <td class="px-3 py-2 text-xs text-slate-700 font-medium">{{ $catName }}</td>
                    <td class="text-right px-3 py-2 text-xs text-slate-800 font-semibold whitespace-nowrap">
                        {{ \App\Models\Transaction::formatShortRupiah($catTotal) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Card Footer: Grand Total --}}
    <div class="px-5 py-3 bg-gradient-to-r from-indigo-50 to-purple-50 border-t border-indigo-100 mt-auto">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Total Transaksi</span>
            <span class="text-base font-black text-indigo-700">
                {{ \App\Models\Transaction::formatShortRupiah($branch->total) }}
            </span>
        </div>
    </div>
</div>
@endforeach
