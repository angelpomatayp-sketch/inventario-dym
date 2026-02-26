<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Confiar en proxy de Railway (HTTPS termination) para generar URLs correctas
        $middleware->trustProxies(at: '*');

        // Middleware aliases
        $middleware->alias([
            'empresa' => \App\Core\Tenancy\Middleware\EmpresaMiddleware::class,
            'contexto' => \App\Core\Tenancy\Middleware\ValidarAccesoContextoMiddleware::class,
            'session.timeout' => \App\Core\Authentication\Middleware\TokenSessionTimeoutMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // API usa tokens Bearer, no necesita CSRF/session stateful
        // $middleware->api(prepend: [
        //     \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
