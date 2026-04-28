{{-- REJECT MODAL --}}

<div id="reject-modal"
    class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] flex items-center justify-center opacity-0 transition-all duration-300">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 transform scale-95 transition-all duration-300">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-red-100 rounded-xl">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-red-600"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Tolak Nota</h3>
                    <p class="text-xs text-slate-500">Nota: <strong id="reject-modal-invoice"></strong></p>
                </div>
            </div>
            <form id="reject-form" method="POST" action="">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="rejected">
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-600 mb-2">
                        Alasan Penolakan <span class="text-red-500">*</span>
                    </label>
                    <textarea name="rejection_reason" rows="3" required placeholder="Tuliskan alasan penolakan..."
                        class="w-full border border-red-200 rounded-xl p-3 text-sm outline-none focus:ring-2 focus:ring-red-100 focus:border-red-300 resize-none"></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeRejectModal()"
                        class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-600 font-bold text-xs uppercase tracking-wider hover:bg-slate-200 transition-all border border-slate-200">
                        Batal
                    </button>
                    <button type="submit"
                        class="flex-1 py-3 rounded-xl bg-red-600 text-white font-bold text-xs uppercase tracking-wider hover:bg-red-700 transition-all shadow-lg shadow-red-600/20">
                        Konfirmasi Tolak
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>