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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('familia_id')->nullable()->constrained('familias')->nullOnDelete();
            $table->string('codigo', 30);
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('unidad_medida', 10); // UND, PAR, GAL, LT, KG, MT, CJA, BLS
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->integer('stock_minimo')->default(0);
            $table->integer('stock_maximo')->default(0);
            $table->string('ubicacion_fisica')->nullable();
            $table->boolean('requiere_lote')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['empresa_id', 'codigo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
