<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Entrega de Equipo {{ $prestamo->numero }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9.5px;
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
            font-size: 14px; font-weight: bold;
            color: #1E2D72; text-transform: uppercase; letter-spacing: 1.5px;
            margin-top: 2px;
        }
        .header-codigo { width: 140px; font-size: 9px; line-height: 2; }
        .header-codigo table { width: 100%; border-collapse: collapse; }
        .header-codigo td { padding: 0 2px; border: none; font-size: 9px; }
        .header-codigo .lbl { font-weight: bold; white-space: nowrap; }

        /* ── NÚMERO ── */
        .numero-row { width: 100%; text-align: right; margin-bottom: 7px; }
        .numero-box {
            display: inline-block;
            border: 1.5px solid #333;
            padding: 3px 16px;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        /* ── SECCIÓN TÍTULO ── */
        .section-title {
            background-color: #6b7280;
            color: white;
            text-align: center;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 4px 8px;
            margin-bottom: 0;
        }

        /* ── TABLA DE DATOS ── */
        .datos-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #999;
            margin-bottom: 8px;
        }
        .datos-table td {
            padding: 4px 7px;
            border: 1px solid #bbb;
            font-size: 9.5px;
            vertical-align: middle;
        }
        .datos-table .lbl {
            font-weight: bold;
            background-color: #dbeafe;
            color: #1E2D72;
            width: 120px;
            white-space: nowrap;
        }
        .datos-table .val { min-width: 100px; }
        .datos-table .val-empty { min-height: 18px; }

        /* ── TABLA DE RECEPTOR ── */
        .receptor-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #999;
            margin-bottom: 8px;
        }
        .receptor-table td {
            padding: 4px 7px;
            border: 1px solid #bbb;
            font-size: 9.5px;
            vertical-align: middle;
        }
        .receptor-table .lbl {
            font-weight: bold;
            background-color: #f0fdf4;
            color: #14532d;
            width: 120px;
            white-space: nowrap;
        }

        /* ── ESPACIO FOTOGRAFÍAS ── */
        .foto-box {
            width: 100%;
            border: 1px solid #bbb;
            height: 90px;
            margin-bottom: 8px;
        }

        /* ── OBSERVACIONES ── */
        .obs-box {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #999;
            margin-bottom: 12px;
        }
        .obs-box td {
            padding: 5px 8px;
            border: 1px solid #bbb;
            font-size: 9.5px;
        }
        .obs-box .lbl {
            font-weight: bold;
            background-color: #f3f4f6;
            color: #374151;
            white-space: nowrap;
            width: 100px;
        }
        .obs-content { min-height: 35px; vertical-align: top; }

        /* ── FIRMAS ── */
        .firmas-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #bbb;
            margin-top: 8px;
        }
        .firmas-table td {
            width: 33.33%;
            padding: 6px 10px;
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
        .firma-fecha  { font-size: 8px; color: #6b7280; margin-top: 3px; }

        /* ── ESTADO ── */
        .estado-ACTIVO   { background:#d1fae5; color:#065f46; padding:2px 6px; border-radius:3px; font-weight:bold; font-size:8px; }
        .estado-DEVUELTO { background:#dbeafe; color:#1e3a8a; padding:2px 6px; border-radius:3px; font-weight:bold; font-size:8px; }
        .estado-VENCIDO  { background:#fee2e2; color:#991b1b; padding:2px 6px; border-radius:3px; font-weight:bold; font-size:8px; }
        .estado-PERDIDO  { background:#e5e7eb; color:#374151; padding:2px 6px; border-radius:3px; font-weight:bold; font-size:8px; }
        .estado-DANADO   { background:#fef3c7; color:#92400e; padding:2px 6px; border-radius:3px; font-weight:bold; font-size:8px; }
    </style>
</head>
<body>

@php
    $equipo  = $prestamo->equipo;
    $receptor = $prestamo->receptor;
    $entregadoPor = $prestamo->usuarioEntrega;
    $recibidoPor  = $prestamo->usuarioRecepcion;
    $estado = $prestamo->estado ?? 'ACTIVO';
    $esDevolucion = in_array($estado, ['DEVUELTO', 'PERDIDO', 'DANADO']);
@endphp

{{-- ═══════════ CABECERA ═══════════ --}}
<table class="header-table">
    <tr>
        <td class="header-logo">
            @php
                $logoPath = public_path('images/logo2.png');
                $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
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
            <div class="doc-title">ENTREGA DE EQUIPOS</div>
        </td>
        <td class="header-codigo">
            <table>
                <tr><td class="lbl">Código:</td><td>FR-ALM-07</td></tr>
                <tr><td class="lbl">Versión:</td><td>00</td></tr>
                <tr><td class="lbl">Fecha:</td><td>01/04/2022</td></tr>
            </table>
        </td>
    </tr>
</table>

{{-- ═══════════ NÚMERO ═══════════ --}}
<div class="numero-row">
    <div class="numero-box">{{ $prestamo->numero }}</div>
</div>

{{-- ═══════════ DESCRIPCIÓN DEL EQUIPO ═══════════ --}}
<div class="section-title">Descripción del Equipo</div>
<table class="datos-table">
    <tr>
        <td class="lbl">CÓDIGO CAP</td>
        <td class="val">{{ $equipo?->codigo ?? '-' }}</td>
        <td class="lbl">MARCA</td>
        <td class="val">{{ $equipo?->marca ?? '-' }}</td>
    </tr>
    <tr>
        <td class="lbl">NOMBRE / DESCRIPCIÓN</td>
        <td class="val" colspan="3">{{ $equipo?->nombre ?? '-' }}</td>
    </tr>
    <tr>
        <td class="lbl">MODELO</td>
        <td class="val">{{ $equipo?->modelo ?? '-' }}</td>
        <td class="lbl">Nº SERIE</td>
        <td class="val">{{ $equipo?->numero_serie ?? '-' }}</td>
    </tr>
    <tr>
        <td class="lbl">AÑO</td>
        <td class="val">{{ $equipo?->anio ?? '-' }}</td>
        <td class="lbl">Nº MOTOR</td>
        <td class="val">{{ $equipo?->numero_motor ?? '-' }}</td>
    </tr>
    <tr>
        <td class="lbl">DIMENSIONES</td>
        <td class="val">{{ $equipo?->dimensiones ?? '-' }}</td>
        <td class="lbl">COLOR</td>
        <td class="val">{{ $equipo?->color ?? '-' }}</td>
    </tr>
    <tr>
        <td class="lbl">SITUACIÓN</td>
        <td class="val">{{ $equipo?->situacion ?? '-' }}</td>
        <td class="lbl">UBICACIÓN</td>
        <td class="val">{{ $equipo?->ubicacion_fisica ?? ($equipo?->almacen?->nombre ?? '-') }}</td>
    </tr>
    <tr>
        <td class="lbl">CANTIDAD</td>
        <td class="val">{{ $prestamo->cantidad ?? 1 }}</td>
        <td class="lbl">ESTADO</td>
        <td class="val"><span class="estado-{{ $estado }}">{{ $estado }}</span></td>
    </tr>
    @if($prestamo->numero_requerimiento)
    <tr>
        <td class="lbl">Nº REQUERIMIENTO</td>
        <td class="val" colspan="3">{{ $prestamo->numero_requerimiento }}</td>
    </tr>
    @endif
    <tr>
        <td class="lbl">Nº GUÍA REMISIÓN</td>
        <td class="val" style="width:130px;">
            <strong>Ida:</strong> {{ $prestamo->numero_guia_ida ?? '_______________' }}
        </td>
        <td class="lbl">Retorno:</td>
        <td class="val">{{ $prestamo->numero_guia_retorno ?? '_______________' }}</td>
    </tr>
    @if($prestamo->observaciones_entrega || $prestamo->motivo_prestamo)
    <tr>
        <td class="lbl">Observaciones</td>
        <td class="val" colspan="3" style="font-style:italic;">
            {{ $prestamo->observaciones_entrega ?: $prestamo->motivo_prestamo }}
        </td>
    </tr>
    @endif
</table>

{{-- ═══════════ DATOS DEL RECEPTOR ═══════════ --}}
<div class="section-title">Datos del Receptor</div>
<table class="receptor-table">
    <tr>
        <td class="lbl">Recepcionado por</td>
        <td class="val">{{ $receptor?->nombre ?? '-' }}</td>
        <td class="lbl">DNI</td>
        <td class="val">{{ $receptor?->dni ?? '-' }}</td>
    </tr>
    <tr>
        <td class="lbl">Obra / Unidad</td>
        <td class="val">{{ $prestamo->centroCosto?->nombre ?? '-' }}</td>
        <td class="lbl">Área / Destino</td>
        <td class="val">{{ $prestamo->area_destino ?? '-' }}</td>
    </tr>
    <tr>
        <td class="lbl">Fecha de Entrega</td>
        <td class="val">{{ \Carbon\Carbon::parse($prestamo->fecha_prestamo)->format('d/m/Y') }}</td>
        <td class="lbl">Fecha Devolución</td>
        <td class="val">{{ \Carbon\Carbon::parse($prestamo->fecha_devolucion_esperada)->format('d/m/Y') }}</td>
    </tr>
    @if($esDevolucion && $prestamo->fecha_devolucion_real)
    <tr>
        <td class="lbl">Fecha Devolución Real</td>
        <td class="val">{{ \Carbon\Carbon::parse($prestamo->fecha_devolucion_real)->format('d/m/Y') }}</td>
        <td class="lbl">Condición</td>
        <td class="val">{{ $prestamo->condicion_devolucion ?? '-' }}</td>
    </tr>
    @endif
    @if($prestamo->observaciones_devolucion)
    <tr>
        <td class="lbl">Obs. Devolución</td>
        <td class="val" colspan="3">{{ $prestamo->observaciones_devolucion }}</td>
    </tr>
    @endif
</table>

{{-- ═══════════ FOTOGRAFÍAS DEL EQUIPO ═══════════ --}}
<div class="section-title">Fotografías del Equipo</div>
<div class="foto-box"></div>

{{-- ═══════════ OBSERVACIONES ═══════════ --}}
<table class="obs-box">
    <tr>
        <td class="lbl">OBSERVACIONES:</td>
        <td class="obs-content"></td>
    </tr>
</table>

{{-- ═══════════ LUGAR Y FECHA ═══════════ --}}
<div style="text-align:right; font-size:9px; font-style:italic; margin-bottom:12px; color:#555;">
    Huancayo, {{ \Carbon\Carbon::parse($prestamo->fecha_prestamo)->isoFormat('D [de] MMMM [de] Y') }}
</div>

{{-- ═══════════ FIRMAS ═══════════ --}}
<table class="firmas-table">
    <tr>
        <td>
            <div class="firma-titulo">Enviado por:</div>
            <div class="firma-espacio"></div>
            <div class="firma-linea">
                <div class="firma-nombre">{{ $entregadoPor?->nombre ?? 'ALMACÉN CAP PACIFICO' }}</div>
                <div class="firma-cargo">Firma:</div>
                <div class="firma-fecha">Fecha: ___________</div>
            </div>
        </td>
        <td>
            <div class="firma-titulo">Recepcionado por:</div>
            <div class="firma-espacio"></div>
            <div class="firma-linea">
                <div class="firma-nombre">{{ $receptor?->nombre ?? '' }}</div>
                <div class="firma-cargo">Firma:</div>
                <div class="firma-fecha">Fecha: ___________</div>
            </div>
        </td>
        <td>
            <div class="firma-titulo">Devuelto por:</div>
            <div class="firma-espacio"></div>
            <div class="firma-linea">
                <div class="firma-nombre">{{ $recibidoPor?->nombre ?? '' }}</div>
                <div class="firma-cargo">Firma:</div>
                <div class="firma-fecha">Fecha: ___________</div>
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
