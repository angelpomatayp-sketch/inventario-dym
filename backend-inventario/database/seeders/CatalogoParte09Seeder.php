<?php
// Ferretería 401-500 — CAP-0727 a CAP-0826
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inventario\Models\Familia;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Administracion\Models\Empresa;

class CatalogoParte09Seeder extends Seeder
{
    const START = 728;

    public function run(): void
    {
        $empresa = Empresa::first();
        if (!$empresa) return;
        $eid = $empresa->id;

        $fid = Familia::withoutGlobalScopes()->where('empresa_id', $eid)->where('codigo', 'FERR')->value('id');

        $productos = [
            ['MANOMETRO','UND'],
            ['MANTADA','UND'],
            ['MARTILLO','UND'],
            ['MARTILLO DE GOMA','UND'],
            ['MARTILLO TIPO CARPINTERO','UND'],
            ['MARTILLO TRUPER','UND'],
            ['MASILLA','UND'],
            ['MASILLA DE MADERA X1K','BLS'],
            ['MATA INSECTOS','UND'],
            ['MEDIDOR DE AIRE TRUPER 120LB','UND'],
            ['MENNEKE HEMBRA','UND'],
            ['MENNEKE MACHO','UND'],
            ['MENNEKE PULPO 16A','UND'],
            ['MENNEKES','UND'],
            ['MINI PACKER','UND'],
            ['MUELLE PARA MICROBUS','UND'],
            ['NEUMATICO DE VOLQUETE','UND'],
            ['NIPLE 1"X4"','UND'],
            ['NIPLE DE 1/2" X 3"','UND'],
            ['NIPLE GALVANIZADO 1"X1/2"','UND'],
            ['NIPLE MIXTO GALVANIZADO 4" X7"','UND'],
            ['NIPLE PVC','UND'],
            ['NIVEL DE MANO  48" X1.20CM','UND'],
            ['NIVEL DE MANO 18"X46CM','UND'],
            ['NIVEL DE MANO 1MT','UND'],
            ['NIVEL DE MANO 24" X60CM','UND'],
            ['NIVEL DE MANO 30CM','UND'],
            ['NIVEL DE MANO 48CM','UND'],
            ['NIVEL DE MANO 50CM','UND'],
            ['NIVEL DE MANO 60CM','UND'],
            ['NYLON','UND'],
            ['OCRE X1K','BLS'],
            ['OZ','UND'],
            ['PACKER 36MM','UND'],
            ['PACKER 41MM','UND'],
            ['PALA DE JARDIN','UND'],
            ['PALETA DE PULIR','UND'],
            ['PALO DE ECOBA Y RECOGEROR','JGO'],
            ['PALOS DE ESCOBA PARA BANDERINES','UND'],
            ['PARCHE PARA CAMARA DE CARRETILLA','UND'],
            ['PATA DE CABRA','UND'],
            ['PATA Y CABRA','UND'],
            ['PEGAMENTO AFRICANO','UND'],
            ['PEGAMENTO OATEY PVC X946ML','UND'],
            ['PEGAMENTO PARA PARCHE','UND'],
            ['PERCHERO DE MADERA','UND'],
            ['PERNO CROBY','UND'],
            ['PERNOS 1"','UND'],
            ['PERNOS 3/4"','UND'],
            ['PERNOS HILTI PARA ANCLAJE','UND'],
            ['PERNOS PARA PROBETAS','UND'],
            ['PICO','UND'],
            ['PICO PALA ANCHA','UND'],
            ['PICO ROJO PARA ESTACIÓN DE EMERGENCIA','UND'],
            ['PICO TRAMONTINA','UND'],
            ['PICOS','UND'],
            ['PICSA','UND'],
            ['PIN DE GRAMPA DE PERCUTOR NEUMATICO','UND'],
            ['PINTURA ALTO TRÁNSITO','GAL'],
            ['PINTURA ESMALTE AMARILLO','GAL'],
            ['PINTURA ESMALTE AMARILLO ANYPSA','GAL'],
            ['PINTURA ESMALTE AMARILLO CAT','GAL'],
            ['PINTURA ESMALTE BLANCO','GAL'],
            ['PINTURA ESMALTE BLANCO AMARILLO 1/4GL','GAL'],
            ['PINTURA ESMALTE BLANCO ANYPSA','GAL'],
            ['PINTURA ESMALTE GRANATE','GAL'],
            ['PINTURA ESMALTE NEGRO','GAL'],
            ['PINTURA ESMALTE NEGRO ANYPSA','GAL'],
            ['PINTURA ESMALTE ROJO','GAL'],
            ['PINTURA ESMALTE ROJO ANYPSA','GAL'],
            ['PINTURA ESMALTE ROJO BERMELLON','GAL'],
            ['PINTURA ESMALTE VERDE ANYPSA','GAL'],
            ['PINTURA ESMALTE VERDE CROMO','GAL'],
            ['PINTURA SPRAY ALUMINIO','UND'],
            ['PINTURA SPRAY AZUL','UND'],
            ['PINTURA SPRAY BLANCO','UND'],
            ['PINTURA SPRAY MARRON','UND'],
            ['PINTURA SPRAY NEGRO','UND'],
            ['PINTURA SPRAY ROJO','UND'],
            ['PISTO APLICADOR DE SILICONA','UND'],
            ['PISTOLA PARA SILICONA','UND'],
            ['PLACAS DE ANCLAJE','UND'],
            ['PLANCHA DE BATIR 7"','UND'],
            ['PLANCHA DE MADERA 20X30','UND'],
            ['PLANCHA DE PULIR','UND'],
            ['PLANCHA DE TECNOPOR 1"','UND'],
            ['PLASTICO DOBLE ANCHO AZUL','MT'],
            ['PLATINA 30CM X3/16"','UND'],
            ['PLOMADA','UND'],
            ['PLOMADA BRONCE','UND'],
            ['POLEA MANUAL DE 1 TNL','UND'],
            ['POLEA MANUAL DE 5TN','UND'],
            ['PONCHO DE VOLQUETE','UND'],
            ['PORTA SELLOS','UND'],
            ['PRECINTO 10"','UND'],
            ['PRECINTO 6"','UND'],
            ['PRECINTO 8"','UND'],
            ['PUENTE DE CARRETILLA','UND'],
            ['PULPO MENNEKE','UND'],
        ];

        foreach ($productos as $i => [$nombre, $unidad]) {
            $codigo = 'CAP-' . str_pad(self::START + $i, 4, '0', STR_PAD_LEFT);
            Producto::firstOrCreate(
                ['empresa_id' => $eid, 'codigo' => $codigo],
                ['nombre' => $nombre, 'familia_id' => $fid, 'unidad_medida' => $unidad, 'activo' => true, 'stock_minimo' => 0, 'stock_maximo' => 0]
            );
        }
        $this->command->info('Parte 09 — Ferretería 401-500: ' . count($productos) . ' productos.');
    }
}
