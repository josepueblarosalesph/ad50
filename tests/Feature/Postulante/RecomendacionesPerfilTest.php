<?php

use App\Livewire\Postulante\Ficha;
use App\Models\Postulante;
use App\Models\User;
use App\Services\CompletitudPerfil;
use Livewire\Livewire;

/** Postulante que terminó el asistente llenando solo lo obligatorio. */
function postulanteQueSaltoLoOpcional(): User
{
    $user = User::factory()->create(['role' => 'postulante']);

    Postulante::query()->create([
        'user_id' => $user->id,
        ...fichaMinimaDelAsistente(),
        'onboarding_completado' => true,
        'onboarding_paso' => 6,
        'visible' => true,
    ]);

    return $user->fresh();
}

test('la completitud sale de los datos guardados y no del paso del asistente', function () {
    $user = postulanteQueSaltoLoOpcional();
    $postulante = $user->postulante;

    expect(CompletitudPerfil::porcentaje($postulante))->toBe(55);

    $postulante->update(fichaOpcionalCompleta());

    expect(CompletitudPerfil::porcentaje($postulante->fresh()))->toBe(100);
});

test('una ficha vacía no llega al mínimo y reporta lo obligatorio como pendiente', function () {
    $user = User::factory()->create(['role' => 'postulante']);
    $postulante = Postulante::query()->create(['user_id' => $user->id]);

    expect(CompletitudPerfil::porcentaje($postulante))->toBe(0);

    $obligatorias = collect(CompletitudPerfil::pendientes($postulante))
        ->filter->obligatoria
        ->pluck('clave');

    expect($obligatorias->all())->toBe(['experiencia', 'datos', 'educacion', 'titular']);
});

test('las recomendaciones pendientes vienen ordenadas por lo que más aportan', function () {
    $pendientes = CompletitudPerfil::pendientes(postulanteQueSaltoLoOpcional()->postulante);

    expect(collect($pendientes)->pluck('clave')->all())
        ->toBe(['resumen', 'habilidades', 'industrias', 'curriculum', 'idiomas', 'regiones', 'linkedin', 'modalidad', 'situacion_laboral', 'expectativa_renta'])
        ->and(collect($pendientes)->sum('peso'))->toBe(45);
});

test('el aviso de Oportunidades muestra solo las tres recomendaciones que más suman', function () {
    $user = postulanteQueSaltoLoOpcional();

    $this->actingAs($user)->get(route('postulante.busquedas'))
        ->assertOk()
        ->assertSee('Tu perfil está al 55%')
        ->assertSee('Escribe tu presentación profesional')
        ->assertSee('Selecciona tus habilidades')
        ->assertSee('Indica tus industrias de interés')
        // La cuarta ya no cabe en el aviso compacto: se ve entrando a la ficha.
        ->assertDontSee('Sube tu currículum en PDF')
        ->assertSee('Completar mi perfil');
});

test('el aviso desaparece cuando el perfil llega al 100%', function () {
    $user = postulanteQueSaltoLoOpcional();
    $user->postulante->update(fichaOpcionalCompleta());

    $this->actingAs($user->fresh())->get(route('postulante.busquedas'))
        ->assertOk()
        ->assertDontSee('Completar mi perfil')
        ->assertDontSee('Escribe tu presentación profesional');
});

test('la ficha lista todo lo que falta y cada recomendación abre su sección', function () {
    Livewire::actingAs(postulanteQueSaltoLoOpcional())
        ->test(Ficha::class)
        ->assertSet('completitud', 55)
        ->assertSee('Recomendaciones para destacar')
        ->assertSee('Escribe tu presentación profesional')
        ->assertSee('Sube tu currículum en PDF')
        ->assertSee('Agrega los idiomas que manejas')
        ->call('editarSeccion', 'curriculum')
        ->assertSet('seccionEditando', 'curriculum');
});

test('la ficha no muestra recomendaciones durante el asistente de bienvenida', function () {
    $user = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create([
        'user_id' => $user->id,
        'onboarding_paso' => 2,
        'onboarding_completado' => false,
    ]);

    Livewire::actingAs($user->fresh())
        ->test(Ficha::class)
        ->assertSet('modoOnboarding', true)
        ->assertDontSee('Recomendaciones para destacar');
});

test('terminar el asistente ya no deja el perfil marcado como 100% completo', function () {
    $user = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create([
        'user_id' => $user->id,
        ...fichaMinimaDelAsistente(),
        'onboarding_paso' => 6,
        'onboarding_completado' => false,
    ]);

    Livewire::actingAs($user->fresh())
        ->test(Ficha::class)
        ->call('omitir')
        ->assertRedirect(route('postulante.busquedas'));

    $this->assertDatabaseHas('postulantes', [
        'user_id' => $user->id,
        'onboarding_completado' => true,
        'completitud' => 55,
    ]);
});

test('guardar una sección desde el editor actualiza la completitud persistida', function () {
    $user = postulanteQueSaltoLoOpcional();

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->call('editarSeccion', 'acerca')
        ->set('resumenProfesional', 'Veinte años liderando equipos financieros en banca.')
        ->set('habilidades', ['Liderazgo'])
        ->set('industriasInteres', ['Banca y servicios financieros'])
        ->call('guardarSeccion', 'acerca')
        ->assertHasNoErrors()
        // 55 + resumen (8) + habilidades (6) + industrias (6)
        ->assertSet('completitud', 75);

    $this->assertDatabaseHas('postulantes', [
        'user_id' => $user->id,
        'completitud' => 75,
    ]);
});
