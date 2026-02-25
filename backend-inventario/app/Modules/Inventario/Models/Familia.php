<?php

namespace App\Modules\Inventario\Models;

use App\Core\Tenancy\Traits\PerteneceAEmpresa;
use App\Modules\Administracion\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Familia extends Model implements Auditable
{
    use HasFactory, SoftDeletes, PerteneceAEmpresa, AuditableTrait;

    protected $table = 'familias';

    // Categorías de EPP válidas
    const CATEGORIA_CABEZA = 'CABEZA';
    const CATEGORIA_OJOS = 'OJOS';
    const CATEGORIA_OIDOS = 'OIDOS';
    const CATEGORIA_RESPIRATORIO = 'RESPIRATORIO';
    const CATEGORIA_MANOS = 'MANOS';
    const CATEGORIA_PIES = 'PIES';
    const CATEGORIA_CUERPO = 'CUERPO';
    const CATEGORIA_ALTURA = 'ALTURA';

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nombre',
        'descripcion',
        'es_epp',
        'categoria_epp',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'es_epp' => 'boolean',
        ];
    }

    // ==================== RELACIONES ====================

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'familia_id');
    }

    // ==================== SCOPES ====================

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopeEpp($query)
    {
        return $query->where('es_epp', true);
    }

    public function scopeNoEpp($query)
    {
        return $query->where('es_epp', false);
    }

    // ==================== MÉTODOS ESTÁTICOS ====================

    public static function getCategoriasEpp(): array
    {
        return [
            self::CATEGORIA_CABEZA => 'Protección de Cabeza',
            self::CATEGORIA_OJOS => 'Protección Ocular',
            self::CATEGORIA_OIDOS => 'Protección Auditiva',
            self::CATEGORIA_RESPIRATORIO => 'Protección Respiratoria',
            self::CATEGORIA_MANOS => 'Protección de Manos',
            self::CATEGORIA_PIES => 'Protección de Pies',
            self::CATEGORIA_CUERPO => 'Protección Corporal',
            self::CATEGORIA_ALTURA => 'Trabajo en Altura',
        ];
    }
}
