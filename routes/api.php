<?php

use App\Http\Controllers\Api\AiAutoFillController;
use Illuminate\Support\Facades\Cache;

// N8N callback endpoint — stores AI results in Cache
Route::post('/ai/auto-fill', [AiAutoFillController::class, 'store'])->middleware('throttle:ai-auto-fill');

// Polling endpoint — reads from Cache (no database query)
Route::get('/ai-status/{uploadId}', function ($uploadId) {

    $data = Cache::get("ai_autofill:{$uploadId}");

    if (!$data) {
        return response()->json(['status' => 'processing']);
    }

    return response()->json($data);
});
