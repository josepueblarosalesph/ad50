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
        ->set('nombres', 'María')
        ->set('apellidos', 'Fuentes')
        ->set('email', 'maria.onboarding@example.com')
        ->set('rut', '98421157')
        ->set('telefono', '+56 9 5555 1234')
        ->set('nacionalidad', 'Chilena')
        ->set('anioNacimiento', 1971)
        ->set('aniosExperiencia', 25)
        ->set('genero', 'Femenino')
        ->set('ciudad', 'Biobío')
        ->call('avanzar')
        ->assertHasNoErrors()
        ->assertSet('pasoActual', 2)
        // Paso 2: Acerca de mí, ahora una sección propia.
        ->set('titular', 'Gerenta de Finanzas')
        ->set('resumenProfesional', 'Experiencia liderando equipos financieros.')
        ->set('industriasInteres', ['Banca y servicios financieros'])
        ->set('modalidadesTrabajo', ['Jornada Parcial'])
        ->set('situacionLaboral', 'Buscando trabajo')
        ->set('expectativaRenta', 2500000)
        ->call('avanzar')
        ->assertHasNoErrors()
        ->assertSet('pasoActual', 3);

    // Las columnas json no admiten comparación por igualdad en PostgreSQL: se verifican vía el modelo.
    $this->assertDatabaseHas('postulantes', [
        'user_id' => $user->id,
        'rut' => '9.842.115-7',
        'genero' => 'Femenino',
        'nacionalidad' => 'Chilena',
        'ciudad' => 'Biobío',
        'situacion_laboral' => 'Buscando trabajo',
        'expectativa_renta' => 2500000,
        'anios_experiencia' => 25,
        'resumen_profesional' => 'Experiencia liderando equipos financieros.',
        'onboarding_paso' => 3,
        'onboarding_completado' => false,
    ]);
    expect($user->postulante->fresh())
        ->modalidad_trabajo->toBe(['Jornada Parcial'])
        ->industrias_interes->toBe(['Banca y servicios financieros']);

    Livewire::actingAs($user->fresh())
        ->test(Ficha::class)
        ->assertSet('pasoActual', 3)
        ->assertDontSee('Completar después');
});

test('a postulante can use a passport instead of RUT without formatting', function () {
    $user = postulanteEnOnboarding();

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->set('nombres', 'John')
        ->set('apellidos', 'Doe')
        ->set('email', 'john.doe@example.com')
        ->set('tipoDocumento', 'pasaporte')
        ->set('rut', 'AB1234567')
        ->set('telefono', '+56 9 5555 1234')
        ->set('titular', 'Consultor senior')
        ->set('industriasInteres', ['Minería'])
        ->set('nacionalidad', 'Otra')
        ->set('anioNacimiento', 1970)
        ->set('aniosExperiencia', 30)
        ->set('genero', 'Masculino')
        ->set('ciudad', 'Biobío')
        ->call('avanzar')
        ->assertHasNoErrors()
        ->assertSet('rut', 'AB1234567')
        ->assertSet('pasoActual', 2);

    $this->assertDatabaseHas('postulantes', [
        'user_id' => $user->id,
        'rut' => 'AB1234567',
        'tipo_documento' => 'pasaporte',
    ]);
});

test('education mención is optional and egreso is not required while studying', function () {
    $user = postulanteEnOnboarding(4);

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->assertSet('pasoActual', 4)
        ->set('educaciones', [
            [
                'nivel' => 'Media',
                'pais' => 'Chile',
                'institucion' => 'Liceo de Prueba',
                'carrera' => null,
                'mencion' => null,
                'modalidad' => null,
                'situacion' => 'Estudiando',
                'inicio_anio' => null,
                'termino_anio' => null,
                'egreso_anio' => null,
            ],
            [
                'nivel' => 'Universitaria',
                'pais' => 'Chile',
                'institucion' => 'Universidad de Prueba',
                'carrera' => 'Ingeniería Civil / Ingeniería Comercial',
                'mencion' => null,
                'modalidad' => 'Presencial',
                'situacion' => 'Titulado',
                'inicio_anio' => 1990,
                'termino_anio' => 1996,
                'egreso_anio' => null,
            ],
        ])
        ->call('avanzar')
        ->assertHasNoErrors()
        ->assertSet('pasoActual', 5);
});

test('egreso is still required for a school level when not studying', function () {
    $user = postulanteEnOnboarding(4);

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->set('educaciones', [
            [
                'nivel' => 'Media',
                'pais' => 'Chile',
                'institucion' => 'Liceo de Prueba',
                'carrera' => null,
                'mencion' => null,
                'modalidad' => null,
                'situacion' => 'Egresado',
                'inicio_anio' => null,
                'termino_anio' => null,
                'egreso_anio' => null,
            ],
        ])
        ->call('avanzar')
        ->assertHasErrors('educaciones.0.egreso_anio')
        ->assertSet('pasoActual', 4);
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

test('the "acerca de mí" step is optional and can be advanced empty', function () {
    $user = postulanteEnOnboarding(2);

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->assertSet('pasoActual', 2)
        ->call('avanzar')
        ->assertHasNoErrors()
        ->assertSet('pasoActual', 3);
});

test('the languages step is optional and can be advanced with no languages', function () {
    $user = postulanteEnOnboarding(5);

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->assertSet('pasoActual', 5)
        ->call('avanzar')
        ->assertHasNoErrors()
        ->assertSet('pasoActual', 6);

    expect($user->postulante->fresh()->idiomas)->toBe([]);
});

test('at least one experience is required to advance past the experience step', function () {
    $user = postulanteEnOnboarding(3);

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->set('experiencias', [])
        ->call('avanzar')
        ->assertHasErrors('experiencias')
        ->assertSet('pasoActual', 3);
});

test('at least one education entry is required to advance past the education step', function () {
    $user = postulanteEnOnboarding(4);

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->set('educaciones', [])
        ->call('avanzar')
        ->assertHasErrors('educaciones')
        ->assertSet('pasoActual', 4);
});
