<?php

namespace App\Modules\Compras\Requests;

use App\Modules\Compras\Models\SolicitudDeCotizacion;
use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSolicitudRequest extends FormRequest
{
    public function authorize(): bool
    {
        $solicitud = $this->route('solicitud');

        return $solicitud instanceof SolicitudDeCotizacion
            && ($this->user()?->can('update', $solicitud) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'fecha_vencimiento' => [
                'required',
                'date',
                'after_or_equal:today',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (Carbon::parse($value)->gt(today()->addDays(30))) {
                        $fail('El vencimiento no puede superar los 30 días desde hoy.');
                    }
                },
            ],
            'lineas' => ['required', 'array', 'min:1'],
            'lineas.*.articulo_id' => ['required', 'integer', 'exists:articulos,id'],
            'lineas.*.cantidad_solicitada' => ['required', 'numeric', 'gt:0'],
            'proveedores' => ['required', 'array', 'min:1'],
            'proveedores.*' => ['required', 'integer', 'distinct', 'exists:proveedores,id'],
        ];
    }
}
