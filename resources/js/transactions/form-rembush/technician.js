export class TechnicianManager {
    constructor(technicianSelectSelector, bankSelectSelector, bankContainerSelector) {
        this.technicianSelect = document.querySelector(technicianSelectSelector);
        this.bankSelect = document.querySelector(bankSelectSelector);
        this.bankContainer = document.querySelector(bankContainerSelector);

        this.init();
    }

    init() {
        if (!this.technicianSelect) return;

        this.technicianSelect.addEventListener('change', () => {
            const selectedOption = this.technicianSelect.options[this.technicianSelect.selectedIndex];
            const accounts = JSON.parse(selectedOption.getAttribute('data-accounts') || '[]');
            
            // Clear existing
            this.bankSelect.innerHTML = '<option value="">-- Pilih Rekening (Opsional) --</option>';
            
            if (accounts.length > 0) {
                accounts.forEach(acc => {
                    const opt = document.createElement('option');
                    opt.value = acc.id;
                    opt.textContent = `${acc.bank_name} - ${acc.account_number} (${acc.account_name})`;
                    this.bankSelect.appendChild(opt);
                });
            } else {
                const opt = document.createElement('option');
                opt.disabled = true;
                opt.textContent = 'Teknisi ini belum memiliki rekening.';
                this.bankSelect.appendChild(opt);
            }
            
            this.bankContainer.classList.remove('opacity-50', 'pointer-events-none');
        });
        
        // Trigger on load if there's an old value
        if (this.technicianSelect.value) {
            this.technicianSelect.dispatchEvent(new Event('change'));
        }
    }
}
