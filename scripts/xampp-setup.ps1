param(
  [string]$XamppPath = "C:\\xampp",
  [switch]$Migrate,
  [switch]$InstallNodeDeps
)

$ErrorActionPreference = "Stop"

$php = Join-Path $XamppPath "php\\php.exe"
if (-not (Test-Path $php)) {
  throw "No se encontró PHP de XAMPP en: $php. Ajusta -XamppPath (ej: C:\\xampp)."
}

Write-Host "Usando PHP: $php"

Push-Location (Resolve-Path (Join-Path $PSScriptRoot ".."))

try {
  if (-not (Test-Path ".env")) {
    if (Test-Path ".env.example") {
      Copy-Item ".env.example" ".env"
      Write-Host "Creado .env desde .env.example"
    } else {
      throw "No existe .env ni .env.example."
    }
  }

  $composerCmd = Get-Command composer -ErrorAction SilentlyContinue
  if ($null -eq $composerCmd) {
    throw "Composer no está en PATH. Instálalo o agrega composer al PATH para ejecutar 'composer install'."
  }

  Write-Host "Instalando dependencias PHP (composer install)..."
  composer install

  Write-Host "Generando APP_KEY (si hace falta)..."
  & $php artisan key:generate | Out-Host

  if ($InstallNodeDeps) {
    $npmCmd = Get-Command npm -ErrorAction SilentlyContinue
    if ($null -eq $npmCmd) {
      throw "npm no está en PATH. Instala Node.js para usar Vite."
    }

    Write-Host "Instalando dependencias Node (npm install)..."
    npm install
  }

  if ($Migrate) {
    Write-Host "Ejecutando migraciones..."
    & $php artisan migrate | Out-Host
  }

  Write-Host "Listo."
  Write-Host "Siguiente: scripts\\xampp-serve.ps1 o configura VirtualHost (ver README-XAMPP.md)."
}
finally {
  Pop-Location
}
