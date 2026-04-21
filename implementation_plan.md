# Image Compression — Implementation Plan

Fitur kompresi otomatis akan diintegrasikan ke dalam pipeline OCR yang sudah ada. Tujuannya adalah memastikan **setiap file gambar yang dikirim ke n8n webhook selalu berukuran ≤ 1MB**, mencegah payload timeout dan menghemat bandwidth Cloudflare Tunnel.

---

## User Review Required

> [!IMPORTANT]
> **Penyesuaian dari Rancangan README**: Untuk menjaga akurasi pembacaan teks/angka oleh Gemini AI, **kualitas minimum kompresi dinaikkan dari 55% menjadi 75%**. Jika foto masih > 1MB setelah quality reduction, sistem akan melakukan resize dimensi. Ini adalah trade-off antara ukuran file vs. akurasi OCR.

> [!WARNING]
> **Library baru yang akan di-install**: `intervention/image` v3 (kompatibel PHP 8.2+). Library ini menggunakan **GD extension** yang harus sudah aktif di container Docker. Perlu dicek terlebih dahulu sebelum eksekusi.

---

## Proposed Changes

### 1. Dependency

#### [MODIFY] [composer.json](file:///d:/Whusnet/Testing%20Runnig%20Background/Admin-Payment/composer.json)
- Tambahkan `"intervention/image": "^3.0"` ke dalam blok `require`.
- Install via `composer require intervention/image`.

---

### 2. Configuration

#### [MODIFY] [services.php](file:///d:/Whusnet/Testing%20Runnig%20Background/Admin-Payment/config/services.php)
Tambah blok konfigurasi `compression` dan `upload` di akhir array:

```php
'compression' => [
    'max_size'        => env('COMPRESSION_MAX_SIZE', 1048576),  // 1MB dalam bytes
    'initial_quality' => env('COMPRESSION_INITIAL_QUALITY', 85),
    'min_quality'     => env('COMPRESSION_MIN_QUALITY', 75),    // ⚠️ dinaikkan dari 55
    'enabled'         => env('COMPRESSION_ENABLED', true),
],

'upload' => [
    'max_size_kb'       => env('UPLOAD_MAX_SIZE', 5120), // 5MB dalam KB
    'allowed_mimes'     => ['image/jpeg', 'image/jpg', 'image/png'],
    'allowed_extensions'=> ['jpg', 'jpeg', 'png'],
],
```

#### [MODIFY] [.env](file:///d:/Whusnet/Testing%20Runnig%20Background/Admin-Payment/.env)
Tambah variabel baru di bawah blok `# Gemini Rate Limit`:

```env
# Image Compression Settings
COMPRESSION_MAX_SIZE=1048576
COMPRESSION_INITIAL_QUALITY=85
COMPRESSION_MIN_QUALITY=75
COMPRESSION_ENABLED=true

# Upload Limits
UPLOAD_MAX_SIZE=5120
```

---

### 3. Service Layer

#### [NEW] [ImageCompressionService.php](file:///d:/Whusnet/Testing%20Runnig%20Background/Admin-Payment/app/Services/ImageCompressionService.php)

File baru di `app/Services/ImageCompressionService.php`. Service ini bertanggung jawab penuh atas logika kompresi.

**3-Step Strategy (dimodifikasi dari README):**

| Step | Aksi | Quality | Resize | Stop Jika |
|------|------|---------|--------|-----------|
| 1 | Quality Reduction | 85% → 80% → 75% | Tidak | Size < 1MB |
| 2 | Dimension Resize | 75% (fixed) | 90% → 80% → 70% | Size < 1MB |
| 3 | Aggressive Fallback | 75% (fixed) | 50% | Selalu berhenti |

**Method utama yang akan disediakan:**

```php
// Cek apakah file perlu dikompresi
public function needsCompression(string $filePath): bool

// Kompresi in-place, overwrite file asli, return path
public function compress(string $filePath): string

// Kompresi + encode ke base64 (untuk keperluan lain)
public function compressToBase64(string $filePath): array

// Info diagnostik untuk logging
public function getCompressionInfo(string $filePath): array
```

**Logging detail** di setiap step untuk monitoring via `storage/logs/laravel.log`.

---

### 4. Integration ke OCR Pipeline

#### [MODIFY] [OcrProcessingJob.php](file:///d:/Whusnet/Testing%20Runnig%20Background/Admin-Payment/app/Jobs/OcrProcessingJob.php)

Perubahan minimal dan non-destructive. Hanya menambahkan **satu blok kompresi** tepat setelah path validation dan sebelum pengiriman ke n8n:

```php
// Setelah baris: if (!file_exists($fullPath)) { ... }
// Sebelum baris: $response = Http::timeout(120)->...

// ── ✅ NEW: Auto-compress jika file > 1MB ──
$compressionService = app(\App\Services\ImageCompressionService::class);
if ($compressionService->needsCompression($fullPath)) {
    Log::channel('ocr')->info('🗜️ [OCR JOB] COMPRESSING IMAGE', [
        'upload_id'     => $this->uploadId,
        'original_size_kb' => round(filesize($fullPath) / 1024, 2),
    ]);
    $fullPath = $compressionService->compress($fullPath);
    Log::channel('ocr')->info('✅ [OCR JOB] COMPRESSION DONE', [
        'upload_id'       => $this->uploadId,
        'final_size_kb'   => round(filesize($fullPath) / 1024, 2),
    ]);
}
```

> [!NOTE]
> Tidak ada perubahan pada logika pengiriman ke n8n (`Http::attach`). `$fullPath` sudah mengacu ke file yang terkompresi.

---

## Open Questions

> [!IMPORTANT]
> **Q1:** Apakah GD Library sudah aktif di Docker container? Jalankan perintah ini untuk verifikasi:
> ```bash
> docker exec whusnet-app php -m | grep -i gd
> ```
> Jika tidak ada output, perlu ditambahkan `php-gd` ke `Dockerfile`.

> [!WARNING]
> **Q2:** Format PNG mendukung lossless compression saja. Jika user meng-upload PNG besar, kompresi kualitas tidak akan efektif — sistem akan mengandalkan **resize dimensi**. Apakah ada kebutuhan untuk auto-convert PNG → JPG saat kompresi?

---

## Verification Plan

### Automated Checks
```bash
# 1. Pastikan GD tersedia di container
docker exec whusnet-app php -m | grep -i gd

# 2. Install dependency
docker exec whusnet-app composer require intervention/image

# 3. Clear config cache
docker exec whusnet-app php artisan config:clear

# 4. Jalankan test (jika nanti test dibuat)
php artisan test --filter=ImageCompressionServiceTest
```

### Manual Verification
1. Upload nota berukuran **> 1MB** lewat form Transaksi Baru.
2. Buka `storage/logs/ocr.log` — cari log `COMPRESSING IMAGE` dan `COMPRESSION DONE` dengan perbandingan ukuran sebelum/sesudah.
3. Di n8n dashboard, verifikasi ukuran file yang diterima webhook sudah **< 1MB**.
4. Periksa hasil OCR — angka dan teks pada nota harus masih terbaca dengan akurat.
