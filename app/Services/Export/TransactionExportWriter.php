<?php

namespace App\Services\Export;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderName;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\BorderStyle;
use OpenSpout\Common\Entity\Style\BorderWidth;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

/**
 * TransactionExportWriter
 *
 * High-throughput XLSX export untuk Laporan Transaksi (Pengajuan / Rembush).
 *
 * KARAKTERISTIK
 * ─────────────
 * - Memori KONSTAN (~30 MB) bahkan untuk 100k+ baris berkat OpenSpout v5.
 * - Tanpa formula Excel — semua kalkulasi dihitung di PHP saat streaming.
 * - Eager-load relasi via `lazyById` (chunk 500) — eliminasi N+1 query.
 * - Style v5 readonly — di-cache 1 instance per kategori.
 * - Reusable: dipanggil dari controller (sync) maupun job (async).
 *
 * @phpstan-type ExportFilters array{
 *     month?: int|string|null, year?: int|string|null, type?: string|null,
 *     status?: string|null, branch_id?: int|string|null
 * }
 */
class TransactionExportWriter
{
    /** Chunk size untuk lazyById streaming. */
    public const CHUNK_SIZE = 500;

    /** Brand colors (RGB hex 6-char tanpa alpha — OpenSpout v5 convention). */
    private const COLOR_HEADER_BG   = '1E3A5F';
    private const COLOR_HEADER_FONT = 'FFFFFF';
    private const COLOR_ALT_ROW     = 'EFF6FF';
    private const COLOR_TOTAL_BG    = 'E2EFDA';
    private const COLOR_SUMMARY_BG  = 'FFE082';
    private const COLOR_BORDER      = 'B0BEC5';

    /** @var array<string,Style> */
    private array $styleCache = [];

    private string $exportFormat;          // 'pengajuan' | 'rembush'
    /** @var list<string> */
    private array $headers;
    /** @var list<string> */
    private array $currencyColumns;

    /** @var array<string,float> Akumulator total per kolom (key = heading) */
    private array $columnTotals = [];

    /**
     * @param ExportFilters $filters
     * @param int|null      $forceUserId        Filter teknisi: query difilter `submitted_by = $forceUserId`
     * @param callable|null $progressCallback   fn(int $processed, int $total): void
     */
    public function __construct(
        private array $filters,
        private ?int $forceUserId = null,
        private $progressCallback = null,
    ) {
        $this->exportFormat    = (($filters['type'] ?? null) === 'pengajuan') ? 'pengajuan' : 'rembush';
        $this->headers         = $this->getHeaders();
        $this->currencyColumns = $this->getCurrencyColumns();
    }

    // ─────────────────────────────────────────────────────────────
    //  PUBLIC API
    // ─────────────────────────────────────────────────────────────

    /** Generate file ke path absolut (untuk async / temp file). */
    public function writeToFile(string $absolutePath): array
    {
        $writer = $this->makeWriter();
        $writer->openToFile($absolutePath);

        $stats = $this->writeAll($writer);

        $writer->close();
        return $stats;
    }

    /** Generate sebagai response download — untuk path SYNCHRONOUS. */
    public function streamDownload(string $filename): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        // Pakai temp file lalu kirim sebagai BinaryFileResponse.
        // ZIP (xlsx) tidak ramah dialirkan via php://output secara langsung
        // pada banyak setup Nginx + PHP-FPM (output buffering, sendfile, dsb).
        $tmp = tempnam(sys_get_temp_dir(), 'tx_export_');
        try {
            $stats = $this->writeToFile($tmp);
            Log::info('[ExportWriter] Sync export OK', [
                'rows'         => $stats['rows'],
                'transactions' => $stats['transactions'],
                'duration_ms'  => $stats['duration_ms'],
                'filename'     => $filename,
            ]);
        } catch (\Throwable $e) {
            @unlink($tmp);
            throw $e;
        }

