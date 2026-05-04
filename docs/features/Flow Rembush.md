# Flow Rembuse WhusCore AI

> **PT CONNEXA (Whusnet)** - Sistem Reimbursement Berbasis AI  
> Confidential Technical Document

## 📋 Deskripsi Sistem

Flow Rembuse WhusCore AI adalah sistem reimbursement otomatis yang memanfaatkan teknologi AI untuk memproses, memverifikasi, dan membayar klaim penggantian biaya teknisi dengan validasi multi-layer dan audit trail lengkap.

## 🔄 Alur Kerja Sistem

### 1. TAHAP SUBMISSION (TEKNISI)

Teknisi melakukan pengajuan reimbursement melalui WebApp dengan langkah berikut:

#### Input Awal
- Upload foto nota
- Pilih **Cabang**
- Pilih **Metode Pembayaran**:
  - Cash (Bayar Langsung)
  - Transfer Teknisi
  - Transfer Vendor

#### Validasi Multi-Layer

**Layer 1 - Security: Image Hashing**
- Deteksi duplikat nota secara instan (<0.1 detik)
- Mencegah pengajuan ganda

**Layer 2 - Logic: Date Validation**
- AI mengekstrak tanggal dari nota
- Validasi selisih waktu dengan hari pengajuan
- **Auto-Reject**: Jika selisih > 2 hari dari hari ini
- Status: `"AUTO-REJECT"`

**Layer 3 - AI Extraction**
AI mengisi otomatis field berikut:
- Material
- Jumlah
- Satuan
- Nominal
- Nama Vendor

#### Status Akhir
```
Status: "Pending - Menunggu Approval Admin"
```

---

### 2. TAHAP VERIFIKASI & APPROVAL (ADMIN/OWNER)

Dashboard menampilkan hasil ekstraksi AI dengan indikator confidence level.

#### Confidence Indicators
- 🟢 **High Confidence**: Data terdeteksi dengan akurasi tinggi
- 🟡 **Low Confidence**: Memerlukan verifikasi manual

#### Logic Override untuk Auto-Reject
- Nota dengan status **"Auto-Reject"** (telat > 2 hari) dapat di-override
- Tombol **"Request Override"** hanya tersedia untuk level **Admin/Owner**
- **Wajib** mengisi kolom **"Alasan Pengecualian"**

#### Keputusan Approval
- ✅ **Disetujui**: Status berubah → `"Menunggu Pembayaran"`
- ❌ **Ditolak**: Transaksi dibatalkan

---

### 3. TAHAP PEMBAYARAN (3 METODE)

#### A. CASH (Bayar Langsung)

**Proses:**
1. Admin upload foto penyerahan
   - Wajib menampilkan: **Wajah Teknisi + Uang/Voucher**
2. Status: `"Menunggu Konfirmasi Teknisi"`

**Closing:**
- Teknisi klik **"Terima"** via:
  - WebApp, atau
  - Telegram Bot
- Status final: `"Selesai"`

---

#### B. TRANSFER (Teknisi)

**Proses:**
1. Admin pilih rekening dari **Profil Teknisi**
2. Admin upload **Bukti Transfer** (Screenshot M-Banking)

**AI OCR Verification:**
- AI membaca dari struk:
  - Nominal transfer
  - Biaya admin
  - Kode unik
- **Validasi Otomatis:**
  - ✅ **MATCH**: Status → `"Selesai"`
  - ❌ **MISMATCH**: Status → `"Flagged - Selisih Nominal"`

---

#### C. TRANSFER (Vendor)

**Proses:**
1. Admin input manual rekening vendor
2. Admin upload **Bukti Transfer**

**AI OCR Verification:**
- Sama seperti Transfer Teknisi
- **Validasi Otomatis:**
  - ✅ **MATCH**: Status → `"Selesai"`
  - ❌ **MISMATCH**: Status → `"Flagged - Selisih Nominal"`

---

### 4. HANDLING SELISIH & FORCE APPROVE

#### Status "Flagged - Selisih Nominal"
- 🔒 **Transaksi terkunci** (tombol selesai dinonaktifkan)
- 🚨 **Notifikasi Real-time** dikirim ke Telegram Owner
- Hanya **Owner/Admin** yang dapat melakukan **Force Approve**

#### Requirement Force Approve
- **Wajib** mengisi alasan tertulis
- Semua selisih dicatat dalam **database audit**
- Digunakan untuk **laporan kebocoran bulanan**

---

## 🛠️ Tech Stack Recommendation

### Backend
- **Framework**: Laravel 11
- **Queue Processing**: Redis
- **Database**: PostgreSQL / MySQL with Audit Logging

### AI Engine
- **Hardware**: RTX 3090 (Self-Hosted)
- **AI Platform**: Ollama
- **Models**:
  - **Llama-3.2-Vision**: OCR & Image Recognition
  - **DeepSeek-R1**: Reasoning & Audit Analysis

### Automation & Integration
- **Workflow Automation**: n8n
- **Bot Integration**: Telegram Bot
- **Mutation Check**: n8n automated monitoring

---

## 🔐 Security Features

1. **Image Hashing**: Deteksi duplikat nota (<0.1 detik)
2. **Multi-Layer Validation**: Security → Logic → AI
3. **Audit Logging**: Tracking lengkap setiap transaksi
4. **Role-Based Access**: Admin/Owner privilege untuk override
5. **Real-time Monitoring**: Notifikasi Telegram untuk anomali

---

## 📊 Status Flow Diagram

```
[Teknisi Upload] 
    ↓
[Image Hash Check] → Duplikat? → REJECT
    ↓ (Unique)
[Date Validation] → Telat > 2 hari? → AUTO-REJECT → Override Request
    ↓ (Valid)                                            ↓
[AI Extraction]                                    [Admin Approval]
    ↓                                                     ↓
[Pending Approval] ←────────────────────────────────────┘
    ↓
[Admin/Owner Review] → Reject? → END
    ↓ (Approve)
[Menunggu Pembayaran]
    ↓
┌───┴────┬─────────┐
│        │         │
CASH  TRANSFER  TRANSFER
        TEKNISI  VENDOR
    ↓       ↓        ↓
[Upload  [OCR     [OCR
 Bukti]  Verify]  Verify]
    ↓       ↓        ↓
[Teknisi MATCH?  MATCH?
 Terima]  ↓        ↓
    ↓    YES  NO  YES  NO
    ↓     ↓    ↓   ↓   ↓
[SELESAI] SELESAI FLAGGED SELESAI FLAGGED
                    ↓              ↓
              [Force Approve] [Force Approve]
                    ↓              ↓
                [SELESAI]      [SELESAI]
```

---

## 📝 Audit & Reporting

### Database Audit Trail
Setiap transaksi mencatat:
- Timestamp setiap perubahan status
- User yang melakukan aksi
- Alasan override/force approve
- Selisih nominal dan penanganannya

### Laporan Bulanan
- **Laporan Kebocoran**: Analisis selisih nominal
- **Override Report**: Tracking pengecualian aturan
- **Efficiency Metrics**: Kecepatan approval & pembayaran

---

## 🚀 Deployment Notes

- **AI Model**: Perlu RTX 3090 atau setara untuk performa optimal
- **Redis**: Untuk queue processing submission massal
- **n8n**: Setup workflow automation untuk Telegram integration
- **Ollama**: Self-hosted untuk data privacy & control

---

## 📞 Support & Maintenance

Untuk informasi lebih lanjut atau technical support, hubungi:  
**PT CONNEXA (Whusnet)** - Technical Team

---

**Version**: 1.0  
**Last Updated**: 2025  
**Confidentiality**: Internal Use Only
