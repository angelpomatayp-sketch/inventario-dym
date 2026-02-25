<?php

namespace App\Modules\Inventario\Models;

use App\Core\Tenancy\Traits\PerteneceAEmpresa;
use App\Modules\Administracion\Models\Almacen;
use App\Modules\Administracion\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAlmacen extends Model
{
    use HasFactory, PerteneceAEmpresa;

    protected $table = 'stock_almacen';

    protected $fillable = [
        'empresa_id',
        'producto_id',
        'almacen_id',
        'stock_actual',
        'stock_minimo',
        'stock_maximo',
        'costo_promedio',
    ];

    protected function casts(): array
    {
        return [
            'stock_actual' => 'decimal:2',
            'stock_minimo' => 'decimal:2',
            'stock_maximo' => 'decimal:2',
            'costo_promedio' => 'decimal:4',
        ];
    }

    // ==================== RELACIONES ====================

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    // ==================== SCOPES ====================

    public function scopeConStockBajo($query)
    {
        return $query->whereColumn('stock_actual', '<=', 'stock_minimo')
                     ->where('stock_minimo', '>', 0);
    }

    public function scopeSinStock($query)
    {
        return $query->where('stock_actual', '<=', 0);
    }

    // ==================== MÉTODOS ====================

    /**
     * Verificar si tiene stock bajo.
     */
    public function tieneStockBajo(): bool
    {
        return $this->stock_actual <= $this->stock_minimo && $this->stock_minimo > 0;
    }

    /**
     * Obtener valor total del stock.
     */
    public function getValorTotalAttribute(): float
    {
        return $this->stock_actual * $this->costo_promedio;
    }
}
