<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega la relación entre almacenes y centros de costo.
     * Cada almacén puede pertenecer a un proyecto/centro de costo específico.
     */
    public function up(): void
    {
        Schema::table('almacenes', function (Blueprint $table) {
            $table->foreignId('centro_costo_id')
                ->nullable()
                ->after('empresa_id')
                ->constrained('centros_costos')
                ->nullOnDelete();

            $table->index('centro_costo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('almacenes', function (Blueprint $table) {
            $table->dropForeign(['centro_costo_id']);
            $table->dropColumn('centro_costo_id');
        });
    }
};
