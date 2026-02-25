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
        Schema::create('vales_salida', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('numero', 20)->unique();
            $table->foreignId('requisicion_id')->nullable()->constrained('requisiciones')->onDelete('set null');
            $table->foreignId('almacen_id')->constrained('almacenes')->onDelete('restrict');
            $table->foreignId('centro_costo_id')->constrained('centros_costos')->onDelete('restrict');
            $table->foreignId('solicitante_id')->constrained('usuarios')->onDelete('restrict');
            $table->foreignId('despachador_id')->constrained('usuarios')->onDelete('restrict');
            $table->foreignId('receptor_id')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->date('fecha');
            $table->enum('estado', ['PENDIENTE', 'ENTREGADO', 'PARCIAL', 'ANULADO'])->default('PENDIENTE');
            $table->string('receptor_nombre')->nullable();
            $table->string('receptor_dni', 15)->nullable();
            $table->text('motivo')->nullable();
            $table->text('observaciones')->nullable();

            // Referencia al movimiento generado
            $table->foreignId('movimiento_id')->nullable()->constrained('movimientos')->onDelete('set null');

            // Anulacion
            $table->foreignId('anulado_por')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->timestamp('fecha_anulacion')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['empresa_id', 'estado']);
            $table->index(['empresa_id', 'fecha']);
            $table->index(['requisicion_id']);
            $table->index(['almacen_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vales_salida');
    }
};
