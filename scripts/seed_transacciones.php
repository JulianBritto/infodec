<?php
/**
 * Inserta filas “similares” en cl_pagosclaro y gt_pago_pasarela clonando un registro existente.
 *
 * Uso:
 *   php scripts/seed_transacciones.php --count=10
 *   php scripts/seed_transacciones.php --only=pasarela --count=10
 */

declare(strict_types=1);

$opts = getopt('', ['count::', 'only::']);
$count = isset($opts['count']) ? (int)$opts['count'] : 10;
if ($count < 1) {
    fwrite(STDERR, "--count debe ser >= 1\n");
    exit(1);
}

$only = isset($opts['only']) ? strtolower((string)$opts['only']) : 'both';
if (!in_array($only, ['both', 'claro', 'pasarela'], true)) {
    fwrite(STDERR, "--only debe ser: both | claro | pasarela\n");
    exit(1);
}

$root = dirname(__DIR__);
$envPath = $root . DIRECTORY_SEPARATOR . '.env';
if (!file_exists($envPath)) {
    fwrite(STDERR, "No existe .env en: {$envPath}\n");
    exit(1);
}

$env = parseEnvFile($envPath);
if (($env['DB_CONNECTION'] ?? 'mysql') !== 'mysql') {
    fwrite(STDERR, "DB_CONNECTION no es mysql (actual: " . ($env['DB_CONNECTION'] ?? '') . ")\n");
    exit(1);
}

$dbHost = $env['DB_HOST'] ?? '127.0.0.1';
$dbPort = (int)($env['DB_PORT'] ?? 3306);
$dbName = $env['DB_DATABASE'] ?? '';
$dbUser = $env['DB_USERNAME'] ?? '';
$dbPass = $env['DB_PASSWORD'] ?? '';

if ($dbName === '' || $dbUser === '') {
    fwrite(STDERR, "Faltan DB_DATABASE o DB_USERNAME en .env\n");
    exit(1);
}

$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, "No se pudo conectar a MySQL: {$e->getMessage()}\n");
    exit(1);
}

$newClaroNumeros = [];

if ($only === 'both' || $only === 'claro') {
    $newClaroNumeros = seedClaro($pdo, $dbName, $count);
}

if ($only === 'both' || $only === 'pasarela') {
    seedPasarela($pdo, $dbName, $count, $newClaroNumeros);
}

exit(0);

function parseEnvFile(string $path): array
{
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return [];
    }

    $env = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $eq = strpos($line, '=');
        if ($eq === false) {
            continue;
        }
        $key = trim(substr($line, 0, $eq));
        $val = trim(substr($line, $eq + 1));
        if ($val !== '' && ($val[0] === '"' || $val[0] === "'")) {
            $q = $val[0];
            if (str_ends_with($val, $q)) {
                $val = substr($val, 1, -1);
            }
        }
        $env[$key] = $val;
    }
    return $env;
}

function tableExists(PDO $pdo, string $dbName, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? LIMIT 1'
    );
    $stmt->execute([$dbName, $table]);
    return (bool)$stmt->fetchColumn();
}

/**
 * @return array<string, array{type:string, nullable:bool, default:mixed, auto_increment:bool}>
 */
function getColumns(PDO $pdo, string $dbName, string $table): array
{
    $stmt = $pdo->prepare(
        'SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA '
        . 'FROM INFORMATION_SCHEMA.COLUMNS '
        . 'WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION'
    );
    $stmt->execute([$dbName, $table]);
    $out = [];
    foreach ($stmt->fetchAll() as $r) {
        $extra = strtolower((string)$r['EXTRA']);
        $out[$r['COLUMN_NAME']] = [
            'type' => strtolower((string)$r['DATA_TYPE']),
            'nullable' => ((string)$r['IS_NULLABLE']) === 'YES',
            'default' => $r['COLUMN_DEFAULT'],
            'auto_increment' => str_contains($extra, 'auto_increment'),
        ];
    }
    return $out;
}

/**
 * @return list<string>
 */
