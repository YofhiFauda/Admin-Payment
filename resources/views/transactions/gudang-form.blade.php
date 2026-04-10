@extends('layouts.app')

@section('page-title', 'Form Belanja Gudang')

@section('content')
    <div class="max-w-8xl mx-auto px-1 md:px-4 lg:px-6 py-1 lg:py-6">

        {{-- Form Container --}}
        <div class="bg-white rounded-[1rem] md:rounded-[2rem] shadow-sm border border-slate-100 p-3 md:p-8 lg:p-10">
            
            {{-- Header --}}
            <div class="mb-8 md:mb-10 flex items-center gap-4">
                <a href="{{ route('transactions.index') }}" class="w-8 h-8 flex items-center justify-center rounded-full border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-200 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-slate-800 tracking-tight">Input Belanja Gudang</h1>
                    <p class="text-xs md:text-sm text-slate-400 mt-1">Catat belanja inventaris atau operasional gudang secara manual</p>
                </div>
            </div>

            <form method="POST" action="{{ route('gudang.store') }}" id="transaction-form" enctype="multipart/form-data">
                @csrf
                @if ($errors->any())
                    <div class="mb-8 md:mb-10 bg-red-50 border border-red-200 text-red-600 rounded-xl p-4 md:p-5 text-xs md:text-sm">
                        <strong class="font-bold">Terjadi Kesalahan:</strong>
                        <ul class="list-disc pl-5 mt-2 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <input type="hidden" name="amount" id="form-total-amount" value="{{ old('amount', 0) }}">
                
                {{-- 1. INFORMASI UTAMA --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 md:gap-6 mb-8 md:mb-10">
                    
                    {{-- Pengeluaran Atas Nama (Field Khusus Gudang) --}}
                    <div class="md:col-span-2 lg:col-span-2">
                        <label class="block text-[10px] md:text-xs font-bold text-indigo-600 uppercase mb-2 tracking-wider">Pengeluaran Atas Nama <span class="text-red-500">*</span></label>
                        <div class="relative group">
                            <i data-lucide="user" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-indigo-400 group-focus-within:text-indigo-600 transition-colors"></i>
                            <input type="text" name="pengeluaran_siapa" id="pengeluaran_siapa" value="{{ old('pengeluaran_siapa') }}" required
                                placeholder="Misal: Budi (Teknisi) atau Nama Vendor"
                                class="w-full border border-indigo-100 bg-indigo-50/30 rounded-xl pl-10 pr-4 py-3 md:py-3.5 text-xs md:text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 outline-none transition-all" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Tanggal Belanja</label>
                        <input type="date" name="date" id="date" required value="{{ old('date', now()->format('Y-m-d')) }}"
                            class="w-full border border-slate-200 rounded-xl p-3 md:p-3.5 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 outline-none transition-all" />
                    </div>

                    <div>
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Kategori</label>
                        <div class="relative">
                            <select name="category" id="category" required
                                class="w-full appearance-none bg-white border border-slate-200 rounded-xl p-3 md:p-3.5 pr-10 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 outline-none transition-all">
                                <option value="" disabled {{ old('category') ? '' : 'selected' }}>Pilih kategori...</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->name }}" {{ old('category') == $cat->name ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-3 md:right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Nama Toko / Vendor (Opsional)</label>
                        <input type="text" name="vendor" id="vendor" value="{{ old('vendor') }}"
                            placeholder="Misal: Toko Bangunan Jaya"
                            class="w-full border border-slate-200 rounded-xl p-3 md:p-3.5 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 outline-none transition-all" />
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Upload Nota (Opsional)</label>
                        <input type="file" name="nota" id="nota" accept="image/*,application/pdf"
                            class="w-full border border-slate-200 rounded-xl p-2.5 text-xs md:text-sm font-medium text-slate-500 file:mr-4 file:py-1 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 outline-none transition-all" />
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Metode Pembayaran</label>
                        <div class="relative">
                            <select name="payment_method" id="payment_method" required
                                class="w-full appearance-none bg-white border border-slate-200 rounded-xl p-3 md:p-3.5 pr-10 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 outline-none transition-all">
                                <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="transfer_teknisi" {{ old('payment_method') == 'transfer_teknisi' ? 'selected' : '' }}>Transfer ke Teknisi</option>
                                <option value="transfer_penjual" {{ old('payment_method') == 'transfer_penjual' ? 'selected' : '' }}>Transfer ke Penjual</option>
                            </select>
                            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-3 md:right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="md:col-span-4">
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Keterangan Tambahan</label>
                        <textarea name="description" id="description" rows="2" placeholder="Detail belanja gudang..."
                            class="w-full border border-slate-200 rounded-xl p-3 md:p-3.5 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 outline-none resize-none transition-all">{{ old('description') }}</textarea>
                    </div>
                </div>

                {{-- 2. DAFTAR BARANG --}}
                <div class="mb-8 md:mb-10">
                    <div class="flex justify-between items-center mb-4">
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wider">Daftar Barang Belanja</label>
                        <button type="button" id="add-item-btn" class="flex items-center gap-1.5 bg-emerald-50 text-emerald-600 px-3 py-1.5 rounded-lg text-[10px] md:text-xs font-bold hover:bg-emerald-100 transition-colors uppercase tracking-wider">
                            <i data-lucide="plus" class="w-3 h-3 md:w-3.5 md:h-3.5"></i> Tambah Baris
                        </button>
                    </div>

                    <div class="border border-slate-100 rounded-2xl overflow-hidden shadow-sm">
                        {{-- Desktop Table View --}}
                        <div class="hidden md:block overflow-x-auto">
                            <table class="w-full text-left text-sm whitespace-nowrap" id="items-table">
                                <thead class="text-[10px] text-slate-400 font-bold uppercase bg-slate-50 border-b border-slate-100 tracking-wider">
                                    <tr>
                                        <th class="p-4 w-10 text-center">No</th>
                                        <th class="p-4 min-w-[120px]">Nama Barang</th>
                                        <th class="p-4 w-20">Qty</th>
                                        <th class="p-4 w-24">Satuan</th>
                                        <th class="p-4 w-32 text-right">Harga Satuan</th>
                                        <th class="p-4 w-32 text-right">Total</th>
                                        <th class="p-4 min-w-[120px]">Deskripsi</th>
                                        <th class="p-4 w-10 text-center"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100" id="items-tbody">
                                </tbody>
                            </table>
                        </div>

                        {{-- Mobile Cards View --}}
                        <div class="md:hidden divide-y divide-slate-100 bg-white" id="items-cards">
                        </div>
                        
                        {{-- Baris Total --}}
                        <div class="bg-slate-900 px-4 md:px-6 py-4 md:py-5 flex justify-between items-center">
                            <span class="text-[9px] md:text-[11px] font-bold uppercase tracking-[0.15em] text-slate-400">Total Belanja</span>
                            <span class="text-xl md:text-2xl font-black text-emerald-400 tracking-tight" id="display-total-items">Rp 0</span>
                        </div>
                    </div>
                </div>

                {{-- 3. PEMBAGIAN CABANG --}}
                <div class="mb-12">
                    <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Pembagian Cabang (Siapa yang bayar/pakai?)</label>
                    
                    {{-- Branch Pills --}}
                    <div class="flex flex-wrap gap-2 mb-6" id="branch-pills-container">
                        @foreach($branches as $branch)
                            <button type="button" data-branch-id="{{ $branch->id }}" data-branch-name="{{ $branch->name }}" 
                                class="branch-pill px-3 md:px-4 py-1.5 md:py-2 rounded-full text-[10px] md:text-xs font-bold transition-all border border-slate-200 text-slate-500 hover:bg-slate-50">
                                {{ $branch->name }}
                            </button>
                        @endforeach
                    </div>

                    <div class="border border-slate-100 rounded-2xl p-4 md:p-6" id="allocation-container" style="display: none;">
                        <div class="flex flex-col md:flex-row justify-between md:items-center gap-4 border-b border-slate-100 pb-4 mb-4">
                            <span class="text-[9px] md:text-[10px] font-bold text-slate-500 uppercase tracking-wider">Metode Distribusi</span>
                            
                            {{-- Toggles --}}
                            <div class="flex bg-slate-100 p-1 rounded-lg w-fit">
                                <button type="button" data-mode="equal" class="alloc-mode-btn bg-white shadow-sm text-slate-800 px-3 md:px-4 py-1.5 rounded text-[10px] font-bold transition-colors">Bagi Rata</button>
                                <button type="button" data-mode="percent" class="alloc-mode-btn text-slate-400 px-3 md:px-4 py-1.5 rounded text-[10px] font-bold transition-colors hover:text-slate-600">Persentase</button>
                                <button type="button" data-mode="manual" class="alloc-mode-btn text-slate-400 px-3 md:px-4 py-1.5 rounded text-[10px] font-bold transition-colors hover:text-slate-600">Manual</button>
                            </div>
                        </div>

                        {{-- Active Branches Allocation Inputs/Display --}}
                        <div class="space-y-3" id="active-branches-list">
                        </div>
                            
                        {{-- Hidden inputs temp container --}}
                        <div id="branch-hidden-inputs"></div>

                        <p id="percent-warning" class="mt-2 text-red-500 font-bold text-[10px] md:text-xs hidden"></p>
                    </div>
                </div>

                {{-- Divider --}}
                <div class="relative flex justify-center items-center mb-8">
                    <div class="w-full h-px bg-slate-100 absolute"></div>
                    <span class="bg-white px-4 relative z-10 text-[9px] md:text-[10px] font-bold text-slate-300 uppercase tracking-[0.2em]">Ringkasan Pembayaran</span>
                </div>

                {{-- 4. SUMMARY BILLING --}}
                <div class="bg-[#1a1c23] rounded-[1rem] md:rounded-3xl p-4 md:p-8 lg:p-10 text-white relative overflow-hidden shadow-xl">
                    {{-- Decorative circle --}}
                    <div class="absolute -right-20 -top-20 w-64 h-64 bg-white/[0.02] rounded-full pointer-events-none"></div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12 mb-8 md:mb-10 relative z-10">
                        {{-- Left Side: Total --}}
                        <div>
                            <span class="text-[9px] md:text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Total Belanja Gudang</span>
                            <div class="text-3xl md:text-4xl lg:text-5xl font-black text-emerald-400 mb-4 md:mb-6 tracking-tight" id="final-total">Rp 0</div>
                            <div class="flex flex-wrap gap-2">
                                <span class="bg-white/10 text-slate-300 px-3 py-1 md:py-1.5 rounded-full text-[9px] md:text-[10px] font-bold uppercase tracking-wider" id="summary-mode-badge">Metode: Bagi Rata</span>
                                <span class="bg-white/10 text-slate-300 px-3 py-1 md:py-1.5 rounded-full text-[9px] md:text-[10px] font-bold uppercase tracking-wider" id="summary-count-badge">0 Cabang</span>
                            </div>
                        </div>
                        
                        {{-- Right Side: Details --}}
                        <div class="md:border-l border-white/10 md:pl-8 lg:pl-12 flex flex-col justify-center">
                            <span class="text-[9px] md:text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-4">Rincian Penagihan Cabang</span>
                            <div class="space-y-3" id="summary-branches-list">
                                <div class="text-xs text-slate-500 italic">Pilih cabang terlebih dahulu...</div>
                            </div>
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <button type="submit" id="submit-btn"
                        class="w-full relative z-10 bg-indigo-500 hover:bg-indigo-400
                            disabled:bg-slate-700 disabled:text-slate-500 text-white font-bold
                            py-4 md:py-5 rounded-xl transition-all
                            shadow-[0_8px_20px_-6px_rgba(79,70,229,0.4)] disabled:shadow-none
                            text-xs md:text-sm uppercase tracking-wider
                            cursor-pointer disabled:cursor-not-allowed
                            flex items-center justify-center gap-2">
                        <span id="submit-text">Simpan Belanja Gudang</span>
                        <svg id="submit-spinner" class="animate-spin h-4 w-4 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>

