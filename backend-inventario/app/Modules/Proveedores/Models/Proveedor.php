<?php

namespace App\Modules\Proveedores\Models;

use App\Core\Tenancy\Traits\PerteneceAEmpresa;
use App\Modules\Administracion\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Proveedor extends Model implements Auditable
{
    use HasFactory, SoftDeletes, PerteneceAEmpresa, AuditableTrait;

    protected $table = 'proveedores';

    const TIPO_BIENES = 'BIENES';
    const TIPO_SERVICIOS = 'SERVICIOS';
    const TIPO_AMBOS = 'AMBOS';

    protected $fillable = [
        'empresa_id',
        'ruc',
        'razon_social',
        'nombre_comercial',
        'direccion',
        'telefono',
        'email',
        'contacto_nombre',
        'contacto_telefono',
        'contacto_email',
        'tipo',
        'calificacion',
        'total_calificaciones',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'calificacion' => 'decimal:2',
            'total_calificaciones' => 'integer',
            'activo' => 'boolean',
        ];
    }

    // ==================== RELACIONES ====================

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(\App\Modules\Inventario\Models\Movimiento::class, 'proveedor_id');
    }

    // ==================== SCOPES ====================

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    // ==================== MÉTODOS ====================

    /**
     * Agregar nueva calificación.
     */
    public function agregarCalificacion(float $calificacion): void
    {
        $totalActual = $this->calificacion * $this->total_calificaciones;
        $this->total_calificaciones++;
        $this->calificacion = ($totalActual + $calificacion) / $this->total_calificaciones;
        $this->save();
    }
}
