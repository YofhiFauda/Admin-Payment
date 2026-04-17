<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterItem;
use App\Services\PriceIndex\ItemMatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ItemAutocompleteController
 *
 * Endpoint AJAX untuk Smart Autocomplete di form pengajuan.
 * Protected via throttle middleware (lihat routes/api.php & web.php).
 */
class ItemAutocompleteController extends Controller
{
    public function __construct(private ItemMatchingService $matcher) {}

    // ─────────────────────────────────────────────────────────────────────
    //  GET /api/items/autocomplete?q=kabel&category=Elektrikal
    //
    //  Dipanggil setiap keystroke (debounce 300ms di frontend).
    //  Rate-limited: 60 req/menit per user (throttle middleware).
    // ─────────────────────────────────────────────────────────────────────

    public function search(Request $request): JsonResponse
    {
        $q        = (string) $request->input('q', '');
        $category = $request->input('category');

        // Minimal 2 karakter agar query tidak terlalu lebar
        if (strlen(trim($q)) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $suggestions = $this->matcher->getSuggestions($q, $category, 10);

        return response()->json(['suggestions' => $suggestions]);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  POST /api/items/create-pending
    //
    //  Dipanggil saat teknisi klik "Tambah Barang Baru".
    //  Item dibuat dengan status pending_approval → Owner harus review.
    // ─────────────────────────────────────────────────────────────────────

    public function createPending(Request $request): JsonResponse
    {
        $request->validate([
            'item_name' => 'required|string|min:2|max:255',
            'category'  => 'nullable|string|max:255',
        ]);

        $item = $this->matcher->createPendingItem(
            rawInput:          $request->input('item_name'),
            category:          $request->input('category'),
            createdByUserId:   (int) auth()->id(),
        );

        return response()->json([
            'success'    => true,
            'message'    => "Barang baru \"{$item->display_name}\" diajukan dan menunggu persetujuan Owner.",
            'master_item' => [
                'id'             => $item->id,
                'display_name'   => $item->display_name,
                'canonical_name' => $item->canonical_name,
                'status'         => $item->status,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  GET /api/items/{id}
    //
    //  Ambil detail satu MasterItem berdasarkan ID.
    //  Digunakan setelah teknisi memilih dari autocomplete dropdown.
    // ─────────────────────────────────────────────────────────────────────

    public function show(int $id): JsonResponse
    {
        $item = MasterItem::active()->findOrFail($id);

        return response()->json([
            'id'             => $item->id,
            'display_name'   => $item->display_name,
            'canonical_name' => $item->canonical_name,
            'category'       => $item->category,
            'sku'            => $item->sku,
            'status'         => $item->status,
        ]);
    }
}
