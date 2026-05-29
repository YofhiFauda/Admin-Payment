@extends('layouts.app')

@section('page-title', 'Kelola Cabang')

@section('content')
<style>
    .branch-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
    }
    .branch-row { transition: background .15s; }
    .branch-row:hover { background: #f8fafc; }

    /* Dropup state for action dropdown */
    .action-dropdown.dropup {
        bottom: 100%;
        top: auto;
        margin-bottom: 0.5rem;
        margin-top: 0;
        transform-origin: bottom right;
    }
    
    /* Perbaikan Modal Overlay agar selalu paling atas dan center */
    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
        z-index: 99999 !important; 
        background: rgba(15,23,42,.45);
        backdrop-filter: blur(4px);
        display: flex; align-items: center; justify-content: center; padding: 1rem;
    }
    .modal-box {
        background: #fff; border-radius: 20px;
        box-shadow: 0 24px 64px rgba(0,0,0,.18);
        width: 100%; max-width: 440px;
        padding: 2rem; position: relative;
        max-height: 90vh; overflow-y: auto;
    }
    .modal-box h3 { font-size: 1.125rem; font-weight: 800; color: #0f172a; margin-bottom: 1.25rem; }
    .form-label { display: block; font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 6px; }
    .form-input {
        width: 100%; padding: .65rem 1rem; border-radius: 10px;
        border: 1.5px solid #e2e8f0; font-size: .9rem; color: #1e293b;
        transition: border-color .2s, box-shadow .2s; outline: none;
    }
    .form-input:focus { border-color: #0ea5e9; box-shadow: 0 0 0 3px rgba(14,165,233,.12); }
    .btn-primary {
        width: 100%; padding: .7rem; border-radius: 10px; font-weight: 700;
        background: linear-gradient(to right, #0284c7, #0ea5e9); 
        color: #ffffff;
        border: none; cursor: pointer; font-size: .9rem; transition: opacity .2s, transform .1s;
    }
    .btn-primary:hover { opacity: .9; transform: translateY(-1px); }
    .btn-primary:disabled { opacity: .6; cursor: not-allowed; }
    .btn-cancel {
        padding: .5rem 1.25rem; border-radius: 10px; font-weight: 600; font-size: .875rem;
        background: #f1f5f9; color: #64748b; border: none; cursor: pointer; transition: background .2s;
    }
    .btn-cancel:hover { background: #e2e8f0; }
    .error-msg { font-size: .8rem; color: #ef4444; margin-top: 4px; display: none; }
</style>

<div class="p-4 md:p-6 max-w-full overflow-hidden">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-black text-slate-900">Kelola Cabang</h1>
            <p class="text-sm text-slate-500 mt-0.5">Kelola daftar cabang secara dinamis</p>
        </div>
        <button onclick="openAddModal()"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-linear-to-r from-sky-600 to-sky-500 text-white text-sm font-bold shadow-lg hover:opacity-90 transition-all w-full sm:w-auto justify-center">
            <i data-lucide="plus" class="w-4 h-4"></i> Tambah Cabang
        </button>
    </div>

    {{-- Stats bar --}}
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
        <div class="branch-card p-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                <i data-lucide="building-2" class="w-5 h-5 text-blue-600"></i>
            </div>
            <div>
                <p class="text-[10px] sm:text-xs font-semibold text-slate-500 uppercase tracking-wide">Total</p>
                <p class="text-lg sm:text-xl font-black text-slate-900">{{ $branches->total() }}</p>
            </div>
        </div>
        <div class="branch-card p-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
                <i data-lucide="activity" class="w-5 h-5 text-emerald-600"></i>
            </div>
            <div>
                <p class="text-[10px] sm:text-xs font-semibold text-slate-500 uppercase tracking-wide">Aktif</p>
                <p class="text-lg sm:text-xl font-black text-slate-900">{{ $branches->where('transactions_count', '>', 0)->count() }}</p>
            </div>
        </div>
        <div class="branch-card p-4 items-center gap-4 hidden md:flex">
            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center flex-shrink-0">
                <i data-lucide="archive" class="w-5 h-5 text-amber-600"></i>
            </div>
            <div>
                <p class="text-[10px] sm:text-xs font-semibold text-slate-500 uppercase tracking-wide">Tanpa Transaksi</p>
                <p class="text-lg sm:text-xl font-black text-slate-900">{{ $branches->where('transactions_count', 0)->count() }}</p>
            </div>
        </div>
    </div>

    {{-- Table (Desktop Only) --}}
    <div class="branch-card overflow-hidden hidden lg:block">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
            <i data-lucide="list" class="w-4 h-4 text-slate-400"></i>
            <span class="text-sm font-semibold text-slate-600">Daftar Cabang</span>
            <span class="ml-auto text-xs text-slate-400">{{ $branches->total() }} terdaftar</span>
        </div>

        @if($branches->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm whitespace-nowrap" id="branches-table">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/60">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide w-12">No</th>
                        <th class="text-left px-3 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide">Nama Cabang</th>
                        <th class="text-center px-3 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide">Transaksi</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50" id="branches-tbody">
                    @foreach($branches as $i => $branch)
                    <tr class="branch-row" id="branch-row-{{ $branch->id }}">
                        <td class="px-5 py-4 text-slate-400 text-xs">{{ $branches->firstItem() + $i }}</td>
                        <td class="px-3 py-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-linear-to-r from-sky-600 to-sky-500 flex items-center justify-center text-white font-bold text-[11px] flex-shrink-0">
                                    {{ strtoupper(substr($branch->name, 0, 2)) }}
                                </div>
                                <span class="font-semibold text-slate-800 branch-name-{{ $branch->id }}">{{ $branch->name }}</span>
                            </div>
                        </td>
                        <td class="px-3 py-4 text-center">
                            @if($branch->transactions_count > 0)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700">
                                    <i data-lucide="receipt" class="w-3 h-3"></i>
                                    {{ $branch->transactions_count }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-400">
                                    Belum ada
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right relative">
                            <div class="lg:hidden relative inline-block text-left dropdown-wrapper">
                                <button onclick="toggleDropdown(event, {{ $branch->id }})" class="p-2 rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors">
                                    <i data-lucide="more-horizontal" class="w-5 h-5"></i>
                                </button>
                                
                                <div id="dropdown-{{ $branch->id }}" class="action-dropdown hidden absolute right-0 mt-2 w-48 origin-top-right rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 focus:outline-none z-50 p-1.5 border border-slate-100">
                                    <button onclick="openBranchBankAccountsModal({{ $branch->id }}, '{{ addslashes($branch->name) }}')" class="w-full flex items-center gap-3 px-3 py-2.5 text-xs font-bold text-sky-700 hover:bg-sky-50 rounded-xl transition-colors">
                                        <i data-lucide="credit-card" class="w-4 h-4"></i> Rekening
                                    </button>
                                    <button onclick="openEditModal({{ $branch->id }}, '{{ addslashes($branch->name) }}')" class="w-full flex items-center gap-3 px-3 py-2.5 text-xs font-bold text-amber-700 hover:bg-amber-50 rounded-xl transition-colors">
                                        <i data-lucide="pencil" class="w-4 h-4"></i> Edit
                                    </button>
                                    @if($branch->transactions_count === 0)
                                        <button onclick="openDeleteModal({{ $branch->id }}, '{{ addslashes($branch->name) }}')" class="w-full flex items-center gap-3 px-3 py-2.5 text-xs font-bold text-rose-700 hover:bg-rose-50 rounded-xl transition-colors">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i> Hapus
                                        </button>
                                    @else
                                        <span class="w-full flex items-center gap-3 px-3 py-2.5 text-xs font-semibold text-slate-400 rounded-xl cursor-not-allowed" title="Masih ada transaksi">
                                            <i data-lucide="lock" class="w-4 h-4"></i> Terkunci
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Desktop View Buttons --}}
                            <div class="hidden lg:flex items-center justify-end gap-1.5">
                                <button onclick="openBranchBankAccountsModal({{ $branch->id }}, '{{ addslashes($branch->name) }}')"
                                    class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-bold transition-colors">
                                    <i data-lucide="credit-card" class="w-3 h-3"></i> Rekening
                                </button>
                                <button onclick="openEditModal({{ $branch->id }}, '{{ addslashes($branch->name) }}')"
                                    class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 text-xs font-bold transition-colors">
                                    <i data-lucide="pencil" class="w-3 h-3"></i> Edit
                                </button>
                                @if($branch->transactions_count === 0)
                                <button onclick="openDeleteModal({{ $branch->id }}, '{{ addslashes($branch->name) }}')"
                                    class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold transition-colors">
                                    <i data-lucide="trash-2" class="w-3 h-3"></i> Hapus
                                </button>
                                @else
                                <span class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-slate-50 text-slate-300 text-xs font-semibold cursor-not-allowed">
                                    <i data-lucide="lock" class="w-3 h-3"></i> Terkunci
                                </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($branches->hasPages())
        <div class="px-5 py-4 border-t border-slate-100">
            {{ $branches->withQueryString()->links('components.pagination.premium') }}
        </div>
        @endif
        @endif
    </div>

    {{-- Mobile Card List --}}
    <div class="lg:hidden space-y-4">
        @forelse($branches as $branch)
        <div class="branch-card p-5 flex flex-col gap-4 relative overflow-hidden group" id="branch-card-{{ $branch->id }}">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-linear-to-r from-sky-600 to-sky-500 flex items-center justify-center text-white font-black text-sm shrink-0 shadow-lg shadow-sky-600/20">
                        {{ strtoupper(substr($branch->name, 0, 2)) }}
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-black text-slate-800 leading-tight truncate branch-name-{{ $branch->id }}">{{ $branch->name }}</h4>
                        <div class="flex items-center gap-2 mt-1">
                            @if($branch->transactions_count > 0)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 uppercase tracking-wider">
                                    <i data-lucide="receipt" class="w-3 h-3"></i>
                                    {{ $branch->transactions_count }} Transaksi
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-50 text-slate-400 uppercase tracking-wider">
                                    Belum ada transaksi
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 pt-3 border-t border-slate-50">
                <button onclick="openBranchBankAccountsModal({{ $branch->id }}, '{{ addslashes($branch->name) }}')"
                    class="flex-1 flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl bg-sky-50 text-sky-700 text-xs font-bold transition-all active:scale-95 border border-sky-100">
                    <i data-lucide="credit-card" class="w-3.5 h-3.5"></i> Rekening
                </button>
                <button onclick="openEditModal({{ $branch->id }}, '{{ addslashes($branch->name) }}')"
                    class="w-11 h-11 flex items-center justify-center rounded-xl bg-amber-50 text-amber-600 active:scale-95 transition-all border border-amber-100">
                    <i data-lucide="pencil" class="w-4 h-4"></i>
                </button>
                @if($branch->transactions_count === 0)
                <button onclick="openDeleteModal({{ $branch->id }}, '{{ addslashes($branch->name) }}')"
                    class="w-11 h-11 flex items-center justify-center rounded-xl bg-rose-50 text-rose-600 active:scale-95 transition-all border border-rose-100">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
                @else
                <div class="w-11 h-11 flex items-center justify-center rounded-xl bg-slate-50 text-slate-300 border border-slate-100 cursor-not-allowed">
                    <i data-lucide="lock" class="w-4 h-4"></i>
                </div>
                @endif
            </div>
        </div>
        @empty
        <div class="flex flex-col items-center justify-center py-16 text-slate-300 px-4 text-center bg-white rounded-3xl border border-dashed border-slate-200">
            <i data-lucide="building-2" class="w-14 h-14 mb-3"></i>
            <p class="text-sm text-slate-400 font-medium">Belum ada cabang terdaftar</p>
            <button onclick="openAddModal()" class="mt-4 px-6 py-2.5 rounded-xl bg-sky-600 text-white text-sm font-bold hover:bg-sky-700 shadow-lg shadow-sky-600/20 transition-all active:scale-95">
                Tambah Cabang Pertama
            </button>
        </div>
        @endforelse

        @if($branches->hasPages())
        <div class="pt-4">
            {{ $branches->withQueryString()->links('components.pagination.premium') }}
        </div>
        @endif
    </div>
</div>

@push('modals')
{{-- ═══════════════════════════════════════ --}}
{{-- MODAL: Add Branch                       --}}
{{-- ═══════════════════════════════════════ --}}
<div id="modal-add" class="modal-overlay" style="display:none;" onclick="if(event.target===this)closeAddModal()">
    <div class="modal-box">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-sky-50 flex items-center justify-center">
                <i data-lucide="building-2" class="w-5 h-5 text-sky-600"></i>
            </div>
            <h3 class="!mb-0">Tambah Cabang Baru</h3>
        </div>
        <label class="form-label" for="add-name">Nama Cabang</label>
        <input type="text" id="add-name" class="form-input" placeholder="CONTOH: OLT JETIS" autocomplete="off" maxlength="100" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
        <p class="error-msg" id="add-error"></p>
        <div class="flex gap-3 mt-5">
            <button class="btn-cancel" onclick="closeAddModal()">Batal</button>
            <button id="btn-add-submit" class="btn-primary" onclick="submitAdd()">Simpan</button>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════ --}}
{{-- MODAL: Edit Branch                      --}}
{{-- ═══════════════════════════════════════ --}}
<div id="modal-edit" class="modal-overlay" style="display:none;" onclick="if(event.target===this)closeEditModal()">
    <div class="modal-box">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                <i data-lucide="pencil" class="w-5 h-5 text-amber-600"></i>
            </div>
            <h3 class="!mb-0">Edit Nama Cabang</h3>
        </div>
        <input type="hidden" id="edit-id">
        <label class="form-label" for="edit-name">Nama Cabang</label>
        <input type="text" id="edit-name" class="form-input" placeholder="Masukkan nama cabang" autocomplete="off" maxlength="100" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
        <p class="error-msg" id="edit-error"></p>
        <div class="flex gap-3 mt-5">
            <button class="btn-cancel" onclick="closeEditModal()">Batal</button>
            <button id="btn-edit-submit" class="btn-primary" onclick="submitEdit()">Perbarui</button>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════ --}}
{{-- MODAL: Delete Confirm                   --}}
{{-- ═══════════════════════════════════════ --}}
<div id="modal-delete" class="modal-overlay" style="display:none;" onclick="if(event.target===this)closeDeleteModal()">
    <div class="modal-box">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center">
                <i data-lucide="trash-2" class="w-5 h-5 text-rose-600"></i>
            </div>
            <h3 class="!mb-0 text-rose-700">Hapus Cabang?</h3>
        </div>
        <p class="text-sm text-slate-600 mb-1">Anda akan menghapus cabang:</p>
        <p class="font-bold text-slate-900 text-base mb-4" id="delete-name-display"></p>
        <div class="p-3 rounded-xl bg-rose-50 border border-rose-100 text-xs text-rose-700 font-medium mb-4">
            ⚠️ Tindakan ini tidak dapat dibatalkan.
        </div>
        <input type="hidden" id="delete-id">
        <div class="flex gap-3">
            <button class="btn-cancel w-full" onclick="closeDeleteModal()">Batal</button>
            <button id="btn-delete-submit"
                class="w-full py-2.5 rounded-xl font-bold text-sm bg-rose-600 hover:bg-rose-700 text-white transition-colors border-none cursor-pointer"
                onclick="submitDelete()">
                Ya, Hapus
            </button>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════ --}}
{{-- MODAL: Branch Bank Accounts             --}}
{{-- ═══════════════════════════════════════ --}}
<div id="modal-branch-bank-accounts" class="modal-overlay" style="display:none;" onclick="if(event.target===this)closeBranchBankAccountsModal()">
    <div class="bg-white w-full max-w-xl rounded-3xl shadow-2xl transform transition-all duration-300 scale-95 opacity-0 flex flex-col max-h-[95vh] sm:max-h-[90vh]" id="branchBankAccountsModalContent">
            {{-- Header --}}
            <div class="px-6 py-5 sm:px-8 sm:py-6 border-b border-slate-100 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-sky-50 flex items-center justify-center text-sky-600">
                        <i data-lucide="credit-card" class="w-5 h-5 sm:w-6 sm:h-6"></i>
                    </div>
                    <div>
                        <h3 class="text-lg sm:text-xl font-black text-slate-800 leading-tight">Rekening Cabang</h3>
                        <p class="text-xs sm:text-sm font-medium text-slate-500" id="branchAccountTitle">Kelola rekening untuk cabang</p>
                    </div>
                </div>
                <button onclick="closeBranchBankAccountsModal()" class="w-10 h-10 rounded-xl hover:bg-slate-50 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            {{-- List Section --}}
            <div class="flex-1 overflow-y-auto px-6 py-5 sm:px-8 sm:py-6" id="bbaListContainer">
                <div id="bbaLoading" class="flex flex-col items-center justify-center py-12">
                    <div class="w-10 h-10 border-4 border-sky-100 border-t-sky-600 rounded-full animate-spin"></div>
                    <p class="mt-4 text-sm font-bold text-slate-400">Memuat data...</p>
                </div>
                <div id="bbaList" class="space-y-4 hidden">
                    {{-- Dynamically populated --}}
                </div>
                <div id="bbaEmpty" class="hidden flex flex-col items-center justify-center py-12 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-300 mb-4">
                        <i data-lucide="wallet" class="w-8 h-8"></i>
                    </div>
                    <p class="text-slate-500 font-bold">Belum ada rekening tertaut</p>
                    <p class="text-slate-400 text-xs mt-1">Tambahkan rekening untuk transaksi cabang ini</p>
                </div>
            </div>

            {{-- Form Section --}}
            <div id="bbaFormContainer" class="hidden px-6 py-5 sm:px-8 sm:py-6 border-t border-slate-100 bg-slate-50/50">
                <h4 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-4" id="bbaFormTitle">Tambah Rekening</h4>
                <form id="bbaForm" onsubmit="saveBranchBankAccount(event)" class="space-y-4">
                    <input type="hidden" id="bba_id">
                    <input type="hidden" id="bba_branch_id">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Bank / E-Wallet</label>
                            <input type="text" id="bba_bank_name" required placeholder="Contoh: BCA, MANDIRI" 
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/10 transition-all font-bold text-slate-800 placeholder:text-slate-300 uppercase outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Nomor Rekening</label>
                            <input type="text" id="bba_account_number" required placeholder="Nominal angka"
                                inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/10 transition-all font-bold text-slate-800 placeholder:text-slate-300 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Atas Nama</label>
                        <input type="text" id="bba_account_name" required placeholder="Nama pemilik rekening"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/10 transition-all font-bold text-slate-800 placeholder:text-slate-300 uppercase outline-none">
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row items-center gap-3 pt-3">
                        <button type="button" onclick="hideBranchBankAccountForm()" class="w-full sm:w-auto flex-1 px-6 py-3 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-white transition-all">
                            Batal
                        </button>
                        <button type="submit" id="bbaSaveBtn" class="w-full sm:w-auto flex-[2] px-6 py-3 rounded-xl bg-linear-to-r from-sky-600 to-sky-500 text-white font-bold hover:bg-sky-700 shadow-lg shadow-sky-600/20 transition-all flex items-center justify-center gap-2">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            <span>Simpan Rekening</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Footer (Action Buttons) --}}
            <div class="px-6 py-5 sm:px-8 sm:py-6 border-t border-slate-100 flex items-center justify-between shrink-0" id="bbaModalFooter">
                <button type="button" onclick="showBranchBankAccountForm()" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-linear-to-r from-sky-600 to-sky-500 text-white font-bold hover:bg-sky-600 shadow-xl transition-all items-center justify-center gap-2" id="bbaBtnAdd" style="display: {{ Auth::user()->role === 'owner' ? 'flex' : 'none' }}">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Tambah Rekening</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Delete Reason Modal --}}
