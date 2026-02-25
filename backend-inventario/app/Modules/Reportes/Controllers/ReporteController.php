<?php

namespace App\Modules\Reportes\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Inventario\Models\Kardex;
use App\Modules\Inventario\Models\Movimiento;
use App\Modules\Requisiciones\Models\Requisicion;
use App\Modules\Requisiciones\Models\ValeSalida;
use App\Modules\Compras\Models\OrdenCompra;
use App\Modules\Administracion\Models\Almacen;
use App\Modules\Administracion\Models\CentroCosto;
use App\Shared\Traits\ApiResponse;
use App\Shared\Traits\FiltrosPorRol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Modules\Reportes\Exports\KardexExport;
use App\Modules\Reportes\Exports\InventarioExport;
use App\Modules\Reportes\Exports\MovimientosExport;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteController extends Controller
{
    use ApiResponse, FiltrosPorRol;

    /**
     * Reporte de Kardex Valorizado
     */
    public function kardex(Request $request): JsonResponse
    {
        $request->validate([
            'producto_id' => 'nullable|exists:productos,id',
            'almacen_id' => 'nullable|exists:almacenes,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $empresaId = $request->user()->empresa_id;
        $incluirAnulados = $request->boolean('incluir_anulados', false);

        $query = Kardex::with(['producto:id,codigo,nombre,unidad_medida', 'almacen:id,nombre', 'movimiento:id,tipo,documento_referencia'])
            ->where('empresa_id', $empresaId)
            ->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);

        // Filtrar por almacén asignado (almacenero solo ve su almacén)
        $almacenAsignado = $this->getAlmacenAsignado($request);
        if ($almacenAsignado) {
            $query->where('almacen_id', $almacenAsignado);
        } elseif ($request->filled('almacen_id')) {
            $query->where('almacen_id', $request->almacen_id);
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

        $kardex = $query->orderBy('fecha')->orderBy('id')->get();

        // Calcular totales
        $entradas = $kardex->whereIn('tipo_operacion', ['ENTRADA', 'AJUSTE_POSITIVO', 'SALDO_INICIAL']);
        $salidas = $kardex->whereIn('tipo_operacion', ['SALIDA', 'AJUSTE_NEGATIVO']);

        $totales = [
            'total_entradas' => $entradas->sum('cantidad'),
            'total_salidas' => $salidas->sum('cantidad'),
            'valor_entradas' => $entradas->sum('costo_total'),
            'valor_salidas' => $salidas->sum('costo_total'),
            'saldo_final_cantidad' => $kardex->last()?->saldo_cantidad ?? 0,
            'saldo_final_valor' => $kardex->last()?->saldo_costo_total ?? 0,
        ];

        return $this->success([
            'registros' => $kardex,
            'totales' => $totales,
        ]);
    }

    /**
     * Reporte de Inventario Valorizado
     */
    public function inventario(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $query = Producto::with(['familia:id,nombre', 'stockAlmacenes.almacen:id,nombre'])
            ->where('empresa_id', $empresaId)
            ->where('activo', true);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                  ->orWhere('nombre', 'like', "%{$search}%");
            });
        }

        if ($request->filled('familia_id')) {
            $query->where('familia_id', $request->familia_id);
        }

        // Filtrar por almacén asignado (almacenero solo ve su almacén)
        $almacenAsignado = $this->getAlmacenAsignado($request);
        $almacenFiltro = $almacenAsignado ?: ($request->filled('almacen_id') ? $request->almacen_id : null);

        $prestadosPorProducto = DB::table('prestamos_equipos as pe')
            ->join('equipos_prestables as ep', 'pe.equipo_id', '=', 'ep.id')
            ->where('pe.empresa_id', $empresaId)
            ->whereNotNull('ep.producto_id')
            ->whereIn('pe.estado', ['ACTIVO', 'VENCIDO', 'RENOVADO'])
            ->when($almacenFiltro, fn($q) => $q->where('ep.almacen_id', $almacenFiltro))
            ->groupBy('ep.producto_id')
            ->selectRaw('ep.producto_id, SUM(pe.cantidad) as total_prestado')
            ->pluck('total_prestado', 'ep.producto_id');

        $soloConStock = $request->boolean('solo_stock');

        if ($almacenFiltro) {
            $query->whereHas('stockAlmacenes', function ($q) use ($almacenFiltro) {
                $q->where('almacen_id', $almacenFiltro);
            });
            if ($soloConStock) {
                $query->whereHas('stockAlmacenes', function ($q) use ($almacenFiltro) {
                    $q->where('almacen_id', $almacenFiltro)
                      ->where('stock_actual', '>', 0);
                });
            }
        } elseif ($soloConStock) {
            $query->whereHas('stockAlmacenes', function ($q) {
                $q->where('stock_actual', '>', 0);
            });
        }

        $productos = $query->get()->map(function ($producto) use ($almacenFiltro, $prestadosPorProducto) {
            $stocks = $producto->stockAlmacenes;
            if ($almacenFiltro) {
                $stocks = $stocks->where('almacen_id', $almacenFiltro);
            }

            $stockFisico = (float) $stocks->sum('stock_actual');
            $stockPrestado = (float) ($prestadosPorProducto[$producto->id] ?? 0);
            $stockTotalActivo = $stockFisico + $stockPrestado;
            $costoPromedio = $stocks->avg('costo_promedio') ?? 0;
            $valorTotal = $stockFisico * $costoPromedio;

            $estadoStock = 'NORMAL';
            if ($stockFisico <= 0 && $stockPrestado > 0) {
                $estadoStock = 'CON_PRESTAMO';
            } elseif ($stockFisico <= 0) {
                $estadoStock = 'SIN_STOCK';
            } elseif ($stockFisico <= $producto->stock_minimo) {
                $estadoStock = 'BAJO';
            }

            return [
                'id' => $producto->id,
                'codigo' => $producto->codigo,
                'nombre' => $producto->nombre,
                'familia' => $producto->familia?->nombre,
                'unidad_medida' => $producto->unidad_medida,
                'stock_total' => $stockFisico,
                'stock_fisico' => $stockFisico,
                'stock_prestado' => $stockPrestado,
                'stock_total_activo' => $stockTotalActivo,
                'stock_minimo' => $producto->stock_minimo,
                'costo_promedio' => $costoPromedio,
                'valor_total' => $valorTotal,
                'estado_stock' => $estadoStock,
                'stocks_por_almacen' => $stocks->map(fn($s) => [
                    'almacen' => $s->almacen?->nombre,
                    'stock' => $s->stock_actual,
                ]),
            ];
        });

        // Totales
        $totales = [
            'total_productos' => $productos->count(),
            'total_items' => $productos->sum('stock_fisico'),
            'total_prestado' => $productos->sum('stock_prestado'),
            'total_activo' => $productos->sum('stock_total_activo'),
            'valor_total_inventario' => $productos->sum('valor_total'),
            'productos_sin_stock' => $productos->where('estado_stock', 'SIN_STOCK')->count(),
            'productos_stock_bajo' => $productos->where('estado_stock', 'BAJO')->count(),
        ];

        return $this->success([
            'productos' => $productos,
            'totales' => $totales,
        ]);
    }

    /**
     * Reporte de Consumo por Centro de Costo
     */
    public function consumoCentroCosto(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $empresaId = $request->user()->empresa_id;

        // Obtener vales de salida entregados en el período
        $consumo = DB::table('vales_salida as v')
            ->join('vales_salida_detalle as vd', 'v.id', '=', 'vd.vale_salida_id')
            ->join('productos as p', 'vd.producto_id', '=', 'p.id')
            ->join('centros_costos as cc', 'v.centro_costo_id', '=', 'cc.id')
            ->where('v.empresa_id', $empresaId)
            ->where('v.estado', 'ENTREGADO')
            ->whereBetween('v.fecha', [$request->fecha_inicio, $request->fecha_fin])
            ->select(
                'cc.id as centro_costo_id',
                'cc.codigo as centro_costo_codigo',
                'cc.nombre as centro_costo_nombre',
                DB::raw('COUNT(DISTINCT v.id) as total_vales'),
                DB::raw('SUM(vd.cantidad_entregada) as total_items'),
                DB::raw('SUM(vd.cantidad_entregada * vd.costo_unitario) as valor_total')
            )
            ->groupBy('cc.id', 'cc.codigo', 'cc.nombre')
            ->orderBy('valor_total', 'desc')
            ->get();

        // Detalle por centro de costo si se especifica uno
        $detalle = null;
        if ($request->filled('centro_costo_id')) {
            $detalle = DB::table('vales_salida as v')
                ->join('vales_salida_detalle as vd', 'v.id', '=', 'vd.vale_salida_id')
                ->join('productos as p', 'vd.producto_id', '=', 'p.id')
                ->where('v.empresa_id', $empresaId)
                ->where('v.centro_costo_id', $request->centro_costo_id)
                ->where('v.estado', 'ENTREGADO')
                ->whereBetween('v.fecha', [$request->fecha_inicio, $request->fecha_fin])
                ->select(
                    'p.codigo',
                    'p.nombre',
                    'p.unidad_medida',
                    DB::raw('SUM(vd.cantidad_entregada) as cantidad'),
                    DB::raw('AVG(vd.costo_unitario) as costo_promedio'),
                    DB::raw('SUM(vd.cantidad_entregada * vd.costo_unitario) as valor_total')
                )
                ->groupBy('p.id', 'p.codigo', 'p.nombre', 'p.unidad_medida')
                ->orderBy('valor_total', 'desc')
                ->get();
        }

        return $this->success([
            'resumen' => $consumo,
            'detalle' => $detalle,
            'totales' => [
                'total_centros' => $consumo->count(),
                'total_vales' => $consumo->sum('total_vales'),
                'valor_total' => $consumo->sum('valor_total'),
            ],
        ]);
    }

    /**
     * Reporte de Stock Bajo/Crítico
     */
    public function stockBajo(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;
        $almacenAsignado = $this->getAlmacenAsignado($request);

        $productos = Producto::with(['familia:id,nombre', 'stockAlmacenes.almacen:id,nombre'])
            ->where('empresa_id', $empresaId)
            ->where('activo', true)
            ->get()
            ->filter(function ($producto) use ($almacenAsignado) {
                $stocks = $almacenAsignado
                    ? $producto->stockAlmacenes->where('almacen_id', $almacenAsignado)
                    : $producto->stockAlmacenes;
                $stockTotal = $stocks->sum('stock_actual');
                return $stockTotal <= $producto->stock_minimo;
            })
            ->map(function ($producto) use ($almacenAsignado) {
                $stocks = $almacenAsignado
                    ? $producto->stockAlmacenes->where('almacen_id', $almacenAsignado)
                    : $producto->stockAlmacenes;
                $stockTotal = $stocks->sum('stock_actual');
                return [
                    'id' => $producto->id,
                    'codigo' => $producto->codigo,
                    'nombre' => $producto->nombre,
                    'familia' => $producto->familia?->nombre,
                    'unidad_medida' => $producto->unidad_medida,
                    'stock_actual' => $stockTotal,
                    'stock_minimo' => $producto->stock_minimo,
                    'diferencia' => $producto->stock_minimo - $stockTotal,
                    'estado' => $stockTotal <= 0 ? 'CRÍTICO' : 'BAJO',
                    'costo_reposicion' => ($producto->stock_minimo - $stockTotal) * ($stocks->avg('costo_promedio') ?? 0),
                ];
            })
            ->sortByDesc('diferencia')
            ->values();

        return $this->success([
            'productos' => $productos,
            'totales' => [
                'total_productos' => $productos->count(),
                'criticos' => $productos->where('estado', 'CRÍTICO')->count(),
                'bajos' => $productos->where('estado', 'BAJO')->count(),
                'costo_reposicion_total' => $productos->sum('costo_reposicion'),
            ],
        ]);
    }

    /**
     * Reporte de Movimientos del Período
     */
    public function movimientos(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $empresaId = $request->user()->empresa_id;

        $query = Movimiento::with([
            'almacenOrigen:id,nombre',
            'almacenDestino:id,nombre',
            'usuario:id,nombre',
            'detalles.producto:id,codigo,nombre'
        ])
            ->where('empresa_id', $empresaId)
            ->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('almacen_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('almacen_origen_id', $request->almacen_id)
                  ->orWhere('almacen_destino_id', $request->almacen_id);
            });
        }

        $movimientos = $query->orderBy('fecha', 'desc')->get();

        // Resumen por tipo
        $resumenPorTipo = $movimientos->groupBy('tipo')->map(function ($grupo, $tipo) {
            return [
                'tipo' => $tipo,
                'cantidad' => $grupo->count(),
                'total_items' => $grupo->sum(fn($m) => $m->detalles->sum('cantidad')),
            ];
        })->values();

        return $this->success([
            'movimientos' => $movimientos,
            'resumen_por_tipo' => $resumenPorTipo,
            'totales' => [
                'total_movimientos' => $movimientos->count(),
                'entradas' => $movimientos->where('tipo', Movimiento::TIPO_ENTRADA)->count(),
                'salidas' => $movimientos->where('tipo', Movimiento::TIPO_SALIDA)->count(),
            ],
        ]);
    }

    /**
     * Reporte de Requisiciones
     */
    public function requisiciones(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $empresaId = $request->user()->empresa_id;

        $query = Requisicion::with([
            'solicitante:id,nombre',
            'centroCosto:id,codigo,nombre',
            'detalles.producto:id,codigo,nombre'
        ])
            ->where('empresa_id', $empresaId)
            ->whereBetween('fecha_solicitud', [$request->fecha_inicio, $request->fecha_fin]);

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('centro_costo_id')) {
            $query->where('centro_costo_id', $request->centro_costo_id);
        }

        $requisiciones = $query->orderBy('fecha_solicitud', 'desc')->get();

        // Resumen por estado
        $resumenPorEstado = $requisiciones->groupBy('estado')->map(function ($grupo, $estado) {
            return [
                'estado' => $estado,
                'cantidad' => $grupo->count(),
            ];
        })->values();

        return $this->success([
            'requisiciones' => $requisiciones,
            'resumen_por_estado' => $resumenPorEstado,
            'totales' => [
                'total' => $requisiciones->count(),
                'aprobadas' => $requisiciones->where('estado', 'APROBADA')->count(),
                'pendientes' => $requisiciones->whereIn('estado', ['BORRADOR', 'PENDIENTE'])->count(),
                'rechazadas' => $requisiciones->where('estado', 'RECHAZADA')->count(),
            ],
        ]);
    }

    /**
     * Reporte de Top Productos Consumidos
     */
    public function topProductos(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $empresaId = $request->user()->empresa_id;
        $limite = $request->get('limite', 10);

        $topProductos = DB::table('vales_salida as v')
            ->join('vales_salida_detalle as vd', 'v.id', '=', 'vd.vale_salida_id')
            ->join('productos as p', 'vd.producto_id', '=', 'p.id')
            ->leftJoin('familias as f', 'p.familia_id', '=', 'f.id')
            ->where('v.empresa_id', $empresaId)
            ->where('v.estado', 'ENTREGADO')
            ->whereBetween('v.fecha', [$request->fecha_inicio, $request->fecha_fin])
            ->select(
                'p.id',
                'p.codigo',
                'p.nombre',
                'p.unidad_medida',
                'f.nombre as familia',
                DB::raw('SUM(vd.cantidad_entregada) as cantidad_total'),
                DB::raw('SUM(vd.cantidad_entregada * vd.costo_unitario) as valor_total'),
                DB::raw('COUNT(DISTINCT v.id) as veces_solicitado')
            )
            ->groupBy('p.id', 'p.codigo', 'p.nombre', 'p.unidad_medida', 'f.nombre')
            ->orderBy('cantidad_total', 'desc')
            ->limit($limite)
            ->get();

        return $this->success($topProductos);
    }

    /**
     * Exportar Kardex a Excel
     */
    public function exportarKardexExcel(Request $request)
    {
        $request->validate([
            'producto_id' => 'nullable|exists:productos,id',
            'almacen_id' => 'nullable|exists:almacenes,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
        ]);

        $empresaId = $request->user()->empresa_id;
        $nombreArchivo = 'kardex_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(
            new KardexExport($empresaId, $request->all()),
            $nombreArchivo
        );
    }

    /**
     * Exportar Inventario a Excel
     */
    public function exportarInventarioExcel(Request $request)
    {
        $empresaId = $request->user()->empresa_id;
        $nombreArchivo = 'inventario_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(
            new InventarioExport($empresaId, $request->all()),
            $nombreArchivo
        );
    }

    /**
     * Exportar Movimientos a Excel
     */
    public function exportarMovimientosExcel(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
        ]);

        $empresaId = $request->user()->empresa_id;
        $nombreArchivo = 'movimientos_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(
            new MovimientosExport($empresaId, $request->all()),
            $nombreArchivo
        );
    }

    /**
     * Exportar Kardex a PDF
     */
    public function exportarKardexPdf(Request $request)
    {
        $request->validate([
            'producto_id' => 'nullable|exists:productos,id',
            'almacen_id' => 'nullable|exists:almacenes,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
        ]);

        $empresaId = $request->user()->empresa_id;
        $empresa = $request->user()->empresa?->razon_social ?? 'Sistema Inventario';
        $incluirAnulados = $request->boolean('incluir_anulados', false);

        $query = Kardex::with(['producto:id,codigo,nombre,unidad_medida', 'almacen:id,nombre'])
            ->where('empresa_id', $empresaId)
            ->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);

        if ($request->filled('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }

        if ($request->filled('almacen_id')) {
            $query->where('almacen_id', $request->almacen_id);
        }

        if (!$incluirAnulados) {
            $query->where(function ($q) {
                $q->whereNull('movimiento_id')
                  ->orWhereHas('movimiento', function ($movQ) {
                      $movQ->where('estado', '!=', Movimiento::ESTADO_ANULADO);
                  });
            });
        }

        $kardex = $query->orderBy('fecha')->orderBy('id')->get();

        // Preparar datos para la vista
        $registros = $kardex->map(fn($k) => [
            'fecha' => $k->fecha,
            'tipo_movimiento' => in_array($k->tipo_operacion, ['ENTRADA', 'AJUSTE_POSITIVO', 'SALDO_INICIAL']) ? 'ENTRADA' : 'SALIDA',
            'referencia' => $k->documento_referencia,
            'producto_nombre' => $k->producto?->nombre,
            'cantidad' => $k->cantidad,
            'saldo' => $k->saldo_cantidad,
            'costo_unitario' => $k->costo_unitario,
            'valor_total' => $k->costo_total,
        ])->toArray();

        // Totales
        $entradas = $kardex->whereIn('tipo_operacion', ['ENTRADA', 'AJUSTE_POSITIVO', 'SALDO_INICIAL']);
        $salidas = $kardex->whereIn('tipo_operacion', ['SALIDA', 'AJUSTE_NEGATIVO']);

        $totales = [
            'entradas' => $entradas->sum('cantidad'),
            'salidas' => $salidas->sum('cantidad'),
            'valor_total' => $kardex->last()?->saldo_costo_total ?? 0,
        ];

        // Filtros para mostrar
        $almacenNombre = $request->filled('almacen_id') ? Almacen::find($request->almacen_id)?->nombre : 'Todos';
        $productoNombre = $request->filled('producto_id') ? Producto::find($request->producto_id)?->nombre : 'Todos';

        $pdf = Pdf::loadView('pdf.kardex', [
            'empresa' => $empresa,
            'registros' => $registros,
            'totales' => $totales,
            'almacen' => $almacenNombre,
            'producto' => $productoNombre,
            'fecha_inicio' => \Carbon\Carbon::parse($request->fecha_inicio)->format('d/m/Y'),
            'fecha_fin' => \Carbon\Carbon::parse($request->fecha_fin)->format('d/m/Y'),
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('kardex_' . date('Y-m-d_His') . '.pdf');
    }

    /**
     * Exportar Inventario a PDF
     */
    public function exportarInventarioPdf(Request $request)
    {
        $empresaId = $request->user()->empresa_id;
        $empresa = $request->user()->empresa?->razon_social ?? 'Sistema Inventario';

        $query = Producto::with(['familia:id,nombre', 'stockAlmacenes.almacen:id,nombre'])
            ->where('empresa_id', $empresaId)
            ->where('activo', true);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                  ->orWhere('nombre', 'like', "%{$search}%");
            });
        }

        if ($request->filled('familia_id')) {
            $query->where('familia_id', $request->familia_id);
        }

        if ($request->filled('almacen_id')) {
            $query->whereHas('stockAlmacenes', function ($q) use ($request) {
                $q->where('almacen_id', $request->almacen_id)
                  ->where('stock_actual', '>', 0);
            });
        }

        $productosData = $query->get()->map(function ($producto) use ($request) {
            $stocks = $producto->stockAlmacenes;
            if ($request->filled('almacen_id')) {
                $stocks = $stocks->where('almacen_id', $request->almacen_id);
            }

            $stockTotal = $stocks->sum('stock_actual');
            $costoPromedio = $stocks->avg('costo_promedio') ?? 0;
            $valorTotal = $stockTotal * $costoPromedio;

            return [
                'codigo' => $producto->codigo,
                'nombre' => $producto->nombre,
                'familia' => $producto->familia?->nombre,
                'almacen' => $stocks->first()?->almacen?->nombre ?? '-',
                'unidad_medida' => $producto->unidad_medida,
                'stock' => $stockTotal,
                'stock_minimo' => $producto->stock_minimo,
                'costo_unitario' => $costoPromedio,
                'valor_total' => $valorTotal,
            ];
        })->toArray();

        // Totales
        $totales = [
            'total_productos' => count($productosData),
            'total_unidades' => array_sum(array_column($productosData, 'stock')),
            'bajo_stock' => count(array_filter($productosData, fn($p) => $p['stock'] <= $p['stock_minimo'] && $p['stock'] > 0)),
            'valor_total' => array_sum(array_column($productosData, 'valor_total')),
        ];

        // Filtros
        $almacenNombre = $request->filled('almacen_id') ? Almacen::find($request->almacen_id)?->nombre : 'Todos';
        $familiaNombre = $request->filled('familia_id') ? \App\Modules\Inventario\Models\Familia::find($request->familia_id)?->nombre : 'Todas';

        $pdf = Pdf::loadView('pdf.inventario', [
            'empresa' => $empresa,
            'productos' => $productosData,
            'totales' => $totales,
            'almacen' => $almacenNombre,
            'familia' => $familiaNombre,
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('inventario_' . date('Y-m-d_His') . '.pdf');
    }

    /**
     * Exportar Movimientos a PDF
     */
    public function exportarMovimientosPdf(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
        ]);

        $empresaId = $request->user()->empresa_id;
        $empresa = $request->user()->empresa?->razon_social ?? 'Sistema Inventario';

        $query = Movimiento::with([
            'almacenOrigen:id,nombre',
            'almacenDestino:id,nombre',
            'usuario:id,nombre',
            'detalles'
        ])
            ->where('empresa_id', $empresaId)
            ->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('almacen_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('almacen_origen_id', $request->almacen_id)
                  ->orWhere('almacen_destino_id', $request->almacen_id);
            });
        }

        $movimientosData = $query->orderBy('fecha', 'desc')->get()->map(fn($m) => [
            'fecha' => $m->fecha,
            'numero' => $m->numero,
            'tipo' => $m->tipo,
            'almacen' => $m->almacenOrigen?->nombre ?? $m->almacenDestino?->nombre,
            'referencia' => $m->documento_referencia,
            'usuario' => $m->usuario?->nombre,
            'total_items' => $m->detalles->count(),
            'valor_total' => $m->detalles->sum(fn($d) => $d->cantidad * $d->costo_unitario),
            'estado' => $m->estado,
        ])->toArray();

        // Totales
        $entradas = count(array_filter($movimientosData, fn($m) => $m['tipo'] === 'ENTRADA'));
        $salidas = count(array_filter($movimientosData, fn($m) => $m['tipo'] === 'SALIDA'));

        $totales = [
            'total_movimientos' => count($movimientosData),
            'entradas' => $entradas,
            'salidas' => $salidas,
            'valor_entradas' => array_sum(array_map(fn($m) => $m['tipo'] === 'ENTRADA' ? $m['valor_total'] : 0, $movimientosData)),
            'valor_salidas' => array_sum(array_map(fn($m) => $m['tipo'] === 'SALIDA' ? $m['valor_total'] : 0, $movimientosData)),
        ];

        // Filtros
        $almacenNombre = $request->filled('almacen_id') ? Almacen::find($request->almacen_id)?->nombre : 'Todos';
        $tipoNombre = $request->filled('tipo') ? $request->tipo : 'Todos';

        $pdf = Pdf::loadView('pdf.movimientos', [
            'empresa' => $empresa,
            'movimientos' => $movimientosData,
            'totales' => $totales,
            'almacen' => $almacenNombre,
            'tipo' => $tipoNombre,
            'estado' => 'Todos',
            'fecha_inicio' => \Carbon\Carbon::parse($request->fecha_inicio)->format('d/m/Y'),
            'fecha_fin' => \Carbon\Carbon::parse($request->fecha_fin)->format('d/m/Y'),
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('movimientos_' . date('Y-m-d_His') . '.pdf');
    }

    /**
     * Datos para gráficos del Dashboard
     */
    public function dashboardGraficos(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;
        $almacenAsignado = $this->getAlmacenAsignado($request);
        $centroCostoAlmacen = $almacenAsignado
            ? Almacen::where('empresa_id', $empresaId)->where('id', $almacenAsignado)->value('centro_costo_id')
            : null;

        // 1. Consumo mensual (últimos 6 meses)
        $consumoMensual = collect();
        for ($i = 5; $i >= 0; $i--) {
            $mes = now()->subMonths($i);
            $inicioMes = $mes->copy()->startOfMonth();
            $finMes = $mes->copy()->endOfMonth();

            $consumo = DB::table('vales_salida as v')
                ->join('vales_salida_detalle as vd', 'v.id', '=', 'vd.vale_salida_id')
                ->where('v.empresa_id', $empresaId)
                ->where('v.estado', 'ENTREGADO')
                ->whereBetween('v.fecha', [$inicioMes, $finMes])
                ->selectRaw('COALESCE(SUM(vd.cantidad_entregada * vd.costo_unitario), 0) as total')
                ->when($almacenAsignado, fn($q) => $q->where('v.almacen_id', $almacenAsignado))
                ->value('total') ?? 0;

            $consumoMensual->push([
                'mes' => $mes->translatedFormat('M Y'),
                'mes_corto' => $mes->translatedFormat('M'),
                'valor' => round($consumo, 2),
            ]);
        }

        // 2. Movimientos por tipo (mes actual)
        $mesActual = now()->startOfMonth();
        $movimientosPorTipo = Movimiento::where('empresa_id', $empresaId)
            ->where('fecha', '>=', $mesActual)
            ->when($almacenAsignado, function ($q) use ($almacenAsignado) {
                $q->where(function ($subQ) use ($almacenAsignado) {
                    $subQ->where('almacen_origen_id', $almacenAsignado)
                        ->orWhere('almacen_destino_id', $almacenAsignado);
                });
            })
            ->selectRaw('tipo, COUNT(*) as cantidad')
            ->groupBy('tipo')
            ->get()
            ->mapWithKeys(fn($item) => [$item->tipo => $item->cantidad]);

        // 3. Top 10 productos más consumidos (mes actual)
        $topProductos = DB::table('vales_salida as v')
            ->join('vales_salida_detalle as vd', 'v.id', '=', 'vd.vale_salida_id')
            ->join('productos as p', 'vd.producto_id', '=', 'p.id')
            ->where('v.empresa_id', $empresaId)
            ->where('v.estado', 'ENTREGADO')
            ->where('v.fecha', '>=', $mesActual)
            ->when($almacenAsignado, fn($q) => $q->where('v.almacen_id', $almacenAsignado))
            ->select(
                'p.nombre',
                DB::raw('SUM(vd.cantidad_entregada) as cantidad'),
                DB::raw('SUM(vd.cantidad_entregada * vd.costo_unitario) as valor')
            )
            ->groupBy('p.id', 'p.nombre')
            ->orderBy('cantidad', 'desc')
            ->limit(10)
            ->get();

        // 4. Consumo por centro de costo (mes actual)
        $consumoPorCentroCosto = DB::table('vales_salida as v')
            ->join('vales_salida_detalle as vd', 'v.id', '=', 'vd.vale_salida_id')
            ->join('centros_costos as cc', 'v.centro_costo_id', '=', 'cc.id')
            ->where('v.empresa_id', $empresaId)
            ->where('v.estado', 'ENTREGADO')
            ->where('v.fecha', '>=', $mesActual)
            ->when($almacenAsignado, fn($q) => $q->where('v.almacen_id', $almacenAsignado))
            ->select(
                'cc.nombre',
                DB::raw('SUM(vd.cantidad_entregada * vd.costo_unitario) as valor')
            )
            ->groupBy('cc.id', 'cc.nombre')
            ->orderBy('valor', 'desc')
            ->limit(8)
            ->get();

        // 5. Requisiciones por estado (mes actual)
        $requisicionesPorEstado = Requisicion::where('empresa_id', $empresaId)
            ->where('created_at', '>=', $mesActual)
            ->when($centroCostoAlmacen, fn($q) => $q->where('centro_costo_id', $centroCostoAlmacen))
            ->selectRaw('estado, COUNT(*) as cantidad')
            ->groupBy('estado')
            ->get()
            ->mapWithKeys(fn($item) => [$item->estado => $item->cantidad]);

        // 6. Stock por familia (top 8 por valor)
        $stockPorFamilia = DB::table('productos as p')
            ->join('familias as f', 'p.familia_id', '=', 'f.id')
            ->join('stock_almacen as sa', 'p.id', '=', 'sa.producto_id')
            ->where('p.empresa_id', $empresaId)
            ->where('p.activo', true)
            ->when($almacenAsignado, fn($q) => $q->where('sa.almacen_id', $almacenAsignado))
            ->select(
                'f.nombre',
                DB::raw('SUM(sa.stock_actual * sa.costo_promedio) as valor')
            )
            ->groupBy('f.id', 'f.nombre')
            ->orderBy('valor', 'desc')
            ->limit(8)
            ->get();

        return $this->success([
            'consumo_mensual' => $consumoMensual,
            'movimientos_por_tipo' => $movimientosPorTipo,
            'top_productos' => $topProductos,
            'consumo_por_centro_costo' => $consumoPorCentroCosto,
            'requisiciones_por_estado' => $requisicionesPorEstado,
            'stock_por_familia' => $stockPorFamilia,
        ]);
    }

    /**
     * Dashboard de Reportes - Resumen General
     */
    public function dashboard(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;
        $almacenAsignado = $this->getAlmacenAsignado($request);
        $centroCostoAlmacen = $almacenAsignado
            ? Almacen::where('empresa_id', $empresaId)->where('id', $almacenAsignado)->value('centro_costo_id')
            : null;
        $mesActual = now()->startOfMonth();
        $mesAnterior = now()->subMonth()->startOfMonth();

        $stats = [
            // Inventario - stock_actual y costo_promedio están en la tabla stock_almacen
            'valor_inventario' => DB::table('stock_almacen')
                ->where('empresa_id', $empresaId)
                ->when($almacenAsignado, fn($q) => $q->where('almacen_id', $almacenAsignado))
                ->selectRaw('COALESCE(SUM(stock_actual * costo_promedio), 0) as total')
                ->value('total') ?? 0,

            'total_productos' => Producto::where('empresa_id', $empresaId)
                ->where('activo', true)
                ->when($almacenAsignado, function ($q) use ($almacenAsignado) {
                    $q->whereHas('stockAlmacenes', function ($sq) use ($almacenAsignado) {
                        $sq->where('almacen_id', $almacenAsignado);
                    });
                })
                ->count(),

            'productos_stock_bajo' => DB::table('productos as p')
                ->leftJoin('stock_almacen as sa', 'p.id', '=', 'sa.producto_id')
                ->where('p.empresa_id', $empresaId)
                ->where('p.activo', true)
                ->when($almacenAsignado, fn($q) => $q->where('sa.almacen_id', $almacenAsignado))
                ->groupBy('p.id', 'p.stock_minimo')
                ->havingRaw('COALESCE(SUM(sa.stock_actual), 0) <= p.stock_minimo')
                ->select('p.id')
                ->get()
                ->count(),

            // Movimientos del mes
            'movimientos_mes' => Movimiento::where('empresa_id', $empresaId)
                ->where('fecha', '>=', $mesActual)
                ->when($almacenAsignado, function ($q) use ($almacenAsignado) {
                    $q->where(function ($subQ) use ($almacenAsignado) {
                        $subQ->where('almacen_origen_id', $almacenAsignado)
                            ->orWhere('almacen_destino_id', $almacenAsignado);
                    });
                })
                ->count(),

            // Requisiciones
            'requisiciones_pendientes' => Requisicion::where('empresa_id', $empresaId)
                ->whereIn('estado', ['BORRADOR', 'PENDIENTE'])
                ->when($centroCostoAlmacen, fn($q) => $q->where('centro_costo_id', $centroCostoAlmacen))
                ->count(),

            // Órdenes de compra
            'ordenes_por_recibir' => OrdenCompra::where('empresa_id', $empresaId)
                ->whereIn('estado', ['ENVIADA', 'PARCIAL'])
                ->when($almacenAsignado, fn($q) => $q->where('almacen_destino_id', $almacenAsignado))
                ->count(),

            // Consumo mensual - usar selectRaw para evitar errores si no hay datos
            'consumo_mes_actual' => DB::table('vales_salida as v')
                ->join('vales_salida_detalle as vd', 'v.id', '=', 'vd.vale_salida_id')
                ->where('v.empresa_id', $empresaId)
                ->where('v.estado', 'ENTREGADO')
                ->where('v.fecha', '>=', $mesActual)
                ->when($almacenAsignado, fn($q) => $q->where('v.almacen_id', $almacenAsignado))
                ->selectRaw('COALESCE(SUM(vd.cantidad_entregada * vd.costo_unitario), 0) as total')
                ->value('total') ?? 0,

            'consumo_mes_anterior' => DB::table('vales_salida as v')
                ->join('vales_salida_detalle as vd', 'v.id', '=', 'vd.vale_salida_id')
                ->where('v.empresa_id', $empresaId)
                ->where('v.estado', 'ENTREGADO')
                ->whereBetween('v.fecha', [$mesAnterior, $mesActual])
                ->when($almacenAsignado, fn($q) => $q->where('v.almacen_id', $almacenAsignado))
                ->selectRaw('COALESCE(SUM(vd.cantidad_entregada * vd.costo_unitario), 0) as total')
                ->value('total') ?? 0,
        ];

        // Calcular variación de consumo
        if ($stats['consumo_mes_anterior'] > 0) {
            $stats['variacion_consumo'] = round(
                (($stats['consumo_mes_actual'] - $stats['consumo_mes_anterior']) / $stats['consumo_mes_anterior']) * 100,
                2
            );
        } else {
            $stats['variacion_consumo'] = 0;
        }

        return $this->success($stats);
    }
}
