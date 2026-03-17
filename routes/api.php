<?php

/**
 * ═══════════════════════════════════════════════════════════════
 *  api.php — FULL FIX
 *
 *  ✅ Bug #6: Override & Force Approve endpoints use auth:web
 *     (frontend sends X-CSRF-TOKEN via session, not Sanctum bearer token)
 *  ✅ Cash/Transfer payment endpoints juga perlu auth:web
 *     karena dipanggil dari frontend modal via AJAX
 * ═══════════════════════════════════════════════════════════════
 */

use App\Http\Controllers\Api\AiAutoFillController;
use App\Http\Controllers\Api\TelegramWebhookController;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Api\V1\OcrNotaController;
use App\Http\Controllers\Api\PaymentVerificationController;
use App\Http\Middleware\N8nSecretMiddleware;
use App\Http\Middleware\CheckRole;



// ─── Telegram Bot Webhook (harus PUBLIC, tidak perlu auth) ──────
// Telegram server mengirim POST ke URL ini setiap ada pesan baru masuk ke bot.
// Verifikasi menggunakan secret_token header (opsional, untuk keamanan).
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->name('telegram.webhook');


// ─── PUBLIC ENDPOINTS (v1) ───────────────────────────────────────
Route::prefix('v1')->group(function () {

    // Flow 1 - Upload Nota
    Route::post('/nota/upload', [OcrNotaController::class, 'uploadNota']);
    Route::get('/transaksi', [OcrNotaController::class, 'index']);
    Route::get('/transaksi/{id}', [OcrNotaController::class, 'show']);

    // Flow 2 (Cash)
    Route::post('/payment/cash/upload', [OcrNotaController::class, 'uploadCash']);
    Route::post('/payment/cash/konfirmasi', [OcrNotaController::class, 'konfirmasiCash']);

    // Flow 3 (Transfer)
    Route::post('/payment/transfer/upload', [OcrNotaController::class, 'uploadTransfer']);

    // Banks dictionary removed (Moved to UserBankAccount system)
});


// ─── N8N Callback (dari n8n Cloud) ──────────────────────────────
Route::middleware(N8nSecretMiddleware::class)->group(function () {
    // Menerima hasil OCR dan simpan ke Redis Cache
    Route::post('/ai/auto-fill', [AiAutoFillController::class, 'store']);
    // ✅ FIX Bug: Typo dari n8n callback url (kurang huruf 'l' di belakang)
    // Menangkap POST /api/ai/auto-fil dan meroutekannya ke endpoint yang benar
    Route::post('/ai/auto-fil', [AiAutoFillController::class, 'store'])
        ->middleware('throttle:ai-auto-fill');


    // ─── Callback dari n8n: OCR Bukti Transfer/Cash ──────────
    // ✅ FIXED: Route baru untuk payment verification
    // n8n workflow akan callback ke endpoint ini setelah OCR bukti transfer
    Route::post('/payment/verify', [PaymentVerificationController::class, 'handle'])
        ->middleware('throttle:ai-auto-fill')
        ->name('api.payment.verify');

    // Menerima update status pembayaran (dari AI Transfer atau Telegram Cash)
    // ─── Legacy: Update Status Pembayaran ────────────────────
    // DEPRECATED: Untuk backward compatibility, akan dihapus nanti
    // Redirect ke endpoint baru /payment/verify
    Route::post('/pembayaran/update-status', function (Illuminate\Http\Request $request) {
        // Forward request ke PaymentVerificationController
        return app(PaymentVerificationController::class)->handle($request);
    })->middleware('throttle:ai-auto-fill');
});


// ─── Polling Endpoint (dari loading.blade.php) ───────────────────
Route::get('/ai/auto-fill/status/{uploadId}', [AiAutoFillController::class, 'status'])
    ->middleware('throttle:60,1');

// Legacy route (backward compatibility)
Route::get('/ai/ai-status/{uploadId}', [AiAutoFillController::class, 'status']);


// ─── Admin Monitoring ───────────────────────────────────
// ✅ FIX Bug #6: Juga ganti ke auth:web jika diakses dari dashboard admin
Route::get('/admin/ocr-status', [AiAutoFillController::class, 'ocrStatus'])
    ->middleware(['auth:web', 'throttle:30,1']);


// ─── Notifications ──────────────────────────────────────
Route::middleware('auth')->get('/notifications/unread-count', function () {
    return response()->json([
        'count' => auth()->user()->unreadNotifications()
            ->where('data->type', 'ocr_status')
            ->count(),
    ]);
});