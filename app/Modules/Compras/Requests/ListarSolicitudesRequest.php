<?php

namespace App\Modules\Compras\Requests;

use App\Modules\Compras\Models\SolicitudDeCotizacion;
use Illuminate\Foundation\Http\FormRequest;

class ListarSolicitudesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', SolicitudDeCotizacion::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'estado' => ['nullable', 'string', 'in:Enviada,Anulada,Adjudicada,Borrador'],
            'proveedor' => ['nullable', 'string', 'max:255'],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
        ];
    }
}
