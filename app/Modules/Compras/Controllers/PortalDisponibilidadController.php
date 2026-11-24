<?php

namespace App\Modules\Compras\Controllers;

use App\Modules\Compras\Requests\StoreDisponibilidadRequest;
use App\Modules\Compras\Services\GestionDisponibilidadService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PortalDisponibilidadController
{
    public function __construct(private readonly GestionDisponibilidadService $disponibilidad) {}

    public function show(string $token): Response
    {
        if (session()->has('disponibilidad_registrada')) {
            return Inertia::render('Portal/Disponibilidad', [
                'enviada' => true,
            ]);
        }

        $solicitud = $this->disponibilidad->obtenerPorToken($token);

        abort_if($solicitud === null, 404);

        return Inertia::render('Portal/Disponibilidad', [
            'solicitud' => $solicitud,
            'token' => $token,
        ]);
    }

    public function store(StoreDisponibilidadRequest $request, string $token): RedirectResponse
    {
        $this->disponibilidad->responder(
            $token,
            (float) $request->validated('cantidad_disponible'),
            (float) $request->validated('precio_unitario'),
            (string) $request->validated('fecha_prometida'),
            $request->ip(),
        );

        return redirect()
            ->route('portal.disponibilidad.show', ['token' => $token])
            ->with('disponibilidad_registrada', true);
    }
}
