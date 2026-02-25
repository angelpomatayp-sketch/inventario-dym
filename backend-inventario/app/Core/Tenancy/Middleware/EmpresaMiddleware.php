<?php

namespace App\Core\Tenancy\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para validar que el usuario tenga empresa asignada.
 *
 * Este middleware verifica que el usuario autenticado tenga una empresa_id
 * válida antes de permitir el acceso a las rutas protegidas.
 */
class EmpresaMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si hay usuario autenticado
        if (!auth()->check()) {
            return response()->json([
                'mensaje' => 'No autenticado.',
                'error' => 'unauthenticated'
            ], 401);
        }

        // Verificar si el usuario tiene empresa asignada
        if (!auth()->user()->empresa_id) {
            return response()->json([
                'mensaje' => 'Usuario sin empresa asignada. Contacte al administrador.',
                'error' => 'sin_empresa'
            ], 403);
        }

        // Verificar si la empresa está activa
        $empresa = auth()->user()->empresa;
        if (!$empresa || !$empresa->activo) {
            return response()->json([
                'mensaje' => 'La empresa no está activa. Contacte al administrador.',
                'error' => 'empresa_inactiva'
            ], 403);
        }

        return $next($request);
    }
}
