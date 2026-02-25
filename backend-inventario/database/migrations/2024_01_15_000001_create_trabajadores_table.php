<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabla para registrar trabajadores que NO son usuarios del sistema.
     * Solo sirven para control documental: EPPs, Préstamos, Vales de Salida.
     */
    public function up(): void
    {
        Schema::create('trabajadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('centro_costo_id')->nullable()->constrained('centros_costos')->nullOnDelete();

            // Datos personales
            $table->string('nombre');
            $table->string('dni', 20)->nullable();
            $table->string('cargo', 100)->nullable();
            $table->string('telefono', 20)->nullable();

            // Datos laborales
            $table->date('fecha_ingreso')->nullable();
            $table->date('fecha_cese')->nullable();
            $table->boolean('activo')->default(true);

            // Observaciones
            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['empresa_id', 'centro_costo_id']);
            $table->index(['empresa_id', 'dni']);
            $table->index(['empresa_id', 'activo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trabajadores');
    }
};
