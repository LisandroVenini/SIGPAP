<?php

namespace App\Modules\Produccion\Requests;

use App\Modules\Produccion\Models\OrdenDeProduccion;
use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreParteDiarioRequest extends FormRequest
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
            'etapa_id' => ['required', 'integer', 'exists:etapas,id'],
            'maquinaria_id' => ['nullable', 'integer', 'exists:maquinarias,id'],
            'fecha' => [
                'required',
                'date',
                'before_or_equal:today',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $orden = $this->route('orden');

                    if ($orden instanceof OrdenDeProduccion
                        && $orden->fecha_inicio !== null
                        && Carbon::parse($value)->lt($orden->fecha_inicio)) {
                        $fail('La fecha del parte no puede ser anterior al inicio de la orden.');
                    }
                },
            ],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }
}
