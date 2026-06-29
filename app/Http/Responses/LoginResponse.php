<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract, PasskeyLoginResponseContract, TwoFactorLoginResponseContract
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

    private function destination(Request $request): string
    {
        return match ($request->user()->role) {
            'postulante' => route('postulante.panel', absolute: false),
            'empresa' => route('empresa.panel', absolute: false),
            'admin' => route('admin.panel', absolute: false),
            default => route('dashboard', absolute: false),
        };
    }
}
