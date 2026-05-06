/**
 * ═══════════════════════════════════════════════
 * UTILS — Transaction utilities
 * ═══════════════════════════════════════════════
 */

/**
 * Get active search value from any search input
 * Checks all search inputs and returns the first non-empty value
 */
export function getActiveSearchValue() {
    const searchInputs = document.querySelectorAll('.search-input-sync');
    for (const input of searchInputs) {
        const val = input.value?.trim();
        if (val) return val;
    }
    return '';
}

/**
 * Synchronize all search inputs with the same value
 */
export function syncSearchInputs(value) {
    const searchInputs = document.querySelectorAll('.search-input-sync');
    searchInputs.forEach(input => {
        input.value = value;
    });
}

/**
 * Format angka menjadi format ribuan Indonesia (tanpa "Rp ")
 * Contoh: 1500000 → "1.500.000"
 */
export function formatNumber(num) {
    return Math.round(num || 0).toLocaleString('id-ID');
}

/**
 * Hapus semua karakter non-numerik dari string dan kembalikan sebagai integer
 * Contoh: "1.500.000" → 1500000
 */
export function unformatNumber(str) {
    return parseInt(String(str ?? '').replace(/[^0-9]/g, '')) || 0;
}

/**
 * Format angka menjadi format Rupiah lengkap
 * Contoh: 1500000 → "Rp 1.500.000"
 */
export function formatRupiah(num) {
    return 'Rp ' + formatNumber(num);
}

/**
 * Escape HTML untuk mencegah XSS saat insert ke innerHTML
 */
export function escapeHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * Set transaction as reference (for comparison)
 */
export function setAsReference(transactionId) {
    if (!transactionId) {
        console.error('setAsReference: No transaction ID provided');
        return;
    }
    
    // Store in localStorage
    localStorage.setItem('referenceTransactionId', transactionId);
    
    // Show toast notification
    if (typeof showToast === 'function') {
        showToast('Transaksi dijadikan referensi', 'success');
    }
    
    console.log(`✅ Transaction ${transactionId} set as reference`);
}
