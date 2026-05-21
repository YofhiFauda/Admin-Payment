# Panduan Penggunaan Sistem Admin Payment WHUSNET

## 1. Apa itu Website Admin-Payment?
Website Admin-Payment adalah sebuah sistem manajemen keuangan internal yang dikembangkan khusus untuk **WHUSNET**. Sistem ini digunakan untuk mengelola, melacak, dan mengotomatisasi berbagai jenis transaksi keuangan operasional perusahaan, seperti:
- **Rembushment (Rembush):** Proses penggantian dana karyawan yang telah digunakan untuk keperluan perusahaan.
- **Pengajuan (Purchase Request):** Permintaan dana atau pembelian barang/jasa sebelum pengeluaran terjadi.
- **Gudang (Pembelian Internal):** Pencatatan khusus untuk pembelian kebutuhan stok atau operasional gudang secara cepat.
- **Pengeluaran Lain-lain:** Pencatatan arus kas lain seperti hutang, piutang, dan prive perusahaan.
- **Penggajian (Gaji):** Sistem kalkulasi otomatis untuk gaji pokok, bonus, dan potongan karyawan.

## 2. Apa Tujuannya?
Tujuan utama dari sistem ini adalah untuk **mendigitalisasi, mengotomatisasi, dan mengamankan (streamline) proses administrasi serta persetujuan keuangan**. Sistem ini diciptakan untuk memastikan bahwa setiap sen uang yang keluar tercatat dengan akurat, memiliki alur persetujuan (approval) yang tepat sasaran, serta meminimalisir kesalahan pencatatan secara manual (human error).

## 3. Apa Manfaatnya?
Sistem ini membawa berbagai fitur unggulan yang sangat bermanfaat bagi perusahaan:
- **Ekstraksi Data Otomatis (OCR AI):** Didukung oleh teknologi Google Gemini AI dan n8n, sistem dapat membaca foto nota/invoice secara otomatis, mencegah klaim nota ganda (duplikat), memvalidasi tanggal nota, dan menarik data nominal secara instan.
- **Keamanan Finansial Bertingkat (Dual-Gate Approval):** Transaksi dengan nominal di atas Rp 1.000.000 secara otomatis wajib melalui dua lapis persetujuan (dari Atasan, lalu dilanjutkan ke Owner) untuk mencegah kebocoran dana.
- **Pelacakan Hutang Antar Cabang Otomatis:** Jika ada satu cabang yang menalangi biaya cabang lain dalam satu transaksi (Split Tagihan), sistem otomatis mencatatnya sebagai "Hutang/Piutang Cabang" dan menahan status transaksi sampai hutang tersebut lunas.
- **Transparansi & Jejak Audit (Audit Trail):** Sistem merekam dua versi data: versi asli yang diinput Teknisi, dan versi yang diedit Manajemen. Perbedaannya bisa dilihat secara bersebelahan (Side-by-side) untuk audit.
- **Sistem Indeks Harga (Kontrol Anomali):** Sistem secara otomatis menghitung harga pasar rata-rata historis dari barang yang sering dibeli dan memberikan peringatan (Peringatan Anomali) jika ada pengajuan harga yang terdeteksi terlalu mahal.
- **Pemantauan Real-time:** Dilengkapi dengan *Dashboard* interaktif yang melakukan *update* tanpa perlu *refresh* halaman (WebSocket Reverb), memudahkan pemantauan hutang-piutang cabang secara instan.

## 4. Bagaimana Cara Menggunakannya?

### A. Peran Akses Pengguna (Role)
Sistem membedakan fungsi berdasarkan peran (Role):
- **Teknisi / User:** Membuat tiket pengajuan atau rembushment, serta mengunggah foto bukti (nota/invoice).
- **Atasan:** Melakukan tinjauan operasional dan persetujuan tahap pertama.
- **Owner:** Memberikan persetujuan final keuangan (wajib untuk transaksi besar).
- **Admin:** Bertugas mendistribusikan beban tagihan ke berbagai cabang dan memproses pembayaran, dengan dibatasi haknya untuk mengubah nominal (Restricted Edit Mode).

### B. Alur Kerja Standar (Contoh: Pengajuan / Rembushment)

1. **Pembuatan Pengajuan (Submit):** 
   - Teknisi atau Manajemen masuk ke menu Pengajuan / Rembushment.
   - Isi form yang tersedia (Manajemen bisa mewakili teknisi tertentu menggunakan fitur "On Behalf Of").
   - Unggah foto/PDF nota atau invoice.
   
2. **Proses AI & Verifikasi (Otomatis):**
   - Setelah disimpan, sistem OCR akan memproses gambar. Jika terdeteksi usang atau pernah diklaim sebelumnya, sistem akan menolak.
   - Nominal, nama item, dan detail lain akan terbaca otomatis.
   
3. **Proses Persetujuan (Approval):**
   - **Nominal < Rp 1 Juta:** Hanya butuh satu persetujuan (Atasan/Owner).
   - **Nominal >= Rp 1 Juta:** Harus disetujui secara berurutan: Atasan ➔ Owner.
   - Setelah disetujui penuh, transaksi masuk ke fase **"Menunggu Pembayaran" (Waiting Payment)**.
   
4. **Distribusi & Hutang Cabang (Bila Ada):**
   - Manajemen/Admin dapat membagi beban pembayaran transaksi ke satu atau beberapa cabang.
   - Jika terjadi talangan antar cabang, sistem otomatis akan membuat riwayat **Hutang Antar Cabang**.
   
5. **Proses Pelunasan (Pembayaran):**
   - Transaksi dapat dibayar menggunakan opsi **Transfer** (wajib melampirkan bukti transfer dan memilih rekening bank) atau **Tunai/Cash** (tanpa syarat rekening bank).
   - Transaksi hanya akan berpindah ke status **"Selesai" (Completed)** apabila seluruh Hutang Antar Cabang yang terhubung sudah dibayar/dilunasi.
   
6. **Fase Penguncian (Settlement Phase):**
   - Pada saat seluruh pembayaran selesai dan transaksi dalam status **"Menunggu Pelunasan"**, dokumen akan dikunci penuh (Read-only) secara visual maupun di sistem database untuk mencegah perubahan tak terotorisasi.
