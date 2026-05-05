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
            if (this.methodSelect.value === 'transfer_penjual' || this.methodSelect.value === 'transfer') {
                this.bankDetails.classList.remove('hidden');
                
                const accountNameContainer = document.getElementById('account_name_container');
                const accountNumberContainer = document.getElementById('account_number_container');
                
                if (this.methodSelect.value === 'transfer') {
                    document.getElementById('bank_name')?.setAttribute('required', 'required');
                    document.getElementById('account_name')?.removeAttribute('required');
                    document.getElementById('account_number')?.removeAttribute('required');
                    
                    document.getElementById('account_name_asterisk')?.classList.add('hidden');
                    document.getElementById('account_number_asterisk')?.classList.add('hidden');
                    
                    if(accountNameContainer) accountNameContainer.classList.add('hidden');
                    if(accountNumberContainer) accountNumberContainer.classList.add('hidden');
                } else {
                    this.bankInputs.forEach(input => input.setAttribute('required', 'required'));
                    document.getElementById('account_name_asterisk')?.classList.remove('hidden');
                    document.getElementById('account_number_asterisk')?.classList.remove('hidden');
                    
                    if(accountNameContainer) accountNameContainer.classList.remove('hidden');
                    if(accountNumberContainer) accountNumberContainer.classList.remove('hidden');
                }
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
