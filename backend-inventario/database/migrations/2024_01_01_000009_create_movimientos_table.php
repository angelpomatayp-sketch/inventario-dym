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
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('numero', 20);
            $table->enum('tipo', ['ENTRADA', 'SALIDA', 'TRANSFERENCIA', 'AJUSTE']);
            $table->string('subtipo', 30)->nullable(); // COMPRA, REQUISICION, DEVOLUCION, etc.
            $table->foreignId('almacen_origen_id')->nullable()->constrained('almacenes')->nullOnDelete();
            $table->foreignId('almacen_destino_id')->nullable()->constrained('almacenes')->nullOnDelete();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->foreignId('centro_costo_id')->nullable()->constrained('centros_costos')->nullOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->string('referencia_tipo')->nullable(); // OrdenCompra, Requisicion, etc.
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->date('fecha');
            $table->string('documento_referencia')->nullable(); // Factura, guía, etc.
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['PENDIENTE', 'COMPLETADO', 'ANULADO'])->default('COMPLETADO');
            $table->foreignId('anulado_por')->nullable()->constrained('usuarios');
            $table->timestamp('fecha_anulacion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['empresa_id', 'numero']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};
