<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Dispatching job...\n";
    App\Jobs\OcrProcessingJob::dispatch('test-upload-id', 'test-path', 'normal');
    echo "Successfully dispatched!\n";
} catch (\Throwable $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
