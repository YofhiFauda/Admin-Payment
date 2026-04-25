<?php

namespace App\Http\Controllers;

use App\Models\PriceAnomaly;
use App\Models\PriceIndex;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\PriceIndex\PriceIndexService;
use App\Jobs\PriceIndex\SendPriceAnomalyNotificationJob;
use Symfony\Component\HttpFoundation\StreamedResponse;



class PriceIndexController extends Controller
{
    public function __construct(private PriceIndexService $priceIndexService) {}

    // ════════════════════════════════════════════════════════
    //  INDEX — Daftar Master Price Index
    // ════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        $query = PriceIndex::with('manualSetBy')->orderBy('item_name');

        if ($request->filled('search')) {
            $query->where('item_name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('review')) {
            $query->where('needs_initial_review', true);
        }

        $priceIndexes  = $query->paginate(30)->withQueryString();
        $categories    = PriceIndex::distinct()->pluck('category')->filter()->sort()->values();
        $pendingCount  = PriceAnomaly::pending()->count();
        $needsReviewCount = PriceIndex::where('needs_initial_review', true)->count();

        return view('price-index.index', compact('priceIndexes', 'categories', 'pendingCount', 'needsReviewCount'));
    }

    // ════════════════════════════════════════════════════════
    //  STORE — Tambah Price Index Manual
    // ════════════════════════════════════════════════════════

    public function store(Request $request)
    {
        $request->validate([
            'item_name' => 'required|string|max:255',
            'category'  => 'nullable|string|max:255',
            'unit'      => 'required|string|max:50',
            'min_price' => 'required|numeric|min:0',
            'max_price' => 'required|numeric|min:0',
            'avg_price' => 'required|numeric|min:0',
        ], [
            'max_price.min' => 'Harga Maksimal tidak boleh negatif.',
            'min_price.min' => 'Harga Minimal tidak boleh negatif.',
        ]);

        // Validasi logis
        if ($request->min_price > $request->max_price) {
            return back()->withErrors(['min_price' => 'Harga minimum tidak boleh lebih besar dari harga maksimum.'])->withInput();
        }

        // Cari apakah sudah ada
        $existing = PriceIndex::where('item_name', $request->item_name)->first();
        if ($existing) {
            // Jika ada tapi berstatus needs_initial_review, kita update saja
            if ($existing->needs_initial_review) {
                $existing->update([
                    'category'  => $request->category,
                    'unit'      => $request->unit,
                    'min_price' => $request->min_price,
                    'max_price' => $request->max_price,
                    'avg_price' => $request->avg_price,
                    'is_manual' => true,
                    'manual_set_by' => Auth::id(),
                    'manual_set_at' => now(),
                    'needs_initial_review' => false,
                ]);
                return redirect()->route('price-index.index')->with('success', "Price Index '{$request->item_name}' telah di-review dan diaktifkan.");
            }
            return back()->withErrors(['item_name' => "Item '{$request->item_name}' sudah ada di Price Index."])->withInput();
        }

        PriceIndex::create([
            'item_name'     => $request->item_name,
            'category'      => $request->category,
            'unit'          => $request->unit,
            'min_price'     => $request->min_price,
            'max_price'     => $request->max_price,
            'avg_price'     => $request->avg_price,
            'is_manual'     => true,
            'manual_set_by' => Auth::id(),
            'manual_set_at' => now(),
            'last_calculated_at' => now(),
            'needs_initial_review' => false,
        ]);

        return redirect()->route('price-index.index')->with('success', "Price Index '{$request->item_name}' berhasil ditambahkan.");
    }

    // ════════════════════════════════════════════════════════
    //  UPDATE — Edit Price Index
    // ════════════════════════════════════════════════════════

