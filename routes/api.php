<?php

use App\Http\Controllers\Api\AiAutoFillController;
use Illuminate\Support\Facades\Cache;

// N8N callback endpoint — stores AI results in Cache
Route::post('/ai/auto-fill', [AiAutoFillController::class, 'store']);

// Polling endpoint — reads from Cache (no database query)
Route::get('/ai/ai-status/{uploadId}', [AiAutoFillController::class, 'status']);

