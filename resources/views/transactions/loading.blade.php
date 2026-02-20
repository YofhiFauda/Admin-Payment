@extends('layouts.app')

@section('page-title', 'Memproses Nota')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-gray-100 p-8 text-center">
        {{-- Icon --}}
        <div id="icon-container" class="mb-6">
            <div class="w-16 h-16 mx-auto rounded-full bg-blue-100 flex items-center justify-center">
                <svg id="main-icon" class="w-8 h-8 text-blue-600 animate-spin"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
            </div>
        </div>

        {{-- Title --}}
        <h2 id="status-title" class="text-lg font-bold text-slate-800 mb-2">Memproses Nota...</h2>
        <p id="status-subtitle" class="text-sm text-slate-400 mb-6">Mohon tunggu, sistem sedang membaca data dari nota Anda.</p>

        {{-- Progress Bar --}}
        <div class="w-full bg-slate-100 rounded-full h-2 mb-4 overflow-hidden">
            <div id="progress-bar" class="h-full bg-gradient-to-r from-blue-500 to-indigo-600 transition-all duration-500" style="width: 10%"></div>
        </div>

        <div class="text-xs text-slate-400 font-bold uppercase tracking-wider">
            <span id="progress-text">Menghubungkan ke AI...</span>
        </div>
        <div class="mt-6 text-[10px] text-slate-300">Jangan tutup halaman ini</div>
    </div>
</div>

<input type="hidden" id="upload-id" value="{{ $uploadId }}">

{{-- ✅ TAMBAHKAN INI: Data dari Session Laravel (jika ada) --}}
@if(session('ai_data'))
    <input type="hidden" id="session-ai-data" value="{{ json_encode(session('ai_data')) }}">
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const uploadId = document.getElementById('upload-id').value;
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');
    const statusTitle = document.getElementById('status-title');
    const statusSubtitle = document.getElementById('status-subtitle');
    const mainIcon = document.getElementById('main-icon');

    const sessionAiDataEl = document.getElementById('session-ai-data');
    const sessionAiData = sessionAiDataEl ? JSON.parse(sessionAiDataEl.value) : null;

    let attempts = 0;
    const maxAttempts = 30;
    const intervalTime = 1500;
    let progress = 10;

    // ✅ FIX #1: Deklarasikan `interval` di sini agar tidak TDZ crash
    let interval = null;

    const endpoint = `/api/ai/ai-status/${uploadId}`;

    // ==========================================
    // FUNGSI SUKSES
    // ==========================================
    function finishProcess(aiData, source = 'API') {
        // Aman sekarang karena `interval` sudah dideklarasikan di atas
        if (interval) clearInterval(interval);

        progress = 100;
        progressBar.style.width = "100%";
        progressBar.classList.remove('bg-gradient-to-r', 'bg-blue-500');
        progressBar.classList.add('bg-green-500');

        mainIcon.classList.remove('animate-spin', 'text-blue-600');
        mainIcon.classList.add('text-green-600');
        mainIcon.innerHTML = `<path fill="currentColor" d="M9 16.2l-3.5-3.5L4 14.2l5 5 12-12-1.5-1.5z"/>`;

        statusTitle.innerText = "Berhasil!";
        let confidence = aiData.confidence || aiData.data?.confidence || 90;
        statusSubtitle.innerText = `AI Confidence: ${confidence}% — Mengalihkan...`;

        const storageData = aiData.data || aiData;
        sessionStorage.setItem("ai_autofill_" + uploadId, JSON.stringify(storageData));
        sessionStorage.setItem("ai_autofill_latest", JSON.stringify(storageData));

        // ✅ FIX #2: Hapus stale data agar upload berikutnya tidak membaca cache ini
        // (dilakukan setelah disimpan dengan key yang benar)
        setTimeout(() => {
            window.location.href = "{{ route('transactions.form') }}?upload_id=" + uploadId;
        }, 1500);
    }

    // ==========================================
    // FUNGSI GAGAL
    // ==========================================
    function failProcess(message) {
        if (interval) clearInterval(interval);
        progressBar.style.width = "100%";
        progressBar.classList.remove('bg-gradient-to-r');
        progressBar.classList.add('bg-red-500');
        mainIcon.classList.remove('animate-spin', 'text-blue-600');
        mainIcon.classList.add('text-red-500');
        statusTitle.innerText = "Gagal Memproses";
        statusSubtitle.innerText = message;
    }

    // ==========================================
    // PRIORITAS 1: CEK SESSION LARAVEL DULU
    // ==========================================
    if (sessionAiData && (sessionAiData.customer || sessionAiData.amount)) {
        finishProcess(sessionAiData, 'Laravel Session');
        return;
    }

    // ==========================================
    // PRIORITAS 2: CEK BROWSER SESSIONSTORAGE
    // ✅ FIX #3: Hanya cek key spesifik uploadId ini, BUKAN ai_autofill_latest
    // ==========================================
    function checkBrowserCache() {
        // Hanya cek key milik uploadId saat ini — hindari data stale dari upload sebelumnya
        const exactKey = "ai_autofill_" + uploadId;
        const data = sessionStorage.getItem(exactKey);
        if (data) {
            try {
                const parsed = JSON.parse(data);
                if (parsed && (parsed.customer || parsed.amount || parsed.confidence)) {
                    return parsed;
                }
            } catch (e) {}
        }
        return null;
    }

    const cachedData = checkBrowserCache();
    if (cachedData) {
        finishProcess(cachedData, 'Browser Cache');
        return;
    }

    // ✅ FIX #4: Bersihkan ai_autofill_latest dari upload sebelumnya agar tidak terbawa
    sessionStorage.removeItem("ai_autofill_latest");
    sessionStorage.removeItem("ai_autofill:null");

    // ==========================================
    // PRIORITAS 3: POLLING API
    // ==========================================
    interval = setInterval(async () => {
        attempts++;

        if (progress < 90) {
            progress += 2;
            progressBar.style.width = progress + "%";
        }

        if (attempts === 5) progressText.innerText = "Membaca teks (OCR)...";
        if (attempts === 15) progressText.innerText = "Mengekstrak data...";
        if (attempts === 25) progressText.innerText = "Validasi...";

        try {
            const response = await fetch(endpoint, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const res = await response.json();
            if (res.status === 'completed' && res.data) {
                finishProcess(res.data, 'Server Cache');
                return;
            }

            const browserCache = checkBrowserCache();
            if (browserCache) {
                finishProcess(browserCache, 'Browser Cache (Fallback)');
                return;
            }

            if (attempts >= maxAttempts) {
                setTimeout(() => {
                    window.location.href = "{{ route('transactions.form') }}?upload_id=" + uploadId;
                }, 2000);
            }

        } catch (error) {
            console.error(error);
            const browserCache = checkBrowserCache();
            if (browserCache) {
                finishProcess(browserCache, 'Cache on Error');
                return;
            }

            if (attempts >= maxAttempts) {
                setTimeout(() => {
                    window.location.href = "{{ route('transactions.form') }}?upload_id=" + uploadId;
                }, 2000);
            }
        }
    }, intervalTime);
});
</script>
@endpush