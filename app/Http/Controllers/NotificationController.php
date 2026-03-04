<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Ambil semua notifikasi dengan pagination
        $notifications = $user->notifications()
            ->when($request->filled('type'), function ($q) use ($request) {
                if ($request->type === 'ocr') {
                    return $q->where('data->type', 'ocr_status');
                }
                if ($request->type === 'status') {
                    return $q->where('data->type', 'transaction_status');
                }
                return $q;
            })
            ->when($request->input('read') === 'unread', function ($q) {
                return $q->whereNull('read_at');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Hitung statistik
        $stats = [
            'total' => $user->notifications()->count(),
            'unread' => $user->unreadNotifications()->count(),
            'ocr' => $user->notifications()->where('data->type', 'ocr_status')->count(),
            'ocr_unread' => $user->unreadNotifications()->where('data->type', 'ocr_status')->count(),
        ];

        return view('notifications.index', compact('notifications', 'stats'));
    }

    public function markRead($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        
        return back()->with('success', 'Notifikasi ditandai sebagai dibaca');
    }

    public function markAllRead(Request $request)
    {
        $type = $request->input('type');
        
        if ($type === 'ocr') {
            Auth::user()->unreadNotifications()
                ->where('data->type', 'ocr_status')
                ->update(['read_at' => now()]);
        } else {
            Auth::user()->unreadNotifications()->update(['read_at' => now()]);
        }
        
        return back()->with('success', 'Semua notifikasi ditandai sebagai dibaca');
    }

    public function destroy($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->delete();
        
        return back()->with('success', 'Notifikasi dihapus');
    }

    public function destroyAll()
    {
        Auth::user()->notifications()->delete();
        
        return back()->with('success', 'Semua notifikasi berhasil dihapus');
    }

    public function unreadCount()
    {
        $count = Auth::user()->unreadNotifications()->count();
        return response()->json(['count' => $count]);
    }
}