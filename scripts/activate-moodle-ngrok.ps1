$ProjectRoot = Resolve-Path (Join-Path $PSScriptRoot '..')
$EnvPath = Join-Path $ProjectRoot '.env'

$appUrl = ''
Get-Content $EnvPath | ForEach-Object {
    if ($_ -match '^APP_URL=(.+)$') {
        $appUrl = $Matches[1].Trim().Trim('"')
    }
}

if ([string]::IsNullOrWhiteSpace($appUrl)) {
    Write-Error 'APP_URL not found in .env'
    exit 1
}

$academyUrl = $appUrl.TrimEnd('/') + '/academy'
$updates = @{
    'MOODLE_BASE_URL' = $academyUrl
    'MOODLE_REST_URL' = $academyUrl + '/webservice/rest/server.php'
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

Write-Host "Moodle public URL enabled: $academyUrl"
