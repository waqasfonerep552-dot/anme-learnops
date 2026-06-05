$TaskName = 'ANME LearnOps Queue Worker'
$ScriptPath = Join-Path $PSScriptRoot 'start-queue-worker.ps1'
$LoopScriptPath = Join-Path $PSScriptRoot 'run-queue-worker-loop.ps1'
$Argument = "-NoProfile -ExecutionPolicy Bypass -File `"$ScriptPath`" -Foreground"

try {
    $action = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument $Argument
    $trigger = New-ScheduledTaskTrigger -AtLogOn
    $settings = New-ScheduledTaskSettingsSet `
        -AllowStartIfOnBatteries `
        -DontStopIfGoingOnBatteries `
        -ExecutionTimeLimit (New-TimeSpan -Days 30) `
        -MultipleInstances IgnoreNew `
        -RestartCount 999 `
        -RestartInterval (New-TimeSpan -Minutes 1)

    Register-ScheduledTask `
        -TaskName $TaskName `
        -Action $action `
        -Trigger $trigger `
        -Settings $settings `
        -Description 'Runs ANME LearnOps queue worker for Moodle fulfilment jobs.' `
        -Force `
        -ErrorAction Stop | Out-Null

    Start-ScheduledTask -TaskName $TaskName -ErrorAction Stop

    Write-Host "Installed and started scheduled task: $TaskName"
    Write-Host "Check status: Get-ScheduledTask -TaskName '$TaskName'"
    exit 0
} catch {
    Write-Host "Scheduled Task install failed, using current-user Startup fallback."
    Write-Host $_.Exception.Message
}

$StartupDir = [Environment]::GetFolderPath('Startup')
$StartupFile = Join-Path $StartupDir 'ANME-LearnOps-Queue-Worker.cmd'
$StartupCommand = "@echo off`r`npowershell.exe -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File `"$LoopScriptPath`"`r`n"

Set-Content -Path $StartupFile -Value $StartupCommand -Encoding ASCII

$existingLoop = Get-CimInstance Win32_Process |
    Where-Object { $_.CommandLine -like "*$LoopScriptPath*" }

if (! $existingLoop) {
    Start-Process `
        -FilePath 'powershell.exe' `
        -ArgumentList @('-NoProfile', '-ExecutionPolicy', 'Bypass', '-WindowStyle', 'Hidden', '-File', $LoopScriptPath) `
        -WindowStyle Hidden
}

Write-Host "Installed Startup fallback: $StartupFile"
Write-Host "Worker loop starts on Windows login and restarts queue worker if it stops."
