<div class="tabs-scroll-container px-3 sm:px-5 pt-1 sm:pt-2 overflow-x-auto scrollbar-hide">
    <div class="flex items-center gap-0.5 sm:gap-1 md:gap-2 min-w-max border-b border-gray-100">
        @php
            $tabs = [
                'all' => ['label' => 'Semua', 'count' => $stats['count']],
                'pending' => ['label' => 'Pending', 'count' => $stats['pending']],
                'auto-reject' => ['label' => 'Auto Reject', 'count' => $stats['auto_reject'] ?? 0],
                'flagged' => ['label' => 'Flagged', 'count' => $stats['flagged'] ?? 0],
                'waiting_payment' => ['label' => 'Menunggu Pembayaran', 'count' => $stats['waiting_payment'] ?? 0],
                'approved' => ['label' => 'Menunggu Approve Owner', 'count' => $stats['approved'] ?? 0],
                'completed' => ['label' => 'Selesai', 'count' => $stats['completed']],
                'rejected' => ['label' => 'Ditolak', 'count' => $stats['rejected']],
            ];
            $currentStatus = request('status', 'all');
        @endphp

        @foreach($tabs as $key => $tab)
            <a href="{{ route('transactions.index', ['status' => $key === 'all' ? null : $key, 'search' => request('search')]) }}"
                class="relative px-2.5 sm:px-3 md:px-4 py-2.5 sm:py-3 text-[11px] sm:text-xs md:text-sm font-medium transition-all whitespace-nowrap {{ $currentStatus === $key ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                {{ $tab['label'] }}
                <span class="ml-0.5 sm:ml-1 text-[10px] sm:text-xs opacity-70 status-count"
                    data-status="{{ $key }}">({{ $tab['count'] }})</span>
                @if($currentStatus === $key)
                    <div class="absolute bottom-0 left-0 w-full h-[2px] bg-blue-600 rounded-t-full"></div>
                @endif
            </a>
        @endforeach
    </div>
</div>