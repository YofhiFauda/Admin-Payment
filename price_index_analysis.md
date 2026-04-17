# Analisa Komprehensif: Price Index System WHUSNET

Dokumen ini merangkum arsitektur, logika bisnis, perbaikan kritis, dan rencana peningkatan sistem referensi harga (Price Index) berdasarkan dokumentasi sistem terbaru.

## 1. Filosofi Sistem: "Detect, Don't Block"
Berdasarkan `PRICE_INDEX_CRITICAL_FIXES.md`, sistem ini harus bergeser dari validasi keras (hard validation) ke validasi lunak (soft warning).
- **Masalah:** Memblokir input teknisi saat harga tinggi menyebabkan Owner tidak pernah tahu ada upaya pembelian harga mahal.
- **Solusi:** Izinkan pengajuan masuk, tandai sebagai anomali, dan kirimkan notifikasi real-time ke Owner untuk persetujuan manual.

## 2. Arsitektur Data Terpadu (Unified Schema)
Berdasarkan gabungan ketiga dokumen, model data `price_indexes` dan `price_anomalies` harus mencakup:

### PriceIndex Model
- `min_price`, `max_price`, `avg_price`: Nilai referensi.
- `is_manual` (Boolean): Jika true, perhitungan otomatis dihentikan untuk item ini.
- `needs_initial_review` (Boolean): Flag untuk item baru (Cold Start).
- `manual_set_by`, `manual_set_at`, `manual_reason`: Audit trail untuk intervensi Owner.
- `total_transactions`, `last_calculated_at`: Metadata performa.

### PriceAnomaly Model
- `severity`: low, medium, critical (berdasarkan % kelebihan).
- `status`: pending, reviewed, approved, rejected.
- `detection_method`: 'standard' atau 'category_baseline'.

## 3. Logika Bisnis & Algoritma
### A. Perhitungan (Recalculation)
- **Metode IQR (Interquartile Range):** Digunakan untuk membersihkan data dari outlier (harga yang terlalu ekstrem/salah input) sebelum menghitung rata-rata.
- **Weighted Average:** Transaksi terbaru memiliki bobot lebih besar dibanding transaksi lama.
- **Incremental Update:** Hanya menghitung ulang item yang memiliki transaksi `approved` baru dalam 24 jam terakhir untuk menjaga performa server.

### B. Strategi Cold Start (Item Baru)
Jika barang belum pernah dibeli sebelumnya:
1. **Category Fallback:** Gunakan rata-rata harga dari kategori yang sama sebagai referensi sementara.
2. **Auto-Create:** Buat entry `price_indexes` baru dengan status `needs_initial_review`.
3. **Owner Alert:** Beritahu Owner bahwa ada barang baru yang memerlukan penetapan harga referensi.

## 4. Keamanan & Integritas Data (Race Conditions)
Untuk menghindari duplikasi perhitungan atau inkonsistensi data saat banyak worker bekerja:
- **Atomic Increments:** Menggunakan `increment()` database untuk counter transaksi.
- **Cache Locking:** Menggunakan Redis Lock (`ShouldBeUnique`) pada Job recalculation agar satu item tidak dihitung oleh dua worker secara bersamaan.
- **Pessimistic Locking:** Menggunakan `lockForUpdate()` saat proses update harga manual oleh Owner.

## 5. Resiliensi Notifikasi (Failover Strategy)
Sistem tidak boleh bergantung 100% pada n8n.
- **Layer 1 (Primary):** Laravel mengirim notifikasi langsung via Telegram Bot API dan Database Notification.
- **Layer 2 (Secondary/n8n):** Webhook n8n digunakan untuk pencatatan eksternal (Google Sheets, Analytics) dan pengayaan data.
- **Circuit Breaker:** Jika n8n down, sistem berhenti mengirim webhook sementara untuk menghindari penumpukan antrian (timeout), namun notifikasi primary tetap jalan.

## 6. Peningkatan UI Real-time
- **Debounce Optimization:** Mengurangi jeda pengecekan saat mengetik (500ms).
- **Blur Trigger:** Pengecekan instan saat user pindah fokus dari field harga/nama barang.
- **Category Trigger:** Pengecekan ulang saat kategori diubah untuk memicu logika fallback.

## 7. Action Plan (Roadmap Implementasi)
1. **Update Schema:** Migrasi untuk kolom `is_manual`, `needs_initial_review`, dan tabel `price_anomalies`.
2. **Refactor Service:** Perbaiki `PriceIndexService` untuk mendukung manual override dan perhitungan avg yang benar.
3. **Job Refactoring:** Implementasi `CalculatePriceIndexJob` dengan logika incremental dan locking.
4. **Frontend Update:** Perbarui `form-pengajuan.blade.php` dengan trigger yang lebih responsif dan alert non-blocking.
5. **Dashboard Owner:** Pembuatan halaman review anomali dan master data price index.

---
**Status Analisa:** Selesai. Siap untuk tahap implementasi kode.
