<?php

namespace App\Models;

use App\Services\IdGeneratorService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Transaction extends Model
{
    // ─── Type Constants ───────────────────────────────
    const TYPE_REMBUSH = 'rembush';
    const TYPE_PENGAJUAN = 'pengajuan';

    const TYPES = [
        self::TYPE_REMBUSH => 'Rembush',
        self::TYPE_PENGAJUAN => 'Pengajuan',
    ];

    // ─── Category Constants (Rembush) ─────────────────
    const CATEGORIES = [
        'beban_entertain' => 'Beban Entertain',
        'beban_komisi' => 'Beban Komisi',
        'beban_bensin_parkir_tol_kendaraan' => 'Beban Bensin, Parkir, Tol Kendaraan',
        'beban_gaji_upah_honorar' => 'Beban Gaji, Upah & Honorar',
        'beban_pertemuan' => 'Beban Pertemuan',
        'beban_konsumsi' => 'Beban Konsumsi',
        'beban_listrik' => 'Beban Listrik',
        'beban_perlengkapan_kantor' => 'Beban Perlengkapan Kantor',
        'beban_perawatan_dan_perbaikan' => 'Beban Perawatan dan Perbaikan',
        'beban_repeter' => 'Beban Repeter',
        'beban_lain_lain' => 'Beban Lain-lain',
        'beban_ai' => 'Beban AI',
        'beban_administrasi_bank' => 'Beban Administrasi Bank',
        'beban_ekspedisi_pos_materai' => 'Beban Ekspedisi, Pos & Materai',
        'beban_sewa' => 'Beban Sewa',
        'beban_tagihan_bpjs_ketenagakerjaan' => 'Beban Tagihan BPJS Ketenagakerjaan',
        'beban_pembayaran_bpjs_kesehatan' => 'Beban Pembayaran BPJS Kesehatan',
        'beban_seragam_karyawan' => 'Beban Seragam Karyawan',
        'beban_promosi_iklan' => 'Beban Promosi/Iklan',
        'beban_kebersihan_dan_keamanan' => 'Beban Kebersihan dan Keamanan',
        'beban_konsultan' => 'Beban Konsultan',
        'pph_final' => 'PPH Final',
        'pph_21' => 'PPH 21',
        'beban_sumbangan_amal' => 'Beban Sumbangan / Amal',
        'beban_telekomunikasi' => 'Beban Telekomunikasi',
        'pembelian_internet' => 'Pembelian Internet',
        'peralatan' => 'Peralatan',
        'persediaan' => 'Persediaan',
        'piutang_usaha' => 'Piutang Usaha',
        'piutang_karyawan' => 'Piutang Karyawan',
        'prive' => 'Prive',
        'diskon_penjualan' => 'Diskon Penjualan',
        'kendaraan' => 'Kendaraan',
        'retur_penjualan' => 'Retur Penjualan',
        'utang_usaha' => 'Utang Usaha',
        'perlengkapan' => 'Perlengkapan',
        'beban_operasional_lainnya' => 'Beban Operasional Lainnya',
        'beban_vendor' => 'Beban Vendor',
        'bagi_hasil' => 'Bagi Hasil',
    ];

    // ─── Payment Methods (Rembush) ────────────────────
    const PAYMENT_METHODS = [
        'transfer_teknisi' => 'Transfer ke Teknisi',
        'cash' => 'Cash',
        'transfer_penjual' => 'Transfer ke Penjual',
    ];

    // ─── Purchase Reasons (Pengajuan) ─────────────────
    const PURCHASE_REASONS = [
        'beban_entertain' => 'Beban Entertain',
        'beban_komisi' => 'Beban Komisi',
        'beban_bensin_parkir_tol_kendaraan' => 'Beban Bensin, Parkir, Tol Kendaraan',
        'beban_gaji_upah_honorar' => 'Beban Gaji, Upah & Honorar',
        'beban_pertemuan' => 'Beban Pertemuan',
        'beban_konsumsi' => 'Beban Konsumsi',
        'beban_listrik' => 'Beban Listrik',
        'beban_perlengkapan_kantor' => 'Beban Perlengkapan Kantor',
        'beban_perawatan_dan_perbaikan' => 'Beban Perawatan dan Perbaikan',
        'beban_repeter' => 'Beban Repeter',
        'beban_lain_lain' => 'Beban Lain-lain',
        'beban_ai' => 'Beban AI',
        'beban_administrasi_bank' => 'Beban Administrasi Bank',
        'beban_ekspedisi_pos_materai' => 'Beban Ekspedisi, Pos & Materai',
        'beban_sewa' => 'Beban Sewa',
        'beban_tagihan_bpjs_ketenagakerjaan' => 'Beban Tagihan BPJS Ketenagakerjaan',
        'beban_pembayaran_bpjs_kesehatan' => 'Beban Pembayaran BPJS Kesehatan',
        'beban_seragam_karyawan' => 'Beban Seragam Karyawan',
        'beban_promosi_iklan' => 'Beban Promosi/Iklan',
        'beban_kebersihan_dan_keamanan' => 'Beban Kebersihan dan Keamanan',
        'beban_konsultan' => 'Beban Konsultan',
        'pph_final' => 'PPH Final',
        'pph_21' => 'PPH 21',
        'beban_sumbangan_amal' => 'Beban Sumbangan / Amal',
        'beban_telekomunikasi' => 'Beban Telekomunikasi',
        'pembelian_internet' => 'Pembelian Internet',
        'peralatan' => 'Peralatan',
        'persediaan' => 'Persediaan',
        'piutang_usaha' => 'Piutang Usaha',
        'piutang_karyawan' => 'Piutang Karyawan',
        'prive' => 'Prive',
        'diskon_penjualan' => 'Diskon Penjualan',
        'kendaraan' => 'Kendaraan',
        'retur_penjualan' => 'Retur Penjualan',
        'utang_usaha' => 'Utang Usaha',
        'perlengkapan' => 'Perlengkapan',
        'beban_operasional_lainnya' => 'Beban Operasional Lainnya',
        'beban_vendor' => 'Beban Vendor',
        'bagi_hasil' => 'Bagi Hasil',
    ];

    protected $fillable = [
        'type',
        'invoice_number',
        'customer',
        'category',
        'description',
        'payment_method',
        'amount',
        'items',
        'date',
        'file_path',
        'status',
        'submitted_by',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'ai_status',
        'confidence',
        // Pengajuan fields
        'vendor',
        'specs',
        'quantity',
        'estimated_price',
        'purchase_reason',
        'upload_id',
        'trace_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'estimated_price' => 'integer',
            'quantity' => 'integer',
            'date' => 'date',
            'reviewed_at' => 'datetime',
            'items' => 'array',
            'specs' => 'array',
        ];
    }

    /**
     * Model Events
     */
    protected static function booted()
    {
        $clearCache = function ($transaction) {
            // Clear global stats cache (for Admin/Owner)
            Cache::forget('transactions_stats_global');

            // Clear specific user stats cache (for Teknisi)
            if ($transaction->submitted_by) {
                Cache::forget("transactions_stats_teknisi_{$transaction->submitted_by}");
            }
        };

        static::created($clearCache);
        static::updated($clearCache);
        static::deleted($clearCache);
    }

    // ─── ID Generators (delegates to IdGeneratorService) ────────────
    public static function generateInvoiceNumber(): string
    {
        return IdGeneratorService::nextInvoiceNumber();
    }

    public static function generateUploadId(): string
    {
        return IdGeneratorService::nextUploadId();
    }

    public static function generateTraceId(): string
    {
        return IdGeneratorService::nextTraceId();
    }

    // ─── Relationships ────────────────────────────────
    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'transaction_branches')
                    ->withPivot('allocation_percent', 'allocation_amount')
                    ->withTimestamps();
    }

    // ─── Type Helpers ─────────────────────────────────
    public function isRembush(): bool { return $this->type === self::TYPE_REMBUSH; }
    public function isPengajuan(): bool { return $this->type === self::TYPE_PENGAJUAN; }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    // ─── Status Helpers ───────────────────────────────
    public function isPending(): bool { return $this->status === 'pending'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isCompleted(): bool { return $this->status === 'completed'; }
    public function isRejected(): bool { return $this->status === 'rejected'; }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'approved' => 'Disetujui',
            'completed' => 'Selesai',
            'rejected' => 'Ditolak',
            default => $this->status,
        };
    }

    // ─── Amount Helpers ───────────────────────────────

    /**
     * Get the effective amount for display and approval logic.
     * Rembush: uses `amount` field
     * Pengajuan: uses `estimated_price` field
     */
    public function getEffectiveAmountAttribute(): int
    {
        if ($this->isPengajuan()) {
            return $this->estimated_price ?? 0;
        }
        return $this->amount ?? 0;
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->effective_amount, 0, ',', '.');
    }

    public static function formatShortRupiah($value)
    {
        if ($value >= 1000000000000) {
            return 'Rp ' . rtrim(rtrim(number_format($value / 1000000000000, 2, ',', '.'), '0'), ',') . ' T';
        }

        if ($value >= 1000000000) {
            return 'Rp ' . rtrim(rtrim(number_format($value / 1000000000, 2, ',', '.'), '0'), ',') . ' M';
        }

        if ($value >= 1000000) {
            return 'Rp ' . rtrim(rtrim(number_format($value / 1000000, 1, ',', '.'), '0'), ',') . ' Jt';
        }

        return 'Rp ' . number_format($value, 0, ',', '.');
    }
}