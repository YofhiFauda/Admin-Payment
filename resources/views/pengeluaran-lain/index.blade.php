@extends('layouts.app')

@section('page-title', $config['label'])

@section('content')
<div class="p-4 md:p-6 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-lg
                @if($jenis === 'bayar_hutang') bg-gradient-to-br from-red-500 to-rose-600
                @elseif($jenis === 'piutang_usaha') bg-gradient-to-br from-blue-500 to-indigo-600
                @else bg-gradient-to-br from-purple-500 to-violet-600 @endif">
                <i data-lucide="{{ $config['icon'] }}" class="w-6 h-6 text-white"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-slate-900">{{ $config['label'] }}</h1>
                <p class="text-sm text-slate-500 mt-0.5">Daftar semua transaksi {{ $config['label'] }}</p>
            </div>
        </div>
        <a href="{{ route('pengeluaran-lain.' . str_replace('_', '-', $jenis) . '.create') }}"
            class="flex items-center gap-2 px-5 py-3 rounded-xl bg-slate-900 text-white font-bold text-sm hover:bg-slate-800 transition-all shadow-lg">
            <i data-lucide="plus" class="w-4 h-4"></i> Tambah {{ $config['label'] }}
        </a>
    </div>

    {{-- Flash notification --}}
    @if(session('notification'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-3.5 rounded-xl text-sm font-semibold flex items-center gap-3">
        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500 flex-shrink-0"></i>
        {{ session('notification') }}
    </div>
    @endif

    @if($errors->any())
    <div class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-3.5 rounded-xl text-sm font-semibold">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        @if($items->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <div class="w-16 h-16 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-300 mb-4">
                    <i data-lucide="{{ $config['icon'] }}" class="w-8 h-8"></i>
                </div>
                <p class="text-slate-500 font-bold">Belum ada data</p>
                <p class="text-slate-400 text-xs mt-1">Klik "Tambah {{ $config['label'] }}" untuk memulai</p>
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
                            <th class="px-5 py-3.5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Keterangan</th>
                            <th class="px-5 py-3.5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Diinput oleh</th>
                            <th class="px-5 py-3.5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($items as $item)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-4">
                                <span class="font-mono text-xs font-bold text-slate-700 bg-slate-100 px-2 py-1 rounded-lg">{{ $item->invoice_number }}</span>
                            </td>
                            <td class="px-5 py-4 font-semibold text-slate-700">
                                {{ $item->tanggal->format('d M Y') }}
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                @if($jenis === 'prive')
                                    <div class="font-semibold">{{ $item->rekening_tujuan }}</div>
                                    @if($item->dariBranch)
                                    <div class="text-xs text-slate-400 mt-0.5">Dari: {{ $item->dariBranch->name }}</div>
                                    @endif
                                @else
                                    <span class="font-semibold">{{ $item->branch->name ?? '-' }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="font-bold text-slate-800">{{ $item->formatted_nominal }}</span>
                            </td>
                            <td class="px-5 py-4 text-slate-500 text-xs max-w-[200px] truncate">
                                {{ $item->keterangan ?? '-' }}
                            </td>
                            <td class="px-5 py-4 text-slate-600 text-xs">
                                {{ $item->submitter->name ?? '-' }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    {{-- Lihat bukti transfer --}}
                                    @if($item->bukti_transfer)
                                    <a href="{{ route('pengeluaran-lain.record.image', $item->id) }}" target="_blank"
                                        class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-100 transition-all" title="Lihat Bukti">
                                        <i data-lucide="image" class="w-4 h-4"></i>
                                    </a>
                                    @endif
                                    {{-- Delete --}}
                                    @if($item->status === 'pending')
                                    <form method="POST" action="{{ route('pengeluaran-lain.record.destroy', $item->id) }}"
                                        onsubmit="return confirm('Hapus record ini?')" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center hover:bg-rose-100 transition-all" title="Hapus">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($items->hasPages())
            <div class="px-5 py-4 border-t border-slate-100">
                {{ $items->links() }}
            </div>
            @endif
        @endif
    </div>
</div>
@endsection
