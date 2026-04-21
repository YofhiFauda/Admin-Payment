<?php

use App\Services\ImageCompressionService;
use Illuminate\Support\Facades\File;

$files = [
    'image.png',
    'nota_1mb .jpeg',
    'note_5mb.jpeg'
];

$service = app(ImageCompressionService::class);
$testDir = storage_path('app/temp_test');

if (!File::exists($testDir)) {
    File::makeDirectory($testDir, 0755, true);
}

echo "Starting Image Compression Test...\n";
echo "Target Max Size: " . config('services.compression.max_size') . " bytes\n";
echo "----------------------------------------\n";

foreach ($files as $filename) {
    echo "Testing File: $filename\n";
    $sourcePath = base_path($filename);
    $destPath = $testDir . '/' . str_replace(' ', '_', $filename);

    if (!File::exists($sourcePath)) {
        echo "❌ Source file not found at: $sourcePath\n";
        continue;
    }

    // Copy to temp test dir to avoid destroying original
    File::copy($sourcePath, $destPath);
    
    $sizeBefore = filesize($destPath);
    echo "Original Size: " . number_format($sizeBefore / 1024, 2) . " KB\n";

    if ($service->needsCompression($destPath)) {
        echo "Processing compression...\n";
        $startTime = microtime(true);
        $service->compress($destPath);
        $endTime = microtime(true);
        
        $sizeAfter = filesize($destPath);
        $duration = round($endTime - $startTime, 3);
        $reduction = round((1 - ($sizeAfter / $sizeBefore)) * 100, 2);

        echo "✅ Compression Finished in {$duration}s\n";
        echo "Final Size: " . number_format($sizeAfter / 1024, 2) . " KB\n";
        echo "Reduction: $reduction%\n";
    } else {
        echo "ℹ️ No compression needed (already below threshold)\n";
    }
    echo "----------------------------------------\n";
}

echo "Test completed.\n";
