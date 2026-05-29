<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * TransactionExportJob
 *
 * Track progress export Excel async. Pakai UUID sebagai primary key
 * untuk security (tidak guessable lewat URL).
 *
 * NB: Manual UUID generation lewat `creating` event — lebih reliable
 * daripada `HasUuids` trait yang kadang konflik dengan attribute mass-assignment.
 */
class TransactionExportJob extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'filters',
        'status',
        'total_transactions',
        'processed_transactions',
        'filename',
        'file_path',
        'file_size',
        'error_message',
        'duration_ms',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'filters'                => 'array',
            'total_transactions'     => 'integer',
            'processed_transactions' => 'integer',
            'file_size'              => 'integer',
            'duration_ms'            => 'integer',
            'started_at'             => 'datetime',
            'completed_at'           => 'datetime',
        ];
    }

    /**
     * Auto-generate UUID jika tidak di-set saat creating.
     */
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Progress percentage (0-100). */
    public function getProgressPercentAttribute(): int
    {
        if ((int) $this->total_transactions === 0) {
            return 0;
        }
        return (int) round(($this->processed_transactions / $this->total_transactions) * 100);
    }

    /** Human-readable status label. */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'queued'     => 'Menunggu Antrian',
            'processing' => 'Sedang Diproses',
            'completed'  => 'Selesai',
            'failed'     => 'Gagal',
            default      => 'Unknown',
        };
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['queued', 'processing']);
    }

    public function isDone(): bool
    {
        return in_array($this->status, ['completed', 'failed']);
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status'     => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(string $filePath, int $fileSize, int $durationMs): void
    {
        $this->update([
            'status'       => 'completed',
            'file_path'    => $filePath,
            'file_size'    => $fileSize,
            'duration_ms'  => $durationMs,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status'        => 'failed',
            'error_message' => $errorMessage,
            'completed_at'  => now(),
        ]);
    }

    public function updateProgress(int $processed, int $total): void
    {
        $this->update([
            'processed_transactions' => $processed,
            'total_transactions'     => $total,
        ]);
    }
}
