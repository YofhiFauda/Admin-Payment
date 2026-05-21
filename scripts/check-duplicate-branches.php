#!/usr/bin/env php
<?php

/**
 * Script untuk mengecek duplikasi cabang di transaction_branches
 * 
 * Usage:
 *   php scripts/check-duplicate-branches.php
 * 
 * Output:
 *   - Daftar transaksi yang memiliki cabang duplikat
 *   - Total duplikat yang ditemukan
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔍 Checking for duplicate branches in transaction_branches...\n\n";

// Query untuk menemukan duplikat
$duplicates = DB::select("
    SELECT 
        transaction_id, 
        branch_id, 
        COUNT(*) as total,
        GROUP_CONCAT(id ORDER BY id) as duplicate_ids
    FROM transaction_branches
    GROUP BY transaction_id, branch_id
    HAVING COUNT(*) > 1
    ORDER BY transaction_id, branch_id
");

if (empty($duplicates)) {
    echo "✅ No duplicates found! Database is clean.\n";
    exit(0);
}

echo "❌ Found " . count($duplicates) . " duplicate entries:\n\n";
echo str_repeat("=", 80) . "\n";
printf("%-15s %-15s %-10s %-30s\n", "Transaction ID", "Branch ID", "Count", "Duplicate IDs");
echo str_repeat("=", 80) . "\n";

foreach ($duplicates as $dup) {
    printf(
        "%-15s %-15s %-10s %-30s\n",
        $dup->transaction_id,
        $dup->branch_id,
        $dup->total,
        $dup->duplicate_ids
    );
}

echo str_repeat("=", 80) . "\n\n";

// Tampilkan detail transaksi yang bermasalah
echo "📋 Transaction Details:\n\n";
foreach ($duplicates as $dup) {
    $transaction = DB::table('transactions')
        ->where('id', $dup->transaction_id)
        ->first();
    
    $branch = DB::table('branches')
        ->where('id', $dup->branch_id)
        ->first();
    
    if ($transaction && $branch) {
        echo "Transaction #{$transaction->id} ({$transaction->invoice_number}):\n";
        echo "  - Type: {$transaction->type}\n";
        echo "  - Amount: Rp " . number_format($transaction->amount, 0, ',', '.') . "\n";
        echo "  - Duplicate Branch: {$branch->name} (ID: {$branch->id})\n";
        echo "  - Duplicate Count: {$dup->total}\n";
        echo "  - Duplicate IDs: {$dup->duplicate_ids}\n";
        echo "\n";
    }
}

echo "\n";
echo "🔧 To fix these duplicates, run:\n";
echo "   php artisan migrate\n";
echo "\n";
echo "⚠️  The migration will automatically remove duplicates and add unique constraint.\n";

exit(1);
