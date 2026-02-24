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

        // ✅ Resolve upload_id — prioritas: query string > header > body
        // N8N kadang mengirim body upload_id sebagai template literal {{ $json.upload_id }}
        $queryId  = $request->query('upload_id');
        $headerId = $request->header('X-Upload-ID');
        $bodyId   = $request->upload_id;

        // Reject N8N template literals ({{ ... }})
        $isValidId = function($id) {
            return $id && is_string($id) && !str_contains($id, '{{') && !str_contains($id, '}}');
        };

        $uploadId = null;
        if ($isValidId($queryId))  $uploadId = $queryId;
        elseif ($isValidId($headerId)) $uploadId = $headerId;
        elseif ($isValidId($bodyId))   $uploadId = $bodyId;

        // 🔍 Debug log — SEBELUM cache store
        Log::channel('ocr')->info('AI Auto Fill incoming', [
            'resolved_upload_id' => $uploadId,
            'query_upload_id'    => $queryId,
            'header_upload_id'   => $headerId,
            'body_upload_id'     => $bodyId,
            'confidence'         => $request->confidence,
        ]);

        // ✅ VALIDASI upload_id WAJIB ADA & VALID
        if (!$uploadId) {
            Log::channel('ocr')->error('AI Auto Fill FAILED - Missing or invalid upload_id', [
                'query'  => $queryId,
                'header' => $headerId,
                'body'   => $bodyId,
            ]);
            return response()->json(['message' => 'upload_id is required and must not be a template literal'], 422);
        }

        // ✅ Validation
        $validator = Validator::make($request->all(), [
            'customer'   => 'nullable|string|max:255',
            'amount'     => 'nullable|numeric',
            'date'       => 'nullable|date',
            'items' => 'nullable|array',
            'items.*.nama_barang' => 'nullable|string',
            'items.*.qty' => 'nullable|numeric',
            'items.*.satuan' => 'nullable|string',
            'items.*.harga_satuan' => 'nullable|numeric',
            'items.*.total_harga' => 'nullable|numeric',
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

        // ✅ NORMALISASI FIELD N8N ke Format Form
        $items = [];
        if ($request->items && is_array($request->items)) {
            foreach ($request->items as $item) {
                $items[] = [
                    // ✅ Support both N8N & form field names
                    'nama_barang'      => $item['nama_barang'] ?? $item['name'] ?? '',
                    'name'             => $item['nama_barang'] ?? $item['name'] ?? '',  // ✅ Add form-compatible key
                    'qty'              => $item['qty'] ?? 1,
                    'satuan'           => $item['satuan'] ?? $item['unit'] ?? 'pcs',
                    'unit'             => $item['satuan'] ?? $item['unit'] ?? 'pcs',    // ✅ Add form-compatible key
                    'harga_satuan'     => $item['harga_satuan'] ?? $item['price'] ?? 0,
                    'price'            => $item['harga_satuan'] ?? $item['price'] ?? 0, // ✅ Add form-compatible key
                    'total_harga'      => $item['total_harga'] ?? 0,
                    'deskripsi_kalimat'=> $item['deskripsi_kalimat'] ?? $item['desc'] ?? '',
                    'desc'             => $item['deskripsi_kalimat'] ?? $item['desc'] ?? '', // ✅ Add form-compatible key
                ];
            }
        }

        // Store in Cache (expires in 30 minutes)
        $cacheKey = "ai_autofill:{$uploadId}";

        // ✅ Normalisasi field utama juga
        $cacheData = [
            'status'     => 'completed',
            'upload_id'  => $uploadId,
            // ✅ Support both N8N & form field names
            'customer'   => $request->nama_toko ?? $request->customer ?? '',
            'nama_toko'  => $request->nama_toko ?? $request->customer ?? '',  // ✅ Keep original
            'amount'     => $request->total_belanja ?? $request->amount ?? 0,
            'total_belanja' => $request->total_belanja ?? $request->amount ?? 0,  // ✅ Keep original
            'date'       => $date,
            'tanggal'    => $date,  // ✅ Keep original
            'items'      => $items,
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
            'status' => $data['status'] ?? 'unknown',
        ]);

        if (!$data) {
            return response()->json([
                'status' => 'processing',
                'phase'  => 'scanning',
            ]);
        }

        // ✅ Handle error status — tell frontend to show fallback
        if (($data['status'] ?? '') === 'error') {
            return response()->json([
                'status'  => 'error',
                'message' => $data['message'] ?? 'Terjadi kesalahan.',
            ]);
        }

        // ✅ Handle completed status
        if (($data['status'] ?? '') === 'completed') {
            return response()->json([
                'status' => 'completed',
                'data'   => $data,
            ]);
        }

        // ✅ Processing with phase info
        return response()->json([
            'status' => 'processing',
            'phase'  => $data['phase'] ?? 'scanning',
        ]);
    }
}