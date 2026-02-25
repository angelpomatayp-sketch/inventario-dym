<?php

namespace App\Modules\Reportes\Exports;

use App\Modules\Inventario\Models\MovimientoDetalle;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MovimientosExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
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
        $query = MovimientoDetalle::with([
            'movimiento:id,numero,tipo,fecha,documento_referencia,almacen_origen_id,almacen_destino_id,usuario_id,observaciones',
            'movimiento.almacenOrigen:id,nombre',
            'movimiento.almacenDestino:id,nombre',
            'movimiento.usuario:id,nombre',
            'producto:id,codigo,nombre'
        ])
            ->whereHas('movimiento', function ($q) {
                $q->where('empresa_id', $this->empresaId);

                if (!empty($this->filtros['fecha_inicio']) && !empty($this->filtros['fecha_fin'])) {
                    $q->whereBetween('fecha', [$this->filtros['fecha_inicio'], $this->filtros['fecha_fin']]);
                }

                if (!empty($this->filtros['tipo'])) {
                    $q->where('tipo', $this->filtros['tipo']);
                }

                if (!empty($this->filtros['almacen_id'])) {
                    $q->where(function ($sq) {
                        $sq->where('almacen_origen_id', $this->filtros['almacen_id'])
                          ->orWhere('almacen_destino_id', $this->filtros['almacen_id']);
                    });
                }
            });

        return $query->orderBy('id', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'N° Movimiento',
            'Tipo',
            'Documento',
            'Código Producto',
            'Producto',
            'Cantidad',
            'Costo Unit.',
            'Costo Total',
            'Almacén Origen',
            'Almacén Destino',
            'Usuario',
            'Observaciones',
        ];
    }

    public function map($detalle): array
    {
        $movimiento = $detalle->movimiento;

        return [
            $movimiento?->fecha?->format('d/m/Y') ?? '-',
            $movimiento?->numero ?? '-',
            $movimiento?->tipo ?? '-',
            $movimiento?->documento_referencia ?? '-',
            $detalle->producto?->codigo ?? '-',
            $detalle->producto?->nombre ?? '-',
            $detalle->cantidad,
            number_format($detalle->costo_unitario, 4),
            number_format($detalle->costo_total, 2),
            $movimiento?->almacenOrigen?->nombre ?? '-',
            $movimiento?->almacenDestino?->nombre ?? '-',
            $movimiento?->usuario?->nombre ?? '-',
            $movimiento?->observaciones ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF70AD47'],
            ], 'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']]],
        ];
    }

    public function title(): string
    {
        return 'Movimientos';
    }
}
