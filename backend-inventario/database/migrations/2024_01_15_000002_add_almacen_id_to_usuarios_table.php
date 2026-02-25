<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agregar almacen_id para asignar almaceneros a su almacén específico.
     */
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->foreignId('almacen_id')
                ->nullable()
                ->after('centro_costo_id')
                ->constrained('almacenes')
                ->nullOnDelete();

            $table->index(['empresa_id', 'almacen_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropForeign(['almacen_id']);
            $table->dropIndex(['empresa_id', 'almacen_id']);
            $table->dropColumn('almacen_id');
        });
    }
};
