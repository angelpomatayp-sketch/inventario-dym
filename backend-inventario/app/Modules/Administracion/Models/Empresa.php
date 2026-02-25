<?php

namespace App\Modules\Administracion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Empresa extends Model implements Auditable
{
    use HasFactory, SoftDeletes, AuditableTrait;

    protected $table = 'empresas';

    protected $fillable = [
        'ruc',
        'razon_social',
        'nombre_comercial',
        'direccion',
        'ubigeo',
        'departamento',
        'provincia',
        'distrito',
        'telefono',
        'email',
        'logo',
        'metodo_valorizacion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    // ==================== RELACIONES ====================

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'empresa_id');
    }

    public function centrosCostos(): HasMany
    {
        return $this->hasMany(CentroCosto::class, 'empresa_id');
    }

    public function almacenes(): HasMany
    {
        return $this->hasMany(Almacen::class, 'empresa_id');
    }

    // ==================== SCOPES ====================

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorRuc($query, string $ruc)
    {
        return $query->where('ruc', $ruc);
    }

    // ==================== MÉTODOS ====================

    /**
     * Obtener el método de valorización de inventario.
     */
    public function usaPEPS(): bool
    {
        return $this->metodo_valorizacion === 'PEPS';
    }

    public function usaPromedio(): bool
    {
        return $this->metodo_valorizacion === 'PROMEDIO';
    }
}
