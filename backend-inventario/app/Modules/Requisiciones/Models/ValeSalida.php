<?php

namespace App\Modules\Requisiciones\Models;

use App\Core\Tenancy\Traits\PerteneceAEmpresa;
use App\Modules\Administracion\Models\Almacen;
use App\Modules\Administracion\Models\CentroCosto;
use App\Modules\Administracion\Models\Empresa;
use App\Modules\Administracion\Models\Usuario;
use App\Modules\Inventario\Models\Movimiento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class ValeSalida extends Model implements Auditable
{
    use HasFactory, SoftDeletes, PerteneceAEmpresa, AuditableTrait;

    protected $table = 'vales_salida';

    // Estados
    const ESTADO_PENDIENTE = 'PENDIENTE';
    const ESTADO_ENTREGADO = 'ENTREGADO';
    const ESTADO_PARCIAL = 'PARCIAL';
    const ESTADO_ANULADO = 'ANULADO';

    protected $fillable = [
        'empresa_id',
        'numero',
        'requisicion_id',
        'almacen_id',
        'centro_costo_id',
        'solicitante_id',
        'despachador_id',
        'receptor_id',
        'fecha',
        'estado',
        'receptor_nombre',
        'receptor_dni',
        'motivo',
        'observaciones',
        'movimiento_id',
        'anulado_por',
        'fecha_anulacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'fecha_anulacion' => 'datetime',
        ];
    }

    // ==================== RELACIONES ====================

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function requisicion(): BelongsTo
    {
        return $this->belongsTo(Requisicion::class, 'requisicion_id');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function centroCosto(): BelongsTo
    {
        return $this->belongsTo(CentroCosto::class, 'centro_costo_id');
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'solicitante_id');
    }

    public function despachador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'despachador_id');
    }

    public function receptor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'receptor_id');
    }

    public function anulador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'anulado_por');
    }

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class, 'movimiento_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(ValeSalidaDetalle::class, 'vale_salida_id');
    }

    // ==================== SCOPES ====================

    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopeEntregados($query)
    {
        return $query->where('estado', self::ESTADO_ENTREGADO);
    }

    public function scopeDelPeriodo($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    }

    // ==================== METODOS ====================

    /**
     * Verificar si puede ser editado.
     */
    public function puedeEditarse(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    /**
     * Verificar si puede ser anulado.
     */
    public function puedeAnularse(): bool
    {
        return $this->estado !== self::ESTADO_ANULADO;
    }

    /**
     * Marcar como entregado.
     */
    public function marcarEntregado(): void
    {
        $this->update(['estado' => self::ESTADO_ENTREGADO]);
    }

    /**
     * Marcar como parcial.
     */
    public function marcarParcial(): void
    {
        $this->update(['estado' => self::ESTADO_PARCIAL]);
    }

    /**
     * Anular vale.
     */
    public function anular(int $usuarioId): void
    {
        $this->update([
            'estado' => self::ESTADO_ANULADO,
            'anulado_por' => $usuarioId,
            'fecha_anulacion' => now(),
        ]);
    }

    /**
     * Obtener total de items.
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->detalles()->count();
    }

    /**
     * Obtener costo total del vale.
     */
    public function getCostoTotalAttribute(): float
    {
        return $this->detalles()->sum('costo_total');
    }
}
