<?php

namespace App\Modules\Alertas\Requests;

use App\Shared\Alertas\Catalogo\CatalogoDeAlertas;
use App\Shared\Alertas\Catalogo\TipoDeAlerta;
use App\Shared\Alertas\Enums\SeveridadAlerta;
use App\Shared\Alertas\Models\ReglaDeAlerta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateReglaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $regla = $this->route('regla');

        return $regla instanceof ReglaDeAlerta
            && ($this->user()?->can('update', $regla) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var ReglaDeAlerta $regla */
        $regla = $this->route('regla');
        $tipo = CatalogoDeAlertas::obtener($regla->tipo);

        return [
            'nombre' => ['required', 'string', 'max:120', Rule::unique('reglas_de_alerta', 'nombre')->ignore($regla->id)],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'severidad' => ['required', 'string', Rule::in(SeveridadAlerta::values())],
            'mensaje' => ['required', 'string', 'max:500'],
            'activa' => ['boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', 'exists:roles,id'],
            'condiciones' => ['nullable', 'array'],
            ...$this->reglasDeCondiciones($tipo),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var ReglaDeAlerta $regla */
            $regla = $this->route('regla');
            $tipo = CatalogoDeAlertas::obtener($regla->tipo);

            if ($tipo === null) {
                return;
            }

            preg_match_all('/\{\{\s*([a-z_]+)\s*\}\}/', (string) $this->input('mensaje'), $coincidencias);
            $invalidas = array_values(array_diff(array_unique($coincidencias[1]), $tipo->variables));

            if ($invalidas !== []) {
                $validator->errors()->add(
                    'mensaje',
                    'Variables no válidas para este tipo: '.implode(', ', $invalidas).'.',
                );
            }
        });
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function reglasDeCondiciones(?TipoDeAlerta $tipo): array
    {
        if ($tipo === null) {
            return [];
        }

        $reglas = [];

        foreach ($tipo->condiciones as $condicion) {
            $reglas['condiciones.'.$condicion['clave']] = [
                'nullable',
                $condicion['tipo'] === 'integer' ? 'integer' : 'numeric',
                'min:'.$condicion['minimo'],
                'max:'.$condicion['maximo'],
            ];
        }

        return $reglas;
    }
}
