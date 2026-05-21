# Fix: Duplikasi Cabang pada Edit Rembush

## 🐛 Masalah

Ketika mengedit transaksi Rembush dan memilih cabang (misalnya Jetis), cabang tersebut bisa muncul duplikat pada halaman edit. Setiap kali user mengklik cabang yang sama, cabang tersebut bertambah lagi di daftar alokasi.

### Contoh Kasus:
1. User membuka edit Rembush yang sudah memiliki cabang Jetis
2. User mengklik cabang Jetis (untuk toggle/deselect)
3. **BUG**: Cabang Jetis malah bertambah (duplikat) di daftar alokasi
4. Saat submit, backend menerima data cabang yang duplikat

## 🔍 Root Cause Analysis (Multi-Layer Problem)

### **Layer 1: Database - Tidak Ada Unique Constraint** ⚠️ **PALING KRITIS**

File: `database/migrations/2026_02_14_000003_create_transaction_branches_table.php`

```php
Schema::create('transaction_branches', function (Blueprint $table) {
    $table->id();
    $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
    $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
    $table->decimal('allocation_percent', 8, 2)->default(0);
    $table->bigInteger('allocation_amount')->default(0);
    $table->timestamps();
    // ❌ TIDAK ADA: $table->unique(['transaction_id', 'branch_id']);
});
```

**Masalah:**
- Database **TIDAK MENCEGAH** duplikasi kombinasi `transaction_id` + `branch_id`
- Jika ada bug di aplikasi, duplikat bisa masuk ke database
- Ini adalah **single point of failure** - jika layer lain gagal, database tidak melindungi

### **Layer 2: Backend Controller - Pakai attach() Loop**

File: `app/Http/Controllers/TransactionController.php`

```php
// Sync branches
$transaction->branches()->detach();  // ✅ Ada detach()
if ($request->branches && count($request->branches) > 0) {
    // ... validation ...
    
    foreach ($branchAttachData as $branch) {
        $transaction->branches()->attach($branch['id'], [  // ⚠️ Pakai attach() loop
            'allocation_percent' => $branch['allocation_percent'],
            'allocation_amount'  => $branch['allocation_amount'],
        ]);
    }
}
```

**Masalah:**
- Controller **SUDAH** pakai `detach()` sebelum `attach()` - ini **AMAN**
- **TAPI** jika frontend mengirim data duplikat dalam `$request->branches`, semua data (termasuk duplikat) akan di-`attach()`
- Lebih baik pakai `sync()` yang lebih atomic dan aman

### **Layer 3: Validation - Tidak Ada 'distinct' Rule**

**Kode Lama:**
```php
'branches.*.branch_id' => 'required_with:branches|exists:branches,id',
```

**Masalah:**
- Tidak ada validasi `'distinct'` untuk memastikan `branch_id` unik dalam array
- Laravel bisa menerima request dengan duplikat `branch_id`

### **Layer 4: Frontend JavaScript - Race Condition**

File: `resources/views/transactions/edit-rembush.blade.php`

**Kode Lama:**
```javascript
branchPills.forEach(pill => {
    pill.addEventListener('click', function () {
        const id = String(this.dataset.id);
        const name = this.dataset.name;
        const idx = selectedBranches.findIndex(b => String(b.id) === id);

        if (idx > -1) {
            // Deselect
            selectedBranches.splice(idx, 1);
        } else {
            // Select - ensure no duplicates before adding
            if (!selectedBranches.some(b => String(b.id) === id)) {
                selectedBranches.push({ id, name, value: 0, percent: 0 });
            }
        }
    });
});
```

**Masalah:**
- Logika menggunakan `findIndex()` untuk cek apakah cabang sudah ada
- Jika ada **timing issue** atau **state tidak sinkron**, `findIndex` bisa return `-1` meskipun cabang sebenarnya sudah ada
- Ketika `idx === -1`, kode masuk ke blok `else` dan **menambahkan cabang lagi**
- User yang mengklik cepat berkali-kali bisa memicu kondisi ini

