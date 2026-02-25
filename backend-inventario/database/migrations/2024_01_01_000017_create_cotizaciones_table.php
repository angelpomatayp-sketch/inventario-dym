<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('numero', 20)->unique();
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('restrict');
            $table->foreignId('solicitado_por')->constrained('usuarios')->onDelete('restrict');
            $table->date('fecha_solicitud');
            $table->date('fecha_vencimiento')->nullable();
            $table->enum('estado', ['BORRADOR', 'ENVIADA', 'RECIBIDA', 'APROBADA', 'RECHAZADA', 'VENCIDA', 'ANULADA'])->default('BORRADOR');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('igv', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('moneda', 3)->default('PEN');
            $table->decimal('tipo_cambio', 8, 4)->default(1);
            $table->text('condiciones_pago')->nullable();
            $table->integer('tiempo_entrega_dias')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('aprobado_por')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['empresa_id', 'estado']);
            $table->index(['proveedor_id', 'estado']);
        });

        Schema::create('cotizaciones_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('restrict');
            $table->decimal('cantidad', 12, 2);
            $table->decimal('precio_unitario', 12, 4);
            $table->decimal('descuento', 5, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->text('especificaciones')->nullable();
            $table->timestamps();

            $table->index('cotizacion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones_detalle');
        Schema::dropIfExists('cotizaciones');
    }
};
