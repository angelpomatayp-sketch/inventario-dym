<?php

namespace App\Modules\Prestamos\Models;

use App\Core\Tenancy\Traits\PerteneceAEmpresa;
use App\Modules\Administracion\Models\Almacen;
use App\Modules\Inventario\Models\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipoPrestable extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'equipos_prestables';

    protected $fillable = [
        'empresa_id',
        'producto_id',
        'codigo',
        'nombre',
        'descripcion',
        'numero_serie',
        'marca',
        'modelo',
        'tipo_control',
        'cantidad_total',
        'cantidad_disponible',
        'estado',
        'almacen_id',
        'ubicacion_fisica',
        'valor_referencial',
        'fecha_adquisicion',
        'notas',
        'imagen',
        'activo',
    ];

    protected $casts = [
        'fecha_adquisicion' => 'date',
        'valor_referencial' => 'decimal:2',
        'activo' => 'boolean',
    ];

    // Tipos de control
    const TIPO_INDIVIDUAL = 'INDIVIDUAL';
    const TIPO_CANTIDAD = 'CANTIDAD';

    // Estados
    const ESTADO_DISPONIBLE = 'DISPONIBLE';
    const ESTADO_PRESTADO = 'PRESTADO';
    const ESTADO_MANTENIMIENTO = 'EN_MANTENIMIENTO';
    const ESTADO_BAJA = 'DADO_DE_BAJA';

    /**
     * Producto vinculado (si aplica)
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /**
     * Almacén donde se ubica
     */
    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    /**
     * Préstamos de este equipo
     */
    public function prestamos(): HasMany
    {
        return $this->hasMany(PrestamoEquipo::class, 'equipo_id');
    }

    /**
     * Préstamos activos
     */
    public function prestamosActivos(): HasMany
    {
        return $this->hasMany(PrestamoEquipo::class, 'equipo_id')
            ->whereIn('estado', [PrestamoEquipo::ESTADO_ACTIVO, PrestamoEquipo::ESTADO_VENCIDO]);
    }

    /**
     * Scope para equipos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para equipos disponibles
     */
    public function scopeDisponibles($query)
    {
        return $query->where('estado', self::ESTADO_DISPONIBLE)
            ->where('activo', true);
    }

    /**
     * Verificar si está disponible para préstamo
     */
    public function estaDisponible(int $cantidad = 1): bool
    {
        if (!$this->activo || $this->estado === self::ESTADO_BAJA || $this->estado === self::ESTADO_MANTENIMIENTO) {
            return false;
        }

        if ($this->tipo_control === self::TIPO_INDIVIDUAL) {
            return $this->estado === self::ESTADO_DISPONIBLE;
        }

        return $this->cantidad_disponible >= $cantidad;
    }

    /**
     * Reducir cantidad disponible (para préstamo)
     */
    public function reducirDisponibilidad(int $cantidad = 1): void
    {
        if ($this->tipo_control === self::TIPO_INDIVIDUAL) {
            $this->estado = self::ESTADO_PRESTADO;
        } else {
            $cantidadDisponibleActual = (int) ($this->cantidad_disponible ?? 0);
            $this->cantidad_disponible = max(0, $cantidadDisponibleActual - $cantidad);
            if ($this->cantidad_disponible === 0) {
                $this->estado = self::ESTADO_PRESTADO;
            }
        }
        $this->save();
    }

    /**
     * Aumentar cantidad disponible (para devolución)
     */
    public function aumentarDisponibilidad(int $cantidad = 1): void
    {
        if ($this->tipo_control === self::TIPO_INDIVIDUAL) {
            $this->estado = self::ESTADO_DISPONIBLE;
        } else {
            $cantidadTotal = (int) ($this->cantidad_total ?? 0);
            $cantidadDisponibleActual = (int) ($this->cantidad_disponible ?? 0);

            // Evitar nulls y mantener coherencia en registros legacy.
            if ($cantidadTotal <= 0) {
                $cantidadTotal = max(1, $cantidadDisponibleActual + $cantidad);
                $this->cantidad_total = $cantidadTotal;
            }

            $this->cantidad_disponible = min($cantidadTotal, $cantidadDisponibleActual + $cantidad);
            if ($this->cantidad_disponible > 0) {
                $this->estado = self::ESTADO_DISPONIBLE;
            }
        }
        $this->save();
    }

    /**
     * Obtener cantidad en préstamo
     */
    public function getCantidadPrestadaAttribute(): int
    {
        if ($this->tipo_control === self::TIPO_INDIVIDUAL) {
            return $this->estado === self::ESTADO_PRESTADO ? 1 : 0;
        }
        return $this->cantidad_total - $this->cantidad_disponible;
    }
}
