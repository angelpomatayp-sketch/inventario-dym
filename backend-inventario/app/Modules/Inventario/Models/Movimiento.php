<?php

namespace App\Modules\Inventario\Models;

use App\Core\Tenancy\Traits\PerteneceAEmpresa;
use App\Modules\Administracion\Models\Almacen;
use App\Modules\Administracion\Models\CentroCosto;
use App\Modules\Administracion\Models\Empresa;
use App\Modules\Administracion\Models\Usuario;
use App\Modules\Proveedores\Models\Proveedor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Movimiento extends Model implements Auditable
{
    use HasFactory, SoftDeletes, PerteneceAEmpresa, AuditableTrait;

    protected $table = 'movimientos';

    const TIPO_ENTRADA = 'ENTRADA';
    const TIPO_SALIDA = 'SALIDA';
    const TIPO_TRANSFERENCIA = 'TRANSFERENCIA';
    const TIPO_AJUSTE = 'AJUSTE';

    const ESTADO_PENDIENTE = 'PENDIENTE';
    const ESTADO_COMPLETADO = 'COMPLETADO';
    const ESTADO_ANULADO = 'ANULADO';

    protected $fillable = [
        'empresa_id',
        'numero',
        'tipo',
        'subtipo',
        'almacen_origen_id',
        'almacen_destino_id',
        'proveedor_id',
        'centro_costo_id',
        'usuario_id',
        'referencia_tipo',
        'referencia_id',
        'fecha',
        'documento_referencia',
        'observaciones',
        'estado',
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

    public function almacenOrigen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_origen_id');
    }

    public function almacenDestino(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_destino_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function centroCosto(): BelongsTo
    {
        return $this->belongsTo(CentroCosto::class, 'centro_costo_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function anuladoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'anulado_por');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(MovimientoDetalle::class, 'movimiento_id');
    }

    public function referencia(): MorphTo
    {
        return $this->morphTo('referencia', 'referencia_tipo', 'referencia_id');
    }

    // ==================== SCOPES ====================

    public function scopeCompletados($query)
    {
        return $query->where('estado', self::ESTADO_COMPLETADO);
    }

    public function scopeEntradas($query)
    {
        return $query->where('tipo', self::TIPO_ENTRADA);
    }

    public function scopeSalidas($query)
    {
        return $query->where('tipo', self::TIPO_SALIDA);
    }

    public function scopeDelPeriodo($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    }

    // ==================== MÉTODOS ====================

    /**
     * Obtener total del movimiento.
     */
    public function getTotalAttribute(): float
    {
        return $this->detalles()->sum('costo_total');
    }

    /**
     * Verificar si está anulado.
     */
    public function estaAnulado(): bool
    {
        return $this->estado === self::ESTADO_ANULADO;
    }

    /**
     * Anular movimiento.
     */
    public function anular(int $usuarioId): void
    {
        $this->update([
            'estado' => self::ESTADO_ANULADO,
            'anulado_por' => $usuarioId,
            'fecha_anulacion' => now(),
        ]);
    }
}
