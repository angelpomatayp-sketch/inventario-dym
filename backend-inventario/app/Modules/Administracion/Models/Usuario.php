<?php

namespace App\Modules\Administracion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Spatie\Permission\Traits\HasRoles;

class Usuario extends Authenticatable implements Auditable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles, AuditableTrait;

    protected $table = 'usuarios';

    /**
     * Guard para Spatie Permission.
     */
    protected string $guard_name = 'sanctum';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'email',
        'password',
        'dni',
        'telefono',
        'centro_costo_id',
        'almacen_id',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    // ==================== RELACIONES ====================

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function centroCosto(): BelongsTo
    {
        return $this->belongsTo(CentroCosto::class, 'centro_costo_id');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    // ==================== SCOPES ====================

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeDeEmpresa($query, int $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    // ==================== MÉTODOS ====================

    /**
     * Obtener el nombre completo del usuario.
     */
    public function getNombreCompletoAttribute(): string
    {
        return $this->nombre;
    }

    /**
     * Verificar si el usuario está activo.
     */
    public function estaActivo(): bool
    {
        return $this->activo && $this->empresa && $this->empresa->activo;
    }

    /**
     * Verificar contraseña para confirmación de acciones.
     */
    public function verificarPassword(string $password): bool
    {
        return \Hash::check($password, $this->password);
    }

    /**
     * Verificar si el usuario es almacenero (tiene almacén asignado).
     */
    public function esAlmacenero(): bool
    {
        return $this->hasRole('almacenero') && $this->almacen_id !== null;
    }

    /**
     * Verificar si el usuario tiene acceso a un almacén específico.
     */
    public function tieneAccesoAlmacen(int $almacenId): bool
    {
        // Super admin y jefe de logística tienen acceso a todos
        if ($this->hasAnyRole(['super_admin', 'jefe_logistica'])) {
            return true;
        }

        // Almacenero solo a su almacén asignado
        if ($this->esAlmacenero()) {
            return $this->almacen_id === $almacenId;
        }

        return true; // Otros roles no tienen restricción de almacén
    }

    /**
     * Obtener el ID del almacén asignado (para filtros).
     */
    public function getAlmacenAsignadoId(): ?int
    {
        if ($this->hasAnyRole(['super_admin', 'jefe_logistica'])) {
            return null; // Ve todos los almacenes
        }

        return $this->almacen_id;
    }
}
