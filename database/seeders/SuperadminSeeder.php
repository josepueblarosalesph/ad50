<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Cuenta de superadministración de la plataforma.
 *
 * Se siembra por `email` para que correr los seeders de nuevo no duplique la cuenta ni
 * pise la contraseña: la clave solo se escribe al CREARLA. La credencial vive hasheada
 * en `users.password` como la de cualquier usuario; lo de la configuración es apenas la
 * semilla inicial, que nunca se vuelve a leer para iniciar sesión.
 */
class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('ad50.superadmin.email');

        $user = User::query()->firstOrNew(['email' => $email]);
        $sinClaveDefinida = false;

        // Alta: nombre y contraseña iniciales. En una cuenta que ya existe no se tocan.
        if (! $user->exists) {
            $password = $this->passwordInicial();
            $sinClaveDefinida = $password === null;

            $user->forceFill([
                'name' => 'José Puebla',
                'nombres' => 'José',
                'apellidos' => 'Puebla Rosales',
                // Sin semilla definida se hashea una clave al azar que nadie conoce:
                // la cuenta queda creada pero inaccesible hasta restablecerla por correo.
                'password' => Hash::make($password ?? Str::password(48)),
                'email_verified_at' => now(),
                'acepta_ley_21719' => true,
            ]);
        }

        // Lo que sí se reafirma en cada corrida: el rol. Es la razón de ser del seeder.
        $user->role = 'superadmin';
        $user->save();

        $this->command->info("Superadministrador disponible: {$user->email}");

        if ($sinClaveDefinida) {
            $this->command->warn('Se creó sin contraseña conocida (SUPERADMIN_PASSWORD no está definida). Entra con "Olvidé mi contraseña".');
        }
    }

    /**
     * Contraseña con la que nace la cuenta, o null si no hay ninguna que usar.
     *
     * En local se cae a la misma clave que el resto de los seeders, porque ahí el dato
     * es de mentira y la comodidad manda. Fuera de local NO hay valor por omisión: una
     * cuenta con todos los privilegios naciendo con "password" es un agujero mucho peor
     * que la molestia de tener que restablecerla.
     */
    private function passwordInicial(): ?string
    {
        $configurada = config('ad50.superadmin.password');

        if (is_string($configurada) && $configurada !== '') {
            return $configurada;
        }

        return app()->environment('local', 'testing') ? 'password' : null;
    }
}
