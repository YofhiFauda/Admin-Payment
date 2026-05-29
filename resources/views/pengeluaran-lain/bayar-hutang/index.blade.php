@extends('layouts.app')

@section('page-title', $config['label'] ?? 'Bayar Hutang')

@push('styles')
<style>
    /* Ensure modals are above sidebar on all devices */
    #detail-debt-modal,
    #view-proof-modal,
    #branch-debt-modal {
        z-index: 9999 !important;
    }
    
    /* Prevent body scroll when modal is open */
    body.modal-open {
        overflow: hidden;
    }
    
    /* Smooth transitions for responsive layouts */
    @media (max-width: 1024px) {
        #detail-debt-modal-box,
        #view-proof-modal-box,
        #branch-debt-modal-box {
            max-height: 90vh !important;
        }
    }
    
    @media (max-width: 640px) {
        #detail-debt-modal-box,
        #view-proof-modal-box,
        #branch-debt-modal-box {
            max-height: 95vh !important;
            margin: 0.5rem;
        }
    }
</style>
@endpush

@section('content')
@php
    $isPiutang = $jenis === 'piutang_usaha';
    $pageRoute = $isPiutang ? 'pengeluaran-lain.piutang-usaha' : 'pengeluaran-lain.bayar-hutang';
    $pageTitle = $isPiutang ? 'Piutang Usaha' : 'Bayar Hutang';
    $pageDescription = $isPiutang ? 'Daftar semua transaksi Pencairan Piutang' : 'Daftar semua transaksi Pembayaran Hutang';
    $historyTitle = $isPiutang ? 'Riwayat Penerimaan Piutang' : 'Riwayat Pembayaran Hutang';
    $mainTypeLabel = $isPiutang ? 'Piutang Utama' : 'Hutang Utama';
    $actionLabel = $isPiutang ? 'Terima' : 'Bayar';
    $detailLabel = $isPiutang ? 'Detail Piutang' : 'Detail Hutang';
    $proofLabel = $isPiutang ? 'Bukti Penerimaan' : 'Bukti Pembayaran';
    $flowLabel = $isPiutang ? 'Alur Piutang' : 'Alur Hutang';
    $fromFlowLabel = $isPiutang ? 'Dari (Pembayar)' : 'Dari (Berutang)';
    $toFlowLabel = $isPiutang ? 'Ke (Penerima)' : 'Ke (Piutang)';
    $totalSourceLabel = $isPiutang ? 'Total Piutang Asal' : 'Total Hutang Asal';
    $totalPaidLabel = $isPiutang ? 'Total Diterima' : 'Total Dibayar';
    $remainingLabel = $isPiutang ? 'Sisa Piutang' : 'Sisa Hutang';
    $branchSectionTitle = $isPiutang ? 'Piutang Antar Cabang' : 'Hutang Antar Cabang';
    $branchEmptyText = $isPiutang ? 'Data piutang tidak ditemukan' : 'Data hutang tidak ditemukan';
    $routeIndex = route($pageRoute . '.index');
    $routeCreate = route($pageRoute . '.create');
