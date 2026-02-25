<?php

namespace App\Modules\Administracion\Models;

use App\Core\Tenancy\Traits\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo Trabajador
 *
 * Representa a trabajadores que NO son usuarios del sistema.
 * Solo se usan para control documental: EPPs, Préstamos, Vales de Salida.
 * Ejemplos: Peones, Obreros, Operadores, Ayudantes.
 */
class Trabajador extends Model
{
    use HasFactory, SoftDeletes, PerteneceAEmpresa;

    protected $table = 'trabajadores';

    protected $fillable = [
        'empresa_id',
        'centro_costo_id',
        'nombre',
        'dni',
        'cargo',
        'telefono',
        'fecha_ingreso',
        'fecha_cese',
        'activo',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date',
            'fecha_cese' => 'date',
            'activo' => 'boolean',
        ];
    }

    // ==================== RELACIONES ====================

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function centroCosto(): BelongsTo
    {
        return $this->belongsTo(CentroCosto::class, 'centro_costo_id');
    }

    // ==================== SCOPES ====================

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeDelProyecto($query, int $centroCostoId)
    {
        return $query->where('centro_costo_id', $centroCostoId);
    }

    public function scopeBuscar($query, ?string $busqueda)
    {
        if (!$busqueda) {
            return $query;
        }

        return $query->where(function ($q) use ($busqueda) {
            $q->where('nombre', 'like', "%{$busqueda}%")
                ->orWhere('dni', 'like', "%{$busqueda}%")
                ->orWhere('cargo', 'like', "%{$busqueda}%");
        });
    }

    // ==================== MÉTODOS ====================

    /**
     * Obtener el nombre completo con cargo.
     */
    public function getNombreConCargoAttribute(): string
    {
        if ($this->cargo) {
            return "{$this->nombre} ({$this->cargo})";
        }
        return $this->nombre;
    }

    /**
     * Verificar si el trabajador está activo.
     */
    public function estaActivo(): bool
    {
        if (!$this->activo) {
            return false;
        }

        // Si tiene fecha de cese y ya pasó, está inactivo
        if ($this->fecha_cese && $this->fecha_cese->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Dar de baja al trabajador.
     */
    public function darDeBaja(?string $observacion = null): void
    {
        $this->update([
            'activo' => false,
            'fecha_cese' => now(),
            'observaciones' => $observacion
                ? ($this->observaciones ? $this->observaciones . "\n" . $observacion : $observacion)
                : $this->observaciones,
        ]);
    }
}
