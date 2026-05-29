@extends('layouts.app')

@section('page-title', 'Prive')

@push('styles')
<style>
    /* ── Prive page — smooth fluid responsive ────────────────────
       Breakpoints (selaras Tailwind):
       - Mobile low:    ≤ 375px  (xs)
       - Mobile medium: 376–480px
       - Mobile high:   481–640px (sm)
       - Tablet:        641–1023px (md, lg)
       - Laptop:        1024–1279px (lg)
       - Desktop:       ≥ 1280px (xl) */

    .prive-page,
    .prive-page .prive-card,
    .prive-page .prive-header-row,
    .prive-page .prive-filter-row {
        transition:
            padding 0.25s cubic-bezier(0.16, 1, 0.3, 1),
            gap 0.25s cubic-bezier(0.16, 1, 0.3, 1),
            border-radius 0.25s cubic-bezier(0.16, 1, 0.3, 1),
            max-width 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    /* Modal animation polish + responsive sizing */
    #prive-detail-modal,
    #prive-reject-modal {
        will-change: opacity;
    }
    #prive-detail-modal-box,
    #prive-reject-form {
        transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1),
                    opacity 0.2s ease,
                    border-radius 0.25s ease,
                    max-width 0.25s ease;
        will-change: transform;
    }

    /* Modal body height: use dvh for accurate mobile keyboard handling */
    #prive-detail-modal-box .prive-detail-body {
        max-height: 70dvh;
    }
    @media (max-width: 640px) {
        #prive-detail-modal-box .prive-detail-body {
            max-height: 65dvh;
        }
    }
    @media (min-width: 1024px) {
        #prive-detail-modal-box .prive-detail-body {
            max-height: 72dvh;
        }
    }

    /* Mobile low (≤ 375px): tightly padded modal */
    @media (max-width: 375px) {
        #prive-detail-modal,
        #prive-reject-modal {
            padding: 0.5rem !important;
        }
        #prive-detail-modal-box,
        #prive-reject-form {
            border-radius: 1rem !important;
        }
    }

    /* Mobile medium (376–480): keep modal close to edges */
    @media (min-width: 376px) and (max-width: 480px) {
        #prive-detail-modal,
        #prive-reject-modal {
            padding: 0.75rem !important;
        }
    }

    /* Smooth horizontal scroll on table */
    .prive-table-wrap {
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scroll-behavior: smooth;
    }

    /* Reduce motion preference */
    @media (prefers-reduced-motion: reduce) {
        .prive-page,
        .prive-page .prive-card,
        .prive-page .prive-header-row,
        .prive-page .prive-filter-row,
        #prive-detail-modal-box,
        #prive-reject-form {
            transition: none !important;
        }
    }
</style>
@endpush

