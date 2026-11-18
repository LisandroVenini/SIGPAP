<?php

namespace App\Modules\Alertas\Requests;

use App\Shared\Alertas\Models\ReglaDeAlerta;
use Illuminate\Foundation\Http\FormRequest;

class ToggleReglaRequest extends FormRequest
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
        return [];
    }
}
