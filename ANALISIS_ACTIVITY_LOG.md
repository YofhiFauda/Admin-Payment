# Analisis Alur Halaman Log Aktivitas

## 📋 Requirement yang Diminta

1. **Owner**: Merekam aktivitas **semua pengguna**
2. **Atasan**: Merekam aktivitas **Atasan sendiri dan Admin**
3. **Admin**: Hanya bisa melihat rekaman **aktivitasnya sendiri**

---

## 🔍 Analisis Implementasi Saat Ini

### 1. **Controller Logic** (`ActivityLogController.php`)

```php
public function index()
{
    $user = Auth::user();
    
    // Activity Log only accessible by admin, atasan, and owner
    if (!$user->canManageStatus()) {
        abort(403, 'Unauthorized action.');
    }

    $query = ActivityLog::with(['user', 'transaction'])->latest();

    // If Admin or Atasan, only see their own logs OR logs from teknisi (e.g. Reject Payment)
    if ($user->isAdmin() || $user->isAtasan()) {
        $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhereHas('user', function ($u) {
                  $u->where('role', 'teknisi');
              });
        });
    }
    // If Owner, sees everything (Admin, Atasan, Owner)
    // No extra filter needed if they see everything as requested.

    $logs = $query->paginate(20);

    return view('activity-logs.index', compact('logs'));
}
```

---

## ❌ **MASALAH YANG DITEMUKAN**

### **Problem 1: Admin Bisa Melihat Log Teknisi**
**Implementasi Saat Ini:**
```php
if ($user->isAdmin() || $user->isAtasan()) {
    $query->where(function ($q) use ($user) {
        $q->where('user_id', $user->id)
          ->orWhereHas('user', function ($u) {
              $u->where('role', 'teknisi');
          });
    });
}
```

**Masalah:**
- Admin bisa melihat log **Teknisi** (contoh: `reject_payment`)
- **Requirement**: Admin hanya boleh melihat log **dirinya sendiri**

---

### **Problem 2: Atasan Tidak Bisa Melihat Log Admin**
**Implementasi Saat Ini:**
```php
if ($user->isAdmin() || $user->isAtasan()) {
    $query->where(function ($q) use ($user) {
        $q->where('user_id', $user->id)
          ->orWhereHas('user', function ($u) {
              $u->where('role', 'teknisi');
          });
    });
}
```

**Masalah:**
- Atasan hanya bisa melihat log **dirinya sendiri + Teknisi**
- **Requirement**: Atasan harus bisa melihat log **Atasan sendiri + Admin**

---

### **Problem 3: Owner Bisa Melihat Log Teknisi**
**Implementasi Saat Ini:**
```php
// If Owner, sees everything (Admin, Atasan, Owner)
// No extra filter needed if they see everything as requested.
```

**Masalah:**
- Owner bisa melihat **semua log** termasuk Teknisi
- **Requirement**: Owner hanya boleh melihat log **Admin, Atasan, dan Owner sendiri**

---

## ✅ **SOLUSI YANG BENAR**

### **Implementasi yang Sesuai Requirement:**

```php
public function index()
{
    $user = Auth::user();
    
    // Activity Log only accessible by admin, atasan, and owner
    if (!$user->canManageStatus()) {
        abort(403, 'Unauthorized action.');
    }

    $query = ActivityLog::with(['user', 'transaction'])->latest();

    if ($user->isAdmin()) {
        // Admin: Hanya melihat log dirinya sendiri
        $query->where('user_id', $user->id);
        
    } elseif ($user->isAtasan()) {
        // Atasan: Melihat log Atasan sendiri + Admin
        $query->whereHas('user', function ($q) {
            $q->whereIn('role', ['admin', 'atasan']);
        });
        
    } elseif ($user->isOwner()) {
        // Owner: Melihat log Admin + Atasan + Owner
        $query->whereHas('user', function ($q) {
            $q->whereIn('role', ['admin', 'atasan', 'owner']);
        });
    }

    $logs = $query->paginate(20);

    return view('activity-logs.index', compact('logs'));
}
```

---

## 📊 **Perbandingan: Sebelum vs Sesudah**

| Role    | Implementasi Saat Ini | Requirement | Status |
|---------|----------------------|-------------|--------|
| **Admin** | Admin sendiri + Teknisi | Admin sendiri saja | ❌ **SALAH** |
| **Atasan** | Atasan sendiri + Teknisi | Atasan sendiri + Admin | ❌ **SALAH** |
| **Owner** | Semua (termasuk Teknisi) | Admin + Atasan + Owner | ❌ **SALAH** |

---

## 🎯 **Kesimpulan**

### **Alur Log Aktivitas Saat Ini: TIDAK BENAR**

**3 Masalah Utama:**
1. ❌ Admin bisa melihat log Teknisi (seharusnya hanya dirinya sendiri)
2. ❌ Atasan tidak bisa melihat log Admin (seharusnya bisa)
3. ❌ Owner bisa melihat log Teknisi (seharusnya hanya Admin/Atasan/Owner)

**Root Cause:**
- Logic filtering menggunakan `orWhereHas('user', function ($u) { $u->where('role', 'teknisi'); })`
- Ini membuat Admin dan Atasan bisa melihat log Teknisi
- Atasan tidak bisa melihat log Admin karena tidak ada filter untuk role `admin`

---

## 🔧 **Rekomendasi**

1. **Ganti logic filtering** di `ActivityLogController.php` dengan implementasi yang benar (lihat bagian Solusi)
2. **Hapus filter Teknisi** karena tidak sesuai requirement
3. **Tambahkan filter role yang tepat** untuk setiap user:
   - Admin: `where('user_id', $user->id)`
   - Atasan: `whereIn('role', ['admin', 'atasan'])`
   - Owner: `whereIn('role', ['admin', 'atasan', 'owner'])`

---

## 📝 **Catatan Tambahan**

### **Siapa yang Membuat Activity Log?**
Berdasarkan pencarian di codebase, Activity Log dibuat oleh:
- **Admin, Atasan, Owner**: Saat approve/reject/edit transaksi
- **Teknisi**: Saat reject payment (`reject_payment` action)
- **System**: Saat upload invoice/payment via OCR

### **Kenapa Teknisi Tidak Perlu Dilihat?**
- Teknisi hanya **submit** transaksi, tidak melakukan approve/reject
- Log Teknisi (`reject_payment`) adalah edge case yang tidak relevan untuk monitoring approval flow
- Requirement fokus pada **aktivitas manajemen** (Admin, Atasan, Owner)

---

**Tanggal Analisis:** 8 Mei 2026  
**Status:** ❌ **Implementasi Tidak Sesuai Requirement**  
**Action Required:** Perbaiki logic filtering di `ActivityLogController.php`
