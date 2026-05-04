# Script to clean up root directory after copying files to docs/
# This will DELETE files from root that have been copied to docs/

Write-Host "🧹 Starting root directory cleanup..." -ForegroundColor Cyan
Write-Host ""

# Files to keep in root (core project files)
$keepFiles = @(
    "README.md",
    "DOCUMENTATION_INDEX.md",
    "ANALISIS_DOKUMENTASI.md",
    "DOCUMENTATION_REORGANIZATION_SUMMARY.md",
    "DOCUMENTATION_UPDATE_COMPLETE.md",
    "DOCUMENTATION_CLEANUP_COMPLETE.md",
    "FINAL_CLEANUP_ANALYSIS.md",
    "composer.json",
    "composer.lock",
    "package.json",
    "package-lock.json",
    "docker-compose.yml",
    "docker-compose.prod.yml",
    "Dockerfile",
    "Dockerfile.prod",
    ".env",
    ".env.example",
    ".env.production.example",
    ".dockerignore",
    ".editorconfig",
    ".gitattributes",
    ".gitignore",
    ".phpunit.result.cache",
    "artisan",
    "phpunit.xml",
    "vite.config.js",
    "preload.php",
    "OCR_Nota_Kontan_v4.5.json"
)

# Get all .md files in root
$mdFiles = Get-ChildItem -Path . -Filter "*.md" -File

$deletedCount = 0
$keptCount = 0

foreach ($file in $mdFiles) {
    if ($keepFiles -contains $file.Name) {
        Write-Host "✅ KEEP: $($file.Name)" -ForegroundColor Green
        $keptCount++
    } else {
        # Check if file exists in docs/
        $existsInDocs = Get-ChildItem -Path "docs" -Filter $file.Name -Recurse -File
        if ($existsInDocs) {
            Write-Host "🗑️  DELETE: $($file.Name) (exists in docs/)" -ForegroundColor Yellow
            Remove-Item $file.FullName -Force
            $deletedCount++
        } else {
            Write-Host "⚠️  SKIP: $($file.Name) (not found in docs/)" -ForegroundColor Magenta
        }
    }
}

Write-Host ""
Write-Host "📊 Summary:" -ForegroundColor Cyan
Write-Host "   Kept: $keptCount files" -ForegroundColor Green
Write-Host "   Deleted: $deletedCount files" -ForegroundColor Yellow
Write-Host ""
Write-Host "✅ Cleanup complete!" -ForegroundColor Green
