# 🎨 Price Index System - Visual Guide

## 🎯 Sistem Overview

```
╔═══════════════════════════════════════════════════════════════════╗
║                    PRICE INDEX DUAL-MODE SYSTEM                   ║
╚═══════════════════════════════════════════════════════════════════╝

┌─────────────────────────────────────────────────────────────────┐
│                      TRANSAKSI APPROVED                         │
│                   Harga: Rp 52,000                              │
│                   Range: [Rp 45k - Rp 60k]                      │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
              ┌──────────────────────┐
              │  Harga dalam Range?  │
              └──────┬───────────────┘
                     │
            ┌────────┴────────┐
            │                 │
         ✅ YA             ❌ TIDAK
            │                 │
            ▼                 ▼
    ┌───────────────┐   ┌──────────┐
    │ UPDATE AUTO   │   │   SKIP   │
    │  avg_price    │   │          │
    └───────┬───────┘   └──────────┘
            │
            ▼
    ┌───────────────────────────────────────┐
    │  INCREMENTAL MOVING AVERAGE           │
    │                                       │
    │  new_avg = ((old_avg × n) + price)   │
    │            ─────────────────────      │
    │                 (n + 1)               │
    │                                       │
    │  Example:                             │
    │  ((50,000 × 10) + 52,000) / 11        │
    │  = 50,182                             │
    └───────────────┬───────────────────────┘
                    │
                    ▼
    ┌───────────────────────────────────────┐
    │         DATABASE UPDATE               │
    │                                       │
    │  avg_price = 50,182 ✅               │
    │  total_transactions = 11 ✅          │
    │  avg_price_manual = (tidak berubah)  │
    └───────────────┬───────────────────────┘
                    │
                    ▼
    ┌───────────────────────────────────────┐
    │      CEK PRIORITAS PENGGUNAAN         │
    │                                       │
    │  avg_price_manual IS NOT NULL?       │
    └───────┬───────────────────────────────┘
            │
    ┌───────┴────────┐
    │                │
  ✅ ADA         ❌ NULL
    │                │
    ▼                ▼
┌─────────┐    ┌──────────┐
│ MANUAL  │    │   AUTO   │
│ 55,000  │    │  50,182  │
└────┬────┘    └─────┬────┘
     │               │
     └───────┬───────┘
             ▼
    ┌────────────────┐
    │ EFFECTIVE AVG  │
    │   Rp 55,000    │
    └────────────────┘
```

---

## 📊 Data Flow Diagram

```
┌──────────────────────────────────────────────────────────────────┐
│                         DATA LAYERS                              │
└──────────────────────────────────────────────────────────────────┘

Layer 1: TRANSAKSI (Source Data)
┌─────────────────────────────────────────────────────────────────┐
│ Transaction #1: Rp 48,000  ✅ Approved                          │
│ Transaction #2: Rp 51,000  ✅ Approved                          │
│ Transaction #3: Rp 49,500  ✅ Approved                          │
│ Transaction #4: Rp 52,000  ✅ Approved                          │
│ Transaction #5: Rp 50,200  ✅ Approved                          │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
Layer 2: PRICE INDEX (Calculated)
┌─────────────────────────────────────────────────────────────────┐
│ min_price        = Rp 45,000  (Manual/System)                   │
│ max_price        = Rp 60,000  (Manual/System)                   │
│ avg_price        = Rp 50,140  (Auto - dari 5 transaksi)        │
│ avg_price_manual = NULL        (Belum diset)                    │
│ total_transactions = 5                                          │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
Layer 3: EFFECTIVE VALUE (Used by System)
┌─────────────────────────────────────────────────────────────────┐
│ Effective AVG = Rp 50,140  (Karena manual NULL, gunakan auto)  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
Layer 4: USER INTERFACE
┌─────────────────────────────────────────────────────────────────┐
│ 📊 Price Index Dashboard                                        │
│                                                                  │
│ Item: Kabel NYM 3x2.5                                           │
│ ├─ Min:  Rp 45,000                                              │
│ ├─ Max:  Rp 60,000                                              │
│ ├─ AVG:  Rp 50,140  [Auto] 🔵                                   │
│ └─ Transaksi: 5                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔄 State Transitions

```
┌─────────────────────────────────────────────────────────────────┐
│                    STATE MACHINE DIAGRAM                         │
└─────────────────────────────────────────────────────────────────┘

    ┌──────────────┐
    │  COLD START  │  (Item baru, belum ada data)
    │  avg = NULL  │
    └──────┬───────┘
           │
           │ Transaksi #1 Approved
           ▼
    ┌──────────────┐
    │  AUTO MODE   │  avg_price = harga transaksi #1
    │  Manual NULL │  avg_price_manual = NULL
    └──────┬───────┘
           │
           ├─────────────────────────────────────────┐
           │                                         │
           │ Transaksi baru (dalam range)           │ Owner set manual
           ▼                                         ▼
    ┌──────────────┐                         ┌──────────────┐
    │  AUTO MODE   │                         │ MANUAL MODE  │
    │  (Updated)   │                         │ (Override)   │
    │              │                         │              │
    │ avg_price ↑  │                         │ avg_manual ✓ │
    │ manual NULL  │                         │ avg_price ↑  │
    └──────┬───────┘                         └──────┬───────┘
           │                                         │
           │                                         │
           │                                         │ Owner reset
           │                                         ▼
           │                                  ┌──────────────┐
           │                                  │  AUTO MODE   │
           └──────────────────────────────────│  (Restored)  │
                                              │              │
                                              │ manual NULL  │
                                              └──────────────┘

