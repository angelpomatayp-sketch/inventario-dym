<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Simplificación: Los campos de EPP van directamente en el producto.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // Campos EPP (solo aplican si el producto está en una familia EPP)
            $table->integer('vida_util_dias')->nullable()->after('ubicacion_fisica');
            $table->integer('dias_alerta_vencimiento')->nullable()->after('vida_util_dias');
            $table->boolean('requiere_talla')->default(false)->after('dias_alerta_vencimiento');
            $table->string('tallas_disponibles', 255)->nullable()->after('requiere_talla');
        });

        // Modificar asignaciones_epp para usar producto_id directamente
        Schema::table('asignaciones_epp', function (Blueprint $table) {
            // Agregar producto_id
            $table->foreignId('producto_id')->nullable()->after('tipo_epp_id')->constrained('productos')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asignaciones_epp', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->dropColumn('producto_id');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn([
                'vida_util_dias',
                'dias_alerta_vencimiento',
                'requiere_talla',
                'tallas_disponibles'
            ]);
        });
    }
};
