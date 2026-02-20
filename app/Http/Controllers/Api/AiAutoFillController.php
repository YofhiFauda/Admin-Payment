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
     */
    public function store(Request $request)
    {
        // 🔐 Security
        if ($request->header('X-SECRET') !== config('services.n8n.secret')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // ✅ PRIORITASKAN upload_id dari query string (dari N8N webhook)
        $uploadId = $request->query('upload_id') ?? $request->header('X-Upload-ID') ?? $request->upload_id;

        // ✅ Validation
        $validator = Validator::make($request->all(), [
            'customer'   => 'nullable|string|max:255',
            'amount'     => 'nullable|numeric',
            'date'       => 'nullable|date',
            'items'      => 'nullable|string',
            'confidence' => 'required|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // ✅ VALIDASI upload_id WAJIB ADA
        if (!$uploadId) {
            Log::error('AI Auto Fill FAILED - Missing upload_id', [
                'query' => $request->query('upload_id'),
                'header' => $request->header('X-Upload-ID'),
                'body' => $request->upload_id,
            ]);
            return response()->json(['message' => 'upload_id is required'], 422);
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
        $cacheKey = "ai_autofill:{$uploadId}";

        $cacheData = [
            'status'     => 'completed',
            'upload_id'  => $uploadId, // ✅ SIMPAN upload_id DI CACHE
            'customer'   => $request->customer,
            'amount'     => $request->amount,
            'date'       => $date,
            'items'      => $request->items,
            'confidence' => $request->confidence,
        ];

        Cache::put($cacheKey, $cacheData, now()->addMinutes(30));

        Log::channel('ocr')->info('AI Auto Fill stored in cache', $cacheData);

        return response()->json(['success' => true]);
    }

    /**
     * ✅ POLLING ENDPOINT - Read from Cache
     * Called by Loading Page to check if AI processing is done
     */
    public function status($uploadId)
    {
        $cacheKey = "ai_autofill:{$uploadId}";
        $data = Cache::get($cacheKey);

        Log::info('AI Status Polling', [
            'upload_id' => $uploadId,
            'found' => $data !== null,
        ]);

        if ($data) {
            return response()->json([
                'status' => 'completed',
                'data' => $data,
            ]);
        }

        return response()->json([
            'status' => 'processing',
        ]);
    }
}