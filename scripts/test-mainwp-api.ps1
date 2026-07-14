# Test MainWP REST API v2 Bearer token.
# Usage:
#   1. Copy .env.mainwp.example to .env.mainwp.local and set MAINWP_TOKEN
#   2. powershell -ExecutionPolicy Bypass -File scripts/test-mainwp-api.ps1

$ErrorActionPreference = 'Stop'
$envFile = Join-Path (Join-Path $PSScriptRoot '..') '.env.mainwp.local'
$envFile = (Resolve-Path $envFile -ErrorAction SilentlyContinue)
if (-not $envFile) {
	$envFile = Join-Path (Get-Location) '.env.mainwp.local'
}
if (Test-Path $envFile) {
	Get-Content $envFile | ForEach-Object {
		if ($_ -match '^\s*([^#=]+)=(.*)$') {
			Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim()
		}
	}
}

$url = if ($env:MAINWP_URL) { $env:MAINWP_URL.TrimEnd('/') } else { 'https://mainwp.pixelparade.co' }
$token = $env:MAINWP_TOKEN
if (-not $token) {
	Write-Error 'Set MAINWP_TOKEN in .env.mainwp.local (see .env.mainwp.example and docs/MAINWP_API.md).'
}

$endpoint = "$url/wp-json/mainwp/v2/sites?per_page=1"
Write-Host "GET $endpoint"

try {
	$response = Invoke-RestMethod -Uri $endpoint -Headers @{ Authorization = "Bearer $token" }
	$count = @($response).Count
	Write-Host "OK - API key works. Sample response: $count site(s) on first page."
	if ($count -gt 0 -and $response[0].url) {
		Write-Host "First site: $($response[0].url)"
	}
	exit 0
}
catch {
	$status = $null
	$body = $_.ErrorDetails.Message
	if ($_.Exception.Response) {
		$status = [int]$_.Exception.Response.StatusCode
	}
	Write-Host "FAILED - HTTP $status"
	if ($body) { Write-Host $body }
	if ($body -match 'authentication_disabled_key') {
		Write-Host ''
		Write-Host 'Create the first API key in MainWP Dashboard > API Access > API Keys (see docs/MAINWP_API.md).'
	}
	exit 1
}