<div id="bbaDeleteReasonModal" class="modal-overlay" style="display:none;" onclick="if(event.target===this)closeBBADeleteReasonModal()">
    <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl p-6 sm:p-8 text-center sm:text-left">
            <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-rose-50 flex items-center justify-center text-rose-600 mx-auto sm:mx-0 mb-6">
                <i data-lucide="alert-triangle" class="w-7 h-7 sm:w-8 sm:h-8"></i>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-slate-800 mb-2">Konfirmasi Hapus</h3>
            <p class="text-sm sm:text-base text-slate-500 font-medium mb-6">Penghapusan rekening memerlukan alasan singkat.</p>
            
            <form onsubmit="confirmBBADeleteAccount(event)">
                <input type="hidden" id="bba_delete_id">
                <div class="mb-6">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1 text-left">Alasan Penghapusan</label>
                    <textarea id="bba_delete_reason" required placeholder="Contoh: Sudah tidak aktif"
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 transition-all font-bold text-slate-800 placeholder:text-slate-300 min-h-[90px] outline-none"></textarea>
                </div>
                <div class="flex flex-col-reverse sm:flex-row items-center gap-3">
                    <button type="button" onclick="closeBBADeleteReasonModal()" class="w-full sm:w-auto flex-1 px-6 py-3 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition-all">
                        Batal
                    </button>
                    <button type="submit" class="w-full sm:w-auto flex-1 px-6 py-3 rounded-xl bg-rose-600 text-white font-bold hover:bg-rose-700 shadow-lg shadow-rose-600/20 transition-all">
                        Hapus Permanen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;

