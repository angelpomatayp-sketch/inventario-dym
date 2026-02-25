<?php

namespace App\Modules\EPPs\Models;

use App\Core\Tenancy\Traits\PerteneceAEmpresa;
use App\Modules\Administracion\Models\Empresa;
use App\Modules\Inventario\Models\Producto;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoEpp extends Model
{
    use HasFactory, SoftDeletes, PerteneceAEmpresa;

    protected $table = 'tipos_epp';

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
        'categoria',
        'vida_util_dias',
        'dias_alerta_vencimiento',
        'requiere_talla',
        'tallas_disponibles',
        'producto_id',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'requiere_talla' => 'boolean',
            'activo' => 'boolean',
            'vida_util_dias' => 'integer',
            'dias_alerta_vencimiento' => 'integer',
        ];
    }

    // ==================== RELACIONES ====================

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionEpp::class, 'tipo_epp_id');
    }

    // ==================== SCOPES ====================

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    // ==================== MÉTODOS ====================

    public function getTallasArrayAttribute(): array
    {
        if (!$this->tallas_disponibles) {
            return [];
        }
        return array_map('trim', explode(',', $this->tallas_disponibles));
    }

    public function getAsignacionesVigentesCountAttribute(): int
    {
        return $this->asignaciones()->where('estado', 'VIGENTE')->count();
    }

    public function getAsignacionesPorVencerCountAttribute(): int
    {
        return $this->asignaciones()->where('estado', 'POR_VENCER')->count();
    }

    public static function getCategorias(): array
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
