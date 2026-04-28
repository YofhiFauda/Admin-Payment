{{-- PAYMENT UPLOAD MODAL --}}
<div id="payment-modal"
    class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] flex items-center justify-center opacity-0 transition-all duration-300 overflow-y-auto pt-10 pb-10">
    <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 transform scale-95 transition-all duration-300 mt-10 mb-auto">

        <div id="payment-loading"
            class="p-12 text-center w-full flex flex-col items-center justify-center min-h-[50vh]">
            <div class="w-10 h-10 border-4 border-slate-200 border-t-cyan-500 rounded-full animate-spin mx-auto mb-4">
            </div>
            <p class="text-sm text-slate-400 font-medium">Memuat detail...</p>
        </div>

        <div id="payment-body" class="p-6 hidden">
            <div class="flex flex-col gap-1 mb-6 border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-cyan-100 rounded-xl">
                        <i data-lucide="image" class="w-5 h-5 text-cyan-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900" id="payment-modal-title">Upload Bukti
                            Transfer/Cash</h3>
                        <p class="text-xs text-slate-500">Nota: <strong id="payment-modal-invoice"></strong></p>
                    </div>
                </div>
            </div>

            {{-- INSERT DETAILS HERE --}}
            <div id="p-detail-container" class="mb-6 space-y-4 border-b border-slate-100 pb-6 hidden">
                <div class="flex items-center gap-2 flex-wrap" id="p-badges"></div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="p-fields"></div>

                <div id="p-items-wrap" class="hidden">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Daftar
                        Barang</label>
                    {{-- Table Container untuk Rembush --}}
                    <div id="p-items-table-container"
                        class="bg-slate-50 border border-slate-200 rounded-xl overflow-hidden hidden">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-100/50 text-slate-500 font-bold uppercase tracking-wider">
                                <tr>
                                    <th class="px-3 py-2">Nama Barang</th>
                                    <th class="px-3 py-2 text-center">Qty</th>
                                    <th class="px-3 py-2">Satuan</th>
                                    <th class="px-3 py-2 text-right">Harga Sat.</th>
                                    <th class="px-3 py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody id="p-items-tbody" class="divide-y divide-slate-100"></tbody>
                        </table>
                    </div>
                    {{-- Div Container untuk Pengajuan (Cards Grid) --}}
                    <div id="p-items-div-container" class="hidden flex-col"></div>
                </div>

                <div id="p-branches-wrap" class="hidden">
                    <label
                        class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider mt-4">Pembagian
                        Cabang</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="p-branches"></div>
                </div>
            </div>

            <div
                class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 mb-4 flex flex-col justify-center shadow-sm">
                <label class="block text-[10px] font-bold text-blue-800/60 uppercase tracking-wider mb-1">Tagihan
                    Pembayaran</label>
                <p class="text-lg md:text-xl font-black text-blue-700 tracking-tight flex items-baseline"
                    id="payment-modal-amount"></p>
            </div>

            <form id="payment-form" method="POST" action="" enctype="multipart/form-data">
                @csrf

                {{-- DYNAMIC FILE INPUT --}}
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-600 mb-2" id="payment-modal-label">
                        Unggah Foto / Screenshot <span class="text-red-500">*</span>
                    </label>
                    <input type="file" name="file" id="payment_file_input" required accept=".jpg,.jpeg,.png,.pdf"
                        class="w-full border border-cyan-200 rounded-xl p-2 text-sm outline-none focus:ring-2 focus:ring-cyan-100 focus:border-cyan-300 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100 transition-all cursor-pointer bg-white">
                    <p class="mt-1 text-[11px] text-slate-400 font-medium" id="payment-modal-help">Format: JPG, PNG,
                        PDF. Max 2MB.</p>

                    {{-- Real-time File Preview --}}
                    <div id="payment-file-preview"
                        class="hidden mt-3 p-3 bg-slate-50 border-2 border-dashed border-slate-200 rounded-2xl transition-all">
                        <div class="flex items-center gap-4">
                            <div id="preview-placeholder"
                                class="w-20 h-20 bg-white rounded-xl border border-slate-100 flex items-center justify-center overflow-hidden shrink-0 shadow-sm relative group">
                                {{-- Image or PDF Icon will be here --}}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p id="preview-filename" class="text-xs font-bold text-slate-700 truncate mb-0.5">
                                </p>
                                <p id="preview-filesize"
                                    class="text-[10px] text-slate-400 font-bold uppercase tracking-wider"></p>
                                <button type="button" onclick="resetPaymentFileInput()"
                                    class="mt-2 text-[10px] font-bold text-red-500 hover:text-red-600 flex items-center gap-1 transition-colors uppercase tracking-widest outline-none">
                                    <i data-lucide="trash-2" class="w-3 h-3"></i> Hapus File
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- METODE PEMBAYARAN (Cash / Rekening) --}}
                <div id="payment-method-container" class="mb-4 hidden">
                    <label class="block text-xs font-bold text-slate-600 mb-2">Metode Pembayaran <span
                            class="text-red-500">*</span></label>
                    <select name="payment_method" id="payment_method_select"
                        class="w-full border border-slate-200 rounded-xl p-2.5 text-sm font-bold text-slate-700 outline-none focus:ring-4 focus:ring-cyan-100 focus:border-cyan-300 transition-all bg-white">
                        <option value="cash">Cash (Tunai)</option>
                        <option value="transfer">Rekening (Transfer)</option>
                    </select>
                </div>

                {{-- TRANSFER FIELDS (Hidden by default) --}}
                <div id="transfer-fields" class="hidden space-y-4 mb-5 border-t border-slate-100 pt-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1 uppercase tracking-wider">Metode
                            Transfer</label>
                        <div id="transfer-method-badge"
                            class="inline-block px-2 py-1 bg-slate-100 text-slate-600 text-[10px] font-bold rounded">
                            TRANSFER</div>
                    </div>

                    {{-- Bank Account Selection --}}
                    <div id="saved-accounts-container" class="hidden">
                        <label class="block text-xs font-bold text-indigo-600 mb-1.5">Pilih Rekening
                            Tersimpan</label>
                        <select id="saved_bank_account" onchange="autoFillBankAccount(this)"
                            class="w-full border-2 border-indigo-100 rounded-xl p-2.5 text-sm font-bold text-slate-700 outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-300 transition-all bg-indigo-50/30">
                            <option value="">-- Pilih Rekening --</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Bank Tujuan <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="rekening_bank" id="transfer_bank"
                            placeholder="Contoh: BCA / Mandiri / GoPay" required
                            class="w-full border border-slate-200 rounded-lg p-2.5 text-sm outline-none focus:ring-2 focus:ring-cyan-100 focus:border-cyan-300">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Nomor Rekening <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="rekening_nomor" id="transfer_nomor"
                                placeholder="Contoh: 0987654321" required inputmode="numeric"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                class="w-full border border-slate-200 rounded-lg p-2.5 text-sm outline-none focus:ring-2 focus:ring-cyan-100 focus:border-cyan-300">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Atas Nama <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="rekening_nama" id="transfer_nama"
                                placeholder="Contoh: Nama Pemilik" required
                                class="w-full border border-slate-200 rounded-lg p-2.5 text-sm outline-none focus:ring-2 focus:ring-cyan-100 focus:border-cyan-300">
                        </div>
                    </div>
                    <div id="transfer-profile-alert"
                        class="hidden text-[10px] text-emerald-600 bg-emerald-50 border border-emerald-100 p-2 rounded-lg flex items-start gap-1.5 mt-2">
                        <i data-lucide="info" class="w-3.5 h-3.5 flex-shrink-0 mt-0.5"></i>
                        <span>Rekening ini akan disimpan ke dalam Profil Teknisi untuk transaksi berikutnya.</span>
                    </div>
                </div>

                {{-- CASH FIELDS (Hidden by default) --}}
                <div id="cash-fields" class="hidden space-y-4 mb-5 border-t border-slate-100 pt-4">
                    <div class="p-3 bg-amber-50 border border-amber-100 rounded-lg flex items-start gap-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600 mt-0.5"></i>
                        <div class="text-[11px] text-amber-800 font-medium">
                            Pastikan foto yang diunggah menampilkan wajah <strong>Teknisi</strong> dan <strong>Uang
                                Tunai</strong> secara jelas sebagai bukti penyerahan.
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Catatan Tambahan
                            (Opsional)</label>
                        <textarea name="catatan" id="cash_catatan" rows="2"
                            placeholder="Cth: Uang diserahkan ke teknisi A..."
                            class="w-full border border-slate-200 rounded-lg p-2 text-sm outline-none focus:ring-2 focus:ring-cyan-100 focus:border-cyan-300 resize-none"></textarea>
                    </div>
                </div>

                {{-- PENGAJUAN INVOICE FIELDS (Hidden by default) --}}
                <div id="pengajuan-invoice-fields" class="hidden space-y-4 mb-5 border-t border-slate-100 pt-4">
                    <div class="p-3 bg-teal-50 border border-teal-100 rounded-lg flex items-start gap-2 mb-4">
                        <i data-lucide="info" class="w-4 h-4 text-teal-600 mt-0.5"></i>
                        <div class="text-[11px] text-teal-800 font-medium">
                            Pilih cabang <strong>Sumber Dana</strong> dan masukkan nominal yang dibayarkan. Cabang
                            yang tidak dipilih otomatis <strong class="text-red-600">berhutang</strong>.
                        </div>
                    </div>

                    {{-- Multi Sumber Dana Section --}}
                    <div class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-sm">
                        <div class="px-5 py-4 border-b border-slate-50">
                            <label class="text-xs font-black text-slate-800 uppercase tracking-widest">Rincian
                                Sumber Dana <span class="text-red-500">*</span></label>
                        </div>
                        <div id="p_sumber_dana_container" class="p-5 space-y-4">
                            {{-- Dynamically populated by JS --}}
                        </div>

                        <div id="p_sumber_dana_total"
                            class="px-6 py-5 bg-slate-50/50 border-t border-slate-100 flex justify-between items-center hidden">
                            <div class="space-y-1">
                                <span
                                    class="block text-xs font-black text-slate-800 uppercase tracking-widest leading-none">Total
                                    Sumber Dana</span>
                                <div id="p_sumber_dana_diff" class="text-[10px] font-bold tracking-tight"></div>
                            </div>
                            <span id="p_sumber_dana_total_value"
                                class="text-2xl font-black text-teal-600 tracking-tighter">Rp 0</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Ongkir</label>
                            <div class="relative">
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                <input type="text" name="ongkir" id="p_ongkir" placeholder="0"
                                    class="nominal-input w-full pl-8 pr-3 py-2.5 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-300">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Diskon Pengiriman</label>
                            <div class="relative">
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                <input type="text" name="diskon_pengiriman" id="p_diskon_pengiriman" placeholder="0"
                                    class="nominal-input w-full pl-8 pr-3 py-2.5 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-300">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Voucher Diskon</label>
                            <div class="relative">
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                <input type="text" name="voucher_diskon" id="p_voucher_diskon" placeholder="0"
                                    class="nominal-input w-full pl-8 pr-3 py-2.5 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-300">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">DPP Lainnya</label>
                            <div class="relative">
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                <input type="text" name="dpp_lainnya" id="p_dpp_lainnya" placeholder="0"
                                    class="nominal-input w-full pl-8 pr-3 py-2.5 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-300">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">PPN</label>
                            <div class="relative">
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                <input type="text" name="tax_amount" id="p_tax_amount" placeholder="0"
                                    class="nominal-input w-full pl-8 pr-3 py-2.5 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-300">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Biaya Layanan 1</label>
                            <div class="relative">
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                <input type="text" name="biaya_layanan_1" id="p_biaya_layanan_1" placeholder="0"
                                    class="nominal-input w-full pl-8 pr-3 py-2.5 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-300">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Biaya Layanan 2</label>
                            <div class="relative">
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                <input type="text" name="biaya_layanan_2" id="p_biaya_layanan_2" placeholder="0"
                                    class="nominal-input w-full pl-8 pr-3 py-2.5 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-300">
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Catatan (Opsional)</label>
                        <textarea name="catatan" id="p_catatan" rows="2" placeholder="Cth: Pembayaran via Invoice..."
                            class="w-full border border-slate-200 rounded-lg p-2 text-sm outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-300 resize-none"></textarea>
                    </div>

                    {{-- Debt Preview --}}
                    <div id="p_debt_preview"
                        class="hidden border border-red-100 rounded-2xl overflow-hidden mt-6 shadow-sm">
                        <div class="bg-red-50/50 px-4 py-3.5 border-b border-red-100 flex items-center gap-2.5">
                            <div class="w-6 h-6 rounded-full bg-white flex items-center justify-center shadow-sm">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-red-600"></i>
                            </div>
                            <span class="text-[11px] font-black text-red-700 uppercase tracking-widest">Preview
                                Hutang Otomatis</span>
                        </div>
                        <div id="p_debt_preview_list" class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4 bg-white/50">
                        </div>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="closePaymentModal()"
                        class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-600 font-bold text-xs uppercase tracking-wider hover:bg-slate-200 transition-all border border-slate-200">
                        Batal
                    </button>
                    <button type="submit" id="btnSubmitPayment"
                        class="flex-1 py-3 rounded-xl bg-cyan-600 text-white font-bold text-xs uppercase tracking-wider hover:bg-cyan-700 transition-all shadow-lg shadow-cyan-600/20 relative">
                        <span id="btnSubmitPaymentText">Upload & Simpan</span>
                        <i data-lucide="loader-2"
                            class="w-4 h-4 animate-spin absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 hidden"
                            id="btnSubmitPaymentLoader"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>