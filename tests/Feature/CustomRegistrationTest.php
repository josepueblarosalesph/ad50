<?php

use App\Livewire\Auth\Register;
use App\Mail\SolicitudAccesoEquipo;
use App\Models\Empresa;
use App\Models\Postulante;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

test('a postulante can create an account', function () {
    Notification::fake();

    Livewire::test(Register::class)
        ->set('role', 'postulante')
        ->set('nombre', 'María')
        ->set('apellidos', 'Fuentes')
        ->set('email', 'maria@example.com')
        ->set('password', 'password')
        ->set('acepta', true)
        ->call('submit')
        ->assertHasNoErrors()
        // TEMPORAL: mientras Auth\Register omite la verificación de correo se entra
        // directo al panel. Al restaurarla, este destino vuelve a verification.notice.
        ->assertRedirect(route('dashboard'));

    $user = User::query()->where('email', 'maria@example.com')->firstOrFail();

    expect($user->role)->toBe('postulante')
        ->and($user->acepta_ley_21719)->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeTrue();

    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('postulantes', [
        'user_id' => $user->id,
        'onboarding_paso' => 1,
        'onboarding_completado' => false,
    ]);
    Notification::assertNothingSentTo($user);
});

test('an empresa can create an account', function () {
    Notification::fake();

    $registration = file_get_contents(resource_path('views/livewire/auth/register.blade.php'));

    expect(strpos($registration, 'wire:model="razon_social"'))->toBeLessThan(strpos($registration, 'wire:model.blur="rut"'))
        ->and(strpos($registration, 'wire:model.blur="rut"'))->toBeLessThan(strpos($registration, 'Datos de contacto'))
        ->and(strpos($registration, 'Datos de contacto'))->toBeLessThan(strpos($registration, 'wire:model="nombre"'))
        ->and(strpos($registration, 'wire:model="nombre"'))->toBeLessThan(strpos($registration, 'wire:model="apellidos"'))
        ->and(strpos($registration, 'wire:model="apellidos"'))->toBeLessThan(strpos($registration, 'wire:model="telefono"'))
        ->and(strpos($registration, 'wire:model="telefono"'))->toBeLessThan(strpos($registration, 'wire:model="email"'))
        ->and(strpos($registration, 'wire:model="email"'))->toBeLessThan(strpos($registration, 'wire:model="password"'));

    Livewire::test(Register::class)
        ->set('role', 'empresa')
        ->set('nombre', 'Ana')
        ->set('apellidos', 'Silva')
        ->set('email', 'ana@empresa.cl')
        ->set('password', 'password')
        ->set('razon_social', 'Empresa de Prueba SpA')
        ->set('rut', '761234560')
        ->set('telefono', '+56 9 8765 4321')
        ->set('acepta', true)
        ->call('submit')
        ->assertHasNoErrors()
        // TEMPORAL: ver la nota del test anterior.
        ->assertRedirect(route('dashboard'));

    $user = User::query()->where('email', 'ana@empresa.cl')->firstOrFail();

    expect($user->role)->toBe('empresa')
        ->and($user->acepta_ley_21719)->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeTrue();

    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('empresas', [
        'user_id' => $user->id,
        'razon_social' => 'Empresa de Prueba SpA',
        'rut' => '76.123.456-0',
        'telefono' => '+56 9 8765 4321',
        'estado_activacion' => 'inactiva',
    ]);
    Notification::assertNothingSentTo($user);
});

test('an empresa cannot register with a free personal email', function () {
    Livewire::test(Register::class)
        ->set('role', 'empresa')
        ->set('nombre', 'Ana')
        ->set('apellidos', 'Silva')
        ->set('email', 'ana@gmail.com')
        ->set('password', 'password')
        ->set('razon_social', 'Empresa de Prueba SpA')
        ->set('rut', '761234560')
        ->set('telefono', '+56 9 8765 4321')
        ->set('acepta', true)
        ->call('submit')
        ->assertHasErrors('email');

    expect(User::query()->where('email', 'ana@gmail.com')->exists())->toBeFalse();
});

test('a postulante can register with a free personal email', function () {
    Notification::fake();

    Livewire::test(Register::class)
        ->set('role', 'postulante')
        ->set('nombre', 'Ana')
        ->set('apellidos', 'Silva')
        ->set('email', 'ana@gmail.com')
        ->set('password', 'password')
        ->set('acepta', true)
        ->call('submit')
        ->assertHasNoErrors();
});

