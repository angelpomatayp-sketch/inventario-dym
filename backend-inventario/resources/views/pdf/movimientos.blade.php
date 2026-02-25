@extends('pdf.layout')

@section('title', 'Reporte de Movimientos')

@section('content')
    <div class="info-box">
        <table style="border: none;">
            <tr style="background: none;">
                <td style="border: none; width: 33%;">
                    <strong>Almacén:</strong> {{ $almacen ?? 'Todos' }}
                </td>
                <td style="border: none; width: 33%;">
                    <strong>Tipo:</strong> {{ $tipo ?? 'Todos' }}
                </td>
                <td style="border: none; width: 34%;">
                    <strong>Estado:</strong> {{ $estado ?? 'Todos' }}
                </td>
            </tr>
            <tr style="background: none;">
                <td style="border: none;">
                    <strong>Desde:</strong> {{ $fecha_inicio ?? '-' }}
                </td>
                <td style="border: none;" colspan="2">
                    <strong>Hasta:</strong> {{ $fecha_fin ?? '-' }}
                </td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 70px;">Fecha</th>
                <th style="width: 80px;">Número</th>
                <th style="width: 55px;">Tipo</th>
                <th>Almacén</th>
                <th>Referencia</th>
                <th>Usuario</th>
                <th class="text-right" style="width: 55px;">Items</th>
                <th class="text-right" style="width: 75px;">Valor Total</th>
                <th class="text-center" style="width: 55px;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($movimientos as $movimiento)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($movimiento['fecha'])->format('d/m/Y') }}</td>
                    <td>{{ $movimiento['numero'] ?? '-' }}</td>
                    <td>
                        @if($movimiento['tipo'] === 'ENTRADA')
                            <span class="badge badge-success">Entrada</span>
                        @elseif($movimiento['tipo'] === 'SALIDA')
                            <span class="badge badge-danger">Salida</span>
                        @else
                            <span class="badge badge-info">{{ $movimiento['tipo'] }}</span>
                        @endif
                    </td>
                    <td>{{ $movimiento['almacen'] ?? '-' }}</td>
                    <td>{{ $movimiento['referencia'] ?? '-' }}</td>
                    <td>{{ $movimiento['usuario'] ?? '-' }}</td>
                    <td class="text-right">{{ $movimiento['total_items'] ?? 0 }}</td>
                    <td class="text-right">S/ {{ number_format($movimiento['valor_total'] ?? 0, 2) }}</td>
                    <td class="text-center">
                        @if(($movimiento['estado'] ?? '') === 'COMPLETADO')
                            <span class="badge badge-success">OK</span>
                        @elseif(($movimiento['estado'] ?? '') === 'ANULADO')
                            <span class="badge badge-danger">Anulado</span>
                        @else
                            <span class="badge badge-warning">{{ $movimiento['estado'] ?? '-' }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No hay movimientos para mostrar</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if(isset($totales))
    <div class="totals">
        <h3>Resumen de Movimientos</h3>
        <table style="border: none;">
            <tr style="background: none;">
                <td style="border: none; width: 20%;">
                    <strong>Total Movimientos:</strong><br>
                    <span class="text-primary">{{ number_format($totales['total_movimientos'] ?? 0) }}</span>
                </td>
                <td style="border: none; width: 20%;">
                    <strong>Entradas:</strong><br>
                    <span class="text-success">{{ number_format($totales['entradas'] ?? 0) }}</span>
                </td>
                <td style="border: none; width: 20%;">
                    <strong>Salidas:</strong><br>
                    <span class="text-danger">{{ number_format($totales['salidas'] ?? 0) }}</span>
                </td>
                <td style="border: none; width: 20%;">
                    <strong>Valor Entradas:</strong><br>
                    <span class="text-success">S/ {{ number_format($totales['valor_entradas'] ?? 0, 2) }}</span>
                </td>
                <td style="border: none; width: 20%;">
                    <strong>Valor Salidas:</strong><br>
                    <span class="text-danger">S/ {{ number_format($totales['valor_salidas'] ?? 0, 2) }}</span>
                </td>
            </tr>
        </table>
    </div>
    @endif
@endsection
