@extends('layouts.app')

@section('page-title', '')
@php
    $hideHeader = true;
@endphp
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ─── Palette ────────────────────────────────────────────────────
    const PALETTE = [
        '#6366f1','#8b5cf6','#ec4899','#f43f5e','#f97316',
        '#eab308','#22c55e','#06b6d4','#3b82f6','#a855f7'
    ];

    // ─── 1. Pie Chart: Kategori ──────────────────────────────────────
    const catLabels = @json($byCategory->keys()->values());
    const catValues = @json($byCategory->values()->values());

    if (catLabels.length > 0) {
        const ctxCat = document.getElementById('chartCategory');
        if (ctxCat) {
            new Chart(ctxCat, {
                type: 'doughnut',
                data: {
                    labels: catLabels,
                    datasets: [{
                        data: catValues,
                        backgroundColor: PALETTE.slice(0, catLabels.length),
                        borderWidth: 2,
                        borderColor: '#fff',
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 12, font: { size: 11 } } },
                        tooltip: {
                            callbacks: {
                                label: ctx => ' Rp ' + ctx.parsed.toLocaleString('id-ID')
                            }
                        }
                    }
                }
            });
        }
    }

    // ─── 2. Line Chart: Tren Bulanan ─────────────────────────────────
    const trendLabels = @json($trendLabels);
    const trendDatasetsRaw = @json($trendDatasets);
    
    const chartTrendDatasets = trendDatasetsRaw.map((ds, idx) => {
        const color = PALETTE[idx % PALETTE.length];
        return {
            label: ds.label,
            data: ds.data,
            borderColor: color,
            backgroundColor: color + '1a',
            borderWidth: 2,
            pointBackgroundColor: color,
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: false,
            tension: 0.4
        };
    });

    const ctxTrend = document.getElementById('chartTrend');
    if (ctxTrend) {
        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: chartTrendDatasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: { boxWidth: 12, font: { size: 11 }, padding: 15 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                let lbl = ctx.dataset.label || '';
                                if (lbl) lbl += ': ';
                                if (ctx.parsed.y !== null) {
                                    lbl += 'Rp ' + ctx.parsed.y.toLocaleString('id-ID');
                                }
                                return lbl;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: val => 'Rp ' + (val >= 1000000 ? (val/1000000).toFixed(0)+'Jt' : val.toLocaleString('id-ID')),
                            font: { size: 10 }
                        },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: { grid: { display: false }, ticks: { font: { size: 11 } } }
                }
            }
        });
    }

    // ─── 3. Bar Chart: Per Branch ────────────────────────────────────
    @if($isAdmin)
    const branchLabels = @json($byBranch->pluck('name'));
    const branchValues = @json($byBranch->pluck('total'));

    const ctxBranch = document.getElementById('chartBranch');
    if (ctxBranch && branchLabels.length > 0) {
        new Chart(ctxBranch, {
            type: 'bar',
            data: {
                labels: branchLabels,
                datasets: [{
                    label: 'Pengeluaran',
                    data: branchValues,
                    backgroundColor: PALETTE.slice(0, branchLabels.length).map(c => c + 'cc'),
                    borderColor: PALETTE.slice(0, branchLabels.length),
                    borderWidth: 1.5,
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ' Rp ' + ctx.parsed.y.toLocaleString('id-ID')
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: val => 'Rp ' + (val>=1000000 ? (val/1000000).toFixed(0)+'Jt' : val.toLocaleString('id-ID')),
                            font: { size: 10 }
                        },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: { grid: { display: false }, ticks: { font: { size: 11 } } }
                }
            }
        });
    }
    @endif

    // ─── 4. Donut Chart: Rembush vs Pengajuan ───────────────────────
    const rvpCtx = document.getElementById('chartRvP');
    const rembushVal = {{ $rembushTotal }};
    const pengajuanVal = {{ $pengajuanTotal }};

    if (rvpCtx && (rembushVal + pengajuanVal) > 0) {
        new Chart(rvpCtx, {
            type: 'doughnut',
            data: {
                labels: ['Rembush', 'Pengajuan'],
                datasets: [{
                    data: [rembushVal, pengajuanVal],
                    backgroundColor: ['#6366f1', '#ec4899'],
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 14, font: { size: 12 } } },
                    tooltip: {
                        callbacks: {
                            label: ctx => ' Rp ' + ctx.parsed.toLocaleString('id-ID')
                        }
                    }
                }
            }
        });
    }

    // ─── Quick Action: Approve / Reject via AJAX ─────────────────────
    @if($isAdmin)
    function bindPendingButtons() {
        document.querySelectorAll('.btn-quick-approve, .btn-quick-reject').forEach(btn => {
            // skip if already bound
            if (btn.dataset.bound) return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', function () {
                const id     = this.dataset.id;
                const status = this.dataset.status;
                const csrf   = document.querySelector('meta[name="csrf-token"]').content;
                const row    = document.getElementById('pending-row-' + id);

                if (status === 'rejected') {
                    const reason = prompt('Alasan penolakan (wajib diisi):');
                    if (!reason) return;
                    doStatusAndRefresh(id, status, csrf, row, reason);
                } else {
                    doStatusAndRefresh(id, status, csrf, row, null);
                }
            });
        });
    }

    function doStatusAndRefresh(id, status, csrf, row, reason) {
        const body = new URLSearchParams({ _method:'PATCH', _token: csrf, status });
        if (reason) body.append('rejection_reason', reason);

        fetch(`/transactions/${id}/status`, { method:'POST', body, headers:{ 'X-Requested-With':'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Show toast notification
                if (data.toast_message) {
                    showDashToast(data.toast_message, data.toast_type || 'info');
                }
                // Animate out
                if (row) {
                    row.style.transition = 'opacity 0.3s, transform 0.3s';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(-20px)';
                }
                // After animation, refresh entire pending list
                setTimeout(() => refreshPendingList(), 350);
            }
        })
        .catch(() => showDashToast('Gagal mengubah status.', 'error'));
    }

    function showDashToast(message, type) {
        const colors = {
            success: { bg: '#ecfdf5', border: '#a7f3d0', text: '#065f46', icon: 'check-circle' },
            warning: { bg: '#fffbeb', border: '#fde68a', text: '#92400e', icon: 'alert-triangle' },
            error:   { bg: '#fef2f2', border: '#fecaca', text: '#991b1b', icon: 'x-circle' },
            info:    { bg: '#eff6ff', border: '#bfdbfe', text: '#1e40af', icon: 'info' },
        };
        const c = colors[type] || colors.info;

        const toast = document.createElement('div');
        toast.style.cssText = `position:fixed;top:24px;right:24px;z-index:9999;padding:14px 20px;border-radius:14px;background:${c.bg};border:1px solid ${c.border};color:${c.text};font-size:14px;font-weight:600;box-shadow:0 8px 30px rgba(0,0,0,.12);display:flex;align-items:center;gap:10px;max-width:420px;animation:toastSlideIn .3s ease-out;`;
        toast.innerHTML = `<i data-lucide="${c.icon}" style="width:18px;height:18px;flex-shrink:0"></i><span>${message}</span>`;
        document.body.appendChild(toast);
        if (window.lucide) lucide.createIcons();

        setTimeout(() => {
            toast.style.animation = 'toastSlideOut .3s ease-in forwards';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    function refreshPendingList() {
        fetch('{{ route("dashboard.pendingListData") }}', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('pending-tbody');
            const wrapper = document.getElementById('pending-content-wrapper');
            const badge = document.getElementById('pending-count-badge');
            const subtitle = document.getElementById('pending-subtitle');
            const footer = document.getElementById('pending-footer');

            if (data.count > 0 && tbody) {
                tbody.innerHTML = data.html;
                bindPendingButtons();
                if (window.lucide) lucide.createIcons();
            } else if (wrapper) {
                wrapper.innerHTML = '<div class="flex flex-col items-center justify-center py-12 text-slate-300">' +
                    '<i data-lucide="check-circle-2" class="w-10 h-10 mb-2"></i>' +
                    '<p class="text-sm text-slate-400">Semua transaksi sudah diproses!</p></div>';
                if (window.lucide) lucide.createIcons();
            }

            // Update badge & subtitle
            if (badge) {
                if (data.totalPending > 0) {
                    badge.textContent = data.totalPending;
                    badge.style.display = '';
                } else {
                    badge.style.display = 'none';
                }
            }
            if (subtitle) subtitle.textContent = data.totalPending + ' transaksi pending terbaru';

            // Update footer
            if (footer) {
                if (data.totalPending > 10) {
                    footer.style.display = '';
                    footer.querySelector('a').textContent = 'Lihat ' + (data.totalPending - 10) + ' lainnya →';
                } else {
                    footer.style.display = 'none';
                }
            }
        })
        .catch(() => {});
    }

    // Bind initial buttons
    bindPendingButtons();

    // Silent auto-refresh pending list every 15 seconds
    setInterval(refreshPendingList, 15000);
    @endif

    // ─── Silent Auto-refresh: Branch Cost Breakdown ──────────────────
    @if($isAdmin)
    (function() {
        const monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        const monthSel   = document.getElementById('branch-cost-month');
        const yearSel    = document.getElementById('branch-cost-year');
        const startInput = document.getElementById('branch-cost-start');
        const endInput   = document.getElementById('branch-cost-end');
        const periodEl   = document.getElementById('branch-cost-period');

        function updatePeriodLabel() {
            if (!periodEl) return;
            
            const s = startInput.value;
            const e = endInput.value;

            if (s || e) {
                const fmt = d => d ? new Date(d).toLocaleDateString('id-ID', { day:'numeric', month:'short', year:'numeric' }) : '...';
                periodEl.textContent = (s ? fmt(s) : 'Awal') + ' - ' + (e ? fmt(e) : 'Sekarang');
            } else if (monthSel && yearSel) {
                periodEl.textContent = monthNames[monthSel.value - 1] + ' ' + yearSel.value;
            }
        }
        updatePeriodLabel();

        function silentRefreshBranchCost() {
            const grid = document.getElementById('branch-cost-grid');
            const countEl = document.getElementById('branch-cost-count');
            if (!grid) return;

            const s = startInput ? startInput.value : '';
            const e = endInput   ? endInput.value : '';
            const m = monthSel   ? monthSel.value : '';
            const y = yearSel    ? yearSel.value : '';

            let url = '{{ route("dashboard.branchCostData") }}?';
            if (s || e) {
                if (s) url += '&start_date=' + s;
                if (e) url += '&end_date=' + e;
            } else {
                if (m) url += '&month=' + m;
                if (y) url += '&year=' + y;
            }

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                if (data.count > 0) {
                    grid.className = 'grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4';
                    grid.innerHTML = data.html;
                } else {
                    grid.className = 'dash-card p-8 text-center';
                    grid.innerHTML = '<div class="flex flex-col items-center justify-center text-slate-300"><i data-lucide="building-2" class="w-12 h-12 mb-2"></i><p class="text-sm text-slate-400">Belum ada data rincian cabang untuk periode ini</p></div>';
                }
                if (countEl) countEl.textContent = data.count;
                updatePeriodLabel();
                if (window.lucide) lucide.createIcons();
            })
            .catch(() => {});
        }

        // Event listeners
        const refresh = () => { silentRefreshBranchCost(); if (typeof loadAllHutangAmounts === 'function') loadAllHutangAmounts(); };

        if (monthSel) monthSel.addEventListener('change', () => { 
            if (startInput) startInput.value = ''; 
            if (endInput)   endInput.value = ''; 
            refresh(); 
        });
        if (yearSel) yearSel.addEventListener('change', () => { 
            if (startInput) startInput.value = ''; 
            if (endInput)   endInput.value = ''; 
            refresh(); 
        });

        if (startInput) startInput.addEventListener('change', refresh);
        if (endInput)   endInput.addEventListener('change', refresh);

        // Silent auto-refresh every 30 seconds
        setInterval(silentRefreshBranchCost, 30000);
    })();
    @endif

    // ─── Hutang Rembush: Load All Amounts On Page Ready ──────────────
    @if($isAdmin)
    loadAllHutangAmounts();
    @endif
});
</script>