    public function update(Request $request, int $id)
    {
        $priceIndex = PriceIndex::findOrFail($id);

        $request->validate([
            'item_name' => 'required|string|max:255',
            'category'  => 'nullable|string|max:255',
            'unit'      => 'required|string|max:50',
            'min_price' => 'required|numeric|min:0',
            'max_price' => 'required|numeric|min:0',
            'avg_price' => 'required|numeric|min:0',
        ]);

        if ($request->min_price > $request->max_price) {
            return response()->json(['error' => 'Harga Min tidak boleh lebih besar dari Harga Max.'], 422);
        }

        $oldData = $priceIndex->only(['min_price', 'max_price', 'avg_price', 'is_manual']);

        $priceIndex->update([
            'item_name'     => $request->item_name,
            'category'      => $request->category,
            'unit'          => $request->unit,
            'min_price'     => $request->min_price,
            'max_price'     => $request->max_price,
            'avg_price'     => $request->avg_price,
            'is_manual'     => true,
            'manual_set_by' => Auth::id(),
            'manual_set_at' => now(),
            'manual_reason' => $request->input('manual_reason'), // ✅ Audit trail
            'needs_initial_review' => false, // ✅ Mark as reviewed
        ]);

        // Flush cache explicitly just in case
        $priceIndex->flushCache($priceIndex->item_name);

        Log::info('✏️ [PriceIndex] Manual override saved', [
            'price_index_id' => $priceIndex->id,
            'item_name'      => $priceIndex->item_name,
            'by_user_id'     => Auth::id(),
            'old'            => $oldData,
            'new'            => $priceIndex->only(['min_price', 'max_price', 'avg_price']),
            'reason'         => $request->input('manual_reason'),
        ]);

        return response()->json(['success' => true, 'message' => 'Price Index berhasil diupdate.']);
    }

    /**
     * ✅ Togle kembali ke mode Auto
     */
    public function resetToAuto(int $id)
    {
        $priceIndex = PriceIndex::findOrFail($id);
        
        // Reset manual flag
        $priceIndex->update([
            'is_manual'     => false,
            'manual_reason' => 'Mode Auto diaktifkan kembali oleh ' . Auth::user()->name,
            'manual_set_at' => null,
            'manual_set_by' => null,
        ]);

        // Trigger recalculation dari history
        $this->priceIndexService->recalculateFromHistory($priceIndex->item_name, $priceIndex->category);

        Log::info('🔄 [PriceIndex] Reset to auto mode', [
            'price_index_id' => $priceIndex->id,
            'item_name'      => $priceIndex->item_name,
            'by_user_id'     => Auth::id(),
        ]);

        return response()->json([
            'success' => true, 
            'message' => 'Status berhasil dikembalikan ke Auto (Data dihitung ulang dai history).',
            'data'    => $priceIndex->fresh()
        ]);
    }

    // ════════════════════════════════════════════════════════
    //  DESTROY — Hapus Price Index (Owner only)
    // ════════════════════════════════════════════════════════

