<?php
// Materiales de seguridad 57-106 (50) + Tuberías 1-50 — CAP-1248 a CAP-1347
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inventario\Models\Familia;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Administracion\Models\Empresa;

class CatalogoParte14Seeder extends Seeder
{
    const START = 1249;

    public function run(): void
    {
        $empresa = Empresa::first();
        if (!$empresa) return;
        $eid = $empresa->id;

        $getFamilia = fn($c) => Familia::withoutGlobalScopes()->where('empresa_id',$eid)->where('codigo',$c)->value('id');

        $grupos = [
            [$getFamilia('MAT-SEG'), [
                ['FERULAS 60CM','UND'],
                ['FRAZADAS','UND'],
                ['GUANTES DE JEBE PARA LAVADO DE TAPER','PAR'],
                ['JABON LIQUIDO 1L','UND'],
                ['JABON LIQUIDO 5L','UND'],
                ['KIT ANTIDERRAME','KIT'],
                ['KIT ANTIDERRAME IMPLEMENTADO','KIT'],
                ['KIT ANTIDERRAME PARA EQUIPOS','UND'],
                ['KIT ANTIDERRAME PARA EQUIPOS ESTACIONARIOS','UND'],
                ['LAVAOJOS','UND'],
                ['LEJIA','GAL'],
                ['LUVES DE EMERGENCIA','UND'],
                ['MALETIN DE EMERGENCIA','UND'],
                ['MANGUERA PARA CABLEADO NEGRO','MT'],
                ['MESA CUADRADA PLASTICO AZUL','UND'],
                ['MESA CUADRADA PLASTICO BLANCO','UND'],
                ['MESA DE PLASTICO SIN PATAS','UND'],
                ['MMALETA DE KIT ANTIDERRAME','UND'],
                ['MOCHILA DE EMERGENCIA','UND'],
                ['MOCHILA PARA EXPLOSIVOS 25K','UND'],
                ['PALETA DE PARE Y SIGA','UND'],
                ['PALETA PARE Y SIGA','PAR'],
                ['PANEL INFORMATIVO DE MADERA','UND'],
                ['PAPEL TOALLA','ROL'],
                ['PAÑO ABSORBENTE','UND'],
                ['PAÑOS ABSOBENTES','UND'],
                ['PAÑOS AMARILLOS','UND'],
                ['POETT','GAL'],
                ['PORTA EXTINTOR','UND'],
                ['QUITASARRO','GAL'],
                ['REFLECTOR 1000W','UND'],
                ['SALCHICHA ABSORBENTE','UND'],
                ['SALCHICHAS ABSORBENTES','UND'],
                ['SECADOR DE PISO DE GOMA','UND'],
                ['SILLA PLASTICO AZUL','UND'],
                ['SILLA PLASTICO BLANCO','UND'],
                ['SODA CAUSTICA','KG'],
                ['SORBETE PLASTICO','PQTE'],
                ['TACHO RR.SS MED','UND'],
                ['TACHO RR.SS MED X6','JGO'],
                ['TACHO RR.SS REDONDO GR','JGO'],
                ['TACHO VERDE DE 40 LTS','UND'],
                ['TAPER','UND'],
                ['TOMATODO AZUL','UND'],
                ['TRAPEADOR AZUL','UND'],
                ['TRAPO INDUSTRIAL','KG'],
                ['TRAPO INDUSTRIAL COCIDO','UND'],
                ['TRAPO INDUSTRIAL SUELTO','KG'],
                ['VASO DESCARTABLE N°7','PQTE'],
                ['VASO PLASTIUCO REY','UND'],
            ]],
            [Familia::withoutGlobalScopes()->firstOrCreate(
                ['empresa_id' => $eid, 'codigo' => 'TUB'],
                ['nombre' => 'Tuberías', 'es_epp' => false, 'activo' => true]
            )->id, [
                ['ABONO IMPORTADO','SCO'],
                ['ABRAZADERA 1" GALVANIZADO','UND'],
                ['ABRAZADERA 2" GALVANIZADO','UND'],
                ['ABRAZADERA 4" ACERO INOX','UND'],
                ['ABRAZADERA GALVANIZADA 1/2"','UND'],
                ['ACEITE USADO DE 1/4','GAL'],
                ['ACELERANTE SIKACEM','UND'],
                ['ACOPALDOR HEMBRA PARA MANGUER 3/4','UND'],
                ['ACOPLE DE ACERO REDUCCION DE 1 A 1/2"','UND'],
                ['ACOPLE DE TUBO HDPR 4"','UND'],
                ['ACOPLE RAPIDO 1" HDPE','UND'],
                ['ACOPLE RAPIDO 1/2" HDPE','UND'],
                ['ACOPLE RAPIDO 2" HDPE','UND'],
                ['ACOPLE UNION RAPIDA 1"','UND'],
                ['ACOPLE UNION RAPIDA DE 2"A 1/2"','UND'],
                ['ACOPLE UNION RAPIDA DE 2"A 11/2"','UND'],
                ['ACOPLE UNION RAPIDA HDPE 2"','UND'],
                ['ACOPLE UNION RAPIDA TIPO T 2" A 1"','UND'],
                ['ADAPTADOR DE 1/2" BRONCE','UND'],
                ['ADAPTADOR DE COLA DE PEZ PARA JACKLEG','UND'],
                ['ADAPTADOR MACHO INSTALACIÓN DE TUBERIA 1/2"','UND'],
                ['ADAPTADOR PARA AIRE DE 1/2"','UND'],
                ['ADITIVO PARA AGUA X236ML','BT'],
                ['APADTADOR 1" PVC','UND'],
                ['AVENA IMPORTADA','SCO'],
                ['BOYA DE TANQUE DE AGUA','UND'],
                ['BRAZO DE BOYA 1 1/2"','UND'],
                ['BRAZO DE BOYA 2"','UND'],
                ['BUSHING 1/2"','UND'],
                ['CAL','KG'],
                ['CAL NIEVE','BLS'],
                ['CARTUCHO DE RESINA','CJA'],
                ['CEMENTO CONDUCTIVO','BLS'],
                ['CEMENTO EXPANSIVO','BLS'],
                ['CEMENTO EXPANSIVO PIEDRATEK X20K','CJA'],
                ['CEMENTO EXPANSIVO X25K','BLS'],
                ['CEMENTO YURA ANTISALITRE','BLS'],
                ['CHEMA PLASTIFICANTE X55GLN','GAL'],
                ['CODO 1" 90° PVC A PRESIÓN','UND'],
                ['CODO 1/2" METAL','UND'],
                ['CODO 45°','UND'],
                ['CODO 90°','UND'],
                ['CODO DE 1/2" METAL','UND'],
                ['CODO HDPE','UND'],
                ['CONECTOR HDPE GR','UND'],
                ['CONECTOR HDPE MED','UND'],
                ['ENLACE RECTO HDPE','UND'],
                ['EXPANSOR DE CONCRETO SIKA','UND'],
                ['EXPANSOR DE CONCRETO SIKA 0.85K','BLS'],
                ['EXPANSOR DE CONCRETO SIKA X9UND','CJA'],
            ]],
        ];

        $seq = self::START;
        $total = 0;
        foreach ($grupos as [$fid, $prods]) {
            foreach ($prods as [$nombre, $unidad]) {
                $codigo = 'CAP-' . str_pad($seq++, 4, '0', STR_PAD_LEFT);
                Producto::firstOrCreate(
                    ['empresa_id' => $eid, 'nombre' => $nombre],
                    ['familia_id' => $fid, 'codigo' => $codigo, 'unidad_medida' => $unidad, 'activo' => true, 'stock_minimo' => 0, 'stock_maximo' => 0]
                );
                $total++;
            }
        }
        $this->command->info("Parte 14 — Mat.Seg 57-106 + Tuberías 1-50: $total productos.");
    }
}
