<?php

namespace App\Modules\Notificaciones\Services;

use App\Modules\Notificaciones\Models\Notificacion;
use App\Modules\Administracion\Models\Usuario;
use App\Modules\Inventario\Models\Producto;
use App\Modules\EPPs\Models\AsignacionEpp;
use App\Modules\Requisiciones\Models\Requisicion;
use App\Modules\Prestamos\Models\PrestamoEquipo;
use Illuminate\Support\Facades\DB;

class NotificacionService
{
    /**
     * Generar todas las notificaciones automáticas para una empresa
     */
    public function generarNotificacionesAutomaticas(int $empresaId): array
    {
        $resumen = [
            'stock_bajo' => 0,
            'epps_vencidos' => 0,
            'epps_por_vencer' => 0,
            'requisiciones_pendientes' => 0,
            'prestamos_vencidos' => 0,
            'prestamos_por_vencer' => 0,
        ];

        // Generar notificaciones de stock bajo
        $resumen['stock_bajo'] = $this->generarNotificacionesStockBajo($empresaId);

        // Generar notificaciones de EPPs
        $resumen['epps_vencidos'] = $this->generarNotificacionesEppsVencidos($empresaId);
        $resumen['epps_por_vencer'] = $this->generarNotificacionesEppsPorVencer($empresaId);

        // Generar notificaciones de requisiciones pendientes
        $resumen['requisiciones_pendientes'] = $this->generarNotificacionesRequisicionesPendientes($empresaId);

        // Generar notificaciones de préstamos
        $resumen['prestamos_vencidos'] = $this->generarNotificacionesPrestamosVencidos($empresaId);
        $resumen['prestamos_por_vencer'] = $this->generarNotificacionesPrestamosPorVencer($empresaId);

        return $resumen;
    }

