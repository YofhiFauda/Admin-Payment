@extends('layouts.app')

@section('page-title', 'Edit Reimbursement')

@section('content')
    <div class="max-w-4xl mx-auto px-1 md:px-6 lg:px-8 py-1 lg:py-12">

        {{-- Form Container --}}
        <div class="bg-white rounded-[1rem] md:rounded-[2rem] shadow-sm border border-slate-100 p-3 md:p-8 lg:p-10">
            
            {{-- Header --}}
            <div class="mb-8 md:mb-10 flex items-center gap-4">
                <a href="{{ route('transactions.index') }}" class="w-8 h-8 flex items-center justify-center rounded-full border border-slate-200 text-slate-400 hover:text-emerald-600 hover:border-emerald-200 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-slate-800 tracking-tight">Edit Reimbursement</h1>
                    <p class="text-[10px] font-bold text-blue-500 uppercase tracking-widest">{{ $transaction->invoice_number }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('transactions.update', $transaction->id) }}" id="transaction-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="type" value="rembush">
                <input type="hidden" name="amount" id="form-total-amount" value="{{ old('amount', $transaction->amount) }}">

                {{-- 1. FOTO NOTA --}}
                @if($transaction->file_path)
                <div class="mb-8 md:mb-10">
                    <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-3 tracking-wider">Foto Nota</label>
                    <div class="border-2 border-slate-200 rounded-2xl p-2 bg-slate-50/50 flex justify-center relative overflow-hidden group">
                        <img src="{{ route('transactions.image', $transaction->id) }}" class="w-auto h-48 md:h-64 object-contain rounded-xl shadow-sm relative z-10" />
                    </div>
                </div>
                @endif

                {{-- 2. MAIN INFO FIELDS --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6 mb-8 md:mb-10">

                    <div>
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Nama Vendor</label>
                        <input type="text" name="customer" id="customer" value="{{ old('customer', $transaction->customer) }}"
                            placeholder="Opsional"
                            class="w-full border border-slate-200 rounded-xl p-3 md:p-3.5 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 outline-none transition-all" />
                    </div>
                    
                    <div>
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Tanggal Transaksi</label>
                        <input type="date" name="date" id="date" required value="{{ old('date', $transaction->date ? \Carbon\Carbon::parse($transaction->date)->format('Y-m-d') : '') }}"
                            class="w-full border border-slate-200 rounded-xl p-3 md:p-3.5 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 outline-none transition-all" />
                    </div>
                    
                    {{-- Kategori --}}
                    <div>
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Kategori</label>
                        <div class="relative">
                            <select name="category" id="category" required
                                class="w-full appearance-none bg-white border border-slate-200 rounded-xl p-3 md:p-3.5 pr-10 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 outline-none transition-all">
                                <option value="" disabled>Pilih kategori...</option>
                                @foreach($rembushCategories as $cat)
                                    <option value="{{ $cat->name }}" {{ old('category', $transaction->category) == $cat->name ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-3 md:right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>

                    {{-- Metode Pencairan --}}
                    <div>
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Metode Pencairan</label>
                        <div class="relative">
                            <select name="payment_method" id="payment_method" required
                                class="w-full appearance-none bg-white border border-slate-200 rounded-xl p-3 md:p-3.5 pr-10 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 outline-none transition-all">
                                <option value="" disabled>Pilih metode pembayaran...</option>
                                @foreach(\App\Models\Transaction::PAYMENT_METHODS as $key => $label)
                                    <option value="{{ $key }}" {{ old('payment_method', $transaction->payment_method) == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-3 md:right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Keterangan</label>
                        <textarea name="description" id="description" rows="2" placeholder="Nota pembelian dari..."
                            class="w-full border border-slate-200 rounded-xl p-3 md:p-3.5 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 outline-none resize-none transition-all">{{ old('description', $transaction->description) }}</textarea>
                    </div>
                </div>

                {{-- 3. DAFTAR BARANG --}}
                <div class="mb-8 md:mb-10">
                    <div class="flex justify-between items-center mb-4">
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wider">Daftar Barang</label>
                        <button type="button" id="add-item-btn" class="flex items-center gap-1.5 bg-emerald-50 text-emerald-600 px-3 py-1.5 rounded-lg text-[10px] md:text-xs font-bold hover:bg-emerald-100 transition-colors uppercase tracking-wider">
                            <i data-lucide="plus" class="w-3 h-3 md:w-3.5 md:h-3.5"></i> Tambah Baris
                        </button>
                    </div>

                    <div class="border border-slate-100 rounded-2xl overflow-hidden shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs md:text-sm whitespace-nowrap" id="items-table">
                                <thead class="text-[9px] md:text-[10px] text-slate-400 font-bold uppercase bg-slate-50 border-b border-slate-100 tracking-wider">
                                    <tr>
                                        <th class="p-3 md:p-4 w-10 text-center">No</th>
                                        <th class="p-3 md:p-4 min-w-[120px]">Nama Barang</th>
                                        <th class="p-3 md:p-4 w-20">Qty</th>
                                        <th class="p-3 md:p-4 w-24">Satuan</th>
                                        <th class="p-3 md:p-4 w-32">Harga Satuan</th>
                                        <th class="p-3 md:p-4 w-32">Total</th>
                                        <th class="p-3 md:p-4 min-w-[120px]">Deskripsi</th>
                                        <th class="p-3 md:p-4 w-10 text-center"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100" id="items-tbody">
                                    </tbody>
                            </table>
                        </div>
                        
                        {{-- Baris Total --}}
                        <div class="bg-slate-900 px-4 md:px-6 py-4 md:py-5 flex justify-between items-center">
                            <span class="text-[9px] md:text-[11px] font-bold uppercase tracking-[0.15em] text-slate-400">Total Nominal</span>
                            <span class="text-xl md:text-2xl font-black text-emerald-400 tracking-tight" id="display-total-items">Rp 0</span>
                        </div>
                    </div>
                </div>

                {{-- 4. PEMBAGIAN CABANG --}}
                <div class="mb-12">
                    <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Pembagian Cabang</label>
                    
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

                        @error('branches')
                            <p class="mt-2 text-red-500 font-bold text-[10px] md:text-xs">{{ $message }}</p>
                            <p id="percent-warning" class="mt-2 text-red-500 font-bold text-[10px] md:text-xs hidden"></p>
                        @enderror
                    </div>
                </div>

                {{-- Divider --}}
                <div class="relative flex justify-center items-center mb-8">
                    <div class="w-full h-px bg-slate-100 absolute"></div>
                    <span class="bg-white px-4 relative z-10 text-[9px] md:text-[10px] font-bold text-slate-300 uppercase tracking-[0.2em]">Summary Billing</span>
                </div>

                {{-- 5. SUMMARY BILLING --}}
                <div class="bg-[#1a1c23] rounded-[1rem] md:rounded-3xl p-4 md:p-8 lg:p-10 text-white relative overflow-hidden shadow-xl">
                    {{-- Decorative circle --}}
                    <div class="absolute -right-20 -top-20 w-64 h-64 bg-white/[0.02] rounded-full pointer-events-none"></div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12 mb-8 md:mb-10 relative z-10">
                        {{-- Left Side: Total --}}
                        <div>
                            <span class="text-[9px] md:text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Total Pengajuan</span>
                            <div class="text-3xl md:text-4xl lg:text-5xl font-black text-emerald-400 mb-4 md:mb-6 tracking-tight" id="final-total">Rp 0</div>
                            <div class="flex flex-wrap gap-2">
                                <span class="bg-white/10 text-slate-300 px-3 py-1 md:py-1.5 rounded-full text-[9px] md:text-[10px] font-bold uppercase tracking-wider" id="summary-mode-badge">Metode: Bagi Rata</span>
                                <span class="bg-white/10 text-slate-300 px-3 py-1 md:py-1.5 rounded-full text-[9px] md:text-[10px] font-bold uppercase tracking-wider" id="summary-count-badge">0 Cabang</span>
                            </div>
                        </div>
                        
                        {{-- Right Side: Details --}}
                        <div class="md:border-l border-white/10 md:pl-8 lg:pl-12 flex flex-col justify-center">
                            <span class="text-[9px] md:text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-4">Rincian Distribusi Cabang</span>
                            <div class="space-y-3" id="summary-branches-list">
                                <div class="text-xs text-slate-500 italic">Pilih cabang terlebih dahulu...</div>
                            </div>
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <button type="submit" id="submit-btn"
                        class="w-full relative z-10 bg-emerald-500 hover:bg-emerald-400
                            disabled:bg-slate-700 disabled:text-slate-500 text-white font-bold
                            py-4 md:py-5 rounded-xl transition-all
                            shadow-[0_8px_20px_-6px_rgba(16,185,129,0.4)] disabled:shadow-none
                            text-xs md:text-sm uppercase tracking-wider
                            cursor-pointer disabled:cursor-not-allowed
                            flex items-center justify-center gap-2">
                        <span id="submit-text">Simpan Perubahan</span>
                        <svg id="submit-spinner" class="animate-spin h-4 w-4 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    @php
        $mappedBranches = $transaction->branches->map(fn($b) => [
            'id' => (string)$b->id,
            'name' => $b->name,
            'percent' => $b->pivot->allocation_percent,
            'value' => $b->pivot->allocation_amount
        ])->toArray();
    @endphp
    <script>
        {{-- Pass existing data --}}
        window._initialItems = @json($transaction->items ?? []);
        window._initialBranches = @json($mappedBranches ?? []);
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {

        // ─────────────────────────────────────────────
        // STATE
        // ─────────────────────────────────────────────
        let items          = [];
        let currentMethod  = 'equal';
        let selectedBranches = [];
        let totalAmount    = 0;

        // ─────────────────────────────────────────────
        // SELECTORS
        // ─────────────────────────────────────────────
        const aiData    = window._aiData || {};
        const aiStatus  = aiData.status ?? '';

        const itemsTbody            = document.getElementById('items-tbody');
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
        // DATA INITIALIZATION
        // ─────────────────────────────────────────────
        if (Array.isArray(window._initialItems) && window._initialItems.length > 0) {
            items = window._initialItems.map(i => ({
                name:  i.nama_barang || i.name  || '',
                qty:   parseInt(i.qty) || parseInt(i.quantity) || 1,
                unit:  (i.satuan || i.unit || 'pcs').toLowerCase(),
                price: parseInt(i.harga_satuan  || i.price) || 0,
                desc:  i.deskripsi_kalimat || i.desc || '',
            }));
        }
        if (items.length === 0) {
            items = [{ name: '', qty: 1, unit: 'pcs', price: 0, desc: '' }];
        }
        
        if (Array.isArray(window._initialBranches) && window._initialBranches.length > 0) {
            selectedBranches = window._initialBranches;
            allocationContainer.style.display = 'block';
            
            // Mark pills as active
            branchPills.forEach(pill => {
                const id = pill.dataset.branchId;
                if (selectedBranches.some(b => b.id === id)) {
                    pill.classList.remove('border-slate-200', 'text-slate-500');
                    pill.classList.add('bg-emerald-500', 'text-white', 'border-emerald-500');
                }
            });
            // Default to manual mode if editing from an existing percent/ratio
            currentMethod = 'percent';
            methodBtns.forEach(b => {
                b.classList.remove('bg-white', 'shadow-sm', 'text-slate-800');
                b.classList.add('text-slate-400');
                if (b.dataset.mode === 'percent') {
                     b.classList.add('bg-white', 'shadow-sm', 'text-slate-800');
                     b.classList.remove('text-slate-400');
                     summaryModeBadge.textContent = 'Metode: Persentase';
                }
            });
        }

        // ─────────────────────────────────────────────
        // ITEMS — render
        // ─────────────────────────────────────────────
        function renderItems() {
            itemsTbody.innerHTML = '';
            totalAmount = 0;

            items.forEach((item, i) => {
                const rowTotal = (item.qty || 0) * (item.price || 0);
                totalAmount += rowTotal;

                const tr = document.createElement('tr');
                tr.className = 'text-slate-600 text-xs hover:bg-slate-50/50 transition-colors';
                tr.dataset.idx = i;
                tr.innerHTML = `
                    <td class="p-3 md:p-4 text-center text-slate-400 font-medium">${i + 1}</td>
                    <td class="p-2 md:p-3">
                        <input type="text" name="items[${i}][name]" value="${esc(item.name)}"
                            placeholder="Nama item..."
                            class="item-field w-full bg-transparent border-0 border-b border-transparent
                                focus:border-emerald-400 focus:ring-0 px-2 py-1 outline-none transition-colors"
                            data-field="name">
                    </td>
                    <td class="p-2 md:p-3">
                        <input type="number" name="items[${i}][qty]" value="${item.qty}" min="1"
                            class="item-field w-full bg-transparent border-0 border-b border-transparent
                                focus:border-emerald-400 focus:ring-0 px-2 py-1 outline-none transition-colors"
                            data-field="qty">
                    </td>
                    <td class="p-2 md:p-3">
                        <input type="text" name="items[${i}][unit]" value="${esc(item.unit)}"
                            class="item-field w-full bg-transparent border-0 border-b border-transparent
                                focus:border-emerald-400 focus:ring-0 px-2 py-1 outline-none transition-colors text-slate-400"
                            data-field="unit">
                    </td>
                    <td class="p-2 md:p-3">
                        <input type="text" value="${formatRupiah(item.price)}"
                            class="item-price-display w-full bg-transparent border-0 border-b border-transparent
                                focus:border-emerald-400 focus:ring-0 px-2 py-1 outline-none transition-colors">
                        <input type="hidden" name="items[${i}][price]" value="${item.price}" class="item-price-hidden">
                    </td>
                    <td class="p-3 md:p-4 font-bold text-slate-800">${formatRupiah(rowTotal)}</td>
                    <td class="p-2 md:p-3">
                        <input type="text" name="items[${i}][desc]" value="${esc(item.desc)}"
                            placeholder="Catatan..."
                            class="item-field w-full bg-transparent border-0 border-b border-transparent
                                focus:border-emerald-400 focus:ring-0 px-2 py-1 outline-none transition-colors text-slate-400"
                            data-field="desc">
                    </td>
                    <td class="p-3 md:p-4 text-center">
                        <button type="button" class="item-delete text-slate-300 hover:text-red-500 transition-colors">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </td>`;
                itemsTbody.appendChild(tr);
            });

            displayTotalItems.textContent = formatRupiah(totalAmount);
            formTotalAmount.value         = totalAmount;
            finalTotal.textContent        = formatRupiah(totalAmount);

            if (typeof lucide !== 'undefined') lucide.createIcons();
            renderDistribution();   // update alokasi saat total berubah
        }

        // ─────────────────────────────────────────────
        // ITEMS — event delegation (1x listener, tidak rebind)
        // ─────────────────────────────────────────────
        itemsTbody.addEventListener('input', function (e) {
            const tr = e.target.closest('tr[data-idx]');
            if (!tr) return;
            const idx = parseInt(tr.dataset.idx);

            if (e.target.classList.contains('item-field')) {
                items[idx][e.target.dataset.field] = e.target.value;
                if (e.target.dataset.field === 'qty') renderItems();
            }
            if (e.target.classList.contains('item-price-display')) {
                const raw = parseNumber(e.target.value);
                items[idx].price = raw;
                tr.querySelector('.item-price-hidden').value = raw;
            }
        });

        itemsTbody.addEventListener('blur', function (e) {
            if (e.target.classList.contains('item-price-display')) {
                const tr = e.target.closest('tr[data-idx]');
                if (!tr) return;
                items[parseInt(tr.dataset.idx)].price = parseNumber(e.target.value);
                renderItems();
            }
        }, true);

        itemsTbody.addEventListener('click', function (e) {
            const btn = e.target.closest('.item-delete');
            if (!btn) return;
            const tr = btn.closest('tr[data-idx]');
            if (!tr) return;
            items.splice(parseInt(tr.dataset.idx), 1);
            renderItems();
        });

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
                    this.classList.remove('bg-emerald-500', 'text-white', 'border-emerald-500');
                    this.classList.add('border-slate-200', 'text-slate-500');
                } else {
                    selectedBranches.push({ id, name, value: 0, percent: 0 });
                    this.classList.remove('border-slate-200', 'text-slate-500');
                    this.classList.add('bg-emerald-500', 'text-white', 'border-emerald-500');
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
        // DISTRIBUTION INPUTS — event delegation
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

        // ─────────────────────────────────────────────
        // RENDER DISTRIBUTION (rows)
        // ─────────────────────────────────────────────
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
                // Hitung value
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

                // Input HTML
                let inputHtml = '';
                if (currentMethod === 'equal') {
                    inputHtml = `<span class="text-emerald-500 font-bold text-sm">${formatRupiah(branch.value)}</span>`;
                } else if (currentMethod === 'percent') {
                    inputHtml = `
                        <div class="flex items-center gap-2">
                            <input type="number" step="0.1" min="0" max="100"
                                value="${branch.percent || 0}"
                                class="dist-pct w-16 text-right text-sm border border-slate-200 rounded-lg
                                    px-2 py-1.5 focus:ring-2 focus:ring-emerald-300 focus:border-emerald-400 outline-none"
                                data-idx="${idx}">
                            <span class="text-xs font-bold text-slate-400">%</span>
                            <span class="text-emerald-500 font-bold text-sm w-24 text-right">${formatRupiah(branch.value)}</span>
                        </div>`;
                } else if (currentMethod === 'manual') {
                    const displayVal = branch.value > 0 ? branch.value.toLocaleString('id-ID') : '';
                    inputHtml = `
                        <div class="flex items-center gap-1">
                            <span class="text-xs text-slate-400 font-bold">Rp</span>
                            <input type="text" value="${displayVal}" placeholder="0"
                                class="dist-manual w-28 text-right text-sm border border-slate-200 rounded-lg
                                    px-2 py-1.5 focus:ring-2 focus:ring-emerald-300 focus:border-emerald-400 outline-none"
                                data-idx="${idx}">
                        </div>`;
                }

                const row = document.createElement('div');
                row.className = 'flex justify-between items-center text-xs md:text-sm bg-white p-3 rounded-xl border border-slate-100';
                row.innerHTML = `
                    <span class="text-slate-600 font-medium flex items-center gap-2">
                        <i data-lucide="building-2" class="w-3.5 h-3.5 text-slate-400"></i>
                        ${esc(branch.name)}
                    </span>
                    <div>${inputHtml}</div>`;
                activeBranchesList.appendChild(row);
            });

            if (typeof lucide !== 'undefined') lucide.createIcons();
            updateHiddenInputs();
            updateSummaryList();
            validateAndToggleSubmit();
        }

        // ─────────────────────────────────────────────
        // UPDATE hidden inputs
        // ─────────────────────────────────────────────
        function updateHiddenInputs() {
            if (!hiddenInputsContainer) return;
            hiddenInputsContainer.innerHTML = '';
            selectedBranches.forEach((branch, idx) => {
                hiddenInputsContainer.insertAdjacentHTML('beforeend', `
                    <input type="hidden" name="branches[${idx}][branch_id]"          value="${branch.id}">
                    <input type="hidden" name="branches[${idx}][allocation_amount]"  value="${Math.round(branch.value || 0)}">
                    <input type="hidden" name="branches[${idx}][allocation_percent]" value="${branch.percent || 0}">
                `);
            });
        }

        // ─────────────────────────────────────────────
        // UPDATE summary list (tanpa re-render rows)
        // ─────────────────────────────────────────────
        function updateSummaryList() {
            summaryBranchesList.innerHTML = '';
            selectedBranches.forEach(branch => {
                const pct = totalAmount > 0
                    ? ((branch.value / totalAmount) * 100).toFixed(1)
                    : (branch.percent || 0).toFixed(1);
                summaryBranchesList.insertAdjacentHTML('beforeend', `
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-300">${esc(branch.name)}</span>
                        <div class="text-right">
                            <div class="text-emerald-400 font-bold">${formatRupiah(branch.value)}</div>
                            <div class="text-[9px] text-slate-500">${pct}%</div>
                        </div>
                    </div>`);
            });
        }

        // ─────────────────────────────────────────────
        // VALIDATE & TOGGLE SUBMIT BUTTON
        // ─────────────────────────────────────────────
        function validateAndToggleSubmit() {
            let isValid = true;

            if (selectedBranches.length > 0 && currentMethod === 'percent') {
                const totalPct = selectedBranches.reduce((s, b) => s + (parseFloat(b.percent) || 0), 0);
                if (Math.abs(totalPct - 100) > 0.5) {
                    isValid = false;
                    if (percentWarning) {
                        percentWarning.textContent = `⚠ Total persen ${totalPct.toFixed(1)}% — harus 100%`;
                        percentWarning.classList.remove('hidden');
                    }
                } else {
                    if (percentWarning) percentWarning.classList.add('hidden');
                }
            } else {
                if (percentWarning) percentWarning.classList.add('hidden');
            }

            submitBtn.disabled = !isValid;
            if (isValid) {
                submitBtn.classList.remove('bg-slate-700', 'text-slate-500');
                submitBtn.classList.add('bg-emerald-500');
            } else {
                submitBtn.classList.add('bg-slate-700', 'text-slate-500');
                submitBtn.classList.remove('bg-emerald-500');
            }
        }

        // ─────────────────────────────────────────────
        // SUBMIT
        // ─────────────────────────────────────────────
        document.getElementById('transaction-form').addEventListener('submit', function (e) {
            if (submitBtn.disabled) { e.preventDefault(); return; }
            submitBtn.disabled = true;
            document.getElementById('submit-text').textContent = 'Memproses...';
            document.getElementById('submit-spinner').classList.remove('hidden');
        });

        // ─────────────────────────────────────────────
        // INIT
        // ─────────────────────────────────────────────
        renderItems();
    });
    </script>
@endpush
@endsection