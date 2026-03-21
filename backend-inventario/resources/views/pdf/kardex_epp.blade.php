<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Kardex EPP - {{ $trabajador->nombre }}</title>
    <style>
        @page { size: A4 portrait; margin: 10mm 12mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 7.5px; color: #000; }

        /* ── CABECERA ── */
        .header-table { width: 100%; border-collapse: collapse; border: 1.5px solid #1E6BB0; margin-bottom: 0; }
        .header-table td { vertical-align: middle; padding: 3px 8px; border: 1px solid #1E6BB0; }
        .cell-logo { width: 130px; text-align: center; }
        .cell-title { text-align: center; }
        .cell-title .lbl-formato { font-size: 8px; font-weight: bold; letter-spacing: 1px; border-bottom: 1px solid #1E6BB0; display: block; padding-bottom: 2px; margin-bottom: 2px; }
        .cell-title .lbl-titulo { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; display: block; }
        .cell-meta { width: 150px; font-size: 8px; }
        .cell-meta table { width: 100%; border-collapse: collapse; }
        .cell-meta td { padding: 1px 3px; border: none; }
        .cell-meta .mlabel { font-weight: bold; }

        /* ── NÚMERO ── */
        .numero-row { width: 100%; border-collapse: collapse; border-left: 1.5px solid #1E6BB0; border-right: 1.5px solid #1E6BB0; }
        .numero-row td { padding: 2px 6px; }
        .numero-box { float: right; border: 1px solid #1E6BB0; padding: 1px 12px; font-size: 8px; font-weight: bold; }

        /* ── SECCIONES ── */
        .seccion-table { width: 100%; border-collapse: collapse; border: 1.5px solid #1E6BB0; margin-top: 0; }
        .seccion-table td { border: 1px solid #1E6BB0; padding: 2px 6px; font-size: 8px; vertical-align: middle; }
        .seccion-header { background-color: #CCDFF0; text-align: center; font-weight: bold; font-size: 8px; letter-spacing: 0.5px; padding: 3px 6px; }
        .lbl { font-weight: bold; font-size: 7.5px; text-align: center; }
        .val { font-size: 8px; min-height: 14px; }
        .val-blue { color: #1E6BB0; font-weight: bold; }

        /* ── TABLA EPP ── */
        .epp-table { width: 100%; border-collapse: collapse; margin-top: 0; border: 1.5px solid #1E6BB0; }
        .epp-table th {
            background-color: #CCDFF0;
            border: 1px solid #1E6BB0;
            padding: 3px 3px;
            font-size: 7px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            vertical-align: middle;
        }
        .epp-table td {
            border: 1px solid #1E6BB0;
            padding: 1px 3px;
            font-size: 7.5px;
            vertical-align: middle;
            height: 16px;
        }
        .col-desc  { width: 110px; text-align: left; padding-left: 5px; }
        .col-und   { width: 22px; text-align: center; }
        .col-fecha { width: 42px; text-align: center; }
        .col-firma { width: 28px; text-align: center; }
        .col-obs   { width: 60px; text-align: left; padding-left: 3px; }
        .desc-blue { color: #1E6BB0; font-weight: bold; }
        .th-grupo  { background-color: #B8D4EA; }

        /* ── RESPONSABLE ── */
        .resp-table { width: 100%; border-collapse: collapse; margin-top: 0; border: 1.5px solid #1E6BB0; }
        .resp-table td { border: 1px solid #1E6BB0; padding: 2px 6px; font-size: 8px; vertical-align: middle; }
        .resp-box { height: 22px; min-width: 60px; }
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

{{-- ═══════════ CABECERA ═══════════ --}}
<table class="header-table">
    <tr>
        <td class="cell-logo">
            @if($logoData)
                <img src="data:image/png;base64,{{ $logoData }}" style="max-width:110px; max-height:40px; display:block; margin:auto;">
            @else
                <div style="font-size:9px; font-weight:bold; color:#1E6BB0; text-align:center; line-height:1.4;">
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

{{-- ═══════════ NÚMERO ═══════════ --}}
<table class="numero-row">
    <tr>
        <td><div class="numero-box">Nº &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div></td>
    </tr>
</table>

{{-- ═══════════ DATOS DEL EMPLEADOR ═══════════ --}}
<table class="seccion-table">
    <tr>
        <td colspan="6" class="seccion-header">DATOS DEL EMPLEADOR</td>
    </tr>
    <tr>
        <td class="lbl" style="width:120px;">RAZON SOCIAL</td>
        <td class="val val-blue" style="width:240px;">CONTRATISTAS ASOCIADOS PACIFICO S.R.L.</td>
        <td class="lbl" style="width:60px;">RUC</td>
        <td class="val val-blue" style="width:100px;">20487244423</td>
        <td class="lbl" style="width:110px;">ACTIVIDAD ECONOMICA</td>
        <td class="val val-blue">CIERRE DE MINAS Y PASIVOS AMBIENTALES</td>
    </tr>
    <tr>
        <td class="lbl">DOMICILIO</td>
        <td class="val val-blue" colspan="3">AV. DANIEL ALCIDES CARRION Nº2164</td>
        <td class="lbl">Nº DE TRABAJADORES</td>
        <td class="val val-blue" style="text-align:center;">{{ $numTrabajadores }}</td>
    </tr>
</table>

{{-- ═══════════ DATOS DEL TRABAJADOR ═══════════ --}}
<table class="seccion-table" style="margin-top:0; border-top:none;">
    <tr>
        <td colspan="4" class="seccion-header">DATOS DEL TRABAJADOR</td>
    </tr>
    <tr>
        <td class="lbl" style="width:130px;">APELLIDOS Y NOMBRES:</td>
        <td class="val val-blue" style="width:300px;">{{ strtoupper($trabajador->nombre) }}</td>
        <td class="lbl" style="width:40px;">DNI</td>
        <td class="val val-blue">{{ $trabajador->dni ?? '' }}</td>
    </tr>
    <tr>
        <td class="lbl">PUESTO DE TRABAJO</td>
        <td class="val val-blue">{{ strtoupper($trabajador->cargo ?? '') }}</td>
        <td class="lbl">FECHA DE INGRESO</td>
        <td class="val val-blue">{{ $fechaIngreso }}</td>
    </tr>
    <tr>
        <td class="lbl">LUGAR DE TRABAJO</td>
        <td class="val val-blue" colspan="3">{{ strtoupper($centroCosto?->nombre ?? '') }}</td>
    </tr>
</table>

{{-- ═══════════ TABLA EPP ═══════════ --}}
<table class="epp-table" style="margin-top:0; border-top:none;">
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
            <td class="col-fecha" style="text-align:center;">
                {{ isset($fila['slots'][$s]) ? $fila['slots'][$s]['fecha'] : '' }}
            </td>
            <td class="col-firma"></td>
            @endfor
            <td class="col-obs">{{ $fila['obs'] }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="11" style="text-align:center; color:#999; height:18px;">Sin EPPs asignados</td>
        </tr>
        @endforelse
    </tbody>
</table>

{{-- ═══════════ RESPONSABLE DEL REGISTRO ═══════════ --}}
<table class="resp-table" style="margin-top:0; border-top:none;">
    <tr>
        <td colspan="4" class="seccion-header">RESPONSABLE DEL REGISTRO</td>
    </tr>
    <tr>
        <td class="lbl" style="width:200px;">APELLIDOS Y NOMBRES</td>
        <td class="lbl" style="width:100px;">CARGO</td>
        <td class="lbl" style="width:80px;">FIRMA</td>
        <td class="lbl">FECHA</td>
    </tr>
    <tr>
        <td class="resp-box"></td>
        <td class="resp-box"></td>
        <td class="resp-box"></td>
        <td class="resp-box"></td>
    </tr>
</table>

</body>
</html>
