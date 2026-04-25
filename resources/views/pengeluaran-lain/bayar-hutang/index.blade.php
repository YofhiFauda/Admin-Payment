@extends('layouts.app')

@section('page-title', 'Bayar Hutang')

@section('content')
<div class="p-4 md:p-6 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-lg bg-gradient-to-br from-red-500 to-rose-600">
                <i data-lucide="credit-card" class="w-6 h-6 text-white"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-slate-900">Bayar Hutang</h1>
                <p class="text-sm text-slate-500 mt-0.5">Daftar semua transaksi Pembayaran Hutang</p>
            </div>
        </div>
        <a href="{{ route('pengeluaran-lain.bayar-hutang.create') }}"
            class="flex items-center gap-2 px-5 py-3 rounded-xl bg-slate-900 text-white font-bold text-sm hover:bg-slate-800 transition-all shadow-lg">
            <i data-lucide="plus" class="w-4 h-4"></i> Tambah Bayar Hutang
        </a>
    </div>

    {{-- Flash notification --}}
    @if(session('notification'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-3.5 rounded-xl text-sm font-semibold flex items-center gap-3">
        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500 flex-shrink-0"></i>
        {{ session('notification') }}
    </div>
    @endif

    {{-- Table Utama --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h3 class="font-bold text-slate-800">Riwayat Pembayaran Hutang</h3>
            
            {{-- Filter Table Utama --}}
            <form action="{{ route('pengeluaran-lain.bayar-hutang.index') }}" method="GET" class="flex flex-wrap items-center gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No. INV..." 
                    class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all outline-none w-32 md:w-40">
                
                <select name="branch_id" onchange="this.form.submit()" 
                    class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all outline-none">
                    <option value="">Semua Cabang</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>

                @if(request('search') || request('branch_id'))
                    <a href="{{ route('pengeluaran-lain.bayar-hutang.index') }}" class="text-slate-400 hover:text-red-500 transition-colors">
                        <i data-lucide="x-circle" class="w-4 h-4"></i>
                    </a>
                @endif
                <button type="submit" class="hidden">Filter</button>
            </form>
        </div>
        @if($items->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <div class="w-16 h-16 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-300 mb-4">
                    <i data-lucide="credit-card" class="w-8 h-8"></i>
                </div>
                <p class="text-slate-500 font-bold">Belum ada data</p>
                <p class="text-slate-400 text-xs mt-1">Klik "Tambah Bayar Hutang" untuk memulai</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-5 py-3.5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">No. Invoice</th>
                            <th class="px-5 py-3.5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-5 py-3.5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Cabang</th>
                            <th class="px-5 py-3.5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Nominal</th>
                            <th class="px-5 py-3.5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Keterangan</th>
                            <th class="px-5 py-3.5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Input Oleh</th>
                            <th class="px-5 py-3.5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach($items as $item)
                        <tr class="hover:bg-slate-51 transition-colors" id="record-row-{{ $item->id }}">
                            <td class="px-5 py-4">
                                <span class="font-mono font-bold text-slate-700 bg-slate-100 px-2 py-1 rounded-lg">{{ $item->invoice_number }}</span>
                            </td>
                            <td class="px-5 py-4 font-semibold text-slate-700">
                                {{ $item->tanggal instanceof \Carbon\Carbon ? $item->tanggal->translatedFormat('d F Y') : $item->tanggal }}
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                <span class="font-semibold">{{ $item->branch->name ?? '-' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="font-bold text-slate-800">{{ $item->formatted_nominal }}</span>
                            </td>
                            <td class="px-5 py-4 text-slate-500 max-w-[200px] truncate">{{ $item->keterangan ?? '-' }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $item->submitter->name ?? '-' }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    @if($item->bukti_transfer)
                                    <button type="button" 
                                        onclick='openViewProofModal("{{ asset('storage/' . $item->bukti_transfer) }}", "Bukti Pembayaran {{ $item->invoice_number }}", {
                                            amount: "{{ $item->formatted_nominal }}",
                                            notes: "{{ $item->keterangan ?? '-' }}",
                                            paid_by: "{{ $item->submitter->name ?? '-' }}",
                                            paid_at: "{{ $item->tanggal instanceof \Carbon\Carbon ? $item->tanggal->translatedFormat('d F Y') : $item->tanggal }}",
                                            selected_account: null,
                                            all_accounts: [],
                                            sender_branch: "{{ $item->dariBranch->name ?? '-' }}",
                                            receiver_branch: "{{ $item->branch->name ?? '-' }}"
                                        })'
                                        class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-100 transition-all" title="Lihat Bukti">
                                        <i data-lucide="image" class="w-4 h-4"></i>
                                    </button>
                                    @endif
                                    @if($item->status === 'pending')
                                    <button type="button" 
                                            onclick="confirmDeleteRecord({{ $item->id }}, '{{ $item->invoice_number }}')"
                                            class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center hover:bg-rose-100 transition-all" title="Hapus">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
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
                {{ $items->links() }}
            </div>
            @endif
        @endif
    </div>

    {{-- Hutang Antar Cabang --}}
    @if(isset($branchDebts))
    <div class="mt-10">
        <div class="mb-4 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div class="flex-1">
                <h2 class="text-xl font-black text-slate-900">
                    Hutang Antar Cabang
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
                    <form action="{{ route('pengeluaran-lain.bayar-hutang.index') }}" method="GET" class="flex items-center gap-2">
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
                            <a href="{{ route('pengeluaran-lain.bayar-hutang.index', request()->except(['debt_search', 'debt_branch_id'])) }}" class="text-slate-400 hover:text-indigo-500 transition-colors">
                                <i data-lucide="x-circle" class="w-4 h-4"></i>
                            </a>
                        @endif
                        <button type="submit" class="hidden">Filter</button>
                    </form>
                </div>
            </div>
            <div class="px-3 py-1 bg-indigo-50 text-indigo-700 font-bold text-[10px] rounded-full border border-indigo-200 uppercase tracking-wider h-fit">Data Real-time</div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            @if($branchDebts->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <i data-lucide="credit-card" class="w-8 h-8 text-slate-200 mb-2"></i>
                    <p class="text-slate-500 font-bold text-sm">Data hutang tidak ditemukan</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-xs">
                                <th class="px-5 py-3.5 text-left font-black text-slate-500 uppercase tracking-wider">Terkait Transaksi</th>
                                <th class="px-5 py-3.5 text-left font-black text-slate-500 uppercase tracking-wider">Tanggal</th>
                                <th class="px-5 py-3.5 text-left font-black text-slate-500 uppercase tracking-wider">Keterangan</th>
                                <th class="px-5 py-3.5 text-left font-black text-slate-500 uppercase tracking-wider">Nominal</th>
                                <th class="px-5 py-3.5 text-left font-black text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="px-5 py-3.5 text-center font-black text-slate-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            @foreach($branchDebts as $debt)
                            <tr class="hover:bg-slate-50 transition-colors {{ $debt->status === 'paid' ? 'opacity-70' : '' }}">
                                <td class="px-5 py-4 align-top"><span class="font-mono font-bold bg-slate-100 px-2 py-1 rounded-lg">{{ $debt->transaction->invoice_number ?? '-' }}</span></td>
                                <td class="px-5 py-4 align-top font-bold">
                                    {{ $debt->created_at->translatedFormat('d F Y') }}
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <div class="">
                                        <span class="bg-{{ $debt->status === 'paid' ? 'emerald' : 'red' }}-100 text-{{ $debt->status === 'paid' ? 'emerald' : 'red' }}-700  font-bold px-2 py-0.5 rounded-full border border-{{ $debt->status === 'paid' ? 'emerald' : 'red' }}-200">{{ $debt->debtorBranch->name ?? '-' }}</span> 
                                        <span class="text-slate-500">ke</span> 
                                        <span class="font-bold text-slate-700">{{ $debt->creditorBranch->name ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 align-top"><span class="font-bold text-red-600">{{ $debt->formatted_amount }}</span></td>
                                <td class="px-5 py-4 align-top">
                                    <span class="bg-{{ $debt->status === 'paid' ? 'emerald' : 'red' }}-100 text-{{ $debt->status === 'paid' ? 'emerald' : 'red' }}-700 text-[12px] font-bold px-2 py-0.5 rounded-full border border-{{ $debt->status === 'paid' ? 'emerald' : 'red' }}-200">
                                        {{ $debt->status === 'paid' ? 'Sudah Lunas' : 'Belum Lunas' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center align-top">
                                    @if($debt->status === 'pending')
                                        <button type="button" 
                                            onclick='openSettleModal({{ $debt->id }}, {{ json_encode($debt->creditorBranch->bankAccounts) }}, "{{ $debt->creditorBranch->name }}", {{ json_encode($debt->debtorBranch->bankAccounts) }}, "{{ $debt->debtorBranch->name }}")' 
                                            class="px-4 py-1.5 bg-red-600 text-white rounded-lg text-xs font-bold hover:bg-red-700 transition-all shadow-sm">
                                            Bayar
                                        </button>
                                    @elseif($debt->status === 'paid' && $debt->payment_proof)
                                        <button type="button"
                                            onclick='openViewProofModal("{{ asset('storage/' . $debt->payment_proof) }}", "Bukti Pembayaran Hutang {{ $debt->transaction->invoice_number ?? '' }}", {
                                                amount: "{{ $debt->formatted_amount }}",
                                                notes: "{{ $debt->notes ?? '-' }}",
                                                paid_by: "{{ $debt->paidBy->name ?? 'System' }}",
                                                paid_at: "{{ $debt->paid_at->translatedFormat('d F Y H:i') }}",
                                                selected_account: {{ $debt->bankAccount ? json_encode($debt->bankAccount) : 'null' }},
                                                sender_account: {{ $debt->senderBankAccount ? json_encode($debt->senderBankAccount) : 'null' }},
                                                all_accounts: {{ json_encode($debt->creditorBranch->bankAccounts) }},
                                                sender_branch: "{{ $debt->debtorBranch->name ?? '-' }}",
                                                receiver_branch: "{{ $debt->creditorBranch->name ?? '-' }}"
                                            })' 
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-emerald-600 hover:bg-emerald-50 hover:border-emerald-100 transition-all shadow-sm">
                                            <i data-lucide="image" class="w-3.5 h-3.5"></i> Bukti
                                        </button>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($branchDebts->hasPages())
                <div class="px-5 py-4 border-t border-slate-100">
                    {{ $branchDebts->links('pagination::tailwind', ['pageName' => 'branch_debts']) }}
                </div>
                @endif
            @endif
        </div>
    </div>
    @endif
</div>

{{-- Modal View Proof --}}
<div id="view-proof-modal" class="hidden fixed inset-0 z-[100] bg-slate-900/60 backdrop-blur-md flex items-center justify-center p-4 opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden transform scale-95 transition-transform duration-300 flex flex-col md:flex-row" id="view-proof-modal-box">
        {{-- Left: Image --}}
        <div class="w-full md:w-1/2 bg-slate-50 flex items-center justify-center p-4 border-b md:border-b-0 md:border-r border-slate-100">
            <img id="view-proof-img" src="" class="max-w-full max-h-[70vh] rounded-xl shadow-lg border">
        </div>
        
        {{-- Right: Details --}}
        <div class="w-full md:w-1/2 flex flex-col bg-white">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-black text-slate-800 text-lg" id="view-proof-title">Bukti Pembayaran Hutang</h3>
                <button type="button" onclick="closeViewProofModal()" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-200 text-slate-400"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            
            <div class="p-6 space-y-6 flex-grow overflow-y-auto max-h-[60vh]">
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="block text-[10px] font-black text-slate-400 uppercase mb-1">Nominal Bayar</span>
                        <span id="view-proof-amount" class="text-base font-black text-emerald-600 tracking-tight"></span>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="block text-[10px] font-black text-slate-400 uppercase mb-1">Dibayarkan Oleh</span>
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

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                <button type="button" onclick="closeViewProofModal()" class="px-6 py-2 rounded-xl bg-slate-900 text-white font-bold text-sm shadow-md hover:bg-slate-800 transition-all">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div id="branch-debt-modal" class="hidden fixed inset-0 z-[80] bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden transform scale-95 transition-transform duration-300" id="branch-debt-modal-box">
        <form id="branch-debt-form" method="POST" enctype="multipart/form-data">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center text-red-600"><i data-lucide="credit-card" class="w-5 h-5"></i></div>
                    <h3 class="font-black text-slate-800 text-lg">Bayar Hutang Cabang</h3>
                </div>
                <button type="button" onclick="closeSettleModal()" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-200 text-slate-400"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-6 space-y-5">
                <div class="pt-2">
                    <label class="block text-xs font-black text-slate-700 uppercase mb-2 px-1 text-emerald-600 flex items-center gap-1.5">
                        <i data-lucide="image" class="w-3.5 h-3.5"></i>
                        Upload Bukti Transfer <span class="text-red-500">*</span>
                    </label>
                    <input type="file" name="payment_proof" id="branch_debt_file_input" required accept="image/jpeg,image/png" 
                        class="w-full text-sm border-2 border-slate-100 p-2 rounded-xl focus:border-red-500 focus:ring-0 transition-all">
                </div>

                <div id="settle-sender-bank-accounts-container" class="hidden mb-4 pb-4 border-b border-slate-100">
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-2 px-1">Pilih Rekening Pengirim (Sumber: <span id="settle-sender-branch-name"></span>)</label>
                    <select name="sender_bank_account_id" id="settle-sender-bank-accounts-select" required 
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

                <div id="settle-bank-accounts-container" class="hidden">
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-2 px-1">Pilih Rekening Tujuan (<span id="settle-branch-name"></span>)</label>
                    <select name="bank_account_id" id="settle-bank-accounts-select" required 
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
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                <button type="button" onclick="closeSettleModal()" class="px-5 py-2.5 rounded-xl font-bold text-sm text-slate-600">Batal</button>
                <button type="submit" id="btnSubmitBranchDebt" class="px-5 py-2.5 rounded-xl bg-red-600 text-white font-bold text-sm flex items-center justify-center gap-2 transition-all">
                    <span id="btnSubmitBranchDebtText">Konfirmasi Bayar</span>
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

<script>
    let currentDebtId = null;

    function openSettleModal(id, bankAccounts, branchName, senderBankAccounts, senderBranchName) { 
        currentDebtId = id; 
        document.getElementById('settle-branch-name').textContent = branchName;
        document.getElementById('settle-sender-branch-name').textContent = senderBranchName;
        
        const select = document.getElementById('settle-bank-accounts-select');
        const container = document.getElementById('settle-bank-accounts-container');
        const detailContainer = document.getElementById('settle-bank-account-detail');
        
        const senderSelect = document.getElementById('settle-sender-bank-accounts-select');
        const senderContainer = document.getElementById('settle-sender-bank-accounts-container');
        const senderDetailContainer = document.getElementById('settle-sender-bank-account-detail');
        
        detailContainer.classList.add('hidden'); // Reset details
        container.classList.remove('hidden');    // Selalu tampilkan container
        senderDetailContainer.classList.add('hidden');
        senderContainer.classList.remove('hidden');

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
    }

    // Listener for bank account detail dynamic update
    document.addEventListener('DOMContentLoaded', function() {
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
        const modal = document.getElementById('branch-debt-modal');
        const box = document.getElementById('branch-debt-modal-box');
        modal.classList.add('opacity-0'); 
        box.classList.remove('scale-100');
        box.classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 300); 
    }

    function openViewProofModal(url, title, data) {
        const modal = document.getElementById('view-proof-modal');
        const box = document.getElementById('view-proof-modal-box');
        const img = document.getElementById('view-proof-img');
        const titleEl = document.getElementById('view-proof-title');
        
        if (modal && img) {
            img.src = url; 
            titleEl.textContent = title || 'Nota Pembayaran';
            
            if (data) {
                document.getElementById('view-proof-amount').textContent = data.amount;
                document.getElementById('view-proof-settler').textContent = data.paid_by;
                document.getElementById('view-proof-notes').textContent = data.notes;
                
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
        const modal = document.getElementById('view-proof-modal');
        const box = document.getElementById('view-proof-modal-box');
        modal.classList.add('opacity-0'); 
        box.classList.remove('scale-100');
        box.classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 300); 
    }

    document.getElementById('branch-debt-form').onsubmit = function(e){
        e.preventDefault();
        
        const btn = document.getElementById('btnSubmitBranchDebt');
        const loader = document.getElementById('btnSubmitBranchDebtLoader');
        const text = document.getElementById('btnSubmitBranchDebtText');

        btn.disabled = true;
        btn.classList.add('opacity-80', 'cursor-not-allowed');
        loader.classList.remove('hidden');
        text.textContent = 'Memproses...';

        const fd = new FormData(this); 
        fd.append('_method','PATCH');

        fetch('/branch-debts/'+currentDebtId+'/settle',{
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
            showToast(err.message || 'Gagal melunaskan hutang. Silakan coba lagi.', 'error');
            
            btn.disabled = false;
            btn.classList.remove('opacity-80', 'cursor-not-allowed');
            loader.classList.add('hidden');
            text.textContent = 'Konfirmasi Bayar';
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
</script>

<x-confirm-modal id="deleteRecordModal" />
@endsection
