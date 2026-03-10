<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar updated_at a stock_almacen si no existe
        if (Schema::hasTable('stock_almacen') && !Schema::hasColumn('stock_almacen', 'updated_at')) {
            Schema::table('stock_almacen', function (Blueprint $table) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            });
            // Inicializar con el valor de created_at
            DB::statement('UPDATE stock_almacen SET updated_at = created_at WHERE updated_at IS NULL');
        }

        // 2. Índice compuesto en stock_almacen (almacen_id, producto_id) — clave para queries de stock
        if (Schema::hasTable('stock_almacen')) {
            $indexes = $this->getIndexNames('stock_almacen');
            if (!in_array('stock_almacen_almacen_producto_idx', $indexes)) {
                Schema::table('stock_almacen', function (Blueprint $table) {
                    $table->index(['almacen_id', 'producto_id'], 'stock_almacen_almacen_producto_idx');
                });
            }
        }

        // 3. Índice compuesto en kardex (empresa_id, almacen_id, producto_id, fecha)
        if (Schema::hasTable('kardex')) {
            $indexes = $this->getIndexNames('kardex');
            if (!in_array('kardex_empresa_almacen_producto_fecha_idx', $indexes)) {
                Schema::table('kardex', function (Blueprint $table) {
                    $table->index(
                        ['empresa_id', 'almacen_id', 'producto_id', 'fecha'],
                        'kardex_empresa_almacen_producto_fecha_idx'
                    );
                });
            }
        }

        // 4. Cambiar stock_minimo y stock_maximo de integer a decimal(10,2) en productos
        if (Schema::hasTable('productos')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->decimal('stock_minimo', 10, 2)->default(0)->change();
                $table->decimal('stock_maximo', 10, 2)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stock_almacen')) {
            Schema::table('stock_almacen', function (Blueprint $table) {
                $table->dropIndexIfExists('stock_almacen_almacen_producto_idx');
                if (Schema::hasColumn('stock_almacen', 'updated_at')) {
                    $table->dropColumn('updated_at');
                }
            });
        }

        if (Schema::hasTable('kardex')) {
            Schema::table('kardex', function (Blueprint $table) {
                $table->dropIndexIfExists('kardex_empresa_almacen_producto_fecha_idx');
            });
        }

        if (Schema::hasTable('productos')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->integer('stock_minimo')->default(0)->change();
                $table->integer('stock_maximo')->nullable()->change();
            });
        }
    }

    private function getIndexNames(string $table): array
    {
        $dbName = DB::getDatabaseName();
        $rows = DB::select("
            SELECT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
        ", [$dbName, $table]);

        return array_map(fn($r) => $r->INDEX_NAME, $rows);
    }
};
