<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class SeedInfodecTransacciones extends Command
{
    protected $signature = 'infodec:seed-transacciones {--count=10 : Cantidad de filas por tabla}';

    protected $description = 'Inserta transacciones de prueba en cl_pagosclaro y gt_pago_pasarela (clonando registros existentes).';

    public function handle(): int
    {
        $count = (int) $this->option('count');
        if ($count < 1) {
            $this->error('El parámetro --count debe ser >= 1');
            return self::FAILURE;
        }

        $tables = ['cl_pagosclaro', 'gt_pago_pasarela'];

        foreach ($tables as $table) {
            $this->line('');
            $this->info("Tabla: {$table}");

            if (!Schema::hasTable($table)) {
                $this->warn("La tabla {$table} no existe. Se omite.");
                continue;
            }

            $columns = $this->getTableColumns($table);
            if (empty($columns)) {
                $this->warn("No se pudieron leer columnas para {$table}. Se omite.");
                continue;
            }

            $uniqueColumns = $this->getUniqueIndexColumns($table);

            $baseRow = $this->getBaseRow($table, array_keys($columns));
            if ($baseRow === null) {
                $this->warn("No hay registros existentes en {$table}. Se omite para evitar violar NOT NULL/constraints.");
                continue;
            }

            $inserted = 0;
            $attempts = 0;

            while ($inserted < $count && $attempts < ($count * 15)) {
                $attempts++;

                $row = $this->buildRow($baseRow, $columns, $uniqueColumns);

                try {
                    DB::table($table)->insert($row);
                    $inserted++;
                } catch (Throwable $e) {
                    // Retry with different randomized values
                    if ($attempts % 5 === 0) {
                        $this->warn('Reintentando por error de inserción: ' . $e->getMessage());
                    }
                }
            }

            if ($inserted === $count) {
                $this->info("Insertadas {$inserted} filas en {$table}.");
            } else {
                $this->warn("Solo se insertaron {$inserted}/{$count} filas en {$table}. Revisa constraints/columnas únicas.");
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, array{type:string, nullable:bool, default:mixed, extra:string}>
     */
    private function getTableColumns(string $table): array
    {
        $database = DB::getDatabaseName();
        $rows = DB::select(
            'SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA '
            . 'FROM INFORMATION_SCHEMA.COLUMNS '
            . 'WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? '
            . 'ORDER BY ORDINAL_POSITION',
            [$database, $table]
        );

        $cols = [];
        foreach ($rows as $r) {
            $cols[$r->COLUMN_NAME] = [
                'type' => (string) $r->DATA_TYPE,
                'nullable' => ((string) $r->IS_NULLABLE) === 'YES',
                'default' => $r->COLUMN_DEFAULT,
                'extra' => (string) $r->EXTRA,
            ];
        }

        return $cols;
    }

    /**
     * @return list<string>
     */
    private function getUniqueIndexColumns(string $table): array
    {
        $database = DB::getDatabaseName();
        $rows = DB::select(
            'SELECT COLUMN_NAME '
            . 'FROM INFORMATION_SCHEMA.STATISTICS '
            . 'WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND NON_UNIQUE = 0 AND INDEX_NAME <> "PRIMARY"',
            [$database, $table]
        );

        $cols = [];
        foreach ($rows as $r) {
            $cols[] = (string) $r->COLUMN_NAME;
        }

        return array_values(array_unique($cols));
    }

    /**
     * @param list<string> $columnNames
     * @return array<string, mixed>|null
     */
    private function getBaseRow(string $table, array $columnNames): ?array
    {
        $orderColumn = null;
        if (in_array('created_at', $columnNames, true)) {
            $orderColumn = 'created_at';
        } elseif (in_array('id', $columnNames, true)) {
            $orderColumn = 'id';
        }

        $q = DB::table($table);
        if ($orderColumn) {
            $q->orderByDesc($orderColumn);
        }

        $row = $q->first();
        if (!$row) {
            return null;
        }

        return (array) $row;
    }

    /**
     * @param array<string, mixed> $baseRow
     * @param array<string, array{type:string, nullable:bool, default:mixed, extra:string}> $columns
     * @param list<string> $uniqueColumns
     * @return array<string, mixed>
     */
    private function buildRow(array $baseRow, array $columns, array $uniqueColumns): array
    {
        $row = Arr::only($baseRow, array_keys($columns));

        foreach ($columns as $name => $meta) {
            $type = strtolower($meta['type']);
            $extra = strtolower($meta['extra']);

            // Skip auto-increment columns
            if (str_contains($extra, 'auto_increment')) {
                unset($row[$name]);
                continue;
            }

            // Keep nulls if allowed
            if (($row[$name] ?? null) === null && $meta['nullable']) {
                continue;
            }

            // Always refresh timestamps if present
            if (in_array($name, ['created_at', 'updated_at'], true)) {
                $row[$name] = now()->subSeconds(random_int(0, 3600))->toDateTimeString();
                continue;
            }

            // Make unique-index columns unique-ish
            if (in_array($name, $uniqueColumns, true)) {
                $row[$name] = $this->randomValueForType($type, $name);
                continue;
            }

            // Heuristics for common identifier-ish columns
            if (Str::contains($name, ['uuid', 'token'], true)) {
                $row[$name] = (string) Str::uuid();
                continue;
            }
            if (Str::contains($name, ['reference', 'referencia', 'transaction', 'transaccion', 'trx', 'codigo', 'code'], true)) {
                $row[$name] = strtoupper(Str::random(12));
                continue;
            }

            // For non-null columns that are empty, generate something safe
            if (($row[$name] ?? null) === null && !$meta['nullable']) {
                $row[$name] = $this->randomValueForType($type, $name);
            }
        }

        return $row;
    }

    private function randomValueForType(string $type, string $name): mixed
    {
        return match ($type) {
            'int', 'integer', 'bigint', 'mediumint', 'smallint', 'tinyint' => random_int(1, 1000000),
            'decimal', 'numeric', 'float', 'double' => (string) (random_int(100, 999999) / 100),
            'date' => now()->toDateString(),
            'datetime', 'timestamp' => now()->subSeconds(random_int(0, 86400))->toDateTimeString(),
            'json' => json_encode(['seed' => true, 'field' => $name, 't' => now()->toIso8601String()]),
            'text', 'mediumtext', 'longtext' => 'seed_' . Str::random(24),
            default => 'seed_' . Str::random(16),
        };
    }
}
