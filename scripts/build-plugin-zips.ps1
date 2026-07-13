# Build agency (MainWP) and wordpress.org plugin zips. Run from repo root.
# Requires: PowerShell 5+ (Expand-Archive / Compress-Archive) or rsync on PATH (Git Bash).

param(
    [string]$PluginSlug = 'smart-purge-for-breeze-cache'
)

$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot
Set-Location $repoRoot

$agencyZip = "${PluginSlug}.zip"
$wporgApprovedSlug = 'pixelparade-smart-purge-for-breeze-cache'
$wporgZip = "${wporgApprovedSlug}-wporg.zip"
$wporgLegacyZip = "${PluginSlug}-wporg.zip"
$buildRoot = Join-Path $repoRoot 'build'

function Read-Distignore {
    param([string]$Path)
    Get-Content $Path | Where-Object { $_ -and $_ -notmatch '^\s*#' }
}

function Test-Excluded {
    param(
        [string]$RelativePath,
        [string[]]$Patterns
    )
    $normalized = $RelativePath -replace '\\', '/'
    foreach ($pattern in $Patterns) {
        $p = $pattern.TrimEnd('/')
        if ($normalized -eq $p -or $normalized -like "$p /*".Replace(' /*', '/*') -or $normalized -like "$p/*") {
            return $true
        }
        if ($pattern -like '*.*' -and $normalized -like $pattern) {
            return $true
        }
    }
    return $false
}

function Copy-PluginTree {
    param(
        [string]$Destination,
        [string[]]$ExtraExcludes = @()
    )
    if (Test-Path $Destination) {
        Remove-Item $Destination -Recurse -Force
    }
    New-Item -ItemType Directory -Path $Destination -Force | Out-Null

    $baseExcludes = Read-Distignore (Join-Path $repoRoot '.distignore')
    $allExcludes = $baseExcludes + $ExtraExcludes + @('build')

    Get-ChildItem -Path $repoRoot -Recurse -File | ForEach-Object {
        $relative = $_.FullName.Substring($repoRoot.Length + 1)
        if (Test-Excluded $relative $allExcludes) {
            return
        }
        $target = Join-Path $Destination $relative
        $targetDir = Split-Path $target -Parent
        if (-not (Test-Path $targetDir)) {
            New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
        }
        Copy-Item $_.FullName $target -Force
    }
}

function Apply-WporgTransform {
    param(
        [string]$WporgParent,
        [string]$AgencySlug,
        [string]$TargetSlug,
        [string]$TargetVersion = '1.0.0'
    )

    $srcDir = Join-Path $WporgParent $AgencySlug
    $destDir = Join-Path $WporgParent $TargetSlug

    if (-not (Test-Path $srcDir)) {
        throw "Missing wporg build folder: $srcDir"
    }

    Move-Item -Path $srcDir -Destination $destDir
    Move-Item -Path (Join-Path $destDir "$AgencySlug.php") -Destination (Join-Path $destDir "$TargetSlug.php")

    Get-ChildItem -Path $destDir -Recurse -File | Where-Object {
        $_.Extension -in '.php', '.txt', '.js'
    } | ForEach-Object {
        $content = [System.IO.File]::ReadAllText($_.FullName)
        $content = $content -replace [regex]::Escape($AgencySlug), $TargetSlug
        [System.IO.File]::WriteAllText($_.FullName, $content)
    }

    $mainFile = Join-Path $destDir "$TargetSlug.php"
    $mainContent = [System.IO.File]::ReadAllText($mainFile)
    $mainContent = [regex]::Replace($mainContent, '(?m)^ \* Version: .*', " * Version: $TargetVersion")
    $mainContent = [regex]::Replace($mainContent, "define\('PPSPB_VERSION', '[^']*'\)", "define('PPSPB_VERSION', '$TargetVersion')")
    [System.IO.File]::WriteAllText($mainFile, $mainContent)

    $readmeWporg = Join-Path $repoRoot 'readme.wporg.txt'
    if (Test-Path $readmeWporg) {
        Copy-Item $readmeWporg (Join-Path $destDir 'readme.txt') -Force
    }

    Write-Host "Wporg transform complete: $TargetSlug ($TargetVersion)"
}

if (Test-Path $buildRoot) {
    Remove-Item $buildRoot -Recurse -Force
}

$wporgExcludes = Read-Distignore (Join-Path $repoRoot '.distignore.wporg')
$agencyDir = Join-Path $buildRoot "agency\$PluginSlug"
$wporgParent = Join-Path $buildRoot 'wporg'
$wporgStage = Join-Path $wporgParent $PluginSlug

Copy-PluginTree -Destination $agencyDir
Copy-PluginTree -Destination $wporgStage -ExtraExcludes $wporgExcludes
Apply-WporgTransform -WporgParent $wporgParent -AgencySlug $PluginSlug -TargetSlug $wporgApprovedSlug
$wporgDir = Join-Path $wporgParent $wporgApprovedSlug

function New-PluginZip {
    param(
        [string]$SourceDir,
        [string]$ZipPath,
        [string]$EntryPrefix
    )
    Add-Type -AssemblyName System.IO.Compression
    Add-Type -AssemblyName System.IO.Compression.FileSystem

    if (Test-Path $ZipPath) {
        Remove-Item $ZipPath -Force
    }

    $zip = [System.IO.Compression.ZipFile]::Open($ZipPath, [System.IO.Compression.ZipArchiveMode]::Create)
    try {
        Get-ChildItem -Path $SourceDir -Recurse -File | ForEach-Object {
            $relative = $_.FullName.Substring($SourceDir.Length + 1) -replace '\\', '/'
            $entryName = "$EntryPrefix/$relative"
            [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
                $zip,
                $_.FullName,
                $entryName,
                [System.IO.Compression.CompressionLevel]::Optimal
            )
        }
    }
    finally {
        $zip.Dispose()
    }
}

New-PluginZip -SourceDir $agencyDir -ZipPath $agencyZip -EntryPrefix $PluginSlug
New-PluginZip -SourceDir $wporgDir -ZipPath $wporgZip -EntryPrefix $wporgApprovedSlug
# Compatibility alias for older docs/scripts expecting *-wporg.zip with agency slug name.
Copy-Item $wporgZip $wporgLegacyZip -Force

Write-Host "Done."
Write-Host "  Agency (MainWP / GitHub Releases):     $agencyZip"
Write-Host "  wordpress.org upload (pixelparade slug): $wporgZip"
Write-Host "  Compatibility alias (same bytes):        $wporgLegacyZip"
