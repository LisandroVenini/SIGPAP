<?php

namespace App\Modules\Produccion\Requests;

use App\Modules\Produccion\Models\OrdenDeProduccion;
use Carbon\Carbon;
use Closure;
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
            'cantidad' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'fecha_entrega' => [
                'required',
                'date',
                // An unchanged historical delivery date stays valid so an overdue
                // order can still be edited; moving it requires today or later.
                Rule::when($this->fechaCambia(), ['after_or_equal:today']),
                function (string $attribute, mixed $value, Closure $fail): void {
                    $orden = $this->route('orden');

                    if (! $orden instanceof OrdenDeProduccion || ! $this->fechaCambia()) {
                        return;
                    }

                    $fecha = Carbon::parse($value);

                    if ($orden->fecha_prevista_inicio !== null && $fecha->lt($orden->fecha_prevista_inicio)) {
                        $fail('La fecha de entrega no puede ser anterior al inicio previsto de la orden.');
                    }

                    $inicioDeLaPlanificacion = $orden->planificacionDeProduccion?->fecha_inicio;

                    if ($inicioDeLaPlanificacion !== null && $fecha->lt($inicioDeLaPlanificacion)) {
                        $fail('La fecha de entrega no puede ser anterior al inicio de la planificación.');
                    }
                },
            ],
        ];
    }

    /**
     * Whether the submitted delivery date differs from the stored one.
     */
    private function fechaCambia(): bool
    {
        $orden = $this->route('orden');

        if (! $orden instanceof OrdenDeProduccion) {
            return true;
        }

        return (string) $this->input('fecha_entrega') !== $orden->fecha_entrega?->toDateString();
    }
}
