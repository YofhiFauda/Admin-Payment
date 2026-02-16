<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AiAutoFillController extends Controller
{
    /**
     * Receive AI-extracted data from N8N and store in Cache
     * NO database write — data stored temporarily for form auto-fill
     */
    public function store(Request $request)
    {
        // 🔐 Security
        if ($request->header('X-SECRET') !== env('N8N_SECRET')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // ✅ Validation
        $validator = Validator::make($request->all(), [
            'upload_id'  => 'required|string',
            'customer'   => 'nullable|string|max:255',
            'amount'     => 'nullable|numeric',
            'date'       => 'nullable|date',
            'items'      => 'nullable|string',
            'confidence' => 'required|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Parse date
        $date = null;
        if ($request->date) {
            try {
                $date = Carbon::parse($request->date)->format('Y-m-d');
            } catch (\Exception $e) {
                $date = null;
            }
        }

        // Store in Cache (expires in 30 minutes)
        $cacheKey = "ai_autofill:{$request->upload_id}";

        Cache::put($cacheKey, [
            'status'     => 'completed',
            'customer'   => $request->customer,
            'amount'     => $request->amount,
            'date'       => $date,
            'items'      => $request->items,
            'confidence' => $request->confidence,
        ], now()->addMinutes(30));

        Log::info('AI Auto Fill stored in cache', [
            'upload_id'  => $request->upload_id,
            'customer'   => $request->customer,
            'amount'     => $request->amount,
            'confidence' => $request->confidence,
        ]);

        return response()->json(['success' => true]);
    }
}
