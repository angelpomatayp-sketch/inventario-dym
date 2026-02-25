<?php

namespace App\Modules\Administracion\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administracion\Models\CentroCosto;
use App\Shared\Traits\ApiResponse;
use App\Shared\Traits\FiltrosPorRol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CentroCostoController extends Controller
{
    use ApiResponse, FiltrosPorRol;

    /**
     * Listar centros de costo.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CentroCosto::with('empresa');

        // Filtro por empresa (multi-tenancy)
        if ($request->user()->empresa_id) {
            $query->where('empresa_id', $request->user()->empresa_id);
        }

        // Filtro por centro de costo según rol (asistentes/residentes/solicitantes solo ven su centro de costo)
        $centroCostoAsignado = $this->getCentroCostoAsignado($request);
        if ($centroCostoAsignado) {
            $query->where('id', $centroCostoAsignado);
        }

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%");
            });
        }

        if ($request->filled('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        // Ordenamiento
        $sortField = $request->get('sort_field', 'nombre');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortField, $sortOrder);

        // Si se pide sin paginación (para selects)
        if ($request->boolean('all')) {
            $centros = $query->get(['id', 'codigo', 'nombre', 'activo']);
            return $this->success($centros);
        }

        // Paginación
        $perPage = $request->get('per_page', 15);
        $centros = $query->paginate($perPage);

        return $this->paginated($centros);
    }

    /**
     * Crear centro de costo.
     */
    public function store(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id ?? $request->empresa_id;

        $request->validate([
            'codigo' => 'required|string|max:20|unique:centros_costos,codigo,NULL,id,empresa_id,' . $empresaId,
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'responsable' => 'nullable|string|max:255',
            'activo' => 'boolean',
        ], [
            'codigo.required' => 'El código es requerido',
            'codigo.unique' => 'Este código ya está registrado',
            'nombre.required' => 'El nombre es requerido',
        ]);

        $centro = CentroCosto::create([
            'empresa_id' => $empresaId,
            'codigo' => $request->codigo,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'responsable' => $request->responsable,
            'activo' => $request->get('activo', true),
        ]);

        return $this->created($centro, 'Centro de costo creado exitosamente');
    }

    /**
     * Mostrar centro de costo.
     */
    public function show(CentroCosto $centros_costo): JsonResponse
    {
        $centros_costo->load(['empresa', 'usuarios']);

        return $this->success($centros_costo);
    }

    /**
     * Actualizar centro de costo.
     */
    public function update(Request $request, CentroCosto $centros_costo): JsonResponse
    {
        $request->validate([
            'codigo' => 'sometimes|string|max:20|unique:centros_costos,codigo,' . $centros_costo->id . ',id,empresa_id,' . $centros_costo->empresa_id,
            'nombre' => 'sometimes|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'responsable' => 'nullable|string|max:255',
            'activo' => 'boolean',
        ]);

        $centros_costo->update($request->only([
            'codigo', 'nombre', 'descripcion', 'responsable', 'activo'
        ]));

        return $this->success($centros_costo, 'Centro de costo actualizado exitosamente');
    }

    /**
     * Eliminar centro de costo.
     */
    public function destroy(CentroCosto $centros_costo): JsonResponse
    {
        // Verificar si tiene usuarios asociados
        if ($centros_costo->usuarios()->count() > 0) {
            return $this->error('No se puede eliminar el centro de costo porque tiene usuarios asociados', 422);
        }

        $centros_costo->delete();

        return $this->success(null, 'Centro de costo eliminado exitosamente');
    }
}
