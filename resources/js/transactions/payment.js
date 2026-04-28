import { formatNumber, unformatNumber } from './utils.js';
import { SearchEngine } from './search-engine.js';
import { closeViewModal, renderTransactionItemsCards } from './modals.js';
import { Config } from './config.js';

export function initPaymentHandlers() {
    window.performStatusAction = performStatusAction;
    window.submitApproval = submitApproval;
    window.openRejectModal = openRejectModal;
    window.closeRejectModal = closeRejectModal;
    window.openOverrideModal = openOverrideModal;
    window.closeOverrideModal = closeOverrideModal;
    window.openForceApproveModal = openForceApproveModal;
    window.closeForceApproveModal = closeForceApproveModal;
    window.openPaymentModal = openPaymentModal;
    window.closePaymentModal = closePaymentModal;
    window.confirmCashPayment = confirmCashPayment;
    window.resetPaymentFileInput = resetPaymentFileInput;
    
    // Global Event Listeners for closing modals
    document.getElementById('reject-modal')?.addEventListener('click', e => { if (e.target.id === 'reject-modal') closeRejectModal(); });
    document.getElementById('override-modal')?.addEventListener('click', e => { if (e.target.id === 'override-modal') closeOverrideModal(); });
    document.getElementById('force-approve-modal')?.addEventListener('click', e => { if (e.target.id === 'force-approve-modal') closeForceApproveModal(); });
    document.getElementById('payment-modal')?.addEventListener('click', e => { if (e.target.id === 'payment-modal') closePaymentModal(); });

    // Initialize AJAX Forms
    bindAjaxForm('override-form', closeOverrideModal, 'Override berhasil diajukan.');
    bindAjaxForm('force-approve-form', closeForceApproveModal, 'Transaksi berhasil di Force Approve.');
    bindAjaxForm('payment-form', closePaymentModal, 'Bukti Pembayaran berhasil diunggah.');

    // Reject form setup
    const rejectForm = document.getElementById('reject-form');
    if (rejectForm) {
        rejectForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const id = this.action.split('/').at(-2); 
            const reason = this.querySelector('textarea[name="rejection_reason"]')?.value || '';
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) { submitBtn.disabled = true; }
            if (typeof NProgress !== 'undefined') NProgress.start();

            fetch(Config.endpoints.transactions.status(id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': Config.csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ status: 'rejected', rejection_reason: reason, _method: 'PATCH' }),
            })
                .then(r => r.json().catch(() => ({})))
                .then(data => {
                    closeRejectModal();
                    showToast(`<div class="flex items-start gap-2"><i data-lucide="check-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-emerald-600"></i><div><strong class="text-emerald-800">Berhasil!</strong><br><span class="text-[11px] opacity-90 text-emerald-700">Transaksi berhasil ditolak.</span></div></div>`, 'success');
                    if (data.transaction) {
                        SearchEngine.updateTransaction(data.transaction);
                    } else {
                        SearchEngine.init();
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast(`<div class="flex items-start gap-2"><i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-red-600"></i><div><strong class="text-red-800">Gagal!</strong><br><span class="text-[11px] opacity-90 text-red-700">Gagal menolak transaksi. Coba lagi.</span></div></div>`, 'error');
                    if (submitBtn) { submitBtn.disabled = false; }
                })
                .finally(() => {
                    if (typeof NProgress !== 'undefined') NProgress.done();
                });
        });
    }

    // Payment File Input Preview
    const paymentFileInput = document.getElementById('payment_file_input');
    const previewWrap = document.getElementById('payment-file-preview');
    const previewPlaceholder = document.getElementById('preview-placeholder');
    const previewFilename = document.getElementById('preview-filename');
    const previewFilesize = document.getElementById('preview-filesize');

    if (paymentFileInput) {
        paymentFileInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                previewWrap.classList.remove('hidden');
                previewFilename.textContent = file.name;
                previewFilesize.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';

                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function (event) {
                        previewPlaceholder.innerHTML = `<img src="${event.target.result}" class="w-full h-full object-cover rounded-lg">`;
                    };
                    reader.readAsDataURL(file);
                } else if (file.type === 'application/pdf') {
                    previewPlaceholder.innerHTML = `
                            <div class="flex flex-col items-center justify-center text-rose-500">
                                <i data-lucide="file-text" class="w-8 h-8"></i>
                                <span class="text-[8px] font-black mt-1 uppercase">PDF</span>
                            </div>`;
                    if (typeof lucide !== 'undefined') lucide.createIcons({ root: previewPlaceholder });
                } else {
                    previewPlaceholder.innerHTML = `<i data-lucide="file" class="w-8 h-8 text-slate-300"></i>`;
                    if (typeof lucide !== 'undefined') lucide.createIcons({ root: previewPlaceholder });
                }
            } else {
                window.resetPaymentFileInput();
            }
        });
    }
}

