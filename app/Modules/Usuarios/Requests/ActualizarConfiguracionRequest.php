<?php

namespace App\Modules\Usuarios\Requests;

use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\ParametroGlobalService;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarConfiguracionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Usuario|null $actor */
        $actor = $this->user();

        return $actor !== null
            && app(ParametroGlobalService::class)->puedeEditar($actor);
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
