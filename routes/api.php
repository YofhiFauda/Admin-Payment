<?php

use App\Http\Controllers\Api\AiAutoFillController;
use Illuminate\Support\Facades\Cache;

// ─── N8N Callback (dari n8n Cloud) ──────────────────────────────
// Menerima hasil OCR dan simpan ke Redis Cache
Route::post('/ai/auto-fill', [AiAutoFillController::class, 'store'])
    ->middleware('throttle:ai-auto-fill');

// ─── Polling Endpoint (dari loading.blade.php) ───────────────────
// Frontend polling tiap 2 detik untuk cek status OCR
Route::get('/ai/auto-fill/status/{uploadId}', [AiAutoFillController::class, 'status'])
    ->middleware('throttle:60,1');  // Max 60 poll per menit

// Legacy route (backward compatibility)
Route::get('/ai/ai-status/{uploadId}', [AiAutoFillController::class, 'status']);

// ─── ✅ BARU: Admin Monitoring ───────────────────────────────────
Route::get('/admin/ocr-status', [AiAutoFillController::class, 'ocrStatus'])
    ->middleware(['auth:sanctum', 'throttle:30,1']);

// ✅ BENAR: Sesuaikan dengan middleware yang dipakai app
Route::middleware('auth')->get('/notifications/unread-count', function () {
    return response()->json([
        'count' => auth()->user()->unreadNotifications()
                    ->where('data->type', 'ocr_status')
                    ->count()
    ]);
});