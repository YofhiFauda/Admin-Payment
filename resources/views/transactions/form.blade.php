@extends('layouts.app')

@section('page-title', 'Buat Pengajuan')

@section('content')
    <div class="max-w-7xl mx-auto px-4 md:px-6 lg:px-8 py-6 lg:py-10">

        {{-- Stepper --}}
        <div class="flex items-center justify-center mb-4 md:mb-5 lg:mb-6">
            <div class="flex flex-col items-center">
                <div
                    class="w-7 h-7 md:w-8 md:h-8 lg:w-9 lg:h-9 rounded-lg md:rounded-xl flex items-center justify-center border-2 border-blue-600 bg-blue-600 text-white">
                    <i data-lucide="upload" class="w-3 h-3 md:w-3.5 md:h-3.5 lg:w-4 lg:h-4"></i>
                </div>
                <span
                    class="mt-1 md:mt-1.5 text-[7px] md:text-[8px] lg:text-[9px] font-bold uppercase tracking-wider text-blue-600">1.
                    Scan</span>
            </div>
            <div class="w-8 md:w-12 lg:w-16 h-0.5 mx-1.5 md:mx-2 rounded-full bg-blue-600"></div>
            <div class="flex flex-col items-center">
                <div
                    class="w-7 h-7 md:w-8 md:h-8 lg:w-9 lg:h-9 rounded-lg md:rounded-xl flex items-center justify-center border-2 border-blue-600 bg-blue-600 text-white">
                    <i data-lucide="calculator" class="w-3 h-3 md:w-3.5 md:h-3.5 lg:w-4 lg:h-4"></i>
                </div>
                <span
                    class="mt-1 md:mt-1.5 text-[7px] md:text-[8px] lg:text-[9px] font-bold uppercase tracking-wider text-blue-600">2.
                    Alokasi</span>
            </div>
        </div>

        {{-- Form --}}
        <form method="POST" action="{{ route('transactions.store') }}" id="transaction-form" enctype="multipart/form-data"
