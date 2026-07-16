# First wordpress.org SVN upload (trunk + tags/1.0.0 + optional assets).
# Prompts for SVN password; never echoes it. Username: kevpress88
$ErrorActionPreference = 'Stop'
$svn = Join-Path $PSScriptRoot '..\tools\svn\bin\svn.exe' | Resolve-Path
$wc = Join-Path $PSScriptRoot '..\.svn-wporg' | Resolve-Path
$assetsSrc = Join-Path $PSScriptRoot '..\assets\wporg' | Resolve-Path

Write-Host ''
Write-Host 'wordpress.org SVN first upload'
Write-Host 'Plugin: pixelparade-smart-purge-for-breeze-cache'
Write-Host 'Username: kevpress88'
Write-Host 'Working copy:' $wc
Write-Host ''
Write-Host 'Enter your wordpress.org SVN password (not your website login password).'
Write-Host 'Set it at: https://profiles.wordpress.org/ → Account → SVN password'
Write-Host ''

$secure = Read-Host 'SVN password' -AsSecureString
$bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
try {
	$pass = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($bstr)
} finally {
	[Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr) | Out-Null
}

if ([string]::IsNullOrWhiteSpace($pass)) {
	Write-Host 'ERROR: empty password; aborting.' -ForegroundColor Red
	exit 1
}

Set-Location $wc

Write-Host ''
Write-Host '=== Committing trunk ===' -ForegroundColor Cyan
& $svn commit trunk `
	-m 'Initial trunk commit — PixelParade Smart Purge for Breeze Cache 1.0.0' `
	--username kevpress88 `
	--password $pass `
	--non-interactive `
	--no-auth-cache
$trunkExit = $LASTEXITCODE
if ($trunkExit -ne 0) {
	Write-Host "ERROR: trunk commit failed (exit $trunkExit)" -ForegroundColor Red
	$pass = $null
	exit $trunkExit
}

Write-Host ''
Write-Host '=== Tagging 1.0.0 ===' -ForegroundColor Cyan
& $svn update --username kevpress88 --password $pass --non-interactive --no-auth-cache | Out-Null
$tagPath = Join-Path $wc 'tags\1.0.0'
if (-not (Test-Path $tagPath)) {
	& $svn copy trunk tags/1.0.0 --username kevpress88 --password $pass --non-interactive --no-auth-cache
	if ($LASTEXITCODE -ne 0) {
		Write-Host "ERROR: svn copy failed (exit $LASTEXITCODE)" -ForegroundColor Red
		$pass = $null
		exit $LASTEXITCODE
	}
	& $svn commit tags `
		-m 'Tagging version 1.0.0' `
		--username kevpress88 `
		--password $pass `
		--non-interactive `
		--no-auth-cache
	$tagExit = $LASTEXITCODE
	if ($tagExit -ne 0) {
		Write-Host "ERROR: tag commit failed (exit $tagExit)" -ForegroundColor Red
		$pass = $null
		exit $tagExit
	}
} else {
	Write-Host 'tags/1.0.0 already exists; skipping copy.'
}

Write-Host ''
Write-Host '=== Committing directory assets (icons/banners) ===' -ForegroundColor Cyan
$pngs = @(
	'icon-128x128.png',
	'icon-256x256.png',
	'banner-772x250.png',
	'banner-1544x500.png'
)
foreach ($f in $pngs) {
	$src = Join-Path $assetsSrc $f
	$dst = Join-Path $wc "assets\$f"
	if (Test-Path $src) {
		Copy-Item -Path $src -Destination $dst -Force
	} else {
		Write-Host "WARN: missing $src"
	}
}
& $svn add assets\*.png --force 2>$null
& $svn commit assets `
	-m 'Add plugin directory icons and banners.' `
	--username kevpress88 `
	--password $pass `
	--non-interactive `
	--no-auth-cache
$assetsExit = $LASTEXITCODE

$pass = $null
[GC]::Collect()

Write-Host ''
if ($assetsExit -eq 0) {
	Write-Host 'SUCCESS: trunk, tags/1.0.0, and assets committed.' -ForegroundColor Green
} else {
	Write-Host 'PARTIAL: trunk/tag OK; assets commit failed (exit ' $assetsExit ')' -ForegroundColor Yellow
}
Write-Host 'Public URL: https://wordpress.org/plugins/pixelparade-smart-purge-for-breeze-cache'
Write-Host 'Press Enter to close...'
[void][Console]::ReadLine()
exit 0
