<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Eliminar FKs que apuntan a requisiciones antes de renombrar
        Schema::table('vales_salida', function (Blueprint $table) {
            $table->dropForeign(['requisicion_id']);
        });

        Schema::table('vales_salida_detalle', function (Blueprint $table) {
            $table->dropForeign(['requisicion_detalle_id']);
        });

        // 2. Renombrar tablas
        Schema::rename('requisiciones', 'requerimientos');
        Schema::rename('requisiciones_detalle', 'requerimientos_detalle');

        // 3. Renombrar columna solicitante_id → almacenero_id en requerimientos
        Schema::table('requerimientos', function (Blueprint $table) {
            $table->renameColumn('solicitante_id', 'almacenero_id');
        });

        // 4. Restaurar FKs apuntando a las tablas renombradas
        Schema::table('vales_salida', function (Blueprint $table) {
            $table->foreign('requisicion_id')
                  ->references('id')
                  ->on('requerimientos')
                  ->nullOnDelete();
        });

        Schema::table('vales_salida_detalle', function (Blueprint $table) {
            $table->foreign('requisicion_detalle_id')
                  ->references('id')
                  ->on('requerimientos_detalle')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vales_salida', function (Blueprint $table) {
            $table->dropForeign(['requisicion_id']);
        });

        Schema::table('vales_salida_detalle', function (Blueprint $table) {
            $table->dropForeign(['requisicion_detalle_id']);
        });

        Schema::table('requerimientos', function (Blueprint $table) {
            $table->renameColumn('almacenero_id', 'solicitante_id');
        });

        Schema::rename('requerimientos', 'requisiciones');
        Schema::rename('requerimientos_detalle', 'requisiciones_detalle');

        Schema::table('vales_salida', function (Blueprint $table) {
            $table->foreign('requisicion_id')
                  ->references('id')
                  ->on('requisiciones')
                  ->nullOnDelete();
        });

        Schema::table('vales_salida_detalle', function (Blueprint $table) {
            $table->foreign('requisicion_detalle_id')
                  ->references('id')
                  ->on('requisiciones_detalle')
                  ->nullOnDelete();
        });
    }
};
