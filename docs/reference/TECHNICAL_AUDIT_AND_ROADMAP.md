# 🛡️ Project Audit & Technical Roadmap
## WHUSNET Admin Payment System

Dokumen ini berisi hasil analisa mendalam terhadap arsitektur, keamanan, database, dan kualitas kode project **WHUSNET Admin Payment** per April 2026.

---

### 🔍 1. Temuan Utama (Deficiencies)

#### A. Arsitektur Database (Critical)
*   **Masalah "God Model" (`Transaction.php`):** Model ini menangani 3 entitas bisnis (Rembush, Pengajuan, Gudang) dalam satu tabel. Menyebabkan tabel memiliki banyak kolom `nullable`, logika accessor yang kompleks, dan risiko korupsi data.
*   **JSON Blob vs Relasi:** Data barang (`items`) disimpan sebagai JSON. Hal ini sangat menyulitkan pelaporan (reporting) SQL murni (misal: menghitung total quantity barang spesifik antar cabang).
*   **Predictability ID:** `upload_id` dan `invoice_number` yang berbasis urutan (increment) memudahkan serangan *ID Enumeration*.

#### B. Keamanan & Validasi (High)
*   **Inline Validation:** Validasi dilakukan langsung di dalam Controller menggunakan `Validator::make`. Ini membuat Controller sangat gemuk dan sulit di-*maintenance*.
*   **Manual Sanitization:** Proses pembersihan format mata uang (`Rp. 1.000`) dilakukan manual sebelum validasi. Logika ini berulang di banyak controller.
*   **RBAC Tipis:** Middleware `CheckRole` hanya mengecek string role dasar, belum mendukung *Permission-based access* yang lebih granular.

#### C. Integritas Data & Infrastruktur (Very High) - *NEW*
*   **Atomicity Risk:** Proses `uploadPengajuanInvoice` dan `calculateAndCreateDebts` **tidak dibungkus** dalam `DB::transaction()`. Jika server mati di tengah proses, status transaksi berubah tapi data hutang antar cabang tidak tercipta (Laporan Keuangan Selisih).
*   **IdGenerator Collision:** Fallback `rand(90000, 99999)` saat Redis mati sangat berisiko terjadi duplikasi ID pada lingkungan multi-worker.
*   **Redis Eviction:** Redis digunakan bersama untuk Cache & ID Sequence dengan policy `allkeys-lru`. Tekanan cache tinggi dapat menghapus counter ID, menyebabkan reset sequence ke 1.

#### D. Keamanan Lanjutan & UX (High) - *NEW*
*   **IDOR/Path Traversal:** File nota disimpan di storage public dan diakses via asset URL tanpa pengecekan kepemilikan. User cabang A bisa melihat nota cabang B jika menebak URL.
*   **Search Payload:** Hybrid search menarik semua kolom (termasuk JSON items yang besar) ke browser. Dapat menyebabkan lag/crash pada perangkat mobile teknisi.

---

### 💡 2. Saran & Rekomendasi Perbaikan

| Domain | Saran Perbaikan |
| :--- | :--- |
| **Database** | Gunakan **STI** untuk model. Pindahkan `items` ke tabel relasi `transaction_items`. |
| **Validasi** | Implementasikan **FormRequest Classes** untuk setiap operasi Store/Update. |
| **Integritas** | Bungkus proses multi-tabel dalam **DB::transaction()**. |
| **Infrastruktur** | Gunakan Redis dedicated untuk ID Generator atau set policy `volatile-lru`. Tambahkan resource limits di Docker. |
| **Keamanan** | Simpan nota di disk `private` dan gunakan controller khusus untuk serving file (Access Control). |
| **UX/Frontend** | Gunakan **Lean Search Payload** (hanya kirim kolom tabel, bukan detail JSON) untuk search engine. |

---

### 🚀 3. Roadmap Perbaikan (4 Minggu)

#### **Minggu 1: Stability & Security Patch (Kritis)**
*   [ ] Implementasi `DB::transaction()` pada `OcrNotaController`.
*   [ ] Perbaikan `IdGeneratorService` (Gunakan Database-backed sequence sebagai fallback).
*   [ ] Migrasi inline validation ke `app/Http/Requests`.
*   [ ] Tambahkan resource `limits` (CPU/RAM) di `docker-compose.yml`.

#### **Minggu 2: Database Integrity & Relasi**
*   [ ] Buat migrasi tabel `transaction_items` (memindahkan data dari JSON items).
*   [ ] Implementasikan UUID pada tabel `transactions`.
*   [ ] Tambahkan *Foreign Key constraints* yang lebih ketat.

#### **Minggu 3: Security & RBAC Cleanup**
*   [ ] Pindahkan file nota ke disk `private`.
*   [ ] Buat `FileController` untuk serving nota dengan Role Check.
*   [ ] Implementasikan sistem *Permission* (misal: `can('approve-owner')`).

#### **Minggu 4: Frontend & UX Optimization**
*   [ ] Optimalisasi endpoint `/search-data` agar hanya mengirim data "Lean".
*   [ ] Pecah `index.blade.php` menjadi **Blade Components**.
*   [ ] Modularisasi `SearchEngine.js` menggunakan Vite.

---

### 📝 Catatan Tambahan
Fokus utama adalah **Integritas Data Keuangan**. Sistem tidak boleh membiarkan data status "Paid" muncul tanpa catatan hutang yang valid. Skalabilitas harus diimbangi dengan proteksi resource server.
