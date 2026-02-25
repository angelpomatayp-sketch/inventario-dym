<?php

namespace App\Modules\Prestamos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Prestamos\Models\EquipoPrestable;
use App\Modules\Prestamos\Models\PrestamoEquipo;
use App\Modules\Prestamos\Services\PrestamoService;
use App\Modules\Inventario\Models\Producto;
use App\Shared\Traits\ApiResponse;
use App\Shared\Traits\FiltrosPorRol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class PrestamoController extends Controller
{
    use ApiResponse, FiltrosPorRol;

    protected PrestamoService $prestamoService;

    public function __construct(PrestamoService $prestamoService)
    {
        $this->prestamoService = $prestamoService;
    }

    // ==================== EQUIPOS PRESTABLES ====================

    /**
     * Listar equipos prestables
     */
    public function indexEquipos(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $query = EquipoPrestable::where('empresa_id', $empresaId)
            ->with(['almacen', 'producto']);

        // Almacenero: solo equipos de su almacén asignado
        $this->aplicarFiltroAlmacen($query, $request);

        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%")
                    ->orWhere('numero_serie', 'like', "%{$buscar}%");
            });
        }

        $equipos = $query->orderBy('nombre')->paginate($this->resolvePerPage($request, 15, 100));

        return $this->success($equipos);
    }

    /**
     * Crear equipo prestable
     */
    public function storeEquipo(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $validated = $request->validate([
            'codigo' => ['required', 'string', 'max:50', 'unique:equipos_prestables,codigo'],
            'nombre' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
            'numero_serie' => ['nullable', 'string', 'max:100'],
            'marca' => ['nullable', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'tipo_control' => ['required', Rule::in(['INDIVIDUAL', 'CANTIDAD'])],
            'cantidad_total' => ['required_if:tipo_control,CANTIDAD', 'integer', 'min:1'],
            'almacen_id' => ['nullable', Rule::exists('almacenes', 'id')->where(fn($q) => $q->where('empresa_id', $empresaId))],
            'ubicacion_fisica' => ['nullable', 'string', 'max:200'],
            'valor_referencial' => ['nullable', 'numeric', 'min:0'],
            'fecha_adquisicion' => ['nullable', 'date'],
            'producto_id' => ['nullable', Rule::exists('productos', 'id')->where(fn($q) => $q->where('empresa_id', $empresaId))],
            'notas' => ['nullable', 'string'],
        ]);

        $validated['empresa_id'] = $empresaId;
        $validated['cantidad_disponible'] = $validated['cantidad_total'] ?? 1;
        $validated['estado'] = EquipoPrestable::ESTADO_DISPONIBLE;

        $equipo = EquipoPrestable::create($validated);

        return $this->success($equipo->load(['almacen', 'producto']), 'Equipo registrado correctamente', 201);
    }

    /**
     * Ver equipo prestable
     */
    public function showEquipo(Request $request, EquipoPrestable $equipo): JsonResponse
    {
        if ($equipo->empresa_id !== $request->user()->empresa_id) {
            return $this->error('No autorizado', 403);
        }

        $equipo->load(['almacen', 'producto', 'prestamosActivos.trabajador']);

        return $this->success($equipo);
    }

    /**
     * Actualizar equipo prestable
     */
    public function updateEquipo(Request $request, EquipoPrestable $equipo): JsonResponse
    {
        if ($equipo->empresa_id !== $request->user()->empresa_id) {
            return $this->error('No autorizado', 403);
        }

        $empresaId = $request->user()->empresa_id;

        $validated = $request->validate([
            'nombre' => ['sometimes', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
            'numero_serie' => ['nullable', 'string', 'max:100'],
            'marca' => ['nullable', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'cantidad_total' => ['sometimes', 'integer', 'min:1'],
            'almacen_id' => ['nullable', Rule::exists('almacenes', 'id')->where(fn($q) => $q->where('empresa_id', $empresaId))],
            'ubicacion_fisica' => ['nullable', 'string', 'max:200'],
            'valor_referencial' => ['nullable', 'numeric', 'min:0'],
            'notas' => ['nullable', 'string'],
            'activo' => ['sometimes', 'boolean'],
        ]);

        $equipo->update($validated);

        return $this->success($equipo->fresh(['almacen', 'producto']), 'Equipo actualizado correctamente');
    }

    /**
     * Eliminar equipo prestable
     */
    public function destroyEquipo(Request $request, EquipoPrestable $equipo): JsonResponse
    {
        if ($equipo->empresa_id !== $request->user()->empresa_id) {
            return $this->error('No autorizado', 403);
        }

        // Verificar que no tenga préstamos activos
        if ($equipo->prestamosActivos()->exists()) {
            return $this->error('No se puede eliminar un equipo con préstamos activos', 422);
        }

        $equipo->delete();

        return $this->success(null, 'Equipo eliminado correctamente');
    }

    /**
     * Obtener equipos disponibles para préstamo
     */
    public function equiposDisponibles(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;
        $buscar = $request->get('buscar');
        $almacenAsignado = $this->getAlmacenAsignado($request);

        $equipos = $this->prestamoService->obtenerEquiposDisponibles($empresaId, $buscar, $almacenAsignado);

        return $this->success($equipos);
    }

    /**
     * Historial de préstamos de un equipo
     */
    public function historialEquipo(Request $request, EquipoPrestable $equipo): JsonResponse
    {
        if ($equipo->empresa_id !== $request->user()->empresa_id) {
            return $this->error('No autorizado', 403);
        }

        $historial = $this->prestamoService->obtenerHistorialEquipo($equipo->id);

        return $this->success($historial);
    }

    /**
     * Obtener familias que califican para préstamos (herramientas, equipos, etc.)
     */
    public function familiasPrestables(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $familias = \App\Modules\Inventario\Models\Familia::where('empresa_id', $empresaId)
            ->where('activo', true)
            ->where(function ($q) {
                $q->where('nombre', 'like', '%herramienta%')
                    ->orWhere('nombre', 'like', '%equipo%')
                    ->orWhere('nombre', 'like', '%maquinaria%')
                    ->orWhere('nombre', 'like', '%instrumento%')
                    ->orWhere('codigo', 'like', '%HER%')
                    ->orWhere('codigo', 'like', '%EQU%')
                    ->orWhere('codigo', 'like', '%MAQ%');
            })
            ->withCount('productos')
            ->get();

        return $this->success($familias);
    }

    /**
     * Obtener productos del inventario disponibles para importar como equipos prestables
     * Solo muestra productos de categorías: herramientas, equipos, equipos menores
     */
    public function productosParaImportar(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;
        $almacenAsignado = $this->getAlmacenAsignado($request);

        // Obtener IDs de productos ya importados (si es almacenero, solo su almacén)
        $productosYaImportados = EquipoPrestable::where('empresa_id', $empresaId)
            ->whereNotNull('producto_id')
            ->when($almacenAsignado, fn($q) => $q->where('almacen_id', $almacenAsignado))
            ->pluck('producto_id')
            ->toArray();

        // Obtener productos que no han sido importados
        // Filtrar SOLO familias de tipo herramientas, equipos, equipos menores (no consumibles)
        $query = Producto::where('empresa_id', $empresaId)
            ->where('activo', true);

        if ($almacenAsignado) {
            $query->whereHas('stockAlmacenes', function ($q) use ($almacenAsignado) {
                $q->where('almacen_id', $almacenAsignado)
                  ->where('stock_actual', '>', 0);
            });
        }

        // Solo aplicar whereNotIn si hay productos ya importados
        if (!empty($productosYaImportados)) {
            $query->whereNotIn('id', $productosYaImportados);
        }

        // Filtrar por familias de herramientas/equipos
        $query->whereHas('familia', function ($q) use ($empresaId) {
            $q->where('empresa_id', $empresaId)
                ->where(function ($sub) {
                    $sub->where('nombre', 'like', '%herramienta%')
                        ->orWhere('nombre', 'like', '%equipo%')
                        ->orWhere('nombre', 'like', '%maquinaria%')
                        ->orWhere('nombre', 'like', '%instrumento%')
                        ->orWhere('codigo', 'like', '%HER%')
                        ->orWhere('codigo', 'like', '%EQU%')
                        ->orWhere('codigo', 'like', '%MAQ%');
                });
        });

        $query->with(['familia:id,nombre', 'stockAlmacenes']);

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('familia_id')) {
            $query->where('familia_id', $request->familia_id);
        }

        $productos = $query->orderBy('nombre')->limit(100)->get();

        return $this->success($productos);
    }

    /**
     * Importar productos del inventario como equipos prestables
     */
    public function importarProductos(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;
        $almacenAsignado = $this->getAlmacenAsignado($request);

        $validated = $request->validate([
            'producto_ids' => ['required', 'array', 'min:1'],
            'producto_ids.*' => ['exists:productos,id'],
            'tipo_control' => ['required', Rule::in(['INDIVIDUAL', 'CANTIDAD'])],
            'almacen_id' => ['nullable', Rule::exists('almacenes', 'id')->where(fn($q) => $q->where('empresa_id', $empresaId))],
        ]);

        if ($almacenAsignado && !empty($validated['almacen_id']) && (int) $validated['almacen_id'] !== (int) $almacenAsignado) {
            return $this->error('No autorizado para importar en otro almacén', 403);
        }

        $almacenObjetivoId = $almacenAsignado ?: ($validated['almacen_id'] ?? null);

        $importados = 0;
        $errores = [];

        foreach ($validated['producto_ids'] as $productoId) {
            $producto = Producto::with('stockAlmacenes')->find($productoId);

            if (!$producto || $producto->empresa_id !== $empresaId) {
                $errores[] = "Producto {$productoId} no encontrado";
                continue;
            }

            // Verificar si ya existe para el mismo almacén objetivo
            $existe = EquipoPrestable::where('empresa_id', $empresaId)
                ->where('producto_id', $productoId)
                ->when($almacenObjetivoId, fn($q) => $q->where('almacen_id', $almacenObjetivoId))
                ->exists();

            if ($existe) {
                $errores[] = "Producto {$producto->nombre} ya está registrado";
                continue;
            }

            $stocks = $almacenObjetivoId
                ? $producto->stockAlmacenes->where('almacen_id', $almacenObjetivoId)
                : $producto->stockAlmacenes;

            $stockDisponible = (int) ($stocks->sum('stock_actual') ?? 0);
            $stockPrincipal = $stocks->sortByDesc('stock_actual')->first();

            if ($stockDisponible <= 0) {
                $errores[] = "Producto {$producto->nombre} sin stock disponible en el almacén seleccionado";
                continue;
            }

            $almacenDestinoId = $almacenObjetivoId ?: ($stockPrincipal?->almacen_id);
            $codigoBase = $producto->codigo;
            $codigo = $codigoBase;
            if (EquipoPrestable::where('codigo', $codigo)->exists()) {
                $codigo = $codigoBase . '-ALM' . ($almacenDestinoId ?? 'X');
            }
            $contador = 2;
            while (EquipoPrestable::where('codigo', $codigo)->exists()) {
                $codigo = $codigoBase . '-' . $empresaId . '-' . $contador;
                $contador++;
            }

            EquipoPrestable::create([
                'empresa_id' => $empresaId,
                'producto_id' => $productoId,
                'codigo' => $codigo,
                'nombre' => $producto->nombre,
                'descripcion' => $producto->descripcion,
                'tipo_control' => $validated['tipo_control'],
                'cantidad_total' => $validated['tipo_control'] === 'CANTIDAD' ? max(1, $stockDisponible) : 1,
                'cantidad_disponible' => $validated['tipo_control'] === 'CANTIDAD' ? max(1, $stockDisponible) : 1,
                'almacen_id' => $almacenDestinoId,
                'estado' => EquipoPrestable::ESTADO_DISPONIBLE,
                'activo' => true,
            ]);

            $importados++;
        }

        return $this->success([
            'importados' => $importados,
            'errores' => $errores,
        ], "Se importaron {$importados} productos como equipos prestables");
    }

    // ==================== PRÉSTAMOS ====================

    /**
     * Listar préstamos
     */
    public function index(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;
        $almacenAsignado = $this->getAlmacenAsignado($request);

        $filtros = [
            'estado' => $request->get('estado'),
            'trabajador_id' => $request->get('trabajador_id'),
            'equipo_id' => $request->get('equipo_id'),
            'fecha_desde' => $request->get('fecha_desde'),
            'fecha_hasta' => $request->get('fecha_hasta'),
            'solo_vencidos' => $request->boolean('solo_vencidos'),
            'almacen_id' => $almacenAsignado ?: $request->get('almacen_id'),
            'per_page' => $this->resolvePerPage($request, 15, 100),
        ];

        $prestamos = $this->prestamoService->obtenerPrestamos($empresaId, $filtros);

        return $this->success($prestamos);
    }

    /**
     * Crear préstamo
     * Acepta equipo_id como ID numérico de equipos_prestables o "producto_{id}" para productos directos
     * Acepta tipo_receptor para distinguir entre trabajadores y usuarios
     */
    public function store(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $validated = $request->validate([
            'equipo_id' => ['required', function ($attribute, $value, $fail) use ($empresaId) {
                // Permitir formato "producto_X" o ID numérico de equipos_prestables
                if (is_string($value) && str_starts_with($value, 'producto_')) {
                    $productoId = str_replace('producto_', '', $value);
                    $producto = is_numeric($productoId)
                        ? \App\Modules\Inventario\Models\Producto::where('empresa_id', $empresaId)->find($productoId)
                        : null;
                    if (!$producto) {
                        $fail('El producto seleccionado no existe.');
                    }
                } else {
                    $equipo = is_numeric($value)
                        ? \App\Modules\Prestamos\Models\EquipoPrestable::where('empresa_id', $empresaId)->find($value)
                        : null;
                    if (!$equipo) {
                        $fail('El equipo seleccionado no existe.');
                    }
                }
            }],
            'cantidad' => ['sometimes', 'integer', 'min:1'],
            'trabajador_id' => ['required', 'integer'],
            'tipo_receptor' => ['sometimes', 'string', Rule::in(['trabajador', 'usuario'])],
            'centro_costo_id' => ['nullable', Rule::exists('centros_costos', 'id')->where(fn($q) => $q->where('empresa_id', $empresaId))],
            'area_destino' => ['nullable', 'string', 'max:200'],
            'fecha_prestamo' => ['sometimes', 'date'],
            'fecha_devolucion_esperada' => ['required', 'date', 'after_or_equal:fecha_prestamo'],
            'motivo_prestamo' => ['nullable', 'string'],
            'observaciones_entrega' => ['nullable', 'string'],
        ]);

        // Validar que el trabajador/usuario exista según tipo_receptor y empresa
        $tipoReceptor = $validated['tipo_receptor'] ?? 'trabajador';
        if ($tipoReceptor === 'usuario') {
            $usuario = \App\Modules\Administracion\Models\Usuario::where('empresa_id', $empresaId)
                ->where('activo', true)
                ->find($validated['trabajador_id']);

            if (!$usuario) {
                return $this->error('El usuario seleccionado no existe.', 422);
            }
        } else {
            $trabajador = \App\Modules\Administracion\Models\Trabajador::where('empresa_id', $empresaId)
                ->where('activo', true)
                ->find($validated['trabajador_id']);

            if (!$trabajador) {
                return $this->error('El trabajador seleccionado no existe.', 422);
            }
        }

        $validated['empresa_id'] = $empresaId;
        $validated['entregado_por'] = $request->user()->id;
        $validated['almacen_id_contexto'] = $this->getAlmacenAsignado($request);

        try {
            $prestamo = $this->prestamoService->crearPrestamo($validated);
            return $this->success($prestamo, 'Préstamo registrado correctamente', 201);
        } catch (\Exception $e) {
            Log::error('Error al crear préstamo', [
                'empresa_id' => $empresaId,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Error interno al registrar el préstamo', 500);
        }
    }

    /**
     * Ver préstamo
     */
    public function show(Request $request, PrestamoEquipo $prestamo): JsonResponse
    {
        if ($prestamo->empresa_id !== $request->user()->empresa_id) {
            return $this->error('No autorizado', 403);
        }

        if (!$this->usuarioPuedeAccederPrestamo($request, $prestamo)) {
            return $this->error('No autorizado para ver préstamos de otro almacén', 403);
        }

        $prestamo->load(['equipo', 'trabajador', 'centroCosto', 'usuarioEntrega', 'usuarioRecepcion']);

        return $this->success($prestamo);
    }

    /**
     * Procesar devolución
     */
    public function devolver(Request $request, PrestamoEquipo $prestamo): JsonResponse
    {
        if ($prestamo->empresa_id !== $request->user()->empresa_id) {
            return $this->error('No autorizado', 403);
        }

        if (!$this->usuarioPuedeAccederPrestamo($request, $prestamo)) {
            return $this->error('No autorizado para procesar préstamos de otro almacén', 403);
        }

        $validated = $request->validate([
            'condicion_devolucion' => ['required', Rule::in(['BUENO', 'REGULAR', 'MALO', 'PERDIDO'])],
            'fecha_devolucion' => ['sometimes', 'date'],
            'observaciones_devolucion' => ['nullable', 'string'],
        ]);

        $validated['recibido_por'] = $request->user()->id;

        try {
            $prestamo = $this->prestamoService->procesarDevolucion($prestamo, $validated);
            $this->prestamoService->regularizarDevolucionInventarioSiFalta($prestamo->fresh(['equipo']));
            return $this->success($prestamo, 'Devolución registrada correctamente');
        } catch (\Exception $e) {
            Log::error('Error al procesar devolución de préstamo', [
                'empresa_id' => $request->user()->empresa_id,
                'user_id' => $request->user()->id,
                'prestamo_id' => $prestamo->id,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Error interno al procesar la devolución', 500);
        }
    }

    /**
     * Renovar préstamo
     */
    public function renovar(Request $request, PrestamoEquipo $prestamo): JsonResponse
    {
        if ($prestamo->empresa_id !== $request->user()->empresa_id) {
            return $this->error('No autorizado', 403);
        }

        if (!$this->usuarioPuedeAccederPrestamo($request, $prestamo)) {
            return $this->error('No autorizado para renovar préstamos de otro almacén', 403);
        }

        $validated = $request->validate([
            'nueva_fecha_devolucion' => ['required', 'date', 'after:today'],
        ]);

        try {
            $prestamo = $this->prestamoService->renovarPrestamo($prestamo, $validated);
            return $this->success($prestamo, 'Préstamo renovado correctamente');
        } catch (\Exception $e) {
            Log::error('Error al renovar préstamo', [
                'empresa_id' => $request->user()->empresa_id,
                'user_id' => $request->user()->id,
                'prestamo_id' => $prestamo->id,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Error interno al renovar el préstamo', 500);
        }
    }

    /**
     * Historial de préstamos de un trabajador
     */
    public function historialTrabajador(Request $request, int $trabajadorId): JsonResponse
    {
        $historial = $this->prestamoService->obtenerHistorialTrabajador($trabajadorId);

        return $this->success($historial);
    }

    /**
     * Estadísticas de préstamos
     */
    public function estadisticas(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;
        $almacenAsignado = $this->getAlmacenAsignado($request);

        $stats = $this->prestamoService->obtenerEstadisticas($empresaId, $almacenAsignado);

        return $this->success($stats);
    }

    /**
     * Obtener personal para préstamos (trabajadores + usuarios)
     * Similar a EPPs - combina ambas tablas
     */
    public function personalParaPrestamos(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;
        $centroCostoId = $request->get('centro_costo_id');
        $almacenId = $request->get('almacen_id');
        $search = $request->get('search');

        $centroCostoAsignado = $this->getCentroCostoAsignado($request);
        if ($centroCostoAsignado) {
            $centroCostoId = $centroCostoAsignado;
        }

        $almacenAsignado = $this->getAlmacenAsignado($request);
        if ($almacenAsignado) {
            $almacenId = $almacenAsignado;
        }

        if (!$centroCostoId && $almacenId) {
            $centroCostoId = \App\Modules\Administracion\Models\Almacen::where('empresa_id', $empresaId)
                ->where('id', $almacenId)
                ->value('centro_costo_id');
        }

        $personal = collect();

        // 1. Cargar trabajadores (tabla trabajadores - sin login)
        $trabajadores = \App\Modules\Administracion\Models\Trabajador::with('centroCosto:id,codigo,nombre')
            ->where('empresa_id', $empresaId)
            ->where('activo', true)
            ->when($centroCostoId, fn($q) => $q->where('centro_costo_id', $centroCostoId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('nombre', 'like', "%{$search}%")
                        ->orWhere('dni', 'like', "%{$search}%")
                        ->orWhere('cargo', 'like', "%{$search}%");
                });
            })
            ->orderBy('nombre')
            ->get();

        foreach ($trabajadores as $trab) {
            $personal->push([
                'id' => $trab->id,
                'tipo' => 'trabajador',
                'nombre' => $trab->nombre,
                'dni' => $trab->dni,
                'cargo' => $trab->cargo,
                'centro_costo' => $trab->centroCosto,
                'display_name' => $trab->nombre . ($trab->cargo ? " ({$trab->cargo})" : ''),
            ]);
        }

        // 2. Cargar usuarios (tabla usuarios - con login)
        $usuarios = \App\Modules\Administracion\Models\Usuario::with('centroCosto:id,codigo,nombre')
            ->where('empresa_id', $empresaId)
            ->where('activo', true)
            ->when($centroCostoId, fn($q) => $q->where('centro_costo_id', $centroCostoId))
            ->when($almacenId, fn($q) => $q->where('almacen_id', $almacenId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('nombre', 'like', "%{$search}%")
                        ->orWhere('dni', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('nombre')
            ->get();

        foreach ($usuarios as $user) {
            $personal->push([
                'id' => $user->id,
                'tipo' => 'usuario',
                'nombre' => $user->nombre,
                'dni' => $user->dni,
                'cargo' => $user->cargo ?? null,
                'centro_costo' => $user->centroCosto,
                'display_name' => $user->nombre . ' [Usuario]',
            ]);
        }

        return $this->success($personal->sortBy('nombre')->values());
    }

    /**
     * Obtener centros de costo filtrados para el usuario actual
     * Para usuarios almacenero, solo muestra su centro de costo
     */
    public function centrosCostoParaPrestamos(Request $request): JsonResponse
    {
        $user = $request->user();
        $empresaId = $user->empresa_id;

        $query = \App\Modules\Administracion\Models\CentroCosto::where('empresa_id', $empresaId)
            ->where('activo', true);

        $almacenAsignado = $this->getAlmacenAsignado($request);
        if ($almacenAsignado) {
            $centroDesdeAlmacen = \App\Modules\Administracion\Models\Almacen::where('empresa_id', $empresaId)
                ->where('id', $almacenAsignado)
                ->value('centro_costo_id');
            if ($centroDesdeAlmacen) {
                $query->where('id', $centroDesdeAlmacen);
                $centros = $query->orderBy('nombre')->get();
                return $this->success($centros);
            }
        }

        // Si el usuario tiene centro_costo_id asignado y NO es admin/gerencia, filtrar
        if ($user->centro_costo_id && !$user->hasAnyRole(['super_admin', 'administrador', 'gerencia'])) {
            $query->where('id', $user->centro_costo_id);
        }

        $centros = $query->orderBy('nombre')->get();

        return $this->success($centros);
    }

    /**
     * Actualizar préstamos vencidos y generar notificaciones
     */
    public function procesarVencidos(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $actualizados = $this->prestamoService->actualizarPrestamosVencidos($empresaId);
        $notificaciones = $this->prestamoService->generarNotificacionesVencidos($empresaId);

        return $this->success([
            'prestamos_actualizados' => $actualizados,
            'notificaciones_generadas' => $notificaciones,
        ]);
    }

    /**
     * Para almaceneros, restringe acceso a préstamos de su almacén asignado.
     */
    private function usuarioPuedeAccederPrestamo(Request $request, PrestamoEquipo $prestamo): bool
    {
        $almacenAsignado = $this->getAlmacenAsignado($request);
        if (!$almacenAsignado) {
            return true;
        }

        $prestamo->loadMissing('equipo:id,almacen_id');
        return (int) ($prestamo->equipo?->almacen_id ?? 0) === (int) $almacenAsignado;
    }
}
