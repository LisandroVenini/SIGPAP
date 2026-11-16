<?php

namespace App\Modules\Produccion\Requests;

use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Produccion\Enums\ModoDeAltaOrden;
use App\Modules\Produccion\Models\PlanificacionDeProduccion;
use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrdenDeProduccionRequest extends FormRequest
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
            'planificacion_de_produccion_id' => ['required', 'integer', 'exists:'.PlanificacionDeProduccion::class.',id'],
            'fecha_entrega' => [
                'required',
                'date',
                'after_or_equal:today',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $planificacion = PlanificacionDeProduccion::query()
                        ->find($this->input('planificacion_de_produccion_id'));

                    if ($planificacion !== null && Carbon::parse($value)->lt($planificacion->fecha_inicio)) {
                        $fail('La fecha de entrega no puede ser anterior al inicio de la planificación.');
                    }
                },
            ],
            'modo' => ['required', Rule::enum(ModoDeAltaOrden::class)],
            'lineas' => ['required', 'array', 'min:1'],
            'lineas.*.articulo_id' => [
                'required',
                'integer',
                'distinct',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (app(GestionArticulosService::class)->productoTerminadoFabricado((int) $value) === null) {
                        $fail('El producto debe ser un producto terminado de origen Fabricado y estar Activo.');
                    }
                },
            ],
            'lineas.*.cantidad' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
