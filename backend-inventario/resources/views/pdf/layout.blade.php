<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>@yield('title') - Sistema Inventario</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
            padding: 1.5cm 1cm 2.8cm 1cm;
        }

        /* ── CABECERA FR-ALM ── */
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
            color: #1565C0; text-transform: uppercase; letter-spacing: 1.5px;
            margin-top: 2px;
        }
        .header-codigo { width: 140px; font-size: 9px; line-height: 2; }
        .header-codigo table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .header-codigo td { padding: 0 2px; border: none; font-size: 9px; background: none; }
        .header-codigo .lbl { font-weight: bold; white-space: nowrap; }
        .header-fecha {
            text-align: right;
            font-size: 8.5px;
            color: #555;
            margin-bottom: 8px;
            font-style: italic;
        }

        .info-box {
            background-color: #f3f4f6;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .info-box .row {
            display: flex;
            margin-bottom: 3px;
        }

        .info-box .label {
            font-weight: bold;
            width: 120px;
            color: #374151;
        }

        .info-box .value {
            color: #1f2937;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table th {
            background-color: #1565C0;
            color: white;
            padding: 8px 5px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
        }

        table td {
            padding: 6px 5px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9px;
        }

        table tr:nth-child(even) {
            background-color: #f9fafb;
        }

        table tr:hover {
            background-color: #f3f4f6;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-success {
            color: #059669;
        }

        .text-danger {
            color: #dc2626;
        }

        .text-warning {
            color: #d97706;
        }

        .text-primary {
            color: #1565C0;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }

        .badge-success {
            background-color: #d1fae5;
            color: #059669;
        }

        .badge-danger {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .badge-warning {
            background-color: #fef3c7;
            color: #d97706;
        }

        .badge-info {
            background-color: #dbeafe;
            color: #1565C0;
        }

        .totals {
            margin-top: 20px;
            padding: 10px;
            background-color: #eff6ff;
            border: 1px solid #1565C0;
            border-radius: 4px;
        }

        .totals h3 {
            font-size: 12px;
            color: #1565C0;
            margin-bottom: 10px;
        }

        .totals .total-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
        }

        .totals .total-label {
            font-weight: bold;
        }

        .totals .total-value {
            font-weight: bold;
            color: #1565C0;
        }

        /* footer is rendered via PHP canvas script */

        .page-break {
            page-break-after: always;
        }

        @page {
            margin: 0;
        }
    </style>
    @yield('styles')
</head>
<body>
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
                    <div style="font-size:11px; font-weight:bold; color:#1565C0; line-height:1.4;">
                        CONTRATISTAS<br>ASOCIADOS<br>PACIFICO S.R.L.
                    </div>
                @endif
            </td>
            <td class="header-title">
                <div class="formato-label">FORMATO</div>
                <div class="doc-title">@yield('title')</div>
                @hasSection('subtitle')
                <div style="font-size:9px; color:#374151; margin-top:2px;">@yield('subtitle')</div>
                @endif
            </td>
            <td class="header-codigo">
                <table>
                    <tr><td class="lbl">Código:</td><td>@yield('formato_codigo', 'FR-ALM-05')</td></tr>
                    <tr><td class="lbl">Versión:</td><td>00</td></tr>
                    <tr><td class="lbl">Fecha:</td><td>01/04/2022</td></tr>
                </table>
            </td>
        </tr>
    </table>
    <div class="header-fecha">Generado: {{ now()->format('d/m/Y H:i:s') }}</div>

    @yield('content')

    <script type="text/php">
        if (isset($pdf)) {
            $w = $pdf->get_width();
            $h = $pdf->get_height();
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $color = [0.61, 0.64, 0.67];
            $lineColor = [0.90, 0.91, 0.93];

            $pdf->line(28, $h - 30, $w - 28, $h - 30, $lineColor, 0.5);
            $pdf->page_text(
                $w / 2 - 120,
                $h - 18,
                "CAP Pacifico S.R.L.  -  Página {PAGE_NUM} de {PAGE_COUNT}",
                $font,
                7,
                $color
            );
        }
    </script>
</body>
</html>
