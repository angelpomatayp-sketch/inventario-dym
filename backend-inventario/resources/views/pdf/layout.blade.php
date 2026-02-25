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

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #1e40af;
        }

        .header h1 {
            font-size: 18px;
            color: #1e40af;
            margin-bottom: 5px;
        }

        .header .empresa {
            font-size: 14px;
            font-weight: bold;
            color: #374151;
        }

        .header .fecha {
            font-size: 10px;
            color: #6b7280;
            margin-top: 5px;
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
            background-color: #1e40af;
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
            color: #1e40af;
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
            color: #1e40af;
        }

        .totals {
            margin-top: 20px;
            padding: 10px;
            background-color: #eff6ff;
            border: 1px solid #1e40af;
            border-radius: 4px;
        }

        .totals h3 {
            font-size: 12px;
            color: #1e40af;
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
            color: #1e40af;
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
    <div class="header">
        <h1>@yield('title')</h1>
        <div class="empresa">{{ $empresa ?? 'Sistema de Inventario Minero' }}</div>
        <div class="fecha">Generado: {{ now()->format('d/m/Y H:i:s') }}</div>
    </div>

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
                "Sistema de Inventario Minero DYM SAC  -  Página {PAGE_NUM} de {PAGE_COUNT}",
                $font,
                7,
                $color
            );
        }
    </script>
</body>
</html>