## ✅ Solusi yang Diterapkan (Defense in Depth - 5 Layers)

### **Layer 1: Database Unique Constraint** 🛡️ **PALING PENTING**

File: `database/migrations/2026_05_20_000001_add_unique_constraint_to_transaction_branches.php`

```php
public function up(): void
{
    // ✅ STEP 1: Clean up existing duplicates
    DB::statement("
        DELETE t1 FROM transaction_branches t1
        INNER JOIN transaction_branches t2 
        WHERE t1.id < t2.id 
        AND t1.transaction_id = t2.transaction_id 
        AND t1.branch_id = t2.branch_id
    ");

    // ✅ STEP 2: Add unique constraint
    Schema::table('transaction_branches', function (Blueprint $table) {
        $table->unique(['transaction_id', 'branch_id'], 'unique_transaction_branch');
    });
}
```

**Keuntungan:**
- ✅ **Permanent protection** di level database
- ✅ Mencegah duplikasi bahkan jika ada bug di aplikasi
- ✅ Membersihkan duplikat existing sebelum menambahkan constraint

**Cara Jalankan:**
```bash
php artisan migrate
```

### **Layer 2: Validation Rules - Tambahkan 'distinct'**

File: `app/Http/Controllers/TransactionController.php` & `RembushController.php`

**Kode Baru:**
```php
'branches.*.branch_id' => ['required_with:branches', 'exists:branches,id', 'distinct'],
```

**Keuntungan:**
- ✅ Laravel akan reject request jika ada duplikat `branch_id` dalam array
- ✅ Error message otomatis: "The branches.0.branch_id field has a duplicate value."

### **Layer 3: Manual Validation - Check Duplicates**

File: `app/Http/Controllers/TransactionController.php`

**Kode yang Sudah Ada:**
```php
// ✅ FIX: Check for duplicate branch IDs to prevent manipulation
$branchIds = collect($request->branches)->pluck('branch_id');
if ($branchIds->count() !== $branchIds->unique()->count()) {
    DB::rollBack();
    return back()->withErrors([
        'branches' => 'Cabang tidak boleh duplikat. Silakan refresh halaman dan coba lagi.'
    ])->withInput();
}
```

**Keuntungan:**
- ✅ Custom error message yang lebih user-friendly
- ✅ Backup validation jika `'distinct'` rule gagal

### **Layer 4: Frontend Event Handler Fix**

File: `resources/views/transactions/edit-rembush.blade.php`

**Kode Baru:**
```javascript
branchPills.forEach(pill => {
    pill.addEventListener('click', function () {
        const id   = String(this.dataset.id);
        const name = this.dataset.name;
        
        // ✅ FIX: Check visual state (pill classes) as source of truth
        const isCurrentlyActive = this.classList.contains('bg-emerald-500');
        
        if (isCurrentlyActive) {
            // Deselect - remove from array using filter
            selectedBranches = selectedBranches.filter(b => String(b.id) !== id);
            // Update visual...
        } else {
            // Select - add to array only if not already present
            const alreadyExists = selectedBranches.some(b => String(b.id) === id);
            
            if (!alreadyExists) {
                selectedBranches.push({ id, name, value: 0, percent: 0 });
            } else {
                console.warn('[edit-rembush] Branch already in array, skipping:', id, name);
            }
            // Update visual...
        }
    });
});
```

**Keuntungan:**
- ✅ Menggunakan **visual state** (CSS classes) sebagai source of truth
- ✅ Menggunakan `filter()` untuk deselect (lebih reliable)
- ✅ Double-check dengan `some()` sebelum menambahkan

### **Layer 5: Frontend Render Functions - Deduplication**

File: `resources/views/transactions/edit-rembush.blade.php`

