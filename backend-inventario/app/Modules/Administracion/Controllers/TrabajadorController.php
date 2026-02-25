<?php

namespace App\Modules\Administracion\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administracion\Models\Trabajador;
use App\Shared\Traits\FiltrosPorRol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrabajadorController extends Controller
{
    use FiltrosPorRol;

    /**
     * Listar trabajadores con filtros.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Trabajador::with(['centroCosto:id,codigo,nombre'])
            ->orderBy('nombre');

        // Filtro por empresa (multi-tenancy)
        if ($request->user()->empresa_id) {
            $query->where('empresa_id', $request->user()->empresa_id);
        }

        // Filtro por centro de costo según rol (asistente solo ve su centro asignado)
        $centroCostoAsignado = $this->getCentroCostoAsignado($request);
        if ($centroCostoAsignado) {
            $query->where('centro_costo_id', $centroCostoAsignado);
        } elseif ($request->filled('centro_costo_id')) {
            // Filtro manual (para usuarios con acceso total)
            $query->where('centro_costo_id', $request->centro_costo_id);
        }

        // Filtro por estado activo
        if ($request->filled('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        // Búsqueda
        if ($request->filled('buscar')) {
            $query->buscar($request->buscar);
        }

        // Paginación
        $perPage = $request->input('per_page', 15);

        if ($request->boolean('sin_paginacion')) {
            return response()->json([
                'success' => true,
                'data' => $query->get(),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage),
        ]);
    }

    /**
     * Crear nuevo trabajador.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'centro_costo_id' => ['nullable', 'exists:centros_costos,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'dni' => ['nullable', 'string', 'max:20'],
            'cargo' => ['nullable', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'fecha_ingreso' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $validated['empresa_id'] = Auth::user()->empresa_id;
        $validated['activo'] = true;

        $trabajador = Trabajador::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Trabajador registrado exitosamente.',
            'data' => $trabajador->load('centroCosto:id,codigo,nombre'),
        ], 201);
    }

    /**
     * Mostrar un trabajador.
     */
    public function show(Trabajador $trabajador): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $trabajador->load('centroCosto'),
        ]);
    }

    /**
     * Actualizar trabajador.
     */
    public function update(Request $request, Trabajador $trabajador): JsonResponse
    {
        $validated = $request->validate([
            'centro_costo_id' => ['nullable', 'exists:centros_costos,id'],
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'dni' => ['nullable', 'string', 'max:20'],
            'cargo' => ['nullable', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'fecha_ingreso' => ['nullable', 'date'],
            'fecha_cese' => ['nullable', 'date'],
            'activo' => ['sometimes', 'boolean'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $trabajador->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Trabajador actualizado exitosamente.',
            'data' => $trabajador->fresh()->load('centroCosto:id,codigo,nombre'),
        ]);
    }

    /**
     * Eliminar trabajador (soft delete).
     */
    public function destroy(Trabajador $trabajador): JsonResponse
    {
        $trabajador->delete();

        return response()->json([
            'success' => true,
            'message' => 'Trabajador eliminado exitosamente.',
        ]);
    }

    /**
     * Dar de baja a un trabajador.
     */
    public function darDeBaja(Request $request, Trabajador $trabajador): JsonResponse
    {
        $validated = $request->validate([
            'observacion' => ['nullable', 'string'],
        ]);

        $trabajador->darDeBaja($validated['observacion'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Trabajador dado de baja exitosamente.',
            'data' => $trabajador->fresh(),
        ]);
    }

    /**
     * Buscar trabajadores para select/autocomplete.
     */
    public function buscar(Request $request): JsonResponse
    {
        $query = Trabajador::activos()
            ->select('id', 'nombre', 'dni', 'cargo', 'centro_costo_id')
            ->orderBy('nombre');

        if ($request->filled('centro_costo_id')) {
            $query->where('centro_costo_id', $request->centro_costo_id);
        }

        if ($request->filled('q')) {
            $query->buscar($request->q);
        }

        return response()->json([
            'success' => true,
            'data' => $query->limit(20)->get(),
        ]);
    }
}