    /**
     * Generar notificaciones de productos con stock bajo
     */
    public function generarNotificacionesStockBajo(int $empresaId): int
    {
        $productosStockBajo = DB::table('productos as p')
            ->leftJoin('stock_almacen as sa', 'p.id', '=', 'sa.producto_id')
            ->where('p.empresa_id', $empresaId)
            ->where('p.activo', true)
            ->groupBy('p.id', 'p.codigo', 'p.nombre', 'p.stock_minimo')
            ->havingRaw('COALESCE(SUM(sa.stock_actual), 0) <= p.stock_minimo')
            ->select(
                'p.id',
                'p.codigo',
                'p.nombre',
                'p.stock_minimo',
                DB::raw('COALESCE(SUM(sa.stock_actual), 0) as stock_actual')
            )
            ->get();

        $count = 0;
        foreach ($productosStockBajo as $producto) {
            // Verificar si ya existe notificación reciente (últimas 24h)
            $existente = Notificacion::where('empresa_id', $empresaId)
                ->where('tipo', Notificacion::TIPO_STOCK_BAJO)
                ->where('entidad_tipo', 'productos')
                ->where('entidad_id', $producto->id)
                ->where('created_at', '>=', now()->subDay())
                ->exists();

            if (!$existente) {
                $severidad = $producto->stock_actual <= 0
                    ? Notificacion::SEVERIDAD_DANGER
                    : Notificacion::SEVERIDAD_WARN;

                $titulo = $producto->stock_actual <= 0
                    ? "Sin stock: {$producto->nombre}"
                    : "Stock bajo: {$producto->nombre}";

                Notificacion::create([
                    'empresa_id' => $empresaId,
                    'usuario_id' => null, // Notificación global
                    'tipo' => Notificacion::TIPO_STOCK_BAJO,
                    'titulo' => $titulo,
                    'mensaje' => "El producto {$producto->codigo} - {$producto->nombre} tiene stock actual de {$producto->stock_actual} unidades (mínimo: {$producto->stock_minimo})",
                    'icono' => 'pi-box',
                    'severidad' => $severidad,
                    'entidad_tipo' => 'productos',
                    'entidad_id' => $producto->id,
                    'url' => '/inventario/productos',
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Generar notificaciones de EPPs vencidos
     */
    public function generarNotificacionesEppsVencidos(int $empresaId): int
    {
        $eppsVencidos = AsignacionEpp::where('empresa_id', $empresaId)
            ->whereIn('estado', ['ENTREGADO', 'ACTIVO'])
            ->where('fecha_vencimiento', '<', now())
            ->with(['trabajador:id,nombre', 'tipoEpp:id,nombre'])
            ->get();

        $count = 0;
        foreach ($eppsVencidos as $epp) {
            $existente = Notificacion::where('empresa_id', $empresaId)
                ->where('tipo', Notificacion::TIPO_EPP_VENCIMIENTO)
                ->where('entidad_tipo', 'asignaciones_epp')
                ->where('entidad_id', $epp->id)
                ->where('created_at', '>=', now()->subDay())
                ->exists();

            if (!$existente) {
                Notificacion::create([
                    'empresa_id' => $empresaId,
                    'usuario_id' => null,
                    'tipo' => Notificacion::TIPO_EPP_VENCIMIENTO,
                    'titulo' => "EPP Vencido: {$epp->tipoEpp?->nombre}",
                    'mensaje' => "El EPP {$epp->tipoEpp?->nombre} asignado a {$epp->trabajador?->nombre} venció el {$epp->fecha_vencimiento->format('d/m/Y')}",
                    'icono' => 'pi-shield',
                    'severidad' => Notificacion::SEVERIDAD_DANGER,
                    'entidad_tipo' => 'asignaciones_epp',
                    'entidad_id' => $epp->id,
                    'url' => '/epps',
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Generar notificaciones de EPPs por vencer (próximos 7 días)
     */
    public function generarNotificacionesEppsPorVencer(int $empresaId): int
    {
        $eppsPorVencer = AsignacionEpp::where('empresa_id', $empresaId)
            ->whereIn('estado', ['ENTREGADO', 'ACTIVO'])
            ->whereBetween('fecha_vencimiento', [now(), now()->addDays(7)])
            ->with(['trabajador:id,nombre', 'tipoEpp:id,nombre'])
            ->get();

        $count = 0;
        foreach ($eppsPorVencer as $epp) {
            $existente = Notificacion::where('empresa_id', $empresaId)
                ->where('tipo', Notificacion::TIPO_EPP_POR_VENCER)
                ->where('entidad_tipo', 'asignaciones_epp')
                ->where('entidad_id', $epp->id)
                ->where('created_at', '>=', now()->subDay())
                ->exists();

            if (!$existente) {
                $diasRestantes = now()->diffInDays($epp->fecha_vencimiento);

                Notificacion::create([
                    'empresa_id' => $empresaId,
                    'usuario_id' => null,
                    'tipo' => Notificacion::TIPO_EPP_POR_VENCER,
                    'titulo' => "EPP por vencer: {$epp->tipoEpp?->nombre}",
                    'mensaje' => "El EPP {$epp->tipoEpp?->nombre} de {$epp->trabajador?->nombre} vence en {$diasRestantes} días ({$epp->fecha_vencimiento->format('d/m/Y')})",
                    'icono' => 'pi-shield',
                    'severidad' => Notificacion::SEVERIDAD_WARN,
                    'entidad_tipo' => 'asignaciones_epp',
                    'entidad_id' => $epp->id,
                    'url' => '/epps',
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Generar notificaciones de requisiciones pendientes de aprobación
     */
    public function generarNotificacionesRequisicionesPendientes(int $empresaId): int
    {
        $requisicionesPendientes = Requisicion::where('empresa_id', $empresaId)
            ->where('estado', 'PENDIENTE')
            ->where('created_at', '<=', now()->subHours(24)) // Más de 24h sin aprobar
            ->with(['solicitante:id,nombre', 'centroCosto:id,nombre'])
            ->get();

        $count = 0;
        foreach ($requisicionesPendientes as $requisicion) {
            $existente = Notificacion::where('empresa_id', $empresaId)
                ->where('tipo', Notificacion::TIPO_REQUISICION_PENDIENTE)
                ->where('entidad_tipo', 'requisiciones')
                ->where('entidad_id', $requisicion->id)
                ->where('created_at', '>=', now()->subDay())
                ->exists();

            if (!$existente) {
                $horasPendiente = now()->diffInHours($requisicion->created_at);

                Notificacion::create([
                    'empresa_id' => $empresaId,
                    'usuario_id' => null,
                    'tipo' => Notificacion::TIPO_REQUISICION_PENDIENTE,
                    'titulo' => "Requisición pendiente: {$requisicion->numero}",
                    'mensaje' => "La requisición {$requisicion->numero} de {$requisicion->solicitante?->nombre} lleva {$horasPendiente} horas pendiente de aprobación",
                    'icono' => 'pi-file-edit',
                    'severidad' => $horasPendiente > 48 ? Notificacion::SEVERIDAD_DANGER : Notificacion::SEVERIDAD_WARN,
                    'entidad_tipo' => 'requisiciones',
                    'entidad_id' => $requisicion->id,
                    'url' => '/requisiciones',
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Generar notificaciones de préstamos vencidos
     */
    public function generarNotificacionesPrestamosVencidos(int $empresaId): int
    {
        $prestamosVencidos = PrestamoEquipo::where('empresa_id', $empresaId)
            ->where('estado', 'ACTIVO')
            ->where('fecha_devolucion_esperada', '<', now())
            ->with(['equipo:id,nombre,codigo', 'trabajador:id,nombre'])
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
                    'usuario_id' => null,
                    'tipo' => 'PRESTAMO_VENCIDO',
                    'titulo' => "Préstamo vencido: {$prestamo->equipo?->nombre}",
                    'mensaje' => "El préstamo {$prestamo->numero} a {$prestamo->trabajador?->nombre} tiene {$diasAtraso} días de atraso",
                    'icono' => 'pi-clock',
                    'severidad' => $diasAtraso > 7 ? Notificacion::SEVERIDAD_DANGER : Notificacion::SEVERIDAD_WARN,
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
     * Generar notificaciones de préstamos por vencer (próximos 3 días)
     */
    public function generarNotificacionesPrestamosPorVencer(int $empresaId): int
    {
        $prestamosPorVencer = PrestamoEquipo::where('empresa_id', $empresaId)
            ->where('estado', 'ACTIVO')
            ->whereBetween('fecha_devolucion_esperada', [now(), now()->addDays(3)])
            ->with(['equipo:id,nombre,codigo', 'trabajador:id,nombre'])
            ->get();

        $count = 0;
        foreach ($prestamosPorVencer as $prestamo) {
            $existente = Notificacion::where('empresa_id', $empresaId)
                ->where('tipo', 'PRESTAMO_POR_VENCER')
                ->where('entidad_tipo', 'prestamos_equipos')
                ->where('entidad_id', $prestamo->id)
                ->where('created_at', '>=', now()->subDay())
                ->exists();

            if (!$existente) {
                $diasRestantes = now()->diffInDays($prestamo->fecha_devolucion_esperada);

                Notificacion::create([
                    'empresa_id' => $empresaId,
                    'usuario_id' => null,
                    'tipo' => 'PRESTAMO_POR_VENCER',
                    'titulo' => "Préstamo por vencer: {$prestamo->equipo?->nombre}",
                    'mensaje' => "El préstamo {$prestamo->numero} a {$prestamo->trabajador?->nombre} vence en {$diasRestantes} días",
                    'icono' => 'pi-clock',
                    'severidad' => Notificacion::SEVERIDAD_INFO,
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
     * Crear notificación personalizada
     */
    public function crearNotificacion(array $data): Notificacion
    {
        return Notificacion::create($data);
    }

    /**
     * Obtener notificaciones para un usuario
     */
    public function obtenerNotificaciones(int $empresaId, ?int $usuarioId, bool $soloNoLeidas = false, int $limite = 20)
    {
        $query = Notificacion::where('empresa_id', $empresaId)
            ->paraUsuario($usuarioId)
            ->orderBy('created_at', 'desc');

        if ($soloNoLeidas) {
            $query->noLeidas();
        }

        return $query->limit($limite)->get();
    }

    /**
     * Obtener notificaciones para un usuario aplicando restricciones por almacén (almacenero).
     */
    public function obtenerNotificacionesParaUsuario(Usuario $usuario, bool $soloNoLeidas = false, int $limite = 20)
    {
        $query = Notificacion::where('empresa_id', $usuario->empresa_id)
            ->paraUsuario($usuario->id)
            ->orderBy('created_at', 'desc');

        $this->aplicarFiltroNotificacionesPorAlmacen($query, $usuario);

        if ($soloNoLeidas) {
            $query->noLeidas();
        }

        return $query->limit($limite)->get();
    }

    /**
     * Contar notificaciones no leídas
     */
    public function contarNoLeidas(int $empresaId, ?int $usuarioId): int
    {
        return Notificacion::where('empresa_id', $empresaId)
            ->paraUsuario($usuarioId)
            ->noLeidas()
            ->count();
    }

    /**
     * Contar notificaciones no leídas aplicando restricciones por almacén.
     */
    public function contarNoLeidasParaUsuario(Usuario $usuario): int
    {
        $query = Notificacion::where('empresa_id', $usuario->empresa_id)
            ->paraUsuario($usuario->id)
            ->noLeidas();

        $this->aplicarFiltroNotificacionesPorAlmacen($query, $usuario);

        return $query->count();
    }

    /**
     * Marcar todas como leídas
     */
    public function marcarTodasLeidas(int $empresaId, ?int $usuarioId): int
    {
        return Notificacion::where('empresa_id', $empresaId)
            ->paraUsuario($usuarioId)
            ->noLeidas()
            ->update(['leida_en' => now()]);
    }

    public function marcarTodasLeidasParaUsuario(Usuario $usuario): int
    {
        $query = Notificacion::where('empresa_id', $usuario->empresa_id)
            ->paraUsuario($usuario->id)
            ->noLeidas();

        $this->aplicarFiltroNotificacionesPorAlmacen($query, $usuario);

        return $query->update(['leida_en' => now()]);
    }

    public function usuarioPuedeAccederNotificacion(Usuario $usuario, Notificacion $notificacion): bool
    {
        if ((int) $notificacion->empresa_id !== (int) $usuario->empresa_id) {
            return false;
        }

        if (!is_null($notificacion->usuario_id) && (int) $notificacion->usuario_id !== (int) $usuario->id) {
            return false;
        }

        if (!$usuario->hasRole('almacenero') || !$usuario->almacen_id) {
            return true;
        }

        if (!is_null($notificacion->usuario_id) && (int) $notificacion->usuario_id === (int) $usuario->id) {
            return true;
        }

        return $this->notificacionPerteneceAlmacen((int) $notificacion->entidad_id, (string) $notificacion->entidad_tipo, (int) $usuario->almacen_id);
    }

    private function aplicarFiltroNotificacionesPorAlmacen($query, Usuario $usuario): void
    {
        if (!$usuario->hasRole('almacenero') || !$usuario->almacen_id) {
            return;
        }

        $almacenId = (int) $usuario->almacen_id;
        $usuarioId = (int) $usuario->id;

        $query->where(function ($q) use ($almacenId, $usuarioId) {
            $q->where('usuario_id', $usuarioId)
              ->orWhere(function ($sq) use ($almacenId) {
                  $sq->whereNull('usuario_id')
                      ->where(function ($scope) use ($almacenId) {
                          $scope->where(function ($w) use ($almacenId) {
                              $w->where('entidad_tipo', 'prestamos_equipos')
                                  ->whereExists(function ($ex) use ($almacenId) {
                                      $ex->select(DB::raw(1))
                                          ->from('prestamos_equipos as pe')
                                          ->join('equipos_prestables as ep', 'ep.id', '=', 'pe.equipo_id')
                                          ->whereColumn('pe.id', 'notificaciones.entidad_id')
                                          ->where('ep.almacen_id', $almacenId);
                                  });
                          })->orWhere(function ($w) use ($almacenId) {
                              $w->where('entidad_tipo', 'asignaciones_epp')
                                  ->whereExists(function ($ex) use ($almacenId) {
                                      $ex->select(DB::raw(1))
                                          ->from('asignaciones_epp as ae')
                                          ->whereColumn('ae.id', 'notificaciones.entidad_id')
                                          ->where('ae.almacen_id', $almacenId);
                                  });
                          })->orWhere(function ($w) use ($almacenId) {
                              $w->where('entidad_tipo', 'productos')
                                  ->whereExists(function ($ex) use ($almacenId) {
                                      $ex->select(DB::raw(1))
                                          ->from('stock_almacen as sa')
                                          ->whereColumn('sa.producto_id', 'notificaciones.entidad_id')
                                          ->where('sa.almacen_id', $almacenId)
                                          ->where('sa.stock_actual', '<=', DB::raw('(select p.stock_minimo from productos p where p.id = sa.producto_id limit 1)'));
                                  });
                          });
                      });
              });
        });
    }

    private function notificacionPerteneceAlmacen(int $entidadId, string $entidadTipo, int $almacenId): bool
    {
        if (!$entidadId || !$entidadTipo) {
            return false;
        }

        return match ($entidadTipo) {
            'prestamos_equipos' => DB::table('prestamos_equipos as pe')
                ->join('equipos_prestables as ep', 'ep.id', '=', 'pe.equipo_id')
                ->where('pe.id', $entidadId)
                ->where('ep.almacen_id', $almacenId)
                ->exists(),
            'asignaciones_epp' => DB::table('asignaciones_epp')
                ->where('id', $entidadId)
                ->where('almacen_id', $almacenId)
                ->exists(),
            'productos' => DB::table('stock_almacen')
                ->where('producto_id', $entidadId)
                ->where('almacen_id', $almacenId)
                ->exists(),
            default => false,
        };
    }

    /**
     * Limpiar notificaciones antiguas (más de 30 días)
     */
    public function limpiarAntiguas(int $empresaId, int $dias = 30): int
    {
        return Notificacion::where('empresa_id', $empresaId)
            ->where('created_at', '<', now()->subDays($dias))
            ->whereNotNull('leida_en')
            ->delete();
    }
}
