# 📋 Rancangan Halaman Profile User

## 🎯 Tujuan
Halaman profile memungkinkan user untuk melihat dan mengelola informasi akun mereka, termasuk data pribadi, keamanan, notifikasi, dan aktivitas.

---

## 📐 Struktur Halaman

### 1. **Header Profile**
```
┌─────────────────────────────────────────────────────────┐
│  [Avatar]  Nama User                    [Edit Profile]  │
│            email@example.com                             │
│            Role: Admin                                   │
│            Member since: Jan 2024                        │
└─────────────────────────────────────────────────────────┘
```

**Komponen:**
- Avatar/Photo Profile (dengan opsi upload)
- Nama lengkap
- Email
- Role badge (dengan warna berbeda per role)
- Tanggal bergabung
- Tombol "Edit Profile"

---

### 2. **Tab Navigation**
```
┌─────────────────────────────────────────────────────────┐
│  [Informasi Pribadi] [Keamanan] [Notifikasi] [Aktivitas]│
└─────────────────────────────────────────────────────────┘
```

---

## 📑 Detail Setiap Tab

### **Tab 1: Informasi Pribadi**

#### A. Data Pribadi
```
┌─────────────────────────────────────────────────────────┐
│  📝 Data Pribadi                                         │
├─────────────────────────────────────────────────────────┤
│  Nama Lengkap:     [John Doe                    ] [Edit]│
│  Email:            [john@example.com            ] [Edit]│
│  Role:             Admin (tidak bisa diubah)             │
│  Telegram Chat ID: [123456789                   ] [Edit]│
│  Status:           ● Aktif                               │
│  Bergabung:        15 Januari 2024                       │
└─────────────────────────────────────────────────────────┘
```

**Field yang bisa diedit:**
- ✅ Nama lengkap
- ✅ Email (dengan verifikasi)
- ✅ Telegram Chat ID
- ❌ Role (hanya bisa diubah oleh admin/owner)

