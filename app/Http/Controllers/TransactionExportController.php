<?php

namespace App\Http\Controllers;

use App\Jobs\ExportTransactionsJob;
use App\Models\TransactionExportJob;
use App\Services\Export\TransactionExportWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * TransactionExportController
 *
 * Endpoint untuk async export dengan progress tracking + signed download.
 *
 * ROUTES
 * ──────
 * POST   /transactions/export/queue              → dispatch job, return export_id
 * GET    /transactions/export/status/{exportId}  → poll status (fallback Reverb)
 * GET    /transactions/export/download/{exportId} → signed download URL
 * GET    /transactions/export/sync               → legacy sync export (Layer 1)
 */
class TransactionExportController extends Controller
{
    /**
     * Dispatch async export job.
     *
     * Robust error handling: setiap exception ditangkap dan return JSON
     * dengan info yang berguna untuk debugging — tidak pernah biarkan
     * generic 500 HTML response sampai ke frontend.
     */
    public function queue(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            // ── Sanity check: pastikan table sudah di-migrate ──
            if (!Schema::hasTable('transaction_export_jobs')) {
                Log::error('[ExportController] Table transaction_export_jobs tidak ditemukan');
                return response()->json([
                    'message' => 'Setup belum lengkap: jalankan `php artisan migrate` terlebih dahulu.',
                ], 503);
            }

            // ── Normalisasi: empty string → null ──
            $request->merge([
                'month'     => $request->filled('month') ? $request->input('month') : null,
                'type'      => $request->filled('type') ? $request->input('type') : null,
                'status'    => $request->filled('status') ? $request->input('status') : null,
                'branch_id' => $request->filled('branch_id') ? $request->input('branch_id') : null,
            ]);

            // ── Validasi filter ──
            $validated = $request->validate([
                'month'     => 'nullable|integer|min:1|max:12',
                'year'      => 'nullable|integer|min:2020|max:2099',
                'type'      => 'nullable|string|in:rembush,pengajuan,gudang,all',
                'status'    => 'nullable|string|in:pending,approved,waiting_payment,completed,rejected,all',
                'branch_id' => 'nullable|integer|exists:branches,id',
            ]);

            // ── Cek export aktif ──
            $activeExport = TransactionExportJob::where('user_id', $user->id)
                ->whereIn('status', ['queued', 'processing'])
                ->first();

            if ($activeExport) {
                return response()->json([
                    'export_id' => $activeExport->id,
                    'status'    => $activeExport->status,
                    'message'   => 'Anda sudah memiliki export yang sedang diproses. Silakan tunggu selesai.',
                ], 409);
            }

            // ── Create record + dispatch ──
            $export = DB::transaction(function () use ($user, $validated) {
                return TransactionExportJob::create([
                    'user_id' => $user->id,
                    'filters' => $validated,
                    'status'  => 'queued',
                ]);
            });

            $forceUserId = $user->isTeknisi() ? $user->id : null;

            try {
                ExportTransactionsJob::dispatch($export->id, $user->id, $validated, $forceUserId);
            } catch (\Throwable $dispatchError) {
                // Jika dispatch gagal (queue connection error), fallback ke sync.
                Log::error('[ExportController] Dispatch failed, falling back to sync', [
                    'export_id' => $export->id,
                    'error'     => $dispatchError->getMessage(),
                ]);
                $export->markAsFailed('Queue dispatch failed: ' . $dispatchError->getMessage());

                return response()->json([
                    'message' => 'Antrian export tidak tersedia. Coba pakai endpoint sync.',
                    'fallback_url' => route('transactions.export.sync', $validated),
                    'error'   => $dispatchError->getMessage(),
                ], 503);
            }

            Log::info('[ExportController] Export queued', [
                'export_id' => $export->id,
                'user_id'   => $user->id,
                'filters'   => $validated,
            ]);

            return response()->json([
                'export_id' => $export->id,
                'status'    => 'queued',
                'message'   => 'Export sedang diproses. Anda akan menerima notifikasi saat selesai.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi filter gagal',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('[ExportController] queue() FAILED', [
                'error'   => $e->getMessage(),
                'file'    => $e->getFile() . ':' . $e->getLine(),
                'trace'   => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memulai export.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
                'hint'    => 'Cek log: storage/logs/laravel.log',
            ], 500);
        }
    }

    /**
     * Poll export status (fallback jika Reverb down).
     */
    public function status(string $exportId)
    {
        try {
            $export = TransactionExportJob::find($exportId);

            if (!$export) {
                return response()->json(['message' => 'Export not found'], 404);
            }

            if ($export->user_id !== Auth::id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $response = [
                'export_id'        => $export->id,
                'status'           => $export->status,
                'progress_percent' => $export->progress_percent,
                'processed'        => $export->processed_transactions,
                'total'            => $export->total_transactions,
            ];

            if ($export->status === 'completed') {
                $response['filename']     = $export->filename ?? ($export->file_path ? basename($export->file_path) : null);
                $response['file_size']    = $export->file_size;
                // Untuk endpoint ini kita TIDAK pakai signed URL agar simple polling.
                // Authorization sudah dilakukan via Auth::id() check di download().
                $response['download_url'] = route('transactions.export.download', ['exportId' => $export->id]);
            }

            if ($export->status === 'failed') {
                $response['error_message'] = $export->error_message;
            }

            return response()->json($response);
        } catch (\Throwable $e) {
            Log::error('[ExportController] status() FAILED', [
                'export_id' => $exportId,
                'error'     => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to get status'], 500);
        }
    }

    /**
     * Download exported file.
     *
     * Authorization: pakai SESSION saja (Auth::id() check) — tidak pakai
     * signed URL karena tidak reliable lewat reverse proxy chain.
     */
    public function download(Request $request, string $exportId)
    {
        // Aggressive logging untuk debug — log SEMUA langkah
        Log::info('[ExportController] download() ENTRY', [
            'export_id' => $exportId,
            'user_id'   => Auth::id(),
            'has_session' => $request->hasSession(),
            'request_url' => $request->fullUrl(),
        ]);

        try {
            $user = Auth::user();
            if (!$user) {
                Log::warning('[ExportController] Download: not authenticated', ['export_id' => $exportId]);
                abort(401, 'Unauthenticated');
            }

            $export = TransactionExportJob::find($exportId);
            if (!$export) {
                Log::warning('[ExportController] Download: export record not found', [
                    'export_id' => $exportId,
                    'user_id'   => $user->id,
                ]);
                abort(404, 'Export record not found in database');
            }

            // Authorization
            if ($export->user_id !== $user->id) {
                Log::warning('[ExportController] Download: authorization failed', [
                    'export_id'       => $exportId,
                    'export_user_id'  => $export->user_id,
                    'request_user_id' => $user->id,
                ]);
                abort(403, 'Unauthorized');
            }

            if ($export->status !== 'completed') {
                Log::warning('[ExportController] Download: export not completed', [
                    'export_id' => $exportId,
                    'status'    => $export->status,
                ]);
                abort(404, "Export status is '{$export->status}', not ready for download");
            }

            if (!$export->file_path) {
                Log::error('[ExportController] Download: file_path is empty', [
                    'export_id' => $exportId,
                ]);
                abort(404, 'File path not registered');
            }

            // Pakai Storage::exists() untuk konsistensi resolusi path
            $disk = Storage::disk('local');

            if (!$disk->exists($export->file_path)) {
                $absolutePath = $disk->path($export->file_path);
                Log::error('[ExportController] Download: file missing on disk', [
                    'export_id'       => $exportId,
                    'relative_path'   => $export->file_path,
                    'absolute_path'   => $absolutePath,
                    'parent_exists'   => is_dir(dirname($absolutePath)),
                    'storage_root'    => $disk->path(''),
                ]);
                abort(404, 'File has been deleted or moved');
            }

            $absolutePath = $disk->path($export->file_path);
            $filename     = $export->filename ?? basename($export->file_path);

            Log::info('[ExportController] Download: serving file', [
                'export_id' => $exportId,
                'filename'  => $filename,
                'size'      => filesize($absolutePath),
                'real_path' => realpath($absolutePath),
            ]);

            // Pakai Storage::download() — lebih reliable di environment PHP-FPM.
            // Method ini pakai BinaryFileResponse internal Laravel + handle
            // path resolution + symlink dengan baik.
            return $disk->download($export->file_path, $filename, [
                'Content-Type'                  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control'                 => 'no-store',
                'Access-Control-Expose-Headers' => 'Content-Disposition',
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            // Re-throw HTTP exceptions (404, 403, etc.) as-is
            Log::warning('[ExportController] download() HTTP exception', [
                'export_id' => $exportId,
                'status'    => $e->getStatusCode(),
                'message'   => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            // ✨ TANGKAP SEGALA — return JSON dengan info error agar mudah debug
            Log::error('[ExportController] download() FAILED', [
                'export_id' => $exportId,
                'error'     => $e->getMessage(),
                'class'     => get_class($e),
                'file'      => $e->getFile() . ':' . $e->getLine(),
                'trace'     => $e->getTraceAsString(),
            ]);

            // Untuk frontend yang request via fetch/JSON
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'message' => 'Download failed',
                    'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
                    'class'   => get_class($e),
                    'hint'    => 'Cek log: storage/logs/laravel-' . now()->format('Y-m-d') . '.log',
                ], 500);
            }

            // Untuk browser direct: return text plain dengan info supaya user lihat
            return response(
                "ERROR: " . $e->getMessage() . "\n\n" .
                "Class: " . get_class($e) . "\n" .
                "File: " . basename($e->getFile()) . ':' . $e->getLine() . "\n\n" .
                "Hubungi admin atau cek storage/logs/laravel-" . now()->format('Y-m-d') . ".log",
                500,
                ['Content-Type' => 'text/plain; charset=utf-8']
            );
        }
    }

    /**
     * Legacy SYNC export (Layer 1 fallback).
     */
    public function sync(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        try {
            $user = Auth::user();
            if (!$user) {
                abort(401, 'Unauthenticated');
            }

            // Normalisasi: empty string → null
            $request->merge([
                'month'     => $request->filled('month') ? $request->input('month') : null,
                'type'      => $request->filled('type') ? $request->input('type') : null,
                'status'    => $request->filled('status') ? $request->input('status') : null,
                'branch_id' => $request->filled('branch_id') ? $request->input('branch_id') : null,
            ]);

            $validated = $request->validate([
                'month'     => 'nullable|integer|min:1|max:12',
                'year'      => 'nullable|integer|min:2020|max:2099',
                'type'      => 'nullable|string|in:rembush,pengajuan,gudang,all',
                'status'    => 'nullable|string|in:pending,approved,waiting_payment,completed,rejected,all',
                'branch_id' => 'nullable|integer|exists:branches,id',
            ]);

            $forceUserId = $user->isTeknisi() ? $user->id : null;

            Log::info('[ExportController] Sync export START', [
                'user_id' => $user->id,
                'role'    => $user->role,
                'filters' => $validated,
                'force_user_id' => $forceUserId,
            ]);

            $writer = new TransactionExportWriter($validated, $forceUserId);

            return $writer->streamDownload($writer->buildFilename());
        } catch (ValidationException $e) {
            Log::warning('[ExportController] Sync validation failed', [
                'errors' => $e->errors(),
            ]);
            return response()->json([
                'message' => 'Validasi filter gagal',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('[ExportController] sync() FAILED', [
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'file'  => $e->getFile() . ':' . $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Untuk frontend: return JSON error daripada HTML 500.
            // Browser bisa parse pesan error untuk display ke user.
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'message' => 'Export gagal',
                    'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
                    'hint'    => 'Cek log: storage/logs/laravel.log',
                ], 500);
            }

            abort(500, config('app.debug') ? $e->getMessage() : 'Export failed');
        }
    }
}
