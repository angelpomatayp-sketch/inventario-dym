<?php

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\Models\Kardex;
use App\Modules\Inventario\Models\Movimiento;
use App\Modules\Inventario\Models\Producto;
use App\Shared\Traits\ApiResponse;
use App\Shared\Traits\FiltrosPorRol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KardexController extends Controller
{
    use ApiResponse, FiltrosPorRol;

    /**
     * Listar movimientos del kardex.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Kardex::with(['producto:id,codigo,nombre', 'almacen:id,nombre,centro_costo_id', 'movimiento:id,numero,tipo']);
        $incluirAnulados = $request->boolean('incluir_anulados', false);

        // Filtro por empresa (multi-tenancy)
        if ($request->user()->empresa_id) {
            $query->where('empresa_id', $request->user()->empresa_id);
        }

        // Filtro por almacén según rol (almacenero solo ve su almacén)
        $almacenAsignado = $this->getAlmacenAsignado($request);
        if ($almacenAsignado) {
            $query->where('almacen_id', $almacenAsignado);
        } else {
            // Filtro por centro de costo (residente/asistente solo ven almacenes de su proyecto)
            $centroCostoAsignado = $this->getCentroCostoAsignado($request);
            if ($centroCostoAsignado) {
                // Filtrar solo almacenes que pertenecen al centro de costo del usuario
                $query->whereHas('almacen', function ($q) use ($centroCostoAsignado) {
                    $q->where('centro_costo_id', $centroCostoAsignado);
                });
            }

            // Filtro manual por almacén (para usuarios con acceso total o dentro de su CC)
            if ($request->filled('almacen_id')) {
                $query->where('almacen_id', $request->almacen_id);
            }
        }

        // Filtros adicionales
        if ($request->filled('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
        }

        if ($request->filled('tipo_operacion')) {
            $query->where('tipo_operacion', $request->tipo_operacion);
        }

        if (!$incluirAnulados) {
            $query->where(function ($q) {
                $q->whereNull('movimiento_id')
                  ->orWhereHas('movimiento', function ($movQ) {
                      $movQ->where('estado', '!=', Movimiento::ESTADO_ANULADO);
                  });
            });
        }

        // Ordenamiento
        $query->orderBy('fecha', 'desc')->orderBy('id', 'desc');

        // Paginación
        $perPage = $this->resolvePerPage($request, 20, 100);
        $kardex = $query->paginate($perPage);

        return $this->paginated($kardex);
    }

    /**
     * Reporte de kardex valorizado.
     */
    public function reporte(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'producto_id' => 'nullable|exists:productos,id',
            'almacen_id' => 'nullable|exists:almacenes,id',
        ], [
            'fecha_inicio.required' => 'La fecha de inicio es requerida',
            'fecha_fin.required' => 'La fecha de fin es requerida',
        ]);

        $empresaId = $request->user()->empresa_id;
        $fechaInicio = $request->fecha_inicio;
        $fechaFin = $request->fecha_fin;
        $incluirAnulados = $request->boolean('incluir_anulados', false);

        // Query base
        $query = Kardex::with(['producto:id,codigo,nombre,unidad_medida', 'almacen:id,nombre'])
            ->whereBetween('fecha', [$fechaInicio, $fechaFin]);

        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        // Filtro por almacén según rol (almacenero solo ve su almacén)
        $almacenAsignado = $this->getAlmacenAsignado($request);
        if ($almacenAsignado) {
            $query->where('almacen_id', $almacenAsignado);
        } else {
            // Filtro por centro de costo (residente/asistente solo ven almacenes de su proyecto)
            $centroCostoAsignado = $this->getCentroCostoAsignado($request);
            if ($centroCostoAsignado) {
                $query->whereHas('almacen', function ($q) use ($centroCostoAsignado) {
                    $q->where('centro_costo_id', $centroCostoAsignado);
                });
            }

            // Filtro manual por almacén
            if ($request->filled('almacen_id')) {
                $query->where('almacen_id', $request->almacen_id);
            }
        }

        if ($request->filled('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }

        if (!$incluirAnulados) {
            $query->where(function ($q) {
                $q->whereNull('movimiento_id')
                  ->orWhereHas('movimiento', function ($movQ) {
                      $movQ->where('estado', '!=', Movimiento::ESTADO_ANULADO);
                  });
            });
        }

        $kardex = $query->orderBy('producto_id')
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        // Agrupar por producto
        $reportePorProducto = $kardex->groupBy('producto_id')->map(function ($movimientos, $productoId) use ($fechaInicio) {
            $producto = $movimientos->first()->producto;

            // Obtener saldo inicial (último registro antes del período)
            $saldoInicial = Kardex::where('producto_id', $productoId)
                ->where('fecha', '<', $fechaInicio)
                ->orderBy('fecha', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $saldoInicialCantidad = $saldoInicial ? $saldoInicial->saldo_cantidad : 0;
            $saldoInicialValor = $saldoInicial ? $saldoInicial->saldo_costo_total : 0;

            // Calcular totales
            $totalEntradas = $movimientos->whereIn('tipo_operacion', ['ENTRADA', 'AJUSTE_POSITIVO'])->sum('cantidad');
            $totalEntradasValor = $movimientos->whereIn('tipo_operacion', ['ENTRADA', 'AJUSTE_POSITIVO'])->sum('costo_total');
            $totalSalidas = $movimientos->whereIn('tipo_operacion', ['SALIDA', 'AJUSTE_NEGATIVO'])->sum('cantidad');
            $totalSalidasValor = $movimientos->whereIn('tipo_operacion', ['SALIDA', 'AJUSTE_NEGATIVO'])->sum('costo_total');

            $ultimoMovimiento = $movimientos->last();

            return [
                'producto' => [
                    'id' => $producto->id,
                    'codigo' => $producto->codigo,
                    'nombre' => $producto->nombre,
                    'unidad_medida' => $producto->unidad_medida,
                ],
                'saldo_inicial' => [
                    'cantidad' => $saldoInicialCantidad,
                    'valor' => $saldoInicialValor,
                ],
                'entradas' => [
                    'cantidad' => $totalEntradas,
                    'valor' => $totalEntradasValor,
                ],
                'salidas' => [
                    'cantidad' => $totalSalidas,
                    'valor' => $totalSalidasValor,
                ],
                'saldo_final' => [
                    'cantidad' => $ultimoMovimiento->saldo_cantidad,
                    'valor' => $ultimoMovimiento->saldo_costo_total,
                    'costo_promedio' => $ultimoMovimiento->saldo_costo_unitario,
                ],
                'movimientos' => $movimientos->map(fn($k) => [
                    'fecha' => $k->fecha->format('Y-m-d'),
                    'tipo' => $k->tipo_operacion,
                    'documento' => $k->documento_referencia,
                    'cantidad' => $k->cantidad,
                    'costo_unitario' => $k->costo_unitario,
                    'costo_total' => $k->costo_total,
                    'saldo_cantidad' => $k->saldo_cantidad,
                    'saldo_valor' => $k->saldo_costo_total,
                ]),
            ];
        })->values();

        return $this->success([
            'periodo' => [
                'inicio' => $fechaInicio,
                'fin' => $fechaFin,
            ],
            'productos' => $reportePorProducto,
            'resumen' => [
                'total_productos' => $reportePorProducto->count(),
                'total_entradas' => $reportePorProducto->sum('entradas.valor'),
                'total_salidas' => $reportePorProducto->sum('salidas.valor'),
                'valor_inventario_final' => $reportePorProducto->sum('saldo_final.valor'),
            ],
        ]);
    }

    /**
     * Exportar kardex a Excel.
     */
    public function exportar(Request $request): JsonResponse
    {
        // Implementación básica - retornar datos formateados
        // La exportación real se haría con Maatwebsite/Excel

        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
            'producto_id' => 'nullable|exists:productos,id',
            'almacen_id' => 'nullable|exists:almacenes,id',
            'formato' => 'in:excel,pdf',
        ]);

        // Por ahora, retornar mensaje de éxito
        // La implementación real generaría el archivo

        return $this->success([
            'mensaje' => 'Exportación en desarrollo',
            'parametros' => $request->only(['fecha_inicio', 'fecha_fin', 'producto_id', 'almacen_id', 'formato']),
        ]);
    }
}
