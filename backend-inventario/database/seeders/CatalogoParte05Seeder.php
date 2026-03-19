<?php
// Ferretería 001-100 — CAP-0327 a CAP-0426
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inventario\Models\Familia;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Administracion\Models\Empresa;

class CatalogoParte05Seeder extends Seeder
{
    const START = 327;

    public function run(): void
    {
        $empresa = Empresa::first();
        if (!$empresa) return;
        $eid = $empresa->id;

        $familia = Familia::withoutGlobalScopes()->firstOrCreate(
            ['empresa_id' => $eid, 'codigo' => 'FERR'],
            ['nombre' => 'Ferretería', 'es_epp' => false, 'activo' => true]
        );
        $fid = $familia->id;

        $productos = [
            ['ABRAZADERA 1"','UND'],
            ['ABRAZADERA 1/2"','UND'],
            ['ABRAZADERA 2"','UND'],
            ['ABRAZADERA 3"','UND'],
            ['ABRAZADERA 4"','UND'],
            ['ABRAZADERA DE 4" ACERO','UND'],
            ['ABRAZADERA DE 6" ACERO','UND'],
            ['ABRAZADERA DE ALINEAMIENTO','UND'],
            ['ABRAZADERA DE MANGUERA','UND'],
            ['ABRAZADERA GALVANIZADA 3/8"','UND'],
            ['ABRAZADERA METALICA 3"','UND'],
            ['ACCESORIOS PARA INSTALACIÓN DE BAÑO','UND'],
            ['ACERO GALVANIZADO CON GRILLETES','KG'],
            ['ACOPLE DE ALUMIMIO','UND'],
            ['ADAPTADOR DE 1" PVC','UND'],
            ['ADAPTADOR DE AGUA 2" PVC','UND'],
            ['ADAPTADOR DE PVC 2"','UND'],
            ['ADAPTADOR ENCHUFE MULTIPLE OPALUX','UND'],
            ['ADAPTADOR MULTIPLE INTERNACIONAL','UND'],
            ['ALAMBRE DE AMARRE N°16','KG'],
            ['ALAMBRE GALVANIZADO N°16','KG'],
            ['ALAMBRE N°16','KG'],
            ['ALAMBRE N°8','KG'],
            ['ALCAYATA','UND'],
            ['ALCAYATA L EN FIERRO CORRUGADO','UND'],
            ['ALDABA','UND'],
            ['ALICATE DE PRESION 10"','UND'],
            ['ALICATE KAMASA','UND'],
            ['ALICATE PARA ELECTRICISTA','UND'],
            ['AMARRADOR DE VARILLA','UND'],
            ['AMOLADORA MAKITA GR','UND'],
            ['ANILLO DE CENTRADO','UND'],
            ['ANILLO RN-164281','UND'],
            ['ANILLO RN-164811','UND'],
            ['ARANDELA TIPO RECTANGULAR','UND'],
            ['ARCO JARDINERO TIPO TUBULAR','UND'],
            ['ARCO Y SIERRA','UND'],
            ['ARCO Y SIERRA PARA MADERA','UND'],
            ['ARMASON DE CARPA - COMPLETO','JGO'],
            ['ASA DE CAMIONETA','UND'],
            ['ASA DE MADERA','UND'],
            ['AZUELA','UND'],
            ['BADEILEJO 7"','UND'],
            ['BADILEJO','UND'],
            ['BADILEJO 6"','UND'],
            ['BALANZA ANALOGICA AZUL','UND'],
            ['BALANZA OPALUX','UND'],
            ['BANDEJA 1.20X80','UND'],
            ['BANDEJA 70X40X8CM ALTO','UND'],
            ['BANDEJA 70X50X10 CM ALTO','UND'],
            ['BANDEJA 80X60X10CM ALTO','UND'],
            ['BANDEJA DE CARRETILLA PLOMO','UND'],
            ['BANDEJA PARA COMPRESORA DE AIRE','UND'],
            ['BANDEJA PARA PERFORACION','UND'],
            ['BANDEJA PARAR GRUPO ELECTROGENO','UND'],
            ['BARILLA DE BALACIN','UND'],
            ['BARNIZ MARINO','GAL'],
            ['BARRA PARA MEZCLAR','UND'],
            ['BARRAS','UND'],
            ['BARRETA 1 1/4 X1.50M','UND'],
            ['BARRETA 1"X1.50M','UND'],
            ['BARRETA 3/4"X1.20M','UND'],
            ['BARRETA 3/4"X1.50M','UND'],
            ['BARRETA 5/8"X1.50M','UND'],
            ['BARRETA CORRUGADA 1"X1.50M','UND'],
            ['BARRETA PLANO 4"','UND'],
            ['BARRETA PUNTA Y PLANA 1.60MT','UND'],
            ['BARRILLA','UND'],
            ['BASE ZINCROMATO','GAL'],
            ['BATIDOR 3"','UND'],
            ['BENTONITA X25K','SCO'],
            ['BISAGRA','UND'],
            ['BOQUILLA PARA ENGRASAR','UND'],
            ['BORNES DE BATERIA','UND'],
            ['BRIQUETAS','UND'],
            ['BROCA 38MM','UND'],
            ['BROCA PARA CEMENTO BOSCH 10MM','UND'],
            ['BROCA PARA METAL BOSCH 3/16" X10','JGO'],
            ['BROCHA 2"','UND'],
            ['BROCHA 3"','UND'],
            ['BROCHA 4"','UND'],
            ['BROCHA 5"','UND'],
            ['BRUÑA','UND'],
            ['BRUÑA DE CANTO','UND'],
            ['BRUÑA DE CENTRO','UND'],
            ['BUJE DE CAUCHO','UND'],
            ['BUJE DEL FRENO RND-6205','UND'],
            ['BUJIA 46 COPER AUTOLITE','UND'],
            ['BUJIA 66 COPER AUTOLITE','UND'],
            ['CABALLETE PARA CORTE DE MADERA','UND'],
            ['CABEZA DE MARTILLO','UND'],
            ['CABLE 2X14 X100MTS','UND'],
            ['CABLE 3 HILOS 3X10 X50MT','ROL'],
            ['CABLE 4 HILOS 4X10 X50MT','ROL'],
            ['CABLE AWG 2X12 X100MT','ROL'],
            ['CABLE AWG 2X14 X10MT','MT'],
            ['CABLE AWG 3X10 X100MT','ROL'],
            ['CABLE AWG 3X12 X100MT','ROL'],
            ['CABLE AWG 4X8 X100MT','ROL'],
            ['CABLE DE ACERO DE 1/2"','MT'],
            ['CABLE DE ACERO DE 1/4"','MT'],
        ];

        foreach ($productos as $i => [$nombre, $unidad]) {
            $codigo = 'CAP-' . str_pad(self::START + $i, 4, '0', STR_PAD_LEFT);
            Producto::firstOrCreate(
                ['empresa_id' => $eid, 'codigo' => $codigo],
                ['nombre' => $nombre, 'familia_id' => $fid, 'unidad_medida' => $unidad, 'activo' => true, 'stock_minimo' => 0, 'stock_maximo' => 0]
            );
        }
        $this->command->info('Parte 05 — Ferretería 001-100: ' . count($productos) . ' productos.');
    }
}
