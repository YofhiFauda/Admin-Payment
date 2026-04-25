@extends('layouts.app')

@section('page-title', 'Data Gaji Karyawan')

@section('content')
<div class="p-4 md:p-6 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-lg">
                <i data-lucide="banknote" class="w-6 h-6 text-white"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-slate-900">Data Gaji</h1>
                <p class="text-sm text-slate-500 mt-0.5">Kelola gaji karyawan & alur persetujuan</p>
            </div>
        </div>
        <a href="{{ route('pengeluaran-lain.gaji.create') }}"
            class="flex items-center gap-2 px-5 py-3 rounded-xl bg-slate-900 text-white font-bold text-sm hover:bg-slate-800 transition-all shadow-lg">
            <i data-lucide="plus" class="w-4 h-4"></i> Input Gaji Baru
        </a>
    </div>

    {{-- Flash --}}
    @if(session('notification'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-3.5 rounded-xl text-sm font-semibold flex items-center gap-3">
        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500 flex-shrink-0"></i>
        {{ session('notification') }}
    </div>
    @endif

    {{-- Stats summary --}}
    <div class="grid grid-cols-3 gap-4">
        @php
            $countDraft    = $salaries->getCollection()->where('status', 'draft')->count();
            $countApproved = $salaries->getCollection()->where('status', 'approved')->count();
            $countPaid     = $salaries->getCollection()->where('status', 'paid')->count();
        @endphp
        <div class="bg-white rounded-xl border border-slate-200 p-4 text-center shadow-sm">
            <p class="text-2xl font-black text-slate-700">{{ $countDraft }}</p>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mt-1">Draft</p>
        </div>
        <div class="bg-white rounded-xl border border-blue-200 p-4 text-center shadow-sm">
            <p class="text-2xl font-black text-blue-600">{{ $countApproved }}</p>
            <p class="text-xs font-bold text-blue-400 uppercase tracking-wider mt-1">Disetujui</p>
        </div>
        <div class="bg-white rounded-xl border border-green-200 p-4 text-center shadow-sm">
            <p class="text-2xl font-black text-green-600">{{ $countPaid }}</p>
            <p class="text-xs font-bold text-green-400 uppercase tracking-wider mt-1">Sudah Dibayar</p>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        @if($salaries->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <div class="w-16 h-16 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-300 mb-4">
                    <i data-lucide="banknote" class="w-8 h-8"></i>
                </div>
                <p class="text-slate-500 font-bold">Belum ada data gaji</p>
                <p class="text-slate-400 text-xs mt-1">Klik "Input Gaji Baru" untuk memulai</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-5 py-3.5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Invoice</th>
                            <th class="px-5 py-3.5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Karyawan</th>
                            <th class="px-5 py-3.5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Periode</th>
                            <th class="px-5 py-3.5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Total Gaji</th>
                            <th class="px-5 py-3.5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3.5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Disetujui oleh</th>
                            <th class="px-5 py-3.5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($salaries as $salary)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-4">
                                <span class="font-mono text-xs font-bold text-slate-700 bg-slate-100 px-2 py-1 rounded-lg">{{ $salary->invoice_number }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-800">{{ $salary->employee->name ?? '-' }}</div>
                                <div class="text-xs text-slate-400">Diinput: {{ $salary->submitter->name ?? '-' }}</div>
                            </td>
                            <td class="px-5 py-4 font-semibold text-slate-700">{{ $salary->periode }}</td>
                            <td class="px-5 py-4">
                                <span class="font-black text-slate-800">{{ $salary->formatted_total }}</span>
                            </td>
                            <td class="px-5 py-4">
                                @php
                                    $colorMap = ['draft' => 'gray', 'approved' => 'blue', 'paid' => 'green'];
                                    $c = $colorMap[$salary->status] ?? 'gray';
                                @endphp
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold
                                    bg-{{ $c }}-100 text-{{ $c }}-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-{{ $c }}-500 inline-block"></span>
                                    {{ $salary->status_label }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-600 text-xs">
                                @if($salary->approver)
                                    {{ $salary->approver->name }}<br>
                                    <span class="text-slate-400">{{ $salary->approved_at?->translatedFormat('d F Y') }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <a href="{{ route('pengeluaran-lain.gaji.show', $salary->id) }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-600 text-xs font-bold hover:bg-indigo-100 transition-all">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i> Detail
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($salaries->hasPages())
            <div class="px-5 py-4 border-t border-slate-100">
                {{ $salaries->links() }}
            </div>
            @endif
        @endif
    </div>
</div>
@endsection
