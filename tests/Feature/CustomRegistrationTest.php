<?php

use App\Livewire\Auth\Register;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
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
        ->assertRedirect(route('verification.notice'));

    $user = User::query()->where('email', 'maria@example.com')->firstOrFail();

    expect($user->role)->toBe('postulante')
        ->and($user->acepta_ley_21719)->toBeTrue();

    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('postulantes', ['user_id' => $user->id]);
    Notification::assertSentTo($user, VerifyEmail::class);
});

test('an empresa can create an account', function () {
    Notification::fake();

    $registration = file_get_contents(resource_path('views/livewire/auth/register.blade.php'));

    expect(strpos($registration, 'wire:model="nombre"'))->toBeLessThan(strpos($registration, 'wire:model="apellidos"'))
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
        ->set('telefono', '+56 9 8765 4321')
        ->set('acepta', true)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('verification.notice'));

    $user = User::query()->where('email', 'ana@empresa.cl')->firstOrFail();

    expect($user->role)->toBe('empresa')
        ->and($user->acepta_ley_21719)->toBeTrue();

    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('empresas', [
        'user_id' => $user->id,
        'razon_social' => 'Empresa de Prueba SpA',
        'telefono' => '+56 9 8765 4321',
        'estado_activacion' => 'inactiva',
    ]);
    Notification::assertSentTo($user, VerifyEmail::class);
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
