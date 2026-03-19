<?php
// Filtros y Aceites (103) — CAP-0954 a CAP-1056
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inventario\Models\Familia;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Administracion\Models\Empresa;

class CatalogoParte11Seeder extends Seeder
{
    const START = 954;

    public function run(): void
    {
        $empresa = Empresa::first();
        if (!$empresa) return;
        $eid = $empresa->id;

        $familia = Familia::withoutGlobalScopes()->firstOrCreate(
            ['empresa_id' => $eid, 'codigo' => 'FIL-ACE'],
            ['nombre' => 'Filtros y Aceites', 'es_epp' => false, 'activo' => true]
        );
        $fid = $familia->id;

        $productos = [
            ['ABRILLANTADOR DE LLANTAS 1L','LT'],
            ['ACEITE CAT PARA EQUIPOS  W1540 X3.75 L','GAL'],
            ['ACEITE DE COMPRENSORA SULLAIR','GAL'],
            ['ACEITE DE PERFORACIÓN','GAL'],
            ['ACEITE HIDRAULICO MOBIL NUTO H68','BLS'],
            ['ACEITE MOVIL 15W-40 X4L','GAL'],
            ['ACEITE PARA MOTOR CAT','GAL'],
            ['ACEITE PARA MOTOR CAT 15W-40','BLS'],
            ['ACEITE PARA TROMPO 10W30 X4L','GAL'],
            ['ACEITE TÓRCULA PARA PERFORACION X5GLN','BLS'],
            ['ADITIVO DESCARBONIZANTE LIQUIMOVIL X80ML','GAL'],
            ['AMORTIGUADOR UNICORN','UND'],
            ['ANILLO ADAPTADOR DE 4"','UND'],
            ['ANILLO DE CAUCHO','UND'],
            ['ATF-ACEITE PARA CARMIX X1L','GAL'],
            ['BANDEJA 1.20X60X10 CM ALTO','UND'],
            ['BANDEJA 1.20X90X15 CM ALTO','UND'],
            ['BANDEJA 60X60X10CM ALTO','UND'],
            ['BANDEJA 70X1MX15 CM ALTO','UND'],
            ['BOCINA DE JEBE','UND'],
            ['BOLA PARA LIMPIEZA DE TUBERIA EN BOMBA DE CONCRETO','UND'],
            ['BUJIA DE PRECALENTAMIENTO PM163','UND'],
            ['BUJIA DE PRECALENTAMIENTO PM73','UND'],
            ['BUJIA NGK','UND'],
            ['BUSHING','UND'],
            ['CABLE 250 AMP DE BATERIA','UND'],
            ['CABLE DE ARRANQU COMPLETO','UND'],
            ['CAJA DE HERRAMIENTADE ESCANER','JGO'],
            ['CAMARA PARA VOLQUETE','UND'],
            ['CIRCULINA NARANJA','UND'],
            ['CONTROL REMOTO PARA BOMBA CONCRETERA','UND'],
            ['CUBIERTA CARCASA DE PASADOR ALINEADOR','UND'],
            ['DADO 13/16 21MM','UND'],
            ['DADO 27','UND'],
            ['ENGRANAJE DE CAJA DE CAMBIO DE VEHÍCULOS','UND'],
            ['ESPARRAGO PARA CAMION MITSUBISHI','UND'],
            ['FILTRO CAT 7UV2327','UND'],
            ['FILTRO DE ACEITE','UND'],
            ['FILTRO DE ACEITE LF-1A 3/4"X16 LYS','UND'],
            ['FILTRO DE ACEITE LF604','UND'],
            ['FILTRO DE ACEITE LUBRICANTE LF3349','UND'],
            ['FILTRO DE AIRE DE MOTOR PARA TROMPO','UND'],
            ['FILTRO DE AIRE PARA COMPRENSORA SULLAIR','UND'],
            ['FILTRO DE AIRE PARA VOLQUETE HENGIST E500KP02','UND'],
            ['FILTRO DE AIRE PARA VOLQUETE PSS0453','UND'],
            ['FILTRO DE CAT GB ELEMENT-FUEL 276-1806','UND'],
            ['FILTRO DE COMBUSTIBLE PERKINS 4415125','UND'],
            ['FILTRO DE MOTOR TOYOTA','UND'],
            ['FILTRO DE PETROLEO CAT SEPARADOR 1R-1803','UND'],
            ['FILTRO DE PETROLEO CAT SEPARADOR 1R-1804','UND'],
            ['FILTRO DE PETROLEO MF-619','UND'],
            ['FILTRO E SUCCION DE PVC PARA MOTOBOMBA','UND'],
            ['FILTRO FUEL FILTER 25121074','UND'],
            ['FILTRO PARA COMPRENSORA LFP-829','UND'],
            ['FILTRO PRIMARIO CARMIX','UND'],
            ['FILTRO SECUNDARIO CARMIX','UND'],
            ['FILTRO SEPARADOR DE AGUA','UND'],
            ['FOCO 2 FILAMENTOS','UND'],
            ['FOCO H3 NARVA','UND'],
            ['FOCO H424VLS','UND'],
            ['FOCOS HALOGENOS H3 12V','PAR'],
            ['GALONERA DE 5 GLN','UND'],
            ['GANCHO DE ELEVACION','UND'],
            ['GASOLINA','GAL'],
            ['GOMA DE ESTABILIZADOR 30814','UND'],
            ['GRASA LITHIUM VISTONY EP2','BLS'],
            ['HIDROLINA','GAL'],
            ['KIT CARBURADOR TOYOTA','JGO'],
            ['LAMPARA NEBLINERO FOG LAMP YELLOW','PAR'],
            ['LIMPIA PARABRISAS VISTONY X3.78L','GAL'],
            ['LIMPIADOR DE FRENOS','UND'],
            ['LUBRICANTE MOBIL 80W-90','BLS'],
            ['MAIN ELEMENT 123-2367','UND'],
            ['MANOMETRO 20 BAR','UND'],
            ['MANOMETRO 200 BAR','UND'],
            ['MANOMETRO 700 BAR','UND'],
            ['MANOMETRO LX1-3520','UND'],
            ['PARCHE PARA LLANTA DE CARRETILLA','UND'],
            ['PASTILLA DE FRENO DE DISCO','UND'],
            ['PASTILLA POSTERIOR MINIBUSS','UND'],
            ['PERNO EXAGONAL 4"','UND'],
            ['PIPA PARA GRASERA','UND'],
            ['PLUMILLA 22" TITAN','UND'],
            ['PONCHOS DE VOLQUETE','UND'],
            ['PROTECTOR DE PARABRISA','UND'],
            ['REFRIGERANTE CAT PARA COMPRENSORA Y GENERADOR','GAL'],
            ['REFRIGERANTE CF COOLANT X3.75','GAL'],
            ['REFRIGERANTE TRUCKSTAR','BLS'],
            ['RESORTE DE RETORNO DE PERNO','UND'],
            ['RESTAURADOR DE PLASTICO X500ML','UND'],
            ['RODAMIENTO KOYO HM801346110','UND'],
            ['SEPARADOR DE ACEITE SULLAIR 022501153-904','UND'],
            ['SEPARADOR DE AGUA Y COMBUSTIBLE F519608','UND'],
            ['SEPARADOR DE COMBUSTIBLE WK1040','UND'],
            ['SILICONA PARA TABLERO','GAL'],
            ['SILICONA PARA TABLERO X500ML','UND'],
            ['SPRAY GRASA MULTIPROPOSITO VISTONY','UND'],
            ['SPRAY LIMPIADOR DE FRENOS','UND'],
            ['TINA DE LATA','UND'],
            ['TORQUIMETRO TRUPER','UND'],
            ['TRIANGULO DE SEGURIDAD','UND'],
            ['VÁLVULA DE SEGURIDAD DE AIRE','UND'],
            ['ZAPATA PRIMAVERA PARA MINIBUS','UND'],
        ];

        foreach ($productos as $i => [$nombre, $unidad]) {
            $codigo = 'CAP-' . str_pad(self::START + $i, 4, '0', STR_PAD_LEFT);
            Producto::firstOrCreate(
                ['empresa_id' => $eid, 'codigo' => $codigo],
                ['nombre' => $nombre, 'familia_id' => $fid, 'unidad_medida' => $unidad, 'activo' => true, 'stock_minimo' => 0, 'stock_maximo' => 0]
            );
        }
        $this->command->info('Parte 11 — Filtros y Aceites: ' . count($productos) . ' productos.');
    }
}
