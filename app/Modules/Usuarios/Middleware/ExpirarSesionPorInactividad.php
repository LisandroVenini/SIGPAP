<?php

namespace App\Modules\Usuarios\Middleware;

use App\Modules\Usuarios\Services\ParametroGlobalService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ExpirarSesionPorInactividad
{
    public function __construct(private readonly ParametroGlobalService $parametros) {}

    /**
     * Log out a session that stayed idle longer than the configured limit.
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if (! Auth::guard('web')->check()) {
            return $next($request);
        }

        $limite = max(1, (int) ($this->parametros->parametro('seguridad_expiracion_sesion_min') ?? 30));
        $ultimaActividad = $request->session()->get('ultima_actividad_en');
        $segundosInactivo = is_int($ultimaActividad) ? now()->getTimestamp() - $ultimaActividad : null;

        if ($segundosInactivo !== null && $segundosInactivo >= $limite * 60) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Tu sesión expiró por inactividad.']);
        }

        $request->session()->put('ultima_actividad_en', now()->getTimestamp());

        return $next($request);
    }
}
