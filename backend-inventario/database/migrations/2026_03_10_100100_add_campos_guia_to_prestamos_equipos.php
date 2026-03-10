<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prestamos_equipos', function (Blueprint $table) {
            $table->string('numero_requerimiento', 100)->nullable()->after('motivo_prestamo');
            $table->string('numero_guia_ida', 100)->nullable()->after('numero_requerimiento');
            $table->string('numero_guia_retorno', 100)->nullable()->after('numero_guia_ida');
        });
    }

    public function down(): void
    {
        Schema::table('prestamos_equipos', function (Blueprint $table) {
            $table->dropColumn(['numero_requerimiento', 'numero_guia_ida', 'numero_guia_retorno']);
        });
    }
};
