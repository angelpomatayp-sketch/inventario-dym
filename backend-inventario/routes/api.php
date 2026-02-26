<?php

use Illuminate\Support\Facades\Route;
use App\Core\Authentication\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rutas de la API del Sistema de Inventario Minero DYM SAC
|
*/

// =====================================================================
// RUTAS PÚBLICAS (Sin autenticación)
// =====================================================================

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Servir imágenes de productos (ruta pública)
Route::get('/storage/productos/{productoId}/{filename}', function ($productoId, $filename) {
    $path = storage_path("app/public/productos/{$productoId}/{$filename}");

    if (!file_exists($path)) {
        abort(404, 'Imagen no encontrada');
    }

    $mimeType = mime_content_type($path);
    return response()->file($path, ['Content-Type' => $mimeType]);
});

// =====================================================================
// RUTAS PROTEGIDAS (Requieren autenticación)
// =====================================================================

Route::middleware(['auth:sanctum', 'session.timeout', 'contexto'])->group(function () {

    // -----------------------------------------------------------------
    // AUTENTICACIÓN
    // -----------------------------------------------------------------
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/verify-password', [AuthController::class, 'verifyPassword']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::get('/refresh', [AuthController::class, 'refresh']);
    });

    // -----------------------------------------------------------------
    // MÓDULO: ADMINISTRACIÓN
    // -----------------------------------------------------------------
    Route::prefix('administracion')->group(function () {
        // Empresas
        Route::apiResource('empresas', \App\Modules\Administracion\Controllers\EmpresaController::class);

        // Usuarios
        Route::apiResource('usuarios', \App\Modules\Administracion\Controllers\UsuarioController::class);
        Route::put('usuarios/{usuario}/toggle-activo', [\App\Modules\Administracion\Controllers\UsuarioController::class, 'toggleActivo']);
        Route::put('usuarios/{usuario}/cambiar-password', [\App\Modules\Administracion\Controllers\UsuarioController::class, 'cambiarPassword']);
        Route::post('usuarios/{usuario}/kardex-pdf', [\App\Modules\Administracion\Controllers\UsuarioController::class, 'subirKardexPdf']);
        Route::get('usuarios/{usuario}/kardex-pdf', [\App\Modules\Administracion\Controllers\UsuarioController::class, 'descargarKardexPdf']);
        Route::delete('usuarios/{usuario}/kardex-pdf', [\App\Modules\Administracion\Controllers\UsuarioController::class, 'eliminarKardexPdf']);

        // Centros de Costo
        Route::apiResource('centros-costo', \App\Modules\Administracion\Controllers\CentroCostoController::class);

        // Almacenes
        Route::apiResource('almacenes', \App\Modules\Administracion\Controllers\AlmacenController::class)
            ->parameters(['almacenes' => 'almacen']);
        Route::get('almacenes/{almacen}/productos', [\App\Modules\Administracion\Controllers\AlmacenController::class, 'productos']);

        // Roles y Permisos
        Route::get('roles', [\App\Modules\Administracion\Controllers\RolController::class, 'index']);
        Route::get('permisos', [\App\Modules\Administracion\Controllers\RolController::class, 'permisos']);

        // Trabajadores (personal sin login - solo para control documental)
        Route::get('trabajadores/buscar', [\App\Modules\Administracion\Controllers\TrabajadorController::class, 'buscar']);
        Route::apiResource('trabajadores', \App\Modules\Administracion\Controllers\TrabajadorController::class);
        Route::post('trabajadores/{trabajador}/dar-de-baja', [\App\Modules\Administracion\Controllers\TrabajadorController::class, 'darDeBaja']);
        Route::post('trabajadores/{trabajador}/kardex-pdf', [\App\Modules\Administracion\Controllers\TrabajadorController::class, 'subirKardexPdf']);
        Route::get('trabajadores/{trabajador}/kardex-pdf', [\App\Modules\Administracion\Controllers\TrabajadorController::class, 'descargarKardexPdf']);
        Route::delete('trabajadores/{trabajador}/kardex-pdf', [\App\Modules\Administracion\Controllers\TrabajadorController::class, 'eliminarKardexPdf']);
    });

    // -----------------------------------------------------------------
    // MÓDULO: INVENTARIO
    // -----------------------------------------------------------------
    Route::prefix('inventario')->group(function () {
        // Unidades de Medida
        Route::apiResource('unidades', \App\Modules\Inventario\Controllers\UnidadMedidaController::class)
            ->parameters(['unidades' => 'unidade']);

        // Familias/Categorías
        Route::apiResource('familias', \App\Modules\Inventario\Controllers\FamiliaController::class);

        // Productos
        Route::apiResource('productos', \App\Modules\Inventario\Controllers\ProductoController::class);
        Route::get('productos/{producto}/kardex', [\App\Modules\Inventario\Controllers\ProductoController::class, 'kardex']);
        Route::get('productos/{producto}/stock-almacenes', [\App\Modules\Inventario\Controllers\ProductoController::class, 'stockAlmacenes']);
        Route::get('productos-stock-bajo', [\App\Modules\Inventario\Controllers\ProductoController::class, 'stockBajo']);

        // Imágenes de productos
        Route::get('productos/{producto}/imagenes', [\App\Modules\Inventario\Controllers\ProductoController::class, 'imagenes']);
        Route::post('productos/{producto}/imagenes', [\App\Modules\Inventario\Controllers\ProductoController::class, 'subirImagenes']);
        Route::delete('productos/{producto}/imagenes/{imagen}', [\App\Modules\Inventario\Controllers\ProductoController::class, 'eliminarImagen']);
        Route::put('productos/{producto}/imagenes/{imagen}/principal', [\App\Modules\Inventario\Controllers\ProductoController::class, 'imagenPrincipal']);
        Route::put('productos/{producto}/imagenes/reordenar', [\App\Modules\Inventario\Controllers\ProductoController::class, 'reordenarImagenes']);

        // Movimientos
        Route::apiResource('movimientos', \App\Modules\Inventario\Controllers\MovimientoController::class);
        Route::post('movimientos/{movimiento}/anular', [\App\Modules\Inventario\Controllers\MovimientoController::class, 'anular']);
        Route::post('movimientos/{movimiento}/confirmar-recepcion', [\App\Modules\Inventario\Controllers\MovimientoController::class, 'confirmarRecepcion']);

        // Kardex
        Route::get('kardex', [\App\Modules\Inventario\Controllers\KardexController::class, 'index']);
        Route::get('kardex/reporte', [\App\Modules\Inventario\Controllers\KardexController::class, 'reporte']);
        Route::get('kardex/exportar', [\App\Modules\Inventario\Controllers\KardexController::class, 'exportar']);
    });

    // -----------------------------------------------------------------
    // MÓDULO: REQUISICIONES
    // -----------------------------------------------------------------
    Route::prefix('requisiciones')->group(function () {
        Route::get('/', [\App\Modules\Requisiciones\Controllers\RequisicionController::class, 'index']);
        Route::post('/', [\App\Modules\Requisiciones\Controllers\RequisicionController::class, 'store']);
        Route::get('/estadisticas', [\App\Modules\Requisiciones\Controllers\RequisicionController::class, 'estadisticas']);
        Route::get('/{requisicion}', [\App\Modules\Requisiciones\Controllers\RequisicionController::class, 'show']);
        Route::put('/{requisicion}', [\App\Modules\Requisiciones\Controllers\RequisicionController::class, 'update']);
        Route::delete('/{requisicion}', [\App\Modules\Requisiciones\Controllers\RequisicionController::class, 'destroy']);
        Route::post('/{requisicion}/enviar-aprobacion', [\App\Modules\Requisiciones\Controllers\RequisicionController::class, 'enviarAprobacion']);
        Route::post('/{requisicion}/aprobar', [\App\Modules\Requisiciones\Controllers\RequisicionController::class, 'aprobar']);
        Route::post('/{requisicion}/rechazar', [\App\Modules\Requisiciones\Controllers\RequisicionController::class, 'rechazar']);
        Route::post('/{requisicion}/anular', [\App\Modules\Requisiciones\Controllers\RequisicionController::class, 'anular']);
        Route::post('/{requisicion}/generar-vale', [\App\Modules\Requisiciones\Controllers\ValeSalidaController::class, 'crearDesdeRequisicion']);
    });

    // -----------------------------------------------------------------
    // MÓDULO: VALES DE SALIDA
    // -----------------------------------------------------------------
    Route::prefix('vales-salida')->group(function () {
        Route::get('/', [\App\Modules\Requisiciones\Controllers\ValeSalidaController::class, 'index']);
        Route::post('/', [\App\Modules\Requisiciones\Controllers\ValeSalidaController::class, 'store']);
        Route::get('/estadisticas', [\App\Modules\Requisiciones\Controllers\ValeSalidaController::class, 'estadisticas']);
        Route::get('/personal', [\App\Modules\Requisiciones\Controllers\ValeSalidaController::class, 'personalReceptores']);
        Route::get('/requisiciones-aprobadas', [\App\Modules\Requisiciones\Controllers\ValeSalidaController::class, 'requisicionesAprobadas']);
        Route::get('/{valeSalida}', [\App\Modules\Requisiciones\Controllers\ValeSalidaController::class, 'show']);
        Route::post('/{valeSalida}/entregar', [\App\Modules\Requisiciones\Controllers\ValeSalidaController::class, 'entregar']);
        Route::post('/{valeSalida}/anular', [\App\Modules\Requisiciones\Controllers\ValeSalidaController::class, 'anular']);
    });

    // -----------------------------------------------------------------
    // MÓDULO: PROVEEDORES
    // -----------------------------------------------------------------
    Route::prefix('proveedores')->group(function () {
        Route::get('/', [\App\Modules\Proveedores\Controllers\ProveedorController::class, 'index']);
        Route::post('/', [\App\Modules\Proveedores\Controllers\ProveedorController::class, 'store']);
        Route::get('/{proveedor}', [\App\Modules\Proveedores\Controllers\ProveedorController::class, 'show']);
        Route::put('/{proveedor}', [\App\Modules\Proveedores\Controllers\ProveedorController::class, 'update']);
        Route::delete('/{proveedor}', [\App\Modules\Proveedores\Controllers\ProveedorController::class, 'destroy']);
        Route::get('/validar-ruc/{ruc}', [\App\Modules\Proveedores\Controllers\ProveedorController::class, 'validarRuc']);
        Route::post('/{proveedor}/calificar', [\App\Modules\Proveedores\Controllers\ProveedorController::class, 'calificar']);
    });

    // -----------------------------------------------------------------
    // MÓDULO: COTIZACIONES
    // -----------------------------------------------------------------
    Route::prefix('cotizaciones')->group(function () {
        Route::get('/', [\App\Modules\Compras\Controllers\CotizacionController::class, 'index']);
        Route::post('/', [\App\Modules\Compras\Controllers\CotizacionController::class, 'store']);
        Route::get('/estadisticas', [\App\Modules\Compras\Controllers\CotizacionController::class, 'estadisticas']);
        Route::get('/aprobadas', [\App\Modules\Compras\Controllers\CotizacionController::class, 'aprobadas']);
        Route::get('/{cotizacion}', [\App\Modules\Compras\Controllers\CotizacionController::class, 'show']);
        Route::put('/{cotizacion}', [\App\Modules\Compras\Controllers\CotizacionController::class, 'update']);
        Route::delete('/{cotizacion}', [\App\Modules\Compras\Controllers\CotizacionController::class, 'destroy']);
        Route::post('/{cotizacion}/enviar', [\App\Modules\Compras\Controllers\CotizacionController::class, 'enviar']);
        Route::post('/{cotizacion}/registrar-respuesta', [\App\Modules\Compras\Controllers\CotizacionController::class, 'registrarRespuesta']);
        Route::post('/{cotizacion}/aprobar', [\App\Modules\Compras\Controllers\CotizacionController::class, 'aprobar']);
        Route::post('/{cotizacion}/rechazar', [\App\Modules\Compras\Controllers\CotizacionController::class, 'rechazar']);
    });

    // -----------------------------------------------------------------
    // MÓDULO: ÓRDENES DE COMPRA
    // -----------------------------------------------------------------
    Route::prefix('ordenes-compra')->group(function () {
        Route::get('/', [\App\Modules\Compras\Controllers\OrdenCompraController::class, 'index']);
        Route::post('/', [\App\Modules\Compras\Controllers\OrdenCompraController::class, 'store']);
        Route::get('/estadisticas', [\App\Modules\Compras\Controllers\OrdenCompraController::class, 'estadisticas']);
        Route::get('/{ordenCompra}', [\App\Modules\Compras\Controllers\OrdenCompraController::class, 'show']);
        Route::put('/{ordenCompra}', [\App\Modules\Compras\Controllers\OrdenCompraController::class, 'update']);
        Route::delete('/{ordenCompra}', [\App\Modules\Compras\Controllers\OrdenCompraController::class, 'destroy']);
        Route::post('/{ordenCompra}/enviar-aprobacion', [\App\Modules\Compras\Controllers\OrdenCompraController::class, 'enviarAprobacion']);
        Route::post('/{ordenCompra}/aprobar', [\App\Modules\Compras\Controllers\OrdenCompraController::class, 'aprobar']);
        Route::post('/{ordenCompra}/enviar-proveedor', [\App\Modules\Compras\Controllers\OrdenCompraController::class, 'enviarProveedor']);
        Route::post('/{ordenCompra}/recibir', [\App\Modules\Compras\Controllers\OrdenCompraController::class, 'recibir']);
        Route::post('/{ordenCompra}/anular', [\App\Modules\Compras\Controllers\OrdenCompraController::class, 'anular']);
    });

    // -----------------------------------------------------------------
    // MÓDULO: REPORTES
    // -----------------------------------------------------------------
    Route::prefix('reportes')->group(function () {
        Route::get('/kardex', [\App\Modules\Reportes\Controllers\ReporteController::class, 'kardex']);
        Route::get('/kardex/exportar', [\App\Modules\Reportes\Controllers\ReporteController::class, 'exportarKardexExcel']);
        Route::get('/kardex/exportar-pdf', [\App\Modules\Reportes\Controllers\ReporteController::class, 'exportarKardexPdf']);
        Route::get('/inventario', [\App\Modules\Reportes\Controllers\ReporteController::class, 'inventario']);
        Route::get('/inventario/exportar', [\App\Modules\Reportes\Controllers\ReporteController::class, 'exportarInventarioExcel']);
        Route::get('/inventario/exportar-pdf', [\App\Modules\Reportes\Controllers\ReporteController::class, 'exportarInventarioPdf']);
        Route::get('/movimientos', [\App\Modules\Reportes\Controllers\ReporteController::class, 'movimientos']);
        Route::get('/movimientos/exportar', [\App\Modules\Reportes\Controllers\ReporteController::class, 'exportarMovimientosExcel']);
        Route::get('/movimientos/exportar-pdf', [\App\Modules\Reportes\Controllers\ReporteController::class, 'exportarMovimientosPdf']);
        Route::get('/consumo-centro-costo', [\App\Modules\Reportes\Controllers\ReporteController::class, 'consumoCentroCosto']);
        Route::get('/stock-bajo', [\App\Modules\Reportes\Controllers\ReporteController::class, 'stockBajo']);
        Route::get('/requisiciones', [\App\Modules\Reportes\Controllers\ReporteController::class, 'requisiciones']);
        Route::get('/top-productos', [\App\Modules\Reportes\Controllers\ReporteController::class, 'topProductos']);
        Route::get('/dashboard', [\App\Modules\Reportes\Controllers\ReporteController::class, 'dashboard']);
        Route::get('/dashboard/graficos', [\App\Modules\Reportes\Controllers\ReporteController::class, 'dashboardGraficos']);
    });

    // -----------------------------------------------------------------
    // MÓDULO: EPPs (Equipos de Protección Personal)
    // -----------------------------------------------------------------
    Route::prefix('epps')->group(function () {
        // Tipos de EPP
        Route::get('/tipos', [\App\Modules\EPPs\Controllers\EppController::class, 'tiposIndex']);
        Route::post('/tipos', [\App\Modules\EPPs\Controllers\EppController::class, 'tiposStore']);
        Route::get('/tipos/{tipoEpp}', [\App\Modules\EPPs\Controllers\EppController::class, 'tiposShow']);
        Route::put('/tipos/{tipoEpp}', [\App\Modules\EPPs\Controllers\EppController::class, 'tiposUpdate']);
        Route::delete('/tipos/{tipoEpp}', [\App\Modules\EPPs\Controllers\EppController::class, 'tiposDestroy']);

        // Asignaciones
        Route::get('/asignaciones', [\App\Modules\EPPs\Controllers\EppController::class, 'asignacionesIndex']);
        Route::post('/asignaciones', [\App\Modules\EPPs\Controllers\EppController::class, 'asignacionesStore']);
        Route::get('/asignaciones/{asignacion}', [\App\Modules\EPPs\Controllers\EppController::class, 'asignacionesShow']);
        Route::post('/asignaciones/{asignacion}/confirmar', [\App\Modules\EPPs\Controllers\EppController::class, 'confirmarRecepcion']);
        Route::post('/asignaciones/{asignacion}/devolver', [\App\Modules\EPPs\Controllers\EppController::class, 'registrarDevolucion']);
        Route::post('/asignaciones/{asignacion}/cambiar-estado', [\App\Modules\EPPs\Controllers\EppController::class, 'cambiarEstado']);
        Route::post('/asignaciones/{asignacion}/renovar', [\App\Modules\EPPs\Controllers\EppController::class, 'renovar']);

        // Historial y estadísticas
        Route::get('/trabajador/{trabajadorId}/historial', [\App\Modules\EPPs\Controllers\EppController::class, 'historialTrabajador']);
        Route::get('/estadisticas', [\App\Modules\EPPs\Controllers\EppController::class, 'estadisticas']);
        Route::get('/alertas-vencimiento', [\App\Modules\EPPs\Controllers\EppController::class, 'alertasVencimiento']);
        Route::post('/actualizar-estados', [\App\Modules\EPPs\Controllers\EppController::class, 'actualizarEstados']);
        Route::get('/categorias', [\App\Modules\EPPs\Controllers\EppController::class, 'categorias']);

        // Productos y familias EPP (para vincular a TipoEpp)
        Route::get('/familias-epp', [\App\Modules\EPPs\Controllers\EppController::class, 'familiasEpp']);
        Route::get('/productos-epp', [\App\Modules\EPPs\Controllers\EppController::class, 'productosEpp']);
        Route::get('/categorias-epp', [\App\Modules\EPPs\Controllers\EppController::class, 'categoriasEpp']);

        // Personal para asignación (trabajadores + usuarios)
        Route::get('/personal', [\App\Modules\EPPs\Controllers\EppController::class, 'personalParaEpp']);
    });

    // -----------------------------------------------------------------
    // MÓDULO: NOTIFICACIONES
    // -----------------------------------------------------------------
    Route::prefix('notificaciones')->group(function () {
        Route::get('/', [\App\Modules\Notificaciones\Controllers\NotificacionController::class, 'index']);
        Route::get('/contar', [\App\Modules\Notificaciones\Controllers\NotificacionController::class, 'contarNoLeidas']);
        Route::get('/resumen', [\App\Modules\Notificaciones\Controllers\NotificacionController::class, 'resumen']);
        Route::post('/generar', [\App\Modules\Notificaciones\Controllers\NotificacionController::class, 'generar']);
        Route::post('/marcar-todas-leidas', [\App\Modules\Notificaciones\Controllers\NotificacionController::class, 'marcarTodasLeidas']);
        Route::post('/limpiar', [\App\Modules\Notificaciones\Controllers\NotificacionController::class, 'limpiar']);
        Route::post('/{notificacion}/marcar-leida', [\App\Modules\Notificaciones\Controllers\NotificacionController::class, 'marcarLeida']);
        Route::delete('/{notificacion}', [\App\Modules\Notificaciones\Controllers\NotificacionController::class, 'destroy']);
    });

    // -----------------------------------------------------------------
    // MÓDULO: PRÉSTAMOS DE EQUIPOS
    // -----------------------------------------------------------------
    Route::prefix('prestamos')->group(function () {
        // Equipos prestables
        Route::get('/equipos', [\App\Modules\Prestamos\Controllers\PrestamoController::class, 'indexEquipos']);
        Route::post('/equipos', [\App\Modules\Prestamos\Controllers\PrestamoController::class, 'storeEquipo']);
        Route::get('/equipos/disponibles', [\App\Modules\Prestamos\Controllers\PrestamoController::class, 'equiposDisponibles']);
        Route::get('/equipos/{equipo}', [\App\Modules\Prestamos\Controllers\PrestamoController::class, 'showEquipo']);
        Route::put('/equipos/{equipo}', [\App\Modules\Prestamos\Controllers\PrestamoController::class, 'updateEquipo']);
        Route::delete('/equipos/{equipo}', [\App\Modules\Prestamos\Controllers\PrestamoController::class, 'destroyEquipo']);
        Route::get('/equipos/{equipo}/historial', [\App\Modules\Prestamos\Controllers\PrestamoController::class, 'historialEquipo']);

        // Importar productos del inventario como equipos prestables
        Route::get('/familias-prestables', [\App\Modules\Prestamos\Controllers\PrestamoController::class, 'familiasPrestables']);
        Route::get('/productos-para-importar', [\App\Modules\Prestamos\Controllers\PrestamoController::class, 'productosParaImportar']);
        Route::post('/importar-productos', [\App\Modules\Prestamos\Controllers\PrestamoController::class, 'importarProductos']);

        // Personal y centros de costo para préstamos
        Route::get('/personal', [\App\Modules\Prestamos\Controllers\PrestamoController::class, 'personalParaPrestamos']);
        Route::get('/centros-costo', [\App\Modules\Prestamos\Controllers\PrestamoController::class, 'centrosCostoParaPrestamos']);

        // Préstamos
        Route::get('/', [\App\Modules\Prestamos\Controllers\PrestamoController::class, 'index']);
        Route::post('/', [\App\Modules\Prestamos\Controllers\PrestamoController::class, 'store']);
        Route::get('/estadisticas', [\App\Modules\Prestamos\Controllers\PrestamoController::class, 'estadisticas']);
        Route::get('/trabajador/{trabajadorId}/historial', [\App\Modules\Prestamos\Controllers\PrestamoController::class, 'historialTrabajador']);
        Route::post('/procesar-vencidos', [\App\Modules\Prestamos\Controllers\PrestamoController::class, 'procesarVencidos']);
        Route::get('/{prestamo}', [\App\Modules\Prestamos\Controllers\PrestamoController::class, 'show']);
        Route::post('/{prestamo}/devolver', [\App\Modules\Prestamos\Controllers\PrestamoController::class, 'devolver']);
        Route::post('/{prestamo}/renovar', [\App\Modules\Prestamos\Controllers\PrestamoController::class, 'renovar']);
    });

});
