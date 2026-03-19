<?php
// Barras y Cinceles — CAP-0001 a CAP-0092
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inventario\Models\Familia;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Administracion\Models\Empresa;

class CatalogoParte01Seeder extends Seeder
{
    const START = 1;

    public function run(): void
    {
        $empresa = Empresa::first();
        if (!$empresa) return;
        $eid = $empresa->id;

        $familia = Familia::withoutGlobalScopes()->firstOrCreate(
            ['empresa_id' => $eid, 'codigo' => 'BAR-CIN'],
            ['nombre' => 'Barras y Cinceles', 'es_epp' => false, 'activo' => true]
        );
        $fid = $familia->id;

        $productos = [
            ['ABRAZADERA PARA TUBERÍA DE BOMBA','UND'],
            ['BARRA CONICA 10 PIES','UND'],
            ['BARRA CONICA 2 PIES','UND'],
            ['BARRA CONICA 4 PIES','UND'],
            ['BARRA CONICA 5 PIES','UND'],
            ['BARRA CONICA 6 PIES','UND'],
            ['BARRA CONICA 8 PIES','UND'],
            ['BARRA CÓNICA INTEGRAL 3 PIES','UND'],
            ['BARRA DE AVANCE DE 4 PIES','UND'],
            ['BARRA DE AVANCE DE 6 PIES','UND'],
            ['BARRA DE AVANCE DE 6 PIES PARA JACKLEG','UND'],
            ['BARRA ESCARREADORA 2"','UND'],
            ['BARRA PILOTO 2 PIES','UND'],
            ['BARRA PILOTO 4 PIES','UND'],
            ['BARRA PILOTO 6 PIES','UND'],
            ['BARRA R- 25 5 PIES','UND'],
            ['BARRA R-25 4 PIES','UND'],
            ['BARRA R-25 6 PIES','UND'],
            ['BARRA R-25 EXTENSIÓN 4 PIES','UND'],
            ['BARRENO 10"','UND'],
            ['BARRENO 8"','UND'],
            ['BARRENO R-25 4 PIES','UND'],
            ['BARRENO R-25 5 PIES','UND'],
            ['BARRENO R-25 6 PIES','UND'],
            ['BARRETILLA 10 PIES PUNTA','UND'],
            ['BARRETILLA 10 PIES UÑA','UND'],
            ['BARRETILLA 4 PIES PUNTA','UND'],
            ['BARRETILLA 4 PIES UÑA','UND'],
            ['BARRETILLA 6 PIES PUNTA','UND'],
            ['BARRETILLA 6 PIES UÑA','UND'],
            ['BARRETILLA 8 PIES PUNTA','UND'],
            ['BARRETILLA 8 PIES UÑA','UND'],
            ['BOCINA DE JACKLEG','UND'],
            ['BROCA 1 1/4" PARA CONCRETO','UND'],
            ['BROCA 1 1/4" X 47CM PARA CONCRETO','UND'],
            ['BROCA 3" X5/8 PARA CONCRETO','UND'],
            ['BROCA 36MM 050122','UND'],
            ['BROCA 38MM 0501145','UND'],
            ['BROCA 41MM 050146','UND'],
            ['BROCA CONICA','UND'],
            ['BROCA DE 1/2" BOSCH','UND'],
            ['BROCA DE 2" X 1/4" /32','UND'],
            ['BROCA DE 3/8" X 20CM','UND'],
            ['BROCA DE CONCRETO 1" X2PIES','UND'],
            ['BROCA DE MARTILLO PERFORADOR 1/2"','UND'],
            ['BROCA DE PERFORACION 3/4" X40CM','UND'],
            ['BROCA ESCARREADORA 62MM','UND'],
            ['BROCA R-25 35MM','UND'],
            ['BROCA R-25 38MM','UND'],
            ['BROCA R-25 41MM','UND'],
            ['BROCA TIPO PIPA X 40CM','UND'],
            ['CAMPANA REDUCTOR DE 4" A 1"','UND'],
            ['CINCEL CON EMPUÑADORA DE JEBE','UND'],
            ['CINCEL DE 25CM','UND'],
            ['CINCEL LISO 1" X 30CM','UND'],
            ['CINCEL PLANO BOSH','UND'],
            ['CINCEL PLANO BOSH MEDIANO','UND'],
            ['CINCEL PLANO HECHIZAS','UND'],
            ['CINCEL PLANO PARA BOSCH','UND'],
            ['CINCEL PUNTA 1 1/4 PARA PERCUTOR NEUMATICO','UND'],
            ['CINCEL PUNTA BOSH','UND'],
            ['CINCEL PUNTA BOSH MEDIANO','UND'],
            ['CINCEL PUNTA EXAGONAL','UND'],
            ['CINCEL PUNTA PARA BOSCH','UND'],
            ['CINCEL PUNTA Y PLANO PEQUEÑO','UND'],
            ['CINCEL PUNTAS METALICAS HECHIZAS','UND'],
            ['CODO DE TUBERIA METALICA 45° PARA BOMBA CONCRETERA','UND'],
            ['CONO DE MADERA PARA PERFORACIÓN','UND'],
            ['COUPLING','UND'],
            ['COUPLING R-25 S/B 350054','UND'],
            ['LANZA PARA INYECCIÓN DE RESINA 1.5M','UND'],
            ['LANZA PARA INYECCIÓN DE RESINA CON MANGUERA DE PRESION','UND'],
            ['MANGA DE VENTILACION 24"','ROL'],
            ['MEZCLADO PARA BOSCH','UND'],
            ['MINIPAKER 3/16','UND'],
            ['PLATAFORMA DE PERFORACIÓN','UND'],
            ['PONCHO PARA LLANTA DE CAMION','UND'],
            ['PORTA BARRETILLAS','UND'],
            ['PUNTAS DE 25CM','UND'],
            ['PUNTERA 0.65CM','UND'],
            ['PUNTERA 0.85CM','UND'],
            ['PUNTERA 1.60MT','UND'],
            ['ROD SHANK R-25','UND'],
            ['SOPORTE PARA ENCOFRADO','UND'],
            ['SPLIT ADAPTADOR','UND'],
            ['TUBERIA ACOPLE 20CM','UND'],
            ['TUBERIA ACOPLE 30CM','UND'],
            ['TUBERIA ACOPLE 50CM','UND'],
            ['TUBERIA PARA BOMBA DE CONCRETO 1MT','UND'],
            ['TUBERIA PARA BOMBA DE CONCRETO 2MT','UND'],
            ['TUBERÍA PARA BOMBA DE CONCRETO 3MT','UND'],
            ['VARILLA DE EJE R-25 HEXAGONAL','UND'],
        ];

        foreach ($productos as $i => [$nombre, $unidad]) {
            $codigo = 'CAP-' . str_pad(self::START + $i, 4, '0', STR_PAD_LEFT);
            Producto::firstOrCreate(
                ['empresa_id' => $eid, 'nombre' => $nombre],
                ['familia_id' => $fid, 'codigo' => $codigo, 'unidad_medida' => $unidad, 'activo' => true, 'stock_minimo' => 0, 'stock_maximo' => 0]
            );
        }
        $this->command->info('Parte 01 — Barras y Cinceles: ' . count($productos) . ' productos.');
    }
}