<script>
// ═══════════════════════════════════════════════════════
// HUTANG REMBUSH MODAL
// ═══════════════════════════════════════════════════════

const hutangModal    = document.getElementById('hutang-modal');
const hutangLoading  = document.getElementById('hutang-loading');
const hutangBody     = document.getElementById('hutang-body');
const hutangTbody    = document.getElementById('hutang-tbody');
const hutangEmpty    = document.getElementById('hutang-empty');
const hutangTotal    = document.getElementById('hutang-total-label');
const hutangTitle    = document.getElementById('hutang-modal-title');
const hutangSubtitle = document.getElementById('hutang-modal-subtitle');

function openHutangModal(branchName) {
    if (!hutangModal) return;
    hutangTitle.textContent    = 'Hutang Rembush — ' + branchName;
    hutangSubtitle.textContent = 'Memuat daftar rembush yang belum selesai...';

    // Reset state
    hutangLoading.classList.remove('hidden');
    hutangBody.classList.add('hidden');
    hutangEmpty.classList.add('hidden');

    // Show modal
    hutangModal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    if (window.lucide) lucide.createIcons({ root: hutangModal });

    fetchHutangData(branchName);
}

function closeHutangModal() {
    if (!hutangModal) return;
    hutangModal.classList.add('hidden');
    document.body.style.overflow = '';
}

