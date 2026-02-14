<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Claro Colombia - Dashboard</title>

    <style>
        :root {
            color-scheme: light dark;
            --trans-table-rows: 5;
            --trans-row-h: 44px;
            --trans-head-h: 44px;
            --trans-scroll-gap: 16px;

            /* Theme (default: dark) */
            --bg: #0b1220;
            --text: #e5e7eb;
            --muted: #94a3b8;
            --panel: rgba(15, 23, 42, 0.65);
            --panelStrong: rgba(15, 23, 42, 0.75);
            --panelSolid: rgba(15, 23, 42, 0.95);
            --border: rgba(148, 163, 184, 0.18);
            --borderStrong: rgba(148, 163, 184, 0.25);
            --chip: rgba(148, 163, 184, 0.12);
            --chip2: rgba(148, 163, 184, 0.08);
            --chipActive: rgba(148, 163, 184, 0.16);
        }

        body.theme-light {
            --bg: #f8fafc;
            --text: #0f172a;
            --muted: #475569;
            --panel: rgba(255, 255, 255, 0.90);
            --panelStrong: rgba(255, 255, 255, 0.95);
            --panelSolid: rgba(255, 255, 255, 0.98);
            --border: rgba(15, 23, 42, 0.12);
            --borderStrong: rgba(15, 23, 42, 0.18);
            --chip: rgba(15, 23, 42, 0.06);
            --chip2: rgba(15, 23, 42, 0.04);
            --chipActive: rgba(15, 23, 42, 0.08);
        }
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji";
            background: var(--bg);
            color: var(--text);
            overflow: hidden;
        }
        a { color: inherit; }

        .layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            height: 100vh;
        }

        .sidebar {
            height: 100vh;
            padding: 18px;
            background: var(--panelStrong);
            border-right: 1px solid var(--border);
            overflow: hidden;
            display: flex;
            flex-direction: column;
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
            border: 1px solid var(--borderStrong);
            background: var(--chip);
            color: inherit;
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
            border: 1px solid var(--border);
            background: var(--chip2);
            text-decoration: none;
            font-size: 13px;
        }


        .busqueda-menu {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        .busqueda-menu a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 999px;
            background: var(--chip2);
            border: 1px solid var(--border);
            text-decoration: none;
            font-size: 12px;
            color: inherit;
            white-space: nowrap;
        }

        .busqueda-menu a:hover, .busqueda-menu a:focus {
            outline: none;
            border-color: rgba(148, 163, 184, 0.28);
            background: var(--chip);
        }

        .nav a:hover, .nav a:focus {
            outline: none;
            border-color: rgba(148, 163, 184, 0.28);
            background: var(--chip);
        }

        .side-meta {
            margin-top: 16px;
            font-size: 12px;
            color: var(--muted);
            line-height: 1.5;
        }

        .content {
            padding: 22px 16px 60px;
            height: 100vh;
            overflow: auto;
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
        body.theme-light .subtitle { color: var(--muted); }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 999px;
            background: var(--chip);
            border: 1px solid var(--borderStrong);
            font-size: 12px;
            color: inherit;
            white-space: nowrap;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 14px;
        }
        .card {
            grid-column: span 12;
            border-radius: 14px;
            background: var(--panel);
            border: 1px solid var(--border);
            padding: 14px;
        }
        @media (min-width: 900px) {
            .card.half { grid-column: span 6; }
            .card.third { grid-column: span 4; }
        }
        .card h2 {
            margin: 0 0 10px 0;
            font-size: 14px;
            font-weight: 700;
            color: inherit;
        }
        .kv {
            display: grid;
            grid-template-columns: 170px 1fr;
            gap: 8px 10px;
            font-size: 13px;
        }
        .k { color: var(--muted); }
        .v { color: var(--text); overflow-wrap: anywhere; }
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
        .warn { background: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.18); }
        .muted { color: var(--muted); font-size: 12px; margin-top: 10px; }
        .footer { margin-top: 18px; color: var(--muted); font-size: 12px; }
        code { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 12px; }

        .section {
            scroll-margin-top: 14px;
            margin-top: 18px;
        }

        .section h1 {
            margin: 0 0 10px;
            font-size: 16px;
            font-weight: 800;
            color: #e2e8f0;
        }

        .placeholder {
            border-radius: 14px;
            background: var(--panel);
            border: 1px solid var(--border);
            padding: 14px;
            font-size: 13px;
            color: inherit;
        }

        .table-wrap {
            overflow: auto;
            border-radius: 14px;
            border: 1px solid var(--border);
            background: var(--panel);
            height: calc(var(--trans-head-h) + (var(--trans-row-h) * var(--trans-table-rows)) + var(--trans-scroll-gap));
            overflow-y: hidden;
            overflow-x: auto;
            padding-bottom: var(--trans-scroll-gap);
            box-sizing: border-box;
            scrollbar-gutter: stable both-edges;
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, 0.35) var(--panel);
        }

        /* When searching, fit table height to actual rows (avoid blank space) */
        .table-wrap.search-fit {
            height: auto;
            padding-bottom: 0;
            overflow-y: visible;
            scrollbar-gutter: auto;
        }

        /* Subtle scrollbar styling (WebKit/Blink) */
        .table-wrap::-webkit-scrollbar {
            height: 10px;
            width: 10px;
        }
        .table-wrap::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.55);
            border-radius: 999px;
        }
        .table-wrap::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.28);
            border-radius: 999px;
            border: 2px solid rgba(15, 23, 42, 0.55);
        }
        .table-wrap::-webkit-scrollbar-thumb:hover {
            background: rgba(148, 163, 184, 0.42);
        }
        .table-wrap::-webkit-scrollbar-corner {
            background: rgba(15, 23, 42, 0.55);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            min-width: 780px;
        }

        thead th {
            position: sticky;
            top: 0;
            background: var(--panelSolid);
            color: inherit;
            text-align: left;
            padding: 0 10px;
            height: var(--trans-head-h);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        tbody td {
            padding: 0 10px;
            height: var(--trans-row-h);
            border-bottom: 1px solid rgba(148, 163, 184, 0.12);
            vertical-align: top;
            color: inherit;
            white-space: nowrap;
        }

        thead th > code,
        tbody td > code {
            display: block;
            line-height: var(--trans-row-h);
            white-space: nowrap;
        }

        thead th > code {
            line-height: var(--trans-head-h);
        }

        tbody tr:hover td {
            background: rgba(148, 163, 184, 0.06);
        }

        .table-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin: 0 0 10px;
            font-size: 14px;
            font-weight: 800;
            color: inherit;
        }

        .table-sub {
            font-size: 12px;
            color: var(--muted);
            margin-top: 6px;
        }

        .searchbar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 12px 0 6px;
            flex-wrap: wrap;
        }

        .searchbar input {
            flex: 1;
            min-width: 240px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: var(--panel);
            color: var(--text);
            padding: 10px 12px;
            font-size: 13px;
            outline: none;
        }

        .searchbar input:focus {
            border-color: rgba(148, 163, 184, 0.28);
        }

        .searchbar button {
            border-radius: 12px;
            border: 1px solid var(--borderStrong);
            background: var(--chip);
            color: var(--text);
            padding: 10px 12px;
            font-size: 13px;
            cursor: pointer;
            white-space: nowrap;
        }

        .search-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .searchbar button:hover, .searchbar button:focus {
            border-color: rgba(148, 163, 184, 0.28);
            background: var(--chipActive);
            outline: none;
        }

        .theme-wrap {
            position: sticky;
            bottom: 14px;
            margin-top: 16px;
            padding-top: 12px;
            padding-bottom: 4px;
            background: var(--panelStrong);
        }

        .theme-toggle {
            width: 100%;
            position: relative;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            border-radius: 999px;
            border: 1px solid var(--borderStrong);
            background: var(--chip2);
            padding: 4px;
            cursor: pointer;
            user-select: none;
        }

        .theme-toggle .opt {
            position: relative;
            z-index: 2;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 36px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.2px;
            color: var(--muted);
        }

        .theme-toggle .ico {
            font-size: 14px;
            line-height: 1;
        }

        /* When sidebar is collapsed, show only icons */
        body.sidebar-collapsed .theme-toggle .label {
            display: none;
        }

        body.sidebar-collapsed .theme-toggle .opt {
            gap: 0;
            width: 100%;
        }

        .theme-toggle .thumb {
            position: absolute;
            top: 4px;
            bottom: 4px;
            width: calc(50% - 6px);
            left: calc(50% + 2px);
            border-radius: 999px;
            background: var(--chip);
            border: 1px solid var(--border);
            transition: left 180ms ease, background 180ms ease, border-color 180ms ease;
        }

        body.theme-light .theme-toggle .thumb {
            left: 4px;
        }

        body.theme-light .theme-toggle .opt.light,
        body:not(.theme-light) .theme-toggle .opt.dark {
            color: var(--text);
        }

        .pager {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 14px;
        }

        .pager a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            padding: 8px 10px;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.18);
            background: rgba(148, 163, 184, 0.08);
            text-decoration: none;
            font-size: 13px;
            color: #e5e7eb;
        }

        .pager a:hover, .pager a:focus {
            outline: none;
            border-color: rgba(148, 163, 184, 0.28);
            background: rgba(148, 163, 184, 0.12);
        }

        .pager a.active {
            border-color: rgba(148, 163, 184, 0.38);
            background: rgba(148, 163, 184, 0.16);
            font-weight: 800;
        }

        /* Collapsed sidebar */
        body.sidebar-collapsed .layout { grid-template-columns: 76px 1fr; }
        body.sidebar-collapsed .brand-title,
        body.sidebar-collapsed .nav span,
        body.sidebar-collapsed .side-meta {
            display: none;
        }
        body.sidebar-collapsed .nav a { justify-content: center; }
        body.sidebar-collapsed .toggle { padding: 8px; }
        .nav .icon { width: 18px; text-align: center; opacity: 0.9; }

        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; }
            .sidebar { position: relative; height: auto; }
            body.sidebar-collapsed .layout { grid-template-columns: 1fr; }
            body.sidebar-collapsed .brand-title,
            body.sidebar-collapsed .nav span,
            body.sidebar-collapsed .side-meta { display: initial; }

            body { overflow: auto; }
            .content { height: auto; overflow: visible; }
            .sidebar { overflow: visible; }
        }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar" aria-label="Menú">
        <div class="brand">
            <a class="brand-title" href="{{ url('/dashboard') }}" data-section-link="principal" style="text-decoration:none;">Claro Colombia</a>
            <button class="toggle" id="sidebarToggle" type="button" aria-label="Plegar o desplegar menú" aria-expanded="true">
                Menú
            </button>
        </div>

        <nav class="nav">
            <a href="{{ url('/dashboard') }}" data-section-link="principal">
                <span class="icon">H</span>
                <span>Inicio</span>
            </a>
            <a href="{{ url('/dashboard/transacciones') }}" data-section-link="transacciones">
                <span class="icon">T</span>
                <span>Transacciones</span>
            </a>
            <a href="{{ url('/dashboard/busqueda-transacciones') }}" data-section-link="busqueda-transacciones">
                <span class="icon">B</span>
                <span>Búsqueda</span>
            </a>
            <a href="{{ url('/dashboard/monitoreo') }}" data-section-link="monitoreo">
                <span class="icon">M</span>
                <span>Monitoreo</span>
            </a>
            <a href="{{ url('/dashboard/estadisticas') }}" data-section-link="estadisticas">
                <span class="icon">E</span>
                <span>Estadísticas</span>
            </a>
            <a href="{{ url('/dashboard/usuarios') }}" data-section-link="usuarios">
                <span class="icon">U</span>
                <span>Usuarios</span>
            </a>
            <a href="{{ url('/dashboard/conexionBD') }}" data-section-link="conexionBD">
                <span class="icon">DB</span>
                <span>Conexión BD</span>
            </a>
        </nav>

        <div class="side-meta">
            <div><strong>Ambiente:</strong> <code>{{ $app['env'] ?? '' }}</code></div>
            <div><strong>Hora:</strong> <code>{{ $app['time'] ?? '' }}</code></div>
        </div>

        <div class="theme-wrap" aria-label="Tema">
            <button class="theme-toggle" id="themeToggle" type="button" aria-label="Cambiar tema" aria-pressed="false">
                <span class="thumb" aria-hidden="true"></span>
                <span class="opt light"><span class="ico" aria-hidden="true">☀</span><span class="label">Claro</span></span>
                <span class="opt dark"><span class="ico" aria-hidden="true">🌙</span><span class="label">Oscuro</span></span>
            </button>
        </div>
    </aside>

    <main class="content">
        <div class="wrap">
            <div class="top">
                <div>
                    <div class="title">Claro Colombia</div>
                    <div class="subtitle">
                        Dashboard · {{ $app['time'] ?? '' }} ({{ $app['timezone'] ?? '' }})
                    </div>
                </div>
                @if ((($section ?? 'principal') === 'conexionBD'))
                    <div class="pill">
                        <span class="badge">
                            <span class="dot {{ ($db['status'] ?? 'unknown') === 'ok' ? 'ok' : 'fail' }}"></span>
                            DB: {{ strtoupper($db['status'] ?? 'unknown') }}
                        </span>
                    </div>
                @endif
            </div>

            <section class="section" id="principal" data-section="principal" {{ (($section ?? 'principal') === 'principal') ? '' : 'hidden' }}>
                <h1>Inicio</h1>
                <div class="placeholder">
                    Bienvenido al dashboard de <strong>Claro Colombia</strong>.<br>
                    Esta vista es solo una introducción. Más adelante puedes agregar más información aquí.
                </div>
            </section>

            <section class="section" id="inicio" data-section="inicio" {{ (($section ?? 'principal') === 'inicio') ? '' : 'hidden' }}>
                <h1>Inicio</h1>
                <div class="grid">
                    <div class="card half">
                        <h2>Gráfico 1</h2>
                        <div class="placeholder" style="height: 220px; display:flex; align-items:center; justify-content:center;">Gráfico vacío</div>
                    </div>
                    <div class="card half">
                        <h2>Gráfico 2</h2>
                        <div class="placeholder" style="height: 220px; display:flex; align-items:center; justify-content:center;">Gráfico vacío</div>
                    </div>
                    <div class="card half">
                        <h2>Gráfico 3</h2>
                        <div class="placeholder" style="height: 220px; display:flex; align-items:center; justify-content:center;">Gráfico vacío</div>
                    </div>
                    <div class="card half">
                        <h2>Gráfico 4</h2>
                        <div class="placeholder" style="height: 220px; display:flex; align-items:center; justify-content:center;">Gráfico vacío</div>
                    </div>
                </div>
            </section>

            <section class="section" id="transacciones" data-section="transacciones" {{ (($section ?? 'principal') === 'transacciones') ? '' : 'hidden' }}>
                <h1>Transacciones</h1>
                @php($t = $transacciones ?? null)

                @if (!$t)
                    <div class="placeholder">No hay datos de transacciones cargados.</div>
                @else
                    @php($meta = $t['meta'] ?? ['per_page' => 5, 'pages' => 4, 'page_claro' => 1, 'page_pasarela' => 1, 'param_claro' => 'p_claro', 'param_pasarela' => 'p_pasarela'])
                    @php($activeQ = trim((string) ($meta['q'] ?? '')))

                    <form class="searchbar" id="transSearch" autocomplete="off">
                        <input
                            type="text"
                            id="transSearchInput"
                            placeholder="Buscar por CUS, NUMERO_DOCUMENTO, ID_TRANSACCION, EMAIL, CLACO_NUMERO"
                            value="{{ (string) ($meta['q'] ?? '') }}"
                        />
                        <div class="search-actions">
                            <button type="button" id="transClearBtn">Limpiar búsqueda</button>
                            <button type="submit">Buscar</button>
                        </div>
                    </form>

                    @php($shownAny = false)
                    @foreach (['cl_pagosclaro' => 'cl_pagosclaro', 'gt_pago_pasarela' => 'gt_pago_pasarela'] as $key => $label)
                        @php($info = $t[$key] ?? null)
                        @php($rowsObj = $info['rows'] ?? null)
                        @php($rowsCount = (is_object($rowsObj) && method_exists($rowsObj, 'count')) ? (int) $rowsObj->count() : 0)

                        @if ($activeQ !== '' && $rowsCount === 0)
                            @continue
                        @endif

                        @php($shownAny = true)
                        @php($pg = ($info['pagination'] ?? ['page' => 1, 'per_page' => ($meta['per_page'] ?? 5), 'pages' => ($meta['pages'] ?? 4), 'param' => ($key === 'cl_pagosclaro' ? ($meta['param_claro'] ?? 'p_claro') : ($meta['param_pasarela'] ?? 'p_pasarela'))]))
                        <div class="section" style="margin-top: 14px;" data-trans-table="{{ $key }}">
                            <div class="table-title">
                                <span>{{ $label }}</span>
                                <span class="pill">
                                    <span class="badge">
                                        <span class="dot {{ !empty($info['exists']) && empty($info['error']) ? 'ok' : 'fail' }}"></span>
                                        {{ !empty($info['exists']) ? 'OK' : 'MISSING' }}
                                    </span>
                                </span>
                            </div>

                            @if (!$info)
                                <div class="placeholder">Sin información.</div>
                            @elseif (empty($info['exists']))
                                <div class="placeholder">La tabla <code>{{ $label }}</code> no existe en la base de datos.</div>
                            @elseif (!empty($info['error']))
                                <div class="placeholder">Error consultando <code>{{ $label }}</code>: <code>{{ $info['error'] }}</code></div>
                            @elseif (empty($info['columns']))
                                <div class="placeholder">No se pudieron leer las columnas de <code>{{ $label }}</code>.</div>
                            @else
                                <div class="table-sub">Mostrando top {{ (int) ($pg['per_page'] ?? 5) }} · Página {{ (int) ($pg['page'] ?? 1) }} de {{ (int) ($pg['pages'] ?? 4) }} (orden: <code>created_at</code> o <code>id</code> desc).</div>
                                <div class="table-wrap {{ $activeQ !== '' ? 'search-fit' : '' }}" style="margin-top: 10px;">
                                    <table>
                                        <thead>
                                            <tr>
                                                @foreach ($info['columns'] as $col)
                                                    <th><code>{{ $col }}</code></th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($info['rows'] as $row)
                                                <tr>
                                                    @foreach ($info['columns'] as $col)
                                                        @php($val = $row->$col ?? null)
                                                        <td><code>{{ is_scalar($val) || $val === null ? (string) $val : json_encode($val) }}</code></td>
                                                    @endforeach
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="{{ count($info['columns']) }}" class="muted">Sin registros.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                @if (((int) ($pg['pages'] ?? 1)) > 1)
                                    <div class="pager" aria-label="Paginación {{ $label }}">
                                        @php($otherParam = ($key === 'cl_pagosclaro') ? ($meta['param_pasarela'] ?? 'p_pasarela') : ($meta['param_claro'] ?? 'p_claro'))
                                        @php($otherPage = ($key === 'cl_pagosclaro') ? (int) ($meta['page_pasarela'] ?? 1) : (int) ($meta['page_claro'] ?? 1))
                                        @php($q = (string) ($meta['q'] ?? ''))

                                        @for ($i = 1; $i <= ((int) ($pg['pages'] ?? 1)); $i++)
                                            <a
                                                href="{{ url('/dashboard/transacciones') . '?' . (($pg['param'] ?? 'p') . '=' . $i) . '&' . ($otherParam . '=' . $otherPage) . ($q !== '' ? ('&q=' . urlencode($q)) : '') }}"
                                                class="{{ ((int) ($pg['page'] ?? 1)) === $i ? 'active' : '' }}"
                                            >{{ $i }}</a>
                                        @endfor
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endforeach

                    @if ($activeQ !== '' && !$shownAny)
                        <div class="placeholder" style="margin-top: 14px;">Sin resultados para la búsqueda: <code>{{ $activeQ }}</code></div>
                    @endif
                @endif
            </section>

            <section class="section" id="busqueda-transacciones" data-section="busqueda-transacciones" {{ (($section ?? 'principal') === 'busqueda-transacciones') ? '' : 'hidden' }}>
                <h1>Búsqueda de transacciones</h1>
                @php($busquedaCat = (string) request()->query('cat', 'personas'))
                @php($busquedaTitles = [
                    'personas' => 'Portal Personas',
                    'empresas' => 'Portal Empresas',
                    'ecommerce' => 'Portal ecommerce',
                ])
                @php($busquedaTitle = $busquedaTitles[$busquedaCat] ?? 'Portal Personas')
                <div class="placeholder">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                        <strong id="busquedaTitle">{{ $busquedaTitle }}</strong>
                        <span class="pill">Cat: <code id="busquedaCatLabel">{{ $busquedaCat }}</code></span>
                    </div>
                    <div class="busqueda-menu" aria-label="Categorías de búsqueda">
                        <a href="{{ url('/dashboard/busqueda-transacciones') }}?cat=personas" data-busqueda-cat="personas">Portal Personas</a>
                        <a href="{{ url('/dashboard/busqueda-transacciones') }}?cat=empresas" data-busqueda-cat="empresas">Portal Empresas</a>
                        <a href="{{ url('/dashboard/busqueda-transacciones') }}?cat=ecommerce" data-busqueda-cat="ecommerce">Portal ecommerce</a>
                    </div>
                    <div class="muted" id="busquedaMsg">Se estará agregando información muy pronto.</div>
                </div>
            </section>

            <section class="section" id="monitoreo" data-section="monitoreo" {{ (($section ?? 'principal') === 'monitoreo') ? '' : 'hidden' }}>
                <h1>Monitoreo</h1>
            </section>

            <section class="section" id="estadisticas" data-section="estadisticas" {{ (($section ?? 'principal') === 'estadisticas') ? '' : 'hidden' }}>
                <h1>Estadísticas</h1>
                <div class="placeholder">Sección en construcción.</div>
            </section>

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

            <section class="section" id="usuarios" data-section="usuarios" {{ (($section ?? 'principal') === 'usuarios') ? '' : 'hidden' }}>
                <h1>Usuarios</h1>
                <div class="placeholder">Sección en construcción.</div>
            </section>

            <div class="footer">
                Ruta: <code>/dashboard</code> · Usa el menú para navegar.
            </div>
        </div>
    </main>
