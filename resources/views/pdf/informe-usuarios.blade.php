{{--
    Informe de usuarios registrados, agrupado por tipo de cuenta.

    Lo arma Admin\Usuarios::descargarInforme() y lo convierte dompdf. Ojo con el CSS:
    dompdf entiende CSS 2.1 y poco más, así que aquí no hay flex ni grid, las tablas
    llevan el ancho en la propia columna y los colores van escritos a mano en vez de
    salir de los tokens de Tailwind, que en este documento no existen.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }} · AD+50</title>
    <style>
        @page { margin: 32mm 14mm 22mm 14mm; }

        /* DejaVu Sans viene con dompdf y es la que trae los acentos y la ñ. Con la
           fuente por omisión (serif) las tildes salen como cuadros. */
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 9pt;
            color: #343638;
            margin: 0;
        }

        /* El encabezado y el pie van fijos: dompdf los repite en todas las páginas. */
        header {
            position: fixed;
            top: -22mm; left: 0; right: 0;
            height: 16mm;
            border-bottom: 1.2pt solid #E87722;
        }

        header .marca { font-size: 15pt; font-weight: bold; color: #E87722; }
        header .titulo { font-size: 10.5pt; font-weight: bold; }
        header .meta { font-size: 7.5pt; color: #66696C; }

        footer {
            position: fixed;
            bottom: -14mm; left: 0; right: 0;
            height: 10mm;
            font-size: 7.5pt;
            color: #75787B;
            border-top: 0.6pt solid #E6E6E3;
            padding-top: 2mm;
        }

        h2 {
            font-size: 11pt;
            margin: 7mm 0 2.5mm;
            padding-bottom: 1.5mm;
            border-bottom: 0.8pt solid #E6E6E3;
        }

        h2 .cuenta { font-size: 8.5pt; font-weight: normal; color: #66696C; }

        table { width: 100%; border-collapse: collapse; }

        th {
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
            color: #66696C;
            text-align: left;
            padding: 2mm 2mm;
            border-bottom: 0.8pt solid #D4D5D3;
        }

        td {
            padding: 1.8mm 2mm;
            border-bottom: 0.4pt solid #E6E6E3;
            vertical-align: top;
        }

        .num { text-align: right; }
        .tenue { color: #75787B; }
        .hora { font-size: 7.5pt; color: #75787B; }
        .si { color: #16A34A; font-weight: bold; }
        .no { color: #8F3B04; font-weight: bold; }
        .vacio { padding: 4mm 2mm; color: #75787B; font-style: italic; }

        /* Cada tipo empieza en hoja nueva. Sin esto dompdf deja el título de un tipo
           y su cabecera de tabla al final de una página y las filas en la siguiente. */
        .salto { page-break-before: always; }

        .resumen th, .resumen td { border-bottom: 0.4pt solid #E6E6E3; }
        .resumen .total td { border-top: 0.8pt solid #D4D5D3; border-bottom: none; font-weight: bold; }

        /* Repite la cabecera cuando un tipo ocupa varias hojas, y no parte una fila. */
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
    <header>
        <table>
            <tr>
                <td style="border: none; padding: 0;">
                    <span class="marca">AD+50</span>
                    <span class="titulo">&nbsp;·&nbsp;{{ $titulo }}</span>
                </td>
                <td style="border: none; padding: 0; text-align: right;" class="meta">
                    Generado el {{ $generadoEn->format('d/m/Y \a \l\a\s H:i') }}<br>
                    por {{ $generadoPor }}
                </td>
            </tr>
        </table>
    </header>

    <footer>
        <table>
            <tr>
                <td style="border: none; padding: 0;">
                    Documento interno de AD+50. Contiene datos personales: trátalo conforme a la Ley 21.719.
                </td>
                {{-- El «Página N de M» lo escribe Usuarios::numerarPaginas() sobre el
                     lienzo ya maquetado; aquí no se sabe cuántas páginas habrá. --}}
            </tr>
        </table>
    </footer>

    {{-- Con un solo tipo sobran la fila «Total» y la columna del porcentaje: repetirían
         la única fila y dirían siempre 100%. --}}
    @php($variosTipos = count($resumen) > 1)

    <h2>
        {{ $variosTipos ? 'Resumen por tipo de cuenta' : 'Resumen' }}
        <span class="cuenta">— {{ $total }} {{ $total === 1 ? 'cuenta' : 'cuentas' }} en total</span>
    </h2>

    <table class="resumen">
        <thead>
            <tr>
                <th style="width: 40%;">Tipo de usuario</th>
                <th class="num" style="width: 15%;">Cuentas</th>
                <th class="num" style="width: 15%;">Verificadas</th>
                <th class="num" style="width: 15%;">Sin verificar</th>
                @if ($variosTipos)
                    <th class="num" style="width: 15%;">Del total</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($resumen as $fila)
                <tr>
                    <td>{{ $fila['etiqueta'] }}</td>
                    <td class="num">{{ $fila['total'] }}</td>
                    <td class="num">{{ $fila['verificadas'] }}</td>
                    <td class="num">{{ $fila['sin_verificar'] }}</td>
                    @if ($variosTipos)
                        <td class="num tenue">{{ $total > 0 ? number_format($fila['total'] * 100 / $total, 1, ',', '.').'%' : '—' }}</td>
                    @endif
                </tr>
            @endforeach
            @if ($variosTipos)
                <tr class="total">
                    <td>Total</td>
                    <td class="num">{{ $total }}</td>
                    <td class="num">{{ $totalVerificadas }}</td>
                    <td class="num">{{ $totalSinVerificar }}</td>
                    <td class="num">{{ $total > 0 ? '100,0%' : '—' }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    @foreach ($resumen as $rol => $fila)
        <div class="grupo @unless ($loop->first) salto @endunless">
            <h2>
                {{ $fila['etiqueta'] }}
                <span class="cuenta">— {{ $fila['total'] }} {{ $fila['total'] === 1 ? 'cuenta registrada' : 'cuentas registradas' }}</span>
            </h2>

            @if ($fila['total'] === 0)
                <p class="vacio">No hay cuentas de este tipo.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th class="num" style="width: 6%;">#</th>
                            <th style="width: 30%;">Nombre</th>
                            <th style="width: 34%;">Correo</th>
                            <th style="width: 18%;">Se registró</th>
                            <th style="width: 12%;">Correo verificado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($usuariosPorTipo[$rol] as $i => $usuario)
                            <tr>
                                <td class="num tenue">{{ $i + 1 }}</td>
                                <td>{{ $usuario->name }}</td>
                                <td class="tenue">{{ $usuario->email }}</td>
                                <td>
                                    @if ($usuario->created_at)
                                        {{ $usuario->created_at->format('d/m/Y') }}
                                        <span class="hora">{{ $usuario->created_at->format('H:i') }}</span>
                                    @else
                                        <span class="tenue">Sin fecha</span>
                                    @endif
                                </td>
                                <td class="{{ $usuario->email_verified_at ? 'si' : 'no' }}">
                                    {{ $usuario->email_verified_at ? 'Sí' : 'No' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach

</body>
</html>
