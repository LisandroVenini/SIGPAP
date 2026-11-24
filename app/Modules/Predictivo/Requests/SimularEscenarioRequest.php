<?php

namespace App\Modules\Predictivo\Requests;

use App\Modules\Inventario\Services\GestionArticulosService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimularEscenarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'articulo_id' => ['required', 'integer', Rule::exists('articulos', 'id')],
            'cantidad' => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'fecha_limite' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $articuloId = (int) $this->input('articulo_id');

                if (app(GestionArticulosService::class)->productoTerminadoFabricado($articuloId) === null) {
                    $validator->errors()->add(
                        'articulo_id',
                        'El artículo debe ser un producto terminado de origen Fabricado.',
                    );
                }
            },
        ];
    }
}
