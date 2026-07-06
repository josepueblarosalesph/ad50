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

        if (! $request->user()->empresa?->estaActiva()) {
            return redirect()->route('empresa.activacion');
        }

        return $next($request);
    }
}
