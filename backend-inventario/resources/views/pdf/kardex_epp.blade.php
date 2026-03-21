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
        .header-table { width: 100%; border-collapse: collapse; border: 1px solid #1E6BB0; margin-bottom: 0; }
        .header-table td { vertical-align: middle; border: 1px solid #1E6BB0; }
        .cell-logo { width: 110px; text-align: center; padding: 4px 6px; }
        .cell-title { text-align: center; padding: 3px 6px; }
        .cell-title .lbl-formato { font-size: 7px; font-weight: bold; letter-spacing: 1px; border-bottom: 1px solid #1E6BB0; display: block; padding-bottom: 2px; margin-bottom: 2px; }
        .cell-title .lbl-titulo  { font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3px; display: block; }
        .cell-meta { width: 130px; font-size: 7.5px; padding: 4px 6px; }
        .cell-meta table { width: 100%; border-collapse: collapse; }
        .cell-meta td { padding: 1px 2px; border: none; }
        .cell-meta .mlabel { font-weight: bold; }

        /* ── NÚMERO ── */
        .numero-row { width: 100%; border-collapse: collapse; }
        .numero-row td { padding: 2px 4px; border-left: 1px solid #1E6BB0; border-right: 1px solid #1E6BB0; }
        .numero-inner { float: right; border: 1px solid #1E6BB0; padding: 1px 8px; font-size: 7.5px; font-weight: bold; }

        /* ── SECCIÓN GENÉRICA ── */
        .sec { width: 100%; border-collapse: collapse; border: 1px solid #1E6BB0; border-top: none; }
        .sec td { border: 1px solid #1E6BB0; font-size: 7.5px; vertical-align: middle; }
        .sec-header { background-color: #C5DCF0; text-align: center; font-weight: bold; font-size: 7.5px;
                      letter-spacing: 0.5px; padding: 3px 4px; }
        /* Celda con label arriba y valor abajo */
        .fc { padding: 0; vertical-align: top; }
        .fc-lbl { display: block; font-weight: bold; font-size: 6.8px; text-align: center;
                  text-transform: uppercase; padding: 2px 4px 1px; border-bottom: 1px solid #1E6BB0; }
        .fc-val { display: block; font-size: 7.5px; color: #1E6BB0; font-weight: bold;
                  padding: 2px 4px; min-height: 12px; }

        /* ── TABLA EPP ── */
        .epp-table { width: 100%; border-collapse: collapse; border: 1px solid #1E6BB0; border-top: none; }
        .epp-table th {
            background-color: #C5DCF0;
            border: 1px solid #1E6BB0;
            padding: 2px 2px;
            font-size: 6.5px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            vertical-align: middle;
        }
        .epp-table td {
            border: 1px solid #1E6BB0;
            padding: 1px 2px;
            font-size: 7px;
            vertical-align: middle;
            height: 14px;
        }
        .th-grupo { background-color: #A8CCEA; }
        .col-desc  { width: 105px; text-align: left; padding-left: 4px; }
        .col-und   { width: 20px;  text-align: center; }
        .col-fecha { width: 40px;  text-align: center; }
        .col-firma { width: 26px;  text-align: center; }
        .col-obs   { width: 55px;  text-align: left; padding-left: 3px; }
        .desc-blue { color: #1E6BB0; font-weight: bold; }

        /* ── RESPONSABLE ── */
        .resp-table { width: 100%; border-collapse: collapse; border: 1px solid #1E6BB0; border-top: none; }
        .resp-table td { border: 1px solid #1E6BB0; font-size: 7.5px; vertical-align: middle; }
        .resp-box { height: 20px; }
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
                <div style="font-size:8px; font-weight:bold; color:#1E6BB0; text-align:center; line-height:1.4;">
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
<table class="numero-row">
    <tr>
        <td><div class="numero-inner">Nº &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div><div style="clear:both;height:4px;"></div></td>
    </tr>
</table>

{{-- ═══ DATOS DEL EMPLEADOR ═══ --}}
<table class="sec">
    <tr>
        <td colspan="5" class="sec-header">DATOS DEL EMPLEADOR</td>
    </tr>
    <tr>
        <td class="fc" style="width:135px;">
            <span class="fc-lbl">Razon Social</span>
            <span class="fc-val">CONTRATISTAS ASOCIADOS PACIFICO S.R.L.</span>
        </td>
        <td class="fc" style="width:70px;">
            <span class="fc-lbl">RUC</span>
            <span class="fc-val">20487244423</span>
        </td>
        <td class="fc">
            <span class="fc-lbl">Actividad Economica</span>
            <span class="fc-val">CIERRE DE MINAS Y PASIVOS AMBIENTALES</span>
        </td>
    </tr>
    <tr>
        <td class="fc" colspan="2">
            <span class="fc-lbl">Domicilio</span>
            <span class="fc-val">AV. DANIEL ALCIDES CARRION Nº2164</span>
        </td>
        <td class="fc">
            <span class="fc-lbl">Nº de Trabajadores</span>
            <span class="fc-val" style="text-align:center;">{{ $numTrabajadores }}</span>
        </td>
    </tr>
</table>

{{-- ═══ DATOS DEL TRABAJADOR ═══ --}}
<table class="sec">
    <tr>
        <td colspan="4" class="sec-header">DATOS DEL TRABAJADOR</td>
    </tr>
    <tr>
        <td class="fc" style="width:200px;">
            <span class="fc-lbl">Apellidos y Nombres:</span>
            <span class="fc-val">{{ strtoupper($trabajador->nombre) }}</span>
        </td>
        <td class="fc">
            <span class="fc-lbl">DNI</span>
            <span class="fc-val">{{ $trabajador->dni ?? '' }}</span>
        </td>
    </tr>
    <tr>
        <td class="fc" style="width:130px;">
            <span class="fc-lbl">Puesto de Trabajo</span>
            <span class="fc-val">{{ strtoupper($trabajador->cargo ?? '') }}</span>
        </td>
        <td class="fc" style="width:100px;">
            <span class="fc-lbl">Fecha de Ingreso</span>
            <span class="fc-val">{{ $fechaIngreso }}</span>
        </td>
        <td class="fc">
            <span class="fc-lbl">Lugar de Trabajo</span>
            <span class="fc-val">{{ strtoupper($centroCosto?->nombre ?? '') }}</span>
        </td>
    </tr>
</table>

{{-- ═══ TABLA EPP ═══ --}}
<table class="epp-table">
    <thead>
        <tr>
            <th class="col-desc" rowspan="2">DESCRIPCION</th>
            <th class="col-und"  rowspan="2">UND.</th>
            <th colspan="6" class="th-grupo">FECHA DE ENTREGA DE EQUIPO DE PROTECCION PERSONAL</th>
            <th class="col-obs" rowspan="2">OBSERVACION</th>
        </tr>
        <tr>
            <th class="col-fecha">FECHA</th>
            <th class="col-firma">FIRMA</th>
            <th class="col-fecha">FECHA<br>ENT./DEV.</th>
            <th class="col-firma">FIRMA</th>
            <th class="col-fecha">FECHA<br>ENT./DEV.</th>
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
            <td colspan="9" style="text-align:center; color:#999; height:16px; border:1px solid #1E6BB0;">Sin EPPs asignados</td>
        </tr>
        @endforelse
    </tbody>
</table>

{{-- ═══ RESPONSABLE DEL REGISTRO ═══ --}}
<table class="resp-table">
    <tr>
        <td colspan="4" class="sec-header">RESPONSABLE DEL REGISTRO</td>
    </tr>
    <tr>
        <td class="fc" style="width:200px;">
            <span class="fc-lbl">Apellidos y Nombres</span>
        </td>
        <td class="fc" style="width:80px;">
            <span class="fc-lbl">Cargo</span>
        </td>
        <td class="fc" style="width:60px;">
            <span class="fc-lbl">Firma</span>
        </td>
        <td class="fc">
            <span class="fc-lbl">Fecha</span>
        </td>
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
