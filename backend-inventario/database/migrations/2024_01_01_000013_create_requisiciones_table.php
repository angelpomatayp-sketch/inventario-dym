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
        Schema::create('requisiciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('numero', 20)->unique();
            $table->foreignId('solicitante_id')->constrained('usuarios')->onDelete('restrict');
            $table->foreignId('centro_costo_id')->constrained('centros_costos')->onDelete('restrict');
            $table->foreignId('almacen_id')->nullable()->constrained('almacenes')->onDelete('set null');
            $table->date('fecha_solicitud');
            $table->date('fecha_requerida');
            $table->enum('prioridad', ['BAJA', 'NORMAL', 'ALTA', 'URGENTE'])->default('NORMAL');
            $table->enum('estado', ['BORRADOR', 'PENDIENTE', 'APROBADA', 'RECHAZADA', 'PARCIAL', 'COMPLETADA', 'ANULADA'])->default('BORRADOR');
            $table->text('motivo');
            $table->text('observaciones')->nullable();

            // Aprobacion
            $table->foreignId('aprobado_por')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->text('comentario_aprobacion')->nullable();

            // Anulacion
            $table->foreignId('anulado_por')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->timestamp('fecha_anulacion')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['empresa_id', 'estado']);
            $table->index(['empresa_id', 'fecha_solicitud']);
            $table->index(['solicitante_id']);
            $table->index(['centro_costo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisiciones');
    }
};
