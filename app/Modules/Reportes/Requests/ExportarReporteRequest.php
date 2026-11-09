<?php

namespace App\Modules\Reportes\Requests;

use App\Modules\Reportes\Requests\Concerns\AuthorizesReporteTipo;
use App\Modules\Reportes\Services\GestionReportesService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportarReporteRequest extends FormRequest
{
    use AuthorizesReporteTipo;

    public function authorize(): bool
    {
        return $this->autorizarTipoDeReporte();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::in(array_keys(GestionReportesService::TIPOS))],
            'desde' => ['required', 'date'],
            'hasta' => ['required', 'date', 'after_or_equal:desde'],
            'estado' => ['nullable', 'string', 'max:100'],
            'formato' => ['required', Rule::in(['pdf', 'xlsx'])],
        ];
    }
}
