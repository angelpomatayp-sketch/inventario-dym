<?php

namespace App\Modules\EPPs\Models;

use App\Core\Tenancy\Traits\PerteneceAEmpresa;
use App\Modules\Administracion\Models\Almacen;
use App\Modules\Administracion\Models\Empresa;
use App\Modules\Administracion\Models\Usuario;
use App\Modules\Administracion\Models\Trabajador;
use App\Modules\Inventario\Models\Movimiento;
use App\Modules\Inventario\Models\Producto;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class AsignacionEpp extends Model implements Auditable
{
    use HasFactory, SoftDeletes, PerteneceAEmpresa, AuditableTrait;

    protected $table = 'asignaciones_epp';

    const ESTADO_VIGENTE = 'VIGENTE';
    const ESTADO_POR_VENCER = 'POR_VENCER';
    const ESTADO_VENCIDO = 'VENCIDO';
    const ESTADO_DEVUELTO = 'DEVUELTO';
    const ESTADO_EXTRAVIADO = 'EXTRAVIADO';
    const ESTADO_DAÑADO = 'DAÑADO';

    const TIPO_TRABAJADOR = 'trabajador';
    const TIPO_USUARIO = 'usuario';

    protected $fillable = [
        'empresa_id',
        'tipo_epp_id', // Deprecado - usar producto_id
        'producto_id',
        'trabajador_id',
        'tipo_receptor', // 'trabajador' o 'usuario'
        'entregado_por',
        'numero_serie',
        'talla',
        'cantidad',
        'fecha_entrega',
        'fecha_vencimiento',
        'fecha_devolucion',
        'estado',
        'observaciones',
        'firma_trabajador',
        'confirmado_trabajador',
        'fecha_confirmacion',
        'almacen_id',
        'movimiento_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_entrega' => 'date',
            'fecha_vencimiento' => 'date',
            'fecha_devolucion' => 'date',
            'fecha_confirmacion' => 'datetime',
            'confirmado_trabajador' => 'boolean',
            'cantidad' => 'integer',
        ];
    }

    // ==================== RELACIONES ====================

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function tipoEpp(): BelongsTo
    {
        return $this->belongsTo(TipoEpp::class, 'tipo_epp_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /**
     * Relación con usuario (cuando tipo_receptor = 'usuario').
     */
    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'trabajador_id');
    }

    /**
     * Relación con trabajador de la tabla trabajadores (cuando tipo_receptor = 'trabajador').
     */
    public function trabajadorPersona(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'trabajador_id');
    }

    /**
     * Obtiene el receptor correcto según tipo_receptor.
     */
    public function getReceptorAttribute()
    {
        if ($this->tipo_receptor === self::TIPO_TRABAJADOR) {
            return $this->trabajadorPersona;
        }
        return $this->trabajador;
    }

    /**
     * Obtiene el nombre del receptor.
     */
    public function getNombreReceptorAttribute(): ?string
    {
        $receptor = $this->receptor;
        return $receptor ? $receptor->nombre : null;
    }

    /**
     * Obtiene el DNI del receptor.
     */
    public function getDniReceptorAttribute(): ?string
    {
        $receptor = $this->receptor;
        return $receptor ? $receptor->dni : null;
    }

    public function entregadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'entregado_por');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class, 'movimiento_id');
    }

    // ==================== SCOPES ====================

    public function scopeVigentes($query)
    {
        return $query->where('estado', self::ESTADO_VIGENTE);
    }

    public function scopePorVencer($query)
    {
        return $query->where('estado', self::ESTADO_POR_VENCER);
    }

    public function scopeVencidos($query)
    {
        return $query->where('estado', self::ESTADO_VENCIDO);
    }

    public function scopeDelTrabajador($query, $trabajadorId)
    {
        return $query->where('trabajador_id', $trabajadorId);
    }

    public function scopeDelTipoEpp($query, $tipoEppId)
    {
        return $query->where('tipo_epp_id', $tipoEppId);
    }

    public function scopeDelProducto($query, $productoId)
    {
        return $query->where('producto_id', $productoId);
    }

    public function scopeProximosAVencer($query, $dias = 30)
    {
        return $query->where('estado', self::ESTADO_VIGENTE)
            ->where('fecha_vencimiento', '<=', now()->addDays($dias))
            ->where('fecha_vencimiento', '>', now());
    }

    // ==================== MÉTODOS ====================

    public function getDiasRestantesAttribute(): int
    {
        if (!$this->fecha_vencimiento) return 0;
        return max(0, now()->diffInDays($this->fecha_vencimiento, false));
    }

    public function getEstaVencidoAttribute(): bool
    {
        return $this->fecha_vencimiento && $this->fecha_vencimiento->isPast();
    }

    /**
     * Obtiene el nombre del EPP (producto o tipoEpp para retrocompatibilidad)
     */
    public function getNombreEppAttribute(): ?string
    {
        if ($this->producto) {
            return $this->producto->nombre;
        }
        if ($this->tipoEpp) {
            return $this->tipoEpp->nombre;
        }
        return null;
    }

    /**
     * Obtiene la categoría del EPP
     */
    public function getCategoriaEppAttribute(): ?string
    {
        if ($this->producto && $this->producto->familia) {
            return $this->producto->familia->categoria_epp;
        }
        if ($this->tipoEpp) {
            return $this->tipoEpp->categoria;
        }
        return null;
    }

    public function getEstaPorVencerAttribute(): bool
    {
        if (!$this->fecha_vencimiento) return false;

        // Primero intentar con producto, luego con tipoEpp (retrocompatibilidad)
        $diasAlerta = 15; // Valor por defecto
        if ($this->producto && $this->producto->dias_alerta_vencimiento) {
            $diasAlerta = $this->producto->dias_alerta_vencimiento;
        } elseif ($this->tipoEpp && $this->tipoEpp->dias_alerta_vencimiento) {
            $diasAlerta = $this->tipoEpp->dias_alerta_vencimiento;
        }

        return $this->fecha_vencimiento->diffInDays(now()) <= $diasAlerta && !$this->esta_vencido;
    }

    public function actualizarEstado(): void
    {
        if ($this->estado === self::ESTADO_DEVUELTO) return;

        if ($this->esta_vencido) {
            $this->update(['estado' => self::ESTADO_VENCIDO]);
        } elseif ($this->esta_por_vencer) {
            $this->update(['estado' => self::ESTADO_POR_VENCER]);
        } else {
            $this->update(['estado' => self::ESTADO_VIGENTE]);
        }
    }

    public function confirmarRecepcion(): void
    {
        $this->update([
            'confirmado_trabajador' => true,
            'fecha_confirmacion' => now(),
        ]);
    }

    public function registrarDevolucion(?string $observaciones = null): void
    {
        $this->update([
            'estado' => self::ESTADO_DEVUELTO,
            'fecha_devolucion' => now(),
            'observaciones' => $observaciones ?? $this->observaciones,
        ]);
    }

    public function marcarExtraviado(?string $observaciones = null): void
    {
        $this->update([
            'estado' => self::ESTADO_EXTRAVIADO,
            'observaciones' => $observaciones ?? $this->observaciones,
        ]);
    }

    public function marcarDañado(?string $observaciones = null): void
    {
        $this->update([
            'estado' => self::ESTADO_DAÑADO,
            'observaciones' => $observaciones ?? $this->observaciones,
        ]);
    }

    public static function getEstados(): array
    {
        return [
            self::ESTADO_VIGENTE => 'Vigente',
            self::ESTADO_POR_VENCER => 'Por Vencer',
            self::ESTADO_VENCIDO => 'Vencido',
            self::ESTADO_DEVUELTO => 'Devuelto',
            self::ESTADO_EXTRAVIADO => 'Extraviado',
            self::ESTADO_DAÑADO => 'Dañado',
        ];
    }

    // Boot para calcular fecha_vencimiento automáticamente
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($asignacion) {
            if (!$asignacion->fecha_vencimiento) {
                $vidaUtilDias = null;

                // Primero intentar con producto (nueva forma)
                if ($asignacion->producto_id) {
                    $producto = Producto::find($asignacion->producto_id);
                    if ($producto && $producto->vida_util_dias) {
                        $vidaUtilDias = $producto->vida_util_dias;
                    }
                }

                // Fallback a tipoEpp (retrocompatibilidad)
                if (!$vidaUtilDias && $asignacion->tipo_epp_id) {
                    $tipoEpp = TipoEpp::find($asignacion->tipo_epp_id);
                    if ($tipoEpp && $tipoEpp->vida_util_dias) {
                        $vidaUtilDias = $tipoEpp->vida_util_dias;
                    }
                }

                if ($vidaUtilDias && $asignacion->fecha_entrega) {
                    $asignacion->fecha_vencimiento = $asignacion->fecha_entrega->addDays($vidaUtilDias);
                }
            }
        });
    }
}
