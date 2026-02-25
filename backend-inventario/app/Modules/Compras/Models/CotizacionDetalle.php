<?php

namespace App\Modules\Compras\Models;

use App\Modules\Inventario\Models\Producto;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CotizacionDetalle extends Model
{
    use HasFactory;

    protected $table = 'cotizaciones_detalle';

    protected $fillable = [
        'cotizacion_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'descuento',
        'subtotal',
        'especificaciones',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'precio_unitario' => 'decimal:4',
            'descuento' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    // ==================== RELACIONES ====================

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    // ==================== MÉTODOS ====================

    public function calcularSubtotal(): float
    {
        $subtotal = $this->cantidad * $this->precio_unitario;
        if ($this->descuento > 0) {
            $subtotal -= $subtotal * ($this->descuento / 100);
        }
        return round($subtotal, 2);
    }
}
