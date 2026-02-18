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
use Illuminate\Support\Str;


class TransactionController extends Controller
{
    /**
     * History / List page with stats
     */
    public function index(Request $request)
    {
        $query = Transaction::with(['submitter', 'reviewer', 'branches'])->latest();

        // Teknisi hanya melihat transaksi sendiri
        if (Auth::user()->isTeknisi()) {
            $query->where('submitted_by', Auth::id());
        }

        // Search filter (nama, invoice, tanggal)
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('customer', 'like', "%{$search}%")
                  ->orWhere('invoice_number', 'like', "%{$search}%");

                // Coba parse sebagai tanggal
                $dateFormats = ['d-m-Y', 'd/m/Y', 'Y-m-d', 'd M Y'];
                foreach ($dateFormats as $format) {
                    try {
                        $parsed = \Carbon\Carbon::createFromFormat($format, $search);
                        if ($parsed) {
                            $q->orWhereDate('date', $parsed->toDateString());
                            break;
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            });
        }

        // Status filter
        if ($status = $request->get('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        // Category filter
        if ($category = $request->get('category')) {
            if ($category !== 'all') {
                $query->where('category', $category);
            }
        }

        $transactions = $query->paginate(20);

        // Stats - scoped per role
        $statsQuery = Auth::user()->isTeknisi()
            ? Transaction::where('submitted_by', Auth::id())
            : new Transaction;

        $stats = [
            'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
            'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
            'rejected' => (clone $statsQuery)->where('status', 'rejected')->count(),
            'count' => (clone $statsQuery)->count(),
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
     * Handle file upload → store temp → send to N8N → redirect to form
     * NO database insert here — data only saved on form submit
     */
    public function processUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $file = $request->file('file');

        // Generate unique upload ID
        $uploadId = (string) Str::uuid();

        // Save file to temp storage
        $extension = $file->getClientOriginalExtension();
        $fileName = $uploadId . '.' . $extension;
        $storagePath = 'temp-uploads/' . $fileName;

        Storage::disk('public')->put($storagePath, file_get_contents($file));

        // Encode file to base64 for session preview & N8N
        $base64 = base64_encode(file_get_contents($file));
        $mime = $file->getMimeType();

        // Store in session (for form preview)
        session([
            'upload_id' => $uploadId,
            'upload_file_path' => $storagePath,
            'upload_file_base64' => $base64,
            'upload_file_mime' => $mime,
        ]);

        // Send to N8N for AI processing (non-blocking)
        $this->sendToN8N($uploadId, $base64);

        return redirect()->route('transactions.loading');
    }



    /**
     * Intermediary Loading Page (Wait for OCR)
     */
    public function loading()
    {
        $uploadId = session('upload_id');

        if (!$uploadId) {
            return redirect()->route('transactions.create');
        }

        return view('transactions.loading', compact('uploadId'));
    }

    /**
     * Step 2: Form with branch allocation
     * Loads image from session, passes uploadId for AI polling
     */
    public function showForm()
    {
        $uploadId = session('upload_id');
        $base64 = session('upload_file_base64');
        $mime = session('upload_file_mime');

        if (!$uploadId || !$base64) {
            return redirect()->route('transactions.create')
                ->withErrors(['error' => 'Silakan unggah nota terlebih dahulu']);
        }

        $branches = Branch::all();

        return view('transactions.form', compact('branches', 'base64', 'mime', 'uploadId'));
    }


    /**
     * Store transaction — THIS is where the database insert happens
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer' => 'required|string|max:255',
            'category' => 'required|string|in:' . implode(',', array_keys(Transaction::CATEGORIES)),
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
                $permanentPath = $uploadFilePath; // fallback
            }

            // CREATE transaction in database (first time!)
            $transaction = Transaction::create([
                'invoice_number' => Transaction::generateInvoiceNumber(),
                'customer' => $request->customer,
                'category' => $request->category,
                'amount' => $request->amount,
                'items' => $request->items,
                'date' => $request->date ?? now()->format('Y-m-d'),
                'file_path' => $permanentPath,
                'status' => 'pending',
                'submitted_by' => Auth::id(),
            ]);

            // Attach branches
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

            // Clear all upload session data
            session()->forget([
                'upload_id',
                'upload_file_path',
                'upload_file_base64',
                'upload_file_mime',
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
     * Send image to N8N for AI processing
     * Uses upload_id as identifier (no database record yet)
     */
    private function sendToN8N(string $uploadId, string $filePath)
    {
        try {
            Http::timeout(60)
                ->withHeaders([
                    'X-SECRET' => env('N8N_SECRET')
                ])
                ->attach(
                    'file',
                    fopen($filePath, 'r'),
                    basename($filePath)
                )
                ->post('http://localhost:5678/webhook-test/ocr-nota', [
                    'upload_id' => $uploadId
                ]);


        } catch (\Exception $e) {
            Log::error('Gagal kirim ke N8N', [
                'upload_id' => $uploadId,
                'error' => $e->getMessage()
            ]);
        }
    }


    /**
     * Transaction detail page (all authenticated users)
     */
    public function show($id)
    {
        try {
            $transaction = Transaction::with(['submitter', 'reviewer', 'branches'])->findOrFail($id);
            $fileExists = $transaction->file_path && Storage::disk('public')->exists($transaction->file_path);

            return view('transactions.show', compact('transaction', 'fileExists'));
        } catch (\Exception $e) {
            return redirect()->route('transactions.index')
                ->withErrors(['error' => 'Transaksi tidak ditemukan']);
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
     * Edit transaction form (admin, atasan, owner)
     */
    public function edit($id)
    {
        $transaction = Transaction::with('branches')->findOrFail($id);
        $branches = Branch::all();

        return view('transactions.edit', compact('transaction', 'branches'));
    }

    /**
     * Update transaction details (admin, atasan, owner)
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'customer' => 'required|string|max:255',
            'category' => 'required|string|in:' . implode(',', array_keys(Transaction::CATEGORIES)),
            'amount' => 'required|numeric|min:1',
            'items' => 'nullable|string',
            'date' => 'nullable|date',
            'branches' => 'required|array|min:1',
            'branches.*.branch_id' => 'required|exists:branches,id',
            'branches.*.allocation_percent' => 'required|numeric|min:0|max:100',
            'branches.*.allocation_amount' => 'nullable|numeric|min:0',
        ]);

        $totalPercent = collect($request->branches)->sum('allocation_percent');
        if (abs($totalPercent - 100) > 1) {
            return back()->withErrors(['branches' => 'Total alokasi harus 100%.'])->withInput();
        }

        DB::beginTransaction();
        try {
            $transaction = Transaction::findOrFail($id);
            $transaction->update([
                'customer' => $request->customer,
                'category' => $request->category,
                'amount' => $request->amount,
                'items' => $request->items,
                'date' => $request->date ?? $transaction->date,
            ]);

            // Sync branches
            $transaction->branches()->detach();
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
            return redirect()->route('transactions.index')
                ->with('success', "Transaksi {$transaction->invoice_number} berhasil diperbarui.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Gagal memperbarui transaksi: ' . $e->getMessage()]);
        }
    }

    /**
     * Update transaction status - Two-tier approval:
     * < 1jt: Admin/Atasan approve → completed (done)
     * >= 1jt: Admin/Atasan approve → approved (waiting Owner) → Owner approve → completed
     */
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        $newStatus = $request->status;

        // Validate allowed statuses per role
        $allowedStatuses = $user->isOwner()
            ? ['pending', 'approved', 'completed', 'rejected']
            : ['approved', 'rejected'];

        $request->validate([
            'status' => 'required|in:' . implode(',', $allowedStatuses),
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:1000',
        ]);

        try {
            $transaction = Transaction::findOrFail($id);
            $oldStatus = $transaction->status;

            // Two-tier approval logic
            if ($newStatus === 'approved' && !$user->isOwner()) {
                // Admin/Atasan approving a pending transaction
                if ($transaction->amount < 1000000) {
                    // < 1jt: single approval sufficient → directly completed
                    $newStatus = 'completed';
                }
                // >= 1jt: stays 'approved', waiting for Owner approval
            }

            // Owner approving an already-approved transaction → completed
            if ($newStatus === 'approved' && $user->isOwner() && $oldStatus === 'approved') {
                $newStatus = 'completed';
            }

            $updateData = [
                'status' => $newStatus,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ];

            if ($newStatus === 'rejected') {
                $updateData['rejection_reason'] = $request->rejection_reason;
            } else {
                $updateData['rejection_reason'] = null;
            }

            $transaction->update($updateData);
            
            Log::info('Transaction status updated', [
                'transaction_id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reviewed_by' => Auth::id(),
                'rejection_reason' => $request->rejection_reason,
            ]);

            $statusLabel = match($newStatus) {
                'approved' => 'DISETUJUI (Menunggu Owner)',
                'rejected' => 'DITOLAK',
                'completed' => 'SELESAI',
                'pending' => 'PENDING',
                default => strtoupper($newStatus),
            };

            return back()->with('success', "Nota {$transaction->invoice_number} diubah ke: {$statusLabel}");
            
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
     * Serve transaction image directly from storage
     */
    public function serveImage($id)
    {
        $transaction = Transaction::findOrFail($id);

        if (!$transaction->file_path || !Storage::disk('public')->exists($transaction->file_path)) {
            abort(404, 'Gambar tidak ditemukan');
        }

        $file = Storage::disk('public')->get($transaction->file_path);
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($file);

        return response($file, 200)->header('Content-Type', $mimeType);
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
    public function testUpl(Request $request)
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