class="space-y-6 lg:space-y-12">
            @csrf
            @if(isset($uploadId))
                <input type="hidden" id="upload-id" value="{{ $uploadId }}">
            @endif

            <div class="space-y-3 md:space-y-4 lg:space-y-5">
                {{-- Image Preview --}}
                @if(isset($base64))
                    <div
                        class="bg-white/80 backdrop-blur-sm p-4 rounded-xl shadow-md hover:shadow-lg border border-gray-100 transition-all duration-300">

                        <p class="text-xs font-bold text-slate-400 uppercase mb-2">
                            Nota yang diunggah
                        </p>

                        <div class="bg-slate-50 rounded-lg overflow-hidden text-center p-4">
                            @if(str_contains($mime, 'image'))
                                <img src="data:{{ $mime }};base64,{{ $base64 }}" class="w-full max-h-60 object-contain mx-auto" />
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Main Info Card --}}
                <div
                    class="bg-white/80 backdrop-blur-sm p-3 md:p-4 lg:p-6 rounded-lg md:rounded-xl lg:rounded-2xl shadow-md hover:shadow-lg border border-gray-100 border-b-4 border-b-blue-600 transition-all duration-300">

                    <a href="{{ route('transactions.create') }}"
                        class="flex items-center gap-1 md:gap-1.5 mb-3 md:mb-4 lg:mb-5 text-slate-400 hover:text-blue-600 font-bold text-xs md:text-smuppercase tracking-wider transition-all">
                        <i data-lucide="arrow-left" class="w-3 h-3 md:w-3.5 md:h-3.5"></i> Kembali
                    </a>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 md:gap-3 lg:gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs md:text-sm
                                     font-bold text-slate-400 uppercase mb-1 md:mb-1.5 tracking-wider">Penerima
                                Dana / Vendor</label>
                            <input type="text" name="customer" id="customer" required value="{{ old('customer', '') }}"
                                placeholder="Nama vendor..."
                                class="w-full bg-slate-50 border border-slate-200 rounded-md md:rounded-lg p-2 md:p-2.5 lg:p-3 outline-none focus:ring-2 focus:ring-blue-100 font-medium text-xs md:text-sm" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs md:text-sm
                                     font-bold text-slate-400 uppercase mb-1 md:mb-1.5 tracking-wider">Kategori</label>
                            <div class="relative">
                                <select name="category" id="category" required
                                    class="w-full appearance-none bg-slate-50 border border-slate-200 rounded-md md:rounded-lg p-2 md:p-2.5 lg:p-3 pr-8 md:pr-10 outline-none focus:ring-2 focus:ring-blue-100 font-medium text-xs md:text-sm">
                                    <option value="" disabled {{ old('category') ? '' : 'selected' }}>Pilih kategori...</option>
                                    @foreach(\App\Models\Transaction::CATEGORIES as $key => $label)
                                        <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <i data-lucide="chevron-down"
                                    class="w-3 h-3 md:w-3.5 md:h-3.5 absolute right-2.5 md:right-3 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs md:text-sm
                                     font-bold text-blue-600 uppercase mb-1 md:mb-1.5 tracking-wider">Total
                                Nominal</label>
                            <div class="relative">
                                <span
                                    class="absolute left-2.5 md:left-3 lg:left-3.5 top-1/2 -translate-y-1/2 font-semibold text-blue-400 text-[10px] md:text-xs lg:text-sm pointer-events-none select-none">Rp</span>
                                <input type="number" name="amount" required id="total-amount" value="{{ old('amount', '') }}"
                                    placeholder="0"
                                    class="w-full bg-blue-50/30 border border-blue-100 rounded-md md:rounded-lg p-2 md:p-2.5 lg:p-3 pl-10 md:pl-12 lg:pl-14 outline-none focus:ring-2 focus:ring-blue-100 font-bold text-base md:text-lg lg:text-xl text-blue-700 placeholder:text-blue-300" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs md:text-sm
                                     font-bold text-slate-400 uppercase mb-1 md:mb-1.5 tracking-wider">Tanggal
                                Terbit</label>
                            <input type="date" name="date" id="date" value="{{ old('date',now()->format('Y-m-d')) }}"
                                class="w-full bg-slate-50 border border-slate-200 rounded-md md:rounded-lg p-2 md:p-2.5 lg:p-3 outline-none focus:ring-2 focus:ring-blue-100 font-medium text-xs md:text-sm" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs md:text-sm
                                     font-bold text-slate-400 uppercase mb-1 md:mb-1.5 tracking-wider">Keterangan</label>
                            <textarea name="items" id="items" rows="2" placeholder="Deskripsi transaksi..."
                                class="w-full bg-slate-50 border border-slate-200 rounded-md md:rounded-lg p-2 md:p-2.5 lg:p-3 outline-none focus:ring-2 focus:ring-blue-100 font-medium text-xs md:text-sm resize-none">{{ old('items') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Branch Allocation --}}
                <div
                    class="bg-white/80 backdrop-blur-sm p-3 md:p-4 lg:p-6 rounded-lg md:rounded-xl lg:rounded-2xl shadow-md hover:shadow-lg border border-gray-100 transition-all duration-300">

                    <div
                        class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 md:gap-3 mb-3 md:mb-4 lg:mb-5">
                        <h3
                            class="text-xs md:text-sm lg:text-base font-bold flex items-center gap-1.5 md:gap-2 text-slate-800">
                            <i data-lucide="building-2" class="w-3.5 h-3.5 md:w-4 md:h-4 lg:w-5 lg:h-5 text-blue-600"></i>
                            Distribusi Cabang
                        </h3>
                        <div class="flex bg-slate-100 p-0.5 md:p-1 rounded-md md:rounded-lg w-full sm:w-auto">
                            <button type="button" data-mode="equal" class="alloc-mode-btn flex-1 sm:flex-none px-2.5 md:px-3 lg:px-4 py-1.5 md:py-2 rounded text-xs md:text-sm
                                     lg:text-[10px] font-bold transition-all bg-white shadow text-blue-600">RATA</button>
                            <button type="button" data-mode="percent" class="alloc-mode-btn flex-1 sm:flex-none px-2.5 md:px-3 lg:px-4 py-1.5 md:py-2 rounded text-xs md:text-sm
                                     lg:text-[10px] font-bold transition-all text-slate-400">PERSEN</button>
                            <button type="button" data-mode="manual" class="alloc-mode-btn flex-1 sm:flex-none px-2.5 md:px-3 lg:px-4 py-1.5 md:py-2 rounded text-xs md:text-sm
                                     lg:text-[10px] font-bold transition-all text-slate-400">MANUAL</button>
                        </div>
                    </div>

                    <div id="branches-container" class="space-y-2 md:space-y-3">
                        <div class="branch-row flex flex-col sm:flex-row gap-2 md:gap-3 items-end bg-slate-50 p-2.5 md:p-3 lg:p-4 rounded-lg md:rounded-xl border border-slate-100"
                            data-index="0">
                            <div class="flex-1 w-full">
                                <label class="block text-xs md:text-sm
                                     font-bold text-slate-400 uppercase mb-1 md:mb-1.5 tracking-wider">Cabang</label>
                                <div class="relative">
                                    <select name="branches[0][branch_id]"
                                        class="branch-select w-full appearance-none border border-slate-200 rounded-md md:rounded-lg p-2 md:p-2.5 text-xs md:text-sm font-medium bg-white pr-7 md:pr-8 outline-none focus:ring-2 focus:ring-blue-100">
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                    <i data-lucide="chevron-down"
                                        class="w-3 h-3 md:w-3.5 md:h-3.5 absolute right-2.5 md:right-3 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                                </div>
                            </div>
                            <div class="w-full sm:w-32 md:w-36">
                                <label class="alloc-label block text-xs md:text-sm
                                     font-bold text-slate-400 uppercase mb-1 md:mb-1.5 tracking-wider">Porsi
                                    (%)</label>
                                <div class="relative">
                                    <input type="number" step="0.01"
                                        class="alloc-percent w-full border border-slate-200 rounded-md md:rounded-lg p-2 md:p-2.5 pr-7 md:pr-8 text-xs md:text-sm font-bold bg-white outline-none focus:ring-2 focus:ring-blue-100"
                                        value="100" />
                                    <span
                                        class="alloc-suffix absolute right-2.5 md:right-3 top-1/2 -translate-y-1/2 font-bold text-slate-300 text-xs md:text-sm pointer-events-none">%</span>
                                </div>
                            </div>
                            <input type="hidden" name="branches[0][allocation_percent]" class="alloc-percent-hidden"
                                value="100" />
                            <input type="hidden" name="branches[0][allocation_amount]" class="alloc-amount" value="0" />
                        </div>
                    </div>

                    <button type="button" id="add-branch-btn"
                        class="mt-3 md:mt-4 w-full border-2 border-dotted border-slate-200 rounded-lg md:rounded-xl py-2.5 md:py-3 lg:py-4 text-slate-400 font-bold text-[9px] md:text-xs hover:border-blue-200 hover:text-blue-600 transition-all uppercase tracking-wider flex items-center justify-center gap-1.5 md:gap-2 cursor-pointer">
                        <i data-lucide="plus" class="w-3.5 h-3.5 md:w-4 md:h-4"></i> Tambah Cabang
                    </button>

                    @error('branches')
                        <p class="mt-2 md:mt-3 text-red-500 font-bold text-[9px] md:text-xs">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Summary Sidebar --}}
            <div>
                <div
                    class="bg-slate-900 p-4 md:p-5 lg:p-6 rounded-lg md:rounded-xl lg:rounded-2xl text-white shadow-xl lg:sticky lg:top-24 border border-white/5">
                    <h4 class="text-xs md:text-sm
                                     font-bold text-blue-400 mb-4 md:mb-5 uppercase tracking-widest">
                        Billing Summary</h4>
                    <div class="space-y-3 md:space-y-4 mb-5 md:mb-6">
                        <div class="flex justify-between items-end border-b border-white/10 pb-3 md:pb-4">
                            <span class="text-slate-400 text-[9px] md:text-[10px] font-bold uppercase">Total</span>
                            <span id="summary-total"
                                class="font-bold text-base md:text-lg lg:text-xl text-white tracking-tight">Rp
                                0</span>
                        </div>
                        <div class="flex justify-between items-center border-b border-white/10 pb-3 md:pb-4">
                            <span class="text-slate-400 text-[9px] md:text-[10px] font-bold uppercase">Distribusi</span>
                            <div id="alloc-badge" class="px-2.5 md:px-3 py-1 md:py-1.5 rounded-full flex items-center gap-1.5 md:gap-2 font-bold text-xs md:text-sm
                                     uppercase tracking-wider bg-red-500/20 text-red-400">
                                <i data-lucide="alert-circle" class="w-2.5 h-2.5 md:w-3 md:h-3"></i>
                                <span id="alloc-percent-display">100.0%</span>
                            </div>
                        </div>
                        {{-- Detail Distribusi Per Cabang --}}
                        <div class="pt-3 border-t border-white/10 hidden lg:block">
                            <p class="text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-3">
                                Rincian Distribusi
                            </p>

                            <div id="summary-branches" class="space-y-2 text-xs">
                                <!-- Auto generated by JS -->
                            </div>
                        </div>

                    </div>
                    <button type="submit" id="submit-btn"
                        class="w-full bg-blue-600 hover:bg-blue-500 disabled:bg-slate-800 disabled:text-slate-600 text-white font-bold py-3 md:py-3.5 lg:py-4 rounded-lg md:rounded-xl transition-all shadow-xl text-xs md:text-sm uppercase tracking-wider cursor-pointer disabled:cursor-not-allowed flex items-center justify-center gap-2">

                        <span id="submit-text">Kirim Pengajuan</span>

                        <svg id="submit-spinner" class="animate-spin h-4 w-4 hidden" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                    </button>

                </div>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const branchesJson = @json($branches);
            let allocMode = 'equal';
            let branchIndex = 1;

            const container = document.getElementById('branches-container');
            const totalInput = document.getElementById('total-amount');
            const summaryTotal = document.getElementById('summary-total');
            const allocBadge = document.getElementById('alloc-badge');
            const allocDisplay = document.getElementById('alloc-percent-display');
            const submitBtn = document.getElementById('submit-btn');
            let isSubmitting = false;
            const form = document.getElementById('transaction-form');

            // ==========================================
            // 🔍 DEBUG PANEL
            // ==========================================
            const debugPanel = document.createElement('div');
            debugPanel.className = "fixed bottom-4 right-4 bg-slate-800 text-white p-3 rounded-lg text-xs max-w-sm max-h-80 overflow-y-auto z-50 shadow-xl font-mono";
            debugPanel.innerHTML = "<strong>🔍 AI Debug:</strong><br>";
            document.body.appendChild(debugPanel);

            function debug(msg, type = 'success') {
                const color = type === 'error' ? 'text-red-400' : type === 'warning' ? 'text-yellow-400' : 'text-green-400';
                debugPanel.innerHTML += `<div class="${color}">${msg}</div>`;
                console.log("[AI Debug]", msg);
            }

            function formatRupiah(num) {
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(num || 0);
            }

            // Mode buttons
            document.querySelectorAll('.alloc-mode-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    allocMode = this.dataset.mode;
                    document.querySelectorAll('.alloc-mode-btn').forEach(b => {
                        b.classList.remove('bg-white', 'shadow', 'text-blue-600');
                        b.classList.add('text-slate-400');
                    });
                    this.classList.add('bg-white', 'shadow', 'text-blue-600');
                    this.classList.remove('text-slate-400');
                    recalc();
                });
            });

            // Add Branch
            document.getElementById('add-branch-btn').addEventListener('click', function () {
                const idx = branchIndex++;
                const opts = branchesJson
                    .map(b => `<option value="${b.id}">${b.name}</option>`)
                    .join('');

                const row = document.createElement('div');
                row.className = `
                    branch-row flex flex-col sm:flex-row gap-2 md:gap-3 items-end 
                    bg-slate-50 p-2.5 md:p-3 lg:p-4 rounded-lg md:rounded-xl border border-slate-100
                `;
                row.dataset.index = idx;

                row.innerHTML = `
                    <div class="flex-1 w-full">
                        <label class="block text-xs md:text-sm font-bold text-slate-400 uppercase mb-1 md:mb-1.5 tracking-wider">Cabang</label>
                        <div class="relative">
                            <select name="branches[${idx}][branch_id]" class="branch-select w-full appearance-none border border-slate-200 rounded-md md:rounded-lg p-2 md:p-2.5 text-xs md:text-sm font-medium bg-white pr-7 md:pr-8 outline-none focus:ring-2 focus:ring-blue-100">
                                ${opts}
                            </select>
                            <i data-lucide="chevron-down" class="w-3 h-3 md:w-3.5 md:h-3.5 absolute right-2.5 md:right-3 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                        </div>
                    </div>
                    <div class="w-full sm:w-32 md:w-36">
                        <label class="alloc-label block text-xs md:text-sm font-bold text-slate-400 uppercase mb-1 md:mb-1.5 tracking-wider">${allocMode === 'manual' ? 'Nominal' : 'Porsi (%)'}</label>
                        <div class="relative">
                            <input type="number" step="0.01" value="0" class="alloc-percent w-full border border-slate-200 rounded-md md:rounded-lg p-2 md:p-2.5 pr-7 md:pr-8 text-xs md:text-sm font-bold bg-white outline-none focus:ring-2 focus:ring-blue-100" />
                            <span class="alloc-suffix absolute right-2.5 md:right-3 top-1/2 -translate-y-1/2 font-bold text-slate-300 text-xs md:text-sm pointer-events-none">${allocMode === 'manual' ? '' : '%'}</span>
                        </div>
                    </div>
                    <input type="hidden" name="branches[${idx}][allocation_percent]" class="alloc-percent-hidden" value="0" />
                    <input type="hidden" name="branches[${idx}][allocation_amount]" class="alloc-amount" value="0" />
                    <button type="button" class="remove-branch-btn p-2 md:p-2.5 text-red-400 hover:bg-red-50 rounded-md md:rounded-lg transition-all cursor-pointer shrink-0">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5 md:w-4 md:h-4"></i>
                    </button>`;

                container.appendChild(row);
                lucide.createIcons();
                bindRemove();
                recalc();
                enforceUniqueBranches();
            });

            function bindRemove() {
                document.querySelectorAll('.remove-branch-btn').forEach(btn => {
                    btn.onclick = function () {
                        if (container.querySelectorAll('.branch-row').length > 1) {
                            this.closest('.branch-row').remove();
                            recalc();
                            enforceUniqueBranches();
                        }
                    };
                });
            }
            bindRemove();

            function recalc() {
                const rows = container.querySelectorAll('.branch-row');
                const totalAmt = parseInt(totalInput.value) || 0;
                const count = rows.length;

                rows.forEach(row => {
                    const pctInput = row.querySelector('.alloc-percent');
                    const pctHidden = row.querySelector('.alloc-percent-hidden');
                    const amtHidden = row.querySelector('.alloc-amount');
                    const label = row.querySelector('.alloc-label');
                    const suffix = row.querySelector('.alloc-suffix');

                    if (allocMode === 'equal') {
                        const eqPct = parseFloat((100 / count).toFixed(2));
                        pctInput.value = eqPct;
                        pctInput.readOnly = true;
                        pctHidden.value = eqPct;
                        amtHidden.value = Math.round((totalAmt * eqPct) / 100);
                        if (label) label.textContent = 'Porsi (%)';
                        if (suffix) suffix.textContent = '%';
                    } else if (allocMode === 'percent') {
                        pctInput.readOnly = false;
                        const pct = parseFloat(pctInput.value) || 0;
                        pctHidden.value = pct;
                        amtHidden.value = Math.round((totalAmt * pct) / 100);
                        if (label) label.textContent = 'Porsi (%)';
                        if (suffix) suffix.textContent = '%';
                    } else {
                        pctInput.readOnly = false;
                        if (label) label.textContent = 'Nominal';
                        if (suffix) suffix.textContent = '';
                        const nominal = parseInt(pctInput.value) || 0;
                        const pct = totalAmt > 0 ? parseFloat(((nominal / totalAmt) * 100).toFixed(2)) : 0;
                        pctHidden.value = pct;
                        amtHidden.value = nominal;
                    }
                });
                updateSummary();
            }

            function updateSummary() {
                const totalAmt = parseInt(totalInput.value) || 0;
                summaryTotal.textContent = formatRupiah(totalAmt);

                const summaryBranches = document.getElementById('summary-branches');
                summaryBranches.innerHTML = '';

                let totalPct = 0;

                container.querySelectorAll('.branch-row').forEach(row => {
                    const pct = parseFloat(row.querySelector('.alloc-percent-hidden').value) || 0;
                    const amt = parseInt(row.querySelector('.alloc-amount').value) || 0;
                    const branchName = row.querySelector('.branch-select option:checked').textContent;

                    totalPct += pct;

                    if (pct > 0 || amt > 0) {
                        const wrapper = document.createElement('div');
                        wrapper.className = "flex justify-between items-center text-white/90";

                        const name = document.createElement('span');
                        name.className = "text-slate-300";
                        name.textContent = branchName;

                        const right = document.createElement('div');
                        right.className = "text-right";

                        const amount = document.createElement('div');
                        amount.className = "font-bold";
                        amount.textContent = formatRupiah(amt);

                        const percent = document.createElement('div');
                        percent.className = "text-[10px] text-slate-400";
                        percent.textContent = pct.toFixed(1) + "%";

                        right.appendChild(amount);
                        right.appendChild(percent);
                        wrapper.appendChild(name);
                        wrapper.appendChild(right);
                        summaryBranches.appendChild(wrapper);
                    }
                });

                allocDisplay.textContent = totalPct.toFixed(1) + '%';

                const ok = Math.abs(totalPct - 100) < 0.5 && totalAmt > 0;

                allocBadge.className = ok
                    ? 'px-2.5 md:px-3 py-1 md:py-1.5 rounded-full flex items-center gap-1.5 md:gap-2 font-bold text-xs md:text-sm uppercase tracking-wider bg-green-500/20 text-green-400'
                    : 'px-2.5 md:px-3 py-1 md:py-1.5 rounded-full flex items-center gap-1.5 md:gap-2 font-bold text-xs md:text-sm uppercase tracking-wider bg-red-500/20 text-red-400';

                if (!isSubmitting) {
                    submitBtn.disabled = !ok;
                }
            }

            function enforceUniqueBranches() {
                const selects = container.querySelectorAll('.branch-select');
                const usedBranches = [];

                selects.forEach(select => {
                    if (select.value) {
                        usedBranches.push(select.value);
                    }
                });

                selects.forEach(select => {
                    const currentValue = select.value;
                    select.querySelectorAll('option').forEach(option => {
                        if (!option.value) return;
                        if (usedBranches.includes(option.value) && option.value !== currentValue) {
                            option.disabled = true;
                        } else {
                            option.disabled = false;
                        }
                    });
                });
            }

            totalInput.addEventListener('input', recalc);

            container.addEventListener('input', function (e) {
                if (e.target.classList.contains('alloc-percent')) recalc();
            });

            container.addEventListener('change', function(e) {
                if (e.target.classList.contains('branch-select')) {
                    enforceUniqueBranches();
                    updateSummary();
                }
            });

            form.addEventListener('submit', function () {
                recalc();
                if (submitBtn.disabled || isSubmitting) return;
                isSubmitting = true;
                submitBtn.disabled = true;
                document.getElementById('submit-text').textContent = 'Mengirim...';
                document.getElementById('submit-spinner').classList.remove('hidden');
            });

            recalc();

            // ========================================
            // 🔄 AI AUTOFILL
            // ========================================

            const uploadId = document.getElementById('upload-id')?.value;
            debug(`Upload ID: ${uploadId}`);

            let pollingInterval = null;
            let pollCount = 0;
            const maxPolls = 20;

            function applyAIData(aiData, source) {
                debug(`✅ Applying AI Data from ${source}`, 'success');
                debug(`   Customer: ${aiData.customer || 'N/A'}`);
                debug(`   Amount: ${aiData.amount || 'N/A'}`);
                debug(`   Date: ${aiData.date || 'N/A'}`);
                debug(`   Confidence: ${aiData.confidence || 'N/A'}%`);

                let filled = false;

                if (aiData.customer) {
                    document.getElementById('customer').value = aiData.customer;
                    filled = true;
                }

                if (aiData.amount) {
                    document.getElementById('total-amount').value = aiData.amount;
                    filled = true;
                }

                if (aiData.date) {
                    document.getElementById('date').value = aiData.date;
                    filled = true;
                }

                if (aiData.items) {
                    document.getElementById('items').value = aiData.items;
                    filled = true;
                }

                if (filled) {
                    recalc();
                    showAutoFillNotification(aiData.confidence || 90);
                    debug("✅ Form auto-filled successfully!", 'success');
                    
                    if (pollingInterval) {
                        clearInterval(pollingInterval);
                        pollingInterval = null;
                    }
                } else {
                    debug("❌ No valid data to fill", 'error');
                }
            }

            function showAutoFillNotification(confidence) {
                const notification = document.createElement('div');
                notification.className = "fixed top-4 right-4 bg-green-500 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-2 z-50";
                notification.innerHTML = `
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>Data terisi otomatis (AI: ${confidence}%)</span>
                `;
                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.style.opacity = '0';
                    notification.style.transition = 'opacity 0.5s';
                    setTimeout(() => notification.remove(), 500);
                }, 3000);
            }

            function startPolling() {
                debug("🔄 Starting API polling...", 'warning');
                
                pollingInterval = setInterval(() => {
                    pollCount++;
                    debug(`Polling ${pollCount}/${maxPolls}...`);

                    fetch(`/api/ai/ai-status/${uploadId}`)
                        .then(res => res.json())
                        .then(data => {
                            debug(`API: ${JSON.stringify(data)}`);

                            if (data.status === 'completed' && data.data) {
                                applyAIData(data.data, 'Server API');
                            }

                            if (data.status === 'failed') {
                                debug('AI processing failed', 'error');
                                stopPolling();
                                showManualFillButton();
                            }

                            if (pollCount >= maxPolls) {
                                debug("⚠️ Polling timeout - AI data not received", 'warning');
                                showManualFillButton();
                                stopPolling();
                            }
                        })
                        .catch(err => {
                            debug(`Error: ${err.message}`, 'error');
                            if (pollCount >= maxPolls) {
                                showManualFillButton();
                                stopPolling();
                            }
                        });
                }, 2000);
            }

            function stopPolling() {
                if (pollingInterval) {
                    clearInterval(pollingInterval);
                    pollingInterval = null;
                }
            }

            function showManualFillButton() {
                const existingBtn = document.getElementById('manual-fill-btn');
                if (existingBtn) return;

                const manualBtn = document.createElement('button');
                manualBtn.id = 'manual-fill-btn';
                manualBtn.type = 'button';
                manualBtn.className = "fixed bottom-20 right-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-lg flex items-center gap-2 z-50 text-sm font-bold transition-all";
                manualBtn.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span>Refresh AI Data</span>
                `;
                manualBtn.onclick = function() {
                    pollCount = 0;
                    debug("🔄 Manual refresh triggered", 'warning');
                    startPolling();
                    this.remove();
                };
                document.body.appendChild(manualBtn);
            }

            // ==========================================
            // ✅ START PROCESS
            // ==========================================
            if (uploadId) {
                const possibleKeys = [
                    `ai_autofill_${uploadId}`,
                    `ai_autofill:null`,
                    `ai_autofill_latest`,
                    `ai_autofill`,
                ];

                debug(`Checking ${possibleKeys.length} cache keys...`);

                let foundData = null;
                let foundKey = null;

                for (const key of possibleKeys) {
                    const data = sessionStorage.getItem(key);
                    if (data) {
                        try {
                            const parsed = JSON.parse(data);
                            if (parsed && (parsed.customer || parsed.amount || parsed.confidence)) {
                                foundData = parsed;
                                foundKey = key;
                                debug(`✅ Found: ${key}`, 'success');
                                break;
                            }
                        } catch (e) {
                            debug(`Invalid JSON: ${key}`, 'error');
                        }
                    }
                }

                if (foundData) {
                    applyAIData(foundData, `Browser Cache (${foundKey})`);
                } else {
                    debug("No cache found, starting polling...", 'warning');
                    startPolling();
                }
            } else {
                debug("No upload_id found!", 'error');
            }

            lucide.createIcons();
        });
    </script>
    @endpush
@endsection