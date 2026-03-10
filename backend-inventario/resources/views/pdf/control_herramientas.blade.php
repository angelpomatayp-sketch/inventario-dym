<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Control de Entrega de Herramientas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            line-height: 1.4;
            color: #1a1a1a;
            padding: 0.8cm 1cm 1.5cm 1cm;
        }

        /* ── CABECERA ── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #333;
            margin-bottom: 8px;
        }
        .header-table td { vertical-align: middle; padding: 5px 8px; }
        .header-logo  { width: 130px; border-right: 1.5px solid #333; }
        .header-title { text-align: center; border-right: 1.5px solid #333; }
        .header-title .formato-label {
            font-size: 8px; color: #555;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .header-title .doc-title {
            font-size: 13px; font-weight: bold;
            color: #1E2D72; text-transform: uppercase; letter-spacing: 1.5px;
            margin-top: 2px;
        }
        .header-codigo { width: 140px; font-size: 9px; line-height: 2; }
        .header-codigo table { width: 100%; border-collapse: collapse; }
        .header-codigo td { padding: 0 2px; border: none; font-size: 9px; }
        .header-codigo .lbl { font-weight: bold; white-space: nowrap; }

        /* ── DATOS DEL TRABAJADOR ── */
        .trabajador-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #999;
            margin-bottom: 8px;
        }
        .trabajador-table td {
            padding: 4px 7px;
            border: 1px solid #bbb;
            font-size: 9px;
            vertical-align: middle;
        }
        .trabajador-table .lbl {
            font-weight: bold;
            background-color: #dbeafe;
            color: #1E2D72;
            white-space: nowrap;
            width: 130px;
        }

        /* ── SECCIÓN TÍTULO ── */
        .section-title {
            background-color: #6b7280;
            color: white;
            text-align: center;
            font-weight: bold;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 3px 8px;
            margin-bottom: 0;
        }

        /* ── TABLA DE HERRAMIENTAS ── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #999;
            margin-bottom: 10px;
        }
        .items-table th {
            background-color: #1E2D72;
            color: white;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            padding: 4px 3px;
            border: 1px solid #1E2D72;
            letter-spacing: 0.5px;
        }
        .items-table td {
            font-size: 8.5px;
            padding: 4px 4px;
            border: 1px solid #ccc;
            vertical-align: middle;
            text-align: center;
        }
        .items-table td.desc { text-align: left; }
        .items-table tr:nth-child(even) td { background-color: #f9fafb; }
        .items-table .firma-cell { min-height: 22px; height: 22px; }
        .col-it       { width: 24px; }
        .col-cant     { width: 34px; }
        .col-desc     { width: auto; }
        .col-fecha    { width: 68px; }
        .col-firma    { width: 70px; }
        .col-cant-dev { width: 34px; }
        .col-fecha-dev{ width: 68px; }
        .col-firma-rec{ width: 70px; }

        /* ── FILA VACÍA ── */
        .fila-vacia td { height: 18px; }

        /* ── NOTA LEGAL ── */
        .nota-legal {
            border: 1px solid #e5e7eb;
            background-color: #fefce8;
            padding: 6px 10px;
            font-size: 7.5px;
            color: #374151;
            margin-bottom: 12px;
            line-height: 1.5;
        }
        .nota-legal .nota-titulo {
            font-weight: bold;
            color: #92400e;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        /* ── LUGAR Y FECHA ── */
        .lugar-fecha {
            text-align: right;
            font-size: 8.5px;
            font-style: italic;
            margin-bottom: 14px;
            color: #555;
        }

        /* ── FIRMAS ── */
        .firmas-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #bbb;
        }
        .firmas-table td {
            width: 50%;
            padding: 6px 12px;
            border: 1px solid #bbb;
            vertical-align: top;
            text-align: center;
        }
        .firma-titulo {
            font-size: 8.5px; font-weight: bold;
            color: #1E2D72; margin-bottom: 3px;
            text-align: left;
        }
        .firma-espacio { height: 50px; }
        .firma-linea {
            border-top: 1px solid #555;
            padding-top: 4px;
        }
        .firma-nombre { font-weight: bold; font-size: 9.5px; color: #1f2937; }
        .firma-cargo  { font-size: 8px; color: #6b7280; margin-top: 2px; }

        /* ── RESUMEN ── */
        .resumen-badge {
            display: inline-block;
            background-color: #dbeafe;
            color: #1E2D72;
            font-weight: bold;
            font-size: 8px;
            padding: 2px 8px;
            border-radius: 3px;
            margin-right: 6px;
        }
        .resumen-badge.verde { background-color: #d1fae5; color: #065f46; }
        .resumen-badge.rojo  { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

@php
    $trabajadorNombre = $trabajador->nombre ?? '-';
    $trabajadorDni    = $trabajador->dni ?? '-';
    $trabajadorCargo  = $trabajador->cargo ?? '-';
    $centroCosto      = $trabajador->centroCosto?->nombre ?? '-';
    $totalItems       = count($prestamos);
    $activos          = $prestamos->whereIn('estado', ['ACTIVO', 'VENCIDO'])->count();
    $devueltos        = $prestamos->whereIn('estado', ['DEVUELTO', 'PERDIDO', 'DANADO'])->count();
    $minFilas         = 12;
    $filasVacias      = max(0, $minFilas - $totalItems);
    $fechaImpresion   = \Carbon\Carbon::now()->isoFormat('D [de] MMMM [de] Y');
@endphp

{{-- ═══════════ CABECERA ═══════════ --}}
<table class="header-table">
    <tr>
        <td class="header-logo">
            @php
                $logoPath = public_path('images/LOGO2.png');
                $logoData = (extension_loaded('gd') && file_exists($logoPath)) ? base64_encode(file_get_contents($logoPath)) : null;
            @endphp
            @if($logoData)
                <img src="data:image/png;base64,{{ $logoData }}" style="max-width:120px; max-height:55px; display:block; margin:auto;">
            @else
                <div style="font-size:11px; font-weight:bold; color:#1E2D72; line-height:1.4;">
                    CONTRATISTAS<br>ASOCIADOS<br>PACIFICO S.R.L.
                </div>
            @endif
        </td>
        <td class="header-title">
            <div class="formato-label">FORMATO</div>
            <div class="doc-title">Control de Entrega de Herramientas</div>
        </td>
        <td class="header-codigo">
            <table>
                <tr><td class="lbl">Código:</td><td>FR-ALM-03</td></tr>
                <tr><td class="lbl">Versión:</td><td>00</td></tr>
                <tr><td class="lbl">Fecha:</td><td>01/04/2022</td></tr>
            </table>
        </td>
    </tr>
</table>

{{-- ═══════════ DATOS DEL TRABAJADOR ═══════════ --}}
<div class="section-title">Datos del Trabajador</div>
<table class="trabajador-table">
    <tr>
        <td class="lbl">Apellidos y Nombres</td>
        <td colspan="3"><strong>{{ $trabajadorNombre }}</strong></td>
    </tr>
    <tr>
        <td class="lbl">DNI</td>
        <td>{{ $trabajadorDni }}</td>
        <td class="lbl">Puesto de Trabajo</td>
        <td>{{ $trabajadorCargo }}</td>
    </tr>
    <tr>
        <td class="lbl">Obra / Unidad</td>
        <td colspan="3">{{ $centroCosto }}</td>
    </tr>
</table>

{{-- ═══════════ TABLA DE HERRAMIENTAS ═══════════ --}}
<div class="section-title">Registro de Herramientas / Equipos</div>
<table class="items-table">
    <thead>
        <tr>
            <th class="col-it" rowspan="2">IT</th>
            <th class="col-cant" rowspan="2">CANT.</th>
            <th class="col-desc" rowspan="2">DESCRIPCIÓN</th>
            <th colspan="2" style="border-bottom:1px solid #3b5db0;">ENTREGA</th>
            <th colspan="3" style="border-bottom:1px solid #3b5db0;">DEVOLUCIÓN</th>
        </tr>
        <tr>
            <th class="col-fecha">FECHA</th>
            <th class="col-firma">FIRMA</th>
            <th class="col-cant-dev">CANT.</th>
            <th class="col-fecha-dev">FECHA</th>
            <th class="col-firma-rec">FIRMA</th>
        </tr>
    </thead>
    <tbody>
        @foreach($prestamos as $i => $p)
        @php
            $eqNombre = $p->equipo?->nombre ?? '-';
            $eqCodigo = $p->equipo?->codigo ? ' [' . $p->equipo->codigo . ']' : '';
            $fechaEntrega = $p->fecha_prestamo
                ? \Carbon\Carbon::parse($p->fecha_prestamo)->format('d/m/Y')
                : '-';
            $fechaDevReal = ($p->fecha_devolucion_real)
                ? \Carbon\Carbon::parse($p->fecha_devolucion_real)->format('d/m/Y')
                : '';
            $cantDev = in_array($p->estado, ['DEVUELTO', 'PERDIDO', 'DANADO']) ? ($p->cantidad ?? 1) : '';
        @endphp
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $p->cantidad ?? 1 }}</td>
            <td class="desc">
                {{ $eqNombre }}{{ $eqCodigo }}
                @if($p->estado === 'PERDIDO')
                    <span style="color:#991b1b; font-size:7.5px;"> [PERDIDO]</span>
                @elseif($p->estado === 'DANADO')
                    <span style="color:#92400e; font-size:7.5px;"> [DAÑADO]</span>
                @endif
            </td>
            <td>{{ $fechaEntrega }}</td>
            <td class="firma-cell"></td>
            <td>{{ $cantDev }}</td>
            <td>{{ $fechaDevReal }}</td>
            <td class="firma-cell"></td>
        </tr>
        @endforeach

        @for($v = 0; $v < $filasVacias; $v++)
        <tr class="fila-vacia">
            <td>{{ $totalItems + $v + 1 }}</td>
            <td></td>
            <td class="desc"></td>
            <td></td>
            <td class="firma-cell"></td>
            <td></td>
            <td></td>
            <td class="firma-cell"></td>
        </tr>
        @endfor
    </tbody>
</table>

{{-- ═══════════ RESUMEN ═══════════ --}}
<div style="margin-bottom:10px; font-size:8.5px;">
    <span class="resumen-badge">Total: {{ $totalItems }}</span>
    <span class="resumen-badge verde">En préstamo: {{ $activos }}</span>
    <span class="resumen-badge rojo">Devueltos/Otros: {{ $devueltos }}</span>
</div>

{{-- ═══════════ NOTA LEGAL ═══════════ --}}
<div class="nota-legal">
    <div class="nota-titulo">Nota:</div>
    El trabajador es responsable de las herramientas y/o equipos recibidos, debiendo devolverlos en las mismas
    condiciones en que fueron entregados. La pérdida o daño injustificado de los mismos será de responsabilidad
    del trabajador, quien deberá reponer el bien o asumir el costo correspondiente conforme a la política interna
    de la empresa.
</div>

{{-- ═══════════ LUGAR Y FECHA ═══════════ --}}
<div class="lugar-fecha">
    Huancayo, {{ $fechaImpresion }}
</div>

{{-- ═══════════ FIRMAS ═══════════ --}}
<table class="firmas-table">
    <tr>
        <td>
            <div class="firma-titulo">Firma del Trabajador:</div>
            <div class="firma-espacio"></div>
            <div class="firma-linea">
                <div class="firma-nombre">{{ $trabajadorNombre }}</div>
                <div class="firma-cargo">DNI: {{ $trabajadorDni }}</div>
            </div>
        </td>
        <td>
            <div class="firma-titulo">Responsable del Registro:</div>
            <div class="firma-espacio"></div>
            <div class="firma-linea">
                <div class="firma-nombre">ALMACÉN CAP PACIFICO</div>
                <div class="firma-cargo">Sello y Firma:</div>
            </div>
        </td>
    </tr>
</table>

<script type="text/php">
    if (isset($pdf)) {
        $w = $pdf->get_width();
        $h = $pdf->get_height();
        $font = $fontMetrics->getFont("DejaVu Sans", "normal");
        $color = [0.61, 0.64, 0.67];
        $lineColor = [0.90, 0.91, 0.93];
        $pdf->line(28, $h - 22, $w - 28, $h - 22, $lineColor, 0.5);
        $pdf->page_text(
            $w / 2 - 90,
            $h - 13,
            "CAP Pacifico S.R.L.  -  Página {PAGE_NUM} de {PAGE_COUNT}",
            $font, 7, $color
        );
    }
</script>
</body>
</html>
