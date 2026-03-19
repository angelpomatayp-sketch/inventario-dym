<?php
// EPP-CAB (30) + EPP-OJO (15) + EPP-AUD (9) — CAP-0093 a CAP-0146
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inventario\Models\Familia;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Administracion\Models\Empresa;

class CatalogoParte02Seeder extends Seeder
{
    const START = 93;

    public function run(): void
    {
        $empresa = Empresa::first();
        if (!$empresa) return;
        $eid = $empresa->id;

        $getFamilia = fn($codigo) => Familia::withoutGlobalScopes()
            ->where('empresa_id', $eid)->where('codigo', $codigo)->value('id');

        $grupos = [
            [$getFamilia('EPP-CAB'), [
                ['BARBIQUEJO CLUTE','UND'],
                ['BARBIQUEJO NACIONAL','PAR'],
                ['CARGADOR DE LAMPARA GLORIA','UND'],
                ['CARGADOR DE LAMPARA TOMBALED','UND'],
                ['CARGADOR PARA LAMPARA MINERA GLORIA','UND'],
                ['CASCO TIPO JOCKEY AZUL','UND'],
                ['CASCO TIPO JOCKEY AZUL USADO','UND'],
                ['CASCO TIPO JOCKEY BLANCO','UND'],
                ['CASCO TIPO JOCKEY BLANCO USADO','UND'],
                ['CASCO TIPO JOCKEY MSA AZUL','UND'],
                ['CASCO TIPO JOCKEY MSA BLANCO','UND'],
                ['CASCO TIPO SOMBRERO AZUL','UND'],
                ['CASCO TIPO SOMBRERO AZUL USADO','UND'],
                ['CASCO TIPO SOMBRERO BLANCO USADO','UND'],
                ['CASCO TIPO SOMBRERO MSA AZUL','UND'],
                ['CASCO TIPO SOMBRERO MSA BLANCO','UND'],
                ['CORREA PORTA LAMPARA','UND'],
                ['LAMPARA GLORIA','UND'],
                ['LAMPARA GLORIA CON CARGADOR','UND'],
                ['LAMPARA MINERA GLORIA','UND'],
                ['LAMPARA SAFETY NARANJA CON CARGADOR','UND'],
                ['LAMPARA SAFETY S/CARGADOR','UND'],
                ['LAMPARA TOMBALED CON CARGADOR','UND'],
                ['PROTECTOR TIPO JOCKEY AZUL','UND'],
                ['PROTECTOR TIPO JOCKEY BLANCO','UND'],
                ['PROTECTOR TIPO JOCKEY VERDE','UND'],
                ['PROTECTOR TIPO SOMBRERO BLANCO','UND'],
                ['PROTECTOR TIPO SOMBRERO VERDE','UND'],
                ['TAFILETE MSA','UND'],
                ['TAFILETE SAFETY','UND'],
            ]],
            [$getFamilia('EPP-OJO'), [
                ['CARETA DE POLICARBONATO','UND'],
                ['CARETA FACIAL AMARILLA','UND'],
                ['CARETA FACIAL AMARILLO','UND'],
                ['CARETA FACIAL DE POLICARBONATO','UND'],
                ['LENTES CLARO','UND'],
                ['LENTES CLARO 3M','UND'],
                ['LENTES CLARO STEELPRO','UND'],
                ['LENTES DE MALLA','UND'],
                ['LENTES GOGGLES','UND'],
                ['LENTES OSCURO','UND'],
                ['LENTES OSCURO 3M','UND'],
                ['MICA PARA CARETA','UND'],
                ['SOBRELENTES BLANCO','UND'],
                ['SOBRELENTES CLARO','UND'],
                ['SOBRELENTES OSCURO','UND'],
            ]],
            [$getFamilia('EPP-AUD'), [
                ['OREJERA PARA CASCO TIPO SOMBRERO','UND'],
                ['OREJERA PARA CASCO TIPO SOMBRERO 3M','UND'],
                ['OREJERA PARA CASCO TIPO SOMBRERO LIBUS','UND'],
                ['OREJERA PARA CASCO TIPO SOMBRERO MSA','UND'],
                ['OREJERAS 3M','UND'],
                ['TAPON AUDITIVO','UND'],
                ['TAPON AUDITIVO BOLSA SEGPRO','UND'],
                ['TAPON AUDITIVO EN CAJA','UND'],
                ['TAPON AUDITIVO EN CAJA SEGPRO','UND'],
            ]],
        ];

        $seq = self::START;
        $total = 0;
        foreach ($grupos as [$fid, $prods]) {
            foreach ($prods as [$nombre, $unidad]) {
                $codigo = 'CAP-' . str_pad($seq++, 4, '0', STR_PAD_LEFT);
                Producto::firstOrCreate(
                    ['empresa_id' => $eid, 'nombre' => $nombre],
                    ['familia_id' => $fid, 'codigo' => $codigo, 'unidad_medida' => $unidad, 'activo' => true, 'stock_minimo' => 0, 'stock_maximo' => 0]
                );
                $total++;
            }
        }
        $this->command->info("Parte 02 — EPP-CAB/OJO/AUD: $total productos.");
    }
}
