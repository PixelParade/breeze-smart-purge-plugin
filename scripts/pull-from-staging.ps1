# Pull breeze-smart-purge plugin from staging (SCP with plink fallback)
$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent

$envFile = Join-Path $root '.env.deploy.local'
if (-not (Test-Path $envFile)) {
    Write-Error "Missing $envFile — copy .env.deploy.example and fill credentials."
}

Get-Content $envFile | ForEach-Object {
    if ($_ -match '^\s*([^#=]+)=(.*)$') {
        Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim()
    }
}

$remote = $env:STAGING_REMOTE_PLUGIN_PATH.TrimEnd('/')
$hostSpec = "$($env:STAGING_SSH_USER)@$($env:STAGING_SSH_HOST)"
$password = $env:STAGING_SSH_PASSWORD

Write-Host "Pulling from ${hostSpec}:${remote}/"
Write-Host "Target: $root"

function Invoke-PlinkPull {
    $plink = Get-Command plink -ErrorAction SilentlyContinue
    if (-not $plink) {
        throw 'plink not found in PATH. Install PuTTY or add plink.exe to PATH.'
    }

    $phpPath = Join-Path $root 'smart-purge-for-breeze-cache.php'
    $readmePath = Join-Path $root 'readme.txt'

    Write-Host 'Using plink fallback (cat + base64)...'

    $php = & $plink.Source -batch -pw $password $hostSpec "cat '$remote/smart-purge-for-breeze-cache.php'"
    if ($LASTEXITCODE -ne 0) { throw "plink cat failed (exit $LASTEXITCODE)" }
    [System.IO.File]::WriteAllText($phpPath, ($php -join "`n"), [System.Text.UTF8Encoding]::new($false))

    $b64Lines = & $plink.Source -batch -pw $password $hostSpec "base64 '$remote/readme.txt'"
    if ($LASTEXITCODE -ne 0) { throw "plink base64 failed (exit $LASTEXITCODE)" }
    $bytes = [Convert]::FromBase64String(($b64Lines -join ''))
    [System.IO.File]::WriteAllBytes($readmePath, $bytes)

    Write-Host "Wrote $phpPath ($((Get-Item $phpPath).Length) bytes)"
    Write-Host "Wrote $readmePath ($((Get-Item $readmePath).Length) bytes)"
}

$pscp = Get-Command pscp -ErrorAction SilentlyContinue
if ($pscp -and $password) {
    try {
        & $pscp.Source -batch -pw $password -r "${hostSpec}:${remote}/*" $root
        if ($LASTEXITCODE -eq 0) {
            Write-Host 'Done (pscp).'
            return
        }
        Write-Warning "pscp failed (exit $LASTEXITCODE); trying plink fallback..."
    }
    catch {
        Write-Warning "pscp error: $_; trying plink fallback..."
    }
}

Invoke-PlinkPull
Write-Host 'Done (plink).'
