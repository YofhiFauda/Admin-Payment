@props([
    'id' => 'confirmModal',
    'title' => 'Konfirmasi Tindakan',
    'message' => 'Apakah Anda yakin ingin melakukan tindakan ini?',
    'action' => '',
    'method' => 'POST',
    'submitText' => 'Ya, Lanjutkan',
    'cancelText' => 'Batal',
    'icon' => 'alert-triangle',
    'iconColor' => 'text-red-500',
    'iconBg' => 'bg-red-50',
    'submitColor' => 'bg-red-500 hover:bg-red-600'
])

<div id="{{ $id }}" class="fixed inset-0 z-[100] hidden modal-container">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm modal-overlay" onclick="closeConfirmModal('{{ $id }}')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="bg-white rounded-3xl shadow-2xl max-w-sm w-full p-6 relative z-10 animate-fade-in-up pointer-events-auto">
            <div class="flex flex-col items-center text-center">
                <div class="w-14 h-14 rounded-2xl {{ $iconBg }} flex items-center justify-center mb-4 modal-icon-container">
                    <i data-lucide="{{ $icon }}" class="w-6 h-6 {{ $iconColor }} modal-icon"></i>
                </div>
                <h3 class="text-lg font-black text-slate-900 mb-2 modal-title">{{ $title }}</h3>
                <p class="text-sm text-slate-500 font-medium mb-6 modal-message">
                    {!! $message !!}
                </p>
                <div class="flex gap-3 w-full">
                    <button type="button" onclick="closeConfirmModal('{{ $id }}')"
                            class="flex-1 px-4 py-3 rounded-xl border border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors modal-cancel">
                        {{ $cancelText }}
                    </button>
                    <form id="{{ $id }}Form" action="{{ $action }}" method="POST" class="flex-1 modal-form">
                        @csrf
                        <div class="method-container">
                            @if(in_array(strtoupper($method), ['PUT', 'PATCH', 'DELETE']))
                                @method($method)
                            @endif
                        </div>
                        <button type="submit"
                                class="w-full px-4 py-3 rounded-xl {{ $submitColor }} text-white text-sm font-bold transition-colors modal-submit flex items-center justify-center gap-2">
                            <span class="submit-text">{{ $submitText }}</span>
                            <svg class="animate-spin h-4 w-4 text-white hidden submit-loader" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@once
<script>
    let modalCallbacks = {};

    function openConfirmModal(id, options = {}) {
        const modal = document.getElementById(id);
        if (!modal) return;

        const form = modal.querySelector('.modal-form');
        const submitBtn = modal.querySelector('.modal-submit');
        const submitText = modal.querySelector('.submit-text');
        const submitLoader = modal.querySelector('.submit-loader');

        // Reset state
        submitBtn.disabled = false;
        submitText.classList.remove('hidden');
        submitLoader.classList.add('hidden');

        if (options.title) modal.querySelector('.modal-title').textContent = options.title;
        if (options.message) modal.querySelector('.modal-message').innerHTML = options.message;
        if (options.action) form.action = options.action;
        
        if (options.method) {
            const methodContainer = modal.querySelector('.method-container');
            const upperMethod = options.method.toUpperCase();
            methodContainer.innerHTML = '';
            if (['PUT', 'PATCH', 'DELETE'].includes(upperMethod)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_method';
                input.value = upperMethod;
                methodContainer.appendChild(input);
            }
        }

        if (options.submitText) submitText.textContent = options.submitText;
        if (options.submitColor) {
            submitBtn.className = `w-full px-4 py-3 rounded-xl ${options.submitColor} text-white text-sm font-bold transition-colors modal-submit flex items-center justify-center gap-2`;
        }

        if (options.icon) {
            const iconEl = modal.querySelector('.modal-icon');
            iconEl.setAttribute('data-lucide', options.icon);
        }

        if (options.iconColor && options.iconBg) {
            const iconContainer = modal.querySelector('.modal-icon-container');
            const iconEl = modal.querySelector('.modal-icon');
            iconContainer.className = `w-14 h-14 rounded-2xl ${options.iconBg} flex items-center justify-center mb-4 modal-icon-container`;
            iconEl.className = `w-6 h-6 ${options.iconColor} modal-icon`;
        }

        if (typeof lucide !== 'undefined') {
            lucide.createIcons({ root: modal });
        }

        // Handle Confirmation
        if (options.onConfirm && typeof options.onConfirm === 'function') {
            modalCallbacks[id] = options.onConfirm;
            form.onsubmit = async (e) => {
                e.preventDefault();
                submitBtn.disabled = true;
                submitText.classList.add('hidden');
                submitLoader.classList.remove('hidden');
                
                try {
                    await modalCallbacks[id]();
                    closeConfirmModal(id);
                } catch (err) {
                    console.error('Modal Action Error:', err);
                    submitBtn.disabled = false;
                    submitText.classList.remove('hidden');
                    submitLoader.classList.add('hidden');
                }
            };
        } else {
            form.onsubmit = null; // Use standard submission
        }

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeConfirmModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    // Close on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-container:not(.hidden)').forEach(modal => {
                closeConfirmModal(modal.id);
            });
        }
    });
</script>
@endonce
