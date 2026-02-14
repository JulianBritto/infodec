# Ejecutar INFODEC (Laravel) con XAMPP (Windows)

Este proyecto es **Laravel 10** y requiere **PHP 8.1+**.

## 1) Requisitos

- XAMPP con **PHP 8.1 o superior**.
  - Verifica con: `C:\xampp\php\php.exe -v`
- Composer (recomendado instalado globalmente).
  - Verifica con: `composer -V`
- Node.js + npm (para Vite / assets).
  - Verifica con: `node -v` y `npm -v`
- MySQL/MariaDB de XAMPP corriendo.

## 2) Ubicación recomendada

Recomendado (no obligatorio):
- `C:\xampp\htdocs\INFODEC`

## 3) Configurar el archivo .env

1) Si no tienes `.env`, crea uno copiando `.env.example`.
2) Ajusta la DB para XAMPP (valores típicos):

- `DB_CONNECTION=mysql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_DATABASE=laravel` (o el nombre que uses)
- `DB_USERNAME=root`
- `DB_PASSWORD=` (en XAMPP muchas veces es vacío)

## 4) Crear base de datos

Entra a phpMyAdmin:
- `http://localhost/phpmyadmin`

Crea una base de datos con el mismo nombre que pusiste en `DB_DATABASE`.

## 5) Instalar dependencias y preparar Laravel

Desde la carpeta del proyecto (donde está `artisan`):

1) Dependencias PHP:
- `composer install`

2) Generar APP_KEY:
- `C:\xampp\php\php.exe artisan key:generate`

3) Migraciones:
- `C:\xampp\php\php.exe artisan migrate`

## 6) Formas de ejecutar

### Opción A (más fácil): `artisan serve`

- `C:\xampp\php\php.exe artisan serve`

Abre:
- `http://127.0.0.1:8000`

> En esta opción **no necesitas** configurar Apache VirtualHost.

### Opción B (Apache de XAMPP): VirtualHost

Esta opción sirve el proyecto como un sitio en Apache (recomendado para “modo hosting”).

1) Asegúrate de habilitar:
- `mod_rewrite` en Apache (`httpd.conf`)
- `httpd-vhosts.conf` (include en `httpd.conf`)

2) En `C:\xampp\apache\conf\extra\httpd-vhosts.conf` agrega (ajusta la ruta):

```apache
<VirtualHost *:80>
  ServerName infodec.local
  DocumentRoot "C:/xampp/htdocs/INFODEC/public"

  <Directory "C:/xampp/htdocs/INFODEC/public">
    AllowOverride All
    Require all granted
  </Directory>
</VirtualHost>
```

3) En tu archivo `hosts` (como Administrador):
- `C:\Windows\System32\drivers\etc\hosts`

Agrega:

```
127.0.0.1 infodec.local
```

4) Reinicia Apache desde el panel de XAMPP.

5) (Recomendado) Ajusta `APP_URL` en `.env`:
- `APP_URL=http://infodec.local`

Luego abre:
- `http://infodec.local`

## 7) Assets (Vite)

Para desarrollo (hot reload):
- `npm install`
- `npm run dev`

Para compilar:
- `npm run build`

## Scripts incluidos (opcional)

- `scripts/xampp-setup.ps1`: prepara dependencias, `.env` y `APP_KEY`.
- `scripts/xampp-serve.ps1`: levanta `php artisan serve` usando el PHP de XAMPP.

Ejemplo:
- `powershell -ExecutionPolicy Bypass -File scripts\\xampp-setup.ps1 -XamppPath C:\\xampp -Migrate`
- `powershell -ExecutionPolicy Bypass -File scripts\\xampp-serve.ps1 -XamppPath C:\\xampp`
