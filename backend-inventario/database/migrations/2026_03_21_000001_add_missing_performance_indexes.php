<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // vales_salida: (empresa_id, fecha) y (empresa_id, estado)
        $this->addIndexIfMissing('vales_salida', 'idx_vales_salida_empresa_fecha', '(empresa_id, fecha)');
        $this->addIndexIfMissing('vales_salida', 'idx_vales_salida_empresa_estado', '(empresa_id, estado)');

        // asignaciones_epp: (empresa_id, trabajador_id) para historialTrabajador
        $this->addIndexIfMissing('asignaciones_epp', 'idx_asignaciones_empresa_trabajador', '(empresa_id, trabajador_id)');
        // asignaciones_epp: (empresa_id, estado, fecha_vencimiento) para alertas y actualizarEstados
        $this->addIndexIfMissing('asignaciones_epp', 'idx_asignaciones_empresa_estado_vencimiento', '(empresa_id, estado, fecha_vencimiento)');

        // trabajadores: (empresa_id, activo) y (empresa_id, centro_costo_id)
        $this->addIndexIfMissing('trabajadores', 'idx_trabajadores_empresa_activo', '(empresa_id, activo)');
        $this->addIndexIfMissing('trabajadores', 'idx_trabajadores_empresa_centro_costo', '(empresa_id, centro_costo_id)');

        // notificaciones: (empresa_id, entidad_tipo, entidad_id) para los EXISTS de almacenero
        $this->addIndexIfMissing('notificaciones', 'idx_notificaciones_entidad', '(empresa_id, entidad_tipo, entidad_id)');
        // notificaciones: (empresa_id, tipo, entidad_tipo, created_at) para bulk-check de generación
        $this->addIndexIfMissing('notificaciones', 'idx_notificaciones_tipo_entidad_created', '(empresa_id, tipo, entidad_tipo, created_at)');

        // kardex: (empresa_id, producto_id, fecha, id) para saldo inicial en reporte
        $this->addIndexIfMissing('kardex', 'idx_kardex_empresa_producto_fecha', '(empresa_id, producto_id, fecha, id)');

        // movimientos: (empresa_id, numero) para búsqueda de número duplicado en generación
        $this->addIndexIfMissing('movimientos', 'idx_movimientos_empresa_numero', '(empresa_id, numero)');

        // vales_salida: (empresa_id, numero) para búsqueda en generarNumero
        $this->addIndexIfMissing('vales_salida', 'idx_vales_salida_empresa_numero', '(empresa_id, numero)');

        // requerimientos (requisiciones): (empresa_id, estado) para filtros frecuentes
        $this->addIndexIfMissing('requerimientos', 'idx_requerimientos_empresa_estado', '(empresa_id, estado)');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('vales_salida', 'idx_vales_salida_empresa_fecha');
        $this->dropIndexIfExists('vales_salida', 'idx_vales_salida_empresa_estado');
        $this->dropIndexIfExists('asignaciones_epp', 'idx_asignaciones_empresa_trabajador');
        $this->dropIndexIfExists('asignaciones_epp', 'idx_asignaciones_empresa_estado_vencimiento');
        $this->dropIndexIfExists('trabajadores', 'idx_trabajadores_empresa_activo');
        $this->dropIndexIfExists('trabajadores', 'idx_trabajadores_empresa_centro_costo');
        $this->dropIndexIfExists('notificaciones', 'idx_notificaciones_entidad');
        $this->dropIndexIfExists('notificaciones', 'idx_notificaciones_tipo_entidad_created');
        $this->dropIndexIfExists('kardex', 'idx_kardex_empresa_producto_fecha');
        $this->dropIndexIfExists('movimientos', 'idx_movimientos_empresa_numero');
        $this->dropIndexIfExists('vales_salida', 'idx_vales_salida_empresa_numero');
        $this->dropIndexIfExists('requerimientos', 'idx_requerimientos_empresa_estado');
    }

    private function addIndexIfMissing(string $table, string $indexName, string $columnsSql): void
    {
        if (!$this->tableExists($table) || $this->indexExists($table, $indexName)) {
            return;
        }
        DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` {$columnsSql}");
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (!$this->tableExists($table) || !$this->indexExists($table, $indexName)) {
            return;
        }
        DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
    }

    private function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $result = DB::selectOne(
            "SELECT 1 FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1",
            [DB::getDatabaseName(), $table, $indexName]
        );
        return (bool) $result;
    }
};
