$ProjectRoot = Resolve-Path (Join-Path $PSScriptRoot '..')
$EnvPath = Join-Path $ProjectRoot '.env'
$updates = @{
    'MOODLE_BASE_URL' = 'http://moodle.test'
    'MOODLE_REST_URL' = 'http://moodle.test/webservice/rest/server.php'
}

$lines = Get-Content $EnvPath
foreach ($key in $updates.Keys) {
    $found = $false
    $lines = $lines | ForEach-Object {
        if ($_ -match "^$([regex]::Escape($key))=") {
            $found = $true
            "$key=$($updates[$key])"
        } else {
            $_
        }
    }
    if (-not $found) {
        $lines += "$key=$($updates[$key])"
    }
}

Set-Content -Path $EnvPath -Value $lines

Push-Location $ProjectRoot
php artisan config:clear
php artisan moodle:check
Pop-Location

Write-Host 'Moodle local URL enabled: http://moodle.test'
