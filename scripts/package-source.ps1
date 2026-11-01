param([string]$OutputPath = (Join-Path (Split-Path $PSScriptRoot -Parent) '../handover/recipe-feature06.zip'))
$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression.FileSystem
$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$output = [IO.Path]::GetFullPath($OutputPath)
if (Test-Path -LiteralPath $output) { throw 'Output already exists. Choose a new zip name to preserve the previous snapshot.' }
New-Item -ItemType Directory -Force -Path (Split-Path $output) | Out-Null
$files = @()
foreach ($folder in @('app','bootstrap','config','database','lang','public','resources','routes','tests','scripts','docs')) {
    $files += Get-ChildItem -LiteralPath (Join-Path $root $folder) -Recurse -File -Force | Where-Object {
        $_.FullName -notmatch '\\bootstrap\\cache\\(?!\.gitignore$)' -and
        $_.FullName -notmatch '\\public\\(?:hot$|storage\\)' -and
        $_.Extension -notin @('.log', '.zip')
    }
}
$files += Get-ChildItem -LiteralPath (Join-Path $root 'storage') -Recurse -File -Force -Filter '.gitignore'
foreach ($name in @('artisan','composer.json','composer.lock','package.json','package-lock.json','vite.config.js','phpunit.xml','.env.example','.env.testing.example','.gitignore','.gitattributes','.editorconfig','README.md')) {
    $files += Get-Item -LiteralPath (Join-Path $root $name) -Force
}
if (@($files | Where-Object { $_.Name -match '^\.env' -and $_.Name -notin @('.env.example','.env.testing.example') }).Count) {
    throw 'Refusing to package a real environment file.'
}
$zip = [IO.Compression.ZipFile]::Open($output, 'Create')
try {
    foreach ($file in ($files | Sort-Object FullName -Unique)) {
        $relative = $file.FullName.Substring($root.Length + 1).Replace('\','/')
        [IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $file.FullName, $relative, 'Optimal') | Out-Null
    }
} finally { $zip.Dispose() }
$hash = (Get-FileHash -LiteralPath $output -Algorithm SHA256).Hash
[IO.File]::WriteAllText($output + '.sha256', $hash + '  ' + [IO.Path]::GetFileName($output) + [Environment]::NewLine)
Write-Output "Package: $output"
Write-Output "SHA256: $hash"
