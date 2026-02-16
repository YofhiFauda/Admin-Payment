<?php

use App\Http\Controllers\Api\AiAutoFillController;

Route::post('/ai/auto-fill', [AiAutoFillController::class, 'store'])->middleware('throttle:ai-auto-fill');

Route::get('/transactions/{id}/ai-status', function ($id) {

    $transaction = \App\Models\Transaction::find($id);

    if (!$transaction) {
        return response()->json(['status' => 'not_found']);
    }

    return response()->json([
        'ai_status' => $transaction->ai_status,
        'customer' => $transaction->customer,
        'amount' => $transaction->amount,
        'date' => $transaction->date,
        'items' => $transaction->items,
        'confidence' => $transaction->confidence
    ]);
});



