# Run after each deploy on Windows (PowerShell, from project root).
$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot\..

Write-Host "==> Ledrix post-deploy"

php artisan down --retry=60 2>$null

# Primary (tenant CRM) DB
php artisan migrate --force

# Central (SaaS / Super Admin) DB
php artisan migrate --database=central --path=database/migrations/central --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan queue:restart 2>$null

php artisan up

Write-Host "==> Done. Register Task Scheduler to run scripts/schedule-run.ps1 every minute."
Write-Host "==> Verify: php artisan schedule:list"
