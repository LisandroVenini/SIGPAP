<?php

namespace App\Http\Middleware;

use App\Shared\Alertas\Queries\AlertaQuery;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'nombre' => $user->nombre,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                ] : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
            ],
            'alertas_no_leidas' => $user !== null ? app(AlertaQuery::class)->noLeidas($user) : 0,
            'alertas_recientes' => $user !== null
                ? app(AlertaQuery::class)->ultimas($user, 5)->map(fn ($alerta): array => [
                    'id' => $alerta->id,
                    'tipo' => $alerta->tipo,
                    'severidad' => $alerta->severidad,
                    'descripcion' => $alerta->descripcion,
                    'fecha' => $alerta->fecha?->format('d/m H:i'),
                ])->all()
                : [],
        ];
    }
}
