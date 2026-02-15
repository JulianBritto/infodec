<section class="section" id="inicio" data-section="inicio" {{ (($section ?? 'principal') === 'inicio') ? '' : 'hidden' }}>
    <h1>Inicio</h1>
    <div class="grid">
        @php($dbOk = (($db['status'] ?? 'unknown') === 'ok'))
        @php($cacheOk = (($cache['status'] ?? 'unknown') === 'ok'))

        <div class="card third">
            <h2>Base de datos</h2>
            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom: 10px;">
                <span class="pill">Estado: <code>{{ $db['status'] ?? 'unknown' }}</code></span>
                <span class="pill">Latencia: <code>{{ $db['latency_ms'] ?? '—' }}{{ ($db['latency_ms'] ?? null) !== null ? 'ms' : '' }}</code></span>
            </div>
            <div class="kv">
                <div class="k">Conexión</div>
                <div class="v"><code>{{ $db['connection'] ?? '—' }}</code></div>
                <div class="k">Host</div>
                <div class="v"><code>{{ $db['host'] ?? '—' }}</code></div>
                <div class="k">DB</div>
                <div class="v"><code>{{ $db['database'] ?? '—' }}</code></div>
            </div>
            @if (!$dbOk && !empty($db['error']))
                <div class="placeholder" style="margin-top: 10px;">Error: <code>{{ $db['error'] }}</code></div>
            @endif
        </div>

        <div class="card third">
            <h2>Cache</h2>
            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom: 10px;">
                <span class="pill">Estado: <code>{{ $cache['status'] ?? 'unknown' }}</code></span>
                <span class="pill">Driver: <code>{{ $cache['driver'] ?? '—' }}</code></span>
            </div>
            <div class="kv">
                <div class="k">Probe</div>
                <div class="v"><code>{{ $cacheOk ? 'ok' : 'fail' }}</code></div>
                <div class="k">Notas</div>
                <div class="v">Validación rápida (put/get)</div>
            </div>
            @if (!$cacheOk && !empty($cache['error']))
                <div class="placeholder" style="margin-top: 10px;">Error: <code>{{ $cache['error'] }}</code></div>
            @endif
        </div>

        <div class="card third">
            <h2>Storage</h2>
            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom: 10px;">
                <span class="pill">storage/: <code>{{ !empty($storage['writable']) ? 'writable' : 'not writable' }}</code></span>
                <span class="pill">logs/: <code>{{ !empty($storage['logs_writable']) ? 'writable' : 'not writable' }}</code></span>
            </div>
            <div class="kv">
                <div class="k">Ruta</div>
                <div class="v"><code>{{ $storage['storage_path'] ?? '—' }}</code></div>
                <div class="k">framework/cache</div>
                <div class="v"><code>{{ !empty($storage['framework_cache_writable']) ? 'writable' : 'not writable' }}</code></div>
            </div>
        </div>

        <div class="card third">
            <h2>Runtime</h2>
            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom: 10px;">
                <span class="pill">RAM: <code>{{ $runtime['memory_usage_mb'] ?? '—' }}{{ ($runtime['memory_usage_mb'] ?? null) !== null ? 'MB' : '' }}</code></span>
                <span class="pill">Peak: <code>{{ $runtime['peak_memory_mb'] ?? '—' }}{{ ($runtime['peak_memory_mb'] ?? null) !== null ? 'MB' : '' }}</code></span>
            </div>
            <div class="kv">
                <div class="k">PHP</div>
                <div class="v"><code>{{ $app['php_version'] ?? '—' }}</code></div>
                <div class="k">Laravel</div>
                <div class="v"><code>{{ $app['laravel_version'] ?? '—' }}</code></div>
                <div class="k">Server</div>
                <div class="v"><code>{{ $runtime['server_software'] ?? '—' }}</code></div>
            </div>
        </div>

        <div class="card third">
            <h2>Entorno</h2>
            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom: 10px;">
                <span class="pill">Env: <code>{{ $app['env'] ?? '—' }}</code></span>
                <span class="pill">Debug: <code>{{ !empty($app['debug']) ? 'on' : 'off' }}</code></span>
            </div>
            <div class="kv">
                <div class="k">App</div>
                <div class="v"><code>{{ $app['name'] ?? '—' }}</code></div>
                <div class="k">Timezone</div>
                <div class="v"><code>{{ $app['timezone'] ?? '—' }}</code></div>
                <div class="k">Hora</div>
                <div class="v"><code>{{ $app['time'] ?? '—' }}</code></div>
            </div>
        </div>

        <div class="card third">
            <h2>Accesos rápidos</h2>
            <div class="placeholder" style="margin-bottom: 10px;">Atajos para navegación rápida.</div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a class="pill" href="{{ url('/dashboard/transacciones') }}">Transacciones</a>
                <a class="pill" href="{{ url('/dashboard/busqueda-transacciones') }}?cat=personas">Búsqueda</a>
                <a class="pill" href="{{ url('/dashboard/monitoreo') }}">Monitoreo</a>
                <a class="pill" href="{{ url('/dashboard/usuarios') }}">Usuarios</a>
            </div>
        </div>

        <div class="card half">
            <h2>Actividad reciente</h2>
            <div class="placeholder" style="margin-bottom: 10px;">Placeholder (se puede conectar a logs o a transacciones recientes).</div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th>Evento</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><code>—</code></td><td><code>—</code></td><td><code>—</code></td></tr>
                        <tr><td><code>—</code></td><td><code>—</code></td><td><code>—</code></td></tr>
                        <tr><td><code>—</code></td><td><code>—</code></td><td><code>—</code></td></tr>
                        <tr><td><code>—</code></td><td><code>—</code></td><td><code>—</code></td></tr>
                        <tr><td><code>—</code></td><td><code>—</code></td><td><code>—</code></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card half">
            <h2>Distribución (placeholder)</h2>
            <div class="placeholder" style="margin-bottom: 10px;">Ejemplo visual mientras llegan datos reales.</div>

            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                <span class="pill">Aprobadas: <code>—</code></span>
                <span class="pill">Rechazadas: <code>—</code></span>
                <span class="pill">Pendientes: <code>—</code></span>
            </div>

            <div style="margin-top: 12px; border: 1px solid var(--border); border-radius: 999px; overflow: hidden; background: var(--chip2);">
                <div style="display:flex; height: 12px;">
                    <div style="width: 45%; background: var(--chipActive);"></div>
                    <div style="width: 35%; background: var(--chip);"></div>
                    <div style="width: 20%; background: var(--chip2);"></div>
                </div>
            </div>

            <div class="kv" style="margin-top: 12px;">
                <div class="k">Últimos 7 días</div>
                <div class="v">—</div>
                <div class="k">Total hoy</div>
                <div class="v">—</div>
                <div class="k">Valor hoy</div>
                <div class="v">—</div>
            </div>
        </div>
    </div>
</section>
