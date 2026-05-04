import { Config } from '../config.js';
import { formatNumber, unformatNumber } from '../shared/helpers.js';

export class ItemRepeater {
    constructor(containerSelector, templateSelector, totalInputSelector, globalTotalSelector, onTotalChange) {
        this.itemsContainer = document.querySelector(containerSelector);
        this.itemTemplate = document.querySelector(templateSelector)?.innerHTML;
        this.formTotalInput = document.querySelector(totalInputSelector);
        this.globalTotalDisplay = document.querySelector(globalTotalSelector);
        this.onTotalChange = onTotalChange;
        
        this.btnAddItem = document.getElementById('btn-add-item');
        this.itemCounter = 0;
        this.priceCheckDebounce = {};

        this.initEvents();
    }

    initEvents() {
        if (this.btnAddItem) {
            this.btnAddItem.addEventListener('click', () => this.addItem());
        }

        // DPP, PPN & Service Fee Listeners
        const dppLainnyaDisp = document.getElementById('input-dpp-lainnya-display');
        const dppLainnyaHid = document.getElementById('input-dpp-lainnya-hidden');
        const ppnDisp = document.getElementById('input-ppn-display');
        const ppnHid = document.getElementById('input-ppn-hidden');
        const lay1Disp = document.getElementById('input-layanan1-display');
        const lay1Hid = document.getElementById('input-layanan1-hidden');

        const attachFeeListener = (disp, hid) => {
            if (disp && hid) {
                disp.addEventListener('input', (e) => {
                    let raw = unformatNumber(e.target.value);
                    e.target.value = raw > 0 ? formatNumber(raw) : '';
                    hid.value = raw;
                    this.updateGlobalTotal();
                });
            }
        };

        attachFeeListener(dppLainnyaDisp, dppLainnyaHid);
        attachFeeListener(ppnDisp, ppnHid);
        attachFeeListener(lay1Disp, lay1Hid);

        if (this.itemsContainer) {
            this.addItem(); // Add initial first item
        }
    }

    updateGlobalTotal() {
        if (!this.itemsContainer) return;
        
        let itemsTotal = 0;
        const itemCards = this.itemsContainer.querySelectorAll('.item-card');

        itemCards.forEach(card => {
            const priceInput = card.querySelector('.input-price-hidden');
            const qtyInput = card.querySelector('.input-qty');
            const subtotalDisplay = card.querySelector('.item-subtotal');
            const titleDisplay = card.querySelector('.item-title');
            const subtitleDisplay = card.querySelector('.item-subtitle');
            const customerInput = card.querySelector('.input-customer');

            const price = unformatNumber(priceInput?.value);
            const qty = parseInt(qtyInput?.value) || 1;
            const subtotal = price * qty;
            itemsTotal += subtotal;

            if (subtotalDisplay) subtotalDisplay.textContent = 'Rp ' + formatNumber(subtotal);
            if (titleDisplay && customerInput) titleDisplay.textContent = customerInput.value || 'Barang Baru';
            if (subtitleDisplay) subtitleDisplay.textContent = `Rp ${formatNumber(price)} x ${qty}`;
        });

        const dppLainnya = unformatNumber(document.getElementById('input-dpp-lainnya-hidden')?.value);
        const ppn = unformatNumber(document.getElementById('input-ppn-hidden')?.value);
        const layanan1 = unformatNumber(document.getElementById('input-layanan1-hidden')?.value);
        const grandTotal = itemsTotal + dppLainnya + ppn + layanan1;

        if(this.formTotalInput) this.formTotalInput.value = grandTotal;
        if(this.globalTotalDisplay) this.globalTotalDisplay.textContent = 'Rp ' + formatNumber(grandTotal);

        if (typeof this.onTotalChange === 'function') {
            this.onTotalChange();
        }
    }

