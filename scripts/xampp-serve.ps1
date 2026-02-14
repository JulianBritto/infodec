param(
  [string]$XamppPath = "C:\\xampp",
  [string]$Host = "127.0.0.1",
  [int]$Port = 8000
)

$ErrorActionPreference = "Stop"

$php = Join-Path $XamppPath "php\\php.exe"
if (-not (Test-Path $php)) {
  throw "No se encontró PHP de XAMPP en: $php. Ajusta -XamppPath (ej: C:\\xampp)."
}

Push-Location (Resolve-Path (Join-Path $PSScriptRoot ".."))

try {
  Write-Host "Levantando Laravel con XAMPP PHP en http://${Host}:${Port} ..."
  & $php artisan serve --host=$Host --port=$Port
}
finally {
  Pop-Location
}
