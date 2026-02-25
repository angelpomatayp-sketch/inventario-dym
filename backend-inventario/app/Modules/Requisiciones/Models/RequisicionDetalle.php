<?php

namespace App\Modules\Requisiciones\Models;

use App\Modules\Inventario\Models\Producto;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisicionDetalle extends Model
{
    use HasFactory;

    protected $table = 'requisiciones_detalle';

    protected $fillable = [
        'requisicion_id',
        'producto_id',
        'cantidad_solicitada',
        'cantidad_aprobada',
        'cantidad_entregada',
        'especificaciones',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_solicitada' => 'decimal:2',
            'cantidad_aprobada' => 'decimal:2',
            'cantidad_entregada' => 'decimal:2',
        ];
    }

    // ==================== RELACIONES ====================

    public function requisicion(): BelongsTo
    {
        return $this->belongsTo(Requisicion::class, 'requisicion_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    // ==================== METODOS ====================

    /**
     * Obtener cantidad pendiente de entregar.
     */
    public function getCantidadPendienteAttribute(): float
    {
        $cantidadBase = $this->cantidad_aprobada ?? $this->cantidad_solicitada;
        return max(0, $cantidadBase - $this->cantidad_entregada);
    }

    /**
     * Verificar si esta completamente entregado.
     */
    public function estaCompleto(): bool
    {
        return $this->cantidad_pendiente <= 0;
    }

    /**
     * Registrar entrega.
     */
    public function registrarEntrega(float $cantidad): void
    {
        $this->increment('cantidad_entregada', $cantidad);
    }
}
