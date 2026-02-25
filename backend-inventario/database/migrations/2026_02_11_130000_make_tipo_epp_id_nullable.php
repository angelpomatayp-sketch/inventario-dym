<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Hace tipo_epp_id nullable ya que ahora se usa producto_id directamente.
     */
    public function up(): void
    {
        Schema::table('asignaciones_epp', function (Blueprint $table) {
            // Hacer tipo_epp_id nullable (ya no es requerido con el nuevo flujo)
            $table->unsignedBigInteger('tipo_epp_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asignaciones_epp', function (Blueprint $table) {
            $table->unsignedBigInteger('tipo_epp_id')->nullable(false)->change();
        });
    }
};
