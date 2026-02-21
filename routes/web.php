<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\QueryExecutionLog;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect('/dashboard');
});

$getMonitoringSnapshot = function (bool $probeDb = true, bool $probeCache = true, ?callable $withQueryGroup = null): array {
    $now = now();

    $app = [
        'name' => config('app.name'),
        'env' => config('app.env'),
        'debug' => (bool) config('app.debug'),
        'url' => config('app.url'),
        'timezone' => config('app.timezone'),
        'locale' => config('app.locale'),
        'time' => $now->toDateTimeString(),
        'laravel_version' => app()->version(),
        'php_version' => PHP_VERSION,
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
    ];

    $defaultConnection = (string) config('database.default');
    $db = [
        'connection' => $defaultConnection,
        'database' => (string) config('database.connections.' . $defaultConnection . '.database'),
        'host' => (string) config('database.connections.' . $defaultConnection . '.host'),
        'status' => $probeDb ? 'unknown' : 'skipped',
        'latency_ms' => null,
        'error' => null,
        'migrations_table' => null,
    ];

    if ($probeDb) {
        try {
            $dbProbeTtl = (int) env('DASHBOARD_DB_PROBE_TTL', 5);
            if ($dbProbeTtl < 1) {
                $dbProbeTtl = 1;
            }

            $dbProbeKey = 'dashboard:db_probe:' . $defaultConnection . ':' . ($db['database'] ?? '');

            $runDbProbe = function () use ($defaultConnection) {
                $start = microtime(true);

                // Lightweight probe: one roundtrip, avoids schema checks and reconnect/purge.
                DB::connection($defaultConnection)->select('select 1');

                $latencyMs = (int) round((microtime(true) - $start) * 1000);
                if ($latencyMs < 0) {
                    $latencyMs = 0;
                }

                return [
                    'status' => 'ok',
                    'latency_ms' => $latencyMs,
                    'error' => null,
                    'migrations_table' => null,
                ];
            };

            $probeResult = null;
            try {
                $probeResult = Cache::remember($dbProbeKey, $dbProbeTtl, $runDbProbe);
            } catch (Throwable $e) {
                $probeResult = $runDbProbe();
            }

            if (is_array($probeResult)) {
                $db['status'] = (string) ($probeResult['status'] ?? $db['status']);
                $db['latency_ms'] = $probeResult['latency_ms'] ?? $db['latency_ms'];
                $db['error'] = $probeResult['error'] ?? $db['error'];
                $db['migrations_table'] = $probeResult['migrations_table'] ?? $db['migrations_table'];
            }

        } catch (Throwable $e) {
            $db['status'] = 'fail';
            $db['error'] = $e->getMessage();
        }
    }

    $cache = [
        'driver' => (string) config('cache.default'),
        'status' => $probeCache ? 'unknown' : 'skipped',
        'error' => null,
    ];

    if ($probeCache) {
        try {
            $probeKey = 'dashboard_probe';
            $ttl = (int) env('DASHBOARD_PROBE_TTL', 10);
            if ($ttl < 3) {
                $ttl = 3;
            }

            // Usar remember para evitar escrituras constantes en cache file.
            $cacheProbe = function () use ($probeKey, $ttl) {
                return Cache::remember($probeKey, $ttl, function () {
                    return 'ok';
                });
            };

            $val = is_callable($withQueryGroup)
                ? $withQueryGroup('dashboard|snapshot|cache_probe', $cacheProbe)
                : $cacheProbe();

            $cache['status'] = $val === 'ok' ? 'ok' : 'fail';
        } catch (Throwable $e) {
            $cache['status'] = 'fail';
            $cache['error'] = $e->getMessage();
        }
    }

    $storage = [
        'storage_path' => storage_path(),
        'writable' => is_writable(storage_path()),
        'framework_cache_writable' => is_writable(storage_path('framework/cache')),
        'logs_writable' => is_writable(storage_path('logs')),
    ];

    $runtime = [
        'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
        'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        'server_software' => request()->server('SERVER_SOFTWARE'),
    ];

    return compact('app', 'db', 'cache', 'storage', 'runtime');
};

