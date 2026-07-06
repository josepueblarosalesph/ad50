<?php

use App\Models\User;
use Laravel\Fortify\Features;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response
        ->assertOk()
        ->assertSeeText('Tu experiencia sigue en movimiento.')
        ->assertSeeText('Ingresar a mi cuenta')
        ->assertSee('/images/ad50-hero-experiencia-v2.webp', false)
        ->assertSee('/images/ad50-logo.png', false);
});

test('authenticated users are redirected from login to their role dashboard', function (string $role, string $dashboardRoute) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)
        ->get(route('login'))
        ->assertRedirect(route($dashboardRoute, absolute: false));
})->with([
    'postulante' => ['postulante', 'postulante.panel'],
    'empresa' => ['empresa', 'empresa.activacion'],
    'admin' => ['admin', 'admin.panel'],
]);

test('users are redirected to their role dashboard after authentication', function (string $role, string $dashboardRoute) {
    $user = User::factory()->create(['role' => $role]);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route($dashboardRoute, absolute: false));

    $this->assertAuthenticated();
})->with([
    'postulante' => ['postulante', 'postulante.panel'],
    'empresa' => ['empresa', 'empresa.activacion'],
    'admin' => ['admin', 'admin.panel'],
]);

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrorsIn('email');

    $this->assertGuest();
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});
