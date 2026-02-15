<section class="section" id="conexionBD" data-section="conexionBD" {{ (($section ?? 'principal') === 'conexionBD') ? '' : 'hidden' }}>
    <h1>Conexión base de datos</h1>
    <div class="grid">
        <div class="card half">
            <h2>Aplicación</h2>
            <div class="kv">
                <div class="k">Entorno</div><div class="v"><code>{{ $app['env'] ?? '' }}</code></div>
                <div class="k">Debug</div><div class="v"><code>{{ !empty($app['debug']) ? 'true' : 'false' }}</code></div>
                <div class="k">APP_URL</div><div class="v"><code>{{ $app['url'] ?? '' }}</code></div>
                <div class="k">Locale</div><div class="v"><code>{{ $app['locale'] ?? '' }}</code></div>
                <div class="k">Laravel</div><div class="v"><code>{{ $app['laravel_version'] ?? '' }}</code></div>
                <div class="k">PHP</div><div class="v"><code>{{ $app['php_version'] ?? '' }}</code></div>
            </div>
        </div>

        <div class="card half">
            <h2>Runtime</h2>
            <div class="kv">
                <div class="k">Server</div><div class="v"><code>{{ $runtime['server_software'] ?? 'N/A' }}</code></div>
                <div class="k">Memoria (uso)</div><div class="v"><code>{{ $runtime['memory_usage_mb'] ?? '' }} MB</code></div>
                <div class="k">Memoria (pico)</div><div class="v"><code>{{ $runtime['peak_memory_mb'] ?? '' }} MB</code></div>
                <div class="k">memory_limit</div><div class="v"><code>{{ $app['memory_limit'] ?? '' }}</code></div>
                <div class="k">max_execution_time</div><div class="v"><code>{{ $app['max_execution_time'] ?? '' }}</code></div>
            </div>
            <div class="muted">Tip: si esta página carga lento, revisa DB/cache primero.</div>
        </div>

        <div class="card third">
            <h2>Base de Datos</h2>
            <div class="kv">
                <div class="k">Conexión</div><div class="v"><code>{{ $db['connection'] ?? '' }}</code></div>
                <div class="k">Host</div><div class="v"><code>{{ $db['host'] ?? '' }}</code></div>
                <div class="k">Database</div><div class="v"><code>{{ $db['database'] ?? '' }}</code></div>
                <div class="k">Estado</div>
                <div class="v">
                    <span class="badge">
                        <span class="dot {{ ($db['status'] ?? 'unknown') === 'ok' ? 'ok' : 'fail' }}"></span>
                        <code>{{ strtoupper($db['status'] ?? 'unknown') }}</code>
                    </span>
                </div>
                <div class="k">Latencia</div><div class="v"><code>{{ $db['latency_ms'] !== null ? $db['latency_ms'].' ms' : 'N/A' }}</code></div>
                <div class="k">Migrations</div><div class="v"><code>{{ $db['migrations_table'] ?? 'N/A' }}</code></div>
            </div>
            @if (!empty($db['error']))
                <div class="muted">Error: <code>{{ $db['error'] }}</code></div>
            @endif
        </div>

        <div class="card third">
            <h2>Cache</h2>
            <div class="kv">
                <div class="k">Driver</div><div class="v"><code>{{ $cache['driver'] ?? '' }}</code></div>
                <div class="k">Estado</div>
                <div class="v">
                    <span class="badge">
                        <span class="dot {{ ($cache['status'] ?? 'unknown') === 'ok' ? 'ok' : 'fail' }}"></span>
                        <code>{{ strtoupper($cache['status'] ?? 'unknown') }}</code>
                    </span>
                </div>
            </div>
            @if (!empty($cache['error']))
                <div class="muted">Error: <code>{{ $cache['error'] }}</code></div>
            @endif
        </div>

        <div class="card third">
            <h2>Storage</h2>
            <div class="kv">
                <div class="k">storage/</div>
                <div class="v">
                    <span class="badge">
                        <span class="dot {{ !empty($storage['writable']) ? 'ok' : 'fail' }}"></span>
                        <code>{{ !empty($storage['writable']) ? 'WRITABLE' : 'NOT WRITABLE' }}</code>
                    </span>
                </div>
                <div class="k">framework/cache</div>
                <div class="v">
                    <span class="badge">
                        <span class="dot {{ !empty($storage['framework_cache_writable']) ? 'ok' : 'warn' }}"></span>
                        <code>{{ !empty($storage['framework_cache_writable']) ? 'WRITABLE' : 'CHECK' }}</code>
                    </span>
                </div>
                <div class="k">logs/</div>
                <div class="v">
                    <span class="badge">
                        <span class="dot {{ !empty($storage['logs_writable']) ? 'ok' : 'warn' }}"></span>
                        <code>{{ !empty($storage['logs_writable']) ? 'WRITABLE' : 'CHECK' }}</code>
                    </span>
                </div>
            </div>
            <div class="muted"><code>{{ $storage['storage_path'] ?? '' }}</code></div>
        </div>
    </div>
</section>
