<?php

namespace App\Modules\Inventario\Models;

use App\Core\Tenancy\Traits\PerteneceAEmpresa;
use App\Modules\Administracion\Models\Almacen;
use App\Modules\Administracion\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Producto extends Model implements Auditable
{
    use HasFactory, SoftDeletes, PerteneceAEmpresa, AuditableTrait;

    protected $table = 'productos';

    protected $fillable = [
        'empresa_id',
        'familia_id',
        'codigo',
        'nombre',
        'descripcion',
        'unidad_medida',
        'marca',
        'modelo',
        'stock_minimo',
        'stock_maximo',
        'ubicacion_fisica',
        'requiere_lote',
        'activo',
        // Campos EPP (solo aplican si el producto está en una familia EPP)
        'vida_util_dias',
        'dias_alerta_vencimiento',
        'requiere_talla',
        'tallas_disponibles',
    ];

    protected function casts(): array
    {
        return [
            'stock_minimo' => 'integer',
            'stock_maximo' => 'integer',
            'requiere_lote' => 'boolean',
            'activo' => 'boolean',
            'vida_util_dias' => 'integer',
            'dias_alerta_vencimiento' => 'integer',
            'requiere_talla' => 'boolean',
        ];
    }

    // ==================== EVENTS ====================

    protected static function booted(): void
    {
        // Al eliminar el producto (soft o force), borrar todas sus imágenes y archivos físicos
        static::deleting(function (Producto $producto) {
            $producto->imagenes()->each(fn($imagen) => $imagen->delete());
        });
    }

    // ==================== RELACIONES ====================

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function familia(): BelongsTo
    {
        return $this->belongsTo(Familia::class, 'familia_id');
    }

    public function stockAlmacenes(): HasMany
    {
        return $this->hasMany(StockAlmacen::class, 'producto_id');
    }

    public function kardex(): HasMany
    {
        return $this->hasMany(Kardex::class, 'producto_id');
    }

    public function movimientosDetalle(): HasMany
    {
        return $this->hasMany(MovimientoDetalle::class, 'producto_id');
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(ProductoImagen::class, 'producto_id')->orderBy('orden');
    }

    public function imagenPrincipal(): HasMany
    {
        return $this->hasMany(ProductoImagen::class, 'producto_id')->where('principal', true);
    }

    // ==================== SCOPES ====================

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeStockBajo($query)
    {
        return $query->whereHas('stockAlmacenes', function ($q) {
            $q->whereColumn('stock_actual', '<=', 'stock_minimo');
        });
    }

    /**
     * Scope para productos EPP (de familias marcadas como EPP).
     */
    public function scopeEpp($query)
    {
        return $query->whereHas('familia', function ($q) {
            $q->where('es_epp', true);
        });
    }

    /**
     * Scope para productos EPP por categoría.
     */
    public function scopeEppCategoria($query, string $categoria)
    {
        return $query->whereHas('familia', function ($q) use ($categoria) {
            $q->where('es_epp', true)->where('categoria_epp', $categoria);
        });
    }

    // ==================== MÉTODOS ====================

    /**
     * Obtener stock total en todos los almacenes.
     */
    public function getStockTotalAttribute(): float
    {
        return $this->stockAlmacenes()->sum('stock_actual');
    }

    /**
     * Obtener costo promedio ponderado.
     */
    public function getCostoPromedioAttribute(): float
    {
        $stock = $this->stockAlmacenes()
            ->selectRaw('SUM(stock_actual * costo_promedio) as total_valor, SUM(stock_actual) as total_cantidad')
            ->first();

        if ($stock && $stock->total_cantidad > 0) {
            return $stock->total_valor / $stock->total_cantidad;
        }

        return 0;
    }

    /**
     * Verificar si tiene stock bajo.
     */
    public function tieneStockBajo(): bool
    {
        return $this->stockAlmacenes()
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->exists();
    }

    /**
     * Obtener stock en un almacén específico.
     */
    public function getStockEnAlmacen(int $almacenId): float
    {
        $stockAlmacen = $this->stockAlmacenes()
            ->where('almacen_id', $almacenId)
            ->first();

        return $stockAlmacen ? $stockAlmacen->stock_actual : 0;
    }

    // ==================== MÉTODOS EPP ====================

    /**
     * Verificar si el producto es un EPP (su familia está marcada como EPP).
     */
    public function getEsEppAttribute(): bool
    {
        return $this->familia && $this->familia->es_epp;
    }

    /**
     * Obtener categoría EPP del producto.
     */
    public function getCategoriaEppAttribute(): ?string
    {
        return $this->familia?->categoria_epp;
    }

    /**
     * Obtener array de tallas disponibles.
     */
    public function getTallasArrayAttribute(): array
    {
        if (!$this->tallas_disponibles) {
            return [];
        }
        return array_map('trim', explode(',', $this->tallas_disponibles));
    }

    /**
     * Calcular fecha de vencimiento desde una fecha de entrega.
     */
    public function calcularFechaVencimiento(\Carbon\Carbon $fechaEntrega): ?\Carbon\Carbon
    {
        if (!$this->vida_util_dias) {
            return null;
        }
        return $fechaEntrega->copy()->addDays($this->vida_util_dias);
    }
}
