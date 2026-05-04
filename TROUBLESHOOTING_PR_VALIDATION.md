# 🔧 Troubleshooting: PR Validation Errors

## 📋 Ringkasan Masalah

Berdasarkan screenshot PR validation yang gagal, ada beberapa check yang error:

- ❌ **PR Validation** - Format PR title tidak sesuai
- ❌ **Automated Code Review** - Code style atau dependency issues
- ❌ **Database Changes Review** - Migration changes detected
- ❌ **Test Coverage Review** - Coverage di bawah 80% atau test gagal
- ❌ **Auto Label PR** - Gagal auto-labeling

---

## 🎯 Solusi Utama: Pindahkan Dev Tools ke `require-dev`

### Masalah

Laravel Pulse dan Log Viewer saat ini ada di section `require` di `composer.json`, yang berarti:
- ✅ Terinstall di **local development**
- ✅ Terinstall di **production** ← **INI MASALAH!**

Tools monitoring/debugging ini **TIDAK SEHARUSNYA** ada di production karena:
- 🔒 **Security risk** - Expose informasi sensitif
- 🐌 **Performance overhead** - Menambah beban server
- 💾 **Disk space** - Dependencies yang tidak perlu

### Solusi

Pindahkan ke `require-dev`:

```json
{
  "require": {
    "php": "^8.2",
    "dedoc/scramble": "^0.13",
    "intervention/image": "^4.0",
    "laravel/framework": "^12.0",
    "laravel/horizon": "^5.45",
    "laravel/reverb": "^1.0",
    "laravel/tinker": "^2.10.1",
    "phpoffice/phpspreadsheet": "^5.7"
  },
  "require-dev": {
    "fakerphp/faker": "^1.23",
    "laravel/pail": "^1.2.2",
    "laravel/pint": "^1.24",
    "laravel/pulse": "^1.0",
    "laravel/sail": "^1.41",
    "laravel/telescope": "*",
    "mockery/mockery": "^1.6",
    "nunomaduro/collision": "^8.6",
    "opcodesio/log-viewer": "^3.0",
    "phpunit/phpunit": "^11.5.3"
  }
}
```

**Catatan:** `laravel/horizon` tetap di `require` karena digunakan untuk queue processing di production.

---

## 🔍 Solusi Per Check yang Gagal

### 1. ❌ PR Validation

**Penyebab:** Format PR title tidak sesuai semantic commit

**Format yang Benar:**
```
<type>: <description>

atau

<type>(<scope>): <description>
```

**Valid Types:**
- `feat` - Fitur baru
- `fix` - Bug fix
- `docs` - Dokumentasi
- `style` - Formatting, missing semicolons, etc
- `refactor` - Code refactoring
- `perf` - Performance improvements
- `test` - Menambah tests
- `build` - Build system changes
- `ci` - CI configuration changes
- `chore` - Maintenance tasks
- `revert` - Revert previous commit

**Contoh:**
```
✅ feat: add price anomaly detection
✅ fix(auth): resolve login redirect issue
✅ docs: update deployment guide
✅ refactor(services): optimize price index calculation

❌ Added new feature
❌ Fixed bug
❌ Update
```

**Cara Fix:**
1. Edit PR title di GitHub
2. Atau rename branch dengan format yang benar

---

### 2. ❌ Automated Code Review

**Kemungkinan Penyebab:**

#### A. Debug Statements
Check tidak menemukan debug statements di PHP, tapi pastikan tidak ada:
```php
// ❌ Hapus ini sebelum commit
dd($variable);
dump($data);
var_dump($array);
print_r($object);
```

```javascript
// ❌ Hapus ini juga
console.log('debug');
debugger;
```

**Cara Check:**
```bash
# Search debug statements
git diff origin/main...HEAD | grep -E "(dd\(|dump\(|var_dump\(|print_r\(|console\.log\(|debugger)"
```

#### B. Code Style (Laravel Pint)
```bash
# Check code style
./vendor/bin/pint --test

# Auto-fix
./vendor/bin/pint
```

