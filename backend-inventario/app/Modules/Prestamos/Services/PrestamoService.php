<?php

namespace App\Modules\Prestamos\Services;

use App\Modules\Prestamos\Models\EquipoPrestable;
use App\Modules\Prestamos\Models\PrestamoEquipo;
use App\Modules\Inventario\Models\Movimiento;
use App\Modules\Inventario\Models\StockAlmacen;
use App\Modules\Inventario\Models\Kardex;
use App\Modules\Notificaciones\Models\Notificacion;
use Illuminate\Support\Facades\DB;
use Exception;

class PrestamoService
{
    /**
     * Crear un nuevo préstamo
     * Soporta equipo_id como ID de EquipoPrestable o "producto_{id}" para productos directos
     */
    public function crearPrestamo(array $data): PrestamoEquipo
    {
        return DB::transaction(function () use ($data) {
            $equipoId = $data['equipo_id'];
            $cantidad = $data['cantidad'] ?? 1;
            $almacenContextoId = $data['almacen_id_contexto'] ?? null;

            // Verificar si es un producto directo (formato: "producto_123")
            if (is_string($equipoId) && str_starts_with($equipoId, 'producto_')) {
                $productoId = (int) str_replace('producto_', '', $equipoId);
                $equipo = $this->crearEquipoPrestableDesdeProducto($productoId, $data['empresa_id'], $almacenContextoId);
            } else {
                $equipo = EquipoPrestable::findOrFail($equipoId);
                if ($equipo->empresa_id !== $data['empresa_id']) {
                    throw new Exception("El equipo seleccionado no pertenece a la empresa.");
                }
                if ($almacenContextoId && (int) $equipo->almacen_id !== (int) $almacenContextoId) {
                    throw new Exception("El equipo seleccionado no pertenece al almacén asignado.");
                }
            }

            // Verificar disponibilidad
            if (!$equipo->estaDisponible($cantidad)) {
                throw new Exception("El equipo '{$equipo->nombre}' no está disponible para préstamo.");
            }

            // Asegurar almacén para equipos vinculados a inventario
            if ($equipo->producto_id && !$equipo->almacen_id) {
                $stockPrincipal = StockAlmacen::where('empresa_id', $data['empresa_id'])
                    ->where('producto_id', $equipo->producto_id)
                    ->orderByDesc('stock_actual')
                    ->first();

                if ($stockPrincipal) {
                    $equipo->almacen_id = $stockPrincipal->almacen_id;
                    $equipo->save();
                }
            }

            // Crear el préstamo
            $prestamo = PrestamoEquipo::create([
                'empresa_id' => $data['empresa_id'],
                'equipo_id' => $equipo->id,
                'cantidad' => $cantidad,
                'trabajador_id' => $data['trabajador_id'],
                'tipo_receptor' => $data['tipo_receptor'] ?? PrestamoEquipo::TIPO_TRABAJADOR,
                'centro_costo_id' => $data['centro_costo_id'] ?? null,
                'area_destino' => $data['area_destino'] ?? null,
                'fecha_prestamo' => $data['fecha_prestamo'] ?? now(),
                'fecha_devolucion_esperada' => $data['fecha_devolucion_esperada'],
                'estado' => PrestamoEquipo::ESTADO_ACTIVO,
                'entregado_por' => $data['entregado_por'],
                'motivo_prestamo' => $data['motivo_prestamo'] ?? null,
                'observaciones_entrega' => $data['observaciones_entrega'] ?? null,
            ]);

            // Reducir disponibilidad del equipo
            $equipo->reducirDisponibilidad($cantidad);

            // Si está vinculado a inventario, generar movimiento de salida
            if ($equipo->producto_id) {
                $this->generarMovimientoSalida($prestamo, $equipo);
            }

            // Cargar relaciones según tipo_receptor
            $prestamo->load(['equipo', 'centroCosto']);
            if ($prestamo->tipo_receptor === PrestamoEquipo::TIPO_USUARIO) {
                $prestamo->load('trabajadorUsuario');
            } else {
                $prestamo->load('trabajador');
            }

            return $prestamo;
        });
    }

