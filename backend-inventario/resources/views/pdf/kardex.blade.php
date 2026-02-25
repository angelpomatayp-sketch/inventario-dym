@extends('pdf.layout')

@section('title', 'Reporte Kardex')

@section('content')
    <div class="info-box">
        <table style="border: none;">
            <tr style="background: none;">
                <td style="border: none; width: 50%;">
                    <strong>Almacén:</strong> {{ $almacen ?? 'Todos' }}
                </td>
                <td style="border: none; width: 50%;">
                    <strong>Producto:</strong> {{ $producto ?? 'Todos' }}
                </td>
            </tr>
            <tr style="background: none;">
                <td style="border: none;">
                    <strong>Desde:</strong> {{ $fecha_inicio ?? '-' }}
                </td>
                <td style="border: none;">
                    <strong>Hasta:</strong> {{ $fecha_fin ?? '-' }}
                </td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 70px;">Fecha</th>
                <th style="width: 60px;">Tipo</th>
                <th>Referencia</th>
                <th>Producto</th>
                <th class="text-right" style="width: 50px;">Entrada</th>
                <th class="text-right" style="width: 50px;">Salida</th>
                <th class="text-right" style="width: 50px;">Saldo</th>
                <th class="text-right" style="width: 70px;">Costo Unit.</th>
                <th class="text-right" style="width: 70px;">Valor Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($registros as $registro)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($registro['fecha'])->format('d/m/Y') }}</td>
                    <td>
                        @if($registro['tipo_movimiento'] === 'ENTRADA')
                            <span class="badge badge-success">Entrada</span>
                        @else
                            <span class="badge badge-danger">Salida</span>
                        @endif
                    </td>
                    <td>{{ $registro['referencia'] ?? '-' }}</td>
                    <td>{{ $registro['producto_nombre'] ?? '-' }}</td>
                    <td class="text-right text-success">
                        {{ $registro['tipo_movimiento'] === 'ENTRADA' ? number_format($registro['cantidad'], 2) : '-' }}
                    </td>
                    <td class="text-right text-danger">
                        {{ $registro['tipo_movimiento'] === 'SALIDA' ? number_format($registro['cantidad'], 2) : '-' }}
                    </td>
                    <td class="text-right">{{ number_format($registro['saldo'] ?? 0, 2) }}</td>
                    <td class="text-right">S/ {{ number_format($registro['costo_unitario'] ?? 0, 2) }}</td>
                    <td class="text-right">S/ {{ number_format($registro['valor_total'] ?? 0, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No hay registros para mostrar</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if(isset($totales))
    <div class="totals">
        <h3>Resumen</h3>
        <table style="border: none;">
            <tr style="background: none;">
                <td style="border: none; width: 33%;">
                    <strong>Total Entradas:</strong><br>
                    <span class="text-success">{{ number_format($totales['entradas'] ?? 0, 2) }} unidades</span>
                </td>
                <td style="border: none; width: 33%;">
                    <strong>Total Salidas:</strong><br>
                    <span class="text-danger">{{ number_format($totales['salidas'] ?? 0, 2) }} unidades</span>
                </td>
                <td style="border: none; width: 34%;">
                    <strong>Valor Total Inventario:</strong><br>
                    <span class="text-primary">S/ {{ number_format($totales['valor_total'] ?? 0, 2) }}</span>
                </td>
            </tr>
        </table>
    </div>
    @endif
@endsection
