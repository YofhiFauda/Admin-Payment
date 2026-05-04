import { formatRupiah, parseNumber, esc } from '../shared/helpers.js';

/**
 * ═══════════════════════════════════════════════════════════════
 * ITEM REPEATER MODULE (Pembelian)
 * Handles dynamic item rows and total calculation.
 * ═══════════════════════════════════════════════════════════════
 */

export class ItemRepeater {
    constructor(tbodySelector, cardsSelector, totalInputSelector, totalDisplaySelector, onTotalChange) {
        this.tbody = document.querySelector(tbodySelector);
        this.cards = document.querySelector(cardsSelector);
        this.totalInput = document.querySelector(totalInputSelector);
        this.totalDisplay = document.querySelector(totalDisplaySelector);
        this.onTotalChange = onTotalChange;

        this.items = [{ name: '', qty: 1, unit: 'pcs', price: 0, desc: '' }];
        this.totalAmount = 0;

        if (this.tbody) {
            this.init();
            this.render();
        }
    }

    init() {
        // Add Item Button
        const addBtn = document.getElementById('add-item-btn');
        if (addBtn) {
            addBtn.addEventListener('click', () => {
                this.items.push({ name: '', qty: 1, unit: 'pcs', price: 0, desc: '' });
                this.render();
            });
        }

        // Input Handling (Delegation)
        const handleInput = (e) => {
            const container = e.target.closest('[data-idx]');
            if (!container) return;
            const idx = parseInt(container.dataset.idx);

            if (e.target.classList.contains('item-field')) {
                this.items[idx][e.target.dataset.field] = e.target.value;
                if (e.target.dataset.field === 'qty') this.render();
            }

            if (e.target.classList.contains('item-price-display')) {
                const raw = parseNumber(e.target.value);
                this.items[idx].price = raw;
                // Sync to hidden input in that row
                const row = this.tbody.querySelector(`tr[data-idx="${idx}"]`);
                if (row) row.querySelector('.item-price-hidden').value = raw;
            }
        };

        this.tbody.addEventListener('input', handleInput);
        if (this.cards) this.cards.addEventListener('input', handleInput);

        // Price Format on Blur
        const handleBlur = (e) => {
            if (e.target.classList.contains('item-price-display')) {
                const container = e.target.closest('[data-idx]');
                if (!container) return;
                this.items[parseInt(container.dataset.idx)].price = parseNumber(e.target.value);
                this.render();
            }
        };

        this.tbody.addEventListener('blur', handleBlur, true);
        if (this.cards) this.cards.addEventListener('blur', handleBlur, true);

        // Delete Handling
        const handleClick = (e) => {
            const btn = e.target.closest('.item-delete');
            if (!btn) return;
            const container = btn.closest('[data-idx]');
            if (!container) return;
            if (this.items.length > 1) {
                this.items.splice(parseInt(container.dataset.idx), 1);
                this.render();
            }
        };

        this.tbody.addEventListener('click', handleClick);
        if (this.cards) this.cards.addEventListener('click', handleClick);
    }

    render() {
        if (!this.tbody) return;

        this.tbody.innerHTML = '';
        if (this.cards) this.cards.innerHTML = '';
        this.totalAmount = 0;

        this.items.forEach((item, i) => {
            const rowTotal = (item.qty || 0) * (item.price || 0);
            this.totalAmount += rowTotal;

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
            this.tbody.appendChild(tr);

            // Mobile Card
            if (this.cards) {
                const card = document.createElement('div');
                card.className = 'p-4 space-y-4 relative bg-white';
                card.dataset.idx = i;
                card.innerHTML = `
                    <div class="flex justify-between items-start">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500">${i + 1}</span>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Baris #${i + 1}</span>
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
                this.cards.appendChild(card);
            }
        });

        // Update Totals
        if (this.totalDisplay) this.totalDisplay.textContent = formatRupiah(this.totalAmount);
        if (this.totalInput) this.totalInput.value = this.totalAmount;

        // Refresh Lucide Icons
        if (typeof lucide !== 'undefined') lucide.createIcons({ root: this.tbody });
        if (typeof lucide !== 'undefined' && this.cards) lucide.createIcons({ root: this.cards });

        // Trigger callback for distribution sync
        if (this.onTotalChange) this.onTotalChange(this.totalAmount);
    }
}
