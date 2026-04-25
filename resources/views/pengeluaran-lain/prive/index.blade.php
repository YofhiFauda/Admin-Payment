@extends('layouts.app')

@section('page-title', 'Prive')

@section('content')
<div class="p-4 md:p-6 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-lg bg-gradient-to-br from-purple-500 to-violet-600">
                <i data-lucide="user-minus" class="w-6 h-6 text-white"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-slate-900">Prive</h1>
                <p class="text-sm text-slate-500 mt-0.5">Daftar semua transaksi Prive</p>
            </div>
        </div>
        <a href="{{ route('pengeluaran-lain.prive.create') }}"
            class="flex items-center gap-2 px-5 py-3 rounded-xl bg-slate-900 text-white font-bold text-sm hover:bg-slate-800 transition-all shadow-lg">
            <i data-lucide="plus" class="w-4 h-4"></i> Tambah Prive
        </a>
    </div>

    {{-- Flash notification --}}
    @if(session('notification'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-3.5 rounded-xl text-sm font-semibold flex items-center gap-3">
        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500 flex-shrink-0"></i>
        {{ session('notification') }}
    </div>
    @endif

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        @if($items->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <i data-lucide="user-minus" class="w-8 h-8 text-slate-200 mb-2"></i>
                <p class="text-slate-500 font-bold">Belum ada data</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-5 py-3.5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">No. Invoice</th>
                            <th class="px-5 py-3.5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-5 py-3.5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Tujuan</th>
                            <th class="px-5 py-3.5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Nominal</th>
                            <th class="px-5 py-3.5 text-left text-xs font-black text-slate-500 uppercase tracking-wider uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($items as $item)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-4 font-mono text-xs font-bold">{{ $item->invoice_number }}</td>
                            <td class="px-5 py-4 font-semibold">{{ $item->tanggal->translatedFormat('d F Y') }}</td>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-700">{{ $item->rekening_tujuan }}</div>
                                @if($item->dariBranch) <div class="text-[10px] text-slate-400">Dari: {{ $item->dariBranch->name }}</div> @endif
                            </td>
                            <td class="px-5 py-4 font-bold text-slate-800">{{ $item->formatted_nominal }}</td>
                            <td class="px-5 py-4">
                                @if($item->bukti_transfer)
                                <a href="{{ route('pengeluaran-lain.record.image', $item->id) }}" target="_blank" class="w-8 h-8 flex items-center justify-center bg-blue-50 text-blue-600 rounded-lg"><i data-lucide="image" class="w-4 h-4"></i></a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($items->hasPages())<div class="px-5 py-4 border-t border-slate-100">{{ $items->links() }}</div>@endif
        @endif
    </div>
</div>
@endsection