    /**
     * Procesar devolución
     */
    public function procesarDevolucion(PrestamoEquipo $prestamo, array $data): PrestamoEquipo
    {
        return DB::transaction(function () use ($prestamo, $data) {
            if (!in_array($prestamo->estado, [PrestamoEquipo::ESTADO_ACTIVO, PrestamoEquipo::ESTADO_VENCIDO])) {
                throw new Exception("El préstamo no puede ser devuelto en su estado actual: {$prestamo->estado}");
            }

            $condicion = $data['condicion_devolucion'] ?? PrestamoEquipo::CONDICION_BUENO;
            $equipo = $prestamo->equipo;

            // Determinar estado final según condición
            $estadoFinal = match ($condicion) {
                PrestamoEquipo::CONDICION_PERDIDO => PrestamoEquipo::ESTADO_PERDIDO,
                PrestamoEquipo::CONDICION_MALO => PrestamoEquipo::ESTADO_DANADO,
                default => PrestamoEquipo::ESTADO_DEVUELTO,
            };

            // Actualizar préstamo
            $prestamo->update([
                'estado' => $estadoFinal,
                'fecha_devolucion_real' => $data['fecha_devolucion'] ?? now(),
                'condicion_devolucion' => $condicion,
                'recibido_por' => $data['recibido_por'],
                'observaciones_devolucion' => $data['observaciones_devolucion'] ?? null,
            ]);

            // Manejar stock según condición
            if ($condicion !== PrestamoEquipo::CONDICION_PERDIDO) {
                // Aumentar disponibilidad (incluso si está dañado, vuelve al inventario)
                $equipo->aumentarDisponibilidad($prestamo->cantidad);

                // Si está muy dañado, marcar equipo para mantenimiento
                if ($condicion === PrestamoEquipo::CONDICION_MALO) {
                    $equipo->update(['estado' => EquipoPrestable::ESTADO_MANTENIMIENTO]);
                }

                // Generar movimiento de entrada en inventario
                if ($equipo->producto_id) {
                    $this->generarMovimientoEntrada($prestamo, $equipo, $condicion);
                }
            } else {
                // Si está perdido, reducir cantidad total (para control por cantidad)
                if ($equipo->tipo_control === EquipoPrestable::TIPO_CANTIDAD) {
                    $equipo->update([
                        'cantidad_total' => max(0, $equipo->cantidad_total - $prestamo->cantidad)
                    ]);
                } else {
                    // Para individual, dar de baja
                    $equipo->update(['estado' => EquipoPrestable::ESTADO_BAJA, 'activo' => false]);
                }
            }

            return $prestamo->fresh(['equipo', 'trabajador']);
        });
    }

    /**
     * Si por cualquier motivo faltó el movimiento de entrada de devolución, lo regulariza.
     */
    public function regularizarDevolucionInventarioSiFalta(PrestamoEquipo $prestamo): void
    {
        if (!in_array($prestamo->estado, [PrestamoEquipo::ESTADO_DEVUELTO, PrestamoEquipo::ESTADO_DANADO])) {
            return;
        }

        $existeEntrada = Movimiento::where('empresa_id', $prestamo->empresa_id)
            ->where('tipo', Movimiento::TIPO_ENTRADA)
            ->where('subtipo', 'DEVOLUCION_PRESTAMO')
            ->where('referencia_tipo', 'prestamos_equipos')
            ->where('referencia_id', $prestamo->id)
            ->exists();

        if ($existeEntrada) {
            return;
        }

        $equipo = $prestamo->equipo;
        if (!$equipo) {
            throw new Exception("No se encontró el equipo del préstamo {$prestamo->id} para regularizar devolución.");
        }

        $condicion = $prestamo->condicion_devolucion ?? PrestamoEquipo::CONDICION_BUENO;
        $this->generarMovimientoEntrada($prestamo, $equipo, $condicion);
    }