// ─── Dropdown Script untuk Desktop ───────────────
function toggleDropdown(event, id) {
    event.stopPropagation();
    
    // Tutup dropdown lain yang terbuka
    document.querySelectorAll('.action-dropdown').forEach(el => {
        if (el.id !== 'dropdown-' + id) {
            el.classList.add('hidden');
            el.classList.remove('dropup');
        }
    });
    
    const dropdown = document.getElementById('dropdown-' + id);
    if (!dropdown) return;

    const isHidden = dropdown.classList.contains('hidden');
    
    if(isHidden) {
        // Reset state
        dropdown.classList.remove('hidden');
        dropdown.classList.remove('dropup');
        
        // Detect bottom overflow
        const rect = dropdown.getBoundingClientRect();
        const viewportHeight = window.innerHeight;
        
        if (rect.bottom > viewportHeight - 20) {
            dropdown.classList.add('dropup');
        }
    } else {
        dropdown.classList.add('hidden');
    }
}

// Global click listener untuk menutup dropdown saat klik di luar
document.addEventListener('click', function(event) {
    if (!event.target.closest('.dropdown-wrapper')) {
        document.querySelectorAll('.action-dropdown').forEach(el => {
            el.classList.add('hidden');
            el.classList.remove('dropup');
        });
    }
});

