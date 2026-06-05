$ProjectRoot = Resolve-Path (Join-Path $PSScriptRoot '..')
$ArtisanPath = Join-Path $ProjectRoot 'artisan'
$LogDir = Join-Path $ProjectRoot 'storage\logs'
$LoopLog = Join-Path $LogDir 'scheduler-worker-loop.log'
$OutLog = Join-Path $LogDir 'scheduler-worker.log'
$ErrLog = Join-Path $LogDir 'scheduler-worker-error.log'

New-Item -ItemType Directory -Force -Path $LogDir | Out-Null

Push-Location $ProjectRoot

while ($true) {
    Add-Content -Path $LoopLog -Value "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] Scheduler worker starting..."

    & php $ArtisanPath schedule:work 1>> $OutLog 2>> $ErrLog

    Add-Content -Path $LoopLog -Value "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] Scheduler worker stopped. Restarting in 5 seconds..."
    Start-Sleep -Seconds 5
}
