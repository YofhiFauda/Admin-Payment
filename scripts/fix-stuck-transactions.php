#!/usr/bin/env php
<?php

/**
 * ═══════════════════════════════════════════════════════════════
 *  Fix Stuck Transactions Script
 * 
 *  Memperbaiki transaksi yang застрял dengan status invalid
 *  "Sedang Diverifikasi AI" menjadi status valid.
 * 
 *  Usage:
 *    php scripts/fix-stuck-transactions.php [--dry-run] [--auto-complete]
 * 
 *  Options:
 *    --dry-run        Hanya tampilkan transaksi yang akan diperbaiki
 *    --auto-complete  Otomatis set status ke 'completed' (default: 'waiting_payment')
 * ═══════════════════════════════════════════════════════════════
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\Transaction;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Parse arguments
$dryRun = in_array('--dry-run', $argv);
$autoComplete = in_array('--auto-complete', $argv);

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Fix Stuck Transactions - Payment Verification\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

if ($dryRun) {
    echo "🔍 DRY RUN MODE - Tidak ada perubahan yang akan disimpan\n\n";
}

// Find stuck transactions
$stuckTransactions = Transaction::where('status', 'Sedang Diverifikasi AI')
    ->with(['submitter', 'branches'])
    ->orderBy('created_at', 'desc')
    ->get();

if ($stuckTransactions->isEmpty()) {
    echo "✅ Tidak ada transaksi yang застрял dengan status 'Sedang Diverifikasi AI'\n\n";
    exit(0);
}

echo "📊 Ditemukan {$stuckTransactions->count()} transaksi yang застрял:\n\n";

// Display stuck transactions
$table = [];
foreach ($stuckTransactions as $transaction) {
    $table[] = [
        'ID' => $transaction->id,
        'Upload ID' => $transaction->upload_id ?? '-',
        'Invoice' => $transaction->invoice_number ?? '-',
        'Submitter' => $transaction->submitter->name ?? '-',
        'Amount' => 'Rp ' . number_format($transaction->amount ?? 0, 0, ',', '.'),
        'Created' => $transaction->created_at->format('Y-m-d H:i:s'),
    ];
}

// Print table
$headers = array_keys($table[0]);
$widths = [];
foreach ($headers as $header) {
    $widths[$header] = max(
        strlen($header),
        max(array_map(fn($row) => strlen($row[$header]), $table))
    );
}

// Print header
echo "┌";
foreach ($headers as $i => $header) {
    echo str_repeat('─', $widths[$header] + 2);
    if ($i < count($headers) - 1) echo "┬";
}
echo "┐\n";

echo "│";
foreach ($headers as $header) {
    echo " " . str_pad($header, $widths[$header]) . " │";
}
echo "\n";

echo "├";
foreach ($headers as $i => $header) {
    echo str_repeat('─', $widths[$header] + 2);
    if ($i < count($headers) - 1) echo "┼";
}
echo "┤\n";

// Print rows
foreach ($table as $row) {
    echo "│";
    foreach ($headers as $header) {
        echo " " . str_pad($row[$header], $widths[$header]) . " │";
    }
    echo "\n";
}

echo "└";
foreach ($headers as $i => $header) {
    echo str_repeat('─', $widths[$header] + 2);
    if ($i < count($headers) - 1) echo "┴";
}
echo "┘\n\n";

if ($dryRun) {
    echo "💡 Untuk memperbaiki transaksi ini, jalankan tanpa --dry-run:\n";
    echo "   php scripts/fix-stuck-transactions.php\n\n";
    exit(0);
}

// Ask for confirmation
echo "⚠️  PERHATIAN:\n";
echo "   Transaksi akan direset ke status '" . ($autoComplete ? 'completed' : 'waiting_payment') . "'\n";
echo "   AI status akan diset ke 'error'\n\n";

if (!$autoComplete) {
    echo "💡 Tip: Gunakan --auto-complete untuk langsung set status ke 'completed'\n\n";
}

echo "Lanjutkan? (y/n): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim(strtolower($line)) !== 'y') {
    echo "\n❌ Dibatalkan\n\n";
    exit(0);
}

echo "\n";
echo "🔧 Memperbaiki transaksi...\n\n";

$fixed = 0;
$errors = 0;

foreach ($stuckTransactions as $transaction) {
    try {
        $newStatus = $autoComplete ? 'completed' : 'waiting_payment';
        
        $transaction->update([
            'status' => $newStatus,
            'ai_status' => 'error',
        ]);

        // If auto-complete, also set actual_total and confidence
        if ($autoComplete) {
            $transaction->update([
                'actual_total' => $transaction->expected_total,
                'confidence' => 100,
            ]);
        }

        echo "✅ #{$transaction->id} - {$transaction->invoice_number} → {$newStatus}\n";
        $fixed++;

        // Broadcast update
        try {
            broadcast(new \App\Events\TransactionUpdated($transaction->fresh()));
        } catch (\Exception $e) {
            echo "   ⚠️  Broadcast failed: {$e->getMessage()}\n";
        }

    } catch (\Exception $e) {
        echo "❌ #{$transaction->id} - Error: {$e->getMessage()}\n";
        $errors++;
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Summary\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ Fixed: {$fixed}\n";
echo "❌ Errors: {$errors}\n";
echo "\n";

if ($fixed > 0) {
    echo "💡 Next Steps:\n";
    if (!$autoComplete) {
        echo "   1. User perlu upload ulang bukti transfer untuk transaksi ini\n";
        echo "   2. Atau gunakan --auto-complete untuk langsung selesaikan transaksi\n";
    } else {
        echo "   1. Transaksi sudah diselesaikan otomatis\n";
        echo "   2. Verifikasi manual jika diperlukan\n";
    }
    echo "\n";
}

exit($errors > 0 ? 1 : 0);
