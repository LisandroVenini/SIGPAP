<?php

namespace App\Modules\Produccion\Requests;

use App\Modules\Produccion\Models\Operario;
use Illuminate\Foundation\Http\FormRequest;

class StoreOperarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Operario::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'especialidad' => ['required', 'string', 'max:255'],
        ];
    }
}
