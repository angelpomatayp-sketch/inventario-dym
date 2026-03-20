<?php
// Tuberías 51-112 (62) + Útiles de Escritorio 1-75 — CAP-1353 a CAP-1489
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inventario\Models\Familia;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Administracion\Models\Empresa;

class CatalogoParte15Seeder extends Seeder
{
    const START = 1353;

    public function run(): void
    {
        $empresa = Empresa::first();
        if (!$empresa) return;
        $eid = $empresa->id;

        $getFamilia = fn($c) => Familia::withoutGlobalScopes()->where('empresa_id',$eid)->where('codigo',$c)->value('id');

        $grupos = [
            [$getFamilia('TUB'), [
                ['FIBRA DE VIDRIO PARA CONCRETO','CJA'],
                ['FLOCULANTE','SCO'],
                ['FLOCULANTE X25K','SCO'],
                ['INCORPORADOR DE AIRE SIKA','GAL'],
                ['LLAE DE PASO PVC 1"','UND'],
                ['LLAE DE PASO PVC 2"','UND'],
                ['LLAE DE PASO PVC 4"','UND'],
                ['LLAVE DE PASO 1"','UND'],
                ['LLAVE DE PASO DE 1" ACERO','UND'],
                ['LLAVE DE PASO DE 1/2" ACERO','UND'],
                ['LLAVE DE PASO GALVANIZADO 2"','UND'],
                ['LLAVE DE PASO PVC 1/2"','UND'],
                ['MANGA DE VENTILACION X15MTS','UND'],
                ['NIPLE 1" COLA DE PEZ','UND'],
                ['NIPLE 1/2" COLA DE PEZ','UND'],
                ['NIPLE 1/2" PVC','UND'],
                ['NIPLE 2" AGUA CALIENTE','UND'],
                ['NIPLE 2" METAL','UND'],
                ['NIPLE 4" PVC','UND'],
                ['NIPLE DE ACERO DE 1/2"','UND'],
                ['NIPLE DE ACERO DE 1/4"','UND'],
                ['NIPLE DE ACERO TIPO ROSCA 1"','UND'],
                ['NIPLE DE ACERO TIPO ROSCA 1/2"','UND'],
                ['NIPLE DE ACERO TIPO ROSCA 2"','UND'],
                ['NIPLE MIXTO 1/2"','UND'],
                ['NIPLE MIXTO 2" METAL','UND'],
                ['NIPLE REDUCTOR DE 2" A 1" METAL','UND'],
                ['NIPLE ROSCADO 2" METAL','UND'],
                ['NIPLE ROSCADO 4" PVC','UND'],
                ['NIPLE UNION ESPIGA DE ACERO 2"','UND'],
                ['REDUCCIÓN DE 1/2" A 1" PVC','UND'],
                ['REDUCCIÓN DE PVC DE 6 A 4','UND'],
                ['REDUCTOR DE 2" A 1" HDPE','UND'],
                ['RESINA','BT'],
                ['SAL INDUSTRIAL','KG'],
                ['SIKA ACELERANTE','GAL'],
                ['SIKA CURADOR PARA CONCRETO','GAL'],
                ['SIKA SEPAROL','BLS'],
                ['T DE PVC DE 4"','UND'],
                ['T DE TUBO DE PVC 2"','UND'],
                ['TAPOM HEMBRA PVC 1/2','UND'],
                ['TUBERIA 1" X100MTS','ROL'],
                ['TUBERIA ACOPLE 18CM','UND'],
                ['TUBO GALVANIZADO DE 1/2"','UND'],
                ['TUBO PVC 2" PESADO','UND'],
                ['UNION 1" HDPE','UND'],
                ['UNION 1/2','UND'],
                ['UNION CON ROSCA PVC 4"','UND'],
                ['UNION DE 2" ACERADO ROSCA','UND'],
                ['UNION DE ACERO CON REDUCCIÓN DE 2" A 1"','UND'],
                ['UNION DE ACERO CON REDUCCIÓN DE 2" A 11/2"','UND'],
                ['UNION DE ACERO TIPO ROSCA DE 1"','UND'],
                ['UNION DE ACERO TIPO ROSCA DE 1/2"','UND'],
                ['UNION DE PVC 8"','UND'],
                ['UNIÓN SIMPLE 1" PVC','UND'],
                ['VALVULA BOLA 1/2"','UND'],
                ['VALVULA BOLA 2" METAL','UND'],
                ['VALVULA BRIDADA DE AGUA','UND'],
                ['VALVULA CHECK SWING 1/2" ACERO','UND'],
                ['VALVULA DE TOPE 1/2"','UND'],
                ['VALVULOA DE RETENCION','UND'],
                ['Z CURADOR','BLS'],
            ]],
            [Familia::withoutGlobalScopes()->firstOrCreate(
                ['empresa_id' => $eid, 'codigo' => 'UT-ESC'],
                ['nombre' => 'Útiles de Escritorio', 'es_epp' => false, 'activo' => true]
            )->id, [
                ['ACRILICO PARA FOTOCHEK','UND'],
                ['ACTOS Y CONDICIONES','UND'],
                ['ADAPTADOR DE PILAS 2A','UND'],
                ['AGENDA 2025','UND'],
                ['AGENDA 2026','UND'],
                ['AGUA MINERAL','CJA'],
                ['ARCHIVADOR 1/2 OFICIO','UND'],
                ['ARCHIVADOR A4','UND'],
                ['ARCHIVADOR ARTESCO 1/2 OFICIO','UND'],
                ['ARCHIVADOR ARTESCO OFICIO','UND'],
                ['ARCHIVADOR BLANCO A4','UND'],
                ['ARCHIVADOR OFICIO AZUL','UND'],
                ['ARCHIVADOR OFICIO NEGRO','UND'],
                ['BALANZA ELECTRONICA PORTATIL','UND'],
                ['BANDEJA ACRILICA','PQTE'],
                ['BANDEROLA CELESTE','UND'],
                ['BATERIA OPALUX','UND'],
                ['BOLSA CELOFAN 9X14','PQTE'],
                ['BOLSA DE TELA NAVIDEÑA','UND'],
                ['BOLSA ZIP','UND'],
                ['BORRADOR ARTESCO','UND'],
                ['BROCHETA #8','PQTE'],
                ['CABLE CONEXIÓN DE INTERNET','MT'],
                ['CABLE ETHERNET','UND'],
                ['CABLE Y ENCHUFE PARA IMPRESORA','UND'],
                ['CALEFACTOR RECCO','UND'],
                ['CALEFACTOR RECCO NEGRO','UND'],
                ['CAMA DE MADERA 1.5 PL','UND'],
                ['CAPA NAVIDEÑA','UND'],
                ['CARGADOR DE BATERIA UNIVERSAL','UND'],
                ['CARGADOR DE LAPTOP','UND'],
                ['CARTULINA VERDE','UND'],
                ['CHINCHE COLOR','CJA'],
                ['CHINCHE DORADO','CJA'],
                ['CHINCHE DORADO X100','UND'],
                ['CHINCHE X100','CJA'],
                ['CINTA AZUL PARA FOTOCHEK','UND'],
                ['CINTA FILM','UND'],
                ['CINTA METRICA','UND'],
                ['CINTA PARA SILBATO','UND'],
                ['CINTA SCOCH CHICO','UND'],
                ['CINTA SCOCH GR','UND'],
                ['CINTILLLO GR','UND'],
                ['CINTILLO CHICO','UND'],
                ['CINTILLO DORADO','ROL'],
                ['CLIPS','CJA'],
                ['CLIPS ARTESCO X100','CJA'],
                ['CLIPS X100','UND'],
                ['COLORES VR/MD','UND'],
                ['CORONA NAVIDEÑA','UND'],
                ['CORRECTOR ARTESCO','UND'],
                ['CORRECTOR FC','UND'],
                ['CORTADOR DE HOJAS','UND'],
                ['CPU HALION MODELO: ATX-500W','UND'],
                ['CPU MACRONICS FENIX -DESK 9981-2014','UND'],
                ['CUADERNO DE OBRA RAYADO X100 HOJAS','UND'],
                ['CUADERNO DE OBRA X100HOJAS','UND'],
                ['CUADERNO OPERACIÓN SIPERFICIE','UND'],
                ['CUADERNO OPERACIÓN SUBTERRANEO','UND'],
                ['CUADERNO OPERACIÓN SUPERFICIE','UND'],
                ['CUADERNO RAYADO A4','UND'],
                ['DECLARACION JURADA','UND'],
                ['DECLARCION JURADA','TAL'],
                ['DECRETO 024','UND'],
                ['DISCO DURO SSD 486 GB','UND'],
                ['EMBALAJE','UND'],
                ['EMBALAJE 2" X200YDS','UND'],
                ['EMPAQUE BLUE PACK','UND'],
                ['ENGRAPADOR','UND'],
                ['ENGRAPADOR DE 100 HOJAS','UND'],
                ['ENGRAPADOR METAL GR','UND'],
                ['ENMICADORA','UND'],
                ['ENVASE DE TINTA','UND'],
                ['ESCANER CANON C357 IF FL79200','UND'],
                ['ESCRITORIO','UND'],
            ]],
        ];

        $seq = self::START;
        $total = 0;
        foreach ($grupos as [$fid, $prods]) {
            foreach ($prods as [$nombre, $unidad]) {
                $codigo = 'CAP-' . str_pad($seq++, 4, '0', STR_PAD_LEFT);
                Producto::withoutGlobalScopes()->firstOrCreate(
                    ['empresa_id' => $eid, 'codigo' => $codigo],
                    ['nombre' => $nombre, 'familia_id' => $fid, 'unidad_medida' => $unidad, 'activo' => true, 'stock_minimo' => 0, 'stock_maximo' => 0]
                );
                $total++;
            }
        }
        $this->command->info("Parte 15 — Tuberías 51-112 + Útiles 1-75: $total productos.");
    }
}
