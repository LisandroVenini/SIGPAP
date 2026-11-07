<?php

namespace App\Modules\Compras\Controllers;

use App\Modules\Compras\Requests\ConfirmarOrdenRequest;
use App\Modules\Compras\Services\GestionOrdenesDeCompraService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PortalOrdenController
{
    public function __construct(private readonly GestionOrdenesDeCompraService $ordenes) {}

    public function show(string $token): Response
    {
        if (session()->has('orden_resuelta')) {
            return Inertia::render('Portal/Orden', [
                'resultado' => session('orden_resuelta'),
                'token' => $token,
            ]);
        }

        $orden = $this->ordenes->obtenerPorToken($token);

        abort_if($orden === null, 404);

        return Inertia::render('Portal/Orden', [
            'orden' => $orden,
            'token' => $token,
        ]);
    }

    public function store(ConfirmarOrdenRequest $request, string $token): RedirectResponse
    {
        $aceptada = $request->boolean('aceptada');

        $this->ordenes->confirmar(
            $token,
            $aceptada,
            $request->validated('motivo'),
            $request->ip(),
        );

        return redirect()
            ->route('portal.ordenes.show', ['token' => $token])
            ->with('orden_resuelta', $aceptada ? 'Confirmada' : 'Rechazada');
    }
}
