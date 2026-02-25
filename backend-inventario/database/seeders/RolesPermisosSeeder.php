<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermisosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Estructura de roles DYM SAC:
     * - super_admin: Gerencia / TICs (acceso total)
     * - jefe_logistica: Jefe de Logística (todos los almacenes)
     * - asistente_admin: Asistente Administrativa por proyecto
     * - almacenero: Almacenero (solo su almacén)
     * - residente: Residente de Obra (aprueba/valida)
     * - solicitante: Ing. Seguridad, Calidad, etc.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // =====================================================================
        // PERMISOS POR MÓDULO
        // =====================================================================

        $permisos = [
            // Empresas
            'empresas.ver',
            'empresas.crear',
            'empresas.editar',
            'empresas.eliminar',

            // Usuarios
            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.eliminar',
            'usuarios.cambiar_password',

            // Trabajadores (personal sin login)
            'trabajadores.ver',
            'trabajadores.crear',
            'trabajadores.editar',
            'trabajadores.eliminar',

            // Roles
            'roles.ver',
            'roles.asignar',

            // Centros de Costo (Proyectos)
            'centros_costo.ver',
            'centros_costo.crear',
            'centros_costo.editar',
            'centros_costo.eliminar',

            // Almacenes
            'almacenes.ver',
            'almacenes.crear',
            'almacenes.editar',
            'almacenes.eliminar',

            // Familias/Categorías
            'familias.ver',
            'familias.crear',
            'familias.editar',
            'familias.eliminar',

            // Unidades de Medida
            'unidades.ver',
            'unidades.crear',
            'unidades.editar',
            'unidades.eliminar',

            // Productos
            'productos.ver',
            'productos.crear',
            'productos.editar',
            'productos.eliminar',
            'productos.exportar',

            // Movimientos
            'movimientos.ver',
            'movimientos.crear',
            'movimientos.anular',
            'movimientos.exportar',

            // Kardex
            'kardex.ver',
            'kardex.exportar',

            // Proveedores
            'proveedores.ver',
            'proveedores.crear',
            'proveedores.editar',
            'proveedores.eliminar',
            'proveedores.calificar',

            // Requisiciones
            'requisiciones.ver',
            'requisiciones.crear',
            'requisiciones.editar',
            'requisiciones.eliminar',
            'requisiciones.aprobar',
            'requisiciones.rechazar',
            'requisiciones.validar',

            // Vales de Salida
            'vales_salida.ver',
            'vales_salida.crear',
            'vales_salida.entregar',
            'vales_salida.anular',
            'vales_salida.validar',

            // Cotizaciones
            'cotizaciones.ver',
            'cotizaciones.crear',
            'cotizaciones.editar',
            'cotizaciones.eliminar',
            'cotizaciones.seleccionar',

            // Órdenes de Compra
            'ordenes_compra.ver',
            'ordenes_compra.crear',
            'ordenes_compra.editar',
            'ordenes_compra.eliminar',
            'ordenes_compra.aprobar',
            'ordenes_compra.recepcionar',
            'ordenes_compra.exportar',

            // EPPs
            'epps.ver',
            'epps.crear',
            'epps.editar',
            'epps.eliminar',
            'epps.asignar',
            'epps.renovar',

            // Préstamos de Equipos
            'prestamos.ver',
            'prestamos.crear',
            'prestamos.editar',
            'prestamos.eliminar',
            'prestamos.devolver',
            'prestamos.renovar',
            'prestamos.reportes',

            // Equipos Prestables
            'equipos_prestables.ver',
            'equipos_prestables.crear',
            'equipos_prestables.editar',
            'equipos_prestables.eliminar',
            'equipos_prestables.importar',

            // Reportes
            'reportes.inventario',
            'reportes.kardex',
            'reportes.movimientos',
            'reportes.consumos',
            'reportes.compras',
            'reportes.epps',
            'reportes.dashboard',
        ];

        // Crear permisos
        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'sanctum']);
        }

        // =====================================================================
        // ROLES
        // =====================================================================

        // -----------------------------------------------------------------
        // NIVEL 1: Super Admin - Gerencia / TICs (Acceso total)
        // -----------------------------------------------------------------
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'sanctum']);
        $superAdmin->syncPermissions(Permission::all());

        // -----------------------------------------------------------------
        // NIVEL 2: Jefe de Logística (Ve todos los almacenes, crea usuarios)
        // -----------------------------------------------------------------
        $jefeLogistica = Role::firstOrCreate(['name' => 'jefe_logistica', 'guard_name' => 'sanctum']);
        $jefeLogistica->syncPermissions([
            // Usuarios y trabajadores
            'usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.cambiar_password',
            'trabajadores.ver', 'trabajadores.crear', 'trabajadores.editar', 'trabajadores.eliminar',
            'roles.ver', 'roles.asignar',

            // Estructura organizacional
            'centros_costo.ver', 'centros_costo.crear', 'centros_costo.editar',
            'almacenes.ver', 'almacenes.crear', 'almacenes.editar',

            // Inventario completo
            'familias.ver', 'familias.crear', 'familias.editar',
            'unidades.ver', 'unidades.crear', 'unidades.editar',
            'productos.ver', 'productos.crear', 'productos.editar', 'productos.exportar',
            'movimientos.ver', 'movimientos.crear', 'movimientos.anular', 'movimientos.exportar',
            'kardex.ver', 'kardex.exportar',

            // Proveedores
            'proveedores.ver', 'proveedores.crear', 'proveedores.editar', 'proveedores.calificar',

            // Requisiciones y Vales
            'requisiciones.ver', 'requisiciones.aprobar', 'requisiciones.rechazar', 'requisiciones.validar',
            'vales_salida.ver', 'vales_salida.crear', 'vales_salida.entregar', 'vales_salida.validar',

            // Compras
            'cotizaciones.ver', 'cotizaciones.crear', 'cotizaciones.editar', 'cotizaciones.seleccionar',
            'ordenes_compra.ver', 'ordenes_compra.crear', 'ordenes_compra.aprobar',
            'ordenes_compra.recepcionar', 'ordenes_compra.exportar',

            // EPPs
            'epps.ver', 'epps.crear', 'epps.editar', 'epps.asignar', 'epps.renovar',

            // Préstamos - gestión completa
            'prestamos.ver', 'prestamos.crear', 'prestamos.editar', 'prestamos.eliminar',
            'prestamos.devolver', 'prestamos.renovar', 'prestamos.reportes',
            'equipos_prestables.ver', 'equipos_prestables.crear', 'equipos_prestables.editar',
            'equipos_prestables.eliminar', 'equipos_prestables.importar',

            // Reportes
            'reportes.inventario', 'reportes.kardex', 'reportes.movimientos',
            'reportes.consumos', 'reportes.compras', 'reportes.epps', 'reportes.dashboard',
        ]);

        // -----------------------------------------------------------------
        // NIVEL 3: Asistente Administrativa (Registra personal del proyecto)
        // -----------------------------------------------------------------
        $asistenteAdmin = Role::firstOrCreate(['name' => 'asistente_admin', 'guard_name' => 'sanctum']);
        $asistenteAdmin->syncPermissions([
            // Puede crear usuarios del staff y trabajadores
            'usuarios.ver', 'usuarios.crear', 'usuarios.editar',
            'trabajadores.ver', 'trabajadores.crear', 'trabajadores.editar',

            // Ve su proyecto
            'centros_costo.ver',
            'almacenes.ver',

            // Inventario (solo lectura)
            'familias.ver',
            'unidades.ver',
            'productos.ver',
            'kardex.ver',

            // Proveedores (compartidos)
            'proveedores.ver',

            // Puede crear solicitudes
            'requisiciones.ver', 'requisiciones.crear', 'requisiciones.editar',
            'vales_salida.ver', 'vales_salida.crear',

            // EPPs (solo ver)
            'epps.ver',

            // Préstamos - puede solicitar
            'prestamos.ver', 'prestamos.crear',
            'equipos_prestables.ver',
        ]);

        // -----------------------------------------------------------------
        // NIVEL 3: Almacenero (Solo su almacén asignado)
        // -----------------------------------------------------------------
        $almacenero = Role::firstOrCreate(['name' => 'almacenero', 'guard_name' => 'sanctum']);
        $almacenero->syncPermissions([
            // Ve trabajadores para asignar EPPs/préstamos
            'trabajadores.ver',

            // Su almacén
            'almacenes.ver',

            // Inventario
            'familias.ver',
            'unidades.ver',
            'productos.ver', 'productos.crear',
            'movimientos.ver', 'movimientos.crear',
            'kardex.ver',

            // Proveedores (compartidos)
            'proveedores.ver',

            // Atiende solicitudes
            'requisiciones.ver',
            'vales_salida.ver', 'vales_salida.crear', 'vales_salida.entregar',
            'ordenes_compra.ver', 'ordenes_compra.recepcionar',

            // EPPs - asigna
            'epps.ver', 'epps.asignar',

            // Préstamos - registra y devuelve
            'prestamos.ver', 'prestamos.crear', 'prestamos.devolver',
            'equipos_prestables.ver',

            // Reportes básicos (de su almacén)
            'reportes.inventario', 'reportes.kardex', 'reportes.dashboard',
        ]);

        // -----------------------------------------------------------------
        // NIVEL 4: Residente (Aprueba/Valida solicitudes de su proyecto)
        // -----------------------------------------------------------------
        $residente = Role::firstOrCreate(['name' => 'residente', 'guard_name' => 'sanctum']);
        $residente->syncPermissions([
            // Ve su almacén asignado
            'almacenes.ver',

            // Kardex - puede ver el de su almacén
            'kardex.ver',

            // Requisiciones - crea solicitudes, aprueba y valida
            'requisiciones.ver', 'requisiciones.crear', 'requisiciones.editar',
            'requisiciones.aprobar', 'requisiciones.rechazar', 'requisiciones.validar',

            // Vales - solo ver y validar (NO crear)
            'vales_salida.ver', 'vales_salida.validar',

            // EPPs (solo ver)
            'epps.ver',

            // Préstamos - solo ver (NO crear)
            'prestamos.ver',
            'equipos_prestables.ver',

            // Reportes de consumo
            'reportes.consumos',
        ]);

        // -----------------------------------------------------------------
        // NIVEL 4: Solicitante (Ing. Seguridad, Calidad, etc.)
        // -----------------------------------------------------------------
        $solicitante = Role::firstOrCreate(['name' => 'solicitante', 'guard_name' => 'sanctum']);
        $solicitante->syncPermissions([
            // Productos (solo ver)
            'productos.ver',

            // Solo crea solicitudes
            'requisiciones.ver', 'requisiciones.crear',
            'vales_salida.ver', 'vales_salida.crear',

            // EPPs (solo ver los suyos)
            'epps.ver',

            // Préstamos - puede solicitar
            'prestamos.ver', 'prestamos.crear',
            'equipos_prestables.ver',
        ]);

        // -----------------------------------------------------------------
        // ROLES ADICIONALES (Opcionales)
        // -----------------------------------------------------------------

        // Auditor - Solo lectura para auditorías
        $auditor = Role::firstOrCreate(['name' => 'auditor', 'guard_name' => 'sanctum']);
        $auditor->syncPermissions([
            'empresas.ver',
            'usuarios.ver',
            'trabajadores.ver',
            'centros_costo.ver',
            'almacenes.ver',
            'familias.ver',
            'unidades.ver',
            'productos.ver', 'productos.exportar',
            'movimientos.ver', 'movimientos.exportar',
            'kardex.ver', 'kardex.exportar',
            'proveedores.ver',
            'requisiciones.ver',
            'vales_salida.ver',
            'cotizaciones.ver',
            'ordenes_compra.ver', 'ordenes_compra.exportar',
            'epps.ver',
            'prestamos.ver', 'prestamos.reportes',
            'equipos_prestables.ver',
            'reportes.inventario', 'reportes.kardex', 'reportes.movimientos',
            'reportes.consumos', 'reportes.compras', 'reportes.epps', 'reportes.dashboard',
        ]);

        // =====================================================================
        // RESUMEN
        // =====================================================================

        $this->command->info('Roles y permisos creados exitosamente.');
        $this->command->newLine();
        $this->command->table(
            ['Nivel', 'Rol', 'Descripción', 'Permisos'],
            [
                ['1', 'super_admin', 'Gerencia / TICs', count(Permission::all())],
                ['2', 'jefe_logistica', 'Jefe de Logística', $jefeLogistica->permissions->count()],
                ['3', 'asistente_admin', 'Asist. Administrativa (por proyecto)', $asistenteAdmin->permissions->count()],
                ['3', 'almacenero', 'Almacenero (por almacén)', $almacenero->permissions->count()],
                ['4', 'residente', 'Residente de Obra', $residente->permissions->count()],
                ['4', 'solicitante', 'Ing. Seguridad, Calidad, etc.', $solicitante->permissions->count()],
                ['-', 'auditor', 'Auditor (solo lectura)', $auditor->permissions->count()],
            ]
        );

        $this->command->newLine();
        $this->command->info('Jerarquía de creación de usuarios:');
        $this->command->line('  Gerencia/TICs → Jefe de Logística, Asist. Admin, Almacenero');
        $this->command->line('  Asist. Admin  → Residente, Solicitantes, Trabajadores');
    }
}