Legend:
  ↑  = Update otomatis dari transaksi
  ✓  = Set manual oleh Owner
  NULL = Tidak ada nilai manual
```

---

## 🎭 Mode Comparison

```
╔═══════════════════════════════════════════════════════════════════╗
║                      AUTO MODE vs MANUAL MODE                     ║
╚═══════════════════════════════════════════════════════════════════╝

┌─────────────────────────────────┬─────────────────────────────────┐
│          AUTO MODE 🔵           │        MANUAL MODE 🟣           │
├─────────────────────────────────┼─────────────────────────────────┤
│                                 │                                 │
│  avg_price_manual = NULL        │  avg_price_manual = 55,000      │
│                                 │                                 │
│  ┌───────────────────────────┐  │  ┌───────────────────────────┐  │
│  │ Transaksi Baru Approved   │  │  │ Transaksi Baru Approved   │  │
│  │ Harga: Rp 52,000          │  │  │ Harga: Rp 52,000          │  │
│  └───────────┬───────────────┘  │  └───────────┬───────────────┘  │
│              │                   │              │                   │
│              ▼                   │              ▼                   │
│  ┌───────────────────────────┐  │  ┌───────────────────────────┐  │
│  │ avg_price UPDATE ✅       │  │  │ avg_price UPDATE ✅       │  │
│  │ 50,000 → 50,182           │  │  │ 50,000 → 50,182           │  │
│  └───────────┬───────────────┘  │  └───────────┬───────────────┘  │
│              │                   │              │                   │
│              ▼                   │              ▼                   │
│  ┌───────────────────────────┐  │  ┌───────────────────────────┐  │
│  │ Effective AVG             │  │  │ Effective AVG             │  │
│  │ = avg_price               │  │  │ = avg_price_manual        │  │
│  │ = Rp 50,182 ✅           │  │  │ = Rp 55,000 ✅           │  │
│  └───────────────────────────┘  │  └───────────────────────────┘  │
│                                 │                                 │
│  💡 Mengikuti harga pasar       │  💡 Harga tetap (kontrak)       │
│                                 │                                 │
└─────────────────────────────────┴─────────────────────────────────┘
```

---

## 📈 Timeline Example

```
┌─────────────────────────────────────────────────────────────────┐
│                    TIMELINE PERUBAHAN HARGA                      │
└─────────────────────────────────────────────────────────────────┘

Hari 1: Item Baru
├─ avg_price: Rp 50,000
├─ avg_price_manual: NULL
└─ Effective: Rp 50,000 🔵

Hari 5: Transaksi #2-5 Approved
├─ avg_price: Rp 50,140 (update otomatis)
├─ avg_price_manual: NULL
└─ Effective: Rp 50,140 🔵

Hari 10: Owner Set Manual (Kontrak Supplier)
├─ avg_price: Rp 50,140 (tetap)
├─ avg_price_manual: Rp 55,000 ✅
└─ Effective: Rp 55,000 🟣

Hari 15: Transaksi #6-10 Approved
├─ avg_price: Rp 50,265 (update otomatis di background)
├─ avg_price_manual: Rp 55,000 (tidak berubah)
└─ Effective: Rp 55,000 🟣

Hari 20: Owner Reset ke Auto
├─ avg_price: Rp 50,265 (tetap)
├─ avg_price_manual: NULL ✅
└─ Effective: Rp 50,265 🔵

Hari 25: Transaksi #11-15 Approved
├─ avg_price: Rp 50,320 (update otomatis)
├─ avg_price_manual: NULL
└─ Effective: Rp 50,320 🔵
```

---

## 🎯 Decision Tree

```
                    ┌─────────────────────┐
                    │  Tampilkan Harga    │
                    │  AVG ke User?       │
                    └──────────┬──────────┘
                               │
                               ▼
                    ┌─────────────────────┐
                    │ Load PriceIndex     │
                    │ dari Database       │
                    └──────────┬──────────┘
                               │
                               ▼
                    ┌─────────────────────┐
                    │ avg_price_manual    │
                    │ IS NOT NULL?        │
                    └──────┬──────────────┘
                           │
                  ┌────────┴────────┐
                  │                 │
               ✅ YA             ❌ NO
                  │                 │
                  ▼                 ▼
        ┌──────────────────┐  ┌──────────────────┐
        │ Return Manual    │  │ Return Auto      │
        │ avg_price_manual │  │ avg_price        │
        └──────────────────┘  └──────────────────┘
                  │                 │
                  └────────┬────────┘
                           │
                           ▼
                ┌──────────────────────┐
                │  Effective AVG       │
                │  (Yang Ditampilkan)  │
                └──────────────────────┘
                           │
                           ▼
                ┌──────────────────────┐
                │  Format ke Rupiah    │
                │  "Rp 55.000"         │
                └──────────────────────┘
