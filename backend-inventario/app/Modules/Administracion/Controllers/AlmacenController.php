<?php

namespace App\Modules\Administracion\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administracion\Models\Almacen;
use App\Shared\Traits\ApiResponse;
use App\Shared\Traits\FiltrosPorRol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlmacenController extends Controller
{
    use ApiResponse, FiltrosPorRol;

    /**
     * Listar almacenes.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Almacen::with(['empresa:id,razon_social', 'responsable:id,nombre', 'centroCosto:id,codigo,nombre']);
        $soloDestinosTransferencia = $request->boolean('solo_destinos_transferencia');

        // Filtro por empresa (multi-tenancy)
        if ($request->user()->empresa_id) {
            $query->where('empresa_id', $request->user()->empresa_id);
        }

        if (!$soloDestinosTransferencia) {
            // Filtro por almacén según rol (almacenero solo ve su almacén asignado)
            $almacenAsignado = $this->getAlmacenAsignado($request);
            if ($almacenAsignado) {
                $query->where('id', $almacenAsignado);
            } else {
                // Filtro por centro de costo (residente/asistente solo ven almacenes de su proyecto)
                $centroCostoAsignado = $this->getCentroCostoAsignado($request);
                if ($centroCostoAsignado) {
                    $query->where('centro_costo_id', $centroCostoAsignado);
                }
            }
        } else {
            // Para transferencias: solo destinos activos y excluir el origen cuando se envíe.
            $query->where('activo', true);
            if ($request->filled('exclude_id')) {
                $query->where('id', '!=', (int) $request->exclude_id);
            }
        }

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%")
                  ->orWhere('ubicacion', 'like', "%{$search}%");
            });
        }

        if ($request->filled('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        // Ordenamiento
        $sortField = $request->get('sort_field', 'nombre');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortField, $sortOrder);

        // Si se pide sin paginación (para selects)
        if ($request->boolean('all')) {
            $almacenes = $query->get(['id', 'codigo', 'nombre', 'tipo', 'centro_costo_id', 'activo']);
            return $this->success($almacenes);
        }

        // Paginación
        $perPage = $request->get('per_page', 15);
        $almacenes = $query->paginate($perPage);

        return $this->paginated($almacenes);
    }

    /**
     * Crear almacén.
     */
    public function store(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id ?? $request->empresa_id;

        $request->validate([
            'codigo' => 'required|string|max:20|unique:almacenes,codigo,NULL,id,empresa_id,' . $empresaId,
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|in:PRINCIPAL,CAMPAMENTO,SATELITE',
            'centro_costo_id' => 'nullable|exists:centros_costos,id',
            'ubicacion' => 'nullable|string|max:500',
            'responsable_id' => 'nullable|exists:usuarios,id',
            'activo' => 'boolean',
        ], [
            'codigo.required' => 'El código es requerido',
            'codigo.unique' => 'Este código ya está registrado',
            'nombre.required' => 'El nombre es requerido',
            'tipo.required' => 'El tipo de almacén es requerido',
            'tipo.in' => 'El tipo de almacén no es válido',
        ]);

        $almacen = Almacen::create([
            'empresa_id' => $empresaId,
            'centro_costo_id' => $request->centro_costo_id,
            'codigo' => $request->codigo,
            'nombre' => $request->nombre,
            'tipo' => $request->tipo,
            'ubicacion' => $request->ubicacion,
            'responsable_id' => $request->responsable_id,
            'activo' => $request->get('activo', true),
        ]);

        return $this->created($almacen, 'Almacén creado exitosamente');
    }

    /**
     * Mostrar almacén.
     */
    public function show(Almacen $almacen): JsonResponse
    {
        $almacen->load(['empresa', 'responsable']);

        return $this->success($almacen);
    }

    /**
     * Actualizar almacén.
     */
    public function update(Request $request, Almacen $almacen): JsonResponse
    {
        $request->validate([
            'codigo' => 'sometimes|string|max:20|unique:almacenes,codigo,' . $almacen->id . ',id,empresa_id,' . $almacen->empresa_id,
            'nombre' => 'sometimes|string|max:255',
            'tipo' => 'sometimes|in:PRINCIPAL,CAMPAMENTO,SATELITE',
            'centro_costo_id' => 'nullable|exists:centros_costos,id',
            'ubicacion' => 'nullable|string|max:500',
            'responsable_id' => 'nullable|exists:usuarios,id',
            'activo' => 'boolean',
        ]);

        $almacen->update($request->only([
            'codigo', 'nombre', 'tipo', 'centro_costo_id', 'ubicacion', 'responsable_id', 'activo'
        ]));

        return $this->success($almacen, 'Almacén actualizado exitosamente');
    }

    /**
     * Eliminar almacén.
     */
    public function destroy(Almacen $almacen): JsonResponse
    {
        // Verificar si tiene stock
        if ($almacen->stockAlmacen()->count() > 0) {
            return $this->error('No se puede eliminar el almacén porque tiene productos con stock', 422);
        }

        $almacen->delete();

        return $this->success(null, 'Almacén eliminado exitosamente');
    }

    /**
     * Obtener productos del almacén con stock.
     */
    public function productos(Request $request, Almacen $almacen): JsonResponse
    {
        $query = $almacen->stockAlmacen()->with('producto.familia');

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('producto', function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%");
            });
        }

        if ($request->filled('familia_id')) {
            $query->whereHas('producto', function ($q) use ($request) {
                $q->where('familia_id', $request->familia_id);
            });
        }

        if ($request->boolean('stock_bajo')) {
            $query->whereColumn('stock_actual', '<=', 'stock_minimo');
        }

        // Paginación
        $perPage = $request->get('per_page', 15);
        $productos = $query->paginate($perPage);

        return $this->paginated($productos);
    }
}
