<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vales_salida_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vale_salida_id')->constrained('vales_salida')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('restrict');
            $table->foreignId('requisicion_detalle_id')->nullable()->constrained('requisiciones_detalle')->onDelete('set null');
            $table->decimal('cantidad_solicitada', 12, 2);
            $table->decimal('cantidad_entregada', 12, 2)->default(0);
            $table->decimal('costo_unitario', 12, 4)->default(0);
            $table->decimal('costo_total', 12, 4)->default(0);
            $table->string('lote', 50)->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['vale_salida_id']);
            $table->index(['producto_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vales_salida_detalle');
    }
};
