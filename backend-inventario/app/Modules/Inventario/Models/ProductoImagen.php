<?php

namespace App\Modules\Inventario\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductoImagen extends Model
{
    protected $table = 'producto_imagenes';

    protected $fillable = [
        'producto_id',
        'nombre_original',
        'nombre_archivo',
        'ruta',
        'mime_type',
        'tamano',
        'orden',
        'principal',
    ];

    protected function casts(): array
    {
        return [
            'tamano' => 'integer',
            'orden' => 'integer',
            'principal' => 'boolean',
        ];
    }

    protected $appends = ['url'];

    protected static function booted(): void
    {
        // Si se cambia la ruta del archivo (reemplazo), eliminar el archivo anterior.
        static::updating(function (self $imagen) {
            if ($imagen->isDirty('ruta')) {
                $rutaOriginal = $imagen->getOriginal('ruta');
                if ($rutaOriginal && Storage::disk('public')->exists($rutaOriginal)) {
                    Storage::disk('public')->delete($rutaOriginal);
                }
            }
        });

        // Al eliminar el registro, eliminar también el archivo físico
        // y limpiar carpeta del producto si queda vacía.
        static::deleted(function (self $imagen) {
            $imagen->eliminarArchivo();
            $imagen->limpiarCarpetaSiVacia();
        });
    }

    // ==================== RELACIONES ====================

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    // ==================== ACCESSORS ====================

    /**
     * Obtener URL pública de la imagen a través del API.
     */
    public function getUrlAttribute(): string
    {
        // Generar URL a través del endpoint API para evitar problemas de CORS/acceso
        return url("/api/storage/productos/{$this->producto_id}/{$this->nombre_archivo}");
    }

    /**
     * Obtener tamaño formateado.
     */
    public function getTamanoFormateadoAttribute(): string
    {
        $bytes = $this->tamano;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }

    // ==================== MÉTODOS ====================

    /**
     * Eliminar archivo físico.
     */
    public function eliminarArchivo(): bool
    {
        if (Storage::disk('public')->exists($this->ruta)) {
            return Storage::disk('public')->delete($this->ruta);
        }
        return true;
    }

    /**
     * Elimina la carpeta del producto si ya no tiene archivos.
     */
    public function limpiarCarpetaSiVacia(): void
    {
        if (!$this->ruta) {
            return;
        }

        $directorio = Str::beforeLast($this->ruta, '/');
        if (!$directorio) {
            return;
        }

        $files = Storage::disk('public')->files($directorio);
        if (empty($files)) {
            Storage::disk('public')->deleteDirectory($directorio);
        }
    }
}
