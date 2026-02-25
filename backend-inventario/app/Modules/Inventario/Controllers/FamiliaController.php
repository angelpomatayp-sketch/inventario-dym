<?php

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\Models\Familia;
use App\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FamiliaController extends Controller
{
    use ApiResponse;

    /**
     * Listar familias.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // El scope global PerteneceAEmpresa ya filtra por empresa_id
            $query = Familia::withCount('productos');

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

            // Filtro por es_epp
            if ($request->has('es_epp')) {
                $query->where('es_epp', $request->boolean('es_epp'));
            }

            // Si se pide sin paginación (para selects)
            if ($request->boolean('all')) {
                $familias = $query->get(['id', 'codigo', 'nombre', 'es_epp', 'categoria_epp', 'activo']);
                return $this->success($familias);
            }

            // Paginación
            $perPage = $request->get('per_page', 15);
            $familias = $query->paginate($perPage);

            return $this->paginated($familias);
        } catch (\Exception $e) {
            \Log::error('Error en FamiliaController@index: ' . $e->getMessage());
            return $this->error('Error al cargar familias: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Crear familia.
     */
    public function store(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id ?? $request->empresa_id;

        $request->validate([
            'codigo' => 'required|string|max:20|unique:familias,codigo,NULL,id,empresa_id,' . $empresaId,
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'es_epp' => 'boolean',
            'categoria_epp' => 'nullable|string|in:CABEZA,OJOS,OIDOS,RESPIRATORIO,MANOS,PIES,CUERPO,ALTURA',
            'activo' => 'boolean',
        ], [
            'codigo.required' => 'El código es requerido',
            'codigo.unique' => 'Este código ya está registrado',
            'nombre.required' => 'El nombre es requerido',
            'categoria_epp.in' => 'La categoría de EPP no es válida',
        ]);

        $familia = Familia::create([
            'empresa_id' => $empresaId,
            'codigo' => $request->codigo,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'es_epp' => $request->get('es_epp', false),
            'categoria_epp' => $request->es_epp ? $request->categoria_epp : null,
            'activo' => $request->get('activo', true),
        ]);

        return $this->created($familia, 'Familia creada exitosamente');
    }

    /**
     * Mostrar familia.
     */
    public function show(Familia $familia): JsonResponse
    {
        $familia->loadCount('productos');

        return $this->success($familia);
    }

    /**
     * Actualizar familia.
     */
    public function update(Request $request, Familia $familia): JsonResponse
    {
        $request->validate([
            'codigo' => 'sometimes|string|max:20|unique:familias,codigo,' . $familia->id . ',id,empresa_id,' . $familia->empresa_id,
            'nombre' => 'sometimes|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'es_epp' => 'boolean',
            'categoria_epp' => 'nullable|string|in:CABEZA,OJOS,OIDOS,RESPIRATORIO,MANOS,PIES,CUERPO,ALTURA',
            'activo' => 'boolean',
        ]);

        // Si se desmarca es_epp, limpiar categoria_epp
        $data = $request->only(['codigo', 'nombre', 'descripcion', 'es_epp', 'categoria_epp', 'activo']);
        if (isset($data['es_epp']) && !$data['es_epp']) {
            $data['categoria_epp'] = null;
        }

        $familia->update($data);

        return $this->success($familia, 'Familia actualizada exitosamente');
    }

    /**
     * Eliminar familia.
     */
    public function destroy(Familia $familia): JsonResponse
    {
        // Verificar si tiene productos asociados
        if ($familia->productos()->count() > 0) {
            return $this->error('No se puede eliminar la familia porque tiene productos asociados', 422);
        }

        $familia->delete();

        return $this->success(null, 'Familia eliminada exitosamente');
    }
}
