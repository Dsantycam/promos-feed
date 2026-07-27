# =====================================================================
#  Publicar una nueva versión de Promos Feed en GitHub (release)
#  Autor: Santiago Camacho
#
#  Uso:
#    1. Sube el número de versión en promos-feed.php (línea "Version:").
#    2. Ejecuta:  ./release.ps1
#
#  El script construye el .zip del plugin (con la carpeta "promos-feed"
#  dentro) y crea la release en GitHub. WordPress detectará la nueva
#  versión y ofrecerá la actualización con un clic.
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

# 2) Construir el .zip en /build con la carpeta interna "promos-feed".
$build = Join-Path $root 'build'
$dest  = Join-Path $build 'promos-feed'
if (Test-Path $build) { Remove-Item $build -Recurse -Force }
New-Item -ItemType Directory -Force -Path $dest | Out-Null

$excluir = @('.git', 'build', 'release.ps1', '.gitignore')
Get-ChildItem -Path $root -Force | Where-Object { $excluir -notcontains $_.Name } | ForEach-Object {
    Copy-Item $_.FullName -Destination $dest -Recurse -Force
}

$zip = Join-Path $build 'promos-feed.zip'
Compress-Archive -Path $dest -DestinationPath $zip -Force
Write-Host "ZIP construido: $zip" -ForegroundColor Green

# 3) Crear la release en GitHub y adjuntar el .zip.
Write-Host "Publicando release $tag en GitHub..." -ForegroundColor Cyan
gh release create $tag $zip --title $tag --generate-notes
Write-Host "¡Listo! Release $tag publicada. WordPress ya podrá actualizar." -ForegroundColor Green
