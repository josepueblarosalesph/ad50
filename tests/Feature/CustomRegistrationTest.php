<?php

use App\Livewire\Auth\Register;
use App\Models\Empresa;
use App\Models\Postulante;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
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

    // El mensaje nombra la empresa y a quién recurrir.
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
        ->toContain('admin@ejemplo.cl')
        ->toContain('Equipo');

    expect(User::query()->where('email', 'ana@ejemplo.cl')->exists())->toBeFalse();
});

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
