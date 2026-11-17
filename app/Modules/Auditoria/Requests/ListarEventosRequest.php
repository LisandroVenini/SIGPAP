<?php

namespace App\Modules\Auditoria\Requests;

use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class ListarEventosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('auditoria.consultar') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'usuario_id' => ['nullable', 'integer'],
            'accion' => ['nullable', 'string', 'max:255'],
            'direccion_ip' => ['nullable', 'string', 'max:45'],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => [
                'nullable',
                'date',
                'after_or_equal:fecha_desde',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $desde = $this->input('fecha_desde');

                    if (is_string($desde) && $desde !== ''
                        && Carbon::parse($desde)->diffInDays(Carbon::parse($value)) > 366) {
                        $fail('El rango de fechas no puede superar los 366 días.');
                    }
                },
            ],
        ];
    }
}
