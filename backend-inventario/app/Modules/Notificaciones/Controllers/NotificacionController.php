<?php

namespace App\Modules\Notificaciones\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notificaciones\Models\Notificacion;
use App\Modules\Notificaciones\Services\NotificacionService;
use App\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    use ApiResponse;

    protected NotificacionService $notificacionService;

    public function __construct(NotificacionService $notificacionService)
    {
        $this->notificacionService = $notificacionService;
    }

    /**
     * Listar notificaciones del usuario
     */
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $soloNoLeidas = $request->boolean('solo_no_leidas', false);
        $limite = $request->get('limite', 20);

        $notificaciones = $this->notificacionService->obtenerNotificacionesParaUsuario($usuario, $soloNoLeidas, $limite);

        $conteoNoLeidas = $this->notificacionService->contarNoLeidasParaUsuario($usuario);

        return $this->success([
            'notificaciones' => $notificaciones,
            'no_leidas' => $conteoNoLeidas,
        ]);
    }

    /**
     * Contar notificaciones no leídas
     */
    public function contarNoLeidas(Request $request): JsonResponse
    {
        $count = $this->notificacionService->contarNoLeidasParaUsuario($request->user());

        return $this->success(['count' => $count]);
    }

    /**
     * Marcar una notificación como leída
     */
    public function marcarLeida(Request $request, Notificacion $notificacion): JsonResponse
    {
        if (!$this->notificacionService->usuarioPuedeAccederNotificacion($request->user(), $notificacion)) {
            return $this->error('No autorizado', 403);
        }

        $notificacion->marcarLeida();

        return $this->success(['mensaje' => 'Notificación marcada como leída']);
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function marcarTodasLeidas(Request $request): JsonResponse
    {
        $actualizadas = $this->notificacionService->marcarTodasLeidasParaUsuario($request->user());

        return $this->success([
            'mensaje' => 'Notificaciones marcadas como leídas',
            'actualizadas' => $actualizadas,
        ]);
    }

    /**
     * Generar notificaciones automáticas (ejecutar manualmente o vía cron)
     */
    public function generar(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $resumen = $this->notificacionService->generarNotificacionesAutomaticas($empresaId);

        return $this->success([
            'mensaje' => 'Notificaciones generadas',
            'resumen' => $resumen,
        ]);
    }

    /**
     * Eliminar una notificación
     */
    public function destroy(Request $request, Notificacion $notificacion): JsonResponse
    {
        if (!$this->notificacionService->usuarioPuedeAccederNotificacion($request->user(), $notificacion)) {
            return $this->error('No autorizado', 403);
        }

        $notificacion->delete();

        return $this->success(['mensaje' => 'Notificación eliminada']);
    }

    /**
     * Limpiar notificaciones antiguas
     */
    public function limpiar(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;
        $dias = $request->get('dias', 30);

        $eliminadas = $this->notificacionService->limpiarAntiguas($empresaId, $dias);

        return $this->success([
            'mensaje' => 'Notificaciones antiguas eliminadas',
            'eliminadas' => $eliminadas,
        ]);
    }

    /**
     * Obtener resumen de alertas activas (para widget del dashboard)
     */
    public function resumen(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $empresaId = $usuario->empresa_id;

        // Primero generamos las notificaciones automáticas
        $this->notificacionService->generarNotificacionesAutomaticas($empresaId);

        // Contamos por tipo
        $notificaciones = $this->notificacionService->obtenerNotificacionesParaUsuario($usuario, true, 1000);
        $resumen = $notificaciones
            ->groupBy('tipo')
            ->map(fn($grupo) => $grupo->count());

        $totalNoLeidas = $this->notificacionService->contarNoLeidasParaUsuario($usuario);

        // Últimas 5 notificaciones
        $ultimas = $this->notificacionService->obtenerNotificacionesParaUsuario($usuario, true, 5);

        return $this->success([
            'total_no_leidas' => $totalNoLeidas,
            'por_tipo' => $resumen,
            'ultimas' => $ultimas,
        ]);
    }
}
