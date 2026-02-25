<?php

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\Models\UnidadMedida;
use App\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnidadMedidaController extends Controller
{
    use ApiResponse;

    /**
     * Listar unidades de medida.
     */
    public function index(Request $request): JsonResponse
    {
        $query = UnidadMedida::query()->orderBy('nombre');

        // Filtro por empresa (incluye globales)
        if ($request->user()->empresa_id) {
            $query->deEmpresa($request->user()->empresa_id);
        }

        // Filtro por estado
        if ($request->filled('activo')) {
            $query->where('activo', $request->boolean('activo'));
        } else {
            // Por defecto solo activas
            $query->activas();
        }

        // Búsqueda
        if ($request->filled('buscar')) {
            $query->buscar($request->buscar);
        }

        return $this->success($query->get());
    }

    /**
     * Crear unidad de medida.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'codigo' => ['required', 'string', 'max:10', 'unique:unidades_medida,codigo'],
            'nombre' => ['required', 'string', 'max:100'],
            'abreviatura' => ['required', 'string', 'max:10'],
            'activo' => ['boolean'],
        ], [
            'codigo.required' => 'El código es requerido',
            'codigo.unique' => 'Este código ya está en uso',
            'nombre.required' => 'El nombre es requerido',
            'abreviatura.required' => 'La abreviatura es requerida',
        ]);

        $validated['empresa_id'] = $request->user()->empresa_id;
        $validated['activo'] = $validated['activo'] ?? true;

        $unidad = UnidadMedida::create($validated);

        return $this->created($unidad, 'Unidad de medida creada exitosamente');
    }

    /**
     * Mostrar unidad de medida.
     */
    public function show(UnidadMedida $unidade): JsonResponse
    {
        return $this->success($unidade);
    }

    /**
     * Actualizar unidad de medida.
     */
    public function update(Request $request, UnidadMedida $unidade): JsonResponse
    {
        $validated = $request->validate([
            'codigo' => ['sometimes', 'string', 'max:10', 'unique:unidades_medida,codigo,' . $unidade->id],
            'nombre' => ['sometimes', 'string', 'max:100'],
            'abreviatura' => ['sometimes', 'string', 'max:10'],
            'activo' => ['boolean'],
        ], [
            'codigo.unique' => 'Este código ya está en uso',
        ]);

        $unidade->update($validated);

        return $this->success($unidade, 'Unidad de medida actualizada exitosamente');
    }

    /**
     * Eliminar unidad de medida.
     */
    public function destroy(UnidadMedida $unidade): JsonResponse
    {
        // Verificar si está en uso (opcional)
        // $enUso = Producto::where('unidad_medida', $unidade->codigo)->exists();
        // if ($enUso) {
        //     return $this->error('No se puede eliminar, está en uso por productos', 422);
        // }

        $unidade->delete();

        return $this->success(null, 'Unidad de medida eliminada exitosamente');
    }
}
