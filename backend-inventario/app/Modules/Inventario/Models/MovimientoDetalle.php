<?php

namespace App\Modules\Inventario\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoDetalle extends Model
{
    use HasFactory;

    protected $table = 'movimientos_detalle';

    protected $fillable = [
        'movimiento_id',
        'producto_id',
        'cantidad',
        'costo_unitario',
        'costo_total',
        'lote',
        'fecha_vencimiento',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'costo_unitario' => 'decimal:4',
            'costo_total' => 'decimal:4',
            'fecha_vencimiento' => 'date',
        ];
    }

    // ==================== RELACIONES ====================

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class, 'movimiento_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