function getPrimaryKeyColumns(PDO $pdo, string $dbName, string $table): array
{
    $stmt = $pdo->prepare(
        'SELECT COLUMN_NAME '
        . 'FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE '
        . 'WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = "PRIMARY" '
        . 'ORDER BY ORDINAL_POSITION'
    );
    $stmt->execute([$dbName, $table]);
    return array_values(array_map(static fn($r) => (string)$r['COLUMN_NAME'], $stmt->fetchAll()));
}

/**
 * @return list<string>
 */
function getUniqueIndexColumns(PDO $pdo, string $dbName, string $table): array
{
    $stmt = $pdo->prepare(
        'SELECT COLUMN_NAME '
        . 'FROM INFORMATION_SCHEMA.STATISTICS '
        . 'WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND NON_UNIQUE = 0 AND INDEX_NAME <> "PRIMARY"'
    );
    $stmt->execute([$dbName, $table]);
    $cols = [];
    foreach ($stmt->fetchAll() as $r) {
        $cols[] = (string)$r['COLUMN_NAME'];
    }
    $cols = array_values(array_unique($cols));
    return $cols;
}

/**
 * @param array<string, array{type:string, nullable:bool, default:mixed, auto_increment:bool}> $columns
 * @return array<string,mixed>|null
 */
function getBaseRow(PDO $pdo, string $table, array $columns): ?array
{
    $order = null;
    if (array_key_exists('created_at', $columns)) {
        $order = 'created_at';
    } elseif (array_key_exists('id', $columns)) {
        $order = 'id';
    }

    $sql = $order
        ? "SELECT * FROM `{$table}` ORDER BY `{$order}` DESC LIMIT 1"
        : "SELECT * FROM `{$table}` LIMIT 1";

    $row = $pdo->query($sql)->fetch();
    if (!$row) {
        return null;
    }
    return $row;
}

/**
 * @return list<string|int>
 */
function seedClaro(PDO $pdo, string $dbName, int $count): array
{
    $table = 'cl_pagosclaro';
    echo "\nTabla: {$table}\n";

    if (!tableExists($pdo, $dbName, $table)) {
        echo "- No existe, se omite.\n";
        return [];
    }

    $columns = getColumns($pdo, $dbName, $table);
    if (!$columns) {
        echo "- No se pudieron obtener columnas, se omite.\n";
        return [];
    }

    $pkCols = getPrimaryKeyColumns($pdo, $dbName, $table);
    $uniqueCols = getUniqueIndexColumns($pdo, $dbName, $table);

    $baseRow = getBaseRow($pdo, $table, $columns);
    if ($baseRow === null) {
        echo "- Tabla vacía, se omite (para no romper NOT NULL/constraints).\n";
        return [];
    }

    $insertableCols = [];
    foreach ($columns as $name => $meta) {
        if ($meta['auto_increment']) {
            continue;
        }
        if (in_array($name, $pkCols, true)) {
            continue;
        }
        $insertableCols[] = $name;
    }

    if (!$insertableCols) {
        echo "- No hay columnas insertables (todo es PK/AI?), se omite.\n";
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($insertableCols), '?'));
    $colList = implode(',', array_map(static fn($c) => "`{$c}`", $insertableCols));
    $sql = "INSERT INTO `{$table}` ({$colList}) VALUES ({$placeholders})";
    $stmt = $pdo->prepare($sql);

    $inserted = 0;
    $attempts = 0;
    $maxAttempts = $count * 20;
    $newClaro = [];

    $canSetClaco = in_array('CLACO_NUMERO', $insertableCols, true);
    $clacoType = $canSetClaco ? ($columns['CLACO_NUMERO']['type'] ?? 'varchar') : null;

    while ($inserted < $count && $attempts < $maxAttempts) {
        $attempts++;

        $overrides = [];
        if ($canSetClaco) {
            $overrides['CLACO_NUMERO'] = generateClaroNumero((string)$clacoType);
        }

        $row = buildRow($baseRow, $columns, $insertableCols, $uniqueCols, $overrides);

        $values = [];
        foreach ($insertableCols as $c) {
            $values[] = $row[$c] ?? null;
        }

        try {
            $stmt->execute($values);
            $inserted++;

            if ($canSetClaco) {
                $newClaro[] = $row['CLACO_NUMERO'];
            }
        } catch (Throwable $e) {
            if ($attempts % 8 === 0) {
                echo "- Reintento por error: " . oneLine($e->getMessage()) . "\n";
            }
        }
    }

    if ($inserted === $count) {
        echo "- OK: insertadas {$inserted} filas.\n";
    } else {
        echo "- WARN: insertadas {$inserted}/{$count} filas (constraints/unique).\n";
    }

    $total = (int)$pdo->query("SELECT COUNT(*) AS c FROM `{$table}`")->fetchColumn();
    echo "- Total actual: {$total}\n";
    return $newClaro;
}