function performStatusAction(id, status, triggerEl) {
    if (triggerEl) {
        triggerEl.disabled = true;
        triggerEl.innerHTML = '<i data-lucide="loader-2" class="w-3 h-3 animate-spin"></i>';
        if (typeof lucide !== 'undefined') lucide.createIcons({ root: triggerEl });
    }
    fetch(Config.endpoints.transactions.status(id), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': Config.csrfToken,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ status, _method: 'PATCH' }),
    })
        .then(r => r.json().catch(() => ({})))
        .then(data => {
            if (data.success) {
                showToast(`<div class="flex items-start gap-2"><i data-lucide="check-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-emerald-600"></i><div><strong class="text-emerald-800">Berhasil!</strong><br><span class="text-[11px] opacity-90 text-emerald-700">${data.message || 'Status berhasil diperbarui.'}</span></div></div>`, 'success');
                if (data.transaction) {
                    SearchEngine.updateTransaction(data.transaction);
                } else {
                    SearchEngine.init();
                }
            } else {
                throw new Error(data.message || 'Gagal memperbarui status');
            }
        })
        .catch(err => {
            console.error(err);
            showToast(`<div class="flex items-start gap-2"><i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i><div><strong>Gagal!</strong><br><span class="text-[11px] opacity-90">Coba lagi.</span></div></div>`, 'error');
            if (triggerEl) { triggerEl.disabled = false; }
        });
}

function submitApproval(status) {
    const currentTransactionId = document.getElementById('view-modal')?.querySelector('[onclick^="submitApproval"]')?.closest('.flex')?.querySelector('button')?.getAttribute('onclick')?.match(/performStatusAction\('([^']+)'/)?.[1];
    
    if (status === 'pending' && !confirm('Reset status ke Pending?')) return;
    closeViewModal();
    // Assuming currentTransactionId might be available. Wait, since it's hard to get from DOM, 
    // we should use the SearchEngine to find the active transaction or rely on it being passed.
    // However, in Blade it relies on global currentTransactionId. 
    // We can expose it or find it from the DOM.
    // Actually, in the old script: submitApproval relies on currentTransactionId.
    const id = window._modalVersionData?.d?.id;
    if (!id) return;
    performStatusAction(id, status, null);
}

function openRejectModal(transactionId, invoiceNumber) {
    const modal = document.getElementById('reject-modal');
    const inner = modal.querySelector('div');

    document.getElementById('reject-form').action = Config.endpoints.transactions.status(transactionId);
    document.getElementById('reject-modal-invoice').textContent = invoiceNumber;

    modal.classList.remove('hidden');
    requestAnimationFrame(() => {
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.remove('opacity-0');
        inner.classList.remove('scale-95');
        inner.classList.add('scale-100');
    });

    setTimeout(() => {
        const textarea = modal.querySelector('textarea[name="rejection_reason"]');
        if (textarea) textarea.focus();
    }, 350);
}

function closeRejectModal() {
    const modal = document.getElementById('reject-modal');
    const inner = modal.querySelector('div');

    if (document.activeElement && modal.contains(document.activeElement)) {
        document.activeElement.blur();
    }

    modal.classList.add('opacity-0');
    inner.classList.remove('scale-100');
    inner.classList.add('scale-95');

    setTimeout(() => {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        modal.querySelector('textarea').value = '';
    }, 300);
}

function openOverrideModal(transactionId, invoiceNumber) {
    const modal = document.getElementById('override-modal');
    const inner = modal.querySelector('div');

    document.getElementById('override-form').action = Config.endpoints.transactions.override(transactionId);
    document.getElementById('override-modal-invoice').textContent = invoiceNumber;

    modal.classList.remove('hidden');
    requestAnimationFrame(() => {
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.remove('opacity-0');
        inner.classList.remove('scale-95');
        inner.classList.add('scale-100');
    });
    setTimeout(() => {
        const textarea = modal.querySelector('textarea[name="override_reason"]');
        if (textarea) textarea.focus();
    }, 350);
}

function closeOverrideModal() {
    const modal = document.getElementById('override-modal');
    const inner = modal.querySelector('div');
    if (document.activeElement && modal.contains(document.activeElement)) document.activeElement.blur();
    modal.classList.add('opacity-0');
    inner.classList.remove('scale-100');
    inner.classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.getElementById('override-form').reset();
    }, 300);
}

function openForceApproveModal(transactionId, invoiceNumber) {
    const modal = document.getElementById('force-approve-modal');
    const inner = modal.querySelector('div');

    document.getElementById('force-approve-form').action = Config.endpoints.transactions.forceApprove(transactionId);
    document.getElementById('force-approve-modal-invoice').textContent = invoiceNumber;

    modal.classList.remove('hidden');
    requestAnimationFrame(() => {
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.remove('opacity-0');
        inner.classList.remove('scale-95');
        inner.classList.add('scale-100');
    });
    setTimeout(() => {
        const textarea = modal.querySelector('textarea[name="force_approve_reason"]');
        if (textarea) textarea.focus();
    }, 350);
}

function closeForceApproveModal() {
    const modal = document.getElementById('force-approve-modal');
    const inner = modal.querySelector('div');
    if (document.activeElement && modal.contains(document.activeElement)) document.activeElement.blur();
    modal.classList.add('opacity-0');
    inner.classList.remove('scale-100');
    inner.classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.getElementById('force-approve-form').reset();
    }, 300);
}

function resetPaymentFileInput() {
    const input = document.getElementById('payment_file_input');
    const wrap = document.getElementById('payment-file-preview');
    if (input) input.value = '';
    if (wrap) wrap.classList.add('hidden');
}

