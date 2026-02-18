@extends('layouts.app')

@section('title', 'Memproses Nota...')

@section('content')
<div class="min-h-[80vh] flex flex-col items-center justify-center p-4">
    <div class="w-full max-w-md bg-white/80 backdrop-blur-xl rounded-2xl shadow-2xl p-8 border border-white/20 text-center relative overflow-hidden">
        
        <!-- Background Decoration -->
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 animate-gradient-x"></div>
        
        <!-- Animated Icon Container -->
        <div class="relative w-32 h-32 mx-auto mb-8">
            <!-- Outer Ring -->
            <div class="absolute inset-0 border-4 border-blue-100 rounded-full"></div>
            <!-- Spinning Ring -->
            <div class="absolute inset-0 border-4 border-blue-500 rounded-full border-t-transparent animate-spin"></div>
            
            <!-- AI/Scan Icon -->
            <div class="absolute inset-0 flex items-center justify-center">
                <svg class="w-12 h-12 text-blue-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                </svg>
            </div>
            
            <!-- Floating Particles (CSS only) -->
            <div class="absolute -top-2 -right-2 w-4 h-4 bg-purple-400 rounded-full animate-bounce delay-100"></div>
            <div class="absolute -bottom-2 -left-2 w-3 h-3 bg-pink-400 rounded-full animate-bounce delay-300"></div>
        </div>

        <!-- Text Content -->
        <h2 class="text-2xl font-bold text-slate-800 mb-2">Sedang Memproses Nota</h2>
        <p class="text-slate-500 mb-8">AI sedang membaca data dari gambar Anda...</p>

        <!-- Progress Steps -->
        <div class="space-y-3 text-sm text-left max-w-xs mx-auto mb-8">
            <div class="flex items-center gap-3 text-slate-600">
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>Mengupload gambar...</span>
            </div>
            <div class="flex items-center gap-3 text-blue-600 font-medium animate-pulse" id="step-ocr">
                <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                <span>Mengekstrak teks & data...</span>
            </div>
            <div class="flex items-center gap-3 text-slate-400" id="step-finalize">
                <div class="w-5 h-5 rounded-full border-2 border-slate-200"></div>
                <span>Finalisasi data...</span>
            </div>
        </div>

        <!-- Action -->
        <p class="text-xs text-slate-400">Harap jangan tutup halaman ini.</p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const uploadId = "{{ $uploadId }}";
        const checkUrl = `/api/ai-status/${uploadId}`;
        const redirectUrl = "{{ route('transactions.form') }}";
        
        let attempts = 0;
        const maxAttempts = 30; // 60 seconds timeout (2s interval)

        const pollStatus = async () => {
            try {
                const response = await fetch(checkUrl);
                const data = await response.json();

                if (data.status === 'completed' || data.customer || data.amount) {
                    // Success! Update UI then redirect
                    document.getElementById('step-ocr').innerHTML = `
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Ekstraksi berhasil!</span>
                    `;
                    document.getElementById('step-ocr').classList.remove('animate-pulse', 'text-blue-600');
                    document.getElementById('step-ocr').classList.add('text-slate-600');

                    document.getElementById('step-finalize').innerHTML = `
                        <svg class="w-5 h-5 animate-spin text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span class="text-blue-600 font-medium">Mengalihkan...</span>
                    `;
                    
                    setTimeout(() => {
                        window.location.href = redirectUrl;
                    }, 500); // Small delay for UX
                    return;
                }
            } catch (error) {
                console.error("Polling error:", error);
            }

            attempts++;
            if (attempts < maxAttempts) {
                setTimeout(pollStatus, 2000); // Retry every 2 seconds
            } else {
                // Timeout fallback - redirect anyway so user isn't stuck
                window.location.href = redirectUrl; 
            }
        };

        // Start polling after 1s delay
        setTimeout(pollStatus, 1000);
    });
</script>

<style>
    @keyframes gradient-x {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    .animate-gradient-x {
        background-size: 200% 200%;
        animation: gradient-x 3s ease infinite;
    }
</style>
@endsection
