<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Permite asignar préstamos tanto a trabajadores (sin login) como usuarios (con login).
     */
    public function up(): void
    {
        Schema::table('prestamos_equipos', function (Blueprint $table) {
            // Tipo de receptor: 'trabajador' (tabla trabajadores) o 'usuario' (tabla usuarios)
            $table->enum('tipo_receptor', ['trabajador', 'usuario'])->default('trabajador')->after('trabajador_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestamos_equipos', function (Blueprint $table) {
            $table->dropColumn('tipo_receptor');
        });
    }
};
