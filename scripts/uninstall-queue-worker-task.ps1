$TaskName = 'ANME LearnOps Queue Worker'
$StartupFile = Join-Path ([Environment]::GetFolderPath('Startup')) 'ANME-LearnOps-Queue-Worker.cmd'
$LoopScriptPath = Join-Path $PSScriptRoot 'run-queue-worker-loop.ps1'

if (Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue) {
    Stop-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
    Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
    Write-Host "Removed scheduled task: $TaskName"
} else {
    Write-Host "Scheduled task not found: $TaskName"
}

if (Test-Path $StartupFile) {
    Remove-Item $StartupFile -Force
    Write-Host "Removed Startup fallback: $StartupFile"
}

Get-CimInstance Win32_Process |
    Where-Object { $_.CommandLine -like "*$LoopScriptPath*" } |
    ForEach-Object {
        Stop-Process -Id $_.ProcessId -Force
        Write-Host "Stopped worker loop PID: $($_.ProcessId)"
    }