// ─── Add Modal ─────────────────────────────────────────
function openAddModal() {
    document.getElementById('add-name').value = '';
    document.getElementById('add-error').style.display = 'none';
    document.getElementById('modal-add').style.display = 'flex';
    setTimeout(()=>document.getElementById('add-name').focus(), 50);
}
function closeAddModal() { document.getElementById('modal-add').style.display = 'none'; }

async function submitAdd() {
    const name = document.getElementById('add-name').value.trim();
    const errEl = document.getElementById('add-error');
    const btn   = document.getElementById('btn-add-submit');
    if (!name) { errEl.textContent = 'Nama cabang wajib diisi.'; errEl.style.display = 'block'; return; }
    errEl.style.display = 'none';
    btn.disabled = true; btn.textContent = 'Menyimpan...';

    try {
        const res = await fetch('{{ route("branches.store") }}', {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With':'XMLHttpRequest' },
            body: JSON.stringify({ name })
        });
        const data = await res.json();
        if (data.success) {
            closeAddModal();
            showToast(data.message);
            setTimeout(()=>location.reload(), 800);
        } else {
            errEl.textContent = data.message || 'Gagal menyimpan.';
            errEl.style.display = 'block';
        }
    } catch(e) {
        errEl.textContent = 'Terjadi kesalahan. Coba lagi.';
        errEl.style.display = 'block';
    } finally {
        btn.disabled = false; btn.textContent = 'Simpan Cabang';
    }
}

