<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Auth\Events\Verified;

/**
 * Rescate de cuentas que quedaron atascadas sin verificar su correo.
 *
 * Mientras `email_verified_at` sea null, el middleware `verified` deja a la persona
 * fuera de todo el panel y no hay nada que pueda hacer desde la interfaz salvo pedir
 * otro correo. En un portal cuyo público son personas 50+, "no me llegó" o "lo borré
 * sin querer" es soporte del día a día, así que la administración necesita poder
 * resolverlo sin tocar la base a mano.
 *
 * Dos caminos, en orden de preferencia:
 *
 * 1. reenviarVerificacion() — la vía sana: la persona confirma su propio correo.
 * 2. marcarVerificada() — el rescate, cuando el correo definitivamente no llega
 *    (dirección con una errata, buzón perdido). Da por bueno un correo que nadie
 *    demostró controlar, así que la interfaz lo pide confirmando y queda registrado
 *    en el log con quién lo hizo.
 *
 * Lo usan las tres pantallas donde un administrador ya trabaja: Usuarios, Postulantes
 * y Empresas. El componente que lo incluya expone ambas acciones a cualquier admin
 * (esAdmin), no solo al superadministrador: desatascar a alguien es soporte, no una
 * operación privilegiada.
 */
trait VerificaCuentas
{
    /** Reenvía el correo con el enlace de verificación de Fortify. */
    public function reenviarVerificacion(int $userId): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $user = User::query()->findOrFail($userId);

        if ($user->hasVerifiedEmail()) {
            session()->flash('status', "La cuenta de {$user->name} ya estaba verificada.");

            return;
        }

        $user->sendEmailVerificationNotification();

        session()->flash('status', "Reenviamos el correo de verificación a {$user->email}.");
    }

    /**
     * Da la cuenta por verificada sin que la persona haga clic.
     *
     * markEmailAsVerified() solo escribe la fecha: el evento Verified lo dispara aparte
     * el controlador de Fortify, no el modelo. Se emite aquí a mano para que verificar
     * desde la administración sea indistinguible de verificar por el enlace; si no,
     * cualquier cosa que en el futuro cuelgue de Verified se saltaría estos casos.
     */
    public function marcarVerificada(int $userId): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $user = User::query()->findOrFail($userId);

        if ($user->hasVerifiedEmail()) {
            session()->flash('status', "La cuenta de {$user->name} ya estaba verificada.");

            return;
        }

        $user->markEmailAsVerified();

        event(new Verified($user));

        // Se confirma un correo que nadie probó controlar: queda quién lo autorizó.
        logger()->info('Cuenta verificada manualmente por la administración', [
            'usuario_verificado' => $user->id,
            'email' => $user->email,
            'administrador' => auth()->id(),
        ]);

        session()->flash('status', "Marcaste la cuenta de {$user->name} como verificada.");
    }
}
