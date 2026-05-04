import { formatNumber, unformatNumber } from './helpers.js';

export class BranchDistribution {
    constructor(formTotalInputSelector, summarySubmitSelector, options = {}) {
        this.formTotalInput = document.querySelector(formTotalInputSelector);
        this.summarySubmit = document.querySelector(summarySubmitSelector);
        
        // Options for different transaction types
        this.isOptional = options.isOptional || false;
        this.tolerance = options.tolerance || 2;

        this.branchPills = document.querySelectorAll('.branch-pill');
        this.methodBtns = document.querySelectorAll('.method-btn');
        this.distributionList = document.getElementById('distribution-list');
        this.hiddenInputsContainer = document.getElementById('distribution-hidden-inputs');
        this.percentWarning = document.getElementById('percent-warning');
        
        this.summarySection = document.getElementById('summary-billing-section');
        this.summaryTotal = document.getElementById('summary-total');
        this.summaryMethod = document.getElementById('summary-method');
        this.summaryBranchCount = document.getElementById('summary-branch-count');
        this.summaryBranchesList = document.getElementById('summary-branches-list');

        this.selectedBranches = [];
        this.currentMethod = 'equal';
        
        this.initEvents();
        this.validateAndSubmit();
    }

    initEvents() {
        if (this.branchPills.length > 0) {
            this.branchPills.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const id = e.currentTarget.dataset.id;
                    const name = e.currentTarget.dataset.name;
                    const index = this.selectedBranches.findIndex(b => b.id == id);

                    if (index > -1) {
                        this.selectedBranches.splice(index, 1);
                        e.currentTarget.classList.remove('bg-emerald-500', 'text-white', 'border-emerald-500', 'shadow-md', 'hover:text-emerald-500');
                        e.currentTarget.classList.add('bg-white', 'text-slate-600', 'border-slate-200');
                    } else {
                        this.selectedBranches.push({ id, name, value: 0, percent: 0 });
                        e.currentTarget.classList.remove('bg-white', 'text-slate-600', 'border-slate-200');
                        e.currentTarget.classList.add('bg-emerald-500', 'text-white', 'border-emerald-500', 'shadow-md', 'hover:text-emerald-500');
                    }
                    this.renderDistribution();
                });
            });
        }

        if (this.methodBtns.length > 0) {
            this.methodBtns.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    this.methodBtns.forEach(b => {
                        b.classList.remove('bg-white', 'shadow', 'text-slate-700');
                        b.classList.add('text-slate-500');
                    });
                    e.currentTarget.classList.remove('text-slate-500');
                    e.currentTarget.classList.add('bg-white', 'shadow', 'text-slate-700');
                    this.currentMethod = e.currentTarget.dataset.method;
                    this.renderDistribution();
                });
            });
        }

        if (this.distributionList) {
            this.distributionList.addEventListener('input', (e) => {
                const index = e.target.dataset.index;
                if (index === undefined) return;
                
                const totalAmount = parseInt(this.formTotalInput?.value) || 0;

                if (e.target.classList.contains('dist-input-percent')) {
                    const val = parseFloat(e.target.value) || 0;
                    this.selectedBranches[index].percent = val;
                    this.selectedBranches[index].value = totalAmount > 0 ? Math.round((totalAmount * val) / 100) : 0;
                    const siblingSpan = e.target.parentElement.querySelector('.text-emerald-500');
                    if (siblingSpan) siblingSpan.textContent = 'Rp ' + formatNumber(this.selectedBranches[index].value);
                }
                
                if (e.target.classList.contains('dist-input-manual')) {
                    const raw = unformatNumber(e.target.value);
                    e.target.value = raw > 0 ? formatNumber(raw) : '';
                    this.selectedBranches[index].value = raw;
                    if (totalAmount > 0) {
                        this.selectedBranches[index].percent = totalAmount > 0 ? parseFloat(((raw / totalAmount) * 100).toFixed(2)) : 0;
                    }
                }

                this.updateHiddenInputs();
                this.updateSummaryList();
                this.validateAndSubmit();
            });
        }
    }

    renderDistribution() {
        if (!this.distributionList) return;
        this.distributionList.innerHTML = '';

        const totalAmount = parseInt(this.formTotalInput?.value) || 0;

        if (this.selectedBranches.length === 0) {
            if(this.percentWarning) this.percentWarning.classList.add('hidden');
            this.updateSummaryList();
            this.validateAndSubmit();
            return;
        }

        this.selectedBranches.forEach((branch, idx) => {
            if (this.currentMethod === 'equal') {
                branch.percent = parseFloat((100 / this.selectedBranches.length).toFixed(2));
                branch.value = totalAmount > 0 ? Math.round(totalAmount / this.selectedBranches.length) : 0;
            } else if (this.currentMethod === 'percent') {
                branch.value = totalAmount > 0 ? Math.round((totalAmount * (branch.percent || 0)) / 100) : 0;
            } else if (this.currentMethod === 'manual') {
                branch.percent = totalAmount > 0 ? parseFloat(((branch.value / totalAmount) * 100).toFixed(2)) : 0;
            }

            let inputHtml = '';
            if (this.currentMethod === 'equal') {
                inputHtml = `<div class="font-bold text-emerald-600">Rp ${formatNumber(branch.value)}</div>`;
            } else if (this.currentMethod === 'percent') {
                inputHtml = `
                <div class="flex items-center gap-2">
                    <input type="number" 
                        class="dist-input-percent w-20 text-right text-sm border border-slate-200 rounded-lg px-2 py-1 focus:ring-2 focus:ring-emerald-500 outline-none" 
                        data-index="${idx}" 
                        value="${branch.percent || 0}" 
                        min="0" max="100">
                    <span class="text-xs font-bold text-slate-400">%</span>
                    <span class="text-emerald-500 font-bold text-sm w-32 text-right">Rp ${formatNumber(branch.value)}</span>
                </div>
            `;
            } else if (this.currentMethod === 'manual') {
                const displayVal = branch.value > 0 ? formatNumber(branch.value) : '';
                inputHtml = `
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 font-bold">Rp</span>
                    <input type="text" 
                        class="dist-input-manual w-32 text-right text-sm border border-slate-200 rounded-lg pl-8 pr-3 py-1 focus:ring-2 focus:ring-emerald-500 outline-none" 
                        data-index="${idx}" 
                        value="${displayVal}" placeholder="0">
                </div>
            `;
            }

            const rowHtml = `
            <div class="flex justify-between items-center text-xs md:text-sm bg-white p-3 rounded-xl border border-slate-50" data-branch-index="${idx}">
                <span class="text-slate-600 font-medium flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                    ${branch.name}
                </span>
                <div class="flex justify-end">${inputHtml}</div>
            </div>
        `;
            this.distributionList.insertAdjacentHTML('beforeend', rowHtml);
        });

        const methodLabels = { 'equal': 'BAGI RATA', 'percent': 'PERSENTASE', 'manual': 'MANUAL' };
        if(this.summaryMethod) this.summaryMethod.textContent = 'METODE: ' + (methodLabels[this.currentMethod] || '-');

        this.updateHiddenInputs();
        this.updateSummaryList();
        this.validateAndSubmit();
    }

    /**
     * ✅ FIX: Update distribution values without full re-render
     * This prevents input loss when user is typing
     */
    updateValues() {
        if (!this.distributionList || this.selectedBranches.length === 0) return;

        const totalAmount = parseInt(this.formTotalInput?.value) || 0;

        this.selectedBranches.forEach((branch, idx) => {
            // Recalculate values based on method
            if (this.currentMethod === 'equal') {
                branch.percent = parseFloat((100 / this.selectedBranches.length).toFixed(2));
                branch.value = totalAmount > 0 ? Math.round(totalAmount / this.selectedBranches.length) : 0;
            } else if (this.currentMethod === 'percent') {
                branch.value = totalAmount > 0 ? Math.round((totalAmount * (branch.percent || 0)) / 100) : 0;
            } else if (this.currentMethod === 'manual') {
                branch.percent = totalAmount > 0 ? parseFloat(((branch.value / totalAmount) * 100).toFixed(2)) : 0;
            }

            // Update DOM directly without innerHTML
            const row = this.distributionList.querySelector(`[data-branch-index="${idx}"]`);
            if (!row) return;

            // For equal method: update both value and percent displays
            if (this.currentMethod === 'equal') {
                const valueDisplay = row.querySelector('.font-bold.text-emerald-600');
                if (valueDisplay) {
                    valueDisplay.textContent = 'Rp ' + formatNumber(branch.value);
                }
            }

            // For percent method: update value display (input stays as is)
            if (this.currentMethod === 'percent') {
                const valueDisplay = row.querySelector('.text-emerald-500');
                if (valueDisplay) {
                    valueDisplay.textContent = 'Rp ' + formatNumber(branch.value);
                }
            }

            // For manual method: percent is calculated, no need to update input
            // (user is typing in the input, don't override it)
        });

        this.updateHiddenInputs();
        this.updateSummaryList();
        this.validateAndSubmit();
    }

    updateHiddenInputs() {
        if(!this.hiddenInputsContainer) return;
        this.hiddenInputsContainer.innerHTML = '';
        this.selectedBranches.forEach((branch, idx) => {
            this.hiddenInputsContainer.insertAdjacentHTML('beforeend', `
            <input type="hidden" name="branches[${idx}][branch_id]"          value="${branch.id}">
            <input type="hidden" name="branches[${idx}][allocation_amount]"  value="${Math.round(branch.value || 0)}">
            <input type="hidden" name="branches[${idx}][allocation_percent]" value="${branch.percent || 0}">
        `);
        });
    }

    updateSummaryList() {
        if(!this.summaryBranchesList) return;
        this.summaryBranchesList.innerHTML = '';
        const totalAmount = parseInt(this.formTotalInput?.value) || 0;

        if (this.selectedBranches.length === 0) {
            this.summaryBranchesList.innerHTML = '<div class="text-xs text-slate-500 italic">Pilih cabang terlebih dahulu...</div>';
        } else {
            this.selectedBranches.forEach(branch => {
                const pct = totalAmount > 0
                    ? ((branch.value / totalAmount) * 100).toFixed(1)
                    : (branch.percent || 0).toFixed(1);

                const summaryRow = `
                <div class="flex justify-between items-start text-sm border-b border-white/10 pb-3 pt-3 px-2 last:border-0 last:pb-0">
                    <div class="flex flex-col">
                        <span class="text-slate-300 font-medium">${branch.name}</span>
                        <span class="text-[10px] text-emerald-400/70 mt-0.5">${pct}%</span>
                    </div>
                    <span class="text-emerald-400 font-bold">Rp ${formatNumber(branch.value)}</span>
                </div>
            `;
                this.summaryBranchesList.insertAdjacentHTML('beforeend', summaryRow);
            });
        }

        if(this.summaryTotal) this.summaryTotal.textContent = 'Rp ' + formatNumber(totalAmount);
        if(this.summaryBranchCount) this.summaryBranchCount.textContent = this.selectedBranches.length + ' CABANG';
    }

    validateAndSubmit() {
        let isValid = true;
        const totalAmount = parseInt(this.formTotalInput?.value) || 0;
        const totalAllocated = this.selectedBranches.reduce((sum, b) => sum + (parseFloat(b.value) || 0), 0);

        if (this.selectedBranches.length > 0) {
            // Validation for Percent
            const totalPercent = this.selectedBranches.reduce((sum, b) => sum + (parseFloat(b.percent) || 0), 0);
            if (Math.abs(totalPercent - 100) > 0.5) { // Match legacy threshold
                isValid = false;
                if(this.percentWarning) {
                    this.percentWarning.classList.remove('hidden');
                    this.percentWarning.textContent = `⚠ Total alokasi harus tepat 100% (saat ini ${totalPercent.toFixed(1)}%)`;
                }
            } else {
                if(this.percentWarning) this.percentWarning.classList.add('hidden');
            }

            // Validation for Amount (only if totalAmount > 0)
            if (isValid && totalAmount > 0) {
                if (Math.abs(totalAllocated - totalAmount) > this.tolerance) {
                    isValid = false;
                    if(this.percentWarning) {
                        this.percentWarning.classList.remove('hidden');
                        this.percentWarning.textContent = `⚠ Total alokasi (Rp ${formatNumber(totalAllocated)}) belum sesuai dengan total transaksi (Rp ${formatNumber(totalAmount)})`;
                    }
                }
            }
        } else {
            // No branches selected
            if (!this.isOptional) {
                isValid = false;
            }
        }

        // Basic requirement: must have amount for Pengajuan (isOptional = false)
        // For Rembush (isOptional = true), amount can be 0 initially because it will be filled by OCR
        if (!this.isOptional && totalAmount <= 0) isValid = false;
        
        if(this.summarySubmit) {
            this.summarySubmit.disabled = !isValid;
            if (isValid) {
                this.summarySubmit.classList.remove('bg-slate-700', 'text-slate-500');
                this.summarySubmit.classList.add('bg-emerald-500');
            } else {
                this.summarySubmit.classList.add('bg-slate-700', 'text-slate-500');
                this.summarySubmit.classList.remove('bg-emerald-500');
            }
        }
    }
}
