<?php

use App\Livewire\Admin\Empresas as AdminEmpresas;
use App\Livewire\Admin\Postulantes as AdminPostulantes;
use App\Livewire\Admin\Usuarios as AdminUsuarios;
use App\Models\Empresa;
use App\Models\Postulante;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('an admin marks a stuck account as verified', function () {
    Event::fake([Verified::class]);

    $admin = User::factory()->create(['role' => 'admin']);
    $atascado = User::factory()->unverified()->create(['name' => 'Marta Rojas']);
    Postulante::query()->create(['user_id' => $atascado->id]);

    Livewire::actingAs($admin)
        ->test(AdminPostulantes::class)
        ->call('marcarVerificada', $atascado->id);

    expect($atascado->fresh()->hasVerifiedEmail())->toBeTrue();

    // markEmailAsVerified() y no un update: de este evento cuelga cualquier reacción.
    Event::assertDispatched(Verified::class);
});

test('an admin resends the verification email without verifying the account', function () {
    Notification::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    $atascado = User::factory()->unverified()->create();
    Postulante::query()->create(['user_id' => $atascado->id]);

    Livewire::actingAs($admin)
        ->test(AdminPostulantes::class)
        ->call('reenviarVerificacion', $atascado->id);

    Notification::assertSentTo($atascado, VerifyEmail::class);

    // Reenviar no verifica: la persona todavía tiene que hacer clic.
    expect($atascado->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('verifying an already verified account changes nothing and does not resend', function () {
    Notification::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    $verificado = User::factory()->create();
    Postulante::query()->create(['user_id' => $verificado->id]);
    $momentoOriginal = $verificado->email_verified_at;

    Livewire::actingAs($admin)
        ->test(AdminPostulantes::class)
        ->call('marcarVerificada', $verificado->id)
        ->call('reenviarVerificacion', $verificado->id);

    Notification::assertNothingSent();

    // No se repisa la fecha original: es el registro de cuándo se verificó de verdad.
    expect($verificado->fresh()->email_verified_at->timestamp)->toBe($momentoOriginal->timestamp);
});

test('the superadmin can verify accounts from the users screen too', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $atascado = User::factory()->unverified()->create();

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('marcarVerificada', $atascado->id);

    expect($atascado->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('an admin verifies a company account from the empresas screen', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $dueno = User::factory()->unverified()->create(['role' => 'empresa']);
    Empresa::query()->create([
        'user_id' => $dueno->id,
        'razon_social' => 'Constructora Sur SpA',
        'estado_activacion' => 'inactiva',
    ]);

    Livewire::actingAs($admin)
        ->test(AdminEmpresas::class)
        ->call('marcarVerificada', $dueno->id);

    expect($dueno->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('a postulante cannot verify accounts by calling the action directly', function () {
    $intruso = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create(['user_id' => $intruso->id, 'onboarding_completado' => true]);
    $victima = User::factory()->unverified()->create();

    Livewire::actingAs($intruso)
        ->test(AdminPostulantes::class)
        ->assertForbidden();

    expect($victima->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('the postulantes list can be filtered down to the unverified accounts', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $sinVerificar = User::factory()->unverified()->create(['name' => 'Marta Rojas']);
    Postulante::query()->create(['user_id' => $sinVerificar->id]);

    $verificado = User::factory()->create(['name' => 'Carlos Vega']);
    Postulante::query()->create(['user_id' => $verificado->id]);

    Livewire::actingAs($admin)
        ->test(AdminPostulantes::class)
        ->assertSee('Marta Rojas')
        ->assertSee('Carlos Vega')
        ->set('verificacion', 'pendientes')
        ->assertSee('Marta Rojas')
        ->assertDontSee('Carlos Vega');
});

test('the users list can be filtered down to the unverified accounts', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    User::factory()->unverified()->create(['name' => 'Marta Rojas']);
    User::factory()->create(['name' => 'Carlos Vega']);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->set('verificacion', 'pendientes')
        ->assertSee('Marta Rojas')
        ->assertDontSee('Carlos Vega');
});

/**
 * Las acciones de la tabla son iconos sin texto visible. Lo que las nombra es el
 * aria-label, y es justo lo que se pierde sin que nada se vea roto: la pantalla sigue
 * pintándose igual mientras un lector de pantalla anuncia "botón" a secas.
 */
test('the icon-only row actions keep an accessible name', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin', 'name' => 'Jose Puebla']);
    $atascado = User::factory()->unverified()->create(['name' => 'Marta Rojas']);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->assertSeeHtml('aria-label="Reenviar el correo de verificación a Marta Rojas"')
        ->assertSeeHtml('aria-label="Marcar la cuenta de Marta Rojas como verificada"')
        ->assertSeeHtml('aria-label="Cambiar el tipo de usuario de Marta Rojas"')
        // La propia cuenta no se puede degradar: el candado también se anuncia.
        ->assertSeeHtml('aria-label="No puedes cambiar el tipo de tu propia cuenta"');
});

test('a verified account shows no verification actions at all', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    User::factory()->create(['name' => 'Carlos Vega']);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->assertSee('Carlos Vega')
        ->assertDontSeeHtml('aria-label="Reenviar el correo de verificación a Carlos Vega"')
        ->assertDontSeeHtml('aria-label="Marcar la cuenta de Carlos Vega como verificada"');
});
