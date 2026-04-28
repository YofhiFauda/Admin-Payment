/**
 * ═══════════════════════════════════════════════════════════════
 * CONFIGURATION MODULE - Single Source of Truth
 * ═══════════════════════════════════════════════════════════════
 * Centralized configuration for endpoints, UI settings,
 * and business logic constants.
 */

export const Config = {
    // 1. Global Authentication & Context (Passed from Blade)
    csrfToken: window.AppConfig?.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
    user: {
        role: window.AppConfig?.userRole,
        id: window.AppConfig?.userId,
        isAdmin: window.AppConfig?.userIsAdmin || false,
        canManage: window.AppConfig?.userCanManage || false,
        isOwner: window.AppConfig?.userIsOwner || false,
    },

    // 2. API Endpoints
    endpoints: {
        transactions: {
            count: '/transactions/count',
            search: '/transactions/search',
            searchData: '/transactions/search-data',
            stats: '/transactions/stats',
            detail: (id) => `/transactions/${id}/detail-json`,
            status: (id) => `/transactions/${id}/status`,
            override: (id) => `/transactions/${id}/override`,
            forceApprove: (id) => `/transactions/${id}/force-approve`,
            delete: (id) => `/transactions/${id}`,
        },
        payment: {
            uploadCash: '/api/v1/payment/cash/upload',
            uploadTransfer: '/api/v1/payment/transfer/upload',
            uploadPengajuan: '/api/v1/payment/pengajuan/upload',
        },
        reference: {
            userBankAccounts: (userId) => `/user-bank-accounts/${userId}`,
            setPriceIndex: (id) => `/price-index/set-reference/${id}`,
            checkPriceIndex: '/api/price-index/check',
            autocomplete: '/api/items/autocomplete',
        }
    },

    // 3. UI Settings & Thresholds
    ui: {
        searchThreshold: 5000,    // Switch to server-side search >= 5k records
        itemsPerPage: 20,         // Rows per page
        searchDebounce: 300,      // Debounce timer for server-side search (ms)
        maxVisibleBranches: 2,    // Max branch tags to show before tooltip
    },

    // 4. Status Configurations (Colors, Icons, Labels)
    status: {
        pending: {
            label: (type) => type === 'gudang' ? 'Review Management' : 'Pending',
            color: (type) => type === 'gudang' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-yellow-50 text-yellow-700 border-yellow-200',
            icon: (type) => type === 'gudang' ? 'search' : 'clock'
        },
        approved: {
            label: (isLarge) => isLarge ? 'Menunggu Approve Owner' : 'Menunggu Owner',
            color: (isLarge) => isLarge ? 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200' : 'bg-purple-50 text-purple-700 border-purple-200',
            icon: (isLarge) => isLarge ? 'shield-alert' : 'user-check'
        },
        completed: {
            label: 'Selesai',
            color: 'bg-emerald-50 text-emerald-700 border-emerald-200',
            icon: 'check-circle-2'
        },
        rejected: {
            label: 'Ditolak',
            color: 'bg-red-50 text-red-700 border-red-200',
            icon: 'x-circle'
        },
        waiting_payment: {
            label: (isDebt) => isDebt ? 'Menunggu Pelunasan' : 'Menunggu Pembayaran',
            color: (isDebt) => isDebt ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-orange-50 text-orange-700 border-orange-200',
            icon: (isDebt) => isDebt ? 'wallet' : 'credit-card'
        },
        pending_technician: {
            label: 'Konfirmasi Teknisi',
            color: 'bg-teal-50 text-teal-700 border-teal-200',
            icon: 'package-check'
        },
        flagged: {
            label: 'Flagged',
            color: 'bg-rose-50 text-rose-700 border-rose-200',
            icon: 'flag'
        },
        'auto-reject': {
            label: 'Auto Reject',
            color: 'bg-gray-800 text-gray-50 border-gray-900',
            icon: 'bot'
        }
    },

    // 5. Type Configurations
    types: {
        rembush: {
            label: 'Rembush',
            color: 'bg-indigo-50 text-indigo-600 border-indigo-100',
            icon: 'receipt',
            prefix: 'UP-'
        },
        pengajuan: {
            label: 'Pengajuan',
            color: 'bg-teal-50 text-teal-600 border-teal-100',
            icon: 'shopping-bag',
            prefix: 'UP-'
        },
        gudang: {
            label: 'Gudang',
            color: 'bg-amber-50 text-amber-600 border-amber-100',
            icon: 'package',
            prefix: 'UP-'
        }
    },

    // 6. AI & OCR Status
    ai: {
        queued: { color: 'bg-gray-50 text-gray-600 border-gray-200', icon: 'clock', label: 'Antrian' },
        pending: { color: 'bg-gray-50 text-gray-600 border-gray-200', icon: 'clock', label: 'Pending' },
        processing: { color: 'bg-purple-50 text-purple-600 border-purple-200', icon: 'loader-2', label: 'OCR...', pulse: true },
        completed: { color: 'bg-green-50 text-green-600 border-green-200', icon: 'check-circle', label: 'AI ✓' },
        error: { color: 'bg-red-50 text-red-600 border-red-200', icon: 'alert-circle', label: 'AI ✗' }
    }
};

// Exporting aliases for convenience
export const userRole = Config.user.role;
export const canManage = Config.user.canManage;
export const isOwner = Config.user.isOwner;
export const isAdmin = Config.user.isAdmin;
export const userId = Config.user.id;