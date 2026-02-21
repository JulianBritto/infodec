<section class="section" id="tiempo-ejecucion-querys" data-section="tiempo-ejecucion-querys" {{ (($section ?? 'principal') === 'tiempo-ejecucion-querys') ? '' : 'hidden' }}>
    <h1>Tiempo ejecucion Querys</h1>

    @php($rows = $query_logs ?? [])
    @php($loadErr = $query_logs_error ?? null)

    @if (!empty($loadErr))
        <div class="placeholder">{{ (string) $loadErr }}</div>
    @elseif (empty($rows))
        <div class="placeholder">No hay registros aún.</div>
    @else
        <div class="table-wrap" style="margin-top: 10px;">
            <table>
                <thead>
                    <tr>
                        <th><code>Fecha</code></th>
                        <th><code>Query</code></th>
                        <th><code>Tiempo</code></th>
                        <th><code>Copiar</code></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $r)
                        @php($fullSql = (string) ($r->sql ?? ''))
                        @php($previewRaw = function_exists('mb_substr') ? mb_substr($fullSql, 0, 160) : substr($fullSql, 0, 160))
                        @php($preview = trim((string) $previewRaw))
                        @php($sqlLen = function_exists('mb_strlen') ? mb_strlen($fullSql) : strlen($fullSql))
                        @php($preview = $preview . ($sqlLen > 160 ? '…' : ''))
                        <tr>
                            <td><code>{{ (string) ($r->executed_at ?? '') }}</code></td>
                            <td><code>{{ $preview }}</code></td>
                            <td><code>{{ (int) ($r->time_ms ?? 0) }} ms</code></td>
                            <td>
                                <button
                                    type="button"
                                    class="acc-btn"
                                    data-copy-sql="{{ base64_encode($fullSql) }}"
                                >Copiar</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="muted" style="margin-top: 10px;">Mostrando los últimos {{ count($rows) }} registros.</div>
    @endif

    <script>
        (function () {
            function decodeBase64Utf8(b64) {
                try {
                    var binary = atob(b64);
                    var bytes = new Uint8Array(binary.length);
                    for (var i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
                    return new TextDecoder('utf-8').decode(bytes);
                } catch (e) {
                    try { return atob(b64); } catch (e2) { return ''; }
                }
            }

            document.addEventListener('click', function (e) {
                var btn = e.target && e.target.closest ? e.target.closest('[data-copy-sql]') : null;
                if (!btn) return;
                var b64 = btn.getAttribute('data-copy-sql') || '';
                var sql = decodeBase64Utf8(b64);
                if (!sql) return;

                function done(ok) {
                    btn.textContent = ok ? 'Copiado' : 'Error';
                    setTimeout(function () { btn.textContent = 'Copiar'; }, 900);
                }

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(sql).then(function () { done(true); }).catch(function () { done(false); });
                    return;
                }

                try {
                    var ta = document.createElement('textarea');
                    ta.value = sql;
                    ta.style.position = 'fixed';
                    ta.style.left = '-9999px';
                    document.body.appendChild(ta);
                    ta.focus();
                    ta.select();
                    var ok = document.execCommand('copy');
                    document.body.removeChild(ta);
                    done(!!ok);
                } catch (err) {
                    done(false);
                }
            });
        })();
    </script>
</section>
