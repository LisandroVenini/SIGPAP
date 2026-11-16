<?php

namespace App\Modules\Produccion\Requests;

use App\Modules\Produccion\Models\Maquinaria;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreMaquinariaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Maquinaria::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (Maquinaria::query()->whereRaw('lower(nombre) = lower(?)', [$value])->exists()) {
                        $fail('Ya existe una maquinaria con ese nombre.');
                    }
                },
            ],
            'tipo' => ['required', 'string', 'max:255'],
        ];
    }
}
