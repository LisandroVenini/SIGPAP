<?php

namespace App\Modules\Produccion\Requests;

use App\Modules\Produccion\Models\OrdenDeProduccion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrdenDeProduccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('produccion.gestionar') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'fecha_entrega' => [
                'required',
                'date',
                // An unchanged historical delivery date stays valid so an overdue
                // order can still be edited; moving it requires today or later.
                Rule::when(
                    (string) $this->input('fecha_entrega') !== $this->fechaOriginal(),
                    ['after_or_equal:today'],
                ),
            ],
        ];
    }

    /**
     * Delivery date stored on the bound order, used to allow unchanged dates.
     */
    private function fechaOriginal(): ?string
    {
        $orden = $this->route('orden');

        return $orden instanceof OrdenDeProduccion
            ? $orden->fecha_entrega?->toDateString()
            : null;
    }
}
