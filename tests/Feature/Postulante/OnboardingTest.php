<?php

use App\Livewire\Postulante\Busquedas;
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
        ->get(route('postulante.busquedas'))
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
        ->assertSee('RUT / Pasaporte')
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
                'nivel' => 'Título Profesional',
                'pais' => 'Chile',
                'institucion' => 'Universidad de Prueba',
                'carrera' => 'Ingeniería Civil / Ingeniería Comercial',
                'mencion' => null,
                'modalidad' => 'Presencial',
                'situacion' => 'Titulado/a',
                'inicio_anio' => 1990,
                'termino_anio' => 1996,
                'egreso_anio' => null,
            ],
            [
                // Estudiando: no se exige año de término.
                'nivel' => 'Magíster',
                'pais' => 'Chile',
                'institucion' => 'Universidad de Prueba',
                'carrera' => 'Ingeniería Civil / Ingeniería Comercial',
                'mencion' => null,
                'modalidad' => 'Online',
                'situacion' => 'Estudiando',
                'inicio_anio' => 2024,
                'termino_anio' => null,
                'egreso_anio' => null,
            ],
        ])
        ->call('avanzar')
        ->assertHasNoErrors()
        ->assertSet('pasoActual', 5);
});

// El test "egreso is still required for a school level when not studying" se eliminó:
// los niveles escolares (básica, media, técnico medio) ya no son una opción del catálogo,
// así que la rama que exigía año de egreso quedó inalcanzable. Ver CatalogosProfesionales
// ::nivelesEscolares(), que ahora devuelve una lista vacía a propósito.

test('a postulante can skip the curriculum and enter the panel', function () {
    $user = postulanteEnOnboarding(6);

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->assertSet('pasoActual', 6)
        ->assertSee('Completar después')
        ->call('omitir')
        ->assertRedirect(route('postulante.busquedas'));

    $this->assertDatabaseHas('postulantes', [
        'user_id' => $user->id,
        'onboarding_paso' => 6,
        'onboarding_completado' => true,
    ]);

    $this->actingAs($user->fresh())
        ->get(route('postulante.busquedas'))
        ->assertOk();
});

test('the "acerca de mí" step requires a titular before advancing', function () {
    $user = postulanteEnOnboarding(2);

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->assertSet('pasoActual', 2)
        ->call('avanzar')
        ->assertHasErrors(['titular' => 'required'])
        ->assertSet('pasoActual', 2)
        ->set('titular', 'Gerenta de Finanzas')
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

test('el panel muestra la completitud junto a la visibilidad y la esconde al llegar al 100%', function () {
    $user = User::factory()->create(['role' => 'postulante']);
    // Ficha recién salida del asistente: lo obligatorio lleno y lo opcional saltado.
    $postulante = Postulante::query()->create([
        'user_id' => $user->id,
        ...fichaMinimaDelAsistente(),
        'onboarding_completado' => true,
        'onboarding_paso' => 6,
        'visible' => true,
    ]);

    // Con el perfil incompleto: indicador compacto en la cabecera, sin la tarjeta antigua.
    $this->actingAs($user)->get(route('postulante.busquedas'))
        ->assertOk()
        ->assertSee('title="Completa tu perfil para llegar al 100%"', false)
        ->assertSee('55%')
        ->assertSee('Visible para reclutadores')
        ->assertDontSee('Completitud del perfil');

    $postulante->update(fichaOpcionalCompleta());

    // Al 100% no queda rastro de la barra. Se autentica una instancia nueva: la anterior
    // ya trae cargada la relación `postulante` con el valor viejo.
    $this->actingAs($user->fresh())->get(route('postulante.busquedas'))
        ->assertOk()
        ->assertDontSee('title="Completa tu perfil para llegar al 100%"', false)
        ->assertSee('Visible para reclutadores');
});

test('el postulante entra a Oportunidades y desde ahí controla su visibilidad', function () {
    $user = User::factory()->create(['role' => 'postulante']);
    $postulante = Postulante::query()->create([
        'user_id' => $user->id,
        'completitud' => 100,
        'onboarding_completado' => true,
        'onboarding_paso' => 6,
        'visible' => true,
    ]);

    expect($user->fresh()->dashboardRouteName())->toBe('postulante.busquedas');

    // La URL del panel antiguo ya no tiene pantalla propia: redirige a Oportunidades.
    $this->actingAs($user)->get('/postulante')->assertRedirect('/postulante/busquedas');

    // El menú superior parte por Oportunidades y ya no ofrece "Mi panel".
    $this->actingAs($user)->get(route('postulante.busquedas'))
        ->assertOk()
        ->assertSee('Oportunidades')
        ->assertSee('Mis postulaciones')
        ->assertSee('Mi perfil')
        ->assertDontSee('Mi panel');

    // El interruptor de visibilidad se mudó aquí y sigue funcionando.
    Livewire::actingAs($user)
        ->test(Busquedas::class)
        ->assertSee('Visible para reclutadores')
        ->call('toggleVisibilidad')
        ->assertSee('Perfil pausado');

    expect($postulante->fresh()->visible)->toBeFalse();
});

/** Una experiencia válida, con las responsabilidades a elección. */
function experienciaValida(string $cargo = 'Gerente Finanza', ?string $responsabilidades = null): array
{
    return [
        'cargo' => $cargo,
        'cargo_otro' => '',
        'tipo_trabajo' => 'Jornada completa',
        'empresa' => 'Codelco',
        'empresa_otro' => '',
        'jerarquia' => 'Gerencia / Dirección',
        'actividad_empresa' => 'Minería',
        'inicio_mes' => 3,
        'inicio_anio' => now()->year - 10,
        'actualmente' => true,
        'fin_mes' => null,
        'fin_anio' => null,
        'responsabilidades' => $responsabilidades ?? '',
    ];
}

test('las responsabilidades del cargo son opcionales', function () {
    $user = postulanteEnOnboarding(3);

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->set('experiencias', [experienciaValida()])
        ->call('avanzar')
        ->assertHasNoErrors()
        ->assertSet('pasoActual', 4);

    expect($user->postulante->fresh()->experiencias[0]['responsabilidades'])->toBe('');
});

test('la primera experiencia se titula "Última Experiencia" y las demás van numeradas aparte', function () {
    $user = postulanteEnOnboarding(3);

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->set('experiencias', [experienciaValida(), experienciaValida('Contador'), experienciaValida('Abogado')])
        ->assertSee('Última Experiencia')
        ->assertSee('Experiencia Adicional 1')
        ->assertSee('Experiencia Adicional 2')
        // La numeración vieja arrancaba en 1 para la primera.
        ->assertDontSee('Experiencia 1');
});

test('la primera educación indica que se empiece por el título profesional', function () {
    $user = postulanteEnOnboarding(4);

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->assertSee('Educación (comience por su título profesional o equivalente)')
        ->assertDontSee('Educación 1');
});

test('los títulos nuevos también salen en el editor de "Mi perfil profesional"', function () {
    $user = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create([
        'user_id' => $user->id,
        ...fichaMinimaDelAsistente(),
        'onboarding_completado' => true,
        'onboarding_paso' => 6,
    ]);

    // El editor monta el formulario dentro del modal de la sección abierta.
    Livewire::actingAs($user->fresh())
        ->test(Ficha::class)
        ->call('editarSeccion', 'experiencia')
        ->assertSee('Última Experiencia')
        ->call('editarSeccion', 'educacion')
        ->assertSee('Educación (comience por su título profesional o equivalente)');
});
