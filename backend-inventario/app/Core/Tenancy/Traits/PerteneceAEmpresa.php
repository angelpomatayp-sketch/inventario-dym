<?php

namespace App\Core\Tenancy\Traits;

use App\Core\Tenancy\Scopes\EmpresaScope;
use App\Modules\Administracion\Models\Empresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait para implementar multi-tenancy por columna empresa_id.
 *
 * Este trait debe ser usado en todos los modelos que pertenecen a una empresa.
 * Automáticamente:
 * - Filtra las consultas por empresa_id del usuario autenticado
 * - Asigna empresa_id al crear nuevos registros
 *
 * @property int $empresa_id
 */
trait PerteneceAEmpresa
{
    /**
     * Boot del trait - registra el scope global y el evento creating.
     */
    protected static function bootPerteneceAEmpresa(): void
    {
        // Agregar scope global para filtrar por empresa
        static::addGlobalScope(new EmpresaScope);

        // Auto-asignar empresa_id al crear
        static::creating(function (Model $modelo) {
            if (auth()->check() && empty($modelo->empresa_id)) {
                $modelo->empresa_id = auth()->user()->empresa_id;
            }
        });
    }

    /**
     * Relación con la empresa.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Scope para consultar sin filtro de empresa (usar con precaución).
     */
    public function scopeSinTenancy($query)
    {
        return $query->withoutGlobalScope(EmpresaScope::class);
    }

    /**
     * Scope para filtrar por una empresa específica.
     */
    public function scopeDeEmpresa($query, int $empresaId)
    {
        return $query->withoutGlobalScope(EmpresaScope::class)
                     ->where('empresa_id', $empresaId);
    }
}