// ─── Edit Modal ────────────────────────────────────────
function openEditModal(id, name) {
    document.getElementById('edit-id').value   = id;
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-error').style.display = 'none';
    document.getElementById('modal-edit').style.display = 'flex';
    setTimeout(()=>document.getElementById('edit-name').focus(), 50);
}
function closeEditModal() { document.getElementById('modal-edit').style.display = 'none'; }

async function submitEdit() {
    const id   = document.getElementById('edit-id').value;
    const name = document.getElementById('edit-name').value.trim();
    const errEl = document.getElementById('edit-error');
    const btn   = document.getElementById('btn-edit-submit');
    if (!name) { errEl.textContent = 'Nama cabang wajib diisi.'; errEl.style.display = 'block'; return; }
    errEl.style.display = 'none';
    btn.disabled = true; btn.textContent = 'Menyimpan...';

    try {
        const res = await fetch(`/branches/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With':'XMLHttpRequest' },
            body: JSON.stringify({ name })
        });
        const data = await res.json();
        if (data.success) {
            closeEditModal();
            const nameEl = document.querySelector(`.branch-name-${id}`);
            if (nameEl) nameEl.textContent = data.branch.name;
            showToast(data.message);
        } else {
            errEl.textContent = data.errors?.name?.[0] || data.message || 'Gagal menyimpan.';
            errEl.style.display = 'block';
        }
    } catch(e) {
        errEl.textContent = 'Terjadi kesalahan. Coba lagi.';
        errEl.style.display = 'block';
    } finally {
        btn.disabled = false; btn.textContent = 'Simpan Perubahan';
    }
}

// ─── Delete Modal ──────────────────────────────────────
function openDeleteModal(id, name) {
    openConfirmModal('globalConfirmModal', {
        title: 'Hapus Cabang?',
        message: `Anda yakin ingin menghapus cabang <strong class="text-slate-900">${name}</strong>?<br><br><span class="p-2.5 rounded-xl bg-rose-50 border border-rose-100 text-[10px] text-rose-700 font-bold block uppercase tracking-wider">⚠️ Tindakan ini tidak dapat dibatalkan.</span>`,
        submitText: 'Ya, Hapus',
        onConfirm: async () => {
            try {
                const res = await fetch(`/branches/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With':'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data.success) {
                    const row = document.getElementById(`branch-row-${id}`);
                    if (row) { 
                        row.style.opacity='0'; 
                        row.style.transform='translateX(-10px)';
                        row.style.transition='all .3s ease'; 
                        setTimeout(()=>row.remove(),300); 
                    }
                    showToast(data.message);
                } else {
                    showToast(data.message, 'error');
                }
            } catch(e) {
                showToast('Terjadi kesalahan.', 'error');
            }
        }
    });
}

