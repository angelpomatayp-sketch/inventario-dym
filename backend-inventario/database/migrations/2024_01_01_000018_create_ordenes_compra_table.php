<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_compra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('numero', 20)->unique();
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('restrict');
            $table->foreignId('cotizacion_id')->nullable()->constrained('cotizaciones')->onDelete('set null');
            $table->foreignId('almacen_destino_id')->constrained('almacenes')->onDelete('restrict');
            $table->foreignId('solicitado_por')->constrained('usuarios')->onDelete('restrict');
            $table->date('fecha_emision');
            $table->date('fecha_entrega_esperada')->nullable();
            $table->date('fecha_recepcion')->nullable();
            $table->enum('estado', ['BORRADOR', 'PENDIENTE', 'APROBADA', 'ENVIADA', 'PARCIAL', 'RECIBIDA', 'ANULADA'])->default('BORRADOR');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('igv', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('moneda', 3)->default('PEN');
            $table->decimal('tipo_cambio', 8, 4)->default(1);
            $table->text('condiciones_pago')->nullable();
            $table->text('direccion_entrega')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('aprobado_por')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->foreignId('recibido_por')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->foreignId('movimiento_id')->nullable()->constrained('movimientos')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['empresa_id', 'estado']);
            $table->index(['proveedor_id', 'estado']);
            $table->index('fecha_emision');
        });

        Schema::create('ordenes_compra_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_id')->constrained('ordenes_compra')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('restrict');
            $table->foreignId('cotizacion_detalle_id')->nullable()->constrained('cotizaciones_detalle')->onDelete('set null');
            $table->decimal('cantidad_solicitada', 12, 2);
            $table->decimal('cantidad_recibida', 12, 2)->default(0);
            $table->decimal('precio_unitario', 12, 4);
            $table->decimal('descuento', 5, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->string('lote', 50)->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('orden_compra_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_compra_detalle');
        Schema::dropIfExists('ordenes_compra');
    }
};
