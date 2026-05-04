{{-- VIEW DETAIL MODAL --}}

<div id="view-modal"
    class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black/30 backdrop-blur-md p-0 sm:p-4 opacity-0 transition-all duration-300"
    role="dialog" aria-modal="true" aria-labelledby="view-modal-title">
    <div class="bg-white rounded-none sm:rounded-2xl shadow-2xl max-w-2xl w-full h-[100dvh] sm:h-auto sm:max-h-[90vh] flex flex-col overflow-hidden transform scale-95 transition-all duration-300 overscroll-contain"
        id="view-modal-content">

        <div id="view-loading" class="p-12 text-center w-full flex flex-col items-center justify-center min-h-[50vh]">
            <div class="w-10 h-10 border-4 border-slate-200 border-t-blue-500 rounded-full animate-spin mx-auto mb-4">
            </div>
            <p class="text-sm text-slate-400 font-medium">Memuat detail...</p>
        </div>

        <div id="view-body" class="flex-col flex-auto min-h-0 w-full" style="display: none;">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-white z-10 shrink-0">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900" id="view-modal-title">Detail Pengajuan</h3>
                    <p class="text-xs text-slate-400 font-medium mt-0.5" id="v-invoice"></p>
                </div>
                <button onclick="closeViewModal()"
                    class="p-2 hover:bg-slate-100 rounded-xl transition-colors text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="p-6 space-y-6 overflow-y-auto grow min-h-0 overscroll-contain">
                <div class="flex items-center gap-2 flex-wrap" id="v-badges"></div>

                {{-- ✅ Revisi Banner (Pengajuan yang direvisi Management) --}}
                <div id="v-revision-banner" class="hidden"></div>

                {{-- ✅ UPDATED: Foto/PDF dengan Click-to-Zoom --}}
                <div id="v-image-wrap" class="hidden">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Foto
                        Nota / Dokumen</label>
                    <div id="v-image-wrapper"
                        class="group relative bg-slate-50 border-2 border-slate-100 rounded-2xl p-4 flex items-center justify-center cursor-zoom-in hover:border-emerald-200 transition-all">
                        <img id="v-image" src="" class="max-h-48 object-contain rounded-xl" alt="Nota">

                        {{-- PDF Placeholder in Detail View --}}
                        <div id="v-pdf-icon" class="hidden flex flex-col items-center justify-center py-4">
                            <i data-lucide="file-text" class="w-16 h-16 text-emerald-600 mb-2"></i>
                            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Dokumen PDF
                                Terlampir</span>
                            <p class="text-[10px] text-slate-400 mt-1 uppercase font-bold">Klik untuk melihat detail
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="v-fields"></div>
                <div id="v-items-wrap" class="hidden">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Daftar
                        Barang</label>
                    {{-- Table Container untuk Rembush --}}
                    <div id="v-items-table-container"
                        class="bg-slate-50 border border-slate-200 rounded-xl overflow-hidden hidden">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-blue-50/50 text-slate-500 font-bold uppercase tracking-wider">
                                <tr>
                                    <th class="px-3 py-2">Nama Barang</th>
                                    <th class="px-3 py-2 text-center">QTY</th>
                                    <th class="px-3 py-2">SATUAN</th>
                                    <th class="px-3 py-2 text-right">HARGA SAT.</th>
                                    <th class="px-3 py-2 text-right">TOTAL</th>
                                </tr>
                            </thead>
                            <tbody id="v-items-tbody" class="divide-y divide-slate-100"></tbody>
                        </table>
                    </div>
                    {{-- Div Container untuk Pengajuan (Cards Grid) --}}
                    <div id="v-items-div-container" class="hidden flex-col"></div>
                </div>
                <div id="v-specs-wrap" class="hidden">
                    <label
                        class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Spesifikasi</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3" id="v-specs"></div>
                </div>

                {{-- ✅ Summary Area (Keterangan Global & Total Estimasi) --}}
                <div id="v-summary-wrap" class="hidden mt-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div id="v-summary-desc-wrap"
                            class="md:col-span-2 bg-slate-50 border border-slate-200 rounded-xl p-4 hidden">
                            <label
                                class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Keterangan
                                Global</label>
                            <p class="text-xs font-medium text-slate-700 whitespace-pre-wrap leading-relaxed"
                                id="v-summary-desc"></p>
                        </div>
                        <div id="v-summary-total-wrap"
                            class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-5 flex flex-col justify-center shadow-sm">
                            <label
                                class="block text-[10px] font-bold text-blue-800/60 uppercase tracking-wider mb-1" id="v-summary-total-label">Total
                                Estimasi</label>
                            <p class="text-xl md:text-2xl font-black text-blue-700 tracking-tight flex items-baseline"
                                id="v-summary-total"></p>
                        </div>
                    </div>
                </div>

                <div id="v-branches-wrap" class="hidden">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Pembagian
                        Cabang</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="v-branches"></div>
                </div>
                <div id="v-rejection-wrap" class="hidden">
                    <div class="bg-red-50 border border-red-100 rounded-xl p-4">
                        <p class="text-[10px] font-bold text-red-400 uppercase tracking-wider mb-1">Alasan Penolakan
                        </p>
                        <p class="text-sm text-red-700 font-medium" id="v-rejection"></p>
                    </div>
                </div>
                <div id="v-waiting-owner" class="hidden bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <div class="flex items-center gap-2 text-amber-700">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                        <p class="text-xs font-bold">Menunggu persetujuan dari Owner (nominal ≥ Rp 1.000.000)</p>
                    </div>
                </div>
                <div id="v-reviewer-wrap"
                    class="hidden items-center gap-2 text-xs text-slate-400 pt-2 border-t border-slate-100">
                    <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                    <span>Direview oleh <strong id="v-reviewer" class="text-slate-600"></strong> pada <span
                            id="v-reviewed-at"></span></span>
                </div>

                <!-- Riwayat Pembayaran (Payment History) -->
                <div id="v-payment-history-wrap" class="hidden mt-6 pt-6 border-t border-slate-100 bg-white">
                    <h4 class="text-lg font-black text-slate-800 mb-6">Riwayat Pembayaran</h4>

                    <!-- Main Card Container -->
                    <div class="bg-slate-100 border border-slate-100 rounded-3xl shadow-sm p-5 sm:p-6">
                        <div
                            class="space-y-10 relative before:absolute before:left-[7px] before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-100">

                            <!-- Step 1: Penyerahan / Upload -->
                            <div class="relative pl-8">
                                <div
                                    class="absolute left-0 top-1.5 w-3.5 h-3.5 bg-blue-600 rounded-full border-2 border-white shadow-[0_0_0_1px_rgba(37,99,235,0.2)]">
                                </div>

                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                    <div class="space-y-1.5">
                                        <h5 id="v-pay-step1-title"
                                            class="text-xs font-black text-slate-800 uppercase tracking-widest">
                                            BUKTI TRANSFER DIUNGGAH</h5>

                                        <div
                                            class="flex items-center gap-1.5 px-3 py-1 bg-slate-50 text-slate-500 rounded-full w-fit border border-slate-100">
                                            <i data-lucide="calendar" class="w-3 h-3"></i>
                                            <span id="v-pay-step1-at"
                                                class="text-[10px] font-black uppercase tracking-tight"></span>
                                        </div>

                                        <p class="text-xs text-slate-400 font-medium">
                                            Oleh: <span id="v-pay-step1-by" class="font-bold text-slate-600"></span>
                                            <span class="mx-1 opacity-50">•</span>
                                            Role: <span id="v-pay-step1-role" class="font-bold text-slate-600"></span>
                                        </p>
                                    </div>

                                    <!-- Action Button Step 1 -->
                                    <div id="v-pay-step1-action-wrap"></div>
                                </div>
                            </div>

                            <!-- Step 2: Penerimaan -->
                            <div class="relative pl-8" id="v-pay-step2-wrap">
                                <div
                                    class="absolute left-0 top-1.5 w-3.5 h-3.5 bg-emerald-500 rounded-full border-2 border-white shadow-[0_0_0_1px_rgba(16,185,129,0.2)]">
                                </div>

                                <div class="space-y-1.5">
                                    <h5 class="text-xs font-black text-slate-800 uppercase tracking-widest">
                                        PEMBAYARAN DITERIMA</h5>

                                    <div
                                        class="flex items-center gap-1.5 px-3 py-1 bg-slate-50 text-slate-500 rounded-full w-fit border border-slate-100">
                                        <i data-lucide="calendar" class="w-3 h-3"></i>
                                        <span id="v-pay-step2-at"
                                            class="text-[10px] font-black uppercase tracking-tight"></span>
                                    </div>

                                    <p class="text-xs text-slate-400 font-medium">
                                        Oleh: <span id="v-pay-step2-by" class="font-bold text-slate-600"></span>
                                        <span class="mx-1 opacity-50">•</span>
                                        Role: <span id="v-pay-step2-role" class="font-bold text-slate-600"></span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Black Summary Box -->
                        <div
                            class="mt-8 bg-[#0F172A] rounded-2xl p-6 text-white relative overflow-hidden group shadow-xl">
                            <!-- Subtle Grid Background -->
                            <div class="absolute inset-0  pointer-events-none"
                                style="background-image: radial-gradient(#000000 1px, #000000  1px); background-size: 20px 20px;">
                            </div>

                            <div class="relative z-10 space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-1">
                                        <span
                                            class="block text-[9px] font-black text-slate-500 uppercase tracking-[0.2em]">TOTAL
                                            DIBAYARKAN</span>
                                        <div id="v-pay-summary-amount"
                                            class="text-3xl sm:text-4xl font-black text-emerald-400 tracking-tighter">
                                        </div>
                                        <div id="v-pay-summary-discrepancy"
                                            class="text-[10px] font-bold mt-1 px-2 py-0.5 rounded-lg hidden uppercase tracking-wider">
                                        </div>
                                    </div>
                                    <div id="v-pay-method-wrap" class="space-y-1 md:text-right hidden">
                                        <span
                                            class="block text-[9px] font-black text-slate-500 uppercase tracking-[0.2em]">METODE
                                            PENCAIRAN</span>
                                        <div id="v-pay-summary-method"
                                            class="text-lg font-black text-white tracking-tight uppercase"></div>
                                        <div id="v-pay-summary-account"
                                            class="text-[10px] font-bold text-slate-400 mt-1 leading-relaxed"></div>
                                    </div>
                                </div>

                                <div class="pt-6 border-t border-slate-800 flex items-center gap-2">
                                    <div class="w-6 h-6 bg-emerald-500/10 rounded-lg flex items-center justify-center">
                                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-emerald-400"></i>
                                    </div>
                                    <p class="text-[10px] font-bold text-slate-400 tracking-wide">
                                        Otomatis diverifikasi & divalidasi oleh <span class="text-white">AI</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="v-actions" class="hidden pt-2 border-t border-slate-100">
                    <button id="v-btn-reset" onclick="submitApproval('pending')"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold text-xs transition-all border border-slate-200">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Reset ke Pending
                    </button>
                </div>
            </div>

            <div class="p-6 border-t border-gray-100 bg-slate-50/50 shrink-0">
                <button onclick="closeViewModal()"
                    class="w-full py-3 bg-slate-800 text-white rounded-xl font-bold text-sm hover:bg-slate-700 transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>