<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\RembushController;
use App\Http\Controllers\PengajuanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\NotificationController;

use Illuminate\Support\Facades\Route;

// Redirect root: ke dashboard jika login, ke login jika guest
Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->role === 'teknisi'
            ? redirect()->route('transactions.create')
            : redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});


// routes/web.php — tambah sementara
Route::get('/debug-session', function () {
    $path = session('pengajuan_file_path');
    return [
        'session_path' => $path,
        'file_exists'  => $path ? Storage::disk('public')->exists($path) : false,
        'full_url'     => $path ? asset('storage/' . $path) : null,
        'storage_path' => $path ? storage_path('app/public/' . $path) : null,
        'real_exists'  => $path ? file_exists(storage_path('app/public/' . $path)) : false,
    ];
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ── Dashboard ────────────────────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/branch-cost-data', [DashboardController::class, 'branchCostData'])->name('dashboard.branchCostData');
    Route::get('/dashboard/pending-list-data', [DashboardController::class, 'pendingListData'])->name('dashboard.pendingListData');

    // ── Shared Transaction Routes (all roles) ──────────────
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{id}/detail', [TransactionController::class, 'show'])->name('transactions.show');
    Route::get('/transactions/{id}/detail-json', [TransactionController::class, 'detailJson'])->name('transactions.detail.json');
    Route::get('/transactions/{id}/image', [TransactionController::class, 'serveImage'])->name('transactions.image');

    // ── Transaction Creation (teknisi, admin, owner) ───────
    Route::middleware('role:teknisi,admin,owner')->group(function () {
        // Selection page
        Route::get('/transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
        Route::get('/transactions/{id}/confirm', [TransactionController::class, 'confirmation'])->name('transactions.confirm');

        // Rembush flow: upload → OCR → loading → form → store
        Route::post('/rembush/upload', [RembushController::class, 'processUpload'])->name('rembush.upload');
        Route::get('/rembush/loading', [RembushController::class, 'loading'])->name('rembush.loading');
        Route::get('/rembush/form', [RembushController::class, 'showForm'])->name('rembush.form');
        Route::post('/rembush/store', [RembushController::class, 'store'])->name('rembush.store');

        // Pengajuan flow: form → store (no OCR)
        Route::get('/pengajuan/form', [PengajuanController::class, 'showForm'])->name('pengajuan.form');
        Route::post('/pengajuan/upload', [PengajuanController::class, 'uploadPhoto'])->name('pengajuan.upload');
        Route::post('/pengajuan/store', [PengajuanController::class, 'store'])->name('pengajuan.store');
    });

    // ── Status Management, Edit & Delete (admin, atasan, owner) ──
    Route::middleware('role:admin,atasan,owner')->group(function () {
        Route::get('/transactions/{id}/edit', [TransactionController::class, 'edit'])->name('transactions.edit');
        Route::put('/transactions/{id}', [TransactionController::class, 'update'])->name('transactions.update');
        Route::patch('/transactions/{id}/status', [TransactionController::class, 'updateStatus'])->name('transactions.updateStatus');
        Route::delete('/transactions/{id}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
    });

    // ── User, Branch & Activity Management (admin, atasan, owner) ──
    Route::middleware('role:admin,atasan,owner')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

        // ── Branch Management ─────────────────────────────────────
        Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
        Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
        Route::put('/branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
        Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy');
    });

    //Notifications
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unreadCount');
    Route::get('/notifications',          [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])
        ->name('notifications.readAll');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])
        ->name('notifications.read');
    Route::delete('/notifications', [NotificationController::class, 'destroyAll'])
        ->name('notifications.destroyAll');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])
        ->name('notifications.destroy');

    // ── Search ──
    Route::get('/transactions/search-data', [TransactionController::class, 'getAllForSearch'])->name('transactions.searchData');
});