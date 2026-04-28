import { Config, userRole, canManage, isOwner, isAdmin } from './config.js';

// Helper: Branch Tags Rendering
export function renderBranchTags(branches, maxVisible = 2) {
    if (!branches || branches.length === 0) return '';
    const icon = '<i data-lucide="git-branch" class="w-2.5 h-2.5 mr-0.5"></i>';
    const visibleTags = branches.slice(0, maxVisible).map(b =>
        `<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-600 border border-slate-200">${icon} ${escapeHtml(b)}</span>`
    ).join('');

    if (branches.length <= maxVisible) return visibleTags;

    const remaining = branches.length - maxVisible;
    const hiddenItems = branches.slice(maxVisible).map(b =>
        `<div class="flex items-center gap-1.5 py-0.5">
                <span class="w-1 h-1 rounded-full bg-blue-400"></span>
                <span>${escapeHtml(b)}</span>
            </div>`
    ).join('');

    return visibleTags + `
            <div class="relative inline-block group/branch ml-0.5">
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-50 text-blue-600 border border-blue-200 cursor-help hover:bg-blue-100 transition-colors">
                    +${remaining}
                </span>
                <!-- Tooltip Premium -->
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 opacity-0 invisible group-hover/branch:opacity-100 group-hover/branch:visible transition-all duration-200 z-[100] pointer-events-none">
                    <div class="bg-slate-900 text-white p-2.5 rounded-xl shadow-2xl border border-white/10 backdrop-blur-md">
                        <div class="flex items-center gap-1.5 mb-1.5 pb-1.5 border-b border-white/10">
                            <i data-lucide="git-branch" class="w-3 h-3 text-blue-400"></i>
                            <span class="text-[10px] font-bold text-gray-400 tracking-wider">CABANG LAINNYA</span>
                        </div>
                        <div class="text-[11px] font-semibold text-gray-200 max-h-48 overflow-y-auto custom-scrollbar">
                            ${hiddenItems}
                        </div>
                    </div>
                    <!-- Arrow -->
                    <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-900"></div>
                </div>
            </div>`;
}

// Helper: Escape HTML
export function escapeHtml(text) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

export function generateAIBadge(t) {
    if (t.type !== 'rembush' || !['queued', 'pending', 'processing', 'completed', 'error'].includes(t.ai_status)) return '';
    
    const aiBadge = Config.ai[t.ai_status];
    if (!aiBadge) return '';

    return `
            <span class="ai-status-badge inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[9px] font-bold border ml-1 ${aiBadge.color} ${aiBadge.pulse ? 'animate-pulse' : ''}">
                <i data-lucide="${aiBadge.icon}" class="w-2.5 h-2.5 ${aiBadge.pulse ? 'animate-spin' : ''}"></i>
                ${aiBadge.label}
            </span>`;
}

export function buildEditButton(t, style = 'desktop') {
    const label = 'Edit';
    const icon = 'pencil';
    if (style === 'desktop') {
        return `<a href="/transactions/${t.id}/edit" title="${label}"
                class="p-2 rounded-lg text-slate-400 hover:text-amber-600 hover:bg-amber-50 active:scale-95 transition-all outline-none">
                <i data-lucide="${icon}" class="w-4 h-4"></i>
            </a>`;
    } else {
        return `<a href="/transactions/${t.id}/edit"
                class="flex items-center gap-1 px-2.5 py-1.5 bg-white border border-slate-200 text-slate-500 rounded-lg hover:text-amber-600 hover:border-amber-300 active:scale-95 transition-all text-[11px] font-semibold outline-none">
                <i data-lucide="${icon}" class="w-3 h-3"></i> ${label}
            </a>`;
    }
}

