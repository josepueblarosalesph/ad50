<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract, TwoFactorLoginResponseContract
{
    /**
     * Create the response for a successful authentication.
     *
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        $destination = $this->destination($request);

        if ($request->wantsJson()) {
            return new JsonResponse([
                'two_factor' => false,
                'redirect' => $destination,
            ]);
        }

        return redirect($destination);
    }

    /**
     * A dónde mandar a la persona recién autenticada.
     *
     * Manda primero a la página que intentaba abrir cuando el middleware `auth` la
     * derivó al login. Importa sobre todo para el enlace de verificación de correo:
     * quien abre el correo en el teléfono (o en otro navegador) llega sin sesión, y si
     * al iniciarla lo llevamos a su panel en vez de al enlace firmado, el gating
     * `verified` lo devuelve a "confirma tu correo" y queda dando vueltas sin
     * verificarse nunca.
     */
    private function destination(Request $request): string
    {
        $intencion = $request->session()->pull('url.intended');

        if (is_string($intencion) && $intencion !== '' && $this->esDelSitio($request, $intencion)) {
            return $intencion;
        }

        return route($request->user()->dashboardRouteName(), absolute: false);
    }

    /** Resguardo contra redirecciones a otro dominio si algo dejara una URL externa en sesión. */
    private function esDelSitio(Request $request, string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host === null || $host === $request->getHost();
    }
}