**Kode Baru di `renderDistribution()` dan `updateHiddenInputs()`:**
```javascript
// ✅ FIX: Remove duplicates before rendering
const uniqueBranches = [];
const seenIds = new Set();

selectedBranches.forEach(branch => {
    const branchId = String(branch.id);
    if (!seenIds.has(branchId)) {
        seenIds.add(branchId);
        uniqueBranches.push(branch);
    } else {
        console.warn('[edit-rembush] Duplicate detected, removing:', branchId, branch.name);
    }
});

// Update selectedBranches if duplicates were found
if (uniqueBranches.length !== selectedBranches.length) {
    selectedBranches = uniqueBranches;
}
```

**Keuntungan:**
- ✅ Safety net yang membersihkan duplikat sebelum render
- ✅ Menggunakan `Set` untuk tracking (O(1) lookup)

## 🧪 Testing & Verification

### **1. Cek Duplikat Existing di Database**

```bash
php scripts/check-duplicate-branches.php
```

Output jika ada duplikat:
```
❌ Found 3 duplicate entries:

Transaction ID  Branch ID       Count      Duplicate IDs
================================================================================
123             5               2          456,789
124             3               3          460,461,462
================================================================================

📋 Transaction Details:

Transaction #123 (INV-2026-001):
  - Type: rembush
  - Amount: Rp 500,000
  - Duplicate Branch: Jetis (ID: 5)
  - Duplicate Count: 2
  - Duplicate IDs: 456,789
```

### **2. Jalankan Migration untuk Fix**

```bash
php artisan migrate
```

Migration akan:
1. ✅ Menghapus duplikat existing (keep yang terbaru)
2. ✅ Menambahkan unique constraint

### **3. Test Manual di Browser**

**Test Case 1: Klik Cepat Berkali-kali**
1. Buka edit Rembush
2. Klik cabang Jetis berkali-kali dengan cepat
3. **Expected**: Cabang hanya toggle on/off, tidak ada duplikat

**Test Case 2: Edit Existing Rembush**
1. Buka edit Rembush yang sudah memiliki cabang Jetis
2. Klik cabang Jetis (untuk deselect)
3. **Expected**: Cabang Jetis hilang dari daftar, tidak duplikat

**Test Case 3: Submit dengan Duplikat (Manual Manipulation)**
1. Buka browser console
2. Manipulasi form untuk mengirim duplikat
3. **Expected**: Backend reject dengan error "Cabang tidak boleh duplikat"

**Test Case 4: Database Protection**
1. Coba insert duplikat langsung ke database via SQL
2. **Expected**: Database reject dengan error "Duplicate entry"

## 🎯 Hasil

### Sebelum Fix:
- ❌ User bisa membuat duplikat cabang dengan mengklik berkali-kali
- ❌ Duplikat bisa masuk ke database
- ❌ UI tidak konsisten dengan state
- ❌ Database tidak melindungi dari duplikasi

### Setelah Fix:
- ✅ **Database unique constraint** mencegah duplikasi permanent
- ✅ **Validation rules** mencegah duplikat di request
- ✅ **Frontend deduplication** di multiple layers
- ✅ **Visual state** sebagai source of truth mencegah race condition
- ✅ **Logging** untuk debugging
- ✅ **Script checker** untuk monitoring

## 📊 Defense in Depth Summary

| Layer | Protection | Status | Impact |
|-------|-----------|--------|--------|
| 1. Database Constraint | `unique(['transaction_id', 'branch_id'])` | ✅ Added | **CRITICAL** - Permanent protection |
| 2. Validation Rule | `'distinct'` in validation | ✅ Added | HIGH - Reject invalid requests |
| 3. Manual Validation | Custom duplicate check | ✅ Existing | MEDIUM - User-friendly errors |
| 4. Frontend Event Handler | Visual state as source of truth | ✅ Fixed | MEDIUM - Prevent user errors |
| 5. Frontend Render | Deduplication before render | ✅ Added | LOW - Safety net |

## 🔗 Related Files

- ✅ `database/migrations/2026_05_20_000001_add_unique_constraint_to_transaction_branches.php` - Database constraint
- ✅ `scripts/check-duplicate-branches.php` - Duplicate checker script
- ✅ `resources/views/transactions/edit-rembush.blade.php` - Frontend fix
- ✅ `app/Http/Controllers/TransactionController.php` - Validation fix
- ✅ `app/Http/Controllers/RembushController.php` - Validation fix

