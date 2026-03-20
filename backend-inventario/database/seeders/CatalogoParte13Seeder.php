<?php
// Materiales (44) + Materiales de seguridad 1-56 — CAP-1153 a CAP-1252
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inventario\Models\Familia;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Administracion\Models\Empresa;

class CatalogoParte13Seeder extends Seeder
{
    const START = 1153;

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
            [$getFamilia('MAT','Materiales'), [
                ['BALDE DE PLASTICO','UND'],
                ['BANDEJA DE METAL 1.20X1.50X20CM ALT','UND'],
                ['BANDEJA DE METAL 1.20X1.50X40CM ALT','UND'],
                ['CABALLETE PARA CILINDRO','UND'],
                ['CARRO MINERO Z20 CON PLATAFORMA','UND'],
                ['CASETA PARA DETECTOR','UND'],
                ['CASETA PARA LAVAOJOS','UND'],
                ['CONCERTINA','ROL'],
                ['FENOLICOS 2.44MX1.22M','UND'],
                ['GEOMEMBRANA HDPE 1MM','ROL'],
                ['GEOMEMBRANA HDPE 2MM','MT'],
                ['GEOTEXTIL 200GR','ROL'],
                ['LISTON 3X3','UND'],
                ['LONA DE CARPA 3X3 AZUL','UND'],
                ['MALLA CON ALAMBRE EXAGONAL','MT'],
                ['MALLA GALVANIZADA 1/25" X0.90','ROL'],
                ['MALLA GANADERA','MT'],
                ['MALLA PLASTICA X4MTS','ROL'],
                ['MANGUERA HDPE 1" X100MT','ROL'],
                ['PANELES','UND'],
                ['PLATAFORMA DE BOMBA DE CONCRETO','UND'],
                ['PLATAFORMA DE PERFORACION','UND'],
                ['PLATAFORMA METALICA X1.60MT','UND'],
                ['POLYLOK X5MTS','TIR'],
                ['POLYLOK X6MTS','TIR'],
                ['POSTES TIPO Y','UND'],
                ['PUNTALES 2"X2MT','UND'],
                ['PUNTALES DE 6", 7" Y 8" X4MTS','UND'],
                ['PUNTALES DE 8"X4MTS','UND'],
                ['PUNTALES PINTADOS ESTANDARIZACION','UND'],
                ['PUNTALES RECUPERADOS X1MT (7", 8")','UND'],
                ['PUNTALES RECUPERADOS X2MT (7", 8")','UND'],
                ['PUNTALES RECUPERADOS X3 MTS (4", 6", 8")','UND'],
                ['REFLECTOR SOLAR','UND'],
                ['SPLITSET','UND'],
                ['TABLAS 2X8X1.5MTS','UND'],
                ['TABLAS 2X8X10','UND'],
                ['TABLAS 2X8X3 MTS','UND'],
                ['TABLERO ESTACIÓN DE EMERGENCIA','UND'],
                ['TABLERO PANEL INFORMATIVO','UND'],
                ['TANQUE DE PULMÓN DE AIRE','UND'],
                ['TANQUE METALICO PARA AGUA','UND'],
                ['TRIPLEY','PLN'],
                ['TUBERIA 4" HDPE X100MTS','ROL'],
            ]],
            [$getFamilia('MAT-SEG','Materiales de seguridad'), [
                ['ALCOHOL 1L','UND'],
                ['BALDE 20L','UND'],
                ['BALDE AZUL PARA LIMPIEZA','UND'],
                ['BALDE CON CAÑO','UND'],
                ['BALDE CON CAÑO 20L','UND'],
                ['BALDE CON TRAPEADOR AUTOMATICO','UND'],
                ['BALON DE OXIGENO 10M3 X1.40MTS','UND'],
                ['BANCA AZUL','UND'],
                ['BANCA NEGRA','UND'],
                ['BANDEJA METAL 60X40X10 ALTO','UND'],
                ['BANDERIN PARA TORMENTA AM/RJ/VR','JGO'],
                ['BANDERINES PARA TORMENTAS','JGO'],
                ['BANDERINES PARA TORMENTAS VR/AM/RJ','JGO'],
                ['BANNER PUNTO DE REUNION','UND'],
                ['BANNER RR.SS','UND'],
                ['BANNER USO OBLIGATORIO','UND'],
                ['BOLSA NEGRA','UND'],
                ['BOLSA NEGRA PESADA X100','PQTE'],
                ['BOLSA ROJA','UND'],
                ['BOTIQUIN 20X30','UND'],
                ['BOTIQUIN 30X40','UND'],
                ['BOTIQUIN IMPLEMENTADO','UND'],
                ['BOTIQUIN TIPO CAJA DE HERRAMIENTA','UND'],
                ['CAMILLA RIGIDA','UND'],
                ['CAMILLA RIGIDA CON CANASTILLA','JGO'],
                ['CANASTILLA','UND'],
                ['CINTA AISLANTE AMARILLO','UND'],
                ['CINTA AISLANTE AZUL','UND'],
                ['CINTA AISLANTE DORADA','UND'],
                ['CINTA COREANO PARA BANDERINES','ROL'],
                ['CINTA DE SEÑALIZACION AMARILLO','ROL'],
                ['CINTA DE SEÑALIZACION ROJO','ROL'],
                ['CINTA REFLECTIVA AZUL','UND'],
                ['CINTA REFLECTIVA BLANCO','UND'],
                ['CINTA REFLECTIVA PLOMO','ROL'],
                ['CINTA REFLECTIVA ROJO','ROL'],
                ['CINTA REFLECTIVA VERDE','UND'],
                ['CINTA REFLECTIVA VERDE LIMON','MT'],
                ['COLCHON','UND'],
                ['COLCHON HOMBRE ARAÑA','UND'],
                ['CORREA NEGRA PARA CAMILLA','UND'],
                ['CORREA SPIDER COLORES','UND'],
                ['CUCHARA METAL','UND'],
                ['CUCHARA Y TENEDOR DESCARTABLE','PQTE'],
                ['CUCHARON DE PLASTICO','UND'],
                ['DETERGENTE','KG'],
                ['ESCOBILLA PARA BAÑO','UND'],
                ['ESTABILIZADORES PARA MALETIN DE EMERGENCIA','UND'],
                ['ESTACION DE SALVATAJE IMPLEMENTADO','KIT'],
                ['EXTENSION NEGRO','UND'],
                ['EXTINTOR 6K','UND'],
                ['EXTINTOR 9K','UND'],
                ['FERULAS 20CM','UND'],
                ['FERULAS 30CM','UND'],
                ['FERULAS 40CM','UND'],
                ['FERULAS 50CM','UND'],
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
        $this->command->info("Parte 13 — Materiales + Mat.Seguridad 1-56: $total productos.");
    }
}
