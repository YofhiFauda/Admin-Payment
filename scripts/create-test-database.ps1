# ═══════════════════════════════════════════════════════════════════
#  Create Testing Database (PowerShell)
#  Script untuk membuat database testing di MySQL Docker
# ═══════════════════════════════════════════════════════════════════

Write-Host "🔧 Creating testing database..." -ForegroundColor Cyan

# Ambil credentials dari .env secara tepat (menghindari duplikasi match seperti PULSE_DB_HOST)
$DB_HOST = ""
$DB_PORT = ""
$DB_USERNAME = ""
$DB_PASSWORD = ""
foreach ($line in Get-Content .env) {
    if ($line -match "^DB_HOST=(.+)$") { $DB_HOST = $Matches[1].Trim() }
    if ($line -match "^DB_PORT=(.+)$") { $DB_PORT = $Matches[1].Trim() }
    if ($line -match "^DB_USERNAME=(.+)$") { $DB_USERNAME = $Matches[1].Trim() }
    if ($line -match "^DB_PASSWORD=(.+)$") { $DB_PASSWORD = $Matches[1].Trim() }
}

# Nama database testing
$DB_TEST = "admin_payment_testing"

Write-Host "📦 Database Host: $DB_HOST" -ForegroundColor Yellow
Write-Host "📦 Database Port: $DB_PORT" -ForegroundColor Yellow
Write-Host "📦 Creating database: $DB_TEST" -ForegroundColor Yellow

# Coba buat database testing menggunakan beberapa alternatif cara:
$success = $false

# Cara 1: Menggunakan mysql client lokal (jika terinstall)
if (Get-Command mysql -ErrorAction SilentlyContinue) {
    Write-Host "🔄 Mencoba via local mysql client..." -ForegroundColor Gray
    $query = "CREATE DATABASE IF NOT EXISTS $DB_TEST CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    $mysqlCmd = "mysql -h $DB_HOST -P $DB_PORT -u $DB_USERNAME -p$DB_PASSWORD -e `"$query`""
    Invoke-Expression $mysqlCmd 2>$null
    if ($LASTEXITCODE -eq 0) { $success = $true }
}

# Cara 2: Menggunakan docker exec ke database container (jika docker ada di host)
if (-not $success -and (Get-Command docker -ErrorAction SilentlyContinue)) {
    Write-Host "🔄 mysql client lokal tidak ditemukan atau gagal. Mencoba via Docker container '$DB_HOST'..." -ForegroundColor Gray
    $dockerCmd = "docker exec -i $DB_HOST mysql -u $DB_USERNAME -p$DB_PASSWORD -e `"CREATE DATABASE IF NOT EXISTS $DB_TEST CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`""
    Invoke-Expression $dockerCmd 2>$null
    if ($LASTEXITCODE -eq 0) { $success = $true }
}

# Cara 3: Menggunakan docker exec ke app container (jika ada php di sana)
if (-not $success -and (Get-Command docker -ErrorAction SilentlyContinue)) {
    Write-Host "🔄 Mencoba via PHP PDO di dalam container 'admin-payment-app-1'..." -ForegroundColor Gray
    $phpCode = "try { `$pdo = new PDO('mysql:host=$DB_HOST;port=$DB_PORT', '$DB_USERNAME', '$DB_PASSWORD'); `$pdo->exec('CREATE DATABASE IF NOT EXISTS $DB_TEST CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'); exit(0); } catch (Exception `$e) { exit(1); }"
    $dockerPhpCmd = "docker exec -i admin-payment-app-1 php -r `"$phpCode`""
    Invoke-Expression $dockerPhpCmd 2>$null
    if ($LASTEXITCODE -eq 0) { $success = $true }
}

if ($success) {
    Write-Host "✅ Database testing berhasil dibuat!" -ForegroundColor Green
    Write-Host ""
    Write-Host "📝 Sekarang jalankan migration (di dalam container app):" -ForegroundColor Cyan
    Write-Host "   docker exec -it admin-payment-app-1 php artisan migrate --env=testing" -ForegroundColor White
    Write-Host ""
    Write-Host "🧪 Kemudian jalankan tests (di dalam container app):" -ForegroundColor Cyan
    Write-Host "   docker exec -it admin-payment-app-1 php artisan test" -ForegroundColor White
} else {
    Write-Host "❌ Gagal membuat database testing! Silakan pastikan container database '$DB_HOST' sedang berjalan." -ForegroundColor Red
    exit 1
}
