<?php

namespace App\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Global query timing log (HTTP requests only). Visible only in the "Tiempo ejecucion Querys" view.
        DB::listen(function (QueryExecuted $query) {
            static $isWriting = false;

            if ($isWriting) {
                return;
            }

            // Only record web requests; avoid noise from CLI, migrations, etc.
            if (app()->runningInConsole()) {
                return;
            }

            try {
                $sql = (string) ($query->sql ?? '');

                // Avoid recursion / noise: don't log queries against the log table itself.
                if ($sql !== '' && stripos($sql, 'query_execution_logs') !== false) {
                    return;
                }

                $req = request();
                $path = method_exists($req, 'path') ? (string) $req->path() : null;
                $url = method_exists($req, 'fullUrl') ? (string) $req->fullUrl() : null;

                $section = null;
                if (is_string($path) && preg_match('#^dashboard(?:/([^/?]+))?#', $path, $m)) {
                    $section = $m[1] ?? null;
                    $section = $section ?: 'inicio';
                }

                // Only log queries that are explicitly tagged (reduces overhead massively).
                // Tagging is set via routes/web.php using the withQueryGroup() helper.
                if (!app()->bound('query_log_group')) {
                    return;
                }

                $group = null;
                try {
                    $group = (string) app('query_log_group');
                } catch (\Throwable $e) {
                    $group = null;
                }

                if (!is_string($group) || trim($group) === '') {
                    return;
                }

                $timeMs = (int) round((float) ($query->time ?? 0));
                if ($timeMs < 0) {
                    $timeMs = 0;
                }

                $isWriting = true;
                DB::table('query_execution_logs')->insert([
                    'executed_at' => now(),
                    'connection' => (string) ($query->connectionName ?? ''),
                    'method' => method_exists($req, 'method') ? (string) $req->method() : null,
                    'path' => $path,
                    'url' => $url,
                    'section' => $section,
                    'group' => $group,
                    'time_ms' => $timeMs,
                    'sql' => $sql,
                    'bindings' => ($query->bindings ?? null) !== null ? json_encode($query->bindings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                    'ip' => method_exists($req, 'ip') ? (string) $req->ip() : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // Never break the request due to logging
            } finally {
                $isWriting = false;
            }
        });
    }
}
