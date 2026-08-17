<?php

use App\Models\Postulante;
use App\Models\User;
use Livewire\Livewire;

test('profile page is displayed', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get(route('profile.edit'))->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when email address is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('Mi cuenta muestra datos, acceso a seguridad y visibilidad para un postulante', function () {
    $user = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->assertSee('Datos de la cuenta')
        ->assertSee('Contraseña y seguridad')
        ->assertSee('Visibilidad del perfil')
        ->assertSee('Eliminar cuenta');
});

test('a non-postulante does not see the visibility section', function () {
    $user = User::factory()->create(['role' => 'empresa']);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->assertSee('Datos de la cuenta')
        ->assertDontSee('Visibilidad del perfil');
});

test('Mi cuenta no ofrece el formulario de contraseña, solo el acceso a Seguridad', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    // El cambio de contraseña vive en Configuración → Seguridad (ver SecurityTest).
    Livewire::test('pages::settings.profile')
        ->assertSee('Contraseña y seguridad')
        ->assertSee('Ir a Seguridad')
        ->assertDontSee('Contraseña actual')
        ->assertDontSee('Nueva contraseña');
});

test('a postulante can toggle profile visibility from settings', function () {
    $user = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create(['user_id' => $user->id, 'visible' => true]);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->assertSet('visible', true)
        ->set('visible', false);

    expect($user->postulante->fresh()->visible)->toBeFalse();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'password')
        ->call('deleteUser');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('home'));

    expect($user->fresh())->toBeNull();
    expect(auth()->check())->toBeFalse();
    expect(session('cuenta_eliminada'))->toBe('Tus datos han sido eliminados exitosamente.');
});

test('the landing greets the deleted account with a confirmation notice', function () {
    // El aviso tiene que sobrevivir a la invalidación de sesión que hace el logout: es la
    // única constancia que recibe la persona de que sus datos ya no están.
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertRedirect(route('home'));

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Tus datos han sido eliminados exitosamente.');
});

test('the landing shows no deletion notice on an ordinary visit', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Tus datos han sido eliminados exitosamente.');
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $response->assertHasErrors(['password']);

    expect($user->fresh())->not->toBeNull();
});

test('Mi cuenta y Configuración son pantallas separadas', function () {
    $user = User::factory()->create();

    // "Mi cuenta": pantalla única. No lleva el menú lateral de Configuración, aunque sí
    // enlaza a Seguridad desde su tarjeta (y a Configuración desde el desplegable
    // superior): por eso se comprueba la ausencia del menú lateral, no la de los enlaces.
    $this->actingAs($user)->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Mi cuenta')
        ->assertDontSee('aria-label="Configuración"', false)
        ->assertDontSee('Contraseña actual');

    // "Configuración": menú lateral con Seguridad y Apariencia; ya no ofrece Perfil.
    $this->actingAs($user)->get(route('appearance.edit'))
        ->assertOk()
        ->assertSee('aria-label="Configuración"', false)
        ->assertSee('Seguridad')
        ->assertSee('Apariencia')
        ->assertSee('href="'.route('security.edit').'"', false)
        ->assertDontSee('>Perfil<', false);
});
