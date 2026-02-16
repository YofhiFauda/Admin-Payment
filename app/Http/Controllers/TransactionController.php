<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;


class TransactionController extends Controller
{
    /**
     * History / List page with stats
     */
    public function index(Request $request)
    {
        $query = Transaction::with(['submitter', 'branches'])->latest();

        // Search filter
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('customer', 'like', "%{$search}%")
                  ->orWhere('invoice_number', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status = $request->get('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        $transactions = $query->paginate(20);

        // Stats - Efficient SQL aggregates
        $stats = [
            'total' => Transaction::sum('amount'),
            'pending' => Transaction::where('status', 'pending')->count(),
            'approved' => Transaction::where('status', 'approved')->count(),
            'completed' => Transaction::where('status', 'completed')->count(),
            'count' => Transaction::count(),
        ];

        return view('transactions.index', compact('transactions', 'stats'));
    }

    /**
     * Step 1: Upload page
     */
    public function create()
    {
        return view('transactions.create');
    }

    /**
     * Handle file upload → redirect to step 2
     * Route name: transactions.upload
     */
    public function processUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $file = $request->file('file');

        // Encode file ke base64
        $base64 = base64_encode(file_get_contents($file));
        $mime = $file->getMimeType();

        session([
            'upload_file_base64' => $base64,
            'upload_file_mime' => $mime,
            'upload_file_name' => $file->getClientOriginalName(),
        ]);

        return redirect()->route('transactions.form');
    }


    /**
     * Step 2: Form with branch allocation
     */
    public function showForm()
    {
        $base64 = session('upload_file_base64');
        $mime = session('upload_file_mime');
        $transactionId = session('draft_transaction_id');


        if (!$base64) {
            return redirect()->route('transactions.create')
                ->withErrors(['error' => 'Silakan unggah nota terlebih dahulu']);
        }

        $branches = Branch::all();

        return view('transactions.form', compact('branches', 'base64', 'mime', 'transactionId'));
    }


    /**
     * Store transaction
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'items' => 'nullable|string',
            'date' => 'nullable|date',
            'branches' => 'required|array|min:1',
            'branches.*.branch_id' => 'required|exists:branches,id',
            'branches.*.allocation_percent' => 'required|numeric|min:0|max:100',
            'branches.*.allocation_amount' => 'nullable|numeric|min:0',
        ]);

        // Validate unique branches
        $branchIds = collect($request->branches)->pluck('branch_id');
        if ($branchIds->count() !== $branchIds->unique()->count()) {
            return back()
                ->withErrors(['branches' => 'Cabang tidak boleh duplikat.'])
                ->withInput();
        }

        // Validate total allocation is ~100%
        $totalPercent = collect($request->branches)->sum('allocation_percent');
        if (abs($totalPercent - 100) > 1) {
            return back()
                ->withErrors(['branches' => 'Total alokasi harus 100%.'])
                ->withInput();
        }

        /**
         * ==============================
         *  🔥 AMBIL FILE DARI SESSION
         * ==============================
         */
        $base64 = session('upload_file_base64');
        $mime = session('upload_file_mime');
        $fileName = session('upload_file_name');

        if (!$base64 || !$fileName) {
            return back()
                ->withErrors(['error' => 'File nota tidak ditemukan. Silakan upload ulang.'])
                ->withInput();
        }

        /**
         * ==============================
         *  🔥 SIMPAN FILE KE STORAGE
         * ==============================
         */
        $decodedFile = base64_decode($base64);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = time() . '_' . uniqid() . '.' . $extension;

        $storagePath = 'nota-uploads/' . $newFileName;

        Storage::disk('public')->put($storagePath, $decodedFile);

        /**
         * ==============================
         *  🔥 BUAT TRANSACTION
         * ==============================
         */
        DB::beginTransaction();

        try {

            $transaction = Transaction::create([
                'invoice_number' => Transaction::generateInvoiceNumber(),
                'customer' => $request->customer,
                'amount' => $request->amount,
                'items' => $request->items,
                'date' => $request->date ?? now()->format('Y-m-d'),
                'file_path' => $storagePath,
                'status' => 'pending',
                'submitted_by' => Auth::id(),
            ]);

            foreach ($request->branches as $branchData) {

                $allocPercent = floatval($branchData['allocation_percent']);

                $allocAmount = isset($branchData['allocation_amount']) && $branchData['allocation_amount']
                    ? intval($branchData['allocation_amount'])
                    : intval(round(($transaction->amount * $allocPercent) / 100));

                $transaction->branches()->attach($branchData['branch_id'], [
                    'allocation_percent' => $allocPercent,
                    'allocation_amount' => $allocAmount,
                ]);
            }

            DB::commit();
            /**
             * ==============================
             * 🔥 KIRIM KE N8N (AI PARSING)
             * ==============================
             */

            try {

                // Ambil file dari storage
                $fileContent = Storage::disk('public')->get($storagePath);

                // Convert ke base64
                $imageBase64 = base64_encode($fileContent);

                Http::timeout(60)
                    ->withHeaders([
                        'X-SECRET' => env('N8N_SECRET')
                    ])
                    ->post('https://wases.app.n8n.cloud/webhook/upload-nota', [
                        'transaction_id' => $transaction->id,
                        'image_base64' => $imageBase64,
                    ]);

            } catch (\Exception $e) {

                Log::error('Gagal kirim ke N8N', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage()
                ]);
            }


            /**
             * ==============================
             *  🔥 CLEAR SESSION
             * ==============================
             */
            session()->forget([
                'upload_file_base64',
                'upload_file_mime',
                'upload_file_name'
            ]);

            return redirect()->route('transactions.confirm', $transaction->id);

        } catch (\Exception $e) {

            DB::rollBack();

            return back()
                ->withInput()
                ->withErrors(['error' => 'Gagal menyimpan transaksi: ' . $e->getMessage()]);
        }
    }


    /**
     * Step 3: Confirmation page
     */
    public function confirmation($id)
    {
        try {
            $transaction = Transaction::with(['submitter', 'branches'])->findOrFail($id);
            
            // Verify file exists
            $fileExists = $transaction->file_path && Storage::disk('public')->exists($transaction->file_path);
            
            return view('transactions.confirm', compact('transaction', 'fileExists'));
            
        } catch (\Exception $e) {
            return redirect()->route('transactions.index')
                ->withErrors(['error' => 'Transaksi tidak ditemukan']);
        }
    }

    /**
     * Update transaction status (admin/atasan/owner only)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,completed,rejected',
        ]);

        try {
            $transaction = Transaction::findOrFail($id);
            $oldStatus = $transaction->status;
            
            $transaction->update(['status' => $request->status]);
            
            Log::info('Transaction status updated', [
                'transaction_id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'updated_by' => Auth::id(),
            ]);

            return back()->with('success', "Nota {$transaction->invoice_number} diubah ke: " . strtoupper($request->status));
            
        } catch (\Exception $e) {
            Log::error('Status update failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $id,
            ]);
            
            return back()->withErrors(['error' => 'Gagal mengubah status']);
        }
    }

    /**
     * Delete transaction (admin/atasan/owner only)
     */
    public function destroy($id)
    {
        try {
            $transaction = Transaction::findOrFail($id);
            $invoiceNumber = $transaction->invoice_number;
            $filePath = $transaction->file_path;

            // Delete uploaded file if exists
            if ($filePath && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
                Log::info('File deleted', ['path' => $filePath]);
            }

            $transaction->delete();
            
            Log::info('Transaction deleted', [
                'transaction_id' => $id,
                'invoice_number' => $invoiceNumber,
                'deleted_by' => Auth::id(),
            ]);

            return back()->with('success', "Transaksi {$invoiceNumber} berhasil dihapus");
            
        } catch (\Exception $e) {
            Log::error('Transaction deletion failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $id,
            ]);
            
            return back()->withErrors(['error' => 'Gagal menghapus transaksi']);
        }
    }

    /**
     * DEBUGGING: Check image path and existence
     * Remove this method in production
     */
    public function debugImage($id)
    {
        $transaction = Transaction::findOrFail($id);
        $path = $transaction->file_path;
        
        return response()->json([
            'transaction_id' => $transaction->id,
            'invoice_number' => $transaction->invoice_number,
            'path_from_db' => $path,
            'full_storage_path' => storage_path('app/public/' . $path),
            'file_exists_storage' => Storage::disk('public')->exists($path),
            'file_exists_filesystem' => file_exists(storage_path('app/public/' . $path)),
            'public_url' => asset('storage/' . $path),
            'storage_url' => Storage::url($path),
            'symlink_exists' => file_exists(public_path('storage')),
            'symlink_target' => is_link(public_path('storage')) ? readlink(public_path('storage')) : 'not a symlink',
        ]);
    }

    /**
     * DEBUGGING: Test upload directly
     * Remove this method in production
     */
    public function testUpload(Request $request)
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = 'test_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('nota-uploads', $fileName, 'public');
            
            return response()->json([
                'success' => true,
                'filename' => $fileName,
                'path' => $path,
                'full_path' => storage_path('app/public/' . $path),
                'exists' => Storage::disk('public')->exists($path),
                'url' => asset('storage/' . $path),
            ]);
        }
        
        return response()->json(['error' => 'No file uploaded'], 400);
    }
}