<?php
// Formatos (10) + Insumos médicos (86) — CAP-1057 a CAP-1152
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inventario\Models\Familia;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Administracion\Models\Empresa;

class CatalogoParte12Seeder extends Seeder
{
    const START = 1057;

    public function run(): void
    {
        $empresa = Empresa::first();
        if (!$empresa) return;
        $eid = $empresa->id;

        $getFamilia = function($codigo, $nombre) use ($eid) {
            return Familia::withoutGlobalScopes()->firstOrCreate(
                ['empresa_id' => $eid, 'codigo' => $codigo],
                ['nombre' => $nombre, 'es_epp' => false, 'activo' => true]
            )->id;
        };

        $grupos = [
            [$getFamilia('FORM','Formatos'), [
                ['CUADERNO DE OPERACIÓN SUBTERRANEO','UND'],
                ['CUADERNO DE OPERRACIÓN SUPERFICIE','UND'],
                ['DECLARACIÓN JURADA','TAL'],
                ['ESTANDARES DE PROCEDIMIENTO','UND'],
                ['GUÍA DE REMISIÓN REMITENTE','TAL'],
                ['GUÍA DE REMISIÓN TRANSPORTISTA','TAL'],
                ['HOJA DE INSPECCIÓN DE VOLQUETE','UND'],
                ['RENDICIÓN DE GASTOS','TAL'],
                ['RITRA','UND'],
                ['VALE DE SALIDA ALMACÉN','TAL'],
            ]],
            [$getFamilia('INS-MED','Insumos médicos'), [
                ['AGUA OXIGENADA 120ML','UND'],
                ['AGUA OXIGENADA X120ML','UND'],
                ['AGUA OXIGENADA X500ML','UND'],
                ['AGUA OXIGENADA X60ML','UND'],
                ['AGUA OXIGENDA 1L','UND'],
                ['ALCOHOL 120ML','UND'],
                ['ALCOHOL 70° 120ML','UND'],
                ['ALCOHOL 70° 700ML','UND'],
                ['ALCOHOL 70° X120ML','UND'],
                ['ALCOHOL 70°X 1000ML','UND'],
                ['ALCOHOL 96° 700ML','UND'],
                ['ALGODÓN 100GR','UND'],
                ['ALGODÓN 10GR','UND'],
                ['ALGODÓN 120GR','UND'],
                ['ALGODÓN X25GR','UND'],
                ['APOSITO DE GASA 10X10','UND'],
                ['APÓSITO DE GASA 10X10','UND'],
                ['ATOMIZADOR PEQUEÑO','UND'],
                ['BAJA LENGUA ESTERIL','UND'],
                ['BAJA LENGUA SUELTO','UND'],
                ['BLOQUEADOR EN SHACHET','UND'],
                ['BOTIQUIN 25X35','UND'],
                ['BOTIQUIN 40X30','UND'],
                ['CABESTRILLO','UND'],
                ['CABESTRILLO DE TELA','UND'],
                ['CICATRIZANTE EN POLVO','UND'],
                ['CLORURO DE SODIO','UND'],
                ['CLORURO DE SODIO X1000ML','UND'],
                ['COLIRIO','UND'],
                ['COLIRIO EYEMO X12ML','UND'],
                ['COLLARIN CERVICAL AJUSTABLE M','UND'],
                ['COLLARIN CERVICAL BLANDO L','UND'],
                ['COLLARIN CERVICAL BLANDO XL','UND'],
                ['COLLARIN CERVICAL RIGIDO AJUSTABLE','UND'],
                ['CORREA NEGRA','UND'],
                ['CORTINAS PARA DECORACIÓN LI/DR','UND'],
                ['CURITAS','UND'],
                ['ESPARADRAPO','UND'],
                ['ESQUINERO PARA PUERTA','UND'],
                ['FERULAS','JGO'],
                ['FOCO LED GR','UND'],
                ['GASA ABSORVENTE','UND'],
                ['GASA ESTERIL 10X10','UND'],
                ['GASA ESTERIL 5X5','UND'],
                ['GASA ESTERIL 7.5X7.5','UND'],
                ['GLOBO METALICO PAPA CHICO','UND'],
                ['GUANTES A GRANEL DESECHALES','PAR'],
                ['GUANTES ESTERILIZADOS N°7','UND'],
                ['GUANTES QUIRURGICO ESTERIL','UND'],
                ['GUANTES QUIRURGICOS','UND'],
                ['JABON ANTISEPTICO CH','UND'],
                ['JABON EN BARRA PEQUEÑO','UND'],
                ['JABON LIQUIDO 360ML','UND'],
                ['JABON LIQUIDO 60ML','UND'],
                ['JABON LIQUIDO X4L','GAL'],
                ['JABON LIQUIDO X700ML','UND'],
                ['JENOLET','UND'],
                ['JERINGA EXTRINSECA 20ML','UND'],
                ['LLAVERO CASCO','UND'],
                ['MASCARILLA','UND'],
                ['MASCARILLA DESECHABLE','UND'],
                ['MASCARILLA KN95','UND'],
                ['MASCARILLA NEGRA KN95','UND'],
                ['MASCARILLA SIMPLE','CJA'],
                ['PINZA DE DISECCION','UND'],
                ['PINZA SINGER','UND'],
                ['SORBETE','PQTE'],
                ['SULFADIAZINA DE PLATA','UND'],
                ['TAZAS PAPA','UND'],
                ['TENSIOMETRO DIGITAL','UND'],
                ['TIJERA DE CIRUGIA','UND'],
                ['TIJERA DE TRAUMA','UND'],
                ['TIJERA PUNTA ROMA','UND'],
                ['TIJERA QUIRURGICA RECTA','UND'],
                ['TINTURA DE ARNICA','UND'],
                ['VASO DESCARTABLE 5.5','PQTE'],
                ['VENDA 2"X5','UND'],
                ['VENDA 3"X5','UND'],
                ['VENDA ELASTICA 2X5','UND'],
                ['VENDA ELASTICA 3X5','UND'],
                ['VENDA ELASTICA 4X5','UND'],
                ['VENDA ELASTICA 6X5','UND'],
                ['VENDAS 3"X5','UND'],
                ['VENDAS 4"X5','UND'],
                ['VENDITAS ADHESIVAS','UND'],
                ['YODOPOVIDOMA X60ML','UND'],
            ]],
        ];

        $seq = self::START;
        $total = 0;
        foreach ($grupos as [$fid, $prods]) {
            foreach ($prods as [$nombre, $unidad]) {
                $codigo = 'CAP-' . str_pad($seq++, 4, '0', STR_PAD_LEFT);
                Producto::firstOrCreate(
                    ['empresa_id' => $eid, 'codigo' => $codigo],
                    ['nombre' => $nombre, 'familia_id' => $fid, 'unidad_medida' => $unidad, 'activo' => true, 'stock_minimo' => 0, 'stock_maximo' => 0]
                );
                $total++;
            }
        }
        $this->command->info("Parte 12 — Formatos + Insumos médicos: $total productos.");
    }
}
