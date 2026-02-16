<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AiAutoFillController extends Controller
{
    public function store(Request $request)
    {
        // 🔐 Security - FIXED: Use correct env variable
        if ($request->header('X-SECRET') !== env('N8N_SECRET')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // ✅ Validation - FIXED: Use correct field names matching database
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|integer|exists:transactions,id',
            'customer'       => 'nullable|string|max:255',
            'amount'         => 'nullable|numeric',
            'date'           => 'nullable|date',
            'items'          => 'nullable|string',
            'confidence'     => 'required|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $transaction = Transaction::find($request->transaction_id);

        // Parse date if provided
        $date = null;
        if ($request->date) {
            try {
                $date = Carbon::parse($request->date)->format('Y-m-d');
            } catch (\Exception $e) {
                $date = null;
            }
        }

        // FIXED: Update with correct field names and set ai_status to completed
        $transaction->update([
            'customer'    => $request->customer,
            'amount'      => $request->amount,
            'date'        => $date,
            'items'       => $request->items,
            'confidence'  => $request->confidence,
            'ai_status'   => 'completed', // CRITICAL: Mark AI processing as completed
        ]);

        Log::info('AI Auto Fill Updated', [
            'transaction_id' => $transaction->id,
            'customer' => $request->customer,
            'amount' => $request->amount,
            'confidence' => $request->confidence
        ]);

        return response()->json(['success' => true]);
    }
}

