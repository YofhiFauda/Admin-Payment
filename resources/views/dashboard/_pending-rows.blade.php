@foreach($pendingTransactions as $t)
<tr class="table-row" id="pending-row-{{ $t->id }}">
    <td class="px-5 py-3">
        <a href="{{ route('transactions.show', $t->id) }}"
           class="font-semibold text-slate-800 hover:text-indigo-600 transition-colors block">
            {{ $t->invoice_number }}
        </a>
        <span class="text-xs text-slate-400">
            {{ $t->submitter->name ?? '-' }} &bull;
            {{ $t->type === 'rembush' ? 'Rembush' : 'Pengajuan' }}
            @if($t->status === 'approved')
                <span class="ml-1 px-1.5 py-0.5 rounded-md bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200 text-[10px] font-bold border">Menunggu Owner</span>
            @endif
        </span>
    </td>
    <td class="px-2 py-3 hidden sm:table-cell">
        <span class="font-semibold text-slate-700">{{ \App\Models\Transaction::formatShortRupiah($t->effective_amount) }}</span>
    </td>
    <td class="px-5 py-3">
        <div class="flex items-center justify-end gap-1.5">
            <button class="btn-quick-approve flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-bold transition-colors"
                data-id="{{ $t->id }}" data-status="approved">
                <i data-lucide="check" class="w-3 h-3"></i>
                <span class="hidden sm:inline">Setuju</span>
            </button>
            <button class="btn-quick-reject flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold transition-colors"
                data-id="{{ $t->id }}" data-status="rejected" data-invoice="{{ $t->invoice_number }}">
                <i data-lucide="x" class="w-3 h-3"></i>
                <span class="hidden sm:inline">Tolak</span>
            </button>
        </div>
    </td>
</tr>
@endforeach
