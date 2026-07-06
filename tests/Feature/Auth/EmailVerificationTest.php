<?php

use App\Models\Empresa;
use App\Models\Postulante;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::emailVerification());
});

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('verification.notice'));

    $response->assertOk()
        ->assertSee('Confirma tu correo para activar tu cuenta')
        ->assertSee($user->email);
});

test('unverified users cannot access their account until confirming their email', function () {
    $user = User::factory()->unverified()->create(['role' => 'postulante']);

    $this->actingAs($user)
        ->get(route('postulante.panel'))
        ->assertRedirect(route('verification.notice'));
});

test('email can be verified', function () {
    $user = User::factory()->unverified()->create(['role' => 'postulante']);

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('postulante.panel', ['verified' => 1]));
});

test('a newly verified postulante is redirected to onboarding', function () {
    $user = User::factory()->unverified()->create(['role' => 'postulante']);
    Postulante::query()->create([
        'user_id' => $user->id,
        'onboarding_paso' => 1,
        'onboarding_completado' => false,
    ]);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $this->actingAs($user)
        ->get($verificationUrl)
        ->assertRedirect(route('postulante.ficha', ['verified' => 1]));
});

test('a newly verified empresa is redirected to its activation panel', function () {
    $user = User::factory()->unverified()->create(['role' => 'empresa']);
    Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => 'Empresa de Prueba SpA',
        'estado_activacion' => 'inactiva',
    ]);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $this->actingAs($user)
        ->get($verificationUrl)
        ->assertRedirect(route('empresa.activacion', ['verified' => 1]));
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')],
    );

    $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('already verified user visiting verification link is redirected without firing event again', function () {
    $user = User::factory()->create([
        'role' => 'postulante',
        'email_verified_at' => now(),
    ]);

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $this->actingAs($user)->get($verificationUrl)
        ->assertRedirect(route('postulante.panel', ['verified' => 1]));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertNotDispatched(Verified::class);
});
