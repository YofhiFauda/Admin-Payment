{{-- ══════════════════════════════════════════════════ --}}
{{-- IMAGE VIEWER MODAL --}}
{{-- hidden → flex saat dibuka via JS --}}
{{-- ══════════════════════════════════════════════════ --}}
<div id="image-viewer"
    class="fixed inset-0 bg-black/90 backdrop-blur-md hidden items-center justify-center z-[9999] p-4 sm:p-10 overscroll-contain"
    role="dialog" aria-modal="true" aria-label="Preview foto referensi">

    {{-- Container margin sisi 4 --}}
    <div class="w-full h-full max-w-4xl bg-white rounded-2xl flex flex-col p-4 sm:p-8 shadow-2xl relative overflow-hidden"
        id="viewer-card">

        {{-- Header & Close Button --}}
        <div class="flex justify-between items-center shrink-0 mb-6 border-b border-slate-100 pb-4">
            <div>
                <h3 class="text-sm sm:text-base font-black text-slate-800 uppercase tracking-widest"
                    id="viewer-header-title">PREVIEW FOTO</h3>
                <p class="text-[10px] text-slate-400 font-bold mt-1 uppercase tracking-wider" id="viewer-title">Klik
                    di luar gambar ruang ini atau X untuk menutup</p>
            </div>
            <button id="close-viewer" type="button"
                class="w-10 h-10 sm:w-12 sm:h-12 flex items-center justify-center rounded-2xl bg-slate-100 hover:bg-red-50 text-slate-500 hover:text-red-500 transition-all active:scale-95"
                aria-label="Tutup preview">
                <i data-lucide="x" class="w-5 h-5 sm:w-6 sm:h-6"></i>
            </button>
        </div>

        {{-- Gambar/PDF Wrapper dengan Background Grid/Dots --}}
        <div
            class="w-full flex-1 flex justify-center items-center bg-slate-50 rounded-2xl overflow-hidden relative border-2 border-slate-100 p-2 sm:p-4">
            <div class="absolute inset-0 opacity-[0.03]"
                style="background-image: radial-gradient(#000 1px, transparent 1px); background-size: 20px 20px;">
            </div>
            <img id="viewer-image" src=""
                class="relative z-10 max-w-full max-h-full object-contain drop-shadow-2xl rounded-lg"
                alt="Preview foto referensi" />

            {{-- PDF Viewer Iframe --}}
            <iframe id="viewer-pdf" class="hidden relative z-10 w-full h-full rounded-lg border-0" src=""></iframe>
        </div>

        {{-- Footer for PDF Actions --}}
        <div id="viewer-footer" class="mt-6 flex justify-center hidden">
            <a id="viewer-pdf-link" href="#" target="_blank"
                class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-xl text-xs font-bold transition-all shadow-lg shadow-emerald-600/20 active:scale-95">
                <i data-lucide="external-link" class="w-4 h-4"></i>
                BUKA DI TAB BARU / DOWNLOAD
            </a>
        </div>
    </div>
</div>
