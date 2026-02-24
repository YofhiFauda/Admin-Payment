{{-- transactions/loading.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinanceOps – AI Memproses Nota</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* ─── Design Tokens ─── */
        :root {
            --bg:        #0B1225;
            --surface:   #111827;
            --surface-2: #1A2342;
            --border:    rgba(255,255,255,0.07);
            --indigo:    #6366f1;
            --teal:      #14b8a6;
            --violet:    #8b5cf6;
            --text-1:    #f1f5f9;
            --text-2:    #94a3b8;
            --text-3:    #475569;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text-1);
            min-height: 100svh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* ─── Background blobs ─── */
        .blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(160px);
            pointer-events: none;
            animation: drift 12s ease-in-out infinite alternate;
        }
        .blob-1 { width: 600px; height: 600px; background: rgba(99,102,241,0.18); top: -200px; left: -200px; }
        .blob-2 { width: 500px; height: 500px; background: rgba(20,184,166,0.12); bottom: -150px; right: -150px; animation-delay: -6s; }
        .blob-3 { width: 400px; height: 400px; background: rgba(139,92,246,0.10); top: 50%; left: 50%; transform: translate(-50%,-50%); animation-delay: -3s; }

        @keyframes drift {
            from { transform: translate(0,0) scale(1); }
            to   { transform: translate(30px,20px) scale(1.05); }
        }

        /* ─── Noise grain overlay ─── */
        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
            pointer-events: none; opacity: 0.4; z-index: 0;
        }

        /* ─── Card ─── */
        .card {
            position: relative; z-index: 10;
            width: min(480px, calc(100vw - 2rem));
            padding: 2.5rem;
            background: rgba(17,24,39,0.7);
            border: 1px solid var(--border);
            border-radius: 28px;
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            box-shadow:
                0 0 0 1px rgba(99,102,241,0.08),
                0 32px 64px rgba(0,0,0,0.4),
                0 0 80px rgba(99,102,241,0.06);
            animation: card-in 0.6s cubic-bezier(0.34,1.56,0.64,1) both;
        }

        @keyframes card-in {
            from { opacity: 0; transform: translateY(24px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ─── Logo bar ─── */
        .logo-bar {
            display: flex; align-items: center; gap: 0.625rem;
            margin-bottom: 2rem;
        }
        .logo-icon {
            width: 36px; height: 36px; border-radius: 10px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            display: flex; align-items: center; justify-content: center;
        }
        .logo-text { font-size: 1rem; font-weight: 800; color: var(--text-1); letter-spacing: -0.02em; }

        /* ─── AI Scanner ─── */
        .scanner-wrap {
            position: relative;
            width: 100%;
            aspect-ratio: 3/2;
            border-radius: 18px;
            overflow: hidden;
            background: var(--surface-2);
            border: 1px solid rgba(99,102,241,0.15);
            margin-bottom: 2rem;
        }

        /* Document silhouette lines */
        .doc-lines {
            position: absolute; inset: 0;
            display: flex; flex-direction: column;
            justify-content: center;
            padding: 1.5rem 2rem;
            gap: 0;
            opacity: 1;
        }

        .doc-line {
            height: 9px; border-radius: 5px;
            background: rgba(255,255,255,0.06);
            margin-bottom: 10px;
            position: relative;
            overflow: hidden;
        }
        .doc-line::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.12), transparent);
            animation: shimmer 2s ease-in-out infinite;
        }
        .doc-line:nth-child(1) { width: 70%; }
        .doc-line:nth-child(2) { width: 90%; animation-delay: 0.15s; }
        .doc-line:nth-child(3) { width: 55%; animation-delay: 0.3s; }
        .doc-line:nth-child(4) { width: 80%; animation-delay: 0.45s; }
        .doc-line:nth-child(5) { width: 65%; animation-delay: 0.6s; }
        .doc-line:nth-child(6) { width: 85%; animation-delay: 0.75s; }
        .doc-line:nth-child(7) { width: 40%; animation-delay: 0.9s; }

        @keyframes shimmer {
            0%   { transform: translateX(-100%); }
            100% { transform: translateX(400%); }
        }

        /* Corner brackets */
        .corner {
            position: absolute; width: 22px; height: 22px;
            border-color: var(--indigo);
            border-style: solid;
            opacity: 0.9;
        }
        .corner-tl { top: 14px; left: 14px; border-width: 2px 0 0 2px; border-radius: 4px 0 0 0; }
        .corner-tr { top: 14px; right: 14px; border-width: 2px 2px 0 0; border-radius: 0 4px 0 0; }
        .corner-bl { bottom: 14px; left: 14px; border-width: 0 0 2px 2px; border-radius: 0 0 0 4px; }
        .corner-br { bottom: 14px; right: 14px; border-width: 0 2px 2px 0; border-radius: 0 0 4px 0; }

        /* Scan beam */
        .scan-beam {
            position: absolute; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, transparent, var(--teal) 30%, rgba(20,184,166,0.8) 50%, var(--teal) 70%, transparent);
            box-shadow: 0 0 16px 4px rgba(20,184,166,0.35), 0 0 40px 8px rgba(20,184,166,0.15);
            animation: scan 2.4s ease-in-out infinite;
            top: 0;
        }

        @keyframes scan {
            0%   { top: 10%;  opacity: 0; }
            8%   { opacity: 1; }
            92%  { opacity: 1; }
            100% { top: 90%; opacity: 0; }
        }

        /* Detected highlight fragments */
        .detected-fragment {
            position: absolute;
            border-radius: 4px;
            background: rgba(99,102,241,0.12);
            border: 1px solid rgba(99,102,241,0.35);
            animation: fragment-pop 0.4s ease both;
            pointer-events: none;
        }

        @keyframes fragment-pop {
            from { opacity: 0; transform: scale(0.92); }
            to   { opacity: 1; transform: scale(1); }
        }

        /* AI badge in scanner */
        .ai-badge {
            position: absolute;
            top: 14px; right: 14px;
            display: flex; align-items: center; gap: 6px;
            padding: 4px 10px;
            background: rgba(99,102,241,0.15);
            border: 1px solid rgba(99,102,241,0.3);
            border-radius: 999px;
            font-size: 10px; font-weight: 700;
            color: #a5b4fc; letter-spacing: 0.07em; text-transform: uppercase;
            z-index: 2;
        }
        .ai-badge .dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: #6ee7b7;
            animation: pulse-dot 1.2s ease-in-out infinite;
        }
        @keyframes pulse-dot {
            0%,100% { opacity: 1; transform: scale(1); }
            50%      { opacity: 0.5; transform: scale(0.7); }
        }

        /* ─── Status label ─── */
        .status-label {
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px; font-weight: 500;
            color: var(--teal);
            letter-spacing: 0.08em; text-transform: uppercase;
            display: flex; align-items: center; gap: 6px;
            margin-bottom: 0.5rem;
        }
        .status-label .line { flex: 1; height: 1px; background: rgba(20,184,166,0.2); }

        /* ─── Heading ─── */
        .heading { font-size: 1.35rem; font-weight: 800; letter-spacing: -0.03em; color: var(--text-1); margin-bottom: 0.4rem; }
        .subheading { font-size: 0.875rem; color: var(--text-2); font-weight: 500; line-height: 1.5; margin-bottom: 1.75rem; }

        /* ─── Progress steps ─── */
        .steps { display: flex; flex-direction: column; gap: 0.625rem; margin-bottom: 1.75rem; }
        .step {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            border: 1px solid transparent;
            transition: all 0.4s ease;
            position: relative; overflow: hidden;
        }
        .step.done    { background: rgba(20,184,166,0.07); border-color: rgba(20,184,166,0.2); }
        .step.active  { background: rgba(99,102,241,0.08); border-color: rgba(99,102,241,0.25); }
        .step.waiting { opacity: 0.4; }

        /* Step shimmer (active) */
        .step.active::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(90deg, transparent, rgba(99,102,241,0.07), transparent);
            animation: step-shine 2s ease-in-out infinite;
        }
        @keyframes step-shine {
            0%   { transform: translateX(-100%); }
            100% { transform: translateX(200%); }
        }

        .step-icon {
            width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px;
        }
        .step.done   .step-icon { background: rgba(20,184,166,0.15); color: var(--teal); }
        .step.active .step-icon { background: rgba(99,102,241,0.15); color: var(--indigo); }
        .step.waiting .step-icon { background: rgba(255,255,255,0.04); color: var(--text-3); }

        .step-text { flex: 1; }
        .step-title { font-size: 0.8rem; font-weight: 600; color: var(--text-1); }
        .step-desc  { font-size: 0.72rem; color: var(--text-2); margin-top: 1px; }

        .step-tick { color: var(--teal); flex-shrink: 0; }

        /* Spinner for active step */
        .spinner {
            width: 14px; height: 14px; border-radius: 50%;
            border: 2px solid rgba(99,102,241,0.2);
            border-top-color: var(--indigo);
            animation: spin 0.7s linear infinite;
            flex-shrink: 0;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ─── Timeout fallback button ─── */
        .fallback-btn {
            display: none;
            width: 100%; padding: 0.875rem;
            background: rgba(99,102,241,0.12);
            border: 1px solid rgba(99,102,241,0.3);
            border-radius: 14px;
            color: #a5b4fc; font-size: 0.875rem; font-weight: 600;
            text-align: center; cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            margin-bottom: 0.75rem;
        }
        .fallback-btn:hover { background: rgba(99,102,241,0.2); }
        .fallback-btn.show { display: block; }

        /* ─── Footer note ─── */
        .footer-note {
            text-align: center;
            font-size: 0.72rem; color: var(--text-3); font-weight: 500;
            margin-top: 0.25rem;
        }

        /* ─── Skeleton shimmer (for form preview) ─── */
        .skeleton {
            border-radius: 8px;
            background: linear-gradient(90deg, rgba(255,255,255,0.04) 25%, rgba(255,255,255,0.09) 50%, rgba(255,255,255,0.04) 75%);
            background-size: 200% 100%;
            animation: skel-wave 1.8s ease-in-out infinite;
        }
        @keyframes skel-wave {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Form preview skeleton */
        .form-preview {
            display: none; /* shown on complete */
            margin-top: 1.25rem; padding-top: 1.25rem;
            border-top: 1px solid var(--border);
        }
        .form-preview.show { display: block; }
        .skel-label { height: 10px; width: 30%; border-radius: 4px; margin-bottom: 8px; }
        .skel-input { height: 40px; width: 100%; border-radius: 10px; margin-bottom: 14px; }
        .skel-row { display: flex; gap: 10px; }
        .skel-half { height: 40px; flex: 1; border-radius: 10px; }
    </style>
</head>
<body>

    {{-- Atmospheric blobs --}}
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <div class="card">

        {{-- Logo --}}
        <div class="logo-bar">
            <div class="logo-icon">
                <i data-lucide="wallet" style="width:16px;height:16px;color:#94a3b8;"></i>
            </div>
            <span class="logo-text">FinanceOps</span>
        </div>

        {{-- AI Scanner Visual --}}
        <div class="scanner-wrap" id="scannerWrap">
            {{-- Doc skeleton lines --}}
            <div class="doc-lines">
                <div class="doc-line"></div>
                <div class="doc-line"></div>
                <div class="doc-line"></div>
                <div class="doc-line"></div>
                <div class="doc-line"></div>
                <div class="doc-line"></div>
                <div class="doc-line"></div>
            </div>

            {{-- Corner brackets --}}
            <div class="corner corner-tl"></div>
            <div class="corner corner-tr"></div>
            <div class="corner corner-bl"></div>
            <div class="corner corner-br"></div>

            {{-- Scan beam --}}
            <div class="scan-beam" id="scanBeam"></div>

            {{-- AI badge --}}
            <div class="ai-badge" style="top:14px;left:14px;right:auto;">
                <div class="dot"></div>
                AI OCR
            </div>
        </div>

        {{-- Status label --}}
        <div class="status-label">
            <div class="line"></div>
            <span id="statusText">Mendeteksi teks nota</span>
            <div class="line"></div>
        </div>

        <h2 class="heading">AI sedang memproses nota Anda</h2>
        <p class="subheading">Teknologi OCR cerdas kami mengekstrak data secara otomatis. Harap tunggu sebentar.</p>

        {{-- Progress steps --}}
        <div class="steps" id="stepsContainer">
            <div class="step done" id="step0">
                <div class="step-icon"><i data-lucide="upload-cloud" style="width:14px;height:14px;"></i></div>
                <div class="step-text">
                    <div class="step-title">Nota berhasil diunggah</div>
                    <div class="step-desc">File diterima sistem</div>
                </div>
                <i data-lucide="check" class="step-tick" style="width:14px;height:14px;"></i>
            </div>

            <div class="step active" id="step1">
                <div class="step-icon"><i data-lucide="scan" style="width:14px;height:14px;"></i></div>
                <div class="step-text">
                    <div class="step-title">Memindai gambar</div>
                    <div class="step-desc">Praproses resolusi &amp; kontras</div>
                </div>
                <div class="spinner" id="spinner1"></div>
            </div>

            <div class="step waiting" id="step2">
                <div class="step-icon"><i data-lucide="cpu" style="width:14px;height:14px;"></i></div>
                <div class="step-text">
                    <div class="step-title">Ekstraksi teks AI</div>
                    <div class="step-desc">Membaca nomor &amp; nominal</div>
                </div>
                <div class="spinner" id="spinner2" style="display:none;"></div>
            </div>

            <div class="step waiting" id="step3">
                <div class="step-icon"><i data-lucide="check-circle" style="width:14px;height:14px;"></i></div>
                <div class="step-text">
                    <div class="step-title">Validasi &amp; pengisian form</div>
                    <div class="step-desc">Menyusun data untuk Anda</div>
                </div>
            </div>
        </div>

        {{-- Timeout fallback --}}
        <a href="{{ route('rembush.form') }}" class="fallback-btn" id="fallbackBtn">
            Lanjut isi form secara manual →
        </a>

        <p class="footer-note" id="footerNote">
            Proses biasanya selesai dalam 10–20 detik
        </p>

        {{-- Form preview skeleton (shown when done) --}}
        <div class="form-preview" id="formPreview">
            <div class="skel-label skeleton"></div>
            <div class="skel-input skeleton"></div>
            <div class="skel-row">
                <div class="skel-half skeleton"></div>
                <div class="skel-half skeleton"></div>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            lucide.createIcons();

            const UPLOAD_ID    = @json($uploadId);
            const POLL_URL     = `/api/ai/ai-status/${UPLOAD_ID}`;
            const REDIRECT_URL = @json(route('rembush.form'));

            const POLL_BASE_INTERVAL = 2500;
            const MAX_TIMEOUT = 90000;

            let destroyed = false;
            let pollTimer = null;
            let controller = null;
            let attempt = 0;

            const statusText  = document.getElementById('statusText');
            const fallbackBtn = document.getElementById('fallbackBtn');
            const footerNote  = document.getElementById('footerNote');
            const scanBeam    = document.getElementById('scanBeam');

            const steps = {
                scanning: document.getElementById('step1'),
                parsing: document.getElementById('step2'),
                validating: document.getElementById('step3')
            };

            const spinners = {
                scanning: document.getElementById('spinner1'),
                parsing: document.getElementById('spinner2')
            };

            function setActivePhase(phase) {

        Object.values(steps).forEach(step => {
            step.classList.remove('active');
            step.classList.remove('done');
            step.classList.add('waiting');
        });

        if (phase === 'scanning') {
            activate('scanning');
            statusText.textContent = 'Memindai gambar nota';
        }

        if (phase === 'parsing') {
            complete('scanning');
            activate('parsing');
            statusText.textContent = 'Mengekstrak teks & nominal';
        }

        if (phase === 'validating') {
            complete('scanning');
            complete('parsing');
            activate('validating');
            statusText.textContent = 'Memvalidasi data hasil OCR';
        }
    }

    function activate(phase) {
        steps[phase].classList.remove('waiting');
        steps[phase].classList.add('active');
        if (spinners[phase]) spinners[phase].style.display = 'block';
    }

    function complete(phase) {
        steps[phase].classList.remove('waiting');
        steps[phase].classList.remove('active');
        steps[phase].classList.add('done');
        if (spinners[phase]) spinners[phase].style.display = 'none';
    }

    async function poll() {

        if (destroyed) return;

        if (controller) controller.abort();
        controller = new AbortController();

        try {
            const res = await fetch(POLL_URL, {
                signal: controller.signal,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!res.ok) throw new Error(res.status);

            const data = await res.json();

            if (data.status === 'completed') {
                onComplete();
                return;
            }

            if (data.phase) {
                setActivePhase(data.phase);
            }

            scheduleNext();

        } catch (err) {

            if (err.name === 'AbortError') return;

            attempt++;

            const backoff = Math.min(
                POLL_BASE_INTERVAL * Math.pow(1.5, attempt),
                15000
            );

            pollTimer = setTimeout(poll, backoff);
        }
    }

    function scheduleNext() {
        attempt = 0;
        pollTimer = setTimeout(poll, POLL_BASE_INTERVAL);
    }

    function onComplete() {

        destroyed = true;
        cleanup();

        complete('scanning');
        complete('parsing');
        complete('validating');

        statusText.textContent = 'Data berhasil diekstrak ✓';
        footerNote.textContent = 'Mengarahkan ke form...';

        scanBeam.style.animation = 'none';
        scanBeam.style.opacity = '0';

        setTimeout(() => {
            window.location.href = REDIRECT_URL;
        }, 800);
    }

    function cleanup() {
        clearTimeout(pollTimer);
        if (controller) controller.abort();
    }

    setTimeout(poll, 1200);

    setTimeout(() => {
        if (destroyed) return;
        fallbackBtn.classList.add('show');
        footerNote.textContent = 'AI lebih lama dari biasanya. Anda bisa isi manual.';
    }, 25000);

    setTimeout(() => {
        if (destroyed) return;
        destroyed = true;
        cleanup();
        statusText.textContent = 'Waktu habis — lanjut manual';
        scanBeam.style.display = 'none';
        fallbackBtn.classList.add('show');
    }, MAX_TIMEOUT);

});
    </script>
</body>
</html>