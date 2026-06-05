param(
    [switch] $Foreground
)

$ProjectRoot = Resolve-Path (Join-Path $PSScriptRoot '..')
$ArtisanPath = Join-Path $ProjectRoot 'artisan'
$LogDir = Join-Path $ProjectRoot 'storage\logs'
$OutLog = Join-Path $LogDir 'queue-worker.log'
$ErrLog = Join-Path $LogDir 'queue-worker-error.log'

New-Item -ItemType Directory -Force -Path $LogDir | Out-Null

$workerArgs = @(
    $ArtisanPath,
    'queue:work',
    '--tries=3',
    '--sleep=3',
    '--backoff=10',
    '--timeout=120'
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
        $_.CommandLine -like '*queue:work*'
    }

if ($existing) {
    $pids = ($existing | Select-Object -ExpandProperty ProcessId) -join ', '
    Write-Host "Queue worker already running. PID(s): $pids"
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

Write-Host "Queue worker started. PID: $($process.Id)"
Write-Host "Logs: $OutLog"
