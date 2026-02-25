<?php

namespace App\Modules\Compras\Models;

use App\Core\Tenancy\Traits\PerteneceAEmpresa;
use App\Modules\Administracion\Models\Almacen;
use App\Modules\Administracion\Models\Empresa;
use App\Modules\Administracion\Models\Usuario;
use App\Modules\Inventario\Models\Movimiento;
use App\Modules\Proveedores\Models\Proveedor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class OrdenCompra extends Model implements Auditable
{
    use HasFactory, SoftDeletes, PerteneceAEmpresa, AuditableTrait;

    protected $table = 'ordenes_compra';

    const ESTADO_BORRADOR = 'BORRADOR';
    const ESTADO_PENDIENTE = 'PENDIENTE';
    const ESTADO_APROBADA = 'APROBADA';
    const ESTADO_ENVIADA = 'ENVIADA';
    const ESTADO_PARCIAL = 'PARCIAL';
    const ESTADO_RECIBIDA = 'RECIBIDA';
    const ESTADO_ANULADA = 'ANULADA';

    protected $fillable = [
        'empresa_id',
        'numero',
        'proveedor_id',
        'cotizacion_id',
        'almacen_destino_id',
        'solicitado_por',
        'fecha_emision',
        'fecha_entrega_esperada',
        'fecha_recepcion',
        'estado',
        'subtotal',
        'igv',
        'total',
        'moneda',
        'tipo_cambio',
        'condiciones_pago',
        'direccion_entrega',
        'observaciones',
        'aprobado_por',
        'fecha_aprobacion',
        'recibido_por',
        'movimiento_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'date',
            'fecha_entrega_esperada' => 'date',
            'fecha_recepcion' => 'date',
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

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }

    public function almacenDestino(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_destino_id');
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'solicitado_por');
    }

    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'aprobado_por');
    }

    public function receptor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'recibido_por');
    }

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class, 'movimiento_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(OrdenCompraDetalle::class, 'orden_compra_id');
    }

    // ==================== SCOPES ====================

    public function scopePendientes($query)
    {
        return $query->whereIn('estado', [self::ESTADO_BORRADOR, self::ESTADO_PENDIENTE]);
    }

    public function scopeAprobadas($query)
    {
        return $query->where('estado', self::ESTADO_APROBADA);
    }

    public function scopeEnviadas($query)
    {
        return $query->where('estado', self::ESTADO_ENVIADA);
    }

    public function scopePorRecibir($query)
    {
        return $query->whereIn('estado', [self::ESTADO_ENVIADA, self::ESTADO_PARCIAL]);
    }

    public function scopeDelProveedor($query, $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    public function scopeDelPeriodo($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha_emision', [$fechaInicio, $fechaFin]);
    }

    // ==================== MÉTODOS ====================

    public function puedeEditarse(): bool
    {
        return in_array($this->estado, [self::ESTADO_BORRADOR]);
    }

    public function puedeAprobarse(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    public function puedeEnviarse(): bool
    {
        return $this->estado === self::ESTADO_APROBADA;
    }

    public function puedeRecibirse(): bool
    {
        return in_array($this->estado, [self::ESTADO_ENVIADA, self::ESTADO_PARCIAL]);
    }

    public function puedeAnularse(): bool
    {
        return !in_array($this->estado, [self::ESTADO_RECIBIDA, self::ESTADO_ANULADA]);
    }

    public function enviarAprobacion(): void
    {
        $this->update(['estado' => self::ESTADO_PENDIENTE]);
    }

    public function aprobar(int $usuarioId): void
    {
        $this->update([
            'estado' => self::ESTADO_APROBADA,
            'aprobado_por' => $usuarioId,
            'fecha_aprobacion' => now(),
        ]);
    }

    public function enviarProveedor(): void
    {
        $this->update(['estado' => self::ESTADO_ENVIADA]);
    }

    public function anular(int $usuarioId): void
    {
        $this->update(['estado' => self::ESTADO_ANULADA]);
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

    public function getPorcentajeRecepcionAttribute(): float
    {
        $totalSolicitado = $this->detalles()->sum('cantidad_solicitada');
        $totalRecibido = $this->detalles()->sum('cantidad_recibida');

        if ($totalSolicitado == 0) return 0;

        return round(($totalRecibido / $totalSolicitado) * 100, 2);
    }

    public function estaCompletamenteRecibida(): bool
    {
        return $this->detalles()->whereColumn('cantidad_recibida', '<', 'cantidad_solicitada')->count() === 0;
    }
}
