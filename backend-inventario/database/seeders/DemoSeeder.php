<?php

namespace Database\Seeders;

use App\Modules\Administracion\Models\Almacen;
use App\Modules\Administracion\Models\CentroCosto;
use App\Modules\Administracion\Models\Empresa;
use App\Modules\Administracion\Models\Usuario;
use App\Modules\Inventario\Models\Familia;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Inventario\Models\StockAlmacen;
use App\Modules\EPPs\Models\TipoEpp;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear empresa demo
        $empresa = Empresa::firstOrCreate(
            ['ruc' => '20123456789'],
            [
                'razon_social' => 'DYM SAC',
                'nombre_comercial' => 'DYM Minería',
                'direccion' => 'Av. Principal 123, Lima',
                'departamento' => 'Lima',
                'provincia' => 'Lima',
                'distrito' => 'San Isidro',
                'telefono' => '01-234-5678',
                'email' => 'contacto@dym.com.pe',
                'metodo_valorizacion' => 'PROMEDIO',
                'activo' => true,
            ]
        );

        $this->command->info("Empresa creada: {$empresa->razon_social}");

        // Crear usuario administrador
        $admin = Usuario::firstOrCreate(
            ['email' => 'admin@dym.com.pe'],
            [
                'empresa_id' => $empresa->id,
                'nombre' => 'Administrador DYM',
                'password' => Hash::make('admin123'),
                'dni' => '12345678',
                'telefono' => '999-888-777',
                'activo' => true,
            ]
        );
        $admin->assignRole('super_admin');

        $this->command->info("Usuario admin creado: {$admin->email} / admin123");

        // Crear centros de costo
        $centros = [
            ['codigo' => 'ADM', 'nombre' => 'Administración', 'descripcion' => 'Área administrativa'],
            ['codigo' => 'OPE', 'nombre' => 'Operaciones Mina', 'descripcion' => 'Operaciones mineras'],
            ['codigo' => 'MAN', 'nombre' => 'Mantenimiento', 'descripcion' => 'Mantenimiento de equipos'],
            ['codigo' => 'SEG', 'nombre' => 'Seguridad', 'descripcion' => 'Seguridad y SSOMA'],
            ['codigo' => 'LOG', 'nombre' => 'Logística', 'descripcion' => 'Logística y almacén'],
        ];

        foreach ($centros as $centro) {
            CentroCosto::firstOrCreate(
                ['empresa_id' => $empresa->id, 'codigo' => $centro['codigo']],
                array_merge($centro, ['empresa_id' => $empresa->id, 'activo' => true])
            );
        }

        $this->command->info("Centros de costo creados: " . count($centros));

        // Crear almacenes
        $almacenes = [
            ['codigo' => 'ALM-001', 'nombre' => 'Almacén Central', 'tipo' => 'PRINCIPAL', 'ubicacion' => 'Lima - Sede Principal'],
            ['codigo' => 'ALM-002', 'nombre' => 'Almacén Campamento', 'tipo' => 'CAMPAMENTO', 'ubicacion' => 'Unidad Minera'],
            ['codigo' => 'ALM-003', 'nombre' => 'Almacén Taller', 'tipo' => 'SATELITE', 'ubicacion' => 'Taller de Mantenimiento'],
        ];

        foreach ($almacenes as $almacen) {
            Almacen::firstOrCreate(
                ['empresa_id' => $empresa->id, 'codigo' => $almacen['codigo']],
                array_merge($almacen, ['empresa_id' => $empresa->id, 'activo' => true])
            );
        }

        $this->command->info("Almacenes creados: " . count($almacenes));

        // Crear familias de productos
        $familias = [
            ['codigo' => 'EPP', 'nombre' => 'EPPs', 'descripcion' => 'Equipos de Protección Personal'],
            ['codigo' => 'REP', 'nombre' => 'Repuestos', 'descripcion' => 'Repuestos y componentes'],
            ['codigo' => 'LUB', 'nombre' => 'Lubricantes', 'descripcion' => 'Aceites y lubricantes'],
            ['codigo' => 'HER', 'nombre' => 'Herramientas', 'descripcion' => 'Herramientas manuales y eléctricas'],
            ['codigo' => 'MAT', 'nombre' => 'Materiales', 'descripcion' => 'Materiales de construcción'],
            ['codigo' => 'FER', 'nombre' => 'Ferretería', 'descripcion' => 'Artículos de ferretería'],
            ['codigo' => 'ELE', 'nombre' => 'Eléctricos', 'descripcion' => 'Material eléctrico'],
        ];

        foreach ($familias as $familia) {
            Familia::firstOrCreate(
                ['empresa_id' => $empresa->id, 'codigo' => $familia['codigo']],
                array_merge($familia, ['empresa_id' => $empresa->id, 'activo' => true])
            );
        }

        $this->command->info("Familias de productos creadas: " . count($familias));

        // Obtener IDs de familias y almacenes
        $familiaEPP = Familia::where('empresa_id', $empresa->id)->where('codigo', 'EPP')->first();
        $familiaREP = Familia::where('empresa_id', $empresa->id)->where('codigo', 'REP')->first();
        $familiaLUB = Familia::where('empresa_id', $empresa->id)->where('codigo', 'LUB')->first();
        $familiaHER = Familia::where('empresa_id', $empresa->id)->where('codigo', 'HER')->first();
        $familiaFER = Familia::where('empresa_id', $empresa->id)->where('codigo', 'FER')->first();

        $almacenCentral = Almacen::where('empresa_id', $empresa->id)->where('codigo', 'ALM-001')->first();
        $almacenCampamento = Almacen::where('empresa_id', $empresa->id)->where('codigo', 'ALM-002')->first();

        // Crear productos de prueba
        $productos = [
            // EPPs
            ['codigo' => 'PRD-001', 'nombre' => 'Casco de Seguridad MSA V-Gard', 'familia_id' => $familiaEPP->id, 'unidad_medida' => 'UND', 'stock_minimo' => 20, 'marca' => 'MSA', 'stock' => 45, 'costo' => 85.00],
            ['codigo' => 'PRD-002', 'nombre' => 'Guantes de Nitrilo Talla M', 'familia_id' => $familiaEPP->id, 'unidad_medida' => 'PAR', 'stock_minimo' => 50, 'marca' => '3M', 'stock' => 150, 'costo' => 12.50],
            ['codigo' => 'PRD-003', 'nombre' => 'Zapatos de Seguridad Punta Acero', 'familia_id' => $familiaEPP->id, 'unidad_medida' => 'PAR', 'stock_minimo' => 20, 'marca' => 'CAT', 'stock' => 12, 'costo' => 180.00],
            ['codigo' => 'PRD-004', 'nombre' => 'Lentes de Seguridad 3M', 'familia_id' => $familiaEPP->id, 'unidad_medida' => 'UND', 'stock_minimo' => 30, 'marca' => '3M', 'stock' => 0, 'costo' => 25.00],
            ['codigo' => 'PRD-005', 'nombre' => 'Chaleco Reflectivo Naranja', 'familia_id' => $familiaEPP->id, 'unidad_medida' => 'UND', 'stock_minimo' => 15, 'marca' => 'Nacional', 'stock' => 35, 'costo' => 28.00],
            ['codigo' => 'PRD-006', 'nombre' => 'Respirador 3M Serie 6000', 'familia_id' => $familiaEPP->id, 'unidad_medida' => 'UND', 'stock_minimo' => 10, 'marca' => '3M', 'stock' => 8, 'costo' => 145.00],

            // Repuestos
            ['codigo' => 'PRD-007', 'nombre' => 'Rodamiento SKF 6205-2RS', 'familia_id' => $familiaREP->id, 'unidad_medida' => 'UND', 'stock_minimo' => 15, 'marca' => 'SKF', 'stock' => 8, 'costo' => 45.00],
            ['codigo' => 'PRD-008', 'nombre' => 'Filtro de Aire CAT 131-8822', 'familia_id' => $familiaREP->id, 'unidad_medida' => 'UND', 'stock_minimo' => 8, 'marca' => 'CAT', 'stock' => 5, 'costo' => 320.00],
            ['codigo' => 'PRD-009', 'nombre' => 'Correa de Transmisión Gates', 'familia_id' => $familiaREP->id, 'unidad_medida' => 'UND', 'stock_minimo' => 5, 'marca' => 'Gates', 'stock' => 12, 'costo' => 89.00],
            ['codigo' => 'PRD-010', 'nombre' => 'Filtro de Aceite Hidráulico', 'familia_id' => $familiaREP->id, 'unidad_medida' => 'UND', 'stock_minimo' => 10, 'marca' => 'Donaldson', 'stock' => 6, 'costo' => 185.00],

            // Lubricantes
            ['codigo' => 'PRD-011', 'nombre' => 'Aceite Hidráulico Shell Tellus S2 M46', 'familia_id' => $familiaLUB->id, 'unidad_medida' => 'GAL', 'stock_minimo' => 10, 'marca' => 'Shell', 'stock' => 25, 'costo' => 125.00],
            ['codigo' => 'PRD-012', 'nombre' => 'Grasa Multiusos EP2', 'familia_id' => $familiaLUB->id, 'unidad_medida' => 'KG', 'stock_minimo' => 20, 'marca' => 'Mobil', 'stock' => 45, 'costo' => 35.00],
            ['codigo' => 'PRD-013', 'nombre' => 'Aceite Motor 15W40', 'familia_id' => $familiaLUB->id, 'unidad_medida' => 'GAL', 'stock_minimo' => 15, 'marca' => 'Castrol', 'stock' => 30, 'costo' => 95.00],

            // Herramientas
            ['codigo' => 'PRD-014', 'nombre' => 'Llave Francesa 12"', 'familia_id' => $familiaHER->id, 'unidad_medida' => 'UND', 'stock_minimo' => 5, 'marca' => 'Stanley', 'stock' => 8, 'costo' => 65.00],
            ['codigo' => 'PRD-015', 'nombre' => 'Juego de Llaves Allen', 'familia_id' => $familiaHER->id, 'unidad_medida' => 'JGO', 'stock_minimo' => 3, 'marca' => 'Stanley', 'stock' => 6, 'costo' => 45.00],

            // Ferretería
            ['codigo' => 'PRD-016', 'nombre' => 'Perno Hexagonal 1/2" x 2"', 'familia_id' => $familiaFER->id, 'unidad_medida' => 'UND', 'stock_minimo' => 100, 'marca' => 'Nacional', 'stock' => 250, 'costo' => 1.50],
            ['codigo' => 'PRD-017', 'nombre' => 'Tuerca Hexagonal 1/2"', 'familia_id' => $familiaFER->id, 'unidad_medida' => 'UND', 'stock_minimo' => 100, 'marca' => 'Nacional', 'stock' => 300, 'costo' => 0.80],
            ['codigo' => 'PRD-018', 'nombre' => 'Arandela Plana 1/2"', 'familia_id' => $familiaFER->id, 'unidad_medida' => 'UND', 'stock_minimo' => 100, 'marca' => 'Nacional', 'stock' => 400, 'costo' => 0.30],
        ];

        foreach ($productos as $productoData) {
            $stock = $productoData['stock'];
            $costo = $productoData['costo'];
            unset($productoData['stock'], $productoData['costo']);

            $producto = Producto::firstOrCreate(
                ['empresa_id' => $empresa->id, 'codigo' => $productoData['codigo']],
                array_merge($productoData, [
                    'empresa_id' => $empresa->id,
                    'activo' => $stock > 0 || $productoData['codigo'] !== 'PRD-004',
                ])
            );

            // Crear stock en almacén central
            if ($almacenCentral) {
                StockAlmacen::firstOrCreate(
                    ['producto_id' => $producto->id, 'almacen_id' => $almacenCentral->id],
                    [
                        'empresa_id' => $empresa->id,
                        'stock_actual' => $stock,
                        'stock_minimo' => $productoData['stock_minimo'],
                        'stock_maximo' => $productoData['stock_minimo'] * 3,
                        'costo_promedio' => $costo,
                    ]
                );
            }
        }

        $this->command->info("Productos creados: " . count($productos));

        // Crear tipos de EPP
        $tiposEpp = [
            [
                'codigo' => 'EPP-CAS',
                'nombre' => 'Casco de Seguridad',
                'descripcion' => 'Casco de seguridad industrial',
                'categoria' => 'CABEZA',
                'vida_util_dias' => 730, // 2 años
                'dias_alerta_vencimiento' => 60,
                'requiere_talla' => true,
                'tallas_disponibles' => 'S, M, L, XL',
            ],
            [
                'codigo' => 'EPP-GUA',
                'nombre' => 'Guantes de Seguridad',
                'descripcion' => 'Guantes de nitrilo o cuero',
                'categoria' => 'MANOS',
                'vida_util_dias' => 90, // 3 meses
                'dias_alerta_vencimiento' => 15,
                'requiere_talla' => true,
                'tallas_disponibles' => 'S, M, L, XL',
            ],
            [
                'codigo' => 'EPP-ZAP',
                'nombre' => 'Zapatos de Seguridad',
                'descripcion' => 'Zapatos con punta de acero',
                'categoria' => 'PIES',
                'vida_util_dias' => 365, // 1 año
                'dias_alerta_vencimiento' => 30,
                'requiere_talla' => true,
                'tallas_disponibles' => '38, 39, 40, 41, 42, 43, 44',
            ],
            [
                'codigo' => 'EPP-LEN',
                'nombre' => 'Lentes de Seguridad',
                'descripcion' => 'Lentes protectores',
                'categoria' => 'OJOS',
                'vida_util_dias' => 180, // 6 meses
                'dias_alerta_vencimiento' => 30,
                'requiere_talla' => false,
                'tallas_disponibles' => null,
            ],
            [
                'codigo' => 'EPP-CHA',
                'nombre' => 'Chaleco Reflectivo',
                'descripcion' => 'Chaleco de alta visibilidad',
                'categoria' => 'CUERPO',
                'vida_util_dias' => 365, // 1 año
                'dias_alerta_vencimiento' => 30,
                'requiere_talla' => true,
                'tallas_disponibles' => 'S, M, L, XL, XXL',
            ],
            [
                'codigo' => 'EPP-RES',
                'nombre' => 'Respirador',
                'descripcion' => 'Respirador con filtros',
                'categoria' => 'RESPIRATORIO',
                'vida_util_dias' => 180, // 6 meses
                'dias_alerta_vencimiento' => 30,
                'requiere_talla' => true,
                'tallas_disponibles' => 'S, M, L',
            ],
            [
                'codigo' => 'EPP-TAP',
                'nombre' => 'Tapones Auditivos',
                'descripcion' => 'Protectores auditivos',
                'categoria' => 'OIDOS',
                'vida_util_dias' => 30, // 1 mes
                'dias_alerta_vencimiento' => 7,
                'requiere_talla' => false,
                'tallas_disponibles' => null,
            ],
            [
                'codigo' => 'EPP-ARN',
                'nombre' => 'Arnés de Seguridad',
                'descripcion' => 'Arnés para trabajo en altura',
                'categoria' => 'ALTURA',
                'vida_util_dias' => 1825, // 5 años
                'dias_alerta_vencimiento' => 90,
                'requiere_talla' => true,
                'tallas_disponibles' => 'M, L, XL',
            ],
        ];

        foreach ($tiposEpp as $tipoData) {
            TipoEpp::firstOrCreate(
                ['empresa_id' => $empresa->id, 'codigo' => $tipoData['codigo']],
                array_merge($tipoData, ['empresa_id' => $empresa->id, 'activo' => true])
            );
        }

        $this->command->info("Tipos de EPP creados: " . count($tiposEpp));

        // Crear usuarios adicionales con los nuevos roles
        $usuarios = [
            [
                'nombre' => 'Juan Pérez',
                'email' => 'logistica@dym.com.pe',
                'password' => 'logistica123',
                'rol' => 'jefe_logistica',
            ],
            [
                'nombre' => 'María García',
                'email' => 'almacenero@dym.com.pe',
                'password' => 'almacen123',
                'rol' => 'almacenero',
                'almacen_id' => $almacenCentral?->id,
            ],
            [
                'nombre' => 'Ana Torres',
                'email' => 'asistente@dym.com.pe',
                'password' => 'asistente123',
                'rol' => 'asistente_admin',
            ],
            [
                'nombre' => 'Carlos López',
                'email' => 'residente@dym.com.pe',
                'password' => 'residente123',
                'rol' => 'residente',
            ],
            [
                'nombre' => 'Pedro Ramírez',
                'email' => 'seguridad@dym.com.pe',
                'password' => 'seguridad123',
                'rol' => 'solicitante',
            ],
        ];

        foreach ($usuarios as $userData) {
            $almacenId = $userData['almacen_id'] ?? null;
            unset($userData['almacen_id']);

            $usuario = Usuario::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'empresa_id' => $empresa->id,
                    'nombre' => $userData['nombre'],
                    'password' => Hash::make($userData['password']),
                    'almacen_id' => $almacenId,
                    'activo' => true,
                ]
            );
            $usuario->assignRole($userData['rol']);
        }

        $this->command->info("Usuarios demo creados: " . count($usuarios));

        $this->command->newLine();
        $this->command->info('=== DATOS DE ACCESO ===');
        $this->command->table(
            ['Usuario', 'Email', 'Password', 'Rol'],
            [
                ['Administrador', 'admin@dym.com.pe', 'admin123', 'super_admin'],
                ['Juan Pérez', 'logistica@dym.com.pe', 'logistica123', 'jefe_logistica'],
                ['María García', 'almacenero@dym.com.pe', 'almacen123', 'almacenero'],
                ['Ana Torres', 'asistente@dym.com.pe', 'asistente123', 'asistente_admin'],
                ['Carlos López', 'residente@dym.com.pe', 'residente123', 'residente'],
                ['Pedro Ramírez', 'seguridad@dym.com.pe', 'seguridad123', 'solicitante'],
            ]
        );
    }
}
