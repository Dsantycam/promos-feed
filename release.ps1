# =====================================================================
#  Publicar una nueva versión de Promos Feed en GitHub (release)
#  Autor: Santiago Camacho
#
#  Uso:
#    1. Sube el número de versión en promos-feed.php (línea "Version:").
#    2. Ejecuta:  ./release.ps1
#
#  Construye el .zip del plugin (carpeta interna "promos-feed", con rutas
#  compatibles con WordPress) y crea la release en GitHub. WordPress
#  detectará la nueva versión y ofrecerá la actualización con un clic.
#
#  Nota: se genera el zip con .NET creando las entradas con "/" explícitas.
#  NO uses Compress-Archive: en Windows PowerShell escribe rutas con "\"
#  que rompen la instalación en WordPress ("El archivo del plugin no existe").
# =====================================================================

$ErrorActionPreference = 'Stop'
$root = $PSScriptRoot

# 1) Leer la versión desde la cabecera del plugin.
$main = Join-Path $root 'promos-feed.php'
$match = Select-String -Path $main -Pattern 'Version:\s*([0-9.]+)' | Select-Object -First 1
if (-not $match) { throw 'No se pudo leer la versión de promos-feed.php' }
$version = $match.Matches[0].Groups[1].Value
$tag = "v$version"
Write-Host "Versión detectada: $tag" -ForegroundColor Cyan

# 2) Construir el .zip (rutas con "/" -> compatible con WordPress).
Add-Type -AssemblyName System.IO.Compression | Out-Null
Add-Type -AssemblyName System.IO.Compression.FileSystem | Out-Null

$zipPath = Join-Path $root 'promos-feed.zip'
$folder  = 'promos-feed'
$exclude = @('.git', 'build', 'release.ps1', 'promos-feed.zip')
$base    = (Resolve-Path $root).Path.TrimEnd('\')

$fs  = [System.IO.File]::Open($zipPath, [System.IO.FileMode]::Create)
$zip = New-Object System.IO.Compression.ZipArchive($fs, [System.IO.Compression.ZipArchiveMode]::Create)
$n = 0
Get-ChildItem -Path $root -Recurse -File -Force | ForEach-Object {
    $rel = $_.FullName.Substring($base.Length + 1) -replace '\\', '/'
    $top = ($rel -split '/')[0]
    if ($exclude -contains $top) { return }
    $entry  = $zip.CreateEntry("$folder/$rel", [System.IO.Compression.CompressionLevel]::Optimal)
    $stream = $entry.Open()
    $bytes  = [System.IO.File]::ReadAllBytes($_.FullName)
    $stream.Write($bytes, 0, $bytes.Length)
    $stream.Dispose()
    $n++
}
$zip.Dispose()
$fs.Dispose()
Write-Host "ZIP construido: $zipPath ($n archivos)" -ForegroundColor Green

# 3) Crear la release en GitHub y adjuntar el .zip.
Write-Host "Publicando release $tag en GitHub..." -ForegroundColor Cyan
gh release create $tag $zipPath --title $tag --generate-notes
Write-Host "¡Listo! Release $tag publicada. WordPress ya podrá actualizar." -ForegroundColor Green
