@extends('layouts.app')

@section('page-title', 'Data Riwayat Transaksi')

@section('content')

        {{-- Main Content Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
            {{-- Header Toolbar --}}
            <div class="p-5 border-b border-gray-100 flex flex-col md:flex-row gap-4 justify-between items-center">
                {{-- Search --}}
                <div class="relative w-full md:w-64 lg:w-96">
                    <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                    <form method="GET" action="{{ route('transactions.index') }}">
                        @if(request('status'))
                            <input type="hidden" name="status" value="{{ request('status') }}">
                        @endif
                        <input type="text" 
                            name="search" 
                            value="{{ request('search') }}"
                            placeholder="Search payments id, user..." 
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-300 transition-all placeholder:text-gray-400">
                    </form>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-3 w-full md:w-auto overflow-x-auto pb-1 md:pb-0 scrollbar-hide">
                    <button class="flex items-center gap-2 px-4 py-2.5 border border-gray-200 rounded-xl bg-white hover:bg-gray-50 text-sm font-semibold text-gray-700 transition-all whitespace-nowrap">
                        <i data-lucide="filter" class="w-4 h-4"></i>
                        Filter
                    </button>
                    <button class="flex items-center gap-2 px-4 py-2.5 border border-blue-100 bg-blue-50/50 rounded-xl hover:bg-blue-100 text-sm font-semibold text-blue-600 transition-all whitespace-nowrap">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        Exports
                    </button>
                </div>
            </div>

            {{-- Status Tabs --}}
            <div class="px-5 pt-2 overflow-x-auto">
                <div class="flex items-center gap-2 min-w-max border-b border-gray-100">
                    @php
                        $tabs = [
                            'all' => ['label' => 'All', 'count' => $stats['count']],
                            'pending' => ['label' => 'Pending', 'count' => $stats['pending']],
                            'approved' => ['label' => 'Approved', 'count' => $stats['approved'] ?? 0], // Assuming you might have this stat
                            'completed' => ['label' => 'Paid', 'count' => $stats['completed']],
                            'rejected' => ['label' => 'Rejected', 'count' => $stats['rejected']],
                        ];
                        $currentStatus = request('status', 'all');
                    @endphp

                    @foreach($tabs as $key => $tab)
                        <a href="{{ route('transactions.index', ['status' => $key === 'all' ? null : $key, 'search' => request('search')]) }}" 
                           class="relative px-4 py-3 text-sm font-medium transition-all {{ $currentStatus === $key ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                            {{ $tab['label'] }} 
                            <span class="ml-1 text-xs opacity-70">({{ $tab['count'] }})</span>
                            @if($currentStatus === $key)
                                <div class="absolute bottom-0 left-0 w-full h-[2px] bg-blue-600 rounded-t-full"></div>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Table View --}}
            <div class="hidden lg:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-100 text-xs uppercase tracking-wider text-gray-400 font-semibold bg-gray-50/50">
                            <th class="px-6 py-4 w-12 text-center">
                                <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-100 w-4 h-4">
                            </th>
                            <th class="px-6 py-4">Contact</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Type</th>
                            <th class="px-6 py-4">Manager</th>
                            <th class="px-6 py-4">Amount</th>
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4 w-12"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-sm text-gray-600">
                        @forelse($transactions as $t)
                            <tr class="hover:bg-gray-50/50 transition-colors group">
                                <td class="px-6 py-4 text-center">
                                    <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-100 w-4 h-4">
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900">{{ $t->customer }}</div>
                                    <div class="text-xs text-gray-400 mt-0.5 font-medium">{{ $t->invoice_number }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $badges = [
                                            'pending' => 'bg-amber-50 text-amber-600 border border-amber-100',
                                            'approved' => 'bg-blue-50 text-blue-600 border border-blue-100',
                                            'completed' => 'bg-green-50 text-green-600 border border-green-100', // "Paid" look
                                            'rejected' => 'bg-red-50 text-red-600 border border-red-100',
                                        ];
                                        $labels = [
                                            'pending' => 'Pending',
                                            'approved' => 'Approved',
                                            'completed' => 'Paid',
                                            'rejected' => 'Failed',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $badges[$t->status] ?? 'bg-gray-100 text-gray-600' }}">
                                        {{ $labels[$t->status] ?? ucfirst($t->status) }}
                                    </span>
                                    @if($t->status === 'rejected' && $t->rejection_reason)
                                        <i data-lucide="info" class="w-3 h-3 text-red-400 ml-1 inline cursor-help" title="{{ $t->rejection_reason }}"></i>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-600 font-medium">
                                        {{ \App\Models\Transaction::CATEGORIES[$t->category] ?? $t->category }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        {{-- Optional Avatar --}}
                                        {{-- <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-[10px] font-bold">
                                            {{ substr($t->submitter->name ?? 'U', 0, 1) }}
                                        </div> --}}
                                        <span>{{ $t->submitter->name ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-bold text-gray-900">
                                    {{ $t->formatted_amount }}
                                </td>
                                <td class="px-6 py-4 text-gray-500 font-medium">
                                    {{ $t->created_at->format('d M, Y') }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="relative group-action">
                                        <button class="p-1.5 hover:bg-gray-100 rounded-lg text-gray-400 hover:text-gray-600 transition-colors">
                                            <i data-lucide="more-vertical" class="w-4 h-4"></i>
                                        </button>
                                        {{-- Dropdown (Simplified for now, can be JS interactive or just inline) 
                                             For now, let's keep it simple with inline actions or a proper dropdown if needed.
                                             Let's stick to the visible actions logic from before but cleaner.
                                        --}}
                                        <div class="hidden absolute right-0 top-full mt-1 w-48 bg-white rounded-xl shadow-xl border border-gray-100 z-10 p-1 group-hover:block dir-rtl">
                                            <a href="{{ route('transactions.show', $t->id) }}" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-lg">
                                                <i data-lucide="eye" class="w-4 h-4"></i> View Details
                                            </a>
                                            @if(Auth::user()->canManageStatus())
                                                <a href="{{ route('transactions.edit', $t->id) }}" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-lg">
                                                    <i data-lucide="pencil" class="w-4 h-4"></i> Edit
                                                </a>
                                                {{-- Quick Approve (Only if pending) --}}
                                                @if($t->status === 'pending')
                                                    <form action="{{ route('transactions.updateStatus', $t->id) }}" method="POST">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="status" value="approved">
                                                        <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-green-600 hover:bg-green-50 rounded-lg">
                                                            <i data-lucide="check" class="w-4 h-4"></i> Approve
                                                        </button>
                                                    </form>
                                                    <button onclick="openRejectModal({{ $t->id }}, '{{ $t->invoice_number }}')" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg">
                                                        <i data-lucide="x" class="w-4 h-4"></i> Reject
                                                    </button>
                                                @endif
                                            @endif
                                            
                                            {{-- Owner Reset Action --}}
                                            @if(Auth::user()->isOwner() && $t->status !== 'pending')
                                                <div class="border-t border-gray-100 my-1"></div>
                                                <form action="{{ route('transactions.updateStatus', $t->id) }}" method="POST" onsubmit="return confirm('Reset status to Pending?')">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="status" value="pending">
                                                    <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-amber-600 hover:bg-amber-50 rounded-lg">
                                                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i> Reset Status
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center justify-center grayscale opacity-40">
                                        <div class="p-4 bg-gray-50 rounded-full mb-4">
                                            <i data-lucide="search" class="w-8 h-8 text-gray-400"></i>
                                        </div>
                                        <h3 class="text-base font-bold text-gray-900">No Transactions Found</h3>
                                        <p class="text-xs text-gray-500 mt-1">Try adjusting your filters or search query.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile/Tablet Card View (shown on screens smaller than LG) --}}
            <div class="lg:hidden divide-y divide-gray-50">
                @forelse($transactions as $t)
                    @php
                        // Status styling for cards
                        $statusColors = [
                            'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                            'approved' => 'bg-blue-50 text-blue-700 border-blue-200',
                            'completed' => 'bg-green-50 text-green-700 border-green-200', // Paid
                            'rejected' => 'bg-red-50 text-red-700 border-red-200',
                        ];
                        $statusLabels = [
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                            'completed' => 'Paid',
                            'rejected' => 'Rejected',
                        ];
                    @endphp
                    
                    <div class="p-4 md:p-5 hover:bg-slate-50/50 transition-colors transaction-card" data-name="{{ strtolower($t->customer) }}" data-invoice="{{ strtolower($t->invoice_number) }}">
                        {{-- Header with invoice and vendor --}}
                        <div class="flex items-start gap-3 mb-3">
                            <div class="bg-blue-50 text-blue-600 p-2.5 rounded-xl flex-shrink-0">
                                <i data-lucide="file-text" class="w-5 h-5"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-[10px] font-bold text-blue-500 uppercase tracking-widest mb-0.5">
                                            {{ $t->invoice_number }}
                                        </p>
                                        <h5 class="font-bold text-slate-900 text-base tracking-tight mb-1 break-words">
                                            {{ $t->customer }}
                                        </h5>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide border {{ $statusColors[$t->status] ?? 'bg-gray-100 text-gray-600' }}">
                                        {{ $statusLabels[$t->status] ?? ucfirst($t->status) }}
                                    </span>
                                </div>
                                
                                <div class="flex items-center gap-2 flex-wrap text-xs font-medium text-slate-500 mt-1">
                                    <span>{{ $t->created_at->format('d M, Y') }}</span>
                                    <div class="w-1 h-1 rounded-full bg-slate-300"></div>
                                    <span class="flex items-center gap-1.5">
                                        <i data-lucide="user" class="w-3 h-3"></i>
                                        {{ $t->submitter->name ?? '-' }}
                                    </span>
                                    @if($t->category)
                                        <div class="w-1 h-1 rounded-full bg-slate-300"></div>
                                        <span>{{ \App\Models\Transaction::CATEGORIES[$t->category] ?? $t->category }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Amount --}}
                        <div class="mb-4">
                            <p class="font-black text-slate-900 text-lg tracking-tight">{{ $t->formatted_amount }}</p>
                             @if($t->status === 'rejected' && $t->rejection_reason)
                                <p class="text-xs text-red-500 mt-1.5 flex items-start gap-1.5 bg-red-50 p-2 rounded-lg border border-red-100">
                                    <i data-lucide="alert-circle" class="w-3.5 h-3.5 mt-0.5 flex-shrink-0"></i>
                                    {{ $t->rejection_reason }}
                                </p>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="grid grid-cols-2 gap-2 mt-3">
                             <a href="{{ route('transactions.show', $t->id) }}"
                                class="flex items-center justify-center gap-2 p-2.5 bg-white border border-gray-200 text-slate-600 rounded-xl hover:text-blue-600 hover:border-blue-200 transition-all shadow-sm">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                                <span class="text-xs font-bold">Detail</span>
                            </a>
                            
                            @if(Auth::user()->canManageStatus())
                                @if($t->status === 'pending')
                                    <form method="POST" action="{{ route('transactions.updateStatus', $t->id) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="w-full h-full flex items-center justify-center gap-2 p-2.5 bg-green-50 text-green-700 border border-green-200 rounded-xl hover:bg-green-100 transition-all shadow-sm">
                                            <i data-lucide="check" class="w-4 h-4"></i>
                                            <span class="text-xs font-bold">Approve</span>
                                        </button>
                                    </form>
                                    <button type="button" onclick="openRejectModal({{ $t->id }}, '{{ $t->invoice_number }}')" class="col-span-2 flex items-center justify-center gap-2 p-2.5 bg-white text-red-600 border border-red-100 rounded-xl hover:bg-red-50 transition-all shadow-sm">
                                        <i data-lucide="x" class="w-4 h-4"></i>
                                        <span class="text-xs font-bold">Reject</span>
                                    </button>
                                @else
                                    <a href="{{ route('transactions.edit', $t->id) }}"
                                        class="flex items-center justify-center gap-2 p-2.5 bg-white border border-gray-200 text-slate-600 rounded-xl hover:text-amber-600 hover:border-amber-200 transition-all shadow-sm">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                        <span class="text-xs font-bold">Edit</span>
                                    </a>
                                @endif
                            @endif
                            
                            {{-- Owner Reset --}}
                            @if(Auth::user()->isOwner() && $t->status !== 'pending')
                                <div class="col-span-2">
                                    <form method="POST" action="{{ route('transactions.updateStatus', $t->id) }}" onsubmit="return confirm('Reset status to Pending?')">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="pending">
                                        <button type="submit" class="w-full flex items-center justify-center gap-2 p-2.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-xl hover:bg-amber-100 transition-all shadow-sm">
                                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                            <span class="text-xs font-bold">Reset Status</span>
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center">
                        <div class="flex flex-col items-center justify-center grayscale opacity-40">
                            <i data-lucide="search" class="w-12 h-12 text-gray-300 mb-3"></i>
                            <h3 class="text-sm font-bold text-gray-900">No Transactions</h3>
                            <p class="text-xs text-gray-500">Try adjusting your filters.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- Footer / Pagination --}}
            <div class="p-5 border-t border-gray-100 flex items-center justify-between">
                <p class="text-xs text-gray-500 font-medium">
                    Showing {{ $transactions->firstItem() ?? 0 }} - {{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }} payments
                </p>
                @if($transactions->hasPages())
                    <div class="scale-90 origin-right">
                        {{ $transactions->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Reject Modal (Kept same logic, updated style) --}}
    @if(Auth::user()->canManageStatus())
        <div id="reject-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/20 backdrop-blur-sm p-4 transition-all duration-300 opacity-0" aria-hidden="true">
            <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 transform scale-95 transition-all duration-300">
                <div class="text-center mb-6">
                    <div class="w-12 h-12 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="alert-octagon" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Reject Transaction</h3>
                    <p class="text-sm text-gray-500 mt-1">Are you sure you want to reject <span id="reject-modal-invoice" class="font-mono font-medium text-gray-800"></span>?</p>
                </div>
                
                <form id="reject-form" method="POST" action="">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="rejected">
                    <div class="mb-5">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Rejection Reason</label>
                        <textarea name="rejection_reason" rows="3" required placeholder="Type reason here..."
                            class="w-full border border-gray-200 rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-100 focus:border-red-300 transition-all resize-none"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" onclick="closeRejectModal()"
                            class="px-4 py-2.5 rounded-xl border border-gray-200 text-gray-600 font-semibold text-sm hover:bg-gray-50 transition-all">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2.5 rounded-xl bg-red-600 text-white font-semibold text-sm hover:bg-red-700 shadow-lg shadow-red-600/20 transition-all">
                            Reject Now
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
        // Simple fade in
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modal.querySelector('div').classList.remove('scale-95');
            modal.querySelector('div').classList.add('scale-100');
        }, 10);
    }

    function closeRejectModal() {
        const modal = document.getElementById('reject-modal');
        modal.classList.add('opacity-0');
        modal.querySelector('div').classList.remove('scale-100');
        modal.querySelector('div').classList.add('scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.querySelector('textarea').value = '';
        }, 300);
    }

    // Close modal on backdrop click
    const modal = document.getElementById('reject-modal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeRejectModal();
        });
    }

    // Hover dropdown logic for desktop if not using click
    document.querySelectorAll('.group-action').forEach(group => {
        group.addEventListener('mouseenter', () => {
            const dropdown = group.querySelector('div');
            if(dropdown) dropdown.classList.remove('hidden');
        });
        group.addEventListener('mouseleave', () => {
            const dropdown = group.querySelector('div');
            if(dropdown) dropdown.classList.add('hidden');
        });
    });
</script>
@endpush