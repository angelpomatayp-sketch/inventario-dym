<?php

namespace App\Modules\Requisiciones\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Requisiciones\Models\Requisicion;
use App\Modules\Requisiciones\Models\RequisicionDetalle;
use App\Shared\Traits\ApiResponse;
use App\Shared\Traits\FiltrosPorRol;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RequisicionController extends Controller
{
    use ApiResponse, FiltrosPorRol;

    /**
     * Listar requerimientos.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Requisicion::with([
            'almacenero:id,nombre',
            'centroCosto:id,nombre',
            'almacen:id,nombre',
            'aprobador:id,nombre',
        ])->withCount('detalles');

        if ($request->user()->empresa_id) {
            $query->where('empresa_id', $request->user()->empresa_id);
        }

        // Almacenero solo ve sus propios requerimientos
        if ($request->user()->hasAnyRole(['almacenero'])) {
            $query->where('almacenero_id', $request->user()->id);
        }

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

        if ($request->boolean('pendientes_aprobar')) {
            $query->where('estado', Requisicion::ESTADO_PENDIENTE);
        }

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha_solicitud', [$request->fecha_inicio, $request->fecha_fin]);
        }

        [$sortField, $sortOrder] = $this->sanitizarOrden(
            ['fecha_solicitud', 'numero', 'estado', 'created_at'],
            'fecha_solicitud',
            (string) $request->get('sort_field', 'fecha_solicitud'),
            (string) $request->get('sort_order', 'desc')
        );
        $query->orderBy($sortField, $sortOrder)->orderBy('id', 'desc');

        $perPage = $this->resolvePerPage($request, 15, 100);
        $requerimientos = $query->paginate($perPage);

        return $this->paginated($requerimientos);
    }

    /**
     * Crear requerimiento. Solo almaceneros y admin.
     */
    public function store(Request $request): JsonResponse
    {
        if (!$request->user()->hasAnyRole(['almacenero', 'super_admin'])) {
            return $this->error('Solo los almaceneros pueden crear requerimientos', 403);
        }

        $request->validate([
            'centro_costo_id' => 'required|exists:centros_costos,id',
            'almacen_id'      => 'nullable|exists:almacenes,id',
            'fecha_requerida' => 'required|date|after_or_equal:today',
            'prioridad'       => 'required|in:BAJA,NORMAL,ALTA,URGENTE',
            'motivo'          => 'required|string|max:500',
            'observaciones'   => 'nullable|string|max:1000',
            'detalles'        => 'required|array|min:1',
            'detalles.*.producto_id'         => 'required|exists:productos,id',
            'detalles.*.cantidad_solicitada' => 'required|numeric|min:0.01',
            'detalles.*.especificaciones'    => 'nullable|string|max:500',
        ], [
            'centro_costo_id.required'               => 'El centro de costo es requerido',
            'fecha_requerida.required'               => 'La fecha requerida es obligatoria',
            'fecha_requerida.after_or_equal'         => 'La fecha requerida debe ser hoy o posterior',
            'prioridad.required'                     => 'La prioridad es requerida',
            'motivo.required'                        => 'El motivo es requerido',
            'detalles.required'                      => 'Debe agregar al menos un producto',
            'detalles.*.producto_id.required'        => 'El producto es requerido',
            'detalles.*.cantidad_solicitada.required'=> 'La cantidad es requerida',
            'detalles.*.cantidad_solicitada.min'     => 'La cantidad debe ser mayor a 0',
        ]);

        $empresaId = $request->user()->empresa_id;

        try {
            DB::beginTransaction();

            $numero = $this->generarNumero($empresaId, $request->centro_costo_id, $request->almacen_id);

            $estado = $request->boolean('enviar_aprobacion')
                ? Requisicion::ESTADO_PENDIENTE
                : Requisicion::ESTADO_BORRADOR;

            $requerimiento = Requisicion::create([
                'empresa_id'      => $empresaId,
                'numero'          => $numero,
                'almacenero_id'   => $request->user()->id,
                'centro_costo_id' => $request->centro_costo_id,
                'almacen_id'      => $request->almacen_id,
                'fecha_solicitud' => now()->toDateString(),
                'fecha_requerida' => $request->fecha_requerida,
                'prioridad'       => $request->prioridad,
                'estado'          => $estado,
                'motivo'          => $request->motivo,
                'observaciones'   => $request->observaciones,
            ]);

            foreach ($request->detalles as $detalle) {
                RequisicionDetalle::create([
                    'requisicion_id'      => $requerimiento->id,
                    'producto_id'         => $detalle['producto_id'],
                    'cantidad_solicitada' => $detalle['cantidad_solicitada'],
                    'especificaciones'    => $detalle['especificaciones'] ?? null,
                ]);
            }

            DB::commit();

            $requerimiento->load(['detalles.producto', 'almacenero', 'centroCosto', 'almacen']);

            $mensaje = $estado === Requisicion::ESTADO_PENDIENTE
                ? 'Requerimiento creado y enviado a aprobación'
                : 'Requerimiento guardado como borrador';

            return $this->created($requerimiento, $mensaje);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear requerimiento', [
                'empresa_id' => $empresaId,
                'user_id'    => $request->user()->id,
                'error'      => $e->getMessage(),
            ]);
            return $this->serverError('Error interno al crear el requerimiento');
        }
    }

    /**
     * Mostrar requerimiento.
     */
    public function show(Requisicion $requisicion): JsonResponse
    {
        $requisicion->load([
            'detalles.producto.familia',
            'almacenero',
            'centroCosto',
            'almacen',
            'aprobador',
            'anulador',
        ]);

        return $this->success($requisicion);
    }

    /**
     * Actualizar requerimiento. Solo almaceneros (dueño) y admin.
     */
    public function update(Request $request, Requisicion $requisicion): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasAnyRole(['almacenero', 'super_admin'])) {
            return $this->error('No tiene permiso para editar requerimientos', 403);
        }

        // El almacenero solo puede editar sus propios requerimientos
        if ($user->hasAnyRole(['almacenero']) && $requisicion->almacenero_id !== $user->id) {
            return $this->error('Solo puede editar sus propios requerimientos', 403);
        }

        if (!$requisicion->puedeEditarse()) {
            return $this->error('Este requerimiento no puede ser editado en su estado actual', 422);
        }

        $request->validate([
            'centro_costo_id' => 'required|exists:centros_costos,id',
            'almacen_id'      => 'nullable|exists:almacenes,id',
            'fecha_requerida' => 'required|date|after_or_equal:today',
            'prioridad'       => 'required|in:BAJA,NORMAL,ALTA,URGENTE',
            'motivo'          => 'required|string|max:500',
            'observaciones'   => 'nullable|string|max:1000',
            'detalles'        => 'required|array|min:1',
            'detalles.*.producto_id'         => 'required|exists:productos,id',
            'detalles.*.cantidad_solicitada' => 'required|numeric|min:0.01',
            'detalles.*.especificaciones'    => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $estado = $request->boolean('enviar_aprobacion')
                ? Requisicion::ESTADO_PENDIENTE
                : Requisicion::ESTADO_BORRADOR;

            $requisicion->update([
                'centro_costo_id'        => $request->centro_costo_id,
                'almacen_id'             => $request->almacen_id,
                'fecha_requerida'        => $request->fecha_requerida,
                'prioridad'              => $request->prioridad,
                'estado'                 => $estado,
                'motivo'                 => $request->motivo,
                'observaciones'          => $request->observaciones,
                'aprobado_por'           => null,
                'fecha_aprobacion'       => null,
                'comentario_aprobacion'  => null,
            ]);

            $requisicion->detalles()->delete();

            foreach ($request->detalles as $detalle) {
                RequisicionDetalle::create([
                    'requisicion_id'      => $requisicion->id,
                    'producto_id'         => $detalle['producto_id'],
                    'cantidad_solicitada' => $detalle['cantidad_solicitada'],
                    'especificaciones'    => $detalle['especificaciones'] ?? null,
                ]);
            }

            DB::commit();

            $requisicion->load(['detalles.producto', 'almacenero', 'centroCosto', 'almacen']);

            return $this->success($requisicion, 'Requerimiento actualizado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar requerimiento', [
                'empresa_id'      => $requisicion->empresa_id,
                'user_id'         => $request->user()->id,
                'requerimiento_id'=> $requisicion->id,
                'error'           => $e->getMessage(),
            ]);
            return $this->serverError('Error interno al actualizar el requerimiento');
        }
    }

    /**
     * Enviar a aprobación. Solo almaceneros (dueño) y admin.
     */
    public function enviarAprobacion(Request $request, Requisicion $requisicion): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasAnyRole(['almacenero', 'super_admin'])) {
            return $this->error('No tiene permiso para esta acción', 403);
        }

        if ($user->hasAnyRole(['almacenero']) && $requisicion->almacenero_id !== $user->id) {
            return $this->error('Solo puede enviar sus propios requerimientos', 403);
        }

        if ($requisicion->estado !== Requisicion::ESTADO_BORRADOR) {
            return $this->error('Solo los requerimientos en borrador pueden enviarse a aprobación', 422);
        }

        if ($requisicion->detalles()->count() === 0) {
            return $this->error('El requerimiento debe tener al menos un producto', 422);
        }

        $requisicion->enviarAprobacion();

        return $this->success($requisicion, 'Requerimiento enviado a aprobación');
    }

    /**
     * Aprobar requerimiento. Solo admin (super_admin).
     */
    public function aprobar(Request $request, Requisicion $requisicion): JsonResponse
    {
        if (!$request->user()->hasAnyRole(['super_admin'])) {
            return $this->error('Solo el administrador puede aprobar requerimientos', 403);
        }

        if (!$requisicion->puedeAprobarse()) {
            return $this->error('Este requerimiento no puede ser aprobado en su estado actual', 422);
        }

        $request->validate([
            'comentario'                        => 'nullable|string|max:500',
            'cantidades_aprobadas'              => 'nullable|array',
            'cantidades_aprobadas.*.detalle_id' => 'required|exists:requerimientos_detalle,id',
            'cantidades_aprobadas.*.cantidad'   => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            if ($request->filled('cantidades_aprobadas')) {
                foreach ($request->cantidades_aprobadas as $item) {
                    RequisicionDetalle::where('id', $item['detalle_id'])
                        ->where('requisicion_id', $requisicion->id)
                        ->update(['cantidad_aprobada' => $item['cantidad']]);
                }
            } else {
                $requisicion->detalles()->update([
                    'cantidad_aprobada' => DB::raw('cantidad_solicitada')
                ]);
            }

            $requisicion->aprobar($request->user()->id, $request->comentario);

            DB::commit();

            return $this->success($requisicion->fresh(), 'Requerimiento aprobado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al aprobar requerimiento', [
                'empresa_id'      => $requisicion->empresa_id,
                'user_id'         => $request->user()->id,
                'requerimiento_id'=> $requisicion->id,
                'error'           => $e->getMessage(),
            ]);
            return $this->serverError('Error interno al aprobar el requerimiento');
        }
    }

    /**
     * Rechazar requerimiento. Solo admin (super_admin).
     */
    public function rechazar(Request $request, Requisicion $requisicion): JsonResponse
    {
        if (!$request->user()->hasAnyRole(['super_admin'])) {
            return $this->error('Solo el administrador puede rechazar requerimientos', 403);
        }

        if (!$requisicion->puedeAprobarse()) {
            return $this->error('Este requerimiento no puede ser rechazado en su estado actual', 422);
        }

        $request->validate([
            'comentario' => 'required|string|max:500',
        ], [
            'comentario.required' => 'El motivo del rechazo es obligatorio',
        ]);

        $requisicion->rechazar($request->user()->id, $request->comentario);

        return $this->success($requisicion, 'Requerimiento rechazado');
    }

    /**
     * Anular requerimiento.
     */
    public function anular(Request $request, Requisicion $requisicion): JsonResponse
    {
        $user = $request->user();

        // Almacenero solo anula los suyos; admin puede anular cualquiera
        if ($user->hasAnyRole(['almacenero']) && $requisicion->almacenero_id !== $user->id) {
            return $this->error('Solo puede anular sus propios requerimientos', 403);
        }

        if (!$requisicion->puedeAnularse()) {
            return $this->error('Este requerimiento no puede ser anulado', 422);
        }

        $requisicion->anular($user->id);

        return $this->success($requisicion, 'Requerimiento anulado exitosamente');
    }

    /**
     * Eliminar requerimiento (solo borradores).
     */
    public function destroy(Request $request, Requisicion $requisicion): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasAnyRole(['almacenero', 'super_admin'])) {
            return $this->error('No tiene permiso para eliminar requerimientos', 403);
        }

        if ($user->hasAnyRole(['almacenero']) && $requisicion->almacenero_id !== $user->id) {
            return $this->error('Solo puede eliminar sus propios requerimientos', 403);
        }

        if ($requisicion->estado !== Requisicion::ESTADO_BORRADOR) {
            return $this->error('Solo los requerimientos en borrador pueden eliminarse', 422);
        }

        $requisicion->detalles()->delete();
        $requisicion->delete();

        return $this->success(null, 'Requerimiento eliminado exitosamente');
    }

    /**
     * Estadísticas de requerimientos.
     */
    public function estadisticas(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;
        $userId    = $request->user()->id;

        $base = Requisicion::where('empresa_id', $empresaId);

        // Almacenero solo ve sus propios stats
        if ($request->user()->hasAnyRole(['almacenero'])) {
            $base->where('almacenero_id', $userId);
        }

        $stats = [
            'total'        => (clone $base)->count(),
            'pendientes'   => (clone $base)->where('estado', Requisicion::ESTADO_PENDIENTE)->count(),
            'aprobadas'    => (clone $base)->where('estado', Requisicion::ESTADO_APROBADA)->count(),
            'mis_pendientes'=> (clone $base)->where('almacenero_id', $userId)->where('estado', Requisicion::ESTADO_PENDIENTE)->count(),
            'urgentes'     => (clone $base)->where('prioridad', Requisicion::PRIORIDAD_URGENTE)
                                           ->whereIn('estado', [Requisicion::ESTADO_PENDIENTE, Requisicion::ESTADO_APROBADA])
                                           ->count(),
        ];

        return $this->success($stats);
    }

    /**
     * Generar PDF del requerimiento.
     */
    public function generarPdf(Requisicion $requisicion): \Symfony\Component\HttpFoundation\Response
    {
        $requisicion->load([
            'detalles.producto',
            'almacenero',
            'centroCosto',
            'almacen',
            'aprobador',
        ]);

        try {
            $pdf = Pdf::loadView('pdf.requerimiento', ['requerimiento' => $requisicion]);
            $pdf->setPaper('A4', 'landscape');
            $pdfContent = $pdf->output();
        } catch (\Throwable $e) {
            Log::error('Error generando PDF requerimiento', [
                'requerimiento_id' => $requisicion->id,
                'error'            => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Error al generar el PDF: ' . $e->getMessage()], 500);
        }

        $filename = 'requerimiento-' . $requisicion->numero . '.pdf';

        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * Generar número de requerimiento por unidad/almacén.
     * Formato: KOLPA-001, KOLPA-002, LIMA-001, etc.
     * El prefijo se extrae del nombre del almacén (si existe) o del centro de costo.
     */
    private function generarNumero(int $empresaId, ?int $centroCostoId, ?int $almacenId): string
    {
        // Obtener nombre base para el prefijo
        $nombreBase = null;

        if ($almacenId) {
            $nombreBase = \DB::table('almacenes')->where('id', $almacenId)->value('nombre');
        }

        if (!$nombreBase && $centroCostoId) {
            $nombreBase = \DB::table('centros_costos')->where('id', $centroCostoId)->value('nombre');
        }

        // Limpiar y extraer prefijo: tomar la última palabra tras "-" si existe,
        // si no, la primera palabra. Solo letras y números, máx 10 chars, mayúsculas.
        if ($nombreBase) {
            $partes = preg_split('/[\s\-\/]+/', trim($nombreBase));
            $partes = array_filter($partes); // quitar vacíos
            // Preferir última parte con letras
            $ultimo = end($partes);
            $prefijoBruto = preg_replace('/[^A-Za-z0-9]/', '', $ultimo);
            $prefijoBruto = strtoupper(substr($prefijoBruto, 0, 10));
        }

        $prefijo = ($prefijoBruto ?? 'RQ') . '-';

        // Correlativo por empresa + prefijo (cada unidad tiene su propia secuencia)
        $ultimoNumero = Requisicion::where('empresa_id', $empresaId)
            ->where('numero', 'like', $prefijo . '%')
            ->orderByRaw('CAST(SUBSTRING_INDEX(numero, "-", -1) AS UNSIGNED) DESC')
            ->lockForUpdate()
            ->value('numero');

        $secuencia = 1;
        if ($ultimoNumero) {
            $partesFinal = explode('-', $ultimoNumero);
            $secuencia   = (int) end($partesFinal) + 1;
        }

        $numero = $prefijo . str_pad($secuencia, 3, '0', STR_PAD_LEFT);
        while (Requisicion::where('empresa_id', $empresaId)->where('numero', $numero)->exists()) {
            $secuencia++;
            $numero = $prefijo . str_pad($secuencia, 3, '0', STR_PAD_LEFT);
        }

        return $numero;
    }
}
