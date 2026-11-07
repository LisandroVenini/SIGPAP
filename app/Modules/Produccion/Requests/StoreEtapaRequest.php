<?php

namespace App\Modules\Produccion\Requests;

use App\Modules\Produccion\Models\Etapa;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreEtapaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Etapa::class) ?? false;
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
                    if (Etapa::query()->conNombre((string) $value)->exists()) {
                        $fail('Ya existe una etapa con ese nombre.');
                    }
                },
            ],
            'descripcion' => ['nullable', 'string'],
        ];
    }
}
