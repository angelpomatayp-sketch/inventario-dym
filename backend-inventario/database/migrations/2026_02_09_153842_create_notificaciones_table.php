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
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->onDelete('cascade');
            $table->string('tipo', 50); // STOCK_BAJO, EPP_VENCIMIENTO, REQUISICION_PENDIENTE, etc.
            $table->string('titulo');
            $table->text('mensaje');
            $table->string('icono', 50)->default('pi-bell');
            $table->string('severidad', 20)->default('info'); // info, warn, danger, success
            $table->string('entidad_tipo', 50)->nullable(); // productos, asignaciones_epp, requisiciones
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->string('url')->nullable(); // URL para navegar al detalle
            $table->timestamp('leida_en')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'usuario_id', 'leida_en']);
            $table->index(['tipo', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
