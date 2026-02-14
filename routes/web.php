<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

$getMonitoringSnapshot = function (): array {
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
        'status' => 'unknown',
        'latency_ms' => null,
        'error' => null,
        'migrations_table' => null,
    ];

    try {
        $start = microtime(true);
        DB::connection()->getPdo();
        $db['latency_ms'] = (int) round((microtime(true) - $start) * 1000);
        $db['status'] = 'ok';
        $db['migrations_table'] = Schema::hasTable('migrations') ? 'present' : 'missing';
    } catch (Throwable $e) {
        $db['status'] = 'fail';
        $db['error'] = $e->getMessage();
    }

    $cache = [
        'driver' => (string) config('cache.default'),
        'status' => 'unknown',
        'error' => null,
    ];

    try {
        $probeKey = 'dashboard_probe';
        Cache::put($probeKey, 'ok', 10);
        $cache['status'] = Cache::get($probeKey) === 'ok' ? 'ok' : 'fail';
    } catch (Throwable $e) {
        $cache['status'] = 'fail';
        $cache['error'] = $e->getMessage();
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
    // /dashboard (sin sección) => vista principal (introducción)
    $section = $section ?: 'principal';
    if (!in_array($section, ['principal', 'inicio', 'transacciones', 'busqueda-transacciones', 'monitoreo', 'estadisticas', 'usuarios', 'conexionBD'], true)) {
        abort(404);
    }

    $data = $getMonitoringSnapshot() + ['section' => $section];

    if ($section === 'busqueda-transacciones') {
        $cat = (string) request()->query('cat', 'personas');
        $cat = trim($cat) === '' ? 'personas' : $cat;

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
        ];

        if ($cat === 'personas') {
            try {
                $requiredTables = ['gt_pago_pasarela', 'cl_pagosclaro', 'gt_valores'];
                foreach ($requiredTables as $tName) {
                    if (!Schema::hasTable($tName)) {
                        throw new RuntimeException('La tabla ' . $tName . ' no existe en la base de datos.');
                    }
                }

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
                        // For numeric ids, prefer exact match on ID_TRANSACCION
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

                $query->orderByDesc('a.FECHA_INICIO');

                $rows = $query->limit(50)->get();
                $data['busqueda']['personas']['rows'] = $rows;
            } catch (Throwable $e) {
                $data['busqueda']['personas']['error'] = $e->getMessage();
                $data['busqueda']['personas']['rows'] = [];
            }
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
            $idBucket = [];
            $tableConfigs = [
                ['name' => 'cl_pagosclaro', 'id_col' => 'CLACO_NUMERO'],
                ['name' => 'gt_pago_pasarela', 'id_col' => 'ID_TRANSACCION'],
            ];

            foreach ($tableConfigs as $cfg) {
                if (!Schema::hasTable($cfg['name'])) {
                    continue;
                }

                try {
                    $cols = Schema::getColumnListing($cfg['name']);
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
            $linkedSearchIds = empty($idBucket) ? [] : $idBucket;
        }

        $fetchTable = function (string $tableName, int $page, int $offset, string $param) use ($perPage, $pages, $search, $linkedSearchIds): array {
            if (!Schema::hasTable($tableName)) {
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

            try {
                $columns = Schema::getColumnListing($tableName);

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

                $total = (clone $query)->count();
                $effectivePages = $pages;
                if ($search !== '') {
                    $effectivePages = (int) max(1, min($pages, (int) ceil($total / $perPage)));
                }

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
                    'total' => (int) $total,
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
                    'exists' => true,
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
            'cl_pagosclaro' => $fetchTable('cl_pagosclaro', $pageClaro, $offsetClaro, 'p_claro'),
            'gt_pago_pasarela' => $fetchTable('gt_pago_pasarela', $pagePasarela, $offsetPasarela, 'p_pasarela'),
        ];
    }

    return view('dashboard', $data);
})->name('dashboard');

Route::get('/informacion-base-datos', function () {
    return redirect('/dashboard/conexionBD');
})->name('db.info');
