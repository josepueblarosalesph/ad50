<?php

use App\Livewire\Auth\Register;
use App\Models\User;
use Livewire\Livewire;

test('a postulante can create an account', function () {
    Livewire::test(Register::class)
        ->set('role', 'postulante')
        ->set('nombre', 'María')
        ->set('apellidos', 'Fuentes')
        ->set('email', 'maria@example.com')
        ->set('password', 'password')
        ->set('acepta', true)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('postulante.panel'));

    $user = User::query()->where('email', 'maria@example.com')->firstOrFail();

    expect($user->role)->toBe('postulante')
        ->and($user->acepta_ley_21719)->toBeTrue();

    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('postulantes', ['user_id' => $user->id]);
});

test('an empresa can create an account', function () {
    Livewire::test(Register::class)
        ->set('role', 'empresa')
        ->set('nombre', 'Ana')
        ->set('apellidos', 'Silva')
        ->set('email', 'ana@empresa.cl')
        ->set('password', 'password')
        ->set('razon_social', 'Empresa de Prueba SpA')
        ->set('acepta', true)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('empresa.panel'));

    $user = User::query()->where('email', 'ana@empresa.cl')->firstOrFail();

    expect($user->role)->toBe('empresa')
        ->and($user->acepta_ley_21719)->toBeTrue();

    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('empresas', [
        'user_id' => $user->id,
        'razon_social' => 'Empresa de Prueba SpA',
    ]);
});
