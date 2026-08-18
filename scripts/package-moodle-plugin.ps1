[CmdletBinding()]
param(
    [string] $OutputDirectory
)

$ErrorActionPreference = 'Stop'
$repositoryRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$pluginRoot = Join-Path $repositoryRoot 'local\h5pchapteraccess'

if ([string]::IsNullOrWhiteSpace($OutputDirectory)) {
    $OutputDirectory = Join-Path $repositoryRoot 'release'
}

$requiredFiles = @(
    'version.php',
    'lang\en\local_h5pchapteraccess.php',
    'lang\pt_br\local_h5pchapteraccess.php',
    'amd\build\bridge.min.js',
    'amd\build\bridge_helpers.min.js',
    'db\hooks.php',
    'db\services.php'
)

foreach ($relativePath in $requiredFiles) {
    $absolutePath = Join-Path $pluginRoot $relativePath
    if (!(Test-Path -LiteralPath $absolutePath -PathType Leaf)) {
        throw "Required production file is missing: $relativePath"
    }
}

if (Test-Path -LiteralPath (Join-Path $pluginRoot 'node_modules')) {
    throw 'node_modules must not be included inside the Moodle plugin directory.'
}

$versionContents = Get-Content -LiteralPath (Join-Path $pluginRoot 'version.php') -Raw -Encoding UTF8
if ($versionContents -notmatch "\`$plugin->release\s*=\s*'([^']+)'\s*;") {
    throw 'Unable to read the plugin release from version.php.'
}
$release = $Matches[1]

New-Item -ItemType Directory -Path $OutputDirectory -Force | Out-Null
$zipPath = Join-Path $OutputDirectory "h5pchapteraccess-$release.zip"
if (Test-Path -LiteralPath $zipPath) {
    Remove-Item -LiteralPath $zipPath -Force
}

Compress-Archive -LiteralPath $pluginRoot -DestinationPath $zipPath -CompressionLevel Optimal

Add-Type -AssemblyName System.IO.Compression.FileSystem
$archive = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
try {
    $entries = @($archive.Entries | ForEach-Object { $_.FullName.Replace('\', '/') })
    foreach ($relativePath in $requiredFiles) {
        $expectedEntry = 'h5pchapteraccess/' + $relativePath.Replace('\', '/')
        if ($expectedEntry -notin $entries) {
            throw "Release ZIP is missing: $expectedEntry"
        }
    }
} finally {
    $archive.Dispose()
}

$hash = (Get-FileHash -LiteralPath $zipPath -Algorithm SHA256).Hash.ToLowerInvariant()
$checksumPath = "$zipPath.sha256"
Set-Content -LiteralPath $checksumPath -Value "$hash  $(Split-Path $zipPath -Leaf)" -Encoding ASCII

Write-Output "Release package: $zipPath"
Write-Output "SHA-256: $hash"
