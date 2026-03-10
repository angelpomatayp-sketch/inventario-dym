<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos_prestables', function (Blueprint $table) {
            $table->string('anio', 10)->nullable()->after('modelo');
            $table->string('numero_motor', 100)->nullable()->after('anio');
            $table->string('dimensiones', 150)->nullable()->after('numero_motor');
            $table->string('color', 80)->nullable()->after('dimensiones');
            $table->string('situacion', 200)->nullable()->after('color');
        });
    }

    public function down(): void
    {
        Schema::table('equipos_prestables', function (Blueprint $table) {
            $table->dropColumn(['anio', 'numero_motor', 'dimensiones', 'color', 'situacion']);
        });
    }
};