Route::get('/dashboard/{section?}', function (?string $section = null) use ($getMonitoringSnapshot) {
    // /dashboard (sin sección) => vista de inicio
    $section = $section ?: 'inicio';
    if (!in_array($section, ['principal', 'inicio', 'transacciones', 'busqueda-transacciones', 'monitoreo', 'estadisticas', 'usuarios', 'conexionBD', 'tiempo-ejecucion-querys'], true)) {
        abort(404);
    }

    // Group helper: tags persisted query logs (query_execution_logs.group) for queries executed inside $fn.
    $withQueryGroup = function (string $group, callable $fn) {
        $prev = null;
        $hadPrev = false;
        try {
            if (app()->bound('query_log_group')) {
                $prev = app('query_log_group');
                $hadPrev = true;
            }
        } catch (Throwable $e) {
            $hadPrev = false;
            $prev = null;
        }

        app()->instance('query_log_group', $group);
        try {
            return $fn();
        } finally {
            try {
                if ($hadPrev) {
                    app()->instance('query_log_group', $prev);
                } else {
                    app()->forgetInstance('query_log_group');
                }
            } catch (Throwable $e) {
                // ignore
            }
        }
    };

    // Solo hacemos probes explícitos de DB/cache en secciones que los muestran.
    $probe = in_array($section, ['inicio', 'conexionBD'], true);
    // Snapshot shouldn't tag queries for persistent logging (only search/transacciones are logged).
    $data = $getMonitoringSnapshot($probe, $probe, null) + ['section' => $section];

    if ($section === 'tiempo-ejecucion-querys') {
        try {
            $data['query_logs'] = QueryExecutionLog::query()
                ->orderByDesc('executed_at')
                ->limit(200)
                ->get();
            $data['query_logs_error'] = null;
        } catch (Throwable $e) {
            $data['query_logs'] = [];
            $data['query_logs_error'] = 'No se pudo leer el historial. Verifica la conexión y ejecuta php artisan migrate.';
        }
    }

    // Cachear metadata de esquema para no golpear information_schema en cada request.
    $schemaTtl = (int) env('DASHBOARD_SCHEMA_TTL', 300);
    if ($schemaTtl < 30) {
        $schemaTtl = 30;
    }

    $schemaConn = (string) config('database.default');
    $schemaDb = (string) config('database.connections.' . $schemaConn . '.database');
    $schemaKeyPrefix = 'dashboard:schema:' . $schemaConn . ':' . $schemaDb;

    $cacheRemember = function (string $key, int $seconds, callable $fn) {
        try {
            return Cache::remember($key, $seconds, $fn);
        } catch (Throwable $e) {
            return $fn();
        }
    };

    $tableExists = function (string $tableName) use ($cacheRemember, $schemaTtl, $schemaKeyPrefix, $withQueryGroup): bool {
        $k = $schemaKeyPrefix . ':has:' . $tableName;
        return (bool) $cacheRemember($k, $schemaTtl, function () use ($tableName) {
            try {
                return Schema::hasTable($tableName);
            } catch (Throwable $e) {
                return false;
            }
        });
    };

    $getColumns = function (string $tableName) use ($cacheRemember, $schemaTtl, $schemaKeyPrefix, $withQueryGroup): array {
        $k = $schemaKeyPrefix . ':cols:' . $tableName;
        $cols = $cacheRemember($k, $schemaTtl, function () use ($tableName) {
            try {
                return Schema::getColumnListing($tableName);
            } catch (Throwable $e) {
                return [];
            }
        });

        return is_array($cols) ? $cols : [];
    };

    if ($section === 'busqueda-transacciones') {
        $cat = (string) request()->query('cat', 'personas');
        $cat = trim($cat) === '' ? 'personas' : $cat;
        if (!in_array($cat, ['personas', 'empresas', 'ecommerce'], true)) {
            $cat = 'personas';
        }

        $q = (string) request()->query('q', '');
        $q = trim($q);

        $data['busqueda'] = [
            'meta' => [
                'cat' => $cat,
                'q' => $q,
            ],
            'personas' => [
                'rows' => [],
                'error' => null,
            ],
            'empresas' => [
                'rows' => [],
                'error' => null,
            ],
            'ecommerce' => [
                'rows' => [],
                'error' => null,
            ],
        ];

        $buildBusquedaConsulta1 = function (string $q) {
            $query = DB::table('gt_pago_pasarela as b')
                ->leftJoin('cl_pagosclaro as a', 'b.ID_TRANSACCION', '=', 'a.CLACO_NUMERO')
                ->leftJoin('gt_valores as orgen', function ($join) {
                    $join->on('b.ORIGEN_PAGO', '=', 'orgen.CODIGO')
                        ->where('orgen.ELIMINADO', '=', '-1')
                        ->where('orgen.LIST_NUMERO', '=', 10005);
                })
                ->leftJoin('gt_valores as frpag', function ($join) {
                    $join->on('b.FORMA_PAGO', '=', 'frpag.CODIGO')
                        ->where('frpag.ELIMINADO', '=', '-1')
                        ->where('frpag.LIST_NUMERO', '=', 10001);
                })
                ->join('gt_valores as tiptra', function ($join) {
                    $join->on('a.TIPO_TRANS', '=', 'tiptra.CODIGO')
                        ->where('tiptra.ELIMINADO', '=', '-1')
                        ->where('tiptra.LIST_NUMERO', '=', 10002);
                })
                ->select([
                    'a.FECHA_INICIO as fecha_inicio',
                    'b.ESTADO as ESTADO',
                    'b.INTENTOS as INTENTOS',
                    'b.TITULAR as TITULAR',
                    'b.NUMEROFACTURA as NUMEROFACTURA',
                    'b.FECHA_TRANSACCION as FECHA_TRANSACCION',
                    'b.VALOR as VALOR',
                    'b.DESCRIPCION_COMPRA as DESCRIPCION_COMPRA',
                    'b.NUMERO_DOCUMENTO as NUMERO_DOCUMENTO',
                    'b.TELEFONO as TELEFONO',
                    'b.EMAIL as EMAIL',
                    'b.CUS as CUS',
                    'tiptra.VALOR_ES as TIPO_TRANS',
                    'orgen.VALOR_ES as ORIGEN_PAGO',
                    'frpag.VALOR_ES as FORMA_PAGO',
                    'a.CodigoCliente as CodigoCliente',
                    'b.PASA_NUMERO as PASA_NUMERO',
                    'b.ID_TRANSACCION as ID_TRANSACCION',
                ]);

            if ($q !== '') {
                $query->where(function ($w) use ($q) {
                    if (ctype_digit($q)) {
                        $w->orWhere('b.ID_TRANSACCION', '=', $q);
                    } else {
                        $w->orWhere('b.ID_TRANSACCION', 'like', '%' . $q . '%');
                    }
                    $w->orWhere('b.NUMERO_DOCUMENTO', 'like', '%' . $q . '%')
                        ->orWhere('b.EMAIL', 'like', '%' . $q . '%')
                        ->orWhere('b.CUS', 'like', '%' . $q . '%');
                });
            }

            return $query->orderByDesc('a.FECHA_INICIO');
        };

        $runBusquedaConsulta1 = function (string $catKey) use ($q, $withQueryGroup, $tableExists, $buildBusquedaConsulta1, $section, &$data) {
            try {
                if ($q === '') {
                    $data['busqueda'][$catKey]['rows'] = [];
                    $data['busqueda'][$catKey]['error'] = null;
                    $data['busqueda'][$catKey]['time_ms'] = null;
                    return;
                }

                $withQueryGroup('dashboard|' . $section . '|busqueda|' . $catKey . '|prechecks', function () use ($tableExists) {
                    $requiredTables = ['gt_pago_pasarela', 'cl_pagosclaro', 'gt_valores'];
                    foreach ($requiredTables as $tName) {
                        if (!$tableExists($tName)) {
                            throw new RuntimeException('La tabla ' . $tName . ' no existe en la base de datos.');
                        }
                    }
                });

                $query = $buildBusquedaConsulta1($q);
                $t0 = microtime(true);
                $rows = $withQueryGroup('dashboard|' . $section . '|busqueda|' . $catKey . '|consulta1', function () use ($query) {
                    return $query->limit(50)->get();
                });
                $elapsedMs = (int) round((microtime(true) - $t0) * 1000);
                if ($elapsedMs < 0) {
                    $elapsedMs = 0;
                }

                $data['busqueda'][$catKey]['rows'] = $rows;
                $data['busqueda'][$catKey]['error'] = null;
                $data['busqueda'][$catKey]['time_ms'] = $elapsedMs;
            } catch (Throwable $e) {
                $data['busqueda'][$catKey]['error'] = $e->getMessage();
                $data['busqueda'][$catKey]['rows'] = [];
                $data['busqueda'][$catKey]['time_ms'] = null;
            }
        };

        if ($cat === 'personas') {
            $runBusquedaConsulta1('personas');
        } elseif ($cat === 'empresas') {
            $runBusquedaConsulta1('empresas');
        } elseif ($cat === 'ecommerce') {
            $runBusquedaConsulta1('ecommerce');
        }
    }

    if ($section === 'transacciones') {
        $perPage = 5;
        $pages = 4;

        $search = (string) request()->query('q', '');
        $search = trim($search);

        $clampPage = function (int $page) use ($pages): int {
            if ($page < 1) {
                return 1;
            }
            if ($page > $pages) {
                return $pages;
            }
            return $page;
        };

        $pageClaro = $clampPage((int) request()->query('p_claro', 1));
        $pagePasarela = $clampPage((int) request()->query('p_pasarela', 1));

        $offsetClaro = ($pageClaro - 1) * $perPage;
        $offsetPasarela = ($pagePasarela - 1) * $perPage;

        // When searching, treat CLACO_NUMERO (cl_pagosclaro) and ID_TRANSACCION (gt_pago_pasarela) as the linking transaction id.
        // We first find matching transaction ids from any table, then filter both tables by those ids.
        $linkedSearchIds = null;
        if ($search !== '') {
            $linkedSearchIds = $withQueryGroup('dashboard|' . $section . '|transacciones|linked_search_ids', function () use ($tableExists, $getColumns, $search) {
                $idBucket = [];
                $tableConfigs = [
                    ['name' => 'cl_pagosclaro', 'id_col' => 'CLACO_NUMERO'],
                    ['name' => 'gt_pago_pasarela', 'id_col' => 'ID_TRANSACCION'],
                ];

                foreach ($tableConfigs as $cfg) {
                    try {
                        if (!$tableExists($cfg['name'])) {
                            continue;
                        }

                        $cols = $getColumns($cfg['name']);
                        if (!in_array($cfg['id_col'], $cols, true)) {
                            continue;
                        }

                        $filterable = array_values(array_intersect($cols, ['CUS', 'NUMERO_DOCUMENTO', 'ID_TRANSACCION', 'EMAIL', 'CLACO_NUMERO']));
                        if (empty($filterable)) {
                            continue;
                        }

                        $q = DB::table($cfg['name'])->select($cfg['id_col']);
                        $q->where(function ($w) use ($filterable, $search) {
                            foreach ($filterable as $col) {
                                if (in_array($col, ['CLACO_NUMERO', 'ID_TRANSACCION'], true) && ctype_digit($search)) {
                                    $w->orWhere($col, '=', $search);
                                } else {
                                    $w->orWhere($col, 'like', '%' . $search . '%');
                                }
                            }
                        });

                        // Cap to avoid giant IN() lists; enough for dashboard use.
                        $ids = $q->limit(500)->pluck($cfg['id_col'])->all();
                        foreach ($ids as $id) {
                            if ($id === null || $id === '') {
                                continue;
                            }
                            $idBucket[] = (string) $id;
                        }
                    } catch (Throwable $e) {
                        // Ignore per-table search-id errors; table will fall back to empty results.
                    }
                }

                $idBucket = array_values(array_unique($idBucket));
                return empty($idBucket) ? [] : $idBucket;
            });
        }

        $fetchTable = function (string $tableName, int $page, int $offset, string $param) use ($perPage, $pages, $search, $linkedSearchIds, $tableExists, $getColumns): array {
            try {
                if (!$tableExists($tableName)) {
                    return [
                        'table' => $tableName,
                        'exists' => false,
                        'columns' => [],
                        'rows' => [],
                        'total' => 0,
                        'error' => null,
                        'search_applies' => false,
                        'pagination' => [
                            'page' => $page,
                            'per_page' => $perPage,
                            'pages' => $pages,
                            'param' => $param,
                        ],
                    ];
                }

                $columns = $getColumns($tableName);

                $idCol = null;
                if ($tableName === 'cl_pagosclaro' && in_array('CLACO_NUMERO', $columns, true)) {
                    $idCol = 'CLACO_NUMERO';
                } elseif ($tableName === 'gt_pago_pasarela' && in_array('ID_TRANSACCION', $columns, true)) {
                    $idCol = 'ID_TRANSACCION';
                }

                $orderColumn = null;
                if (in_array('created_at', $columns, true)) {
                    $orderColumn = 'created_at';
                } elseif (in_array('id', $columns, true)) {
                    $orderColumn = 'id';
                }

                $query = DB::table($tableName);

                if ($search !== '') {
                    // Relational search: only show rows whose transaction id is among matched ids from either table.
                    if (!$idCol) {
                        $query->whereRaw('1 = 0');
                    } elseif (is_array($linkedSearchIds) && !empty($linkedSearchIds)) {
                        $query->whereIn($idCol, $linkedSearchIds);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                }

                if ($orderColumn) {
                    $query->orderByDesc($orderColumn);
                }

                // NOTE: Evitamos COUNT() porque puede ser muy costoso en tablas grandes.
                // Para este dashboard usamos paginación fija y cargamos solo la página actual.
                $effectivePages = $pages;
                $effectivePage = $page;
                if ($effectivePage < 1) {
                    $effectivePage = 1;
                }
                if ($effectivePage > $effectivePages) {
                    $effectivePage = $effectivePages;
                }
                $effectiveOffset = ($effectivePage - 1) * $perPage;

                $rows = $query->offset($effectiveOffset)->limit($perPage)->get();

                return [
                    'table' => $tableName,
                    'exists' => true,
                    'columns' => $columns,
                    'rows' => $rows,
                    'total' => 0,
                    'error' => null,
                    'search_applies' => $search === '' ? true : ($idCol !== null && is_array($linkedSearchIds) && !empty($linkedSearchIds)),
                    'pagination' => [
                        'page' => $effectivePage,
                        'per_page' => $perPage,
                        'pages' => $effectivePages,
                        'param' => $param,
                    ],
                ];
            } catch (Throwable $e) {
                return [
                    'table' => $tableName,
                    'exists' => false,
                    'columns' => [],
                    'rows' => [],
                    'total' => 0,
                    'error' => $e->getMessage(),
                    'search_applies' => false,
                    'pagination' => [
                        'page' => $page,
                        'per_page' => $perPage,
                        'pages' => $pages,
                        'param' => $param,
                    ],
                ];
            }
        };

        $data['transacciones'] = [
            'meta' => [
                'per_page' => $perPage,
                'pages' => $pages,
                'page_claro' => $pageClaro,
                'page_pasarela' => $pagePasarela,
                'param_claro' => 'p_claro',
                'param_pasarela' => 'p_pasarela',
                'q' => $search,
            ],
            'cl_pagosclaro' => $withQueryGroup('dashboard|' . $section . '|transacciones|cl_pagosclaro', function () use ($fetchTable, $pageClaro, $offsetClaro) {
                return $fetchTable('cl_pagosclaro', $pageClaro, $offsetClaro, 'p_claro');
            }),
            'gt_pago_pasarela' => $withQueryGroup('dashboard|' . $section . '|transacciones|gt_pago_pasarela', function () use ($fetchTable, $pagePasarela, $offsetPasarela) {
                return $fetchTable('gt_pago_pasarela', $pagePasarela, $offsetPasarela, 'p_pasarela');
            }),
        ];
    }

    return view('dashboard', $data);
})->name('dashboard');

Route::get('/informacion-base-datos', function () {
    return redirect('/dashboard/conexionBD');
})->name('db.info');
