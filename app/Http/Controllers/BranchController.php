<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::withCount('transactions')
            ->orderBy('name')
            ->paginate(20);

        return view('branches.index', compact('branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:branches,name',
        ], [
            'name.required' => 'Nama cabang wajib diisi.',
            'name.unique'   => 'Nama cabang sudah terdaftar.',
            'name.max'      => 'Nama cabang maksimal 100 karakter.',
        ]);

        $branch = Branch::create(['name' => trim($request->name)]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cabang berhasil ditambahkan.',
                'branch'  => $branch,
            ]);
        }

        return back()->with('success', 'Cabang berhasil ditambahkan.');
    }

    public function update(Request $request, Branch $branch)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:branches,name,' . $branch->id,
        ], [
            'name.required' => 'Nama cabang wajib diisi.',
            'name.unique'   => 'Nama cabang sudah terdaftar.',
            'name.max'      => 'Nama cabang maksimal 100 karakter.',
        ]);

        $branch->update(['name' => strtoupper(trim($request->name))]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cabang berhasil diperbarui.',
                'branch'  => $branch->fresh(),
            ]);
        }

        return back()->with('success', 'Cabang berhasil diperbarui.');
    }

    public function destroy(Branch $branch)
    {
        if ($branch->transactions()->exists()) {
            $msg = 'Cabang tidak dapat dihapus karena masih memiliki transaksi terkait.';
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $branch->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Cabang berhasil dihapus.']);
        }

        return back()->with('success', 'Cabang berhasil dihapus.');
    }
}