// Close on backdrop click
if (hutangModal) {
    hutangModal.addEventListener('click', function(e) {
        if (e.target === hutangModal) closeHutangModal();
    });
}

// ESC to close
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && hutangModal && !hutangModal.classList.contains('hidden')) {
        closeHutangModal();
    }
});

async function fetchHutangData(branchName) {
    try {
        const url = '{{ route("dashboard.branchHutangData") }}?branch_name=' + encodeURIComponent(branchName);
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();

        hutangLoading.classList.add('hidden');
        hutangBody.classList.remove('hidden');

        if (data.transactions.length === 0) {
            hutangTbody.innerHTML = '';
            hutangEmpty.classList.remove('hidden');
            hutangTotal.textContent = 'Rp 0';
            hutangSubtitle.textContent = 'Semua rembush sudah terselesaikan ✓';
        } else {
            hutangEmpty.classList.add('hidden');
            hutangTbody.innerHTML = data.transactions.map(renderHutangRow).join('');
            hutangTotal.textContent = data.formatted_total;
            hutangSubtitle.textContent = data.transactions.length + ' transaksi belum selesai';
        }

        if (window.lucide) lucide.createIcons({ root: hutangModal });
    } catch (err) {
        hutangLoading.classList.add('hidden');
        hutangBody.classList.remove('hidden');
        hutangTbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-red-400 text-sm font-medium">Gagal memuat data</td></tr>';
        hutangEmpty.classList.add('hidden');
    }
}

