<?php

namespace App\Modules\Usuarios\Middleware;

use App\Modules\Usuarios\Enums\EstadoUsuario;
use App\Modules\Usuarios\Models\Usuario;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUsuarioIsActive
{
    /**
     * Log out an authenticated user whose account was disabled after the session started.
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $usuario = $request->user();

        if ($usuario instanceof Usuario && $usuario->estado !== EstadoUsuario::Activo->value) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Tu cuenta está inhabilitada.']);
        }

        return $next($request);
    }
}
