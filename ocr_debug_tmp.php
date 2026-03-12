<?php
// OCR Status Diagnostics Script - Jalankan via: docker exec whusnet-app php /var/www/ocr_debug_tmp.php
require '/var/www/vendor/autoload.php';
$app = require '/var/www/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

echo "=== OCR DIAGNOSTIK ===\n\n";

// 1. Cek transaksi stuck
echo "1. TRANSAKSI AI STATUS QUEUED/PROCESSING:\n";
$stuck = DB::table('transactions')
    ->whereIn('ai_status', ['queued', 'processing'])
    ->orderBy('id', 'DESC')
    ->take(5)
    ->get(['id','invoice_number','status','ai_status','upload_id','created_at']);

if ($stuck->isEmpty()) {
    echo "   [OK] Tidak ada transaksi stuck di queued/processing\n";
    // Tampilkan 3 transaksi terbaru apapun
    $recent = DB::table('transactions')->orderBy('id','DESC')->take(3)
        ->get(['id','invoice_number','status','ai_status','upload_id']);
    echo "   3 Transaksi terbaru:\n";
    foreach ($recent as $t) {
        echo "   --> ID:{$t->id} status:{$t->status} ai_status:{$t->ai_status} upload_id:{$t->upload_id}\n";
    }
} else {
    foreach ($stuck as $t) {
        echo "   [STUCK] ID:{$t->id} | {$t->invoice_number} | status:{$t->status} | ai_status:{$t->ai_status} | upload:{$t->upload_id}\n";
    }
}

// 2. Cek jobs queue
echo "\n2. JOBS DALAM QUEUE:\n";
try {
    $jobCount = DB::table('jobs')->count();
    $failedCount = DB::table('failed_jobs')->count();
    echo "   Pending jobs: {$jobCount}\n";
    echo "   Failed jobs: {$failedCount}\n";
    $recent = DB::table('jobs')->orderBy('id','DESC')->take(2)->get(['id','queue','payload']);
    foreach ($recent as $j) {
        $payload = json_decode($j->payload, true);
        $cls = $payload['displayName'] ?? 'unknown';
        echo "   Job#{$j->id} queue:{$j->queue} class:{$cls}\n";
    }
} catch (\Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

// 3. Cek Redis
echo "\n3. REDIS:\n";
try {
    $pong = Redis::command('ping');
    echo "   Status: " . ($pong ? "CONNECTED" : "ERROR") . "\n";

    // Cek key OCR yang ada
    $keys = Redis::keys('ocr:autofill:*');
    echo "   OCR autofill keys: " . count($keys) . "\n";
    foreach (array_slice($keys, 0, 5) as $k) {
        $raw = Redis::get($k);
        if ($raw) {
            $d = json_decode($raw, true);
            $uid = $d['upload_id'] ?? $k;
            $st = $d['status'] ?? 'unknown';
            $ai_st = $d['ai_status'] ?? '-';
            echo "   $k -> status:{$st} ai_status:{$ai_st}\n";
        }
    }
} catch (\Exception $e) {
    echo "   Redis ERROR: " . $e->getMessage() . "\n";
}

// 4. N8N Config
echo "\n4. N8N WEBHOOK CONFIG:\n";
$n8n = config('services.n8n.webhook_url');
$secret = config('services.n8n.secret') ? '***CONFIGURED***' : 'NOT SET';
echo "   Webhook URL: " . ($n8n ?: 'NOT_SET') . "\n";
echo "   Secret: {$secret}\n";

// 5. Queue worker check via horizon
echo "\n5. WORKER STATUS:\n";
try {
    $hp = DB::table('horizon_processes')->count();
    echo "   Horizon processes running: {$hp}\n";
} catch (\Exception $e) {
    echo "   Horizon not available\n";
}

// Cek ada pending jobs OCR
$ocrJobs = DB::table('jobs')->where('queue', 'like', '%ocr%')->orWhere('payload', 'like', '%OcrProcessing%')->count();
echo "   OCR-tagged jobs pending: {$ocrJobs}\n";

echo "\n=== SELESAI ===\n";
