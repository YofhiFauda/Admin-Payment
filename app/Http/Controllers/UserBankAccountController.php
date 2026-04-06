<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\UserBankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserBankAccountController extends Controller
{
    /**
     * Get list of bank accounts for a specific user.
     */
    public function index($userId)
    {
        $user = User::findOrFail($userId);
        
        // Authorization check
        if (Auth::id() != $userId && !Auth::user()->canManageUsers()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $accounts = $user->bankAccounts()->latest()->get();
        return response()->json($accounts);
    }

    /**
     * Store a new bank account.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id'        => 'required|exists:users,id',
            'bank_name'      => 'required|string|max:255',
            'account_number' => 'required|numeric|digits_between:5,30',
            'account_name'   => 'required|string|max:255',
        ]);

        $userId = $request->user_id;

        // Authorization check
        if (Auth::id() != $userId && !Auth::user()->canManageUsers()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $account = UserBankAccount::create([
            'user_id'        => $userId,
            'bank_name'      => $request->bank_name,
            'account_number' => $request->account_number,
            'account_name'   => $request->account_name,
        ]);

        $targetUser = User::findOrFail($userId);
        $logDescription = Auth::id() == $userId
            ? "Menambahkan rekening {$account->bank_name} baru"
            : "Menambahkan rekening {$account->bank_name} untuk user {$targetUser->name}";

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'create',
            'target_id'   => $targetUser->name,
            'description' => $logDescription,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rekening berhasil ditambahkan',
            'data'    => $account
        ]);
    }

    /**
     * Update an existing bank account.
     */
    public function update(Request $request, $id)
    {
        $account = UserBankAccount::findOrFail($id);

        // Authorization check
        if (Auth::id() != $account->user_id && !Auth::user()->canManageUsers()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'bank_name'      => 'required|string|max:255',
            'account_number' => 'required|numeric|digits_between:5,30',
            'account_name'   => 'required|string|max:255',
        ]);

        $account->update([
            'bank_name'      => $request->bank_name,
            'account_number' => $request->account_number,
            'account_name'   => $request->account_name,
        ]);

        $targetUser = $account->user;
        $logDescription = Auth::id() == $account->user_id
            ? "Mengedit rekening {$account->bank_name}"
            : "Mengedit rekening {$account->bank_name} milik user {$targetUser->name}";

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'edit',
            'target_id'   => $targetUser->name,
            'description' => $logDescription,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rekening berhasil diperbarui',
            'data'    => $account
        ]);
    }

    /**
     * Remove the specified bank account.
     */
    public function destroy(Request $request, $id)
    {
        $account = UserBankAccount::findOrFail($id);
        $userId = $account->user_id;

        // Authorization check
        $isAdmin = Auth::user()->canManageUsers();
        if (Auth::id() != $userId && !$isAdmin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Require reason for admin/atasan/owner
        if ($isAdmin && Auth::id() != $userId) {
            $request->validate([
                'reason' => 'required|string|max:255',
            ]);
        }

        $bankName = $account->bank_name;
        $targetUserName = $account->user->name;
        $accountNumber = $account->account_number;

        $account->delete();

        $logDescription = Auth::id() == $userId
            ? "Menghapus rekening {$bankName} ({$accountNumber})"
            : "Menghapus rekening {$bankName} ({$accountNumber}) milik user {$targetUserName} dengan alasan: " . ($request->reason ?? '-');

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'delete',
            'target_id'   => $targetUserName,
            'description' => $logDescription,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rekening berhasil dihapus'
        ]);
    }
}