function renderHutangRow(t) {
    const badge = getStatusBadge(t.status);
    return `<tr class="hover:bg-slate-50 transition-colors">
        <td class="px-5 py-3">
            <span class="text-xs font-bold text-slate-800">${t.invoice_number}</span>
            <span class="block text-[10px] text-slate-400">${t.created_at}</span>
        </td>
        <td class="px-3 py-3 hidden sm:table-cell">
            <span class="text-xs text-slate-600 font-medium">${t.submitter_name}</span>
        </td>
        <td class="px-3 py-3 hidden md:table-cell">
            <span class="text-xs text-slate-500 truncate max-w-[120px] block">${t.category}</span>
        </td>
        <td class="px-3 py-3 text-right">
            <span class="text-xs font-bold text-slate-800">${t.formatted_amount}</span>
        </td>
        <td class="px-5 py-3 text-center">
            <span class="${badge.cls} inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold whitespace-nowrap">
                <span class="w-1.5 h-1.5 rounded-full ${badge.dot}"></span>
                ${badge.label}
            </span>
        </td>
    </tr>`;
}

function getStatusBadge(status) {
    const map = {
        'pending':            { cls: 'bg-amber-50 text-amber-700 border border-amber-200',    dot: 'bg-amber-500',    label: 'Pending' },
        'waiting_payment':    { cls: 'bg-blue-50 text-blue-700 border border-blue-200',       dot: 'bg-blue-500',     label: 'Waiting Payment' },
        'flagged':            { cls: 'bg-red-50 text-red-700 border border-red-200',           dot: 'bg-red-500',      label: 'Flagged' },
        'pending_technician': { cls: 'bg-orange-50 text-orange-700 border border-orange-200', dot: 'bg-orange-500',   label: 'Siap Ambil' },
        'approved':           { cls: 'bg-emerald-50 text-emerald-700 border border-emerald-200', dot: 'bg-emerald-500', label: 'Menunggu Owner' },
    };
    return map[status] || { cls: 'bg-slate-100 text-slate-600', dot: 'bg-slate-400', label: status };
}

// Load hutang amounts for all visible branch cards (for the badge)
async function loadAllHutangAmounts() {
    const buttons = document.querySelectorAll('.hutang-btn[data-branch]');
    buttons.forEach(async (btn) => {
        const branchName = btn.dataset.branch;
        const amountEl = btn.querySelector('.hutang-amount');
        if (!amountEl) return;
        try {
            const url = '{{ route("dashboard.branchHutangData") }}?branch_name=' + encodeURIComponent(branchName);
            const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            if (data.total_hutang > 0) {
                amountEl.innerHTML = `<span class="font-black text-orange-600">${data.formatted_total}</span>`;
                btn.classList.add('border-orange-300');
            } else {
                amountEl.innerHTML = `<span class="text-slate-400 font-semibold text-[10px]">Lunas</span>`;
                btn.classList.remove('border-orange-300');
                btn.classList.add('border-slate-200', 'opacity-60');
            }
        } catch (e) {
            amountEl.innerHTML = `<span class="text-slate-300 text-[10px]">—</span>`;
        }
    });
}
</script>
@endpush

