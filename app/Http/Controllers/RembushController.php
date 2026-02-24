<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class RembushController extends Controller
{
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  UPLOAD — Handle nota upload + send to N8N OCR
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function processUpload(Request $request)
    {
        Log::channel('ocr')->info('=== START REMBUSH UPLOAD ===');

        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:1024',
        ]);

        $file = $request->file('file');
        $uploadId = 'nota-' . round(microtime(true) * 1000);

        Log::channel('ocr')->info('Upload ID generated', [
            'upload_id' => $uploadId,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        $extension = $file->getClientOriginalExtension();
        $fileName = $uploadId . '.' . $extension;
        $storagePath = 'temp-uploads/' . $fileName;

        Storage::disk('public')->put($storagePath, file_get_contents($file));

        $base64 = base64_encode(file_get_contents($file));
        $mime = $file->getMimeType();

        session([
            'upload_id' => $uploadId,
            'upload_file_path' => $storagePath,
            'upload_file_base64' => $base64,
            'upload_file_mime' => $mime,
        ]);

        $this->sendToN8N($uploadId, storage_path('app/public/' . $storagePath));

        return redirect()->route('rembush.loading');
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  LOADING — Wait for OCR
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function loading()
    {
        $uploadId = session('upload_id');

        if (!$uploadId) {
            return redirect()->route('transactions.create');
        }

        return view('transactions.loading', compact('uploadId'));
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  FORM — Rembush form after OCR
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function showForm()
    {
        $uploadId = session('upload_id');
        $base64 = session('upload_file_base64');
        $mime = session('upload_file_mime');

        if (!$uploadId || !$base64) {
            return redirect()->route('transactions.create')
                ->withErrors(['error' => 'Silakan unggah nota terlebih dahulu']);
        }

        // Check AI cache for auto-fill data
        $cacheKey = "ai_autofill:{$uploadId}";
        $aiData = Cache::get($cacheKey);

        if ($aiData) {
            session(['ai_data' => $aiData]);
            Log::info('AI Data loaded from cache to session', [
                'upload_id' => $uploadId,
                'customer' => $aiData['customer'] ?? null,
            ]);
        }

        $branches = Branch::all();

        return view('transactions.form', compact('branches', 'base64', 'mime', 'uploadId'));
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  STORE — Save Rembush Transaction
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function store(Request $request)
    {
        $request->validate([
            'customer'       => 'required|string|max:255',
            'category'       => 'required|string|in:' . implode(',', array_keys(Transaction::CATEGORIES)),
            'amount'         => 'required|numeric|min:1',
            'description'    => 'nullable|string|max:2000',
            'payment_method' => 'required|string|in:' . implode(',', array_keys(Transaction::PAYMENT_METHODS)),
            'items'          => 'nullable|array',
            'date'           => 'nullable|date',
            'branches'       => 'nullable|array',
            'branches.*.branch_id' => 'required_with:branches|exists:branches,id',
            'branches.*.allocation_percent' => 'required_with:branches|numeric|min:0|max:100',
            'branches.*.allocation_amount' => 'nullable|numeric|min:0',
        ]);

        // Validate branch allocation if provided
        if ($request->branches && count($request->branches) > 0) {
            $branchIds = collect($request->branches)->pluck('branch_id');
            if ($branchIds->count() !== $branchIds->unique()->count()) {
                return back()->withErrors(['branches' => 'Cabang tidak boleh duplikat.'])->withInput();
            }

            $totalPercent = collect($request->branches)->sum('allocation_percent');
            if (abs($totalPercent - 100) > 1) {
                return back()->withErrors(['branches' => 'Total alokasi harus 100%.'])->withInput();
            }
        }

        // Get upload info from session
        $uploadFilePath = session('upload_file_path');

        if (!$uploadFilePath) {
            return back()
                ->withErrors(['error' => 'File nota tidak ditemukan. Silakan upload ulang.'])
                ->withInput();
        }

        DB::beginTransaction();

        try {
            // Move file from temp to permanent storage
            $permanentPath = str_replace('temp-uploads/', '', $uploadFilePath);

            if (Storage::disk('public')->exists($uploadFilePath)) {
                $fileContent = Storage::disk('public')->get($uploadFilePath);
                Storage::disk('public')->put($permanentPath, $fileContent);
                Storage::disk('public')->delete($uploadFilePath);
            } else {
                $permanentPath = $uploadFilePath;
            }

            $transaction = Transaction::create([
                'type'           => Transaction::TYPE_REMBUSH,
                'invoice_number' => Transaction::generateInvoiceNumber(),
                'customer'       => $request->customer,
                'category'       => $request->category,
                'description'    => $request->description,
                'payment_method' => $request->payment_method,
                'amount'         => $request->amount,
                'items'          => $request->items,
                'date'           => $request->date ?? now()->format('Y-m-d'),
                'file_path'      => $permanentPath,
                'status'         => 'pending',
                'submitted_by'   => Auth::id(),
            ]);

            // Attach branches if provided
            if ($request->branches && count($request->branches) > 0) {
                foreach ($request->branches as $branchData) {
                    $allocPercent = floatval($branchData['allocation_percent']);
                    $allocAmount = isset($branchData['allocation_amount']) && $branchData['allocation_amount']
                        ? intval($branchData['allocation_amount'])
                        : intval(round(($transaction->amount * $allocPercent) / 100));

                    $transaction->branches()->attach($branchData['branch_id'], [
                        'allocation_percent' => $allocPercent,
                        'allocation_amount'  => $allocAmount,
                    ]);
                }
            }

            DB::commit();

            session()->forget([
                'upload_id',
                'upload_file_path',
                'upload_file_base64',
                'upload_file_mime',
                'ai_data',
            ]);

            return redirect()->route('transactions.confirm', $transaction->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Rembush store failed', ['error' => $e->getMessage()]);
            return back()->withInput()
                ->withErrors(['error' => 'Gagal menyimpan transaksi: ' . $e->getMessage()]);
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  N8N — Send image for OCR processing
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    private function sendToN8N(string $uploadId, string $localFilePath)
    {
        Log::channel('ocr')->info('Sending to N8N started', [
            'upload_id' => $uploadId,
            'file_path' => $localFilePath,
        ]);

        try {
            $webhookUrl = rtrim(env('N8N_WEBHOOK'), '/') . '?upload_id=' . $uploadId;

            $response = Http::asMultipart()
                ->withHeaders([
                    'X-SECRET' => env('N8N_SECRET'),
                    'X-Upload-ID' => $uploadId,
                ])
                ->attach('data', fopen($localFilePath, 'r'), basename($localFilePath))
                ->timeout(60)
                ->post($webhookUrl, [
                    'upload_id' => $uploadId
                ]);

            Log::channel('ocr')->info('N8N Response Received', [
                'upload_id' => $uploadId,
                'status_code' => $response->status(),
                'body' => $response->body(),
            ]);

        } catch (\Exception $e) {
            Log::channel('ocr')->error('N8N CONNECTION FAILED', [
                'upload_id' => $uploadId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
