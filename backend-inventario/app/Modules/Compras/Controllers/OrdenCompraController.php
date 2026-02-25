<?php

namespace App\Modules\Compras\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compras\Models\OrdenCompra;
use App\Modules\Compras\Models\OrdenCompraDetalle;
use App\Modules\Inventario\Models\Movimiento;
use App\Modules\Inventario\Models\Kardex;
use App\Modules\Inventario\Models\StockAlmacen;
use App\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrdenCompraController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = OrdenCompra::with(['proveedor:id,ruc,razon_social', 'almacenDestino:id,nombre', 'solicitante:id,nombre'])
            ->withCount('detalles');

        if ($request->user()->empresa_id) {
            $query->where('empresa_id', $request->user()->empresa_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                  ->orWhereHas('proveedor', fn($q) => $q->where('razon_social', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('proveedor_id')) {
            $query->where('proveedor_id', $request->proveedor_id);
        }

        if ($request->filled('fecha_desde')) {
            $query->where('fecha_emision', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha_emision', '<=', $request->fecha_hasta);
        }

        $sortField = $request->get('sort_field', 'fecha_emision');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        $perPage = $request->get('per_page', 15);
        $ordenes = $query->paginate($perPage);

        return $this->paginated($ordenes);
    }

    public function store(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id ?? $request->empresa_id;

        $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id',
            'almacen_destino_id' => 'required|exists:almacenes,id',
            'fecha_emision' => 'required|date',
            'fecha_entrega_esperada' => 'nullable|date|after_or_equal:fecha_emision',
            'condiciones_pago' => 'nullable|string|max:500',
            'direccion_entrega' => 'nullable|string|max:500',
            'observaciones' => 'nullable|string',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.cantidad_solicitada' => 'required|numeric|min:0.01',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
            'detalles.*.descuento' => 'nullable|numeric|min:0|max:100',
        ], [
            'proveedor_id.required' => 'Seleccione un proveedor',
            'almacen_destino_id.required' => 'Seleccione un almacén de destino',
            'detalles.required' => 'Agregue al menos un producto',
        ]);

        DB::beginTransaction();
        try {
            $numero = $this->generarNumero($empresaId);

            $orden = OrdenCompra::create([
                'empresa_id' => $empresaId,
                'numero' => $numero,
                'proveedor_id' => $request->proveedor_id,
                'cotizacion_id' => $request->cotizacion_id,
                'almacen_destino_id' => $request->almacen_destino_id,
                'solicitado_por' => $request->user()->id,
                'fecha_emision' => $request->fecha_emision,
                'fecha_entrega_esperada' => $request->fecha_entrega_esperada,
                'estado' => OrdenCompra::ESTADO_BORRADOR,
                'moneda' => $request->moneda ?? 'PEN',
                'tipo_cambio' => $request->tipo_cambio ?? 1,
                'condiciones_pago' => $request->condiciones_pago,
                'direccion_entrega' => $request->direccion_entrega,
                'observaciones' => $request->observaciones,
            ]);

            foreach ($request->detalles as $detalle) {
                $subtotal = $detalle['cantidad_solicitada'] * $detalle['precio_unitario'];
                if (isset($detalle['descuento']) && $detalle['descuento'] > 0) {
                    $subtotal -= $subtotal * ($detalle['descuento'] / 100);
                }

                OrdenCompraDetalle::create([
                    'orden_compra_id' => $orden->id,
                    'producto_id' => $detalle['producto_id'],
                    'cotizacion_detalle_id' => $detalle['cotizacion_detalle_id'] ?? null,
                    'cantidad_solicitada' => $detalle['cantidad_solicitada'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'descuento' => $detalle['descuento'] ?? 0,
                    'subtotal' => $subtotal,
                ]);
            }

            $orden->calcularTotales();

            DB::commit();

            return $this->created(
                $orden->load(['proveedor', 'almacenDestino', 'detalles.producto']),
                'Orden de compra creada exitosamente'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear orden de compra', [
                'empresa_id' => $empresaId,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Error interno al crear la orden de compra', 500);
        }
    }

    public function show(OrdenCompra $ordenCompra): JsonResponse
    {
        $ordenCompra->load([
            'proveedor',
            'cotizacion',
            'almacenDestino',
            'solicitante:id,nombre',
            'aprobador:id,nombre',
            'receptor:id,nombre',
            'detalles.producto:id,codigo,nombre,unidad_medida',
        ]);

        return $this->success($ordenCompra);
    }

    public function update(Request $request, OrdenCompra $ordenCompra): JsonResponse
    {
        if (!$ordenCompra->puedeEditarse()) {
            return $this->error('Esta orden no puede ser editada en su estado actual', 422);
        }

        $request->validate([
            'proveedor_id' => 'sometimes|exists:proveedores,id',
            'almacen_destino_id' => 'sometimes|exists:almacenes,id',
            'fecha_emision' => 'sometimes|date',
            'fecha_entrega_esperada' => 'nullable|date',
            'detalles' => 'sometimes|array|min:1',
        ]);

        DB::beginTransaction();
        try {
            $ordenCompra->update($request->only([
                'proveedor_id', 'almacen_destino_id', 'fecha_emision',
                'fecha_entrega_esperada', 'condiciones_pago', 'direccion_entrega', 'observaciones'
            ]));

            if ($request->has('detalles')) {
                $ordenCompra->detalles()->delete();

                foreach ($request->detalles as $detalle) {
                    $subtotal = $detalle['cantidad_solicitada'] * $detalle['precio_unitario'];
                    if (isset($detalle['descuento']) && $detalle['descuento'] > 0) {
                        $subtotal -= $subtotal * ($detalle['descuento'] / 100);
                    }

                    OrdenCompraDetalle::create([
                        'orden_compra_id' => $ordenCompra->id,
                        'producto_id' => $detalle['producto_id'],
                        'cantidad_solicitada' => $detalle['cantidad_solicitada'],
                        'precio_unitario' => $detalle['precio_unitario'],
                        'descuento' => $detalle['descuento'] ?? 0,
                        'subtotal' => $subtotal,
                    ]);
                }

                $ordenCompra->calcularTotales();
            }

            DB::commit();

            return $this->success(
                $ordenCompra->fresh(['proveedor', 'detalles.producto']),
                'Orden actualizada exitosamente'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar orden de compra', [
                'empresa_id' => $ordenCompra->empresa_id,
                'user_id' => $request->user()->id,
                'orden_compra_id' => $ordenCompra->id,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Error interno al actualizar la orden de compra', 500);
        }
    }

    public function destroy(OrdenCompra $ordenCompra): JsonResponse
    {
        if (!$ordenCompra->puedeEditarse()) {
            return $this->error('Esta orden no puede ser eliminada', 422);
        }

        $ordenCompra->detalles()->delete();
        $ordenCompra->delete();

        return $this->success(null, 'Orden eliminada exitosamente');
    }

    public function enviarAprobacion(OrdenCompra $ordenCompra): JsonResponse
    {
        if ($ordenCompra->estado !== OrdenCompra::ESTADO_BORRADOR) {
            return $this->error('Solo se pueden enviar órdenes en borrador', 422);
        }

        $ordenCompra->enviarAprobacion();

        return $this->success($ordenCompra, 'Orden enviada a aprobación');
    }

    public function aprobar(Request $request, OrdenCompra $ordenCompra): JsonResponse
    {
        if (!$ordenCompra->puedeAprobarse()) {
            return $this->error('Esta orden no puede ser aprobada', 422);
        }

        $ordenCompra->aprobar($request->user()->id);

        return $this->success($ordenCompra, 'Orden aprobada exitosamente');
    }

    public function enviarProveedor(OrdenCompra $ordenCompra): JsonResponse
    {
        if (!$ordenCompra->puedeEnviarse()) {
            return $this->error('Esta orden no puede ser enviada al proveedor', 422);
        }

        $ordenCompra->enviarProveedor();

        return $this->success($ordenCompra, 'Orden enviada al proveedor');
    }

    public function recibir(Request $request, OrdenCompra $ordenCompra): JsonResponse
    {
        if (!$ordenCompra->puedeRecibirse()) {
            return $this->error('Esta orden no puede recibir mercancía en su estado actual', 422);
        }

        $request->validate([
            'recepciones' => 'required|array|min:1',
            'recepciones.*.detalle_id' => 'required|exists:ordenes_compra_detalle,id',
            'recepciones.*.cantidad' => 'required|numeric|min:0.01',
            'recepciones.*.lote' => 'nullable|string|max:50',
            'recepciones.*.fecha_vencimiento' => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            $movimiento = $this->crearMovimientoEntrada($request, $ordenCompra);

            foreach ($request->recepciones as $recepcion) {
                $detalle = OrdenCompraDetalle::find($recepcion['detalle_id']);

                if ($detalle->orden_compra_id !== $ordenCompra->id) {
                    throw new \Exception('Detalle no pertenece a esta orden');
                }

                $cantidadPendiente = $detalle->cantidad_solicitada - $detalle->cantidad_recibida;
                if ($recepcion['cantidad'] > $cantidadPendiente) {
                    throw new \Exception("Cantidad excede lo pendiente para {$detalle->producto->nombre}");
                }

                $detalle->registrarRecepcion($recepcion['cantidad']);

                if (isset($recepcion['lote'])) {
                    $detalle->update([
                        'lote' => $recepcion['lote'],
                        'fecha_vencimiento' => $recepcion['fecha_vencimiento'] ?? null,
                    ]);
                }

                // Actualizar stock
                $this->actualizarStock(
                    $ordenCompra->empresa_id,
                    $ordenCompra->almacen_destino_id,
                    $detalle->producto_id,
                    $recepcion['cantidad'],
                    $detalle->precio_unitario
                );

                // Registrar en Kardex
                $this->registrarKardex(
                    $movimiento,
                    $detalle,
                    $recepcion['cantidad'],
                    $ordenCompra->almacen_destino_id
                );
            }

            // Actualizar estado de la orden
            if ($ordenCompra->estaCompletamenteRecibida()) {
                $ordenCompra->update([
                    'estado' => OrdenCompra::ESTADO_RECIBIDA,
                    'fecha_recepcion' => now(),
                    'recibido_por' => $request->user()->id,
                    'movimiento_id' => $movimiento->id,
                ]);
            } else {
                $ordenCompra->update([
                    'estado' => OrdenCompra::ESTADO_PARCIAL,
                    'movimiento_id' => $movimiento->id,
                ]);
            }

            DB::commit();

            return $this->success(
                $ordenCompra->fresh(['detalles.producto']),
                'Recepción procesada exitosamente'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al procesar recepción de orden de compra', [
                'empresa_id' => $ordenCompra->empresa_id,
                'user_id' => $request->user()->id,
                'orden_compra_id' => $ordenCompra->id,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Error interno al procesar la recepción', 500);
        }
    }

    public function anular(OrdenCompra $ordenCompra, Request $request): JsonResponse
    {
        if (!$ordenCompra->puedeAnularse()) {
            return $this->error('Esta orden no puede ser anulada', 422);
        }

        $ordenCompra->anular($request->user()->id);

        return $this->success($ordenCompra, 'Orden anulada exitosamente');
    }

    public function estadisticas(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;
        $mesActual = now()->startOfMonth();

        $stats = [
            'total' => OrdenCompra::where('empresa_id', $empresaId)->count(),
            'pendientes' => OrdenCompra::where('empresa_id', $empresaId)
                ->whereIn('estado', [OrdenCompra::ESTADO_BORRADOR, OrdenCompra::ESTADO_PENDIENTE])
                ->count(),
            'por_recibir' => OrdenCompra::where('empresa_id', $empresaId)
                ->whereIn('estado', [OrdenCompra::ESTADO_ENVIADA, OrdenCompra::ESTADO_PARCIAL])
                ->count(),
            'valor_mes' => OrdenCompra::where('empresa_id', $empresaId)
                ->where('fecha_emision', '>=', $mesActual)
                ->whereNotIn('estado', [OrdenCompra::ESTADO_ANULADA])
                ->sum('total'),
        ];

        return $this->success($stats);
    }

    // ==================== MÉTODOS PRIVADOS ====================

    private function generarNumero(int $empresaId): string
    {
        $año = date('Y');
        $prefijo = "OC-{$año}-";
        $ultimoNumero = OrdenCompra::where('empresa_id', $empresaId)
            ->where('numero', 'like', $prefijo . '%')
            ->orderBy('numero', 'desc')
            ->lockForUpdate()
            ->value('numero');

        $secuencia = 1;
        if ($ultimoNumero) {
            $secuencia = (int) substr($ultimoNumero, -6) + 1;
        }

        $numero = sprintf("%s%06d", $prefijo, $secuencia);
        while (OrdenCompra::where('empresa_id', $empresaId)->where('numero', $numero)->exists()) {
            $secuencia++;
            $numero = sprintf("%s%06d", $prefijo, $secuencia);
        }

        return $numero;
    }

    private function crearMovimientoEntrada(Request $request, OrdenCompra $ordenCompra): Movimiento
    {
        return Movimiento::create([
            'empresa_id' => $ordenCompra->empresa_id,
            'tipo' => 'ENTRADA',
            'subtipo' => 'COMPRA',
            'almacen_id' => $ordenCompra->almacen_destino_id,
            'fecha' => now(),
            'documento_tipo' => 'ORDEN_COMPRA',
            'documento_numero' => $ordenCompra->numero,
            'proveedor_id' => $ordenCompra->proveedor_id,
            'observaciones' => "Recepción de OC: {$ordenCompra->numero}",
            'usuario_id' => $request->user()->id,
            'estado' => 'COMPLETADO',
        ]);
    }

    private function actualizarStock(int $empresaId, int $almacenId, int $productoId, float $cantidad, float $costoUnitario): void
    {
        $stock = StockAlmacen::firstOrCreate(
            [
                'empresa_id' => $empresaId,
                'almacen_id' => $almacenId,
                'producto_id' => $productoId
            ],
            [
                'stock_actual' => 0,
                'costo_promedio' => 0,
                'stock_minimo' => 0,
                'stock_maximo' => 0
            ]
        );

        // Calcular nuevo costo promedio
        $cantidadAnterior = $stock->stock_actual;
        $costoAnterior = $stock->costo_promedio;
        $cantidadNueva = $cantidadAnterior + $cantidad;

        if ($cantidadNueva > 0) {
            $nuevoCostoPromedio = (($cantidadAnterior * $costoAnterior) + ($cantidad * $costoUnitario)) / $cantidadNueva;
        } else {
            $nuevoCostoPromedio = $costoUnitario;
        }

        $stock->update([
            'stock_actual' => $cantidadNueva,
            'costo_promedio' => $nuevoCostoPromedio,
        ]);
    }

    private function registrarKardex(Movimiento $movimiento, OrdenCompraDetalle $detalle, float $cantidad, int $almacenId): void
    {
        $stockAlmacen = StockAlmacen::where('empresa_id', $movimiento->empresa_id)
            ->where('almacen_id', $almacenId)
            ->where('producto_id', $detalle->producto_id)
            ->first();

        $saldoCantidad = $stockAlmacen->stock_actual ?? $cantidad;
        $saldoCostoUnitario = $stockAlmacen->costo_promedio ?? $detalle->precio_unitario;

        Kardex::create([
            'empresa_id' => $movimiento->empresa_id,
            'producto_id' => $detalle->producto_id,
            'almacen_id' => $almacenId,
            'movimiento_id' => $movimiento->id,
            'fecha' => now(),
            'tipo_operacion' => Kardex::TIPO_ENTRADA,
            'documento_referencia' => $detalle->ordenCompra->numero,
            'descripcion' => "Compra OC: {$detalle->ordenCompra->numero}",
            'cantidad' => $cantidad,
            'costo_unitario' => $detalle->precio_unitario,
            'costo_total' => $cantidad * $detalle->precio_unitario,
            'saldo_cantidad' => $saldoCantidad,
            'saldo_costo_unitario' => $saldoCostoUnitario,
            'saldo_costo_total' => $saldoCantidad * $saldoCostoUnitario,
        ]);
    }
}
