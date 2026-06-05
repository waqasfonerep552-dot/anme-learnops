param(
    [switch] $Foreground
)

$ProjectRoot = Resolve-Path (Join-Path $PSScriptRoot '..')
$ArtisanPath = Join-Path $ProjectRoot 'artisan'
$LogDir = Join-Path $ProjectRoot 'storage\logs'
$OutLog = Join-Path $LogDir 'scheduler-worker.log'
$ErrLog = Join-Path $LogDir 'scheduler-worker-error.log'

New-Item -ItemType Directory -Force -Path $LogDir | Out-Null

$workerArgs = @(
    $ArtisanPath,
    'schedule:work'
)

if ($Foreground) {
    Push-Location $ProjectRoot
    & php @workerArgs
    $exitCode = $LASTEXITCODE
    Pop-Location
    exit $exitCode
}

$existing = Get-CimInstance Win32_Process |
    Where-Object {
        $_.CommandLine -like "*$ArtisanPath*" -and
        $_.CommandLine -like '*schedule:work*'
    }

if ($existing) {
    $pids = ($existing | Select-Object -ExpandProperty ProcessId) -join ', '
    Write-Host "Scheduler worker already running. PID(s): $pids"
    exit 0
}

$process = Start-Process `
    -FilePath 'php' `
    -ArgumentList $workerArgs `
    -WorkingDirectory $ProjectRoot `
    -WindowStyle Hidden `
    -RedirectStandardOutput $OutLog `
    -RedirectStandardError $ErrLog `
    -PassThru

Write-Host "Scheduler worker started. PID: $($process.Id)"
Write-Host "Logs: $OutLog"
