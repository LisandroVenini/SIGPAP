<?php

namespace App\Modules\Compras\Requests;

use App\Modules\Compras\Models\SolicitudDeCotizacion;
use Illuminate\Foundation\Http\FormRequest;

class AnularSolicitudRequest extends FormRequest
{
    public function authorize(): bool
    {
        $solicitud = $this->route('solicitud');

        return $solicitud instanceof SolicitudDeCotizacion
            && ($this->user()?->can('delete', $solicitud) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'motivo' => ['nullable', 'string', 'max:500'],
        ];
    }
}