function seedPasarela(PDO $pdo, string $dbName, int $count, array $newClaroNumeros): void
{
    $table = 'gt_pago_pasarela';
    echo "\nTabla: {$table}\n";

    if (!tableExists($pdo, $dbName, $table)) {
        echo "- No existe, se omite.\n";
        return;
    }

    $columns = getColumns($pdo, $dbName, $table);
    if (!$columns) {
        echo "- No se pudieron obtener columnas, se omite.\n";
        return;
    }

    $pkCols = getPrimaryKeyColumns($pdo, $dbName, $table);
    $uniqueCols = getUniqueIndexColumns($pdo, $dbName, $table);

    $baseRow = getBaseRow($pdo, $table, $columns);
    if ($baseRow === null) {
        echo "- Tabla vacía, se omite (para no romper NOT NULL/constraints).\n";
        return;
    }

    $insertableCols = [];
    foreach ($columns as $name => $meta) {
        if ($meta['auto_increment']) {
            continue;
        }
        if (in_array($name, $pkCols, true)) {
            continue;
        }
        $insertableCols[] = $name;
    }

    if (!$insertableCols) {
        echo "- No hay columnas insertables (todo es PK/AI?), se omite.\n";
        return;
    }

    $placeholders = implode(',', array_fill(0, count($insertableCols), '?'));
    $colList = implode(',', array_map(static fn($c) => "`{$c}`", $insertableCols));
    $sql = "INSERT INTO `{$table}` ({$colList}) VALUES ({$placeholders})";
    $stmt = $pdo->prepare($sql);

    // FK: gt_pago_pasarela.ID_TRANSACCION -> cl_pagosclaro.CLACO_NUMERO
    $claroIds = $newClaroNumeros;
    if (empty($claroIds)) {
        // Si no se acaban de crear nuevos, usar los últimos existentes
        $claroIds = fetchClaroNumeros($pdo, $count);
    }

    if (empty($claroIds)) {
        echo "- No hay CLACO_NUMERO disponibles en cl_pagosclaro para cumplir la FK.\n";
        return;
    }

    $inserted = 0;
    $attempts = 0;
    $maxAttempts = $count * 25;

    $canSetIdTransaccion = in_array('ID_TRANSACCION', $insertableCols, true);
    while ($inserted < $count && $attempts < $maxAttempts) {
        $attempts++;

        $overrides = [];
        if ($canSetIdTransaccion) {
            $overrides['ID_TRANSACCION'] = $claroIds[$inserted % count($claroIds)];
        }

        $row = buildRow($baseRow, $columns, $insertableCols, $uniqueCols, $overrides);

        $values = [];
        foreach ($insertableCols as $c) {
            $values[] = $row[$c] ?? null;
        }

        try {
            $stmt->execute($values);
            $inserted++;
        } catch (Throwable $e) {
            if ($attempts % 8 === 0) {
                echo "- Reintento por error: " . oneLine($e->getMessage()) . "\n";
            }
        }
    }

    if ($inserted === $count) {
        echo "- OK: insertadas {$inserted} filas.\n";
    } else {
        echo "- WARN: insertadas {$inserted}/{$count} filas (constraints/unique).\n";
    }

    $total = (int)$pdo->query("SELECT COUNT(*) AS c FROM `{$table}`")->fetchColumn();
    echo "- Total actual: {$total}\n";
}

/**
 * @param array<string,mixed> $baseRow
 * @param array<string, array{type:string, nullable:bool, default:mixed, auto_increment:bool}> $columns
 * @param list<string> $insertableCols
 * @param list<string> $uniqueCols
 * @return array<string,mixed>
 */
