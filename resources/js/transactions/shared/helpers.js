/**
 * ═══════════════════════════════════════════════
 * HELPERS — form-pengajuan scoped utilities
 * ═══════════════════════════════════════════════
 */

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
