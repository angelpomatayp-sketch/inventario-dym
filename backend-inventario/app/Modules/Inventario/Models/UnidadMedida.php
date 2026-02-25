<?php

namespace App\Modules\Inventario\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnidadMedida extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'unidades_medida';

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nombre',
        'abreviatura',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // ==================== SCOPES ====================

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopeDeEmpresa($query, int $empresaId)
    {
        return $query->where(function ($q) use ($empresaId) {
            $q->where('empresa_id', $empresaId)
              ->orWhereNull('empresa_id'); // Unidades globales
        });
    }

    public function scopeBuscar($query, string $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('codigo', 'like', "%{$termino}%")
              ->orWhere('nombre', 'like', "%{$termino}%")
              ->orWhere('abreviatura', 'like', "%{$termino}%");
        });
    }
}
