<?php
// Ferretería 501-622 — CAP-0827 a CAP-0948
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inventario\Models\Familia;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Administracion\Models\Empresa;

class CatalogoParte10Seeder extends Seeder
{
    const START = 828;

    public function run(): void
    {
        $empresa = Empresa::first();
        if (!$empresa) return;
        $eid = $empresa->id;

        $fid = Familia::withoutGlobalScopes()->where('empresa_id', $eid)->where('codigo', 'FERR')->value('id');

        $productos = [
            ['PUNZON DE MANGO','UND'],
            ['RAFIA','ROL'],
            ['RASTRILLO','UND'],
            ['RASTRILLO MANGO METALICO','UND'],
            ['RASTRILLO TRUPER','UND'],
            ['RECOGEDOR','UND'],
            ['REDUCCIÓN BUSHING DE 1/2"A 3/4"','UND'],
            ['REDUCCIÓN PVC 3/4" A 1"','UND'],
            ['REFLECTOR 100W','UND'],
            ['REFLECTOR 200W','UND'],
            ['REFLECTOR 50W','UND'],
            ['REFLECTOR 50WR','UND'],
            ['REFLECTOR PARA LUMINARIA','UND'],
            ['REFLECTOR SOLAR NEW VITALY','UND'],
            ['REGLA DE ALUMINIO 1.5M','UND'],
            ['REGLA DE ALUMINIO 1M','UND'],
            ['REGLA DE ALUMINIO 2.5M','UND'],
            ['REGLA DE ALUMINIO 2MTS','UND'],
            ['REGLA METALICA 1.20MTS','UND'],
            ['REGLA METALICA 21/2 MTS','UND'],
            ['REGLA METALICA 2MTS','UND'],
            ['REGLA METALICA 3MTS','UND'],
            ['REGLA METALICA 4MTS','UND'],
            ['REGLA METALICA 60CM','UND'],
            ['REMACHADOR STANLEY','UND'],
            ['REMACHE','UND'],
            ['REPUESTOS DE CARRETILLA COMPLETA','CJA'],
            ['RESISTENCIA ELECTRICA','UND'],
            ['RESORTE PARA PERCUTOR NEUMATICO','UND'],
            ['RODILLO PARA PINTAR','UND'],
            ['ROLLO DE COSTAL 2MTS','MT'],
            ['ROLLO NYLON','ROL'],
            ['ROSCA DE JEBE','UND'],
            ['ROTOPLAST PQEUEÑO','UND'],
            ['SACA BARRENO','UND'],
            ['SACA BROCA','UND'],
            ['SACA BUJIA','UND'],
            ['SACABROCA CON MANGO','UND'],
            ['SACO 80K NEGRO','UND'],
            ['SACO BLANCO 60K','UND'],
            ['SACO DE ARROZ','UND'],
            ['SELECTOR DE ENERGIA','UND'],
            ['SERRUCHO','UND'],
            ['SERRUCHO 20"','UND'],
            ['SERRUCHO PICO DE LORO','UND'],
            ['SIERRA','UND'],
            ['SIERRA COPA UYUSTOOL','KIT'],
            ['SIERRA DE ARCO TIPO TUBULAR','UND'],
            ['SILICONA WHITE PARA TABLERO','UND'],
            ['SOCKET DE ACERO','UND'],
            ['SOCKET EPEM','UND'],
            ['SOGA 1/2"','ROL'],
            ['SOGA 1/4','MT'],
            ['SOGA 1/8','MT'],
            ['SOGA 3/4','MT'],
            ['SOGA 3/4"','ROL'],
            ['SOPLETE MANUAL','UND'],
            ['SOPORTE CON TOPE DE GOMA','UND'],
            ['SOPORTE DE ACERO DE 40CM','UND'],
            ['SOPORTE DE CARRETILLA','PAR'],
            ['SPRAY BLANCO','UND'],
            ['SPRAY RENOVADOR DE MADERA','UND'],
            ['SPRAY SAPOLIO MATATODO','UND'],
            ['TACHUELAS','CJA'],
            ['TACO PARA CARRO AMARILLO','UND'],
            ['TACO PARA VOLQUETE AMARILLO','UND'],
            ['TACOS PARA CAMIONETA NARANJA','UND'],
            ['TAMPON HEMBRA PVC','UND'],
            ['TARJETA DE SEGURIDAD LOCKOUT','UND'],
            ['TECLE DE CADENA DE 2TN','UND'],
            ['TECLE TRUPER 5TN','UND'],
            ['TEE GALVANIZADA 1/2"','UND'],
            ['TEFLON','UND'],
            ['TEFLÓN','UND'],
            ['TEMPLADOR DE ACOMETIDA','UND'],
            ['TEMPLADOR DE CABLE','UND'],
            ['TEROCAL AFRICANO PEQUEÑO','UND'],
            ['THINNER ACRILICO','GAL'],
            ['TIJERA DE PODAR','UND'],
            ['TIJERA PARA PODAR','UND'],
            ['TILFOR','UND'],
            ['TIRAFON CON CAPUCHON','KG'],
            ['TIRALINEA','UND'],
            ['TIRALINEA 30CM','UND'],
            ['TIRANTE DE CARRETILLA','UND'],
            ['TIRANTES PARA CARRETILLA','PAR'],
            ['TOMA CORRIENTE AÉREO','UND'],
            ['TOMACORRIENTE','UND'],
            ['TORNILLO DE PURGA','UND'],
            ['TORNILLOS Y TUERCAS','KG'],
            ['TORNILLOS Y TUERCAS PARA CARRETILLA','KG'],
            ['TORQUIMETRO 150LB TRUPER','UND'],
            ['TORQUIMETRO 2TN TRUPER','UND'],
            ['TORTOL','UND'],
            ['TRABA TUERCA','UND'],
            ['TRAMPA 4"','UND'],
            ['TRAMPA PARA FIERRO','UND'],
            ['TRENZA DE COBRE','UND'],
            ['TRINQUETE CREMALLERA','UND'],
            ['TRIPLEY 3MM','UND'],
            ['TRIPLEY MULTILAMINADO 15MM','PLN'],
            ['TUBO DE AGUA','UND'],
            ['TUBO DE ESPUMA DE POLIMENO 1 1/16"','MT'],
            ['TUERCA ADAPTADOR RND2440','UND'],
            ['TUERCA CHUEK RNC-1512','UND'],
            ['TUERCA DE ALA ENCOFRADA','UND'],
            ['TUERCA RIFLE RNC-1508','UND'],
            ['UNION UNIVERSAL GALVANIZADO DE 4"','UND'],
            ['UNIÓN GALVANIZADA 1 1/2"','UND'],
            ['UNIÓN HDPE EN ELECTROFUCIÓN DE 4"','UND'],
            ['UNIÓN MIXTA PARA AGUA 1/2"','UND'],
            ['UNIÓN PVC 2"','UND'],
            ['VALVULA CHEK 1/2"','UND'],
            ['VALVULA DE BOLA DE 1" DE ACERO','UND'],
            ['VALVULA DE BOLA DE 1/2" DE ACERO','UND'],
            ['VALVULA DE BOLA DE 3/4" DE ACERO','UND'],
            ['VALVULA DE PASO 4"','UND'],
            ['VALVULA DE PASO PVC','UND'],
            ['VALVULA PVC 1/2"','UND'],
            ['VARILLA DE COBRE','UND'],
            ['VARILLA DE COBRE 2.20MT','UND'],
            ['WHIPCHEEK CABLE DE SEGURIDAD','UND'],
            ['WINCHA DE LONA 100MTS','UND'],
            ['WINCHA DE LONA 50MT','UND'],
            ['WINCHA LONA UYUSTOOL DE 50MT','UND'],
            ['WINCHA LONA X100MTS','UND'],
        ];

        foreach ($productos as $i => [$nombre, $unidad]) {
            $codigo = 'CAP-' . str_pad(self::START + $i, 4, '0', STR_PAD_LEFT);
            Producto::firstOrCreate(
                ['empresa_id' => $eid, 'nombre' => $nombre],
                ['familia_id' => $fid, 'codigo' => $codigo, 'unidad_medida' => $unidad, 'activo' => true, 'stock_minimo' => 0, 'stock_maximo' => 0]
            );
        }
        $this->command->info('Parte 10 — Ferretería 501-622: ' . count($productos) . ' productos.');
    }
}