// ─── Keyboard Shortcut ───────────────
document.getElementById('add-name').addEventListener('keydown', e => { if(e.key==='Enter') submitAdd(); });
document.getElementById('edit-name').addEventListener('keydown', e => { if(e.key==='Enter') submitEdit(); });

// ─── Slide in animation ────────────────────────────────
const style = document.createElement('style');
style.textContent = '@keyframes slideIn{from{transform:translateX(20px);opacity:0}to{transform:translateX(0);opacity:1}}';
document.head.appendChild(style);

// ─── Branch Bank Accounts Modal Logic ───────────────────
const isOwner = {{ Auth::user()->role === 'owner' ? 'true' : 'false' }};
const bbaModal = document.getElementById('modal-branch-bank-accounts');
const bbaContent = document.getElementById('branchBankAccountsModalContent');

function openBranchBankAccountsModal(branchId, branchName) {
    // Tutup dropdown jika dibuka dari mobile view
    document.querySelectorAll('.action-dropdown').forEach(el => el.classList.add('hidden'));

    document.getElementById('bba_branch_id').value = branchId;
    document.getElementById('branchAccountTitle').textContent = `${branchName}`;
    bbaModal.style.display = 'flex';
    setTimeout(() => {
        bbaContent.classList.remove('scale-95', 'opacity-0');
        bbaContent.classList.add('scale-100', 'opacity-100');
    }, 10);
    fetchBranchBankAccounts(branchId);
}

