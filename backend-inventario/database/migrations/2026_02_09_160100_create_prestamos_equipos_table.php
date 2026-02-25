<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestamos_equipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->string('numero', 50)->unique()->comment('PRE-2026-0001');

            // Equipo prestado
            $table->foreignId('equipo_id')->constrained('equipos_prestables');
            $table->integer('cantidad')->default(1)->comment('Para equipos con control por cantidad');

            // Responsable del préstamo (usuario/trabajador)
            $table->foreignId('trabajador_id')->constrained('usuarios');
            $table->foreignId('centro_costo_id')->nullable()->constrained('centros_costos');
            $table->string('area_destino', 200)->nullable();

            // Fechas
            $table->date('fecha_prestamo');
            $table->date('fecha_devolucion_esperada');
            $table->date('fecha_devolucion_real')->nullable();

            // Estado del préstamo
            $table->enum('estado', [
                'ACTIVO',           // Préstamo vigente
                'DEVUELTO',         // Devuelto en buen estado
                'VENCIDO',          // No devuelto a tiempo
                'RENOVADO',         // Extendido
                'PERDIDO',          // Equipo extraviado
                'DANADO'            // Devuelto con daños
            ])->default('ACTIVO');

            // Estado del equipo al devolver
            $table->enum('condicion_devolucion', [
                'BUENO',
                'REGULAR',
                'MALO',
                'PERDIDO'
            ])->nullable();

            // Usuarios que gestionan
            $table->foreignId('entregado_por')->constrained('usuarios');
            $table->foreignId('recibido_por')->nullable()->constrained('usuarios');

            // Información adicional
            $table->text('motivo_prestamo')->nullable();
            $table->text('observaciones_entrega')->nullable();
            $table->text('observaciones_devolucion')->nullable();

            // Renovaciones
            $table->integer('numero_renovaciones')->default(0);
            $table->date('fecha_devolucion_original')->nullable()->comment('Guarda fecha original si hay renovación');

            $table->timestamps();

            $table->index(['empresa_id', 'numero']);
            $table->index(['empresa_id', 'estado']);
            $table->index(['empresa_id', 'trabajador_id']);
            $table->index(['empresa_id', 'fecha_devolucion_esperada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamos_equipos');
    }
};
