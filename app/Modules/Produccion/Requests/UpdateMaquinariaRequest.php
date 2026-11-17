<?php

namespace App\Modules\Produccion\Requests;

use App\Modules\Produccion\Models\Maquinaria;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMaquinariaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('maquinaria')) ?? false;
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
                    $actual = $this->route('maquinaria');
                    $existe = Maquinaria::query()
                        ->whereRaw('lower(nombre) = lower(?)', [$value])
                        ->when($actual instanceof Maquinaria, fn ($query) => $query->whereKeyNot($actual->getKey()))
                        ->exists();

                    if ($existe) {
                        $fail('Ya existe una maquinaria con ese nombre.');
                    }
                },
            ],
            'tipo' => ['required', 'string', 'max:255'],
        ];
    }
}
