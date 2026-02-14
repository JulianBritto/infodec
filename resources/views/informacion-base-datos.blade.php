<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Claro Colombia - Información base de datos</title>

    <style>
        :root { color-scheme: light dark; }
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji";
            background: #0b1220;
            color: #e5e7eb;
        }
        a { color: inherit; }

        .layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }

        .sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            padding: 18px;
            background: rgba(15, 23, 42, 0.75);
            border-right: 1px solid rgba(148, 163, 184, 0.18);
            overflow: auto;
        }

        .brand {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 14px;
        }

        .brand-title {
            font-weight: 800;
            font-size: 14px;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }

        .toggle {
            border: 1px solid rgba(148, 163, 184, 0.25);
            background: rgba(148, 163, 184, 0.12);
            color: #cbd5e1;
            border-radius: 10px;
            padding: 8px 10px;
            cursor: pointer;
            font-size: 12px;
        }

        .nav {
            display: grid;
            gap: 8px;
            margin-top: 12px;
        }

        .nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 10px;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.18);
            background: rgba(148, 163, 184, 0.08);
            text-decoration: none;
            font-size: 13px;
        }

        .nav a:hover, .nav a:focus {
            outline: none;
            border-color: rgba(148, 163, 184, 0.28);
            background: rgba(148, 163, 184, 0.12);
        }

        .nav .icon { width: 18px; text-align: center; opacity: 0.9; }

        .side-meta {
            margin-top: 16px;
            font-size: 12px;
            color: #94a3b8;
            line-height: 1.5;
        }

        .content {
            padding: 22px 16px 60px;
        }

        .wrap { max-width: 1100px; margin: 0 auto; }

        .top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .title { font-size: 20px; font-weight: 800; letter-spacing: 0.2px; }
        .subtitle { margin-top: 6px; font-size: 13px; color: #94a3b8; }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.12);
            border: 1px solid rgba(148, 163, 184, 0.25);
            font-size: 12px;
            color: #cbd5e1;
            white-space: nowrap;
        }

        .card {
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.65);
            border: 1px solid rgba(148, 163, 184, 0.18);
            padding: 14px;
        }

        .card h2 {
            margin: 0 0 10px 0;
            font-size: 14px;
            font-weight: 700;
            color: #e2e8f0;
        }

        .kv {
            display: grid;
            grid-template-columns: 170px 1fr;
            gap: 8px 10px;
            font-size: 13px;
        }

        .k { color: #94a3b8; }
        .v { color: #e5e7eb; overflow-wrap: anywhere; }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            display: inline-block;
            background: #64748b;
            box-shadow: 0 0 0 3px rgba(100,116,139,0.2);
        }

        .ok { background: #22c55e; box-shadow: 0 0 0 3px rgba(34,197,94,0.18); }
        .fail { background: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,0.18); }

        .muted { color: #94a3b8; font-size: 12px; margin-top: 10px; }
        .footer { margin-top: 18px; color: #94a3b8; font-size: 12px; }
        code { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 12px; }

        body.sidebar-collapsed .layout { grid-template-columns: 76px 1fr; }
        body.sidebar-collapsed .brand-title,
        body.sidebar-collapsed .nav span,
        body.sidebar-collapsed .side-meta {
            display: none;
        }
        body.sidebar-collapsed .nav a { justify-content: center; }
        body.sidebar-collapsed .toggle { padding: 8px; }

        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; }
            .sidebar { position: relative; height: auto; }
            body.sidebar-collapsed .layout { grid-template-columns: 1fr; }
            body.sidebar-collapsed .brand-title,
            body.sidebar-collapsed .nav span,
            body.sidebar-collapsed .side-meta { display: initial; }
        }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar" aria-label="Menú">
        <div class="brand">
            <div class="brand-title">Claro Colombia</div>
            <button class="toggle" id="sidebarToggle" type="button" aria-label="Plegar o desplegar menú" aria-expanded="true">
                Menú
            </button>
        </div>

        <nav class="nav">
            <a href="{{ route('dashboard') }}">
                <span class="icon">←</span>
                <span>Volver a Dashboard</span>
            </a>
            <a href="{{ route('db.info') }}">
                <span class="icon">DB</span>
                <span>Información base de datos</span>
            </a>
        </nav>

        <div class="side-meta">
            <div><strong>Ambiente:</strong> <code>{{ $app['env'] ?? '' }}</code></div>
            <div><strong>Hora:</strong> <code>{{ $app['time'] ?? '' }}</code></div>
        </div>
    </aside>

    <main class="content">
        <div class="wrap">
            <div class="top">
                <div>
                    <div class="title">Información base de datos</div>
                    <div class="subtitle">{{ $app['time'] ?? '' }} ({{ $app['timezone'] ?? '' }})</div>
                </div>
                <div class="pill">
                    <span class="badge">
                        <span class="dot {{ ($db['status'] ?? 'unknown') === 'ok' ? 'ok' : 'fail' }}"></span>
                        DB: {{ strtoupper($db['status'] ?? 'unknown') }}
                    </span>
                </div>
            </div>

            <div class="card">
                <h2>Estado de conexión</h2>
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

            <div class="footer">Ruta: <code>/informacion-base-datos</code></div>
        </div>
    </main>
</div>

<script>
    (function () {
        var btn = document.getElementById('sidebarToggle');
        if (!btn) return;

        function setCollapsed(collapsed) {
            document.body.classList.toggle('sidebar-collapsed', collapsed);
            btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            try { localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0'); } catch (e) {}
        }

        var initial = false;
        try { initial = localStorage.getItem('sidebarCollapsed') === '1'; } catch (e) {}
        setCollapsed(initial);

        btn.addEventListener('click', function () {
            setCollapsed(!document.body.classList.contains('sidebar-collapsed'));
        });
    })();
</script>
</body>
</html>
