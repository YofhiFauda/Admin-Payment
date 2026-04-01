<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$t = \App\Models\Transaction::where('invoice_number', 'INV-20260401-00003')->first();
if (!$t) {
    echo "Transaction not found\n";
    exit;
}

echo "Fixing Invoice: " . $t->invoice_number . "\n";
echo "Total Amount: " . $t->amount . "\n";

foreach ($t->branches as $branch) {
    $amount = (int) round(($t->amount * $branch->pivot->allocation_percent) / 100);
    $t->branches()->updateExistingPivot($branch->id, ['allocation_amount' => $amount]);
    echo "Updated " . $branch->name . " | Percent: " . $branch->pivot->allocation_percent . " | New Amount: " . $amount . "\n";
}

echo "Fix Complete!\n";
