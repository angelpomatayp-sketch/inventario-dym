<?php

namespace App\Modules\Requisiciones\Models;

use App\Modules\Inventario\Models\Producto;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValeSalidaDetalle extends Model
{
    use HasFactory;

    protected $table = 'vales_salida_detalle';

    protected $fillable = [
        'vale_salida_id',
        'producto_id',
        'requisicion_detalle_id',
        'cantidad_solicitada',
        'cantidad_entregada',
        'costo_unitario',
        'costo_total',
        'lote',
        'fecha_vencimiento',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_solicitada' => 'decimal:2',
            'cantidad_entregada' => 'decimal:2',
            'costo_unitario' => 'decimal:4',
            'costo_total' => 'decimal:4',
            'fecha_vencimiento' => 'date',
        ];
    }

    // ==================== RELACIONES ====================

    public function valeSalida(): BelongsTo
    {
        return $this->belongsTo(ValeSalida::class, 'vale_salida_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function requisicionDetalle(): BelongsTo
    {
        return $this->belongsTo(RequisicionDetalle::class, 'requisicion_detalle_id');
    }

    // ==================== METODOS ====================

    /**
     * Verificar si esta completamente entregado.
     */
    public function estaCompleto(): bool
    {
        return $this->cantidad_entregada >= $this->cantidad_solicitada;
    }

    /**
     * Obtener cantidad pendiente.
     */
    public function getCantidadPendienteAttribute(): float
    {
        return max(0, $this->cantidad_solicitada - $this->cantidad_entregada);
    }
}
