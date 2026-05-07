# PowerShell Script untuk memperbaiki permission di Windows
# ================================================================

Write-Host "Memulai perbaikan permission..." -ForegroundColor Cyan
Write-Host ""

# --- 1. Pastikan direktori ada ---
Write-Host "[1/3] Memastikan direktori log ada..." -ForegroundColor Yellow

if (!(Test-Path "storage/logs")) {
    New-Item -ItemType Directory -Path "storage/logs" -Force | Out-Null
    Write-Host "Direktori storage/logs dibuat" -ForegroundColor Green
} else {
    Write-Host "Direktori storage/logs sudah ada" -ForegroundColor Green
}

if (!(Test-Path "bootstrap/cache")) {
    New-Item -ItemType Directory -Path "bootstrap/cache" -Force | Out-Null
    Write-Host "Direktori bootstrap/cache dibuat" -ForegroundColor Green
} else {
    Write-Host "Direktori bootstrap/cache sudah ada" -ForegroundColor Green
}

# --- 2. Set Full Control untuk current user ---
Write-Host ""
Write-Host "[2/3] Setting permissions..." -ForegroundColor Yellow

try {
    # Get current user
    $currentUser = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name
    
    # Set Full Control untuk storage
    $acl = Get-Acl "storage"
    $accessRule = New-Object System.Security.AccessControl.FileSystemAccessRule($currentUser, "FullControl", "ContainerInherit,ObjectInherit", "None", "Allow")
    $acl.SetAccessRule($accessRule)
    Set-Acl "storage" $acl
    
    # Set Full Control untuk bootstrap/cache
    $acl = Get-Acl "bootstrap/cache"
    $accessRule = New-Object System.Security.AccessControl.FileSystemAccessRule($currentUser, "FullControl", "ContainerInherit,ObjectInherit", "None", "Allow")
    $acl.SetAccessRule($accessRule)
    Set-Acl "bootstrap/cache" $acl
    
    Write-Host "Permissions berhasil diset untuk user: $currentUser" -ForegroundColor Green
} catch {
    Write-Host "Warning: Tidak bisa set permissions: $_" -ForegroundColor Yellow
}

# --- 3. Clear Cache ---
Write-Host ""
Write-Host "[3/3] Membersihkan cache..." -ForegroundColor Yellow

php artisan config:clear | Out-Null
php artisan cache:clear | Out-Null
php artisan view:clear | Out-Null
php artisan route:clear | Out-Null

Write-Host "Cache dibersihkan" -ForegroundColor Green

# ─── Summary ───────────────────────────────────────────────────
Write-Host ""
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "Script selesai!" -ForegroundColor Green
Write-Host ""
Write-Host "Langkah selanjutnya:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Rebuild frontend assets:" -ForegroundColor White
Write-Host "   npm run build" -ForegroundColor Gray
Write-Host ""
Write-Host "2. Restart Reverb server (jika sudah running):" -ForegroundColor White
Write-Host "   php artisan reverb:restart" -ForegroundColor Gray
Write-Host ""
Write-Host "3. Restart Queue workers:" -ForegroundColor White
Write-Host "   php artisan queue:restart" -ForegroundColor Gray
Write-Host ""
Write-Host "================================================================" -ForegroundColor Cyan
