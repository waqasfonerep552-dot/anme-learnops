$ProjectRoot = Resolve-Path (Join-Path $PSScriptRoot '..')
$ArtisanPath = Join-Path $ProjectRoot 'artisan'
$LogDir = Join-Path $ProjectRoot 'storage\logs'
$LoopLog = Join-Path $LogDir 'queue-worker-loop.log'
$OutLog = Join-Path $LogDir 'queue-worker.log'
$ErrLog = Join-Path $LogDir 'queue-worker-error.log'

New-Item -ItemType Directory -Force -Path $LogDir | Out-Null

Push-Location $ProjectRoot

while ($true) {
    Add-Content -Path $LoopLog -Value "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] Queue worker starting..."

    & php $ArtisanPath queue:work --tries=3 --sleep=3 --backoff=10 --timeout=120 1>> $OutLog 2>> $ErrLog

    Add-Content -Path $LoopLog -Value "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] Queue worker stopped. Restarting in 5 seconds..."
    Start-Sleep -Seconds 5
}
