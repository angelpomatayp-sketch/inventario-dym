<?php

namespace App\Modules\Requisiciones\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Requisiciones\Models\Requisicion;
use App\Modules\Requisiciones\Models\RequisicionDetalle;
use App\Shared\Traits\ApiResponse;
use App\Shared\Traits\FiltrosPorRol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RequisicionController extends Controller
{
    use ApiResponse, FiltrosPorRol;

    /**
     * Listar requisiciones.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Requisicion::with([
            'solicitante:id,nombre',
            'centroCosto:id,nombre',
            'almacen:id,nombre',
            'aprobador:id,nombre',
        ])->withCount('detalles');

        // Filtro por empresa (multi-tenancy)
        if ($request->user()->empresa_id) {
            $query->where('empresa_id', $request->user()->empresa_id);
        }

        // Filtro por centro de costo según rol (asistentes/residentes/solicitantes solo ven su centro de costo)
        $this->aplicarFiltroCentroCosto($query, $request);

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                  ->orWhere('motivo', 'like', "%{$search}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('prioridad')) {
            $query->where('prioridad', $request->prioridad);
        }

        if ($request->filled('centro_costo_id')) {
            $query->where('centro_costo_id', $request->centro_costo_id);
        }

        if ($request->filled('solicitante_id')) {
            $query->where('solicitante_id', $request->solicitante_id);
        }

        // Filtro "mis requisiciones"
        if ($request->boolean('mis_requisiciones')) {
            $query->where('solicitante_id', $request->user()->id);
        }

        // Filtro "pendientes de aprobar"
        if ($request->boolean('pendientes_aprobar')) {
            $query->where('estado', Requisicion::ESTADO_PENDIENTE);
        }

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha_solicitud', [$request->fecha_inicio, $request->fecha_fin]);
        }

        // Ordenamiento
        $sortField = $request->get('sort_field', 'fecha_solicitud');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortField, $sortOrder)->orderBy('id', 'desc');

        // Paginacion
        $perPage = $this->resolvePerPage($request, 15, 100);
        $requisiciones = $query->paginate($perPage);

        return $this->paginated($requisiciones);
    }

    /**
     * Crear requisicion.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'centro_costo_id' => 'required|exists:centros_costos,id',
            'almacen_id' => 'nullable|exists:almacenes,id',
            'fecha_requerida' => 'required|date|after_or_equal:today',
            'prioridad' => 'required|in:BAJA,NORMAL,ALTA,URGENTE',
            'motivo' => 'required|string|max:500',
            'observaciones' => 'nullable|string|max:1000',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.cantidad_solicitada' => 'required|numeric|min:0.01',
            'detalles.*.especificaciones' => 'nullable|string|max:500',
        ], [
            'centro_costo_id.required' => 'El centro de costo es requerido',
            'fecha_requerida.required' => 'La fecha requerida es obligatoria',
            'fecha_requerida.after_or_equal' => 'La fecha requerida debe ser hoy o posterior',
            'prioridad.required' => 'La prioridad es requerida',
            'motivo.required' => 'El motivo es requerido',
            'detalles.required' => 'Debe agregar al menos un producto',
            'detalles.*.producto_id.required' => 'El producto es requerido',
            'detalles.*.cantidad_solicitada.required' => 'La cantidad es requerida',
            'detalles.*.cantidad_solicitada.min' => 'La cantidad debe ser mayor a 0',
        ]);

        // Validar acceso por centro de costo (usuarios restringidos solo pueden crear para su centro de costo)
        $centroCostoAsignado = $this->getCentroCostoAsignado($request);
        if ($centroCostoAsignado && $request->centro_costo_id != $centroCostoAsignado) {
            return $this->error('Solo puede crear requisiciones para su centro de costo asignado', 403);
        }

        $empresaId = $request->user()->empresa_id;

        try {
            DB::beginTransaction();

            // Generar numero de requisicion
            $numero = $this->generarNumero($empresaId);

            // Determinar estado inicial
            $estado = $request->boolean('enviar_aprobacion')
                ? Requisicion::ESTADO_PENDIENTE
                : Requisicion::ESTADO_BORRADOR;

            // Crear requisicion
            $requisicion = Requisicion::create([
                'empresa_id' => $empresaId,
                'numero' => $numero,
                'solicitante_id' => $request->user()->id,
                'centro_costo_id' => $request->centro_costo_id,
                'almacen_id' => $request->almacen_id,
                'fecha_solicitud' => now()->toDateString(),
                'fecha_requerida' => $request->fecha_requerida,
                'prioridad' => $request->prioridad,
                'estado' => $estado,
                'motivo' => $request->motivo,
                'observaciones' => $request->observaciones,
            ]);

            // Crear detalles
            foreach ($request->detalles as $detalle) {
                RequisicionDetalle::create([
                    'requisicion_id' => $requisicion->id,
                    'producto_id' => $detalle['producto_id'],
                    'cantidad_solicitada' => $detalle['cantidad_solicitada'],
                    'especificaciones' => $detalle['especificaciones'] ?? null,
                ]);
            }

            DB::commit();

            $requisicion->load(['detalles.producto', 'solicitante', 'centroCosto', 'almacen']);

            $mensaje = $estado === Requisicion::ESTADO_PENDIENTE
                ? 'Requisicion creada y enviada a aprobacion'
                : 'Requisicion guardada como borrador';

            return $this->created($requisicion, $mensaje);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear requisición', [
                'empresa_id' => $empresaId,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError('Error interno al crear la requisición');
        }
    }

    /**
     * Mostrar requisicion.
     */
    public function show(Requisicion $requisicion): JsonResponse
    {
        $requisicion->load([
            'detalles.producto.familia',
            'solicitante',
            'centroCosto',
            'almacen',
            'aprobador',
            'anulador',
        ]);

        return $this->success($requisicion);
    }

    /**
     * Actualizar requisicion.
     */
    public function update(Request $request, Requisicion $requisicion): JsonResponse
    {
        if (!$requisicion->puedeEditarse()) {
            return $this->error('Esta requisicion no puede ser editada en su estado actual', 422);
        }

        $request->validate([
            'centro_costo_id' => 'required|exists:centros_costos,id',
            'almacen_id' => 'nullable|exists:almacenes,id',
            'fecha_requerida' => 'required|date|after_or_equal:today',
            'prioridad' => 'required|in:BAJA,NORMAL,ALTA,URGENTE',
            'motivo' => 'required|string|max:500',
            'observaciones' => 'nullable|string|max:1000',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.cantidad_solicitada' => 'required|numeric|min:0.01',
            'detalles.*.especificaciones' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Determinar estado
            $estado = $request->boolean('enviar_aprobacion')
                ? Requisicion::ESTADO_PENDIENTE
                : Requisicion::ESTADO_BORRADOR;

            // Actualizar requisicion
            $requisicion->update([
                'centro_costo_id' => $request->centro_costo_id,
                'almacen_id' => $request->almacen_id,
                'fecha_requerida' => $request->fecha_requerida,
                'prioridad' => $request->prioridad,
                'estado' => $estado,
                'motivo' => $request->motivo,
                'observaciones' => $request->observaciones,
                // Limpiar datos de rechazo previo
                'aprobado_por' => null,
                'fecha_aprobacion' => null,
                'comentario_aprobacion' => null,
            ]);

            // Eliminar detalles anteriores y crear nuevos
            $requisicion->detalles()->delete();

            foreach ($request->detalles as $detalle) {
                RequisicionDetalle::create([
                    'requisicion_id' => $requisicion->id,
                    'producto_id' => $detalle['producto_id'],
                    'cantidad_solicitada' => $detalle['cantidad_solicitada'],
                    'especificaciones' => $detalle['especificaciones'] ?? null,
                ]);
            }

            DB::commit();

            $requisicion->load(['detalles.producto', 'solicitante', 'centroCosto', 'almacen']);

            return $this->success($requisicion, 'Requisicion actualizada exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar requisición', [
                'empresa_id' => $requisicion->empresa_id,
                'user_id' => $request->user()->id,
                'requisicion_id' => $requisicion->id,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError('Error interno al actualizar la requisición');
        }
    }

    /**
     * Enviar a aprobacion.
     */
    public function enviarAprobacion(Requisicion $requisicion): JsonResponse
    {
        if ($requisicion->estado !== Requisicion::ESTADO_BORRADOR) {
            return $this->error('Solo las requisiciones en borrador pueden enviarse a aprobacion', 422);
        }

        if ($requisicion->detalles()->count() === 0) {
            return $this->error('La requisicion debe tener al menos un producto', 422);
        }

        $requisicion->enviarAprobacion();

        return $this->success($requisicion, 'Requisicion enviada a aprobacion');
    }

    /**
     * Aprobar requisicion.
     */
    public function aprobar(Request $request, Requisicion $requisicion): JsonResponse
    {
        if (!$requisicion->puedeAprobarse()) {
            return $this->error('Esta requisicion no puede ser aprobada en su estado actual', 422);
        }

        $request->validate([
            'comentario' => 'nullable|string|max:500',
            'cantidades_aprobadas' => 'nullable|array',
            'cantidades_aprobadas.*.detalle_id' => 'required|exists:requisiciones_detalle,id',
            'cantidades_aprobadas.*.cantidad' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Actualizar cantidades aprobadas si se especificaron
            if ($request->filled('cantidades_aprobadas')) {
                foreach ($request->cantidades_aprobadas as $item) {
                    RequisicionDetalle::where('id', $item['detalle_id'])
                        ->where('requisicion_id', $requisicion->id)
                        ->update(['cantidad_aprobada' => $item['cantidad']]);
                }
            } else {
                // Si no se especifican, aprobar todas las cantidades solicitadas
                $requisicion->detalles()->update([
                    'cantidad_aprobada' => DB::raw('cantidad_solicitada')
                ]);
            }

            $requisicion->aprobar($request->user()->id, $request->comentario);

            DB::commit();

            return $this->success($requisicion->fresh(), 'Requisicion aprobada exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al aprobar requisición', [
                'empresa_id' => $requisicion->empresa_id,
                'user_id' => $request->user()->id,
                'requisicion_id' => $requisicion->id,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError('Error interno al aprobar la requisición');
        }
    }

    /**
     * Rechazar requisicion.
     */
    public function rechazar(Request $request, Requisicion $requisicion): JsonResponse
    {
        if (!$requisicion->puedeAprobarse()) {
            return $this->error('Esta requisicion no puede ser rechazada en su estado actual', 422);
        }

        $request->validate([
            'comentario' => 'required|string|max:500',
        ], [
            'comentario.required' => 'El motivo del rechazo es obligatorio',
        ]);

        $requisicion->rechazar($request->user()->id, $request->comentario);

        return $this->success($requisicion, 'Requisicion rechazada');
    }

    /**
     * Anular requisicion.
     */
    public function anular(Request $request, Requisicion $requisicion): JsonResponse
    {
        if (!$requisicion->puedeAnularse()) {
            return $this->error('Esta requisicion no puede ser anulada', 422);
        }

        $requisicion->anular($request->user()->id);

        return $this->success($requisicion, 'Requisicion anulada exitosamente');
    }

    /**
     * Eliminar requisicion (solo borradores).
     */
    public function destroy(Requisicion $requisicion): JsonResponse
    {
        if ($requisicion->estado !== Requisicion::ESTADO_BORRADOR) {
            return $this->error('Solo las requisiciones en borrador pueden eliminarse', 422);
        }

        $requisicion->detalles()->delete();
        $requisicion->delete();

        return $this->success(null, 'Requisicion eliminada exitosamente');
    }

    /**
     * Obtener estadisticas de requisiciones.
     */
    public function estadisticas(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $stats = [
            'total' => Requisicion::where('empresa_id', $empresaId)->count(),
            'pendientes' => Requisicion::where('empresa_id', $empresaId)
                ->where('estado', Requisicion::ESTADO_PENDIENTE)->count(),
            'aprobadas' => Requisicion::where('empresa_id', $empresaId)
                ->where('estado', Requisicion::ESTADO_APROBADA)->count(),
            'mis_pendientes' => Requisicion::where('empresa_id', $empresaId)
                ->where('solicitante_id', $request->user()->id)
                ->where('estado', Requisicion::ESTADO_PENDIENTE)->count(),
            'urgentes' => Requisicion::where('empresa_id', $empresaId)
                ->where('prioridad', Requisicion::PRIORIDAD_URGENTE)
                ->whereIn('estado', [Requisicion::ESTADO_PENDIENTE, Requisicion::ESTADO_APROBADA])
                ->count(),
        ];

        return $this->success($stats);
    }

    /**
     * Generar numero de requisicion.
     */
    private function generarNumero(int $empresaId): string
    {
        $año = date('Y');
        $mes = date('m');
        $prefijo = "REQ-{$año}{$mes}-";

        $ultimoNumero = Requisicion::where('empresa_id', $empresaId)
            ->where('numero', 'like', $prefijo . '%')
            ->orderBy('numero', 'desc')
            ->lockForUpdate()
            ->value('numero');

        $secuencia = 1;
        if ($ultimoNumero) {
            $partes = explode('-', $ultimoNumero);
            $secuencia = (int) end($partes) + 1;
        }

        $numero = $prefijo . str_pad($secuencia, 6, '0', STR_PAD_LEFT);
        while (Requisicion::where('empresa_id', $empresaId)->where('numero', $numero)->exists()) {
            $secuencia++;
            $numero = $prefijo . str_pad($secuencia, 6, '0', STR_PAD_LEFT);
        }

        return $numero;
    }
}