function closeBranchBankAccountsModal() {
    bbaContent.classList.add('scale-95', 'opacity-0');
    bbaContent.classList.remove('scale-100', 'opacity-100');
    setTimeout(() => {
        bbaModal.style.display = 'none';
        hideBranchBankAccountForm();
    }, 300);
}

function fetchBranchBankAccounts(branchId) {
    const list = document.getElementById('bbaList');
    const loading = document.getElementById('bbaLoading');
    const empty = document.getElementById('bbaEmpty');

    list.classList.add('hidden');
    loading.classList.remove('hidden');
    empty.classList.add('hidden');

    fetch(`/branch-bank-accounts/${branchId}`)
        .then(r => r.json())
        .then(accounts => {
            loading.classList.add('hidden');
            list.innerHTML = '';

            if (accounts.length === 0) {
                empty.classList.remove('hidden');
                return;
            }

            list.classList.remove('hidden');
            accounts.forEach(acc => {
                const actionButtons = isOwner ? `
                    <div class="flex items-center gap-2 scale-90 sm:scale-100 opacity-100 sm:opacity-0 group-hover:opacity-100 transition-opacity">
                        <button onclick='showBranchBankAccountForm(${JSON.stringify(acc)})' class="w-9 h-9 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center hover:bg-sky-100 transition-all">
                            <i data-lucide="edit-2" class="w-4 h-4"></i>
                        </button>
                        <button onclick="deleteBranchBankAccount(${acc.id})" class="w-9 h-9 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center hover:bg-rose-100 transition-all">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                ` : '';

                const card = `
                    <div class="group bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 hover:border-sky-500 hover:shadow-xl hover:shadow-sky-500/5 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3 sm:gap-4">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-slate-50 text-slate-400 group-hover:bg-sky-50 group-hover:text-sky-600 flex items-center justify-center transition-colors shadow-inner flex-shrink-0">
                                <i data-lucide="landmark" class="w-5 h-5 sm:w-6 sm:h-6"></i>
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                                    <span class="text-[9px] sm:text-[10px] font-black bg-slate-800 text-white px-1.5 py-0.5 rounded uppercase tracking-widest">${acc.bank_name}</span>
                                    <h4 class="text-xs sm:text-sm font-black text-slate-800 tracking-tight">${acc.account_number}</h4>
                                </div>
                                <p class="text-[10px] sm:text-xs font-bold text-slate-400 mt-0.5 sm:mt-1 uppercase">${acc.account_name}</p>
                            </div>
                        </div>
                        <div class="flex justify-end border-t border-slate-50 sm:border-0 pt-2 sm:pt-0 mt-2 sm:mt-0">
                            ${actionButtons}
                        </div>
                    </div>
                `;
                list.insertAdjacentHTML('beforeend', card);
            });
            lucide.createIcons();
        });
}

