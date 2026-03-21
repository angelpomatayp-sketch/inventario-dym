<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Kardex EPP - {{ $trabajador->nombre }}</title>
    <style>
        @page { size: A4 portrait; margin: 20mm 18mm 20mm 18mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 7.5px; color: #000; margin: 20mm 18mm; }

        /* ── CABECERA ── */
        .header-table { width: 100%; border-collapse: collapse; border: 1px solid #1565C0; }
        .header-table td { vertical-align: middle; border: 1px solid #1565C0; }
        .cell-logo { width: 110px; text-align: center; padding: 4px 6px; }
        .cell-title { text-align: center; padding: 4px 8px; }
        .cell-title .lbl-formato { font-size: 7px; font-weight: bold; letter-spacing: 1px;
            border-bottom: 1px solid #1565C0; display: block; padding-bottom: 2px; margin-bottom: 3px; }
        .cell-title .lbl-titulo { font-size: 9px; font-weight: bold; text-transform: uppercase; display: block; }
        .cell-meta { width: 125px; font-size: 7.5px; padding: 4px 8px; }
        .cell-meta table { width: 100%; border-collapse: collapse; }
        .cell-meta td { padding: 1px 2px; border: none; }
        .cell-meta .mlabel { font-weight: bold; }

        /* ── NÚMERO ── */
        .numero-wrap { width: 100%; border-collapse: collapse; }
        .numero-wrap td { padding: 2px 0; border-left: 1px solid #1565C0; border-right: 1px solid #1565C0; }
        .numero-box { float: right; border: 1px solid #1565C0; padding: 1px 10px; font-size: 7.5px; font-weight: bold; }

        /* ── TABLAS DE SECCIÓN ── */
        .sec { width: 100%; border-collapse: collapse; border: 1px solid #1565C0; border-top: none; }
        .sec td { border: 1px solid #1565C0; font-size: 7.5px; }

        /* Fila de encabezado de sección */
        .sec-hdr { background-color: #BBDEFB; text-align: center; font-weight: bold;
                   font-size: 7.5px; letter-spacing: 0.5px; padding: 3px 4px; }

        /* Fila de etiquetas (negrita, centrada) */
        .lbl-row td { font-weight: bold; text-align: center; padding: 3px 6px 1px 6px;
                      font-size: 7.5px; text-transform: uppercase; }

        /* Valores empresa: negro sin negrita */
        .val-emp td { color: #000; font-weight: normal; text-align: center;
                      padding: 1px 6px 3px 6px; font-size: 7.5px; min-height: 12px; }

        /* Valores trabajador: negro sin negrita */
        .val-trab td { color: #000; font-weight: normal; text-align: left;
                       padding: 1px 6px 3px 6px; font-size: 7.5px; min-height: 12px; }

        /* ── TABLA EPP ── */
        .epp-table { width: 100%; border-collapse: collapse; border: 1px solid #1565C0; border-top: none; }
        .epp-table th {
            background-color: #BBDEFB;
            border: 1px solid #1565C0;
            padding: 2px 2px;
            font-size: 6.5px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            vertical-align: middle;
        }
        .epp-table td {
            border: 1px solid #1565C0;
            padding: 1px 3px;
            font-size: 7px;
            vertical-align: middle;
            height: 14px;
        }
        .th-grupo { background-color: #90CAF9; }
        .col-desc  { width: 110px; text-align: left;   padding-left: 4px; }
        .col-und   { width: 22px;  text-align: center; }
        .col-fecha { width: 42px;  text-align: center; }
        .col-firma { width: 28px;  text-align: center; }
        .col-obs   { width: 55px;  text-align: left;   padding-left: 3px; }
        .desc-blue { color: #1565C0; font-weight: bold; }

        /* ── RESPONSABLE ── */
        .resp-table { width: 100%; border-collapse: collapse; border: 1px solid #1565C0; border-top: none; }
        .resp-table td { border: 1px solid #1565C0; font-size: 7.5px; }
        .resp-lbl { font-weight: bold; text-align: center; padding: 3px 6px 1px; font-size: 7px; text-transform: uppercase; }
        .resp-val { height: 20px; }
    </style>
</head>
<body>

@php
    $logoPath = public_path('images/LOGO2.png');
    $logoData = (extension_loaded('gd') && file_exists($logoPath))
        ? base64_encode(file_get_contents($logoPath))
        : null;
    $centroCosto = $trabajador->centroCosto;
    $fechaIngreso = $trabajador->fecha_ingreso
        ? \Carbon\Carbon::parse($trabajador->fecha_ingreso)->format('d/m/Y')
        : '';
@endphp

{{-- ═══ CABECERA ═══ --}}
<table class="header-table">
    <tr>
        <td class="cell-logo">
            @if($logoData)
                <img src="data:image/png;base64,{{ $logoData }}" style="max-width:95px; max-height:38px; display:block; margin:auto;">
            @else
                <div style="font-size:8px; font-weight:bold; color:#1565C0; text-align:center; line-height:1.4;">
                    CONTRATISTAS ASOCIADOS<br>CAP PACIFICO S.R.L.
                </div>
            @endif
        </td>
        <td class="cell-title">
            <span class="lbl-formato">FORMATO</span>
            <span class="lbl-titulo">Registro de Entrega de Equipos de Protección Personal</span>
        </td>
        <td class="cell-meta">
            <table>
                <tr><td class="mlabel">Código:</td><td>FR-ALM-02</td></tr>
                <tr><td class="mlabel">Versión:</td><td>00</td></tr>
                <tr><td class="mlabel">Fecha:</td><td>01/04/2022</td></tr>
            </table>
        </td>
    </tr>
</table>

{{-- ═══ NÚMERO ═══ --}}
<table class="numero-wrap">
    <tr>
        <td>
            <div class="numero-box">Nº &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
            <div style="clear:both; height:5px;"></div>
        </td>
    </tr>
</table>

{{-- ═══ DATOS DEL EMPLEADOR ═══ --}}
<table class="sec">
    <tr>
        <td colspan="3" class="sec-hdr">DATOS DEL EMPLEADOR</td>
    </tr>
    <tr class="lbl-row">
        <td style="width:57%;">RAZON SOCIAL</td>
        <td style="width:17%;">RUC</td>
        <td style="width:26%;">ACTIVIDAD ECONOMICA</td>
    </tr>
    <tr class="val-emp">
        <td>CONTRATISTAS ASOCIADOS PACIFICO S.R.L.</td>
        <td>20487244423</td>
        <td>CIERRE DE MINAS Y PASIVOS AMBIENTALES</td>
    </tr>
    <tr class="lbl-row">
        <td colspan="2" style="width:74%;">DOMICILIO</td>
        <td style="width:26%;">Nº DE TRABAJADORES</td>
    </tr>
    <tr class="val-emp">
        <td colspan="2">AV. DANIEL ALCIDES CARRION Nº2164</td>
        <td style="text-align:center;">{{ $numTrabajadores }}</td>
    </tr>
</table>

{{-- ═══ DATOS DEL TRABAJADOR ═══ --}}
<table class="sec">
    <tr>
        <td colspan="3" class="sec-hdr">DATOS DEL TRABAJADOR</td>
    </tr>
    <tr class="lbl-row">
        <td colspan="2" style="width:79%; text-align:left; padding-left:8px;">APELLIDOS Y NOMBRES:</td>
        <td style="width:21%;">DNI</td>
    </tr>
    <tr class="val-trab">
        <td colspan="2">{{ strtoupper($trabajador->nombre) }}</td>
        <td>{{ $trabajador->dni ?? '' }}</td>
    </tr>
    <tr class="lbl-row">
        <td style="width:42%;">PUESTO DE TRABAJO</td>
        <td style="width:26%;">FECHA DE INGRESO</td>
        <td style="width:32%;">LUGAR DE TRABAJO</td>
    </tr>
    <tr class="val-trab">
        <td>{{ strtoupper($trabajador->cargo ?? '') }}</td>
        <td>{{ $fechaIngreso }}</td>
        <td>{{ strtoupper($centroCosto?->nombre ?? '') }}</td>
    </tr>
</table>

{{-- ═══ TABLA EPP ═══ --}}
<table class="epp-table">
    <thead>
        <tr>
            <th class="col-desc" rowspan="2">DESCRIPCION</th>
            <th class="col-und"  rowspan="2">UNIDAD</th>
            <th colspan="6" class="th-grupo">FECHA DE ENTREGA DE EQUIPO DE PROTECCION PERSONAL</th>
            <th class="col-obs" rowspan="2">OBSERVACION</th>
        </tr>
        <tr>
            <th class="col-fecha">FECHA</th>
            <th class="col-firma">FIRMA</th>
            <th class="col-fecha">FECHA<br>ENTREGA/<br>DEVOLUCIÓN</th>
            <th class="col-firma">FIRMA</th>
            <th class="col-fecha">FECHA<br>ENTREGA/<br>DEVOLUCIÓN</th>
            <th class="col-firma">FIRMA</th>
        </tr>
    </thead>
    <tbody>
        @forelse($filas as $fila)
        <tr>
            <td class="col-desc {{ $fila['descripcion'] ? 'desc-blue' : '' }}">{{ $fila['descripcion'] }}</td>
            <td class="col-und">{{ $fila['unidad'] }}</td>
            @for($s = 0; $s < 3; $s++)
            <td class="col-fecha">{{ isset($fila['slots'][$s]) ? $fila['slots'][$s]['fecha'] : '' }}</td>
            <td class="col-firma"></td>
            @endfor
            <td class="col-obs">{{ $fila['obs'] }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="9" style="text-align:center; color:#999; height:16px;">Sin EPPs asignados</td>
        </tr>
        @endforelse
    </tbody>
</table>

{{-- ═══ RESPONSABLE DEL REGISTRO ═══ --}}
<table class="resp-table">
    <tr>
        <td colspan="4" class="sec-hdr">RESPONSABLE DEL REGISTRO</td>
    </tr>
    <tr>
        <td class="resp-lbl" style="width:200px;">APELLIDOS Y NOMBRES</td>
        <td class="resp-lbl" style="width:80px;">CARGO</td>
        <td class="resp-lbl" style="width:60px;">FIRMA</td>
        <td class="resp-lbl">FECHA</td>
    </tr>
    <tr>
        <td class="resp-val"></td>
        <td class="resp-val"></td>
        <td class="resp-val"></td>
        <td class="resp-val"></td>
    </tr>
</table>

</body>
</html>
