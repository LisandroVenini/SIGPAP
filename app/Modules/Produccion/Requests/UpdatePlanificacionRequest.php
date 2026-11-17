<?php

namespace App\Modules\Produccion\Requests;

use App\Modules\Produccion\Models\PlanificacionDeProduccion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanificacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('planificacion')) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $planificacion = $this->route('planificacion');
        $original = $planificacion instanceof PlanificacionDeProduccion ? $planificacion : null;

        return [
            'nombre' => ['required', 'string', 'max:255'],
            'fecha_inicio' => [
                'required',
                'date',
                // An unchanged historical date stays valid so old records can
                // still be edited; moving it requires a date from today on.
                Rule::when(
                    (string) $this->input('fecha_inicio') !== $original?->fecha_inicio?->toDateString(),
                    ['after_or_equal:today'],
                ),
            ],
            'fecha_fin' => [
                'required',
                'date',
                'after_or_equal:fecha_inicio',
                Rule::when(
                    (string) $this->input('fecha_fin') !== $original?->fecha_fin?->toDateString(),
                    ['after_or_equal:today'],
                ),
            ],
            'observaciones' => ['nullable', 'string'],
        ];
    }
}