## 📅 Date

May 20, 2026

---

## 🙏 Credits

Analisa awal oleh: **User** (100% akurat!)
- ✅ Identified missing unique constraint
- ✅ Identified attach() loop issue
- ✅ Suggested 'distinct' validation
- ✅ Suggested database cleanup query

Implementation by: **Kiro AI**
- ✅ Created migration with cleanup
- ✅ Added validation rules
- ✅ Fixed frontend race conditions
- ✅ Created monitoring script
- ✅ Comprehensive documentation


### 1. **Race Condition pada Event Handler**

File: `resources/views/transactions/edit-rembush.blade.php`

**Kode Lama:**
```javascript
branchPills.forEach(pill => {
    pill.addEventListener('click', function () {
        const id = String(this.dataset.id);
        const name = this.dataset.name;
        const idx = selectedBranches.findIndex(b => String(b.id) === id);

        if (idx > -1) {
            // Deselect
            selectedBranches.splice(idx, 1);
            // Update visual...
        } else {
            // Select - ensure no duplicates before adding
            if (!selectedBranches.some(b => String(b.id) === id)) {
                selectedBranches.push({ id, name, value: 0, percent: 0 });
            }
            // Update visual...
        }
    });
});
```

**Masalah:**
- Logika menggunakan `findIndex()` untuk cek apakah cabang sudah ada
- Jika ada **timing issue** atau **state tidak sinkron**, `findIndex` bisa return `-1` meskipun cabang sebenarnya sudah ada
- Ketika `idx === -1`, kode masuk ke blok `else` dan **menambahkan cabang lagi**
- User yang mengklik cepat berkali-kali bisa memicu kondisi ini

### 2. **Tidak Ada Validasi Duplikat di Render Functions**

Fungsi `renderDistribution()` dan `updateHiddenInputs()` tidak memiliki mekanisme untuk mendeteksi dan menghapus duplikat, sehingga jika duplikat masuk ke array `selectedBranches`, duplikat tersebut akan di-render dan dikirim ke backend.

### 3. **Inisialisasi Data Tidak Membersihkan Duplikat**

Saat load data dari backend (`window._initialBranches`), tidak ada validasi untuk memastikan data yang diterima tidak mengandung duplikat.

## ✅ Solusi yang Diterapkan

### 1. **Perbaikan Event Handler - Gunakan Visual State sebagai Source of Truth**

**Kode Baru:**
```javascript
branchPills.forEach(pill => {
    pill.addEventListener('click', function () {
        const id   = String(this.dataset.id);
        const name = this.dataset.name;
        
        // ✅ FIX: Check visual state (pill classes) as source of truth
        // This prevents race conditions between state and UI
        const isCurrentlyActive = this.classList.contains('bg-emerald-500');
        
        if (isCurrentlyActive) {
            // Deselect - remove from array using filter (more reliable)
            selectedBranches = selectedBranches.filter(b => String(b.id) !== id);
            
            // Update visual
            this.classList.remove('bg-emerald-500', 'text-white', 'border-emerald-500', 'shadow-md');
            this.classList.add('bg-white', 'text-slate-600', 'border-slate-200');
            
            console.log('[edit-rembush] Branch deselected:', id, name);
        } else {
            // Select - add to array only if not already present (anti-duplicate)
            const alreadyExists = selectedBranches.some(b => String(b.id) === id);
            
            if (!alreadyExists) {
                selectedBranches.push({ id, name, value: 0, percent: 0 });
                console.log('[edit-rembush] Branch selected:', id, name);
            } else {
                console.warn('[edit-rembush] Branch already in array, skipping:', id, name);
            }
            
            // Update visual
            this.classList.remove('bg-white', 'text-slate-600', 'border-slate-200');
            this.classList.add('bg-emerald-500', 'text-white', 'border-emerald-500', 'shadow-md');
        }

        // Log current state for debugging
        console.log('[edit-rembush] Selected branches:', selectedBranches.map(b => `${b.name} (${b.id})`));

        allocationContainer.style.display = selectedBranches.length > 0 ? 'block' : 'none';
        renderDistribution();
    });
});
```

