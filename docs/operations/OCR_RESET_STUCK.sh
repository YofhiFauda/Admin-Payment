# ═══════════════════════════════════════════════════════════════
#  CHEAT SHEET — Reset Nota Stuck di OCR
#  Container: whusnet-app
# ═══════════════════════════════════════════════════════════════

# ─────────────────────────────────────────────────────────────
#  ARTISAN COMMAND (direkomendasikan)
# ─────────────────────────────────────────────────────────────

# 1. DRY-RUN: Lihat semua yang stuck (> 10 menit) tanpa mengubah apapun
docker exec whusnet-app php artisan ocr:reset-stuck

# 2. FIX: Reset semua yang stuck > 10 menit ke ai_status=error
docker exec -it whusnet-app php artisan ocr:reset-stuck --fix

# 3. FIX: Reset satu transaksi spesifik by ID
docker exec -it whusnet-app php artisan ocr:reset-stuck --id=42 --fix

# 4. FIX: Hanya yang stuck > 30 menit
docker exec -it whusnet-app php artisan ocr:reset-stuck --minutes=30 --fix

# 5. FIX: Hanya yang statusnya 'queued' saja
docker exec -it whusnet-app php artisan ocr:reset-stuck --status=queued --fix

# 6. FIX: Hanya yang statusnya 'processing' saja
docker exec -it whusnet-app php artisan ocr:reset-stuck --status=processing --fix


# ─────────────────────────────────────────────────────────────
#  TINKER ONE-LINER (untuk emergency cepat)
# ─────────────────────────────────────────────────────────────

# Lihat semua stuck (tanpa fix)
docker exec whusnet-app php artisan tinker --execute="
\App\Models\Transaction::whereIn('ai_status', ['queued','processing'])
  ->where('updated_at', '<=', now()->subMinutes(10))
  ->select('id','invoice_number','ai_status','status','updated_at')
  ->get()->each(fn(\$t) => print(\$t->id.' | '.\$t->invoice_number.' | '.\$t->ai_status.' | '.\$t->updated_at.PHP_EOL));
"

# Reset SEMUA stuck > 10 menit (langsung tanpa konfirmasi)
docker exec whusnet-app php artisan tinker --execute="
\$count = \App\Models\Transaction::whereIn('ai_status', ['queued','processing'])
  ->where('updated_at', '<=', now()->subMinutes(10))
  ->update(['ai_status' => 'error']);
print('Reset: '.\$count.' transaksi'.PHP_EOL);
"

# Reset satu transaksi by ID (ganti 42 dengan ID yang diinginkan)
docker exec whusnet-app php artisan tinker --execute="
\$t = \App\Models\Transaction::find(42);
if(\$t) {
  \Illuminate\Support\Facades\Cache::forget('ai_autofill:'.\$t->upload_id);
  \$t->update(['ai_status' => 'error']);
  print('Reset: '.\$t->invoice_number.PHP_EOL);
} else { print('Tidak ditemukan'.PHP_EOL); }
"

# ─────────────────────────────────────────────────────────────
#  CEK STATUS (diagnosis)
# ─────────────────────────────────────────────────────────────

# Lihat semua transaksi stuck (semua durasi)
docker exec whusnet-app php artisan tinker --execute="
\App\Models\Transaction::whereIn('ai_status', ['queued','processing'])
  ->select('id','invoice_number','ai_status','status','created_at','updated_at')
  ->latest('updated_at')->get()
  ->each(fn(\$t) => print(\$t->id.' | '.\$t->invoice_number.' | '.\$t->ai_status.' | stuck: '.\$t->updated_at->diffForHumans().PHP_EOL));
"

# Cek cache Redis untuk upload_id tertentu
docker exec whusnet-app php artisan tinker --execute="
print_r(\Illuminate\Support\Facades\Cache::get('ai_autofill:UPLOAD_ID_DISINI'));
"

# Hapus cache Redis satu upload_id (ganti UPLOAD_ID_DISINI)
docker exec whusnet-app php artisan tinker --execute="
\Illuminate\Support\Facades\Cache::forget('ai_autofill:UPLOAD_ID_DISINI');
\Illuminate\Support\Facades\Cache::forget('lock:ai_callback:UPLOAD_ID_DISINI');
print('Cache cleared'.PHP_EOL);
"

# ─────────────────────────────────────────────────────────────
#  HORIZON — Cek antrian
# ─────────────────────────────────────────────────────────────

# Restart Horizon (clear semua job yang menggantung)
docker exec whusnet-horizon php artisan horizon:terminate
# (Horizon akan restart otomatis karena restart: unless-stopped)

# Cek panjang antrian Redis
docker exec whusnet-app php artisan tinker --execute="
\$redis = \Illuminate\Support\Facades\Redis::connection();
print('default: '.\$redis->llen('queues:default').PHP_EOL);
print('ocr_high: '.\$redis->llen('queues:ocr_high').PHP_EOL);
print('ocr_normal: '.\$redis->llen('queues:ocr_normal').PHP_EOL);
"
