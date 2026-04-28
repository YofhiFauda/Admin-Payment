{{-- BRANCH DEBT SETTLEMENT MODAL --}}
<div id="branch-debt-modal"
    class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] flex items-center justify-center opacity-0 transition-all duration-300">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 transform scale-95 transition-all duration-300">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-4 border-b border-slate-100 pb-4">
                <div class="p-2 bg-red-100 rounded-xl">
                    <i data-lucide="receipt" class="w-5 h-5 text-red-600"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Upload Bukti Pembayaran Hutang</h3>
                    <p class="text-[11px] text-slate-500 font-medium mt-0.5">Antar Cabang</p>
                </div>
            </div>
            <form id="branch-debt-form" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-600 mb-2">
                        Unggah Foto Bukti Transfer/Cash <span class="text-red-500">*</span>
                    </label>
                    <input type="file" name="payment_proof" id="branch_debt_file_input" required
                        accept=".jpg,.jpeg,.png,.pdf"
                        class="w-full border border-red-200 rounded-xl p-2 text-sm outline-none focus:ring-2 focus:ring-red-100 focus:border-red-300 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100 transition-all cursor-pointer bg-white">
                    <p class="mt-1 text-[11px] text-slate-400 font-medium">Format: JPG, PNG, PDF. Max 2MB.</p>
                </div>
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">Catatan (Opsional)</label>
                    <textarea name="notes" id="branch_debt_notes" rows="2" placeholder="Catatan pelunasan..."
                        class="w-full border border-slate-200 rounded-lg p-2 text-sm outline-none focus:ring-2 focus:ring-red-100 focus:border-red-300 resize-none"></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeBranchDebtModal()"
                        class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-600 font-bold text-xs uppercase tracking-wider hover:bg-slate-200 transition-all border border-slate-200">
                        Batal
                    </button>
                    <button type="submit" id="btnSubmitBranchDebt"
                        class="flex-1 py-3 rounded-xl bg-red-600 text-white font-bold text-xs uppercase tracking-wider hover:bg-red-700 transition-all shadow-lg shadow-red-600/20 relative flex items-center justify-center">
                        <span id="btnSubmitBranchDebtText">Upload & Simpan</span>
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin absolute hidden"
                            id="btnSubmitBranchDebtLoader"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>