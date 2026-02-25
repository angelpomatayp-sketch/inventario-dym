<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Esta migración NO agrega FK porque trabajador_id puede apuntar
     * tanto a usuarios como a trabajadores durante la transición.
     * La integridad se maneja a nivel de aplicación.
     */
    public function up(): void
    {
        // Solo eliminamos la FK existente si existe (para que no haya conflictos)
        Schema::table('prestamos_equipos', function (Blueprint $table) {
            // Verificar si existe la FK y eliminarla
            $foreignKeys = $this->getForeignKeys('prestamos_equipos');
            if (in_array('prestamos_equipos_trabajador_id_foreign', $foreignKeys)) {
                $table->dropForeign(['trabajador_id']);
            }
        });

        // Nota: No agregamos FK a trabajadores para permitir flexibilidad
        // La validación se hace en el Controller/Service
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nada que revertir
    }

    /**
     * Obtener nombres de foreign keys de una tabla
     */
    private function getForeignKeys(string $table): array
    {
        $database = config('database.connections.mysql.database');
        $result = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$database, $table]);

        return array_column($result, 'CONSTRAINT_NAME');
    }
};
