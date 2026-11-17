<?php

namespace App\Modules\Produccion\Requests;

use App\Modules\Produccion\Models\Maquinaria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIntervencionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('produccion.gestionar') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'fecha' => [
                'required',
                'date',
                'before_or_equal:today',
                Rule::unique('intervenciones_de_mantenimiento', 'fecha')
                    ->where(fn ($query) => $query->where('maquinaria_id', $this->maquinariaId())),
            ],
            'tipo' => ['required', 'string', 'max:255'],
            'responsable' => ['required', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function maquinariaId(): ?int
    {
        $maquinaria = $this->route('maquinaria');

        return $maquinaria instanceof Maquinaria ? (int) $maquinaria->getKey() : null;
    }
}
