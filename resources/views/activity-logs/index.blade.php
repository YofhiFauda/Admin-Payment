@extends('layouts.app')

@section('content')
<div class="px-4 py-8 max-w-8xl mx-auto">
    <!-- Header Section -->
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Log Aktivitas</h1>
        <p class="mt-2 text-slate-500 font-medium">Catatan aktivitas persetujuan, penolakan, dan perubahan data.</p>
    </div>

    <!-- Log Table Card -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100">Waktu</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100">Aktor (Pengguna)</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 text-center">Aksi</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100">Target ID</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-5">
                                <span class="text-sm font-semibold text-slate-600 block">
                                    {{ $log->created_at->format('d M Y,') }}
                                </span>
                                <span class="text-xs text-slate-400 mt-0.5 block">
                                    {{ $log->created_at->format('H.i.s') }}
                                </span>
                            </td>
                            <td class="px-6 py-5">
                                <span class="text-sm font-bold text-slate-800">
                                    {{ $log->user->name ?? 'System' }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-center">
                                @php
                                    $actionColors = [
                                        'approve' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                                        'reject'  => 'bg-rose-50 text-rose-600 border-rose-100',
                                        'edit'    => 'bg-blue-50 text-blue-600 border-blue-100',
                                        'submit'  => 'bg-indigo-50 text-indigo-600 border-indigo-100',
                                    ];
                                    $colorClass = $actionColors[strtolower($log->action)] ?? 'bg-slate-50 text-slate-600 border-slate-100';
                                @endphp
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border {{ $colorClass }}">
                                    {{ ucfirst($log->action) }}
                                </span>
                            </td>
                            <td class="px-6 py-5">
                                @if($log->target_id)
                                    <a href="{{ route('transactions.index', ['search' => $log->target_id]) }}" class="text-sm font-mono font-bold text-blue-600 hover:text-blue-800 hover:underline uppercase transition-colors">
                                        {{ $log->target_id }}
                                    </a>
                                @else
                                    <span class="text-sm font-mono font-medium text-slate-500 uppercase">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-5">
                                <p class="text-sm text-slate-600 leading-relaxed max-w-md">
                                    {{ $log->description }}
                                </p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                        <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-slate-800 font-bold">Belum Ada Catatan</h3>
                                    <p class="text-slate-400 text-sm mt-1">Belum ada aktivitas yang dicatat saat ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($logs->hasPages())
            <div class="px-6 py-4 bg-slate-50/50 border-t border-slate-100">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // ─────────────────────────────────────────────────────────
    // REALTIME ECHO HANDLER (ACTIVITY LOGS)
    // ─────────────────────────────────────────────────────────
    window.handleRealtimeActivityLog = function(activityLog) {
        // Fetch the current page HTML via XHR so we don't do a full page reload,
        // then swap the table body natively to make it feel instant.
        fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                const newTbody = doc.querySelector('table tbody');
                const oldTbody = document.querySelector('table tbody');
                
                if (newTbody && oldTbody) {
                    oldTbody.innerHTML = newTbody.innerHTML;
                }
            });
    };
</script>
@endpush
@endsection
