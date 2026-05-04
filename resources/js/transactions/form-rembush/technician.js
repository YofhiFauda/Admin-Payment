export class TechnicianManager {
    constructor(technicianSelectSelector, bankSelectSelector, bankContainerSelector) {
        this.technicianSelect = document.querySelector(technicianSelectSelector);
        this.bankSelect = document.querySelector(bankSelectSelector);
        this.bankContainer = document.querySelector(bankContainerSelector);

        this.init();
    }

    init() {
        if (!this.technicianSelect || !this.bankSelect || !this.bankContainer) {
            return;
        }

        this.technicianSelect.addEventListener('change', () => {
            const val = this.technicianSelect.value;
            
            if (!val) {
                this.bankSelect.innerHTML = '<option value="">-- Pilih Rekening (Opsional) --</option>';
                this.bankContainer.classList.add('opacity-50', 'pointer-events-none');
                return;
            }

            const selectedOption = this.technicianSelect.options[this.technicianSelect.selectedIndex];
            if (!selectedOption) return;

            const accountsRaw = selectedOption.getAttribute('data-accounts');
            let accounts = [];
            try {
                accounts = JSON.parse(accountsRaw || '[]');
            } catch (e) {
                console.error('[TechnicianManager] Failed to parse accounts JSON', e);
            }
            
            // Try to get value to restore: either current value or from data-old (for initial load after validation error)
            const currentValue = this.bankSelect.value || this.bankSelect.dataset.old;
            
            // Clear existing
            this.bankSelect.innerHTML = '<option value="">-- Pilih Rekening (Opsional) --</option>';
            
            if (accounts.length > 0) {
                accounts.forEach(acc => {
                    const opt = document.createElement('option');
                    opt.value = acc.id;
                    opt.textContent = `${acc.bank_name} - ${acc.account_number} (${acc.account_name})`;
                    if (currentValue && acc.id.toString() === currentValue.toString()) {
                        opt.selected = true;
                    }
                    this.bankSelect.appendChild(opt);
                });

                // Auto-select if only one account and no current value to restore
                if (accounts.length === 1 && !currentValue) {
                    this.bankSelect.selectedIndex = 1; // 0 is placeholder
                }
            } else {
                const opt = document.createElement('option');
                opt.value = "";
                opt.disabled = true;
                opt.textContent = 'Teknisi ini belum memiliki rekening.';
                this.bankSelect.appendChild(opt);
            }
            
            this.bankContainer.classList.remove('opacity-50', 'pointer-events-none');

            // Refresh Lucide if available in global scope
            if (typeof window.lucide !== 'undefined') {
                window.lucide.createIcons({ root: this.bankContainer });
            }
        });
        
        // Trigger on load if there's an old value
        if (this.technicianSelect.value) {
            this.technicianSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }
}
