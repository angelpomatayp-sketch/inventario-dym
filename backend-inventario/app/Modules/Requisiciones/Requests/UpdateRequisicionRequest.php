<?php

namespace App\Modules\Requisiciones\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequisicionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['almacenero', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'centro_costo_id' => 'required|exists:centros_costos,id',
            'almacen_id'      => 'nullable|exists:almacenes,id',
            // Sin after_or_equal:today para permitir editar requerimientos con fecha pasada
            'fecha_requerida' => 'required|date',
            'prioridad'       => 'required|in:BAJA,NORMAL,ALTA,URGENTE',
            'motivo'          => 'required|string|max:500',
            'observaciones'   => 'nullable|string|max:1000',
            'detalles'                           => 'required|array|min:1',
            'detalles.*.producto_id'             => 'required|exists:productos,id',
            'detalles.*.cantidad_solicitada'     => 'required|numeric|min:0.01',
            'detalles.*.especificaciones'        => 'nullable|string|max:500',
        ];
    }
}
