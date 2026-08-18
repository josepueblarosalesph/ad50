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
        abort_unless($request->user()?->esEmpresa(), 403);

        $empresa = $request->user()->empresa;

        // 1) Debe elegir y pagar un plan.
        if ($empresa === null || ! $empresa->planVigente()) {
            return redirect()->route('empresa.planes');
        }

        // 2) Con el pago confirmado, completa el resto de los datos.
        if (! $empresa->datosEnviados()) {
            return redirect()->route('empresa.activacion');
        }

        return $next($request);
    }
}