```

---

## 🔐 Access Control Matrix

```
╔═══════════════════════════════════════════════════════════════════╗
║                    ROLE-BASED ACCESS CONTROL                      ║
╚═══════════════════════════════════════════════════════════════════╝

┌──────────────────┬──────────┬───────┬─────────┬─────────┐
│ Action           │ Teknisi  │ Admin │ Atasan  │  Owner  │
├──────────────────┼──────────┼───────┼─────────┼─────────┤
│ View avg_price   │    ✅    │  ✅   │   ✅    │   ✅    │
│ View avg_manual  │    ❌    │  ❌   │   ✅    │   ✅    │
│ Set avg_manual   │    ❌    │  ❌   │   ❌    │   ✅    │
│ Reset to Auto    │    ❌    │  ❌   │   ❌    │   ✅    │
│ View Effective   │    ✅    │  ✅   │   ✅    │   ✅    │
└──────────────────┴──────────┴───────┴─────────┴─────────┘

Legend:
  ✅ = Allowed
  ❌ = Denied
```

---

## 📱 UI Mockup

```
┌─────────────────────────────────────────────────────────────────┐
│  📊 Price Index Dashboard                                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Item: Kabel NYM 3x2.5                        [Edit] [Delete]   │
│  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━  │
│                                                                  │
│  📈 Harga Referensi                                             │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  Min:  Rp 45,000                                           │ │
│  │  Max:  Rp 60,000                                           │ │
│  │  AVG:  Rp 50,265  [Auto] 🔵                                │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
│  💡 Harga AVG Manual (Override)                                 │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  [_________________] Rp                                    │ │
│  │                                                             │ │
│  │  Alasan: [_______________________________________]         │ │
│  │                                                             │ │
│  │  [Set Manual]  [Reset ke Auto]                             │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
│  📊 Statistik                                                    │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  Total Transaksi: 12                                       │ │
│  │  Last Update: 4 Mei 2026 14:30                             │ │
│  │  Status: Auto Mode 🔵                                      │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  SETELAH SET MANUAL                                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  📈 Harga Referensi                                             │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  Min:  Rp 45,000                                           │ │
│  │  Max:  Rp 60,000                                           │ │
│  │  AVG:  Rp 55,000  [Manual] 🟣                              │ │
│  │                                                             │ │
│  │  ℹ️ Harga Auto (Background): Rp 50,265                     │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
│  💡 Harga AVG Manual (Override)                                 │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  [____55,000_______] Rp  ✅                                │ │
│  │                                                             │ │
│  │  Alasan: Kontrak supplier 6 bulan                          │ │
│  │                                                             │ │
│  │  [Update Manual]  [Reset ke Auto]                          │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
│  📊 Statistik                                                    │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  Total Transaksi: 12                                       │ │
│  │  Last Update: 4 Mei 2026 14:35                             │ │
│  │  Status: Manual Mode 🟣                                    │ │
│  │  Set By: Owner (John Doe)                                  │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🎓 Key Takeaways

```
┌─────────────────────────────────────────────────────────────────┐
│                      POIN PENTING                                │
└─────────────────────────────────────────────────────────────────┘

1️⃣  DUA NILAI, SATU TUJUAN
    ├─ avg_price: Tracking harga pasar real-time
    └─ avg_price_manual: Override untuk kebutuhan bisnis

2️⃣  PRIORITAS JELAS
    ├─ Manual ada? → Gunakan manual
    └─ Manual NULL? → Gunakan auto

3️⃣  INDEPENDEN TAPI SINKRON
    ├─ avg_price SELALU update dari transaksi
    └─ avg_price_manual HANYA update manual

4️⃣  TRANSPARANSI PENUH
    ├─ Kedua nilai tersimpan di database
    ├─ Audit trail lengkap (who, when, why)
    └─ Bisa rollback kapan saja

5️⃣  FLEKSIBILITAS MAKSIMAL
    ├─ Owner bisa override saat kontrak/negosiasi
    ├─ Sistem tetap track harga pasar
    └─ Reset ke auto dengan satu klik
```

---

**Created:** 4 Mei 2026  
**Version:** 1.0  
**For:** WHUSNET Admin Payment System
