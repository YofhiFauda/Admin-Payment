{{-- transactions/loading.blade.php --}}
@extends('layouts.app')
@section('page-title', 'Memproses Nota')

{{-- Lock scroll immediately (before DOMContentLoaded) to prevent background scroll --}}
<script>
    // Immediate scroll lock - runs before layout paint
    (function() {
        document.documentElement.style.overflow = 'hidden';
        document.body.style.overflow = 'hidden';
    })();
</script>

@section('content')
<div class="fixed inset-0 z-[100] bg-slate-50 overflow-hidden flex flex-col items-center justify-center p-4">
    
    {{-- Atmospheric blobs --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-emerald-400/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-blue-400/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative z-10 w-full max-w-md">
        {{-- Logo --}}
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-slate-800">FinanceOps</h1>
        </div>

        {{-- AI Scanner Visual --}}
        <div class="bg-white rounded-3xl shadow-xl p-8 mb-6 relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-blue-500/5"></div>
            
            {{-- Doc skeleton lines --}}
            <div class="space-y-3 mb-6">
                <div class="h-2 bg-slate-200 rounded animate-pulse"></div>
                <div class="h-2 bg-slate-200 rounded animate-pulse w-5/6"></div>
                <div class="h-2 bg-slate-200 rounded animate-pulse w-4/6"></div>
            </div>

            {{-- Corner brackets --}}
            <div class="absolute top-4 left-4 w-8 h-8 border-l-2 border-t-2 border-emerald-400 rounded-tl-lg"></div>
            <div class="absolute top-4 right-4 w-8 h-8 border-r-2 border-t-2 border-emerald-400 rounded-tr-lg"></div>
            <div class="absolute bottom-4 left-4 w-8 h-8 border-l-2 border-b-2 border-emerald-400 rounded-bl-lg"></div>
            <div class="absolute bottom-4 right-4 w-8 h-8 border-r-2 border-b-2 border-emerald-400 rounded-br-lg"></div>

            {{-- Scan beam --}}
            <div class="absolute inset-x-0 h-1 bg-emerald-400/50 animate-scan"></div>

            {{-- AI badge --}}
            <div class="absolute -top-2 -right-2 bg-emerald-500 text-white px-3 py-1 rounded-full text-xs font-bold shadow-lg">
                AI OCR
            </div>
        </div>

        {{-- Status label --}}
        <div class="text-center mb-6">
            <h2 class="text-lg font-bold text-slate-800 mb-2">Mendeteksi teks nota</h2>
            <p class="text-sm text-slate-500">AI sedang memproses nota Anda</p>
            <p class="text-xs text-slate-400 mt-1">Teknologi OCR cerdas kami mengekstrak data secara otomatis. Harap tunggu sebentar.</p>
        </div>

        {{-- Progress steps --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 mb-6">
            <div class="space-y-3">
                <div class="flex items-center gap-3 text-xs">
                    <div class="w-5 h-5 rounded-full bg-emerald-500 flex items-center justify-center">
                        <i data-lucide="check" class="w-3 h-3 text-white"></i>
                    </div>
                    <span class="text-slate-600">Nota berhasil diunggah</span>
                </div>
                <div class="flex items-center gap-3 text-xs">
                    <div class="w-5 h-5 rounded-full bg-emerald-500 flex items-center justify-center">
                        <i data-lucide="check" class="w-3 h-3 text-white"></i>
                    </div>
                    <span class="text-slate-600">File diterima sistem</span>
                </div>
                <div class="flex items-center gap-3 text-xs">
                    <div class="w-5 h-5 rounded-full bg-emerald-500 animate-pulse flex items-center justify-center">
                        <i data-lucide="scan" class="w-3 h-3 text-white"></i>
                    </div>
                    <span class="text-slate-800 font-medium">Memindai gambar</span>
                </div>
                <div class="flex items-center gap-3 text-xs text-slate-400">
                    <div class="w-5 h-5 rounded-full bg-slate-200 flex items-center justify-center">
                        <span class="text-[10px]">4</span>
                    </div>
                    <span>Praproses resolusi & kontras</span>
                </div>
                <div class="flex items-center gap-3 text-xs text-slate-400">
                    <div class="w-5 h-5 rounded-full bg-slate-200 flex items-center justify-center">
                        <span class="text-[10px]">5</span>
                    </div>
                    <span>Ekstraksi teks AI</span>
                </div>
                <div class="flex items-center gap-3 text-xs text-slate-400">
                    <div class="w-5 h-5 rounded-full bg-slate-200 flex items-center justify-center">
                        <span class="text-[10px]">6</span>
                    </div>
                    <span>Membaca nomor & nominal</span>
                </div>
                <div class="flex items-center gap-3 text-xs text-slate-400">
                    <div class="w-5 h-5 rounded-full bg-slate-200 flex items-center justify-center">
                        <span class="text-[10px]">7</span>
                    </div>
                    <span>Validasi & pengisian form</span>
                </div>
            </div>
        </div>

        {{-- Timeout fallback --}}
        <div id="fallback-container" class="hidden">
            <div class="bg-orange-50 border border-orange-100 rounded-2xl p-4 mb-4">
                <div class="flex items-start gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-orange-500 shrink-0 mt-0.5"></i>
                    <div>
                        <p class="text-xs font-bold text-orange-800 mb-1">Proses memakan waktu lama</p>
                        <p class="text-xs text-orange-600">AI mengalami kesulitan membaca nota Anda. Anda dapat melanjutkan secara manual.</p>
                    </div>
                </div>
            </div>
            <a href="{{ route('rembush.form') }}" class="block w-full bg-slate-800 hover:bg-slate-700 text-white font-bold py-3 px-4 rounded-xl text-sm text-center transition-colors">
                Lanjut isi form secara manual →
            </a>
            <p class="text-center text-xs text-slate-400 mt-3">Proses biasanya selesai dalam 10–20 detik</p>
        </div>

        {{-- Loading spinner --}}
        <div id="loading-spinner" class="text-center">
            <div class="inline-flex items-center gap-2 text-xs text-slate-400">
                <div class="w-4 h-4 border-2 border-emerald-400 border-t-transparent rounded-full animate-spin"></div>
                <span>Memproses...</span>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const uploadId = '{{ $uploadId ?? "" }}';
    const userId = {{ Auth::id() }};
    const redirectUrl = '{{ route("rembush.form") }}';
    let safetyTimeout = null;

    function initEcho() {
        if (typeof window.Echo === 'undefined') {
            console.warn('⚠️ [OCR] Echo not found, falling back to basic timeout');
            startSafetyTimeout(10000); // Short fallback if Echo fails to load
            return;
        }

        console.log('📡 [OCR] Listening for updates on channel:', `ocr.${userId}`);

        window.Echo.private(`ocr.${userId}`)
            .listen('.ocr.updated', (e) => {
                console.log('📥 [OCR] Received broadcast:', e);
                
                // Ensure this event is for the current upload
                if (e.payload.upload_id !== uploadId) return;

                if (e.payload.ai_status === 'completed') {
                    console.log('✅ [OCR] Completed! Redirecting...');
                    window.location.href = redirectUrl;
                } else if (e.payload.ai_status === 'error' || e.payload.ai_status === 'auto-reject') {
                    console.warn('❌ [OCR] Failed or Auto-rejected:', e.payload.message);
                    showFallback(e.payload.message);
                }
            });

        // Set a safety timeout (90 seconds) in case broadcast is missed or n8n fails
        startSafetyTimeout(90000);
    }

    function startSafetyTimeout(ms) {
        if (safetyTimeout) clearTimeout(safetyTimeout);
        safetyTimeout = setTimeout(() => {
            console.warn('⏳ [OCR] Safety timeout reached');
            showFallback('Proses terlalu lama, silakan isi manual atau coba refresh halaman.');
        }, ms);
    }

    function showFallback(message) {
        if (safetyTimeout) clearTimeout(safetyTimeout);
        document.getElementById('loading-spinner').classList.add('hidden');
        document.getElementById('fallback-container').classList.remove('hidden');
        if (message) {
            const fallbackText = document.querySelector('#fallback-container p.text-orange-600');
            if (fallbackText) {
                fallbackText.textContent = message;
            }
        }
    }

    window.addEventListener('beforeunload', function() {
        if (window.toggleBodyScroll) window.toggleBodyScroll(false);
        else document.body.style.overflow = '';
    });

    // Start when page loads
    document.addEventListener('DOMContentLoaded', function() {
        if (window.toggleBodyScroll) window.toggleBodyScroll(true);
        else document.body.style.overflow = 'hidden';

        if (uploadId) {
            lucide.createIcons();
            initEcho();
        } else {
            showFallback('Upload ID tidak ditemukan');
        }
    });
</script>
@endpush
@endsection