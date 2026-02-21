param(
  [string]$BindHost = "127.0.0.1",
  [int]$PortStart = 8099,
  [int]$PortEnd = 8099,
  [switch]$KillPort,
  [switch]$UpdateEnv
)

$ErrorActionPreference = "Stop"

function Get-ListeningPid([int]$Port) {
  $line = netstat -ano | findstr (":" + $Port) | findstr "LISTENING" | Select-Object -First 1
  if (-not $line) { return $null }

  $parts = ($line -split "\s+") | Where-Object { $_ -ne "" }
  if ($parts.Count -lt 5) { return $null }
  return [int]$parts[-1]
}

function Get-FreePort([int]$Start, [int]$End) {
  for ($p = $Start; $p -le $End; $p++) {
    $listeningPid = Get-ListeningPid -Port $p
    if (-not $listeningPid) { return $p }
  }
  return $null
}

Push-Location (Resolve-Path (Join-Path $PSScriptRoot ".."))

try {
  $phpCmd = Get-Command php -ErrorAction SilentlyContinue
  if ($null -eq $phpCmd) {
    throw "No se encontró 'php' en PATH. Asegúrate de tener PHP instalado y agregado a PATH."
  }

  $router = Join-Path "vendor/laravel/framework/src/Illuminate/Foundation/resources" "server.php"
  if (-not (Test-Path $router)) {
    throw "No se encontró el router de Laravel: $router"
  }

  if ($KillPort) {
    for ($p = $PortStart; $p -le $PortEnd; $p++) {
      $listeningPid = Get-ListeningPid -Port $p
      if ($listeningPid) {
        Stop-Process -Id $listeningPid -Force -ErrorAction SilentlyContinue
        Start-Sleep -Milliseconds 300
      }
    }
  }

  $port = Get-FreePort -Start $PortStart -End $PortEnd
  if (-not $port) {
    throw "No encontré un puerto libre entre $PortStart y $PortEnd."
  }

  if ($UpdateEnv -and (Test-Path ".env")) {
    $appUrl = "http://${BindHost}:${port}"
    $envText = Get-Content .env -Raw

    if ($envText -match "(?m)^APP_URL=") {
      $envText = [regex]::Replace($envText, "(?m)^APP_URL=.*$", "APP_URL=$appUrl")
    } else {
      $envText = "APP_URL=$appUrl`r`n" + $envText
    }

    Set-Content -Path .env -Value $envText -Encoding UTF8
  }

  Write-Host "Servidor en: http://${BindHost}:${port}"
  Write-Host "(Ctrl+C para detener)"

  Push-Location public
  try {
    & php -S ("${BindHost}:${port}") ("..\\" + $router.Replace('/', '\\'))
  }
  finally {
    Pop-Location
  }
}
finally {
  Pop-Location
}
