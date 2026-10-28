<?php

namespace App\Modules\Produccion\Requests;

use App\Modules\Produccion\Models\Etapa;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEtapaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('etapa')) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Etapa|null $etapa */
        $etapa = $this->route('etapa');

        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail) use ($etapa): void {
                    $existe = Etapa::query()
                        ->conNombre((string) $value)
                        ->when($etapa !== null, fn (Builder $query) => $query->whereKeyNot($etapa->id))
                        ->exists();

                    if ($existe) {
                        $fail('Ya existe una etapa con ese nombre.');
                    }
                },
            ],
            'descripcion' => ['nullable', 'string'],
        ];
    }
}
