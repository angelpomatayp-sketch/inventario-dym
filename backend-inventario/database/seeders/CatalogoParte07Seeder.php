<?php
// Ferretería 201-300 — CAP-0527 a CAP-0626
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inventario\Models\Familia;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Administracion\Models\Empresa;

class CatalogoParte07Seeder extends Seeder
{
    const START = 528;

    public function run(): void
    {
        $empresa = Empresa::first();
        if (!$empresa) return;
        $eid = $empresa->id;

        $fid = Familia::withoutGlobalScopes()->where('empresa_id', $eid)->where('codigo', 'FERR')->value('id');

        $productos = [
            ['COSTAL BLANCO','UND'],
            ['COSTAL METALERO AMARILLO','UND'],
            ['COUPLING BORT LONGYEAR','UND'],
            ['CUCHARON','UND'],
            ['CUCHARON DE ALUMINIO','UND'],
            ['CUCHILLA C32','UND'],
            ['DESINFECTANTE EXQUAT','BT'],
            ['DESTORNILLADOR','UND'],
            ['DESTORNILLADOR ESTRELLA','UND'],
            ['DESTORNILLADOR PLANO','UND'],
            ['DESTORNILLADOR TIPO PATA DE CABRA','UND'],
            ['DESTORNILLADOR UYUSTOOL X6','JGO'],
            ['DIFERENCIAL MAGNETICO C100A TRIFASICO','UND'],
            ['DIFERENCIAL MAGNETICO C16A 2 POLOS','UND'],
            ['DIFERENCIAL MAGNETICO C20A 2 POLOS','UND'],
            ['DISCO DE CORTE 7"','UND'],
            ['DISCO DE CORTE DE FIERRO','UND'],
            ['DISCO DE DESCASTE','UND'],
            ['DISCO DE ESMERIL','UND'],
            ['DISCO DE MADER 4/2" UYUSTOOL','UND'],
            ['DISCO DE MADER 7/4" UYUSTOOL','UND'],
            ['DISCO DE SIERRA','UND'],
            ['DISCO DIAMANTADA','UND'],
            ['DISCO PARA BARRENO DE PERFORACIÓN','UND'],
            ['DISCO PARA BARRETILLA','UND'],
            ['DUCHA','UND'],
            ['ECUADRA ZINCADO DE 4 HUECOS','UND'],
            ['EMBUDO PARA GASOLINA GR','UND'],
            ['EMPAQUE DEL CUERPO 164631A','UND'],
            ['EMPAQUE PARA TUBERIA','UND'],
            ['ENCHUFE','UND'],
            ['ENCHUFE LEVITON','UND'],
            ['ENCHUFE MUTIPLE PLANO','UND'],
            ['ENDIRECEDOR DE MASILLA','UND'],
            ['ENVASE DE PVC PARA MUESTRA DE H2O','UND'],
            ['ENVASE DE VIDRIO PARA MUESTRA DE H2O','UND'],
            ['ESCALERA 7 PELDAÑOS X2.5MT','UND'],
            ['ESCALERA DE 10 PELDAÑOS X3MTS','UND'],
            ['ESCALERA DE 6 PELDAÑOS TRUPER X2MT','UND'],
            ['ESCALERA DE MADERA 8 PELDAÑOS X3MT','UND'],
            ['ESCOBILLA DE ACERO','UND'],
            ['ESCOFINA PARA MADERA','UND'],
            ['ESCUADRA 25 CM','UND'],
            ['ESCUADRA 55CM','UND'],
            ['ESCUADRA 60CM','UND'],
            ['ESCUADRA TRUPER 30CM','UND'],
            ['ESLINGA 8MT X 11TN','UND'],
            ['ESLINGA 8MT X 8TN','UND'],
            ['ESMALTE SUPERIOR','GAL'],
            ['ESMERIL 8" TRUPER','UND'],
            ['ESMERIL 8"X3/4"X5/8" KAMASA','UND'],
            ['ESPATULA 2"','UND'],
            ['ESPATULA 3"','UND'],
            ['ESPEJOS DE CAMION','UND'],
            ['ESQUINEROS PARA CARPA','UND'],
            ['ESTABILIZADOR DE CARRETILLA','PAR'],
            ['ESTUCHE ORINGS','UND'],
            ['EXTENSION PARA LLAVES1','UND'],
            ['EXTINTOR DE 9K','UND'],
            ['FAJA A-72 PARA TROMPO','UND'],
            ['FENOLICO','UND'],
            ['FENOLICO A.40X2.50MTS','UND'],
            ['FIERRO PARA DOBLAR','UND'],
            ['FLEXOMETO 5MTS','UND'],
            ['FLEXOMETRO 5M','UND'],
            ['FLEXOMETRO 5M ACEROS AREQUIPA','UND'],
            ['FLEXOMETRO 5M TRUPER','UND'],
            ['FLEXOMETRO 5MTS TRUPER','UND'],
            ['FLEXOMETRO 8MTS TRUPER','UND'],
            ['FOCO 100W','UND'],
            ['FOCO PARA REFLECTOR LUMINARIA 1000 W','UND'],
            ['FORMON 4"','UND'],
            ['FORMON AZUL CH','UND'],
            ['FORMÓN TRIPER','UND'],
            ['FROTACHIO DE ESPONJA 4"','UND'],
            ['FROTACHO DE ESPONJA','UND'],
            ['FROTACHO DE MADERA','UND'],
            ['FROTACHO DE MADERA 6X25','UND'],
            ['FROTACHO DE MADERA 6X30','UND'],
            ['FROTACHO PLANCHA PVC 20X30','UND'],
            ['FROTACHO PVC 6X30CM','UND'],
            ['FUENTE DE ALUMINIO','UND'],
            ['GALONERA 10GLN','UND'],
            ['GALONERA 5 GLN','UND'],
            ['GANCHO DE 1MT','UND'],
            ['GANCHO DE TABLERO DE MADERA','UND'],
            ['GANCHO J','UND'],
            ['GANCHO PARA JALAR MADERA','UND'],
            ['GANCHO S','UND'],
            ['GANCHO U FIERRO CORRUGADI','UND'],
            ['GEOMEMBRANA 1" HDPE','M2'],
            ['GRAMPA PARA PERCUTOR NEUMATICO','UND'],
            ['GRAPAS PARA MALLA GANADERA','KG'],
            ['GRASA LOTHIUM MULTIPROPOSITO TAPER','UND'],
            ['GRIFA PARA DOBLAR FIERRO','UND'],
            ['GRILLETE 12TN','UND'],
            ['GRILLETE 6.5TN','UND'],
            ['HACHA 3 1/2 LBS TITAN','UND'],
            ['HACHA 4 1/2 LBS HERRAGRO','UND'],
        ];

        foreach ($productos as $i => [$nombre, $unidad]) {
            $codigo = 'CAP-' . str_pad(self::START + $i, 4, '0', STR_PAD_LEFT);
            Producto::withoutGlobalScopes()->firstOrCreate(
                ['empresa_id' => $eid, 'codigo' => $codigo],
                ['nombre' => $nombre, 'familia_id' => $fid, 'unidad_medida' => $unidad, 'activo' => true, 'stock_minimo' => 0, 'stock_maximo' => 0]
            );
        }
        $this->command->info('Parte 07 — Ferretería 201-300: ' . count($productos) . ' productos.');
    }
}
