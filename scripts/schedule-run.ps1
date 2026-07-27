# Windows Task Scheduler entry — run every 1 minute.
# Action: powershell.exe -ExecutionPolicy Bypass -File "F:\path\to\ledrix\scripts\schedule-run.ps1"
Set-Location $PSScriptRoot\..

php artisan schedule:run >> storage\logs\scheduler.log 2>&1
