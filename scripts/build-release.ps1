param(
    [ValidateSet('all', 'pro', 'wporg')]
    [string] $Target = 'all'
)

$ErrorActionPreference = 'Stop'

$Slug = 'smart-search-chips'
$Root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$BuildRoot = Join-Path $Root 'build'
$WpOrgSourceDir = Join-Path $Root 'build-sources\wporg'
$ProDir = Join-Path $BuildRoot "$Slug-pro\$Slug"
$WpOrgDir = Join-Path $BuildRoot "$Slug-wporg\$Slug"
$ProZip = Join-Path $BuildRoot "$Slug-pro.zip"
$WpOrgZip = Join-Path $BuildRoot "$Slug-wporg.zip"

function Assert-ChildPath {
    param(
        [string] $Parent,
        [string] $Child
    )

    $separator = [System.IO.Path]::DirectorySeparatorChar
    $parentFull = [System.IO.Path]::GetFullPath($Parent).TrimEnd([System.IO.Path]::DirectorySeparatorChar, [System.IO.Path]::AltDirectorySeparatorChar) + $separator
    $childFull = [System.IO.Path]::GetFullPath($Child)

    if (-not $childFull.StartsWith($parentFull, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw "Refusing to operate outside $parentFull`: $childFull"
    }
}

function Reset-BuildRoot {
    if (Test-Path $BuildRoot) {
        Assert-ChildPath -Parent $Root -Child $BuildRoot
        Remove-Item -LiteralPath $BuildRoot -Recurse -Force
    }

    New-Item -ItemType Directory -Path $BuildRoot | Out-Null
}

function Copy-PluginTree {
    param(
        [string] $Destination,
        [bool] $IncludeVendor
    )

    New-Item -ItemType Directory -Path $Destination -Force | Out-Null

    $rootFiles = @(
        'smart-search-chips.php',
        'readme.txt',
        'icon.png',
        'screenshot-1.png',
        'screenshot-2.png',
        'screenshot-3.png'
    )

    foreach ($file in $rootFiles) {
        $source = Join-Path $Root $file
        if (Test-Path $source) {
            Copy-Item -LiteralPath $source -Destination (Join-Path $Destination $file) -Force
        }
    }

    foreach ($dir in @('assets', 'blocks', 'includes', 'languages')) {
        $source = Join-Path $Root $dir
        if (Test-Path $source) {
            Copy-Item -LiteralPath $source -Destination (Join-Path $Destination $dir) -Recurse -Force
        }
    }

    if ($IncludeVendor) {
        $vendor = Join-Path $Root 'vendor'
        if (Test-Path $vendor) {
            Copy-Item -LiteralPath $vendor -Destination (Join-Path $Destination 'vendor') -Recurse -Force
        }
    }
}

function Copy-WpOrgSourceOverrides {
    param([string] $PluginDir)

    if (-not (Test-Path $WpOrgSourceDir)) {
        throw "Missing WordPress.org source overrides: $WpOrgSourceDir"
    }

    Get-ChildItem -LiteralPath $WpOrgSourceDir -Force | ForEach-Object {
        $destination = Join-Path $PluginDir $_.Name
        if ($_.PSIsContainer) {
            New-Item -ItemType Directory -Path $destination -Force | Out-Null
            Get-ChildItem -LiteralPath $_.FullName -Force | ForEach-Object {
                Copy-Item -LiteralPath $_.FullName -Destination (Join-Path $destination $_.Name) -Recurse -Force
            }
        } else {
            Copy-Item -LiteralPath $_.FullName -Destination $destination -Force
        }
    }
}

function Compress-Plugin {
    param(
        [string] $ParentDir,
        [string] $ZipPath
    )

    if (Test-Path $ZipPath) {
        Remove-Item -LiteralPath $ZipPath -Force
    }

    Push-Location $ParentDir
    try {
        Compress-Archive -Path $Slug -DestinationPath $ZipPath -Force
    } finally {
        Pop-Location
    }
}

function Remove-WpOrgExcludedFiles {
    param([string] $PluginDir)

    $paths = @(
        'vendor',
        'includes\class-license.php',
        'includes\class-chip-rules__premium_only.php',
        'includes\class-range-filters__premium_only.php',
        'includes\class-ai-tagger__premium_only.php',
        'includes\class-analytics__premium_only.php',
        'assets\js\admin-ai.js',
        'assets\js\range-filters__premium_only.js',
        'assets\css\range-filters__premium_only.css',
        'assets\css\analytics.css',
        'languages\smart-search-chips.pot'
    )

    foreach ($path in $paths) {
        $fullPath = Join-Path $PluginDir $path
        if (Test-Path $fullPath) {
            Assert-ChildPath -Parent $PluginDir -Child $fullPath
            Remove-Item -LiteralPath $fullPath -Recurse -Force
        }
    }
}

function Build-Pro {
    $parent = Split-Path -Parent $ProDir
    New-Item -ItemType Directory -Path $parent -Force | Out-Null
    Copy-PluginTree -Destination $ProDir -IncludeVendor $true
    Compress-Plugin -ParentDir $parent -ZipPath $ProZip
}

function Build-WpOrg {
    $parent = Split-Path -Parent $WpOrgDir
    New-Item -ItemType Directory -Path $parent -Force | Out-Null
    Copy-PluginTree -Destination $WpOrgDir -IncludeVendor $false
    Remove-WpOrgExcludedFiles -PluginDir $WpOrgDir
    Copy-WpOrgSourceOverrides -PluginDir $WpOrgDir
    Remove-WpOrgExcludedFiles -PluginDir $WpOrgDir
    Compress-Plugin -ParentDir $parent -ZipPath $WpOrgZip
}

Reset-BuildRoot

if ($Target -in @('all', 'pro')) {
    Build-Pro
    Write-Host "Built pro package: $ProZip"
}

if ($Target -in @('all', 'wporg')) {
    Build-WpOrg
    Write-Host "Built WordPress.org package: $WpOrgZip"
}