export function generateInlineActions(t) {
    if (!canManage) return '';

    let html = '';
    const isPengajuan = t.type === 'pengajuan';
    const canApprovePengajuan = isOwner || userRole === 'atasan';

    if (t.status === 'pending') {
        // For Pengajuan, only Atasan/Owner can approve
        if (isPengajuan && !canApprovePengajuan) {
            return '';
        }

        const approveTitle = (!isPengajuan && t.effective_amount >= 1000000) ? 'Setujui (Menunggu Owner)' : 'Setujui';
        html = `
                <div class="flex items-center gap-1 ml-1">
                    <button type="button" onclick="performStatusAction(${t.id}, 'approved', this)" title="${approveTitle}"
                        class="p-1.5 rounded-lg bg-green-50 text-green-600 hover:bg-green-600 hover:text-white border border-green-200 hover:border-green-600 active:scale-90 transition-all outline-none">
                        <i data-lucide="check" class="w-3 h-3"></i>
                    </button>
                    <button type="button" onclick="openRejectModal(${t.id}, '${t.invoice_number}')" title="Tolak"
                        class="p-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white border border-red-200 hover:border-red-600 active:scale-90 transition-all outline-none">
                        <i data-lucide="x" class="w-3 h-3"></i>
                    </button>
                </div>
            `;
    } else if (isOwner && t.status === 'approved') {
        const approveTitle = 'Approve Final';
        html = `
                <div class="flex items-center gap-1 ml-1">
                    <button type="button" onclick="performStatusAction(${t.id}, 'approved', this)" title="${approveTitle}"
                        class="p-1.5 rounded-lg bg-green-50 text-green-600 hover:bg-green-600 hover:text-white border border-green-200 hover:border-green-600 active:scale-90 transition-all outline-none">
                        <i data-lucide="check" class="w-3 h-3"></i>
                    </button>
                    <button type="button" onclick="openRejectModal(${t.id}, '${t.invoice_number}')" title="Tolak"
                        class="p-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white border border-red-200 hover:border-red-600 active:scale-90 transition-all outline-none">
                        <i data-lucide="x" class="w-3 h-3"></i>
                    </button>
                </div>
            `;
    }

    // New Action Buttons
    if (t.status === 'auto-reject' && (canManage || isOwner)) {
        html += `
                <div class="flex items-center gap-1 ml-1">
                    <button type="button" onclick="openOverrideModal(${t.id}, '${t.invoice_number}')" title="Request Override"
                        class="p-1.5 rounded-lg bg-orange-50 text-orange-600 hover:bg-orange-600 hover:text-white border border-orange-200 hover:border-orange-600 active:scale-90 transition-all outline-none">
                        <i data-lucide="alert-circle" class="w-3 h-3"></i>
                    </button>
                </div>
            `;
    } else if (t.status === 'waiting_payment' && canManage) {
        // ✅ Only show Upload button if NO payment proof exists yet
        // AND there are no pending inter-branch debts
        const hasPaymentProof = !!(t.invoice_file_path || t.bukti_transfer || t.foto_penyerahan);
        const isDebtPending = t.status_label === 'Menunggu Pelunasan';

        if (!hasPaymentProof && !isDebtPending) {
            html += `
                    <div class="flex items-center gap-1 ml-1">
                        <button type="button" onclick="openPaymentModal(${t.id})" title="Proses Pembayaran"
                            class="p-1.5 rounded-lg bg-cyan-50 text-cyan-600 hover:bg-cyan-600 hover:text-white border border-cyan-200 hover:border-cyan-600 active:scale-90 transition-all outline-none">
                            <i data-lucide="upload-cloud" class="w-3 h-3"></i>
                        </button>
                    </div>
                `;
        }
    } else if (t.status === 'flagged' && (canManage || isOwner)) {
        html += `
                <div class="flex items-center gap-1 ml-1">
                    <button type="button" onclick="openForceApproveModal(${t.id}, '${t.invoice_number}')" title="Force Approve"
                        class="p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white border border-rose-200 hover:border-rose-600 active:scale-90 transition-all outline-none">
                        <i data-lucide="shield-alert" class="w-3 h-3"></i>
                    </button>
                </div>
            `;
    } else if (t.status === 'Menunggu Konfirmasi Teknisi' && userRole === 'teknisi') {
        html += `
                <div class="flex items-center gap-1 ml-1">
                    <button type="button" onclick="confirmCashPayment(${t.id}, 'terima')" title="Terima Uang"
                        class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white border border-emerald-200 hover:border-emerald-600 active:scale-90 transition-all outline-none">
                        <i data-lucide="check-circle" class="w-3 h-3"></i>
                    </button>
                    <button type="button" onclick="confirmCashPayment(${t.id}, 'tolak')" title="Tolak / Terdapat Kendala"
                        class="p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white border border-rose-200 hover:border-rose-600 active:scale-90 transition-all outline-none">
                        <i data-lucide="x-circle" class="w-3 h-3"></i>
                    </button>
                </div>
            `;
    }

    return html;
}

