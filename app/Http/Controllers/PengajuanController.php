<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Services\IdGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\PriceIndex\PriceIndexService;
use App\Jobs\PriceIndex\SendPriceAnomalyNotificationJob;

class PengajuanController extends Controller
{




    /**
     * ✅ OPTIMIZED: Server-side search dengan pagination
     */
    public function searchTransactions(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);
        $search = $request->input('search', '');
        $status = $request->input('status', 'all');
        $type = $request->input('type', 'all');
        $category = $request->input('category', 'all');
        $branchId = $request->input('branch_id', 'all');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Cache key berdasarkan semua filter
        $cacheKey = "transactions_search_" . md5(json_encode($request->all()));
        
        $result = Cache::remember($cacheKey, 300, function () use ($request, $perPage, $search, $status, $type, $category, $branchId, $startDate, $endDate) {
            $query = Transaction::query()
                ->with(['submitter.bankAccounts', 'reviewer', 'branches'])
                ->select([
                    'id', 'invoice_number', 'customer', 'vendor', 'type', 'category',
                    'status', 'amount', 'date', 'created_at', 'updated_at', 
                    'submitted_by', 'ai_status', 'confidence', 'upload_id',
                    'payment_method', 'specs', 'rejection_reason'
                ]);

            // Teknisi filter
            if (auth()->user()->isTeknisi()) {
                $query->where('submitted_by', auth()->id());
            }

            // Search query - OPTIMIZED dengan FULLTEXT atau LIKE
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'LIKE', "%{$search}%")
                      ->orWhere('customer', 'LIKE', "%{$search}%")
                      ->orWhere('vendor', 'LIKE', "%{$search}%")
                      ->orWhereHas('submitter', function ($sub) use ($search) {
                          $sub->where('name', 'LIKE', "%{$search}%");
                      });
                });
            }

            // Status filter
            if ($status !== 'all') {
                $query->where('status', $status);
            }

            // Type filter
            if ($type !== 'all') {
                $query->where('type', $type);
            }

            // Category filter
            if ($category !== 'all') {
                $query->where('category', $category);
            }

            // Branch filter
            if ($branchId !== 'all') {
                $query->whereHas('branches', function ($q) use ($branchId) {
                    $q->where('branches.id', $branchId);
                });
            }

            // Date range
            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            // Execute dengan pagination
            $paginated = $query->latest()->paginate($perPage);
            
            // Format data untuk frontend
            $items = $paginated->getCollection()->map(function (Transaction $t) {
                return $t->toSearchArray();
            });

            return [
                'data' => $items,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ];
        });

        return response()->json($result);
    }

    /**
     * ✅ NEW: Get transaction stats (for status tabs)
     */
    public function getTransactionStats(Request $request)
    {
        $cacheKey = "tx_stats_" . auth()->id() . "_" . md5(json_encode($request->except('_')));
        
        $stats = Cache::remember($cacheKey, 300, function () use ($request) {
            $query = Transaction::query();

            if (auth()->user()->isTeknisi()) {
                $query->where('submitted_by', auth()->id());
            }

            // Apply same filters as search
            if ($type = $request->input('type')) {
                if ($type !== 'all') $query->where('type', $type);
            }
            if ($category = $request->input('category')) {
                if ($category !== 'all') $query->where('category', $category);
            }
            if ($branchId = $request->input('branch_id')) {
                if ($branchId !== 'all') {
                    $query->whereHas('branches', fn($q) => $q->where('branches.id', $branchId));
                }
            }
            if ($start = $request->input('start_date')) {
                $query->whereDate('created_at', '>=', $start);
            }
            if ($end = $request->input('end_date')) {
                $query->whereDate('created_at', '<=', $end);
            }

            return [
                'count' => (clone $query)->count(),
                'pending' => (clone $query)->where('status', 'pending')->count(),
                'approved' => (clone $query)->where('status', 'approved')->count(),
                'completed' => (clone $query)->where('status', 'completed')->count(),
                'rejected' => (clone $query)->where('status', 'rejected')->count(),
                'auto_reject' => (clone $query)->where('status', 'auto-reject')->count(),
                'waiting_payment' => (clone $query)->where('status', 'waiting_payment')->count(),
                'flagged' => (clone $query)->where('status', 'flagged')->count(),
            ];
        });

        return response()->json($stats);
    }
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  FORM — Pengajuan form (no OCR)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function showForm()
    {
        $branches = Branch::all();
        $pengajuanCategories = TransactionCategory::forPengajuan()->active()->get();

        return view('transactions.form-pengajuan', compact('branches', 'pengajuanCategories'));
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  UPLOAD PHOTO — Optional reference photo
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpg,jpeg,png|max:1024',
        ]);

        $file = $request->file('file');

        // Generate sequence ONCE — both upload_id & invoice_number will share this number
        $seq       = IdGeneratorService::nextSequence();
        $uploadId  = IdGeneratorService::buildUploadId($seq);
        $extension = $file->getClientOriginalExtension();
        $fileName   = $uploadId . '.' . $extension;   // UP-20260302-00002.jpeg
        $storedPath = $fileName;                       // root of public disk
        $base64 = base64_encode(file_get_contents($file));
        $mime   = $file->getMimeType();

        // Save with predictable name at storage root
        Storage::disk('public')->put($storedPath, file_get_contents($file));


        session([
            'pengajuan_seq'           => $seq,
            'pengajuan_upload_id'     => $uploadId,
            'pengajuan_file_path'     => $storedPath,
            'pengajuan_file_base64'   => $base64,      // ← NEW
            'pengajuan_file_mime'     => $mime,        // ← NEW
        ]);

        return redirect()->route('pengajuan.form');
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  STORE — Save Pengajuan Transaction
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function store(Request $request)
    {
        $role = Auth::user()->role;
        
        $request->validate([
            'type'                              => 'required|in:pengajuan',
            'file'                              => 'nullable|image|max:2048',
            'items'                             => 'required|array|min:1',
            'items.*.customer'                  => 'required|string|max:255',
            'items.*.category'                  => ['required', 'string', function($attr, $val, $fail) {
                $exists = \App\Models\TransactionCategory::where('name', $val)
                    ->where('type', 'pengajuan')
                    ->where('is_active', true)
                    ->exists();
                if (!$exists) $fail('Alasan/Kategori tidak valid.');
            }],
            'items.*.estimated_price'           => 'required|integer|min:0',
            'items.*.quantity'                  => 'required|integer|min:1',
            'branches'                          => 'nullable|array',
            'branches.*.branch_id'              => 'required_with:branches|exists:branches,id',
            'branches.*.allocation_percent'     => 'required_with:branches|numeric|min:0|max:100',
            'branches.*.allocation_amount'      => 'nullable|numeric|min:0',
            'global_notes'                      => 'nullable|string|max:2000',
            'estimated_price'                   => 'nullable|numeric|min:0',
        ], [
            'items.*.link.url' => 'Terdapat Link/Referensi Barang yang tidak valid. Pastikan formatnya benar (contoh: https://...).',
            'items.*.customer.required' => 'Nama Barang/Jasa pada salah satu daftar barang wajib diisi.',
            'items.*.quantity.required' => 'Jumlah barang wajib diisi.',
            'items.*.estimated_price.required' => 'Estimasi harga satuan wajib diisi.',
            'estimated_price.min' => 'Total estimasi biaya harus lebih dari 0.',
        ]);

        // Generate identifiers
        $seq = IdGeneratorService::nextSequence();
        $uploadId = IdGeneratorService::buildUploadId($seq);
        $invoiceNumber = IdGeneratorService::buildInvoiceNumber((int) $seq);

        $filePath = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $fileName   = $uploadId . '.' . $extension;   // UP-20260302-00002.jpeg
            Storage::disk('public')->put($fileName, file_get_contents($file));
            $filePath = $fileName;
        }

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

        DB::beginTransaction();

        try {
            $matchingService = app(\App\Services\PriceIndex\ItemMatchingService::class);
            $items = array_map(function ($item) use ($matchingService) {
                // Standarisasi: Cari ID jika tidak ada, & bersihkan string
                $rawCustomer = trim(preg_replace('/\s+/', ' ', $item['customer'] ?? ''));
                $masterItemId = $item['master_item_id'] ?? null;
                $categoryId = $item['category'] ?? null;

                if (empty($masterItemId) && !empty($rawCustomer)) {
                    // Jika dari frontend sengaja bypass Autocomplete, cari best match
                    $bestMatch = $matchingService->findBestMatch($rawCustomer, $categoryId);
                    if ($bestMatch) {
                        $masterItemId = $bestMatch->id;
                    } else {
                        // Jika benar-benar baru, otomatis create as pending_approval
                        $newItem = $matchingService->createPendingItem($rawCustomer, $categoryId, Auth::id());
                        $masterItemId = $newItem->id;
                    }
                }

                // Timpa nama barang dengan nama Master Catalog (Mencegah Typo / Bypass)
                if (!empty($masterItemId)) {
                    $master = \App\Models\MasterItem::find($masterItemId);
                    if ($master) {
                        $rawCustomer = $master->display_name;
                    }
                }

                return [
                    'master_item_id'  => $masterItemId,
                    'customer'        => $rawCustomer,
                    'vendor'          => $item['vendor']          ?? '',
                    'link'            => $item['link']            ?? '',
                    'category'        => $categoryId,  // unified field
                    'description'     => $item['description']     ?? '',
                    'specs'           => $item['specs']           ?? [],
                    'estimated_price' => intval($item['estimated_price'] ?? 0),
                    'quantity'        => intval($item['quantity']        ?? 1),
                ];
            }, $request->items);

            $totalAmount = collect($items)->sum(function($item) {
                return ($item['estimated_price'] ?? 0) * ($item['quantity'] ?? 1);
            });

            $transaction = Transaction::create([
                'type'            => Transaction::TYPE_PENGAJUAN,
                'invoice_number'  => $invoiceNumber,
                'customer'        => $items[0]['customer'] ?? 'Multiple Items',
                'vendor'          => $items[0]['vendor'] ?? null,
                'link'            => $items[0]['link'] ?? null,
                'category'        => $items[0]['category'] ?? null,
                'description'     => $request->global_notes,
                'amount'          => $totalAmount,
                'items'           => $items,
                'file_path'       => $filePath,
                'date'            => now()->format('Y-m-d'),
                'status'          => 'pending',
                'upload_id'       => $uploadId,
                'trace_id'        => Transaction::generateTraceId(),
                'submitted_by'    => Auth::id(),
            ]);


            // ✅ SNAPSHOT data asli pengaju (immutable)
            $transaction->items_snapshot = $transaction->items;
            $transaction->save();

            // Attach branches if provided
            if ($request->branches && count($request->branches) > 0) {
                $effectiveAmount = $transaction->amount;
                $branchAttachData = [];
                $totalAllocated = 0;
                
                foreach ($request->branches as $branchData) {
                    $allocPercent = floatval($branchData['allocation_percent']);
                    // Selalu hitung ulang di backend berdasarkan persen untuk akurasi
                    $allocAmount = intval(round(($effectiveAmount * $allocPercent) / 100));
                    $totalAllocated += $allocAmount;

                    $branchAttachData[] = [
                        'id'                 => $branchData['branch_id'],
                        'allocation_percent' => $allocPercent,
                        'allocation_amount'  => $allocAmount,
                    ];
                }
                
                // Absorb difference in last branch to ensure sum equals exactly $effectiveAmount
                $diff = $effectiveAmount - $totalAllocated;
                if (count($branchAttachData) > 0 && $diff != 0) {
                    $branchAttachData[count($branchAttachData) - 1]['allocation_amount'] += $diff;
                }

                foreach ($branchAttachData as $branch) {
                    $transaction->branches()->attach($branch['id'], [
                        'allocation_percent' => $branch['allocation_percent'],
                        'allocation_amount'  => $branch['allocation_amount'],
                    ]);
                }
            }

            // ✅ PRICE INDEX — Deteksi Anomali Harga
            $priceIndexService = app(PriceIndexService::class);
            $anomalies = $priceIndexService->detectForTransaction($transaction);

            // Jika ada anomali → dispatch job notifikasi Telegram ke Owner
            foreach ($anomalies as $anomaly) {
                dispatch(new SendPriceAnomalyNotificationJob($anomaly->id));
            }

            broadcast(new \App\Events\TransactionCreated($transaction));
            DB::commit();

            return redirect()->route('transactions.confirm', $transaction->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Pengajuan store failed', ['error' => $e->getMessage()]);
            return back()->withInput()
                ->withErrors(['error' => 'Gagal menyimpan pengajuan: ' . $e->getMessage()]);
        }
    }
}