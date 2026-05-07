import { formatNumber, unformatNumber, formatRupiah, escapeHtml } from '../shared/helpers.js';

export class ItemRepeater {
    constructor(tbodySelector, cardsSelector, totalDisplaySelector, formTotalSelector, onTotalChange) {
        this.tbody = document.querySelector(tbodySelector);
        this.cards = document.querySelector(cardsSelector);
        this.totalDisplay = document.querySelector(totalDisplaySelector);
        this.formTotal = document.querySelector(formTotalSelector);
        this.onTotalChange = onTotalChange;

        this.rowTemplate = document.getElementById('item-row-template');
        this.cardTemplate = document.getElementById('item-card-template');

        this.items = [];
        this.init();
    }

    init() {
        // Load initial data from window._aiData
        const aiData = window._aiData || {};
        const aiStatus = aiData.status ?? '';

        if (aiStatus === 'completed' && Array.isArray(aiData.items) && aiData.items.length > 0) {
            this.items = aiData.items.map(i => ({
                name: i.nama_barang || i.name || '',
                qty: parseInt(i.qty) || 1,
                unit: (i.satuan || i.unit || 'pcs').toLowerCase(),
                price: parseInt(i.harga_satuan || i.price) || 0,
                desc: i.deskripsi_kalimat || i.desc || '',
            }));
        }

        if (this.items.length === 0) {
            this.items = [{ name: '', qty: 1, unit: 'pcs', price: 0, desc: '' }];
        }

        this.render();
        this.initEvents();
    }

    initEvents() {
        const handleInput = (e) => {
            const container = e.target.closest('[data-idx]');
            if (!container) return;
            const idx = parseInt(container.dataset.idx);

            if (e.target.classList.contains('item-field')) {
                this.items[idx][e.target.dataset.field] = e.target.value;
                
                // Realtime update untuk Qty tanpa full re-render
                if (e.target.dataset.field === 'qty') {
                    const qty = parseInt(e.target.value) || 0;
                    this.items[idx].qty = qty;
                    this.updateRowTotal(idx);
                }
            }

            if (e.target.classList.contains('item-price-display')) {
                // Real-time formatting untuk harga satuan
                const raw = unformatNumber(e.target.value);
                this.items[idx].price = raw;
                
                // Format ulang dengan Rp prefix
                const formatted = raw > 0 ? formatRupiah(raw) : 'Rp 0';
                
                // Simpan posisi cursor
                const cursorPos = e.target.selectionStart;
                const oldLength = e.target.value.length;
                
                // Update value
                e.target.value = formatted;
                
                // Restore posisi cursor (adjust untuk perubahan panjang)
                const newLength = formatted.length;
                const newCursorPos = cursorPos + (newLength - oldLength);
                e.target.setSelectionRange(newCursorPos, newCursorPos);
                
                // Update hidden price
                const row = this.tbody ? this.tbody.querySelector(`tr[data-idx="${idx}"]`) : null;
                const card = this.cards ? this.cards.querySelector(`[data-idx="${idx}"]`) : null;
                
                if (row) {
                    const hidden = row.querySelector('.item-price-hidden');
                    if (hidden) hidden.value = raw;
                }
                
                // Update total untuk row/card ini
                this.updateRowTotal(idx);
            }
        };

        const handleFocus = (e) => {
            if (e.target.classList.contains('item-price-display')) {
                // Saat focus, jika value adalah "Rp 0", select semua agar user bisa langsung ketik
                if (e.target.value === 'Rp 0') {
                    setTimeout(() => e.target.select(), 0);
                }
            }
        };

        const handleBlur = (e) => {
            if (e.target.classList.contains('item-price-display')) {
                const container = e.target.closest('[data-idx]');
                if (!container) return;
                const idx = parseInt(container.dataset.idx);
                
                // Pastikan format tetap benar saat blur
                const raw = unformatNumber(e.target.value);
                this.items[idx].price = raw;
                e.target.value = raw > 0 ? formatRupiah(raw) : 'Rp 0';
                
                // Update total keseluruhan
                this.updateTotalAmount();
            }
        };

        const handleClick = (e) => {
            const btn = e.target.closest('.item-delete');
            if (!btn) return;
            const container = btn.closest('[data-idx]');
            if (!container) return;
            const idx = parseInt(container.dataset.idx);
            this.items.splice(idx, 1);
            this.render();
        };

        this.tbody.addEventListener('input', handleInput);
        if (this.cards) this.cards.addEventListener('input', handleInput);

        this.tbody.addEventListener('focus', handleFocus, true);
        if (this.cards) this.cards.addEventListener('focus', handleFocus, true);

        this.tbody.addEventListener('blur', handleBlur, true);
        if (this.cards) this.cards.addEventListener('blur', handleBlur, true);

        this.tbody.addEventListener('click', handleClick);
        if (this.cards) this.cards.addEventListener('click', handleClick);

        const addBtn = document.getElementById('add-item-btn');
        if (addBtn) {
            addBtn.addEventListener('click', () => {
                this.items.push({ name: '', qty: 1, unit: 'pcs', price: 0, desc: '' });
                this.render();
            });
        }
    }

