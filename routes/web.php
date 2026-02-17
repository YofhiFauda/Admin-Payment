<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

// Redirect root: ke dashboard jika login, ke login jika guest
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('transactions.index')
        : redirect()->route('login');
});

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Transaction History (all roles)
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');

    // Transaction Detail (all roles)
    Route::get('/transactions/{id}/detail', [TransactionController::class, 'show'])->name('transactions.show');

    // Serve transaction image (all roles)
    Route::get('/transactions/{id}/image', [TransactionController::class, 'serveImage'])->name('transactions.image');

    // Transaction Creation (teknisi, admin, owner)
    Route::middleware('role:teknisi,admin,owner')->group(function () {
        Route::get('/transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
        Route::post('/transactions/upload', [TransactionController::class, 'processUpload'])->name('transactions.upload');
        Route::get('/transactions/form', [TransactionController::class, 'showForm'])->name('transactions.form');
        Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
        Route::get('/transactions/{id}/confirm', [TransactionController::class, 'confirmation'])->name('transactions.confirm');
    });

    // Status Management, Edit & Delete (admin, atasan, owner)
    Route::middleware('role:admin,atasan,owner')->group(function () {
        Route::get('/transactions/{id}/edit', [TransactionController::class, 'edit'])->name('transactions.edit');
        Route::put('/transactions/{id}', [TransactionController::class, 'update'])->name('transactions.update');
        Route::patch('/transactions/{id}/status', [TransactionController::class, 'updateStatus'])->name('transactions.updateStatus');
        Route::delete('/transactions/{id}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
    });
});