    /**
     * Renovar préstamo (extender fecha)
     */
    public function renovarPrestamo(PrestamoEquipo $prestamo, array $data): PrestamoEquipo
    {
        if (!in_array($prestamo->estado, [PrestamoEquipo::ESTADO_ACTIVO, PrestamoEquipo::ESTADO_VENCIDO])) {
            throw new Exception("Solo se pueden renovar préstamos activos o vencidos.");
        }

        // Guardar fecha original si es primera renovación
        $fechaOriginal = $prestamo->fecha_devolucion_original ?? $prestamo->fecha_devolucion_esperada;

        $prestamo->update([
            'fecha_devolucion_original' => $fechaOriginal,
            'fecha_devolucion_esperada' => $data['nueva_fecha_devolucion'],
            'estado' => PrestamoEquipo::ESTADO_ACTIVO, // Reactiva si estaba vencido
            'numero_renovaciones' => $prestamo->numero_renovaciones + 1,
        ]);

        return $prestamo->fresh();
    }

    /**
     * Obtener préstamos con filtros
     */
    public function obtenerPrestamos(int $empresaId, array $filtros = [])
    {
        $query = PrestamoEquipo::where('empresa_id', $empresaId)
            ->with(['equipo', 'trabajador', 'trabajadorUsuario', 'centroCosto', 'usuarioEntrega']);

        if (!empty($filtros['almacen_id'])) {
            $query->whereHas('equipo', function ($q) use ($filtros) {
                $q->where('almacen_id', $filtros['almacen_id']);
            });
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['trabajador_id'])) {
            $query->where('trabajador_id', $filtros['trabajador_id']);
        }

