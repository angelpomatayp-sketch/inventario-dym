<?php

namespace Database\Seeders;

use App\Modules\Administracion\Models\Empresa;
use App\Modules\Inventario\Models\Familia;
use Illuminate\Database\Seeder;

class FamiliasEppSeeder extends Seeder
{
    /**
     * Crear familias de EPP por defecto para todas las empresas.
     */
    public function run(): void
    {
        $familiasEpp = [
            [
                'codigo' => 'EPP-CAB',
                'nombre' => 'EPP - Protección de Cabeza',
                'descripcion' => 'Cascos, capuchas y otros equipos de protección para la cabeza',
                'es_epp' => true,
                'categoria_epp' => 'CABEZA',
            ],
            [
                'codigo' => 'EPP-OJO',
                'nombre' => 'EPP - Protección Ocular',
                'descripcion' => 'Lentes de seguridad, caretas faciales y protectores oculares',
                'es_epp' => true,
                'categoria_epp' => 'OJOS',
            ],
            [
                'codigo' => 'EPP-AUD',
                'nombre' => 'EPP - Protección Auditiva',
                'descripcion' => 'Tapones auditivos, orejeras y protectores de oído',
                'es_epp' => true,
                'categoria_epp' => 'OIDOS',
            ],
            [
                'codigo' => 'EPP-RES',
                'nombre' => 'EPP - Protección Respiratoria',
                'descripcion' => 'Respiradores, mascarillas y equipos de protección respiratoria',
                'es_epp' => true,
                'categoria_epp' => 'RESPIRATORIO',
            ],
            [
                'codigo' => 'EPP-MAN',
                'nombre' => 'EPP - Protección de Manos',
                'descripcion' => 'Guantes de diferentes tipos y materiales',
                'es_epp' => true,
                'categoria_epp' => 'MANOS',
            ],
            [
                'codigo' => 'EPP-PIE',
                'nombre' => 'EPP - Protección de Pies',
                'descripcion' => 'Botas de seguridad, zapatos dieléctricos y calzado de protección',
                'es_epp' => true,
                'categoria_epp' => 'PIES',
            ],
            [
                'codigo' => 'EPP-CUE',
                'nombre' => 'EPP - Protección Corporal',
                'descripcion' => 'Overoles, chalecos reflectivos, mandiles y ropa de protección',
                'es_epp' => true,
                'categoria_epp' => 'CUERPO',
            ],
            [
                'codigo' => 'EPP-ALT',
                'nombre' => 'EPP - Trabajo en Altura',
                'descripcion' => 'Arneses, líneas de vida, mosquetones y equipos para trabajo en altura',
                'es_epp' => true,
                'categoria_epp' => 'ALTURA',
            ],
        ];

        // Obtener todas las empresas
        $empresas = Empresa::all();

        foreach ($empresas as $empresa) {
            foreach ($familiasEpp as $familiaData) {
                // Verificar si ya existe
                $existe = Familia::withoutGlobalScopes()
                    ->where('empresa_id', $empresa->id)
                    ->where('codigo', $familiaData['codigo'])
                    ->exists();

                if (!$existe) {
                    Familia::create([
                        'empresa_id' => $empresa->id,
                        ...$familiaData,
                        'activo' => true,
                    ]);
                }
            }
        }

        $this->command->info('Familias de EPP creadas para ' . $empresas->count() . ' empresa(s)');
    }
}
