<?php

namespace App\Modules\Prestamos\Models;

use App\Core\Tenancy\Traits\PerteneceAEmpresa;
use App\Modules\Administracion\Models\CentroCosto;
use App\Modules\Administracion\Models\Trabajador;
use App\Modules\Administracion\Models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrestamoEquipo extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'prestamos_equipos';

    // Tipos de receptor
    const TIPO_TRABAJADOR = 'trabajador';
    const TIPO_USUARIO = 'usuario';

    protected $fillable = [
        'empresa_id',
        'numero',
        'equipo_id',
        'cantidad',
        'trabajador_id',
        'tipo_receptor', // 'trabajador' o 'usuario'
        'centro_costo_id',
        'area_destino',
        'fecha_prestamo',
        'fecha_devolucion_esperada',
        'fecha_devolucion_real',
        'estado',
        'condicion_devolucion',
        'entregado_por',
        'recibido_por',
        'motivo_prestamo',
        'observaciones_entrega',
        'observaciones_devolucion',
        'numero_renovaciones',
        'fecha_devolucion_original',
    ];

    protected $casts = [
        'fecha_prestamo' => 'date',
        'fecha_devolucion_esperada' => 'date',
        'fecha_devolucion_real' => 'date',
        'fecha_devolucion_original' => 'date',
    ];

    // Estados del préstamo
    const ESTADO_ACTIVO = 'ACTIVO';
    const ESTADO_DEVUELTO = 'DEVUELTO';
    const ESTADO_VENCIDO = 'VENCIDO';
    const ESTADO_RENOVADO = 'RENOVADO';
    const ESTADO_PERDIDO = 'PERDIDO';
    const ESTADO_DANADO = 'DANADO';

    // Condiciones de devolución
    const CONDICION_BUENO = 'BUENO';
    const CONDICION_REGULAR = 'REGULAR';
    const CONDICION_MALO = 'MALO';
    const CONDICION_PERDIDO = 'PERDIDO';

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($prestamo) {
            if (empty($prestamo->numero)) {
                $prestamo->numero = self::generarNumero($prestamo->empresa_id);
            }
        });
    }

    /**
     * Generar número de préstamo
     */
    public static function generarNumero(int $empresaId): string
    {
        $year = date('Y');
        $base = "PRE-{$year}-";
        $ultimoNumero = self::where('empresa_id', $empresaId)
            ->where('numero', 'like', $base . '%')
            ->orderBy('numero', 'desc')
            ->lockForUpdate()
            ->value('numero');

        $secuencia = 1;
        if ($ultimoNumero) {
            $partes = explode('-', $ultimoNumero);
            $secuencia = (int) end($partes) + 1;
        }

        $numero = $base . str_pad($secuencia, 4, '0', STR_PAD_LEFT);
        while (self::where('empresa_id', $empresaId)->where('numero', $numero)->exists()) {
            $secuencia++;
            $numero = $base . str_pad($secuencia, 4, '0', STR_PAD_LEFT);
        }

        return $numero;
    }

    /**
     * Equipo prestado
     */
    public function equipo(): BelongsTo
    {
        return $this->belongsTo(EquipoPrestable::class, 'equipo_id');
    }

    /**
     * Trabajador que tiene el equipo (cuando tipo_receptor = 'trabajador')
     */
    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'trabajador_id');
    }

    /**
     * Usuario que tiene el equipo (cuando tipo_receptor = 'usuario')
     */
    public function trabajadorUsuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'trabajador_id');
    }

    /**
     * Obtiene el receptor correcto según tipo_receptor.
     */
    public function getReceptorAttribute()
    {
        if ($this->tipo_receptor === self::TIPO_USUARIO) {
            return $this->trabajadorUsuario;
        }
        return $this->trabajador;
    }

    /**
     * Obtiene el nombre del receptor.
     */
    public function getNombreReceptorAttribute(): ?string
    {
        $receptor = $this->receptor;
        return $receptor ? $receptor->nombre : null;
    }

    /**
     * Centro de costo destino
     */
    public function centroCosto(): BelongsTo
    {
        return $this->belongsTo(CentroCosto::class, 'centro_costo_id');
    }

    /**
     * Usuario que entregó
     */
    public function usuarioEntrega(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'entregado_por');
    }

    /**
     * Usuario que recibió devolución
     */
    public function usuarioRecepcion(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'recibido_por');
    }

    /**
     * Scope para préstamos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVO);
    }

    /**
     * Scope para préstamos vencidos
     */
    public function scopeVencidos($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVO)
            ->where('fecha_devolucion_esperada', '<', now());
    }

    /**
     * Scope para préstamos por vencer (próximos 3 días)
     */
    public function scopePorVencer($query, int $dias = 3)
    {
        return $query->where('estado', self::ESTADO_ACTIVO)
            ->whereBetween('fecha_devolucion_esperada', [now(), now()->addDays($dias)]);
    }

    /**
     * Verificar si está vencido
     */
    public function estaVencido(): bool
    {
        return $this->estado === self::ESTADO_ACTIVO
            && $this->fecha_devolucion_esperada < now();
    }

    /**
     * Días de atraso
     */
    public function getDiasAtrasoAttribute(): int
    {
        if (!$this->estaVencido()) {
            return 0;
        }
        return now()->diffInDays($this->fecha_devolucion_esperada);
    }

    /**
     * Días restantes para devolución
     */
    public function getDiasRestantesAttribute(): int
    {
        if ($this->estado !== self::ESTADO_ACTIVO) {
            return 0;
        }
        return max(0, now()->diffInDays($this->fecha_devolucion_esperada, false));
    }
}
