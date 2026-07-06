<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePostulanteOnboardingComplete
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $postulante = $request->user()?->postulante;

        if ($request->user()?->role === 'postulante' && $postulante && ! $postulante->onboarding_completado) {
            return redirect()->route('postulante.ficha');
        }

        return $next($request);
    }
}
