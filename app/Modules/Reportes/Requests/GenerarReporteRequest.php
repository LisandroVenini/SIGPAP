<?php

namespace App\Modules\Reportes\Requests;

use App\Modules\Reportes\Requests\Concerns\AuthorizesReporteTipo;
use App\Modules\Reportes\Services\GestionReportesService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerarReporteRequest extends FormRequest
{
    use AuthorizesReporteTipo;

    public function authorize(): bool
    {
        return $this->autorizarTipoDeReporte();
    }

    /**
     * The index request also serves the first page load, when no filter is
     * present yet. The range is mandatory as soon as a type is requested.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'tipo' => ['nullable', 'required_with:desde,hasta', Rule::in(array_keys(GestionReportesService::TIPOS))],
            'desde' => ['nullable', 'required_with:tipo', 'date'],
            'hasta' => ['nullable', 'required_with:tipo', 'date', 'after_or_equal:desde'],
            'estado' => ['nullable', 'string', 'max:100'],
        ];
    }
}