{{-- ── SCRIPTS ─────────────────────── --}}
@push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {

        // ─────────────────────────────────────────────
        // STATE
        // ─────────────────────────────────────────────
        let items          = [{ name: '', qty: 1, unit: 'pcs', price: 0, desc: '' }];
        let currentMethod  = 'equal';
        let selectedBranches = [];
        let totalAmount    = 0;

        // ─────────────────────────────────────────────
        // SELECTORS
        // ─────────────────────────────────────────────
        const itemsTbody            = document.getElementById('items-tbody');
        const itemsCards            = document.getElementById('items-cards');
        const addItemBtn            = document.getElementById('add-item-btn');
        const displayTotalItems     = document.getElementById('display-total-items');
        const formTotalAmount       = document.getElementById('form-total-amount');

        const branchPills           = document.querySelectorAll('.branch-pill');
        const methodBtns            = document.querySelectorAll('.alloc-mode-btn');
        const allocationContainer   = document.getElementById('allocation-container');
        const activeBranchesList    = document.getElementById('active-branches-list');
        const percentWarning        = document.getElementById('percent-warning');
        const hiddenInputsContainer = document.getElementById('branch-hidden-inputs');

        const finalTotal            = document.getElementById('final-total');
        const summaryModeBadge      = document.getElementById('summary-mode-badge');
        const summaryCountBadge     = document.getElementById('summary-count-badge');
        const summaryBranchesList   = document.getElementById('summary-branches-list');
        const submitBtn             = document.getElementById('submit-btn');

        // ─────────────────────────────────────────────
        // HELPERS
        // ─────────────────────────────────────────────
        function esc(str) {
            return String(str ?? '').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }
        function parseNumber(str) {
            return parseInt(String(str ?? '').replace(/[^0-9]/g, '')) || 0;
        }
        function formatRupiah(num) {
            return 'Rp ' + Math.round(num || 0).toLocaleString('id-ID');
        }

        // ─────────────────────────────────────────────
        // ITEMS — render
        // ─────────────────────────────────────────────
        function renderItems() {
            itemsTbody.innerHTML = '';
            if (itemsCards) itemsCards.innerHTML = '';
            totalAmount = 0;

            items.forEach((item, i) => {
                const rowTotal = (item.qty || 0) * (item.price || 0);
                totalAmount += rowTotal;

                // Desktop Row
                const tr = document.createElement('tr');
                tr.className = 'text-slate-600 text-xs hover:bg-slate-50/50 transition-colors';
                tr.dataset.idx = i;
                tr.innerHTML = `
                    <td class="p-4 text-center text-slate-400 font-medium">${i + 1}</td>
                    <td class="p-3">
                        <input type="text" name="items[${i}][name]" value="${esc(item.name)}"
                            placeholder="Nama item..." required
                            class="item-field w-full bg-transparent border-0 border-b border-slate-100
                                focus:border-indigo-400 focus:ring-0 px-2 py-1 outline-none transition-colors"
                            data-field="name">
                    </td>
                    <td class="p-3">
                        <input type="number" name="items[${i}][qty]" value="${item.qty}" min="1"
                            class="item-field w-full bg-transparent border-0 border-b border-slate-100
                                focus:border-indigo-400 focus:ring-0 px-2 py-1 outline-none transition-colors"
                            data-field="qty">
                    </td>
                    <td class="p-3">
                        <input type="text" name="items[${i}][unit]" value="${esc(item.unit)}"
                            class="item-field w-full bg-transparent border-0 border-b border-slate-100
                                focus:border-indigo-400 focus:ring-0 px-2 py-1 outline-none transition-colors text-slate-400"
                            data-field="unit">
                    </td>
                    <td class="p-3">
                        <input type="text" value="${formatRupiah(item.price)}"
                            class="item-price-display w-full bg-transparent border-0 border-b border-slate-100
                                focus:border-indigo-400 focus:ring-0 px-2 py-1 outline-none transition-colors text-right font-bold">
                        <input type="hidden" name="items[${i}][price]" value="${item.price}" class="item-price-hidden">
                    </td>
                    <td class="p-4 font-bold text-slate-800 text-right">${formatRupiah(rowTotal)}</td>
                    <td class="p-3">
                        <input type="text" name="items[${i}][desc]" value="${esc(item.desc)}"
                            placeholder="Catatan..."
                            class="item-field w-full bg-transparent border-0 border-b border-slate-100
                                focus:border-indigo-400 focus:ring-0 px-2 py-1 outline-none transition-colors text-slate-400"
                            data-field="desc">
                    </td>
                    <td class="p-4 text-center">
                        <button type="button" class="item-delete text-slate-300 hover:text-red-500 transition-colors">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </td>`;
                itemsTbody.appendChild(tr);

                // Mobile Card
                if (itemsCards) {
                    const card = document.createElement('div');
                    card.className = 'p-4 space-y-4 relative bg-white';
                    card.dataset.idx = i;
                    card.innerHTML = `
                        <div class="flex justify-between items-start">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500">${i + 1}</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Baris #${i+1}</span>
                            </div>
                            <button type="button" class="item-delete p-1.5 bg-red-50 text-red-500 rounded-lg">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                        <div class="space-y-3">
                            <input type="text" value="${esc(item.name)}" placeholder="Nama Barang..."
                                class="item-field w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-700 outline-none focus:border-indigo-400 transition-all"
                                data-field="name">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[8px] font-bold text-slate-400 uppercase mb-1">Qty</label>
                                    <input type="number" value="${item.qty}" min="1"
                                        class="item-field w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-700"
                                        data-field="qty">
                                </div>
                                <div>
                                    <label class="block text-[8px] font-bold text-slate-400 uppercase mb-1">Satuan</label>
                                    <input type="text" value="${esc(item.unit)}"
                                        class="item-field w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm"
                                        data-field="unit">
                                </div>
                            </div>
                            <input type="text" value="${formatRupiah(item.price)}"
                                class="item-price-display w-full bg-white border-2 border-slate-100 rounded-xl px-4 py-2.5 text-sm font-black text-indigo-600 outline-none focus:border-indigo-400 transition-all">
                        </div>`;
                    itemsCards.appendChild(card);
                }
            });

            displayTotalItems.textContent = formatRupiah(totalAmount);
            formTotalAmount.value         = totalAmount;
            finalTotal.textContent        = formatRupiah(totalAmount);

            if (typeof lucide !== 'undefined') lucide.createIcons();
            renderDistribution();
        }

        // ─────────────────────────────────────────────
        // ITEMS — event delegation
        // ─────────────────────────────────────────────
        function handleItemInput(e) {
            const container = e.target.closest('[data-idx]');
            if (!container) return;
            const idx = parseInt(container.dataset.idx);

            if (e.target.classList.contains('item-field')) {
                items[idx][e.target.dataset.field] = e.target.value;
                if (e.target.dataset.field === 'qty') renderItems();
            }
            if (e.target.classList.contains('item-price-display')) {
                const raw = parseNumber(e.target.value);
                items[idx].price = raw;
                const row = itemsTbody.querySelector(`tr[data-idx="${idx}"]`);
                if (row) row.querySelector('.item-price-hidden').value = raw;
            }
        }

        itemsTbody.addEventListener('input', handleItemInput);
        if (itemsCards) itemsCards.addEventListener('input', handleItemInput);

        function handleItemBlur(e) {
            if (e.target.classList.contains('item-price-display')) {
                const container = e.target.closest('[data-idx]');
                if (!container) return;
                items[parseInt(container.dataset.idx)].price = parseNumber(e.target.value);
                renderItems();
            }
        }

        itemsTbody.addEventListener('blur', handleItemBlur, true);
        if (itemsCards) itemsCards.addEventListener('blur', handleItemBlur, true);

        function handleItemClick(e) {
            const btn = e.target.closest('.item-delete');
            if (!btn) return;
            const container = btn.closest('[data-idx]');
            if (!container) return;
            if (items.length > 1) {
                items.splice(parseInt(container.dataset.idx), 1);
                renderItems();
            }
        }

        itemsTbody.addEventListener('click', handleItemClick);
        if (itemsCards) itemsCards.addEventListener('click', handleItemClick);

        addItemBtn.addEventListener('click', () => {
            items.push({ name: '', qty: 1, unit: 'pcs', price: 0, desc: '' });
            renderItems();
        });

        // ─────────────────────────────────────────────
        // BRANCH PILLS
        // ─────────────────────────────────────────────
        branchPills.forEach(pill => {
            pill.addEventListener('click', function () {
                const id   = this.dataset.branchId;
                const name = this.dataset.branchName;
                const idx  = selectedBranches.findIndex(b => b.id === id);

                if (idx > -1) {
                    selectedBranches.splice(idx, 1);
                    this.classList.remove('bg-indigo-500', 'text-white', 'border-indigo-500');
                    this.classList.add('border-slate-200', 'text-slate-500');
                } else {
                    selectedBranches.push({ id, name, value: 0, percent: 0 });
                    this.classList.remove('border-slate-200', 'text-slate-500');
                    this.classList.add('bg-indigo-500', 'text-white', 'border-indigo-500');
                }

                allocationContainer.style.display = selectedBranches.length > 0 ? 'block' : 'none';
                renderDistribution();
            });
        });

        // ─────────────────────────────────────────────
        // METHOD BUTTONS
        // ─────────────────────────────────────────────
        methodBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                currentMethod = this.dataset.mode;
                methodBtns.forEach(b => {
                    b.classList.remove('bg-white', 'shadow-sm', 'text-slate-800');
                    b.classList.add('text-slate-400');
                });
                this.classList.add('bg-white', 'shadow-sm', 'text-slate-800');
                this.classList.remove('text-slate-400');
                const labels = { equal: 'Bagi Rata', percent: 'Persentase', manual: 'Manual' };
                summaryModeBadge.textContent = 'Metode: ' + (labels[currentMethod] || '');
                renderDistribution();
            });
        });

        // ─────────────────────────────────────────────
        // DISTRIBUTION INPUTS
        // ─────────────────────────────────────────────
        activeBranchesList.addEventListener('input', function (e) {
            if (e.target.classList.contains('dist-pct')) {
                const idx = parseInt(e.target.dataset.idx);
                selectedBranches[idx].percent = parseFloat(e.target.value) || 0;
                selectedBranches[idx].value   = totalAmount > 0
                    ? Math.round((totalAmount * selectedBranches[idx].percent) / 100) : 0;
                updateHiddenInputs();
                updateSummaryList();
                validateAndToggleSubmit();
            }
            if (e.target.classList.contains('dist-manual')) {
                const idx = parseInt(e.target.dataset.idx);
                const raw = parseNumber(e.target.value);
                e.target.value = raw > 0 ? raw.toLocaleString('id-ID') : '';
                selectedBranches[idx].value   = raw;
                selectedBranches[idx].percent = totalAmount > 0
                    ? parseFloat(((raw / totalAmount) * 100).toFixed(2)) : 0;
                updateHiddenInputs();
                updateSummaryList();
                validateAndToggleSubmit();
            }
        });

        function renderDistribution() {
            activeBranchesList.innerHTML  = '';
            summaryBranchesList.innerHTML = '';
            if (hiddenInputsContainer) hiddenInputsContainer.innerHTML = '';
            summaryCountBadge.textContent = `${selectedBranches.length} Cabang`;

            if (selectedBranches.length === 0) {
                summaryBranchesList.innerHTML = `<div class="text-xs text-slate-500 italic">Pilih cabang terlebih dahulu...</div>`;
                validateAndToggleSubmit();
                return;
            }

            selectedBranches.forEach((branch, idx) => {
                if (currentMethod === 'equal') {
                    branch.percent = parseFloat((100 / selectedBranches.length).toFixed(2));
                    branch.value   = totalAmount > 0 ? Math.round(totalAmount / selectedBranches.length) : 0;
                } else if (currentMethod === 'percent') {
                    branch.value = totalAmount > 0
                        ? Math.round((totalAmount * (branch.percent || 0)) / 100) : 0;
                } else if (currentMethod === 'manual') {
                    branch.percent = totalAmount > 0
                        ? parseFloat(((branch.value / totalAmount) * 100).toFixed(2)) : 0;
                }

                let distributionInput = '';
                if (currentMethod === 'equal') {
                    distributionInput = `<span class="text-indigo-500 font-bold text-sm">${formatRupiah(branch.value)}</span>`;
                } else if (currentMethod === 'percent') {
                    distributionInput = `
                        <div class="flex items-center gap-2">
                            <input type="number" step="0.1" min="0" max="100" value="${branch.percent || 0}"
                                class="dist-pct w-16 text-right text-sm border border-slate-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-indigo-300 outline-none"
                                data-idx="${idx}">
                            <span class="text-[10px] font-bold text-slate-400">%</span>
                            <span class="text-indigo-500 font-bold text-xs w-20 text-right">${formatRupiah(branch.value)}</span>
                        </div>`;
                } else if (currentMethod === 'manual') {
                    const displayVal = branch.value > 0 ? branch.value.toLocaleString('id-ID') : '';
                    distributionInput = `
                        <div class="flex items-center gap-1">
                            <span class="text-[10px] text-slate-400 font-bold">Rp</span>
                            <input type="text" value="${displayVal}" placeholder="0"
                                class="dist-manual w-28 text-right text-sm border border-slate-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-indigo-300 outline-none font-bold"
                                data-idx="${idx}">
                        </div>`;
                }

                const row = document.createElement('div');
                row.className = 'flex justify-between items-center bg-white p-3 rounded-xl border border-slate-100 shadow-sm transition-all hover:border-indigo-100';
                row.innerHTML = `
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-indigo-400"></div>
                        <span class="text-xs font-bold text-slate-600">${esc(branch.name)}</span>
                    </div>
                    <div>${distributionInput}</div>`;
                activeBranchesList.appendChild(row);
            });

            if (typeof lucide !== 'undefined') lucide.createIcons();
            updateHiddenInputs();
            updateSummaryList();
            validateAndToggleSubmit();
        }

        function updateHiddenInputs() {
            hiddenInputsContainer.innerHTML = '';
            selectedBranches.forEach((branch, idx) => {
                hiddenInputsContainer.insertAdjacentHTML('beforeend', `
                    <input type="hidden" name="branches[${idx}][branch_id]" value="${branch.id}">
                    <input type="hidden" name="branches[${idx}][allocation_percent]" value="${branch.percent || 0}">
                `);
            });
        }

        function updateSummaryList() {
            summaryBranchesList.innerHTML = '';
            selectedBranches.forEach(branch => {
                summaryBranchesList.insertAdjacentHTML('beforeend', `
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-400 font-medium">${esc(branch.name)}</span>
                        <div class="text-right">
                            <div class="text-indigo-400 font-bold">${formatRupiah(branch.value)}</div>
                            <div class="text-[9px] text-slate-500">${(branch.percent || 0).toFixed(1)}%</div>
                        </div>
                    </div>`);
            });
        }

        function validateAndToggleSubmit() {
            let isValid = (selectedBranches.length > 0);
            if (currentMethod === 'percent') {
                const totalPct = selectedBranches.reduce((s, b) => s + (parseFloat(b.percent) || 0), 0);
                if (Math.abs(totalPct - 100) > 0.5) isValid = false;
            }
            submitBtn.disabled = !isValid;
        }

        document.getElementById('transaction-form').addEventListener('submit', function (e) {
            submitBtn.disabled = true;
            document.getElementById('submit-text').textContent = 'Menyimpan...';
            document.getElementById('submit-spinner').classList.remove('hidden');
        });

        renderItems();
    });
    </script>
@endpush
@endsection
