<?php

namespace App\Modules\Compras\Controllers;

use App\Modules\Compras\Requests\StoreCotizacionRequest;
use App\Modules\Compras\Services\GestionCotizacionesService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PortalCotizacionController
{
    public function __construct(private readonly GestionCotizacionesService $cotizaciones) {}

    public function show(string $token): Response
    {
        if (session()->has('cotizacion_registrada')) {
            return Inertia::render('Portal/Cotizacion', [
                'enviada' => true,
            ]);
        }

        $solicitud = $this->cotizaciones->obtenerPorToken($token);

        abort_if($solicitud === null, 404);

        return Inertia::render('Portal/Cotizacion', [
            'solicitud' => $solicitud,
            'token' => $token,
        ]);
    }

    public function store(StoreCotizacionRequest $request, string $token): RedirectResponse
    {
        $this->cotizaciones->registrar($token, $request->validated('lineas'), $request->ip());

        return redirect()
            ->route('portal.cotizaciones.show', ['token' => $token])
            ->with('cotizacion_registrada', true);
    }
}