@section('content')
@php
    $statusMeta = [
        'pending'  => ['label' => 'Menunggu Approval', 'class' => 'bg-amber-50 text-amber-700 border-amber-200',   'icon' => 'clock-3'],
        'approved' => ['label' => 'Disetujui',          'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'check-circle-2'],
        'rejected' => ['label' => 'Ditolak',            'class' => 'bg-rose-50 text-rose-700 border-rose-200',     'icon' => 'x-circle'],
    ];
@endphp

<div class="prive-page p-3 sm:p-4 md:p-5 lg:p-6 xl:p-8 space-y-4 md:space-y-5 lg:space-y-6">

    {{-- ── Page Header ── --}}
    <div class="prive-header-row flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 md:gap-4">
        <div class="flex items-center gap-3 sm:gap-4 min-w-0">
            <div class="w-11 h-11 md:w-12 md:h-12 rounded-2xl flex items-center justify-center shadow-lg bg-slate-900 shrink-0">
                <i data-lucide="user-minus" class="w-5 h-5 md:w-6 md:h-6 text-white"></i>
            </div>
            <div class="min-w-0">
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 truncate">Prive</h1>
                <p class="text-[11px] sm:text-sm text-slate-500 mt-0.5">Pengajuan pengambilan dana dengan approval Owner</p>
            </div>
        </div>
        <a href="{{ route('pengeluaran-lain.prive.create') }}"
            class="inline-flex items-center justify-center gap-2 px-4 sm:px-5 py-2.5 sm:py-3 rounded-xl bg-linear-to-r from-sky-600 to-sky-500 text-white font-bold text-xs sm:text-sm transition-all shadow-lg whitespace-nowrap">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Tambah Prive
        </a>
    </div>

    {{-- ── Main Card ── --}}
    <div class="prive-card bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

        {{-- Card Header + Filters --}}
        <div class="prive-filter-row px-4 sm:px-5 py-3 sm:py-4 border-b border-slate-100 bg-slate-50/60 space-y-3">
            <div>
                <h2 class="font-black text-slate-900 text-sm sm:text-base">Daftar Pengajuan</h2>
                <p class="text-[11px] sm:text-xs font-medium text-slate-500 mt-0.5">Klik <strong>Detail</strong> pada kolom Aksi untuk melihat alur dana, bukti, catatan, dan audit.</p>
            </div>

            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                {{-- Status filter pills --}}
                <div class="flex items-center gap-2 p-1 bg-slate-100 rounded-xl w-full md:w-fit max-w-full overflow-x-auto">
                    @foreach([
                        ''         => ['label' => 'Semua',          'active' => 'bg-white text-slate-900 shadow-sm'],
                        'pending'  => ['label' => 'Menunggu',       'active' => 'bg-amber-500 text-white shadow-sm'],
                        'approved' => ['label' => 'Disetujui',      'active' => 'bg-emerald-500 text-white shadow-sm'],
                        'rejected' => ['label' => 'Ditolak',        'active' => 'bg-rose-500 text-white shadow-sm'],
                    ] as $val => $pill)
                        @php $isActive = request('record_status', '') === $val; @endphp
                        <a href="{{ request()->fullUrlWithQuery(['record_status' => $val ?: null]) }}"
                            class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all whitespace-nowrap
                                   {{ $isActive ? $pill['active'] : 'text-slate-500 hover:text-slate-700' }}">
                            {{ $pill['label'] }}
                        </a>
                    @endforeach
                </div>

                <form action="{{ route('pengeluaran-lain.prive.index') }}" method="GET"
                    class="flex flex-wrap items-center gap-2 md:justify-end">
                    <input type="hidden" name="record_status" value="{{ request('record_status') }}">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Cari invoice / penerima..."
                        class="flex-1 min-w-0 sm:flex-none px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 outline-none w-full sm:w-44 md:w-56">

                    <button type="submit"
                        class="w-9 h-9 rounded-lg bg-slate-900 text-white flex items-center justify-center hover:bg-purple-700 transition-colors"
                        title="Cari">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </button>

                    <select name="branch_id" onchange="this.form.submit()"
                        class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 outline-none">
                        <option value="">Semua Cabang</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>

                    @if(request('search') || request('branch_id') || request('record_status'))
                        <a href="{{ route('pengeluaran-lain.prive.index') }}"
                            class="w-9 h-9 rounded-lg bg-white border border-slate-200 text-slate-400 hover:text-rose-500 flex items-center justify-center transition-colors"
                            title="Reset filter">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </a>
                    @endif
                </form>
            </div>
        </div>

        {{-- Empty State --}}
        @if($items->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <div class="w-16 h-16 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-300 mb-4">
                    <i data-lucide="user-minus" class="w-8 h-8"></i>
                </div>
                <p class="text-slate-500 font-bold">Belum ada data Prive</p>
                <p class="text-slate-400 text-xs mt-1">Klik "Tambah Prive" untuk membuat pengajuan baru</p>
            </div>

        @else
            <div class="overflow-x-auto prive-table-wrap">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-xs">
                            <th class="px-5 py-3.5 text-left font-black text-slate-500 uppercase tracking-wider">Invoice</th>
                            <th class="px-5 py-3.5 text-left font-black text-slate-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-5 py-3.5 text-left font-black text-slate-500 uppercase tracking-wider">Penerima</th>
                            <th class="px-5 py-3.5 text-left font-black text-slate-500 uppercase tracking-wider">Dari Cabang</th>
                            <th class="px-5 py-3.5 text-right font-black text-slate-500 uppercase tracking-wider">Nominal</th>
                            <th class="px-5 py-3.5 text-left font-black text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3.5 text-center font-black text-slate-500 uppercase tracking-wider w-32">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($items as $item)
                        @php
                            $meta         = $statusMeta[$item->status] ?? $statusMeta['pending'];
                            $isOwner      = Auth::user()->role === 'owner';
                            $canOwnerReview = $isOwner && $item->status === 'pending';
                            $canChange    = $isOwner || ($item->status === 'pending' && $item->parent_id === null && $item->children->isEmpty());
                            $method       = $item->payment_method ?? ($item->rekening_tujuan ? 'transfer' : 'cash');
                            $proofUrl     = $item->bukti_transfer ? route('pengeluaran-lain.record.image', $item->id) : null;
                            $rawRecipient = trim((string) ($item->recipient_name ?? ''));
                            $recipientLabel = strcasecmp($rawRecipient, 'Penerima Default') === 0 || $rawRecipient === ''
                                ? '-'
                                : $rawRecipient;

                            $detailPayload = [
                                'invoice'     => $item->invoice_number,
                                'date'        => $item->tanggal->translatedFormat('d F Y'),
                                'amount'      => $item->formatted_nominal,
                                'status'      => $meta['label'],
                                'statusClass' => $meta['class'],
                                'method'      => $method === 'cash' ? 'Cash' : 'Transfer',
                                'methodIcon'  => $method === 'cash' ? 'banknote' : 'send',
                                'fromBranch'  => $item->dariBranch->name ?? '-',
                                'recipient'   => $recipientLabel,
                                'destination' => $item->rekening_tujuan ?? '-',
                                'notes'       => $item->keterangan ?? '-',
                                'submittedBy' => $item->submitter->name ?? '-',
                                'submittedAt' => $item->created_at?->format('d/m/Y H:i') ?? '-',
                                'reviewedBy'  => $item->paidBy->name ?? '-',
                                'reviewedAt'  => $item->paid_at?->format('d/m/Y H:i') ?? '-',
                                'proofUrl'    => $proofUrl,
                                'id'          => $item->id,
                                'canReview'   => $canOwnerReview,
                                'canChange'   => $canChange,
                                'editUrl'     => route('pengeluaran-lain.record.edit', $item->id),
                            ];
                        @endphp

                        <tr id="record-row-{{ $item->id }}" class="hover:bg-slate-50/80 transition-colors group">

                            {{-- Invoice --}}
                            <td class="px-5 py-4 align-middle">
                                <span class="font-mono text-xs font-black text-slate-700 bg-slate-100 px-2.5 py-1 rounded-lg inline-flex whitespace-nowrap">
                                    {{ $item->invoice_number }}
                                </span>
                            </td>

                            {{-- Tanggal --}}
                            <td class="px-5 py-4 align-middle whitespace-nowrap">
                                <span class="font-semibold text-slate-700 text-xs">
                                    {{ $item->tanggal->translatedFormat('d F Y') }}
                                </span>
                            </td>

                            {{-- Penerima --}}
                            <td class="px-5 py-4 align-middle">
                                <div class="font-bold text-slate-800 text-xs leading-tight whitespace-nowrap">{{ $recipientLabel }}</div>
                            </td>

                            {{-- Dari Cabang --}}
                            <td class="px-5 py-4 align-middle">
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full border text-[10px] font-black
                                        {{ $method === 'cash' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-blue-50 text-blue-700 border-blue-200' }}">
                                        <i data-lucide="{{ $method === 'cash' ? 'banknote' : 'send' }}" class="w-2.5 h-2.5"></i>
                                        {{ $method === 'cash' ? 'Cash' : 'Transfer' }}
                                    </span>
                                    <span class="text-xs text-slate-700 font-bold whitespace-nowrap">{{ $item->dariBranch->name ?? '-' }}</span>
                                </div>
                            </td>

                            {{-- Nominal --}}
                            <td class="px-5 py-4 align-middle text-right whitespace-nowrap">
                                <span class="font-black text-slate-900 text-sm">{{ $item->formatted_nominal }}</span>
                            </td>

                            {{-- Status + Approve / Reject --}}
                            <td class="px-5 py-4 align-middle">
                                <div class="flex flex-col gap-2 items-start">
                                    {{-- Badge --}}
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-[11px] font-black whitespace-nowrap {{ $meta['class'] }}">
                                        <i data-lucide="{{ $meta['icon'] }}" class="w-3 h-3"></i>
                                        {{ $meta['label'] }}
                                    </span>
                                </div>
                            </td>

                            {{-- Aksi --}}
                            <td class="px-5 py-4 align-middle text-center">
                                <div class="flex items-center justify-center gap-1.5">

                                    {{-- Detail Prive --}}
                                    <button type="button"
                                        data-detail='@json($detailPayload)'
                                        onclick="openPriveDetailModal(this)"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg bg-violet-50 text-violet-600 hover:bg-violet-100 transition-all shadow-sm"
                                        title="Detail Prive">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    </button>

                                    @if($canChange)
                                    {{-- Edit --}}
                                    <a href="{{ route('pengeluaran-lain.record.edit', $item->id) }}"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition-all shadow-sm"
                                        title="Edit">
                                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                    </a>

                                    {{-- Hapus --}}
                                    <button type="button"
                                        onclick="confirmDeleteRecord({{ $item->id }}, @js($item->invoice_number))"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-all shadow-sm"
                                        title="Hapus">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($items->hasPages())
                <div class="px-5 py-4 border-t border-slate-100">
                    {{ $items->withQueryString()->links('components.pagination.premium') }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection

{{-- ══════════════════════════════════════════════════════════════
     MODALS — di-push ke @stack('modals') di layouts.app sehingga
     dirender di body-level (di luar <main> & <aside> sidebar).
     Ini mencegah modal "tertutup" sidebar pada view tablet karena
     tidak lagi terjebak dalam stacking context dari transform/flex
     parent.
══════════════════════════════════════════════════════════════ --}}
@push('modals')
{{-- ══════════════════════════════════════════════════════════════
     MODAL — Detail Prive
══════════════════════════════════════════════════════════════ --}}
<div id="prive-detail-modal"
    class="fixed inset-0 z-[1000] hidden bg-slate-950/60 backdrop-blur-sm p-3 sm:p-4">
    <div class="min-h-full flex items-center justify-center">
        <div id="prive-detail-modal-box"
            class="w-full max-w-md sm:max-w-xl md:max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden transform scale-95">

            {{-- Modal Header --}}
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-slate-100 bg-gradient-to-r from-violet-50 to-white flex items-start justify-between gap-3 sm:gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-violet-100 flex items-center justify-center text-violet-600 flex-shrink-0">
                        <i data-lucide="user-minus" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 id="detail-invoice" class="font-mono text-xs sm:text-sm font-black text-slate-800 truncate"></h3>
                            <span id="detail-status" class="inline-flex items-center px-2.5 py-0.5 rounded-full border text-[10px] sm:text-[11px] font-black"></span>
                        </div>
                        <p id="detail-date" class="text-[11px] sm:text-xs font-semibold text-slate-400 mt-0.5"></p>
                    </div>
                </div>
                <button type="button" onclick="closePriveDetailModal()"
                    class="w-8 h-8 flex-shrink-0 rounded-lg bg-white border border-slate-200 text-slate-400 hover:bg-slate-100 flex items-center justify-center transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="prive-detail-body p-4 sm:p-6 space-y-3 sm:space-y-4 overflow-y-auto">

                {{-- Nominal + Metode --}}
                <div class="flex items-center justify-between gap-3 sm:gap-4 p-3 sm:p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="min-w-0">
                        <p class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Nominal</p>
                        <p id="detail-amount" class="text-xl sm:text-2xl font-black text-slate-900 mt-0.5 truncate"></p>
                    </div>
                    <span id="detail-method" class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-full border text-[11px] sm:text-xs font-black flex-shrink-0"></span>
                </div>

                {{-- Info Grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3">
                    <div class="rounded-xl bg-slate-50 border border-slate-100 p-3">
                        <p class="text-[10px] font-black uppercase text-slate-400 tracking-wider mb-1">Dari Cabang</p>
                        <p id="detail-from-branch" class="text-xs sm:text-sm font-black text-slate-800"></p>
                    </div>
                    <div class="rounded-xl bg-slate-50 border border-slate-100 p-3">
                        <p class="text-[10px] font-black uppercase text-slate-400 tracking-wider mb-1">Nama Penerima</p>
                        <p id="detail-recipient" class="text-xs sm:text-sm font-black text-slate-800"></p>
                    </div>
                    <div id="detail-destination-wrap" class="rounded-xl bg-slate-50 border border-slate-100 p-3 sm:col-span-2">
                        <p class="text-[10px] font-black uppercase text-slate-400 tracking-wider mb-1">Tujuan Transfer</p>
                        <p id="detail-destination" class="text-xs sm:text-sm font-semibold text-slate-700 break-all"></p>
                    </div>
                </div>

                {{-- Keterangan --}}
                <div class="rounded-xl border border-slate-200 p-3 sm:p-4">
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-wider mb-2">Keterangan</p>
                    <p id="detail-notes" class="text-xs sm:text-sm font-medium text-slate-700 whitespace-pre-line leading-relaxed"></p>
                </div>

                {{-- Audit + Bukti (side-by-side on md+) --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5 sm:gap-3">

                    {{-- Audit Trail --}}
                    <div class="rounded-xl border border-slate-200 p-3 sm:p-4 space-y-3">
                        <p class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Audit Trail</p>
                        <div class="flex gap-3">
                            <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="user-plus" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0">
                                <p id="detail-submitted-by" class="text-[11px] sm:text-xs font-black text-slate-800 truncate"></p>
                                <p id="detail-submitted-at" class="text-[10px] sm:text-[11px] font-semibold text-slate-400 mt-0.5"></p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="shield-check" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0">
                                <p id="detail-reviewed-by" class="text-[11px] sm:text-xs font-black text-slate-800 truncate"></p>
                                <p id="detail-reviewed-at" class="text-[10px] sm:text-[11px] font-semibold text-slate-400 mt-0.5"></p>
                            </div>
                        </div>
                    </div>

                    {{-- Bukti Transaksi --}}
                    <div class="rounded-xl border border-slate-200 p-3 sm:p-4">
                        <p class="text-[10px] font-black uppercase text-slate-400 tracking-wider mb-3">Bukti Transaksi</p>
                        <a id="detail-proof-link" href="#" target="_blank"
                            class="hidden w-full px-4 py-3 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 font-black text-xs sm:text-sm hover:bg-blue-100 transition-all items-center justify-center gap-2">
                            <i data-lucide="image" class="w-4 h-4"></i>
                            Lihat Bukti
                        </a>
                        <div id="detail-proof-empty" class="rounded-xl bg-slate-50 border border-dashed border-slate-200 px-4 py-5 sm:py-6 text-center">
                            <i data-lucide="image-off" class="w-7 h-7 text-slate-300 mx-auto mb-2"></i>
                            <p class="text-[11px] sm:text-xs font-bold text-slate-400">Belum ada bukti</p>
                        </div>
                    </div>
                </div>
            </div>{{-- /body --}}

            {{-- Modal Actions --}}
            <div id="detail-actions" class="hidden px-4 sm:px-6 py-3 sm:py-4 border-t border-slate-100 bg-white">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-2">
                    <div id="detail-review-actions" class="hidden flex items-center gap-2">
                        <button type="button" id="detail-approve-btn"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black transition-all shadow-sm">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            Setujui
                        </button>
                        <button type="button" id="detail-reject-btn"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-black transition-all">
                            <i data-lucide="x" class="w-4 h-4"></i>
                            Tolak
                        </button>
                    </div>
                    <div id="detail-change-actions" class="hidden flex items-center gap-2">
                        <a id="detail-edit-link" href="#"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-xs font-black transition-all">
                            <i data-lucide="pencil" class="w-4 h-4"></i>
                            Edit
                        </a>
                        <button type="button" id="detail-delete-btn"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-black transition-all shadow-sm">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                            Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     MODAL — Tolak Prive
══════════════════════════════════════════════════════════════ --}}
<div id="prive-reject-modal"
    class="fixed inset-0 z-[1010] hidden bg-slate-950/60 backdrop-blur-sm p-3 sm:p-4">
    <div class="min-h-full flex items-center justify-center">
        <form id="prive-reject-form"
            class="w-full max-w-md sm:max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden transform scale-95">
            <div class="px-4 sm:px-6 py-4 sm:py-5 border-b border-slate-100 flex items-start justify-between gap-3 sm:gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="x-circle" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-black text-slate-900">Tolak Prive</h3>
                        <p id="reject-invoice-text" class="text-[11px] sm:text-xs font-semibold text-slate-500 mt-0.5 truncate"></p>
                    </div>
                </div>
                <button type="button" onclick="closePriveRejectModal()"
                    class="w-8 h-8 flex-shrink-0 rounded-lg bg-white border border-slate-200 text-slate-400 hover:bg-slate-100 flex items-center justify-center transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="p-4 sm:p-6 space-y-3">
                <label for="prive-reject-reason" class="block text-[10px] font-black uppercase text-slate-400 tracking-wider">
                    Alasan Penolakan <span class="text-rose-500">*</span>
                </label>
                <textarea id="prive-reject-reason" rows="4" maxlength="500"
                    placeholder="Tuliskan alasan penolakan yang jelas..."
                    class="w-full px-3 sm:px-4 py-2.5 sm:py-3 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm font-semibold text-slate-800 placeholder:text-slate-300 resize-none outline-none focus:bg-white focus:border-rose-400 focus:ring-4 focus:ring-rose-500/10 transition-all"></textarea>
                <div class="flex items-center justify-between gap-3">
                    <p id="prive-reject-error" class="hidden text-[11px] sm:text-xs font-bold text-rose-600"></p>
                    <p id="prive-reject-count" class="ml-auto text-[10px] sm:text-[11px] font-bold text-slate-400">0/500</p>
                </div>
            </div>

            <div class="px-4 sm:px-6 py-3 sm:py-4 border-t border-slate-100 bg-slate-50 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                <button type="button" onclick="closePriveRejectModal()"
                    class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 text-xs font-black hover:bg-slate-100 transition-colors">
                    Batal
                </button>
                <button type="submit" id="prive-reject-submit"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-black transition-all shadow-sm">
                    <i data-lucide="x" class="w-4 h-4"></i>
                    Tolak Prive
                </button>
            </div>
        </form>
    </div>
</div>
@endpush

@push('scripts')
<script>
/* ── Helpers ──────────────────────────────────────────── */
function sendPriveAction(url, formData = null) {
    return fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    }).then(async response => {
        const payload = await response.json();
        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Aksi gagal diproses.');
        }
        return payload;
    });
}

/* ── Detail Modal ─────────────────────────────────────── */
function openPriveDetailModal(button) {
    const data         = JSON.parse(button.dataset.detail || '{}');
    const modal        = document.getElementById('prive-detail-modal');
    const box          = document.getElementById('prive-detail-modal-box');
    const statusEl     = document.getElementById('detail-status');
    const methodEl     = document.getElementById('detail-method');
    const proofLink    = document.getElementById('detail-proof-link');
    const proofEmpty   = document.getElementById('detail-proof-empty');
    const destWrap     = document.getElementById('detail-destination-wrap');
    const actionsWrap  = document.getElementById('detail-actions');
    const reviewActions = document.getElementById('detail-review-actions');
    const changeActions = document.getElementById('detail-change-actions');
    const editLink     = document.getElementById('detail-edit-link');
    const approveBtn   = document.getElementById('detail-approve-btn');
    const rejectBtn    = document.getElementById('detail-reject-btn');
    const deleteBtn    = document.getElementById('detail-delete-btn');

    document.getElementById('detail-invoice').textContent       = data.invoice     || '-';
    document.getElementById('detail-date').textContent          = data.date        || '-';
    document.getElementById('detail-amount').textContent        = data.amount      || '-';
    document.getElementById('detail-from-branch').textContent   = data.fromBranch  || '-';
    document.getElementById('detail-recipient').textContent     = data.recipient   || '-';
    document.getElementById('detail-destination').textContent   = data.destination || '-';
    document.getElementById('detail-notes').textContent         = data.notes       || '-';
    document.getElementById('detail-submitted-by').textContent  = `Diinput: ${data.submittedBy || '-'}`;
    document.getElementById('detail-submitted-at').textContent  = data.submittedAt || '-';
    document.getElementById('detail-reviewed-by').textContent   = data.reviewedBy && data.reviewedBy !== '-'
        ? `Direview: ${data.reviewedBy}`
        : 'Belum direview';
    document.getElementById('detail-reviewed-at').textContent   = data.reviewedAt && data.reviewedAt !== '-'
        ? data.reviewedAt
        : 'Menunggu keputusan Owner';

    statusEl.className   = `inline-flex items-center px-2.5 py-0.5 rounded-full border text-[10px] sm:text-[11px] font-black ${data.statusClass || ''}`;
    statusEl.textContent = data.status || '-';

    const isCash = data.method === 'Cash';
    methodEl.className   = `inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-full border text-[11px] sm:text-xs font-black flex-shrink-0 ${isCash ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-blue-50 text-blue-700 border-blue-200'}`;
    methodEl.innerHTML   = `<i data-lucide="${isCash ? 'banknote' : 'send'}" class="w-3.5 h-3.5"></i>${data.method || '-'}`;

    destWrap.classList.toggle('hidden', isCash);

    if (data.proofUrl) {
        proofLink.onclick = (e) => {
            e.preventDefault();
            openImageViewer(data.proofUrl, 'Bukti Transaksi Prive');
        };
        proofLink.removeAttribute('href');
        proofLink.classList.remove('hidden');
        proofLink.classList.add('flex');
        proofEmpty.classList.add('hidden');
    } else {
        proofLink.onclick = null;
        proofLink.classList.add('hidden');
        proofLink.classList.remove('flex');
        proofEmpty.classList.remove('hidden');
    }

    const canReview = Boolean(data.canReview);
    const canChange = Boolean(data.canChange);

    actionsWrap.classList.toggle('hidden', !canReview && !canChange);
    reviewActions.classList.toggle('hidden', !canReview);
    changeActions.classList.toggle('hidden', !canChange);

    editLink.href = data.editUrl || '#';
    approveBtn.onclick = () => {
        closePriveDetailModal();
        setTimeout(() => confirmApprovePrive(data.id, data.invoice), 170);
    };
    rejectBtn.onclick = () => {
        closePriveDetailModal();
        setTimeout(() => confirmRejectPrive(data.id, data.invoice), 170);
    };
    deleteBtn.onclick = () => {
        closePriveDetailModal();
        setTimeout(() => confirmDeleteRecord(data.id, data.invoice), 170);
    };

    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(() => box.classList.remove('scale-95'));
    if (window.lucide) lucide.createIcons({ root: modal });
}

function closePriveDetailModal() {
    const modal = document.getElementById('prive-detail-modal');
    const box   = document.getElementById('prive-detail-modal-box');
    box.classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }, 200);
}

document.getElementById('prive-detail-modal').addEventListener('click', function (e) {
    if (e.target === this) closePriveDetailModal();
});

/* ── Approve / Reject ─────────────────────────────────── */
function confirmApprovePrive(id, invoice) {
    openConfirmModal('globalConfirmModal', {
        title:       'Setujui Prive?',
        message:     `Prive <strong class="text-slate-800">${invoice}</strong> akan disetujui oleh Owner.`,
        submitText:  'Setujui',
        submitColor: 'bg-emerald-600 hover:bg-emerald-700',
        icon:        'check-circle-2',
        iconColor:   'text-emerald-600',
        iconBg:      'bg-emerald-50',
        onConfirm: async () => {
            const fd = new FormData();
            fd.append('_method', 'PATCH');
            const result = await sendPriveAction(`/pengeluaran-lain/record/${id}/approve-prive`, fd);
            showToast(result.message, 'success');
            setTimeout(() => location.reload(), 700);
        }
    });
}

function confirmRejectPrive(id, invoice) {
    openPriveRejectModal(id, invoice);
}

function openPriveRejectModal(id, invoice) {
    const modal = document.getElementById('prive-reject-modal');
    const form = document.getElementById('prive-reject-form');
    const textarea = document.getElementById('prive-reject-reason');
    const error = document.getElementById('prive-reject-error');
    const counter = document.getElementById('prive-reject-count');
    const submitBtn = document.getElementById('prive-reject-submit');

    form.dataset.id = id;
    form.dataset.invoice = invoice;
    document.getElementById('reject-invoice-text').textContent = `Invoice ${invoice} akan ditolak.`;
    textarea.value = '';
    error.textContent = '';
    error.classList.add('hidden');
    counter.textContent = '0/500';
    submitBtn.disabled = false;
    submitBtn.classList.remove('opacity-70', 'cursor-not-allowed');

    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(() => {
        form.classList.remove('scale-95');
        textarea.focus();
    });
    if (window.lucide) lucide.createIcons({ root: modal });
}

function closePriveRejectModal() {
    const modal = document.getElementById('prive-reject-modal');
    const form = document.getElementById('prive-reject-form');

    form.classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }, 200);
}

