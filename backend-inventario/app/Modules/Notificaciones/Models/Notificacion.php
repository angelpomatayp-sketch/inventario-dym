<?php

namespace App\Modules\Notificaciones\Models;

use App\Core\Tenancy\Traits\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'notificaciones';

    protected $fillable = [
        'empresa_id',
        'usuario_id',
        'tipo',
        'titulo',
        'mensaje',
        'icono',
        'severidad',
        'entidad_tipo',
        'entidad_id',
        'url',
        'leida_en',
    ];

    protected $casts = [
        'leida_en' => 'datetime',
    ];

    // Tipos de notificación
    const TIPO_STOCK_BAJO = 'STOCK_BAJO';
    const TIPO_EPP_VENCIMIENTO = 'EPP_VENCIMIENTO';
    const TIPO_EPP_POR_VENCER = 'EPP_POR_VENCER';
    const TIPO_REQUISICION_PENDIENTE = 'REQUISICION_PENDIENTE';
    const TIPO_REQUISICION_APROBADA = 'REQUISICION_APROBADA';
    const TIPO_REQUISICION_RECHAZADA = 'REQUISICION_RECHAZADA';
    const TIPO_ORDEN_COMPRA = 'ORDEN_COMPRA';
    const TIPO_PRESTAMO_VENCIDO = 'PRESTAMO_VENCIDO';
    const TIPO_PRESTAMO_POR_VENCER = 'PRESTAMO_POR_VENCER';
    const TIPO_SISTEMA = 'SISTEMA';

    // Severidades
    const SEVERIDAD_INFO = 'info';
    const SEVERIDAD_SUCCESS = 'success';
    const SEVERIDAD_WARN = 'warn';
    const SEVERIDAD_DANGER = 'danger';

    /**
     * Usuario destino de la notificación
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Administracion\Models\Usuario::class, 'usuario_id');
    }

    /**
     * Empresa
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Administracion\Models\Empresa::class, 'empresa_id');
    }

    /**
     * Scope para notificaciones no leídas
     */
    public function scopeNoLeidas($query)
    {
        return $query->whereNull('leida_en');
    }

    /**
     * Scope para notificaciones leídas
     */
    public function scopeLeidas($query)
    {
        return $query->whereNotNull('leida_en');
    }

    /**
     * Scope para un usuario específico o globales
     */
    public function scopeParaUsuario($query, $usuarioId)
    {
        return $query->where(function ($q) use ($usuarioId) {
            $q->whereNull('usuario_id')
              ->orWhere('usuario_id', $usuarioId);
        });
    }

    /**
     * Marcar como leída
     */
    public function marcarLeida(): void
    {
        $this->update(['leida_en' => now()]);
    }

    /**
     * Verificar si está leída
     */
    public function estaLeida(): bool
    {
        return $this->leida_en !== null;
    }
}
