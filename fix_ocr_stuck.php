<?php
require '/var/www/vendor/autoload.php';
$app = require '/var/www/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

echo "Memperbaiki transaksi yang stuck di processing...\n";

// Update DB
$updated = DB::table('transactions')->whereIn('ai_status', ['processing', 'queued'])->update(['ai_status' => 'error']);
echo "V DB Updated: {$updated} transaksi direset ke error.\n";

// Clear redis cache untuk OCR
$keys = Redis::keys('ocr:autofill:*');
if (empty($keys)) {
    // maybe it's ai_autofill:*
    $keys = Redis::keys('ai_autofill:*');
}

$cleared = 0;
foreach ($keys as $key) {
    // If the prefix is included in the keys returned by Redis::keys, we can just delete it, or we might need to remove the db prefix.
    Redis::connection()->client()->del($key);
    $cleared++;
}
echo "V Redis Cache Cleared: {$cleared} key OCR dihapus.\n";

echo "Selesai. Silakan refresh halamannya.\n";
