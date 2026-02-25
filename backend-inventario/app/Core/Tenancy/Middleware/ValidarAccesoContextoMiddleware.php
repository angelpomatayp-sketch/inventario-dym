<?php

namespace App\Core\Tenancy\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidarAccesoContextoMiddleware
{
    /**
     * Valida acceso por empresa y, para almacenero, por almacén.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 401);
        }

        $empresaId = (int) $user->empresa_id;
        $almacenAsignado = method_exists($user, 'esAlmacenero') && $user->esAlmacenero()
            ? (int) $user->almacen_id
            : null;

        $route = $request->route();
        if (!$route) {
            return $next($request);
        }

        foreach ($route->parameters() as $parameter) {
            if (!$parameter instanceof Model) {
                continue;
            }

            if ($this->hasAttribute($parameter, 'empresa_id')) {
                $modelEmpresaId = (int) $parameter->getAttribute('empresa_id');
                if ($empresaId > 0 && $modelEmpresaId > 0 && $modelEmpresaId !== $empresaId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No autorizado',
                    ], 403);
                }
            }

            if ($almacenAsignado && !$this->allowedByAlmacen($parameter, $almacenAsignado)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado',
                ], 403);
            }
        }

        return $next($request);
    }

    private function hasAttribute(Model $model, string $attribute): bool
    {
        return array_key_exists($attribute, $model->getAttributes());
    }

    private function allowedByAlmacen(Model $model, int $almacenAsignado): bool
    {
        if ($this->hasAttribute($model, 'almacen_id')) {
            $almacenId = (int) $model->getAttribute('almacen_id');
            return $almacenId === 0 || $almacenId === $almacenAsignado;
        }

        if ($this->hasAttribute($model, 'almacen_origen_id') || $this->hasAttribute($model, 'almacen_destino_id')) {
            $origen = (int) ($model->getAttribute('almacen_origen_id') ?? 0);
            $destino = (int) ($model->getAttribute('almacen_destino_id') ?? 0);

            if ($origen === 0 && $destino === 0) {
                return true;
            }

            return $origen === $almacenAsignado || $destino === $almacenAsignado;
        }

        return true;
    }
}
