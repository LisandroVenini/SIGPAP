<?php

namespace App\Modules\Produccion\Requests;

use App\Modules\Produccion\Models\Operario;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOperarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('operario')) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $nombre = (string) $this->input('nombre');
                    $actual = $this->route('operario');
                    $existe = Operario::withTrashed()
                        ->whereRaw('lower(nombre) = lower(?)', [$nombre])
                        ->whereRaw('lower(apellido) = lower(?)', [$value])
                        ->when($actual instanceof Operario, fn ($query) => $query->whereKeyNot($actual->getKey()))
                        ->exists();

                    if ($existe) {
                        $fail('Ya existe un operario con ese nombre y apellido.');
                    }
                },
            ],
            'especialidad' => ['required', 'string', 'max:255'],
        ];
    }
}