**Keuntungan:**
- ✅ Menggunakan **visual state** (class CSS) sebagai source of truth, bukan state array
- ✅ Menggunakan `filter()` untuk deselect (lebih reliable daripada `splice()`)
- ✅ Double-check dengan `some()` sebelum menambahkan cabang baru
- ✅ Logging untuk debugging

### 2. **Validasi Duplikat di `updateHiddenInputs()`**

**Kode Baru:**
```javascript
function updateHiddenInputs() {
    if (!hiddenInputsContainer) return;
    hiddenInputsContainer.innerHTML = '';
    
    // ✅ FIX: Remove duplicates before creating hidden inputs
    // This is a safety net in case duplicates somehow get into selectedBranches
    const uniqueBranches = [];
    const seenIds = new Set();
    
    selectedBranches.forEach(branch => {
        const branchId = String(branch.id);
        if (!seenIds.has(branchId)) {
            seenIds.add(branchId);
            uniqueBranches.push(branch);
        } else {
            console.warn('[edit-rembush] Duplicate branch detected and removed:', branchId, branch.name);
        }
    });
    
    // Update selectedBranches to remove duplicates
    if (uniqueBranches.length !== selectedBranches.length) {
        selectedBranches = uniqueBranches;
        console.log('[edit-rembush] Duplicates removed. Clean branches:', selectedBranches.map(b => `${b.name} (${b.id})`));
    }
    
    selectedBranches.forEach((branch, idx) => {
        hiddenInputsContainer.insertAdjacentHTML('beforeend', `
            <input type="hidden" name="branches[${idx}][branch_id]"          value="${branch.id}">
            <input type="hidden" name="branches[${idx}][allocation_amount]"  value="${Math.round(branch.value || 0)}">
            <input type="hidden" name="branches[${idx}][allocation_percent]" value="${branch.percent || 0}">
        `);
    });
}
```

**Keuntungan:**
- ✅ **Safety net** yang menghapus duplikat sebelum membuat hidden inputs
- ✅ Menggunakan `Set` untuk tracking ID yang sudah dilihat (O(1) lookup)
- ✅ Update `selectedBranches` jika duplikat ditemukan

### 3. **Validasi Duplikat di `renderDistribution()`**

**Kode Baru:**
```javascript
function renderDistribution() {
    activeBranchesList.innerHTML  = '';
    summaryBranchesList.innerHTML = '';
    if (hiddenInputsContainer) hiddenInputsContainer.innerHTML = '';

    // ✅ FIX: Remove duplicates before rendering
    const uniqueBranches = [];
    const seenIds = new Set();
    
    selectedBranches.forEach(branch => {
        const branchId = String(branch.id);
        if (!seenIds.has(branchId)) {
            seenIds.add(branchId);
            uniqueBranches.push(branch);
        } else {
            console.warn('[edit-rembush] Duplicate branch detected in renderDistribution, removing:', branchId, branch.name);
        }
    });
    
    // Update selectedBranches if duplicates were found
    if (uniqueBranches.length !== selectedBranches.length) {
        selectedBranches = uniqueBranches;
        console.log('[edit-rembush] Duplicates removed in renderDistribution. Clean branches:', selectedBranches.map(b => `${b.name} (${b.id})`));
    }

    summaryCountBadge.textContent = `${selectedBranches.length} Cabang`;
    
    // ... rest of render logic
}
```

**Keuntungan:**
- ✅ Membersihkan duplikat sebelum render
- ✅ Memastikan UI selalu menampilkan data yang clean

### 4. **Validasi Duplikat saat Inisialisasi Data**

