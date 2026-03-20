<?php
// Ferretería 301-400 — CAP-0627 a CAP-0726
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inventario\Models\Familia;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Administracion\Models\Empresa;

class CatalogoParte08Seeder extends Seeder
{
    const START = 628;

    public function run(): void
    {
        $empresa = Empresa::first();
        if (!$empresa) return;
        $eid = $empresa->id;

        $fid = Familia::withoutGlobalScopes()->where('empresa_id', $eid)->where('codigo', 'FERR')->value('id');

        $productos = [
            ['HEBILLA DE ACERO','UND'],
            ['HERVIDORA PARA CONCRETO','UND'],
            ['HEXAGONAL','UND'],
            ['HIERRO DE MARCA NUMEROS Y LETRAS','UND'],
            ['HILO PESCAR 0.60','ROL'],
            ['HOROMETRO','UND'],
            ['INFLADOR DE LLANTA','UND'],
            ['INFLADOR PARA LLANTA BUGGY','UND'],
            ['INTERRUPTOR','UND'],
            ['INTERRUPTOR DIFERENCIAL DE 25 A 2 POLOS','UND'],
            ['INTERRUPTOR DIFERENCIAL DE 40 A 2 POLOS','UND'],
            ['INYECTOR DE GRASA','UND'],
            ['J DE FIERRO CORRUGADO PARA TRASLADO DE MADERA','UND'],
            ['JUEGO DE DADOS','JGO'],
            ['JUEGO DE DESARMADOR','JGO'],
            ['JUEGO DE LLAVE MIXTA','JGO'],
            ['JUEGO DE LLAVE MIXTA FERRAWYY','JGO'],
            ['KIT HERRAMIENTA MANUAL','KIT'],
            ['KIT PORTA TALADRO Y BROCAS','UND'],
            ['LAMPA PARA ESTACION DE EMERGENCIA ROJO','UND'],
            ['LAMPA TRAMONTINA','UND'],
            ['LAMPA TRUPER','UND'],
            ['LAMPAS','UND'],
            ['LARGUEROS PARA CARRETILLA','UND'],
            ['LIJA 220','UND'],
            ['LIMA BELLOTA','UND'],
            ['LIMA CIRCILAR','UND'],
            ['LIMA ESCOFINA','UND'],
            ['LIMA TRIANGULAR','UND'],
            ['LIMPIATODO X3L','UND'],
            ['LINTERNA DE MANO','UND'],
            ['LINTERNA DE MANO AUTORECARGABLE','UND'],
            ['LLANTA CON ARO DE CARRETILLA','UND'],
            ['LLANTA CON ARO PARA CARRETILLA','UND'],
            ['LLANTA DE TACHOS','UND'],
            ['LLANTA PARA CARRETILLA CON EJE TRUPER','UND'],
            ['LLANTAS COMPLETAS','UND'],
            ['LLANTAS PARA MINIBUS','UND'],
            ['LLAVE  RIG DIG 12"','UND'],
            ['LLAVE DE TRINQUETE 10"','UND'],
            ['LLAVE EXAGONAL TRUPER X7','JGO'],
            ['LLAVE FIJA 14/17','UND'],
            ['LLAVE FRANCESA','UND'],
            ['LLAVE FRANCESA 12"','UND'],
            ['LLAVE FRANCESA 14"','UND'],
            ['LLAVE FRANCESA 15"','UND'],
            ['LLAVE FRANCESA 24"','UND'],
            ['LLAVE FRANCESA 8"','UND'],
            ['LLAVE MATRACA','UND'],
            ['LLAVE MIXTA 14"','UND'],
            ['LLAVE MIXTA 17"','UND'],
            ['LLAVE MIXTA 24"','UND'],
            ['LLAVE MIXTA N°10','UND'],
            ['LLAVE MIXTA N°13','UND'],
            ['LLAVE MIXTA N°8','UND'],
            ['LLAVE MIXTA X12','JGO'],
            ['LLAVE PARA AMOLADORA','UND'],
            ['LLAVE RIG DIG 18"','UND'],
            ['LLAVE STILSON','UND'],
            ['LLAVE STILSON 12"','UND'],
            ['LLAVE STILSON 12" UYUSTOOL','UND'],
            ['LLAVE STILSON 18"','UND'],
            ['LLAVE STILSON 24"','UND'],
            ['LLAVE TERMOMAGNETICA','UND'],
            ['LLAVE TIPO S 15/12','UND'],
            ['LLAVE TIPO T 10"','UND'],
            ['LLAVE TRAMONTINA','JGO'],
            ['LONA DE CARPA 3X3','UND'],
            ['LUBRICADOR DE PACK SACK','UND'],
            ['MACHETE','UND'],
            ['MACHETE 14"','UND'],
            ['MALLA DE SEGURIDAD','UND'],
            ['MALLA DE SEGURIDAD ANARANJADA','ROL'],
            ['MALLA GANADERA  0.90X1.20','ROL'],
            ['MALLA PLASTICO NEGRO X5MTS','ROL'],
            ['MANGA DE VENTILACIÓN 18"X25M','UND'],
            ['MANGA DE VENTILACIÓN 24"X25M','UND'],
            ['MANGO DE PICO','UND'],
            ['MANGOS DE PICO PEQUEÑO','UND'],
            ['MANGUERA 1" HDPE','MT'],
            ['MANGUERA 1/2" HDPE','MT'],
            ['MANGUERA DE ALTA PRESION AMARILLO','MT'],
            ['MANGUERA DE ALTA PRESION HIDRAULICA 1/2','UND'],
            ['MANGUERA DE ALTA PRESION NEGRO','MT'],
            ['MANGUERA DE ALTA PRESIÓN 1/2"','MT'],
            ['MANGUERA DE ALTA PRESIÓN 1/4"','MT'],
            ['MANGUERA DE EXTINTOR','UND'],
            ['MANGUERA DE GAS PARA FUMIGAR','UND'],
            ['MANGUERA DE JEBE Y LONA 1"','MT'],
            ['MANGUERA DE JEBE Y LONA 1" X50MTS','ROL'],
            ['MANGUERA DE JEBE Y LONA 1/2"','MT'],
            ['MANGUERA DE JEBE Y LONA 1/2" X175MTS','ROL'],
            ['MANGUERA DE JEBE Y LONA 3/4"','MT'],
            ['MANGUERA DE LATA PRESIÓN HIDRAULICO X10MTS','ROL'],
            ['MANGUERA DE RIEGO 1"','MT'],
            ['MANGUERA DE RIEGO DE 1/2" X100MTS','ROL'],
            ['MANGUERA HDPE X30MTS','ROL'],
            ['MANGUERA PARA BAÑO','UND'],
        ];

        foreach ($productos as $i => [$nombre, $unidad]) {
            $codigo = 'CAP-' . str_pad(self::START + $i, 4, '0', STR_PAD_LEFT);
            Producto::withoutGlobalScopes()->firstOrCreate(
                ['empresa_id' => $eid, 'codigo' => $codigo],
                ['nombre' => $nombre, 'familia_id' => $fid, 'unidad_medida' => $unidad, 'activo' => true, 'stock_minimo' => 0, 'stock_maximo' => 0]
            );
        }
        $this->command->info('Parte 08 — Ferretería 301-400: ' . count($productos) . ' productos.');
    }
}
