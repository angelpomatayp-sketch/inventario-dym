<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('movimientos', 'idx_movimientos_empresa_fecha_id', '(empresa_id, fecha, id)');
        $this->addIndexIfMissing('prestamos_equipos', 'idx_prestamos_empresa_created_id', '(empresa_id, created_at, id)');
        $this->addIndexIfMissing('asignaciones_epp', 'idx_asignaciones_empresa_fecha_entrega_id', '(empresa_id, fecha_entrega, id)');
        $this->addIndexIfMissing('kardex', 'idx_kardex_empresa_fecha_id', '(empresa_id, fecha, id)');
        $this->addIndexIfMissing('notificaciones', 'idx_notificaciones_empresa_leida_created', '(empresa_id, leida_en, created_at)');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('movimientos', 'idx_movimientos_empresa_fecha_id');
        $this->dropIndexIfExists('prestamos_equipos', 'idx_prestamos_empresa_created_id');
        $this->dropIndexIfExists('asignaciones_epp', 'idx_asignaciones_empresa_fecha_entrega_id');
        $this->dropIndexIfExists('kardex', 'idx_kardex_empresa_fecha_id');
        $this->dropIndexIfExists('notificaciones', 'idx_notificaciones_empresa_leida_created');
    }

    private function addIndexIfMissing(string $table, string $indexName, string $columnsSql): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` {$columnsSql}");
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (!$this->indexExists($table, $indexName)) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $dbName = DB::getDatabaseName();

        $result = DB::selectOne(
            "SELECT 1
             FROM information_schema.statistics
             WHERE table_schema = ?
               AND table_name = ?
               AND index_name = ?
             LIMIT 1",
            [$dbName, $table, $indexName]
        );

        return (bool) $result;
    }
};

