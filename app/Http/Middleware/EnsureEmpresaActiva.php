<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmpresaActiva
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->role === 'empresa', 403);

        $empresa = $request->user()->empresa;

        // 1) Debe completar sus datos de activación.
        if ($empresa === null || ! $empresa->datosEnviados()) {
            return redirect()->route('empresa.activacion');
        }

        // 2) Debe tener un plan pagado vigente para acceder al panel.
        if (! $empresa->planVigente()) {
            return redirect()->route('empresa.planes');
        }

        return $next($request);
    }
}
