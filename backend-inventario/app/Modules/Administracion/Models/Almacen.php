<?php

namespace App\Modules\Administracion\Models;

use App\Core\Tenancy\Traits\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Almacen extends Model implements Auditable
{
    use HasFactory, SoftDeletes, PerteneceAEmpresa, AuditableTrait;

    protected $table = 'almacenes';

    protected $fillable = [
        'empresa_id',
        'centro_costo_id',
        'codigo',
        'nombre',
        'ubicacion',
        'tipo',
        'responsable_id',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    // ==================== CONSTANTES ====================

    public const TIPO_PRINCIPAL = 'PRINCIPAL';
    public const TIPO_CAMPAMENTO = 'CAMPAMENTO';
    public const TIPO_SATELITE = 'SATELITE';

    public const TIPOS = [
        self::TIPO_PRINCIPAL => 'Almacén Principal',
        self::TIPO_CAMPAMENTO => 'Campamento Mina',
        self::TIPO_SATELITE => 'Almacén Satélite',
    ];

    // ==================== RELACIONES ====================

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'responsable_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function centroCosto(): BelongsTo
    {
        return $this->belongsTo(CentroCosto::class, 'centro_costo_id');
    }

    public function stockAlmacen(): HasMany
    {
        return $this->hasMany(\App\Modules\Inventario\Models\StockAlmacen::class, 'almacen_id');
    }

    // ==================== SCOPES ====================

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorCodigo($query, string $codigo)
    {
        return $query->where('codigo', $codigo);
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopePrincipales($query)
    {
        return $query->where('tipo', self::TIPO_PRINCIPAL);
    }

    // ==================== MÉTODOS ====================

    /**
     * Obtener el nombre completo con código.
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->codigo} - {$this->nombre}";
    }

    /**
     * Obtener el nombre del tipo de almacén.
     */
    public function getTipoNombreAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    /**
     * Verificar si es almacén principal.
     */
    public function esPrincipal(): bool
    {
        return $this->tipo === self::TIPO_PRINCIPAL;
    }
}
