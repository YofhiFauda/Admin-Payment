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
        // Payment & OCR fields
        'expected_total',
        'actual_total',
        'selisih',
        'ocr_result',
        'flag_reason',
        'ocr_confidence',
        'konfirmasi_by',
        'konfirmasi_at',
        'pembayaran_id',
        'foto_penyerahan',
        'bukti_transfer',
        'overall_confidence',
        'confidence_label',
        'field_confidence',
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
            'confidence' => 'integer',
            'overall_confidence' => 'integer',
            'field_confidence' => 'array',
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

        // Auto-calculate discrepancy (selisih) and ensure expected_total is set
        static::saving(function ($transaction) {
            // Ensure expected_total has a fallback to the approved amount
            if (is_null($transaction->expected_total)) {
                $transaction->expected_total = $transaction->effective_amount;
            }

            // Always recalculate selisih if actual_total is present
            if (!is_null($transaction->actual_total)) {
                $transaction->selisih = $transaction->expected_total - $transaction->actual_total;
            }
        });
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
     * Both Rembush and Pengajuan now use the `amount` field for the total value.
     */
    public function getEffectiveAmountAttribute(): int
    {
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

    /**
     * Standardize response data shape for frontend search and real-time broadcasting.
     */
    public function toSearchArray(): array
    {
        // Hubungan (submitter, reviewer, branches) HARUS dipastikan diload sebelumnya via `with()` 
        // untuk menghindari N+1 query yang masif. 
        // Jangan panggil $this->loadMissing() di sini bila sedang memproses banyak data (seperti di getAllForSearch).

        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'submitter_name' => $this->submitter->name ?? '-',
            'customer' => $this->customer ?? '',
            'vendor' => $this->vendor ?? '',
            'type' => $this->type,
            'type_label' => $this->type_label,
            'category' => $this->category,
            'category_label' => $this->type === 'pengajuan' 
                ? (self::PURCHASE_REASONS[$this->purchase_reason] ?? '-')
                : (self::CATEGORIES[$this->category] ?? '-'),
            'status' => $this->status,
            'status_label' => $this->status_label,
            'amount' => $this->amount,
            'formatted_amount' => number_format($this->amount ?? 0, 0, ',', '.'),
            'date' => $this->date ? \Carbon\Carbon::parse($this->date)->format('d M Y') : null,
            'created_at' => $this->created_at->format('d M Y'),
            'created_at_search' => $this->created_at->format('d-m-Y Y-m-d'),
            'ai_status' => $this->ai_status,
            'payment_method' => $this->payment_method,
            'specs' => $this->specs,
            'submitter' => $this->submitter ? [
                'id' => $this->submitter->id,
                'name' => $this->submitter->name,
                'rekening_bank' => $this->submitter->rekening_bank,
                'rekening_nomor' => $this->submitter->rekening_nomor,
                'rekening_nama' => $this->submitter->rekening_nama,
            ] : null,
            'upload_id' => $this->upload_id,
            'confidence' => $this->confidence,
            'submitter_has_telegram' => (bool) ($this->submitter->telegram_chat_id ?? false),
            'rejection_reason' => $this->rejection_reason,
            'effective_amount' => $this->effective_amount,
            'purchase_reason' => $this->purchase_reason,
            'purchase_reason_label' => $this->purchase_reason ? (self::PURCHASE_REASONS[$this->purchase_reason] ?? '') : '',
            // Search string untuk matching
            'search_text' => strtolower(
                ($this->submitter->name ?? '') . ' ' .
                $this->invoice_number . ' ' .
                ($this->customer ?? '') . ' ' .
                ($this->vendor ?? '') . ' ' .
                $this->created_at->format('d M Y d-m-Y Y-m-d')
            ),
        ];
    }
}