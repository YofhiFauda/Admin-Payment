@extends('layouts.app')

@php
    $hideHeader = true;
@endphp

@section('content')
<div class="max-w-7xl mx-auto px-4 md:px-6 lg:px-8 py-8 transition-all duration-500 page-enter">

    {{-- Page Header & Stats --}}
    <div class="mb-10 flex flex-col lg:flex-row lg:items-end justify-between gap-6">
        <div class="space-y-1">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-2xl bg-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-200">
                    <i data-lucide="tags" class="w-5 h-5 text-white"></i>
                </div>
                <h1 class="text-3xl font-black text-slate-800 tracking-tight">Kelola Kategori</h1>
            </div>
            <p class="text-slate-400 font-medium max-w-lg">Strukturkan pengeluaran Anda dengan kategori yang tepat untuk laporan keuangan yang lebih akurat.</p>
        </div>

        {{-- Quick Stats Row --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div class="bg-white/60 backdrop-blur-sm border border-slate-200/60 p-4 rounded-2xl flex flex-col">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total Kategori</span>
                <span class="text-xl font-black text-slate-800">{{ $allRembush->count() + $allPengajuan->count() }}</span>
            </div>
            <div class="bg-emerald-50/50 border border-emerald-100 p-4 rounded-2xl flex flex-col">
                <span class="text-[10px] font-bold text-emerald-600/60 uppercase tracking-widest mb-1">Rembush</span>
                <span class="text-xl font-black text-emerald-600">{{ $allRembush->count() }}</span>
            </div>
            <div class="bg-blue-50/50 border border-blue-100 p-4 rounded-2xl flex flex-col">
                <span class="text-[10px] font-bold text-blue-600/60 uppercase tracking-widest mb-1">Pengajuan</span>
                <span class="text-xl font-black text-blue-600">{{ $allPengajuan->count() }}</span>
            </div>
        </div>
    </div>

    {{-- Main Grid --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">

        {{-- ══ SECTION: REMBUSH ══ --}}
        <div class="flex flex-col h-full">
            <div class="flex items-center justify-between mb-4 px-2">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-6 bg-emerald-500 rounded-full"></div>
                    <h2 class="font-black text-slate-800 tracking-tight">Reimbursement (Rembush)</h2>
                </div>
                <button type="button" onclick="openAddModal('rembush')"
                    class="group flex items-center gap-2 bg-white hover:bg-emerald-600 text-emerald-600 hover:text-white border border-emerald-100 px-4 py-2 rounded-xl text-xs font-black transition-all shadow-sm hover:shadow-emerald-200">
                    <i data-lucide="plus" class="w-4 h-4 transition-transform group-hover:rotate-90"></i>
                    TAMBAH
                </button>
            </div>

            <div class="bg-white border border-slate-200/60 rounded-[2.5rem] shadow-sm flex flex-col flex-1 overflow-hidden transition-all hover:shadow-md">
                {{-- Search/Filter Bar --}}
                <div class="p-5 border-b border-slate-50 bg-slate-50/30">
                    <div class="relative group">
                        <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 group-focus-within:text-emerald-500 transition-colors"></i>
                        <input type="text" placeholder="Cari kategori rembush..."
                            onkeyup="filterCategories('rembush', this.value)"
                            class="w-full bg-white border border-slate-200 rounded-2xl pl-11 pr-4 py-3 text-sm font-medium text-slate-600 focus:ring-2 focus:ring-emerald-500/5 focus:border-emerald-500 outline-none transition-all placeholder:text-slate-300">
                    </div>
                </div>

                {{-- Scrollable List --}}
                <div class="p-4 space-y-3 max-h-[550px] overflow-y-auto custom-scrollbar" id="rembush-list">
                    @forelse($allRembush as $cat)
                    <div class="category-item group flex items-center justify-between gap-4 p-4 rounded-3xl border {{ $cat->is_active ? 'border-slate-100 bg-white hover:border-emerald-200 hover:shadow-lg hover:shadow-emerald-500/5' : 'border-slate-50 bg-slate-50/50 opacity-60' }} transition-all duration-300"
                        data-id="{{ $cat->id }}" data-type="rembush" data-name="{{ $cat->name }}">
                        <div class="flex items-center gap-4 min-w-0">
                            <div class="w-12 h-12 rounded-2xl flex-shrink-0 flex items-center justify-center {{ $cat->is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-200 text-slate-400' }} transition-colors">
                                <i data-lucide="bookmark" class="w-5 h-5"></i>
                            </div>
                            <div class="min-w-0">
                                <span class="block text-sm font-black text-slate-700 leading-tight truncate px-1">{{ $cat->name }}</span>
                                <span class="text-[10px] font-bold {{ $cat->is_active ? 'text-emerald-500' : 'text-slate-400' }} uppercase tracking-widest px-1">
                                    {{ $cat->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5 transition-opacity">
                            <button type="button"
                                onclick="openEditModal({{ $cat->id }}, '{{ addslashes($cat->name) }}', 'rembush')"
                                class="w-9 h-9 flex items-center justify-center rounded-xl bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white transition-all shadow-sm"
                                title="Edit Kategori">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            <button type="button"
                                onclick="toggleActive({{ $cat->id }}, this)"
                                class="w-9 h-9 flex items-center justify-center rounded-xl {{ $cat->is_active ? 'bg-amber-100 text-amber-600 hover:bg-amber-600' : 'bg-emerald-100 text-emerald-600 hover:bg-emerald-600' }} hover:text-white transition-all shadow-sm"
                                title="{{ $cat->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                <i data-lucide="{{ $cat->is_active ? 'eye-off' : 'eye' }}" class="w-4 h-4"></i>
                            </button>
                            <button type="button"
                                onclick="deleteCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}')"
                                class="w-9 h-9 flex items-center justify-center rounded-xl bg-red-100 text-red-600 hover:bg-red-600 hover:text-white transition-all shadow-sm"
                                title="Hapus Kategori">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-20 px-4">
                        <div class="w-20 h-20 bg-slate-50 rounded-[2rem] flex items-center justify-center mx-auto mb-4 border-2 border-dashed border-slate-200">
                            <i data-lucide="package-search" class="w-10 h-10 text-slate-200"></i>
                        </div>
                        <h3 class="font-black text-slate-800 mb-1">Belum Ada Kategori</h3>
                        <p class="text-xs text-slate-400 font-medium">Klik tombol tambah untuk membuat kategori pertama Anda.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ══ SECTION: PENGAJUAN ══ --}}
        <div class="flex flex-col h-full">
            <div class="flex items-center justify-between mb-4 px-2">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-6 bg-blue-500 rounded-full"></div>
                    <h2 class="font-black text-slate-800 tracking-tight">Pengajuan Pembelian</h2>
                </div>
                <button type="button" onclick="openAddModal('pengajuan')"
                    class="group flex items-center gap-2 bg-white hover:bg-blue-600 text-blue-600 hover:text-white border border-blue-100 px-4 py-2 rounded-xl text-xs font-black transition-all shadow-sm hover:shadow-blue-200">
                    <i data-lucide="plus" class="w-4 h-4 transition-transform group-hover:rotate-90"></i>
                    TAMBAH
                </button>
            </div>

            <div class="bg-white border border-slate-200/60 rounded-[2.5rem] shadow-sm flex flex-col flex-1 overflow-hidden transition-all hover:shadow-md">
                {{-- Search/Filter Bar --}}
                <div class="p-5 border-b border-slate-50 bg-slate-50/30">
                    <div class="relative group">
                        <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 group-focus-within:text-blue-500 transition-colors"></i>
                        <input type="text" placeholder="Cari kategori pengajuan..."
                            onkeyup="filterCategories('pengajuan', this.value)"
                            class="w-full bg-white border border-slate-200 rounded-2xl pl-11 pr-4 py-3 text-sm font-medium text-slate-600 focus:ring-4 focus:ring-blue-500/5 focus:border-blue-500 outline-none transition-all placeholder:text-slate-300">
                    </div>
                </div>

                {{-- Scrollable List --}}
                <div class="p-4 space-y-3 max-h-[550px] overflow-y-auto custom-scrollbar" id="pengajuan-list">
                    @forelse($allPengajuan as $cat)
                    <div class="category-item group flex items-center justify-between gap-4 p-4 rounded-3xl border {{ $cat->is_active ? 'border-slate-100 bg-white hover:border-blue-200 hover:shadow-lg hover:shadow-blue-500/5' : 'border-slate-50 bg-slate-50/50 opacity-60' }} transition-all duration-300"
                        data-id="{{ $cat->id }}" data-type="pengajuan" data-name="{{ $cat->name }}">
                        <div class="flex items-center gap-4 min-w-0">
                            <div class="w-12 h-12 rounded-2xl flex-shrink-0 flex items-center justify-center {{ $cat->is_active ? 'bg-blue-50 text-blue-600' : 'bg-slate-200 text-slate-400' }} transition-colors">
                                <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                            </div>
                            <div class="min-w-0">
                                <span class="block text-sm font-black text-slate-700 leading-tight truncate px-1">{{ $cat->name }}</span>
                                <span class="text-[10px] font-bold {{ $cat->is_active ? 'text-blue-500' : 'text-slate-400' }} uppercase tracking-widest px-1">
                                    {{ $cat->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5 transition-opacity">
                            <button type="button"
                                onclick="openEditModal({{ $cat->id }}, '{{ addslashes($cat->name) }}', 'pengajuan')"
                                class="w-9 h-9 flex items-center justify-center rounded-xl bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white transition-all shadow-sm"
                                title="Edit Kategori">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            <button type="button"
                                onclick="toggleActive({{ $cat->id }}, this)"
                                class="w-9 h-9 flex items-center justify-center rounded-xl {{ $cat->is_active ? 'bg-amber-100 text-amber-600 hover:bg-amber-600' : 'bg-emerald-100 text-emerald-600 hover:bg-emerald-600' }} hover:text-white transition-all shadow-sm"
                                title="{{ $cat->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                <i data-lucide="{{ $cat->is_active ? 'eye-off' : 'eye' }}" class="w-4 h-4"></i>
                            </button>
                            <button type="button"
                                onclick="deleteCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}')"
                                class="w-9 h-9 flex items-center justify-center rounded-xl bg-red-100 text-red-600 hover:bg-red-600 hover:text-white transition-all shadow-sm"
                                title="Hapus Kategori">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-20 px-4">
                        <div class="w-20 h-20 bg-slate-50 rounded-[2rem] flex items-center justify-center mx-auto mb-4 border-2 border-dashed border-slate-200">
                            <i data-lucide="package-search" class="w-10 h-10 text-slate-200"></i>
                        </div>
                        <h3 class="font-black text-slate-800 mb-1">Belum Ada Kategori</h3>
                        <p class="text-xs text-slate-400 font-medium">Klik tombol tambah untuk membuat kategori pertama Anda.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══ ADD / EDIT MODAL (Glassmorphism Overhaul) ══ --}}
<div id="category-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xl hidden items-center justify-center z-50 p-4 transition-all duration-300" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="bg-white rounded-[2.5rem] shadow-[0_32px_64px_-16px_rgba(0,0,0,0.2)] w-full max-w-lg p-8 relative transform transition-all duration-300 scale-95 opacity-0" id="modal-content">
        {{-- Close Button --}}
        <button type="button" onclick="closeModal()" class="absolute top-6 right-6 w-10 h-10 flex items-center justify-center rounded-2xl text-slate-400 hover:text-red-500 hover:bg-red-50 transition-all group">
            <i data-lucide="x" class="w-5 h-5 transition-transform group-hover:rotate-90"></i>
        </button>

        {{-- Modal Header --}}
        <div class="flex items-center gap-4 mb-8">
            <div class="w-14 h-14 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600 shadow-inner">
                <i data-lucide="layers" class="w-7 h-7" id="modal-icon"></i>
            </div>
            <div>
                <h3 id="modal-title" class="text-2xl font-black text-slate-800 tracking-tight leading-tight">Tambah Kategori</h3>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-0.5">Categorization Engine</p>
            </div>
        </div>

        <input type="hidden" id="modal-category-id" value="">
        <input type="hidden" id="modal-category-type" value="">

        <div class="space-y-6">
            {{-- Tipe Display --}}
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2.5 ml-1">Type Allocation</label>
                <div class="px-5 py-4 bg-slate-50/80 border border-slate-100 rounded-2xl text-sm font-black text-slate-700 flex items-center gap-3" id="modal-type-display-bg">
                    <div class="w-2 h-2 rounded-full animate-pulse" id="modal-type-indicator"></div>
                    <span id="modal-type-display" class="truncate">-</span>
                </div>
            </div>

            {{-- Nama Input --}}
            <div>
                <label for="modal-name-input" class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2.5 ml-1">Category Label *</label>
                <input type="text" id="modal-name-input" placeholder="Contoh: ATK, Transportasi, Listrik..."
                    class="w-full border border-slate-200 rounded-2xl px-5 py-4 text-sm font-medium text-slate-700 focus:ring-8 focus:ring-indigo-50 focus:border-indigo-500 outline-none transition-all placeholder:text-slate-300 pointer-events-auto"
                    onkeydown="if(event.key==='Enter') saveCategory()">
                <p id="modal-error" class="text-xs text-red-500 mt-2 font-bold hidden"></p>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button type="button" onclick="closeModal()"
                    class="flex-1 px-6 py-4 rounded-2xl border border-slate-100 text-slate-400 text-sm font-black hover:bg-slate-50 hover:text-slate-600 transition-all">
                    CANCEL
                </button>
                <button type="button" id="save-btn" onclick="saveCategory()"
                    class="flex-[2] px-6 py-4 rounded-2xl bg-slate-900 hover:bg-indigo-600 text-white text-sm font-black shadow-xl shadow-slate-900/10 hover:shadow-indigo-600/20 transition-all flex items-center justify-center gap-3 group">
                    <span id="save-btn-text">SAVE CHANGES</span>
                    <i data-lucide="arrow-right" class="w-4 h-4 transition-transform group-hover:translate-x-1"></i>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    lucide.createIcons();
});

const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

/* ── Search Filter Logic ── */
function filterCategories(type, query) {
    const list = document.getElementById(`${type}-list`);
    const items = list.querySelectorAll('.category-item');
    const q = query.toLowerCase().trim();

    items.forEach(item => {
        const name = item.getAttribute('data-name').toLowerCase();
        if (name.includes(q)) {
            item.classList.remove('hidden');
        } else {
            item.classList.add('hidden');
        }
    });

    // Handle empty search results visually
    const visibleItems = list.querySelectorAll('.category-item:not(.hidden)');
    let emptyEl = list.querySelector('.empty-search-results');
    
    if (visibleItems.length === 0 && q !== '') {
        if (!emptyEl) {
            emptyEl = document.createElement('div');
            emptyEl.className = 'empty-search-results text-center py-10 text-slate-300 animate-in fade-in zoom-in duration-300';
            emptyEl.innerHTML = `<i data-lucide="search-x" class="w-10 h-10 mx-auto mb-2 opacity-20"></i><p class="text-xs font-black uppercase tracking-widest">Tidak ada hasil</p>`;
            list.appendChild(emptyEl);
            lucide.createIcons({ root: emptyEl });
        }
    } else if (emptyEl) {
        emptyEl.remove();
    }
}

/* ── Modal (Smooth Transitions) ── */
function openAddModal(type) {
    document.getElementById('modal-title').textContent = 'Tambah Kategori';
    document.getElementById('save-btn-text').textContent = 'TAMBAHKAN SEKARANG';
    document.getElementById('modal-category-id').value = '';
    document.getElementById('modal-category-type').value = type;
    
    const display = document.getElementById('modal-type-display');
    const indicator = document.getElementById('modal-type-indicator');
    
    if (type === 'rembush') {
        display.textContent = 'Reimbursement (Rembush)';
        indicator.className = 'w-2 h-2 rounded-full animate-pulse bg-emerald-500';
    } else {
        display.textContent = 'Pengajuan Pembelian';
        indicator.className = 'w-2 h-2 rounded-full animate-pulse bg-blue-500';
    }
    
    document.getElementById('modal-name-input').value = '';
    document.getElementById('modal-error').classList.add('hidden');
    openModal();
}

function openEditModal(id, name, type) {
    document.getElementById('modal-title').textContent = 'Edit Kategori';
    document.getElementById('save-btn-text').textContent = 'SIMPAN PERUBAHAN';
    document.getElementById('modal-category-id').value = id;
    document.getElementById('modal-category-type').value = type;
    
    const display = document.getElementById('modal-type-display');
    const indicator = document.getElementById('modal-type-indicator');
    
    if (type === 'rembush') {
        display.textContent = 'Reimbursement (Rembush)';
        indicator.className = 'w-2 h-2 rounded-full bg-emerald-500';
    } else {
        display.textContent = 'Pengajuan Pembelian';
        indicator.className = 'w-2 h-2 rounded-full bg-blue-500';
    }
    
    document.getElementById('modal-name-input').value = name;
    document.getElementById('modal-error').classList.add('hidden');
    openModal();
}

function openModal() {
    const modal = document.getElementById('category-modal');
    const content = document.getElementById('modal-content');
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    requestAnimationFrame(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    });
    
    lucide.createIcons({ root: modal });
    setTimeout(() => document.getElementById('modal-name-input').focus(), 300);
}

function closeModal() {
    const modal = document.getElementById('category-modal');
    const content = document.getElementById('modal-content');
    
    content.classList.add('scale-95', 'opacity-0');
    content.classList.remove('scale-100', 'opacity-100');
    
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }, 300);
}

