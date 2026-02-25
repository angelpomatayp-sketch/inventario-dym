<?php

namespace App\Modules\Requisiciones\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\Models\Movimiento;
use App\Modules\Inventario\Models\MovimientoDetalle;
use App\Modules\Inventario\Models\StockAlmacen;
use App\Modules\Inventario\Models\Kardex;
use App\Modules\Requisiciones\Models\Requisicion;
use App\Modules\Requisiciones\Models\RequisicionDetalle;
use App\Modules\Requisiciones\Models\ValeSalida;
use App\Modules\Requisiciones\Models\ValeSalidaDetalle;
use App\Modules\Administracion\Models\Almacen;
use App\Modules\Administracion\Models\Trabajador;
use App\Modules\Administracion\Models\Usuario;
use App\Modules\Inventario\Models\Producto;
use App\Shared\Traits\ApiResponse;
use App\Shared\Traits\FiltrosPorRol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ValeSalidaController extends Controller
{
    use ApiResponse, FiltrosPorRol;

    /**
     * Listar vales de salida.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ValeSalida::with([
            'almacen:id,nombre',
            'centroCosto:id,nombre',
            'solicitante:id,nombre',
            'despachador:id,nombre',
            'requisicion:id,numero',
        ])->withCount('detalles');

        // Filtro por empresa
        if ($request->user()->empresa_id) {
            $query->where('empresa_id', $request->user()->empresa_id);
        }

        // Filtro por almacén según rol (almacenero solo ve vales de su almacén)
        $this->aplicarFiltroAlmacen($query, $request);

        // Filtro por centro de costo según rol (asistentes/residentes/solicitantes solo ven su centro de costo)
        $this->aplicarFiltroCentroCosto($query, $request);

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                  ->orWhere('receptor_nombre', 'like', "%{$search}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('almacen_id')) {
            $query->where('almacen_id', $request->almacen_id);
        }

        if ($request->filled('centro_costo_id')) {
            $query->where('centro_costo_id', $request->centro_costo_id);
        }

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
        }

        // Ordenamiento
        $sortField = $request->get('sort_field', 'fecha');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortField, $sortOrder)->orderBy('id', 'desc');

        // Paginacion
        $perPage = $this->resolvePerPage($request, 15, 100);
        $vales = $query->paginate($perPage);

        return $this->paginated($vales);
    }

    /**
     * Crear vale de salida.
     */
    public function store(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $request->validate([
            'almacen_id' => [
                'required',
                Rule::exists('almacenes', 'id')->where(fn($q) => $q->where('empresa_id', $empresaId)),
            ],
            'centro_costo_id' => [
                'required',
                Rule::exists('centros_costos', 'id')->where(fn($q) => $q->where('empresa_id', $empresaId)),
            ],
            'fecha' => 'required|date',
            'requisicion_id' => [
                'nullable',
                Rule::exists('requisiciones', 'id')->where(fn($q) => $q->where('empresa_id', $empresaId)),
            ],
            'receptor_id' => 'nullable|integer',
            'receptor_tipo' => 'nullable|in:trabajador,usuario',
            'receptor_nombre' => 'nullable|string|max:200',
            'receptor_dni' => 'nullable|string|max:15',
            'motivo' => 'nullable|string|max:500',
            'observaciones' => 'nullable|string|max:1000',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => [
                'required',
                Rule::exists('productos', 'id')->where(fn($q) => $q->where('empresa_id', $empresaId)),
            ],
            'detalles.*.cantidad_solicitada' => 'required|numeric|min:0.01',
            'detalles.*.requisicion_detalle_id' => 'nullable|exists:requisiciones_detalle,id',
        ], [
            'almacen_id.required' => 'El almacen es requerido',
            'centro_costo_id.required' => 'El centro de costo es requerido',
            'fecha.required' => 'La fecha es requerida',
            'detalles.required' => 'Debe agregar al menos un producto',
        ]);

        // Validar acceso por almacén (almacenero solo puede despachar de su almacén)
        $almacenAsignado = $this->getAlmacenAsignado($request);
        if ($almacenAsignado && $request->almacen_id != $almacenAsignado) {
            return $this->error('Solo puede crear vales de salida para su almacén asignado', 403);
        }

        // Validar acceso por centro de costo (usuarios restringidos solo para su centro de costo)
        $centroCostoAsignado = $this->getCentroCostoAsignado($request);
        if ($centroCostoAsignado && $request->centro_costo_id != $centroCostoAsignado) {
            return $this->error('Solo puede crear vales de salida para su centro de costo asignado', 403);
        }

        try {
            DB::beginTransaction();
            $receptor = $this->resolverReceptor($request, $empresaId, $request->centro_costo_id, $request->almacen_id);

            // Generar numero
            $numero = $this->generarNumero($empresaId);

            // Crear vale
            $vale = ValeSalida::create([
                'empresa_id' => $empresaId,
                'numero' => $numero,
                'requisicion_id' => $request->requisicion_id,
                'almacen_id' => $request->almacen_id,
                'centro_costo_id' => $request->centro_costo_id,
                'solicitante_id' => $request->user()->id,
                'despachador_id' => $request->user()->id,
                'receptor_id' => $receptor['receptor_id'],
                'fecha' => $request->fecha,
                'estado' => ValeSalida::ESTADO_PENDIENTE,
                'receptor_nombre' => $receptor['receptor_nombre'],
                'receptor_dni' => $receptor['receptor_dni'],
                'motivo' => $request->motivo,
                'observaciones' => $request->observaciones,
            ]);

            // Crear detalles
            foreach ($request->detalles as $detalle) {
                ValeSalidaDetalle::create([
                    'vale_salida_id' => $vale->id,
                    'producto_id' => $detalle['producto_id'],
                    'requisicion_detalle_id' => $detalle['requisicion_detalle_id'] ?? null,
                    'cantidad_solicitada' => $detalle['cantidad_solicitada'],
                    'cantidad_entregada' => 0,
                ]);
            }

            DB::commit();

            $vale->load(['detalles.producto', 'almacen', 'centroCosto', 'solicitante']);

            return $this->created($vale, 'Vale de salida creado exitosamente');

        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            return $this->error($e->getMessage(), 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear vale de salida', [
                'empresa_id' => $empresaId,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError('Error interno al crear el vale de salida');
        }
    }

    /**
     * Crear vale desde requisicion aprobada.
     */
    public function crearDesdeRequisicion(Request $request, Requisicion $requisicion): JsonResponse
    {
        if ($requisicion->estado !== Requisicion::ESTADO_APROBADA &&
            $requisicion->estado !== Requisicion::ESTADO_PARCIAL) {
            return $this->error('La requisicion debe estar aprobada para generar un vale de salida', 422);
        }

        $empresaId = $request->user()->empresa_id;

        $request->validate([
            'almacen_id' => [
                'required',
                Rule::exists('almacenes', 'id')->where(fn($q) => $q->where('empresa_id', $empresaId)),
            ],
            'receptor_id' => 'nullable|integer',
            'receptor_tipo' => 'nullable|in:trabajador,usuario',
            'receptor_nombre' => 'nullable|string|max:200',
            'receptor_dni' => 'nullable|string|max:15',
            'observaciones' => 'nullable|string|max:1000',
            'detalles' => 'required|array|min:1',
            'detalles.*.requisicion_detalle_id' => 'required|exists:requisiciones_detalle,id',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
        ]);

        // Validar acceso por almacén (almacenero solo puede despachar de su almacén)
        $almacenAsignado = $this->getAlmacenAsignado($request);
        if ($almacenAsignado && $request->almacen_id != $almacenAsignado) {
            return $this->error('Solo puede crear vales de salida para su almacén asignado', 403);
        }

        // Validar acceso por centro de costo según rol
        $centroCostoAsignado = $this->getCentroCostoAsignado($request);
        if ($centroCostoAsignado && $requisicion->centro_costo_id != $centroCostoAsignado) {
            return $this->error('Solo puede crear vales para requisiciones de su centro de costo asignado', 403);
        }

        try {
            DB::beginTransaction();
            $receptor = $this->resolverReceptor($request, $empresaId, $requisicion->centro_costo_id, $request->almacen_id);

            $numero = $this->generarNumero($empresaId);

            $vale = ValeSalida::create([
                'empresa_id' => $empresaId,
                'numero' => $numero,
                'requisicion_id' => $requisicion->id,
                'almacen_id' => $request->almacen_id,
                'centro_costo_id' => $requisicion->centro_costo_id,
                'solicitante_id' => $requisicion->solicitante_id,
                'despachador_id' => $request->user()->id,
                'receptor_id' => $receptor['receptor_id'],
                'fecha' => now()->toDateString(),
                'estado' => ValeSalida::ESTADO_PENDIENTE,
                'receptor_nombre' => $receptor['receptor_nombre'],
                'receptor_dni' => $receptor['receptor_dni'],
                'motivo' => "Atencion de requisicion {$requisicion->numero}",
                'observaciones' => $request->observaciones,
            ]);

            foreach ($request->detalles as $detalle) {
                $reqDetalle = RequisicionDetalle::find($detalle['requisicion_detalle_id']);

                if ($reqDetalle && $reqDetalle->requisicion_id === $requisicion->id) {
                    ValeSalidaDetalle::create([
                        'vale_salida_id' => $vale->id,
                        'producto_id' => $reqDetalle->producto_id,
                        'requisicion_detalle_id' => $reqDetalle->id,
                        'cantidad_solicitada' => $detalle['cantidad'],
                        'cantidad_entregada' => 0,
                    ]);
                }
            }

            DB::commit();

            $vale->load(['detalles.producto', 'almacen', 'centroCosto', 'requisicion']);

            return $this->created($vale, 'Vale de salida creado desde requisicion');

        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            return $this->error($e->getMessage(), 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear vale desde requisición', [
                'empresa_id' => $empresaId,
                'user_id' => $request->user()->id,
                'requisicion_id' => $requisicion->id,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError('Error interno al crear el vale desde requisición');
        }
    }

    /**
     * Mostrar vale de salida.
     */
    public function show(Request $request, ValeSalida $valeSalida): JsonResponse
    {
        if ($valeSalida->empresa_id !== $request->user()->empresa_id) {
            return $this->error('No autorizado', 403);
        }

        $valeSalida->load([
            'detalles.producto.familia',
            'almacen',
            'centroCosto',
            'solicitante',
            'despachador',
            'receptor',
            'requisicion',
            'movimiento',
            'anulador',
        ]);

        return $this->success($valeSalida);
    }

    /**
     * Entregar vale (procesar salida de inventario).
     */
    public function entregar(Request $request, ValeSalida $valeSalida): JsonResponse
    {
        if ($valeSalida->empresa_id !== $request->user()->empresa_id) {
            return $this->error('No autorizado', 403);
        }

        if ($valeSalida->estado === ValeSalida::ESTADO_ENTREGADO) {
            return $this->error('Este vale ya fue entregado completamente', 422);
        }

        if ($valeSalida->estado === ValeSalida::ESTADO_ANULADO) {
            return $this->error('Este vale esta anulado', 422);
        }

        $request->validate([
            'entregas' => 'required|array|min:1',
            'entregas.*.detalle_id' => 'required|exists:vales_salida_detalle,id',
            'entregas.*.cantidad' => 'required|numeric|min:0',
        ]);

        $empresaId = $request->user()->empresa_id;

        try {
            DB::beginTransaction();

            $detallesMovimiento = [];
            $hayEntregas = false;

            foreach ($request->entregas as $entrega) {
                $detalle = ValeSalidaDetalle::where('id', $entrega['detalle_id'])
                    ->where('vale_salida_id', $valeSalida->id)
                    ->first();

                if (!$detalle || $entrega['cantidad'] <= 0) {
                    continue;
                }

                // Verificar stock disponible
                $stockAlmacen = StockAlmacen::where('empresa_id', $empresaId)
                    ->where('producto_id', $detalle->producto_id)
                    ->where('almacen_id', $valeSalida->almacen_id)
                    ->first();

                if (!$stockAlmacen || $stockAlmacen->stock_actual < $entrega['cantidad']) {
                    $producto = Producto::select('codigo', 'nombre')->find($detalle->producto_id);
                    $nombreProducto = $producto
                        ? "{$producto->codigo} - {$producto->nombre}"
                        : "ID {$detalle->producto_id}";
                    throw new \InvalidArgumentException("Stock insuficiente para {$nombreProducto} en el almacén del vale.");
                }

                // Actualizar detalle del vale
                $costoUnitario = $stockAlmacen->costo_promedio;
                $costoTotal = $entrega['cantidad'] * $costoUnitario;

                $detalle->update([
                    'cantidad_entregada' => $detalle->cantidad_entregada + $entrega['cantidad'],
                    'costo_unitario' => $costoUnitario,
                    'costo_total' => $detalle->costo_total + $costoTotal,
                ]);

                $detallesMovimiento[] = [
                    'producto_id' => $detalle->producto_id,
                    'cantidad' => $entrega['cantidad'],
                    'costo_unitario' => $costoUnitario,
                    'costo_total' => $costoTotal,
                ];

                // Actualizar requisicion detalle si existe
                if ($detalle->requisicion_detalle_id) {
                    $reqDetalle = RequisicionDetalle::find($detalle->requisicion_detalle_id);
                    if ($reqDetalle) {
                        $reqDetalle->registrarEntrega($entrega['cantidad']);
                    }
                }

                $hayEntregas = true;
            }

            if (!$hayEntregas) {
                throw new \InvalidArgumentException('No hay entregas válidas para procesar');
            }

            // Crear movimiento de salida
            $movimiento = $this->crearMovimientoSalida(
                $empresaId,
                $valeSalida,
                $detallesMovimiento,
                $request->user()->id
            );

            // Actualizar vale con referencia al movimiento
            $valeSalida->update(['movimiento_id' => $movimiento->id]);

            // Determinar estado del vale
            $valeSalida->load('detalles');
            $todoEntregado = $valeSalida->detalles->every(fn($d) => $d->cantidad_entregada >= $d->cantidad_solicitada);

            if ($todoEntregado) {
                $valeSalida->marcarEntregado();

                // Actualizar estado de requisicion si aplica
                if ($valeSalida->requisicion_id) {
                    $this->actualizarEstadoRequisicion($valeSalida->requisicion_id);
                }
            } else {
                $valeSalida->marcarParcial();
            }

            DB::commit();

            return $this->success($valeSalida->fresh(), 'Entrega procesada exitosamente');

        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            return $this->error($e->getMessage(), 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al procesar entrega de vale de salida', [
                'empresa_id' => $empresaId,
                'user_id' => $request->user()->id,
                'vale_salida_id' => $valeSalida->id,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError('Error interno al procesar la entrega');
        }
    }

    /**
     * Anular vale de salida.
     */
    public function anular(Request $request, ValeSalida $valeSalida): JsonResponse
    {
        if ($valeSalida->empresa_id !== $request->user()->empresa_id) {
            return $this->error('No autorizado', 403);
        }

        if (!$valeSalida->puedeAnularse()) {
            return $this->error('Este vale no puede ser anulado', 422);
        }

        // Si ya hubo entregas, no se puede anular
        $tieneEntregas = $valeSalida->detalles()->where('cantidad_entregada', '>', 0)->exists();
        if ($tieneEntregas) {
            return $this->error('No se puede anular un vale con entregas realizadas', 422);
        }

        $valeSalida->anular($request->user()->id);

        return $this->success($valeSalida, 'Vale anulado exitosamente');
    }

    /**
     * Obtener requisiciones aprobadas para generar vales.
     */
    public function requisicionesAprobadas(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $query = Requisicion::with(['detalles.producto', 'solicitante', 'centroCosto'])
            ->where('empresa_id', $empresaId)
            ->whereIn('estado', [Requisicion::ESTADO_APROBADA, Requisicion::ESTADO_PARCIAL])
            ->orderBy('fecha_solicitud', 'desc');

        // Aplicar filtro por centro de costo según rol del usuario
        $this->aplicarFiltroCentroCosto($query, $request);

        $requisiciones = $query->get()
            ->map(function ($req) {
                // Calcular pendientes de cada detalle
                $req->detalles->each(function ($det) {
                    $det->pendiente = max(0, ($det->cantidad_aprobada ?? $det->cantidad_solicitada) - $det->cantidad_entregada);
                });
                return $req;
            });

        return $this->success($requisiciones);
    }

    /**
     * Obtener receptores disponibles para vales (trabajadores + usuarios).
     * Filtro principal: centro de costo. Filtro adicional: almacén (usuarios).
     */
    public function personalReceptores(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;
        $centroCostoId = $request->get('centro_costo_id');
        $almacenId = $request->get('almacen_id');
        $search = $request->get('search');

        // Si el usuario está restringido por rol, sus asignaciones prevalecen
        $centroCostoAsignado = $this->getCentroCostoAsignado($request);
        if ($centroCostoAsignado) {
            $centroCostoId = $centroCostoAsignado;
        }

        $almacenAsignado = $this->getAlmacenAsignado($request);
        if ($almacenAsignado) {
            $almacenId = $almacenAsignado;
        }

        // Si hay almacén y no llega centro de costo, inferir centro desde el almacén.
        if (!$centroCostoId && $almacenId) {
            $centroCostoId = Almacen::where('empresa_id', $empresaId)
                ->where('id', $almacenId)
                ->value('centro_costo_id');
        }

        $personal = collect();

        $trabajadores = Trabajador::with('centroCosto:id,codigo,nombre')
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
                'centro_costo_id' => $trab->centro_costo_id,
                'almacen_id' => null,
                'display_name' => $trab->nombre . ($trab->cargo ? " ({$trab->cargo})" : ''),
            ]);
        }

        $usuarios = Usuario::with('centroCosto:id,codigo,nombre')
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
                'cargo' => null,
                'centro_costo_id' => $user->centro_costo_id,
                'almacen_id' => $user->almacen_id,
                'display_name' => $user->nombre . ' [Usuario]',
            ]);
        }

        return $this->success($personal->sortBy('nombre')->values());
    }

    /**
     * Obtener estadisticas.
     */
    public function estadisticas(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $stats = [
            'total' => ValeSalida::where('empresa_id', $empresaId)->count(),
            'pendientes' => ValeSalida::where('empresa_id', $empresaId)
                ->where('estado', ValeSalida::ESTADO_PENDIENTE)->count(),
            'entregados_hoy' => ValeSalida::where('empresa_id', $empresaId)
                ->where('estado', ValeSalida::ESTADO_ENTREGADO)
                ->whereDate('fecha', today())->count(),
            'valor_mes' => ValeSalidaDetalle::whereHas('valeSalida', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId)
                  ->whereMonth('fecha', now()->month)
                  ->whereYear('fecha', now()->year)
                  ->where('estado', ValeSalida::ESTADO_ENTREGADO);
            })->sum('costo_total'),
        ];

        return $this->success($stats);
    }

    /**
     * Crear movimiento de salida en inventario.
     */
    private function crearMovimientoSalida(
        int $empresaId,
        ValeSalida $vale,
        array $detalles,
        int $usuarioId
    ): Movimiento {
        // Generar numero de movimiento
        $año = date('Y');
        $mes = date('m');
        $prefijo = "SAL-{$año}{$mes}-";
        $ultimoNumero = Movimiento::where('empresa_id', $empresaId)
            ->where('tipo', Movimiento::TIPO_SALIDA)
            ->where('numero', 'like', $prefijo . '%')
            ->orderBy('numero', 'desc')
            ->lockForUpdate()
            ->value('numero');

        $secuencia = 1;
        if ($ultimoNumero) {
            $partes = explode('-', $ultimoNumero);
            $secuencia = (int) end($partes) + 1;
        }

        $numeroMov = $prefijo . str_pad($secuencia, 6, '0', STR_PAD_LEFT);
        while (Movimiento::where('empresa_id', $empresaId)->where('numero', $numeroMov)->exists()) {
            $secuencia++;
            $numeroMov = $prefijo . str_pad($secuencia, 6, '0', STR_PAD_LEFT);
        }

        // Crear movimiento
        $movimiento = Movimiento::create([
            'empresa_id' => $empresaId,
            'numero' => $numeroMov,
            'tipo' => Movimiento::TIPO_SALIDA,
            'subtipo' => 'VALE_SALIDA',
            'almacen_origen_id' => $vale->almacen_id,
            'centro_costo_id' => $vale->centro_costo_id,
            'usuario_id' => $usuarioId,
            'referencia_tipo' => ValeSalida::class,
            'referencia_id' => $vale->id,
            'fecha' => $vale->fecha,
            'documento_referencia' => $vale->numero,
            'observaciones' => "Vale de salida {$vale->numero}",
            'estado' => Movimiento::ESTADO_COMPLETADO,
        ]);

        // Crear detalles y actualizar stock
        foreach ($detalles as $det) {
            MovimientoDetalle::create([
                'movimiento_id' => $movimiento->id,
                'producto_id' => $det['producto_id'],
                'cantidad' => $det['cantidad'],
                'costo_unitario' => $det['costo_unitario'],
                'costo_total' => $det['costo_total'],
            ]);

            // Decrementar stock
            $stockAlmacen = StockAlmacen::where('empresa_id', $empresaId)
                ->where('producto_id', $det['producto_id'])
                ->where('almacen_id', $vale->almacen_id)
                ->first();

            $nuevoStock = $stockAlmacen->stock_actual - $det['cantidad'];
            $stockAlmacen->update(['stock_actual' => $nuevoStock]);

            // Registrar en Kardex
            Kardex::create([
                'empresa_id' => $empresaId,
                'producto_id' => $det['producto_id'],
                'almacen_id' => $vale->almacen_id,
                'movimiento_id' => $movimiento->id,
                'fecha' => $vale->fecha,
                'tipo_operacion' => Kardex::TIPO_SALIDA,
                'documento_referencia' => $vale->numero,
                'cantidad' => $det['cantidad'],
                'costo_unitario' => $det['costo_unitario'],
                'costo_total' => $det['costo_total'],
                'saldo_cantidad' => $nuevoStock,
                'saldo_costo_unitario' => $stockAlmacen->costo_promedio,
                'saldo_costo_total' => $nuevoStock * $stockAlmacen->costo_promedio,
                'descripcion' => "Vale de salida {$vale->numero}",
            ]);
        }

        return $movimiento;
    }

    /**
     * Actualizar estado de la requisicion segun entregas.
     */
    private function actualizarEstadoRequisicion(int $requisicionId): void
    {
        $requisicion = Requisicion::with('detalles')->find($requisicionId);

        if (!$requisicion) return;

        $todoEntregado = $requisicion->detalles->every(function ($det) {
            $cantidadBase = $det->cantidad_aprobada ?? $det->cantidad_solicitada;
            return $det->cantidad_entregada >= $cantidadBase;
        });

        if ($todoEntregado) {
            $requisicion->update(['estado' => Requisicion::ESTADO_COMPLETADA]);
        } else {
            $hayAlgoEntregado = $requisicion->detalles->some(fn($det) => $det->cantidad_entregada > 0);
            if ($hayAlgoEntregado && $requisicion->estado !== Requisicion::ESTADO_PARCIAL) {
                $requisicion->update(['estado' => Requisicion::ESTADO_PARCIAL]);
            }
        }
    }

    /**
     * Generar numero de vale.
     */
    private function generarNumero(int $empresaId): string
    {
        $año = date('Y');
        $mes = date('m');
        $prefijo = "VS-{$año}{$mes}-";

        $ultimoNumero = ValeSalida::where('empresa_id', $empresaId)
            ->where('numero', 'like', $prefijo . '%')
            ->orderBy('numero', 'desc')
            ->lockForUpdate()
            ->value('numero');

        $secuencia = 1;
        if ($ultimoNumero) {
            $partes = explode('-', $ultimoNumero);
            $secuencia = (int) end($partes) + 1;
        }

        $numero = $prefijo . str_pad($secuencia, 6, '0', STR_PAD_LEFT);
        while (ValeSalida::where('empresa_id', $empresaId)->where('numero', $numero)->exists()) {
            $secuencia++;
            $numero = $prefijo . str_pad($secuencia, 6, '0', STR_PAD_LEFT);
        }

        return $numero;
    }

    /**
     * Resuelve y valida el receptor seleccionado o manual.
     */
    private function resolverReceptor(Request $request, int $empresaId, int $centroCostoId, int $almacenId): array
    {
        $receptorId = $request->input('receptor_id');
        $receptorTipo = $request->input('receptor_tipo');
        $receptorNombre = trim((string) $request->input('receptor_nombre', ''));
        $receptorDni = $request->input('receptor_dni');

        if ($receptorId && $receptorTipo) {
            if ($receptorTipo === 'usuario') {
                $usuario = Usuario::where('empresa_id', $empresaId)
                    ->where('activo', true)
                    ->where('id', $receptorId)
                    ->where('centro_costo_id', $centroCostoId)
                    ->when($almacenId, fn($q) => $q->where('almacen_id', $almacenId))
                    ->first();

                if (!$usuario) {
                    throw new \InvalidArgumentException('El usuario receptor no es válido para el centro de costo/almacén seleccionado');
                }

                return [
                    'receptor_id' => $usuario->id,
                    'receptor_nombre' => $usuario->nombre,
                    'receptor_dni' => $usuario->dni ?: $receptorDni,
                ];
            }

            $trabajador = Trabajador::where('empresa_id', $empresaId)
                ->where('activo', true)
                ->where('id', $receptorId)
                ->where('centro_costo_id', $centroCostoId)
                ->first();

            if (!$trabajador) {
                throw new \InvalidArgumentException('El trabajador receptor no es válido para el centro de costo seleccionado');
            }

            return [
                'receptor_id' => null,
                'receptor_nombre' => $trabajador->nombre,
                'receptor_dni' => $trabajador->dni ?: $receptorDni,
            ];
        }

        if ($receptorNombre === '') {
            throw new \InvalidArgumentException('Seleccione un receptor');
        }

        return [
            'receptor_id' => null,
            'receptor_nombre' => $receptorNombre,
            'receptor_dni' => $receptorDni,
        ];
    }
}
