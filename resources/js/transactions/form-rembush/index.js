import { ItemRepeater } from './item-repeater.js';
import { TechnicianManager } from './technician.js';
import { PaymentMethodManager } from './payment-method.js';
import { Uploader } from './uploader.js';
import { BranchDistribution } from './distribution.js';

document.addEventListener('DOMContentLoaded', function () {
    // Check if we are on the form-rembush page
    const form = document.getElementById('transaction-form');
    const isRembush = document.querySelector('input[name="type"][value="rembush"]');
    if (!form || !isRembush) return;

    // 1. Initial State & AI Data
    const aiData = window._aiData || {};
    const aiStatus = aiData.status ?? '';

    // 2. Initialize Modules
    const branchDistribution = new BranchDistribution(
        '#form-total-amount',
        '#summary-submit',
        { isOptional: true, tolerance: 500 }
    );

    const itemRepeater = new ItemRepeater(
        '#items-tbody',
        '#items-cards',
        '#display-total-items',
        '#form-total-amount'
    );

    const technicianManager = new TechnicianManager(
        '#technician_id',
        '#technician_bank_account_id',
        '#technician_bank_container'
    );

    const paymentMethodManager = new PaymentMethodManager(
        '#payment_method',
        '#bank_details_section'
    );

    const uploader = new Uploader('image-viewer');

    // 3. AI Autofill for Main Info
    if (aiStatus === 'completed') {
        const fillField = (sel, val) => {
            const node = document.querySelector(sel);
            if (node && val != null && val !== '') node.value = val;
        };
        fillField('[name="customer"]', aiData.nama_toko || aiData.customer || '');
        fillField('[name="date"]', aiData.tanggal || aiData.date || '');
    }

    // 4. Branch Distribution Auto-click from AI
    setTimeout(() => {
        if (aiData && aiData.branches && aiData.branches.length > 0) {
            const defaultBranchesToClick = aiData.branches.map(b => b.branch_id);
            defaultBranchesToClick.forEach(branchId => {
                const btn = document.querySelector(`.branch-pill[data-id="${branchId}"]`);
                if (btn) btn.click();
            });
        }
    }, 300);

    // 5. Total Change Sync
    document.getElementById('form-total-amount').addEventListener('change', () => {
        branchDistribution.renderDistribution();
    });

    // 6. Force initial sync
    branchDistribution.renderDistribution();

    // 6. Form Submission Spinner
    const submitBtn = document.getElementById('summary-submit');
    const submitText = document.getElementById('submit-text');
    const submitSpinner = document.getElementById('submit-spinner');

    if (form && submitBtn) {
        form.addEventListener('submit', function (e) {
            if (submitBtn.disabled) {
                e.preventDefault();
                return;
            }
            submitBtn.disabled = true;
            if (submitText) submitText.textContent = 'Memproses...';
            if (submitSpinner) submitSpinner.classList.remove('hidden');
        });
    }

    // Initialize Lucide Icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