#### C. Sensitive Data
Pastikan tidak ada credentials di code:
```bash
git diff origin/main...HEAD | grep -iE "(password|secret|api_key|token|private_key).*=.*['\"]"
```

---

### 3. ❌ Database Changes Review

**Penyebab:** Ada perubahan migration yang perlu review manual

**Checklist Migration:**
- [ ] Method `down()` sudah diimplementasi (reversible)
- [ ] Sudah ditest dengan copy production data
- [ ] Tidak ada data loss
- [ ] Migration idempotent (bisa dijalankan multiple times)
- [ ] Index ditambahkan untuk foreign keys
- [ ] Pertimbangkan impact pada large tables

**Contoh Migration yang Baik:**
```php
public function up()
{
    Schema::create('price_indexes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('master_item_id')->constrained()->onDelete('cascade');
        $table->foreignId('branch_id')->constrained()->onDelete('cascade');
        $table->decimal('price', 15, 2);
        $table->timestamps();
        
        // ✅ Tambahkan index untuk foreign keys
        $table->index(['master_item_id', 'branch_id']);
        $table->index('created_at');
    });
}

public function down()
{
    // ✅ Implementasi rollback
    Schema::dropIfExists('price_indexes');
}
```

---

### 4. ❌ Test Coverage Review

**Penyebab:** Test coverage di bawah 80% atau test gagal

**Cara Check Local:**
```bash
# Run tests dengan coverage
php artisan test --coverage --min=80

# Atau dengan detail
php artisan test --coverage
```

**Jika Coverage Rendah:**
1. Tambahkan unit tests untuk services/models baru
2. Tambahkan feature tests untuk endpoints baru
3. Focus pada critical paths

**Contoh Test:**
```php
// tests/Unit/PriceIndexServiceTest.php
public function test_calculate_price_index()
{
    $service = new PriceIndexService();
    $result = $service->calculateIndex($itemId, $branchId);
    
    $this->assertNotNull($result);
    $this->assertIsFloat($result->average_price);
}
```

**Jika Test Gagal:**
```bash
# Run specific test
php artisan test --filter=test_name

# Run dengan verbose
php artisan test --verbose
```

---

### 5. ❌ Auto Label PR

**Penyebab:** Script gagal menambahkan label otomatis

**Biasanya ini bukan blocker**, tapi jika perlu fix:
1. Check GitHub token permissions
2. Pastikan workflow punya write access ke issues

---

## 🚀 Quick Fix Checklist

Sebelum push PR, jalankan:

```bash
# 1. Fix code style
./vendor/bin/pint

# 2. Run tests
php artisan test --coverage --min=80

# 3. Check for debug statements
git diff origin/main | grep -E "(dd\(|dump\(|var_dump\(|console\.log\()"

# 4. Security audit
composer audit
npm audit --audit-level=moderate

# 5. Commit dengan semantic format
git commit -m "fix: move dev tools to require-dev"
```

---

## 📝 Recommended PR Title untuk Fix Ini

```
refactor(deps): move monitoring tools to require-dev

- Move laravel/pulse to require-dev
- Move opcodesio/log-viewer to require-dev
- Prevent dev tools from being installed in production
- Improve production security and performance
```

---

## 🔄 Setelah Fix

1. **Update composer.json**
2. **Run composer update** (local)
3. **Commit changes**
4. **Push ke branch**
5. **PR checks akan re-run otomatis**

---

## 📚 Resources

- [Semantic Commit Messages](https://www.conventionalcommits.org/)
- [Laravel Pint Documentation](https://laravel.com/docs/pint)
- [Laravel Testing](https://laravel.com/docs/testing)
- [Composer require vs require-dev](https://getcomposer.org/doc/04-schema.md#require-dev)

---

## ⚠️ Production Deployment Note

Setelah fix ini, di production:
- `composer install --no-dev` akan **SKIP** Pulse & Log Viewer
- Dockerfile.prod sudah menggunakan `--no-dev` ✅
- Ukuran image Docker akan lebih kecil
- Security posture lebih baik
