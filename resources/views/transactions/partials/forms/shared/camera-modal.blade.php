{{-- WebRTC CAMERA MODAL --}}
<div id="camera-modal" 
    class="fixed inset-0 z-[9999] hidden items-center justify-center p-4 md:p-6 transition-all duration-300 opacity-0"
    role="dialog" aria-modal="true">
    
    {{-- Backdrop with Blur --}}
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-md"></div>
    
    {{-- Camera Container --}}
    <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col transform scale-95 transition-transform duration-300" 
        id="camera-container">
        
        {{-- Header --}}
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-white/80 backdrop-blur-sm sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                    <i data-lucide="camera" class="w-5 h-5 text-emerald-600"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Ambil Foto Nota</h3>
                    <p class="text-[10px] text-slate-500 font-medium uppercase tracking-tight">Pastikan pencahayaan cukup dan teks terbaca jelas</p>
                </div>
            </div>
            <button type="button" id="close-camera" class="w-10 h-10 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors cursor-pointer">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        {{-- Camera Viewport --}}
        <div class="relative aspect-[4/3] md:aspect-video bg-slate-900 flex items-center justify-center overflow-hidden">
            {{-- Video Stream --}}
            <video id="camera-video" autoplay playsinline class="w-full h-full object-cover"></video>
            
            {{-- Capture Canvas (Hidden) --}}
            <canvas id="camera-canvas" class="hidden"></canvas>
            
            {{-- Captured Preview (Hidden by default) --}}
            <div id="captured-preview-container" class="absolute inset-0 hidden bg-slate-900 z-20">
                <img id="captured-image" src="" class="w-full h-full object-contain" />
            </div>

            {{-- Camera Guides --}}
            <div id="camera-guides" class="absolute inset-0 pointer-events-none border-[20px] md:border-[40px] border-black/20 flex items-center justify-center">
                <div class="w-full h-full border-2 border-dashed border-white/40 rounded-xl"></div>
            </div>

            {{-- Loading State --}}
            <div id="camera-loading" class="absolute inset-0 bg-slate-900 flex flex-col items-center justify-center text-white z-30">
                <div class="w-12 h-12 border-4 border-white/20 border-t-emerald-500 rounded-full animate-spin mb-4"></div>
                <p class="text-xs font-bold uppercase tracking-widest">Memulai Kamera...</p>
            </div>
        </div>

        {{-- Controls --}}
        <div class="p-6 md:p-8 bg-slate-50 border-t border-slate-100 relative overflow-hidden">
            {{-- Capture Button State --}}
            <div id="controls-capture" class="flex items-center justify-center gap-8 md:gap-12 relative z-10">
                {{-- Switch Camera (Mobile) --}}
                <button type="button" id="switch-camera" class="w-12 h-12 rounded-full bg-white border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-100 transition-all shadow-sm active:scale-95 cursor-pointer" title="Ganti Kamera">
                    <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                </button>

                {{-- Shutter Button --}}
                <div class="relative group">
                    <div class="absolute -inset-4 bg-emerald-500/20 rounded-full blur-xl group-hover:bg-emerald-500/30 transition-all"></div>
                    <button type="button" id="capture-photo" class="relative w-20 h-20 rounded-full border-4 border-white bg-emerald-500 text-white flex items-center justify-center shadow-2xl hover:bg-emerald-600 transition-all active:scale-90 cursor-pointer">
                        <i data-lucide="camera" class="w-8 h-8"></i>
                    </button>
                </div>

                {{-- Empty Spacer for alignment if not mobile, or gallery icon maybe? --}}
                <div class="w-12 h-12 flex items-center justify-center text-slate-400">
                    <i data-lucide="info" class="w-5 h-5 opacity-30"></i>
                </div>
            </div>

            {{-- Result Button State (Confirm/Retake) --}}
            <div id="controls-result" class="hidden flex items-center justify-center gap-4 relative z-10">
                <button type="button" id="retake-photo" class="flex-1 max-w-[160px] py-4 rounded-2xl bg-white border border-slate-200 text-slate-700 font-bold text-sm uppercase tracking-wider hover:bg-slate-50 transition-all active:scale-95 flex items-center justify-center gap-2 cursor-pointer shadow-sm">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    Ulangi
                </button>
                <button type="button" id="use-photo" class="flex-1 max-w-[200px] py-4 rounded-2xl bg-emerald-500 text-white font-bold text-sm uppercase tracking-wider hover:bg-emerald-600 transition-all active:scale-95 flex items-center justify-center gap-2 cursor-pointer shadow-lg shadow-emerald-500/20">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    Gunakan Foto
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    #camera-modal.flex {
        display: flex;
    }
    #camera-modal.opacity-100 #camera-container {
        transform: scale(1);
    }
</style>
