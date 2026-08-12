<?php

use App\Livewire\Postulante\Busquedas;
use App\Livewire\Postulante\Ficha;
use App\Models\Postulante;
use App\Models\User;
use App\Services\CompletitudPerfil;
use Livewire\Livewire;

// La funcionalidad va apagada por omisión (ver config/ad50.php). Estos tests describen
// cómo se comporta publicada, así que la encienden; los del final cubren el apagado.
beforeEach(fn () => config()->set('ad50.funcionalidades.recomendaciones_perfil', true));

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

test('cerrar el aviso lo oculta y anota el porcentaje que tenía en ese momento', function () {
    $user = postulanteQueSaltoLoOpcional();

    Livewire::actingAs($user)
        ->test(Busquedas::class)
        ->assertSee('Escribe tu presentación profesional')
        ->call('ocultarRecomendaciones')
        ->assertDontSee('Escribe tu presentación profesional')
        ->assertDontSee('Completar mi perfil');

    expect($user->postulante->fresh()->recomendaciones_ocultas_hasta)->toBe(55);

    // Y sigue oculto al volver a entrar.
    $this->actingAs($user->fresh())->get(route('postulante.busquedas'))
        ->assertOk()
        ->assertDontSee('Escribe tu presentación profesional');
});

test('el aviso cerrado vuelve a aparecer en cuanto la persona completa algo', function () {
    $user = postulanteQueSaltoLoOpcional();
    $postulante = $user->postulante;

    Livewire::actingAs($user)->test(Busquedas::class)->call('ocultarRecomendaciones');

    expect($postulante->fresh()->recomendaciones_ocultas_hasta)->toBe(55);

    // Agrega su presentación: 55 % -> 63 %, así que el aviso reaparece con lo que queda.
    $postulante->update(['resumen_profesional' => 'Veinte años liderando equipos financieros.']);

    Livewire::actingAs($user->fresh())
        ->test(Busquedas::class)
        ->assertSee('Tu perfil está al 63%')
        ->assertSee('Selecciona tus habilidades')
        // Lo que ya completó deja de aparecer.
        ->assertDontSee('Escribe tu presentación profesional');
});

test('borrar información no hace reaparecer el aviso cerrado', function () {
    $user = postulanteQueSaltoLoOpcional();
    $postulante = $user->postulante;
    $postulante->update(['resumen_profesional' => 'Veinte años liderando equipos financieros.']);

    Livewire::actingAs($user->fresh())->test(Busquedas::class)->call('ocultarRecomendaciones');
    expect($postulante->fresh()->recomendaciones_ocultas_hasta)->toBe(63);

    // Quitar el resumen baja a 55 %: no completó nada, así que el aviso sigue cerrado.
    $postulante->update(['resumen_profesional' => null]);

    Livewire::actingAs($user->fresh())
        ->test(Busquedas::class)
        ->assertDontSee('Completar mi perfil');
});

test('cerrar en una pantalla las oculta en las dos', function () {
    $user = postulanteQueSaltoLoOpcional();

    // Cerrar es una sola decisión: no tiene sentido pedirla dos veces.
    Livewire::actingAs($user)->test(Busquedas::class)->call('ocultarRecomendaciones');

    Livewire::actingAs($user->fresh())
        ->test(Ficha::class)
        ->assertDontSee('Recomendaciones para destacar')
        ->assertDontSee('Escribe tu presentación profesional');
});

test('la tarjeta de la ficha también se puede cerrar con su propia X', function () {
    $user = postulanteQueSaltoLoOpcional();

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->assertSee('Recomendaciones para destacar')
        ->call('ocultarRecomendaciones')
        ->assertDontSee('Recomendaciones para destacar');

    expect($user->postulante->fresh()->recomendaciones_ocultas_hasta)->toBe(55);

    // Y el aviso de Oportunidades queda cerrado también.
    Livewire::actingAs($user->fresh())
        ->test(Busquedas::class)
        ->assertDontSee('Completar mi perfil');
});

test('cerradas desde la ficha, vuelven en las dos pantallas al completar algo', function () {
    $user = postulanteQueSaltoLoOpcional();

    Livewire::actingAs($user)->test(Ficha::class)->call('ocultarRecomendaciones');
    $user->postulante->update(['resumen_profesional' => 'Veinte años liderando equipos financieros.']);

    Livewire::actingAs($user->fresh())
        ->test(Ficha::class)
        ->assertSee('Recomendaciones para destacar');

    Livewire::actingAs($user->fresh())
        ->test(Busquedas::class)
        ->assertSee('Tu perfil está al 63%');
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

test('con la funcionalidad apagada no se ven las recomendaciones en ninguna pantalla', function () {
    config()->set('ad50.funcionalidades.recomendaciones_perfil', false);

    $user = postulanteQueSaltoLoOpcional();

    Livewire::actingAs($user)
        ->test(Busquedas::class)
        ->assertDontSee('Tu perfil está al')
        ->assertDontSee('Escribe tu presentación profesional')
        ->assertDontSee('Completar mi perfil');

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->assertDontSee('Recomendaciones para destacar')
        ->assertDontSee('Escribe tu presentación profesional');
});

test('con la funcionalidad apagada cerrar el aviso responde 404', function () {
    config()->set('ad50.funcionalidades.recomendaciones_perfil', false);

    $user = postulanteQueSaltoLoOpcional();

    Livewire::actingAs($user)
        ->test(Busquedas::class)
        ->call('ocultarRecomendaciones')
        ->assertStatus(404);

    expect($user->postulante->fresh()->recomendaciones_ocultas_hasta)->toBeNull();
});

test('apagar las recomendaciones no toca el cálculo de completitud', function () {
    config()->set('ad50.funcionalidades.recomendaciones_perfil', false);

    $user = postulanteQueSaltoLoOpcional();

    // Decisión explícita: se oculta el mensaje, no el porcentaje. La ficha sigue
    // valiendo 55 % y la píldora de Oportunidades se sigue viendo.
    expect(CompletitudPerfil::porcentaje($user->postulante))->toBe(55);

    Livewire::actingAs($user)
        ->test(Busquedas::class)
        ->assertViewHas('completitud', 55)
        ->assertSee('55%');
});

test('el encabezado de la pantalla de entrada dice "Oportunidades Laborales"', function () {
    $this->actingAs(postulanteQueSaltoLoOpcional())
        ->get(route('postulante.busquedas'))
        ->assertOk()
        ->assertSee('Oportunidades Laborales');
});
