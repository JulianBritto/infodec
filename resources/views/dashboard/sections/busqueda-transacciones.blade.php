<section class="section" id="busqueda-transacciones" data-section="busqueda-transacciones" {{ (($section ?? 'principal') === 'busqueda-transacciones') ? '' : 'hidden' }}>
    <h1>Búsqueda de transacciones</h1>
    @php($busq = $busqueda ?? null)
    @php($busqMeta = $busq['meta'] ?? [])
    @php($busquedaCat = (string) ($busqMeta['cat'] ?? request()->query('cat', 'personas')))
    @php($busquedaQ = trim((string) ($busqMeta['q'] ?? request()->query('q', ''))))
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

        @if ($busquedaCat === 'personas')
            @php($p = $busq['personas'] ?? null)
            @php($perr = is_array($p) ? ($p['error'] ?? null) : null)
            @php($prows = is_array($p) ? ($p['rows'] ?? []) : [])
            @php($consulta1Open = ($busquedaQ !== ''))

            <div class="consulta-grid">
                <div class="card consulta-card {{ $consulta1Open ? 'consulta-expanded' : '' }}">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                        <div class="table-title" style="margin-bottom: 6px;">
                            <span>Búsqueda de transacciones (Consulta 1)</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <button
                                type="button"
                                class="acc-btn"
                                data-accordion-action="open"
                                data-accordion-target="busquedaPersonasQueryBody"
                                data-accordion-submit="busquedaPersonasSubmit"
                                data-accordion-input="busquedaPersonasInput"
                                {{ $consulta1Open ? 'hidden' : '' }}
                            >Hacer búsqueda</button>
                            <button
                                type="button"
                                class="acc-btn"
                                aria-label="Ocultar consulta"
                                title="Ocultar"
                                data-accordion-action="close"
                                data-accordion-target="busquedaPersonasQueryBody"
                                data-accordion-submit="busquedaPersonasSubmit"
                                data-accordion-input="busquedaPersonasInput"
                                data-accordion-results="busquedaPersonasResults"
                                data-accordion-clear-busqueda-q="1"
                                {{ $consulta1Open ? '' : 'hidden' }}
                            >▴</button>
                        </div>
                    </div>
                    <div class="muted">Query para buscar transacciones con un detallado resumido para el cliente.</div>

                    <div id="busquedaPersonasQueryBody" style="margin-top: 12px;" {{ $consulta1Open ? '' : 'hidden' }}>
                        <form class="searchbar" id="busquedaPersonasSearch" autocomplete="off">
                            <input
                                type="text"
                                id="busquedaPersonasInput"
                                placeholder="Buscar por NUMERO_DOCUMENTO, EMAIL, CUS, ID_TRANSACCION"
                                value="{{ $busquedaQ }}"
                            />
                            <div class="search-actions">
                                <button type="button" id="busquedaPersonasClear">Limpiar búsqueda</button>
                                <button type="submit" id="busquedaPersonasSubmit" {{ $consulta1Open ? '' : 'disabled' }}>Ejecutar query</button>
                            </div>
                        </form>

                        <div id="busquedaPersonasResults">
                            @if ($perr)
                                <div class="placeholder" style="margin-top: 10px;">Error ejecutando la búsqueda: <code>{{ $perr }}</code></div>
                            @elseif (empty($prows))
                                @if ($busquedaQ !== '')
                                    <div class="placeholder" style="margin-top: 10px;">Sin resultados para la búsqueda: <code>{{ $busquedaQ }}</code></div>
                                @else
                                    <div class="placeholder" style="margin-top: 10px;">No hay registros para mostrar.</div>
                                @endif
                            @else
                                <div class="table-wrap search-fit" style="margin-top: 10px;">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th><code>fecha_inicio</code></th>
                                                <th><code>ESTADO</code></th>
                                                <th><code>INTENTOS</code></th>
                                                <th><code>TITULAR</code></th>
                                                <th><code>NUMEROFACTURA</code></th>
                                                <th><code>FECHA_TRANSACCION</code></th>
                                                <th><code>VALOR</code></th>
                                                <th><code>DESCRIPCION_COMPRA</code></th>
                                                <th><code>NUMERO_DOCUMENTO</code></th>
                                                <th><code>TELEFONO</code></th>
                                                <th><code>EMAIL</code></th>
                                                <th><code>CUS</code></th>
                                                <th><code>TIPO_TRANS</code></th>
                                                <th><code>ORIGEN_PAGO</code></th>
                                                <th><code>FORMA_PAGO</code></th>
                                                <th><code>CodigoCliente</code></th>
                                                <th><code>PASA_NUMERO</code></th>
                                                <th><code>ID_TRANSACCION</code></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($prows as $r)
                                                <tr>
                                                    <td><code>{{ (string) ($r->fecha_inicio ?? '') }}</code></td>
                                                    <td><code>{{ (string) ($r->ESTADO ?? '') }}</code></td>
                                                    <td><code>{{ (string) ($r->INTENTOS ?? '') }}</code></td>
                                                    <td><code>{{ (string) ($r->TITULAR ?? '') }}</code></td>
                                                    <td><code>{{ (string) ($r->NUMEROFACTURA ?? '') }}</code></td>
                                                    <td><code>{{ (string) ($r->FECHA_TRANSACCION ?? '') }}</code></td>
                                                    <td><code>{{ (string) ($r->VALOR ?? '') }}</code></td>
                                                    <td><code>{{ (string) ($r->DESCRIPCION_COMPRA ?? '') }}</code></td>
                                                    <td><code>{{ (string) ($r->NUMERO_DOCUMENTO ?? '') }}</code></td>
                                                    <td><code>{{ (string) ($r->TELEFONO ?? '') }}</code></td>
                                                    <td><code>{{ (string) ($r->EMAIL ?? '') }}</code></td>
                                                    <td><code>{{ (string) ($r->CUS ?? '') }}</code></td>
                                                    <td><code>{{ (string) ($r->TIPO_TRANS ?? '') }}</code></td>
                                                    <td><code>{{ (string) ($r->ORIGEN_PAGO ?? '') }}</code></td>
                                                    <td><code>{{ (string) ($r->FORMA_PAGO ?? '') }}</code></td>
                                                    <td><code>{{ (string) ($r->CodigoCliente ?? '') }}</code></td>
                                                    <td><code>{{ (string) ($r->PASA_NUMERO ?? '') }}</code></td>
                                                    <td><code>{{ (string) ($r->ID_TRANSACCION ?? '') }}</code></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                @for ($i = 2; $i <= 8; $i++)
                    @php($bodyId = 'busquedaConsulta' . $i . 'Body')
                    @php($inputId = 'busquedaConsulta' . $i . 'Input')
                    <div class="card consulta-card">
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                            <div class="table-title" style="margin-bottom: 6px;">
                                <span>Búsqueda de transacciones (Consulta {{ $i }})</span>
                            </div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <button
                                    type="button"
                                    class="acc-btn"
                                    data-accordion-action="open"
                                    data-accordion-target="{{ $bodyId }}"
                                    data-accordion-input="{{ $inputId }}"
                                >Hacer búsqueda</button>
                                <button
                                    type="button"
                                    class="acc-btn"
                                    aria-label="Ocultar consulta"
                                    title="Ocultar"
                                    data-accordion-action="close"
                                    data-accordion-target="{{ $bodyId }}"
                                    data-accordion-input="{{ $inputId }}"
                                    hidden
                                >▴</button>
                            </div>
                        </div>
                        <div class="muted">Query pendiente. Aquí irá una consulta diferente para el Portal Personas.</div>

                        <div id="{{ $bodyId }}" style="margin-top: 12px;" hidden>
                            <form class="searchbar" autocomplete="off" onsubmit="return false;">
                                <input id="{{ $inputId }}" type="text" placeholder="Pendiente: define el query para habilitar esta consulta" disabled />
                                <div class="search-actions">
                                    <button type="button" disabled>Ejecutar query</button>
                                </div>
                            </form>
                            <div class="placeholder" style="margin-top: 10px;">Pendiente por definir el query (te lo armo cuando me lo pases).</div>
                        </div>
                    </div>
                @endfor
            </div>
        @else
            <div class="muted" id="busquedaMsg">Se estará agregando información muy pronto.</div>
        @endif
    </div>
</section>
