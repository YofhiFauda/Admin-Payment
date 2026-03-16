<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$queues = ['default', 'ocr_high', 'ocr_normal', 'ocr_low', 'callbacks', 'notifications'];
foreach ($queues as $q) {
    echo "$q: " . \Illuminate\Support\Facades\Queue::size($q) . PHP_EOL;
}

echo "Redis Keys for queues:" . PHP_EOL;
$redis = \Illuminate\Support\Facades\Redis::connection();
$keys = $redis->keys('*queues:*');
foreach ($keys as $key) {
    echo "$key" . PHP_EOL;
}
