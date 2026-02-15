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
