{{-- 1. FOTO REFERENSI --}}
<div class="mb-8 md:mb-10">
    <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-3 tracking-wider">
        Foto Referensi
        @if(isset($base64) || isset($filePath))
            <span class="text-emerald-500">(Dari Upload Sebelumnya)</span>
        @else
            <span class="text-slate-400">(Opsional)</span>
        @endif
    </label>

    {{-- ✅ CONDITIONAL: Show EITHER photo preview OR empty state --}}
    @if((isset($base64) && str_contains($mime ?? '', 'image')) || (isset($filePath) && $filePath))
        {{-- ═══ PHOTO PREVIEW (Base64 or File Path) ═══ --}}
        <div class="border-2 border-emerald-200 rounded-2xl p-2 bg-emerald-50/50 flex justify-center relative overflow-hidden cursor-pointer hover:border-emerald-400 transition-colors group"
            id="ref-photo-wrapper" title="Klik untuk memperbesar">

            @if(isset($base64) && isset($mime) && str_contains($mime, 'image'))
                {{-- ✅ PRIORITAS 1: DATA URI (Base64) - Instant Preview --}}
                <img src="data:{{ $mime }};base64,{{ $base64 }}"
                    class="w-auto h-48 md:h-64 object-contain rounded-xl shadow-sm" alt="Preview Foto Referensi"
                    id="ref-photo-img" />
            @elseif(isset($filePath) && $filePath)
                {{-- ⚠️ FALLBACK: Storage URL (bisa 404 jika symlink belum dibuat) --}}
                <img src="{{ Storage::url($filePath) }}" class="w-auto h-48 md:h-64 object-contain rounded-xl shadow-sm"
                    alt="Preview Foto Referensi" id="ref-photo-img"
                    onerror="this.parentElement.innerHTML='<div class=\'text-red-500 text-sm\'>❌ Gagal memuat foto</div>'" />
            @endif

            {{-- Preview Badge --}}
            <div
                class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm rounded-full px-3 py-1.5 shadow-lg flex items-center gap-1.5 pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity">
                <i data-lucide="expand" class="w-3.5 h-3.5 text-emerald-600"></i>
                <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Perbesar</span>
            </div>

            {{-- Success Indicator --}}
            <div
                class="absolute bottom-3 left-3 bg-emerald-500/90 backdrop-blur-sm rounded-full px-2.5 py-1 shadow-lg flex items-center gap-1.5">
                <i data-lucide="check-circle" class="w-3 h-3 text-white"></i>
                <span class="text-[9px] font-bold text-white uppercase tracking-wider">Foto Terupload</span>
            </div>
        </div>

        {{-- Tips Jika Ada Foto --}}
        <div
            class="mt-4 bg-orange-50 border border-orange-100/50 rounded-xl p-3 md:p-4 flex gap-3 md:gap-4 items-start">
            <div class="bg-orange-100 p-1.5 md:p-2 rounded-lg text-orange-500 shrink-0">
                <i data-lucide="shield-alert" class="w-4 h-4 md:w-5 md:h-5"></i>
            </div>
            <div>
                <h4 class="text-[9px] md:text-[10px] font-bold text-orange-800 uppercase tracking-wider mb-1">Tips
                    Penting</h4>
                <p class="text-[11px] md:text-xs text-orange-600 leading-relaxed">Segera foto nota setelah transaksi.
                    Nota yang lecek atau tinta pudar berisiko tinggi ditolak oleh sistem verifikasi admin.</p>
            </div>
        </div>
    @else
        {{-- ═══ EMPTY STATE (No Photo) ═══ --}}
        {{-- Drag & Drop Upload Area (Khusus Form Create) --}}
        <div id="photo-upload-container"
            class="border-2 border-dashed border-slate-200 rounded-2xl p-8 md:p-12 bg-slate-50/50 hover:bg-slate-50 hover:border-emerald-300 transition-all cursor-pointer relative group flex flex-col items-center justify-center">

            <input type="file" name="reference_photo" id="reference_photo" accept="image/*,application/pdf"
                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" />

            <div id="photo-placeholder" class="text-center flex flex-col items-center pointer-events-none">
                <div class="bg-white p-3 rounded-full shadow-sm mb-3 group-hover:scale-110 transition-transform">
                    <i data-lucide="upload-cloud" class="w-8 h-8 md:w-10 md:h-10 text-slate-400"></i>
                </div>
                <span id="photo-name-display" class="text-xs md:text-sm font-bold text-slate-700 mb-1">Pilih Foto (Klik atau Drag)</span>
                <span class="text-[10px] md:text-xs text-slate-400">Maks. 5MB (JPG, PNG, PDF)</span>
            </div>

            {{-- Hidden Preview Area for New Uploads --}}
            <div id="photo-preview-wrapper" class="hidden relative w-full flex flex-col items-center pointer-events-none mt-2">
                <img id="photo-preview-img" src="" class="max-h-48 md:max-h-64 object-contain rounded-xl shadow-sm z-0" />
                <div id="photo-preview-pdf" class="hidden flex flex-col items-center bg-red-50 p-4 rounded-xl border border-red-100 z-0">
                    <i data-lucide="file-text" class="w-12 h-12 text-red-500 mb-2"></i>
                    <span class="text-xs font-bold text-red-700" id="pdf-name-display">Dokumen PDF</span>
                </div>
            </div>
        </div>

        {{-- Tips Jika Tidak Ada Foto --}}
        <div class="mt-4 bg-amber-50 border border-amber-100/50 rounded-xl p-3 md:p-4 flex gap-3 items-start">
            <div class="bg-amber-100 p-1.5 md:p-2 rounded-lg text-amber-500 shrink-0">
                <i data-lucide="lightbulb" class="w-4 h-4 md:w-5 md:h-5"></i>
            </div>
            <div>
                <h4 class="text-[9px] md:text-[10px] font-bold text-amber-800 uppercase tracking-wider mb-1">Tips
                </h4>
                <p class="text-[11px] md:text-xs text-amber-600 leading-relaxed">
                    Jika memiliki foto/screenshot barang yang ingin dibeli, upload terlebih dahulu.
                    Foto referensi membantu mempercepat proses verifikasi.
                </p>
            </div>
        </div>
    @endif
</div>
