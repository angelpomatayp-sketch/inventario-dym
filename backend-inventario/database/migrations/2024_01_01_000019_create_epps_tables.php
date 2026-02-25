<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tipos de EPP (cascos, guantes, lentes, etc.)
        Schema::create('tipos_epp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->string('categoria', 50); // CABEZA, OJOS, MANOS, PIES, CUERPO, RESPIRATORIO
            $table->integer('vida_util_dias')->default(365); // Duración estimada
            $table->integer('dias_alerta_vencimiento')->default(30); // Días antes para alertar
            $table->boolean('requiere_talla')->default(false);
            $table->string('tallas_disponibles')->nullable(); // S,M,L,XL o 38,39,40,41
            $table->foreignId('producto_id')->nullable()->constrained('productos')->onDelete('set null'); // Vinculado a inventario
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['empresa_id', 'categoria']);
            $table->index(['empresa_id', 'activo']);
        });

        // Asignaciones de EPP a trabajadores
        Schema::create('asignaciones_epp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('tipo_epp_id')->constrained('tipos_epp')->onDelete('restrict');
            $table->foreignId('trabajador_id')->constrained('usuarios')->onDelete('restrict');
            $table->foreignId('entregado_por')->constrained('usuarios')->onDelete('restrict');
            $table->string('numero_serie', 50)->nullable(); // Número de serie del EPP si aplica
            $table->string('talla', 10)->nullable();
            $table->integer('cantidad')->default(1);
            $table->date('fecha_entrega');
            $table->date('fecha_vencimiento'); // Calculada: fecha_entrega + vida_util_dias
            $table->date('fecha_devolucion')->nullable();
            $table->enum('estado', ['VIGENTE', 'POR_VENCER', 'VENCIDO', 'DEVUELTO', 'EXTRAVIADO', 'DAÑADO'])->default('VIGENTE');
            $table->text('observaciones')->nullable();
            $table->string('firma_trabajador')->nullable(); // Para almacenar firma digital si se implementa
            $table->boolean('confirmado_trabajador')->default(false);
            $table->timestamp('fecha_confirmacion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['empresa_id', 'estado']);
            $table->index(['trabajador_id', 'estado']);
            $table->index(['tipo_epp_id', 'estado']);
            $table->index('fecha_vencimiento');
        });

        // Historial de renovaciones
        Schema::create('renovaciones_epp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignacion_anterior_id')->constrained('asignaciones_epp')->onDelete('cascade');
            $table->foreignId('asignacion_nueva_id')->constrained('asignaciones_epp')->onDelete('cascade');
            $table->enum('motivo', ['VENCIMIENTO', 'DETERIORO', 'EXTRAVIO', 'CAMBIO_TALLA', 'OTRO']);
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renovaciones_epp');
        Schema::dropIfExists('asignaciones_epp');
        Schema::dropIfExists('tipos_epp');
    }
};
