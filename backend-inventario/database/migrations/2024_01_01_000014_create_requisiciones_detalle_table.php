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
        Schema::create('requisiciones_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisicion_id')->constrained('requisiciones')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('restrict');
            $table->decimal('cantidad_solicitada', 12, 2);
            $table->decimal('cantidad_aprobada', 12, 2)->nullable();
            $table->decimal('cantidad_entregada', 12, 2)->default(0);
            $table->text('especificaciones')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['requisicion_id']);
            $table->index(['producto_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisiciones_detalle');
    }
};
