@extends('layouts.app')

@section('page-title', 'Form Reimbursement')

@section('content')
    <div class="max-w-4xl mx-auto px-4 md:px-6 lg:px-8 py-8 lg:py-12">

        {{-- Form Container --}}
        <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-6 md:p-8 lg:p-10">
            
            {{-- Header --}}
            <div class="mb-8 md:mb-10 flex items-center gap-4">
                <a href="{{ route('transactions.create') }}" class="w-8 h-8 flex items-center justify-center rounded-full border border-slate-200 text-slate-400 hover:text-emerald-600 hover:border-emerald-200 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-slate-800 tracking-tight">Form Reimbursement</h1>
                    <p class="text-xs md:text-sm text-slate-400 mt-1">Lengkapi data klaim pengeluaran Anda dengan presisi</p>
                </div>
            </div>

            <form method="POST" action="{{ route('rembush.store') }}" id="transaction-form" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="type" value="rembush">
                @if(isset($uploadId))
                    <input type="hidden" id="upload-id" value="{{ $uploadId }}">
                @endif
                <input type="hidden" name="amount" id="form-total-amount" value="{{ old('amount', 0) }}">

                {{-- 1. FOTO NOTA --}}
                <div class="mb-8 md:mb-10">
                    <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-3 tracking-wider">Foto Nota</label>
                    <div class="border-2 border-dashed border-emerald-400 rounded-2xl p-2 bg-slate-50/50 flex justify-center relative overflow-hidden group">
                        @if(isset($base64) && str_contains($mime, 'image'))
                            <img src="data:{{ $mime }};base64,{{ $base64 }}" class="w-auto h-48 md:h-64 object-contain rounded-xl shadow-sm relative z-10" />
                        @else
                            <div class="w-full h-48 md:h-64 flex flex-col items-center justify-center text-slate-400">
                                <i data-lucide="image" class="w-8 h-8 mb-2 opacity-50"></i>
                                <span class="text-xs font-medium">Tidak ada nota yang diunggah</span>
                            </div>
                        @endif
                    </div>

                    {{-- Alert Tips --}}
                    <div class="mt-4 bg-orange-50 border border-orange-100/50 rounded-xl p-3 md:p-4 flex gap-3 md:gap-4 items-start">
                        <div class="bg-orange-100 p-1.5 md:p-2 rounded-lg text-orange-500 shrink-0">
                            <i data-lucide="shield-alert" class="w-4 h-4 md:w-5 md:h-5"></i>
                        </div>
                        <div>
                            <h4 class="text-[9px] md:text-[10px] font-bold text-orange-800 uppercase tracking-wider mb-1">Tips Penting</h4>
                            <p class="text-[11px] md:text-xs text-orange-600 leading-relaxed">Segera foto nota setelah transaksi. Nota yang lecek atau tinta pudar berisiko tinggi ditolak oleh sistem verifikasi admin.</p>
                        </div>
                    </div>
                </div>

                {{-- 2. MAIN INFO FIELDS --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6 mb-8 md:mb-10">

                    <div>
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Nama Vendor</label>
                        <input type="text" name="customer" id="customer" required value="{{ old('customer', '') }}"
                            placeholder="Contoh: Jetis..."
                            class="w-full border border-slate-200 rounded-xl p-3 md:p-3.5 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 outline-none transition-all" />
                    </div>
                    
                    <div>
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Tanggal Transaksi</label>
                        <input type="date" name="date" id="date" required value="{{ old('date',now()->format('Y-m-d')) }}"
                            class="w-full border border-slate-200 rounded-xl p-3 md:p-3.5 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 outline-none transition-all" />
                    </div>
                    
                    {{-- Kategori (Baru ditambahkan) --}}
                    <div>
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Kategori</label>
                        <div class="relative">
                            <select name="category" id="category" required
                                class="w-full appearance-none bg-white border border-slate-200 rounded-xl p-3 md:p-3.5 pr-10 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 outline-none transition-all">
                                <option value="" disabled {{ old('category') ? '' : 'selected' }}>Pilih kategori...</option>
                                @foreach(\App\Models\Transaction::CATEGORIES as $key => $label)
                                    <option value="{{ $key }}" {{ old('category') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-3 md:right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>

                    {{-- Metode Pencairan (Baru ditambahkan) --}}
                    <div>
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Metode Pencairan</label>
                        <div class="relative">
                            <select name="payment_method" id="payment_method" required
                                class="w-full appearance-none bg-white border border-slate-200 rounded-xl p-3 md:p-3.5 pr-10 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 outline-none transition-all">
                                <option value="" disabled {{ old('payment_method') ? '' : 'selected' }}>Pilih metode pembayaran...</option>
                                @foreach(\App\Models\Transaction::PAYMENT_METHODS as $key => $label)
                                    <option value="{{ $key }}" {{ old('payment_method') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-3 md:right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Keterangan</label>
                        <textarea name="description" id="description" rows="2" placeholder="Nota pembelian dari..."
                            class="w-full border border-slate-200 rounded-xl p-3 md:p-3.5 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 outline-none resize-none transition-all">{{ old('description') }}</textarea>
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
                        @error('branches')
                            <p class="mt-2 text-red-500 font-bold text-[10px] md:text-xs">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Divider --}}
                <div class="relative flex justify-center items-center mb-8">
                    <div class="w-full h-px bg-slate-100 absolute"></div>
                    <span class="bg-white px-4 relative z-10 text-[9px] md:text-[10px] font-bold text-slate-300 uppercase tracking-[0.2em]">Summary Billing</span>
                </div>

                {{-- 5. SUMMARY BILLING --}}
                <div class="bg-[#1a1c23] rounded-3xl p-6 md:p-8 lg:p-10 text-white relative overflow-hidden shadow-xl">
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
                    <button type="submit" id="submit-btn" disabled
                        class="w-full relative z-10 bg-emerald-500 hover:bg-emerald-400 disabled:bg-slate-700 disabled:text-slate-500 text-white font-bold py-4 md:py-5 rounded-xl transition-all shadow-[0_8px_20px_-6px_rgba(16,185,129,0.4)] disabled:shadow-none text-xs md:text-sm uppercase tracking-wider cursor-pointer disabled:cursor-not-allowed flex items-center justify-center gap-2">
                        <span id="submit-text">Kirim Pengajuan Rembush</span>
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // State
            let items = [{ name: '', qty: 1, unit: 'pcs', price: 0, desc: '' }];
            let allocMode = 'equal';
            let activeBranches = []; // Stores objects: { id, name, value, percent }
            let totalAmount = 0;

            // DOM Elements
            const itemsTbody = document.getElementById('items-tbody');
            const addItemBtn = document.getElementById('add-item-btn');
            const displayTotalItems = document.getElementById('display-total-items');
            const formTotalAmount = document.getElementById('form-total-amount');
            
            const branchPills = document.querySelectorAll('.branch-pill');
            const allocationContainer = document.getElementById('allocation-container');
            const activeBranchesList = document.getElementById('active-branches-list');
            const modeBtns = document.querySelectorAll('.alloc-mode-btn');

            const finalTotal = document.getElementById('final-total');
            const summaryModeBadge = document.getElementById('summary-mode-badge');
            const summaryCountBadge = document.getElementById('summary-count-badge');
            const summaryBranchesList = document.getElementById('summary-branches-list');
            const submitBtn = document.getElementById('submit-btn');

            // Utilities
            const formatRupiah = (num) => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(num || 0));
            const parseNumber = (str) => parseInt(str.toString().replace(/[^0-9]/g, '')) || 0;

            // ==========================================
            // 🛒 ITEMS MANAGEMENT
            // ==========================================
            function renderItems() {
                itemsTbody.innerHTML = '';
                totalAmount = 0;

                if(items.length === 0) {
                    itemsTbody.innerHTML = `<tr><td colspan="8" class="p-6 text-center text-xs text-slate-400">Belum ada barang. Klik 'Tambah Baris'.</td></tr>`;
                }

                items.forEach((item, index) => {
                    const rowTotal = item.qty * item.price;
                    totalAmount += rowTotal;

                    const tr = document.createElement('tr');
                    tr.className = "text-slate-600 text-xs hover:bg-slate-50/50 transition-colors";
                    tr.innerHTML = `
                        <td class="p-3 md:p-4 text-center text-slate-400 font-medium">${index + 1}</td>
                        <td class="p-2 md:p-3">
                            <input type="text" name="items[${index}][name]" value="${item.name}" placeholder="Nama item..." class="item-input w-full bg-transparent border-0 border-b border-transparent focus:border-emerald-400 focus:ring-0 px-2 py-1 outline-none transition-colors" data-idx="${index}" data-field="name">
                        </td>
                        <td class="p-2 md:p-3">
                            <input type="number" name="items[${index}][qty]" value="${item.qty}" min="1" class="item-input w-full bg-transparent border-0 border-b border-transparent focus:border-emerald-400 focus:ring-0 px-2 py-1 outline-none transition-colors" data-idx="${index}" data-field="qty">
                        </td>
                        <td class="p-2 md:p-3">
                            <input type="text" name="items[${index}][unit]" value="${item.unit}" class="item-input w-full bg-transparent border-0 border-b border-transparent focus:border-emerald-400 focus:ring-0 px-2 py-1 outline-none transition-colors text-slate-400" data-idx="${index}" data-field="unit">
                        </td>
                        <td class="p-2 md:p-3">
                            <input type="text" value="${item.price}" class="item-price-input w-full bg-transparent border-0 border-b border-transparent focus:border-emerald-400 focus:ring-0 px-2 py-1 outline-none transition-colors" data-idx="${index}">
                            <input type="hidden" name="items[${index}][price]" value="${item.price}">
                        </td>
                        <td class="p-3 md:p-4 font-bold text-slate-800">${formatRupiah(rowTotal)}</td>
                        <td class="p-2 md:p-3">
                            <input type="text" name="items[${index}][desc]" value="${item.desc}" placeholder="Catatan..." class="item-input w-full bg-transparent border-0 border-b border-transparent focus:border-emerald-400 focus:ring-0 px-2 py-1 outline-none transition-colors text-slate-400" data-idx="${index}" data-field="desc">
                        </td>
                        <td class="p-3 md:p-4 text-center">
                            <button type="button" class="delete-item-btn text-slate-300 hover:text-red-500 transition-colors" data-idx="${index}">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </td>
                    `;
                    itemsTbody.appendChild(tr);
                });

                // Update UI Texts
                displayTotalItems.textContent = formatRupiah(totalAmount);
                formTotalAmount.value = totalAmount;
                finalTotal.textContent = formatRupiah(totalAmount);
                
                lucide.createIcons();
                bindItemEvents();
                recalculateAllocations(); 
            }

            function bindItemEvents() {
                document.querySelectorAll('.item-input').forEach(input => {
                    input.addEventListener('input', (e) => {
                        const idx = e.target.dataset.idx;
                        const field = e.target.dataset.field;
                        items[idx][field] = e.target.value;
                        if(field === 'qty') renderItems(); 
                    });
                });

                document.querySelectorAll('.item-price-input').forEach(input => {
                    input.addEventListener('blur', (e) => {
                        const idx = e.target.dataset.idx;
                        items[idx].price = parseNumber(e.target.value);
                        renderItems();
                    });
                });

                document.querySelectorAll('.delete-item-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const idx = e.currentTarget.dataset.idx;
                        items.splice(idx, 1);
                        renderItems();
                    });
                });
            }

            addItemBtn.addEventListener('click', () => {
                items.push({ name: '', qty: 1, unit: 'pcs', price: 0, desc: '' });
                renderItems();
            });

            // ==========================================
            // 🏢 BRANCH ALLOCATION
            // ==========================================
            
            // Toggle Pills
            branchPills.forEach(pill => {
                pill.addEventListener('click', function() {
                    const id = this.dataset.branchId;
                    const name = this.dataset.branchName;
                    
                    const isSelected = this.classList.contains('bg-emerald-500');
                    if (isSelected) {
                        this.classList.remove('bg-emerald-500', 'text-white', 'border-emerald-500');
                        this.classList.add('border-slate-200', 'text-slate-500');
                        activeBranches = activeBranches.filter(b => b.id !== id);
                    } else {
                        this.classList.remove('border-slate-200', 'text-slate-500');
                        this.classList.add('bg-emerald-500', 'text-white', 'border-emerald-500');
                        activeBranches.push({ id, name, value: 0, percent: 0 });
                    }
                    
                    allocationContainer.style.display = activeBranches.length > 0 ? 'block' : 'none';
                    recalculateAllocations();
                });
            });

            // Mode Toggles
            modeBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    allocMode = this.dataset.mode;
                    modeBtns.forEach(b => {
                        b.classList.remove('bg-white', 'shadow-sm', 'text-slate-800');
                        b.classList.add('text-slate-400');
                    });
                    this.classList.add('bg-white', 'shadow-sm', 'text-slate-800');
                    this.classList.remove('text-slate-400');
                    summaryModeBadge.textContent = 'Metode: ' + (allocMode === 'equal' ? 'Bagi Rata' : allocMode === 'percent' ? 'Persentase' : 'Manual');
                    recalculateAllocations();
                });
            });

            function recalculateAllocations() {
                const count = activeBranches.length;
                summaryCountBadge.textContent = `${count} Cabang`;

                if (count === 0) {
                    renderAllocationsUI();
                    return;
                }

                activeBranches.forEach((branch, idx) => {
                    if (allocMode === 'equal') {
                        branch.percent = parseFloat((100 / count).toFixed(2));
                        branch.value = Math.round((totalAmount * branch.percent) / 100);
                    } else if (allocMode === 'percent') {
                        branch.value = Math.round((totalAmount * branch.percent) / 100);
                    } else if (allocMode === 'manual') {
                        branch.percent = totalAmount > 0 ? parseFloat(((branch.value / totalAmount) * 100).toFixed(2)) : 0;
                    }
                });

                renderAllocationsUI();
            }

            function renderAllocationsUI() {
                activeBranchesList.innerHTML = '';
                summaryBranchesList.innerHTML = '';
                
                let isAllocValid = true;
                let totalPct = 0;

                if (activeBranches.length === 0) {
                    summaryBranchesList.innerHTML = `<div class="text-xs text-slate-500 italic">Pilih cabang terlebih dahulu...</div>`;
                    submitBtn.disabled = true;
                    return;
                }

                activeBranches.forEach((branch, idx) => {
                    totalPct += branch.percent;

                    const row = document.createElement('div');
                    row.className = "flex justify-between items-center text-xs md:text-sm bg-white p-3 rounded-xl border border-slate-100";
                    
                    let inputHtml = '';
                    if (allocMode === 'equal') {
                        inputHtml = `<span class="text-emerald-500 font-bold">${formatRupiah(branch.value)}</span>`;
                    } else if (allocMode === 'percent') {
                        inputHtml = `
                            <div class="flex items-center gap-2">
                                <input type="number" step="0.1" value="${branch.percent}" class="alloc-input-pct w-16 text-right border border-slate-200 rounded p-1 text-xs focus:ring-1 outline-none" data-idx="${idx}">
                                <span class="text-slate-400">%</span>
                                <span class="text-emerald-500 font-bold ml-2 w-24 text-right">${formatRupiah(branch.value)}</span>
                            </div>
                        `;
                    } else if (allocMode === 'manual') {
                        inputHtml = `
                            <div class="flex items-center gap-2">
                                <span class="text-slate-400 text-[10px]">Rp</span>
                                <input type="number" value="${branch.value}" class="alloc-input-val w-28 text-right border border-slate-200 rounded p-1 text-xs focus:ring-1 outline-none" data-idx="${idx}">
                            </div>
                        `;
                    }

                    row.innerHTML = `
                        <span class="text-slate-600 font-medium flex items-center gap-2">
                            <i data-lucide="building-2" class="w-3.5 h-3.5 text-slate-400"></i>
                            ${branch.name}
                        </span>
                        <div>
                            ${inputHtml}
                            <input type="hidden" name="branches[${idx}][branch_id]" value="${branch.id}">
                            <input type="hidden" name="branches[${idx}][allocation_amount]" value="${branch.value}">
                            <input type="hidden" name="branches[${idx}][allocation_percent]" value="${branch.percent}">
                        </div>
                    `;
                    activeBranchesList.appendChild(row);

                    const sumRow = document.createElement('div');
                    sumRow.className = "flex justify-between items-center text-xs";
                    sumRow.innerHTML = `
                        <span class="text-slate-300">${branch.name}</span>
                        <div class="text-right">
                            <div class="text-emerald-400 font-bold">${formatRupiah(branch.value)}</div>
                            <div class="text-[9px] text-slate-500">${branch.percent.toFixed(1)}%</div>
                        </div>
                    `;
                    summaryBranchesList.appendChild(sumRow);
                });

                if (Math.abs(totalPct - 100) > 1 || totalAmount <= 0) isAllocValid = false;
                submitBtn.disabled = !isAllocValid;

                lucide.createIcons();
                bindAllocInputs();
            }

            function bindAllocInputs() {
                document.querySelectorAll('.alloc-input-pct').forEach(input => {
                    input.addEventListener('input', (e) => {
                        const idx = e.target.dataset.idx;
                        activeBranches[idx].percent = parseFloat(e.target.value) || 0;
                        activeBranches[idx].value = Math.round((totalAmount * activeBranches[idx].percent) / 100);
                        renderAllocationsUI(); 
                    });
                });
                document.querySelectorAll('.alloc-input-val').forEach(input => {
                    input.addEventListener('input', (e) => {
                        const idx = e.target.dataset.idx;
                        activeBranches[idx].value = parseInt(e.target.value) || 0;
                        activeBranches[idx].percent = totalAmount > 0 ? parseFloat(((activeBranches[idx].value / totalAmount) * 100).toFixed(2)) : 0;
                        renderAllocationsUI();
                    });
                });
            }

            document.getElementById('transaction-form').addEventListener('submit', function (e) {
                if (submitBtn.disabled) {
                    e.preventDefault();
                    return;
                }
                submitBtn.disabled = true;
                document.getElementById('submit-text').textContent = 'Memproses...';
                document.getElementById('submit-spinner').classList.remove('hidden');
            });

            renderItems();
        });
    </script>
    @endpush
@endsection