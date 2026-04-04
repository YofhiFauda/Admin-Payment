# 📋 SPESIFIKASI SISTEM PENGAJUAN
## Implementasi Complete: Dual-Version, Authorization, Detail Modal, & Role-Based Visibility

**Version:** 2.1  
**Date:** 2 April 2026  
**Status:** Ready for Implementation  

---

## 📑 Table of Contents

1. [Executive Summary](#executive-summary)
2. [Business Rules](#business-rules)
3. [Authorization Matrix](#authorization-matrix)
4. [User Flow by Role](#user-flow-by-role)
5. [UI/UX Specifications](#uiux-specifications)
6. [Technical Implementation](#technical-implementation)
7. [Testing Scenarios](#testing-scenarios)
8. [Migration Guide](#migration-guide)
9. [Performance Optimization](#performance-optimization)
10. [Mobile Considerations](#mobile-considerations)

---


### Solution Overview
Implementasi sistem pengajuan dengan 4 komponen utama:

1. **Dual-Version System** - Track original vs edited version
2. **Role-Based Authorization** - Fine-grained permission control dengan read-only access untuk Admin
3. **Detail Modal Pengajuan** - Rich information display dengan version toggle (embedded di index.blade.php)
4. **Edit Protection** - Disable edit saat status "selesai"

### Key Features
- ✅ Management (Owner/Atasan) dapat Create, Read, Update, Delete pengajuan (jika status != selesai)
- ✅ **Admin dapat View detail (read-only) DAN akses halaman Edit (read-only) untuk melihat kedua versi**
- ✅ Teknisi dapat View detail transaksi mereka
- ✅ Edit disabled otomatis saat status = "selesai" untuk SEMUA role
- ✅ Status "pending" masih bisa di-edit oleh Management
- ✅ Version tracking dengan visual indicators (🟡 outline untuk edited fields)
- ✅ Transparent revision history dengan toggle button
- ✅ **Modal Detail embedded di index.blade.php** (tidak pakai file terpisah)
- ✅ Mobile-responsive dengan performance optimization

---

## 📜 Business Rules

### BR-001: Edit Permission Based on Role & Status

| Role | Create | Read | Update (Pending) | Update (Selesai) | Delete |
|------|--------|------|------------------|------------------|--------|
| **Owner** | ✅ | ✅ | ✅ | ❌ | ✅ |
| **Atasan** | ✅ | ✅ | ✅ | ❌ | ✅ |
| **Admin** | ❌ | ✅ | ❌ (Read-only view) | ❌ | ❌ |
| **Teknisi** | ✅ (via form) | ✅ (own only) | ❌ | ❌ | ❌ |

**Catatan Penting:**
- Admin bisa **akses halaman edit** tapi dalam mode **read-only** untuk melihat perbandingan Versi Pengaju vs Versi Management
- Management (Owner/Atasan) bisa edit pengajuan selama status masih **Pending** atau **Diproses**
- Semua role **TIDAK BISA** edit jika status = **Selesai**

### BR-002: Edit Protection Based on Status

```
IF status = 'completed' THEN
    - Tombol Edit = disabled/hidden (ALL ROLES termasuk Owner & Atasan)
    - Halaman Edit = read-only mode (jika ada role yang coba akses)
    - Submit button = hidden
    - Alert message = "Pengajuan yang sudah selesai tidak dapat diedit"
END IF

IF status = 'pending' OR status = 'waiting_payment' THEN
    - Management (Owner/Atasan): Edit allowed (full access)
    - Admin: Edit button visible → navigate to read-only view page
    - Teknisi: No edit access
END IF

IF status = 'rejected' THEN
    - Management: Edit allowed (dapat mengubah kembali untuk koreksi)
    - Admin: Read-only view
    - Teknisi: No edit access
END IF
```

**Status Definitions:**
- `pending` - Pengajuan baru, belum diproses → **Management bisa Edit** ✅
- `waiting_payment` - Disetujui, menunggu proses pembayaran/pembelian → **Management bisa Edit** ✅
- `rejected` - Ditolak → **Management bisa Edit** ✅
- `completed` - **FINAL STATE** - Sudah dibayar/selesai → **SEMUA ROLE TIDAK BISA EDIT** ❌

**Catatan Status intermediate:**
- Status `approved` (Disetujui) secara teknis digunakan untuk transaksi yang menunggu persetujuan Owner (misal Rembush >= 1jt), namun untuk Pengajuan alurnya disederhanakan langsung ke `waiting_payment` setelah disetujui oleh Management yang berwenang.

### BR-003: Version Visibility Rules

**Prinsip: Transparency First**

```
Default Version Display:
- Modal Detail (any status, any role):
  IF is_edited_by_management = true THEN
      Default = "Versi Management" (current/edited version)
      Toggle available = true
  ELSE
      Default = "Versi Pengaju" (original version)
      Toggle available = false
  END IF

- Edit Pengajuan (Management only):
  Always default = "Versi Management"
  Toggle available = true (untuk compare)

- Edit Pengajuan (Admin - READ ONLY):
  Default = "Versi Management" (jika is_edited_by_management = true)
  Toggle available = true (untuk compare kedua versi)
  All form fields = DISABLED/read-only
  Submit button = HIDDEN
  Purpose: Untuk melihat perbandingan antara Versi Pengaju vs Versi Management
```

**Rationale:** 
- User melihat data "terkini" sebagai default
- Toggle selalu tersedia untuk transparency
- Konsisten across all status & roles
- **Admin bisa melihat perbandingan versi tanpa bisa melakukan perubahan**

### BR-004: Revision Tracking

```
ON first edit by Management:
    - Freeze items_snapshot (original data)
    - Set is_edited_by_management = true
    - Set edited_by = current_user_id
    - Set edited_at = now()
    - Set revision_count = 1

ON subsequent edits by Management:
    - Update items (current data)
    - Update edited_by = current_user_id
    - Update edited_at = now()
    - Increment revision_count++
    - items_snapshot UNCHANGED (immutable)
```

### BR-005: Modal Detail Location

```
Modal Detail Pengajuan Location:
- File: resources/views/transactions/index.blade.php (EMBEDDED)
- NOT using: resources/views/transactions/detail-modal.blade.php
- Reason: Tidak ingin menambah halaman baru, modal langsung di index

Implementation:
<div id="detailPengajuanModal" class="modal">
    <!-- Modal content embedded di index.blade.php -->
    <!-- Loaded dynamically via AJAX/Fetch -->
</div>
```

---

## 🔐 Authorization Matrix

### Permission Grid: Pengajuan vs Rembush

| Skenario | Teknisi | Admin | Atasan | Owner |
|----------|---------|-------|--------|-------|
| **Tombol Edit di List (Pengajuan - Pending)** | ❌ | ✅ Read-only | ✅ Full Edit | ✅ Full Edit |
| **Tombol Edit di List (Pengajuan - Selesai)** | ❌ | ❌ | ❌ | ❌ |
| **Tombol Edit di List (Rembush)** | ❌ | ✅ | ✅ | ✅ |
| **Access Edit Page (Pengajuan - Pending)** | ❌ 403 | ✅ Read-only | ✅ Full Edit | ✅ Full Edit |
| **Access Edit Page (Pengajuan - Selesai)** | ❌ 403 | ❌ 403 | ❌ 403 | ❌ 403 |
| **Submit Changes (Pengajuan - Pending)** | ❌ 403 | ❌ 403 | ✅ | ✅ |
| **Submit Changes (Pengajuan - Selesai)** | ❌ 403 | ❌ 403 | ❌ 403 | ❌ 403 |
| **View Detail Modal - Pengajuan** | ✅ | ✅ | ✅ | ✅ |
| **View Detail Modal - Rembush** | ✅ | ✅ | ✅ | ✅ |
| **See Revision Banner in Detail** | ✅ | ✅ | ✅ | ✅ |
| **Toggle Version in Detail Modal** | ✅ | ✅ | ✅ | ✅ |
| **Toggle Version in Edit Page (Management)** | — | — | ✅ | ✅ |
| **Toggle Version in Edit Page (Admin - Read-only)** | — | ✅ | — | — |

### Middleware Protection

**File:** `routes/web.php` atau `Middleware/CheckPengajuanEditPermission.php`

```php
// Route Protection
Route::middleware(['auth'])->group(function () {
    // Management dan Admin bisa akses halaman edit
    // Tapi Admin dalam mode read-only
    Route::get('/transactions/{id}/edit', [TransactionController::class, 'edit'])
        ->name('transactions.edit');
    
    // Hanya Management yang bisa submit perubahan
    Route::put('/transactions/{id}', [TransactionController::class, 'update'])
        ->middleware('can:edit-pengajuan')
        ->name('transactions.update');
});

// Gate Definition (in AuthServiceProvider)
Gate::define('edit-pengajuan', function (User $user, Transaction $transaction) {
    // Only Owner & Atasan can SUBMIT edits for Pengajuan
    if ($transaction->type !== 'pengajuan') {
        return true; // Rembush follows different rules
    }
    
    // Check role - only Management can submit changes
    if (!in_array($user->role, ['owner', 'atasan'])) {
        return false;
    }
    
    // Check status - NO ONE can edit "selesai" transactions
    if ($transaction->status === 'selesai') {
        return false;
    }
    
    return true;
});

// Gate untuk akses halaman edit (termasuk Admin read-only)
Gate::define('view-pengajuan-edit-page', function (User $user, Transaction $transaction) {
    // Admin, Atasan, Owner bisa akses halaman edit
    if (in_array($user->role, ['admin', 'owner', 'atasan'])) {
        return true;
    }
    
    return false;
});
```

### Controller-Level Authorization

**File:** `app/Http/Controllers/TransactionController.php`

```php
public function edit($id)
{
    $transaction = Transaction::with(['items', 'editor', 'submittedBy', 'branches'])->findOrFail($id);
    
    // Check if user can view edit page
    $this->authorize('view-pengajuan-edit-page', $transaction);
    
    // Determine if this is read-only mode
    $isReadOnly = false;
    
    if ($transaction->type === 'pengajuan') {
        // Status selesai = read-only untuk semua
        if ($transaction->status === 'selesai') {
            $isReadOnly = true;
        }
        
        // Admin = always read-only untuk pengajuan
        if (auth()->user()->role === 'admin') {
            $isReadOnly = true;
        }
        
        // Management tidak read-only (jika status != selesai)
        if (in_array(auth()->user()->role, ['owner', 'atasan']) && $transaction->status !== 'selesai') {
            $isReadOnly = false;
        }
    }
    
    return view('transactions.edit', compact('transaction', 'isReadOnly'));
}

public function update(Request $request, $id)
{
    $transaction = Transaction::findOrFail($id);
    
    // Double-check authorization
    if ($transaction->type === 'pengajuan') {
        // Check role
        if (!in_array(auth()->user()->role, ['owner', 'atasan'])) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit Pengajuan.');
        }
        
        // Check status - selesai tidak bisa diedit oleh siapapun
        if ($transaction->status === 'selesai') {
            abort(403, 'Pengajuan yang sudah selesai tidak dapat diedit.');
        }
    }
    
    // Process update...
    // (rest of update logic)
}
```

---

## 👥 User Flow by Role

### 1️⃣ **Teknisi Flow**

```
Start → Login
  ↓
List Transaksi (own transactions only)
  ↓
[Tombol Detail] ← Click
  ↓
Modal Detail Pengajuan (embedded di index.blade.php)
  ├─ IF is_edited_by_management = true:
  │   ├─ 🔔 Banner: "Pengajuan Telah Direvisi oleh Management"
  │   ├─ Default: Versi Management
  │   ├─ Toggle Button Available
  │   └─ Switch to Versi Pengaju → See 🟡 indicators
  │
  └─ IF is_edited_by_management = false:
      ├─ Show original data (Versi Pengaju)
      └─ No toggle button
  ↓
[Tombol Tutup]
  ↓
End
```

**Key Points:**
- ❌ **NO** tombol Edit di list
- ❌ **NO** access ke halaman edit (403 if try direct URL)
- ✅ **CAN** see detail modal dengan version comparison
- ✅ **CAN** see revision history & edited fields indicators
- ✅ Modal Detail embedded di `index.blade.php`, tidak perlu halaman baru

---

### 2️⃣ **Admin Flow**

```
Start → Login
  ↓
List Transaksi (all transactions)
  ↓
Option A: [Tombol Detail] ← Click (Pengajuan)
  ↓
Modal Detail Pengajuan (embedded di index.blade.php)
  ├─ View both versions (jika ada revisi)
  ├─ See revision banner
  └─ Toggle available
  ↓
[Tombol Tutup]

Option B: [Tombol Edit] ← Click (Pengajuan dengan status != selesai)
  ↓
Halaman Edit Pengajuan (READ-ONLY MODE)
  ├─ All form fields = DISABLED (read-only)
  ├─ Default: Versi Management (jika ada)
  ├─ Toggle Button Available (switch ke Versi Pengaju)
  ├─ See 🟡 indicators on edited fields
  ├─ Purpose: Untuk melihat perbandingan kedua versi
  └─ Submit button = HIDDEN (tidak bisa save)
  ↓
[Tombol Kembali]
  ↓
End

Option C: Status = Selesai
  ↓
Tombol Edit = Hidden/Disabled
(Admin tidak bisa akses edit page untuk pengajuan selesai)
```

**Key Points:**
- ✅ **CAN** see tombol Edit untuk Pengajuan (jika status != selesai)
- ✅ **CAN** access halaman edit Pengajuan dalam mode READ-ONLY
- ✅ **CAN** toggle antara Versi Pengaju dan Versi Management untuk melihat perbandingan
- ❌ **CANNOT** submit any changes (form disabled, submit button hidden)
- ❌ **NO** tombol Edit jika status = selesai
- ✅ **CAN** see detail modal (same as Teknisi)
- ✅ **CAN** edit Rembush (different transaction type dengan full permission)

**Admin Read-Only Purpose:**
- Admin bisa melihat apa yang Management ubah dari versi asli Teknisi
- Berguna untuk audit, review, atau keperluan administratif
- Tidak bisa melakukan perubahan, hanya view comparison

---

### 3️⃣ **Management (Owner/Atasan) Flow**

```
Start → Login
  ↓
List Transaksi (all transactions)
  ↓
IF status = 'selesai' THEN
    Tombol Edit = Hidden/Disabled
    (Tidak bisa edit pengajuan yang sudah selesai)
ELSE
    [Tombol Edit] ← Click (Pengajuan with status != selesai)
    ↓
    Halaman Edit Pengajuan (FULL EDIT MODE)
      ├─ Default: Versi Management (if edited before)
      ├─ Toggle Button Available
      ├─ All fields ENABLED (dapat diedit)
      ├─ [Toggle] → Switch to Versi Pengaju (compare mode, read-only view)
      ├─ Edit items, change values
      ├─ See 🟡 indicators on edited fields
      └─ [Tombol Simpan] ← Click
      ↓
    Backend Processing:
      ├─ Check status != 'selesai' (double validation)
      ├─ IF first edit:
      │   ├─ Freeze items_snapshot
      │   └─ Set is_edited_by_management = true
      │
      └─ Update items (current version)
          └─ Increment revision_count
      ↓
    Redirect to List or Detail
      ↓
    Success Message: "Pengajuan berhasil diperbarui"
END IF
  ↓
End
```

**Key Points:**
- ✅ **CAN** see tombol Edit di list (jika status != selesai)
- ✅ **CAN** access edit page dengan full permission
- ✅ **CAN** submit changes (if status != selesai)
- ✅ **CAN** toggle between versions untuk comparison
- ❌ **CANNOT** edit jika status = 'selesai' (tombol hidden/disabled)
- ✅ **CAN** edit untuk status: pending, diproses, disetujui, ditolak

**Edit Permission by Status:**
```
Status Pending    → Management ✅ dapat edit
Status Diproses   → Management ✅ dapat edit
Status Disetujui  → Management ✅ dapat edit
Status Ditolak    → Management ✅ dapat edit
Status Selesai    → Management ❌ TIDAK dapat edit
```

---

## 📚 Summary of Changes from v2.0 to v2.1

### Key Updates:
1. ✅ **Status Pending Edit**: Explicitly stated Management can edit pengajuan with status "pending"
2. ✅ **Admin Read-Only Access**: Admin sekarang bisa akses halaman Edit dalam mode read-only untuk melihat perbandingan versi
3. ✅ **Status Selesai Protection**: Diperjelas bahwa SEMUA role (termasuk Owner & Atasan) tidak bisa edit jika status = "selesai"
4. ✅ **Modal Location**: Dijelaskan bahwa Modal Detail embedded di `resources/views/transactions/index.blade.php`, tidak menggunakan file terpisah
5. ✅ **Business Rules Update**: BR-001 updated dengan kolom untuk status Pending vs Selesai
6. ✅ **Authorization Matrix**: Updated dengan role Admin untuk read-only view
7. ✅ **User Flow**: Admin flow updated dengan opsi akses halaman edit (read-only)
8. ✅ **Testing Scenarios**: Added test cases untuk admin read-only dan status selesai protection

---

## ✅ Pre-Implementation Checklist

Before starting implementation, ensure:

- [ ] All stakeholders reviewed and approved this spec v2.1
- [ ] Database backup created
- [ ] Development environment ready
- [ ] Testing environment available
- [ ] Rollback plan documented
- [ ] Team members assigned tasks
- [ ] Timeline communicated to stakeholders
- [ ] Confirmed modal akan embedded di index.blade.php
- [ ] Confirmed admin perlu read-only access ke edit page

---

**Document Version:** 2.1  
**Last Updated:** 2 April 2026  
**Author:** Development Team  
**Status:** ✅ Ready for Implementation  
**Changes:** Added Admin read-only access, Status Pending edit clarification, Modal location specification, Status Selesai protection for all roles
