<?php

namespace App\Modules\Usuarios\Requests;

use App\Modules\Usuarios\Enums\EstadoUsuario;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('usuario')) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Usuario $usuario */
        $usuario = $this->route('usuario');

        return [
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('usuarios', 'email')->ignore($usuario->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'rol' => ['required', 'string', Rule::in(Rol::values())],
            'estado' => ['required', 'string', Rule::in(EstadoUsuario::values())],
        ];
    }
}
