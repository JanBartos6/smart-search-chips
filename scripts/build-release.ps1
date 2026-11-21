param(
    # Kept for backwards compatibility with older npm scripts; there is only one package now.
    [ValidateSet('all', 'wporg')]
    [string] $Target = 'all'
)

$ErrorActionPreference = 'Stop'

$Slug = 'smart-search-chips'
$Root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$BuildRoot = Join-Path $Root 'build'
$WpOrgDir = Join-Path $BuildRoot "$Slug-wporg\$Slug"
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
        [string] $Destination
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
        if (Get-Command zip -ErrorAction SilentlyContinue) {
            & zip -qr $ZipPath $Slug
        } else {
            Compress-Archive -Path $Slug -DestinationPath $ZipPath -Force
        }
    } finally {
        Pop-Location
    }
}

function Remove-WpOrgExcludedFiles {
    param([string] $PluginDir)

    $paths = @(
        'vendor',
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

function Build-WpOrg {
    $parent = Split-Path -Parent $WpOrgDir
    New-Item -ItemType Directory -Path $parent -Force | Out-Null
    Copy-PluginTree -Destination $WpOrgDir
    Remove-WpOrgExcludedFiles -PluginDir $WpOrgDir
    Compress-Plugin -ParentDir $parent -ZipPath $WpOrgZip
}

Reset-BuildRoot

Build-WpOrg
Write-Host "Built WordPress.org package: $WpOrgZip"
