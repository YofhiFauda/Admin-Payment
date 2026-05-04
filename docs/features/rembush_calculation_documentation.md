# Alur Perhitungan Rembush & Pembagian Cabang

Dokumen ini menjelaskan alur teknis mengenai bagaimana sistem menghitung nominal pengeluaran pada transaksi Rembush (Reimbursement) dan Pengajuan beserta rumus pembagian beban ke masing-masing cabang. 

Proses ini terbagi menjadi tahapan aplikasi antarmuka (Frontend), Backend (Validasi & Penyimpanan), API Integrations (OCR), dan Skrip Cadangan/Perbaikan (Maintenance).

---

## 1. Perhitungan Total Pembelian Barang (Frontend)
Di sisi antarmuka pengguna (Frontend), perhitungan bersifat *real-time* menggunakan JavaScript ketika user (Teknisi) menambahkan baris barang/item.

**Lokasi File:** `resources/views/transactions/form.blade.php`

### Rumus Total Barang
Sistem akan selalu me-render ulang total nilai setiap kali ada perubahan input (Qty atau Harga Satuan) yang diproses melalui fungsi `renderItems()`. 

```javascript
function renderItems() {
    itemsTbody.innerHTML = '';
    let totalAmount = 0; // Inisialisasi awal

    items.forEach((item, i) => {
        // Rumus Dasar per Item: Kuantitas x Harga Satuan
        const rowTotal = (item.qty || 0) * (item.price || 0);
        totalAmount += rowTotal;
        
        // ... kode DOM append ...
    });
    
    // Menyimpan hasil total akhir ke dalam input hidden 
    // untuk dikirim via POST request ke backend
    formTotalAmount.value = totalAmount; 
}
```

---

## 2. Rumus Pembagian Cabang / Alokasi (Frontend)
Setelah mendapatkan `totalAmount` dari pembelian barang, sistem bisa membaginya ke berbagai cabang tujuan menggunakan tiga opsi metode fungsi dalam `renderDistribution()`.

### Metode Pembagian:
1. **Bagi Rata (Equal)**:
   Membagi jumlah nominal secara merata ke seluruh cabang yang dipilih.
   - **Persentase:** `100 / Jumlah Cabang`
   - **Nominal:** `Total Transaksi / Jumlah Cabang`

2. **Persentase (Percent)**:
   Alokasi berdasarkan input persen dari user secara dinamis (Total harus 100%).
   - **Nominal:** `(Total Transaksi x Persentase Input) / 100`

3. **Manual**:
   User memasukkan beban nominal secara spesifik per masing-masing cabang.
   - **Persentase:** `(Nominal Input User / Total Transaksi) x 100`

**Contoh Kode di JS (Alokasi Frontend):**
```javascript
selectedBranches.forEach((branch, idx) => {
    if (currentMethod === 'equal') {
        branch.percent = parseFloat((100 / selectedBranches.length).toFixed(2));
        branch.value   = totalAmount > 0 ? Math.round(totalAmount / selectedBranches.length) : 0;
    } 
    else if (currentMethod === 'percent') {
        branch.value = totalAmount > 0
            ? Math.round((totalAmount * (branch.percent || 0)) / 100) : 0;
    } 
    else if (currentMethod === 'manual') {
        branch.percent = totalAmount > 0
            ? parseFloat(((branch.value / totalAmount) * 100).toFixed(2)) : 0;
    }
});
```

---

## 3. Penyimpanan Transaksi Rembush & Pengajuan (Backend)
Setelah dikirimkan, Controller menerima Request untuk divalidasi dan disimpan. Skema *Pengajuan Pembelian* memiliki logika yang identik namun sedikit modifikasi pada variabel pengalinya. Transaksi akan mencatat nominal utama dan menambahkan relasi alokasi untuk tabel Pivot Cabang (`transaction_branch`).

**Lokasi File Utama:** 
- `app/Http/Controllers/RembushController.php`
- `app/Http/Controllers/PengajuanController.php`

### A. Proses Penyimpanan Rembush
```php
// Pembuatan Record Transaksi Utama
$transaction = Transaction::create([
    'type'           => Transaction::TYPE_REMBUSH,
    'amount'         => $request->amount, // Total nominal dari JS Frontend
    'items'          => $request->items,  // Data JSON array rincian barang
    // ...
]);

// Proses Pembagian Beban ke Relasi Cabang
if ($request->branches && count($request->branches) > 0) {
    foreach ($request->branches as $branchData) {
        $allocPercent = floatval($branchData['allocation_percent']);
        
        // Perhitungan Ulang Backend:
        // Nominal Beban = (Total Transaksi x Persentase Cabang) / 100
        $allocAmount  = intval(round(($transaction->amount * $allocPercent) / 100));

        // Attach relasi tabel pivot transaction_branch
        $transaction->branches()->attach($branchData['branch_id'], [
            'allocation_percent' => $allocPercent,
            'allocation_amount'  => $allocAmount,
        ]);
    }
}
```

