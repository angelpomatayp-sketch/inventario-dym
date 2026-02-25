<?php

namespace App\Modules\Administracion\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administracion\Models\Empresa;
use App\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    use ApiResponse;

    /**
     * Listar empresas.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Empresa::query();

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('razon_social', 'like', "%{$search}%")
                  ->orWhere('ruc', 'like', "%{$search}%");
            });
        }

        if ($request->filled('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        // Ordenamiento
        $sortField = $request->get('sort_field', 'razon_social');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortField, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $empresas = $query->paginate($perPage);

        return $this->paginated($empresas);
    }

    /**
     * Crear empresa.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'ruc' => 'required|string|size:11|unique:empresas,ruc',
            'razon_social' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:500',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'metodo_valuacion' => 'required|in:PEPS,PROMEDIO',
            'activo' => 'boolean',
        ], [
            'ruc.required' => 'El RUC es requerido',
            'ruc.size' => 'El RUC debe tener 11 dígitos',
            'ruc.unique' => 'Este RUC ya está registrado',
            'razon_social.required' => 'La razón social es requerida',
            'metodo_valuacion.required' => 'El método de valuación es requerido',
            'metodo_valuacion.in' => 'El método de valuación debe ser PEPS o PROMEDIO',
        ]);

        $empresa = Empresa::create($request->all());

        return $this->created($empresa, 'Empresa creada exitosamente');
    }

    /**
     * Mostrar empresa.
     */
    public function show(Empresa $empresa): JsonResponse
    {
        $empresa->load(['almacenes', 'centrosCostos']);

        return $this->success($empresa);
    }

    /**
     * Actualizar empresa.
     */
    public function update(Request $request, Empresa $empresa): JsonResponse
    {
        $request->validate([
            'ruc' => 'sometimes|string|size:11|unique:empresas,ruc,' . $empresa->id,
            'razon_social' => 'sometimes|string|max:255',
            'direccion' => 'nullable|string|max:500',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'metodo_valuacion' => 'sometimes|in:PEPS,PROMEDIO',
            'activo' => 'boolean',
        ]);

        $empresa->update($request->all());

        return $this->success($empresa, 'Empresa actualizada exitosamente');
    }

    /**
     * Eliminar empresa.
     */
    public function destroy(Empresa $empresa): JsonResponse
    {
        // Verificar si tiene relaciones
        if ($empresa->usuarios()->count() > 0) {
            return $this->error('No se puede eliminar la empresa porque tiene usuarios asociados', 422);
        }

        $empresa->delete();

        return $this->success(null, 'Empresa eliminada exitosamente');
    }
}
