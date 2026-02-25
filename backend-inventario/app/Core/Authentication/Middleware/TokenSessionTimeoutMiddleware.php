<?php

namespace App\Core\Authentication\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TokenSessionTimeoutMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (!$token) {
            return $next($request);
        }

        $now = now();
        $maxMinutes = (int) env('SESSION_MAX_MINUTES', 480);   // 8 horas
        $idleMinutes = (int) env('SESSION_IDLE_MINUTES', 20);  // 20 min inactividad

        if ($maxMinutes > 0 && $token->created_at && $token->created_at->copy()->addMinutes($maxMinutes)->lte($now)) {
            $token->delete();
            return response()->json([
                'success' => false,
                'message' => 'Sesión expirada por tiempo máximo. Vuelva a iniciar sesión.',
            ], 401);
        }

        $baseIdleAt = $token->last_used_at ?: $token->created_at;
        if ($idleMinutes > 0 && $baseIdleAt && $baseIdleAt->copy()->addMinutes($idleMinutes)->lte($now)) {
            $token->delete();
            return response()->json([
                'success' => false,
                'message' => 'Sesión expirada por inactividad. Vuelva a iniciar sesión.',
            ], 401);
        }

        return $next($request);
    }
}