### B. Proses Penyimpanan Pengajuan Saldo
Untuk pengajuan, perhitungan jumlah awalnya langsung dikali di Controller (Kuantitas x Estimasi Satuan):
```php
// Proses perhitungannya saat disimpan
$amount = $request->estimated_price * $request->quantity;

foreach ($request->branches as $branchData) {
    $allocPercent = floatval($branchData['allocation_percent']);
    // Menggunakan variabel $amount dari hasil kali kuantitas & estimasi
    $allocAmount  = intval(round(($amount * $allocPercent) / 100));

    $transaction->branches()->attach($branchData['branch_id'], [
        'allocation_percent' => $allocPercent,
        'allocation_amount'  => $allocAmount,
    ]);
}
```

---

## 4. Pembaruan Transaksi (Backend Edit)
Dalam kasus di mana pihak Admin memeriksa dan melakukan *Edit/Update* terkait cabang, file controller yang memprosesnya mengakomodasi penyesuaian nominal secara manual untuk persentasenya.

**Lokasi File:** `app/Http/Controllers/TransactionController.php` (Method: `update`)

```php
if ($request->branches && count($request->branches) > 0) {
    $effectiveAmount = $transaction->amount;
    foreach ($request->branches as $branchData) {
        $allocPercent = floatval($branchData['allocation_percent']);
        
        // Validasi Alokasi Manual
        // Jika UI mengirim array object 'allocation_amount' (Metode Manual),
        // gunakan nominal tersebut langsung. Jika tidak, pakai rumus persentase:
        $allocAmount = isset($branchData['allocation_amount']) && $branchData['allocation_amount']
            ? intval($branchData['allocation_amount'])
            : intval(round(($effectiveAmount * $allocPercent) / 100));

        $transaction->branches()->attach($branchData['branch_id'], [
            'allocation_percent' => $allocPercent,
            'allocation_amount'  => $allocAmount,
        ]);
    }
}
```

---

## 5. Komponen Tambahan (API & Maintenance Ekosistem)

Sistem juga memiliki interaksi lain terhadap komponen relasi cabang di luar ekosistem antarmuka web biasa:

### A. Endpoint Direct API (Mobile / OCR Upload)
API webhooks/callbacks untuk mengunggah bukti nota pertama kali (misalnya dari aplikasi teknisi) tidak menghitung baris iterasi item, melainkan langsung memberikan beban tunggal ke cabang yang dipilih dari device.

**Lokasi File:** `app/Http/Controllers/Api/V1/OcrNotaController.php`

```php
// Cek jika ID cabang dikirim melalui request upload utama
if ($request->has('branch_id')) {
    // Memberikan full 100% alokasi ke cabang target yang diinput
    $transaction->branches()->attach($request->branch_id, [
        'allocation_percent' => 100, 
        'allocation_amount'  => $request->expected_nominal ?? 0,
    ]);
}
```

### B. Command Perbaikan Kesalahan Cabang
Sebuah *Artisan Console Command* dirancang sebagai *safeguard*/skrip penyelamat apabila ada *bug/glitch* data. Masalah ini bisa saja berupa input yang memuat nominal menjadi Rp 0 meskipun persentasenya tercatat dengan benar. 

**Lokasi File:** `app/Console/Commands/FixBranchPivots.php` 
*(Jalankan menggunakan: `php artisan db:fix-branch-pivots`)*

```php
public function handle()
{
    $txs = \App\Models\Transaction::with('branches')->get();
    foreach ($txs as $t) {
        $eff = $t->effective_amount; 
        
        foreach ($t->branches as $b) {
            // Jika ada relasi "allocation_amount == 0" NAMUN persentasenya valid
            if ($b->pivot->allocation_amount == 0 && $b->pivot->allocation_percent > 0) {
                
                // Recalculate secara programatis dan simpan aman
                $amt = intval(round(($eff * $b->pivot->allocation_percent) / 100));
                $t->branches()->updateExistingPivot($b->id, ['allocation_amount' => $amt]);
            }
        }
    }
}
```

---

## Ringkasan Eksekusi Alur Kerja
1. **User (Pembuat):** Memasukkan nominal item/barang per baris pada browser, Frontend (Javascript) mengalikan menjadi subtotal dan memunculkan "Total Amount".
2. **Setup Distribusi (Web):** JS mendistribusikan "Total Amount" berdasarkan 3 metode pilihan alokasinya dan menyiapkan payload Request (JSON).
3. **Penyimpanan (Backend):** Payload diteruskan ke backend. Backend memvalidasi *request*, menyimpan total, dan menghitung `allocAmount` ke Pivot DB menggunakan kalkulasi matematika murni via nilai variabel `allocation_percent`.
4. **Modifikasi (Admin):** Bila Admin melakukan update pada transaksinya, backend dapat menerima override prioritas nilai operasional manual selama validnya request.
5. **Alternatif & Pemulihan:** Flow alternatif langsung menargetkan 100% Branch pada saat upload langsung *(Direct Upload)* dan sewaktu-waktu data cacat bisa dipulihkan berbekal Artisan CLI (melalui command *Fixtures/Maintenance*).
