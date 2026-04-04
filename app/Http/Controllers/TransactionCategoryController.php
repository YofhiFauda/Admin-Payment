<?php

namespace App\Http\Controllers;

use App\Models\TransactionCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionCategoryController extends Controller
{
    /**
     * Display the Kelola Kategori management page.
     */
    public function index()
    {
        $rembushCategories   = TransactionCategory::forRembush()->get();
        $pengajuanCategories = TransactionCategory::forPengajuan()->get();

        // Also include inactive for management page
        $allRembush   = TransactionCategory::where('type', 'rembush')->orderBy('sort_order')->orderBy('name')->get();
        $allPengajuan = TransactionCategory::where('type', 'pengajuan')->orderBy('sort_order')->orderBy('name')->get();

        return view('transaction-categories.index', compact(
            'allRembush', 'allPengajuan'
        ));
    }

    /**
     * Store a new category.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:rembush,pengajuan',
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'type.required' => 'Tipe kategori wajib dipilih.',
            'type.in'       => 'Tipe kategori tidak valid.',
        ]);

        // Check for duplicate
        $exists = TransactionCategory::where('name', $request->name)
            ->where('type', $request->type)
            ->first();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori dengan nama ini sudah ada untuk tipe yang sama.',
            ], 422);
        }

        $maxOrder = TransactionCategory::where('type', $request->type)->max('sort_order') ?? 0;

        $category = TransactionCategory::create([
            'name'       => $request->name,
            'type'       => $request->type,
            'sort_order' => $maxOrder + 1,
            'is_active'  => true,
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Kategori berhasil ditambahkan.',
            'category' => $category,
        ]);
    }

    /**
     * Update an existing category.
     */
    public function update(Request $request, $id)
    {
        $category = TransactionCategory::findOrFail($id);

        $request->validate([
            'name'      => 'required|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        // Check for duplicate (excluding self)
        $exists = TransactionCategory::where('name', $request->name)
            ->where('type', $category->type)
            ->where('id', '!=', $id)
            ->first();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori dengan nama ini sudah ada untuk tipe yang sama.',
            ], 422);
        }

        $category->update([
            'name'      => $request->name,
            'is_active' => $request->boolean('is_active', $category->is_active),
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Kategori berhasil diperbarui.',
            'category' => $category->fresh(),
        ]);
    }

    /**
     * Toggle active status.
     */
    public function toggleActive($id)
    {
        $category = TransactionCategory::findOrFail($id);
        $category->update(['is_active' => !$category->is_active]);

        return response()->json([
            'success'   => true,
            'message'   => 'Status kategori berhasil diubah.',
            'is_active' => $category->is_active,
        ]);
    }

    /**
     * Delete a category (soft delete via is_active=false or hard delete if unused).
     */
    public function destroy($id)
    {
        $category = TransactionCategory::findOrFail($id);
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dihapus.',
        ]);
    }
}
