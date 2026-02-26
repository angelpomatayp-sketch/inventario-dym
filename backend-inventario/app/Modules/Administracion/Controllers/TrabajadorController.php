<?php

namespace App\Modules\Administracion\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administracion\Models\Trabajador;
use App\Shared\Traits\FiltrosPorRol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
     * Subir kardex físico escaneado en PDF.
     */
    public function subirKardexPdf(Request $request, Trabajador $trabajador): JsonResponse
    {
        $request->validate([
            'kardex_pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ], [
            'kardex_pdf.required' => 'Seleccione un archivo PDF.',
            'kardex_pdf.mimes'    => 'El archivo debe ser PDF.',
            'kardex_pdf.max'      => 'El PDF no debe superar 20 MB.',
        ]);

        // Eliminar PDF anterior si existe
        if ($trabajador->kardex_pdf_ruta && Storage::disk('public')->exists($trabajador->kardex_pdf_ruta)) {
            Storage::disk('public')->delete($trabajador->kardex_pdf_ruta);
        }

        $archivo = $request->file('kardex_pdf');
        $nombreArchivo = Str::uuid() . '.pdf';
        $directorio = "kardex/trabajadores/{$trabajador->id}";

        Storage::disk('public')->putFileAs($directorio, $archivo, $nombreArchivo);

        $trabajador->update([
            'kardex_pdf_ruta'           => "{$directorio}/{$nombreArchivo}",
            'kardex_pdf_nombre_original' => $archivo->getClientOriginalName(),
            'kardex_pdf_tamano'          => $archivo->getSize(),
            'kardex_pdf_subido_en'       => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kardex PDF subido exitosamente.',
            'data'    => $trabajador->only(['tiene_kardex', 'kardex_pdf_nombre_original', 'kardex_pdf_tamano', 'kardex_pdf_subido_en']),
        ]);
    }

    /**
     * Descargar / visualizar kardex PDF.
     */
    public function descargarKardexPdf(Trabajador $trabajador): Response
    {
        if (!$trabajador->kardex_pdf_ruta) {
            abort(404, 'Este trabajador no tiene kardex PDF.');
        }

        $path = Storage::disk('public')->path($trabajador->kardex_pdf_ruta);

        if (!file_exists($path)) {
            abort(404, 'Archivo no encontrado en el servidor.');
        }

        return response()->file($path, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $trabajador->kardex_pdf_nombre_original . '"',
        ]);
    }

    /**
     * Eliminar kardex PDF.
     */
    public function eliminarKardexPdf(Trabajador $trabajador): JsonResponse
    {
        if (!$trabajador->kardex_pdf_ruta) {
            return response()->json(['success' => false, 'message' => 'No hay kardex PDF.'], 404);
        }

        if (Storage::disk('public')->exists($trabajador->kardex_pdf_ruta)) {
            Storage::disk('public')->delete($trabajador->kardex_pdf_ruta);
        }

        $directorio = "kardex/trabajadores/{$trabajador->id}";
        if (empty(Storage::disk('public')->files($directorio))) {
            Storage::disk('public')->deleteDirectory($directorio);
        }

        $trabajador->update([
            'kardex_pdf_ruta'           => null,
            'kardex_pdf_nombre_original' => null,
            'kardex_pdf_tamano'          => null,
            'kardex_pdf_subido_en'       => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Kardex PDF eliminado.']);
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
