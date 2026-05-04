<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;

/**
 * ═══════════════════════════════════════════════════════════════
 *  ImageCompressionService — WHUSNET OCR Pre-processing (v4 API)
 *
 *  Mengompres gambar nota sebelum dikirim ke n8n webhook.
 *  Target: selalu di bawah 1MB (dapat dikonfigurasi via .env).
 *
 *  Strategi Kompresi (3 Step - Updated for v4):
 *  - Menggunakan decodePath() untuk membaca file.
 *  - Menggunakan discrete encoders untuk output.
 * ═══════════════════════════════════════════════════════════════
 */
class ImageCompressionService
{
    private ImageManager $manager;
    private int $maxSize;
    private int $initialQuality;
    private int $minQuality;
    private bool $enabled;

    public function __construct()
    {
        // v4 instantiates ImageManager with the driver instance or class name
        $this->manager       = new ImageManager(new Driver());
        $this->maxSize       = (int) config('services.compression.max_size', 1048576);
        $this->initialQuality = (int) config('services.compression.initial_quality', 85);
        $this->minQuality    = (int) config('services.compression.min_quality', 75);
        $this->enabled       = (bool) config('services.compression.enabled', true);
    }

    /**
     * Cek apakah file perlu dikompresi.
     */
    public function needsCompression(string $filePath): bool
    {
        if (! $this->enabled) {
            return false;
        }

        if (! file_exists($filePath)) {
            return false;
        }

        return filesize($filePath) > $this->maxSize;
    }

    /**
     * Kompresi file gambar in-place (overwrite file asli).
     * Return path file yang sudah terkompresi.
     */
    public function compress(string $filePath): string
    {
        // Gunakan optimasi OCR secara default untuk hasil terbaik di Gemini
        return $this->optimizeForOcr($filePath);
    }

    /**
     * Normalisasi gambar untuk OCR Gemini.
     * Target: 1920px max resolution, JPEG 85%, High Contrast.
     */
    public function optimizeForOcr(string $filePath): string
    {
        if (! file_exists($filePath)) {
            return $filePath;
        }

        $originalSize = filesize($filePath);
        $isPng        = strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'png';

        Log::info('[ImageOptimization] Optimasi OCR dimulai.', [
            'path' => $filePath,
            'size' => $originalSize,
        ]);

        try {
            $image = $this->manager->decodePath($filePath);

            // 1. Normalisasi Resolusi (Max 1920px)
            // Gemini Pro optimal pada 1024-2048px untuk dokumen.
            $maxWidth  = 1920;
            $maxHeight = 1920;

            if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
                $image->scale(width: $maxWidth, height: $maxHeight);
                Log::info('[ImageOptimization] Image scaled to 1920px limit.');
            }

            // 2. Standardisasi Format & Kualitas (EXIF removal included in v4 save)
            // Kita gunakan JPEG 85% untuk keseimbangan tajam vs ukuran.
            $encoded = $image->encode(new JpegEncoder(quality: 85));
            $encoded->save($filePath);

            $finalSize = filesize($filePath);
            Log::info('[ImageOptimization] Optimasi selesai.', [
                'final_size'    => $finalSize,
                'reduction_pct' => round((1 - $finalSize / $originalSize) * 100, 2),
            ]);

        } catch (\Exception $e) {
            Log::error('[ImageOptimization] Gagal optimasi OCR.', [
                'error' => $e->getMessage(),
            ]);
        }

        return $filePath;
    }

    /**
     * Info diagnostik untuk keperluan logging.
     */
    public function getCompressionInfo(string $filePath): array
    {
        if (! file_exists($filePath)) {
            return ['error' => 'File tidak ditemukan.'];
        }

        $size = filesize($filePath);
        return [
            'current_size'      => $size,
            'current_size_mb'   => round($size / 1048576, 2),
            'needs_compression' => $size > $this->maxSize,
            'target_size'       => $this->maxSize,
            'target_size_mb'    => round($this->maxSize / 1048576, 2),
        ];
    }

    // ─── Private: Kompresi Steps (v4 Syntax) ────────────────────

    private function stepQualityReduction(string $filePath): bool
    {
        $steps = [$this->initialQuality, 80, $this->minQuality];

        foreach ($steps as $quality) {
            // v4: Use decodePath instead of read
            $image = $this->manager->decodePath($filePath);
            
            // v4: Use encoders instead of toJpeg sugar
            $encoded = $image->encode(new JpegEncoder(quality: $quality));
            $encoded->save($filePath);

            $size = filesize($filePath);
            Log::info('[ImageCompression] Step 1 — quality reduction.', [
                'quality'      => $quality,
                'current_size' => $size,
            ]);

            if ($size <= $this->maxSize) {
                return true;
            }
        }

        return false;
    }

    private function stepDimensionResize(string $filePath, bool $isPng): bool
    {
        $scales = [0.90, 0.80, 0.70];

        foreach ($scales as $scale) {
            $image  = $this->manager->decodePath($filePath);
            $width  = (int) ($image->width() * $scale);
            $height = (int) ($image->height() * $scale);

            // v4: resize() method still exists
            $image->resize($width, $height);

            if ($isPng) {
                $encoded = $image->encode(new PngEncoder());
            } else {
                $encoded = $image->encode(new JpegEncoder(quality: $this->minQuality));
            }
            $encoded->save($filePath);

            $size = filesize($filePath);
            Log::info('[ImageCompression] Step 2 — dimension resize.', [
                'scale'        => $scale,
                'new_width'    => $width,
                'new_height'   => $height,
                'current_size' => $size,
            ]);

            if ($size <= $this->maxSize) {
                return true;
            }
        }

        return false;
    }

    private function stepAggressiveFallback(string $filePath, bool $isPng): void
    {
        $image  = $this->manager->decodePath($filePath);
        $width  = (int) ($image->width() * 0.50);
        $height = (int) ($image->height() * 0.50);

        $image->resize($width, $height);

        if ($isPng) {
            $encoded = $image->encode(new PngEncoder());
        } else {
            $encoded = $image->encode(new JpegEncoder(quality: $this->minQuality));
        }
        $encoded->save($filePath);

        Log::info('[ImageCompression] Step 3 — aggressive fallback.', [
            'new_width'    => $width,
            'new_height'   => $height,
            'current_size' => filesize($filePath),
        ]);
    }
}