document.getElementById('prive-reject-modal').addEventListener('click', function (e) {
    if (e.target === this) closePriveRejectModal();
});

document.getElementById('prive-reject-reason').addEventListener('input', function () {
    document.getElementById('prive-reject-count').textContent = `${this.value.length}/500`;
    if (this.value.trim() !== '') {
        document.getElementById('prive-reject-error').classList.add('hidden');
    }
});

document.getElementById('prive-reject-form').addEventListener('submit', async function (e) {
    e.preventDefault();

    const id = this.dataset.id;
    const invoice = this.dataset.invoice;
    const textarea = document.getElementById('prive-reject-reason');
    const error = document.getElementById('prive-reject-error');
    const submitBtn = document.getElementById('prive-reject-submit');
    const reason = textarea.value.trim();

    if (!reason) {
        error.textContent = 'Alasan penolakan wajib diisi.';
        error.classList.remove('hidden');
        textarea.focus();
        return;
    }

    submitBtn.disabled = true;
    submitBtn.classList.add('opacity-70', 'cursor-not-allowed');

    try {
        const fd = new FormData();
        fd.append('_method', 'PATCH');
        fd.append('rejection_reason', reason);

        const result = await sendPriveAction(`/pengeluaran-lain/record/${id}/reject-prive`, fd);
        closePriveRejectModal();
        showToast(result.message || `Prive ${invoice} berhasil ditolak.`, 'success');
        setTimeout(() => location.reload(), 700);
    } catch (err) {
        error.textContent = err.message || 'Gagal menolak Prive.';
        error.classList.remove('hidden');
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-70', 'cursor-not-allowed');
    }
});