    public function destroy(int $id)
    {
        $priceIndex = PriceIndex::findOrFail($id);
        $name = $priceIndex->item_name;
        $priceIndex->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => "Price Index '{$name}' dihapus."]);
        }

        return redirect()->route('price-index.index')->with('success', "Price Index '{$name}' berhasil dihapus.");
    }

    // ════════════════════════════════════════════════════════
    //  SET AS REFERENCE — Jadikan harga dari transaksi sebagai referensi
    // ════════════════════════════════════════════════════════

    public function setAsReference(Request $request, Transaction $transaction)
    {
        $request->validate([
            'item_name' => 'required|string|max:255',
            'reason'    => 'nullable|string|max:500',
        ]);

        $priceIndex = $this->priceIndexService->setAsReference(
            transaction: $transaction,
            itemName:    $request->item_name,
            setBy:       Auth::id(),
            reason:      $request->reason,
        );

        if (!$priceIndex) {
            return response()->json(['error' => 'Item tidak ditemukan pada transaksi ini atau harga tidak valid.'], 422);
        }

        return response()->json([
            'success'    => true,
            'message'    => "✅ Harga '{$request->item_name}' berhasil dijadikan referensi.",
            'price_index' => [
                'id'        => $priceIndex->id,
                'item_name' => $priceIndex->item_name,
                'min_price' => $priceIndex->min_price,
                'max_price' => $priceIndex->max_price,
                'avg_price' => $priceIndex->avg_price,
            ],
        ]);
    }

    // ════════════════════════════════════════════════════════
    //  ANOMALIES — Daftar Anomali Harga
    // ════════════════════════════════════════════════════════

    public function anomalies(Request $request)
    {
        $query = PriceAnomaly::with(['transaction.submitter', 'reporter'])
                             ->orderByRaw("FIELD(severity, 'critical', 'medium', 'low')")
                             ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'pending'); // Default: pending only
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                  ->orWhereHas('transaction', function($t) use ($search) {
                      $t->where('invoice_number', 'like', "%{$search}%");
                  })
                  ->orWhereHas('reporter', function($r) use ($search) {
                      $r->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $anomalies    = $query->paginate(25)->withQueryString();
        $pendingCount = PriceAnomaly::pending()->count();
        $criticalCount = PriceAnomaly::where('severity', 'critical')->where('status', 'pending')->count();

        return view('price-index.anomalies', compact('anomalies', 'pendingCount', 'criticalCount'));
    }

    // ════════════════════════════════════════════════════════
    //  REVIEW ANOMALY — Approve / Reject
    // ════════════════════════════════════════════════════════

    public function reviewAnomaly(Request $request, int $id)
    {
        $request->validate([
            'action' => 'required|in:approved,rejected',
            'notes'  => 'nullable|string|max:1000',
        ]);

        $anomaly = PriceAnomaly::findOrFail($id);

        if ($anomaly->status !== 'pending') {
            return response()->json(['error' => 'Anomali ini sudah pernah di-review.'], 422);
        }

        $anomaly->update([
            'status'        => $request->action,
            'owner_reviewed'=> true,
            'reviewed_at'   => now(),
            'reviewed_by'   => Auth::id(),
            'owner_notes'   => $request->notes,
        ]);

        $label = $request->action === 'approved' ? 'disetujui' : 'ditolak';

        return response()->json([
            'success' => true,
            'message' => "Anomali harga berhasil {$label}.",
            'status'  => $anomaly->status,
        ]);
    }

    /**
     * REVIEW ANOMALY BULK — Mass Approve / Reject
     */
    public function bulkReviewAnomaly(Request $request)
    {
        $request->validate([
            'ids'    => 'required|array',
            'ids.*'  => 'exists:price_anomalies,id',
            'action' => 'required|in:approved,rejected',
            'notes'  => 'nullable|string|max:1000',
        ]);

        $count = PriceAnomaly::whereIn('id', $request->ids)
            ->where('status', 'pending')
            ->update([
                'status'        => $request->action,
                'owner_reviewed'=> true,
                'reviewed_at'   => now(),
                'reviewed_by'   => Auth::id(),
                'owner_notes'   => $request->notes,
            ]);

        $label = $request->action === 'approved' ? 'disetujui' : 'ditolak';

        return response()->json([
            'success' => true,
            'message' => "{$count} anomali harga berhasil {$label}.",
        ]);
    }

    // ════════════════════════════════════════════════════════
    //  API LOOKUP — untuk AJAX di form pengajuan
    // ════════════════════════════════════════════════════════

    public function lookup(Request $request)
    {
        $request->validate(['item_name' => 'required|string|min:2']);

        $priceIndex = $this->priceIndexService->findMatchingIndex($request->item_name);

        if (!$priceIndex) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found'     => true,
            'id'        => $priceIndex->id,
            'item_name' => $priceIndex->item_name,
            'unit'      => $priceIndex->unit,
            'min_price' => $priceIndex->min_price,
            'max_price' => $priceIndex->max_price,
            'avg_price' => $priceIndex->avg_price,
            'is_manual' => $priceIndex->is_manual,
            'formatted' => [
                'min' => $priceIndex->formatted_min,
                'max' => $priceIndex->formatted_max,
                'avg' => $priceIndex->formatted_avg,
            ],
        ]);
    }

    // ════════════════════════════════════════════════════════
    //  API CHECK — Real-time soft warning di form pengajuan
    // ════════════════════════════════════════════════════════

    public function check(Request $request)
    {
        $request->validate([
            'item_name'      => 'required|string|min:2',
            'unit_price'     => 'required|numeric|min:0',
            'category'       => 'nullable|string|max:255',
            'master_item_id' => 'nullable|integer',
        ]);

        $priceIndex = null;
        if ($request->filled('master_item_id')) {
            $priceIndex = PriceIndex::where('master_item_id', $request->master_item_id)->first();
        }

        if (!$priceIndex) {
            $priceIndex = $this->priceIndexService->findMatchingIndex($request->item_name, $request->category);
        }

        if (!$priceIndex || $priceIndex->max_price <= 0) {
            return response()->json(['has_reference' => false]);
        }

        $unitPrice        = (float) $request->unit_price;
        $isAnomaly        = $unitPrice > $priceIndex->max_price;
        $excessAmount     = $isAnomaly ? ($unitPrice - $priceIndex->max_price) : 0;
        $excessPercentage = ($priceIndex->max_price > 0 && $isAnomaly)
            ? round(($excessAmount / $priceIndex->max_price) * 100, 1)
            : 0;

        $severity = null;
        if ($isAnomaly) {
            $severity = match(true) {
                $excessPercentage >= 50 => 'critical',
                $excessPercentage >= 20 => 'medium',
                default                  => 'low',
            };
        }

        return response()->json([
            'has_reference'    => true,
            'item_name'        => $priceIndex->item_name,
            'unit'             => $priceIndex->unit,
            'min_price'        => $priceIndex->min_price,
            'max_price'        => $priceIndex->max_price,
            'avg_price'        => $priceIndex->avg_price,
            'is_manual'        => $priceIndex->is_manual,
            'is_anomaly'       => $isAnomaly,
            'severity'         => $severity,
            'excess_amount'    => $excessAmount,
            'excess_percentage'=> $excessPercentage,
            'formatted'        => [
                'min' => $priceIndex->formatted_min,
                'max' => $priceIndex->formatted_max,
                'avg' => $priceIndex->formatted_avg,
            ],
        ]);
    }

    // ════════════════════════════════════════════════════════
    //  ANALYTICS DASHBOARD
    // ════════════════════════════════════════════════════════

    public function analytics(Request $request)
    {
        // ── Summary Cards ─────────────────────────────────────
        $totalIndexed     = PriceIndex::where('needs_initial_review', false)->count();
        $pendingAnomalies = PriceAnomaly::where('status', 'pending')->count();
        $needsReview      = PriceIndex::where('needs_initial_review', true)->count();
        $manualOverrides  = PriceIndex::where('is_manual', true)->count();

        // ── Anomali Trend (Terakhir 90 hari, per bulan) ───────
        $anomalyTrend = PriceAnomaly::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->where('created_at', '>=', now()->subDays(90))
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->orderBy('month')
            ->get()
            ->map(fn($r) => [
                'month' => $r->month,
                'count' => (int) $r->count,
            ]);

        // ── Top 10 Items Anomali (Tertinggi Deviation %) ──────
        $topVolatile = PriceAnomaly::selectRaw('item_name, AVG(excess_percentage) as avg_excess, COUNT(*) as anomaly_count')
            ->groupBy('item_name')
            ->orderByDesc('avg_excess')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'item_name'     => $r->item_name,
                'avg_excess'    => round($r->avg_excess, 1),
                'anomaly_count' => (int) $r->anomaly_count,
            ]);

        // ── Category Breakdown ────────────────────────────────
        $categoryBreakdown = PriceIndex::selectRaw('category, COUNT(*) as count, AVG(avg_price) as avg_price')
            ->whereNotNull('category')
            ->where('needs_initial_review', false)
            ->groupBy('category')
            ->orderByDesc('count')
            ->get()
            ->map(fn($r) => [
                'category'  => $r->category,
                'count'     => (int) $r->count,
                'avg_price' => round($r->avg_price),
            ]);

        // ── Items Without Index (cold start, perlu review) ────
        $itemsNeedingReview = PriceIndex::where('needs_initial_review', true)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['item_name', 'category', 'min_price', 'created_at']);

        // ── Severity Breakdown ──────────────────────────────
        $severityBreakdown = PriceAnomaly::where('status', 'pending')
            ->selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->get()
            ->keyBy('severity')
            ->map(fn($r) => (int) $r->count);

        return view('price-index.analytics', compact(
            'totalIndexed',
            'pendingAnomalies',
            'needsReview',
            'manualOverrides',
            'anomalyTrend',
            'topVolatile',
            'categoryBreakdown',
            'itemsNeedingReview',
            'severityBreakdown'
        ));
    }

    // ════════════════════════════════════════════════════════
    //  EXPORT CSV — Download semua price index sebagai CSV
    // ════════════════════════════════════════════════════════

    public function exportCsv(): StreamedResponse
    {
        $filename = 'price-index-' . now()->format('Ymd-His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');

            // BOM untuk Excel compatibility
            fputs($handle, "\xEF\xBB\xBF");

            // Header row
            fputcsv($handle, [
                'ID', 'Nama Barang', 'Kategori', 'Satuan',
                'Harga Min', 'Harga Avg', 'Harga Maks',
                'Sumber', 'Total Transaksi', 'Terakhir Diupdate',
            ]);

            PriceIndex::orderBy('item_name')->chunk(200, function ($items) use ($handle) {
                foreach ($items as $item) {
                    fputcsv($handle, [
                        $item->id,
                        $item->item_name,
                        $item->category ?? '-',
                        $item->unit,
                        $item->min_price,
                        $item->avg_price,
                        $item->max_price,
                        $item->is_manual ? 'Manual' : 'Auto',
                        $item->total_transactions ?? 0,
                        $item->last_calculated_at?->translatedFormat('d F Y H:i') ?? '-',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}