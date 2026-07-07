# Install staging-only BSP test CPT mu-plugin on breeze-smart-purge.pixelparade.dev.
# Copies scripts/staging/* to wp-content/mu-plugins/ — never runs on MainWP clients.
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

$hostSpec = "$($env:STAGING_SSH_USER)@$($env:STAGING_SSH_HOST)"
$password = $env:STAGING_SSH_PASSWORD
$appRoot = "/home/1305358.cloudwaysapps.com/$($env:STAGING_APP_ID)/public_html"
$muLoaderRemote = "$appRoot/wp-content/mu-plugins/bsp-staging-test-cpt.php"
$muCptRemote = "$appRoot/wp-content/mu-plugins/bsp-staging-test-cpt/bsp-test-cpt.php"

$loaderLocal = Join-Path $root 'scripts/staging/bsp-staging-test-cpt.php'
$cptLocal = Join-Path $root 'scripts/staging/bsp-test-cpt.php'

if (-not (Test-Path $loaderLocal) -or -not (Test-Path $cptLocal)) {
    Write-Error "Missing scripts/staging source files. Run from plugin repo root."
}

Write-Host "Installing staging test CPT mu-plugin to $hostSpec"
Write-Host "  Loader -> $muLoaderRemote"
Write-Host "  CPT    -> $muCptRemote"

$pscp = Get-Command pscp -ErrorAction SilentlyContinue
if (-not $pscp -or -not $password) {
    Write-Error 'pscp and STAGING_SSH_PASSWORD are required (see .env.deploy.local).'
}

& $pscp.Source -batch -pw $password $loaderLocal "${hostSpec}:${muLoaderRemote}"
if ($LASTEXITCODE -ne 0) { throw "pscp loader failed (exit $LASTEXITCODE)" }

$plink = Get-Command plink -ErrorAction SilentlyContinue
if (-not $plink) {
    Write-Error 'plink not found in PATH. Install PuTTY or add plink.exe to PATH.'
}

& $plink.Source -batch -pw $password $hostSpec "mkdir -p '$appRoot/wp-content/mu-plugins/bsp-staging-test-cpt'"
if ($LASTEXITCODE -ne 0) { throw "plink mkdir failed (exit $LASTEXITCODE)" }

& $pscp.Source -batch -pw $password $cptLocal "${hostSpec}:${muCptRemote}"
if ($LASTEXITCODE -ne 0) { throw "pscp CPT failed (exit $LASTEXITCODE)" }

Write-Host 'Done. Verify with:'
Write-Host "  wp post-type list --fields=name,public | grep bsp_test_project"
Write-Host 'Then seed fixtures:'
Write-Host '  wp eval-file wp-content/plugins/smart-purge-for-breeze-cache/scripts/seed-staging-test-fixtures.php'