@section('content')
<style>
    .dash-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
        transition: box-shadow .2s, transform .2s;
    }
    .dash-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.10); transform: translateY(-2px); }
    .metric-icon {
        width: 48px; height: 48px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .chart-container { position: relative; height: 260px; }
    .badge-pending  { background: #fefce8; color: #92400e; border: 1px solid #fde68a; }
    .badge-approved { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .badge-completed{ background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .badge-rejected { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    .status-badge { display: inline-flex; align-items: center; padding: 2px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; }
    .table-row { transition: background .15s; }
    .table-row:hover { background: #f8fafc; }
    .leaderboard-rank { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items:center; justify-content:center; font-weight:800; font-size:12px; flex-shrink:0; }
    .rank-1 { background: linear-gradient(135deg,#f59e0b,#fbbf24); color:#fff; }
    .rank-2 { background: linear-gradient(135deg,#94a3b8,#cbd5e1); color:#fff; }
    .rank-3 { background: linear-gradient(135deg,#d97706,#fbbf24); color:#fff; }
    .rank-other { background: #f1f5f9; color: #64748b; }
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    @keyframes toastSlideIn { from { opacity:0; transform:translateX(100px); } to { opacity:1; transform:translateX(0); } }
    @keyframes toastSlideOut { from { opacity:1; transform:translateX(0); } to { opacity:0; transform:translateX(100px); } }
</style>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- Header row                                                         --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="p-4 md:p-6 pb-0">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-black text-slate-900">Dashboard</h1>
            <p class="text-sm text-slate-500 mt-0.5">
                {{ now()->format('l, d F Y') }} &bull;
                @if($isAdmin) Menampilkan data seluruh perusahaan
                @else Menampilkan data Anda
                @endif
            </p>
        </div>
        <a href="{{ route('transactions.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold transition-colors">
            <i data-lucide="list" class="w-4 h-4"></i>
            Lihat Semua Transaksi
        </a>
    </div>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- ROW 1 — METRIC CARDS                                          --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        {{-- Total Pengeluaran --}}
        <div class="dash-card p-5">
            <div class="flex items-start gap-4">
                <div class="metric-icon bg-gradient-to-br from-indigo-500 to-purple-600 shadow-lg shadow-indigo-500/30">
                    <i data-lucide="trending-up" class="w-5 h-5 text-white"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Pengeluaran Bulan Ini</p>
                    <p class="text-xl font-black text-slate-900 leading-tight truncate">
                        {{ \App\Models\Transaction::formatShortRupiah($totalPengeluaranRaw) }}
                    </p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Approved + Selesai</p>
                </div>
            </div>
        </div>

        {{-- Pending --}}
        <div class="dash-card p-5">
            <div class="flex items-start gap-4">
                <div class="metric-icon bg-gradient-to-br from-amber-400 to-orange-500 shadow-lg shadow-amber-500/30">
                    <i data-lucide="clock" class="w-5 h-5 text-white"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Menunggu Persetujuan</p>
                    <p class="text-xl font-black text-slate-900 leading-tight">{{ $pendingCount }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">{{ \App\Models\Transaction::formatShortRupiah($pendingTotal) }}</p>
                </div>
            </div>
        </div>

        {{-- Total Transaksi --}}
        <div class="dash-card p-5">
            <div class="flex items-start gap-4">
                <div class="metric-icon bg-gradient-to-br from-blue-500 to-cyan-500 shadow-lg shadow-blue-500/30">
                    <i data-lucide="receipt" class="w-5 h-5 text-white"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Total Transaksi</p>
                    <p class="text-xl font-black text-slate-900 leading-tight">{{ $totalTransaksi }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Bulan {{ now()->format('F Y') }}</p>
                </div>
            </div>
        </div>

        {{-- Ditolak --}}
        <div class="dash-card p-5">
            <div class="flex items-start gap-4">
                <div class="metric-icon bg-gradient-to-br from-rose-500 to-red-600 shadow-lg shadow-rose-500/30">
                    <i data-lucide="x-circle" class="w-5 h-5 text-white"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Pengajuan Ditolak</p>
                    <p class="text-xl font-black text-slate-900 leading-tight">{{ $rejectedCount }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Bulan ini</p>
                </div>
            </div>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- ROW 2 — CHARTS: Kategori | Tren Bulanan                       --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

        {{-- Pie Chart: Kategori --}}
        <div class="dash-card p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-slate-800">Pengeluaran per Kategori</h3>
                    <p class="text-xs text-slate-400">Bulan ini (approved + selesai)</p>
                </div>
                <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                    <i data-lucide="pie-chart" class="w-4 h-4 text-indigo-600"></i>
                </div>
            </div>
            @if($byCategory->count() > 0)
                <div class="chart-container">
                    <canvas id="chartCategory"></canvas>
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-48 text-slate-300">
                    <i data-lucide="pie-chart" class="w-12 h-12 mb-2"></i>
                    <p class="text-sm">Belum ada data bulan ini</p>
                </div>
            @endif
        </div>

        {{-- Line Chart: Tren Bulanan --}}
        <div class="dash-card p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-slate-800">Tren Pengeluaran</h3>
                    <p class="text-xs text-slate-400">6 bulan terakhir</p>
                </div>
                <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center">
                    <i data-lucide="trending-up" class="w-4 h-4 text-purple-600"></i>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="chartTrend"></canvas>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- ROW 3 — CHARTS: Per Branch | Rembush vs Pengajuan             --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

        {{-- Bar Chart: Per Branch --}}
        @if($isAdmin)
        <div class="dash-card p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-slate-800">Pengeluaran per Cabang</h3>
                    <p class="text-xs text-slate-400">Bulan ini (approved + selesai)</p>
                </div>
                <div class="w-8 h-8 rounded-lg bg-cyan-50 flex items-center justify-center">
                    <i data-lucide="bar-chart-2" class="w-4 h-4 text-cyan-600"></i>
                </div>
            </div>
            @if($byBranch->count() > 0)
                <div class="chart-container">
                    <canvas id="chartBranch"></canvas>
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-48 text-slate-300">
                    <i data-lucide="bar-chart-2" class="w-12 h-12 mb-2"></i>
                    <p class="text-sm">Belum ada alokasi cabang bulan ini</p>
                </div>
            @endif
        </div>
        @endif

        {{-- Donut Chart: Rembush vs Pengajuan --}}
        <div class="dash-card p-5 {{ !$isAdmin ? 'lg:col-span-2' : '' }}">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-slate-800">Rembush vs Pengajuan</h3>
                    <p class="text-xs text-slate-400">Perbandingan tipe transaksi bulan ini</p>
                </div>
                <div class="w-8 h-8 rounded-lg bg-pink-50 flex items-center justify-center">
                    <i data-lucide="donut" class="w-4 h-4 text-pink-600"></i>
                </div>
            </div>
            @if($rembushTotal + $pengajuanTotal > 0)
                <div class="flex flex-col sm:flex-row items-center gap-6">
                    <div class="relative w-full sm:w-auto" style="height:220px; min-width:220px;">
                        <canvas id="chartRvP"></canvas>
                    </div>
                    <div class="flex flex-col gap-3 w-full">
                        {{-- Rembush --}}
                        <div class="flex items-center gap-3 p-3 rounded-xl bg-indigo-50">
                            <span class="w-3 h-3 rounded-full bg-indigo-500 flex-shrink-0"></span>
                            <div class="flex-1">
                                <p class="text-xs font-semibold text-slate-500">Rembush</p>
                                <p class="font-bold text-slate-800">{{ \App\Models\Transaction::formatShortRupiah($rembushTotal) }}</p>
                            </div>
                            @if($rembushTotal + $pengajuanTotal > 0)
                            <span class="text-xs font-bold text-indigo-600">
                                {{ round($rembushTotal / ($rembushTotal + $pengajuanTotal) * 100, 1) }}%
                            </span>
                            @endif
                        </div>
                        {{-- Pengajuan --}}
                        <div class="flex items-center gap-3 p-3 rounded-xl bg-pink-50">
                            <span class="w-3 h-3 rounded-full bg-pink-500 flex-shrink-0"></span>
                            <div class="flex-1">
                                <p class="text-xs font-semibold text-slate-500">Pengajuan</p>
                                <p class="font-bold text-slate-800">{{ \App\Models\Transaction::formatShortRupiah($pengajuanTotal) }}</p>
                            </div>
                            @if($rembushTotal + $pengajuanTotal > 0)
                            <span class="text-xs font-bold text-pink-600">
                                {{ round($pengajuanTotal / ($rembushTotal + $pengajuanTotal) * 100, 1) }}%
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-48 text-slate-300">
                    <i data-lucide="bar-chart" class="w-12 h-12 mb-2"></i>
                    <p class="text-sm">Belum ada data bulan ini</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- ROW 4 — TABLES: Pending Approval | Recent Transactions         --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

        {{-- Quick Actions: Pending --}}
        @if($isAdmin)
        <div class="dash-card overflow-hidden">
            <div class="px-5 pt-5 pb-3 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-800">Butuh Persetujuan Anda</h3>
                    <p class="text-xs text-slate-400" id="pending-subtitle">{{ $pendingCount }} transaksi pending terbaru</p>
                </div>
                @if($pendingCount > 0)
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-100 text-amber-700 text-xs font-black" id="pending-count-badge">
                    {{ $pendingCount }}
                </span>
                @else
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-100 text-amber-700 text-xs font-black" id="pending-count-badge" style="display:none">0</span>
                @endif
            </div>
            <div class="overflow-x-auto" id="pending-content-wrapper">
                @if($pendingTransactions->count() > 0)
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="text-left px-5 py-2 text-xs font-semibold text-slate-400 uppercase">Nota</th>
                            <th class="text-left px-2 py-2 text-xs font-semibold text-slate-400 uppercase hidden sm:table-cell">Nominal</th>
                            <th class="text-right px-5 py-2 text-xs font-semibold text-slate-400 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50" id="pending-tbody">
                        @include('dashboard._pending-rows')
                    </tbody>
                </table>
                @else
                <div class="flex flex-col items-center justify-center py-12 text-slate-300">
                    <i data-lucide="check-circle-2" class="w-10 h-10 mb-2"></i>
                    <p class="text-sm text-slate-400">Semua transaksi sudah diproses!</p>
                </div>
                @endif
            </div>
            <div class="px-5 py-3 border-t border-slate-100" id="pending-footer" @if($pendingCount <= 10) style="display:none" @endif>
                <a href="{{ route('transactions.index', ['status' => 'pending']) }}"
                   class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">
                    Lihat {{ max(0, $pendingCount - 10) }} lainnya →
                </a>
            </div>
        </div>
        @endif

        {{-- Recent Transactions --}}
        <div class="dash-card overflow-hidden {{ !$isAdmin ? 'lg:col-span-2' : '' }}">
            <div class="px-5 pt-5 pb-3 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-800">Transaksi Terbaru</h3>
                    <p class="text-xs text-slate-400">{{ $recentTransactions->count() }} transaksi terakhir</p>
                </div>
                <a href="{{ route('transactions.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">
                    Lihat Semua →
                </a>
            </div>
            <div class="overflow-x-auto">
                @if($recentTransactions->count() > 0)
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="text-left px-5 py-2 text-xs font-semibold text-slate-400 uppercase">Tanggal</th>
                            <th class="text-left px-2 py-2 text-xs font-semibold text-slate-400 uppercase hidden md:table-cell">Nama</th>
                            <th class="text-left px-2 py-2 text-xs font-semibold text-slate-400 uppercase hidden sm:table-cell">Jenis</th>
                            <th class="text-right px-2 py-2 text-xs font-semibold text-slate-400 uppercase hidden sm:table-cell">Nominal</th>
                            <th class="text-right px-5 py-2 text-xs font-semibold text-slate-400 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($recentTransactions as $t)
                        <tr class="table-row">
                            <td class="px-5 py-3">
                                <a href="{{ route('transactions.show', $t->id) }}"
                                   class="font-semibold text-slate-800 hover:text-indigo-600 transition-colors block text-xs">
                                    {{ $t->invoice_number }}
                                </a>
                                <span class="text-[11px] text-slate-400">{{ $t->created_at->format('d M Y') }}</span>
                            </td>
                            <td class="px-2 py-3 hidden md:table-cell">
                                <span class="text-slate-700 text-xs">
                                    {{ $t->customer ?? $t->vendor ?? ($t->submitter->name ?? '-') }}
                                </span>
                            </td>
                            <td class="px-2 py-3 hidden sm:table-cell">
                                <span class="text-xs px-2 py-0.5 rounded-full font-semibold
                                    {{ $t->type === 'rembush' ? 'bg-indigo-50 text-indigo-700' : 'bg-purple-50 text-purple-700' }}">
                                    {{ $t->type_label }}
                                </span>
                            </td>
                            <td class="px-2 py-3 text-right hidden sm:table-cell">
                                <span class="font-semibold text-slate-700 text-xs">
                                    {{ \App\Models\Transaction::formatShortRupiah($t->effective_amount) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                @php
                                    $badgeClass = match($t->status) {
                                        'pending'   => 'badge-pending',
                                        'approved'  => 'badge-approved',
                                        'completed' => 'badge-completed',
                                        'rejected'  => 'badge-rejected',
                                        default     => 'bg-slate-100 text-slate-600',
                                    };
                                    $dotColor = match($t->status) {
                                        'pending'   => 'bg-amber-500',
                                        'approved'  => 'bg-blue-500',
                                        'completed' => 'bg-emerald-500',
                                        'rejected'  => 'bg-rose-500',
                                        default     => 'bg-slate-400',
                                    };
                                @endphp
                                <span class="status-badge {{ $badgeClass }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }} mr-1.5"></span>
                                    {{ $t->status_label }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="flex flex-col items-center justify-center py-12 text-slate-300">
                    <i data-lucide="receipt" class="w-10 h-10 mb-2"></i>
                    <p class="text-sm text-slate-400">Belum ada transaksi</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{-- ROW 6 — RINCIAN BIAYA PER CABANG (admin only, auto-refresh)       --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    @if($isAdmin)
    <div class="mb-6" id="branch-cost-section">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500 to-emerald-600 shadow-lg shadow-teal-500/30 flex items-center justify-center">
                    <i data-lucide="building-2" class="w-5 h-5 text-white"></i>
                </div>
                <div>
                    <h2 class="text-lg font-black text-slate-900">Rincian Biaya per Cabang</h2>
                    <p class="text-xs text-slate-400">
                        <span id="branch-cost-period"></span> &bull;
                        <span id="branch-cost-count">{{ $branchCostBreakdown->count() }}</span> cabang
                    </p>
                </div>
            </div>

            {{-- Filters: Month/Year + Custom Date Range --}}
            <div class="flex flex-wrap items-center gap-2">
                {{-- Month/Year Selectors --}}
                <div class="flex items-center gap-2">
                    <select id="branch-cost-month" class="text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 shadow-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 outline-none cursor-pointer transition-all hover:border-slate-300">
                        @php $bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']; @endphp
                        @foreach($bulan as $i => $b)
                            <option value="{{ $i + 1 }}" {{ ($i + 1) == now()->month ? 'selected' : '' }}>{{ $b }}</option>
                        @endforeach
                    </select>
                    <select id="branch-cost-year" class="text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 shadow-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 outline-none cursor-pointer transition-all hover:border-slate-300">
                        @for($y = now()->year; $y >= now()->year - 2; $y--)
                            <option value="{{ $y }}" {{ $y == now()->year ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <span class="text-slate-200 mx-1 hidden lg:inline">|</span>

                {{-- Custom Date Range --}}
                <div class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl px-2 py-1 shadow-sm focus-within:ring-2 focus-within:ring-teal-500/20 focus-within:border-teal-400 transition-all">
                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400 ml-1"></i>
                    <input type="date" id="branch-cost-start" value="{{ request('start_date') }}" title="Tanggal Mulai" class="text-xs font-bold text-slate-700 bg-transparent outline-none cursor-pointer p-1">
                    <span class="text-slate-300 text-[10px]">—</span>
                    <input type="date" id="branch-cost-end" value="{{ request('end_date') }}" title="Tanggal Selesai" class="text-xs font-bold text-slate-700 bg-transparent outline-none cursor-pointer p-1">
                </div>
            </div>
        </div>

        @if($branchCostBreakdown->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4" id="branch-cost-grid">
            @include('dashboard._branch-cost-cards')
        </div>
        @else
        <div class="dash-card p-8 text-center" id="branch-cost-grid">
            <div class="flex flex-col items-center justify-center text-slate-300">
                <i data-lucide="building-2" class="w-12 h-12 mb-2"></i>
                <p class="text-sm text-slate-400">Belum ada data rincian cabang bulan ini</p>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{-- HUTANG REMBUSH MODAL                                              --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    <div id="hutang-modal"
         class="hidden fixed inset-0 z-[70] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
         role="dialog" aria-modal="true" aria-labelledby="hutang-modal-title">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden"
             id="hutang-modal-box">

            {{-- Header --}}
            <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 shrink-0">
                <div class="w-9 h-9 rounded-xl bg-orange-100 flex items-center justify-center shrink-0">
                    <i data-lucide="alert-circle" class="w-4.5 h-4.5 text-orange-500"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-base font-extrabold text-slate-900" id="hutang-modal-title">Hutang Rembush</h3>
                    <p class="text-xs text-slate-400 font-medium" id="hutang-modal-subtitle">Transaksi rembush belum selesai</p>
                </div>
                <button onclick="closeHutangModal()"
                    class="p-2 rounded-xl hover:bg-slate-100 transition-colors text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            {{-- Loading Skeleton --}}
            <div id="hutang-loading" class="flex-1 p-6 space-y-3">
                @for($i = 0; $i < 4; $i++)
                <div class="animate-pulse flex items-center gap-3">
                    <div class="h-4 bg-slate-200 rounded w-24"></div>
                    <div class="h-4 bg-slate-100 rounded flex-1"></div>
                    <div class="h-4 bg-slate-200 rounded w-20"></div>
                    <div class="h-5 bg-slate-100 rounded-full w-16"></div>
                    <div class="h-4 bg-slate-200 rounded w-24"></div>
                </div>
                @endfor
            </div>

            {{-- Content --}}
            <div id="hutang-body" class="hidden flex flex-col flex-1 min-h-0">
                {{-- Table --}}
                <div class="flex-1 overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-white z-10">
                            <tr class="border-b border-slate-100 bg-slate-50/80">
                                <th class="text-left px-5 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Invoice</th>
                                <th class="text-left px-3 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider hidden sm:table-cell">Pengaju</th>
                                <th class="text-left px-3 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider hidden md:table-cell">Kategori</th>
                                <th class="text-right px-3 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Nominal</th>
                                <th class="text-center px-5 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody id="hutang-tbody" class="divide-y divide-slate-50"></tbody>
                    </table>
                    <div id="hutang-empty" class="hidden py-16 text-center">
                        <div class="flex flex-col items-center opacity-40">
                            <i data-lucide="check-circle-2" class="w-12 h-12 text-emerald-400 mb-3"></i>
                            <p class="font-bold text-slate-700">Tidak ada hutang rembush</p>
                            <p class="text-xs text-slate-400 mt-1">Semua rembush pada cabang ini sudah terselesaikan</p>
                        </div>
                    </div>
                </div>

                {{-- Footer: Total + Tutup --}}
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between shrink-0">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Total Hutang Rembush</p>
                        <p class="text-lg font-black text-orange-600" id="hutang-total-label">Rp 0</p>
                    </div>
                    <button onclick="closeHutangModal()"
                        class="px-5 py-2.5 rounded-xl bg-slate-800 text-white text-xs font-bold uppercase tracking-wider hover:bg-slate-700 transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
