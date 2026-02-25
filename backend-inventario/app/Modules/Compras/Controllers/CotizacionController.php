<?php

namespace App\Modules\Compras\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compras\Models\Cotizacion;
use App\Modules\Compras\Models\CotizacionDetalle;
use App\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CotizacionController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Cotizacion::with(['proveedor:id,ruc,razon_social', 'solicitante:id,nombre'])
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

        $sortField = $request->get('sort_field', 'fecha_solicitud');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        $perPage = $request->get('per_page', 15);
        $cotizaciones = $query->paginate($perPage);

        return $this->paginated($cotizaciones);
    }

    public function store(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id ?? $request->empresa_id;

        $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id',
            'fecha_solicitud' => 'required|date',
            'fecha_vencimiento' => 'nullable|date|after_or_equal:fecha_solicitud',
            'condiciones_pago' => 'nullable|string|max:500',
            'tiempo_entrega_dias' => 'nullable|integer|min:1',
            'observaciones' => 'nullable|string',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
            'detalles.*.descuento' => 'nullable|numeric|min:0|max:100',
        ], [
            'proveedor_id.required' => 'Seleccione un proveedor',
            'detalles.required' => 'Agregue al menos un producto',
        ]);

        DB::beginTransaction();
        try {
            $numero = $this->generarNumero($empresaId);

            $cotizacion = Cotizacion::create([
                'empresa_id' => $empresaId,
                'numero' => $numero,
                'proveedor_id' => $request->proveedor_id,
                'solicitado_por' => $request->user()->id,
                'fecha_solicitud' => $request->fecha_solicitud,
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'estado' => Cotizacion::ESTADO_BORRADOR,
                'moneda' => $request->moneda ?? 'PEN',
                'tipo_cambio' => $request->tipo_cambio ?? 1,
                'condiciones_pago' => $request->condiciones_pago,
                'tiempo_entrega_dias' => $request->tiempo_entrega_dias,
                'observaciones' => $request->observaciones,
            ]);

            foreach ($request->detalles as $detalle) {
                $subtotal = $detalle['cantidad'] * $detalle['precio_unitario'];
                if (isset($detalle['descuento']) && $detalle['descuento'] > 0) {
                    $subtotal -= $subtotal * ($detalle['descuento'] / 100);
                }

                CotizacionDetalle::create([
                    'cotizacion_id' => $cotizacion->id,
                    'producto_id' => $detalle['producto_id'],
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'descuento' => $detalle['descuento'] ?? 0,
                    'subtotal' => $subtotal,
                    'especificaciones' => $detalle['especificaciones'] ?? null,
                ]);
            }

            $cotizacion->calcularTotales();

            DB::commit();

            return $this->created(
                $cotizacion->load(['proveedor', 'detalles.producto']),
                'Cotización creada exitosamente'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear cotización', [
                'empresa_id' => $empresaId,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Error interno al crear la cotización', 500);
        }
    }

    public function show(Cotizacion $cotizacion): JsonResponse
    {
        $cotizacion->load([
            'proveedor',
            'solicitante:id,nombre',
            'aprobador:id,nombre',
            'ordenCompra:id,numero,estado',
            'detalles.producto:id,codigo,nombre,unidad_medida',
        ]);

        return $this->success($cotizacion);
    }

    public function update(Request $request, Cotizacion $cotizacion): JsonResponse
    {
        if (!$cotizacion->puedeEditarse()) {
            return $this->error('Esta cotización no puede ser editada', 422);
        }

        $request->validate([
            'proveedor_id' => 'sometimes|exists:proveedores,id',
            'fecha_solicitud' => 'sometimes|date',
            'fecha_vencimiento' => 'nullable|date',
            'detalles' => 'sometimes|array|min:1',
        ]);

        DB::beginTransaction();
        try {
            $cotizacion->update($request->only([
                'proveedor_id', 'fecha_solicitud', 'fecha_vencimiento',
                'condiciones_pago', 'tiempo_entrega_dias', 'observaciones'
            ]));

            if ($request->has('detalles')) {
                $cotizacion->detalles()->delete();

                foreach ($request->detalles as $detalle) {
                    $subtotal = $detalle['cantidad'] * $detalle['precio_unitario'];
                    if (isset($detalle['descuento']) && $detalle['descuento'] > 0) {
                        $subtotal -= $subtotal * ($detalle['descuento'] / 100);
                    }

                    CotizacionDetalle::create([
                        'cotizacion_id' => $cotizacion->id,
                        'producto_id' => $detalle['producto_id'],
                        'cantidad' => $detalle['cantidad'],
                        'precio_unitario' => $detalle['precio_unitario'],
                        'descuento' => $detalle['descuento'] ?? 0,
                        'subtotal' => $subtotal,
                    ]);
                }

                $cotizacion->calcularTotales();
            }

            DB::commit();

            return $this->success($cotizacion->fresh(['proveedor', 'detalles.producto']), 'Cotización actualizada');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar cotización', [
                'empresa_id' => $cotizacion->empresa_id,
                'user_id' => $request->user()->id,
                'cotizacion_id' => $cotizacion->id,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Error interno al actualizar la cotización', 500);
        }
    }

    public function destroy(Cotizacion $cotizacion): JsonResponse
    {
        if (!$cotizacion->puedeEditarse()) {
            return $this->error('Esta cotización no puede ser eliminada', 422);
        }

        $cotizacion->detalles()->delete();
        $cotizacion->delete();

        return $this->success(null, 'Cotización eliminada');
    }

    public function enviar(Cotizacion $cotizacion): JsonResponse
    {
        if ($cotizacion->estado !== Cotizacion::ESTADO_BORRADOR) {
            return $this->error('Solo se pueden enviar cotizaciones en borrador', 422);
        }

        $cotizacion->update(['estado' => Cotizacion::ESTADO_ENVIADA]);

        return $this->success($cotizacion, 'Cotización enviada al proveedor');
    }

    public function registrarRespuesta(Request $request, Cotizacion $cotizacion): JsonResponse
    {
        if ($cotizacion->estado !== Cotizacion::ESTADO_ENVIADA) {
            return $this->error('Solo se puede registrar respuesta de cotizaciones enviadas', 422);
        }

        $request->validate([
            'detalles' => 'required|array',
            'detalles.*.id' => 'required|exists:cotizaciones_detalle,id',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->detalles as $det) {
                $detalle = CotizacionDetalle::find($det['id']);
                $subtotal = $detalle->cantidad * $det['precio_unitario'];
                if ($detalle->descuento > 0) {
                    $subtotal -= $subtotal * ($detalle->descuento / 100);
                }
                $detalle->update([
                    'precio_unitario' => $det['precio_unitario'],
                    'subtotal' => $subtotal,
                ]);
            }

            $cotizacion->calcularTotales();
            $cotizacion->update([
                'estado' => Cotizacion::ESTADO_RECIBIDA,
                'condiciones_pago' => $request->condiciones_pago ?? $cotizacion->condiciones_pago,
                'tiempo_entrega_dias' => $request->tiempo_entrega_dias ?? $cotizacion->tiempo_entrega_dias,
            ]);

            DB::commit();

            return $this->success($cotizacion->fresh(['detalles.producto']), 'Respuesta registrada');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar respuesta de cotización', [
                'empresa_id' => $cotizacion->empresa_id,
                'user_id' => $request->user()->id,
                'cotizacion_id' => $cotizacion->id,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Error interno al registrar la respuesta', 500);
        }
    }

    public function aprobar(Request $request, Cotizacion $cotizacion): JsonResponse
    {
        if (!$cotizacion->puedeAprobarse()) {
            return $this->error('Esta cotización no puede ser aprobada', 422);
        }

        $cotizacion->aprobar($request->user()->id);

        return $this->success($cotizacion, 'Cotización aprobada');
    }

    public function rechazar(Request $request, Cotizacion $cotizacion): JsonResponse
    {
        if ($cotizacion->estado !== Cotizacion::ESTADO_RECIBIDA) {
            return $this->error('Solo se pueden rechazar cotizaciones recibidas', 422);
        }

        $cotizacion->rechazar($request->user()->id);

        return $this->success($cotizacion, 'Cotización rechazada');
    }

    public function aprobadas(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $cotizaciones = Cotizacion::where('empresa_id', $empresaId)
            ->where('estado', Cotizacion::ESTADO_APROBADA)
            ->doesntHave('ordenCompra')
            ->with(['proveedor:id,razon_social', 'detalles.producto:id,codigo,nombre'])
            ->get();

        return $this->success($cotizaciones);
    }

    public function estadisticas(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;
        $mesActual = now()->startOfMonth();

        $stats = [
            'total' => Cotizacion::where('empresa_id', $empresaId)->count(),
            'pendientes' => Cotizacion::where('empresa_id', $empresaId)
                ->whereIn('estado', [Cotizacion::ESTADO_BORRADOR, Cotizacion::ESTADO_ENVIADA])
                ->count(),
            'por_aprobar' => Cotizacion::where('empresa_id', $empresaId)
                ->where('estado', Cotizacion::ESTADO_RECIBIDA)
                ->count(),
            'valor_aprobadas_mes' => Cotizacion::where('empresa_id', $empresaId)
                ->where('estado', Cotizacion::ESTADO_APROBADA)
                ->where('fecha_aprobacion', '>=', $mesActual)
                ->sum('total'),
        ];

        return $this->success($stats);
    }

    private function generarNumero(int $empresaId): string
    {
        $año = date('Y');
        $prefijo = "COT-{$año}-";
        $ultimoNumero = Cotizacion::where('empresa_id', $empresaId)
            ->where('numero', 'like', $prefijo . '%')
            ->orderBy('numero', 'desc')
            ->lockForUpdate()
            ->value('numero');

        $secuencia = 1;
        if ($ultimoNumero) {
            $secuencia = (int) substr($ultimoNumero, -6) + 1;
        }

        $numero = sprintf("%s%06d", $prefijo, $secuencia);
        while (Cotizacion::where('empresa_id', $empresaId)->where('numero', $numero)->exists()) {
            $secuencia++;
            $numero = sprintf("%s%06d", $prefijo, $secuencia);
        }

        return $numero;
    }
}