document.getElementById('category-modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

/* ── Save (Add or Edit) ── */
async function saveCategory() {
    const id   = document.getElementById('modal-category-id').value;
    const type = document.getElementById('modal-category-type').value;
    const name = document.getElementById('modal-name-input').value.trim();
    const errEl = document.getElementById('modal-error');
    const btn = document.getElementById('save-btn');
    const btnText = document.getElementById('save-btn-text');

    if (!name) {
        errEl.textContent = 'Nama kategori tidak boleh kosong.';
        errEl.classList.remove('hidden');
        return;
    }

    btn.disabled = true;
    const originalText = btnText.textContent;
    btnText.innerHTML = '<svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

    try {
        const isEdit = !!id;
        const url    = isEdit ? `/transaction-categories/${id}` : '/transaction-categories';
        const method = isEdit ? 'PUT' : 'POST';

        const res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ name, type }),
        });

        const data = await res.json();

        if (data.success) {
            showToast(data.message, 'success');
            closeModal();
            setTimeout(() => {
                 // Option A: Refresh page (cleanest)
                 location.reload();
                 // Option B: Append dynamically (more complex logic to fix IDs)
            }, 800);
        } else {
            errEl.textContent = data.message || 'Terjadi kesalahan sistem.';
            errEl.classList.remove('hidden');
        }
    } catch (e) {
        errEl.textContent = 'Koneksi ke server terputus.';
        errEl.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btnText.textContent = originalText;
    }
}

