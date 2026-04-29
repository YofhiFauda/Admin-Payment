import { formatNumber, unformatNumber, escapeHtml } from './utils.js';
import { SearchEngine } from './search-engine.js';
import { Config } from './config.js';

let currentTransactionId = null;
let currentDebtId = null;

export function initModals() {
    // Make variables accessible globally where needed by inline handlers
    window.openImageViewer = openImageViewer;
    window.closeImageViewer = closeImageViewer;
    window.openViewModal = openViewModal;
    window.closeViewModal = closeViewModal;
    window.toggleVersionInModal = toggleVersionInModal;
    window.settleBranchDebt = settleBranchDebt;
    window.closeBranchDebtModal = closeBranchDebtModal;

    // Attach click events for image viewer
    const closeViewer = document.getElementById('close-viewer');
    const imageViewer = document.getElementById('image-viewer');
    const viewerImage = document.getElementById('viewer-image');
    
    if (closeViewer) closeViewer.addEventListener('click', e => { e.stopPropagation(); closeImageViewer(); });
    if (imageViewer) imageViewer.addEventListener('click', e => { if (e.target === imageViewer) closeImageViewer(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && imageViewer && !imageViewer.classList.contains('hidden')) closeImageViewer(); });

    // View Modal outside click
    document.getElementById('view-modal')?.addEventListener('click', e => {
        if (e.target.id === 'view-modal') closeViewModal();
    });

    // Branch debt form submit
    const branchDebtForm = document.getElementById('branch-debt-form');
    if (branchDebtForm) {
        branchDebtForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!currentDebtId) return;

            const btn = document.getElementById('btnSubmitBranchDebt');
            const loader = document.getElementById('btnSubmitBranchDebtLoader');
            const text = document.getElementById('btnSubmitBranchDebtText');

            btn.disabled = true;
            btn.classList.add('opacity-80', 'cursor-not-allowed');
            loader.classList.remove('hidden');
            text.textContent = 'Memproses...';

            const formData = new FormData(this);
            formData.append('_method', 'PATCH');

            fetch(Config.endpoints.branchDebts.settle(currentDebtId), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': Config.csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        showToast(res.message, 'success');
                        closeBranchDebtModal();
                        if (currentTransactionId) {
                            openViewModal(currentTransactionId);
                        }
                    } else {
                        showToast(res.message, 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Terjadi kesalahan jaringan.', 'error');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.classList.remove('opacity-80', 'cursor-not-allowed');
                    loader.classList.add('hidden');
                    text.textContent = 'Upload & Simpan';
                });
        });
    }
}

// ─── Image Viewer ──────────────────────────────────────────────
let lastFocusedElement = null;

export function openImageViewer(src, title = null, forcePdf = false) {
    lastFocusedElement = document.activeElement;
    const isPdf = forcePdf || src.toLowerCase().endsWith('.pdf') || src.startsWith('data:application/pdf');
    const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
    const viewerPdf = document.getElementById('viewer-pdf');
    const viewerFooter = document.getElementById('viewer-footer');
    const viewerPdfLink = document.getElementById('viewer-pdf-link');
    const viewerImage = document.getElementById('viewer-image');
    const imageViewer = document.getElementById('image-viewer');
    const closeViewer = document.getElementById('close-viewer');

    if (!imageViewer) return;

    if (isPdf) {
        if (isMobile) {
            window.open(src, '_blank');
            return;
        }
        viewerImage.classList.add('hidden');
        viewerPdf.classList.remove('hidden');
        viewerPdf.src = src;
        viewerFooter.classList.remove('hidden');
        viewerPdfLink.href = src;
        document.getElementById('viewer-header-title').textContent = 'PREVIEW DOKUMEN PDF';
    } else {
        viewerImage.classList.remove('hidden');
        viewerPdf.classList.add('hidden');
        viewerFooter.classList.add('hidden');
        viewerImage.src = src;
        document.getElementById('viewer-header-title').textContent = 'PREVIEW FOTO';
    }

    imageViewer.classList.remove('hidden');
    imageViewer.classList.add('flex');
    requestAnimationFrame(() => {
        imageViewer.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        if (typeof lucide !== 'undefined') lucide.createIcons({ root: imageViewer });
        setTimeout(() => closeViewer?.focus(), 50);
    });
}

export function closeImageViewer() {
    const imageViewer = document.getElementById('image-viewer');
    const viewerImage = document.getElementById('viewer-image');
    if (!imageViewer) return;

    if (document.activeElement && imageViewer.contains(document.activeElement)) document.activeElement.blur();
    imageViewer.classList.add('hidden');
    imageViewer.classList.remove('flex');
    document.body.style.overflow = '';
    imageViewer.setAttribute('aria-hidden', 'true');
    setTimeout(() => {
        if (viewerImage) viewerImage.src = '';
        const vPdf = document.getElementById('viewer-pdf');
        if (vPdf) {
            vPdf.src = '';
            vPdf.classList.add('hidden');
        }
        const vFooter = document.getElementById('viewer-footer');
        if (vFooter) vFooter.classList.add('hidden');

        if (lastFocusedElement?.focus) lastFocusedElement.focus();
    }, 200);
}

