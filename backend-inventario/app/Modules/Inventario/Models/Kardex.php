<?php

namespace App\Modules\Inventario\Models;

use App\Core\Tenancy\Traits\PerteneceAEmpresa;
use App\Modules\Administracion\Models\Almacen;
use App\Modules\Administracion\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kardex extends Model
{
    use HasFactory, PerteneceAEmpresa;

    protected $table = 'kardex';

    const TIPO_ENTRADA = 'ENTRADA';
    const TIPO_SALIDA = 'SALIDA';
    const TIPO_AJUSTE_POSITIVO = 'AJUSTE_POSITIVO';
    const TIPO_AJUSTE_NEGATIVO = 'AJUSTE_NEGATIVO';
    const TIPO_SALDO_INICIAL = 'SALDO_INICIAL';

    protected $fillable = [
        'empresa_id',
        'producto_id',
        'almacen_id',
        'movimiento_id',
        'fecha',
        'tipo_operacion',
        'documento_referencia',
        'cantidad',
        'costo_unitario',
        'costo_total',
        'saldo_cantidad',
        'saldo_costo_unitario',
        'saldo_costo_total',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'cantidad' => 'decimal:2',
            'costo_unitario' => 'decimal:4',
            'costo_total' => 'decimal:4',
            'saldo_cantidad' => 'decimal:2',
            'saldo_costo_unitario' => 'decimal:4',
            'saldo_costo_total' => 'decimal:4',
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

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class, 'movimiento_id');
    }

    // ==================== SCOPES ====================

    public function scopeDelPeriodo($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    }

    public function scopeEntradas($query)
    {
        return $query->whereIn('tipo_operacion', [self::TIPO_ENTRADA, self::TIPO_AJUSTE_POSITIVO]);
    }

    public function scopeSalidas($query)
    {
        return $query->whereIn('tipo_operacion', [self::TIPO_SALIDA, self::TIPO_AJUSTE_NEGATIVO]);
    }
}