**Kode Baru:**
```javascript
if (Array.isArray(window._initialBranches) && window._initialBranches.length > 0) {
    // ✅ FIX: Deep clone to prevent reference issues & ensure consistent string IDs
    // Also remove any duplicates that might exist in backend data
    const seenIds = new Set();
    const cleanBranches = [];
    
    window._initialBranches.forEach(b => {
        const branchId = String(b.id);
        if (!seenIds.has(branchId)) {
            seenIds.add(branchId);
            cleanBranches.push({
                id: branchId,
                name: b.name,
                percent: parseFloat(b.percent) || 0,
                value: parseInt(b.value) || 0
            });
        } else {
            console.warn('[edit-rembush] Duplicate branch in initial data, skipping:', branchId, b.name);
        }
    });
    
    selectedBranches = cleanBranches;
    // ... rest of initialization
}
```

**Keuntungan:**
- ✅ Membersihkan duplikat dari data backend
- ✅ Memastikan state awal selalu clean

### 5. **Backend Validation (Sudah Ada)**

File: `app/Http/Controllers/TransactionController.php`

```php
// Validate branches if provided
if ($request->branches && count($request->branches) > 0) {
    // ✅ FIX: Check for duplicate branch IDs to prevent manipulation
    $branchIds = collect($request->branches)->pluck('branch_id');
    if ($branchIds->count() !== $branchIds->unique()->count()) {
        DB::rollBack();
        return back()->withErrors([
            'branches' => 'Cabang tidak boleh duplikat. Silakan refresh halaman dan coba lagi.'
        ])->withInput();
    }
    
    $totalPercent = collect($request->branches)->sum('allocation_percent');
    if (abs($totalPercent - 100) > 1) {
        return back()->withErrors(['branches' => 'Total alokasi harus 100%.'])->withInput();
    }
}
```

**Keuntungan:**
- ✅ **Last line of defense** di backend
- ✅ Mencegah data duplikat masuk ke database
- ✅ Memberikan error message yang jelas ke user

## 🎯 Hasil

### Sebelum Fix:
- ❌ User bisa membuat duplikat cabang dengan mengklik berkali-kali
- ❌ Duplikat bisa masuk ke database jika tidak ada validasi backend
- ❌ UI tidak konsisten dengan state

### Setelah Fix:
- ✅ **Tidak ada duplikat** di level frontend (multiple layers of protection)
- ✅ **Visual state** sebagai source of truth mencegah race condition
- ✅ **Safety nets** di `renderDistribution()` dan `updateHiddenInputs()`
- ✅ **Backend validation** sebagai last line of defense
- ✅ **Logging** untuk debugging jika masalah muncul lagi

## 🧪 Testing

### Test Case 1: Klik Cepat Berkali-kali
1. Buka edit Rembush
2. Klik cabang Jetis berkali-kali dengan cepat
3. **Expected**: Cabang hanya toggle on/off, tidak ada duplikat

### Test Case 2: Edit Existing Rembush
1. Buka edit Rembush yang sudah memiliki cabang Jetis
2. Klik cabang Jetis (untuk deselect)
3. **Expected**: Cabang Jetis hilang dari daftar, tidak duplikat

### Test Case 3: Multiple Branches
1. Pilih beberapa cabang (Jetis, Pusat, Lain)
2. Klik salah satu cabang berkali-kali
3. **Expected**: Cabang hanya toggle, tidak ada duplikat

### Test Case 4: Submit Form
1. Pilih cabang dan atur alokasi
2. Submit form
3. **Expected**: Backend menerima data tanpa duplikat
4. Jika ada duplikat (somehow), backend reject dengan error message

## 📝 Catatan

- Fix ini menggunakan **defense in depth** approach dengan multiple layers of validation
- Logging ditambahkan untuk memudahkan debugging jika masalah muncul lagi
- Backend validation tetap dipertahankan sebagai last line of defense
- Visual state (CSS classes) digunakan sebagai source of truth untuk mencegah race condition

## 🔗 Related Files

- `resources/views/transactions/edit-rembush.blade.php` - Frontend fix
- `app/Http/Controllers/TransactionController.php` - Backend validation
- `app/Http/Controllers/RembushController.php` - Store logic

## 📅 Date

May 20, 2026