/* ── Toggle Active ── */
async function toggleActive(id, btn) {
    // Add subtle visual feedback on button during request
    const icon = btn.querySelector('i');
    icon.classList.add('animate-pulse');

    try {
        const res = await fetch(`/transaction-categories/${id}/toggle`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'info');
            setTimeout(() => location.reload(), 600);
        }
    } catch(e) {
        showToast('Gagal mengubah status kategori.', 'error');
    } finally {
        icon.classList.remove('animate-pulse');
    }
}

/* ── Delete ── */
function deleteCategory(id, name) {
    openConfirmModal('globalConfirmModal', {
        title: 'Hapus Kategori?',
        message: `Hapus kategori <strong class="text-slate-800">"${name}"</strong>?<br><br><span class="text-[11px] font-medium text-slate-400 leading-relaxed uppercase tracking-widest">Riwayat transaksi akan tetap aman, namun kategori ini tidak dapat dipilih lagi.</span>`,
        action: `/transaction-categories/${id}`,
        method: 'DELETE',
        submitText: 'Ya, Hapus',
        onConfirm: async () => {
            try {
                const response = await fetch(`/transaction-categories/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
                });
                const result = await response.json();
                if (response.ok && result.success) {
                    showToast(result.message, 'success');
                    const item = document.querySelector(`.category-item[data-id="${id}"]`);
                    if (item) {
                        item.style.opacity = '0';
                        item.style.transform = 'translateX(-20px)';
                        setTimeout(() => item.remove(), 300);
                    }
                } else {
                    throw new Error(result.message || 'Gagal menghapus kategori');
                }
            } catch (err) {
                showToast(err.message, 'error');
            }
        }
    });
}
</script>

@endpush

@endsection
