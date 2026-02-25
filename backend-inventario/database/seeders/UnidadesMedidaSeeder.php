<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inventario\Models\UnidadMedida;

class UnidadesMedidaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $unidades = [
            ['codigo' => 'UND', 'nombre' => 'Unidad', 'abreviatura' => 'UND'],
            ['codigo' => 'PAR', 'nombre' => 'Par', 'abreviatura' => 'PAR'],
            ['codigo' => 'GAL', 'nombre' => 'Galón', 'abreviatura' => 'GAL'],
            ['codigo' => 'LT', 'nombre' => 'Litro', 'abreviatura' => 'LT'],
            ['codigo' => 'KG', 'nombre' => 'Kilogramo', 'abreviatura' => 'KG'],
            ['codigo' => 'MT', 'nombre' => 'Metro', 'abreviatura' => 'MT'],
            ['codigo' => 'CJA', 'nombre' => 'Caja', 'abreviatura' => 'CJA'],
            ['codigo' => 'BLS', 'nombre' => 'Bolsa', 'abreviatura' => 'BLS'],
            ['codigo' => 'ROL', 'nombre' => 'Rollo', 'abreviatura' => 'ROL'],
            ['codigo' => 'PZA', 'nombre' => 'Pieza', 'abreviatura' => 'PZA'],
            ['codigo' => 'PLG', 'nombre' => 'Pliego', 'abreviatura' => 'PLG'],
            ['codigo' => 'ML', 'nombre' => 'Mililitro', 'abreviatura' => 'ML'],
            ['codigo' => 'GR', 'nombre' => 'Gramo', 'abreviatura' => 'GR'],
            ['codigo' => 'CM', 'nombre' => 'Centímetro', 'abreviatura' => 'CM'],
            ['codigo' => 'M2', 'nombre' => 'Metro cuadrado', 'abreviatura' => 'M2'],
            ['codigo' => 'M3', 'nombre' => 'Metro cúbico', 'abreviatura' => 'M3'],
            ['codigo' => 'JGO', 'nombre' => 'Juego', 'abreviatura' => 'JGO'],
            ['codigo' => 'KIT', 'nombre' => 'Kit', 'abreviatura' => 'KIT'],
        ];

        foreach ($unidades as $unidad) {
            UnidadMedida::firstOrCreate(
                ['codigo' => $unidad['codigo']],
                array_merge($unidad, [
                    'empresa_id' => null, // Unidades globales
                    'activo' => true,
                ])
            );
        }
    }
}
