<?php
// EPP-RES (8) + EPP-MAN (15) + EPP-PIE (30) + EPP-ALT (7) + EPP-CUE (44) — CAP-0147 a CAP-0250
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inventario\Models\Familia;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Administracion\Models\Empresa;

class CatalogoParte03Seeder extends Seeder
{
    const START = 147;

    public function run(): void
    {
        $empresa = Empresa::first();
        if (!$empresa) return;
        $eid = $empresa->id;

        $getFamilia = fn($codigo) => Familia::withoutGlobalScopes()
            ->where('empresa_id', $eid)->where('codigo', $codigo)->value('id');

        $grupos = [
            [$getFamilia('EPP-RES'), [
                ['ADAPTADOR DE FILTRO','UND'],
                ['FILTRO 7093','PAR'],
                ['FILTRO PARA GASES AR-703','PAR'],
                ['FILTRO PARA PARTICULAS 9100 3M','UND'],
                ['FILTRO PARA POLVO 7093 3M','UND'],
                ['FILTRO PARA VAPOR ORGANICO 7093C 3M','UND'],
                ['RESPIRADOR 3M','UND'],
                ['RETENEDOR PRE FILTRO 3M','UND'],
            ]],
            [$getFamilia('EPP-MAN'), [
                ['GUANTES ANTIVIBRATORIO STEEL POWER','PAR'],
                ['GUANTES BADANA','PAR'],
                ['GUANTES DE BADANA','PAR'],
                ['GUANTES DE CUERO','PAR'],
                ['GUANTES DE CUERO REFORZADO','PAR'],
                ['GUANTES DE JEBE ASTARA CAÑA LARGA','PAR'],
                ['GUANTES DE JEBE C-35 ASTARA','PAR'],
                ['GUANTES DE JEBE C35','PAR'],
                ['GUANTES DIELECTRICOS','PAR'],
                ['GUANTES MULTIUSO','PAR'],
                ['GUANTES NEOPRENO','PAR'],
                ['GUANTES NITRILO DESECHABLE','PAR'],
                ['GUANTES NITRON','PAR'],
                ['GUANTES NITRON PUÑO TEJIDO AZUL','PAR'],
                ['GUANTES SHOWA AZUL NK524','PAR'],
            ]],
            [$getFamilia('EPP-PIE'), [
                ['BOTAS DIELECTRICAS METATARSAL T38','PAR'],
                ['BOTAS DIELECTRICAS METATARSAL T39','PAR'],
                ['BOTAS DIELECTRICAS METATARSAL T40','PAR'],
                ['BOTAS DIELECTRICAS METATARSAL T42','PAR'],
                ['BOTAS DIELECTRICAS METATARSAL T43','PAR'],
                ['BOTAS METATARSAL DIELECTRICO T40','PAR'],
                ['BOTAS METATARSAL DIELECTRICO T41','PAR'],
                ['BOTAS METATARSAL DIELECTRICO T42','PAR'],
                ['BOTAS MUSLERA T40','PAR'],
                ['BOTAS PLANTA AMARILLA T39','PAR'],
                ['BOTAS SEGUSA PUNTA ACERO T38','PAR'],
                ['BOTAS SEGUSA PUNTA ACERO T39','PAR'],
                ['BOTAS SEGUSA PUNTA ACERO T40','PAR'],
                ['BOTAS SEGUSA PUNTA ACERO T41','PAR'],
                ['BOTAS SEGUSA PUNTA ACERO T42','PAR'],
                ['BOTAS SPRO PLANTA AMARILLA T39','PAR'],
                ['ZAPATO SEGPRO 35','PAR'],
                ['ZAPATO SEGPRO 39','PAR'],
                ['ZAPATO SEGPRO 40','PAR'],
                ['ZAPATO SEGPRO 42','PAR'],
                ['ZAPATO SEGPRO SEGUNDA','PAR'],
                ['ZAPATO SEGPRO T36','PAR'],
                ['ZAPATO SEGPRO T37','PAR'],
                ['ZAPATO SEGPRO T38','PAR'],
                ['ZAPATO SEGPRO T39','PAR'],
                ['ZAPATO SEGPRO T41','PAR'],
                ['ZAPATO SEGPRO T42','PAR'],
                ['ZAPATO SEGPRO T43','PAR'],
                ['ZAPATOS CAT 37','PAR'],
                ['ZAPATOS CAT 38','PAR'],
            ]],
            [$getFamilia('EPP-ALT'), [
                ['ARNES','UND'],
                ['ARNES CON LINEA DE VIDA','UND'],
                ['ARNES SEGPRO','UND'],
                ['CHALECO SALVAVIDAS','UND'],
                ['LINEA DE ANCLAJE','UND'],
                ['LINEA DE ANCLAJE DE 1','UND'],
                ['LINEA DE VIDA','UND'],
            ]],
            [$getFamilia('EPP-CUE'), [
                ['BARRA LUMINOSA ROJO','UND'],
                ['BARRA LUMINOSA VERDE','UND'],
                ['CAFARENA NEGRA','UND'],
                ['CALENTADOR NEGRO','UND'],
                ['CAMISA TERMICA XL','UND'],
                ['CAPOTIN XL AMARILLO','UND'],
                ['CAPOTIN XL ANARANJADO','UND'],
                ['CAPOTINES','UND'],
                ['CAPOTINES DE SEGUNDA','UND'],
                ['CASACA INDUSTRIAL L','UND'],
                ['CASACA INDUSTRIAL M','UND'],
                ['CASACA INDUSTRIAL S','UND'],
                ['CHALECO L','UND'],
                ['CHALECO M','UND'],
                ['CHALECO ROJO DE COMITÉ L','UND'],
                ['CHALECO ROJO DE COMITÉ M','UND'],
                ['CHALECO S','UND'],
                ['CHALECO XL','UND'],
                ['CORTAVIENTO ACOLCHADO','UND'],
                ['CORTAVIENTO DRILL','UND'],
                ['CORTAVIENTO DRILL AZUL','UND'],
                ['CORTAVIENTO NARANJA','UND'],
                ['COSTAL NEGRO 80K','UND'],
                ['MEDIAS LARGAS','UND'],
                ['OVEROL 2 CUERPOS L','UND'],
                ['OVEROL 2 CUERPOS XL','UND'],
                ['OVEROL 2 PIEZAS L','UND'],
                ['OVEROL 2 PIEZAS M','UND'],
                ['OVEROL ACOLCHADO M SEGUNDA','UND'],
                ['OVEROL L','UND'],
                ['OVEROL M','UND'],
                ['OVEROL S','UND'],
                ['OVEROL S SEGUNDA','UND'],
                ['OVEROL TERMICO AZUL L','UND'],
                ['OVEROL TERMICO L','UND'],
                ['OVEROL TERMICO XL','UND'],
                ['OVEROL UNIDO M','UND'],
                ['OVEROL XL','UND'],
                ['OVEROL XXL','UND'],
                ['PANTALON TERMICO L','UND'],
                ['POLO DEPORTIVO VERDE','UND'],
                ['ROPA DE JEBE T42','UND'],
                ['TYVEX','UND'],
                ['TYVEX M SEGPRO','UND'],
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
        $this->command->info("Parte 03 — EPP-RES/MAN/PIE/ALT/CUE: $total productos.");
    }
}
