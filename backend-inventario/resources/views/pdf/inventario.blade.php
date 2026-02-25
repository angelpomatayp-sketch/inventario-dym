@extends('pdf.layout')

@section('title', 'Reporte de Inventario')

@section('content')
    <div class="info-box">
        <table style="border: none;">
            <tr style="background: none;">
                <td style="border: none; width: 50%;">
                    <strong>Almacén:</strong> {{ $almacen ?? 'Todos' }}
                </td>
                <td style="border: none; width: 50%;">
                    <strong>Familia:</strong> {{ $familia ?? 'Todas' }}
                </td>
            </tr>
            <tr style="background: none;">
                <td style="border: none;" colspan="2">
                    <strong>Fecha de corte:</strong> {{ now()->format('d/m/Y H:i') }}
                </td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 70px;">Código</th>
                <th>Producto</th>
                <th>Familia</th>
                <th>Almacén</th>
                <th>U.M.</th>
                <th class="text-right" style="width: 60px;">Stock</th>
                <th class="text-right" style="width: 50px;">Mínimo</th>
                <th class="text-right" style="width: 70px;">Costo Unit.</th>
                <th class="text-right" style="width: 80px;">Valor Total</th>
                <th class="text-center" style="width: 60px;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($productos as $producto)
                <tr>
                    <td>{{ $producto['codigo'] }}</td>
                    <td>{{ $producto['nombre'] }}</td>
                    <td>{{ $producto['familia'] ?? '-' }}</td>
                    <td>{{ $producto['almacen'] ?? '-' }}</td>
                    <td>{{ $producto['unidad_medida'] ?? 'UND' }}</td>
                    <td class="text-right">{{ number_format($producto['stock'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($producto['stock_minimo'] ?? 0, 2) }}</td>
                    <td class="text-right">S/ {{ number_format($producto['costo_unitario'] ?? 0, 2) }}</td>
                    <td class="text-right">S/ {{ number_format($producto['valor_total'] ?? 0, 2) }}</td>
                    <td class="text-center">
                        @if(($producto['stock'] ?? 0) <= 0)
                            <span class="badge badge-danger">Sin Stock</span>
                        @elseif(($producto['stock'] ?? 0) <= ($producto['stock_minimo'] ?? 0))
                            <span class="badge badge-warning">Bajo</span>
                        @else
                            <span class="badge badge-success">OK</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">No hay productos para mostrar</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if(isset($totales))
    <div class="totals">
        <h3>Resumen del Inventario</h3>
        <table style="border: none;">
            <tr style="background: none;">
                <td style="border: none; width: 25%;">
                    <strong>Total Productos:</strong><br>
                    <span class="text-primary">{{ number_format($totales['total_productos'] ?? 0) }}</span>
                </td>
                <td style="border: none; width: 25%;">
                    <strong>Total Unidades:</strong><br>
                    <span class="text-primary">{{ number_format($totales['total_unidades'] ?? 0, 2) }}</span>
                </td>
                <td style="border: none; width: 25%;">
                    <strong>Productos Bajo Stock:</strong><br>
                    <span class="text-warning">{{ number_format($totales['bajo_stock'] ?? 0) }}</span>
                </td>
                <td style="border: none; width: 25%;">
                    <strong>Valor Total:</strong><br>
                    <span class="text-primary">S/ {{ number_format($totales['valor_total'] ?? 0, 2) }}</span>
                </td>
            </tr>
        </table>
    </div>
    @endif
@endsection
