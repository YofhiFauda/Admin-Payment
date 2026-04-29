import { Uploader } from './uploader.js';
import { BranchDistribution } from './distribution.js';
import { ItemRepeater } from './item-repeater.js';
import { TechnicianManager } from '../form-rembush/technician.js';
import { PaymentMethodManager } from '../form-rembush/payment-method.js';

/**
 * ═══════════════════════════════════════════════════════════════
 * ORCHESTRATOR (Pembelian)
 * Initializes all sub-modules for the Pembelian transaction form.
 * ═══════════════════════════════════════════════════════════════
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Identify Form (Pembelian uses 'transaction-form')
    const form = document.getElementById('transaction-form');
    if (!form || !document.getElementById('pembelian-form-indicator')) return;

    console.log('[Pembelian] Initializing Modular Form...');

    // 2. Initialize Uploader
    new Uploader('nota');

    // 3. Initialize Branch Distribution
    // Parameters: totalInputSelector, submitBtnSelector, options
    const distribution = new BranchDistribution(
        '#form-total-amount',
        '#summary-submit',
        { isOptional: false }
    );

    // 4. Initialize Item Repeater
    // Parameters: tbody, cards, totalInput, totalDisplay, onTotalChange
    new ItemRepeater(
        '#items-tbody',
        '#items-cards',
        '#form-total-amount',
        '#display-total-items',
        (total) => {
            // Callback: Update distribution and final total UI
            distribution.renderDistribution();
            const finalTotalDisplay = document.getElementById('summary-total');
            if (finalTotalDisplay) {
                finalTotalDisplay.textContent = 'Rp ' + Math.round(total).toLocaleString('id-ID');
            }
        }
    );

    // 5. Initialize Technician Manager
    if (document.getElementById('technician_id')) {
        new TechnicianManager('#technician_id', '#technician_bank_account_id', '#technician_bank_container');
        console.log('[Pembelian] TechnicianManager initialized');
        console.log('[Pembelian] Elements found:', {
            technicianSelect: !!document.getElementById('technician_id'),
            bankSelect: !!document.getElementById('technician_bank_account_id'),
            bankContainer: !!document.getElementById('technician_bank_container')
        });
    } else {
        console.log('[Pembelian] Technician select not found - skipping TechnicianManager');
    }

    // 6. Initialize Payment Method Manager (Parity with Rembush)
    new PaymentMethodManager('#payment_method', '#bank_details_section');

    // 6. Form Submission Logic
    form.addEventListener('submit', function (e) {
        const submitBtn = document.getElementById('summary-submit');
        const submitText = document.getElementById('submit-text');
        const submitSpinner = document.getElementById('submit-spinner');

        if (submitBtn) submitBtn.disabled = true;
        if (submitText) submitText.textContent = 'Menyimpan...';
        if (submitSpinner) submitSpinner.classList.remove('hidden');
    });

    // 7. Initialize Lucide Icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
