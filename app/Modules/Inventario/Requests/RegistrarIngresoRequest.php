<?php

namespace App\Modules\Inventario\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarIngresoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventario.gestionar') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'numero_oc' => ['required', 'string'],
            'numero_remito' => ['required', 'string', 'max:255'],
            'entrega_parcial' => ['boolean'],
            'confirmar_excedente' => ['boolean'],
            'lineas' => ['required', 'array', 'min:1'],
            'lineas.*.articulo_id' => ['required', 'integer'],
            'lineas.*.cantidad' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
