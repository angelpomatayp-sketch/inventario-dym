<?php

namespace App\Modules\Administracion\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolController extends Controller
{
    use ApiResponse;

    /**
     * Listar roles.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Role::with('permissions');

        // Filtro de búsqueda
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        // Si se pide sin paginación (para selects)
        if ($request->boolean('all')) {
            $roles = $query->get(['id', 'name']);
            return $this->success($roles);
        }

        $roles = $query->get()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $this->getRoleDescription($role->name),
                'permissions' => $role->permissions->pluck('name'),
                'permissions_count' => $role->permissions->count(),
            ];
        });

        return $this->success($roles);
    }

    /**
     * Listar permisos.
     */
    public function permisos(Request $request): JsonResponse
    {
        $permisos = Permission::all()->groupBy(function ($permission) {
            // Agrupar por módulo (primera parte del nombre del permiso)
            $parts = explode('.', $permission->name);
            return $parts[0] ?? 'general';
        })->map(function ($group, $modulo) {
            return [
                'modulo' => $modulo,
                'modulo_label' => $this->getModuloLabel($modulo),
                'permisos' => $group->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'description' => $this->getPermissionDescription($permission->name),
                    ];
                })->values(),
            ];
        })->values();

        return $this->success($permisos);
    }

    /**
     * Obtener descripción legible del rol.
     */
    private function getRoleDescription(string $roleName): string
    {
        $descriptions = [
            'super_admin' => 'Acceso total al sistema',
            'administrador' => 'Administración general de la empresa',
            'jefe_almacen' => 'Gestión completa del almacén',
            'almacenero' => 'Operaciones de entrada y salida de almacén',
            'jefe_area' => 'Aprobación de requisiciones del área',
            'gerencia' => 'Aprobaciones de alto nivel y reportes',
            'compras' => 'Gestión de compras y proveedores',
            'auditor' => 'Acceso de solo lectura para auditorías',
        ];

        return $descriptions[$roleName] ?? 'Sin descripción';
    }

    /**
     * Obtener etiqueta legible del módulo.
     */
    private function getModuloLabel(string $modulo): string
    {
        $labels = [
            'empresas' => 'Empresas',
            'usuarios' => 'Usuarios',
            'roles' => 'Roles y Permisos',
            'almacenes' => 'Almacenes',
            'centros_costo' => 'Centros de Costo',
            'productos' => 'Productos',
            'familias' => 'Familias/Categorías',
            'movimientos' => 'Movimientos',
            'kardex' => 'Kardex',
            'proveedores' => 'Proveedores',
            'requisiciones' => 'Requisiciones',
            'vales_salida' => 'Vales de Salida',
            'cotizaciones' => 'Cotizaciones',
            'ordenes_compra' => 'Órdenes de Compra',
            'epps' => 'EPPs',
            'reportes' => 'Reportes',
            'general' => 'General',
        ];

        return $labels[$modulo] ?? ucfirst($modulo);
    }

    /**
     * Obtener descripción legible del permiso.
     */
    private function getPermissionDescription(string $permissionName): string
    {
        $actions = [
            'ver' => 'Ver',
            'crear' => 'Crear',
            'editar' => 'Editar',
            'eliminar' => 'Eliminar',
            'aprobar' => 'Aprobar',
            'rechazar' => 'Rechazar',
            'exportar' => 'Exportar',
            'anular' => 'Anular',
        ];

        $parts = explode('.', $permissionName);
        $modulo = $this->getModuloLabel($parts[0] ?? '');
        $action = $actions[$parts[1] ?? ''] ?? ucfirst($parts[1] ?? '');

        return "{$action} {$modulo}";
    }
}