export function generateMobileActions(t) {
    if (!canManage) return '';

    let showActions = false;
    let approveTitle = 'Setujui';
    const isPengajuan = t.type === 'pengajuan';
    const canApprovePengajuan = isOwner || userRole === 'atasan';

    if (t.status === 'pending') {
        if (isPengajuan) {
            if (canApprovePengajuan) {
                showActions = true;
                approveTitle = 'Setujui';
            }
        } else {
            showActions = true;
            approveTitle = t.effective_amount >= 1000000 ? 'Setujui (Menunggu Owner)' : 'Setujui';
        }
    } else if (isOwner && t.status === 'approved') {
        showActions = true;
        approveTitle = 'Approve Final';
    }

    let extraActionHtml = '';
    if (t.status === 'auto-reject' && (canManage || isOwner)) {
        extraActionHtml = `
                <button type="button" onclick="openOverrideModal(${t.id}, '${t.invoice_number}')" title="Override"
                    class="flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg bg-orange-50 text-orange-700 hover:bg-orange-600 hover:text-white font-bold text-[11px] active:scale-95 transition-all border border-orange-200 hover:border-orange-600 outline-none">
                    <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i> <span class="hidden sm:inline">Override</span>
                </button>
            `;
    } else if (t.status === 'waiting_payment' && canManage) {
        // ✅ Only show Upload button if NO payment proof exists yet
        // AND there are no pending inter-branch debts
        const hasPaymentProof = !!(t.invoice_file_path || t.bukti_transfer || t.foto_penyerahan);
        const isDebtPending = t.status_label === 'Menunggu Pelunasan';

        if (!hasPaymentProof && !isDebtPending) {
            extraActionHtml = `
                    <button type="button" onclick="openPaymentModal(${t.id})" title="Upload Bukti"
                        class="flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg bg-cyan-50 text-cyan-700 hover:bg-cyan-600 hover:text-white font-bold text-[11px] active:scale-95 transition-all border border-cyan-200 hover:border-cyan-600 outline-none">
                        <i data-lucide="upload-cloud" class="w-3.5 h-3.5"></i> <span class="hidden sm:inline">Upload Bukti</span>
                    </button>
                `;
        }
    } else if (t.status === 'flagged' && (canManage || isOwner)) {
        extraActionHtml = `
                <button type="button" onclick="openForceApproveModal(${t.id}, '${t.invoice_number}')" title="Force Approve"
                    class="flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-600 hover:text-white font-bold text-[11px] active:scale-95 transition-all border border-rose-200 hover:border-rose-600 outline-none">
                    <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i> <span class="hidden sm:inline">Force Approve</span>
                </button>
            `;
    } else if (t.status === 'Menunggu Konfirmasi Teknisi' && userRole === 'teknisi') {
        extraActionHtml = `
                <button type="button" onclick="confirmCashPayment(${t.id}, 'terima')" title="Terima"
                    class="flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white font-bold text-[11px] active:scale-95 transition-all border border-emerald-200 hover:border-emerald-600 outline-none">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                </button>
                <button type="button" onclick="confirmCashPayment(${t.id}, 'tolak')" title="Tolak"
                    class="flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-600 hover:text-white font-bold text-[11px] active:scale-95 transition-all border border-rose-200 hover:border-rose-600 outline-none">
                    <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                </button>
            `;
    }

    if (!showActions && !extraActionHtml) return '';

    if (extraActionHtml) {
        return extraActionHtml;
    }

    return `
            <button type="button" onclick="performStatusAction(${t.id}, 'approved', this)" title="${approveTitle}"
                class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white font-semibold text-[11px] active:scale-95 transition-all border border-emerald-200 hover:border-emerald-600 outline-none">
                <i data-lucide="check" class="w-3.5 h-3.5"></i> <span class="hidden sm:inline">${approveTitle}</span>
            </button>
            <button type="button" onclick="openRejectModal(${t.id}, '${t.invoice_number}')" title="Tolak"
                class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-600 hover:text-white font-semibold text-[11px] active:scale-95 transition-all border border-rose-200 hover:border-rose-600 outline-none">
                <i data-lucide="x" class="w-3.5 h-3.5"></i> <span class="hidden sm:inline">Tolak</span>
            </button>
        `;
}

