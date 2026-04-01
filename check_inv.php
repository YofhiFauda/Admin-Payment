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

echo "Invoice: " . $t->invoice_number . "\n";
echo "Type: " . $t->type . "\n";
echo "Total Amount: " . $t->amount . "\n";
echo "Status: " . $t->status . "\n";
echo "Date: " . $t->date . "\n";
echo "Created At: " . $t->created_at . "\n";

foreach ($t->branches as $branch) {
    echo "Branch ID: " . $branch->id . " | Branch: " . $branch->name . " | Percent: " . $branch->pivot->allocation_percent . " | Amount (Pivot): " . $branch->pivot->allocation_amount . "\n";
}