</div>

<script>
    (function () {
        var SECTION_KEYS = ['principal', 'inicio', 'transacciones', 'busqueda-transacciones', 'monitoreo', 'estadisticas', 'usuarios', 'conexionBD'];

        // Keep last pagination state for Transacciones (since pager doesn't change URL)
        var transState = { p_claro: 1, p_pasarela: 1, q: '' };

        // State for categories inside "Búsqueda de transacciones" view
        var busquedaState = { cat: 'personas' };

        function parseQuery(search) {
            var out = {};
            if (!search) return out;
            var s = search.charAt(0) === '?' ? search.slice(1) : search;
            if (!s) return out;
            s.split('&').forEach(function (pair) {
                if (!pair) return;
                var idx = pair.indexOf('=');
                var k = idx >= 0 ? pair.slice(0, idx) : pair;
                var v = idx >= 0 ? pair.slice(idx + 1) : '';
                try {
                    k = decodeURIComponent(k);
                    v = decodeURIComponent(v);
                } catch (e) {}
                out[k] = v;
            });
            return out;
        }

        function updateTransStateFromUrl(url) {
            try {
                var u = new URL(url, window.location.origin);
                var q = parseQuery(u.search);
                if (q.p_claro) transState.p_claro = Math.max(1, Math.min(4, parseInt(q.p_claro, 10) || 1));
                if (q.p_pasarela) transState.p_pasarela = Math.max(1, Math.min(4, parseInt(q.p_pasarela, 10) || 1));
                if (typeof q.q === 'string') transState.q = q.q;
            } catch (e) {}
        }

        function updateBusquedaStateFromUrl(url) {
            try {
                var u = new URL(url, window.location.origin);
                var q = parseQuery(u.search);
                if (typeof q.cat === 'string' && q.cat) {
                    busquedaState.cat = q.cat;
                } else {
                    busquedaState.cat = 'personas';
                }
            } catch (e) {
                busquedaState.cat = 'personas';
            }
        }

        function applyBusquedaUi() {
            var label = document.getElementById('busquedaCatLabel');
            if (label) label.textContent = String(busquedaState.cat || 'personas');

            var titles = {
                personas: 'Portal Personas',
                empresas: 'Portal Empresas',
                ecommerce: 'Portal ecommerce'
            };
            var titleEl = document.getElementById('busquedaTitle');
            if (titleEl) titleEl.textContent = titles[busquedaState.cat] || 'Portal Personas';

            var msgEl = document.getElementById('busquedaMsg');
            if (msgEl) msgEl.textContent = 'Se estará agregando información muy pronto.';

            var links = document.querySelectorAll('[data-busqueda-cat]');
            links.forEach(function (a) {
                var isActive = a.getAttribute('data-busqueda-cat') === busquedaState.cat;
                a.style.borderColor = isActive ? 'rgba(148, 163, 184, 0.38)' : 'rgba(148, 163, 184, 0.18)';
                a.style.background = isActive ? 'rgba(148, 163, 184, 0.16)' : 'rgba(148, 163, 184, 0.08)';
            });
        }

        function buildTransUrl() {
            var url = '/dashboard/transacciones?p_claro=' + transState.p_claro + '&p_pasarela=' + transState.p_pasarela;
            if (transState.q && transState.q.trim() !== '') {
                url += '&q=' + encodeURIComponent(transState.q.trim());
            }
            return url;
        }

        function showSection(key) {
            SECTION_KEYS.forEach(function (k) {
                var el = document.querySelector('[data-section="' + k + '"]');
                if (el) el.hidden = (k !== key);
            });

            var links = document.querySelectorAll('.nav a[data-section-link]');
            links.forEach(function (a) {
                var active = a.getAttribute('data-section-link') === key;
                a.style.borderColor = active ? 'rgba(148, 163, 184, 0.38)' : 'rgba(148, 163, 184, 0.18)';
                a.style.background = active ? 'rgba(148, 163, 184, 0.16)' : 'rgba(148, 163, 184, 0.08)';
            });
        }

        function sectionFromPath(pathname) {
            var parts = (pathname || '').split('/').filter(Boolean);
            // /dashboard/{section}
            if (parts[0] !== 'dashboard') return null;
            return parts[1] || 'principal';
        }

        function navigateTo(key, push) {
            if (SECTION_KEYS.indexOf(key) === -1) return;

            // Transacciones data is server-side; load it on demand so it doesn't show as empty.
            if (key === 'transacciones') {
                var url = buildTransUrl();
                var scrollY = window.scrollY;

                fetch(url, {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: { 'X-Requested-With': 'fetch' }
                })
                    .then(function (res) {
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        return res.text();
                    })
                    .then(function (html) {
                        var parser = new DOMParser();
                        var doc = parser.parseFromString(html, 'text/html');

                        var current = document.querySelector('[data-section="transacciones"]');
                        var next = doc.querySelector('[data-section="transacciones"]');
                        if (!current || !next) throw new Error('missing transacciones section');

                        current.replaceWith(next);
                        showSection('transacciones');

                        if (push) {
                            history.pushState({ section: 'transacciones' }, '', '/dashboard/transacciones');
                        }
                        window.scrollTo(0, scrollY);
                    })
                    .catch(function (err) {
                        console.error('Transacciones load failed', err);
                        // Fallback: navigate with full reload
                        window.location.href = '/dashboard/transacciones';
                    });

                return;
            }

            showSection(key);
            if (push) {
                var nextUrl = key === 'principal' ? '/dashboard' : '/dashboard/' + key;
                if (key === 'busqueda-transacciones') {
                    nextUrl += '?cat=' + encodeURIComponent(busquedaState.cat || 'pagos');
                }
                history.pushState({ section: key }, '', nextUrl);
            }
            if (key === 'busqueda-transacciones') applyBusquedaUi();
        }

        var btn = document.getElementById('sidebarToggle');
        if (!btn) return;

        // Theme toggle (light/dark)
        var themeBtn = document.getElementById('themeToggle');

        function applyTheme(mode) {
            var light = mode === 'light';
            document.body.classList.toggle('theme-light', light);
            if (themeBtn) {
                themeBtn.setAttribute('aria-pressed', light ? 'true' : 'false');
            }
            try { localStorage.setItem('theme', light ? 'light' : 'dark'); } catch (e) {}
        }

        var initialTheme = null;
        try { initialTheme = localStorage.getItem('theme'); } catch (e) {}
        applyTheme(initialTheme === 'light' ? 'light' : 'dark');

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

        if (themeBtn) {
            themeBtn.addEventListener('click', function () {
                var isLight = document.body.classList.contains('theme-light');
                applyTheme(isLight ? 'dark' : 'light');
            });
        }

        // Client-side navigation (no reload) but updates URL.
        document.addEventListener('click', function (e) {
            var a = e.target && e.target.closest ? e.target.closest('a[data-section-link]') : null;
            if (!a) return;
            var key = a.getAttribute('data-section-link');
            if (!key) return;

            e.preventDefault();
            navigateTo(key, true);
        });

        window.addEventListener('popstate', function () {
            var key = (history.state && history.state.section) || sectionFromPath(location.pathname) || 'principal';
            updateBusquedaStateFromUrl(location.href);
            navigateTo(key, false);
        });

        // Ensure the correct section is visible on first load.
        var initialSection = sectionFromPath(location.pathname) || 'principal';
        updateTransStateFromUrl(location.href);
        updateBusquedaStateFromUrl(location.href);
        navigateTo(initialSection, false);
        if (initialSection === 'busqueda-transacciones') applyBusquedaUi();

        // Pagination for transacciones: update in-place (no full page reload).
        document.addEventListener('click', function (e) {
            var a = e.target && e.target.closest ? e.target.closest('.pager a') : null;
            if (!a) return;

            // Only handle pagers inside the Transacciones section.
            var transSection = document.querySelector('[data-section="transacciones"]');
            if (!transSection || transSection.hidden) return;
            if (!transSection.contains(a)) return;

            var href = a.getAttribute('href');
            if (!href) return;

            updateTransStateFromUrl(href);

            var wrapper = a.closest('[data-trans-table]');
            if (!wrapper) return;

            e.preventDefault();

            var scrollY = window.scrollY;
            wrapper.setAttribute('aria-busy', 'true');

            fetch(href, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'X-Requested-With': 'fetch'
                }
            })
                .then(function (res) {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.text();
                })
                .then(function (html) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, 'text/html');

                    var key = wrapper.getAttribute('data-trans-table');
                    if (!key) throw new Error('missing data-trans-table');

                    var nextWrapper = doc.querySelector('[data-trans-table="' + key + '"]');
                    if (nextWrapper) {
                        wrapper.replaceWith(nextWrapper);
                    } else {
                        // If the wrapper is not present (e.g. búsqueda activa y la tabla queda sin filas),
                        // refresh the whole Transacciones section in-place.
                        var currentSection = document.querySelector('[data-section="transacciones"]');
                        var nextSection = doc.querySelector('[data-section="transacciones"]');
                        if (!currentSection || !nextSection) throw new Error('missing transacciones section in response');
                        currentSection.replaceWith(nextSection);
                        showSection('transacciones');
                    }
                    window.scrollTo(0, scrollY);
                })
                .catch(function (err) {
                    console.error('Pager fetch failed', err);
                    // Fallback: navigate normally if something goes wrong.
                    window.location.href = href;
                })
                .finally(function () {
                    try {
                        // wrapper might have been replaced; nothing to do.
                    } catch (e2) {}
                });
        });

        // Search in transacciones (in-place, no reload, no URL change)
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form || form.id !== 'transSearch') return;

            var transSection = document.querySelector('[data-section="transacciones"]');
            if (!transSection || transSection.hidden) return;

            e.preventDefault();

            var input = document.getElementById('transSearchInput');
            transState.q = (input && input.value) ? String(input.value) : '';

            // Reset to first page on new search
            transState.p_claro = 1;
            transState.p_pasarela = 1;

            // Trigger in-place load of transacciones
            navigateTo('transacciones', false);
        });

        // Clear search (in-place)
        document.addEventListener('click', function (e) {
            var btnClear = e.target && e.target.closest ? e.target.closest('#transClearBtn') : null;
            if (!btnClear) return;

            var transSection = document.querySelector('[data-section="transacciones"]');
            if (!transSection || transSection.hidden) return;

            e.preventDefault();

            var input = document.getElementById('transSearchInput');
            if (input) input.value = '';

            transState.q = '';
            transState.p_claro = 1;
            transState.p_pasarela = 1;

            navigateTo('transacciones', false);
        });

        // Subcategory click under "Búsqueda" (no reload)
        document.addEventListener('click', function (e) {
            var a = e.target && e.target.closest ? e.target.closest('a[data-busqueda-cat]') : null;
            if (!a) return;

            e.preventDefault();
            busquedaState.cat = a.getAttribute('data-busqueda-cat') || 'pagos';
            applyBusquedaUi();

            var url = '/dashboard/busqueda-transacciones?cat=' + encodeURIComponent(busquedaState.cat);
            history.pushState({ section: 'busqueda-transacciones' }, '', url);
            showSection('busqueda-transacciones');
            applyBusquedaUi();
        });
    })();
</script>
</body>
</html>
