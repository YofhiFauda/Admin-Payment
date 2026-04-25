@extends('layouts.app')

@section('title', 'Anomali Harga — Price Index')

@section('content')
<div class="space-y-6 p-6">

    {{-- ─── Header ─────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('price-index.index') }}" class="text-blue-600 hover:underline text-sm">📊 Price Index</a>
                <span class="text-gray-400 text-sm">›</span>
                <span class="text-sm text-gray-600 dark:text-gray-400">Anomali Harga</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">⚠️ Anomali Harga</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Daftar harga pengajuan yang melebihi referensi maksimum</p>
        </div>

        <div class="flex items-center gap-3">
            @if ($criticalCount > 0)
                <div class="flex items-center gap-1.5 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800 px-3 py-2 rounded-xl text-sm font-medium">
                    <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                    {{ $criticalCount }} Critical
                </div>
            @endif
            <div class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 px-3 py-2 rounded-xl text-sm">
                {{ $pendingCount }} Pending
            </div>
        </div>
    </div>

    {{-- ─── Filter & Bulk Actions ──────────────────────── --}}
    <div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4">
        <form method="GET" action="{{ route('price-index.anomalies') }}" class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari invoice, barang, teknisi..." class="border rounded-xl px-4 py-2 text-sm bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none w-full sm:w-64">
            <select name="status" class="border rounded-xl px-4 py-2 text-sm bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <option value="pending" @selected(!request('status') || request('status') === 'pending')>Pending saja</option>
                <option value="approved" @selected(request('status') === 'approved')>Disetujui</option>
                <option value="rejected" @selected(request('status') === 'rejected')>Ditolak</option>
                <option value="" @selected(request('status') === '')>Semua status</option>
            </select>
            <select name="severity" class="border rounded-xl px-4 py-2 text-sm bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <option value="">Semua Severity</option>
                <option value="critical" @selected(request('severity') === 'critical')>🔴 Critical</option>
                <option value="medium"   @selected(request('severity') === 'medium')>🟠 Medium</option>
                <option value="low"      @selected(request('severity') === 'low')>🟡 Low</option>
            </select>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl text-sm font-medium transition">Filter</button>
        </form>

        {{-- Bulk Actions (Hidden by default) --}}
        <div id="bulkActions" class="hidden flex items-center gap-2 animate-in fade-in slide-in-from-right-4 duration-300">
            <span class="text-sm text-gray-500 dark:text-gray-400 mr-2"><span id="selectedCount">0</span> terpilih</span>
            <button onclick="bulkReview('approved')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition flex items-center gap-1.5">
                <i data-lucide="check-circle" class="w-4 h-4"></i> Approve
            </button>
            <button onclick="bulkReview('rejected')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition flex items-center gap-1.5">
                <i data-lucide="x-circle" class="w-4 h-4"></i> Reject
            </button>
        </div>
    </div>

    {{-- ─── Table ───────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-center w-10">
                            <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </th>
                        <th class="px-4 py-3 text-left">Severity</th>
                        <th class="px-4 py-3 text-left">Invoice / Teknisi</th>
                        <th class="px-4 py-3 text-left">Nama Barang</th>
                        <th class="px-4 py-3 text-right">Harga Input</th>
                        <th class="px-4 py-3 text-right">Harga Max Ref</th>
                        <th class="px-4 py-3 text-right">Selisih</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($anomalies as $anomaly)
                        @php
                            $severityBg = match($anomaly->severity) {
                                'critical' => 'bg-red-50/50 dark:bg-red-950/20',
                                'medium'   => 'bg-orange-50/50 dark:bg-orange-950/20',
                                default    => '',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition {{ $severityBg }}" id="anomaly-row-{{ $anomaly->id }}">
                            <td class="px-4 py-3 text-center">
                                @if ($anomaly->status === 'pending')
                                    <input type="checkbox" name="ids[]" value="{{ $anomaly->id }}" class="anomaly-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                @endif
                            </td>
                            {{-- Severity --}}
                            <td class="px-4 py-3">
                                @php
                                    $sevColor = match($anomaly->severity) {
                                        'critical' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
                                        'medium'   => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-400',
                                        default    => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400',
                                    };
                                    $sevIcon = match($anomaly->severity) {
                                        'critical' => '🔴', 'medium' => '🟠', default => '🟡',
                                    };
                                @endphp
                                <span class="inline-flex items-center gap-1 {{ $sevColor }} px-2 py-0.5 rounded-full text-xs font-semibold">
                                    {{ $sevIcon }} {{ ucfirst($anomaly->severity) }}
                                </span>
                                <div class="text-xs text-orange-600 dark:text-orange-400 font-bold mt-0.5">
                                    +{{ number_format($anomaly->excess_percentage, 1) }}%
                                </div>
                            </td>

                            {{-- Invoice / Teknisi --}}
                            <td class="px-4 py-3">
                                <div class="font-mono text-xs text-blue-600 dark:text-blue-400">
                                    {{ $anomaly->transaction?->invoice_number ?? '-' }}
                                </div>
                                <div class="text-gray-500 dark:text-gray-400 text-xs">
                                    {{ $anomaly->reporter?->name ?? '-' }}
                                </div>
                            </td>

                            {{-- Item --}}
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $anomaly->item_name }}</td>

                            {{-- Harga Input --}}
                            <td class="px-4 py-3 text-right text-red-600 dark:text-red-400 font-semibold">
                                {{ $anomaly->formatted_input_price }}
                            </td>

                            {{-- Harga Max Ref --}}
                            <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">
                                {{ $anomaly->formatted_ref_max }}
                            </td>

                            {{-- Selisih --}}
                            <td class="px-4 py-3 text-right text-orange-600 dark:text-orange-400 font-semibold">
                                +{{ $anomaly->formatted_excess }}
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-3 text-center">
                                @php
                                    $statusColor = match($anomaly->status) {
                                        'pending'  => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        'approved' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                        'rejected' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                        default    => 'bg-gray-100 text-gray-700',
                                    };
                                @endphp
                                <span class="inline-block {{ $statusColor }} px-2 py-0.5 rounded-full text-xs font-medium" id="status-badge-{{ $anomaly->id }}">
                                    {{ $anomaly->status_label }}
                                </span>
                                @if ($anomaly->owner_notes)
                                    <div class="text-xs text-gray-400 mt-0.5 max-w-[120px] truncate" title="{{ $anomaly->owner_notes }}">
                                        {{ $anomaly->owner_notes }}
                                    </div>
                                @endif
                            </td>

                            {{-- Tanggal --}}
                            <td class="px-4 py-3 text-center text-xs text-gray-400">
                                {{ $anomaly->created_at->translatedFormat('d F Y H:i') }}
                            </td>

                            {{-- Aksi --}}
                            <td class="px-4 py-3 text-center">
                                @if ($anomaly->status === 'pending')
                                    <div class="flex items-center justify-center gap-1">
                                        {{-- Lihat Transaksi --}}
                                        @if ($anomaly->transaction_id)
                                            <a href="{{ route('transactions.show', $anomaly->transaction_id) }}"
                                               class="p-1.5 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 text-blue-600 dark:text-blue-400 transition"
                                               title="Lihat Transaksi">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </a>
                                        @endif

                                        {{-- Approve --}}
                                        <button onclick="reviewAnomaly({{ $anomaly->id }}, 'approved')"
                                                class="p-1.5 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 text-green-600 dark:text-green-400 transition"
                                                title="Setujui">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </button>

                                        {{-- Reject --}}
                                        <button onclick="reviewAnomaly({{ $anomaly->id }}, 'rejected')"
                                                class="p-1.5 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 text-red-500 dark:text-red-400 transition"
                                                title="Tolak">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">
                                        {{ $anomaly->reviewed_at?->translatedFormat('d F H:i') ?? '-' }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">
                                <div class="flex flex-col items-center gap-2">
                                    <span class="text-4xl">✅</span>
                                    <p class="text-sm font-medium">Tidak ada anomali harga.</p>
                                    <p class="text-xs">Semua harga pengajuan dalam batas normal.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($anomalies->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-800">
                {{ $anomalies->links() }}
            </div>
        @endif
    </div>
</div>

{{-- ─── Review Notes Modal ──────────────────────────────────────── --}}
<div id="reviewModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/50">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-md">
        <div class="p-6 border-b border-gray-100 dark:border-gray-800">
            <h2 class="text-lg font-bold" id="reviewModalTitle">Review Anomali</h2>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Catatan (opsional)</label>
                <textarea id="reviewNotes" rows="3" placeholder="Tambahkan catatan..."
                          class="w-full border rounded-xl px-4 py-2 text-sm bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none"></textarea>
            </div>
            <div id="reviewError" class="hidden text-red-600 text-sm"></div>
        </div>
        <div class="px-6 pb-6 flex justify-end gap-3">
            <button onclick="closeReviewModal()"
                    class="px-4 py-2 rounded-xl text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition">Batal</button>
            <button id="reviewConfirmBtn" onclick="confirmReview()"
                    class="px-5 py-2 rounded-xl text-sm font-medium text-white transition">Konfirmasi</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let currentReviewId   = null;
let currentReviewAction = null;
const csrfToken = '{{ csrf_token() }}';

// ─── Bulk Actions Logic ───────────────────────────────
const selectAll      = document.getElementById('selectAll');
const checkboxes     = document.querySelectorAll('.anomaly-checkbox');
const bulkActions    = document.getElementById('bulkActions');
const selectedCount  = document.getElementById('selectedCount');

function updateBulkUI() {
    const checked = document.querySelectorAll('.anomaly-checkbox:checked');
    if (checked.length > 0) {
        bulkActions.classList.remove('hidden');
        selectedCount.textContent = checked.length;
    } else {
        bulkActions.classList.add('hidden');
    }
}

if (selectAll) {
    selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateBulkUI();
    });
}

checkboxes.forEach(cb => {
    cb.addEventListener('change', updateBulkUI);
});

async function bulkReview(action) {
    const checked = document.querySelectorAll('.anomaly-checkbox:checked');
    const ids = Array.from(checked).map(cb => cb.value);
    
    if (ids.length === 0) return;

    if (!confirm(`Apakah Anda yakin ingin ${action === 'approved' ? 'menyetujui' : 'menolak'} ${ids.length} anomali terpilih?`)) {
        return;
    }

    // Tampilkan loading di button
    const btn = event.currentTarget;
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="w-4 h-4 animate-spin border-2 border-white border-t-transparent rounded-full mr-2"></i> Memproses...';

    try {
        const res = await fetch('{{ route("price-index.anomalies.bulk-review") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ ids, action, notes: 'Bulk review by owner' }),
        });
        const json = await res.json();
        
        if (json.success) {
            window.location.reload(); // Reload untuk update status massal
        } else {
            alert(json.error || 'Terjadi kesalahan.');
        }
    } catch (e) {
        alert('Kesalahan koneksi.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalContent;
    }
}

// ─── Single Review Logic ─────────────────────────────
function reviewAnomaly(id, action) {
    currentReviewId     = id;
    currentReviewAction = action;

    const isApprove = action === 'approved';
    document.getElementById('reviewModalTitle').textContent = isApprove ? '✅ Setujui Anomali' : '❌ Tolak Anomali';
    const btn = document.getElementById('reviewConfirmBtn');
    btn.textContent = isApprove ? 'Setujui' : 'Tolak';
    btn.className   = `px-5 py-2 rounded-xl text-sm font-medium text-white transition ${isApprove ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'}`;
    document.getElementById('reviewNotes').value = '';
    document.getElementById('reviewError').classList.add('hidden');
    document.getElementById('reviewModal').classList.remove('hidden');
    document.getElementById('reviewModal').classList.add('flex');
}

function closeReviewModal() {
    document.getElementById('reviewModal').classList.add('hidden');
    document.getElementById('reviewModal').classList.remove('flex');
    currentReviewId = null;
    currentReviewAction = null;
}

async function confirmReview() {
    if (!currentReviewId) return;
    const btn = document.getElementById('reviewConfirmBtn');
    btn.disabled = true;
    btn.textContent = 'Memproses...';

    const notes = document.getElementById('reviewNotes').value;

    try {
        const res = await fetch(`/price-index/anomalies/${currentReviewId}/review`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ action: currentReviewAction, notes }),
        });
        const json = await res.json();
        if (res.ok && json.success) {
            closeReviewModal();
            // Update badge
            const badge = document.getElementById(`status-badge-${currentReviewId}`);
            if (badge) {
                const isApproved = currentReviewAction === 'approved';
                badge.textContent = isApproved ? 'Disetujui' : 'Ditolak';
                badge.className = `inline-block ${isApproved ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'} px-2 py-0.5 rounded-full text-xs font-medium`;
            }
            // Hide action buttons
            const row = document.getElementById(`anomaly-row-${currentReviewId}`);
            if (row) {
                const actionCell = row.querySelector('td:last-child');
                if (actionCell) actionCell.innerHTML = '<span class="text-xs text-gray-400">Selesai</span>';
            }
        } else {
            document.getElementById('reviewError').textContent = json.error || 'Gagal menyimpan.';
            document.getElementById('reviewError').classList.remove('hidden');
        }
    } catch (e) {
        document.getElementById('reviewError').textContent = 'Terjadi kesalahan koneksi.';
        document.getElementById('reviewError').classList.remove('hidden');
    }
    btn.disabled = false;
    btn.textContent = currentReviewAction === 'approved' ? 'Setujui' : 'Tolak';
}

document.getElementById('reviewModal').addEventListener('click', function(e) {
    if (e.target === this) closeReviewModal();
});
</script>
@endpush
