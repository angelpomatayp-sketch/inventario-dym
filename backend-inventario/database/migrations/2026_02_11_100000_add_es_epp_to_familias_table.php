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
        Schema::table('familias', function (Blueprint $table) {
            $table->boolean('es_epp')->default(false)->after('descripcion');
            $table->string('categoria_epp', 50)->nullable()->after('es_epp');

            $table->index(['empresa_id', 'es_epp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('familias', function (Blueprint $table) {
            $table->dropIndex(['empresa_id', 'es_epp']);
            $table->dropColumn(['es_epp', 'categoria_epp']);
        });
    }
};