function showBranchBankAccountForm(data = null) {
    if (!isOwner) return;

    const container = document.getElementById('bbaFormContainer');
    const footer = document.getElementById('bbaModalFooter');
    const list = document.getElementById('bbaListContainer');
    const title = document.getElementById('bbaFormTitle');
    const submitBtnText = document.querySelector('#bbaSaveBtn span');

    document.getElementById('bba_id').value = data ? data.id : '';
    document.getElementById('bba_bank_name').value = data ? data.bank_name : '';
    document.getElementById('bba_account_number').value = data ? data.account_number : '';
    document.getElementById('bba_account_name').value = data ? data.account_name : '';

    title.textContent = data ? 'Edit Rekening' : 'Tambah Rekening Baru';
    submitBtnText.textContent = data ? 'Update Rekening' : 'Simpan Rekening';

    list.classList.add('hidden');
    footer.classList.add('hidden');
    container.classList.remove('hidden');
}

function hideBranchBankAccountForm() {
    document.getElementById('bbaFormContainer').classList.add('hidden');
    document.getElementById('bbaModalFooter').classList.remove('hidden');
    document.getElementById('bbaListContainer').classList.remove('hidden');
    document.getElementById('bbaForm').reset();
}

function saveBranchBankAccount(e) {
    e.preventDefault();
    const id = document.getElementById('bba_id').value;
    const branchId = document.getElementById('bba_branch_id').value;
    const url = id ? `/branch-bank-accounts/${id}` : '/branch-bank-accounts';
    const method = id ? 'PUT' : 'POST';

    const btn = document.getElementById('bbaSaveBtn');
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>`;

    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            branch_id: branchId,
            bank_name: document.getElementById('bba_bank_name').value,
            account_number: document.getElementById('bba_account_number').value,
            account_name: document.getElementById('bba_account_name').value
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            hideBranchBankAccountForm();
            fetchBranchBankAccounts(branchId);
            showToast(res.message);
        } else {
            showToast(res.message || 'Gagal menyimpan rekening', 'error');
        }
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalContent;
        lucide.createIcons();
    });
}

function deleteBranchBankAccount(id) {
    if (!isOwner) return;
    document.getElementById('bba_delete_id').value = id;
    document.getElementById('bbaDeleteReasonModal').style.display = 'flex';
}

function closeBBADeleteReasonModal() {
    document.getElementById('bbaDeleteReasonModal').style.display = 'none';
    document.getElementById('bba_delete_reason').value = '';
}

function confirmBBADeleteAccount(e) {
    e.preventDefault();
    const id = document.getElementById('bba_delete_id').value;
    const reason = document.getElementById('bba_delete_reason').value;
    const branchId = document.getElementById('bba_branch_id').value;

    fetch(`/branch-bank-accounts/${id}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ reason })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            closeBBADeleteReasonModal();
            fetchBranchBankAccounts(branchId);
            showToast(res.message);
        } else {
            showToast('Gagal menghapus rekening: ' + res.message, 'error');
        }
    });
}
</script>
@endpush
@endsection
