{{-- FORCE APPROVE MODAL --}}
<div id="force-approve-modal"
    class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] flex items-center justify-center opacity-0 transition-all duration-300">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 transform scale-95 transition-all duration-300">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-rose-100 rounded-xl">
                    <i data-lucide="shield-alert" class="w-5 h-5 text-rose-600"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Force Approve</h3>
                    <p class="text-xs text-slate-500">Nota: <strong id="force-approve-modal-invoice"></strong></p>
                </div>
            </div>
            <form id="force-approve-form" method="POST" action="">
                @csrf
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-600 mb-2">
                        Alasan Rekonsiliasi <span class="text-red-500">*</span>
                    </label>
                    <textarea name="force_approve_reason" rows="3" required
                        placeholder="Alasan mengapa disetujui meski nilai beda..."
                        class="w-full border border-rose-200 rounded-xl p-3 text-sm outline-none focus:ring-2 focus:ring-rose-100 focus:border-rose-300 resize-none"></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeForceApproveModal()"
                        class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-600 font-bold text-xs uppercase tracking-wider hover:bg-slate-200 transition-all border border-slate-200">
                        Batal
                    </button>
                    <button type="submit" id="btnSubmitForce"
                        class="flex-1 py-3 rounded-xl bg-rose-600 text-white font-bold text-xs uppercase tracking-wider hover:bg-rose-700 transition-all shadow-lg shadow-rose-600/20">
                        Force Approve
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>