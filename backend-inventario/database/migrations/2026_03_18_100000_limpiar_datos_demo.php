<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Auditoría
        DB::table('audits')->delete();

        // Imágenes de productos
        DB::table('producto_imagenes')->delete();

        // EPPs
        DB::table('asignaciones_epp')->delete();
        DB::table('renovaciones_epp')->delete();
        DB::table('equipos_prestables')->delete();
        DB::table('prestamos_equipos')->delete();

        // Compras
        DB::table('cotizaciones_detalle')->delete();
        DB::table('cotizaciones')->delete();
        DB::table('ordenes_compra_detalle')->delete();
        DB::table('ordenes_compra')->delete();

        // Proveedores
        DB::table('proveedores')->delete();

        // Trabajadores
        DB::table('trabajadores')->delete();

        // Requerimientos
        DB::table('requerimientos_detalle')->delete();
        DB::table('requerimientos')->delete();

        // Vales de salida
        DB::table('vales_salida_detalle')->delete();
        DB::table('vales_salida')->delete();

        // Movimientos e inventario
        DB::table('movimientos_detalle')->delete();
        DB::table('movimientos')->delete();
        DB::table('kardex')->delete();
        DB::table('stock_almacen')->delete();
        DB::table('productos')->delete();

        // Notificaciones
        DB::table('notificaciones')->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        // No reversible
    }
};
