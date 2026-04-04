@extends('layouts.app')

@php
    $hideHeader = true;
@endphp

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,700;0,9..144,900;1,9..144,700;1,9..144,900&family=DM+Sans:wght@300;400;500;600&display=swap');

    :root {
        --amber:        #d97706;
        --amber-light:  #fef3c7;
        --amber-mid:    #fde68a;
        --amber-glow:   rgba(217,119,6,0.10);
        --teal:         #0891b2;
        --teal-light:   #cffafe;
        --teal-mid:     #a5f3fc;
        --teal-glow:    rgba(8,145,178,0.10);

        --page-bg:      #f4f0e8;
        --card-bg:      #ffffff;
        --card-border:  #e8e2d8;
        --card-shadow:  0 2px 8px rgba(0,0,0,0.05), 0 12px 40px rgba(0,0,0,0.08);
        --card-shadow-hover: 0 4px 16px rgba(0,0,0,0.08), 0 24px 56px rgba(0,0,0,0.13);

        --text-primary: #1c1917;
        --text-second:  #44403c;
        --text-muted:   #78716c;
        --text-faint:   #a8a29e;

        --upload-bg:    #faf8f5;
        --upload-border:#d6cfc4;
    }

    /* ── PAGE ── */
    .ic-page {
        min-height: 100dvh;
        background-color: var(--page-bg);
        background-image:
            radial-gradient(ellipse 70% 50% at 10% 0%, rgba(217,119,6,0.08) 0%, transparent 55%),
            radial-gradient(ellipse 60% 45% at 90% 100%, rgba(8,145,178,0.07) 0%, transparent 55%),
            url("data:image/svg+xml,%3Csvg width='24' height='24' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='1' cy='1' r='1' fill='%23c4bdb5' fill-opacity='0.35'/%3E%3C/svg%3E");
        font-family: 'DM Sans', sans-serif;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2.5rem 1.25rem;
        overflow-x: hidden;
    }

    .ic-wrapper {
        width: 100%;
        max-width: 960px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2.5rem;
    }

    /* ── HEADER ── */
    .ic-header {
        text-align: center;
        opacity: 0;
        transform: translateY(-20px);
        animation: slideDown 0.6s cubic-bezier(0.22,1,0.36,1) 0.08s forwards;
    }

    .ic-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: #fff;
        border: 1px solid var(--card-border);
        border-radius: 100px;
        padding: 5px 14px;
        font-size: 0.70rem;
        font-weight: 600;
        color: var(--amber);
        letter-spacing: 0.10em;
        text-transform: uppercase;
        margin-bottom: 1.4rem;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    }

    .ic-badge .dot {
        width: 7px; height: 7px;
        background: var(--amber);
        border-radius: 50%;
        animation: pulse-dot 1.8s ease-in-out infinite;
    }

    /* TITLE — Fraunces display serif, italic accent */
    .ic-title {
        font-family: 'Fraunces', serif;
        font-optical-sizing: auto;
        font-size: clamp(2.4rem, 5.5vw, 3.6rem);
        font-weight: 900;
        color: var(--text-primary);
        letter-spacing: -0.035em;
        line-height: 1.05;
        margin-bottom: 1rem;
    }

    .ic-title .accent-word {
        font-style: italic;
        color: var(--amber);
        position: relative;
        display: inline-block;
    }

    .ic-title .accent-word::after {
        content: '';
        position: absolute;
        left: 0; right: 0;
        bottom: 2px;
        height: 3px;
        background: linear-gradient(90deg, var(--amber) 0%, #fbbf24 100%);
        border-radius: 2px;
        opacity: 0.5;
    }

    .ic-sub {
        font-size: 0.95rem;
        color: var(--text-muted);
        max-width: 420px;
        margin: 0 auto;
        line-height: 1.65;
    }

    .ic-sub .ai-tag {
        color: var(--teal);
        font-weight: 600;
    }

    /* ── CARDS GRID ── */
    .ic-cards {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.25rem;
        width: 100%;
    }

    @media (max-width: 620px) {
        .ic-cards { grid-template-columns: 1fr; }
    }

    .ic-card {
        position: relative;
        background: var(--card-bg);
        border: 1.5px solid var(--card-border);
        border-radius: 22px;
        padding: 2rem 1.75rem 1.75rem;
        cursor: pointer;
        overflow: hidden;
        transition: border-color 0.28s, box-shadow 0.28s, transform 0.28s;
        box-shadow: var(--card-shadow);
        opacity: 0;
        transform: translateY(28px) scale(0.98);
        animation: riseCard 0.65s cubic-bezier(0.22,1,0.36,1) forwards;
        outline: none;
    }

    .ic-card:nth-child(1) { animation-delay: 0.3s; }
    .ic-card:nth-child(2) { animation-delay: 0.44s; }

    .ic-card:hover {
        transform: translateY(-5px) scale(1.01);
        box-shadow: var(--card-shadow-hover);
    }

    /* Rembush hover/selected */
    .ic-card.rembush-card:hover,
    .ic-card.rembush-card.selected {
        border-color: rgba(217,119,6,0.5);
        box-shadow: var(--card-shadow-hover), 0 0 0 3px rgba(217,119,6,0.08);
    }

    /* Pengajuan hover/selected */
    .ic-card.pengajuan-card:hover,
    .ic-card.pengajuan-card.selected {
        border-color: rgba(8,145,178,0.5);
        box-shadow: var(--card-shadow-hover), 0 0 0 3px rgba(8,145,178,0.08);
    }

    .ic-card.selected { transform: translateY(-5px) scale(1.01); }

    /* Top stripe */
    .ic-card-stripe {
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        border-radius: 3px 3px 0 0;
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.4s cubic-bezier(0.22,1,0.36,1);
    }

    .rembush-card .ic-card-stripe  { background: linear-gradient(90deg, var(--amber), #fbbf24); }
    .pengajuan-card .ic-card-stripe { background: linear-gradient(90deg, var(--teal), #22d3ee); }

    .ic-card:hover .ic-card-stripe,
    .ic-card.selected .ic-card-stripe { transform: scaleX(1); }

    /* Check badge */
    .ic-card-check {
        position: absolute;
        top: 1.1rem; right: 1.1rem;
        width: 26px; height: 26px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        opacity: 0;
        transform: scale(0.4) rotate(-15deg);
        transition: opacity 0.25s, transform 0.35s cubic-bezier(0.34,1.56,0.64,1);
    }

    .rembush-card .ic-card-check  { background: var(--amber); color: #fff; }
    .pengajuan-card .ic-card-check { background: var(--teal);  color: #fff; }

    .ic-card.selected .ic-card-check {
        opacity: 1;
        transform: scale(1) rotate(0deg);
    }

    /* Background tint on selected */
    .rembush-card.selected  { background: linear-gradient(145deg, #fffdf7 0%, #fff 100%); }
    .pengajuan-card.selected { background: linear-gradient(145deg, #f0fbff 0%, #fff 100%); }

    /* Icon */
    .ic-card-icon {
        width: 56px; height: 56px;
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        margin-bottom: 1.3rem;
        transition: transform 0.3s;
    }

    .rembush-card .ic-card-icon {
        background: var(--amber-light);
        border: 1px solid var(--amber-mid);
        color: var(--amber);
    }

    .pengajuan-card .ic-card-icon {
        background: var(--teal-light);
        border: 1px solid var(--teal-mid);
        color: var(--teal);
    }

    .ic-card:hover .ic-card-icon,
    .ic-card.selected .ic-card-icon {
        transform: scale(1.08) rotate(-4deg);
    }

    .ic-card-icon svg { width: 26px; height: 26px; }

    /* Card text */
    .ic-card-title {
        font-family: 'Fraunces', serif;
        font-optical-sizing: auto;
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.45rem;
        letter-spacing: -0.02em;
    }

    .ic-card-desc {
        font-size: 0.865rem;
        color: var(--text-muted);
        line-height: 1.6;
        margin-bottom: 1.5rem;
    }

    /* Tags */
    .ic-card-tags { display: flex; flex-wrap: wrap; gap: 6px; }

    .ic-tag {
        font-size: 0.68rem;
        font-weight: 600;
        letter-spacing: 0.07em;
        padding: 3px 10px;
        border-radius: 100px;
        text-transform: uppercase;
    }

    .rembush-card .ic-tag  { background: var(--amber-light); color: #92400e; border: 1px solid var(--amber-mid); }
    .pengajuan-card .ic-tag { background: var(--teal-light);  color: #155e75; border: 1px solid var(--teal-mid); }

    /* ── UPLOAD SECTION ── */
    .ic-upload-section {
        width: 100%;
        overflow: hidden;
        max-height: 0;
        opacity: 0;
        transform: translateY(12px);
        transition:
            max-height 0.55s cubic-bezier(0.22,1,0.36,1),
            opacity 0.38s ease,
            transform 0.42s cubic-bezier(0.22,1,0.36,1);
        pointer-events: none;
    }

    .ic-upload-section.visible {
        max-height: 380px;
        opacity: 1;
        transform: translateY(0);
        pointer-events: all;
    }

    .ic-upload-area {
        background: var(--upload-bg);
        border: 2px dashed var(--upload-border);
        border-radius: 18px;
        padding: 2.75rem 2rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.9rem;
        cursor: pointer;
        transition: border-color 0.25s, background 0.25s, box-shadow 0.25s;
        text-align: center;
    }

    .ic-upload-area:hover,
    .ic-upload-area.dragging {
        background: #fff;
        box-shadow: 0 4px 20px rgba(0,0,0,0.07);
    }

    .ic-upload-area.active-rembush  { border-color: rgba(217,119,6,0.45); }
    .ic-upload-area.active-pengajuan { border-color: rgba(8,145,178,0.45); }

    .ic-upload-icon-wrap {
        width: 58px; height: 58px;
        border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        transition: transform 0.28s;
    }

    .ic-upload-area:hover .ic-upload-icon-wrap { transform: translateY(-4px); }

    .rembush-active .ic-upload-icon-wrap {
        background: var(--amber-light);
        border: 1px solid var(--amber-mid);
        color: var(--amber);
    }

    .pengajuan-active .ic-upload-icon-wrap {
        background: var(--teal-light);
        border: 1px solid var(--teal-mid);
        color: var(--teal);
    }

    .ic-upload-icon-wrap svg { width: 28px; height: 28px; }

    .ic-upload-title {
        font-family: 'Fraunces', serif;
        font-weight: 700;
        font-size: 1rem;
        color: var(--text-second);
    }

    .ic-upload-hint {
        font-size: 0.8rem;
        color: var(--text-faint);
        margin-top: -0.25rem;
    }

    /* Upload button */
    .ic-upload-btn {
        margin-top: 0.25rem;
        padding: 0.6rem 1.8rem;
        border-radius: 10px;
        font-size: 0.875rem;
        font-weight: 600;
        font-family: 'DM Sans', sans-serif;
        border: none;
        cursor: pointer;
        transition: all 0.22s;
        letter-spacing: 0.01em;
    }

    .ic-upload-btn.rembush-btn {
        background: var(--amber);
        color: #fff;
        box-shadow: 0 4px 14px rgba(217,119,6,0.30);
    }

    .ic-upload-btn.rembush-btn:hover {
        background: #b45309;
        box-shadow: 0 6px 20px rgba(217,119,6,0.40);
        transform: translateY(-1px);
    }

    .ic-upload-btn.pengajuan-btn {
        background: var(--teal);
        color: #fff;
        box-shadow: 0 4px 14px rgba(8,145,178,0.28);
    }

    .ic-upload-btn.pengajuan-btn:hover {
        background: #0e7490;
        box-shadow: 0 6px 20px rgba(8,145,178,0.38);
        transform: translateY(-1px);
    }

    /* File preview pill */
    .ic-file-preview {
        display: none;
        align-items: center;
        gap: 0.65rem;
        background: #fff;
        border: 1px solid var(--card-border);
        border-radius: 10px;
        padding: 0.55rem 0.9rem;
        margin-top: 0.25rem;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    }

    .ic-file-preview.show { display: flex; }

    .ic-file-preview-icon { font-size: 1.1rem; }

    .ic-file-name {
        font-size: 0.82rem;
        color: var(--text-second);
        font-weight: 500;
    }

    /* ── SUBMIT ── */
    .ic-submit-wrap {
        width: 100%;
        overflow: hidden;
        max-height: 0;
        opacity: 0;
        transition:
            max-height 0.4s cubic-bezier(0.22,1,0.36,1) 0.08s,
            opacity 0.32s ease 0.12s;
    }

    .ic-submit-wrap.visible {
        max-height: 90px;
        opacity: 1;
    }

    .ic-submit-btn {
        width: 100%;
        padding: 0.95rem;
        border: none;
        border-radius: 14px;
        font-family: 'Fraunces', serif;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.22s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        letter-spacing: -0.01em;
    }

    .ic-submit-btn.rembush-submit {
        background: var(--amber);
        color: #fff;
        box-shadow: 0 6px 24px rgba(217,119,6,0.35);
    }

    .ic-submit-btn.rembush-submit:hover {
        background: #b45309;
        box-shadow: 0 8px 32px rgba(217,119,6,0.45);
        transform: translateY(-2px);
    }

    .ic-submit-btn.pengajuan-submit {
        background: var(--teal);
        color: #fff;
        box-shadow: 0 6px 24px rgba(8,145,178,0.30);
    }

    .ic-submit-btn.pengajuan-submit:hover {
        background: #0e7490;
        box-shadow: 0 8px 32px rgba(8,145,178,0.40);
        transform: translateY(-2px);
    }

    .ic-submit-btn svg { width: 18px; height: 18px; }

    /* ── GLOBAL DRAG OVERLAY ── */
    #globalDragOverlay {
        position: fixed; inset: 0;
        display: none;
        align-items: center; justify-content: center;
        background: rgba(244,240,232,0.75);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        z-index: 70;
        transition: opacity 0.2s;
        opacity: 0;
    }

    #globalDragOverlay.show { display: flex; }

    .drag-drop-box {
        background: #fff;
        border: 2px dashed rgba(8,145,178,0.45);
        border-radius: 24px;
        padding: 2.75rem 4rem;
        text-align: center;
        box-shadow: 0 8px 40px rgba(0,0,0,0.12);
    }

    .drag-drop-box-icon {
        width: 64px; height: 64px;
        margin: 0 auto 1.1rem;
        background: var(--teal-light);
        border: 1px solid var(--teal-mid);
        border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        color: var(--teal);
    }

    .drag-drop-box h3 {
        font-family: 'Fraunces', serif;
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.3rem;
    }

    .drag-drop-box p { color: var(--text-muted); font-size: 0.85rem; }

    /* ── LOADING OVERLAY ── */
    #loadingOverlay {
        position: fixed; inset: 0;
        display: none;
        align-items: center; justify-content: center;
        background: rgba(244,240,232,0.95);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: 60;
        opacity: 0;
        transition: opacity 0.3s;
        flex-direction: column;
        gap: 0.9rem;
    }

    #loadingOverlay.show { display: flex; }

    .loading-spinner {
        width: 44px; height: 44px;
        border: 3px solid rgba(0,0,0,0.08);
        border-top-color: var(--amber);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    .loading-spinner.teal-spin { border-top-color: var(--teal); }

    .loading-title {
        font-family: 'Fraunces', serif;
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-primary);
    }

    .loading-sub {
        font-size: 0.85rem;
        color: var(--text-muted);
        margin-top: -0.4rem;
    }

    /* ── TOAST ── */
    #toast {
        position: fixed;
        top: 1.5rem; right: 1.5rem;
        display: none;
        background: #dc2626;
        color: #fff;
        padding: 0.8rem 1.3rem;
        border-radius: 12px;
        font-size: 0.875rem;
        font-weight: 500;
        box-shadow: 0 6px 24px rgba(220,38,38,0.30);
        z-index: 80;
        transform: translateY(-16px);
        opacity: 0;
        transition: all 0.28s;
    }

    /* ── KEYFRAMES ── */
    @keyframes slideDown {
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes riseCard {
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    @keyframes pulse-dot {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(0.65); }
    }
</style>

{{-- Hidden form for submission --}}
<form id="uploadForm"
    action="{{ route('rembush.upload') }}"
    method="POST"
    enctype="multipart/form-data"
    class="hidden">
    @csrf
    <input type="hidden" name="jenis" id="formJenis" value="rembush">
    <input type="file" id="file-input" name="file" accept="image/*" class="hidden">
</form>

<div class="ic-page">
    <div class="ic-wrapper">

        {{-- HEADER --}}
        <div class="ic-header">
            <div class="ic-badge">
                <span class="dot"></span>
                Sistem Pengajuan Otomatis
            </div>
            <h1 class="ic-title">
                Mulai <span class="accent-word">Pengajuan</span><br>Anda Sekarang
            </h1>
            <p class="ic-sub">
                Pilih jenis pengajuan, lalu upload foto nota atau referensi barang.
                <span class="ai-tag">✦ Didukung oleh AI Gemini</span>
            </p>
        </div>

        {{-- CARDS --}}
        <div class="ic-cards">

            {{-- REMBUSH --}}
            <div class="ic-card rembush-card" id="cardRembush" role="button" tabindex="0" aria-label="Pilih Reimbursement">
                <div class="ic-card-stripe"></div>
                <div class="ic-card-check" id="checkRembush">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" width="13" height="13">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </div>
                <div class="ic-card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                </div>
                <h3 class="ic-card-title">Reimbursement</h3>
                <p class="ic-card-desc">
                    Klaim penggantian dana untuk pengeluaran yang telah terjadi. Sertakan foto nota atau bukti transaksi.
                </p>
                <div class="ic-card-tags">
                    <span class="ic-tag">Nota / Kwitansi</span>
                    <span class="ic-tag">Dana Kembali</span>
                    <span class="ic-tag">OCR Auto</span>
                </div>
            </div>

            {{-- PENGAJUAN --}}
            <div class="ic-card pengajuan-card" id="cardPengajuan" role="button" tabindex="0" aria-label="Pilih Pengajuan Beli">
                <div class="ic-card-stripe"></div>
                <div class="ic-card-check" id="checkPengajuan">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" width="13" height="13">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </div>
                <div class="ic-card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"/>
                        <circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                </div>
                <h3 class="ic-card-title">Pengajuan Beli</h3>
                <p class="ic-card-desc">
                    Ajukan pembelian barang atau kebutuhan operasional. Sertakan referensi gambar atau spesifikasi produk.
                </p>
                <div class="ic-card-tags">
                    <span class="ic-tag">Referensi Barang</span>
                    <span class="ic-tag">Pre-Purchase</span>
                    <span class="ic-tag">Approval Flow</span>
                </div>
            </div>

        </div>

        {{-- UPLOAD ZONE (revealed after card select) --}}
        <div class="ic-upload-section" id="uploadSection">
            <div class="ic-upload-area" id="uploadArea">
                <div class="ic-upload-icon-wrap" id="uploadIconWrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="16 16 12 12 8 16"/>
                        <line x1="12" y1="12" x2="12" y2="21"/>
                        <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/>
                    </svg>
                </div>

                <div id="uploadDefault">
                    <p class="ic-upload-title">Klik atau Seret File ke Sini</p>
                    <p class="ic-upload-hint">JPG, PNG — Maks. 1MB</p>
                </div>

                <div id="uploadPreview" class="ic-file-preview relative group overflow-hidden">
                    <div class="flex items-center gap-3">
                        <img id="fileNameImg" class="w-12 h-12 object-cover rounded-lg border border-slate-200 bg-white">
                        <div class="flex flex-col items-start">
                            <span class="ic-file-name block max-w-[180px] truncate" id="fileName"></span>
                            <span class="text-[9px] text-emerald-600 font-bold uppercase tracking-wider">Siap diunggah</span>
                        </div>
                    </div>
                </div>

                <button type="button" id="select-file-btn" class="ic-upload-btn">
                    Pilih Foto Dokumen
                </button>
            </div>
        </div>

        {{-- SUBMIT BUTTON --}}
        <div class="ic-submit-wrap" id="submitWrap">
            <button type="button" id="submitBtn" class="ic-submit-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"/>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
                <span id="submitLabel">Kirim Pengajuan</span>
            </button>
        </div>

    </div>
</div>

{{-- DRAG OVERLAY --}}
<div id="globalDragOverlay">
    <div class="drag-drop-box">
        <div class="drag-drop-box-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="30" height="30">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
            </svg>
        </div>
        <h3>Drop file di sini</h3>
        <p>Maksimal ukuran 1MB</p>
    </div>
</div>

{{-- LOADING OVERLAY --}}
<div id="loadingOverlay">
    <div class="loading-spinner" id="loadingSpinner"></div>
    <p class="loading-title" id="loadingTitle">Menyiapkan Form...</p>
    <p class="loading-sub" id="loadingSub">Mohon tunggu sebentar</p>
</div>

{{-- TOAST --}}
<div id="toast"></div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const fileInput      = document.getElementById('file-input');
    const selectBtn      = document.getElementById('select-file-btn');
    const uploadPreview  = document.getElementById('uploadPreview');
    const uploadDefault  = document.getElementById('uploadDefault');
    const fileNameEl     = document.getElementById('fileName');
    const uploadSection  = document.getElementById('uploadSection');
    const uploadArea     = document.getElementById('uploadArea');

    const submitWrap     = document.getElementById('submitWrap');
    const submitBtn      = document.getElementById('submitBtn');
    const submitLabel    = document.getElementById('submitLabel');

    const cardRembush    = document.getElementById('cardRembush');
    const cardPengajuan  = document.getElementById('cardPengajuan');

    const form           = document.getElementById('uploadForm');
    const formJenis      = document.getElementById('formJenis');

    const loadingOverlay = document.getElementById('loadingOverlay');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const loadingTitle   = document.getElementById('loadingTitle');
    const loadingSub     = document.getElementById('loadingSub');

    const globalOverlay  = document.getElementById('globalDragOverlay');
    const toast          = document.getElementById('toast');

    const MAX_SIZE = 1024 * 1024;
    let selectedFile = null;
    let selectedType = null;

    // ── CARD SELECTION ────────────────────────────────────
    function selectCard(type) {
        if (type === 'pengajuan') {
            cardPengajuan.classList.add('selected');
            cardRembush.classList.remove('selected');
            loadingSpinner.classList.add('teal-spin');
            showLoading('Menyiapkan Form Pengajuan...', 'Mohon tunggu sebentar');
            setTimeout(() => {
                window.location.href = "{{ route('pengajuan.form') }}";
            }, 400);
            return;
        }

        selectedType = type;

        cardRembush.classList.toggle('selected', type === 'rembush');
        cardPengajuan.classList.toggle('selected', type === 'pengajuan');

        uploadArea.classList.toggle('active-rembush',   type === 'rembush');
        uploadArea.classList.toggle('active-pengajuan',  type === 'pengajuan');

        uploadSection.classList.toggle('rembush-active',   type === 'rembush');
        uploadSection.classList.toggle('pengajuan-active',  type === 'pengajuan');

        selectBtn.classList.remove('rembush-btn', 'pengajuan-btn');
        selectBtn.classList.add(type === 'rembush' ? 'rembush-btn' : 'pengajuan-btn');

        submitBtn.classList.remove('rembush-submit', 'pengajuan-submit');
        submitBtn.classList.add(type === 'rembush' ? 'rembush-submit' : 'pengajuan-submit');

        submitLabel.textContent = type === 'rembush' ? 'Kirim Reimbursement' : 'Kirim Pengajuan Beli';

        uploadSection.classList.add('visible');

        setTimeout(() => {
            uploadSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 200);

        if (selectedFile) updateSubmitState();
    }

    cardRembush.addEventListener('click', () => selectCard('rembush'));
    cardPengajuan.addEventListener('click', () => selectCard('pengajuan'));
    cardRembush.addEventListener('keydown', (e)  => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); selectCard('rembush'); } });
    cardPengajuan.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); selectCard('pengajuan'); } });

    // ── FILE SELECT ────────────────────────────────────────
    selectBtn.addEventListener('click', (e) => { e.stopPropagation(); fileInput.click(); });

    uploadArea.addEventListener('click', function () {
        if (!selectedType) { showToast('Pilih jenis pengajuan terlebih dahulu!'); return; }
        fileInput.click();
    });

    const fileNameImg    = document.getElementById('fileNameImg');

    fileInput.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            if (file.size > MAX_SIZE) {
                showToast('Ukuran file terlalu besar (Maks. 1MB)');
                this.value = '';
                resetUploadUI();
                return;
            }

            selectedFile = file;
            fileNameEl.textContent = file.name;

            // Image preview logic
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    fileNameImg.src = e.target.result;
                    uploadPreview.classList.add('show');
                    uploadDefault.classList.add('hidden');
                    submitWrap.classList.add('visible');
                    
                    // Add success effect to upload area
                    uploadArea.classList.add(selectedType === 'rembush' ? 'active-rembush' : 'active-pengajuan');
                }
                reader.readAsDataURL(file);
            }
        } else {
            resetUploadUI();
        }
    });

    function resetUploadUI() {
        uploadPreview.classList.remove('show');
        uploadDefault.classList.remove('hidden');
        submitWrap.classList.remove('visible');
        uploadArea.classList.remove('active-rembush', 'active-pengajuan');
        fileNameImg.src = '';
        fileNameEl.textContent = '';
        selectedFile = null;
    }

    function updateSubmitState() {
        submitWrap.classList.toggle('visible', !!(selectedFile && selectedType));
    }

    // ── DRAG / DROP (upload area) ─────────────────────────
    uploadArea.addEventListener('dragover', (e) => { e.preventDefault(); uploadArea.classList.add('dragging'); });
    uploadArea.addEventListener('dragleave', ()  => uploadArea.classList.remove('dragging'));
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragging');
        if (!selectedType) { showToast('Pilih jenis pengajuan terlebih dahulu!'); return; }
        if (e.dataTransfer.files.length) { fileInput.files = e.dataTransfer.files; fileInput.dispatchEvent(new Event('change')); }
    });

    // ── GLOBAL DRAG ────────────────────────────────────────
    let dragCtr = 0;
    window.addEventListener('dragenter', (e) => {
        e.preventDefault(); dragCtr++;
        globalOverlay.classList.add('show');
        setTimeout(() => globalOverlay.style.opacity = '1', 10);
    });
    window.addEventListener('dragleave', () => {
        dragCtr--;
        if (dragCtr === 0) { globalOverlay.style.opacity = '0'; setTimeout(() => globalOverlay.classList.remove('show'), 200); }
    });
    window.addEventListener('dragover', (e) => e.preventDefault());
    window.addEventListener('drop', (e) => {
        e.preventDefault(); dragCtr = 0;
        globalOverlay.style.opacity = '0';
        setTimeout(() => globalOverlay.classList.remove('show'), 200);
        if (e.dataTransfer.files.length) {
            if (e.dataTransfer.files[0].size > MAX_SIZE) { showToast('Ukuran file maksimal 1MB!'); return; }
            fileInput.files = e.dataTransfer.files;
            fileInput.dispatchEvent(new Event('change'));
        }
    });

    // ── SUBMIT ─────────────────────────────────────────────
    submitBtn.addEventListener('click', function () {
        if (!selectedFile || !selectedType) return;
        loadingSpinner.classList.toggle('teal-spin', selectedType === 'pengajuan');
        showLoading(
            selectedType === 'rembush' ? 'Menyiapkan Form Rembush...' : 'Menyiapkan Form Pengajuan...',
            'Mohon tunggu sebentar'
        );
        setTimeout(() => {
            formJenis.value = selectedType;
            form.action = selectedType === 'rembush'
                ? "{{ route('rembush.upload') }}"
                : "{{ route('pengajuan.upload') }}";
            form.submit();
        }, 400);
    });

    // ── LOADING ────────────────────────────────────────────
    function showLoading(title, subtitle) {
        loadingTitle.textContent = title;
        loadingSub.textContent   = subtitle;
        loadingOverlay.classList.add('show');
        setTimeout(() => loadingOverlay.style.opacity = '1', 10);
    }

    // ── TOAST ──────────────────────────────────────────────
    function showToast(msg) {
        toast.textContent = msg;
        toast.style.display = 'block';
        setTimeout(() => { toast.style.opacity = '1'; toast.style.transform = 'translateY(0)'; }, 10);
        setTimeout(() => {
            toast.style.opacity = '0'; toast.style.transform = 'translateY(-16px)';
            setTimeout(() => toast.style.display = 'none', 280);
        }, 3000);
    }

});
</script>
@endpush
