<?php

namespace App\Modules\Requisiciones\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Requisiciones\Models\Requisicion;
use App\Modules\Requisiciones\Requests\StoreRequisicionRequest;
use App\Modules\Requisiciones\Requests\UpdateRequisicionRequest;
use App\Modules\Requisiciones\Services\RequisicionService;
use App\Shared\Traits\ApiResponse;
use App\Shared\Traits\FiltrosPorRol;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RequisicionController extends Controller
{
    use ApiResponse, FiltrosPorRol;

    public function __construct(private readonly RequisicionService $service) {}

    // ==================== LISTADO ====================

    public function index(Request $request): JsonResponse
    {
        $query = Requisicion::with([
            'almacenero:id,nombre',
            'centroCosto:id,nombre',
            'almacen:id,nombre',
            'aprobador:id,nombre',
        ])->withCount('detalles');

        $user = $request->user();

        if ($user->empresa_id) {
            $query->where('empresa_id', $user->empresa_id);
        }

        // Almacenero solo ve sus propios requerimientos
        if ($user->hasAnyRole(['almacenero'])) {
            $query->where('almacenero_id', $user->id);
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

        return $this->paginated($query->paginate($perPage));
    }

    // ==================== CRUD ====================

    public function store(StoreRequisicionRequest $request): JsonResponse
    {
        try {
            $requerimiento = $this->service->crear(
                $request->validated(),
                $request->user()->empresa_id,
                $request->user()->id,
                $request->boolean('enviar_aprobacion')
            );

            $mensaje = $requerimiento->estado === Requisicion::ESTADO_PENDIENTE
                ? 'Requerimiento creado y enviado a aprobación'
                : 'Requerimiento guardado como borrador';

            return $this->created($requerimiento, $mensaje);

        } catch (\Exception $e) {
            Log::error('Error al crear requerimiento', [
                'empresa_id' => $request->user()->empresa_id,
                'user_id'    => $request->user()->id,
                'error'      => $e->getMessage(),
            ]);
            return $this->serverError('Error interno al crear el requerimiento');
        }
    }

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

    public function update(UpdateRequisicionRequest $request, Requisicion $requisicion): JsonResponse
    {
        $user = $request->user();

        if ($user->hasAnyRole(['almacenero']) && $requisicion->almacenero_id !== $user->id) {
            return $this->error('Solo puede editar sus propios requerimientos', 403);
        }

        if (!$requisicion->puedeEditarse()) {
            return $this->error('Este requerimiento no puede ser editado en su estado actual', 422);
        }

        try {
            $requerimiento = $this->service->actualizar(
                $requisicion,
                $request->validated(),
                $request->boolean('enviar_aprobacion')
            );

            return $this->success($requerimiento, 'Requerimiento actualizado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al actualizar requerimiento', [
                'empresa_id'       => $requisicion->empresa_id,
                'user_id'          => $user->id,
                'requerimiento_id' => $requisicion->id,
                'error'            => $e->getMessage(),
            ]);
            return $this->serverError('Error interno al actualizar el requerimiento');
        }
    }

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

    // ==================== FLUJO DE ESTADOS ====================

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
            $requerimiento = $this->service->aprobar(
                $requisicion,
                $request->user()->id,
                $request->comentario,
                $request->cantidades_aprobadas
            );

            return $this->success($requerimiento, 'Requerimiento aprobado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al aprobar requerimiento', [
                'empresa_id'       => $requisicion->empresa_id,
                'user_id'          => $request->user()->id,
                'requerimiento_id' => $requisicion->id,
                'error'            => $e->getMessage(),
            ]);
            return $this->serverError('Error interno al aprobar el requerimiento');
        }
    }

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

    public function anular(Request $request, Requisicion $requisicion): JsonResponse
    {
        $user = $request->user();

        if ($user->hasAnyRole(['almacenero']) && $requisicion->almacenero_id !== $user->id) {
            return $this->error('Solo puede anular sus propios requerimientos', 403);
        }

        if (!$requisicion->puedeAnularse()) {
            return $this->error('Este requerimiento no puede ser anulado', 422);
        }

        $requisicion->anular($user->id);

        return $this->success($requisicion, 'Requerimiento anulado exitosamente');
    }

    // ==================== ESTADÍSTICAS Y PDF ====================

    public function estadisticas(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;
        $userId    = $request->user()->id;

        $base = Requisicion::where('empresa_id', $empresaId);

        if ($request->user()->hasAnyRole(['almacenero'])) {
            $base->where('almacenero_id', $userId);
        }

        $stats = [
            'total'          => (clone $base)->count(),
            'pendientes'     => (clone $base)->where('estado', Requisicion::ESTADO_PENDIENTE)->count(),
            'aprobadas'      => (clone $base)->where('estado', Requisicion::ESTADO_APROBADA)->count(),
            'mis_pendientes' => (clone $base)->where('almacenero_id', $userId)
                                             ->where('estado', Requisicion::ESTADO_PENDIENTE)->count(),
            'urgentes'       => (clone $base)->where('prioridad', Requisicion::PRIORIDAD_URGENTE)
                                             ->whereIn('estado', [
                                                 Requisicion::ESTADO_PENDIENTE,
                                                 Requisicion::ESTADO_APROBADA,
                                             ])->count(),
        ];

        return $this->success($stats);
    }

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
            $pdf        = Pdf::loadView('pdf.requerimiento', ['requerimiento' => $requisicion]);
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
}