/** Registra la primera cuenta de una empresa y devuelve a su administrador. */
function empresaRegistradaCon(string $email, string $razonSocial = 'Ejemplo SpA'): User
{
    $admin = User::factory()->create(['role' => 'empresa', 'email' => $email]);
    Empresa::query()->create([
        'user_id' => $admin->id,
        'razon_social' => $razonSocial,
        'estado_activacion' => 'activa',
    ]);

    return $admin->fresh();
}

test('quien comparte el dominio de una empresa ya registrada debe pedir acceso a su administrador', function () {
    empresaRegistradaCon('admin@ejemplo.cl', 'Ejemplo SpA');

    Livewire::test(Register::class)
        ->set('role', 'empresa')
        ->set('nombre', 'Ana')
        ->set('apellidos', 'Silva')
        ->set('email', 'ana@ejemplo.cl')
        ->set('password', 'password')
        ->set('razon_social', 'Ejemplo SpA')
        ->set('rut', '761234560')
        ->set('telefono', '+56 9 8765 4321')
        ->set('acepta', true)
        ->call('submit')
        ->assertHasErrors('email');

    // El mensaje nombra la empresa y el camino a seguir, pero no expone el correo del
    // administrador: para eso está el botón que le envía la solicitud.
    $mensaje = Livewire::test(Register::class)
        ->set('role', 'empresa')
        ->set('nombre', 'Ana')
        ->set('apellidos', 'Silva')
        ->set('email', 'ana@ejemplo.cl')
        ->set('password', 'password')
        ->set('razon_social', 'Ejemplo SpA')
        ->set('rut', '761234560')
        ->set('telefono', '+56 9 8765 4321')
        ->set('acepta', true)
        ->call('submit')
        ->errors()->first('email');

    expect($mensaje)->toContain('Ejemplo SpA')
        ->toContain('@ejemplo.cl')
        ->not->toContain('admin@ejemplo.cl')
        ->toContain('Equipo');

    expect(User::query()->where('email', 'ana@ejemplo.cl')->exists())->toBeFalse();
});

test('quien comparte el dominio puede pedir por correo que el administrador lo sume al equipo', function () {
    Mail::fake();

    $admin = empresaRegistradaCon('admin@ejemplo.cl', 'Ejemplo SpA');

    $componente = registroEmpresaBloqueado()
        ->assertSet('empresa_registrada_id', $admin->empresa->id)
        ->assertSet('empresa_registrada_nombre', 'Ejemplo SpA')
        ->assertSet('solicitud_enviada', false)
        // El botón aparece; el correo del administrador no.
        ->assertSee('Solicitar acceso al administrador')
        ->assertDontSee('admin@ejemplo.cl');

    $componente->call('solicitarAcceso')
        ->assertHasNoErrors()
        ->assertSet('solicitud_enviada', true)
        ->assertSee('Solicitud enviada');

    Mail::assertSent(SolicitudAccesoEquipo::class, function (SolicitudAccesoEquipo $mail) use ($admin) {
        $mail->assertSeeInHtml('Ana Silva');
        $mail->assertSeeInHtml('ana@ejemplo.cl');
        $mail->assertSeeInHtml('+56 9 8765 4321');
        $mail->assertSeeInHtml(route('empresa.equipo'));

        return $mail->hasTo($admin->email)
            && $mail->hasReplyTo('ana@ejemplo.cl')
            && $mail->empresa->is($admin->empresa);
    });

    // Sigue sin crearse una segunda cuenta: el acceso lo da el administrador.
    expect(User::query()->where('email', 'ana@ejemplo.cl')->exists())->toBeFalse();
});

test('la solicitud de acceso no se puede repetir a voluntad', function () {
    Mail::fake();

    empresaRegistradaCon('admin@ejemplo.cl');

    registroEmpresaBloqueado()
        ->call('solicitarAcceso')
        ->call('solicitarAcceso')
        ->assertSet('solicitud_enviada', true);

    Mail::assertSentCount(1);
});

test('sin empresa bloqueada no se envía ninguna solicitud', function () {
    Mail::fake();

    // Nadie fijó empresa_registrada_id: el botón ni siquiera se muestra, y la acción
    // llamada a mano no envía correo.
    Livewire::test(Register::class)
        ->set('role', 'empresa')
        ->set('email', 'ana@ejemplo.cl')
        ->call('solicitarAcceso')
        ->assertHasErrors('email');

    Mail::assertNothingSent();
});

