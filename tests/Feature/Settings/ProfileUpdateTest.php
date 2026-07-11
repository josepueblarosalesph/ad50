<?php

use App\Models\Postulante;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
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

test('the profile page shows account, password and visibility sections for a postulante', function () {
    $user = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->assertSee('Datos de la cuenta')
        ->assertSee('Contraseña')
        ->assertSee('Visibilidad del perfil')
        ->assertSee('Eliminar cuenta');
});

test('a non-postulante does not see the visibility section', function () {
    $user = User::factory()->create(['role' => 'empresa']);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->assertSee('Contraseña')
        ->assertDontSee('Visibilidad del perfil');
});

test('password can be updated from the profile page', function () {
    $user = User::factory()->create(['password' => Hash::make('password')]);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('current_password', 'password')
        ->set('password', 'new-password-123')
        ->set('password_confirmation', 'new-password-123')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(Hash::check('new-password-123', $user->refresh()->password))->toBeTrue();
});

test('the current password must be correct to change it from the profile page', function () {
    $user = User::factory()->create(['password' => Hash::make('password')]);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('current_password', 'wrong-password')
        ->set('password', 'new-password-123')
        ->set('password_confirmation', 'new-password-123')
        ->call('updatePassword')
        ->assertHasErrors('current_password');

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
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
        ->assertRedirect('/');

    expect($user->fresh())->toBeNull();
    expect(auth()->check())->toBeFalse();
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