    /**
     * Update total untuk satu row/card tanpa full re-render
     */
    updateRowTotal(idx) {
        const item = this.items[idx];
        const rowTotal = (item.qty || 0) * (item.price || 0);
        
        // Update di table (desktop)
        if (this.tbody) {
            const row = this.tbody.querySelector(`tr[data-idx="${idx}"]`);
            if (row) {
                const totalCell = row.querySelector('td:nth-child(6)'); // Kolom total
                if (totalCell) {
                    totalCell.textContent = formatRupiah(rowTotal);
                }
            }
        }
        
        // Update di card (mobile)
        if (this.cards) {
            const card = this.cards.querySelector(`[data-idx="${idx}"]`);
            if (card) {
                // Cari subtotal di card (ada di bagian bawah dengan class text-sm.font-black.text-slate-800)
                const totalSpan = card.querySelector('.text-sm.font-black.text-slate-800');
                if (totalSpan) {
                    totalSpan.textContent = formatRupiah(rowTotal);
                }
            }
        }
        
        // Update total keseluruhan
        this.updateTotalAmount();
    }

    /**
     * Update total amount tanpa full re-render
     */
    updateTotalAmount() {
        let totalAmount = 0;
        this.items.forEach(item => {
            totalAmount += (item.qty || 0) * (item.price || 0);
        });
        
        if (this.totalDisplay) {
            this.totalDisplay.textContent = formatRupiah(totalAmount);
        }
        
        if (this.formTotal) {
            this.formTotal.value = totalAmount;
            this.formTotal.dispatchEvent(new Event('change', { bubbles: true }));
        }
        
        if (this.onTotalChange) {
            this.onTotalChange(totalAmount);
        }
    }

    render() {
        if (!this.tbody) return;
        this.tbody.innerHTML = '';
        if (this.cards) this.cards.innerHTML = '';
        let totalAmount = 0;

        this.items.forEach((item, i) => {
            const rowTotal = (item.qty || 0) * (item.price || 0);
            totalAmount += rowTotal;

            // Format harga dengan "Rp 0" sebagai default
            const priceFormatted = item.price > 0 ? formatRupiah(item.price) : 'Rp 0';

            // Desktop Row
            let rowHtml = this.rowTemplate.innerHTML
                .replace(/{idx}/g, i)
                .replace(/{no}/g, i + 1)
                .replace(/{name}/g, escapeHtml(item.name))
                .replace(/{qty}/g, item.qty)
                .replace(/{unit}/g, escapeHtml(item.unit))
                .replace(/{price}/g, item.price)
                .replace(/{price_formatted}/g, priceFormatted)
                .replace(/{total_formatted}/g, formatRupiah(rowTotal))
                .replace(/{desc}/g, escapeHtml(item.desc));
            
            this.tbody.insertAdjacentHTML('beforeend', rowHtml);

            // Mobile Card
            if (this.cards) {
                let cardHtml = this.cardTemplate.innerHTML
                    .replace(/{idx}/g, i)
                    .replace(/{no}/g, i + 1)
                    .replace(/{name}/g, escapeHtml(item.name))
                    .replace(/{qty}/g, item.qty)
                    .replace(/{unit}/g, escapeHtml(item.unit))
                    .replace(/{price_formatted}/g, priceFormatted)
                    .replace(/{total_formatted}/g, formatRupiah(rowTotal))
                    .replace(/{desc}/g, escapeHtml(item.desc));
                
                this.cards.insertAdjacentHTML('beforeend', cardHtml);
            }
        });

        if (this.totalDisplay) this.totalDisplay.textContent = formatRupiah(totalAmount);
        if (this.formTotal) {
            this.formTotal.value = totalAmount;
            // Trigger change event manually so other modules can react
            this.formTotal.dispatchEvent(new Event('change', { bubbles: true }));
        }

        if (typeof lucide !== 'undefined') lucide.createIcons();
        if (this.onTotalChange) this.onTotalChange(totalAmount);
    }
}
