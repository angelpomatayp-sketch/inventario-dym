<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipos_prestables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('producto_id')->nullable()->constrained('productos')->comment('Vinculado a inventario si aplica');

            // Identificación del equipo
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();
            $table->string('numero_serie', 100)->nullable();
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();

            // Clasificación
            $table->enum('tipo_control', ['INDIVIDUAL', 'CANTIDAD'])->default('INDIVIDUAL');
            $table->integer('cantidad_total')->default(1)->comment('Para control por cantidad');
            $table->integer('cantidad_disponible')->default(1);

            // Estado y ubicación
            $table->enum('estado', ['DISPONIBLE', 'PRESTADO', 'EN_MANTENIMIENTO', 'DADO_DE_BAJA'])->default('DISPONIBLE');
            $table->foreignId('almacen_id')->nullable()->constrained('almacenes');
            $table->string('ubicacion_fisica', 200)->nullable();

            // Información adicional
            $table->decimal('valor_referencial', 12, 2)->nullable();
            $table->date('fecha_adquisicion')->nullable();
            $table->text('notas')->nullable();
            $table->string('imagen', 500)->nullable();

            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['empresa_id', 'codigo']);
            $table->index(['empresa_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos_prestables');
    }
};
