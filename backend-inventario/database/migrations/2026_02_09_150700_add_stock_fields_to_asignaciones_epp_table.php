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
        Schema::table('asignaciones_epp', function (Blueprint $table) {
            $table->foreignId('almacen_id')->nullable()->after('observaciones')->constrained('almacenes')->nullOnDelete();
            $table->foreignId('movimiento_id')->nullable()->after('almacen_id')->constrained('movimientos')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asignaciones_epp', function (Blueprint $table) {
            $table->dropForeign(['movimiento_id']);
            $table->dropForeign(['almacen_id']);
            $table->dropColumn(['movimiento_id', 'almacen_id']);
        });
    }
};