// ─── View Modal & Render Details ────────────────────────────────
export function openViewModal(id) {
    currentTransactionId = id;

    const modal = document.getElementById('view-modal');
    const modalBox = document.getElementById('view-modal-content');
    const loading = document.getElementById('view-loading');
    const body = document.getElementById('view-body');

    if (!modal) return;

    loading.innerHTML = `
            <div class="w-10 h-10 border-4 border-slate-200 border-t-blue-500 rounded-full animate-spin mx-auto mb-4"></div>
            <p class="text-sm text-slate-400 font-medium">Memuat detail...</p>`;
    loading.style.display = 'flex';
    loading.classList.remove('hidden');
    body.style.display = 'none';

    // Show modal first using style.display to avoid Tailwind hidden/flex conflict
    modal.style.display = 'flex';
    modal.classList.remove('hidden', 'opacity-0');
    if (window.toggleBodyScroll) window.toggleBodyScroll(true);
    else { document.documentElement.style.overflow = 'hidden'; document.body.style.overflow = 'hidden'; }

    // Then animate
    requestAnimationFrame(() => {
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.remove('opacity-0');
        modalBox.classList.remove('scale-95');
        modalBox.classList.add('scale-100');
    });

    fetch(Config.endpoints.transactions.detail(id))
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(d => {
            renderViewModal(d);
            loading.style.display = 'none';
            loading.classList.add('hidden');
            body.style.display = 'flex';
            body.style.flexDirection = 'column';

            // ATTACH CLICK EVENT TO IMAGE
            const imgWrapper = document.getElementById('v-image-wrapper');
            if (imgWrapper) {
                // Remove previous listeners by cloning
                const newWrapper = imgWrapper.cloneNode(true);
                imgWrapper.parentNode.replaceChild(newWrapper, imgWrapper);
                newWrapper.addEventListener('click', function () {
                    const img = document.getElementById('v-image');
                    const pdfIcon = document.getElementById('v-pdf-icon');

                    if (img && !img.classList.contains('hidden') && img.src) {
                        openImageViewer(img.src);
                    } else if (pdfIcon && !pdfIcon.classList.contains('hidden') && pdfIcon.dataset.src) {
                        openImageViewer(pdfIcon.dataset.src, null, true);
                    }
                });
            }

            if (typeof lucide !== 'undefined') lucide.createIcons();

            // Focus close button after content loads
            setTimeout(() => {
                const closeBtn = modal.querySelector('button[onclick="closeViewModal()"]');
                if (closeBtn) closeBtn.focus();
            }, 150);
        })
        .catch(err => {
            console.error(err);
            loading.innerHTML = '<p class="text-red-500 text-sm font-bold">Gagal memuat data. Coba lagi.</p>';
            loading.style.display = 'flex';
        });
}

export function closeViewModal() {
    const modal = document.getElementById('view-modal');
    const modalBox = document.getElementById('view-modal-content');
    if (!modal) return;

    // Remove focus from any element inside modal FIRST
    if (document.activeElement && modal.contains(document.activeElement)) {
        document.activeElement.blur();
    }

    // Unlock scroll immediately (before animation finishes)
    if (window.toggleBodyScroll) window.toggleBodyScroll(false);
    else { document.documentElement.style.overflow = ''; document.body.style.overflow = ''; }

    // Animate close
    modal.classList.add('opacity-0');
    if (modalBox) {
        modalBox.classList.remove('scale-100');
        modalBox.classList.add('scale-95');
    }

    // After animation, hide
    setTimeout(() => {
        modal.style.display = 'none';
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
    }, 300);
}

export function settleBranchDebt(debtId) {
    currentDebtId = debtId;
    const modal = document.getElementById('branch-debt-modal');
    if (modal) {
        document.getElementById('branch_debt_file_input').value = '';
        document.getElementById('branch_debt_notes').value = '';
        modal.classList.remove('hidden');
        setTimeout(() => modal.classList.remove('opacity-0'), 10);
        document.body.style.overflow = 'hidden';
        if (typeof lucide !== "undefined") lucide.createIcons({ root: modal });
    }
}

