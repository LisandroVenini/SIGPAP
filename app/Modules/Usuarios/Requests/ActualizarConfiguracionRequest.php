<?php

namespace App\Modules\Usuarios\Requests;

use App\Modules\Usuarios\Models\ParametroGlobal;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarConfiguracionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', ParametroGlobal::class) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'parametros' => ['required', 'array'],
            'parametros.*' => ['required', 'string'],
        ];
    }
}
