<?php

use App\Livewire\Postulante\Postulaciones;
use App\Models\Empresa;
use App\Models\Postulacion;
use App\Models\Postulante;
use App\Models\Publicacion;
use App\Models\User;
use Livewire\Livewire;

/**
 * Postulante con onboarding completo (el middleware lo exige para entrar al panel).
 *
 * @return array{0: User, 1: Postulante}
 */
function postulanteConFicha(): array
{
    $user = User::factory()->create(['role' => 'postulante']);
    $postulante = Postulante::query()->create([
        'user_id' => $user->id,
        'visible' => true,
        'onboarding_completado' => true,
    ]);

    return [$user->fresh(), $postulante];
}

/** Publicación de una empresa cualquiera. */
function publicacionDeAlguien(string $cargo): Publicacion
{
    $empresa = Empresa::query()->create([
        'user_id' => User::factory()->create(['role' => 'empresa'])->id,
        'razon_social' => 'Empresa '.fake()->unique()->numerify('####'),
        'estado_activacion' => 'activa',
    ]);

    return Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'cargo' => $cargo,
        'nombre_empresa' => $empresa->razon_social,
    ]);
}

test('el postulante ve sus postulaciones con el estado que les puso la empresa', function () {
    [$user, $postulante] = postulanteConFicha();

    Postulacion::query()->create([
        'publicacion_id' => publicacionDeAlguien('Jefe de Planta')->id,
        'postulante_id' => $postulante->id,
        'estado' => 'en_revision',
    ]);

    Livewire::actingAs($user)
        ->test(Postulaciones::class)
        ->assertViewHas('totalPostulaciones', 1)
        ->assertSee('Jefe de Planta')
        ->assertSee('En revisión');
});

test('no ve las postulaciones de otro postulante', function () {
    [$user] = postulanteConFicha();
    [, $otro] = postulanteConFicha();

    Postulacion::query()->create([
        'publicacion_id' => publicacionDeAlguien('Cargo ajeno')->id,
        'postulante_id' => $otro->id,
    ]);

    Livewire::actingAs($user)
        ->test(Postulaciones::class)
        ->assertViewHas('totalPostulaciones', 0)
        ->assertDontSee('Cargo ajeno')
        ->assertSee('Aún no has postulado');
});

test('el filtro por estado acota la lista y trae su conteo', function () {
    [$user, $postulante] = postulanteConFicha();

    foreach ([['Enviada uno', 'enviada'], ['Enviada dos', 'enviada'], ['Ya elegida', 'seleccionada']] as [$cargo, $estado]) {
        Postulacion::query()->create([
            'publicacion_id' => publicacionDeAlguien($cargo)->id,
            'postulante_id' => $postulante->id,
            'estado' => $estado,
        ]);
    }

    $componente = Livewire::actingAs($user)->test(Postulaciones::class);

    $componente
        ->assertViewHas('totalPostulaciones', 3)
        ->assertViewHas('conteoPorEstado', ['enviada' => 2, 'seleccionada' => 1]);

    $componente->call('mostrar', 'seleccionada')
        ->assertSet('estado', 'seleccionada')
        ->assertViewHas('postulaciones', fn ($p) => $p->total() === 1)
        ->assertSee('Ya elegida')
        ->assertDontSee('Enviada uno');

    $componente->call('mostrar', 'todas')
        ->assertViewHas('postulaciones', fn ($p) => $p->total() === 3);
});

test('un estado inválido no se acepta', function () {
    [$user] = postulanteConFicha();

    Livewire::actingAs($user)
        ->test(Postulaciones::class)
        ->call('mostrar', 'inventado')
        ->assertStatus(404);
});

test('un estado inválido en la URL cae de vuelta en todas', function () {
    [$user] = postulanteConFicha();

    Livewire::actingAs($user)
        ->withUrlParams(['estado' => 'inventado'])
        ->test(Postulaciones::class)
        ->assertSet('estado', 'todas');
});

test('la postulación sigue visible aunque la empresa retire la publicación', function () {
    [$user, $postulante] = postulanteConFicha();
    $publicacion = publicacionDeAlguien('Cargo retirado');

    Postulacion::query()->create([
        'publicacion_id' => $publicacion->id,
        'postulante_id' => $postulante->id,
    ]);

    $publicacion->delete();

    Livewire::actingAs($user)
        ->test(Postulaciones::class)
        ->assertViewHas('totalPostulaciones', 1)
        ->assertSee('Cargo retirado')
        ->assertSee('la empresa retiró esta publicación');
});

test('el menú del postulante ofrece Mis postulaciones', function () {
    [$user] = postulanteConFicha();

    $this->actingAs($user)
        ->get(route('postulante.postulaciones'))
        ->assertOk()
        ->assertSee('href="'.route('postulante.postulaciones').'"', false)
        ->assertSee('Mis postulaciones');

    // Y también desde el resto del panel.
    $this->actingAs($user)
        ->get(route('postulante.panel'))
        ->assertOk()
        ->assertSee('href="'.route('postulante.postulaciones').'"', false);
});

test('una empresa no puede entrar a las postulaciones del postulante', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);

    $this->actingAs($empresaUser)
        ->get(route('postulante.postulaciones'))
        ->assertForbidden();
});
