<?php

namespace App\Modules\Compras\Models;

use App\Modules\Inventario\Models\Producto;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenCompraDetalle extends Model
{
    use HasFactory;

    protected $table = 'ordenes_compra_detalle';

    protected $fillable = [
        'orden_compra_id',
        'producto_id',
        'cotizacion_detalle_id',
        'cantidad_solicitada',
        'cantidad_recibida',
        'precio_unitario',
        'descuento',
        'subtotal',
        'lote',
        'fecha_vencimiento',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_solicitada' => 'decimal:2',
            'cantidad_recibida' => 'decimal:2',
            'precio_unitario' => 'decimal:4',
            'descuento' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'fecha_vencimiento' => 'date',
        ];
    }

    // ==================== RELACIONES ====================

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function cotizacionDetalle(): BelongsTo
    {
        return $this->belongsTo(CotizacionDetalle::class, 'cotizacion_detalle_id');
    }

    // ==================== MÉTODOS ====================

    public function getCantidadPendienteAttribute(): float
    {
        return max(0, $this->cantidad_solicitada - $this->cantidad_recibida);
    }

    public function estaCompleto(): bool
    {
        return $this->cantidad_recibida >= $this->cantidad_solicitada;
    }

    public function registrarRecepcion(float $cantidad): void
    {
        $this->increment('cantidad_recibida', $cantidad);
    }

    public function calcularSubtotal(): float
    {
        $subtotal = $this->cantidad_solicitada * $this->precio_unitario;
        if ($this->descuento > 0) {
            $subtotal -= $subtotal * ($this->descuento / 100);
        }
        return round($subtotal, 2);
    }
}
