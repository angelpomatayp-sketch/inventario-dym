<?php
// Equipos Electromecánicos (76) — CAP-0251 a CAP-0326
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inventario\Models\Familia;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Administracion\Models\Empresa;

class CatalogoParte04Seeder extends Seeder
{
    const START = 251;

    public function run(): void
    {
        $empresa = Empresa::first();
        if (!$empresa) return;
        $eid = $empresa->id;

        $familia = Familia::withoutGlobalScopes()->firstOrCreate(
            ['empresa_id' => $eid, 'codigo' => 'EQ-ELEC'],
            ['nombre' => 'Equipos Electromecánicos', 'es_epp' => false, 'activo' => true]
        );
        $fid = $familia->id;

        $productos = [
            ['AMOLADORA DEWALT 4"','UND'],
            ['AMOLADORA MAKITA','UND'],
            ['BATERIA RECORD PLUS 1670 AMP','UND'],
            ['BOMBA CONCRETERA SCHWING SP-1000','UND'],
            ['BOMBA DE AGUA 1HP MEBA','UND'],
            ['BOMBA DE AGUA 2HP MEBA','UND'],
            ['BOMBA ICTUS PARA CEMENTO','UND'],
            ['BOMBA MANUAL DE COMBUSTIBLE - TRASEGADOR','UND'],
            ['BOMBAS DE INYECCIÓN DE RESINA','UND'],
            ['CAMION GRUA ABX-728','UND'],
            ['CAMIONETA W6U-836','UND'],
            ['CAMIONETA W7B-874','UND'],
            ['CAMIONETA W7Q-884','UND'],
            ['CARMIX 5-5XL','UND'],
            ['COMPRENSORA DE AIRE SULLAIR N°1','UND'],
            ['COMPRENSORA DE AIRE SULLAIR N°2','UND'],
            ['CORTADORA DE MELTA BOSCH 355MM','UND'],
            ['DETECTOR DE ENERGIA','UND'],
            ['ENGRASADORA DE 2KG','UND'],
            ['ENGRASADORA DE 3.3KG','UND'],
            ['ESMERIL DEREK MOTOR 1/2 HP','UND'],
            ['ESMERIL DEWALT DE 1/2 HP 002169','UND'],
            ['GATO HIDRAULICO DE PATÓN TRUPER 2MT','UND'],
            ['GATO HIDRAULICO PROFESIONAL DE 12TN','UND'],
            ['GENERADOR ELECTRICO CAT N°1','UND'],
            ['GENERADOR ELECTRICO HYUNDAI DHY6000LE','UND'],
            ['GENERADOR ELECTRICO HYUNDAI HMY3000F','UND'],
            ['GENERADOR ELECTRICO N°2','UND'],
            ['HIDROLAVADORA','UND'],
            ['INYECTOR DE GRASA TRUPER','UND'],
            ['LANZADOR DE SHOKRETE - ALIVA','UND'],
            ['LUBRICADORA','UND'],
            ['MAQUINA DE SOLDAR','UND'],
            ['MAQUINA JACKLEG N°1','UND'],
            ['MAQUINA JACKLEG N°2','UND'],
            ['MAQUINA JACKLEG N°3','UND'],
            ['MAQUINA JACKLEG N°5','UND'],
            ['MAQUINA JACKLEG N°7','UND'],
            ['MAQUINA JACKLEG XVI','UND'],
            ['MARTILLO DE DEMOLICION MAKITA HM1306 15KG','UND'],
            ['MEZCLADORA DE CONCRETO DYNAMIC N°1','UND'],
            ['MEZCLADORA DE CONCRETO DYNAMIC N°2','UND'],
            ['MINIBUS CBY-049','UND'],
            ['MOTOBOMBA DE ALTA PRESIÓN POWER SPRAY','UND'],
            ['MOTOBOMBA HONDA 6X160','UND'],
            ['MOTOBOMBA TRUPER','UND'],
            ['MOTOBOMBA WB20','UND'],
            ['MOTOSIERRA ELECTRICA MAKUTE','UND'],
            ['MOTOSIERRA STHIL MS 250','UND'],
            ['MOTOSIERRA STHIL MS 361','UND'],
            ['PACK SACK','UND'],
            ['PERCUTOR NEUMATICO','UND'],
            ['PISTOLA DIESEL','UND'],
            ['PISTOLA PARA PINTAR','UND'],
            ['PULMON DE AIRE 678 TPA1000LV-2024','UND'],
            ['ROTOMARTILLO  GBH 4-32 DFR','UND'],
            ['ROTOMARTILLO BOSCH GBH-11 DE SDS MAX 1500W','UND'],
            ['ROTOMARTILLO BOSH GSH 11E','UND'],
            ['ROTOMARTILLO GBH 1252DW','UND'],
            ['SIERRA CIRCULAR DEWALT 7"X1/4','UND'],
            ['SIERRA CIRCULAR SKIL SAW 7"','UND'],
            ['SIERRA CIRCULAR TRUPER 7"','UND'],
            ['SURTIDOR DE COMBUSTIBLE','UND'],
            ['TABLERO ELECTRICO GRANDE','UND'],
            ['TABLERO ELECTRICO MEDIANO','UND'],
            ['TALADRO ELECTRICO SKILL','UND'],
            ['TALADRO PERCUTOR BOSCH','UND'],
            ['TANQUE ELECTRICO PARA COMBUSTIBLE','UND'],
            ['TECLE 1TN TRUPER','UND'],
            ['TELEMANDO DE BOMBA 1780611','UND'],
            ['TORRE LUMINARIA MAGNUN','UND'],
            ['TRABAPEDAL','UND'],
            ['VENTILADOR INDUSTRIAL ALTA PRESIÓN','UND'],
            ['VENTILADORA ELECTRICA DE 220V','UND'],
            ['VENTILADORA NEUMATICA','UND'],
            ['VIBRADOR ELECTRICO KAILI KL-726','UND'],
        ];

        foreach ($productos as $i => [$nombre, $unidad]) {
            $codigo = 'CAP-' . str_pad(self::START + $i, 4, '0', STR_PAD_LEFT);
            Producto::firstOrCreate(
                ['empresa_id' => $eid, 'nombre' => $nombre],
                ['familia_id' => $fid, 'codigo' => $codigo, 'unidad_medida' => $unidad, 'activo' => true, 'stock_minimo' => 0, 'stock_maximo' => 0]
            );
        }
        $this->command->info('Parte 04 — Equipos Electromecánicos: ' . count($productos) . ' productos.');
    }
}