export function generateDesktopRow(t, rowNum = '') {
    const isDebt = t.status === 'waiting_payment' && t.status_label === 'Menunggu Pelunasan';
    const isLarge = t.type === 'pengajuan' && t.effective_amount >= 1000000;
    
    const statusCfg = Config.status[t.status] || { label: t.status, color: 'bg-gray-50 text-gray-700 border-gray-200', icon: 'info' };
    const typeCfg = Config.types[t.type] || { label: t.type, color: 'bg-gray-50 text-gray-700 border-gray-200', icon: 'file-text' };

    const label = typeof statusCfg.label === 'function' ? statusCfg.label(t.status === 'pending' ? t.type : (t.status === 'approved' ? isLarge : isDebt)) : statusCfg.label;
    const color = typeof statusCfg.color === 'function' ? statusCfg.color(t.status === 'pending' ? t.type : (t.status === 'approved' ? isLarge : isDebt)) : statusCfg.color;
    const icon = typeof statusCfg.icon === 'function' ? statusCfg.icon(t.status === 'pending' ? t.type : (t.status === 'approved' ? isLarge : isDebt)) : statusCfg.icon;

    const aiBadgeHtml = generateAIBadge(t);
    const inlineActionsHtml = generateInlineActions(t);

    return `
            <tr class="hover:bg-blue-50/30 transition-all duration-200 group">
                <td class="px-5 py-4 text-center hidden xl:table-cell"><span class="text-xs font-bold text-slate-400">${rowNum}</span></td>
                <td class="px-5 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center text-xs font-bold text-slate-500 shrink-0">
                            ${(t.submitter_name ? t.submitter_name.charAt(0) : '?').toUpperCase()}
                        </div>
                        <div>
                            <div class="font-bold text-gray-900">${t.submitter_name || '-'}</div>
                            <div class="text-[11px] text-gray-400 font-medium">${t.invoice_number}</div>
                            ${t.branches && t.branches.length > 0 ? `<div class="flex items-center gap-1 mt-1 flex-wrap">${renderBranchTags(t.branches, 2)}</div>` : ''}
                        </div>
                    </div>
                </td>
                <td class="px-5 py-4 whitespace-nowrap">
                    <div class="flex flex-col items-start gap-1">
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-bold border ${typeCfg.color}">
                            <i data-lucide="${typeCfg.icon}" class="w-3 h-3"></i> ${typeCfg.label}
                        </span>

                        ${t.has_price_anomaly
            ? `<span class="flex items-center gap-1 text-[10px] font-medium text-red-500" title="Anomaly">
                                <i data-lucide="alert-triangle" class="w-3 h-3"></i> Anomali Harga
                            </span>`
            : ''
        }
                    </div>
                </td>
                <td class="px-5 py-4 text-gray-700 font-medium text-xs whitespace-nowrap hidden lg:table-cell">${t.category_label}</td>
                <td class="px-5 py-4 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border ${color}">
                            <i data-lucide="${icon}" class="w-3 h-3"></i>
                            ${label}
                        </span>
                        ${aiBadgeHtml}
                        ${inlineActionsHtml}
                    </div>
                </td>
                <td class="px-5 py-4 text-gray-500 font-medium text-xs whitespace-nowrap hidden xl:table-cell">${t.created_at}</td>
                <td class="px-5 py-4 font-bold text-gray-900 whitespace-nowrap">Rp ${t.formatted_amount}</td>
                <td class="px-5 py-4 whitespace-nowrap">
                    <div class="flex items-center justify-center gap-1 opacity-80 group-hover:opacity-100 transition-opacity">
                        <button type="button" onclick="openViewModal(${t.id})" title="Lihat Detail"
                            class="p-2 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 active:scale-95 transition-all outline-none">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                        ${canManage ? `
                            ${buildEditButton(t, 'desktop')}
                            ${(!isAdmin) ? `
                                <button type="button" onclick="confirmDeleteTransaction(${t.id}, '${t.invoice_number}')" title="Hapus"
                                        class="p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 active:scale-95 transition-all outline-none">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                            ` : ''}
                        ` : ''}
                    </div>
                </td>
            </tr>`;
}