/* ── Delete ───────────────────────────────────────────── */
function confirmDeleteRecord(id, invoice) {
    openConfirmModal('globalConfirmModal', {
        title:       'Hapus Prive?',
        message:     `Anda yakin ingin menghapus Prive <strong class="text-slate-800">${invoice}</strong>?`,
        submitText:  'Hapus',
        submitColor: 'bg-rose-600 hover:bg-rose-700',
        icon:        'trash-2',
        iconColor:   'text-rose-600',
        iconBg:      'bg-rose-50',
        onConfirm: async () => {
            const response = await fetch(`/pengeluaran-lain/record/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Gagal menghapus Prive.');
            }
            showToast(result.message, 'success');
            const row = document.getElementById(`record-row-${id}`);
            if (row) {
                row.style.transition = 'all 0.25s ease';
                row.style.opacity    = '0';
                row.style.transform  = 'translateX(-10px)';
                setTimeout(() => row.remove(), 250);
            }
        }
    });
}

/* ── Close on ESC for better UX across devices ────────── */
document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    const detail = document.getElementById('prive-detail-modal');
    const reject = document.getElementById('prive-reject-modal');
    if (reject && !reject.classList.contains('hidden')) {
        closePriveRejectModal();
    } else if (detail && !detail.classList.contains('hidden')) {
        closePriveDetailModal();
    }
});
</script>
@endpush
@push('modals')
@include('transactions.partials.forms.shared.image-viewer-modal')
@endpush
