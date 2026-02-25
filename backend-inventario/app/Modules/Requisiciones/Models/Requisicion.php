<?php

namespace App\Modules\Requisiciones\Models;

use App\Core\Tenancy\Traits\PerteneceAEmpresa;
use App\Modules\Administracion\Models\Almacen;
use App\Modules\Administracion\Models\CentroCosto;
use App\Modules\Administracion\Models\Empresa;
use App\Modules\Administracion\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Requisicion extends Model implements Auditable
{
    use HasFactory, SoftDeletes, PerteneceAEmpresa, AuditableTrait;

    protected $table = 'requisiciones';

    // Estados
    const ESTADO_BORRADOR = 'BORRADOR';
    const ESTADO_PENDIENTE = 'PENDIENTE';
    const ESTADO_APROBADA = 'APROBADA';
    const ESTADO_RECHAZADA = 'RECHAZADA';
    const ESTADO_PARCIAL = 'PARCIAL';
    const ESTADO_COMPLETADA = 'COMPLETADA';
    const ESTADO_ANULADA = 'ANULADA';

    // Prioridades
    const PRIORIDAD_BAJA = 'BAJA';
    const PRIORIDAD_NORMAL = 'NORMAL';
    const PRIORIDAD_ALTA = 'ALTA';
    const PRIORIDAD_URGENTE = 'URGENTE';

    protected $fillable = [
        'empresa_id',
        'numero',
        'solicitante_id',
        'centro_costo_id',
        'almacen_id',
        'fecha_solicitud',
        'fecha_requerida',
        'prioridad',
        'estado',
        'motivo',
        'observaciones',
        'aprobado_por',
        'fecha_aprobacion',
        'comentario_aprobacion',
        'anulado_por',
        'fecha_anulacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_solicitud' => 'date',
            'fecha_requerida' => 'date',
            'fecha_aprobacion' => 'datetime',
            'fecha_anulacion' => 'datetime',
        ];
    }

    // ==================== RELACIONES ====================

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'solicitante_id');
    }

    public function centroCosto(): BelongsTo
    {
        return $this->belongsTo(CentroCosto::class, 'centro_costo_id');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'aprobado_por');
    }

    public function anulador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'anulado_por');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(RequisicionDetalle::class, 'requisicion_id');
    }

    // ==================== SCOPES ====================

    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopeAprobadas($query)
    {
        return $query->where('estado', self::ESTADO_APROBADA);
    }

    public function scopeDelPeriodo($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha_solicitud', [$fechaInicio, $fechaFin]);
    }

    public function scopeDelSolicitante($query, $usuarioId)
    {
        return $query->where('solicitante_id', $usuarioId);
    }

    public function scopeDelCentroCosto($query, $centroCostoId)
    {
        return $query->where('centro_costo_id', $centroCostoId);
    }

    // ==================== METODOS ====================

    /**
     * Verificar si puede ser editada.
     */
    public function puedeEditarse(): bool
    {
        return in_array($this->estado, [self::ESTADO_BORRADOR, self::ESTADO_RECHAZADA]);
    }

    /**
     * Verificar si puede ser aprobada.
     */
    public function puedeAprobarse(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    /**
     * Verificar si puede ser anulada.
     */
    public function puedeAnularse(): bool
    {
        return !in_array($this->estado, [self::ESTADO_ANULADA, self::ESTADO_COMPLETADA]);
    }

    /**
     * Enviar a aprobacion.
     */
    public function enviarAprobacion(): void
    {
        $this->update(['estado' => self::ESTADO_PENDIENTE]);
    }

    /**
     * Aprobar requisicion.
     */
    public function aprobar(int $usuarioId, ?string $comentario = null): void
    {
        $this->update([
            'estado' => self::ESTADO_APROBADA,
            'aprobado_por' => $usuarioId,
            'fecha_aprobacion' => now(),
            'comentario_aprobacion' => $comentario,
        ]);
    }

    /**
     * Rechazar requisicion.
     */
    public function rechazar(int $usuarioId, string $comentario): void
    {
        $this->update([
            'estado' => self::ESTADO_RECHAZADA,
            'aprobado_por' => $usuarioId,
            'fecha_aprobacion' => now(),
            'comentario_aprobacion' => $comentario,
        ]);
    }

    /**
     * Anular requisicion.
     */
    public function anular(int $usuarioId): void
    {
        $this->update([
            'estado' => self::ESTADO_ANULADA,
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
     * Obtener porcentaje de entrega.
     */
    public function getPorcentajeEntregaAttribute(): float
    {
        $totalSolicitado = $this->detalles()->sum('cantidad_aprobada') ?: $this->detalles()->sum('cantidad_solicitada');
        $totalEntregado = $this->detalles()->sum('cantidad_entregada');

        if ($totalSolicitado == 0) return 0;

        return round(($totalEntregado / $totalSolicitado) * 100, 2);
    }
}