@endphp
<div class="p-4 md:p-6 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-lg {{ $isPiutang ? 'bg-linear-to-r from-sky-600 to-sky-500' : 'bg-gradient-to-br from-red-500 to-rose-600' }}">
                <i data-lucide="{{ $isPiutang ? 'trending-up' : 'credit-card' }}" class="w-6 h-6 text-white"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-slate-900">{{ $pageTitle }}</h1>
                <p class="text-sm text-slate-500 mt-0.5">{{ $pageDescription }}</p>
            </div>
        </div>
        <a href="{{ $routeCreate }}"
            class="flex items-center gap-2 px-5 py-3 rounded-xl bg-linear-to-r from-sky-600 to-sky-500 text-white font-bold text-sm hover:bg-slate-800 transition-all shadow-lg">
            <i data-lucide="plus" class="w-4 h-4"></i> Tambah {{ $pageTitle }}
        </a>
    </div>

    {{-- Table Utama --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-col gap-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <h3 class="font-bold text-slate-800">{{ $historyTitle }}</h3>

                {{-- Filter Table Utama --}}
                <form action="{{ $routeIndex }}" method="GET" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="record_status" value="{{ request('record_status') }}">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No. INV..."
                        class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all outline-none w-32 md:w-40">

                    <select name="branch_id" onchange="this.form.submit()"
                        class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all outline-none">
                        <option value="">Semua Cabang</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>

                    @if(request('search') || request('branch_id') || request('record_status'))
                        <a href="{{ $routeIndex }}" class="text-slate-400 hover:text-red-500 transition-colors" title="Reset semua filter">
                            <i data-lucide="x-circle" class="w-4 h-4"></i>
                        </a>
                    @endif
                    <button type="submit" class="hidden">Filter</button>
                </form>
            </div>

            {{-- Status Filter Pills --}}
            <div class="flex items-center gap-2 p-1 bg-slate-100 rounded-xl w-fit">
                <a href="{{ request()->fullUrlWithQuery(['record_status' => null]) }}"
                    class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all {{ !request('record_status') ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                    Semua
                </a>
                <a href="{{ request()->fullUrlWithQuery(['record_status' => 'belum_lunas']) }}"
                    class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all {{ request('record_status') === 'belum_lunas' ? 'bg-amber-500 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                    Belum Lunas
                </a>
                <a href="{{ request()->fullUrlWithQuery(['record_status' => 'sudah_lunas']) }}"
                    class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all {{ request('record_status') === 'sudah_lunas' ? 'bg-emerald-500 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                    Sudah Lunas
                </a>
            </div>
        </div>
        @if($items->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <div class="w-16 h-16 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-300 mb-4">
                    <i data-lucide="credit-card" class="w-8 h-8"></i>
                </div>
                <p class="text-slate-500 font-bold">Belum ada data</p>
                <p class="text-slate-400 text-xs mt-1">Klik "Tambah {{ $pageTitle }}" untuk memulai</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm whitespace-nowrap">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs">
                            <th class="px-5 py-3.5 text-left font-black text-slate-500 uppercase tracking-wider">No. Invoice</th>
                            <th class="px-5 py-3.5 text-left font-black text-slate-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-5 py-3.5 text-left font-black text-slate-500 uppercase tracking-wider">Keterangan</th>
                            <th class="px-5 py-3.5 text-left font-black text-slate-500 uppercase tracking-wider">Jenis</th>
                            <th class="px-5 py-3.5 text-left font-black text-slate-500 uppercase tracking-wider">Nominal</th>
                            <th class="px-5 py-3.5 text-left font-black text-slate-500 uppercase tracking-wider">{{ $isPiutang ? 'Diterima' : 'Terbayar' }}</th>
                            <th class="px-5 py-3.5 text-left font-black text-slate-500 uppercase tracking-wider">Sisa</th>
                            <th class="px-5 py-3.5 text-left font-black text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3.5 text-center font-black text-slate-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach($items as $item)
                        @php
                            $paymentSummary = $item->payment_summary;
                            $children = $item->children->sortBy('created_at')->values();
                            $installmentRows = collect();
                            if ($item->status === 'approved' && $children->isNotEmpty()) {
                                $installmentRows->push($item);
                            }
                            $installmentRows = $installmentRows
                                ->merge($children->where('status', 'approved')->values())
                                ->values();
                            $hasChildren = $installmentRows->isNotEmpty();
                            $pendingRecord = $children->where('status', 'pending')->sortByDesc('created_at')->first();
                            if (!$pendingRecord && $item->status === 'pending') {
                                $pendingRecord = $item;
                            }
                            $isOutstanding = $paymentSummary['remaining'] > 0;
                            $statusColor = $isOutstanding ? 'amber' : 'emerald';
                            $statusLabel = $isOutstanding ? 'Belum Lunas' : 'Sudah Lunas';
                            $itemProofPayload = [
                                'url' => $item->bukti_transfer ? asset('storage/' . $item->bukti_transfer) : '',
                                'title' => ($item->bukti_transfer ? $proofLabel . ' ' : ($isPiutang ? 'Detail Penerimaan ' : 'Detail Pembayaran ')) . $item->invoice_number,
                                'data' => [
                                    'amount' => $item->formatted_nominal,
                                    'notes' => $item->keterangan ?? '-',
                                    'paid_by' => $item->paidBy->name ?? ($item->submitter->name ?? '-'),
                                    'paid_at' => $item->paid_at ? $item->paid_at->translatedFormat('d F Y H:i') : ($item->tanggal instanceof \Carbon\Carbon ? $item->tanggal->translatedFormat('d F Y') : $item->tanggal),
                                    'selected_account' => $item->bankAccount,
                                    'sender_account' => $item->senderBankAccount,
                                    'all_accounts' => $isPiutang ? ($item->dariBranch?->bankAccounts ?? []) : ($item->branch?->bankAccounts ?? []),
                                    'sender_branch' => $isPiutang ? ($item->branch->name ?? '-') : ($item->dariBranch->name ?? '-'),
                                    'receiver_branch' => $isPiutang ? ($item->dariBranch->name ?? '-') : ($item->branch->name ?? '-'),
                                    'method' => $item->payment_method === 'cash' ? 'Tunai (Cash)' : 'Transfer',
                                ],
                            ];
                            $canEditManualReceivable = $isPiutang && $item->status === 'pending' && !$hasChildren && $item->parent_id === null;
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors {{ $item->status === 'approved' ? 'opacity-90' : ($item->status === 'rejected' ? 'opacity-70' : '') }}" id="record-row-{{ $item->id }}">
                            <td class="px-5 py-4 align-top">
                                <div class="flex items-center gap-2">
                                    @if($hasChildren)
                                        <button type="button"
                                            onclick="toggleDebtChildren('record', {{ $item->id }})"
                                            class="w-6 h-6 rounded-md bg-slate-100 text-slate-600 flex items-center justify-center hover:bg-slate-200 transition-all"
                                            title="Buka/tutup cicilan">
                                            <i data-lucide="chevron-right" class="w-4 h-4 transition-transform" id="record-toggle-icon-{{ $item->id }}"></i>
                                        </button>
                                    @else
                                        <span class="w-6 h-6"></span>
                                    @endif
                                    <span class="font-mono font-bold text-slate-700 bg-slate-100 px-2 py-1 rounded-lg">{{ $item->invoice_number }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4 align-top font-bold text-slate-700">
                                {{ $item->tanggal instanceof \Carbon\Carbon ? $item->tanggal->translatedFormat('d F Y') : $item->tanggal }}
                            </td>
                            <td class="px-5 py-4 align-top">
                                <div class="space-y-1">
                                    <div>
                                        <span class="bg-{{ $statusColor }}-100 text-{{ $statusColor }}-700 font-bold px-2 py-0.5 mx-1 rounded-full border border-{{ $statusColor }}-200">
                                            {{ $isPiutang ? ($item->branch->name ?? '-') : ($item->dariBranch->name ?? '-') }}
                                        </span>
                                        <span class="text-slate-500 mx-1"> ke </span>
                                        <span class="font-bold text-slate-700">{{ $isPiutang ? ($item->dariBranch->name ?? '-') : ($item->branch->name ?? '-') }}</span>
                                    </div>
                                    {{-- @if($item->keterangan)
                                        <p class="text-slate-500 max-w-[250px] truncate" title="{{ $item->keterangan }}">{{ $item->keterangan }}</p>
                                    @endif --}}
                                    {{-- <p class="text-[10px] text-slate-400">Input oleh: <span class="font-semibold text-slate-500">{{ $item->submitter->name ?? '-' }}</span></p> --}}
                                </div>
                            </td>
                            <td class="px-5 py-4 align-top">
                                <span class="inline-flex px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200 text-[11px] font-bold">
                                    {{ $mainTypeLabel }}
                                </span>
                            </td>
                            <td class="px-5 py-4 align-top font-bold text-red-600">
                                Rp {{ number_format($paymentSummary['total'], 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-4 align-top font-bold text-emerald-600">
                                Rp {{ number_format($paymentSummary['paid'], 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-4 align-top font-bold text-slate-700">
                                Rp {{ number_format($paymentSummary['remaining'], 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-4 align-top">
                                <span class="bg-{{ $statusColor }}-100 text-{{ $statusColor }}-700 text-[12px] font-bold px-2 py-0.5 rounded-full border border-{{ $statusColor }}-200">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center align-top">
                                <div class="flex items-center justify-center gap-2">
                                    @if($item->status === 'approved' && !$hasChildren)
                                        <button type="button"
                                            data-proof='@json($itemProofPayload)'
                                            onclick="openPaymentProofFromButton(this)"
                                            class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-100 transition-all shadow-sm" title="{{ $isPiutang ? 'Detail Penerimaan' : 'Detail Pembayaran' }}">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </button>
                                    @endif

                                    @if($pendingRecord && !$isPiutang)
                                    <button type="button"
                                        onclick='openSettleModal({{ $pendingRecord->id }}, {{ json_encode($isPiutang ? $pendingRecord->dariBranch->bankAccounts : $pendingRecord->branch->bankAccounts) }}, "{{ $isPiutang ? $pendingRecord->dariBranch->name : $pendingRecord->branch->name }}", {{ json_encode($isPiutang ? $pendingRecord->branch->bankAccounts : $pendingRecord->dariBranch->bankAccounts) }}, "{{ $isPiutang ? $pendingRecord->branch->name : $pendingRecord->dariBranch->name }}", "/pengeluaran-lain/record/{{ $pendingRecord->id }}/settle", {{ $pendingRecord->nominal }})'
                                        class="px-3 py-1 bg-red-600 text-white rounded-lg text-xs font-bold hover:bg-red-700 transition-all shadow-sm" title="{{ $actionLabel }}">
                                        {{ $actionLabel }}
                                    </button>
                                    @endif
                                    @if($canEditManualReceivable)
                                    <a href="{{ route('pengeluaran-lain.record.edit', $item->id) }}"
                                        class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center hover:bg-amber-100 transition-all shadow-sm" title="Edit">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                    </a>
                                    @endif
                                    @if($item->status === 'pending' && !$hasChildren)
                                    <button type="button"
                                            onclick="confirmDeleteRecord({{ $item->id }}, '{{ $item->invoice_number }}')"
                                            class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center hover:bg-rose-100 transition-all shadow-sm" title="Hapus">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                    @endif

                                    {{-- Detail Hutang --}}
                                    @if($hasChildren || $isPiutang)
                                    <button type="button"
                                        onclick="openDetailModal({{ $item->id }}, 'record')"
                                        class="w-8 h-8 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center hover:bg-violet-100 transition-all shadow-sm" title="{{ $detailLabel }}">
                                        <i data-lucide="info" class="w-4 h-4"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @foreach($installmentRows as $childIndex => $child)
                        @php
                            $childStatusColor = $child->status === 'approved' ? 'emerald' : ($child->status === 'rejected' ? 'red' : 'amber');
                            $childStatusLabel = $child->status === 'approved' ? 'Lunas' : $child->status_label;
                            $childProofPayload = [
                                'url' => $child->bukti_transfer ? asset('storage/' . $child->bukti_transfer) : '',
                                'title' => $proofLabel . ' Cicilan CIC-' . str_pad($childIndex + 1, 5, '0', STR_PAD_LEFT),
                                'data' => [
                                    'amount' => $child->formatted_nominal,
                                    'notes' => $child->keterangan ?? '-',
                                    'paid_by' => $child->paidBy->name ?? ($child->submitter->name ?? '-'),
                                    'paid_at' => $child->paid_at ? $child->paid_at->translatedFormat('d F Y H:i') : ($child->tanggal instanceof \Carbon\Carbon ? $child->tanggal->translatedFormat('d F Y') : $child->tanggal),
                                    'selected_account' => $child->bankAccount,
                                    'sender_account' => $child->senderBankAccount,
                                    'all_accounts' => $isPiutang ? ($child->dariBranch?->bankAccounts ?? []) : ($child->branch?->bankAccounts ?? []),
                                    'sender_branch' => $isPiutang ? ($child->branch->name ?? '-') : ($child->dariBranch->name ?? '-'),
                                    'receiver_branch' => $isPiutang ? ($child->dariBranch->name ?? '-') : ($child->branch->name ?? '-'),
                                    'method' => $child->payment_method === 'cash' ? 'Tunai (Cash)' : 'Transfer',
                                ],
                            ];
                        @endphp
                        <tr class="hidden record-child-of-{{ $item->id }} bg-slate-50/60 hover:bg-slate-100/60 transition-colors">
                            <td class="px-5 py-3 align-top">
                                <div class="flex items-center gap-2 pl-8">
                                    <span class="font-mono font-bold text-slate-600 bg-white border border-slate-200 px-2 py-1 rounded-lg">CIC-{{ str_pad($childIndex + 1, 5, '0', STR_PAD_LEFT) }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 align-top font-bold text-slate-600">
                                {{ $child->paid_at ? $child->paid_at->translatedFormat('d F Y') : ($child->tanggal instanceof \Carbon\Carbon ? $child->tanggal->translatedFormat('d F Y') : $child->tanggal) }}
                            </td>
                            <td class="px-5 py-3 align-top text-slate-500">
                                -
                            </td>
                            <td class="px-5 py-3 align-top">
                                <span class="inline-flex px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 border border-amber-200 text-[11px] font-bold">
                                    Cicil
                                </span>
                            </td>
                            <td class="px-5 py-3 align-top font-bold text-slate-700">
                                {{ $child->formatted_nominal }}
                            </td>
                            <td class="px-5 py-3 align-top text-slate-400 font-bold">-</td>
                            <td class="px-5 py-3 align-top text-slate-400 font-bold">-</td>
                            <td class="px-5 py-3 align-top">
                                <span class="bg-{{ $childStatusColor }}-100 text-{{ $childStatusColor }}-700 text-[12px] font-bold px-2 py-0.5 rounded-full border border-{{ $childStatusColor }}-200">
                                    {{ $childStatusLabel }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center align-top">
                                <button type="button"
                                    data-proof='@json($childProofPayload)'
                                    onclick="openPaymentProofFromButton(this)"
                                    class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 inline-flex items-center justify-center hover:bg-blue-100 transition-all shadow-sm" title="{{ $isPiutang ? 'Detail Penerimaan Cicilan' : 'Detail Pembayaran Cicilan' }}">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
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

    {{-- Hutang Antar Cabang --}}
    @if(isset($branchDebts))
    <div class="mt-10 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-col gap-3">
            <div class="mb-4 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                <div class="flex-1">
                    <h2 class="text-xl font-black text-slate-900">
                        {{ $branchSectionTitle }}
                        <span class="block text-xs font-normal text-slate-400 mt-1">Tercatat otomatis berdasarkan alokasi dari Transaksi Pengajuan Pembagian Cabang.</span>
                    </h2>
                    <div class="flex flex-wrap items-center gap-3 mt-4">
                        {{-- Status Filter --}}
                        <div class="flex items-center gap-2 p-1 bg-slate-100 rounded-xl w-fit">
                            <a href="{{ request()->fullUrlWithQuery(['debt_status' => null]) }}" class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all {{ !request('debt_status') ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">Semua</a>
                            <a href="{{ request()->fullUrlWithQuery(['debt_status' => 'pending']) }}" class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all {{ request('debt_status') === 'pending' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">Belum Lunas</a>
                            <a href="{{ request()->fullUrlWithQuery(['debt_status' => 'paid']) }}" class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all {{ request('debt_status') === 'paid' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">Lunas</a>
                        </div>

                        {{-- Search & Branch Filter for Branch Debt --}}
                        <form action="{{ $routeIndex }}" method="GET" class="flex items-center gap-2">
                            {{-- Keep existing filters --}}
                            <input type="hidden" name="debt_status" value="{{ request('debt_status') }}">
                            <input type="hidden" name="search" value="{{ request('search') }}">
                            <input type="hidden" name="branch_id" value="{{ request('branch_id') }}">

                            <input type="text" name="debt_search" value="{{ request('debt_search') }}" placeholder="No. INV Transaksi..."
                                class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none w-32 md:w-40">

                            <select name="debt_branch_id" onchange="this.form.submit()"
                                class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none">
                                <option value="">Semua Cabang</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ request('debt_branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                @endforeach
                            </select>

                            @if(request('debt_search') || request('debt_branch_id'))
                                <a href="{{ route($pageRoute . '.index', request()->except(['debt_search', 'debt_branch_id'])) }}" class="text-slate-400 hover:text-indigo-500 transition-colors">
                                    <i data-lucide="x-circle" class="w-4 h-4"></i>
                                </a>
                            @endif
                            <button type="submit" class="hidden">Filter</button>
                        </form>
                    </div>
                </div>
                <div class="px-3 py-1 bg-indigo-50 text-indigo-700 font-bold text-[10px] rounded-full border border-indigo-200 uppercase tracking-wider h-fit">Data Real-time</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            @if($branchDebts->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <i data-lucide="credit-card" class="w-8 h-8 text-slate-200 mb-2"></i>
                    <p class="text-slate-500 font-bold text-sm">{{ $branchEmptyText }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-xs">
                                <th class="px-5 py-3.5 text-left font-black text-slate-500 uppercase tracking-wider">No. Invoice</th>
                                <th class="px-5 py-3.5 text-left font-black text-slate-500 uppercase tracking-wider">Tanggal</th>
                                <th class="px-5 py-3.5 text-left font-black text-slate-500 uppercase tracking-wider">Keterangan</th>
                                <th class="px-5 py-3.5 text-left font-black text-slate-500 uppercase tracking-wider">Jenis</th>
                                <th class="px-5 py-3.5 text-left font-black text-slate-500 uppercase tracking-wider">Nominal</th>
                                <th class="px-5 py-3.5 text-left font-black text-slate-500 uppercase tracking-wider">{{ $isPiutang ? 'Diterima' : 'Terbayar' }}</th>
                                <th class="px-5 py-3.5 text-left font-black text-slate-500 uppercase tracking-wider">Sisa</th>
                                <th class="px-5 py-3.5 text-left font-black text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="px-5 py-3.5 text-center font-black text-slate-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            @foreach($branchDebts as $debt)
                            @php
                                $debtPaymentSummary = $debt->payment_summary;
                                $debtChildren = $debt->children->sortBy('created_at')->values();
                                $debtInstallmentRows = collect();
                                if ($debt->status === 'paid' && $debtChildren->isNotEmpty()) {
                                    $debtInstallmentRows->push($debt);
                                }
                                $debtInstallmentRows = $debtInstallmentRows
                                    ->merge($debtChildren->where('status', 'paid')->values())
                                    ->values();
                                $hasDebtChildren = $debtInstallmentRows->isNotEmpty();
                                $pendingDebt = $debtChildren->where('status', 'pending')->sortByDesc('created_at')->first();
                                if (!$pendingDebt && $debt->status === 'pending') {
                                    $pendingDebt = $debt;
                                }
                                $isDebtOutstanding = $debtPaymentSummary['remaining'] > 0;
                                $debtStatusColor = $isDebtOutstanding ? 'red' : 'emerald';
                                $debtStatusLabel = $isDebtOutstanding ? 'Belum Lunas' : 'Sudah Lunas';
                                $debtProofPayload = [
                                    'url' => $debt->payment_proof ? asset('storage/' . $debt->payment_proof) : '',
                                    'title' => $proofLabel . ' ' . $mainTypeLabel . ' ' . ($debt->transaction->invoice_number ?? ''),
                                    'data' => [
                                        'amount' => $debt->formatted_amount,
                                        'notes' => $debt->notes ?? '-',
                                        'paid_by' => $debt->paidBy->name ?? 'System',
                                        'paid_at' => $debt->paid_at ? $debt->paid_at->translatedFormat('d F Y H:i') : '-',
                                        'selected_account' => $debt->bankAccount,
                                        'sender_account' => $debt->senderBankAccount,
                                        'all_accounts' => $debt->creditorBranch?->bankAccounts ?? [],
                                        'sender_branch' => $debt->debtorBranch->name ?? '-',
                                        'receiver_branch' => $debt->creditorBranch->name ?? '-',
                                        'method' => $debt->payment_method === 'cash' ? 'Tunai (Cash)' : 'Transfer',
                                    ],
                                ];
                            @endphp
                            <tr class="hover:bg-slate-50 transition-colors {{ !$isDebtOutstanding ? 'opacity-90' : '' }}">
                                <td class="px-5 py-4 align-top">
                                    <div class="flex items-center gap-2">
                                        @if($hasDebtChildren)
                                            <button type="button"
                                                onclick="toggleDebtChildren('branch-debt', {{ $debt->id }})"
                                                class="w-6 h-6 rounded-md bg-slate-100 text-slate-600 flex items-center justify-center hover:bg-slate-200 transition-all"
                                                title="Buka/tutup cicilan">
                                                <i data-lucide="chevron-right" class="w-4 h-4 transition-transform" id="branch-debt-toggle-icon-{{ $debt->id }}"></i>
                                            </button>
                                        @else
                                            <span class="w-6 h-6"></span>
                                        @endif
                                        <span class="font-mono font-bold bg-slate-100 px-2 py-1 rounded-lg">{{ $debt->transaction->invoice_number ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 align-top font-bold">
                                    {{ $debt->created_at->translatedFormat('d F Y') }}
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <div class="">
                                        <span class="bg-{{ $debtStatusColor }}-100 text-{{ $debtStatusColor }}-700  font-bold px-2 py-0.5 rounded-full border border-{{ $debtStatusColor }}-200">{{ $debt->debtorBranch->name ?? '-' }}</span>
                                        <span class="text-slate-500">ke</span>
                                        <span class="font-bold text-slate-700">{{ $debt->creditorBranch->name ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <span class="inline-flex px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200 text-[11px] font-bold">
                                        {{ $mainTypeLabel }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 align-top"><span class="font-bold text-red-600">Rp {{ number_format($debtPaymentSummary['total'], 0, ',', '.') }}</span></td>
                                <td class="px-5 py-4 align-top"><span class="font-bold text-emerald-600">Rp {{ number_format($debtPaymentSummary['paid'], 0, ',', '.') }}</span></td>
                                <td class="px-5 py-4 align-top"><span class="font-bold text-slate-700">Rp {{ number_format($debtPaymentSummary['remaining'], 0, ',', '.') }}</span></td>
                                <td class="px-5 py-4 align-top">
                                    <span class="bg-{{ $debtStatusColor }}-100 text-{{ $debtStatusColor }}-700 text-[12px] font-bold px-2 py-0.5 rounded-full border border-{{ $debtStatusColor }}-200">
                                        {{ $debtStatusLabel }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center align-top">
                                    <div class="flex items-center justify-center gap-2">
                                        @if($pendingDebt && !$isPiutang)
                                            <button type="button"
                                                onclick='openSettleModal({{ $pendingDebt->id }}, {{ json_encode($pendingDebt->creditorBranch->bankAccounts) }}, "{{ $pendingDebt->creditorBranch->name }}", {{ json_encode($pendingDebt->debtorBranch->bankAccounts) }}, "{{ $pendingDebt->debtorBranch->name }}", null, {{ $pendingDebt->amount }})'
                                                class="px-4 py-1.5 bg-red-600 text-white rounded-lg text-xs font-bold hover:bg-red-700 transition-all shadow-sm">
                                                {{ $actionLabel }}
                                            </button>
                                        @elseif($debt->status === 'paid' && !$hasDebtChildren)
                                            <button type="button"
                                                data-proof='@json($debtProofPayload)'
                                                onclick="openPaymentProofFromButton(this)"
                                            class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 inline-flex items-center justify-center hover:bg-blue-100 transition-all shadow-sm" title="{{ $isPiutang ? 'Detail Penerimaan' : 'Detail Pembayaran' }}">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                            </button>
                                        @elseif(!$isPiutang)
                                            -
                                        @endif

                                        {{-- Detail Hutang --}}
                                        @if($hasDebtChildren || $isPiutang)
                                        <button type="button"
                                            onclick="openDetailModal({{ $debt->id }}, 'branch_debt')"
                                            class="w-8 h-8 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center hover:bg-violet-100 transition-all shadow-sm" title="{{ $detailLabel }}">
                                            <i data-lucide="info" class="w-4 h-4"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @foreach($debtInstallmentRows as $childDebtIndex => $childDebt)
                            @php
                                $childDebtStatusColor = $childDebt->status === 'paid' ? 'emerald' : 'red';
                                $childDebtStatusLabel = $childDebt->status === 'paid' ? 'Lunas' : 'Belum Lunas';
                                $childDebtProofPayload = [
                                    'url' => $childDebt->payment_proof ? asset('storage/' . $childDebt->payment_proof) : '',
                                    'title' => $proofLabel . ' Cicilan CIC-' . str_pad($childDebtIndex + 1, 5, '0', STR_PAD_LEFT),
                                    'data' => [
                                        'amount' => $childDebt->formatted_amount,
                                        'notes' => $childDebt->notes ?? '-',
                                        'paid_by' => $childDebt->paidBy->name ?? 'System',
                                        'paid_at' => $childDebt->paid_at ? $childDebt->paid_at->translatedFormat('d F Y H:i') : $childDebt->created_at->translatedFormat('d F Y H:i'),
                                        'selected_account' => $childDebt->bankAccount,
                                        'sender_account' => $childDebt->senderBankAccount,
                                        'all_accounts' => $childDebt->creditorBranch?->bankAccounts ?? [],
                                        'sender_branch' => $childDebt->debtorBranch->name ?? '-',
                                        'receiver_branch' => $childDebt->creditorBranch->name ?? '-',
                                        'method' => $childDebt->payment_method === 'cash' ? 'Tunai (Cash)' : 'Transfer',
                                    ],
                                ];
                            @endphp
                            <tr class="hidden branch-debt-child-of-{{ $debt->id }} bg-slate-50/60 hover:bg-slate-100/60 transition-colors">
                                <td class="px-5 py-3 align-top">
                                    <div class="flex items-center gap-2 pl-8">
                                        <span class="font-mono font-bold text-slate-600 bg-white border border-slate-200 px-2 py-1 rounded-lg">CIC-{{ str_pad($childDebtIndex + 1, 5, '0', STR_PAD_LEFT) }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3 align-top font-bold text-slate-600">
                                    {{ $childDebt->paid_at ? $childDebt->paid_at->translatedFormat('d F Y') : $childDebt->created_at->translatedFormat('d F Y') }}
                                </td>
                                <td class="px-5 py-3 align-top text-slate-500">
                                    -
                                </td>
                                <td class="px-5 py-3 align-top">
                                    <span class="inline-flex px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 border border-amber-200 text-[11px] font-bold">
                                        Cicil
                                    </span>
                                </td>
                                <td class="px-5 py-3 align-top font-bold text-slate-700">
                                    {{ $childDebt->formatted_amount }}
                                </td>
                                <td class="px-5 py-3 align-top text-slate-400 font-bold">-</td>
                                <td class="px-5 py-3 align-top text-slate-400 font-bold">-</td>
                                <td class="px-5 py-3 align-top">
                                    <span class="bg-{{ $childDebtStatusColor }}-100 text-{{ $childDebtStatusColor }}-700 text-[12px] font-bold px-2 py-0.5 rounded-full border border-{{ $childDebtStatusColor }}-200">
                                        {{ $childDebtStatusLabel }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-center align-top">
                                    <button type="button"
                                        data-proof='@json($childDebtProofPayload)'
                                        onclick="openPaymentProofFromButton(this)"
                                        class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 inline-flex items-center justify-center hover:bg-blue-100 transition-all shadow-sm" title="{{ $isPiutang ? 'Detail Penerimaan Cicilan' : 'Detail Pembayaran Cicilan' }}">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($branchDebts->hasPages())
                <div class="px-5 py-4 border-t border-slate-100">
                    {{ $branchDebts->withQueryString()->links('components.pagination.premium') }}
                </div>
                @endif
            @endif
        </div>
    </div>
    @endif
</div>

@push('modals')
{{-- ═══ Modal Detail Hutang ═══════════════════════════════════════════════ --}}
<div id="detail-debt-modal" class="hidden fixed inset-0 z-[9999] bg-slate-900/70 backdrop-blur-md flex items-center justify-center p-2 sm:p-4 opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl overflow-hidden transform scale-95 transition-transform duration-300 flex flex-col" id="detail-debt-modal-box" style="max-height: 95vh;">

        {{-- Header --}}
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-violet-50 to-white flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-violet-100 flex items-center justify-center text-violet-600">
                    <i data-lucide="receipt" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-black text-slate-800 text-base" id="detail-debt-title">{{ $detailLabel }}</h3>
                    <p class="text-[11px] text-slate-400 mt-0.5" id="detail-debt-subtitle">Riwayat cicilan & informasi {{ $isPiutang ? 'penerimaan' : 'pembayaran' }}</p>
                </div>
            </div>
            <button type="button" onclick="closeDetailModal()" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-200 text-slate-400 transition-all">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        {{-- Loading State --}}
        <div id="detail-debt-loading" class="flex flex-col items-center justify-center py-20 gap-3">
            <svg class="animate-spin h-8 w-8 text-violet-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-slate-400 text-sm font-semibold">Memuat data...</p>
        </div>

        {{-- Content: Split Layout --}}
        <div id="detail-debt-content" class="hidden flex-1 overflow-hidden" style="min-height: 0;">
            <div class="flex flex-col lg:flex-row h-full" style="max-height: calc(95vh - 73px);">

                {{-- ── Left: Debt Details ────────────────────────────── --}}
                <div class="w-full lg:w-5/12 border-b lg:border-b-0 lg:border-r border-slate-100 overflow-y-auto flex-shrink-0">
                    <div class="p-4 sm:p-6 space-y-4 sm:space-y-5">

                        {{-- Info Cabang --}}
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">{{ $flowLabel }}</p>
                            <div class="flex items-center gap-2 p-3 bg-red-50 rounded-xl border border-red-100">
                                <div class="text-center flex-1">
                                    <p class="text-[10px] text-red-400 font-bold uppercase mb-0.5">{{ $fromFlowLabel }}</p>
                                    <p class="text-sm font-black text-red-700" id="detail-from-branch">-</p>
                                </div>
                                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                                    <i data-lucide="arrow-right" class="w-4 h-4 text-red-500"></i>
                                </div>
                                <div class="text-center flex-1">
                                    <p class="text-[10px] text-emerald-500 font-bold uppercase mb-0.5">{{ $toFlowLabel }}</p>
                                    <p class="text-sm font-black text-emerald-700" id="detail-to-branch">-</p>
                                </div>
                            </div>
                        </div>

                        {{-- Nominal & Status --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <p class="text-[10px] font-black text-slate-400 uppercase mb-1">{{ $totalSourceLabel }}</p>
                                <p class="text-base font-black text-red-600" id="detail-original-amount">-</p>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Status</p>
                                <span id="detail-status-badge" class="inline-flex items-center gap-1 text-xs font-black px-2 py-0.5 rounded-full">-</span>
                            </div>
                        </div>

                        {{-- Terkait Transaksi --}}
                        <div id="detail-transaction-row" class="p-3 bg-indigo-50 rounded-xl border border-indigo-100 hidden">
                            <p class="text-[10px] font-black text-indigo-400 uppercase mb-1">Terkait Transaksi</p>
                            <p class="text-sm font-black text-indigo-700 font-mono" id="detail-transaction-inv">-</p>
                        </div>

                        {{-- Statistik Cicilan --}}
                        <div id="detail-installment-stats" class="hidden">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">{{ $isPiutang ? 'Ringkasan Penerimaan' : 'Ringkasan Cicilan' }}</p>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-3 bg-amber-50 rounded-xl border border-amber-100">
                                    <p class="text-[10px] font-bold text-amber-500 uppercase mb-1">{{ $totalPaidLabel }}</p>
                                    <p class="text-sm font-black text-amber-700" id="detail-total-paid">-</p>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">{{ $remainingLabel }}</p>
                                    <p class="text-sm font-black text-slate-700" id="detail-remaining">-</p>
                                </div>
                            </div>
                            {{-- Progress Bar --}}
                            <div class="mt-3">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-[10px] font-black text-slate-400 uppercase">Progress {{ $isPiutang ? 'Penerimaan' : 'Pembayaran' }}</span>
                                    <span class="text-[11px] font-black text-emerald-600" id="detail-progress-pct">0%</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2">
                                    <div id="detail-progress-bar" class="bg-gradient-to-r from-emerald-400 to-emerald-600 h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Catatan --}}
                        <div id="detail-notes-row" class="hidden">
                            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Catatan</p>
                            <p class="text-xs text-slate-600 leading-relaxed" id="detail-notes"></p>
                        </div>
                    </div>
                </div>

                {{-- ── Right: Installment History Timeline ───────────── --}}
                <div class="w-full lg:flex-1 overflow-y-auto bg-slate-50/60">
                    <div class="p-4 sm:p-6">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                            {{ $isPiutang ? 'Riwayat Cicilan / Penerimaan' : 'Riwayat Cicilan / Pembayaran' }}
                        </p>
                        <div id="detail-history-timeline" class="space-y-0">
                            {{-- Filled by JS --}}
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Footer --}}
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end flex-shrink-0">
            <button type="button" onclick="closeDetailModal()" class="px-6 py-2.5 rounded-xl bg-slate-900 text-white font-bold text-sm shadow-md hover:bg-slate-800 transition-all">Tutup</button>
        </div>
    </div>
</div>

{{-- Modal View Proof --}}
<div id="view-proof-modal" class="hidden fixed inset-0 z-[9999] bg-slate-900/60 backdrop-blur-md flex items-center justify-center p-2 sm:p-4 opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden transform scale-95 transition-transform duration-300 flex flex-col lg:flex-row" id="view-proof-modal-box" style="max-height: 95vh;">
        {{-- Left: Image --}}
        <div class="w-full lg:w-1/2 bg-slate-50 flex items-center justify-center p-4 border-b lg:border-b-0 lg:border-r border-slate-100">
            <img id="view-proof-img" src="" class="max-w-full max-h-[50vh] lg:max-h-[70vh] rounded-xl shadow-lg border">
            <div id="view-proof-empty" class="hidden w-full min-h-[200px] lg:min-h-[260px] rounded-xl border border-slate-200 bg-white p-4 sm:p-6 flex flex-col items-center justify-center text-center">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-4">
                    <i data-lucide="receipt-text" class="w-7 h-7"></i>
                </div>
                <p class="text-sm font-black text-slate-800" id="view-proof-empty-title">{{ $isPiutang ? 'Penerimaan tercatat' : 'Pembayaran tercatat' }}</p>
                <p class="text-xs font-semibold text-slate-400 mt-1" id="view-proof-empty-subtitle">Tidak ada file bukti yang diunggah.</p>
                <a id="view-proof-file-link" href="#" target="_blank" rel="noopener" class="hidden mt-4 px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition-all">
                    Buka Bukti
                </a>
            </div>
        </div>

        {{-- Right: Details --}}
        <div class="w-full lg:w-1/2 flex flex-col bg-white">
            <div class="px-4 sm:px-6 py-4 sm:py-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-black text-slate-800 text-base sm:text-lg" id="view-proof-title">{{ $proofLabel }} {{ $pageTitle }}</h3>
                <button type="button" onclick="closeViewProofModal()" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-200 text-slate-400"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>

            <div class="p-4 sm:p-6 space-y-4 sm:space-y-6 flex-grow overflow-y-auto max-h-[50vh] lg:max-h-[60vh]">
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="block text-[10px] font-black text-slate-400 uppercase mb-1">{{ $isPiutang ? 'Nominal Diterima' : 'Nominal Bayar' }}</span>
                        <span id="view-proof-amount" class="text-base font-black text-emerald-600 tracking-tight"></span>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="block text-[10px] font-black text-slate-400 uppercase mb-1">{{ $isPiutang ? 'Diterima Oleh' : 'Dibayarkan Oleh' }}</span>
                        <span id="view-proof-settler" class="text-sm font-black text-slate-800"></span>
                    </div>
                </div>

                {{-- Display both sender and destination accounts if available --}}
                <div class="space-y-4">
                    <div id="view-proof-sender-container" class="hidden">
                        <span class="block text-[10px] font-black text-slate-400 uppercase mb-2">Rekening Pengirim</span>
                        <div id="view-proof-sender-account" class="space-y-2"></div>
                    </div>

                    <div>
                        <span class="block text-[10px] font-black text-slate-400 uppercase mb-2" id="account-label">Rekening Tujuan</span>
                        <div id="view-proof-accounts" class="space-y-2">
                            {{-- Filled by JS --}}
                        </div>
                    </div>
                </div>

                <div>
                    <span class="block text-[10px] font-black text-slate-400 uppercase mb-1">Keterangan / Catatan</span>
                    <p id="view-proof-notes" class="text-sm font-medium text-slate-600 leading-relaxed"></p>
                </div>
            </div>

            <div class="px-4 sm:px-6 py-3 sm:py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                <button type="button" onclick="closeViewProofModal()" class="px-4 sm:px-6 py-2 rounded-xl bg-slate-900 text-white font-bold text-sm shadow-md hover:bg-slate-800 transition-all">Tutup</button>
            </div>
        </div>
    </div>
</div>

    <div id="branch-debt-modal" class="hidden fixed inset-0 z-[9999] bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-2 sm:p-4 opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden transform scale-95 transition-transform duration-300 flex flex-col" id="branch-debt-modal-box" style="max-height: 95vh;">
        <form id="branch-debt-form" method="POST" enctype="multipart/form-data" class="flex flex-col overflow-hidden h-full">
            <div class="px-4 sm:px-6 py-4 sm:py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 shrink-0">
                <div class="flex items-center gap-2 sm:gap-3">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-red-100 flex items-center justify-center text-red-600"><i data-lucide="credit-card" class="w-4 h-4 sm:w-5 sm:h-5"></i></div>
                    <h3 class="font-black text-slate-800 text-base sm:text-lg" id="settle-modal-title">{{ $isPiutang ? 'Cairkan Piutang Cabang' : 'Bayar Hutang Cabang' }}</h3>
                </div>
                <button type="button" onclick="closeSettleModal()" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-200 text-slate-400"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-4 sm:p-6 space-y-4 sm:space-y-5 overflow-y-auto">

                {{-- Metode Pembayaran --}}
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase mb-2 px-1">Metode {{ $isPiutang ? 'Penerimaan' : 'Pembayaran' }} <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-2 gap-3" id="payment-method-toggle">
                        <label id="method-transfer-label" class="flex items-center gap-2.5 p-3 rounded-xl border-2 border-indigo-500 bg-indigo-50 cursor-pointer transition-all">
                            <input type="radio" name="payment_method" value="transfer" class="hidden" checked>
                            <div class="w-5 h-5 rounded-full border-2 border-indigo-500 flex items-center justify-center" id="radio-transfer-dot">
                                <div class="w-2.5 h-2.5 rounded-full bg-indigo-500"></div>
                            </div>
                            <div>
                                <div class="text-xs font-black text-indigo-700">Transfer</div>
                                <div class="text-[10px] text-indigo-500">Via rekening bank</div>
                            </div>
                        </label>
                        <label id="method-cash-label" class="flex items-center gap-2.5 p-3 rounded-xl border-2 border-slate-200 bg-white cursor-pointer transition-all">
                            <input type="radio" name="payment_method" value="cash" class="hidden">
                            <div class="w-5 h-5 rounded-full border-2 border-slate-300 flex items-center justify-center" id="radio-cash-dot">
                                <div class="w-2.5 h-2.5 rounded-full bg-slate-300 hidden"></div>
                            </div>
                            <div>
                                <div class="text-xs font-black text-slate-700">Tunai (Cash)</div>
                                <div class="text-[10px] text-slate-400">{{ $isPiutang ? 'Terima langsung' : 'Bayar langsung' }}</div>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Tipe Pembayaran --}}
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase mb-2 px-1">Tipe {{ $isPiutang ? 'Penerimaan' : 'Pembayaran' }} <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-2 gap-3" id="payment-type-toggle">
                        <label id="type-lunas-label" class="flex items-center gap-2.5 p-3 rounded-xl border-2 border-indigo-500 bg-indigo-50 cursor-pointer transition-all">
                            <input type="radio" name="payment_type" value="lunas" class="hidden" checked>
                            <div class="w-5 h-5 rounded-full border-2 border-indigo-500 flex items-center justify-center" id="radio-lunas-dot">
                                <div class="w-2.5 h-2.5 rounded-full bg-indigo-500"></div>
                            </div>
                            <div>
                                <div class="text-xs font-black text-indigo-700">Lunas / Cash</div>
                                <div class="text-[10px] text-indigo-500">{{ $isPiutang ? 'Terima penuh' : 'Bayar penuh' }}</div>
                            </div>
                        </label>
                        <label id="type-cicil-label" class="flex items-center gap-2.5 p-3 rounded-xl border-2 border-slate-200 bg-white cursor-pointer transition-all">
                            <input type="radio" name="payment_type" value="cicil" class="hidden">
                            <div class="w-5 h-5 rounded-full border-2 border-slate-300 flex items-center justify-center" id="radio-cicil-dot">
                                <div class="w-2.5 h-2.5 rounded-full bg-slate-300 hidden"></div>
                            </div>
                            <div>
                                <div class="text-xs font-black text-slate-700">{{ $isPiutang ? 'Terima Cicil' : 'Bayar Cicil' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $isPiutang ? 'Terima sebagian' : 'Bayar sebagian' }}</div>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Nominal Cicilan --}}
                <div id="nominal-cicilan-container" class="hidden">
                    <label class="block text-xs font-black text-slate-700 uppercase mb-2 px-1">Nominal {{ $isPiutang ? 'Penerimaan' : 'Pembayaran' }} (Cicilan) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-slate-500 text-sm font-bold">Rp</span>
                        </div>
                        <input type="text" inputmode="numeric" name="amount_paid" id="amount_paid_input"
                            placeholder="0" autocomplete="off" spellcheck="false"
                            class="w-full text-sm border-2 border-slate-100 pl-10 pr-3 py-3 rounded-xl focus:border-red-500 focus:ring-0 transition-all font-bold text-slate-800">
                    </div>
                    <p class="text-[10px] text-slate-500 mt-1" id="cicilan-warning-text">Masukkan nominal cicilan.</p>
                </div>

                {{-- Upload Bukti --}}
                <div class="pt-1">
                    <label class="block text-xs font-black text-slate-700 uppercase mb-2 px-1 text-emerald-600 flex items-center gap-1.5" id="proof-label">
                        <i data-lucide="image" class="w-3.5 h-3.5"></i>
                        Upload Bukti Transfer <span class="text-red-500" id="proof-required-star">*</span>
                        <span class="text-slate-400 font-normal normal-case" id="proof-optional-note" style="display:none">(Opsional untuk Cash)</span>
                    </label>
                    <input type="file" name="payment_proof" id="branch_debt_file_input" accept="image/jpeg,image/png,application/pdf"
                        class="w-full text-sm border-2 border-slate-100 p-2 rounded-xl focus:border-red-500 focus:ring-0 transition-all">
                </div>

                {{-- Rekening Pengirim (sembunyikan jika Cash) --}}
                <div id="settle-sender-bank-accounts-container" class="hidden mb-4 pb-4 border-b border-slate-100">
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-2 px-1">Pilih Rekening Pengirim (Sumber: <span id="settle-sender-branch-name"></span>)</label>
                    <select name="sender_bank_account_id" id="settle-sender-bank-accounts-select"
                        class="w-full text-sm border-2 border-slate-100 p-3 rounded-xl focus:border-red-500 focus:ring-0 transition-all bg-slate-50 font-bold text-slate-800">
                    </select>

                    <div id="settle-sender-bank-account-detail" class="hidden mt-3 p-4 bg-white border border-slate-200 rounded-xl space-y-3 shadow-sm">
                        <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Nama Pengirim</span>
                            <span id="settle-sender-detail-name" class="text-xs font-bold text-slate-800"></span>
                        </div>
                        <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Nomor Cabang</span>
                            <span id="settle-sender-detail-number" class="text-[15px] font-mono font-black text-red-600 tracking-tight"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Jenis Rekening</span>
                            <span id="settle-sender-detail-bank" class="text-[11px] font-bold text-slate-600 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-md uppercase"></span>
                        </div>
                    </div>
                </div>

                {{-- Rekening Tujuan (sembunyikan jika Cash) --}}
                <div id="settle-bank-accounts-container" class="hidden">
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-2 px-1">Pilih Rekening Tujuan (<span id="settle-branch-name"></span>)</label>
                    <select name="bank_account_id" id="settle-bank-accounts-select"
                        class="w-full text-sm border-2 border-slate-100 p-3 rounded-xl focus:border-red-500 focus:ring-0 transition-all bg-slate-50 font-bold text-slate-800">
                        {{-- Filled by JS --}}
                    </select>

                    {{-- Detail Rekening --}}
                    <div id="settle-bank-account-detail" class="hidden mt-3 p-4 bg-white border border-slate-200 rounded-xl space-y-3 shadow-sm">
                        <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Nama Cabang / Rekening</span>
                            <span id="settle-detail-name" class="text-xs font-bold text-slate-800"></span>
                        </div>
                        <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Nomor Cabang</span>
                            <span id="settle-detail-number" class="text-[15px] font-mono font-black text-red-600 tracking-tight"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Jenis Rekening</span>
                            <span id="settle-detail-bank" class="text-[11px] font-bold text-slate-600 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-md uppercase"></span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase mb-2 px-1">Keterangan / Catatan</label>
                    <textarea name="notes" rows="2" placeholder="Contoh: Sudah transfer via BCA..."
                        class="w-full text-sm border-2 border-slate-100 p-3 rounded-xl focus:border-red-500 focus:ring-0 transition-all"></textarea>
                </div>
            </div>
            <div class="px-4 sm:px-6 py-3 sm:py-4 bg-slate-50 border-t border-slate-100 flex flex-col sm:flex-row justify-end gap-2 sm:gap-3 shrink-0">
                <button type="button" onclick="closeSettleModal()" class="w-full sm:w-auto px-4 sm:px-5 py-2.5 rounded-xl font-bold text-sm text-slate-600 hover:bg-slate-200 transition-all">Batal</button>
                <button type="submit" id="btnSubmitBranchDebt" class="w-full sm:w-auto px-4 sm:px-5 py-2.5 rounded-xl bg-red-600 text-white font-bold text-sm flex items-center justify-center gap-2 transition-all">
                    <span id="btnSubmitBranchDebtText">{{ $isPiutang ? 'Konfirmasi Cairkan' : 'Konfirmasi Bayar' }}</span>
                    <div id="btnSubmitBranchDebtLoader" class="hidden">
                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </button>
            </div>
        </form>
    </div>
</div>

@endpush

@push('scripts')
<script>
    function toggleDebtChildren(type, id) {
        const childClass = type === 'branch-debt'
            ? `branch-debt-child-of-${id}`
            : `record-child-of-${id}`;
        const iconId = type === 'branch-debt'
            ? `branch-debt-toggle-icon-${id}`
            : `record-toggle-icon-${id}`;

        const rows = document.querySelectorAll(`.${childClass}`);
        const icon = document.getElementById(iconId);
        const shouldOpen = Array.from(rows).some(row => row.classList.contains('hidden'));

        rows.forEach(row => row.classList.toggle('hidden', !shouldOpen));

        if (icon) {
            icon.classList.toggle('rotate-90', shouldOpen);
        }
    }

    // ─── Detail Hutang Modal ─────────────────────────────────────────────
    function openDetailModal(id, type) {
        // Close mobile sidebar if open
        closeMobileSidebarIfOpen();
        
        // Prevent body scroll
        document.body.classList.add('modal-open');
        
        const modal = document.getElementById('detail-debt-modal');
        const box = document.getElementById('detail-debt-modal-box');
        const loading = document.getElementById('detail-debt-loading');
        const content = document.getElementById('detail-debt-content');

        // Show modal with loading state
        loading.classList.remove('hidden');
        content.classList.add('hidden');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            box.classList.remove('scale-95');
            box.classList.add('scale-100');
        }, 10);

        const url = type === 'branch_debt'
            ? `/branch-debts/${id}/history`
            : `/pengeluaran-lain/record/${id}/history`;

        fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{csrf_token()}}' } })
            .then(r => r.json())
            .then(res => {
                if (!res.success || !res.data || res.data.length === 0) {
                    throw new Error('Data tidak ditemukan.');
                }
                renderDetailModal(res.data, type);
                loading.classList.add('hidden');
                content.classList.remove('hidden');
                if (window.lucide) lucide.createIcons({ root: modal });
            })
            .catch(err => {
                loading.innerHTML = `<div class="text-center py-16">
                    <i data-lucide="alert-circle" class="w-10 h-10 text-red-300 mx-auto mb-3"></i>
                    <p class="text-slate-400 font-semibold text-sm">Gagal memuat data</p>
                    <p class="text-slate-300 text-xs mt-1">${err.message}</p>
                </div>`;
                if (window.lucide) lucide.createIcons({ root: loading });
            });
    }

    function renderDetailModal(history, type) {
        const isPiutang = @json($isPiutang);
        const mainTypeLabel = @json($mainTypeLabel);
        const remainingLabel = @json($remainingLabel);
        // history[0] = original/ancestor record
        const first = history[0];
        const paid = history.filter(h => h.status === 'paid' || h.status === 'approved');
        const pending = history.filter(h => h.status === 'pending');
        const isInstallment = history.length > 1;

        // ── Helper: format rupiah
        const fmtRp = v => 'Rp ' + Number(v).toLocaleString('id-ID');

        // ── Branches
        if (type === 'branch_debt') {
            document.getElementById('detail-from-branch').textContent = first.debtor_branch?.name ?? (first.dari_branch?.name ?? '-');
            document.getElementById('detail-to-branch').textContent = first.creditor_branch?.name ?? (first.branch?.name ?? '-');
        } else {
            document.getElementById('detail-from-branch').textContent = isPiutang ? (first.branch?.name ?? '-') : (first.dari_branch?.name ?? '-');
            document.getElementById('detail-to-branch').textContent = isPiutang ? (first.dari_branch?.name ?? '-') : (first.branch?.name ?? '-');
        }

        // ── Original amount = sum of all records
        const totalOriginal = history.reduce((s, h) => s + Number(h.nominal ?? h.amount ?? 0), 0);
        document.getElementById('detail-original-amount').textContent = fmtRp(totalOriginal);

        // ── Title & Subtitle
        const inv = first.invoice_number ?? (first.transaction?.invoice_number ?? null);
        document.getElementById('detail-debt-title').textContent = inv ? `Detail: ${inv}` : @json($detailLabel);
        document.getElementById('detail-debt-subtitle').textContent = isInstallment
            ? `${history.length} data riwayat (cicilan)`
            : (isPiutang ? 'Penerimaan tunggal' : 'Pembayaran tunggal');

        // ── Status Badge
        const hasAnyPending = pending.length > 0;
        const statusBadge = document.getElementById('detail-status-badge');
        if (hasAnyPending) {
            statusBadge.className = 'inline-flex items-center gap-1 text-xs font-black px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 border border-amber-200';
            statusBadge.textContent = `Belum Lunas`;
        } else {
            statusBadge.className = 'inline-flex items-center gap-1 text-xs font-black px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200';
            statusBadge.textContent = 'Lunas';
        }

        // ── Transaction row
        if (inv) {
            document.getElementById('detail-transaction-inv').textContent = inv;
            document.getElementById('detail-transaction-row').classList.remove('hidden');
        } else {
            document.getElementById('detail-transaction-row').classList.add('hidden');
        }

        // ── Installment stats (only show for cicil)
        const statsEl = document.getElementById('detail-installment-stats');
        if (isInstallment) {
            statsEl.classList.remove('hidden');
            const totalPaid = paid.reduce((s, h) => s + Number(h.nominal ?? h.amount ?? 0), 0);
            const remaining = pending.reduce((s, h) => s + Number(h.nominal ?? h.amount ?? 0), 0);
            const pct = totalOriginal > 0 ? Math.round((totalPaid / totalOriginal) * 100) : 0;
            document.getElementById('detail-total-paid').textContent = fmtRp(totalPaid);
            document.getElementById('detail-remaining').textContent = fmtRp(remaining);
            document.getElementById('detail-progress-pct').textContent = pct + '%';
            document.getElementById('detail-progress-bar').style.width = pct + '%';
        } else {
            statsEl.classList.add('hidden');
        }

        // ── Notes
        const firstNote = first.keterangan ?? first.notes ?? null;
        const notesRow = document.getElementById('detail-notes-row');
        if (firstNote) {
            document.getElementById('detail-notes').textContent = firstNote;
            notesRow.classList.remove('hidden');
        } else {
            notesRow.classList.add('hidden');
        }

        // ── Timeline
        const timeline = document.getElementById('detail-history-timeline');
        timeline.innerHTML = '';

        if (history.length === 0) {
            timeline.innerHTML = `<p class="text-slate-400 text-xs text-center py-8">Belum ada riwayat ${isPiutang ? 'penerimaan' : 'pembayaran'}.</p>`;
            return;
        }

        const paidEntries = history.filter(h => h.status === 'paid' || h.status === 'approved');
        const pendingEntries = history.filter(h => h.status === 'pending');
        const totalPaidAmount = paidEntries.reduce((s, h) => s + Number(h.nominal ?? h.amount ?? 0), 0);
        const remainingAmount = pendingEntries.reduce((s, h) => s + Number(h.nominal ?? h.amount ?? 0), 0);
        const totalAmount = totalPaidAmount + remainingAmount;

        const timelineEntries = [{
            title: mainTypeLabel,
            amount: totalAmount,
            isPaid: remainingAmount <= 0,
            isRemainder: false,
            statusText: remainingAmount <= 0 ? 'Lunas' : 'Belum Lunas',
            methodLabel: null,
            paidAt: null,
            paidBy: null,
        }];

        paidEntries.forEach((h, paidIdx) => {
            timelineEntries.push({
                title: paidIdx === 0 ? 'Cicilan Pertama' : `Cicilan ke-${paidIdx + 1}`,
                amount: Number(h.nominal ?? h.amount ?? 0),
                isPaid: true,
                isRemainder: false,
                statusText: 'Lunas',
                methodLabel: h.payment_method === 'cash' ? 'Tunai (Cash)' : (h.payment_method === 'transfer' ? 'Transfer' : '-'),
                paidAt: h.paid_at ?? h.updated_at ?? h.created_at,
                paidBy: h.paid_by?.name ?? h.submitter?.name ?? null,
            });
        });

        if (remainingAmount > 0) {
            timelineEntries.push({
                title: remainingLabel,
                amount: remainingAmount,
                isPaid: false,
                isRemainder: true,
                statusText: 'Belum Lunas',
                methodLabel: null,
                paidAt: null,
                paidBy: null,
            });
        }

        timelineEntries.forEach((entry, idx) => {
            const isPaid = entry.isPaid;
            const isLast = idx === timelineEntries.length - 1;
            const amount = entry.amount;
            const methodLabel = entry.methodLabel;
            const paidAt = entry.paidAt;
            const paidBy = entry.paidBy;

            let paidAtStr = '-';
            if (paidAt) {
                try {
                    paidAtStr = new Date(paidAt).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                } catch(e) { paidAtStr = paidAt; }
            }

            const dotColor = isPaid
                ? 'bg-emerald-500 border-emerald-200 shadow-emerald-100 text-white'
                : (entry.isRemainder ? 'bg-amber-400 border-amber-200 shadow-amber-100 text-white animate-pulse' : 'bg-slate-200 border-slate-100 text-slate-500');
            const cardBg   = isPaid ? 'bg-white border-emerald-100' : 'bg-amber-50 border-amber-100';
            const labelColor = isPaid ? 'text-emerald-600' : 'text-amber-500';
            const amtColor   = isPaid ? 'text-emerald-700' : 'text-amber-700';

            const statusLabel = isPaid
                ? `<span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200">Lunas</span>`
                : `<span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 border border-amber-200">Belum Lunas</span>`;

            const methodBadge = isPaid && methodLabel
                ? `<span class="text-[10px] font-bold text-slate-500">${methodLabel}</span>`
                : '';

            timeline.innerHTML += `
                <div class="relative flex gap-4">
                    {{-- Connector line --}}
                    ${idx !== timelineEntries.length - 1 ? `<div class="absolute left-[13px] top-7 bottom-0 w-0.5 bg-slate-200"></div>` : ''}

                    {{-- Dot --}}
                    <div class="flex-shrink-0 mt-1 z-10">
                        <div class="w-7 h-7 rounded-full border-2 ${dotColor} flex items-center justify-center shadow-sm">
                            <span class="text-[10px] font-black">${idx + 1}</span>
                        </div>
                    </div>

                    {{-- Card --}}
                    <div class="flex-1 pb-4">
                        <div class="p-3.5 rounded-xl border ${cardBg} shadow-sm relative overflow-hidden">
                            ${isLast && !isPaid ? '<div class="absolute top-0 right-0 w-16 h-16 bg-amber-400/10 rounded-bl-full -z-10"></div>' : ''}
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <div>
                                    <p class="text-[10px] font-black ${labelColor} uppercase tracking-wider">
                                        ${entry.title}
                                    </p>
                                    <p class="text-base font-black ${amtColor} tracking-tight">${fmtRp(amount)}</p>
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    ${statusLabel}
                                    ${methodBadge}
                                </div>
                            </div>
                            ${isPaid && idx > 0 ? `
                            <div class="pt-2 border-t border-slate-100 space-y-1">
                                ${paidBy ? `<p class="text-[10px] text-slate-500"><span class="font-bold text-slate-600">${isPiutang ? 'Diterima oleh:' : 'Dibayar oleh:'}</span> ${paidBy}</p>` : ''}
                                <p class="text-[10px] text-slate-400">${paidAtStr}</p>
                            </div>` : (!isPaid ? `
                            <div class="pt-2 border-t border-amber-100/50">
                                <p class="text-[10px] text-amber-500 font-bold">Menunggu ${isPiutang ? 'penerimaan' : 'pembayaran'}</p>
                            </div>` : '')}
                        </div>
                    </div>
                </div>
            `;
        });
    }

    function closeDetailModal() {
        // Allow body scroll
        document.body.classList.remove('modal-open');
        
        const modal = document.getElementById('detail-debt-modal');
        const box = document.getElementById('detail-debt-modal-box');
        modal.classList.add('opacity-0');
        box.classList.remove('scale-100');
        box.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            // Reset loading for next open
            document.getElementById('detail-debt-loading').innerHTML = `
                <svg class="animate-spin h-8 w-8 text-violet-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-slate-400 text-sm font-semibold">Memuat data...</p>
            `;
        }, 300);
    }

    let currentDebtId = null;
    let _cachedBankAccounts = [];
    let _cachedSenderBankAccounts = [];

    // ─── Toggle Metode Pembayaran ─────────────────────────────────
    function setPaymentMethod(method) {
        const isCash = method === 'cash';

        // Toggle label style
        document.getElementById('method-transfer-label').className = `flex items-center gap-2.5 p-3 rounded-xl border-2 cursor-pointer transition-all ${!isCash ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200 bg-white'}`;
        document.getElementById('method-cash-label').className = `flex items-center gap-2.5 p-3 rounded-xl border-2 cursor-pointer transition-all ${isCash ? 'border-emerald-500 bg-emerald-50' : 'border-slate-200 bg-white'}`;

        // Radio dot visual
        const transferDot = document.getElementById('radio-transfer-dot');
        const cashDot = document.getElementById('radio-cash-dot');
        transferDot.className = `w-5 h-5 rounded-full border-2 flex items-center justify-center ${!isCash ? 'border-indigo-500' : 'border-slate-300'}`;
        transferDot.querySelector('div').className = `w-2.5 h-2.5 rounded-full ${!isCash ? 'bg-indigo-500' : 'bg-slate-300 hidden'}`;
        cashDot.className = `w-5 h-5 rounded-full border-2 flex items-center justify-center ${isCash ? 'border-emerald-500' : 'border-slate-300'}`;
        cashDot.querySelector('div').className = `w-2.5 h-2.5 rounded-full ${isCash ? 'bg-emerald-500' : 'bg-slate-300 hidden'}`;

        // Bukti upload: required/optional
        const fileInput = document.getElementById('branch_debt_file_input');
        const requiredStar = document.getElementById('proof-required-star');
        const optionalNote = document.getElementById('proof-optional-note');
        fileInput.required = !isCash;
        if (isCash) {
            requiredStar.style.display = 'none';
            optionalNote.style.display = '';
        } else {
            requiredStar.style.display = '';
            optionalNote.style.display = 'none';
        }

        // Tampilkan / sembunyikan rekening
        const senderContainer = document.getElementById('settle-sender-bank-accounts-container');
        const destContainer = document.getElementById('settle-bank-accounts-container');
        if (isCash) {
            senderContainer.classList.add('hidden');
            destContainer.classList.add('hidden');
        } else {
            senderContainer.classList.remove('hidden');
            destContainer.classList.remove('hidden');
        }
    }

    let currentSettleTotalAmount = 0;

    function setPaymentType(type) {
        const isCicil = type === 'cicil';

        // Toggle label style
        document.getElementById('type-lunas-label').className = `flex items-center gap-2.5 p-3 rounded-xl border-2 cursor-pointer transition-all ${!isCicil ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200 bg-white'}`;
        document.getElementById('type-cicil-label').className = `flex items-center gap-2.5 p-3 rounded-xl border-2 cursor-pointer transition-all ${isCicil ? 'border-amber-500 bg-amber-50' : 'border-slate-200 bg-white'}`;

        // Radio dot visual
        const lunasDot = document.getElementById('radio-lunas-dot');
        const cicilDot = document.getElementById('radio-cicil-dot');
        lunasDot.className = `w-5 h-5 rounded-full border-2 flex items-center justify-center ${!isCicil ? 'border-indigo-500' : 'border-slate-300'}`;
        lunasDot.querySelector('div').className = `w-2.5 h-2.5 rounded-full ${!isCicil ? 'bg-indigo-500' : 'bg-slate-300 hidden'}`;
        cicilDot.className = `w-5 h-5 rounded-full border-2 flex items-center justify-center ${isCicil ? 'border-amber-500' : 'border-slate-300'}`;
        cicilDot.querySelector('div').className = `w-2.5 h-2.5 rounded-full ${isCicil ? 'bg-amber-500' : 'bg-slate-300 hidden'}`;

        const cicilanContainer = document.getElementById('nominal-cicilan-container');
        const amountInput = document.getElementById('amount_paid_input');
        if (isCicil) {
            cicilanContainer.classList.remove('hidden');
            amountInput.required = true;
            document.getElementById('cicilan-warning-text').textContent = `Masukkan nominal cicilan (harus lebih kecil dari Rp ${Number(currentSettleTotalAmount).toLocaleString('id-ID')}).`;
        } else {
            cicilanContainer.classList.add('hidden');
            amountInput.required = false;
            amountInput.value = '';
        }
    }

    function openSettleModal(id, bankAccounts, branchName, senderBankAccounts, senderBranchName, actionUrl, totalAmount) {
        // Close mobile sidebar if open
        closeMobileSidebarIfOpen();
        
        // Prevent body scroll
        document.body.classList.add('modal-open');
        
        currentDebtId = id;
        currentSettleUrl = actionUrl || ('/branch-debts/' + id + '/settle');
        _cachedBankAccounts = bankAccounts;
        _cachedSenderBankAccounts = senderBankAccounts;
        currentSettleTotalAmount = totalAmount || 0;

        document.getElementById('settle-branch-name').textContent = branchName;
        document.getElementById('settle-sender-branch-name').textContent = senderBranchName;

        const modalTitle = document.getElementById('settle-modal-title');
        if (modalTitle) {
            modalTitle.textContent = actionUrl
                ? ('{{ $jenis }}' === 'piutang_usaha' ? 'Cairkan Piutang' : 'Bayar Hutang')
                : ('{{ $jenis }}' === 'piutang_usaha' ? 'Cairkan Piutang Cabang' : 'Bayar Hutang Cabang');
        }
        const btnSubmitText = document.getElementById('btnSubmitBranchDebtText');
        if (btnSubmitText) {
            btnSubmitText.textContent = ('{{ $jenis }}' === 'piutang_usaha') ? 'Konfirmasi Cairkan' : 'Konfirmasi Bayar';
        }

        const select = document.getElementById('settle-bank-accounts-select');
        const detailContainer = document.getElementById('settle-bank-account-detail');
        const senderSelect = document.getElementById('settle-sender-bank-accounts-select');
        const senderDetailContainer = document.getElementById('settle-sender-bank-account-detail');

        detailContainer.classList.add('hidden');
        senderDetailContainer.classList.add('hidden');

        // Reset tipe ke Lunas (default)
        document.querySelector('input[name="payment_type"][value="lunas"]').checked = true;
        document.querySelector('input[name="payment_type"][value="cicil"]').checked = false;
        setPaymentType('lunas');

        // Reset metode ke Transfer (default) setiap kali modal dibuka
        document.querySelector('input[name="payment_method"][value="transfer"]').checked = true;
        document.querySelector('input[name="payment_method"][value="cash"]').checked = false;
        setPaymentMethod('transfer');

        // Reset file input & notes
        document.getElementById('branch_debt_file_input').value = '';
        document.querySelector('#branch-debt-form textarea[name="notes"]').value = '';

        // Populate Destination (Creditor) Accounts
        if (bankAccounts && bankAccounts.length > 0) {
            select.disabled = false;
            select.innerHTML = '<option value="">-- Pilih Rekening Tujuan --</option>';
            bankAccounts.forEach(acc => {
                const opt = document.createElement('option');
                opt.value = acc.id;
                opt.dataset.details = JSON.stringify(acc);
                opt.textContent = `${acc.bank_name} - ${acc.account_number} (${acc.account_name})`;
                select.appendChild(opt);
            });
        } else {
            select.disabled = true;
            select.innerHTML = '<option value="">-- Cabang Tujuan Belum Mendaftarkan Rekening --</option>';
        }

        // Populate Sender (Debtor) Accounts
        if (senderBankAccounts && senderBankAccounts.length > 0) {
            senderSelect.disabled = false;
            senderSelect.innerHTML = '<option value="">-- Pilih Rekening Pengirim --</option>';
            senderBankAccounts.forEach(acc => {
                const opt = document.createElement('option');
                opt.value = acc.id;
                opt.dataset.details = JSON.stringify(acc);
                opt.textContent = `${acc.bank_name} - ${acc.account_number} (${acc.account_name})`;
                senderSelect.appendChild(opt);
            });
        } else {
            senderSelect.disabled = true;
            senderSelect.innerHTML = '<option value="">-- Cabang Sumber Belum Mendaftarkan Rekening --</option>';
        }

        document.getElementById('branch-debt-modal').classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('branch-debt-modal').classList.remove('opacity-0');
            document.getElementById('branch-debt-modal-box').classList.remove('scale-95');
            document.getElementById('branch-debt-modal-box').classList.add('scale-100');
        }, 10);
        if (window.lucide) lucide.createIcons({ root: document.getElementById('branch-debt-modal') });
    }

    // Listener for bank account detail dynamic update
    // ─── Helper: Format & parse angka ribuan (ID) ─────────────────────────
    function formatRibuan(val) {
        // Hapus semua karakter bukan digit
        const raw = String(val).replace(/\D/g, '');
        if (!raw) return '';
        // Tambahkan titik setiap 3 digit dari kanan
        return raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function parseRibuan(val) {
        return parseInt(String(val).replace(/\./g, ''), 10) || 0;
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Payment method radio toggle
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                setPaymentMethod(this.value);
            });
        });

        // Payment type radio toggle
        document.querySelectorAll('input[name="payment_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                setPaymentType(this.value);
            });
        });

        // ── Format ribuan on amount_paid_input ─────────────────────────────
        const amountPaidInput = document.getElementById('amount_paid_input');
        if (amountPaidInput) {
            amountPaidInput.addEventListener('input', function() {
                const cursorPos = this.selectionStart;
                const prevLen   = this.value.length;
                const formatted = formatRibuan(this.value);
                this.value = formatted;
                // Adjust cursor position after re-formatting
                const diff = formatted.length - prevLen;
                try { this.setSelectionRange(cursorPos + diff, cursorPos + diff); } catch(_) {}
            });

            // Hapus format saat focus untuk kemudahan edit (opsional)
            amountPaidInput.addEventListener('blur', function() {
                if (this.value) this.value = formatRibuan(this.value);
            });
        }

        const select = document.getElementById('settle-bank-accounts-select');
        if(select) {
            select.addEventListener('change', function() {
                const detailContainer = document.getElementById('settle-bank-account-detail');
                const selectedOpt = this.options[this.selectedIndex];
                if (this.value && selectedOpt.dataset.details) {
                    const acc = JSON.parse(selectedOpt.dataset.details);
                    document.getElementById('settle-detail-name').textContent = acc.account_name;
                    document.getElementById('settle-detail-number').textContent = acc.account_number;
                    document.getElementById('settle-detail-bank').textContent = acc.bank_name;
                    detailContainer.classList.remove('hidden');
                } else {
                    detailContainer.classList.add('hidden');
                }
            });
        }

        const senderSelect = document.getElementById('settle-sender-bank-accounts-select');
        if(senderSelect) {
            senderSelect.addEventListener('change', function() {
                const detailContainer = document.getElementById('settle-sender-bank-account-detail');
                const selectedOpt = this.options[this.selectedIndex];
                if (this.value && selectedOpt.dataset.details) {
                    const acc = JSON.parse(selectedOpt.dataset.details);
                    document.getElementById('settle-sender-detail-name').textContent = acc.account_name;
                    document.getElementById('settle-sender-detail-number').textContent = acc.account_number;
                    document.getElementById('settle-sender-detail-bank').textContent = acc.bank_name;
                    detailContainer.classList.remove('hidden');
                } else {
                    detailContainer.classList.add('hidden');
                }
            });
        }
    });

    function closeSettleModal() {
        // Allow body scroll
        document.body.classList.remove('modal-open');
        
        const modal = document.getElementById('branch-debt-modal');
        const box = document.getElementById('branch-debt-modal-box');
        modal.classList.add('opacity-0');
        box.classList.remove('scale-100');
        box.classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    function openPaymentProofFromButton(button) {
        // Close mobile sidebar if open
        closeMobileSidebarIfOpen();
        
        try {
            const payload = JSON.parse(button.dataset.proof || '{}');
            openViewProofModal(payload.url || '', payload.title || @json($isPiutang ? 'Detail Penerimaan' : 'Detail Pembayaran'), payload.data || {});
        } catch (error) {
            console.error('Gagal membuka detail pembayaran:', error);
            showToast(@json($isPiutang ? 'Gagal membuka detail penerimaan.' : 'Gagal membuka detail pembayaran.'), 'error');
        }
    }

    function openViewProofModal(url, title, data) {
        // Close mobile sidebar if open
        closeMobileSidebarIfOpen();
        
        // Prevent body scroll
        document.body.classList.add('modal-open');
        
        const modal = document.getElementById('view-proof-modal');
        const box = document.getElementById('view-proof-modal-box');
        const img = document.getElementById('view-proof-img');
        const emptyState = document.getElementById('view-proof-empty');
        const emptyTitle = document.getElementById('view-proof-empty-title');
        const emptySubtitle = document.getElementById('view-proof-empty-subtitle');
        const fileLink = document.getElementById('view-proof-file-link');
        const titleEl = document.getElementById('view-proof-title');

        if (modal && img && emptyState) {
            const proofUrl = url || '';
            const isPdf = proofUrl.toLowerCase().endsWith('.pdf');

            img.classList.add('hidden');
            emptyState.classList.add('hidden');
            fileLink.classList.add('hidden');
            fileLink.removeAttribute('href');

            if (proofUrl && !isPdf) {
                img.src = proofUrl;
                img.classList.remove('hidden');
            } else {
                img.removeAttribute('src');
                emptyState.classList.remove('hidden');
                emptyTitle.textContent = isPdf ? @json($isPiutang ? 'Bukti penerimaan PDF' : 'Bukti pembayaran PDF') : (data?.method || @json($isPiutang ? 'Penerimaan tercatat' : 'Pembayaran tercatat'));
                emptySubtitle.textContent = isPdf
                    ? 'File bukti tersedia dalam format PDF.'
                    : @json($isPiutang ? 'Tidak ada file bukti yang diunggah untuk penerimaan ini.' : 'Tidak ada file bukti yang diunggah untuk pembayaran ini.');

                if (proofUrl && isPdf) {
                    fileLink.href = proofUrl;
                    fileLink.classList.remove('hidden');
                }
            }

            titleEl.textContent = title || @json($isPiutang ? 'Nota Penerimaan' : 'Nota Pembayaran');

            if (data) {
                document.getElementById('view-proof-amount').textContent = data.amount;
                document.getElementById('view-proof-settler').textContent = data.paid_by;
                document.getElementById('view-proof-notes').textContent = data.notes && data.notes !== '-' ? data.notes : (data.method ? `Metode ${@json($isPiutang ? 'penerimaan' : 'pembayaran')}: ${data.method}` : '-');

                const accountsDiv = document.getElementById('view-proof-accounts');
                const senderContainer = document.getElementById('view-proof-sender-container');
                const senderAccountsDiv = document.getElementById('view-proof-sender-account');

                accountsDiv.innerHTML = '';
                senderAccountsDiv.innerHTML = '';

                // Show sender account if exists
                if (data.sender_account) {
                    senderContainer.classList.remove('hidden');
                    const acc = data.sender_account;
                    senderAccountsDiv.innerHTML = `
                        <div class="p-3 rounded-xl border border-slate-100 bg-slate-50 shadow-sm flex items-center justify-between group">
                            <div>
                                <div class="text-[10px] font-black text-rose-600 uppercase tracking-widest mb-1.5">${data.sender_branch || ''}</div>
                                <div class="text-[9px] font-black text-slate-400 uppercase tracking-wider">${acc.bank_name}</div>
                                <div class="text-sm font-black text-slate-800 font-mono tracking-tight">${acc.account_number}</div>
                                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wide">${acc.account_name}</div>
                            </div>
                            <i data-lucide="arrow-up-right" class="w-4 h-4 text-rose-500"></i>
                        </div>
                    `;
                } else if (data.sender_branch && data.sender_branch !== '-') {
                    senderContainer.classList.remove('hidden');
                    senderAccountsDiv.innerHTML = `
                        <div class="p-3 rounded-xl border border-slate-100 bg-slate-50 shadow-sm flex items-center justify-between group">
                            <div>
                                <div class="text-[10px] font-black text-rose-600 uppercase tracking-widest mb-1.5">Pengirim Dana</div>
                                <div class="text-sm font-black text-slate-800 tracking-tight">${data.sender_branch}</div>
                            </div>
                            <i data-lucide="arrow-up-right" class="w-4 h-4 text-rose-500"></i>
                        </div>
                    `;
                } else {
                    senderContainer.classList.add('hidden');
                }

                // If specific account selected, show only that. Else show the first one (legacy)
                const displayAccounts = data.selected_account ? [data.selected_account] : (data.all_accounts && data.all_accounts.length > 0 ? [data.all_accounts[0]] : []);
                const accountLabel = document.getElementById('account-label');
                accountLabel.textContent = 'Rekening Tujuan';
                accountLabel.classList.remove('hidden');

                if (displayAccounts && displayAccounts.length > 0) {
                    displayAccounts.forEach(acc => {
                        const div = document.createElement('div');
                        div.className = "p-3 rounded-xl border border-slate-100 bg-slate-50 shadow-sm flex items-center justify-between group";
                        div.innerHTML = `
                            <div>
                                <div class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-1.5">${data.receiver_branch || ''}</div>
                                <div class="text-[9px] font-black text-slate-400 uppercase tracking-wider">${acc.bank_name}</div>
                                <div class="text-sm font-black text-slate-800 font-mono tracking-tight">${acc.account_number}</div>
                                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wide">${acc.account_name}</div>
                            </div>
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-500"></i>
                        `;
                        accountsDiv.appendChild(div);
                    });
                } else if (data.receiver_branch && data.receiver_branch !== '-') {
                    accountLabel.classList.add('hidden');
                    accountsDiv.innerHTML = `
                        <div class="p-3 rounded-xl border border-slate-100 bg-slate-50 shadow-sm flex items-center justify-between group">
                            <div>
                                <div class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-1.5">Penerima Dana</div>
                                <div class="text-sm font-black text-slate-800 tracking-tight">${data.receiver_branch}</div>
                            </div>
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-500"></i>
                        </div>
                    `;
                }
            }

            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                box.classList.remove('scale-95');
                box.classList.add('scale-100');
            }, 10);
            if (window.lucide) lucide.createIcons({ root: modal });
        }
    }

    function closeViewProofModal() {
        // Allow body scroll
        document.body.classList.remove('modal-open');
        
        const modal = document.getElementById('view-proof-modal');
        const box = document.getElementById('view-proof-modal-box');
        const img = document.getElementById('view-proof-img');
        const emptyState = document.getElementById('view-proof-empty');
        modal.classList.add('opacity-0');
        box.classList.remove('scale-100');
        box.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            if (img) img.removeAttribute('src');
            if (emptyState) emptyState.classList.add('hidden');
        }, 300);
    }

    document.getElementById('branch-debt-form').onsubmit = function(e){
        e.preventDefault();

        // Client-side validation for installment amount
        const paymentType = document.querySelector('input[name="payment_type"]:checked').value;
        if (paymentType === 'cicil') {
            const amountInput = document.getElementById('amount_paid_input');
            // Parse format ribuan (misal: "1.500.000" -> 1500000)
            const val = parseRibuan(amountInput.value);
            if (isNaN(val) || val <= 0) {
                showToast('Nominal cicilan tidak valid.', 'error');
                amountInput.focus();
                return;
            }
            if (val >= currentSettleTotalAmount) {
                showToast(@json($isPiutang ? 'Nominal cicilan harus lebih kecil dari total piutang (pilih Lunas jika ingin menerima penuh).' : 'Nominal cicilan harus lebih kecil dari total hutang (pilih Lunas jika ingin membayar penuh).'), 'error');
                amountInput.focus();
                return;
            }
        }

        const btn = document.getElementById('btnSubmitBranchDebt');
        const loader = document.getElementById('btnSubmitBranchDebtLoader');
        const text = document.getElementById('btnSubmitBranchDebtText');

        btn.disabled = true;
        btn.classList.add('opacity-80', 'cursor-not-allowed');
        loader.classList.remove('hidden');
        text.textContent = 'Memproses...';

        const fd = new FormData(this);
        fd.append('_method','PATCH');

        // Strip titik ribuan dari amount_paid sebelum dikirim ke backend
        if (paymentType === 'cicil') {
            const rawAmount = parseRibuan(document.getElementById('amount_paid_input').value);
            fd.set('amount_paid', rawAmount);
        }

        fetch(currentSettleUrl,{
            method:'POST',
            body:fd,
            headers:{
                'X-CSRF-TOKEN':'{{csrf_token()}}',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw err; });
            }
            return response.json();
        })
        .then(res => {
            if (res.success) {
                showToast(res.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                throw res;
            }
        })
        .catch(err => {
            console.error(err);
            showToast(err.message || @json($isPiutang ? 'Gagal mencairkan piutang. Silakan coba lagi.' : 'Gagal melunaskan hutang. Silakan coba lagi.'), 'error');

            btn.disabled = false;
            btn.classList.remove('opacity-80', 'cursor-not-allowed');
            loader.classList.add('hidden');
            text.textContent = @json($isPiutang ? 'Konfirmasi Cairkan' : 'Konfirmasi Bayar');
        });
    };

    function confirmDeleteRecord(id, invoice) {
        openConfirmModal('deleteRecordModal', {
            title: 'Hapus Record?',
            message: `Anda yakin ingin menghapus record <strong class="text-slate-800">${invoice}</strong>? Tindakan ini tidak dapat dibatalkan.`,
            action: `/pengeluaran-lain/record/${id}`,
            method: 'DELETE',
            submitText: 'Ya, Hapus',
            onConfirm: async () => {
                try {
                    const response = await fetch(`/pengeluaran-lain/record/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{csrf_token()}}',
                            'Accept': 'application/json'
                        }
                    });
                    const result = await response.json();
                    if (response.ok && result.success) {
                        showToast(result.message, 'success');
                        const row = document.getElementById(`record-row-${id}`);
                        if (row) {
                            row.style.opacity = '0';
                            row.style.transform = 'translateX(-10px)';
                            row.style.transition = 'all 0.3s ease';
                            setTimeout(() => row.remove(), 300);
                        }
                    } else {
                        throw new Error(result.message || 'Gagal menghapus record');
                    }
                } catch (err) {
                    showToast(err.message, 'error');
                }
            }
        });
    }

    // ─── Helper: Close Mobile Sidebar ─────────────────────────────────────
    function closeMobileSidebarIfOpen() {
        const sidebar = document.getElementById('mobile-sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        
        if (sidebar && overlay) {
            // Check if sidebar is open (not translated)
            if (!sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }
    }
</script>
@endpush

@push('modals')
<x-confirm-modal id="deleteRecordModal" />
@endpush
@endsection