        return response()
            ->download($tmp, $filename, [
                'Content-Type'                  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control'                 => 'no-store',
                'Access-Control-Expose-Headers' => 'Content-Disposition',
            ])
            ->deleteFileAfterSend();
    }

    /**
     * Hitung jumlah transaksi yang akan di-export — dipakai job untuk progress %.
     * Tidak meload data sama sekali, hanya COUNT.
     */
    public function countTransactions(): int
    {
        return $this->buildBaseQuery()->count();
    }

    /** Bangun nama file berdasarkan filter. */
    public function buildFilename(): string
    {
        $month  = $this->filters['month'] ?? null;
        $year   = (int) ($this->filters['year'] ?? now()->year);
        $period = $month
            ? Carbon::create($year, (int) $month)->translatedFormat('F_Y')
            : "Full_{$year}";
        $suffix = $this->exportFormat === 'pengajuan' ? '_Pengajuan' : '_Rembush';
        return "Laporan_Transaksi{$suffix}_{$period}.xlsx";
    }

    // ─────────────────────────────────────────────────────────────
    //  WRITER PIPELINE
    // ─────────────────────────────────────────────────────────────

    private function makeWriter(): Writer
    {
        // OpenSpout v5: instantiate Writer langsung dengan Options.
        $options = new Options();

        // Lebar kolom tetap — hindari `setAutoSize` PhpSpreadsheet yang sangat mahal.
        $defaultWidths = $this->exportFormat === 'pengajuan'
            ? [16, 16, 18, 14, 10, 14, 18, 24, 14, 14, 12, 12, 22, 14, 8, 14, 12, 14, 14, 14, 14, 14, 12, 14, 14, 14]
            : [14, 18, 14, 10, 14, 18, 16, 22, 8, 10, 14, 14, 22, 14, 16, 16, 14];

        foreach ($defaultWidths as $i => $w) {
            // setColumnWidth(width, ...$columns) — kolom 1-indexed
            $options->setColumnWidth((float) $w, $i + 1);
        }

        return new Writer($options);
    }

    private function writeAll(Writer $writer): array
    {
        $start = microtime(true);

        // ── Header Row ──────────────────────────────────────
        $writer->addRow(Row::fromValuesWithStyle($this->headers, $this->headerStyle()));

        // ── Pre-init totals akumulator ──────────────────────
        foreach ($this->getSumColumns() as $heading) {
            $this->columnTotals[$heading] = 0.0;
        }

        // ── Data Rows ────────────────────────────────────────
        $totalEstimated = 0;
        if ($this->progressCallback) {
            $totalEstimated = $this->countTransactions();
            ($this->progressCallback)(0, $totalEstimated);
        }

        $totalTransactions = 0;
        $rowsWritten       = 0;

        // lazyById = keyset pagination by `id`. O(N) tanpa OFFSET cost.
        // Untuk dataset >40k, ini critical — tanpa ini, lazy() pakai LIMIT/OFFSET
        // yang jadi O(N²) saat OFFSET besar.
        // PENTING: pakai 'id' (tanpa prefix table) — Eloquent attribute key
        // adalah 'id', bukan 'transactions.id'. Pakai prefix bikin error
        // "column is not present in the query result".
        foreach ($this->buildBaseQuery()->lazyById(self::CHUNK_SIZE, 'id') as $t) {
            $totalTransactions++;

            $items    = is_array($t->items) && count($t->items) > 0 ? $t->items : [[]];
            $branches = $t->branches;
            $rowCount = max(count($items), $branches->count());

            for ($i = 0; $i < $rowCount; $i++) {
                $item       = $items[$i] ?? null;
                $branch     = $branches[$i] ?? null;
                $isFirstRow = ($i === 0);
                $isAlt      = ($totalTransactions % 2 === 0);

                $rowData = $this->mapTransactionToRowData($t, $item, $branch, $isFirstRow);
                $this->accumulateTotals($rowData);

                $writer->addRow($this->buildDataRow($rowData, $isAlt));
                $rowsWritten++;
            }

            // Report progress setiap chunk transaksi (~setiap 500)
            if ($this->progressCallback && $totalTransactions % self::CHUNK_SIZE === 0) {
                ($this->progressCallback)($totalTransactions, $totalEstimated);
            }
        }

        // ── Total Row ────────────────────────────────────────
        if ($rowsWritten > 0) {
            $writer->addRow($this->buildTotalRow());
        }

        // ── Summary Section ──────────────────────────────────
        if ($rowsWritten > 0 || $totalTransactions === 0) {
            // Spacer
            $writer->addRow(Row::fromValues(['']));
            $writer->addRow($this->buildSummaryHeaderRow());
            $writer->addRow($this->buildSummaryRow('Total Transaksi', $totalTransactions, false));

            $grandTotal = $this->columnTotals['Total'] ?? 0.0;
            $writer->addRow($this->buildSummaryRow('Grand Total', $grandTotal, true));
        }

        return [
            'rows'         => $rowsWritten,
            'transactions' => $totalTransactions,
            'duration_ms'  => (int) round((microtime(true) - $start) * 1000),
        ];
    }

    private function buildDataRow(array $rowData, bool $isAlt): Row
    {
        $rowStyle  = $isAlt ? $this->dataAltStyle() : $this->dataStyle();
        $currStyle = $isAlt ? $this->dataCurrencyAltStyle() : $this->dataCurrencyStyle();

        $cells = [];
        foreach ($rowData as $colIdx => $value) {
            $heading = $this->headers[$colIdx] ?? '';

            if (in_array($heading, $this->currencyColumns, true) && is_numeric($value)) {
                $cells[] = Cell::fromValue((float) $value, $currStyle);
            } else {
                $cells[] = Cell::fromValue($value, $rowStyle);
            }
        }

        return new Row($cells);
    }

    private function buildTotalRow(): Row
    {
        $cells = [];

        foreach ($this->headers as $colIdx => $heading) {
            if ($colIdx === 0) {
                $cells[] = Cell::fromValue('TOTAL', $this->totalRowStyle());
            } elseif (array_key_exists($heading, $this->columnTotals)) {
                $cells[] = Cell::fromValue((float) $this->columnTotals[$heading], $this->totalRowCurrencyStyle());
            } else {
                $cells[] = Cell::fromValue('', $this->totalRowStyle());
            }
        }

        return new Row($cells);
    }

    private function buildSummaryHeaderRow(): Row
    {
        return Row::fromValuesWithStyle(['── RINGKASAN ──'], $this->summaryHeaderStyle());
    }

    private function buildSummaryRow(string $label, float|int $value, bool $isCurrency): Row
    {
        $valueStyle = $isCurrency ? $this->summaryCurrencyStyle() : $this->summaryRowStyle();
        return new Row([
            Cell::fromValue($label, $this->summaryRowStyle()),
            Cell::fromValue($value, $valueStyle),
        ]);
    }

    private function accumulateTotals(array $rowData): void
    {
        foreach ($this->headers as $colIdx => $heading) {
            if (!array_key_exists($heading, $this->columnTotals)) {
                continue;
            }
            $val = $rowData[$colIdx] ?? null;
            if (is_numeric($val) && $val !== '') {
                $this->columnTotals[$heading] += (float) $val;
            }
        }
    }

    /** Heading kolom yang dijumlahkan di TOTAL row. */
    private function getSumColumns(): array
    {
        if ($this->exportFormat === 'pengajuan') {
            return ['Harga Satuan', 'Jumlah', 'Sub Total', 'Ongkir', 'Diskon Pengiriman',
                    'Voucher Diskon', 'Biaya Layanan 1', 'Biaya Layanan 2', 'DPP Lainnya', 'PPN', 'Total'];
        }
        return ['Qty', 'Harga Satuan', 'Sub Total', 'Total'];
    }

    // ─────────────────────────────────────────────────────────────
    //  QUERY (Layer 3)
    // ─────────────────────────────────────────────────────────────

    private function buildBaseQuery(): Builder
    {
        $month    = $this->filters['month']    ?? null;
        $year     = $this->filters['year']     ?? now()->year;
        $type     = $this->filters['type']     ?? null;
        $status   = $this->filters['status']   ?? null;
        $branchId = $this->filters['branch_id'] ?? null;

        // Eager-load HANYA kolom yang dipakai untuk meminimalkan payload memory.
        // PENTING: jangan pakai .orderBy('transactions.id') karena bisa konflik
        // dengan internal ORDER BY id ASC dari lazyById.
        $query = Transaction::query()
            ->with([
                'branches:id,name',
                'sumberDanaBranch:id,name',
                'submitter:id,name',
                'reviewer:id,name',
                'payer:id,name',
            ]);

        if ($this->forceUserId !== null) {
            $query->where('submitted_by', $this->forceUserId);
        }
        if ($type && $type !== 'all') {
            $query->where('type', $type);
        }
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        if ($year) {
            $query->whereYear('created_at', (int) $year);
        }
        if ($month) {
            $query->whereMonth('created_at', (int) $month);
        }
        if ($branchId && $branchId !== 'all') {
            $query->whereHas('branches', fn ($q) => $q->where('branches.id', $branchId));
        }

        return $query;
    }

    // ─────────────────────────────────────────────────────────────
    //  COLUMN DEFINITIONS
    // ─────────────────────────────────────────────────────────────

    private function getHeaders(): array
    {
        if ($this->exportFormat === 'pengajuan') {
            return [
                'Sumber Dana Cabang', 'Cabang Berhutang', 'Invoice Number', 'Tanggal', 'Bulan',
                'Kategori', 'Nama Vendor', 'Link Rekomendasi', 'Merk', 'Tipe/Seri',
                'Ukuran', 'Warna', 'Keterangan', 'Harga Satuan', 'Jumlah',
                'Sub Total', 'Ongkir', 'Diskon Pengiriman', 'Voucher Diskon',
                'Biaya Layanan 1', 'Biaya Layanan 2', 'DPP Lainnya', 'PPN', 'Total',
                'Metode Pembelian', 'Status',
            ];
        }
        return [
            'Cabang', 'Invoice Number', 'Tanggal', 'Bulan', 'Kategori',
            'Nama Vendor', 'Metode Pembayaran', 'Nama Barang', 'Qty', 'Satuan',
            'Harga Satuan', 'Sub Total', 'Deskripsi', 'Total',
            'Metode Distribusi', 'Metode Pencairan', 'Status',
        ];
    }

    private function getCurrencyColumns(): array
    {
        if ($this->exportFormat === 'pengajuan') {
            return ['Harga Satuan', 'Sub Total', 'Ongkir', 'Diskon Pengiriman', 'Voucher Diskon',
                    'Biaya Layanan 1', 'Biaya Layanan 2', 'DPP Lainnya', 'PPN', 'Total'];
        }
        return ['Harga Satuan', 'Sub Total', 'Total'];
    }

    /**
     * Map satu transaksi (+ item ke-i + branch ke-i) ke array nilai per kolom.
     * Sub Total & Total dihitung di PHP (bukan formula Excel).
     */
    private function mapTransactionToRowData(Transaction $t, ?array $item, $branch, bool $isFirstRow): array
    {
        $dateStr  = $t->date ? $t->date->translatedFormat('d F Y') : '-';
        $monthStr = $t->date ? $t->date->translatedFormat('F') : '-';

        if ($this->exportFormat === 'pengajuan') {
            $borrowerName = '';
            if ($branch && $branch->id != $t->sumber_dana_branch_id) {
                $borrowerName = $branch->name;
            } elseif ($isFirstRow && $t->branches->count() > 1) {
                $firstBorrower = $t->branches->first(fn ($b) => $b->id != $t->sumber_dana_branch_id);
                $borrowerName  = $firstBorrower ? $firstBorrower->name : '';
            }

            $specs = $item['specs'] ?? [];
            $qty   = $item ? (float) ($item['quantity'] ?? ($item['qty'] ?? 0)) : 0.0;
            $price = $item ? (float) ($item['estimated_price'] ?? ($item['harga_satuan'] ?? 0)) : 0.0;
            $sub   = $price * $qty;

            $ongkir   = $isFirstRow ? (float) ($t->ongkir ?? 0)              : 0.0;
            $diskonK  = $isFirstRow ? (float) ($t->diskon_pengiriman ?? 0)   : 0.0;
            $voucher  = $isFirstRow ? (float) ($t->voucher_diskon ?? 0)      : 0.0;
            $svc1     = $isFirstRow ? (float) ($t->biaya_layanan_1 ?? 0)     : 0.0;
            $svc2     = $isFirstRow ? (float) ($t->biaya_layanan_2 ?? 0)     : 0.0;
            $dpp      = $isFirstRow ? (float) ($t->dpp_lainnya ?? 0)         : 0.0;
            $ppn      = $isFirstRow ? (float) ($t->tax_amount ?? 0)          : 0.0;

            $total = $isFirstRow
                ? $sub + $ongkir - $diskonK - $voucher + $svc1 + $svc2 + $dpp + $ppn
                : $sub;

            return [
                $t->sumberDanaBranch->name ?? '-',                              // A
                $borrowerName ?: '-',                                           // B
                $t->invoice_number,                                             // C
                $dateStr,                                                       // D
                $monthStr,                                                      // E
                $t->category_label,                                             // F
                $item['vendor'] ?? ($isFirstRow ? ($t->vendor ?? '-') : '-'),   // G
                $item['link'] ?? '-',                                           // H
                $specs['merk'] ?? '-',                                          // I
                $specs['tipe'] ?? ($specs['tipe_seri'] ?? '-'),                 // J
                $specs['ukuran'] ?? '-',                                        // K
                $specs['warna'] ?? '-',                                         // L
                $item['description'] ?? ($item['customer'] ?? '-'),             // M
                $price,                                                         // N
                $qty,                                                           // O
                $sub,                                                           // P
                $isFirstRow ? $ongkir  : '',                                    // Q
                $isFirstRow ? $diskonK : '',                                    // R
                $isFirstRow ? $voucher : '',                                    // S
                $isFirstRow ? $svc1    : '',                                    // T
                $isFirstRow ? $svc2    : '',                                    // U
                $isFirstRow ? $dpp     : '',                                    // V
                $isFirstRow ? $ppn     : '',                                    // W
                $total,                                                         // X
                $t->payment_method === 'cash' ? 'Cash'
                    : ($t->payment_method === 'transfer' ? 'Rekening' : '-'),   // Y
                $t->status_label,                                               // Z
            ];
        }

        // Rembush format
        $branchName = $branch ? $branch->name
                              : ($isFirstRow ? $t->branches->pluck('name')->first() : '-');
        $qty   = $item ? (float) ($item['qty'] ?? ($item['quantity'] ?? 0)) : 0.0;
        $price = $item ? (float) ($item['harga_satuan'] ?? ($item['estimated_price'] ?? 0)) : 0.0;
        $sub   = $qty * $price;
        // Resolve label dengan urutan: PAYMENT_METHODS[key] → key as-is → '-'
        $payMethod = Transaction::PAYMENT_METHODS[$t->payment_method] ?? ($t->payment_method ?? '-');

        return [
            $branchName,                                                        // A
            $t->invoice_number,                                                 // B
            $dateStr,                                                           // C
            $monthStr,                                                          // D
            $t->category_label,                                                 // E
            $t->vendor ?? ($t->vendor_name ?? '-'),                             // F
            $payMethod,                                                         // G
            $item['nama_barang'] ?? ($item['customer'] ?? '-'),                 // H
            $qty,                                                               // I
            $item['satuan'] ?? '-',                                             // J
            $price,                                                             // K
            $sub,                                                               // L
            $t->description ?? '-',                                             // M
            $isFirstRow ? (float) $t->amount : 0.0,                             // N
            $isFirstRow ? $this->deduceDistributionMethod($t) : '',             // O
            $payMethod,                                                         // P
            $t->status_label,                                                   // Q
        ];
    }

    private function deduceDistributionMethod(Transaction $t): string
    {
        if ($t->branches->isEmpty()) {
            return '-';
        }
        $percents = $t->branches->pluck('pivot.allocation_percent')
            ->map(fn ($p) => round((float) $p, 2))
            ->unique();
        if ($percents->count() === 1) {
            return 'Bagi Rata';
        }
        $isAllIntegers = $t->branches->every(function ($b) {
            $p = $b->pivot->allocation_percent;
            return floor($p) == $p;
        });
        return $isAllIntegers ? 'Persentase' : 'Manual';
    }

    // ─────────────────────────────────────────────────────────────
    //  STYLES (cached → 1 instance per kategori)
    //
    //  OpenSpout v5 API:
    //  - Style adalah `final readonly` — pakai constructor + builder pattern
    //  - BorderPart(BorderName, $color_hex, BorderWidth, BorderStyle)
    //  - CellAlignment, BorderName, BorderWidth, BorderStyle = enums
    // ─────────────────────────────────────────────────────────────

    private function thinBorder(): Border
    {
        return new Border(
            new BorderPart(BorderName::TOP,    self::COLOR_BORDER, BorderWidth::THIN, BorderStyle::SOLID),
            new BorderPart(BorderName::BOTTOM, self::COLOR_BORDER, BorderWidth::THIN, BorderStyle::SOLID),
            new BorderPart(BorderName::LEFT,   self::COLOR_BORDER, BorderWidth::THIN, BorderStyle::SOLID),
            new BorderPart(BorderName::RIGHT,  self::COLOR_BORDER, BorderWidth::THIN, BorderStyle::SOLID),
        );
    }

    private function headerStyle(): Style
    {
        return $this->styleCache['header'] ??= new Style(
            fontBold: true,
            fontSize: 11,
            fontColor: self::COLOR_HEADER_FONT,
            cellAlignment: CellAlignment::CENTER,
            shouldWrapText: true,
            border: $this->thinBorder(),
            backgroundColor: self::COLOR_HEADER_BG,
        );
    }

    private function dataStyle(): Style
    {
        return $this->styleCache['data'] ??= new Style(
            border: $this->thinBorder(),
        );
    }

    private function dataAltStyle(): Style
    {
        return $this->styleCache['data_alt'] ??= new Style(
            border: $this->thinBorder(),
            backgroundColor: self::COLOR_ALT_ROW,
        );
    }

    private function dataCurrencyStyle(): Style
    {
        return $this->styleCache['data_curr'] ??= new Style(
            border: $this->thinBorder(),
            format: '#,##0',
        );
    }

    private function dataCurrencyAltStyle(): Style
    {
        return $this->styleCache['data_curr_alt'] ??= new Style(
            border: $this->thinBorder(),
            backgroundColor: self::COLOR_ALT_ROW,
            format: '#,##0',
        );
    }

    private function totalRowStyle(): Style
    {
        return $this->styleCache['total'] ??= new Style(
            fontBold: true,
            border: $this->thinBorder(),
            backgroundColor: self::COLOR_TOTAL_BG,
        );
    }

    private function totalRowCurrencyStyle(): Style
    {
        return $this->styleCache['total_curr'] ??= new Style(
            fontBold: true,
            border: $this->thinBorder(),
            backgroundColor: self::COLOR_TOTAL_BG,
            format: '#,##0',
        );
    }

    private function summaryHeaderStyle(): Style
    {
        return $this->styleCache['summary_h'] ??= new Style(
            fontBold: true,
            fontSize: 11,
            backgroundColor: self::COLOR_SUMMARY_BG,
        );
    }

    private function summaryRowStyle(): Style
    {
        return $this->styleCache['summary'] ??= new Style(
            fontBold: true,
            backgroundColor: self::COLOR_SUMMARY_BG,
        );
    }

    private function summaryCurrencyStyle(): Style
    {
        return $this->styleCache['summary_curr'] ??= new Style(
            fontBold: true,
            backgroundColor: self::COLOR_SUMMARY_BG,
            format: '"Rp "#,##0',
        );
    }
}
