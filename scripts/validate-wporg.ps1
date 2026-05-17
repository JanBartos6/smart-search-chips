param(
    [string] $PackageDir = ''
)

$ErrorActionPreference = 'Stop'

$Root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$Slug = 'smart-search-chips'

if ([string]::IsNullOrWhiteSpace($PackageDir)) {
    $PackageDir = Join-Path $Root "build\$Slug-wporg\$Slug"
}

if (-not (Test-Path $PackageDir)) {
    throw "WordPress.org package directory not found: $PackageDir"
}

$PackageDir = (Resolve-Path $PackageDir).Path
$failures = New-Object System.Collections.Generic.List[string]

function Get-RelativePath {
    param(
        [string] $BasePath,
        [string] $Path
    )

    $baseFull = [System.IO.Path]::GetFullPath($BasePath).TrimEnd('\') + '\'
    $pathFull = [System.IO.Path]::GetFullPath($Path)

    if ($pathFull.StartsWith($baseFull, [System.StringComparison]::OrdinalIgnoreCase)) {
        return $pathFull.Substring($baseFull.Length)
    }

    return $pathFull
}

function Add-Failure {
    param(
        [string] $Message
    )

    $script:failures.Add($Message) | Out-Null
}

$requiredFiles = @(
    'smart-search-chips.php',
    'readme.txt',
    'includes\class-edition.php'
)

foreach ($path in $requiredFiles) {
    $fullPath = Join-Path $PackageDir $path
    if (-not (Test-Path $fullPath)) {
        Add-Failure "Required file is missing: $path"
    }
}

$forbiddenPaths = @(
    'vendor',
    'includes\class-license.php',
    'includes\class-ai-tagger__premium_only.php',
    'includes\class-analytics__premium_only.php',
    'includes\class-chip-rules__premium_only.php',
    'includes\class-range-filters__premium_only.php',
    'assets\js\admin-ai.js',
    'assets\js\range-filters__premium_only.js',
    'assets\css\range-filters__premium_only.css',
    'assets\css\analytics.css',
    'languages\smart-search-chips.pot'
)

foreach ($path in $forbiddenPaths) {
    $fullPath = Join-Path $PackageDir $path
    if (Test-Path $fullPath) {
        Add-Failure "Forbidden path exists: $path"
    }
}

$patterns = [ordered]@{
    'fs_dynamic_init'                  = 'Freemius bootstrap is present.'
    'Freemius'                         = 'Freemius copy is present.'
    'can_use_premium_code'             = 'Premium-code gate is present.'
    '__premium_only'                   = 'Freemius premium-only marker is present.'
    "'is_premium'"                     = 'Freemius premium config is present.'
    "'has_paid_plans'"                 = 'Freemius paid-plan config is present.'
    'SSCHP_Analytics'                  = 'Analytics module reference is present in the wp.org build.'
    'SSCHP_AI_Tagger'                  = 'AI prompt module reference is present in the wp.org build.'
    'SSCHP_License'                    = 'License-gating class reference is present in the wp.org build.'
    'license key'                      = 'License-key wording is present.'
    'trial'                            = 'Trial wording is present.'
    'quota'                            = 'Quota wording is present.'
    'if ( true )'                      = 'Build-stripping placeholder condition is present.'
    'isset( <?php'                     = 'Corrupted PHP fragment is present.'
    '[ssc_search]'                     = 'Old [ssc_search] shortcode is present.'
    'Free: max 5'                      = 'Old free chip cap wording is present.'
    'free version limit'               = 'Old free version cap comment is present.'
    'Upgrade to Pro to unlock'         = 'Unlock wording suggests restricted included functionality.'
    'becomes available in Pro'         = 'Locked-feature wording is present.'
    '$_REQUEST'                        = 'Raw $_REQUEST access is present.'
    '$_GET['                           = 'Raw $_GET array access is present.'
    '$_SERVER['                        = 'Raw $_SERVER array access is present.'
    'echo $markup'                     = 'Raw archive markup echo is present.'
    'Security.EscapeOutput'            = 'Escaping suppression is present.'
    "wp_unslash( `$_POST['active_tags'] )" = 'active_tags is unslashed without array sanitization.'
}

$files = Get-ChildItem -LiteralPath $PackageDir -Recurse -File |
    Where-Object {
        $_.Extension -in @('.php', '.txt', '.js', '.css', '.md', '.pot', '.po', '.mo')
    }

foreach ($file in $files) {
    $bytes = [System.IO.File]::ReadAllBytes($file.FullName)
    if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
        $relative = Get-RelativePath -BasePath $PackageDir -Path $file.FullName
        Add-Failure "UTF-8 BOM is present. ${relative}"
    }
}

foreach ($entry in $patterns.GetEnumerator()) {
    $matches = $files | Select-String -Pattern $entry.Key -SimpleMatch -CaseSensitive:$false
    foreach ($match in $matches) {
        $relative = Get-RelativePath -BasePath $PackageDir -Path $match.Path
        Add-Failure "$($entry.Value) ${relative}:$($match.LineNumber)"
    }
}

$pluginVersion = $null
$stableTag = $null

$mainFile = Join-Path $PackageDir 'smart-search-chips.php'
if (-not (Test-Path $mainFile)) {
    Add-Failure 'Main plugin file is missing.'
} else {
    $main = Get-Content -Raw -LiteralPath $mainFile
    foreach ($required in @(
        "Plugin Name: Smart Search Chips",
        "Text Domain: smart-search-chips",
        "Requires PHP: 7.4"
    )) {
        if ($main -notlike "*$required*") {
            Add-Failure "Main plugin header missing: $required"
        }
    }

    $versionMatch = [regex]::Match($main, '(?im)^\s*\*?\s*Version:\s*([^\r\n]+)')
    if ($versionMatch.Success) {
        $pluginVersion = $versionMatch.Groups[1].Value.Trim()
    } else {
        Add-Failure 'Main plugin header missing: Version'
    }
}

$readmeFile = Join-Path $PackageDir 'readme.txt'
if (-not (Test-Path $readmeFile)) {
    Add-Failure 'readme.txt is missing.'
} else {
    $readme = Get-Content -Raw -LiteralPath $readmeFile
    if ($readme -notlike '*Contributors: honzdev*') {
        Add-Failure 'readme.txt does not list honzdev as contributor.'
    }
    if ($readme -match '\[ssc_search\]') {
        Add-Failure 'readme.txt still references the old [ssc_search] shortcode.'
    }

    $stableMatch = [regex]::Match($readme, '(?im)^Stable tag:\s*([^\r\n]+)')
    if ($stableMatch.Success) {
        $stableTag = $stableMatch.Groups[1].Value.Trim()
    } else {
        Add-Failure 'readme.txt missing Stable tag.'
    }
}

if ($null -ne $pluginVersion -and $null -ne $stableTag -and $pluginVersion -ne $stableTag) {
    Add-Failure "readme.txt Stable tag ($stableTag) does not match plugin header Version ($pluginVersion)."
}

if ($failures.Count -gt 0) {
    Write-Host 'WordPress.org validation failed:' -ForegroundColor Red
    foreach ($failure in $failures) {
        Write-Host " - $failure" -ForegroundColor Red
    }
    exit 1
}

Write-Host "WordPress.org validation passed: $PackageDir" -ForegroundColor Green
