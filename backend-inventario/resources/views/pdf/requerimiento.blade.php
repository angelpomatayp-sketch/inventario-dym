<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Requerimiento {{ $requerimiento->numero }}</title>
    <style>
        @page { size: A4 landscape; margin: 20mm 22mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #000; margin: 20mm 22mm; }

        /* ---- HEADER ---- */
        .header-outer { width: 100%; border-collapse: collapse; border: 1.5px solid #1E2D72; margin-bottom: 0; }
        .header-outer td { border: 1px solid #1E2D72; vertical-align: middle; }
        .cell-logo { width: 140px; padding: 6px 10px; text-align: center; }
        .cell-title { text-align: center; padding: 0; }
        .cell-title .lbl-formato { font-size: 10px; font-weight: bold; letter-spacing: 1px; color: #000; padding: 6px 6px 4px; border-bottom: 1px solid #1E2D72; display: block; }
        .cell-title .lbl-requerimiento { font-size: 13px; font-weight: bold; color: #000; padding: 4px 6px 6px; letter-spacing: 0.5px; display: block; }
        .cell-meta { width: 155px; padding: 5px 8px; font-size: 8.5px; vertical-align: middle; text-align: center; }
        .cell-meta table { border-collapse: collapse; margin: 0 auto; }
        .cell-meta td { padding: 1.5px 4px; border: none; text-align: left; }
        .cell-meta .mlabel { color: #000; font-weight: bold; }
        .cell-meta .mvalue { font-weight: normal; color: #000; }

        /* ---- NÚMERO ---- */
        .numero-row { width: 100%; border-collapse: collapse; margin-top: 0; border: none; }
        .numero-row td { padding: 4px 4px 2px; vertical-align: middle; }
        .numero-right { text-align: right; }
        .numero-box { display: inline-table; border: 1.5px solid #000; padding: 3px 0; }
        .numero-box td { padding: 2px 10px; font-size: 9px; }
        .numero-box .n-label { font-weight: bold; }
        .numero-box .n-value { font-weight: bold; font-size: 10px; min-width: 60px; text-align: center; }

        /* ---- DATOS ---- */
        .datos-row { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .datos-row td { padding: 3px 6px; border: none; border-bottom: 1px solid #1E2D72; font-size: 8.5px; vertical-align: middle; }
        .datos-row .dlabel { color: #1E2D72; font-weight: bold; font-size: 8.5px; }
        .datos-row .dvalue { font-weight: bold; color: #1E2D72; }

        /* ---- TABLA ITEMS ---- */
        .items-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .items-table th {
            background: #fff;
            color: #1E2D72;
            padding: 4px 4px;
            font-size: 8px;
            font-weight: bold;
            text-align: center;
            border: 1px solid #1E2D72;
        }
        .items-table .th-recepcion {
            text-align: center;
            border: 1px solid #1E2D72;
            font-size: 8px;
            font-weight: bold;
            color: #1E2D72;
            padding: 3px 4px;
        }
        .items-table td {
            border: 1px solid #1E2D72;
            vertical-align: middle;
            font-size: 8.5px;
            padding: 0 4px;
        }
        .items-table .col-it    { width: 22px;  text-align: center; }
        .items-table .col-cant  { width: 35px;  text-align: center; }
        .items-table .col-und   { width: 40px;  text-align: center; }
        .items-table .col-desc  { width: auto; text-align: left; padding-left: 6px; }
        .items-table .col-cap   { width: 52px;  text-align: center; }
        .items-table .col-pend  { width: 52px;  text-align: center; }
        .items-table .col-fecha { width: 52px;  text-align: center; }
        .items-table .col-obs   { width: 130px; text-align: center; }

        .row-item td { height: 38px; }
        .c-blue   { color: #000; font-weight: bold; }
        .c-orange { color: #C8871A; font-weight: bold; }

        /* ---- FIRMAS ---- */
        .firmas-table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        .firmas-table td { width: 25%; padding: 0; }
        .firma-label-cell { font-size: 8.5px; font-weight: bold; color: #1E2D72; padding: 2px 4px; border: none; text-align: left; }
        .firma-box { border: 1px solid #555; height: 60px; vertical-align: bottom; text-align: left; padding: 3px 5px; }
        .firma-vb { font-size: 8px; color: #1E2D72; }
        .firma-nombre { font-size: 8px; color: #1E2D72; font-weight: bold; }
    </style>
</head>
<body>

@php
    $logoPath = public_path('images/LOGO2.png');
    $logoData = (extension_loaded('gd') && file_exists($logoPath))
        ? base64_encode(file_get_contents($logoPath))
        : null;

    $centroCosto = $requerimiento->centroCosto;
    $almacenero  = $requerimiento->almacenero;
    $aprobador   = $requerimiento->aprobador ?? null;
    $fechaDoc    = \Carbon\Carbon::parse($requerimiento->fecha_solicitud)->format('d/m/Y');
    $lugar       = $centroCosto?->nombre ?? '';
    $referencia  = $centroCosto?->descripcion ?? '';
@endphp

{{-- ===== HEADER ===== --}}
<table class="header-outer">
    <tr>
        <td class="cell-logo">
            @if($logoData)
                <img src="data:image/png;base64,{{ $logoData }}" style="max-width:119px; max-height:50px; display:block; margin:auto;">
            @else
                <div style="font-size:10px; font-weight:bold; color:#1E2D72; text-align:center; line-height:1.4;">
                    CONTRATISTAS ASOCIADOS<br>CAP PACIFICO S.R.L.<br>
                    <span style="font-size:8px; font-weight:normal;">Ingeniería, Minería &amp; Construcción.</span>
                </div>
            @endif
        </td>
        <td class="cell-title">
            <div class="lbl-formato">FORMATO</div>
            <div class="lbl-requerimiento">REQUERIMIENTO DE COMPRA Y/O SERVICIO</div>
        </td>
        <td class="cell-meta">
            <table>
                <tr><td class="mlabel">Código:</td><td class="mvalue">FR-LOG-02</td></tr>
                <tr><td class="mlabel">Versión:</td><td class="mvalue">00</td></tr>
                <tr><td class="mlabel">Fecha:</td><td class="mvalue">02/01/2023</td></tr>
            </table>
        </td>
    </tr>
</table>

{{-- ===== N° ===== --}}
<table class="numero-row">
    <tr>
        <td></td>
        <td class="numero-right">
            <table class="numero-box">
                <tr>
                    <td class="n-label">N°</td>
                    <td class="n-value">{{ $requerimiento->numero }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- ===== DATOS ===== --}}
<table class="datos-row">
    <tr>
        <td style="width:100px;" class="dlabel">Unidad / Obra :</td>
        <td class="dvalue">{{ $centroCosto?->nombre ?? '' }}</td>
        <td style="width:110px;" class="dlabel">Lugar y fecha :</td>
        <td class="dvalue" style="width:200px;">{{ $lugar }}  &nbsp;&nbsp; {{ $fechaDoc }}</td>
    </tr>
    <tr>
        <td class="dlabel">Referencia :</td>
        <td class="dvalue">{{ $referencia }}</td>
        <td class="dlabel">Solicitante :</td>
        <td class="dvalue">{{ strtoupper($almacenero?->nombre ?? '') }}</td>
    </tr>
</table>

{{-- ===== TABLA DE ITEMS ===== --}}
<table class="items-table">
    <thead>
        <tr>
            <th class="col-it"   rowspan="2">IT.</th>
            <th class="col-cant" rowspan="2">CANT.</th>
            <th class="col-und"  rowspan="2">UNIDAD</th>
            <th class="col-desc" rowspan="2">DESCRIPCIÓN.</th>
            <th class="th-recepcion" colspan="3">RECEPCIÓN DE PEDIDO</th>
            <th class="col-obs"  rowspan="2">OBSERVACIONES</th>
        </tr>
        <tr>
            <th class="col-cap">CANT. APROB.</th>
            <th class="col-pend">PENDIENTE</th>
            <th class="col-fecha">FECHA</th>
        </tr>
    </thead>
    <tbody>
        @forelse($requerimiento->detalles as $index => $detalle)
        @php
            $isOdd = ($index % 2 === 0);
            $colorClass = $isOdd ? 'c-blue' : 'c-orange';
        @endphp
        <tr class="row-item">
            <td class="col-it {{ $colorClass }}">{{ $index + 1 }}</td>
            <td class="col-cant {{ $colorClass }}">{{ rtrim(rtrim(number_format($detalle->cantidad_solicitada, 2), '0'), '.') }}</td>
            <td class="col-und {{ $colorClass }}">{{ strtoupper($detalle->producto?->unidad_medida ?? 'UND.') }}</td>
            <td class="col-desc {{ $colorClass }}">{{ strtoupper($detalle->producto?->nombre ?? '-') }}</td>
            <td class="col-cap">
                @if($detalle->cantidad_aprobada !== null)
                    {{ rtrim(rtrim(number_format($detalle->cantidad_aprobada, 2), '0'), '.') }}
                @endif
            </td>
            <td class="col-pend">
                @if($detalle->cantidad_aprobada !== null)
                    @php $pend = max(0, $detalle->cantidad_aprobada - ($detalle->cantidad_entregada ?? 0)); @endphp
                    {{ $pend > 0 ? rtrim(rtrim(number_format($pend, 2), '0'), '.') : '' }}
                @endif
            </td>
            <td class="col-fecha"></td>
            <td class="col-obs {{ $colorClass }}">
                {{ strtoupper($detalle->especificaciones ?? $detalle->observaciones ?? '') }}
            </td>
        </tr>
        @empty
        <tr class="row-item">
            <td colspan="8" style="text-align:center; color:#999; height:38px;">Sin productos registrados</td>
        </tr>
        @endforelse
    </tbody>
</table>

{{-- ===== FIRMAS ===== --}}
<table class="firmas-table">
    <tr>
        <td class="firma-label-cell">Firma/Solicitante</td>
        <td class="firma-label-cell">Aprobado por:</td>
        <td class="firma-label-cell">Resp. Logística</td>
        <td class="firma-label-cell">Autorizado por Gerencia :</td>
    </tr>
    <tr>
        <td class="firma-box">
            <div class="firma-vb">V°B°</div>
            <div class="firma-nombre">{{ strtoupper($almacenero?->nombre ?? '') }}</div>
        </td>
        <td class="firma-box">
            <div class="firma-vb">V°B°</div>
            <div class="firma-nombre">{{ strtoupper($aprobador?->nombre ?? '') }}</div>
        </td>
        <td class="firma-box">
            <div class="firma-vb">V°B°</div>
            <div class="firma-nombre">&nbsp;</div>
        </td>
        <td class="firma-box">
            <div class="firma-vb">V°B°</div>
            <div class="firma-nombre">&nbsp;</div>
        </td>
    </tr>
</table>

</body>
</html>
