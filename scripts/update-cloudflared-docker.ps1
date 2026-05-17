# ═══════════════════════════════════════════════════════════════════
#  Script untuk Update URL Cloudflared (Docker Environment - PowerShell)
# ═══════════════════════════════════════════════════════════════════
#
#  Usage: .\scripts\update-cloudflared-docker.ps1 -AppUrl <url> -ReverbUrl <url>
#  Example: .\scripts\update-cloudflared-docker.ps1 `
#           -AppUrl "https://new-app.trycloudflare.com" `
#           -ReverbUrl "https://new-reverb.trycloudflare.com"
#
# ═══════════════════════════════════════════════════════════════════

param(
    [Parameter(Mandatory=$true)]
    [string]$AppUrl,
    
    [Parameter(Mandatory=$true)]
    [string]$ReverbUrl
)

# Extract host from URL
$ReverbHost = $ReverbUrl -replace 'https://', '' -replace 'http://', ''

Write-Host "╔════════════════════════════════════════════════════════════╗" -ForegroundColor Blue
Write-Host "║    Update Cloudflared URL (Docker Environment)            ║" -ForegroundColor Blue
Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor Blue
Write-Host ""
Write-Host "New APP_URL: " -NoNewline -ForegroundColor Yellow
Write-Host $AppUrl -ForegroundColor White
Write-Host "New REVERB URL: " -NoNewline -ForegroundColor Yellow
Write-Host $ReverbUrl -ForegroundColor White
Write-Host "New REVERB HOST: " -NoNewline -ForegroundColor Yellow
Write-Host $ReverbHost -ForegroundColor White
Write-Host ""

# ─── Step 1: Update .env file ──────────────────────────────────────
Write-Host "[1/7] " -NoNewline -ForegroundColor Blue
Write-Host "Updating .env file..."

# Backup .env
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
Copy-Item .env ".env.backup.$timestamp"

# Read .env content
$envContent = Get-Content .env

# Update APP_URL
$envContent = $envContent -replace '^APP_URL=.*', "APP_URL=$AppUrl"
$envContent = $envContent -replace '^SERVICE_URL_NGINX=.*', "SERVICE_URL_NGINX=$AppUrl"

# Update REVERB
$envContent = $envContent -replace '^VITE_REVERB_HOST=.*', "VITE_REVERB_HOST=$ReverbHost"

# Save updated content
$envContent | Set-Content .env

Write-Host "✓ " -NoNewline -ForegroundColor Green
Write-Host ".env updated"

# ─── Step 2: Clear Laravel caches in container ─────────────────────
Write-Host "[2/7] " -NoNewline -ForegroundColor Blue
Write-Host "Clearing Laravel caches..."

docker-compose exec -T app php artisan config:clear
docker-compose exec -T app php artisan route:clear
docker-compose exec -T app php artisan view:clear
docker-compose exec -T app php artisan cache:clear

Write-Host "✓ " -NoNewline -ForegroundColor Green
Write-Host "Caches cleared"

# ─── Step 3: Rebuild config cache ──────────────────────────────────
Write-Host "[3/7] " -NoNewline -ForegroundColor Blue
Write-Host "Rebuilding config cache..."

docker-compose exec -T app php artisan config:cache

Write-Host "✓ " -NoNewline -ForegroundColor Green
Write-Host "Config cached"

# ─── Step 4: Restart Queue Workers ─────────────────────────────────
Write-Host "[4/7] " -NoNewline -ForegroundColor Blue
Write-Host "Restarting queue workers..."

docker-compose exec -T app php artisan queue:restart

Write-Host "✓ " -NoNewline -ForegroundColor Green
Write-Host "Queue workers restarted"

# ─── Step 5: Rebuild Vite assets ───────────────────────────────────
Write-Host "[5/7] " -NoNewline -ForegroundColor Blue
Write-Host "Rebuilding Vite assets..."

if (Test-Path "package.json") {
    npm run build
    Write-Host "✓ " -NoNewline -ForegroundColor Green
    Write-Host "Vite assets rebuilt"
} else {
    Write-Host "⊘ " -NoNewline -ForegroundColor Yellow
    Write-Host "No package.json found, skipping"
}

# ─── Step 6: Restart Docker services ───────────────────────────────
Write-Host "[6/7] " -NoNewline -ForegroundColor Blue
Write-Host "Restarting Docker services..."

Write-Host "→ " -NoNewline -ForegroundColor Yellow
Write-Host "Restarting reverb..."
docker-compose restart reverb

Write-Host "→ " -NoNewline -ForegroundColor Yellow
Write-Host "Restarting app..."
docker-compose restart app

Write-Host "→ " -NoNewline -ForegroundColor Yellow
Write-Host "Restarting nginx..."
docker-compose restart nginx

Write-Host "✓ " -NoNewline -ForegroundColor Green
Write-Host "Docker services restarted"

# ─── Step 7: Verify services ───────────────────────────────────────
Write-Host "[7/7] " -NoNewline -ForegroundColor Blue
Write-Host "Verifying services..."

Start-Sleep -Seconds 3

Write-Host ""
Write-Host "Service Status:" -ForegroundColor Yellow
docker-compose ps

# ─── Summary ────────────────────────────────────────────────────────
Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║                    Update Complete!                        ║" -ForegroundColor Green
Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor Green
Write-Host ""
Write-Host "Current Configuration:" -ForegroundColor Yellow
Write-Host "  APP_URL: " -NoNewline
Write-Host $AppUrl -ForegroundColor Green
Write-Host "  VITE_REVERB_HOST: " -NoNewline
Write-Host $ReverbHost -ForegroundColor Green
Write-Host ""
Write-Host "Backup saved to: " -NoNewline -ForegroundColor Yellow
Write-Host ".env.backup.$timestamp"
Write-Host ""
Write-Host "Test your application:" -ForegroundColor Blue
Write-Host "  → Open: " -NoNewline -ForegroundColor Green
Write-Host $AppUrl
Write-Host "  → Check WebSocket: Browser Console" -ForegroundColor Green
Write-Host ""
