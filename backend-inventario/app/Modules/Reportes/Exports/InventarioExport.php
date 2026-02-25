<?php

namespace App\Modules\Reportes\Exports;

use App\Modules\Inventario\Models\Producto;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventarioExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected int $empresaId;
    protected array $filtros;

    public function __construct(int $empresaId, array $filtros = [])
    {
        $this->empresaId = $empresaId;
        $this->filtros = $filtros;
    }

    public function collection()
    {
        $query = Producto::with(['familia:id,nombre', 'stockAlmacenes'])
            ->where('empresa_id', $this->empresaId)
            ->where('activo', true);

        if (!empty($this->filtros['search'])) {
            $search = trim((string) $this->filtros['search']);
            $query->where(function ($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                  ->orWhere('nombre', 'like', "%{$search}%");
            });
        }

        if (!empty($this->filtros['familia_id'])) {
            $query->where('familia_id', $this->filtros['familia_id']);
        }

        if (!empty($this->filtros['almacen_id'])) {
            $query->whereHas('stockAlmacenes', function ($q) {
                $q->where('almacen_id', $this->filtros['almacen_id'])
                  ->where('stock_actual', '>', 0);
            });
        }

        return $query->orderBy('codigo')->get();
    }

    public function headings(): array
    {
        return [
            'Código',
            'Nombre',
            'Familia',
            'Unidad',
            'Stock Actual',
            'Stock Mínimo',
            'Costo Promedio',
            'Valor Total',
            'Estado Stock',
        ];
    }

    public function map($producto): array
    {
        $stockActual = $producto->stockAlmacenes->sum('stock_actual');
        $stockMinimo = $producto->stock_minimo;
        $costoPromedio = $producto->stockAlmacenes->avg('costo_promedio') ?? 0;
        $valorTotal = $stockActual * $costoPromedio;

        $estadoStock = 'Normal';
        if ($stockActual <= 0) {
            $estadoStock = 'Sin Stock';
        } elseif ($stockActual <= $stockMinimo) {
            $estadoStock = 'Stock Bajo';
        }

        return [
            $producto->codigo,
            $producto->nombre,
            $producto->familia?->nombre,
            $producto->unidad_medida,
            $stockActual,
            $stockMinimo,
            number_format($costoPromedio, 4),
            number_format($valorTotal, 2),
            $estadoStock,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF4472C4'],
            ], 'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']]],
        ];
    }

    public function title(): string
    {
        return 'Inventario Valorizado';
    }
}
