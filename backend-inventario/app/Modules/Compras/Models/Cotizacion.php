<?php

namespace App\Modules\Compras\Models;

use App\Core\Tenancy\Traits\PerteneceAEmpresa;
use App\Modules\Administracion\Models\Empresa;
use App\Modules\Administracion\Models\Usuario;
use App\Modules\Proveedores\Models\Proveedor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Cotizacion extends Model implements Auditable
{
    use HasFactory, SoftDeletes, PerteneceAEmpresa, AuditableTrait;

    protected $table = 'cotizaciones';

    const ESTADO_BORRADOR = 'BORRADOR';
    const ESTADO_ENVIADA = 'ENVIADA';
    const ESTADO_RECIBIDA = 'RECIBIDA';
    const ESTADO_APROBADA = 'APROBADA';
    const ESTADO_RECHAZADA = 'RECHAZADA';
    const ESTADO_VENCIDA = 'VENCIDA';
    const ESTADO_ANULADA = 'ANULADA';

    protected $fillable = [
        'empresa_id',
        'numero',
        'proveedor_id',
        'solicitado_por',
        'fecha_solicitud',
        'fecha_vencimiento',
        'estado',
        'subtotal',
        'igv',
        'total',
        'moneda',
        'tipo_cambio',
        'condiciones_pago',
        'tiempo_entrega_dias',
        'observaciones',
        'aprobado_por',
        'fecha_aprobacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_solicitud' => 'date',
            'fecha_vencimiento' => 'date',
            'fecha_aprobacion' => 'datetime',
            'subtotal' => 'decimal:2',
            'igv' => 'decimal:2',
            'total' => 'decimal:2',
            'tipo_cambio' => 'decimal:4',
        ];
    }

    // ==================== RELACIONES ====================

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'solicitado_por');
    }

    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'aprobado_por');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(CotizacionDetalle::class, 'cotizacion_id');
    }

    public function ordenCompra(): HasOne
    {
        return $this->hasOne(OrdenCompra::class, 'cotizacion_id');
    }

    // ==================== SCOPES ====================

    public function scopePendientes($query)
    {
        return $query->whereIn('estado', [self::ESTADO_BORRADOR, self::ESTADO_ENVIADA, self::ESTADO_RECIBIDA]);
    }

    public function scopeAprobadas($query)
    {
        return $query->where('estado', self::ESTADO_APROBADA);
    }

    public function scopeDelProveedor($query, $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    // ==================== MÉTODOS ====================

    public function puedeEditarse(): bool
    {
        return in_array($this->estado, [self::ESTADO_BORRADOR, self::ESTADO_RECHAZADA]);
    }

    public function puedeAprobarse(): bool
    {
        return $this->estado === self::ESTADO_RECIBIDA;
    }

    public function puedeGenerarOrden(): bool
    {
        return $this->estado === self::ESTADO_APROBADA && !$this->ordenCompra;
    }

    public function aprobar(int $usuarioId): void
    {
        $this->update([
            'estado' => self::ESTADO_APROBADA,
            'aprobado_por' => $usuarioId,
            'fecha_aprobacion' => now(),
        ]);
    }

    public function rechazar(int $usuarioId): void
    {
        $this->update([
            'estado' => self::ESTADO_RECHAZADA,
            'aprobado_por' => $usuarioId,
            'fecha_aprobacion' => now(),
        ]);
    }

    public function calcularTotales(): void
    {
        $subtotal = $this->detalles()->sum('subtotal');
        $igv = $subtotal * 0.18;
        $this->update([
            'subtotal' => $subtotal,
            'igv' => $igv,
            'total' => $subtotal + $igv,
        ]);
    }

    public function getTotalItemsAttribute(): int
    {
        return $this->detalles()->count();
    }
}
