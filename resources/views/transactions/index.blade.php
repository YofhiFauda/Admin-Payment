@extends('layouts.app')

@section('page-title', 'Data Riwayat Transaksi')

@section('content')
    <div class="space-y-6 md:space-y-10">
        {{-- Quick Statistics Bar --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-6">
            <div class="bg-white p-4 md:p-8 rounded-2xl md:rounded-[2.5rem] shadow-sm border border-gray-100 flex items-center gap-3 md:gap-6">
                <div class="w-10 h-10 md:w-16 md:h-16 rounded-2xl md:rounded-3xl bg-green-100 text-green-600 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="check-circle" class="w-5 h-5 md:w-8 md:h-8"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[8px] md:text-[10px] font-black text-slate-400 uppercase tracking-wider md:tracking-widest mb-1">Telah Selesai</p>
                    <h4 class="text-sm md:text-2xl font-black text-slate-900 tracking-tighter truncate">{{ $stats['completed'] }} </h4>
                </div>
            </div>
            <div class="bg-white p-4 md:p-8 rounded-2xl md:rounded-[2.5rem] shadow-sm border border-gray-100 flex items-center gap-3 md:gap-6">
                <div class="w-10 h-10 md:w-16 md:h-16 rounded-2xl md:rounded-3xl bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="clock" class="w-5 h-5 md:w-8 md:h-8"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[8px] md:text-[10px] font-black text-slate-400 uppercase tracking-wider md:tracking-widest mb-1">Menunggu Review</p>
                    <h4 class="text-sm md:text-2xl font-black text-slate-900 tracking-tighter truncate">{{ $stats['pending'] }} </h4>
                </div>
            </div>
            <div class="bg-white p-4 md:p-8 rounded-2xl md:rounded-[2.5rem] shadow-sm border border-gray-100 flex items-center gap-3 md:gap-6">
                <div class="w-10 h-10 md:w-16 md:h-16 rounded-2xl md:rounded-3xl bg-red-100 text-red-600 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="x-circle" class="w-5 h-5 md:w-8 md:h-8"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[8px] md:text-[10px] font-black text-slate-400 uppercase tracking-wider md:tracking-widest mb-1">Ditolak</p>
                    <h4 class="text-sm md:text-2xl font-black text-slate-900 tracking-tighter truncate">{{ $stats['rejected'] }} </h4>
                </div>
            </div>
            <div class="bg-white p-4 md:p-8 rounded-2xl md:rounded-[2.5rem] shadow-sm border border-gray-100 flex items-center gap-3 md:gap-6">
                <div class="w-10 h-10 md:w-16 md:h-16 rounded-2xl md:rounded-3xl bg-slate-900 text-white flex items-center justify-center flex-shrink-0">
                    <i data-lucide="file-text" class="w-5 h-5 md:w-8 md:h-8"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[8px] md:text-[10px] font-black text-slate-400 uppercase tracking-wider md:tracking-widest mb-1">Total Dokumen</p>
                    <h4 class="text-sm md:text-2xl font-black text-slate-900 tracking-tighter truncate">{{ $stats['count'] }} </h4>
                </div>
            </div>
        </div>

        {{-- Filters & Table --}}
        <div class="bg-white rounded-2xl md:rounded-[3.5rem] shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden">
            {{-- Filter Bar --}}
            <form method="GET" action="{{ route('transactions.index') }}"
                class="p-4 md:p-10 border-b border-gray-50 flex flex-col gap-4 md:gap-8">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 md:gap-6 w-full">
                    <div class="relative w-full sm:flex-1 sm:max-w-md">
                        <i data-lucide="search" class="w-5 h-5 md:w-6 md:h-6 absolute left-4 md:left-6 top-1/2 -translate-y-1/2 text-gray-300"></i>
                        <input type="text" name="search" id="quick-search" placeholder="Cari cepat nama vendor..."
                            value="{{ request('search') }}"
                            autocomplete="off"
                            class="w-full pl-12 md:pl-16 pr-4 md:pr-8 py-3 md:py-5 border border-gray-100 rounded-xl md:rounded-[2rem] text-xs md:text-sm outline-none focus:ring-4 focus:ring-blue-100/50 bg-slate-50/50 font-bold transition-all" />
                    </div>
                    <div class="relative w-full sm:w-58">
                        <i data-lucide="filter" class="w-4 h-4 md:w-5 md:h-5 absolute left-4 md:left-6 top-1/2 -translate-y-1/2 text-gray-300"></i>
                        <select name="status" onchange="this.form.submit()"
                            class="w-full pl-10 md:pl-14 pr-4 md:pr-8 py-3 md:py-5 border border-gray-100 rounded-xl md:rounded-[2rem] text-xs md:text-sm appearance-none outline-none focus:ring-4 focus:ring-blue-100/50 bg-slate-50/50 font-black uppercase tracking-widest transition-all">
                            <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>Semua Status
                            </option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>PENDING</option>
                            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>DISETUJUI
                            </option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>SELESAI
                            </option>
                            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>DITOLAK</option>
                        </select>
                        <i data-lucide="chevron-down" class="w-4 h-4 md:w-5 md:h-5 absolute right-4 md:right-6 top-1/2 -translate-y-1/2 text-gray-300"></i>
                    </div>
                    <button type="submit"
                        class="flex items-center justify-center gap-3 md:gap-4 px-6 md:px-10 py-3 md:py-5 rounded-xl md:rounded-[2rem] text-xs font-black uppercase tracking-widest bg-slate-900 text-white hover:bg-blue-600 transition-all shadow-2xl shadow-slate-900/10 active:scale-95 cursor-pointer whitespace-nowrap">
                        <i data-lucide="download" class="w-4 h-4 md:w-5 md:h-5"></i> <span class="hidden sm:inline">Export Excel</span><span class="sm:hidden">Export</span>
                    </button>
                </div>
            </form>

            {{-- Desktop Table View (hidden on mobile) --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left">
                    <thead
                        class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] border-b border-gray-100">
                        <tr>
                            <th class="px-6 lg:px-12 py-6 lg:py-8">Dokumen & Transaksi</th>
                            <th class="px-6 lg:px-12 py-6 lg:py-8 text-right">Nominal</th>
                            <th class="px-6 lg:px-12 py-6 lg:py-8">Alur Status</th>
                            <th class="px-6 lg:px-12 py-6 lg:py-8 text-center">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($transactions as $t)
                            @php
                                $statusColors = [
                                    'pending' => 'bg-amber-100 text-amber-700 border-amber-200',
                                    'approved' => 'bg-blue-100 text-blue-700 border-blue-200',
                                    'completed' => 'bg-green-100 text-green-700 border-green-200',
                                    'rejected' => 'bg-red-100 text-red-700 border-red-200',
                                ];
                                $statusDots = [
                                    'pending' => 'bg-amber-500',
                                    'approved' => 'bg-blue-500',
                                    'completed' => 'bg-green-500',
                                    'rejected' => 'bg-red-500',
                                ];
                                $statusLabels = [
                                    'pending' => 'Pending',
                                    'approved' => 'Menunggu Approve Owner',
                                    'completed' => 'Approve',
                                    'rejected' => 'Ditolak',
                                ];
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition-colors group transaction-row" data-name="{{ strtolower($t->customer) }}" data-invoice="{{ strtolower($t->invoice_number) }}">
                                <td class="px-6 lg:px-12 py-6 lg:py-8">
                                    <div class="flex items-center gap-4 lg:gap-5">
                                        <div
                                            class="bg-blue-50 text-blue-600 p-3 lg:p-4 rounded-xl lg:rounded-[1.2rem] group-hover:bg-blue-600 group-hover:text-white transition-all flex-shrink-0">
                                            <i data-lucide="file-text" class="w-5 h-5 lg:w-6 lg:h-6"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-[10px] font-black text-blue-500 uppercase tracking-widest mb-1">
                                                {{ $t->invoice_number }}</p>
                                            <h5 class="font-black text-slate-800 text-base lg:text-lg tracking-tight mb-1 truncate">{{ $t->customer }}
                                            </h5>
                                            @if($t->category)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-600 text-[9px] font-bold uppercase tracking-wider mb-1">
                                                    <i data-lucide="tag" class="w-2.5 h-2.5"></i>
                                                    {{ \App\Models\Transaction::CATEGORIES[$t->category] ?? $t->category }}
                                                </span>
                                            @endif
                                            <div class="flex items-center gap-3">
                                                <span
                                                    class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">{{ $t->created_at->format('d M Y') }}</span>
                                                <div class="w-1.5 h-1.5 rounded-full bg-slate-200"></div>
                                                <span
                                                    class="text-[10px] font-black text-blue-400 uppercase tracking-widest flex items-center gap-2">
                                                    <i data-lucide="user-circle" class="w-3.5 h-3.5"></i>
                                                    {{ $t->submitter->name ?? '-' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 lg:px-12 py-6 lg:py-8 text-right">
                                    <p class="font-black text-slate-900 text-xl lg:text-2xl tracking-tighter">{{ $t->formatted_amount }}
                                    </p>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1 flex items-center justify-end gap-1">
                                        <i data-lucide="building-2" class="w-3 h-3"></i>
                                        {{ $t->branches->count() }} Cabang
                                    </p>
                                </td>
                                <td class="px-6 lg:px-12 py-6 lg:py-8">
                                    <div class="space-y-2">
                                        <div class="inline-flex items-center gap-2 px-4 lg:px-5 py-2.5 lg:py-3 rounded-xl lg:rounded-2xl border-2 font-black text-[10px] uppercase tracking-widest {{ $statusColors[$t->status] ?? '' }}">
                                            <div class="w-2 h-2 rounded-full {{ $statusDots[$t->status] ?? '' }} animate-pulse"></div>
                                            {{ $statusLabels[$t->status] ?? $t->status }}
                                        </div>
                                        @if($t->reviewer)
                                            <p class="text-[9px] font-bold text-slate-400 flex items-center gap-1">
                                                <i data-lucide="user-check" class="w-3 h-3"></i>
                                                {{ $t->reviewer->name }} &bull; {{ $t->reviewed_at?->format('d M Y') }}
                                            </p>
                                        @endif
                                        @if($t->status === 'rejected' && $t->rejection_reason)
                                            <p class="text-[9px] font-bold text-red-500 flex items-center gap-1 max-w-48 truncate" title="{{ $t->rejection_reason }}">
                                                <i data-lucide="message-circle" class="w-3 h-3 flex-shrink-0"></i>
                                                {{ $t->rejection_reason }}
                                            </p>
                                        @endif
                                        @if(Auth::user()->canManageStatus() && $t->status === 'pending')
                                            <div class="flex flex-wrap gap-1.5 mt-1">
                                                <form method="POST" action="{{ route('transactions.updateStatus', $t->id) }}" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="approved">
                                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-green-50 text-green-700 hover:bg-green-600 hover:text-white text-[9px] font-black uppercase tracking-wider transition-all border border-green-200 cursor-pointer">
                                                        Approve
                                                    </button>
                                                </form>
                                                <button type="button" onclick="openRejectModal({{ $t->id }}, '{{ $t->invoice_number }}')"
                                                    class="px-3 py-1.5 rounded-lg bg-red-50 text-red-700 hover:bg-red-600 hover:text-white text-[9px] font-black uppercase tracking-wider transition-all border border-red-200 cursor-pointer">
                                                    Reject
                                                </button>
                                            </div>
                                        @endif
                                        {{-- Owner: approve transactions waiting for owner (>= 1jt, status approved) --}}
                                        @if(Auth::user()->isOwner() && $t->status === 'approved' && $t->amount >= 1000000)
                                            <div class="flex flex-wrap gap-1.5 mt-1">
                                                <form method="POST" action="{{ route('transactions.updateStatus', $t->id) }}" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="approved">
                                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-green-50 text-green-700 hover:bg-green-600 hover:text-white text-[9px] font-black uppercase tracking-wider transition-all border border-green-200 cursor-pointer">
                                                        Approve Final
                                                    </button>
                                                </form>
                                                <button type="button" onclick="openRejectModal({{ $t->id }}, '{{ $t->invoice_number }}')"
                                                    class="px-3 py-1.5 rounded-lg bg-red-50 text-red-700 hover:bg-red-600 hover:text-white text-[9px] font-black uppercase tracking-wider transition-all border border-red-200 cursor-pointer">
                                                    Reject
                                                </button>
                                            </div>
                                        @endif
                                        {{-- Owner: reset to pending --}}
                                        @if(Auth::user()->isOwner() && !in_array($t->status, ['pending', 'approved']))
                                            <div class="flex gap-1.5 mt-1">
                                                <form method="POST" action="{{ route('transactions.updateStatus', $t->id) }}" class="inline"
                                                    onsubmit="return confirm('Ubah status {{ $t->invoice_number }} ke Pending?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="pending">
                                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-500 hover:text-white text-[9px] font-black uppercase tracking-wider transition-all border border-amber-200 cursor-pointer">
                                                        Reset
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 lg:px-12 py-6 lg:py-8">
                                    <div class="flex items-center justify-center gap-2 lg:gap-3">
                                        <a href="{{ route('transactions.show', $t->id) }}"
                                            class="p-3 lg:p-4 bg-slate-50 text-slate-400 rounded-xl lg:rounded-2xl hover:text-blue-600 hover:bg-blue-50 transition-all border border-transparent hover:border-blue-100 shadow-sm">
                                            <i data-lucide="eye" class="w-4 h-4 lg:w-5 lg:h-5"></i>
                                        </a>
                                        @if(Auth::user()->canManageStatus())
                                            <a href="{{ route('transactions.edit', $t->id) }}"
                                                class="p-3 lg:p-4 bg-slate-50 text-slate-400 rounded-xl lg:rounded-2xl hover:text-amber-600 hover:bg-amber-50 transition-all border border-transparent hover:border-amber-100 shadow-sm">
                                                <i data-lucide="pencil" class="w-4 h-4 lg:w-5 lg:h-5"></i>
                                            </a>
                                            <form method="POST" action="{{ route('transactions.destroy', $t->id) }}" class="inline"
                                                onsubmit="return confirm('Hapus transaksi {{ $t->invoice_number }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="p-3 lg:p-4 bg-slate-50 text-slate-400 rounded-xl lg:rounded-2xl hover:text-red-600 hover:bg-red-50 transition-all border border-transparent hover:border-red-100 shadow-sm cursor-pointer">
                                                    <i data-lucide="trash-2" class="w-4 h-4 lg:w-5 lg:h-5"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-12 py-32 lg:py-48 text-center">
                                    <div class="flex flex-col items-center justify-center grayscale opacity-20">
                                        <div class="p-8 lg:p-10 bg-slate-100 rounded-full mb-6 lg:mb-8">
                                            <i data-lucide="search" class="w-16 h-16 lg:w-20 lg:h-20"></i>
                                        </div>
                                        <h4 class="text-2xl lg:text-3xl font-black uppercase tracking-[0.3em]">Data Nihil</h4>
                                        <p class="text-xs lg:text-sm font-bold mt-3 lg:mt-4 uppercase tracking-widest italic">Sesuaikan filter atau
                                            kata kunci pencarian Anda</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile Card View (shown on mobile only) --}}
            <div class="md:hidden divide-y divide-gray-50">
                @forelse($transactions as $t)
                    @php
                        $statusColors = [
                            'pending' => 'bg-amber-100 text-amber-700 border-amber-200',
                            'approved' => 'bg-blue-100 text-blue-700 border-blue-200',
                            'completed' => 'bg-green-100 text-green-700 border-green-200',
                            'rejected' => 'bg-red-100 text-red-700 border-red-200',
                        ];
                        $statusDots = [
                            'pending' => 'bg-amber-500',
                            'approved' => 'bg-blue-500',
                            'completed' => 'bg-green-500',
                            'rejected' => 'bg-red-500',
                        ];
                        $statusLabels = [
                            'pending' => 'Pending',
                            'approved' => 'Menunggu Approve Owner',
                            'completed' => 'Approve',
                            'rejected' => 'Ditolak',
                        ];
                    @endphp
                    
                    <div class="p-4 hover:bg-slate-50/50 transition-colors transaction-card" data-name="{{ strtolower($t->customer) }}" data-invoice="{{ strtolower($t->invoice_number) }}">
                        {{-- Header with invoice and vendor --}}
                        <div class="flex items-start gap-3 mb-4">
                            <div class="bg-blue-50 text-blue-600 p-3 rounded-xl flex-shrink-0">
                                <i data-lucide="file-text" class="w-5 h-5"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[9px] font-black text-blue-500 uppercase tracking-widest mb-0.5">
                                    {{ $t->invoice_number }}
                                </p>
                                <h5 class="font-black text-slate-800 text-base tracking-tight mb-1 break-words">
                                    {{ $t->customer }}
                                </h5>
                                @if($t->category)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-600 text-[9px] font-bold uppercase tracking-wider mb-2">
                                        <i data-lucide="tag" class="w-2.5 h-2.5"></i>
                                        {{ \App\Models\Transaction::CATEGORIES[$t->category] ?? $t->category }}
                                    </span>
                                @endif
                                <div class="flex items-center gap-2 flex-wrap text-[9px] font-black text-slate-400 uppercase">
                                    <span>{{ $t->created_at->format('d M Y') }}</span>
                                    <div class="w-1 h-1 rounded-full bg-slate-200"></div>
                                    <span class="text-blue-400 flex items-center gap-1.5">
                                        <i data-lucide="user-circle" class="w-3 h-3"></i>
                                        {{ $t->submitter->name ?? '-' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Amount --}}
                        <div class="mb-4 p-3 bg-slate-50 rounded-xl">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Nominal Transaksi</p>
                            <p class="font-black text-slate-900 text-xl tracking-tighter">{{ $t->formatted_amount }}</p>
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1 flex items-center gap-1">
                                <i data-lucide="building-2" class="w-3 h-3"></i>
                                {{ $t->branches->count() }} Cabang
                            </p>
                        </div>

                        {{-- Status --}}
                        <div class="mb-4">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Status</p>
                            <div class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border-2 font-black text-[10px] uppercase tracking-widest {{ $statusColors[$t->status] ?? '' }}">
                                <div class="w-2 h-2 rounded-full {{ $statusDots[$t->status] ?? '' }} animate-pulse"></div>
                                {{ $statusLabels[$t->status] ?? $t->status }}
                            </div>
                            @if($t->reviewer)
                                <p class="text-[9px] font-bold text-slate-400 mt-2 flex items-center gap-1">
                                    <i data-lucide="user-check" class="w-3 h-3"></i>
                                    {{ $t->reviewer->name }} &bull; {{ $t->reviewed_at?->format('d M Y') }}
                                </p>
                            @endif
                            @if($t->status === 'rejected' && $t->rejection_reason)
                                <p class="text-[9px] font-bold text-red-500 mt-1 flex items-center gap-1">
                                    <i data-lucide="message-circle" class="w-3 h-3 flex-shrink-0"></i>
                                    {{ $t->rejection_reason }}
                                </p>
                            @endif
                            @if(Auth::user()->canManageStatus() && $t->status === 'pending')
                                <div class="flex gap-2 mt-3">
                                    <form method="POST" action="{{ route('transactions.updateStatus', $t->id) }}" class="flex-1">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="w-full py-2.5 rounded-xl bg-green-50 text-green-700 hover:bg-green-600 hover:text-white text-[10px] font-black uppercase tracking-wider transition-all border border-green-200 cursor-pointer">
                                            Approve
                                        </button>
                                    </form>
                                    <button type="button" onclick="openRejectModal({{ $t->id }}, '{{ $t->invoice_number }}')"
                                        class="flex-1 py-2.5 rounded-xl bg-red-50 text-red-700 hover:bg-red-600 hover:text-white text-[10px] font-black uppercase tracking-wider transition-all border border-red-200 cursor-pointer">
                                        Reject
                                    </button>
                                </div>
                            @endif
                            {{-- Owner: approve transactions waiting for owner --}}
                            @if(Auth::user()->isOwner() && $t->status === 'approved' && $t->amount >= 1000000)
                                <div class="flex gap-2 mt-2">
                                    <form method="POST" action="{{ route('transactions.updateStatus', $t->id) }}" class="flex-1">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="w-full py-2.5 rounded-xl bg-green-50 text-green-700 hover:bg-green-600 hover:text-white text-[10px] font-black uppercase tracking-wider transition-all border border-green-200 cursor-pointer">
                                            Approve Final
                                        </button>
                                    </form>
                                    <button type="button" onclick="openRejectModal({{ $t->id }}, '{{ $t->invoice_number }}')"
                                        class="flex-1 py-2.5 rounded-xl bg-red-50 text-red-700 hover:bg-red-600 hover:text-white text-[10px] font-black uppercase tracking-wider transition-all border border-red-200 cursor-pointer">
                                        Reject
                                    </button>
                                </div>
                            @endif
                            {{-- Owner: reset to pending --}}
                            @if(Auth::user()->isOwner() && !in_array($t->status, ['pending', 'approved']))
                                <div class="flex gap-2 mt-2">
                                    <form method="POST" action="{{ route('transactions.updateStatus', $t->id) }}" class="flex-1"
                                        onsubmit="return confirm('Ubah status ke Pending?')">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="pending">
                                        <button type="submit" class="w-full py-2.5 rounded-xl bg-amber-50 text-amber-700 hover:bg-amber-500 hover:text-white text-[10px] font-black uppercase tracking-wider transition-all border border-amber-200 cursor-pointer">
                                            Reset Status
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                            <a href="{{ route('transactions.show', $t->id) }}"
                                class="flex-1 flex items-center justify-center gap-2 p-3 bg-slate-50 text-slate-600 rounded-xl hover:text-blue-600 hover:bg-blue-50 transition-all border border-transparent hover:border-blue-100 shadow-sm">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                                <span class="text-xs font-bold">Detail</span>
                            </a>
                            @if(Auth::user()->canManageStatus())
                                <a href="{{ route('transactions.edit', $t->id) }}"
                                    class="flex-1 flex items-center justify-center gap-2 p-3 bg-slate-50 text-slate-600 rounded-xl hover:text-amber-600 hover:bg-amber-50 transition-all border border-transparent hover:border-amber-100 shadow-sm">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                    <span class="text-xs font-bold">Edit</span>
                                </a>
                                <form method="POST" action="{{ route('transactions.destroy', $t->id) }}" class="flex-1"
                                    onsubmit="return confirm('Hapus transaksi {{ $t->invoice_number }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="w-full flex items-center justify-center gap-2 p-3 bg-slate-50 text-slate-600 rounded-xl hover:text-red-600 hover:bg-red-50 transition-all border border-transparent hover:border-red-100 shadow-sm">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        <span class="text-xs font-bold">Hapus</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-16 text-center">
                        <div class="flex flex-col items-center justify-center grayscale opacity-20">
                            <div class="p-8 bg-slate-100 rounded-full mb-6">
                                <i data-lucide="search" class="w-16 h-16"></i>
                            </div>
                            <h4 class="text-xl font-black uppercase tracking-[0.3em]">Data Nihil</h4>
                            <p class="text-xs font-bold mt-3 uppercase tracking-widest italic px-4">Sesuaikan filter atau kata kunci pencarian Anda</p>
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if($transactions->hasPages())
                <div class="px-4 md:px-12 py-4 md:py-8 border-t border-gray-100">
                    {{ $transactions->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Reject Modal --}}
    @if(Auth::user()->canManageStatus())
    <div id="reject-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-black text-slate-900">Tolak Nota</h3>
                    <p class="text-xs text-slate-400" id="reject-modal-invoice"></p>
                </div>
            </div>
            <form id="reject-form" method="POST" action="">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="rejected">
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Alasan Penolakan <span class="text-red-500">*</span></label>
                    <textarea name="rejection_reason" rows="3" required placeholder="Tuliskan alasan penolakan..."
                        class="w-full border border-slate-200 rounded-xl p-3 text-sm outline-none focus:ring-2 focus:ring-red-100 resize-none"></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeRejectModal()"
                        class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-100 text-slate-600 font-bold text-xs uppercase tracking-wider hover:bg-slate-200 transition-all cursor-pointer border border-slate-200">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        Batal
                    </button>
                    <button type="submit"
                        class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-red-600 text-white font-bold text-xs uppercase tracking-wider hover:bg-red-700 transition-all cursor-pointer shadow-lg shadow-red-200">
                        <i data-lucide="x-circle" class="w-4 h-4"></i>
                        Konfirmasi Tolak
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
<script>
    function openRejectModal(transactionId, invoiceNumber) {
        const modal = document.getElementById('reject-modal');
        const form = document.getElementById('reject-form');
        const invoiceEl = document.getElementById('reject-modal-invoice');

        form.action = '/transactions/' + transactionId + '/status';
        invoiceEl.textContent = invoiceNumber;
        modal.classList.remove('hidden');

        // Re-init icons in modal
        lucide.createIcons();
    }

    function closeRejectModal() {
        const modal = document.getElementById('reject-modal');
        modal.classList.add('hidden');
        // Clear textarea
        modal.querySelector('textarea').value = '';
    }

    // Close modal on backdrop click
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('reject-modal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) closeRejectModal();
            });
        }

        // Quick Search - instant filter as you type
        const searchInput = document.getElementById('quick-search');
        if (searchInput) {
            let debounceTimer;
            searchInput.addEventListener('keyup', function(e) {
                // Prevent form submit on Enter
                if (e.key === 'Enter') {
                    e.preventDefault();
                    return;
                }
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    const query = this.value.toLowerCase().trim();
                    // Filter desktop rows
                    document.querySelectorAll('.transaction-row').forEach(row => {
                        const name = row.getAttribute('data-name') || '';
                        const invoice = row.getAttribute('data-invoice') || '';
                        const match = !query || name.includes(query) || invoice.includes(query);
                        row.style.display = match ? '' : 'none';
                    });
                    // Filter mobile cards
                    document.querySelectorAll('.transaction-card').forEach(card => {
                        const name = card.getAttribute('data-name') || '';
                        const invoice = card.getAttribute('data-invoice') || '';
                        const match = !query || name.includes(query) || invoice.includes(query);
                        card.style.display = match ? '' : 'none';
                    });
                }, 150);
            });

            // Prevent form submit on Enter for quick search
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') e.preventDefault();
            });
        }
    });
</script>
@endpush