export function closeBranchDebtModal() {
    const modal = document.getElementById('branch-debt-modal');
    if (modal) {
        modal.classList.add('opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }, 300);
    }
}

// ─── Modal View Field Renderers ─────────────────────────────────

function createFieldHTML(label, value, span2 = false) {
    if (value === null || value === undefined || value === '') return '';
    return `
        <div class="${span2 ? 'sm:col-span-2' : ''}">
            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-wider">${label}</label>
            <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800">${value}</div>
        </div>`;
}

function renderRembushFields(d) {
    let html = '';
    html += createFieldHTML('Pengaju', d.submitter?.name || '-');
    html += createFieldHTML('Nama Vendor', d.customer);
    html += createFieldHTML('Tanggal Transaksi', d.date);
    html += createFieldHTML('Kategori', d.category_label);
    html += createFieldHTML('Metode Pencairan', d.payment_method_label, true);
    html += createFieldHTML('Keterangan', d.description, true);
    return html;
}

function renderGudangFields(d) {
    let html = '';
    html += createFieldHTML('Pembeli', d.submitter?.name || '-');
    html += createFieldHTML('Toko / Vendor', d.vendor || '-');
    html += createFieldHTML('Tanggal Belanja', d.date);
    html += createFieldHTML('Kategori', d.category_label);
    html += createFieldHTML('Metode Bayar', d.payment_method_label, true);
    html += createFieldHTML('Keterangan', d.description, true);

    if ((d.status === 'completed' || d.status === 'waiting_payment') && d.invoice_file_url) {
        let sumberDanaHtml = '';
        if (d.sumber_dana_data && d.sumber_dana_data.length > 0) {
            const branchesLookup = {};
            d.branches_raw.forEach(b => branchesLookup[b.id] = b.name);

            sumberDanaHtml = `
                <div class="sm:col-span-2 mb-3">
                    <label class="block text-[9px] font-bold text-teal-600/60 uppercase mb-2 font-black tracking-widest">Sumber Dana Pembayaran</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                        ${d.sumber_dana_data.map(sd => `
                            <div class="bg-teal-50 border border-teal-100 rounded-lg p-2.5 flex justify-between items-center shadow-sm">
                                <span class="text-[10px] font-black text-slate-700 uppercase tracking-tight">${branchesLookup[sd.branch_id] || 'Cabang ' + sd.branch_id}</span>
                                <span class="text-[11px] font-black text-teal-600">Rp ${Number(sd.amount).toLocaleString('id-ID')}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>`;
        }

        html += `
                <div class="sm:col-span-2 mt-4 pt-6 border-t border-slate-100">
                    <label class="block text-[11px] font-black text-emerald-500 uppercase mb-4 tracking-[0.2em]">Detail Pembayaran Pembelian</label>
                    ${sumberDanaHtml}
                    <div class="mt-4 flex flex-col gap-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider">Bukti Transfer / Cash</label>
                        <a href="${d.invoice_file_url}" target="_blank" class="inline-flex items-center gap-2.5 px-5 py-3 bg-white border border-slate-200 rounded-xl text-xs font-black text-emerald-600 hover:bg-emerald-50 hover:border-emerald-100 transition-all shadow-sm active:scale-95 w-fit">
                            <i data-lucide="image" class="w-4 h-4"></i> Lihat Bukti Bayar
                        </a>
                    </div>
                </div>`;
    }
    return html;
}

function renderBranchDebtsHTML(debts) {
    if (!debts || debts.length === 0) return '';
    return `
        <div class="sm:col-span-2 mb-3">
            <div class="flex items-center gap-2 mb-3">
                <label class="block text-[10px] font-bold text-red-500 uppercase tracking-widest">Hutang Tersisa Antar Cabang</label>
                <div class="flex-1 h-px bg-red-100"></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                ${debts.map(debt => `
                    <div class="relative ${debt.status === 'paid' ? 'bg-emerald-50 border-emerald-100' : 'bg-red-50 border-red-100'} rounded-xl p-3 ${debt.status === 'paid' ? 'opacity-90' : ''}">
                        <div class="flex justify-between items-start pt-1">
                            <div class="text-[11px] leading-relaxed">
                                <span class="font-black ${debt.status === 'paid' ? 'text-emerald-600' : 'text-red-600'}">${debt.debtor_branch_name}</span>
                                <span class="text-slate-500">berhutang kepada</span>
                                <span class="font-bold text-slate-700">${debt.creditor_branch_name}</span>
                            </div>
                            <div class="text-xs font-black ${debt.status === 'paid' ? 'text-emerald-600' : 'text-red-600'} whitespace-nowrap ml-2">Rp ${Number(debt.amount).toLocaleString('id-ID')}</div>
                        </div>
                        <div class="flex items-center justify-between mt-3 pt-2 border-t ${debt.status === 'paid' ? 'border-emerald-100/50' : 'border-red-100/50'}">
                            <span class="text-[9px] font-bold ${debt.status === 'paid' ? 'text-emerald-600' : 'text-red-400'} uppercase">
                                Status: ${debt.status === 'paid' ? 'Lunas' : 'Belum Lunas'}
                            </span>
                        </div>
                        ${debt.status === 'paid' && debt.payment_proof ? `
                            <div class="mt-3 bg-white/50 rounded-lg p-2 border border-red-100/30">
                                <label class="block text-[8px] font-bold text-slate-400 uppercase mb-1">Bukti Transfer</label>
                                <a href="/storage/${debt.payment_proof}" target="_blank" class="inline-flex items-center gap-1.5 text-emerald-600 font-bold text-[10px] hover:underline">
                                    <i data-lucide="image" class="w-3 h-3"></i> Lihat Bukti
                                </a>
                                ${debt.notes ? `<p class="text-[9px] text-slate-500 mt-0.5 italic">"${debt.notes}"</p>` : ''}
                            </div>
                        ` : ''}
                    </div>
                `).join('')}
            </div>
        </div>`;
}

function renderPengajuanFields(d) {
    let html = '';
    html += createFieldHTML('Pengaju', d.submitter?.name || '-');
    if (!d.items || d.items.length === 0) {
        html += createFieldHTML('Nama Barang/Jasa', d.customer, true);
        html += createFieldHTML('Vendor', d.vendor);
        html += createFieldHTML('Alasan Pembelian', d.purchase_reason_label);
        html += createFieldHTML('Jumlah', d.quantity);
        html += createFieldHTML('Estimasi Harga Satuan', d.estimated_price ? 'Rp ' + Number(d.estimated_price).toLocaleString('id-ID') : null);
    } else {
        html += createFieldHTML('Alasan Pembelian Utama', d.purchase_reason_label);
    }

    if ((d.status === 'completed' || d.status === 'waiting_payment') && d.invoice_file_url) {
        let sumberDanaHtml = '';
        if (d.sumber_dana_data && d.sumber_dana_data.length > 0) {
            const branchesLookup = {};
            d.branches_raw.forEach(b => branchesLookup[b.id] = b.name);

            sumberDanaHtml = `
                <div class="sm:col-span-2 mb-3">
                    <label class="block text-[9px] font-bold text-teal-600/60 uppercase mb-2">Sumber Dana Pembayaran</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                        ${d.sumber_dana_data.map(sd => `
                            <div class="bg-teal-50 border border-teal-100 rounded-lg p-2 flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-700">${branchesLookup[sd.branch_id] || 'Cabang ' + sd.branch_id}</span>
                                <span class="text-xs font-bold text-teal-600">Rp ${Number(sd.amount).toLocaleString('id-ID')}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>`;
        } else {
            sumberDanaHtml = `
                    <div class="sm:col-span-2 bg-teal-50/50 border border-teal-100 rounded-xl px-4 py-3 mb-3">
                        <label class="block text-[9px] font-bold text-teal-600/60 uppercase mb-1">Sumber Dana</label>
                        <div class="text-sm font-bold text-slate-700">${d.sumber_dana_branch_name || '-'}</div>
                    </div>
                `;
        }

        let debtsHtml = renderBranchDebtsHTML(d.branch_debts);

        html += `
                <div class="sm:col-span-2 mt-4 pt-4 border-t border-slate-100">
                    <label class="block text-[10px] font-bold text-teal-500 uppercase mb-3 tracking-widest">Detail Pembayaran Invoice</label>

                    ${sumberDanaHtml}
                    ${debtsHtml}

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                        <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                            <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Ongkir</label>
                            <div class="text-sm font-bold text-slate-700">Rp ${Number(d.ongkir || 0).toLocaleString('id-ID')}</div>
                        </div>
                        <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                            <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Diskon Pengiriman</label>
                            <div class="text-sm font-bold text-slate-700">Rp ${Number(d.diskon_pengiriman || 0).toLocaleString('id-ID')}</div>
                        </div>
                        <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                            <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Voucher Diskon</label>
                            <div class="text-sm font-bold text-slate-700">Rp ${Number(d.voucher_diskon || 0).toLocaleString('id-ID')}</div>
                        </div>
                        <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                            <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">DPP Lainnya</label>
                            <div class="text-sm font-bold text-slate-700">Rp ${Number(d.biaya_layanan_1 || 0).toLocaleString('id-ID')}</div>
                        </div>
                        <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                            <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">PPN</label>
                            <div class="text-sm font-bold text-slate-700">Rp ${Number(d.tax_amount || 0).toLocaleString('id-ID')}</div>
                        </div>
                        <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                            <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Biaya Layanan 2</label>
                            <div class="text-sm font-bold text-slate-700">Rp ${Number(d.biaya_layanan_2 || 0).toLocaleString('id-ID')}</div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">File Invoice</label>
                        <a href="${d.invoice_file_url}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-xs font-bold text-teal-600 hover:bg-teal-50 transition-colors">
                            <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Lihat Invoice
                        </a>
                    </div>
                </div>`;
    }
    return html;
}

function renderViewModal(d) {
    currentTransactionId = d.id;
    const isDebt = d.status === 'waiting_payment' && d.status_label && d.status_label.includes('Hutang');
    const isLarge = d.type === 'pengajuan' && d.effective_amount >= 1000000;

    const statusCfg = Config.status[d.status] || { label: d.status, color: 'bg-gray-50 text-gray-700 border-gray-200', icon: 'info' };
    const typeCfg = Config.types[d.type] || { label: d.type, color: 'bg-gray-50 text-gray-700 border-gray-200', icon: 'file-text' };

    const color = typeof statusCfg.color === 'function' ? statusCfg.color(d.status === 'pending' ? d.type : (d.status === 'approved' ? isLarge : isDebt)) : statusCfg.color;
    const icon = typeof statusCfg.icon === 'function' ? statusCfg.icon(d.status === 'pending' ? d.type : (d.status === 'approved' ? isLarge : isDebt)) : statusCfg.icon;

    let modalTitle = 'Detail Reimbursement';
    if (d.type === 'pengajuan') modalTitle = 'Detail Pengajuan';
    if (d.type === 'gudang') modalTitle = 'Detail Pembelian';

    document.getElementById('view-modal-title').textContent = modalTitle;
    document.getElementById('v-invoice').textContent = d.invoice_number + ' • ' + d.created_at;

    document.getElementById('v-badges').innerHTML = `
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border ${color}">
                <i data-lucide="${icon}" class="w-3.5 h-3.5"></i>
                ${d.status_label}
            </span>
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-bold border ${typeCfg.color}">
                <i data-lucide="${typeCfg.icon}" class="w-3 h-3"></i> ${d.type_label}
            </span>`;

    const imgWrap = document.getElementById('v-image-wrap');
    const vImage = document.getElementById('v-image');
    const vPdfIcon = document.getElementById('v-pdf-icon');

    if (d.image_url) {
        imgWrap.classList.remove('hidden');
        const isPdf = (d.file_path && d.file_path.toLowerCase().endsWith('.pdf')) || (d.image_url && d.image_url.toLowerCase().endsWith('.pdf'));

        if (isPdf) {
            vImage.classList.add('hidden');
            vPdfIcon.classList.remove('hidden');
            vPdfIcon.dataset.src = d.image_url;
        } else {
            vImage.classList.remove('hidden');
            vPdfIcon.classList.add('hidden');
            vImage.src = d.image_url;
        }
    } else {
        imgWrap.classList.add('hidden');
    }

    const revisBannerContainer = document.getElementById('v-revision-banner');
    if (revisBannerContainer) {
        if (d.type === 'pengajuan' && d.is_edited_by_management) {
            revisBannerContainer.innerHTML = `
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 flex flex-col gap-3">
                        <div class="flex items-start gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="git-branch" class="w-4 h-4 text-blue-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-slate-800">Direvisi oleh Management</p>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    Diedit oleh <strong class="text-blue-600">${d.editor_name || '-'}</strong>
                                    pada ${d.edited_at || '-'}
                                    &nbsp;<span class="inline-block bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-[10px] font-bold">Revisi ke-${d.revision_count}</span>
                                </p>
                            </div>
                        </div>
                        ${(d.items_snapshot && d.items_snapshot.length > 0) ? `
                        <div class="flex gap-2">
                            <button type="button" id="v-toggle-original"
                                onclick="toggleVersionInModal('original')"
                                class="flex-1 px-3 py-2 rounded-lg text-[11px] font-bold bg-white text-slate-600 border border-slate-200 shadow-sm transition-all hover:bg-slate-50">
                                <i data-lucide="user" class="w-3.5 h-3.5 inline mr-1"></i>Versi Pengaju (V1)
                            </button>
                            <button type="button" id="v-toggle-management"
                                onclick="toggleVersionInModal('management')"
                                class="flex-1 px-3 py-2 rounded-lg text-[11px] font-bold bg-blue-500 text-white shadow-sm transition-all">
                                <i data-lucide="shield-check" class="w-3.5 h-3.5 inline mr-1"></i>Versi Management (V${(d.revision_count || 0) + 1})
                            </button>
                        </div>` : ''}
                    </div>`;
            revisBannerContainer.classList.remove('hidden');

            window._modalVersionData = {
                original: d.items_snapshot || [],
                management: d.items || [],
                d: d
            };
            window._modalCurrentVersion = 'management';
        } else {
            revisBannerContainer.innerHTML = '';
            revisBannerContainer.classList.add('hidden');
            window._modalVersionData = {
                original: (d.items_snapshot && d.items_snapshot.length > 0) ? d.items_snapshot : (d.items || []),
                management: d.items || [],
                d: d
            };
            window._modalCurrentVersion = 'original';
        }
        if (typeof lucide !== 'undefined') lucide.createIcons({ root: revisBannerContainer });
    }

    const fieldsEl = document.getElementById('v-fields');
    let fieldsHtml = '';

    if (d.type === 'rembush') {
        fieldsHtml = renderRembushFields(d);
    } else if (d.type === 'gudang') {
        fieldsHtml = renderGudangFields(d);
    } else {
        fieldsHtml = renderPengajuanFields(d);
    }

    fieldsEl.innerHTML = fieldsHtml;

    const summaryWrap = document.getElementById('v-summary-wrap');
    const summaryDescWrap = document.getElementById('v-summary-desc-wrap');
    const summaryDesc = document.getElementById('v-summary-desc');
    const summaryTotalWrap = document.getElementById('v-summary-total-wrap');
    const summaryTotal = document.getElementById('v-summary-total');

    const summaryTotalLabel = document.getElementById('v-summary-total-label');
    if (d.type === 'pengajuan' || d.type === 'gudang' || d.type === 'rembush') {
        summaryWrap.classList.remove('hidden');

        if (summaryTotalLabel) {
            summaryTotalLabel.textContent = (d.type === 'rembush' || d.type === 'gudang' || d.status === 'completed' || d.status === 'paid') 
                ? 'Tagihan Pembayaran' 
                : 'Total Estimasi';
        }

        if ((d.items && d.items.length > 0) || d.type === 'rembush') {
            if (d.description && d.type !== 'rembush') {
                summaryDescWrap.classList.remove('hidden');
                summaryDescWrap.classList.add('md:col-span-2');
                summaryTotalWrap.className = 'bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-5 flex flex-col justify-center shadow-sm';
                if (d.type === 'gudang') {
                    summaryTotalWrap.className = 'bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-200 rounded-xl p-5 flex flex-col justify-center shadow-sm';
                }
                summaryDesc.textContent = d.description;
            } else {
                summaryDescWrap.classList.add('hidden');
                summaryTotalWrap.className = 'md:col-span-3 bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-5 flex flex-col justify-center shadow-sm';
                if (d.type === 'gudang') {
                    summaryTotalWrap.className = 'md:col-span-3 bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-200 rounded-xl p-5 flex flex-col justify-center shadow-sm';
                }
            }
        } else {
            summaryDescWrap.classList.add('hidden');
            summaryTotalWrap.className = 'md:col-span-3 bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-5 flex flex-col justify-center shadow-sm text-center items-center';
            if (d.type === 'gudang') {
                summaryTotalWrap.className = 'md:col-span-3 bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-200 rounded-xl p-5 flex flex-col justify-center shadow-sm text-center items-center';
            }
        }

        let summaryTotalHtml = '';
        if (d.type === 'pengajuan' && (d.dpp_lainnya > 0 || d.tax_amount > 0 || d.biaya_layanan_1 > 0)) {
            summaryTotalHtml = `
                    <div class="flex flex-col gap-1 w-full">
                        <div class="flex justify-between items-center text-[10px] text-blue-600/70 font-bold uppercase tracking-wider border-b border-blue-100 pb-1 mb-1">
                            <span>Subtotal Items</span>
                            <span>Rp ${Number((d.amount || 0) - (d.dpp_lainnya || 0) - (d.tax_amount || 0) - (d.biaya_layanan_1 || 0)).toLocaleString('id-ID')}</span>
                        </div>
                        ${d.dpp_lainnya > 0 ? `
                        <div class="flex justify-between items-center text-[10px] text-blue-500 font-bold">
                            <span>DPP Lainnya</span>
                            <span>+ Rp ${Number(d.dpp_lainnya).toLocaleString('id-ID')}</span>
                        </div>` : ''}
                        ${d.tax_amount > 0 ? `
                        <div class="flex justify-between items-center text-[10px] text-blue-500 font-bold">
                            <span>PPN</span>
                            <span>+ Rp ${Number(d.tax_amount).toLocaleString('id-ID')}</span>
                        </div>` : ''}
                        ${d.biaya_layanan_1 > 0 ? `
                        <div class="flex justify-between items-center text-[10px] text-blue-500 font-bold">
                            <span>Biaya Layanan 1</span>
                            <span>+ Rp ${Number(d.biaya_layanan_1).toLocaleString('id-ID')}</span>
                        </div>` : ''}
                        <div class="flex justify-between items-center mt-1 pt-1 border-t-2 border-blue-200">
                            <span class="text-[11px] font-black text-blue-800 uppercase">Grand Total</span>
                            <span class="text-lg md:text-xl font-black text-blue-700 tracking-tight">Rp ${Number(d.amount).toLocaleString('id-ID')}</span>
                        </div>
                    </div>
                `;
            summaryTotal.innerHTML = summaryTotalHtml;
        } else {
            summaryTotal.textContent = d.amount ? 'Rp ' + Number(d.amount).toLocaleString('id-ID') : '-';
        }
    } else {
        summaryWrap.classList.add('hidden');
    }

    const itemsWrap = document.getElementById('v-items-wrap');
    const itemsTbody = document.getElementById('v-items-tbody');
    const itemsTableCont = document.getElementById('v-items-table-container');
    const itemsDivCont = document.getElementById('v-items-div-container');

    if ((d.items && d.items.length > 0) || (d.items_snapshot && d.items_snapshot.length > 0)) {
        itemsWrap.classList.remove('hidden');

        if (d.type === 'pengajuan') {
            itemsTableCont.classList.add('hidden');
            itemsDivCont.classList.remove('hidden');

            const itemsToRender = window._modalCurrentVersion === 'management' ? d.items : (d.items_snapshot || d.items);
            itemsDivCont.innerHTML = renderTransactionItemsCards(itemsToRender, window._modalCurrentVersion, d.items_snapshot || []);

            if (typeof lucide !== 'undefined') lucide.createIcons({ root: itemsDivCont });
        } else {
            itemsDivCont.classList.add('hidden');
            itemsTableCont.classList.remove('hidden');

            let itemsHtml = d.items.map(item => `
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-3 py-2 text-slate-700 font-medium">${item.name || item.nama_barang || '-'}</td>
                        <td class="px-3 py-2 text-center">${item.qty || '-'}</td>
                        <td class="px-3 py-2">${item.unit || item.satuan || '-'}</td>
                        <td class="px-3 py-2 text-right">Rp ${Number(item.price || item.harga_satuan || 0).toLocaleString('id-ID')}</td>
                        <td class="px-3 py-2 text-right font-bold">Rp ${((Number(item.qty) || 0) * (Number(item.price || item.harga_satuan) || 0)).toLocaleString('id-ID')}</td>
                    </tr>`).join('');
            itemsTbody.innerHTML = itemsHtml;
        }
    } else {
        itemsWrap.classList.add('hidden');
    }

    const specsWrap = document.getElementById('v-specs-wrap');
    const specsEl = document.getElementById('v-specs');
    if (d.type === 'pengajuan' && d.specs && Object.values(d.specs).some(v => v) && (!d.items || d.items.length === 0)) {
        specsWrap.classList.remove('hidden');
        const specLabels = { merk: 'Merk', tipe: 'Tipe/Seri', ukuran: 'Ukuran', warna: 'Warna' };
        specsEl.innerHTML = Object.entries(specLabels).map(([key, label]) => `
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-wider">${label}</label>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">${d.specs[key] || '-'}</div>
                </div>`).join('');
    } else {
        specsWrap.classList.add('hidden');
    }

    const branchesWrap = document.getElementById('v-branches-wrap');
    const branchesEl = document.getElementById('v-branches');
    if (d.branches && d.branches.length > 0) {
        branchesWrap.classList.remove('hidden');
        branchesEl.innerHTML = d.branches.map(b => `
                <div class="flex flex-col bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">${b.name}</span>
                    <div class="flex items-baseline gap-2">
                        <span class="text-xs font-bold text-slate-700">${b.percent}%</span>
                        <span class="text-[10px] text-slate-400">(${b.amount})</span>
                    </div>
                </div>`).join('');
    } else {
        branchesWrap.classList.add('hidden');
    }

    const rejWrap = document.getElementById('v-rejection-wrap');
    if (d.status === 'rejected' && d.rejection_reason) {
        rejWrap.classList.remove('hidden');
        document.getElementById('v-rejection').textContent = d.rejection_reason;
    } else {
        rejWrap.classList.add('hidden');
    }

    const waitOwner = document.getElementById('v-waiting-owner');
    if (waitOwner) waitOwner.classList.toggle('hidden', d.status !== 'approved');

    const revWrap = document.getElementById('v-reviewer-wrap');
    if (d.reviewer) {
        revWrap.classList.remove('hidden');
        revWrap.classList.add('flex');
        document.getElementById('v-reviewer').textContent = d.reviewer.name;
        document.getElementById('v-reviewed-at').textContent = d.reviewed_at;
    } else {
        revWrap.classList.add('hidden');
        revWrap.classList.remove('flex');
    }

    const actionsWrap = document.getElementById('v-actions');
    const btnReset = document.getElementById('v-btn-reset');
    if (d.is_owner && d.status !== 'pending') {
        if (btnReset) btnReset.classList.remove('hidden');
        if (actionsWrap) actionsWrap.classList.remove('hidden');
    } else {
        if (btnReset) btnReset.classList.add('hidden');
        if (actionsWrap) actionsWrap.classList.add('hidden');
    }

    toggleVersionInModal(window._modalCurrentVersion);
}

export function toggleVersionInModal(version) {
    if (!window._modalVersionData) return;

    const items = version === 'original'
        ? window._modalVersionData.original
        : window._modalVersionData.management;

    window._modalCurrentVersion = version;

    const btnOriginal = document.getElementById('v-toggle-original');
    const btnManagement = document.getElementById('v-toggle-management');

    if (btnOriginal && btnManagement) {
        if (version === 'original') {
            btnOriginal.className = 'flex-1 px-3 py-2 rounded-lg text-[11px] font-bold bg-blue-500 text-white shadow-sm transition-all';
            btnManagement.className = 'flex-1 px-3 py-2 rounded-lg text-[11px] font-bold text-slate-600 bg-white border border-slate-200 transition-all hover:bg-slate-50';
        } else {
            btnManagement.className = 'flex-1 px-3 py-2 rounded-lg text-[11px] font-bold bg-blue-500 text-white shadow-sm transition-all';
            btnOriginal.className = 'flex-1 px-3 py-2 rounded-lg text-[11px] font-bold text-slate-600 bg-white border border-slate-200 transition-all hover:bg-slate-50';
        }
    }

    const itemsWrap = document.getElementById('v-items-wrap');
    const itemsTbody = document.getElementById('v-items-tbody');
    const itemsDivCont = document.getElementById('v-items-div-container');
    const itemsTableCont = document.getElementById('v-items-table-container');

    if (items && items.length > 0) {
        if (itemsWrap) itemsWrap.classList.remove('hidden');
        const isEdited = window._modalVersionData?.d?.is_edited_by_management;
        const label = isEdited 
            ? (version === 'original'
                ? '<span class="text-xs text-blue-600 font-bold ml-2">(Versi Pengaju)</span>'
                : '<span class="text-xs text-emerald-600 font-bold ml-2">(Versi Management)</span>')
            : '';

        const sectionLabel = itemsWrap?.querySelector('label');
        if (sectionLabel) {
            sectionLabel.innerHTML = 'Daftar Barang' + label;
        }

        if (itemsDivCont && !itemsDivCont.classList.contains('hidden')) {
            itemsDivCont.innerHTML = renderTransactionItemsCards(items, version, window._modalVersionData.original);
            if (typeof lucide !== 'undefined') lucide.createIcons({ root: itemsDivCont });
        } else if (itemsTbody) {
            itemsTbody.innerHTML = items.map((item, idx) => {
                const itemName = escapeHtml(item.customer || item.name || item.nama_barang || '-');

                return `
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-3 py-2 text-slate-700 font-medium">
                            <div class="flex items-center">${itemName}</div>
                        </td>
                        <td class="px-3 py-2 text-center">${item.quantity || item.qty || '-'}</td>
                        <td class="px-3 py-2">${item.unit || item.satuan || '-'}</td>
                        <td class="px-3 py-2 text-right">Rp ${Number(item.estimated_price || item.price || item.harga_satuan || 0).toLocaleString('id-ID')}</td>
                        <td class="px-3 py-2 text-right font-bold">Rp ${((Number(item.quantity || item.qty) || 0) * (Number(item.estimated_price || item.price || item.harga_satuan) || 0)).toLocaleString('id-ID')}</td>
                    </tr>`;
            }).join('');
            if (typeof lucide !== 'undefined') lucide.createIcons({ root: itemsTbody });
        }
    } else {
        if (itemsWrap) itemsWrap.classList.add('hidden');
    }

    const payHistWrap = document.getElementById('v-payment-history-wrap');

    if (window._modalVersionData?.d?.is_paid && (window._modalVersionData?.d?.type === 'rembush' || window._modalVersionData?.d?.type === 'pengajuan')) {
        const hist = window._modalVersionData.d;
        if (payHistWrap) payHistWrap.classList.remove('hidden');

        const step1Title = document.getElementById('v-pay-step1-title');
        if (step1Title) {
            if (hist.payment_type === 'Transfer') {
                step1Title.textContent = 'BUKTI TRANSFER DIUNGGAH';
            } else if (hist.payment_type === 'Tunai') {
                step1Title.textContent = 'PEMBAYARAN TUNAI DISERAHKAN';
            } else {
                step1Title.textContent = 'PEMBAYARAN DIPROSES';
            }
        }

        const step1At = document.getElementById('v-pay-step1-at');
        if (step1At) step1At.textContent = hist.payment_at || '-';
        const step1By = document.getElementById('v-pay-step1-by');
        if (step1By) step1By.textContent = hist.paid_by_name || 'System';
        const step1Role = document.getElementById('v-pay-step1-role');
        if (step1Role) step1Role.textContent = hist.paid_by_role || 'Admin';

        const actionWrap1 = document.getElementById('v-pay-step1-action-wrap');
        if (actionWrap1) {
            if (hist.payment_type === 'Transfer' && hist.payment_proof_url) {
                actionWrap1.innerHTML = `
                        <button type="button" onclick="openImageViewer('${hist.payment_proof_url}', 'Bukti Pembayaran')" 
                            class="flex items-center gap-1.5 text-[10px] font-black text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-100 hover:bg-emerald-100 transition-all uppercase tracking-widest">
                            <i data-lucide="image" class="w-3.5 h-3.5"></i>
                            LIHAT BUKTI
                        </button>
                    `;
            } else if (hist.payment_type === 'Tunai' && hist.payment_proof_url) {
                actionWrap1.innerHTML = `
                        <button type="button" onclick="openImageViewer('${hist.payment_proof_url}', 'Bukti Penyerahan')" 
                            class="flex items-center gap-1.5 text-[10px] font-black text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-100 hover:bg-emerald-100 transition-all uppercase tracking-widest">
                            <i data-lucide="image" class="w-3.5 h-3.5"></i>
                            LIHAT BUKTI
                        </button>
                    `;
            } else if (hist.payment_type === 'Tunai') {
                actionWrap1.innerHTML = `
                        <div class="flex items-center gap-1.5 text-[10px] font-black text-slate-400 bg-white px-3 py-1.5 rounded-xl border border-slate-100 uppercase tracking-widest">
                            <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-slate-400"></i>
                            Selesai
                        </div>
                    `;
            } else {
                actionWrap1.innerHTML = '';
            }
        }

        const step2Wrap = document.getElementById('v-pay-step2-wrap');
        if (step2Wrap) {
            if (hist.status === 'completed' || hist.konfirmasi_at) {
                step2Wrap.classList.remove('hidden');
                document.getElementById('v-pay-step2-at').textContent = hist.konfirmasi_at || hist.payment_at || '-';
                document.getElementById('v-pay-step2-by').textContent = hist.konfirmasi_by_name || hist.recipient_name || '-';
                document.getElementById('v-pay-step2-role').textContent = hist.konfirmasi_by_role || hist.recipient_role || 'Teknisi';
            } else {
                step2Wrap.classList.add('hidden');
            }
        }

        const summaryMethod = document.getElementById('v-pay-summary-method');
        const summaryAccount = document.getElementById('v-pay-summary-account');
        const methodWrap = document.getElementById('v-pay-method-wrap');

        if (hist.payment_method && methodWrap) {
            methodWrap.classList.remove('hidden');

            let methodLabel = hist.payment_method_label || hist.payment_method;
            let accountInfo = '';

            if (hist.payment_method === 'transfer_teknisi') {
                methodLabel = 'Transfer ke Teknisi';
                if (hist.submitter) {
                    const bank = hist.submitter.rekening_bank || '-';
                    const name = hist.submitter.rekening_nama || '-';
                    const number = hist.submitter.rekening_nomor || '-';
                    accountInfo = `${bank} • ${name}<br>${number}`;
                }
            } else if (hist.payment_method === 'transfer_penjual') {
                methodLabel = 'Transfer ke Penjual';
                if (hist.specs) {
                    const bank = hist.specs.bank_name || '-';
                    const name = hist.specs.account_name || '-';
                    const number = hist.specs.account_number || '-';
                    accountInfo = `${bank} • ${name}<br>${number}`;
                }
            } else if (hist.payment_method === 'cash') {
                methodLabel = 'Tunai (Cash)';
                if (hist.type === 'pengajuan') accountInfo = 'Dibayarkan Tunai';
            } else if (hist.payment_method === 'transfer' && hist.type === 'pengajuan') {
                methodLabel = 'Rekening (Transfer)';
                accountInfo = 'Dibayarkan via Transfer';
            }

            if (summaryMethod) summaryMethod.innerHTML = methodLabel;
            if (summaryAccount) summaryAccount.innerHTML = accountInfo || (hist.payment_type || '-');
        } else if (methodWrap) {
            methodWrap.classList.add('hidden');
        }

        const summaryAmount = document.getElementById('v-pay-summary-amount');
        const summaryDiscrepancy = document.getElementById('v-pay-summary-discrepancy');

        const finalAmount = hist.actual_total || hist.amount || 0;
        if (summaryAmount) summaryAmount.textContent = 'Rp ' + Number(finalAmount).toLocaleString('id-ID');

        if (summaryDiscrepancy) {
            const selisih = Number(hist.selisih || 0);
            if (selisih !== 0) {
                summaryDiscrepancy.classList.remove('hidden');
                const absSelisih = Math.abs(selisih).toLocaleString('id-ID');
                if (selisih > 0) {
                    summaryDiscrepancy.innerHTML = `<i data-lucide="trending-down" class="w-2.5 h-2.5 inline mr-1"></i>Hemat Rp ${absSelisih}`;
                    summaryDiscrepancy.className = "text-[10px] font-bold mt-1 px-2 py-0.5 rounded-lg uppercase tracking-wider bg-emerald-500/20 text-emerald-400 border border-emerald-500/30";
                } else {
                    summaryDiscrepancy.innerHTML = `<i data-lucide="trending-up" class="w-2.5 h-2.5 inline mr-1"></i>Lebih Rp ${absSelisih}`;
                    summaryDiscrepancy.className = "text-[10px] font-bold mt-1 px-2 py-0.5 rounded-lg uppercase tracking-wider bg-rose-500/20 text-rose-400 border border-rose-500/30";
                }
            } else {
                summaryDiscrepancy.classList.add('hidden');
            }
        }

        if (typeof lucide !== 'undefined') lucide.createIcons({ root: payHistWrap });
    } else {
        if (payHistWrap) payHistWrap.classList.add('hidden');
    }
}

export function renderTransactionItemsCards(items, version, originalItems = []) {
    const purchaseReasons = {
        'rusak': 'Barang Rusak/Usang',
        'hilang': 'Barang Hilang',
        'upgrade': 'Upgrade/Peningkatan',
        'kebutuhan_baru': 'Kebutuhan Baru',
        'proyek': 'Keperluan Proyek',
        'lainnya': 'Lainnya'
    };

    return items.map((item, idx) => {
        const price = Number(item.estimated_price || 0);
        const qty = Number(item.quantity || 0);
        const total = price * qty;

        const originalItem = (version === 'management') ? (originalItems[idx] || null) : null;
        const isNew = version === 'management' && originalItems.length > 0 && idx >= originalItems.length;

        const isFieldChanged = (fieldName) => {
            if (version !== 'management' || isNew || !originalItem) return false;
            if (fieldName === 'specs') {
                return JSON.stringify(item.specs) !== JSON.stringify(originalItem.specs);
            }
            return item[fieldName] != originalItem[fieldName];
        };

        const hasAnyChange = version === 'management' && originalItem && JSON.stringify(item) !== JSON.stringify(originalItem);

        const cardClass = isNew
            ? 'border-emerald-200 bg-emerald-50/30'
            : (hasAnyChange ? 'border-orange-200 bg-orange-50/30' : 'border-slate-200 bg-white');

        const fieldClass = (f) => isFieldChanged(f)
            ? 'bg-orange-100/50 border-orange-300 ring-1 ring-orange-200'
            : 'bg-white border-slate-200';

        const specsHTML = item.specs ? Object.entries({ merk: 'Merk', tipe: 'Tipe/Seri', ukuran: 'Ukuran', warna: 'Warna' }).map(([key, label]) => {
            return item.specs[key] ? `
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">${label}</label>
                        <div class="${fieldClass('specs')} border rounded-lg px-3 py-2 text-xs font-medium text-slate-800">${item.specs[key]}</div>
                    </div>
                ` : '';
        }).join('') : '';

        return `
                <div class="border ${cardClass} rounded-2xl overflow-hidden shadow-sm mb-4 last:mb-0 transition-all duration-300">
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-100/50 transition-colors" onclick="const body = this.nextElementSibling; body.classList.toggle('hidden'); this.querySelector('.icon-collapse').classList.toggle('rotate-180')">
                        <div class="flex items-center gap-3 w-full max-w-[70%]">
                            <div class="w-7 h-7 shrink-0 rounded-full ${isNew ? 'bg-emerald-100 text-emerald-600' : (hasAnyChange ? 'bg-orange-100 text-orange-600' : 'bg-slate-200 text-slate-500')} flex items-center justify-center font-bold text-xs transition-colors">${idx + 1}</div>
                            <div class="min-w-0">
                                <h4 class="font-bold text-slate-700 text-sm flex items-center flex-wrap gap-2 truncate">
                                    <span class="truncate">${escapeHtml(item.customer || '-')}</span>
                                    ${isNew ? '<span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700 text-[9px] uppercase tracking-tighter font-black animate-pulse whitespace-nowrap">Baru</span>' : ''}
                                    ${hasAnyChange ? '<span class="px-1.5 py-0.5 rounded bg-orange-100 text-orange-700 text-[9px] uppercase tracking-tighter font-black whitespace-nowrap">Diedit</span>' : ''}
                                    ${(window._modalVersionData?.d?.can_manage || window._modalVersionData?.d?.is_owner) && escapeHtml(item.customer || '-') !== '-' ? `
                                        <button type="button" onclick="event.stopPropagation(); setAsReference('${window._modalVersionData.d.id}', '${escapeHtml(item.customer)}')" class="inline-flex items-center gap-1 px-2 py-1 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg text-[9px] font-bold border border-blue-200 transition-colors whitespace-nowrap">
                                            <i data-lucide="bookmark-plus" class="w-3 h-3"></i> Referensi
                                        </button>
                                    ` : ''}
                                </h4>
                                <p class="text-[10px] text-slate-400">Rp ${price.toLocaleString('id-ID')} x ${qty}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="font-bold text-emerald-600 text-sm hidden sm:inline mr-2">Rp ${total.toLocaleString('id-ID')}</span>
                            <i data-lucide="chevron-down" class="w-5 h-5 text-slate-400 transition-transform duration-200 icon-collapse ${idx !== 0 ? 'rotate-180' : ''}"></i>
                        </div>
                    </div>

                    <div class="p-4 sm:p-5 space-y-5 ${idx !== 0 ? 'hidden' : ''}">
                        <div>
                            <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Informasi Barang / Jasa</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div class="md:col-span-2">
                                    <span class="block text-[10px] text-slate-400">Vendor</span>
                                    <div class="${fieldClass('vendor')} border rounded-lg px-3 py-2 text-xs font-medium text-slate-800">${item.vendor || '-'}</div>
                                </div>
                                <div class="md:col-span-2">
                                    <span class="block text-[10px] text-slate-400">Link Rekomendasi</span>
                                    <div class="${fieldClass('link')} border rounded-lg px-3 py-2 text-xs">
                                        ${item.link ? `<a href="${item.link}" target="_blank" class="font-medium text-blue-600 hover:underline break-all">${item.link}</a>` : `<span class="font-medium text-slate-800">-</span>`}
                                    </div>
                                </div>
                            </div>
                        </div>

                        ${specsHTML ? `
                        <div>
                            <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Spesifikasi Barang</label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">${specsHTML}</div>
                        </div>` : ''}

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Alasan Pembelian</label>
                                <div class="${fieldClass('category')} border rounded-lg px-3 py-2 text-xs font-medium text-slate-800">
                                    ${item.category || purchaseReasons[item.purchase_reason] || item.purchase_reason || '-'}
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Keterangan</label>
                                <div class="${fieldClass('description')} border rounded-lg px-3 py-2 text-xs font-medium text-slate-800 text-wrap whitespace-pre-wrap">${item.description || '-'}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                             <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Harga Satuan</label>
                                <div class="${fieldClass('estimated_price')} border rounded-lg px-3 py-2 text-xs font-bold text-slate-800">Rp ${price.toLocaleString('id-ID')}</div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Jumlah</label>
                                <div class="${fieldClass('quantity')} border rounded-lg px-3 py-2 text-xs font-bold text-slate-800">${qty}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
    }).join('');
}
