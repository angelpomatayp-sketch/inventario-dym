<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CatalogoProductosSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CatalogoParte01Seeder::class, // Barras y Cinceles          CAP-0001 a CAP-0092
            CatalogoParte02Seeder::class, // EPP-CAB/OJO/AUD            CAP-0093 a CAP-0146
            CatalogoParte03Seeder::class, // EPP-RES/MAN/PIE/ALT/CUE   CAP-0147 a CAP-0250
            CatalogoParte04Seeder::class, // Equipos Electromecánicos   CAP-0251 a CAP-0326
            CatalogoParte05Seeder::class, // Ferretería 001-100         CAP-0327 a CAP-0426
            CatalogoParte06Seeder::class, // Ferretería 101-200         CAP-0427 a CAP-0526
            CatalogoParte07Seeder::class, // Ferretería 201-300         CAP-0527 a CAP-0626
            CatalogoParte08Seeder::class, // Ferretería 301-400         CAP-0627 a CAP-0726
            CatalogoParte09Seeder::class, // Ferretería 401-500         CAP-0727 a CAP-0826
            CatalogoParte10Seeder::class, // Ferretería 501-622         CAP-0827 a CAP-0948
            CatalogoParte11Seeder::class, // Filtros y Aceites          CAP-0949 a CAP-1051
            CatalogoParte12Seeder::class, // Formatos + Insumos médicos CAP-1052 a CAP-1147
            CatalogoParte13Seeder::class, // Materiales + MatSeg 1-56   CAP-1148 a CAP-1247
            CatalogoParte14Seeder::class, // MatSeg 57-106 + Tub 1-50   CAP-1248 a CAP-1347
            CatalogoParte15Seeder::class, // Tuberías 51-112 + Esc 1-75 CAP-1348 a CAP-1484
            CatalogoParte16Seeder::class, // Útiles Escritorio 76-275   CAP-1485 a CAP-1684
        ]);

        $this->command->info('✓ Catálogo completo importado: 1,684 productos (CAP-0001 a CAP-1684).');
    }
}
