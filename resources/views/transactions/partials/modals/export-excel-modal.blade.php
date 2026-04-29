{{-- ══════════════════════════════════════════════════ --}}
{{-- EXPORT MODAL: Filter Laporan Bulanan --}}
{{-- ══════════════════════════════════════════════════ --}}

<div id="export-modal"
    class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[70] flex items-center justify-center p-4 opacity-0 transition-opacity duration-200"
    role="dialog" aria-modal="true" aria-labelledby="export-modal-title">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform scale-95 transition-transform duration-200"
        id="export-modal-card">

        {{-- Header --}}
        <div class="flex items-center justify-between p-5 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-100 rounded-xl">
                    <i data-lucide="file-spreadsheet" class="w-5 h-5 text-emerald-600"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900" id="export-modal-title">Export Laporan Transaksi
                    </h3>
                    <p class="text-[11px] text-slate-400 mt-0.5 font-medium">Format Excel (.xlsx) · Rumus Otomatis ·
                        Google Sheets Ready</p>
                </div>
            </div>
            <button type="button" onclick="closeExportModal()"
                class="p-2 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        {{-- Body --}}
        <div class="p-5 space-y-4">

            {{-- Period Row: Bulan + Tahun --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label
                        class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1.5">Bulan</label>
                    <select id="export-month"
                        class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                        <option value="">Semua Bulan</option>
                        <option value="1">Januari</option>
                        <option value="2">Februari</option>
                        <option value="3">Maret</option>
                        <option value="4">April</option>
                        <option value="5">Mei</option>
                        <option value="6">Juni</option>
                        <option value="7">Juli</option>
                        <option value="8">Agustus</option>
                        <option value="9">September</option>
                        <option value="10">Oktober</option>
                        <option value="11">November</option>
                        <option value="12">Desember</option>
                    </select>
                </div>
                <div>
                    <label
                        class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1.5">Tahun</label>
                    <select id="export-year"
                        class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                        @for($y = now()->year; $y >= now()->year - 4; $y--)
                            <option value="{{ $y }}" {{ $y === now()->year ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            {{-- Tipe Transaksi --}}
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1.5">Tipe
                    Transaksi</label>
                <div class="grid grid-cols-4 gap-2">
                    <label class="export-type-option cursor-pointer">
                        <input type="radio" name="export_type" value="" class="sr-only" checked>
                        <div class="px-2 py-2.5 rounded-xl text-xs font-bold text-center transition-all border-2 border-slate-200 bg-white text-slate-500 hover:border-slate-400 peer-checked:border-slate-800 peer-checked:bg-slate-800 peer-checked:text-white"
                            data-type="">
                            Semua
                        </div>
                    </label>
                    <label class="export-type-option cursor-pointer">
                        <input type="radio" name="export_type" value="rembush" class="sr-only">
                        <div class="px-2 py-2.5 rounded-xl text-xs font-bold text-center transition-all border-2 border-slate-200 bg-white text-slate-500 hover:border-indigo-400"
                            data-type="rembush">
                            Rembush
                        </div>
                    </label>
                    <label class="export-type-option cursor-pointer">
                        <input type="radio" name="export_type" value="pengajuan" class="sr-only">
                        <div class="px-2 py-2.5 rounded-xl text-xs font-bold text-center transition-all border-2 border-slate-200 bg-white text-slate-500 hover:border-teal-400"
                            data-type="pengajuan">
                            Pengajuan
                        </div>
                    </label>
                    <label class="export-type-option cursor-pointer">
                        <input type="radio" name="export_type" value="gudang" class="sr-only">
                        <div class="px-2 py-2.5 rounded-xl text-xs font-bold text-center transition-all border-2 border-slate-200 bg-white text-slate-500 hover:border-amber-400"
                            data-type="gudang">
                            Pembelian
                        </div>
                    </label>
                </div>
            </div>

            {{-- Status --}}
            <div>
                <label
                    class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1.5">Status</label>
                <select id="export-status"
                    class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                    <option value="">Semua Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Menunggu Approve Owner</option>
                    <option value="waiting_payment">Menunggu Pembayaran</option>
                    <option value="completed">Selesai / Paid</option>
                    <option value="rejected">Ditolak</option>
                </select>
            </div>

            {{-- Cabang --}}
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1.5">Cabang
                    (Opsional)</label>
                <select id="export-branch"
                    class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                    <option value="">Semua Cabang</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Info callout --}}
            <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 flex gap-2.5">
                <i data-lucide="info" class="w-4 h-4 text-blue-400 shrink-0 mt-0.5"></i>
                <div class="text-[11px] font-medium text-blue-600 leading-relaxed">
                    File <strong>Excel (.xlsx)</strong> akan langsung terdownload. Kolom kalkulasi seperti <em>Total
                        Estimasi</em> dan <em>Grand Total</em> menggunakan <strong>rumus Excel</strong>—klik selnya
                    untuk melihat formula. Pengajuan multi-item di-<em>expand</em> per baris.
                </div>
            </div>
        </div>

        {{-- Footer Actions --}}
        <div class="flex gap-3 p-5 pt-0">
            <button type="button" id="btn-cancel-export" onclick="closeExportModal()"
                class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-600 font-bold text-xs uppercase tracking-wider hover:bg-slate-200 transition-all border border-slate-200">
                Batal
            </button>
            <button type="button" id="btn-do-export" onclick="doExport(this)"
                class="flex-[2] py-3 rounded-xl bg-emerald-600 text-white font-bold text-xs uppercase tracking-wider hover:bg-emerald-700 disabled:opacity-70 disabled:cursor-not-allowed transition-all shadow-lg shadow-emerald-600/20 flex items-center justify-center gap-2 active:scale-[0.98]">
                <span id="export-btn-idle" class="flex items-center gap-2">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                    Download Excel
                </span>
                <span id="export-btn-loading" class="hidden flex items-center gap-2">
                    <svg class="animate-spin w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z">
                        </path>
                    </svg>
                    Menyiapkan file...
                </span>
            </button>
        </div>
    </div>
</div>