# Set GitHub Actions secrets for PixelParade/breeze-smart-purge-plugin
# Requires: gh auth with org access to PixelParade (fine-grained PAT, org authorized)
$ErrorActionPreference = 'Stop'

$repo = 'PixelParade/breeze-smart-purge-plugin'
$envFile = Join-Path (Split-Path $PSScriptRoot -Parent) '.env.deploy.local'
$keyPath = Join-Path $env:USERPROFILE '.ssh\breeze-smart-purge-deploy'

if (-not (Test-Path $envFile)) {
    Write-Error "Missing $envFile"
}
Get-Content $envFile | ForEach-Object {
    if ($_ -match '^\s*([^#=]+)=(.*)$') {
        Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim()
    }
}

if (-not (Test-Path $keyPath)) {
    Write-Host "Generating deploy key at $keyPath"
    ssh-keygen -t ed25519 -f $keyPath -N '""' -C 'breeze-smart-purge-github-actions'
}

$privateKey = Get-Content $keyPath -Raw
$publicKey = Get-Content "$keyPath.pub" -Raw

Write-Host "Add this public key to Cloudways SSH keys for staging:"
Write-Host $publicKey
Write-Host ""
Write-Host "Setting GitHub Actions secrets on $repo ..."

gh secret set STAGING_SSH_HOST --repo $repo --body $env:STAGING_SSH_HOST
gh secret set STAGING_SSH_USER --repo $repo --body $env:STAGING_SSH_USER
gh secret set STAGING_SSH_KEY --repo $repo --body $privateKey

Write-Host "Done. Verify:"
gh secret list -R $repo
Write-Host ""
Write-Host "Next: push to main, then:"
Write-Host "  gh run list -R $repo --workflow deploy-staging.yml"
