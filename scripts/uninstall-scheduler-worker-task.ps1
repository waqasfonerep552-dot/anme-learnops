$TaskName = 'ANME LearnOps Scheduler'
$StartupFile = Join-Path ([Environment]::GetFolderPath('Startup')) 'ANME-LearnOps-Scheduler.cmd'

if (Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue) {
    Stop-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
    Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
    Write-Host "Removed scheduled task: $TaskName"
} else {
    Write-Host "Scheduled task not found: $TaskName"
}

if (Test-Path $StartupFile) {
    Remove-Item -LiteralPath $StartupFile -Force
    Write-Host "Removed Startup fallback: $StartupFile"
}
