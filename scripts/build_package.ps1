param(
    [ValidateSet('1.0.2')]
    [string]$Version = '1.0.2'
)

$ErrorActionPreference = 'Stop'
$repositoryRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$buildRoot = Join-Path $repositoryRoot 'build'
$stagingRoot = Join-Path $buildRoot "One-Time-Checkout-Offer-for-Phoenix-Cart-$Version"
$zipPath = "$stagingRoot.zip"

if (-not ([IO.Path]::GetFullPath($stagingRoot).StartsWith($repositoryRoot + [IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase))) {
    throw 'Package staging path is outside the repository.'
}
if (Test-Path -LiteralPath $stagingRoot) {
    Remove-Item -LiteralPath $stagingRoot -Recurse -Force
}
if (Test-Path -LiteralPath $zipPath) {
    Remove-Item -LiteralPath $zipPath -Force
}
New-Item -ItemType Directory -Path $stagingRoot -Force | Out-Null
$files = Get-Content -LiteralPath (Join-Path $repositoryRoot 'package-manifest.txt')
foreach ($relativePath in $files) {
    if ([string]::IsNullOrWhiteSpace($relativePath)) {
        continue
    }
    $sourcePath = Join-Path $repositoryRoot $relativePath
    $destinationPath = Join-Path $stagingRoot $relativePath
    if (-not (Test-Path -LiteralPath $sourcePath -PathType Leaf)) {
        throw "Missing package file: $relativePath"
    }
    New-Item -ItemType Directory -Path (Split-Path -Parent $destinationPath) -Force | Out-Null
    Copy-Item -LiteralPath $sourcePath -Destination $destinationPath
}
Compress-Archive -LiteralPath $stagingRoot -DestinationPath $zipPath -CompressionLevel Optimal
Add-Type -AssemblyName System.IO.Compression.FileSystem
$archive = [IO.Compression.ZipFile]::OpenRead($zipPath)
try {
    $actual = @($archive.Entries | Where-Object { $_.Name -ne '' } | ForEach-Object { ($_.FullName -replace '\\', '/') -replace '^[^/]+/', '' } | Sort-Object)
    $expected = @($files | Where-Object { $_.Trim() -ne '' } | Sort-Object)
    if (Compare-Object $expected $actual) {
        throw 'ZIP contents do not match package-manifest.txt.'
    }
    foreach ($entry in $archive.Entries | Where-Object { $_.Name -ne '' }) {
        $relative = ($entry.FullName -replace '\\', '/') -replace '^[^/]+/', ''
        $stream = $entry.Open()
        $sha = [Security.Cryptography.SHA256]::Create()
        try {
            $entryHash = [BitConverter]::ToString($sha.ComputeHash($stream)).Replace('-', '')
            if ($entryHash -ne (Get-FileHash -LiteralPath (Join-Path $repositoryRoot $relative) -Algorithm SHA256).Hash) {
                throw "ZIP file content mismatch: $relative"
            }
        } finally {
            $stream.Dispose()
            $sha.Dispose()
        }
    }
} finally {
    $archive.Dispose()
}
$hash = (Get-FileHash -LiteralPath $zipPath -Algorithm SHA256).Hash.ToLowerInvariant()
Set-Content -LiteralPath "$zipPath.sha256.txt" -Value "$hash  $(Split-Path -Leaf $zipPath)" -Encoding ascii
Write-Output $zipPath
Write-Output $hash
