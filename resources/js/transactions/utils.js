import { Config } from './config.js';

/**
 * ═══════════════════════════════════════════════════════════════
 * UTILS & AJAX FUNCTIONS
 * ═══════════════════════════════════════════════════════════════
 */

export function escapeHtml(unsafe) {
    if (!unsafe || typeof unsafe !== 'string') return unsafe;
    return unsafe.replace(/[&<"'>]/g, function (match) {
        const map = {
            '&': '&amp;', '<': '&lt;', '>': '&gt;',
            '"': '&quot;', "'": '&#039;'
        };
        return map[match];
    });
}

export function setAsReference(transactionId, itemName) {
    if (!confirm(`Jadikan harga untuk '${itemName}' sebagai referensi baru?`)) return;

    fetch(Config.endpoints.reference.setPriceIndex(transactionId), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': Config.csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ item_name: itemName })
    })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast(res.message, 'success');
            } else {
                showToast(res.error || 'Gagal menyimpan referensi.', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Terjadi kesalahan jaringan.', 'error');
        });
}

// ═══════════════════════════════════════════════════════════════
// NOMINAL FORMATTERS
// ═══════════════════════════════════════════════════════════════

export function formatNumber(n) {
    if (!n) return '';
    return n.toString().replace(/\D/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

export function unformatNumber(s) {
    if (!s) return 0;
    return parseInt(s.toString().replace(/\D/g, "")) || 0;
}

export function handleNominalInput(e) {
    let cursor = e.target.selectionStart;
    let originalLen = e.target.value.length;
    let formatted = formatNumber(e.target.value);
    e.target.value = formatted;

    // Adjust cursor position
    let newLen = formatted.length;
    e.target.setSelectionRange(cursor + (newLen - originalLen), cursor + (newLen - originalLen));
}

export function attachNominalFormatters() {
    document.querySelectorAll('.nominal-input').forEach(inp => {
        // Remove existing to avoid double binding
        inp.removeEventListener('input', handleNominalInput);
        inp.addEventListener('input', handleNominalInput);
    });
}

// Also export search sync functions if they are strictly utility
export function getActiveSearchValue() {
    const desktop = document.getElementById('instant-search');
    const tablet = document.getElementById('instant-search-tablet');
    const mobile = document.getElementById('instant-search-mobile');
    return (desktop?.value || tablet?.value || mobile?.value || '').trim();
}

export function syncSearchInputs(value) {
    document.querySelectorAll('.search-input-sync').forEach(input => {
        if (input.value !== value) input.value = value;
    });
}

// Global Toast function
window.showToast = function(message, type = 'error') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    let bgColors = 'bg-red-50 border-red-200 text-red-800';
    let accentClasses = 'bg-red-500';
    let iconBody = '<i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-red-500"></i>';

    if (type === 'success') {
        bgColors = 'bg-emerald-50 border-emerald-200 text-emerald-800';
        accentClasses = 'bg-emerald-500';
        iconBody = '<i data-lucide="check-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-emerald-500"></i>';
    } else if (type === 'info') {
        bgColors = 'bg-blue-50 border-blue-200 text-blue-800';
        accentClasses = 'bg-blue-500';
        iconBody = '<i data-lucide="info" class="w-4 h-4 mt-0.5 flex-shrink-0 text-blue-500"></i>';
    }

    const toast = document.createElement('div');
    toast.className = `relative flex items-start gap-3 p-4 rounded-xl shadow-lg border text-sm font-bold transform transition-all duration-300 translate-x-[120%] opacity-0 overflow-hidden ${bgColors}`;
    toast.innerHTML = `
    <div class="absolute left-0 top-0 bottom-0 w-1 ${accentClasses}"></div>
    ${iconBody}
    <div class="flex-1 right-0 text-xs">${message}</div>
    `;

    container.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.remove('translate-x-[120%]', 'opacity-0');
        toast.classList.add('translate-x-0', 'opacity-100');
    });

    if (typeof window.lucide !== 'undefined') {
        window.lucide.createIcons({ root: toast });
    }

    setTimeout(() => {
        toast.classList.remove('translate-x-0', 'opacity-100');
        toast.classList.add('translate-x-[120%]', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 5000);
};
