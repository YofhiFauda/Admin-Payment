<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\BranchBankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchBankAccountController extends Controller
{
    /**
     * Get list of bank accounts for a specific branch.
     * Admin, Atasan, and Owner can view this.
     */
    public function index($branchId)
    {
        $branch = Branch::findOrFail($branchId);
        
        $accounts = $branch->bankAccounts()->latest()->get();
        return response()->json($accounts);
    }

    /**
     * Store a new branch bank account.
     * Only Owner can perform this action.
     */
    public function store(Request $request)
    {
        if (Auth::user()->role !== 'owner') {
            return response()->json(['message' => 'Unauthorized. Hanya Owner yang dapat menambah rekening cabang.'], 403);
        }

        $request->validate([
            'branch_id'      => 'required|exists:branches,id',
            'bank_name'      => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'account_name'   => 'required|string|max:255',
        ]);

        $branch = Branch::findOrFail($request->branch_id);

        $account = BranchBankAccount::create([
            'branch_id'      => $branch->id,
            'bank_name'      => $request->bank_name,
            'account_number' => $request->account_number,
            'account_name'   => $request->account_name,
        ]);

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'create',
            'target_id'   => $branch->name,
            'description' => "Menambahkan rekening {$account->bank_name} untuk cabang {$branch->name}",
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rekening cabang berhasil ditambahkan',
            'data'    => $account
        ]);
    }

    /**
     * Update an existing branch bank account.
     * Only Owner can perform this action.
     */
    public function update(Request $request, $id)
    {
        if (Auth::user()->role !== 'owner') {
            return response()->json(['message' => 'Unauthorized. Hanya Owner yang dapat mengubah rekening cabang.'], 403);
        }

        $account = BranchBankAccount::findOrFail($id);

        $request->validate([
            'bank_name'      => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'account_name'   => 'required|string|max:255',
        ]);

        $account->update([
            'bank_name'      => $request->bank_name,
            'account_number' => $request->account_number,
            'account_name'   => $request->account_name,
        ]);

        $branch = $account->branch;

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'edit',
            'target_id'   => $branch->name,
            'description' => "Mengedit rekening {$account->bank_name} di cabang {$branch->name}",
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rekening cabang berhasil diperbarui',
            'data'    => $account
        ]);
    }

    /**
     * Remove the specified branch bank account.
     * Only Owner can perform this action.
     */
    public function destroy(Request $request, $id)
    {
        if (Auth::user()->role !== 'owner') {
            return response()->json(['message' => 'Unauthorized. Hanya Owner yang dapat menghapus rekening cabang.'], 403);
        }

        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $account = BranchBankAccount::findOrFail($id);
        $branch = $account->branch;

        $bankName = $account->bank_name;
        $accountNumber = $account->account_number;

        $account->delete();

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'delete',
            'target_id'   => $branch->name,
            'description' => "Menghapus rekening {$bankName} ({$accountNumber}) di cabang {$branch->name} dengan alasan: " . $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rekening cabang berhasil dihapus'
        ]);
    }
}