test('cambiar el correo descarta el aviso de la empresa ya registrada', function () {
    empresaRegistradaCon('admin@ejemplo.cl');

    registroEmpresaBloqueado()
        ->set('email', 'ana@otra-empresa.cl')
        ->assertSet('empresa_registrada_id', null)
        ->assertSet('solicitud_enviada', false)
        ->assertDontSee('Solicitar acceso al administrador');
});

/** Deja el formulario en el estado en que el registro quedó bloqueado por dominio. */
function registroEmpresaBloqueado(): Testable
{
    return Livewire::test(Register::class)
        ->set('role', 'empresa')
        ->set('nombre', 'Ana')
        ->set('apellidos', 'Silva')
        ->set('email', 'ana@ejemplo.cl')
        ->set('password', 'password')
        ->set('razon_social', 'Ejemplo SpA')
        ->set('rut', '761234560')
        ->set('telefono', '+56 9 8765 4321')
        ->set('acepta', true)
        ->call('submit')
        ->assertHasErrors('email');
}

test('el dominio se compara completo: un dominio parecido no bloquea el registro', function () {
    empresaRegistradaCon('admin@ejemplo.cl');

    // Ni un dominio que termina igual ni un subdominio son la misma organización.
    foreach (['ana@otro-ejemplo.cl', 'ana@sub.ejemplo.cl'] as $email) {
        Livewire::test(Register::class)
            ->set('role', 'empresa')
            ->set('nombre', 'Ana')
            ->set('apellidos', 'Silva')
            ->set('email', $email)
            ->set('password', 'password')
            ->set('razon_social', 'Otra SpA')
            ->set('rut', '761234560')
            ->set('telefono', '+56 9 8765 4321')
            ->set('acepta', true)
            ->call('submit')
            ->assertHasNoErrors('email');
    }
});

test('el dominio de un postulante no reserva la cuenta de empresa', function () {
    // Un postulante con correo de ese dominio no crea una cuenta de empresa: no bloquea.
    $postulanteUser = User::factory()->create(['role' => 'postulante', 'email' => 'juan@libre.cl']);
    Postulante::query()->create(['user_id' => $postulanteUser->id, 'visible' => true]);

    Livewire::test(Register::class)
        ->set('role', 'empresa')
        ->set('nombre', 'Ana')
        ->set('apellidos', 'Silva')
        ->set('email', 'ana@libre.cl')
        ->set('password', 'password')
        ->set('razon_social', 'Libre SpA')
        ->set('rut', '761234560')
        ->set('telefono', '+56 9 8765 4321')
        ->set('acepta', true)
        ->call('submit')
        ->assertHasNoErrors('email');
});

test('un postulante puede registrarse aunque su dominio ya tenga cuenta de empresa', function () {
    empresaRegistradaCon('admin@ejemplo.cl');

    Livewire::test(Register::class)
        ->set('role', 'postulante')
        ->set('nombre', 'Ana')
        ->set('apellidos', 'Silva')
        ->set('email', 'ana@ejemplo.cl')
        ->set('password', 'password')
        ->set('acepta', true)
        ->call('submit')
        ->assertHasNoErrors();
});

test('un usuario del equipo también reserva el dominio, no solo el administrador', function () {
    $admin = empresaRegistradaCon('admin@corporativo.cl', 'Corporativo SpA');

    // Un contacto adicional agregado desde Equipo, con el mismo dominio.
    User::factory()->create([
        'role' => 'empresa',
        'email' => 'equipo@corporativo.cl',
        'empresa_id' => $admin->empresa_id,
    ]);

    Livewire::test(Register::class)
        ->set('role', 'empresa')
        ->set('nombre', 'Ana')
        ->set('apellidos', 'Silva')
        ->set('email', 'ana@corporativo.cl')
        ->set('password', 'password')
        ->set('razon_social', 'Corporativo SpA')
        ->set('rut', '761234560')
        ->set('telefono', '+56 9 8765 4321')
        ->set('acepta', true)
        ->call('submit')
        ->assertHasErrors('email');
});

test('an empresa must provide a contact phone number', function () {
    Livewire::test(Register::class)
        ->set('role', 'empresa')
        ->set('nombre', 'Ana')
        ->set('apellidos', 'Silva')
        ->set('email', 'ana@empresa.cl')
        ->set('password', 'password')
        ->set('razon_social', 'Empresa de Prueba SpA')
        ->set('telefono', '')
        ->set('acepta', true)
        ->call('submit')
        ->assertHasErrors(['telefono' => 'required']);
});
