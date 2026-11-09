<?php

namespace App\Modules\Auditoria\Requests;

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
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
        ];
    }
}