        if (!empty($filtros['equipo_id'])) {
            $query->where('equipo_id', $filtros['equipo_id']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->where('fecha_prestamo', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->where('fecha_prestamo', '<=', $filtros['fecha_hasta']);
        }

        if (!empty($filtros['solo_vencidos'])) {
            $query->vencidos();
        }

        $perPage = (int) ($filtros['per_page'] ?? 15);
        $perPage = $perPage > 0 ? min($perPage, 100) : 15;

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Obtener equipos disponibles
     * Incluye equipos prestables registrados + productos de familias prestables con stock
     */
    public function obtenerEquiposDisponibles(int $empresaId, ?string $buscar = null, ?int $almacenId = null)
    {
        $resultado = collect();

        // 1. Equipos prestables ya registrados
        $query = EquipoPrestable::where('empresa_id', $empresaId)
            ->activos()
            ->with('almacen');

        if ($almacenId) {
            $query->where('almacen_id', $almacenId);
        }

        if ($buscar) {
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%")
                    ->orWhere('numero_serie', 'like', "%{$buscar}%");
            });
        }

        $equiposRegistrados = $query->get()->map(function ($equipo) {
            return [
                'id' => $equipo->id,
                'codigo' => $equipo->codigo,
                'nombre' => $equipo->nombre,
                'tipo_control' => $equipo->tipo_control,
                'cantidad_disponible' => $equipo->cantidad_disponible,
                'cantidad_total' => $equipo->cantidad_total,
                'estado' => $equipo->estado,
                'disponible' => $equipo->estaDisponible(),
                'almacen' => $equipo->almacen?->nombre,
                'fuente' => 'equipo_prestable',
                'producto_id' => $equipo->producto_id,
            ];
        });

        $resultado = $resultado->merge($equiposRegistrados);

        // 2. Productos de familias prestables (Equipos, Herramientas) que NO están ya registrados
        $productosYaRegistrados = EquipoPrestable::where('empresa_id', $empresaId)
            ->whereNotNull('producto_id')
            ->when($almacenId, fn($q) => $q->where('almacen_id', $almacenId))
            ->pluck('producto_id')
            ->toArray();

        $queryProductos = \App\Modules\Inventario\Models\Producto::where('empresa_id', $empresaId)
            ->where('activo', true)
            ->with(['familia:id,nombre', 'stockAlmacenes.almacen:id,nombre']);

        if ($almacenId) {
            $queryProductos->whereHas('stockAlmacenes', function ($q) use ($almacenId) {
                $q->where('almacen_id', $almacenId)
                  ->where('stock_actual', '>', 0);
            });
        }

        // Solo excluir si hay productos ya registrados
        if (!empty($productosYaRegistrados)) {
            $queryProductos->whereNotIn('id', $productosYaRegistrados);
        }

        // Filtrar por familias de herramientas/equipos
        $queryProductos->whereHas('familia', function ($q) use ($empresaId) {
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

        if ($buscar) {
            $queryProductos->where(function ($q) use ($buscar) {
                $q->where('codigo', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%");
            });
        }

        $productos = $queryProductos->get();

        foreach ($productos as $producto) {
            $stocksProducto = $almacenId
                ? $producto->stockAlmacenes->where('almacen_id', $almacenId)
                : $producto->stockAlmacenes;

            $stockTotal = $stocksProducto->sum('stock_actual');
            if ($stockTotal > 0) {
                $primerAlmacen = $stocksProducto->first();
                $resultado->push([
                    'id' => 'producto_' . $producto->id,
                    'codigo' => $producto->codigo,
                    'nombre' => $producto->nombre . ' [' . ($producto->familia->nombre ?? 'Sin familia') . ']',
                    'tipo_control' => 'CANTIDAD',
                    'cantidad_disponible' => $stockTotal,
                    'cantidad_total' => $stockTotal,
                    'estado' => 'DISPONIBLE',
                    'disponible' => true,
                    'almacen' => $primerAlmacen?->almacen?->nombre,
                    'fuente' => 'producto_inventario',
                    'producto_id' => $producto->id,
                ]);
            }
        }

        return $resultado->values();
    }

    /**
     * Obtener historial de préstamos de un equipo
     */
    public function obtenerHistorialEquipo(int $equipoId)
    {
        return PrestamoEquipo::where('equipo_id', $equipoId)
            ->with(['trabajador', 'usuarioEntrega', 'usuarioRecepcion'])
            ->orderBy('fecha_prestamo', 'desc')
            ->get();
    }

    /**
     * Obtener historial de préstamos de un trabajador
     */
    public function obtenerHistorialTrabajador(int $trabajadorId)
    {
        return PrestamoEquipo::where('trabajador_id', $trabajadorId)
            ->with(['equipo', 'centroCosto'])
            ->orderBy('fecha_prestamo', 'desc')
            ->get();
    }

    /**
     * Actualizar estados de préstamos vencidos
     */
    public function actualizarPrestamosVencidos(int $empresaId): int
    {
        return PrestamoEquipo::where('empresa_id', $empresaId)
            ->where('estado', PrestamoEquipo::ESTADO_ACTIVO)
            ->where('fecha_devolucion_esperada', '<', now())
            ->update(['estado' => PrestamoEquipo::ESTADO_VENCIDO]);
    }

    /**
     * Generar notificaciones de préstamos vencidos
     */
    public function generarNotificacionesVencidos(int $empresaId): int
    {
        $prestamosVencidos = PrestamoEquipo::where('empresa_id', $empresaId)
            ->whereIn('estado', [PrestamoEquipo::ESTADO_ACTIVO, PrestamoEquipo::ESTADO_VENCIDO])
            ->where('fecha_devolucion_esperada', '<', now())
            ->with(['equipo', 'trabajador'])
            ->get();

        $count = 0;
        foreach ($prestamosVencidos as $prestamo) {
            $existente = Notificacion::where('empresa_id', $empresaId)
                ->where('tipo', 'PRESTAMO_VENCIDO')
                ->where('entidad_tipo', 'prestamos_equipos')
                ->where('entidad_id', $prestamo->id)
                ->where('created_at', '>=', now()->subDay())
                ->exists();

            if (!$existente) {
                $diasAtraso = now()->diffInDays($prestamo->fecha_devolucion_esperada);

                Notificacion::create([
                    'empresa_id' => $empresaId,
                    'tipo' => 'PRESTAMO_VENCIDO',
                    'titulo' => "Préstamo vencido: {$prestamo->equipo->nombre}",
                    'mensaje' => "El préstamo {$prestamo->numero} a {$prestamo->trabajador->nombre} tiene {$diasAtraso} días de atraso",
                    'icono' => 'pi-clock',
                    'severidad' => $diasAtraso > 7 ? 'danger' : 'warn',
                    'entidad_tipo' => 'prestamos_equipos',
                    'entidad_id' => $prestamo->id,
                    'url' => '/prestamos',
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Estadísticas de préstamos
     */
    public function obtenerEstadisticas(int $empresaId, ?int $almacenId = null): array
    {
        $prestamosBase = PrestamoEquipo::where('empresa_id', $empresaId)
            ->when($almacenId, function ($q) use ($almacenId) {
                $q->whereHas('equipo', fn($sq) => $sq->where('almacen_id', $almacenId));
            });

        $equiposBase = EquipoPrestable::where('empresa_id', $empresaId)
            ->activos()
            ->when($almacenId, fn($q) => $q->where('almacen_id', $almacenId));

        $activos = (clone $prestamosBase)
            ->where('estado', PrestamoEquipo::ESTADO_ACTIVO)
            ->count();

        $vencidos = (clone $prestamosBase)
            ->whereIn('estado', [PrestamoEquipo::ESTADO_ACTIVO, PrestamoEquipo::ESTADO_VENCIDO])
            ->where('fecha_devolucion_esperada', '<', now())
            ->count();

        $porVencer = (clone $prestamosBase)
            ->where('estado', PrestamoEquipo::ESTADO_ACTIVO)
            ->whereBetween('fecha_devolucion_esperada', [now(), now()->addDays(3)])
            ->count();

        $devueltosHoy = (clone $prestamosBase)
            ->where('estado', PrestamoEquipo::ESTADO_DEVUELTO)
            ->whereDate('fecha_devolucion_real', today())
            ->count();

        $equiposDisponibles = (clone $equiposBase)
            ->where('estado', EquipoPrestable::ESTADO_DISPONIBLE)
            ->count();

        $equiposPrestados = (clone $equiposBase)
            ->where('estado', EquipoPrestable::ESTADO_PRESTADO)
            ->count();

        return [
            'prestamos_activos' => $activos,
            'prestamos_vencidos' => $vencidos,
            'por_vencer' => $porVencer,
            'devueltos_hoy' => $devueltosHoy,
            'equipos_disponibles' => $equiposDisponibles,
            'equipos_prestados' => $equiposPrestados,
        ];
    }

    /**
     * Generar movimiento de salida en inventario (préstamo)
     */
    protected function generarMovimientoSalida(PrestamoEquipo $prestamo, EquipoPrestable $equipo): void
    {
        if (!$equipo->producto_id || !$equipo->almacen_id) return;

        $receptorNombre = $prestamo->tipo_receptor === PrestamoEquipo::TIPO_USUARIO
            ? $prestamo->trabajadorUsuario?->nombre
            : $prestamo->trabajador?->nombre;
        $receptorNombre = $receptorNombre ?? 'N/D';

        // Generar número de movimiento
        $numero = $this->generarNumeroMovimiento($prestamo->empresa_id, 'SAL');

        // Crear el movimiento (header)
        $movimiento = Movimiento::create([
            'empresa_id' => $prestamo->empresa_id,
            'numero' => $numero,
            'tipo' => Movimiento::TIPO_SALIDA,
            'subtipo' => 'PRESTAMO',
            'almacen_origen_id' => $equipo->almacen_id,
            'centro_costo_id' => $prestamo->centro_costo_id,
            'usuario_id' => $prestamo->entregado_por,
            'referencia_tipo' => 'prestamos_equipos',
            'referencia_id' => $prestamo->id,
            'fecha' => $prestamo->fecha_prestamo,
            'documento_referencia' => $prestamo->numero,
            'observaciones' => "Préstamo a: {$receptorNombre}",
            'estado' => Movimiento::ESTADO_COMPLETADO,
        ]);

        // Obtener stock actual para el costo
        $stockAlmacen = StockAlmacen::where('empresa_id', $prestamo->empresa_id)
            ->where('producto_id', $equipo->producto_id)
            ->where('almacen_id', $equipo->almacen_id)
            ->first();

        $costoUnitario = $stockAlmacen ? $stockAlmacen->costo_promedio : 0;

        // Crear el detalle
        $movimiento->detalles()->create([
            'producto_id' => $equipo->producto_id,
            'cantidad' => $prestamo->cantidad,
            'costo_unitario' => $costoUnitario,
            'costo_total' => $prestamo->cantidad * $costoUnitario,
            'observaciones' => "Préstamo equipo: {$equipo->nombre}",
        ]);

        // Actualizar stock (decrementar)
        $this->decrementarStock(
            $prestamo->empresa_id,
            $equipo->producto_id,
            $equipo->almacen_id,
            $prestamo->cantidad,
            $movimiento
        );
    }

    /**
     * Generar movimiento de entrada en inventario (devolución)
     */
    protected function generarMovimientoEntrada(PrestamoEquipo $prestamo, EquipoPrestable $equipo, string $condicion): void
    {
        // Evita duplicar entradas si el préstamo ya fue regularizado.
        $entradaExistente = Movimiento::where('empresa_id', $prestamo->empresa_id)
            ->where('tipo', Movimiento::TIPO_ENTRADA)
            ->where('subtipo', 'DEVOLUCION_PRESTAMO')
            ->where('referencia_tipo', 'prestamos_equipos')
            ->where('referencia_id', $prestamo->id)
            ->first();
        if ($entradaExistente) {
            return;
        }

        $productoId = $equipo->producto_id;
        $almacenId = $equipo->almacen_id;

        // Recuperar movimiento de salida asociado (fuente canónica para costo y fallback de datos).
        $movimientoSalida = Movimiento::where('empresa_id', $prestamo->empresa_id)
            ->where('tipo', Movimiento::TIPO_SALIDA)
            ->where('subtipo', 'PRESTAMO')
            ->where('referencia_tipo', 'prestamos_equipos')
            ->where('referencia_id', $prestamo->id)
            ->with('detalles:movimiento_id,producto_id,costo_unitario')
            ->orderByDesc('id')
            ->first();

        if (!$almacenId) {
            $almacenId = $movimientoSalida?->almacen_origen_id;
        }

        if (!$productoId) {
            $productoId = $movimientoSalida?->detalles?->first()?->producto_id;
        }

        if (!$productoId || !$almacenId) {
            throw new Exception('No se pudo resolver producto o almacén para registrar la devolución en inventario.');
        }

        $receptorNombre = $prestamo->tipo_receptor === PrestamoEquipo::TIPO_USUARIO
            ? $prestamo->trabajadorUsuario?->nombre
            : $prestamo->trabajador?->nombre;
        $receptorNombre = $receptorNombre ?? 'N/D';

        // Generar número de movimiento
        $numero = $this->generarNumeroMovimiento($prestamo->empresa_id, 'ENT');

        // Obtener costo promedio actual
        $stockAlmacen = StockAlmacen::where('empresa_id', $prestamo->empresa_id)
            ->where('producto_id', $productoId)
            ->where('almacen_id', $almacenId)
            ->first();

        $costoUnitario = $stockAlmacen ? $stockAlmacen->costo_promedio : 0;
        if ($costoUnitario <= 0 && $movimientoSalida) {
            $costoUnitario = (float) ($movimientoSalida->detalles?->first()?->costo_unitario ?? 0);
        }

        // Crear el movimiento (header)
        $movimiento = Movimiento::create([
            'empresa_id' => $prestamo->empresa_id,
            'numero' => $numero,
            'tipo' => Movimiento::TIPO_ENTRADA,
            'subtipo' => 'DEVOLUCION_PRESTAMO',
            'almacen_destino_id' => $almacenId,
            'centro_costo_id' => $prestamo->centro_costo_id,
            'usuario_id' => $prestamo->recibido_por,
            'referencia_tipo' => 'prestamos_equipos',
            'referencia_id' => $prestamo->id,
            'fecha' => $prestamo->fecha_devolucion_real,
            'documento_referencia' => $prestamo->numero,
            'observaciones' => "Devolución de: {$receptorNombre}. Condición: {$condicion}",
            'estado' => Movimiento::ESTADO_COMPLETADO,
        ]);

        // Crear el detalle
        $movimiento->detalles()->create([
            'producto_id' => $productoId,
            'cantidad' => $prestamo->cantidad,
            'costo_unitario' => $costoUnitario,
            'costo_total' => $prestamo->cantidad * $costoUnitario,
            'observaciones' => "Devolución equipo: {$equipo->nombre}. Condición: {$condicion}",
        ]);

        // Actualizar stock (incrementar)
        $this->incrementarStock(
            $prestamo->empresa_id,
            $productoId,
            $almacenId,
            $prestamo->cantidad,
            $costoUnitario,
            $movimiento
        );
    }

    /**
     * Decrementar stock (salida por préstamo)
     */
    protected function decrementarStock(
        int $empresaId,
        int $productoId,
        int $almacenId,
        float $cantidad,
        Movimiento $movimiento
    ): void {
        $stockAlmacen = StockAlmacen::where('empresa_id', $empresaId)
            ->where('producto_id', $productoId)
            ->where('almacen_id', $almacenId)
            ->first();

        if (!$stockAlmacen) {
            // Si no existe stock, no hay nada que decrementar
            return;
        }

        $costoUnitario = $stockAlmacen->costo_promedio;
        $nuevoStock = max(0, $stockAlmacen->stock_actual - $cantidad);

        $stockAlmacen->update([
            'stock_actual' => $nuevoStock,
        ]);

        // Registrar en Kardex
        Kardex::create([
            'empresa_id' => $empresaId,
            'producto_id' => $productoId,
            'almacen_id' => $almacenId,
            'movimiento_id' => $movimiento->id,
            'fecha' => $movimiento->fecha,
            'tipo_operacion' => Kardex::TIPO_SALIDA,
            'documento_referencia' => $movimiento->documento_referencia ?? $movimiento->numero,
            'cantidad' => $cantidad,
            'costo_unitario' => $costoUnitario,
            'costo_total' => $cantidad * $costoUnitario,
            'saldo_cantidad' => $nuevoStock,
            'saldo_costo_unitario' => $stockAlmacen->costo_promedio,
            'saldo_costo_total' => $nuevoStock * $stockAlmacen->costo_promedio,
            'descripcion' => $movimiento->observaciones,
        ]);
    }

    /**
     * Incrementar stock (entrada por devolución)
     */
    protected function incrementarStock(
        int $empresaId,
        int $productoId,
        int $almacenId,
        float $cantidad,
        float $costoUnitario,
        Movimiento $movimiento
    ): void {
        $stockAlmacen = StockAlmacen::firstOrCreate(
            [
                'empresa_id' => $empresaId,
                'producto_id' => $productoId,
                'almacen_id' => $almacenId,
            ],
            [
                'stock_actual' => 0,
                'costo_promedio' => 0,
            ]
        );

        // Calcular nuevo costo promedio
        $valorActual = $stockAlmacen->stock_actual * $stockAlmacen->costo_promedio;
        $valorNuevo = $cantidad * $costoUnitario;
        $nuevoStock = $stockAlmacen->stock_actual + $cantidad;
        $nuevoCostoPromedio = $nuevoStock > 0 ? ($valorActual + $valorNuevo) / $nuevoStock : 0;

        $stockAlmacen->update([
            'stock_actual' => $nuevoStock,
            'costo_promedio' => $nuevoCostoPromedio,
        ]);

        // Registrar en Kardex
        Kardex::create([
            'empresa_id' => $empresaId,
            'producto_id' => $productoId,
            'almacen_id' => $almacenId,
            'movimiento_id' => $movimiento->id,
            'fecha' => $movimiento->fecha,
            'tipo_operacion' => Kardex::TIPO_ENTRADA,
            'documento_referencia' => $movimiento->documento_referencia ?? $movimiento->numero,
            'cantidad' => $cantidad,
            'costo_unitario' => $costoUnitario,
            'costo_total' => $cantidad * $costoUnitario,
            'saldo_cantidad' => $nuevoStock,
            'saldo_costo_unitario' => $nuevoCostoPromedio,
            'saldo_costo_total' => $nuevoStock * $nuevoCostoPromedio,
            'descripcion' => $movimiento->observaciones,
        ]);
    }

    /**
     * Generar número de movimiento
     */
    protected function generarNumeroMovimiento(int $empresaId, string $prefijo): string
    {
        $year = date('Y');
        $month = date('m');
        $base = sprintf('%s-%s%s-', $prefijo, $year, $month);
        $ultimoNumero = Movimiento::where('empresa_id', $empresaId)
            ->where('numero', 'like', $base . '%')
            ->orderBy('numero', 'desc')
            ->lockForUpdate()
            ->value('numero');

        $secuencia = 1;
        if ($ultimoNumero) {
            $partes = explode('-', $ultimoNumero);
            $secuencia = (int) end($partes) + 1;
        }

        $numero = $base . str_pad($secuencia, 4, '0', STR_PAD_LEFT);
        while (Movimiento::where('empresa_id', $empresaId)->where('numero', $numero)->exists()) {
            $secuencia++;
            $numero = $base . str_pad($secuencia, 4, '0', STR_PAD_LEFT);
        }

        return $numero;
    }

    /**
     * Crear EquipoPrestable automáticamente desde un producto del inventario
     * Se usa cuando el usuario selecciona un producto directamente para préstamo
     */
    protected function crearEquipoPrestableDesdeProducto(int $productoId, int $empresaId, ?int $almacenId = null): EquipoPrestable
    {
        $producto = \App\Modules\Inventario\Models\Producto::with('stockAlmacenes')
            ->findOrFail($productoId);

        if ($producto->empresa_id !== $empresaId) {
            throw new Exception("El producto no pertenece a la empresa.");
        }

        // Verificar si ya existe un EquipoPrestable para este producto/almacén
        $existente = EquipoPrestable::where('empresa_id', $empresaId)
            ->where('producto_id', $productoId)
            ->when($almacenId, fn($q) => $q->where('almacen_id', $almacenId))
            ->first();

        if ($existente) {
            return $existente;
        }

        $stocks = $producto->stockAlmacenes;
        if ($almacenId) {
            $stocks = $stocks->where('almacen_id', $almacenId);
            $stockEnAlmacen = (float) $stocks->sum('stock_actual');
            if ($stockEnAlmacen <= 0) {
                throw new Exception("El producto no tiene stock en el almacén asignado.");
            }
        }

        // Obtener el almacén con más stock
        $stockPrincipal = $stocks->sortByDesc('stock_actual')->first();
        $stockTotal = (int) $stocks->sum('stock_actual');
        $almacenObjetivo = $almacenId ?: $stockPrincipal?->almacen_id;

        // Crear el equipo prestable automáticamente
        return EquipoPrestable::create([
            'empresa_id' => $empresaId,
            'producto_id' => $productoId,
            'codigo' => $this->generarCodigoEquipoDesdeProducto($empresaId, $producto->codigo, $almacenObjetivo),
            'nombre' => $producto->nombre,
            'descripcion' => $producto->descripcion,
            'tipo_control' => 'CANTIDAD',
            'cantidad_total' => max(1, $stockTotal),
            'cantidad_disponible' => max(1, $stockTotal),
            'almacen_id' => $almacenObjetivo,
            'estado' => EquipoPrestable::ESTADO_DISPONIBLE,
            'activo' => true,
        ]);
    }

    /**
     * Genera un código único para equipos prestables creados desde productos.
     */
    protected function generarCodigoEquipoDesdeProducto(int $empresaId, string $codigoBase, ?int $almacenId = null): string
    {
        $codigo = $codigoBase;

        if (EquipoPrestable::where('codigo', $codigo)->exists()) {
            $sufijoAlmacen = $almacenId ? "-ALM{$almacenId}" : '-PRE';
            $codigo = "{$codigoBase}{$sufijoAlmacen}";
        }

        $contador = 2;
        while (EquipoPrestable::where('codigo', $codigo)->exists()) {
            $codigo = "{$codigoBase}-{$empresaId}-{$contador}";
            $contador++;
        }

        return $codigo;
    }
}
