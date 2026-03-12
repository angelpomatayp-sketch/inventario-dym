<?php

namespace App\Modules\Requisiciones\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequisicionRequest extends FormRequest
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
            'fecha_requerida' => 'required|date|after_or_equal:today',
            'prioridad'       => 'required|in:BAJA,NORMAL,ALTA,URGENTE',
            'motivo'          => 'required|string|max:500',
            'observaciones'   => 'nullable|string|max:1000',
            'detalles'                           => 'required|array|min:1',
            'detalles.*.producto_id'             => 'required|exists:productos,id',
            'detalles.*.cantidad_solicitada'     => 'required|numeric|min:0.01',
            'detalles.*.especificaciones'        => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'centro_costo_id.required'                => 'El centro de costo es requerido',
            'fecha_requerida.required'                => 'La fecha requerida es obligatoria',
            'fecha_requerida.after_or_equal'          => 'La fecha requerida debe ser hoy o posterior',
            'prioridad.required'                      => 'La prioridad es requerida',
            'motivo.required'                         => 'El motivo es requerido',
            'detalles.required'                       => 'Debe agregar al menos un producto',
            'detalles.min'                            => 'Debe agregar al menos un producto',
            'detalles.*.producto_id.required'         => 'El producto es requerido',
            'detalles.*.cantidad_solicitada.required' => 'La cantidad es requerida',
            'detalles.*.cantidad_solicitada.min'      => 'La cantidad debe ser mayor a 0',
        ];
    }
}
