<?php

use App\Livewire\Postulante\Ficha;
use App\Models\Postulante;
use App\Models\User;
use Livewire\Livewire;

function postulanteEnOnboarding(int $paso = 1): User
{
    $user = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create([
        'user_id' => $user->id,
        'completitud' => 10,
        'onboarding_paso' => $paso,
        'onboarding_completado' => false,
    ]);

    return $user;
}

test('a new postulante cannot access the panel before completing onboarding', function () {
    $user = postulanteEnOnboarding();

    $this->actingAs($user)
        ->get(route('postulante.panel'))
        ->assertRedirect(route('postulante.ficha'));

    $this->actingAs($user)
        ->get(route('postulante.busquedas'))
        ->assertRedirect(route('postulante.ficha'));

    expect($user->dashboardRouteName())->toBe('postulante.ficha');
});

test('the onboarding saves personal data and resumes from the persisted step', function () {
    $user = postulanteEnOnboarding();

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->assertSet('modoOnboarding', true)
        ->assertSet('pasoActual', 1)
        ->assertSee('Paso 1 de 6')
        ->assertSee('Guardar y continuar')
        ->set('name', 'María Fuentes')
        ->set('email', 'maria.onboarding@example.com')
        ->set('rut', '98421157')
        ->set('anioNacimiento', 1971)
        ->set('genero', 'Femenino')
        ->set('ciudad', 'Concepción')
        ->call('avanzar')
        ->assertHasNoErrors()
        ->assertSet('pasoActual', 2);

    $this->assertDatabaseHas('postulantes', [
        'user_id' => $user->id,
        'rut' => '9.842.115-7',
        'genero' => 'Femenino',
        'ciudad' => 'Concepción',
        'onboarding_paso' => 2,
        'onboarding_completado' => false,
    ]);

    Livewire::actingAs($user->fresh())
        ->test(Ficha::class)
        ->assertSet('pasoActual', 2)
        ->assertSee('Completar después')
        ->call('omitir')
        ->assertSet('pasoActual', 3)
        ->call('omitir')
        ->assertSet('pasoActual', 3);
});

test('a postulante can skip the curriculum and enter the panel', function () {
    $user = postulanteEnOnboarding(6);

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->assertSet('pasoActual', 6)
        ->assertSee('Completar después')
        ->call('omitir')
        ->assertRedirect(route('postulante.panel'));

    $this->assertDatabaseHas('postulantes', [
        'user_id' => $user->id,
        'onboarding_paso' => 6,
        'onboarding_completado' => true,
    ]);

    $this->actingAs($user->fresh())
        ->get(route('postulante.panel'))
        ->assertOk();
});
