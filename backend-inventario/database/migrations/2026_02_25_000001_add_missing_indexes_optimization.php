<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // productos: falta índice compuesto (empresa_id, activo) para listados frecuentes
        // y (empresa_id, familia_id) para filtrar por familia dentro de empresa
        Schema::table('productos', function (Blueprint $table) {
            $table->index(['empresa_id', 'activo'], 'productos_empresa_id_activo_index');
            $table->index(['empresa_id', 'familia_id'], 'productos_empresa_id_familia_id_index');
        });

        // proveedores: falta (empresa_id, activo) para listar proveedores activos
        Schema::table('proveedores', function (Blueprint $table) {
            $table->index(['empresa_id', 'activo'], 'proveedores_empresa_id_activo_index');
        });

        // movimientos: falta (empresa_id, tipo, estado) para filtros combinados en reportes
        Schema::table('movimientos', function (Blueprint $table) {
            $table->index(['empresa_id', 'tipo', 'estado'], 'movimientos_empresa_id_tipo_estado_index');
        });

        // movimientos_detalle: falta (movimiento_id, producto_id) compuesto
        // para queries de "qué productos tiene este movimiento"
        Schema::table('movimientos_detalle', function (Blueprint $table) {
            $table->index(['movimiento_id', 'producto_id'], 'movimientos_detalle_movimiento_producto_index');
        });

        // requisiciones_detalle: falta (producto_id, requisicion_id) compuesto
        Schema::table('requisiciones_detalle', function (Blueprint $table) {
            $table->index(['producto_id', 'requisicion_id'], 'requisiciones_detalle_producto_requisicion_index');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex('productos_empresa_id_activo_index');
            $table->dropIndex('productos_empresa_id_familia_id_index');
        });

        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropIndex('proveedores_empresa_id_activo_index');
        });

        Schema::table('movimientos', function (Blueprint $table) {
            $table->dropIndex('movimientos_empresa_id_tipo_estado_index');
        });

        Schema::table('movimientos_detalle', function (Blueprint $table) {
            $table->dropIndex('movimientos_detalle_movimiento_producto_index');
        });

        Schema::table('requisiciones_detalle', function (Blueprint $table) {
            $table->dropIndex('requisiciones_detalle_producto_requisicion_index');
        });
    }
};