function openPaymentModal(id) {
    resetPaymentFileInput();
    const transaction = SearchEngine.getAll().find(x => x.id === id);
    if (!transaction) return;
    const transactionId = transaction.id;
    const invoiceNumber = transaction.invoice_number;
    const paymentMethod = transaction.payment_method;
    const amount = transaction.amount;
    const submitter = transaction.submitter || {};
    const specs = transaction.specs || {};
    const hasTelegram = transaction.submitter_has_telegram;

    const modal = document.getElementById('payment-modal');
    const inner = modal.querySelector('div');
    const loading = document.getElementById('payment-loading');
    const body = document.getElementById('payment-body');
    const submitBtn = document.getElementById('btnSubmitPayment');
    const submitBtnText = document.getElementById('btnSubmitPaymentText');

    document.getElementById('payment-form').reset();
    document.getElementById('transfer-profile-alert').classList.add('hidden');
    document.getElementById('cash-fields').classList.add('hidden');
    document.getElementById('transfer-fields').classList.add('hidden');
    document.getElementById('pengajuan-invoice-fields').classList.add('hidden');
    document.getElementById('p-detail-container').classList.add('hidden');
    document.getElementById('payment-method-container').classList.add('hidden');
    document.getElementById('payment_method_select').required = false;

    loading.classList.remove('hidden');
    body.classList.add('hidden');

    submitBtn.disabled = false;
    submitBtn.classList.remove('bg-slate-400', 'cursor-not-allowed', 'hover:bg-slate-400');
    submitBtn.classList.add('bg-cyan-600', 'hover:bg-cyan-700');
    submitBtnText.textContent = 'Upload & Simpan';

    const isPengajuan = transaction.type === 'pengajuan';
    const isGudang = transaction.type === 'gudang';

    if (!hasTelegram && !isPengajuan && !isGudang) {
        submitBtn.disabled = true;
        submitBtn.classList.remove('bg-cyan-600', 'hover:bg-cyan-700');
        submitBtn.classList.add('bg-slate-400', 'cursor-not-allowed', 'hover:bg-slate-400');
        submitBtnText.textContent = 'Teknisi Belum Daftar Telegram';
        showToast(`<div class="flex items-start gap-2"><i data-lucide="bell-off" class="w-4 h-4 mt-0.5 flex-shrink-0 text-rose-600"></i><div><strong class="text-rose-800">Peringatan!</strong><br><span class="text-[11px] opacity-90 text-rose-700">Teknisi belum mendaftarkan Telegram. Pembayaran Cash/Transfer tidak dapat diproses hingga teknisi mendaftar via bot.</span></div></div>`, 'error');
    }

    let endpoint = Config.endpoints.payment.uploadCash;

    document.getElementById('payment-modal-invoice').textContent = invoiceNumber;
    document.getElementById('payment-modal-amount').textContent = 'Rp ' + Number(amount).toLocaleString('id-ID');

    let form = document.getElementById('payment-form');
    form.querySelectorAll('.dyn-hidden').forEach(el => el.remove());

    const addHidden = (name, value) => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = name; inp.value = value; inp.className = 'dyn-hidden';
        form.appendChild(inp);
    };

    addHidden('transaksi_id', transactionId);
    addHidden('upload_id', transaction.upload_id || ('txn_' + transactionId));
    addHidden('expected_nominal', amount); 
    addHidden('teknisi_id', submitter.id || ''); 

    const bankInput = document.getElementById('transfer_bank');
    const nomorInput = document.getElementById('transfer_nomor');
    const namaInput = document.getElementById('transfer_nama');
    const paymentFileInput = document.getElementById('payment_file_input');
    const paymentLabel = document.getElementById('payment-modal-label');

    if (bankInput) { bankInput.required = false; bankInput.disabled = true; }
    if (nomorInput) { nomorInput.required = false; nomorInput.disabled = true; }
    if (namaInput) { namaInput.required = false; namaInput.disabled = true; }
    document.getElementById('cash_catatan').disabled = true;
    document.getElementById('p_catatan').disabled = true;
    ['p_ongkir', 'p_diskon_pengiriman', 'p_voucher_diskon', 'p_dpp_lainnya', 'p_tax_amount', 'p_biaya_layanan_1', 'p_biaya_layanan_2'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.disabled = true;
    });

    paymentFileInput.name = 'file';

    if (isPengajuan) {
        endpoint = Config.endpoints.payment.uploadPengajuan;
        document.getElementById('pengajuan-invoice-fields').classList.remove('hidden');
        document.getElementById('payment-method-container').classList.remove('hidden');
        document.getElementById('payment_method_select').required = true;
        document.getElementById('payment-modal-title').textContent = 'Upload Pembayaran Invoice';
        paymentFileInput.name = 'invoice_file';
        paymentFileInput.required = true;
        if(paymentLabel) paymentLabel.innerHTML = 'Unggah Foto Invoice <span class="text-red-500">*</span>';

        document.getElementById('p_catatan').disabled = false;
        ['p_ongkir', 'p_diskon_pengiriman', 'p_voucher_diskon', 'p_dpp_lainnya', 'p_tax_amount', 'p_biaya_layanan_1', 'p_biaya_layanan_2'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.disabled = false;
        });
    } else {
        document.getElementById('payment-modal-title').textContent = 'Upload Bukti Transfer/Cash';
        const isTransfer = paymentMethod && paymentMethod.includes('transfer');
        paymentFileInput.required = isTransfer;
        if (paymentLabel) {
            paymentLabel.innerHTML = isTransfer
                ? 'Unggah Foto / Screenshot <span class="text-red-500">*</span>'
                : 'Unggah Foto / Screenshot <span class="text-slate-400 font-normal">(Opsional)</span>';
        }

        if (isTransfer) {
            endpoint = Config.endpoints.payment.uploadTransfer;
            document.getElementById('transfer-fields').classList.remove('hidden');

            [bankInput, nomorInput, namaInput].forEach(el => {
                el.disabled = false;
                el.readOnly = false;
                el.required = true; 
                el.classList.remove('bg-slate-100', 'cursor-not-allowed');
            });

            if (paymentMethod === 'transfer_teknisi') {
                const select = document.getElementById('saved_bank_account');
                const container = document.getElementById('saved-accounts-container');
                select.innerHTML = '<option value="">-- Pilih Rekening --</option>';
                container.classList.add('hidden');

                fetch(Config.endpoints.reference.userBankAccounts(submitter.id))
                    .then(r => r.json())
                    .then(accounts => {
                        if (accounts.length > 0) {
                            container.classList.remove('hidden');
                            accounts.forEach(acc => {
                                const opt = document.createElement('option');
                                opt.value = JSON.stringify(acc);
                                opt.textContent = `${acc.bank_name} - ${acc.account_number} (${acc.account_name})`;
                                if (specs && specs.bank_account_id == acc.id) opt.selected = true;
                                select.appendChild(opt);
                            });
                            if (select.value) autoFillBankAccount(select);
                        }
                    });
            } else {
                document.getElementById('saved-accounts-container').classList.add('hidden');
            }

            if (paymentMethod === 'transfer_teknisi') {
                document.getElementById('transfer-method-badge').textContent = 'TRANSFER TEKNISI';

                [bankInput, nomorInput, namaInput].forEach(el => {
                    el.readOnly = true;
                    el.classList.add('bg-slate-100', 'cursor-not-allowed');
                });

                bankInput.value = ''; nomorInput.value = ''; namaInput.value = '';
                document.getElementById('transfer-profile-alert').classList.remove('hidden');
            } else if (paymentMethod === 'transfer_penjual') {
                document.getElementById('transfer-method-badge').textContent = 'TRANSFER PENJUAL (VENDOR)';

                [bankInput, nomorInput, namaInput].forEach(el => {
                    el.readOnly = true;
                    el.classList.add('bg-slate-100', 'cursor-not-allowed');
                });

                bankInput.value = specs.bank_name || '';
                nomorInput.value = specs.account_number || '';
                namaInput.value = specs.account_name || '';
            }
        } else {
            document.getElementById('cash-fields').classList.remove('hidden');
            document.getElementById('cash_catatan').disabled = false;
        }
    }

    form.action = endpoint;

    modal.classList.remove('hidden');
    requestAnimationFrame(() => {
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.remove('opacity-0');
        inner.classList.remove('scale-95');
        inner.classList.add('scale-100');
    });

    fetch(Config.endpoints.transactions.detail(id))
        .then(r => r.json())
        .then(d => {
            renderPaymentModalDetails(d);
            loading.classList.add('hidden');
            body.classList.remove('hidden');
        })
        .catch(err => {
            console.error(err);
            loading.innerHTML = '<p class="text-red-500 text-sm font-bold">Gagal memuat data. Coba lagi.</p>';
        });
}