    addItem() {
        if (!this.itemTemplate) return;
        
        const tempDiv = document.createElement('div');
        let html = this.itemTemplate.replace(/__INDEX__/g, this.itemCounter).replace(/__NUM__/g, this.itemCounter + 1);
        tempDiv.innerHTML = html;
        const newCard = tempDiv.firstElementChild;

        this.setupItemCardEvents(newCard);

        const existingCards = this.itemsContainer.querySelectorAll('.item-card');
        existingCards.forEach(card => {
            const body = card.querySelector('.item-body');
            const icon = card.querySelector('.icon-collapse');
            if (body && !body.classList.contains('hidden')) {
                body.classList.add('hidden');
                if (icon) icon.classList.add('rotate-180');
            }
        });

        this.itemsContainer.appendChild(newCard);
        this.itemCounter++;

        this.updateRemoveButtons();
        if (typeof window.lucide !== 'undefined') window.lucide.createIcons({ root: newCard });

        setTimeout(() => {
            const input = newCard.querySelector('.input-customer');
            if (input) input.focus();
            if (this.itemCounter > 1) newCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
    }

    setupItemCardEvents(card) {
        const priceDisp = card.querySelector('.input-price-display');
        const priceHid = card.querySelector('.input-price-hidden');
        const qtyInput = card.querySelector('.input-qty');
        const customerInput = card.querySelector('.input-customer');
        const reasonSelect = card.querySelector('.input-reason');
        const descInput = card.querySelector('.input-desc');
        const reqSpan = card.querySelector('.keterangan-required');

        if (priceDisp) {
            priceDisp.addEventListener('input', (e) => {
                let raw = unformatNumber(e.target.value);
                e.target.value = raw > 0 ? formatNumber(raw) : '';
                priceHid.value = raw;
                this.updateGlobalTotal();
                this.triggerPriceCheck(card);
            });
            priceDisp.addEventListener('blur', () => this.checkPriceAnomaly(card));
        }

        if (qtyInput) qtyInput.addEventListener('input', () => this.updateGlobalTotal());

        if (customerInput) {
            let debounceTimer;
            const dropdown = card.querySelector('.autocomplete-dropdown');
            const list = card.querySelector('.autocomplete-list');
            const masterIdInput = card.querySelector('.input-master-id');

            if (dropdown && list) {
                customerInput.addEventListener('input', (e) => {
                    if (masterIdInput) masterIdInput.value = '';
                    const query = e.target.value.trim();
                    const category = reasonSelect ? reasonSelect.value : '';

                    clearTimeout(debounceTimer);
                    if (query.length < 2) {
                        dropdown.classList.add('hidden');
                        return;
                    }

                    debounceTimer = setTimeout(() => {
                        fetch(`${Config.endpoints.reference.autocomplete}?q=${encodeURIComponent(query)}&category=${encodeURIComponent(category)}`)
                            .then(res => res.json())
                            .then(data => {
                                list.innerHTML = '';
                                if (data.suggestions && data.suggestions.length > 0) {
                                    data.suggestions.forEach(item => {
                                        const li = document.createElement('li');
                                        li.className = 'px-4 py-3 hover:bg-slate-50 cursor-pointer border-b border-slate-100 transition-colors flex justify-between items-center';

                                        let badgeColor = 'bg-slate-100 text-slate-500';
                                        if (item.match_type === 'exact') badgeColor = 'bg-teal-100 text-teal-700';
                                        else if (item.match_type === 'high') badgeColor = 'bg-emerald-100 text-emerald-700';
                                        else if (item.match_type === 'medium') badgeColor = 'bg-blue-100 text-blue-700';

                                        li.innerHTML = `
                                            <div>
                                                <div class="font-bold text-slate-700">${item.display_name}</div>
                                                ${item.category ? `<div class="text-[10px] text-slate-400 mt-0.5">${item.category}</div>` : ''}
                                            </div>
                                            <span class="text-[10px] items-center px-2 py-0.5 rounded-md font-bold ${badgeColor}">${item.confidence}%</span>
                                        `;

                                        li.addEventListener('click', () => {
                                            customerInput.value = item.display_name;
                                            if (masterIdInput) masterIdInput.value = item.id;
                                            dropdown.classList.add('hidden');

                                            if (reasonSelect && (!reasonSelect.value || reasonSelect.value === '') && item.category) {
                                                reasonSelect.value = item.category;
                                            }
                                            this.updateGlobalTotal();
                                            this.triggerPriceCheck(card);
                                        });
                                        list.appendChild(li);
                                    });
                                    dropdown.classList.remove('hidden');
                                } else {
                                    dropdown.classList.add('hidden');
                                }
                            }).catch(err => {
                                dropdown.classList.add('hidden');
                            });
                    }, 150);

                    this.updateGlobalTotal();
                    this.triggerPriceCheck(card);
                });

                document.addEventListener('click', (e) => {
                    if (!card.contains(e.target)) {
                        dropdown.classList.add('hidden');
                    }
                });
            } else {
                customerInput.addEventListener('input', () => {
                    this.updateGlobalTotal();
                    this.triggerPriceCheck(card);
                });
            }
            customerInput.addEventListener('blur', () => this.checkPriceAnomaly(card));
        }

        if (reasonSelect) {
            reasonSelect.addEventListener('change', (e) => {
                if (e.target.value === 'lainnya') {
                    if (descInput) descInput.required = true;
                    if (reqSpan) reqSpan.classList.remove('hidden');
                } else {
                    if (descInput) descInput.required = false;
                    if (reqSpan) reqSpan.classList.add('hidden');
                }
                this.triggerPriceCheck(card);
            });
        }

        const header = card.querySelector('.item-header');
        const body = card.querySelector('.item-body');
        const iconCollapse = card.querySelector('.icon-collapse');

        if (header && body && iconCollapse) {
            header.addEventListener('click', (e) => {
                if (e.target.closest('.btn-remove-item')) return;
                body.classList.toggle('hidden');
                if (body.classList.contains('hidden')) {
                    iconCollapse.classList.add('rotate-180');
                } else {
                    iconCollapse.classList.remove('rotate-180');
                }
            });
        }

        const btnRemove = card.querySelector('.btn-remove-item');
        if (btnRemove) {
            btnRemove.addEventListener('click', () => {
                card.classList.add('opacity-0', 'scale-95');
                setTimeout(() => {
                    card.remove();
                    this.updateItemNumbers();
                    this.updateGlobalTotal();
                    this.updateRemoveButtons();
                }, 300);
            });
        }
    }

    updateItemNumbers() {
        const cards = this.itemsContainer.querySelectorAll('.item-card');
        cards.forEach((card, idx) => {
            const numBox = card.querySelector('.item-number');
            if (numBox) numBox.textContent = idx + 1;
        });
    }

    triggerPriceCheck(card) {
        const index = card.dataset.index;
        if (this.priceCheckDebounce[index]) clearTimeout(this.priceCheckDebounce[index]);

        this.priceCheckDebounce[index] = setTimeout(() => {
            this.checkPriceAnomaly(card);
        }, 300);
    }

    async checkPriceAnomaly(card) {
        const customerInput = card.querySelector('.input-customer');
        const masterIdInput = card.querySelector('.input-master-id');
        const itemName = customerInput ? customerInput.value : '';
        const masterId = masterIdInput ? masterIdInput.value : '';

        const unitPrice = unformatNumber(card.querySelector('.input-price-display')?.value);
        const category = card.querySelector('.input-reason')?.value;
        const warningDiv = card.querySelector('.price-anomaly-warning');
        const refBox = card.querySelector('.price-ref-box');

        if (!itemName || unitPrice <= 0 || itemName.length < 2) {
            if(refBox) refBox.classList.add('hidden');
            if(warningDiv) warningDiv.classList.add('hidden');
            return;
        }

        try {
            const res = await fetch(Config.endpoints.reference.checkPriceIndex, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': Config.csrfToken },
                body: JSON.stringify({ item_name: itemName, unit_price: unitPrice, category: category, master_item_id: masterId })
            });
            const data = await res.json();

            if (data.has_reference) {
                if(refBox) refBox.classList.remove('hidden');
                
                const setRef = (selector, val) => { const el = card.querySelector(selector); if(el) el.textContent = val; };
                setRef('.price-ref-min', data.formatted.min);
                setRef('.price-ref-max', data.formatted.max);
                setRef('.price-ref-avg', data.formatted.avg);
                setRef('.price-ref-source', data.is_manual ? '• Manual' : '• Auto');

                if (data.is_anomaly) {
                    if(warningDiv) warningDiv.classList.remove('hidden');
                } else {
                    if(warningDiv) warningDiv.classList.add('hidden');
                }

                const setupBtn = (selector, val) => {
                    const btn = card.querySelector(selector);
                    if(btn) {
                        btn.onclick = () => {
                            const priceDisp = card.querySelector('.input-price-display');
                            const priceHid = card.querySelector('.input-price-hidden');
                            if(priceDisp) priceDisp.value = formatNumber(val);
                            if(priceHid) priceHid.value = val;
                            this.updateGlobalTotal();
                            if(warningDiv) warningDiv.classList.add('hidden');
                        };
                    }
                };
                
                setupBtn('.btn-fill-min', data.min_price);
                setupBtn('.btn-fill-avg', data.avg_price);
                setupBtn('.btn-fill-max', data.max_price);

            } else {
                if(refBox) refBox.classList.add('hidden');
                if(warningDiv) warningDiv.classList.add('hidden');
            }
        } catch (e) {
            console.error('Price check failed', e);
        }
    }

    updateRemoveButtons() {
        const cards = this.itemsContainer.querySelectorAll('.item-card');
        cards.forEach((card) => {
            const btn = card.querySelector('.btn-remove-item');
            if (btn) {
                if (cards.length <= 1) btn.classList.add('hidden');
                else btn.classList.remove('hidden');
            }
        });
    }
}