#### B. Rekening Bank
```
┌─────────────────────────────────────────────────────────┐
│  💳 Rekening Bank                          [+ Tambah]    │
├─────────────────────────────────────────────────────────┤
│  ┌───────────────────────────────────────────────────┐  │
│  │ BCA - 1234567890                                  │  │
│  │ a/n John Doe                    [Edit] [Hapus]    │  │
│  └───────────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────────┐  │
│  │ Mandiri - 9876543210                              │  │
│  │ a/n John Doe                    [Edit] [Hapus]    │  │
│  └───────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

**Fitur:**
- List semua rekening bank user
- Tambah rekening baru
- Edit rekening
- Hapus rekening
- Validasi: minimal 1 rekening harus ada

---

### **Tab 2: Keamanan**

#### A. Ubah Password
```
┌─────────────────────────────────────────────────────────┐
│  🔒 Ubah Password                                        │
├─────────────────────────────────────────────────────────┤
│  Password Lama:         [••••••••••••]                  │
│  Password Baru:         [••••••••••••]                  │
│  Konfirmasi Password:   [••••••••••••]                  │
│                                                          │
│  Persyaratan Password:                                   │
│  ✓ Minimal 6 karakter                                   │
│  ✓ Mengandung huruf besar                               │
│  ✓ Mengandung angka                                     │
│                                                          │
│                         [Ubah Password]                  │
└─────────────────────────────────────────────────────────┘
```

#### B. Riwayat Login
```
┌─────────────────────────────────────────────────────────┐
│  📊 Riwayat Login (10 terakhir)                         │
├─────────────────────────────────────────────────────────┤
│  ● 15 Mei 2026, 14:30 WIB - Chrome (Windows)           │
│  ● 15 Mei 2026, 08:15 WIB - Chrome (Windows)           │
│  ● 14 Mei 2026, 16:45 WIB - Firefox (Windows)          │
│  ...                                                     │
└─────────────────────────────────────────────────────────┘
```

**Fitur:**
- Tampilkan 10 login terakhir
- Info: tanggal, waktu, browser, device
- IP address (opsional, untuk keamanan)

#### C. Sesi Aktif
```
┌─────────────────────────────────────────────────────────┐
│  🖥️ Sesi Aktif                                          │
├─────────────────────────────────────────────────────────┤
│  ┌───────────────────────────────────────────────────┐  │
│  │ ● Sesi Ini (Chrome - Windows)                     │  │
│  │   Login: 15 Mei 2026, 14:30 WIB                   │  │
│  │   IP: 192.168.1.100                                │  │
│  └───────────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────────┐  │
│  │ ● Firefox - Windows                  [Logout]     │  │
│  │   Login: 14 Mei 2026, 16:45 WIB                   │  │
│  │   IP: 192.168.1.101                                │  │
│  └───────────────────────────────────────────────────┘  │
│                                                          │
│                    [Logout Semua Sesi Lain]             │
└─────────────────────────────────────────────────────────┘
```

---

### **Tab 3: Notifikasi**

#### A. Preferensi Notifikasi
```
┌─────────────────────────────────────────────────────────┐
│  🔔 Pengaturan Notifikasi                               │
├─────────────────────────────────────────────────────────┤
│  Notifikasi Email:                                       │
│  ☑ Transaksi disetujui                                  │
│  ☑ Transaksi ditolak                                    │
│  ☑ Status OCR berubah                                   │
│  ☑ Anomali harga terdeteksi                             │
│  ☐ Ringkasan harian                                     │
│                                                          │
│  Notifikasi Telegram:                                    │
│  ☑ Transaksi memerlukan approval                        │
│  ☑ Transaksi disetujui/ditolak                          │
│  ☑ Anomali harga                                        │
│  ☐ Broadcast dari admin                                 │
│                                                          │
│  Notifikasi In-App:                                      │
│  ☑ Semua notifikasi                                     │
│  ☑ Suara notifikasi                                     │
│                                                          │
│                         [Simpan Pengaturan]              │
└─────────────────────────────────────────────────────────┘
```

#### B. Test Notifikasi
```
┌─────────────────────────────────────────────────────────┐
│  🧪 Test Notifikasi                                     │
├─────────────────────────────────────────────────────────┤
│  [Test Email]  [Test Telegram]  [Test In-App]          │
└─────────────────────────────────────────────────────────┘
```

---

### **Tab 4: Aktivitas**

#### A. Statistik Pribadi
```
┌─────────────────────────────────────────────────────────┐
│  📊 Statistik Saya                                      │
├─────────────────────────────────────────────────────────┤
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐   │
│  │ 45           │ │ 12           │ │ 3            │   │
│  │ Transaksi    │ │ Pending      │ │ Ditolak      │   │
│  │ Dibuat       │ │              │ │              │   │
│  └──────────────┘ └──────────────┘ └──────────────┘   │
│                                                          │
│  ┌──────────────┐ ┌──────────────┐                     │
│  │ Rp 45.5 Jt   │ │ Rp 1.2 Jt    │                     │
│  │ Total Nilai  │ │ Rata-rata    │                     │
│  └──────────────┘ └──────────────┘                     │
└─────────────────────────────────────────────────────────┘
```

#### B. Aktivitas Terbaru
```
┌─────────────────────────────────────────────────────────┐
│  📝 Aktivitas Terbaru (20 terakhir)                     │
├─────────────────────────────────────────────────────────┤
│  Filter: [Semua ▼] [Bulan Ini ▼]                       │
│                                                          │
│  ● 15 Mei 2026, 14:30                                   │
│    Membuat transaksi #TRX-2026-001                      │
│    Rembush - Rp 500.000                                 │
│                                                          │
│  ● 15 Mei 2026, 10:15                                   │
│    Mengubah transaksi #TRX-2026-002                     │
│    Status: pending → approved                            │
│                                                          │
│  ● 14 Mei 2026, 16:45                                   │
│    Login ke sistem                                       │
│                                                          │
│  ...                                                     │
│                                                          │
│                    [Muat Lebih Banyak]                   │
└─────────────────────────────────────────────────────────┘
```

**Fitur:**
- Filter berdasarkan jenis aktivitas
- Filter berdasarkan periode
- Pagination/infinite scroll
- Link ke detail transaksi

---

## 🎨 Design Guidelines

### Color Scheme (Role Badges)
```css
.role-teknisi  { background: #3b82f6; } /* Blue */
.role-admin    { background: #8b5cf6; } /* Purple */
.role-atasan   { background: #f59e0b; } /* Orange */
.role-owner    { background: #ef4444; } /* Red */
```

### Avatar
- Default: Inisial nama dengan background warna random
- Upload: Support JPG, PNG (max 2MB)
- Crop: Square 1:1 ratio
- Size: 200x200px

### Responsive Design
- Desktop: 2 kolom layout
- Tablet: 1 kolom dengan sidebar
- Mobile: Full width, stacked

---

## 🔐 Permission & Access Control

### Semua Role (teknisi, admin, atasan, owner)
- ✅ Lihat profile sendiri
- ✅ Edit nama, email, telegram chat ID
- ✅ Ubah password
- ✅ Kelola rekening bank sendiri
- ✅ Lihat aktivitas sendiri
- ✅ Atur preferensi notifikasi

### Admin, Atasan, Owner
- ✅ Lihat profile user lain (read-only)
- ✅ Edit role user lain (melalui halaman user management)

### Owner Only
- ✅ Lihat semua aktivitas user
- ✅ Hapus user

---

## 🛠️ Technical Implementation

### Routes
```php
// Profile routes
Route::middleware('auth')->group(function () {
    // Own profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar'])->name('profile.avatar');
    Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.avatar.delete');
    
    // Notification preferences
    Route::get('/profile/notifications', [ProfileController::class, 'notificationSettings'])->name('profile.notifications');
    Route::put('/profile/notifications', [ProfileController::class, 'updateNotifications'])->name('profile.notifications.update');
    Route::post('/profile/notifications/test', [ProfileController::class, 'testNotification'])->name('profile.notifications.test');
    
    // Activity log
    Route::get('/profile/activity', [ProfileController::class, 'activity'])->name('profile.activity');
    Route::get('/profile/stats', [ProfileController::class, 'stats'])->name('profile.stats');
    
    // Sessions
    Route::get('/profile/sessions', [ProfileController::class, 'sessions'])->name('profile.sessions');
    Route::delete('/profile/sessions/{id}', [ProfileController::class, 'destroySession'])->name('profile.sessions.destroy');
    Route::delete('/profile/sessions', [ProfileController::class, 'destroyAllSessions'])->name('profile.sessions.destroy-all');
    
    // View other user profile (admin+)
    Route::middleware('role:admin,atasan,owner')->group(function () {
        Route::get('/profile/{user}', [ProfileController::class, 'showUser'])->name('profile.user');
    });
});
```

### Database Schema Updates

#### 1. Add avatar column to users table
```php
Schema::table('users', function (Blueprint $table) {
    $table->string('avatar')->nullable()->after('telegram_chat_id');
    $table->json('notification_preferences')->nullable()->after('avatar');
});
```

#### 2. Create user_sessions table (untuk tracking sesi)
```php
Schema::create('user_sessions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('session_id')->unique();
    $table->string('ip_address', 45);
    $table->text('user_agent');
    $table->timestamp('last_activity');
    $table->timestamps();
});
```

#### 3. Extend activity_logs table (sudah ada)
```php
// Pastikan activity_logs sudah mencatat:
// - user_id
// - action (login, logout, create, update, delete, etc.)
// - model_type & model_id
// - ip_address
// - user_agent
// - created_at
```

### Controller Methods

#### ProfileController.php
```php
class ProfileController extends Controller
{
    // Show own profile
    public function show() { }
    
    // Edit profile form
    public function edit() { }
    
    // Update profile
    public function update(Request $request) { }
    
    // Update password
    public function updatePassword(Request $request) { }
    
    // Upload avatar
    public function uploadAvatar(Request $request) { }
    
    // Delete avatar
    public function deleteAvatar() { }
    
    // Notification settings
    public function notificationSettings() { }
    public function updateNotifications(Request $request) { }
    public function testNotification(Request $request) { }
    
    // Activity & stats
    public function activity(Request $request) { }
    public function stats() { }
    
    // Sessions management
    public function sessions() { }
    public function destroySession($id) { }
    public function destroyAllSessions() { }
    
    // View other user (admin+)
    public function showUser(User $user) { }
}
```

### Views Structure
```
resources/views/profile/
├── show.blade.php           # Main profile page
├── edit.blade.php           # Edit profile form
├── partials/
│   ├── header.blade.php     # Profile header with avatar
│   ├── tabs.blade.php       # Tab navigation
│   ├── personal-info.blade.php
│   ├── security.blade.php
│   ├── notifications.blade.php
│   └── activity.blade.php
└── components/
    ├── avatar-upload.blade.php
    ├── bank-account-card.blade.php
    ├── activity-item.blade.php
    └── session-card.blade.php
```

---

## 🚀 Features Priority

### Phase 1 (MVP)
- ✅ View profile (header + personal info)
- ✅ Edit nama, email
- ✅ Ubah password
- ✅ Kelola rekening bank
- ✅ Lihat aktivitas terbaru

### Phase 2
- ✅ Upload avatar
- ✅ Statistik pribadi
- ✅ Preferensi notifikasi
- ✅ Riwayat login

### Phase 3
- ✅ Session management
- ✅ Test notifikasi
- ✅ Advanced filters untuk aktivitas
- ✅ Export data pribadi

---

## 📱 Mobile Considerations

### Mobile Layout
```
┌─────────────────────┐
│  [Avatar]           │
│  Nama User          │
│  email@example.com  │
│  Role: Admin        │
│  [Edit Profile]     │
├─────────────────────┤
│  [Tab 1] [Tab 2]    │
│  [Tab 3] [Tab 4]    │
├─────────────────────┤
│  Content Area       │
│                     │
│                     │
└─────────────────────┘
```

### Mobile-Specific Features
- Swipe between tabs
- Pull to refresh activity
- Bottom sheet untuk edit forms
- Touch-friendly buttons (min 44x44px)

---

## 🔍 SEO & Accessibility

### Meta Tags
```html
<title>Profile - {{ $user->name }} | Sistem Keuangan</title>
<meta name="robots" content="noindex, nofollow">
```

### Accessibility
- ARIA labels untuk semua interactive elements
- Keyboard navigation support
- Screen reader friendly
- High contrast mode support
- Focus indicators

---

## 🧪 Testing Checklist

### Unit Tests
- [ ] Update profile information
- [ ] Change password validation
- [ ] Avatar upload & validation
- [ ] Bank account CRUD
- [ ] Notification preferences

### Integration Tests
- [ ] Profile page loads correctly
- [ ] Edit profile flow
- [ ] Password change flow
- [ ] Avatar upload flow
- [ ] Session management

### E2E Tests
- [ ] Complete profile update journey
- [ ] Password change journey
- [ ] Bank account management
- [ ] Activity log pagination

---

## 📊 Analytics & Monitoring

### Track Events
- Profile viewed
- Profile updated
- Password changed
- Avatar uploaded
- Bank account added/edited/deleted
- Notification preferences changed
- Session terminated

### Metrics
- Profile completion rate
- Average time on profile page
- Most edited fields
- Password change frequency

---

## 🎯 Success Metrics

1. **User Engagement**
   - 80%+ users complete profile in first week
   - 50%+ users upload avatar

2. **Security**
   - 90%+ users change default password
   - Average password strength score > 3/5

3. **Usability**
   - < 3 clicks to edit any field
   - < 5 seconds to save changes
   - 0 errors on valid input

---

## 📝 Notes

### Existing Features to Integrate
- User bank accounts (sudah ada model & controller)
- Activity logs (sudah ada model & controller)
- Notifications (sudah ada sistem notifikasi)
- Telegram integration (sudah ada chat_id field)

### Future Enhancements
- Two-factor authentication (2FA)
- Email verification
- Profile visibility settings
- Export personal data (GDPR compliance)
- Dark mode preference
- Language preference
- Timezone settings

---

## 🔗 Related Documentation
- [User Management](./USER_MANAGEMENT.md)
- [Activity Logging](../AUDIT_TRAIL_LOGGING.md)
- [Notification System](./NOTIFICATION_SYSTEM.md)
- [Security Guidelines](./SECURITY.md)