function autoFillBankAccount(select) {
    if (!select.value) {
        document.getElementById('transfer_bank').value = '';
        document.getElementById('transfer_nomor').value = '';
        document.getElementById('transfer_nama').value = '';
        return;
    }
    const acc = JSON.parse(select.value);
    document.getElementById('transfer_bank').value = acc.bank_name;
    document.getElementById('transfer_nomor').value = acc.account_number;
    document.getElementById('transfer_nama').value = acc.account_name;
}

// Make sure to expose autoFillBankAccount to window if used inline
window.autoFillBankAccount = autoFillBankAccount;

function closePaymentModal() {
    const modal = document.getElementById('payment-modal');
    const inner = modal.querySelector('div');
    if (document.activeElement && modal.contains(document.activeElement)) document.activeElement.blur();
    modal.classList.add('opacity-0');
    inner.classList.remove('scale-100');
    inner.classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.getElementById('payment-form').reset();
        document.querySelectorAll('.dyn-hidden').forEach(e => e.remove());
    }, 300);
}

function renderPaymentModalDetails(d) {
    const detailContainer = document.getElementById('p-detail-container');
    detailContainer.classList.remove('hidden');

    const isDebt = d.status === 'waiting_payment' && d.status_label && d.status_label.includes('Hutang');
    const isLarge = d.type === 'pengajuan' && d.effective_amount >= 1000000;

    const statusCfg = Config.status[d.status] || { label: d.status, color: 'bg-gray-50 text-gray-700 border-gray-200', icon: 'info' };
    const typeCfg = Config.types[d.type] || { label: d.type, color: 'bg-gray-50 text-gray-700 border-gray-200', icon: 'file-text' };

    const color = typeof statusCfg.color === 'function' ? statusCfg.color(d.status === 'pending' ? d.type : (d.status === 'approved' ? isLarge : isDebt)) : statusCfg.color;
    const icon = typeof statusCfg.icon === 'function' ? statusCfg.icon(d.status === 'pending' ? d.type : (d.status === 'approved' ? isLarge : isDebt)) : statusCfg.icon;

    document.getElementById('p-badges').innerHTML = `
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border ${color}">
                <i data-lucide="${icon}" class="w-3.5 h-3.5"></i>
                ${d.status_label}
            </span>
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-bold border ${typeCfg.color}">
                <i data-lucide="${typeCfg.icon}" class="w-3 h-3"></i> ${d.type_label}
            </span>`;

    const fieldsEl = document.getElementById('p-fields');
    let fieldsHtml = '';

    const addField = (label, value, span2 = false) => {
        if (value === null || value === undefined || value === '') return;
        fieldsHtml += `
                <div class="${span2 ? 'sm:col-span-2' : ''}">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-wider">${label}</label>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800">${value}</div>
                </div>`;
    };

    addField('Pengaju', d.submitter?.name || '-');

    if (d.type === 'rembush') {
        addField('Nama Vendor', d.customer);
        addField('Tanggal Transaksi', d.date);
        addField('Kategori', d.category_label);
        addField('Metode Pencairan', d.payment_method_label);
        addField('Keterangan', d.description, true);
    } else if (d.type === 'gudang') {
        addField('Pembeli', d.submitter?.name || '-');
        addField('Toko / Vendor', d.vendor || '-');
        addField('Tanggal Belanja', d.date);
        addField('Kategori', d.category_label);
        addField('Metode Bayar', d.payment_method_label);
        addField('Keterangan', d.description, true);
    } else {
        if (!d.items || d.items.length === 0) {
            addField('Nama Barang/Jasa', d.customer, true);
            addField('Vendor', d.vendor);
            addField('Alasan Pembelian', d.purchase_reason_label);
            addField('Jumlah', d.quantity);
            addField('Estimasi Harga Satuan', d.estimated_price ? 'Rp ' + Number(d.estimated_price).toLocaleString('id-ID') : null);
        } else {
            addField('Alasan Pembelian Utama', d.purchase_reason_label);
        }
    }

    fieldsEl.innerHTML = fieldsHtml;

    const itemsWrap = document.getElementById('p-items-wrap');
    const itemsTbody = document.getElementById('p-items-tbody');
    const itemsTableCont = document.getElementById('p-items-table-container');
    const itemsDivCont = document.getElementById('p-items-div-container');

    if ((d.items && d.items.length > 0) || (d.items_snapshot && d.items_snapshot.length > 0)) {
        itemsWrap.classList.remove('hidden');

        if (d.type === 'pengajuan') {
            if (itemsTableCont) itemsTableCont.classList.add('hidden');
            if (itemsDivCont) {
                itemsDivCont.classList.remove('hidden');
                const versionToUse = d.is_edited_by_management ? 'management' : 'original';
                const itemsToRender = d.is_edited_by_management ? d.items : (d.items_snapshot || d.items);

                itemsDivCont.innerHTML = renderTransactionItemsCards(itemsToRender, versionToUse, d.items_snapshot || []);
                if (typeof lucide !== 'undefined') lucide.createIcons({ root: itemsDivCont });
            }
        } else {
            if (itemsDivCont) itemsDivCont.classList.add('hidden');
            if (itemsTableCont) itemsTableCont.classList.remove('hidden');

            itemsTbody.innerHTML = d.items.map(item => `
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-3 py-2 text-slate-700 font-medium">${item.name || '-'}</td>
                        <td class="px-3 py-2 text-center">${item.qty || '-'}</td>
                        <td class="px-3 py-2">${item.unit || '-'}</td>
                        <td class="px-3 py-2 text-right">Rp ${Number(item.price || 0).toLocaleString('id-ID')}</td>
                        <td class="px-3 py-2 text-right font-bold">Rp ${((Number(item.qty) || 0) * (Number(item.price) || 0)).toLocaleString('id-ID')}</td>
                    </tr>`).join('');
        }
    } else {
        itemsWrap.classList.add('hidden');
    }

    const branchesWrap = document.getElementById('p-branches-wrap');
    const branchesEl = document.getElementById('p-branches');

    if (d.branches && d.branches.length > 0) {
        branchesWrap.classList.remove('hidden');
        branchesEl.innerHTML = d.branches.map(b => `
                <div class="flex items-center justify-between bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                    <span class="text-sm font-bold text-slate-700">${b.name}</span>
                    <div class="text-right">
                        <span class="text-xs font-bold text-slate-500">${b.percent}%</span>
                        <span class="text-xs text-slate-400 ml-2">(${b.amount})</span>
                    </div>
                </div>`).join('');

        if (d.type === 'pengajuan') {
            const container = document.getElementById('p_sumber_dana_container');
            if (container) {
                container.innerHTML = '';
                d.branches_raw.forEach((b, idx) => {
                    const html = `
                            <div id="sd_card_${b.id}" class="sd-card p-4 bg-white border-2 border-slate-100 rounded-2xl hover:border-teal-400 transition-all duration-200">
                                <div class="flex items-center gap-4">
                                    <div class="relative flex items-center">
                                        <input type="checkbox" id="sd_check_${b.id}" class="sd-checkbox peer sr-only" value="${b.id}" data-alloc="${b.allocation_amount}" data-percent="${b.allocation_percent}" data-name="${b.name}">
                                        <label for="sd_check_${b.id}" class="w-8 h-8 border-2 border-slate-200 rounded-xl flex items-center justify-center cursor-pointer transition-all peer-checked:bg-teal-600 peer-checked:border-teal-600 peer-checked:[&_svg]:opacity-100 peer-checked:[&_i]:opacity-100 hover:border-teal-200">
                                            <i data-lucide="check" class="w-4 h-4 text-white opacity-0 transition-opacity"></i>
                                        </label>
                                    </div>
                                    <div class="flex-1">
                                        <label for="sd_check_${b.id}" class="block cursor-pointer">
                                            <div class="text-sm font-black text-slate-800 uppercase tracking-tight leading-none mb-1.5">${b.name}</div>
                                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider leading-none">Alokasi: Rp ${Number(b.allocation_amount).toLocaleString('id-ID')} (${b.allocation_percent}%)</div>
                                        </label>
                                    </div>
                                    <div class="w-44 relative">
                                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-black text-slate-400">Rp</span>
                                        <input type="text" id="sd_amount_${b.id}" name="sumber_dana[${idx}][amount]" disabled placeholder="0"
                                            class="sd-amount nominal-input w-full pl-10 pr-4 py-2.5 text-sm font-black text-slate-800 border-2 border-slate-100 rounded-xl outline-none focus:border-teal-400 focus:ring-4 focus:ring-teal-50 transition-all disabled:bg-slate-50/50 disabled:text-slate-300">
                                        <input type="hidden" id="sd_branch_${b.id}" name="sumber_dana[${idx}][branch_id]" value="${b.id}" disabled>
                                    </div>
                                </div>
                                <div id="sd_status_${b.id}" class="mt-3 text-right text-[10px] font-bold tracking-tight hidden"></div>
                            </div>
                        `;
                    container.insertAdjacentHTML('beforeend', html);
                });

                // Attach nominal formatting
                document.querySelectorAll('.nominal-input').forEach(inp => {
                    inp.removeEventListener('input', window.handleNominalInput || window.AppConfig.handleNominalInput);
                    inp.addEventListener('input', window.handleNominalInput || window.AppConfig.handleNominalInput);
                });

                document.querySelectorAll('.sd-checkbox').forEach(cb => {
                    cb.addEventListener('change', function () {
                        const id = this.value;
                        const card = document.getElementById('sd_card_' + id);
                        const amountInput = document.getElementById('sd_amount_' + id);
                        const branchInput = document.getElementById('sd_branch_' + id);
                        const alloc = parseInt(this.dataset.alloc);

                        if (this.checked) {
                            amountInput.disabled = false;
                            branchInput.disabled = false;
                            amountInput.value = formatNumber(alloc);
                            amountInput.required = true;
                            card.classList.remove('border-slate-100');
                            card.classList.add('border-teal-500', 'bg-teal-50/10');
                        } else {
                            amountInput.disabled = true;
                            branchInput.disabled = true;
                            amountInput.value = '';
                            amountInput.required = false;
                            card.classList.remove('border-teal-500', 'bg-teal-50/10');
                            card.classList.add('border-slate-100');
                        }
                        calculateSumberDanaTotal(d.effective_amount);
                    });
                });

                document.querySelectorAll('.sd-amount').forEach(inp => {
                    inp.addEventListener('input', () => calculateSumberDanaTotal(d.effective_amount));
                });

                ['p_ongkir', 'p_diskon_pengiriman', 'p_voucher_diskon', 'p_dpp_lainnya', 'p_tax_amount', 'p_biaya_layanan_1', 'p_biaya_layanan_2'].forEach(id => {
                    document.getElementById(id)?.addEventListener('input', () => calculateSumberDanaTotal(d.effective_amount));
                });

                calculateSumberDanaTotal(d.effective_amount);
            }
        }
    } else {
        branchesWrap.classList.add('hidden');
        document.getElementById('btnSubmitPayment').disabled = false;
        document.getElementById('btnSubmitPayment').classList.remove('opacity-50', 'cursor-not-allowed');
    }

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function calculateSumberDanaTotal(baseTotal) {
    const totalEl = document.getElementById('p_sumber_dana_total');
    const totalVal = document.getElementById('p_sumber_dana_total_value');
    const diffEl = document.getElementById('p_sumber_dana_diff');
    const debtPreview = document.getElementById('p_debt_preview');
    const debtList = document.getElementById('p_debt_preview_list');
    const btnSubmit = document.getElementById('btnSubmitPayment');

    const ongkir = unformatNumber(document.getElementById('p_ongkir')?.value || "0");
    const diskon = unformatNumber(document.getElementById('p_diskon_pengiriman')?.value || "0");
    const voucher = unformatNumber(document.getElementById('p_voucher_diskon')?.value || "0");
    const dppLainnya = unformatNumber(document.getElementById('p_dpp_lainnya')?.value || "0");
    const taxAmt = unformatNumber(document.getElementById('p_tax_amount')?.value || "0");
    const layanan1 = unformatNumber(document.getElementById('p_biaya_layanan_1')?.value || "0");
    const layanan2 = unformatNumber(document.getElementById('p_biaya_layanan_2')?.value || "0");

    const finalTotalTarget = baseTotal + ongkir + dppLainnya + taxAmt + layanan1 + layanan2 - diskon - voucher;

    totalEl.classList.remove('hidden');

    let total = 0;
    let creditors = {};
    let debtors = {};
    let branches = {};

    document.querySelectorAll('.sd-checkbox').forEach(cb => {
        const id = cb.value;
        const name = cb.dataset.name;
        const percent = parseFloat(cb.dataset.percent);

        const alloc = Math.round((finalTotalTarget * percent) / 100);
        const statusEl = document.getElementById('sd_status_' + id);
        const labelEl = document.querySelector(`label[for="sd_check_${id}"] div.text-slate-400`);

        if (labelEl) {
            labelEl.textContent = `Alokasi: Rp ${alloc.toLocaleString('id-ID')} (${percent}%)`;
        }

        branches[id] = { id, name, alloc };

        const paidValue = cb.checked ? document.getElementById('sd_amount_' + id).value : 0;
        const paid = unformatNumber(paidValue);
        total += paid;

        if (cb.checked) {
            if (paid > alloc) {
                creditors[id] = paid - alloc;
                statusEl.innerHTML = `<span class="text-teal-600">+ Lebih bayar Rp ${(paid - alloc).toLocaleString('id-ID')} (Menalangi)</span>`;
                statusEl.classList.remove('hidden');
            } else if (paid < alloc) {
                debtors[id] = alloc - paid;
                statusEl.innerHTML = `<span class="text-red-500">- Kurang bayar Rp ${(alloc - paid).toLocaleString('id-ID')} (Berhutang)</span>`;
                statusEl.classList.remove('hidden');
            } else {
                statusEl.classList.add('hidden');
            }
        } else {
            debtors[id] = alloc;
            statusEl.innerHTML = `<span class="text-red-500">- Kurang bayar Rp ${alloc.toLocaleString('id-ID')} (Berhutang)</span>`;
            statusEl.classList.remove('hidden');
        }
    });

    totalVal.textContent = 'Rp ' + total.toLocaleString('id-ID');

    if (total !== finalTotalTarget) {
        totalVal.classList.remove('text-teal-600');
        totalVal.classList.add('text-red-500');
        diffEl.classList.remove('text-emerald-500');
        diffEl.classList.add('text-red-500');
        const diff = finalTotalTarget - total;
        diffEl.textContent = diff > 0 ? `Kurang Rp ${diff.toLocaleString('id-ID')} dari Total Tagihan` : `Kelebihan Rp ${Math.abs(diff).toLocaleString('id-ID')} dari Total Tagihan`;
        btnSubmit.disabled = true;
        btnSubmit.classList.add('opacity-50', 'cursor-not-allowed');
    } else {
        totalVal.classList.remove('text-red-500');
        totalVal.classList.add('text-teal-600');
        diffEl.classList.remove('text-red-500');
        diffEl.classList.add('text-emerald-500');
        diffEl.textContent = 'Nominal sesuai dengan nilai bayar transaksi';
        btnSubmit.disabled = false;
        btnSubmit.classList.remove('opacity-50', 'cursor-not-allowed');
    }

    const creditorIds = Object.keys(creditors);
    const debtorIds = Object.keys(debtors);

    if (creditorIds.length > 0 && debtorIds.length > 0) {
        debtPreview.classList.remove('hidden');
        let debtHtml = '';

        const totalExcess = Object.values(creditors).reduce((a, b) => a + b, 0);

        for (let debtorId of debtorIds) {
            const debtAmt = debtors[debtorId];
            let cardHtml = `
                    <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm hover:shadow-md transition-all">
                        <div class="flex justify-between items-start mb-1.5">
                            <div class="space-y-0.5">
                                <h4 class="text-sm font-black text-slate-800 uppercase tracking-tight">${branches[debtorId].name}</h4>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Total beban hutang</p>
                            </div>
                            <div class="px-2.5 py-1.5 bg-red-50/50 text-red-600 text-[11px] font-black rounded-lg border border-red-50">
                                Rp ${debtAmt.toLocaleString('id-ID')}
                            </div>
                        </div>
                        <div class="my-4 border-t border-slate-50 border-dashed"></div>
                        <div class="text-[9px] font-black text-slate-400 uppercase tracking-[0.15em] mb-3">Rincian Pembayaran Ke:</div>
                        <div class="space-y-3">
                `;

            for (let creditorId of creditorIds) {
                const excess = creditors[creditorId];
                const proportion = excess / totalExcess;
                const finalAmt = Math.round(debtAmt * proportion);

                if (finalAmt > 0) {
                    cardHtml += `
                            <div class="flex items-center justify-between group">
                                <div class="flex items-center gap-3">
                                    <div class="w-4 h-4 rounded-full bg-slate-50 flex items-center justify-center">
                                        <i data-lucide="arrow-right" class="w-2.5 h-2.5 text-slate-300 group-hover:text-teal-400 transition-colors"></i>
                                    </div>
                                    <span class="text-[11px] font-bold text-slate-600 group-hover:text-slate-800 transition-colors uppercase tracking-tight">${branches[creditorId].name}</span>
                                </div>
                                <span class="text-[11px] font-black text-slate-800 bg-slate-50/50 px-2 py-1 rounded-md">Rp ${finalAmt.toLocaleString('id-ID')}</span>
                            </div>
                        `;
                }
            }

            cardHtml += `</div></div>`;
            debtHtml += cardHtml;
        }
        debtList.innerHTML = debtHtml;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    } else {
        debtPreview.classList.add('hidden');
        debtList.innerHTML = '';
    }
}

let isConfirmingCash = false;
function confirmCashPayment(id, action) {
    if (isConfirmingCash) return;

    const allTx = SearchEngine.getAll();
    if (!allTx || allTx.length === 0) return;
    const t = allTx.find(x => x.id === id);
    if (!t) return;

    let msg = action === 'terima' ? `Konfirmasi terima uang CASH untuk invoice ${t.invoice_number}?` : `Tolak penerimaan CASH untuk invoice ${t.invoice_number} karena tidak sesuai?`;
    if (!confirm(msg)) return;

    let catatan = '';
    if (action === 'tolak') {
        catatan = prompt("Harap masukkan alasan penolakan:") || '';
    } else {
        catatan = prompt("Catatan (Opsional):") || '';
    }

    isConfirmingCash = true;
    if (typeof NProgress !== 'undefined') NProgress.start();

    const formData = new FormData();
    formData.append('transaksi_id', t.id);
    formData.append('upload_id', t.upload_id || `txn_${t.id}`);
    formData.append('teknisi_id', t.submitter?.id || '');
    formData.append('action', action);
    formData.append('catatan', catatan);

    fetch('/api/v1/payment/cash/konfirmasi', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': window.AppConfig.csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
        .then(async r => {
            const data = await r.json().catch(() => ({}));
            if (!r.ok) throw new Error(data.message || 'Gagal mengirim konfirmasi.');
            return data;
        })
        .then(data => {
            showToast(`<div class="flex items-start gap-2"><i data-lucide="check-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-emerald-600"></i><div><strong class="text-emerald-800">Berhasil!</strong><br><span class="text-[11px] opacity-90 text-emerald-700">${data.message || 'Konfirmasi berhasil dikirim.'}</span></div></div>`, 'success');
            SearchEngine.init(); 
        })
        .catch(err => {
            console.error(err);
            showToast(`<div class="flex items-start gap-2"><i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-red-600"></i><div><strong class="text-red-800">Gagal!</strong><br><span class="text-[11px] opacity-90 text-red-700">${err.message || 'Terjadi kesalahan sistem.'}</span></div></div>`, 'error');
        })
        .finally(() => {
            isConfirmingCash = false;
            if (typeof NProgress !== 'undefined') NProgress.done();
        });
}

function bindAjaxForm(formId, closeModalFunc, successMsg) {
    const form = document.getElementById(formId);
    if (!form) return;
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const submitBtn = this.querySelector('button[type="submit"]');
        const submitText = submitBtn.querySelector('span') || submitBtn;
        const loader = submitBtn.querySelector('.animate-spin');

        const originalText = submitText.textContent;
        submitBtn.disabled = true;
        if (loader) {
            submitText.classList.add('opacity-0');
            loader.classList.remove('hidden');
        } else {
            submitText.textContent = 'Memproses...';
        }
        if (typeof NProgress !== 'undefined') NProgress.start();

        const formData = new FormData(this);
        this.querySelectorAll('.nominal-input').forEach(inp => {
            if (inp.name && formData.has(inp.name)) {
                const rawValue = inp.value ? String(inp.value).replace(/\\D/g, "") : "0";
                formData.set(inp.name, rawValue || "0");
            }
        });

        fetch(this.action, {
            method: this.method || 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': window.AppConfig.csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData 
        })
            .then(async r => {
                const data = await r.json().catch(() => ({}));
                if (!r.ok) {
                    const error = new Error(data.message || 'Gagal memproses form');
                    error.errors = data.errors;
                    throw error;
                }
                return data;
            })
            .then(data => {
                closeModalFunc();
                showToast(`<div class="flex items-start gap-2"><i data-lucide="check-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-emerald-600"></i><div><strong class="text-emerald-800">Berhasil!</strong><br><span class="text-[11px] opacity-90 text-emerald-700">${successMsg || data.message || 'Aksi berhasil'}</span></div></div>`, 'success');
                if (data.transaction) {
                    SearchEngine.updateTransaction(data.transaction);
                } else {
                    SearchEngine.init();
                }
            })
            .catch(err => {
                console.error(err);
                let errorHtml = err.message || 'Terjadi kesalahan sistem.';
                if (err.errors) {
                    const errorList = Object.values(err.errors).flat();
                    if (errorList.length > 0) {
                        errorHtml = errorList.join('<br>');
                    }
                }
                showToast(`<div class="flex items-start gap-2"><i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-red-600"></i><div><strong class="text-red-800">Gagal!</strong><br><span class="text-[11px] opacity-90 text-red-700">${errorHtml}</span></div></div>`, 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                if (loader) {
                    submitText.classList.remove('opacity-0');
                    loader.classList.add('hidden');
                } else {
                    submitText.textContent = originalText;
                }
                if (typeof NProgress !== 'undefined') NProgress.done();
            });
    });
}