export function generateMobileCard(t, rowNum = '') {
    const isDebt = t.status === 'waiting_payment' && t.status_label === 'Menunggu Pelunasan';
    const isLarge = t.type === 'pengajuan' && t.effective_amount >= 1000000;
    
    const statusCfg = Config.status[t.status] || { label: t.status, color: 'bg-gray-50 text-gray-700 border-gray-200', icon: 'info' };
    const typeCfg = Config.types[t.type] || { label: t.type, color: 'bg-gray-50 text-gray-700 border-gray-200', icon: 'file-text' };

    const label = typeof statusCfg.label === 'function' ? statusCfg.label(t.status === 'pending' ? t.type : (t.status === 'approved' ? isLarge : isDebt)) : statusCfg.label;
    const color = typeof statusCfg.color === 'function' ? statusCfg.color(t.status === 'pending' ? t.type : (t.status === 'approved' ? isLarge : isDebt)) : statusCfg.color;
    const icon = typeof statusCfg.icon === 'function' ? statusCfg.icon(t.status === 'pending' ? t.type : (t.status === 'approved' ? isLarge : isDebt)) : statusCfg.icon;

    const aiBadgeHtml = generateAIBadge(t);
    const mobileActionsHtml = generateMobileActions(t);

    return `
            <div class="tx-card px-3 sm:px-4 py-3 sm:py-3.5 border-b border-gray-100">
                <div class="flex items-start gap-2.5 mb-2">
                    <div class="relative shrink-0 mt-0.5">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-slate-200 to-slate-300 flex items-center justify-center text-xs font-bold text-slate-600">
                            ${(t.submitter_name ? t.submitter_name.charAt(0) : '?').toUpperCase()}
                        </div>
                        ${rowNum ? `<span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 flex items-center justify-center text-[7px] font-bold bg-slate-500 text-white rounded-full">${rowNum}</span>` : ''}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h5 class="font-bold text-slate-800 text-[13px] leading-snug truncate">${t.submitter_name || '-'}</h5>
                                <p class="text-[10px] font-medium text-slate-400 truncate">${t.invoice_number}</p>
                            </div>
                            <div class="flex flex-col items-end gap-0.5 shrink-0">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold tracking-wide border ${color}">
                                    <i data-lucide="${icon}" class="w-2 h-2"></i>
                                    ${label}
                                </span>
                                ${aiBadgeHtml}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-1.5 flex-wrap text-[10px] text-slate-400 mb-3 pl-10 sm:pl-11">
                    <div class="flex items-center gap-1.5 flex-wrap flex-1">
                        <span class="inline-flex items-center gap-0.5 text-[9px] font-bold px-1.5 py-0.5 rounded-md border ${typeCfg.color}">
                            <i data-lucide="${typeCfg.icon}" class="w-2.5 h-2.5"></i> ${typeCfg.label}
                        </span>
                        <span class="text-slate-300">/</span>
                        <span class="font-bold text-slate-500">${t.category_label}</span>
                    </div>
                    <span class="font-medium text-slate-400 whitespace-nowrap">${t.created_at}</span>
                </div>

                <div class="flex items-center justify-between gap-2 pl-10 sm:pl-11 mb-2.5">
                    <p class="font-black text-slate-800 text-[15px] sm:text-base tracking-tight truncate">Rp ${t.formatted_amount}</p>
                    ${mobileActionsHtml ? `<div class="flex items-center gap-1.5 shrink-0">${mobileActionsHtml}</div>` : ''}
                </div>

                <div class="flex items-center gap-1.5 flex-wrap pl-10 sm:pl-11">
                    <button type="button" onclick="openViewModal(${t.id})"
                        class="flex items-center gap-1 px-2.5 py-1.5 bg-white border border-slate-200 text-slate-500 rounded-lg hover:text-blue-600 hover:border-blue-300 active:scale-95 transition-all text-[11px] font-semibold outline-none">
                        <i data-lucide="eye" class="w-3 h-3"></i> Lihat
                    </button>
                    ${canManage ? `
                        ${buildEditButton(t, 'mobile')}
                        ${(!isAdmin) ? `
                            <button type="button" onclick="confirmDeleteTransaction(${t.id}, '${t.invoice_number}')"
                                    class="flex items-center gap-1 px-2 py-1.5 bg-white border border-slate-200 text-slate-400 rounded-lg hover:text-red-500 hover:border-red-300 active:scale-95 transition-all text-[11px] font-semibold outline-none">
                                    <i data-lucide="trash-2" class="w-3 h-3"></i>
                            </button>
                        ` : ''}
                    ` : ''}
                </div>
            </div>`;
}

export function renderDesktopTable(data, startIndex = 0) {
    const tbody = document.getElementById('desktop-tbody');
    const noResults = document.getElementById('table-no-results');

    if (!tbody) return;

    if (data.length === 0) {
        tbody.innerHTML = '';
        noResults?.classList.remove('hidden');
        const query = document.getElementById('instant-search')?.value || '';
        const elQuery = document.getElementById('no-result-query');
        if (elQuery) elQuery.textContent = query;
    } else {
        noResults?.classList.add('hidden');
        tbody.innerHTML = data.map((t, i) => generateDesktopRow(t, startIndex + i + 1)).join('');

        // ✅ Update Lucide Icons for dynamic content
        if (typeof lucide !== 'undefined') lucide.createIcons({ root: tbody });
    }
}

export function renderMobileCards(data, startIndex = 0) {
    const container = document.getElementById('mobile-container');
    const noResults = document.getElementById('mobile-no-results');

    if (!container) return;

    if (data.length === 0) {
        container.innerHTML = '';
        noResults?.classList.remove('hidden');
        const query = document.getElementById('instant-search')?.value || '';
        const elQuery = document.getElementById('mobile-no-result-query');
        if (elQuery) elQuery.textContent = query;
    } else {
        noResults?.classList.add('hidden');
        container.innerHTML = data.map((t, i) => generateMobileCard(t, startIndex + i + 1)).join('');

        // ✅ Update Lucide Icons for dynamic content
        if (typeof lucide !== 'undefined') lucide.createIcons({ root: container });
    }
}

// Ensure html functions are globally available if referenced inside the HTML string
window.renderBranchTags = renderBranchTags;
window.generateDesktopRow = generateDesktopRow;
window.generateMobileCard = generateMobileCard;
