<?php

namespace App\Modules\Proveedores\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Proveedores\Models\Proveedor;
use App\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ProveedorController extends Controller
{
    use ApiResponse;

    /**
     * Listar proveedores.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Proveedor::query();

        // Filtro por empresa (multi-tenancy)
        if ($request->user()->empresa_id) {
            $query->where('empresa_id', $request->user()->empresa_id);
        }

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('razon_social', 'like', "%{$search}%")
                  ->orWhere('ruc', 'like', "%{$search}%")
                  ->orWhere('nombre_comercial', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        // Ordenamiento
        $sortField = $request->get('sort_field', 'razon_social');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortField, $sortOrder);

        // Si se pide sin paginación (para selects)
        if ($request->boolean('all')) {
            $proveedores = $query->get(['id', 'ruc', 'razon_social', 'activo']);
            return $this->success($proveedores);
        }

        // Paginación
        $perPage = $request->get('per_page', 15);
        $proveedores = $query->paginate($perPage);

        return $this->paginated($proveedores);
    }

    /**
     * Crear proveedor.
     */
    public function store(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id ?? $request->empresa_id;

        $request->validate([
            'ruc' => 'required|string|size:11|unique:proveedores,ruc,NULL,id,empresa_id,' . $empresaId,
            'razon_social' => 'required|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:500',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'contacto_nombre' => 'nullable|string|max:255',
            'contacto_telefono' => 'nullable|string|max:20',
            'contacto_email' => 'nullable|email|max:255',
            'tipo' => 'required|in:BIENES,SERVICIOS,AMBOS',
            'activo' => 'boolean',
        ], [
            'ruc.required' => 'El RUC es requerido',
            'ruc.size' => 'El RUC debe tener 11 dígitos',
            'ruc.unique' => 'Este RUC ya está registrado',
            'razon_social.required' => 'La razón social es requerida',
            'tipo.required' => 'El tipo de proveedor es requerido',
        ]);

        $proveedor = Proveedor::create([
            'empresa_id' => $empresaId,
            'ruc' => $request->ruc,
            'razon_social' => $request->razon_social,
            'nombre_comercial' => $request->nombre_comercial,
            'direccion' => $request->direccion,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'contacto_nombre' => $request->contacto_nombre,
            'contacto_telefono' => $request->contacto_telefono,
            'contacto_email' => $request->contacto_email,
            'tipo' => $request->tipo,
            'activo' => $request->get('activo', true),
        ]);

        return $this->created($proveedor, 'Proveedor creado exitosamente');
    }

    /**
     * Mostrar proveedor.
     */
    public function show(Proveedor $proveedor): JsonResponse
    {
        return $this->success($proveedor);
    }

    /**
     * Actualizar proveedor.
     */
    public function update(Request $request, Proveedor $proveedor): JsonResponse
    {
        $request->validate([
            'ruc' => 'sometimes|string|size:11|unique:proveedores,ruc,' . $proveedor->id . ',id,empresa_id,' . $proveedor->empresa_id,
            'razon_social' => 'sometimes|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:500',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'contacto_nombre' => 'nullable|string|max:255',
            'contacto_telefono' => 'nullable|string|max:20',
            'contacto_email' => 'nullable|email|max:255',
            'tipo' => 'sometimes|in:BIENES,SERVICIOS,AMBOS',
            'activo' => 'boolean',
        ]);

        $proveedor->update($request->only([
            'ruc', 'razon_social', 'nombre_comercial', 'direccion', 'telefono',
            'email', 'contacto_nombre', 'contacto_telefono', 'contacto_email',
            'tipo', 'activo'
        ]));

        return $this->success($proveedor, 'Proveedor actualizado exitosamente');
    }

    /**
     * Eliminar proveedor.
     */
    public function destroy(Proveedor $proveedor): JsonResponse
    {
        // Verificar si tiene movimientos
        if ($proveedor->movimientos()->count() > 0) {
            return $this->error('No se puede eliminar el proveedor porque tiene movimientos asociados', 422);
        }

        $proveedor->delete();

        return $this->success(null, 'Proveedor eliminado exitosamente');
    }

    /**
     * Validar RUC con SUNAT.
     */
    public function validarRuc(string $ruc): JsonResponse
    {
        // Validar formato de RUC (11 dígitos, comienza con 10 o 20)
        if (!preg_match('/^(10|20)\d{9}$/', $ruc)) {
            return $this->error('El RUC no tiene un formato válido. Debe tener 11 dígitos y comenzar con 10 o 20.', 422);
        }

        // Buscar en caché primero (24 horas)
        $cacheKey = "sunat_ruc_{$ruc}";
        $cachedData = Cache::get($cacheKey);

        if ($cachedData) {
            return $this->success($cachedData, 'Datos obtenidos de caché');
        }

        try {
            $apiUrl = config('services.sunat.api_url');
            $apiToken = config('services.sunat.api_token');

            // Construir la petición HTTP
            $httpClient = Http::timeout(10);

            // Agregar token si está configurado
            if (!empty($apiToken)) {
                $httpClient = $httpClient->withHeaders([
                    'Authorization' => "Bearer {$apiToken}",
                ]);
            }

            $response = $httpClient->get($apiUrl, [
                'numero' => $ruc,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Soportar ambos formatos: v1 (nombre) y v2 (razonSocial)
                $razonSocial = $data['razonSocial'] ?? $data['nombre'] ?? null;

                if ($razonSocial) {
                    $resultado = [
                        'ruc' => $data['numeroDocumento'] ?? $ruc,
                        'razon_social' => $razonSocial,
                        'nombre_comercial' => $data['nombreComercial'] ?? null,
                        'direccion' => $data['direccion'] ?? null,
                        'estado' => $data['estado'] ?? 'ACTIVO',
                        'condicion' => $data['condicion'] ?? 'HABIDO',
                        'ubigeo' => $data['ubigeo'] ?? null,
                        'departamento' => $data['departamento'] ?? null,
                        'provincia' => $data['provincia'] ?? null,
                        'distrito' => $data['distrito'] ?? null,
                    ];

                    // Guardar en caché por 24 horas
                    Cache::put($cacheKey, $resultado, now()->addHours(24));

                    return $this->success($resultado, 'RUC validado exitosamente');
                }

                // RUC no encontrado
                return $this->error('RUC no encontrado en SUNAT. Verifique el número.', 404);
            }

            // Error de la API (401, 403, etc.)
            if ($response->status() === 401 || $response->status() === 403) {
                return $this->error('Error de autenticación con la API SUNAT. Configure SUNAT_API_TOKEN en .env', 503);
            }

            // Si la API no responde correctamente, permitir ingreso manual
            return $this->error('No se pudo validar el RUC. Puede ingresar los datos manualmente.', 404);

        } catch (\Exception $e) {
            // Log del error para debugging
            \Log::warning('Error consultando SUNAT API: ' . $e->getMessage());

            // En caso de error, permitir ingreso manual
            return $this->error('Error al conectar con SUNAT. Puede ingresar los datos manualmente.', 503);
        }
    }

    /**
     * Obtener órdenes de compra del proveedor.
     */
    public function ordenesCompra(Request $request, Proveedor $proveedor): JsonResponse
    {
        // Por implementar cuando se cree el módulo de compras
        return $this->success([
            'proveedor_id' => $proveedor->id,
            'ordenes' => [],
            'mensaje' => 'Funcionalidad en desarrollo',
        ]);
    }

    /**
     * Calificar proveedor.
     */
    public function calificar(Request $request, Proveedor $proveedor): JsonResponse
    {
        $request->validate([
            'calificacion' => 'required|numeric|min:1|max:5',
            'comentario' => 'nullable|string|max:500',
        ], [
            'calificacion.required' => 'La calificación es requerida',
            'calificacion.min' => 'La calificación mínima es 1',
            'calificacion.max' => 'La calificación máxima es 5',
        ]);

        $proveedor->agregarCalificacion($request->calificacion);

        return $this->success([
            'calificacion_actual' => $proveedor->calificacion,
            'total_calificaciones' => $proveedor->total_calificaciones,
        ], 'Calificación registrada exitosamente');
    }
}
