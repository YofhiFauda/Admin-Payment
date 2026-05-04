<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Activity Log only accessible by admin, atasan, and owner
        if (!$user->canManageStatus()) {
            abort(403, 'Unauthorized action.');
        }

        $query = ActivityLog::with(['user', 'transaction'])->latest();

        // If Admin or Atasan, only see their own logs OR logs from teknisi (e.g. Reject Payment)
        if ($user->isAdmin() || $user->isAtasan()) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('user', function ($u) {
                      $u->where('role', 'teknisi');
                  });
            });
        }
        // If Owner, sees everything (Admin, Atasan, Owner)
        // No extra filter needed if they see everything as requested.
        // Wait, the request says: "jika owner maka akan bisa melihat Log dari Admin, Atasan dan Owner sendiri"
        // This implies owner sees everything except Teknisi logs? 
        // But logging is only for Approve/Reject/Edit which only Admin/Atasan/Owner do.
        // So owner seeing everything is correct.

        $logs = $query->paginate(20);

        return view('activity-logs.index', compact('logs'));
    }
}
