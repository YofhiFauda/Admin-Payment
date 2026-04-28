import { Uploader } from './uploader.js';
import { BranchDistribution } from './distribution.js';
import { ItemRepeater } from './item-repeater.js';

document.addEventListener('DOMContentLoaded', () => {
    // Check if we are on the form-pengajuan page
    const pengajuanForm = document.getElementById('pengajuan-form');
    if (!pengajuanForm) return;

    // 1. Initialize Uploader & Image Viewer
    new Uploader();

    // 2. Initialize Branch Distribution
    const distribution = new BranchDistribution(
        '#form-total-estimated-price', 
        '#summary-submit',
        { isOptional: false, tolerance: 2 }
    );

    // 3. Initialize Item Repeater (pass callback to update distribution when total changes)
    new ItemRepeater(
        '#items-container',
        '#item-template',
        '#form-total-estimated-price',
        '#total-estimate-global',
        () => distribution.renderDistribution() // Re-render distribution when totals change
    );

    // Form Submission & Validation Fixes
    pengajuanForm.addEventListener('invalid', (e) => {
        const invalidField = e.target;
        const itemBody = invalidField.closest('.item-body');

        if (itemBody && itemBody.classList.contains('hidden')) {
            itemBody.classList.remove('hidden');
            const card = itemBody.closest('.item-card');
            if (card) {
                const icon = card.querySelector('.icon-collapse');
                if (icon) icon.classList.remove('rotate-180');
                setTimeout(() => card.scrollIntoView({ behavior: 'smooth', block: 'center' }), 100);
            }
        }
    }, true);

    pengajuanForm.addEventListener('submit', (e) => {
        const totalAmountInput = document.getElementById('form-total-estimated-price');
        const summarySubmit = document.getElementById('summary-submit');
        const totalAmount = parseInt(totalAmountInput?.value) || 0;
        
        if (totalAmount <= 0) {
            e.preventDefault();
            alert('Total estimasi tidak boleh Rp 0. Silakan isi harga barang.');
            return;
        }

        if (summarySubmit && summarySubmit.disabled) {
            e.preventDefault();
            return;
        }
        
        if(summarySubmit) summarySubmit.disabled = true;
        
        const submitText = document.getElementById('submit-text');
        const submitSpinner = document.getElementById('submit-spinner');
        if(submitText) submitText.textContent = 'Memproses...';
        if(submitSpinner) submitSpinner.classList.remove('hidden');
    });
});
