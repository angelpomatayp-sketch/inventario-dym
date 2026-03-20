<?php
// Ferretería 101-200 — CAP-0427 a CAP-0526
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inventario\Models\Familia;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Administracion\Models\Empresa;

class CatalogoParte06Seeder extends Seeder
{
    const START = 428;

    public function run(): void
    {
        $empresa = Empresa::first();
        if (!$empresa) return;
        $eid = $empresa->id;

        $fid = Familia::withoutGlobalScopes()->where('empresa_id', $eid)->where('codigo', 'FERR')->value('id');

        $productos = [
            ['CABLE DE ALTA PRESIÓN','ROL'],
            ['CABLE DE ALUMINIO','MT'],
            ['CABLE DE INTERNET','MT'],
            ['CABLE DE LUZ','UND'],
            ['CABLE DE NUCLEO SOLIDO','MT'],
            ['CABLE DE REMOLQUE 1','UND'],
            ['CABLE DE VENTILADOR ELECTRICO X15MT','ROL'],
            ['CABLE MELLIZO','ROL'],
            ['CABLE PARA POZO A TIERRA','ROL'],
            ['CABLE TRENZADO DE COBRE X26CM','UND'],
            ['CADENA','MT'],
            ['CADENA PARA INSTALACIÓN DE TUBERÍA','UND'],
            ['CADENA STHILL DE 18"','UND'],
            ['CADENA X5MTS GRUA','UND'],
            ['CAJA DE CUCHILLA ELECTRICA NARANJA','UND'],
            ['CAJA DE HERRAMIENTA DE PLASTICO','JGO'],
            ['CAJA DE HERRAMIENTA NEGRO','KIT'],
            ['CAJA DE REGISTRO DE POLIPROPILENO','UND'],
            ['CAJA METALICA CON PERNOS USADOS','UND'],
            ['CAJA PARA INTERRUPTOR','UND'],
            ['CALAMINA','UND'],
            ['CALAMINA DE FIBRA DE VIDRIO','UND'],
            ['CALIBRADOR VERNIER 6" PRETUL','UND'],
            ['CAMARA DE LLANTA','UND'],
            ['CAMARA PARA LLANTA DE CARRETILLA','UND'],
            ['CANCAMO 1.50MTS X3/4" FIERRO CORRUGADO','UND'],
            ['CANCAMOS 1/4"','UND'],
            ['CANCAMOS 3/4 X1.10M','UND'],
            ['CANCAMOS 5/8 X 90CM','UND'],
            ['CANDADO DE SEGURIDAD LOCKOUT','UND'],
            ['CANDADO GRANDE','UND'],
            ['CANDADO MEDIANO','UND'],
            ['CANDADO PEQUEÑO','UND'],
            ['CANTONERA','UND'],
            ['CAPUCHON DE PVC PARA TECHO','UND'],
            ['CAPUCHONES DE SEGURIDAD 1/4" - 3/8"','UND'],
            ['CARBON PARA ROTOMARTILLO BOSH','PAR'],
            ['CARRETILLA TRUPER','UND'],
            ['CASQUILLOS DE VALVULAS','UND'],
            ['CATALIZADOR DE RESINA','UND'],
            ['CAÑO','UND'],
            ['CEPILLO METALICO TRUPER','UND'],
            ['CERRADURA DE SOBREPONER','UND'],
            ['CHAVETA','UND'],
            ['CHAVETAS PARA PICO','UND'],
            ['CINCEL PLANO SDX MAX','UND'],
            ['CINCEL PUNTA','UND'],
            ['CINTA ADHESIVA PARA DRYWALL','UND'],
            ['CINTA AISLANTE AM','UND'],
            ['CINTA AISLANTE AZ','UND'],
            ['CINTA AISLANTE BL','UND'],
            ['CINTA AISLANTE BLANCO','UND'],
            ['CINTA AISLANTE NG','UND'],
            ['CINTA AISLANTE RJ','UND'],
            ['CINTA DE SEÑALIZACIÓN AMARILLO','UND'],
            ['CINTA DE SEÑALIZACIÓN ROJO','UND'],
            ['CINTA SEÑALIZACIÓN AMARILLA','UND'],
            ['CINTA SEÑALIZACIÓN ROJA','UND'],
            ['CINTA TRENZADA DE COBRE','UND'],
            ['CINTILLO 30CM','UND'],
            ['CINTILLO 50CM NEGRO','UND'],
            ['CINTILLO CH','UND'],
            ['CINTILLO GR','UND'],
            ['CIZALLA 12"','UND'],
            ['CIZALLA 18"','UND'],
            ['CIZALLA 24"','UND'],
            ['CLAVO 1"','KG'],
            ['CLAVO 1/2"','KG'],
            ['CLAVO 2"','KG'],
            ['CLAVO 3"','KG'],
            ['CLAVO 4"','KG'],
            ['CLAVO 5"','KG'],
            ['CLAVO 6"','KG'],
            ['CLAVO DE CEMENTO 2"','UND'],
            ['CLAVO PARA CALAMINA','KG'],
            ['COLA SINTETICA X1K','BLS'],
            ['COLGADOR PARA EXTINTOR','UND'],
            ['COMBA 10 LB','UND'],
            ['COMBA 10 LBS','UND'],
            ['COMBA 10LB','UND'],
            ['COMBA 12 LB','UND'],
            ['COMBA 12 LBS','UND'],
            ['COMBA 4 LB','UND'],
            ['COMBA 4 LBS','UND'],
            ['COMBA 6 LB','UND'],
            ['COMBA 6 LBS','UND'],
            ['COMBA 8 LB','UND'],
            ['COMBA 8 LBS','UND'],
            ['COMBA DE GOMA','UND'],
            ['COMPUERTA','UND'],
            ['COMPUERTA PARA LAVADO DE BOMBA 50CM','UND'],
            ['CONO ABRAHAMS','UND'],
            ['CONO DE 25CM','UND'],
            ['CONO DE MADERA','UND'],
            ['CONO DE SEGURIDAD','UND'],
            ['CORDEL DE NYLON','UND'],
            ['CORDON DE ALGODÓN','ROL'],
            ['CORTINA DE MINIBUS','UND'],
            ['CORVINA','UND'],
        ];

        foreach ($productos as $i => [$nombre, $unidad]) {
            $codigo = 'CAP-' . str_pad(self::START + $i, 4, '0', STR_PAD_LEFT);
            Producto::withoutGlobalScopes()->firstOrCreate(
                ['empresa_id' => $eid, 'codigo' => $codigo],
                ['nombre' => $nombre, 'familia_id' => $fid, 'unidad_medida' => $unidad, 'activo' => true, 'stock_minimo' => 0, 'stock_maximo' => 0]
            );
        }
        $this->command->info('Parte 06 — Ferretería 101-200: ' . count($productos) . ' productos.');
    }
}
