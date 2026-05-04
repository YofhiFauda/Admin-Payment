export class PaymentMethodManager {
    constructor(methodSelectSelector, bankDetailsSelector) {
        this.methodSelect = document.querySelector(methodSelectSelector);
        this.bankDetails = document.querySelector(bankDetailsSelector);
        this.bankInputs = this.bankDetails ? this.bankDetails.querySelectorAll('input') : [];

        this.init();
    }

    init() {
        if (!this.methodSelect || !this.bankDetails) return;

        const toggleBankDetails = () => {
            if (this.methodSelect.value === 'transfer_penjual') {
                this.bankDetails.classList.remove('hidden');
                this.bankInputs.forEach(input => input.setAttribute('required', 'required'));
            } else {
                this.bankDetails.classList.add('hidden');
                this.bankInputs.forEach(input => input.removeAttribute('required'));
            }
        };

        this.methodSelect.addEventListener('change', toggleBankDetails);
        
        // Enforce Uppercase
        const bankNameInput = document.getElementById('bank_name');
        const accountNameInput = document.getElementById('account_name');

        if (bankNameInput) {
            bankNameInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        }

        if (accountNameInput) {
            accountNameInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        }

        // On init
        toggleBankDetails();
    }
}
