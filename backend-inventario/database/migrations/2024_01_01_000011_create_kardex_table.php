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
        Schema::create('kardex', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos');
            $table->foreignId('almacen_id')->constrained('almacenes');
            $table->foreignId('movimiento_id')->nullable()->constrained('movimientos')->nullOnDelete();
            $table->date('fecha');
            $table->enum('tipo_operacion', ['ENTRADA', 'SALIDA', 'AJUSTE_POSITIVO', 'AJUSTE_NEGATIVO', 'SALDO_INICIAL']);
            $table->string('documento_referencia')->nullable();
            $table->decimal('cantidad', 12, 2);
            $table->decimal('costo_unitario', 14, 4);
            $table->decimal('costo_total', 14, 4);
            $table->decimal('saldo_cantidad', 12, 2);
            $table->decimal('saldo_costo_unitario', 14, 4);
            $table->decimal('saldo_costo_total', 14, 4);
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'producto_id', 'fecha']);
            $table->index(['almacen_id', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kardex');
    }
};