function buildRow(array $baseRow, array $columns, array $insertableCols, array $uniqueCols, array $overrides = []): array
{
    $row = [];
    foreach ($insertableCols as $c) {
        $row[$c] = $baseRow[$c] ?? null;
    }

    $now = (new DateTimeImmutable('now'));

    foreach ($overrides as $k => $v) {
        if (in_array($k, $insertableCols, true)) {
            $row[$k] = $v;
        }
    }

    foreach ($insertableCols as $name) {
        if (array_key_exists($name, $overrides)) {
            continue;
        }
        $meta = $columns[$name];
        $type = $meta['type'];

        if (in_array($name, ['created_at', 'updated_at'], true)) {
            $row[$name] = $now->format('Y-m-d H:i:s');
            continue;
        }

        if (in_array($name, $uniqueCols, true)) {
            $row[$name] = randomValue($type, $name);
            continue;
        }

        // Heurísticas de nombres comunes
        $lname = strtolower($name);
        if (str_contains($lname, 'uuid') || str_contains($lname, 'token')) {
            $row[$name] = uuidv4();
            continue;
        }
        if (str_contains($lname, 'reference') || str_contains($lname, 'referencia') || str_contains($lname, 'transaccion') || str_contains($lname, 'transaction') || str_contains($lname, 'trx') || str_contains($lname, 'codigo') || str_contains($lname, 'code')) {
            $row[$name] = strtoupper(bin2hex(random_bytes(6)));
            continue;
        }

        if ($row[$name] === null && !$meta['nullable']) {
            $row[$name] = randomValue($type, $name);
        }
    }

    return $row;
}

/**
 * @return list<string|int>
 */
function fetchClaroNumeros(PDO $pdo, int $count): array
{
    // Intentar traer los últimos CLACO_NUMERO (por created_at si existe, si no por CLACO_NUMERO)
    try {
        $hasCreated = (bool)$pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cl_pagosclaro' AND COLUMN_NAME = 'created_at' LIMIT 1")->fetchColumn();
        $orderBy = $hasCreated ? '`created_at` DESC' : '`CLACO_NUMERO` DESC';
        $stmt = $pdo->query("SELECT `CLACO_NUMERO` FROM `cl_pagosclaro` ORDER BY {$orderBy} LIMIT " . (int)$count);
        $vals = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            if (array_key_exists('CLACO_NUMERO', $r)) {
                $vals[] = $r['CLACO_NUMERO'];
            }
        }
        return $vals;
    } catch (Throwable $e) {
        return [];
    }
}

function generateClaroNumero(string $type): string|int
{
    $type = strtolower($type);
    if (in_array($type, ['int', 'integer', 'bigint', 'mediumint', 'smallint', 'tinyint'], true)) {
        // timestamp en ms + random para reducir colisiones
        return (int)(microtime(true) * 1000) + random_int(1, 999);
    }
    // string: usar solo dígitos para parecer “número”
    $base = (new DateTimeImmutable('now'))->format('ymdHis');
    return $base . (string)random_int(1000, 9999);
}

function randomValue(string $type, string $name): mixed
{
    return match ($type) {
        'int', 'integer', 'bigint', 'mediumint', 'smallint', 'tinyint' => random_int(1, 1000000),
        'decimal', 'numeric', 'float', 'double' => (string)(random_int(100, 999999) / 100),
        'date' => (new DateTimeImmutable('now'))->format('Y-m-d'),
        'datetime', 'timestamp' => (new DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
        'json' => json_encode(['seed' => true, 'field' => $name, 't' => (new DateTimeImmutable('now'))->format(DateTimeInterface::ATOM)]),
        'text', 'mediumtext', 'longtext' => 'seed_' . bin2hex(random_bytes(12)),
        default => 'seed_' . bin2hex(random_bytes(8)),
    };
}

function uuidv4(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    $hex = bin2hex($data);
    return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
}

function oneLine(string $s): string
{
    $s = preg_replace('/\s+/', ' ', $s) ?? $s;
    return trim($s);
}
