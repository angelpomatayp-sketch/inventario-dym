<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Vale de Salida {{ $vale->numero }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #1a1a1a;
            padding: 1cm 1.2cm 1.5cm 1.2cm;
        }

        /* ── CABECERA ── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #333;
            margin-bottom: 6px;
        }
        .header-table td { vertical-align: middle; padding: 6px 10px; }
        .header-logo  { width: 130px; border-right: 1.5px solid #333; }
        .header-title { text-align: center; border-right: 1.5px solid #333; }
        .header-title .formato-label {
            font-size: 8px; color: #000; font-weight: bold;
            text-transform: uppercase; letter-spacing: 1px;
            border-bottom: 1px solid #333; display: block; padding-bottom: 3px; margin-bottom: 3px;
        }
        .header-title .doc-title {
            font-size: 15px; font-weight: bold;
            color: #000; text-transform: uppercase; letter-spacing: 2px;
        }
        .header-codigo { width: 140px; font-size: 9px; line-height: 2; }
        .header-codigo table { width: 100%; border-collapse: collapse; margin: 0; }
        .header-codigo td { padding: 0 2px; border: none; font-size: 9px; }
        .header-codigo .lbl { font-weight: bold; white-space: nowrap; }

        /* ── NÚMERO DEL VALE ── */
        .numero-row {
            width: 100%;
            text-align: right;
            margin-bottom: 7px;
        }
        .numero-box {
            display: inline-block;
            border: 1.5px solid #333;
            padding: 3px 18px;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        /* ── DATOS GENERALES ── */
        .datos-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .datos-table td {
            padding: 4px 8px;
            border: none;
            border-bottom: 1px solid #333;
            font-size: 10px;
            vertical-align: middle;
        }
        .datos-table .lbl {
            font-weight: bold;
            color: #000;
            width: 105px;
            white-space: nowrap;
        }
        .datos-table .val { min-width: 130px; }

        /* ── TABLA DE ÍTEMS ── */
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            border: 1.5px solid #333;
        }
        table.items th {
            background-color: #66BB6A;
            color: #000;
            padding: 5px 7px;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid #43A047;
        }
        table.items th.center, table.items td.center { text-align: center; }
        table.items td {
            padding: 4px 7px;
            border: 1px solid #ccc;
            font-size: 9.5px;
            height: 19px;
        }
        table.items tr:nth-child(even) td { background-color: #f8faf8; }

        /* ── ESTADO ── */
        .estado-PENDIENTE { background:#fef3c7; color:#92400e; padding:2px 7px; border-radius:3px; font-weight:bold; font-size:9px; }
        .estado-ENTREGADO { background:#d1fae5; color:#065f46; padding:2px 7px; border-radius:3px; font-weight:bold; font-size:9px; }
        .estado-PARCIAL   { background:#dbeafe; color:#1e3a8a; padding:2px 7px; border-radius:3px; font-weight:bold; font-size:9px; }
        .estado-ANULADO   { background:#e5e7eb; color:#374151; padding:2px 7px; border-radius:3px; font-weight:bold; font-size:9px; }

        /* ── FIRMAS ── */
        .firmas-table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        .firmas-table td { width: 33.33%; padding: 0; }
        .firma-label-cell { font-size: 8.5px; font-weight: bold; color: #1E2D72; padding: 2px 4px; border: none; text-align: left; }
        .firma-box { border: 1px solid #555; height: 60px; vertical-align: bottom; text-align: left; padding: 3px 5px; }
        .firma-vb { font-size: 8px; color: #1E2D72; }
        .firma-nombre { font-size: 8px; color: #1E2D72; font-weight: bold; }
    </style>
</head>
<body>

@php
    $maxFilas = 10;
    $filasActuales = count($vale->detalles);
    $filasVacias = max(0, $maxFilas - $filasActuales);
    $estado = $vale->estado ?? 'PENDIENTE';
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
            <div class="doc-title">VALE DE SALIDA</div>
        </td>
        <td class="header-codigo">
            <table>
                <tr><td class="lbl">Código:</td><td>FR-ALM-04</td></tr>
                <tr><td class="lbl">Versión:</td><td>00</td></tr>
                <tr><td class="lbl">Fecha:</td><td>01/04/2022</td></tr>
            </table>
        </td>
    </tr>
</table>

{{-- ═══════════ NÚMERO ═══════════ --}}
<div class="numero-row">
    <div class="numero-box">VS-{{ substr($vale->numero, strrpos($vale->numero, '-') + 1) }}</div>
</div>

{{-- ═══════════ DATOS GENERALES ═══════════ --}}
<table class="datos-table">
    <tr>
        <td class="lbl">Obra / Unidad</td>
        <td class="val">{{ $vale->centroCosto?->nombre ?? '-' }}</td>
        <td class="lbl">Fecha de emisión</td>
        <td class="val">{{ \Carbon\Carbon::parse($vale->fecha)->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td class="lbl">Solicitante</td>
        <td class="val">{{ $vale->receptor_nombre }}</td>
        <td class="lbl">Responsable</td>
        <td class="val">{{ $vale->despachador?->nombre ?? '-' }}</td>
    </tr>
    @if($vale->almacen)
    <tr>
        <td class="lbl">Almacén</td>
        <td class="val">{{ $vale->almacen?->nombre ?? '-' }}</td>
        <td class="lbl">Estado</td>
        <td class="val"><span class="estado-{{ $estado }}">{{ $estado }}</span></td>
    </tr>
    @endif
    @if($vale->motivo)
    <tr>
        <td class="lbl">Motivo</td>
        <td class="val" colspan="3">{{ $vale->motivo }}</td>
    </tr>
    @endif
    @if($vale->observaciones)
    <tr>
        <td class="lbl">Observaciones</td>
        <td class="val" colspan="3">{{ $vale->observaciones }}</td>
    </tr>
    @endif
</table>

{{-- ═══════════ DETALLE DE MATERIALES ═══════════ --}}
<table class="items">
    <thead>
        <tr>
            <th class="center" style="width:28px">N°</th>
            <th style="text-align:left">Descripción</th>
            <th class="center" style="width:58px">Unidad</th>
            <th class="center" style="width:65px">Cantidad</th>
            <th class="center" style="width:45px">V°B°</th>
            <th style="width:115px; text-align:left">Observaciones</th>
        </tr>
    </thead>
    <tbody>
        @foreach($vale->detalles as $i => $detalle)
        <tr>
            <td class="center">{{ $i + 1 }}</td>
            <td>{{ $detalle->producto?->nombre ?? '-' }}</td>
            <td class="center">{{ $detalle->producto?->unidad_medida ?? '-' }}</td>
            <td class="center">
                {{ number_format((float)($detalle->cantidad_entregada ?: $detalle->cantidad_solicitada), 2) }}
            </td>
            <td></td>
            <td></td>
        </tr>
        @endforeach
        @for($j = 0; $j < $filasVacias; $j++)
        <tr>
            <td class="center">{{ $filasActuales + $j + 1 }}</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        @endfor
    </tbody>
</table>

{{-- ═══════════ FIRMAS ═══════════ --}}
<table class="firmas-table">
    <tr>
        <td class="firma-label-cell">Firma del Solicitante:</td>
        <td class="firma-label-cell">Firma del Responsable:</td>
        <td class="firma-label-cell">Autorizado por:</td>
    </tr>
    <tr>
        <td class="firma-box">
            <div class="firma-vb">V°B°</div>
            <div class="firma-nombre">{{ strtoupper($vale->receptor_nombre ?? '') }}</div>
        </td>
        <td class="firma-box">
            <div class="firma-vb">V°B°</div>
            <div class="firma-nombre">{{ strtoupper($vale->despachador?->nombre ?? '') }}</div>
        </td>
        <td class="firma-box">
            <div class="firma-vb">V°B°</div>
            <div class="firma-nombre">&nbsp;</div>
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
        $pdf->line(28, $h - 25, $w - 28, $h - 25, $lineColor, 0.5);
        $pdf->page_text(
            $w / 2 - 90,
            $h - 15,
            "CAP Pacifico S.R.L.  -  Página {PAGE_NUM} de {PAGE_COUNT}",
            $font, 7, $color
        );
    }
</script>
</body>
</html>
