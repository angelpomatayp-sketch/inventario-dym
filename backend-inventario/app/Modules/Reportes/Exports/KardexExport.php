<?php

namespace App\Modules\Reportes\Exports;

use App\Modules\Inventario\Models\Kardex;
use App\Modules\Inventario\Models\Movimiento;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KardexExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected int $empresaId;
    protected array $filtros;

    public function __construct(int $empresaId, array $filtros)
    {
        $this->empresaId = $empresaId;
        $this->filtros = $filtros;
    }

    public function collection()
    {
        $incluirAnulados = filter_var($this->filtros['incluir_anulados'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $query = Kardex::with(['producto:id,codigo,nombre', 'almacen:id,nombre'])
            ->where('empresa_id', $this->empresaId)
            ->whereBetween('fecha', [$this->filtros['fecha_inicio'], $this->filtros['fecha_fin']]);

        if (!empty($this->filtros['producto_id'])) {
            $query->where('producto_id', $this->filtros['producto_id']);
        }

        if (!empty($this->filtros['almacen_id'])) {
            $query->where('almacen_id', $this->filtros['almacen_id']);
        }

        if (!$incluirAnulados) {
            $query->where(function ($q) {
                $q->whereNull('movimiento_id')
                  ->orWhereHas('movimiento', function ($movQ) {
                      $movQ->where('estado', '!=', Movimiento::ESTADO_ANULADO);
                  });
            });
        }

        return $query->orderBy('fecha')->orderBy('id')->get();
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Producto',
            'Código',
            'Almacén',
            'Tipo Mov.',
            'Documento',
            'Entrada Cant.',
            'Entrada Costo',
            'Entrada Valor',
            'Salida Cant.',
            'Salida Costo',
            'Salida Valor',
            'Saldo Cant.',
            'Saldo Valor',
        ];
    }

    public function map($kardex): array
    {
        $esEntrada = in_array($kardex->tipo_operacion, ['ENTRADA', 'AJUSTE_POSITIVO', 'SALDO_INICIAL']);
        $esSalida = in_array($kardex->tipo_operacion, ['SALIDA', 'AJUSTE_NEGATIVO']);

        return [
            $kardex->fecha->format('d/m/Y'),
            $kardex->producto?->nombre,
            $kardex->producto?->codigo,
            $kardex->almacen?->nombre,
            $kardex->tipo_operacion,
            $kardex->documento_referencia ?? '-',
            $esEntrada ? $kardex->cantidad : '',
            $esEntrada ? number_format($kardex->costo_unitario, 4) : '',
            $esEntrada ? number_format($kardex->costo_total, 2) : '',
            $esSalida ? $kardex->cantidad : '',
            $esSalida ? number_format($kardex->costo_unitario, 4) : '',
            $esSalida ? number_format($kardex->costo_total, 2) : '',
            $kardex->saldo_cantidad,
            number_format($kardex->saldo_costo_total, 2),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFE0E0E0'],
            ]],
        ];
    }

    public function title(): string
    {
        return 'Kardex Valorizado';
    }
}
