<?php

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\Models\Movimiento;
use App\Modules\Inventario\Models\MovimientoDetalle;
use App\Modules\Inventario\Models\StockAlmacen;
use App\Modules\Inventario\Models\Kardex;
use App\Shared\Traits\ApiResponse;
use App\Shared\Traits\FiltrosPorRol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class MovimientoController extends Controller
{
    use ApiResponse, FiltrosPorRol;

    /**
     * Listar movimientos.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Movimiento::with([
            'almacenOrigen:id,nombre',
            'almacenDestino:id,nombre',
            'proveedor:id,razon_social',
            'centroCosto:id,nombre',
            'usuario:id,nombre',
        ])->withCount('detalles');

        // Filtro por empresa (multi-tenancy)
        if ($request->user()->empresa_id) {
            $query->where('empresa_id', $request->user()->empresa_id);
        }

        // Filtro por almacén según rol (almacenero solo ve movimientos de su almacén)
        $almacenAsignado = $this->getAlmacenAsignado($request);
        if ($almacenAsignado) {
            $query->where(function ($q) use ($almacenAsignado) {
                $q->where('almacen_origen_id', $almacenAsignado)
                  ->orWhere('almacen_destino_id', $almacenAsignado);
            });
        }

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                  ->orWhere('documento_referencia', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('subtipo')) {
            $query->where('subtipo', $request->subtipo);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('almacen_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('almacen_origen_id', $request->almacen_id)
                  ->orWhere('almacen_destino_id', $request->almacen_id);
            });
        }

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
        }

        // Ordenamiento
        $sortField = $request->get('sort_field', 'fecha');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortField, $sortOrder)->orderBy('id', 'desc');

        // Paginación
        $perPage = $this->resolvePerPage($request, 15, 100);
        $movimientos = $query->paginate($perPage);

        return $this->paginated($movimientos);
    }

    /**
     * Crear movimiento.
     */
    public function store(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $request->validate([
            'tipo' => 'required|in:ENTRADA,SALIDA,TRANSFERENCIA,AJUSTE',
            'subtipo' => 'nullable|string|max:30',
            'almacen_origen_id' => [
                'required_if:tipo,SALIDA,TRANSFERENCIA',
                'nullable',
                Rule::exists('almacenes', 'id')->where(fn($q) => $q->where('empresa_id', $empresaId)),
            ],
            'almacen_destino_id' => [
                'required_if:tipo,ENTRADA,TRANSFERENCIA',
                'nullable',
                Rule::exists('almacenes', 'id')->where(fn($q) => $q->where('empresa_id', $empresaId)),
            ],
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'centro_costo_id' => [
                'nullable',
                Rule::exists('centros_costos', 'id')->where(fn($q) => $q->where('empresa_id', $empresaId)),
            ],
            'fecha' => 'required|date',
            'documento_referencia' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => [
                'required',
                Rule::exists('productos', 'id')->where(fn($q) => $q->where('empresa_id', $empresaId)),
            ],
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.costo_unitario' => 'required_if:tipo,ENTRADA|numeric|min:0',
            'detalles.*.lote' => 'nullable|string|max:50',
            'detalles.*.fecha_vencimiento' => 'nullable|date',
        ], [
            'tipo.required' => 'El tipo de movimiento es requerido',
            'fecha.required' => 'La fecha es requerida',
            'detalles.required' => 'Debe agregar al menos un producto',
            'detalles.*.producto_id.required' => 'El producto es requerido',
            'detalles.*.cantidad.required' => 'La cantidad es requerida',
            'detalles.*.cantidad.min' => 'La cantidad debe ser mayor a 0',
        ]);

        // Validar acceso por almacén (almacenero solo puede operar en su almacén)
        $almacenAsignado = $this->getAlmacenAsignado($request);
        if ($almacenAsignado) {
            $almacenOrigen = $request->almacen_origen_id;
            $almacenDestino = $request->almacen_destino_id;

            // Verificar que al menos uno de los almacenes sea el asignado
            if ($almacenOrigen && $almacenOrigen != $almacenAsignado &&
                $almacenDestino && $almacenDestino != $almacenAsignado) {
                return $this->error('No tiene permisos para operar en estos almacenes', 403);
            }

            // Para entradas, el destino debe ser su almacén
            if ($request->tipo === 'ENTRADA' && $almacenDestino != $almacenAsignado) {
                return $this->error('Solo puede registrar entradas en su almacén asignado', 403);
            }

            // Para salidas, el origen debe ser su almacén
            if ($request->tipo === 'SALIDA' && $almacenOrigen != $almacenAsignado) {
                return $this->error('Solo puede registrar salidas desde su almacén asignado', 403);
            }
        }

        try {
            DB::beginTransaction();

            // Generar número de movimiento
            $numero = $this->generarNumero($empresaId, $request->tipo);

            // Crear movimiento
            $movimiento = Movimiento::create([
                'empresa_id' => $empresaId,
                'numero' => $numero,
                'tipo' => $request->tipo,
                'subtipo' => $request->subtipo,
                'almacen_origen_id' => $request->almacen_origen_id,
                'almacen_destino_id' => $request->almacen_destino_id,
                'proveedor_id' => $request->proveedor_id,
                'centro_costo_id' => $request->centro_costo_id,
                'usuario_id' => $request->user()->id,
                'fecha' => $request->fecha,
                'documento_referencia' => $request->documento_referencia,
                'observaciones' => $request->observaciones,
                'estado' => $request->tipo === Movimiento::TIPO_TRANSFERENCIA
                    ? Movimiento::ESTADO_PENDIENTE
                    : Movimiento::ESTADO_COMPLETADO,
            ]);

            // Procesar detalles
            foreach ($request->detalles as $detalle) {
                $costoUnitario = $detalle['costo_unitario'] ?? 0;
                $costoTotal = $detalle['cantidad'] * $costoUnitario;

                // Crear detalle
                $movimientoDetalle = MovimientoDetalle::create([
                    'movimiento_id' => $movimiento->id,
                    'producto_id' => $detalle['producto_id'],
                    'cantidad' => $detalle['cantidad'],
                    'costo_unitario' => $costoUnitario,
                    'costo_total' => $costoTotal,
                    'lote' => $detalle['lote'] ?? null,
                    'fecha_vencimiento' => $detalle['fecha_vencimiento'] ?? null,
                ]);

                // Actualizar stock según tipo de movimiento
                $costoTransferencia = $this->actualizarStock(
                    $empresaId,
                    $detalle['producto_id'],
                    $detalle['cantidad'],
                    $costoUnitario,
                    $movimiento
                );

                if ($movimiento->tipo === Movimiento::TIPO_TRANSFERENCIA && $costoTransferencia !== null) {
                    $movimientoDetalle->update([
                        'costo_unitario' => $costoTransferencia,
                        'costo_total' => $detalle['cantidad'] * $costoTransferencia,
                    ]);
                }
            }

            DB::commit();

            $movimiento->load(['detalles.producto', 'almacenOrigen', 'almacenDestino', 'usuario']);

            $mensaje = $movimiento->tipo === Movimiento::TIPO_TRANSFERENCIA
                ? 'Transferencia registrada y enviada en tránsito'
                : 'Movimiento registrado exitosamente';
            return $this->created($movimiento, $mensaje);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar movimiento de inventario', [
                'empresa_id' => $empresaId,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError('Error interno al registrar el movimiento');
        }
    }

    /**
     * Mostrar movimiento.
     */
    public function show(Movimiento $movimiento): JsonResponse
    {
        $empresaId = auth()->user()?->empresa_id;
        if ($empresaId && $movimiento->empresa_id !== $empresaId) {
            return $this->error('No autorizado', 403);
        }

        $almacenAsignado = $this->getAlmacenAsignado(request());
        if ($almacenAsignado &&
            $movimiento->almacen_origen_id != $almacenAsignado &&
            $movimiento->almacen_destino_id != $almacenAsignado) {
            return $this->error('No autorizado', 403);
        }

        $movimiento->load([
            'detalles.producto.familia',
            'almacenOrigen',
            'almacenDestino',
            'proveedor',
            'centroCosto',
            'usuario',
            'anuladoPor',
        ]);

        return $this->success($movimiento);
    }

    /**
     * Anular movimiento.
     */
    public function anular(Request $request, Movimiento $movimiento): JsonResponse
    {
        if ($movimiento->empresa_id !== $request->user()->empresa_id) {
            return $this->error('No autorizado', 403);
        }

        $almacenAsignado = $this->getAlmacenAsignado($request);
        if ($almacenAsignado &&
            $movimiento->almacen_origen_id != $almacenAsignado &&
            $movimiento->almacen_destino_id != $almacenAsignado) {
            return $this->error('No autorizado', 403);
        }

        if ($movimiento->estado === Movimiento::ESTADO_ANULADO) {
            return $this->error('El movimiento ya está anulado', 422);
        }

        $request->validate([
            'motivo' => 'required|string|max:500',
        ], [
            'motivo.required' => 'El motivo de anulación es requerido',
        ]);

        try {
            DB::beginTransaction();

            // Revertir stock
            foreach ($movimiento->detalles as $detalle) {
                $this->revertirStock(
                    $movimiento->empresa_id,
                    $detalle->producto_id,
                    $detalle->cantidad,
                    $detalle->costo_unitario,
                    $movimiento
                );
            }

            // Anular movimiento
            $movimiento->update([
                'estado' => Movimiento::ESTADO_ANULADO,
                'anulado_por' => $request->user()->id,
                'fecha_anulacion' => now(),
                'observaciones' => $movimiento->observaciones . "\n[ANULADO] " . $request->motivo,
            ]);

            DB::commit();

            return $this->success($movimiento, 'Movimiento anulado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al anular movimiento de inventario', [
                'empresa_id' => $movimiento->empresa_id,
                'user_id' => $request->user()->id,
                'movimiento_id' => $movimiento->id,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError('Error interno al anular el movimiento');
        }
    }

    /**
     * Confirmar recepción de transferencia.
     */
    public function confirmarRecepcion(Request $request, Movimiento $movimiento): JsonResponse
    {
        if ($movimiento->empresa_id !== $request->user()->empresa_id) {
            return $this->error('No autorizado', 403);
        }

        if ($movimiento->tipo !== Movimiento::TIPO_TRANSFERENCIA) {
            return $this->error('Solo aplica para movimientos de transferencia', 422);
        }

        if ($movimiento->estado !== Movimiento::ESTADO_PENDIENTE) {
            return $this->error('La transferencia ya fue recepcionada o anulada', 422);
        }

        $almacenAsignado = $this->getAlmacenAsignado($request);
        if ($almacenAsignado && (int) $movimiento->almacen_destino_id !== (int) $almacenAsignado) {
            return $this->error('Solo el almacén destino puede confirmar la recepción', 403);
        }

        try {
            DB::beginTransaction();

            foreach ($movimiento->detalles as $detalle) {
                $this->incrementarStock(
                    $movimiento->empresa_id,
                    $detalle->producto_id,
                    (int) $movimiento->almacen_destino_id,
                    (float) $detalle->cantidad,
                    (float) $detalle->costo_unitario,
                    $movimiento
                );
            }

            $movimiento->update([
                'estado' => Movimiento::ESTADO_COMPLETADO,
            ]);

            DB::commit();

            return $this->success($movimiento, 'Transferencia recepcionada correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al confirmar recepción de transferencia', [
                'empresa_id' => $movimiento->empresa_id,
                'user_id' => $request->user()->id,
                'movimiento_id' => $movimiento->id,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError('Error interno al confirmar la recepción');
        }
    }

    /**
     * Generar número de movimiento.
     */
    private function generarNumero(int $empresaId, string $tipo): string
    {
        $prefijos = [
            'ENTRADA' => 'ENT',
            'SALIDA' => 'SAL',
            'TRANSFERENCIA' => 'TRF',
            'AJUSTE' => 'AJU',
        ];

        $prefijo = $prefijos[$tipo] ?? 'MOV';
        $año = date('Y');
        $mes = date('m');
        $base = "{$prefijo}-{$año}{$mes}-";

        $ultimoNumero = Movimiento::where('empresa_id', $empresaId)
            ->where('tipo', $tipo)
            ->where('numero', 'like', $base . '%')
            ->orderBy('numero', 'desc')
            ->lockForUpdate()
            ->value('numero');

        $secuencia = 1;
        if ($ultimoNumero) {
            $partes = explode('-', $ultimoNumero);
            $secuencia = (int) end($partes) + 1;
        }

        $numero = $base . str_pad($secuencia, 6, '0', STR_PAD_LEFT);
        while (Movimiento::where('empresa_id', $empresaId)->where('numero', $numero)->exists()) {
            $secuencia++;
            $numero = $base . str_pad($secuencia, 6, '0', STR_PAD_LEFT);
        }

        return $numero;
    }

    /**
     * Actualizar stock según tipo de movimiento.
     */
    private function actualizarStock(
        int $empresaId,
        int $productoId,
        float $cantidad,
        float $costoUnitario,
        Movimiento $movimiento
    ): ?float {
        switch ($movimiento->tipo) {
            case Movimiento::TIPO_ENTRADA:
                $this->incrementarStock(
                    $empresaId,
                    $productoId,
                    $movimiento->almacen_destino_id,
                    $cantidad,
                    $costoUnitario,
                    $movimiento
                );
                return null;

            case Movimiento::TIPO_SALIDA:
                $this->decrementarStock(
                    $empresaId,
                    $productoId,
                    $movimiento->almacen_origen_id,
                    $cantidad,
                    $movimiento
                );
                return null;

            case Movimiento::TIPO_TRANSFERENCIA:
                $costoTransferencia = $this->decrementarStock(
                    $empresaId,
                    $productoId,
                    $movimiento->almacen_origen_id,
                    $cantidad,
                    $movimiento
                );
                return $costoTransferencia;

            case Movimiento::TIPO_AJUSTE:
                if ($cantidad > 0) {
                    $this->incrementarStock(
                        $empresaId,
                        $productoId,
                        $movimiento->almacen_destino_id ?? $movimiento->almacen_origen_id,
                        abs($cantidad),
                        $costoUnitario,
                        $movimiento
                    );
                } else {
                    $this->decrementarStock(
                        $empresaId,
                        $productoId,
                        $movimiento->almacen_origen_id ?? $movimiento->almacen_destino_id,
                        abs($cantidad),
                        $movimiento
                    );
                }
                return null;
        }

        return null;
    }

    /**
     * Incrementar stock (entrada).
     */
    private function incrementarStock(
        int $empresaId,
        int $productoId,
        int $almacenId,
        float $cantidad,
        float $costoUnitario,
        Movimiento $movimiento
    ): void {
        $stockAlmacen = StockAlmacen::firstOrCreate(
            [
                'empresa_id' => $empresaId,
                'producto_id' => $productoId,
                'almacen_id' => $almacenId,
            ],
            [
                'stock_actual' => 0,
                'costo_promedio' => 0,
            ]
        );

        // Calcular nuevo costo promedio
        $valorActual = $stockAlmacen->stock_actual * $stockAlmacen->costo_promedio;
        $valorNuevo = $cantidad * $costoUnitario;
        $nuevoStock = $stockAlmacen->stock_actual + $cantidad;
        $nuevoCostoPromedio = $nuevoStock > 0 ? ($valorActual + $valorNuevo) / $nuevoStock : 0;

        $stockAlmacen->update([
            'stock_actual' => $nuevoStock,
            'costo_promedio' => $nuevoCostoPromedio,
        ]);

        // Registrar en Kardex
        $this->registrarKardex(
            $empresaId,
            $productoId,
            $almacenId,
            $movimiento,
            Kardex::TIPO_ENTRADA,
            $cantidad,
            $costoUnitario,
            $stockAlmacen
        );
    }

    /**
     * Decrementar stock (salida).
     */
    private function decrementarStock(
        int $empresaId,
        int $productoId,
        int $almacenId,
        float $cantidad,
        Movimiento $movimiento
    ): float {
        $stockAlmacen = StockAlmacen::where('empresa_id', $empresaId)
            ->where('producto_id', $productoId)
            ->where('almacen_id', $almacenId)
            ->lockForUpdate()
            ->first();

        if (!$stockAlmacen || $stockAlmacen->stock_actual < $cantidad) {
            throw new \Exception("Stock insuficiente para el producto ID {$productoId}");
        }

        $costoUnitario = $stockAlmacen->costo_promedio;
        $nuevoStock = $stockAlmacen->stock_actual - $cantidad;

        $stockAlmacen->update([
            'stock_actual' => $nuevoStock,
        ]);

        // Registrar en Kardex
        $this->registrarKardex(
            $empresaId,
            $productoId,
            $almacenId,
            $movimiento,
            Kardex::TIPO_SALIDA,
            $cantidad,
            $costoUnitario,
            $stockAlmacen
        );

        return (float) $costoUnitario;
    }

    /**
     * Revertir stock (anulación).
     */
    private function revertirStock(
        int $empresaId,
        int $productoId,
        float $cantidad,
        float $costoUnitario,
        Movimiento $movimiento
    ): void {
        // Lógica inversa a actualizar stock
        switch ($movimiento->tipo) {
            case Movimiento::TIPO_ENTRADA:
                $stockAlmacen = StockAlmacen::where('empresa_id', $empresaId)
                    ->where('producto_id', $productoId)
                    ->where('almacen_id', $movimiento->almacen_destino_id)
                    ->first();

                if ($stockAlmacen) {
                    $stockAlmacen->update([
                        'stock_actual' => max(0, $stockAlmacen->stock_actual - $cantidad),
                    ]);
                }
                break;

            case Movimiento::TIPO_SALIDA:
                $stockAlmacen = StockAlmacen::firstOrCreate(
                    [
                        'empresa_id' => $empresaId,
                        'producto_id' => $productoId,
                        'almacen_id' => $movimiento->almacen_origen_id,
                    ],
                    ['stock_actual' => 0, 'costo_promedio' => $costoUnitario]
                );

                $stockAlmacen->update([
                    'stock_actual' => $stockAlmacen->stock_actual + $cantidad,
                ]);
                break;

            case Movimiento::TIPO_TRANSFERENCIA:
                // Si estaba en tránsito: solo devolver al origen
                // Si estaba completado: devolver al origen y descontar del destino
                $stockOrigen = StockAlmacen::firstOrCreate(
                    [
                        'empresa_id' => $empresaId,
                        'producto_id' => $productoId,
                        'almacen_id' => $movimiento->almacen_origen_id,
                    ],
                    ['stock_actual' => 0, 'costo_promedio' => $costoUnitario]
                );

                $stockOrigen->update([
                    'stock_actual' => $stockOrigen->stock_actual + $cantidad,
                ]);

                if ($movimiento->estado === Movimiento::ESTADO_COMPLETADO) {
                    $stockDestino = StockAlmacen::where('empresa_id', $empresaId)
                        ->where('producto_id', $productoId)
                        ->where('almacen_id', $movimiento->almacen_destino_id)
                        ->first();

                    if ($stockDestino) {
                        $stockDestino->update([
                            'stock_actual' => max(0, $stockDestino->stock_actual - $cantidad),
                        ]);
                    }
                }
                break;
        }
    }

    /**
     * Registrar movimiento en Kardex.
     */
    private function registrarKardex(
        int $empresaId,
        int $productoId,
        int $almacenId,
        Movimiento $movimiento,
        string $tipoOperacion,
        float $cantidad,
        float $costoUnitario,
        StockAlmacen $stockAlmacen
    ): void {
        Kardex::create([
            'empresa_id' => $empresaId,
            'producto_id' => $productoId,
            'almacen_id' => $almacenId,
            'movimiento_id' => $movimiento->id,
            'fecha' => $movimiento->fecha,
            'tipo_operacion' => $tipoOperacion,
            'documento_referencia' => $movimiento->documento_referencia ?? $movimiento->numero,
            'cantidad' => $cantidad,
            'costo_unitario' => $costoUnitario,
            'costo_total' => $cantidad * $costoUnitario,
            'saldo_cantidad' => $stockAlmacen->stock_actual,
            'saldo_costo_unitario' => $stockAlmacen->costo_promedio,
            'saldo_costo_total' => $stockAlmacen->stock_actual * $stockAlmacen->costo_promedio,
            'descripcion' => $movimiento->observaciones,
        ]);
    }
}
