<?php

use App\Livewire\Empresa\FiltrosBusqueda;
use App\Livewire\Empresa\NuevaBusqueda;
use App\Livewire\Postulante\Ficha;
use App\Models\Empresa;
use App\Models\Plan;
use App\Models\Postulante;
use App\Models\User;
use App\Support\CatalogosProfesionales;
use Livewire\Livewire;

test('a structured search lists only candidates that fulfill every configured criterion', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    Empresa::query()->create(['user_id' => $empresaUser->id, 'razon_social' => 'Empresa Uno']);

    $completeUser = User::factory()->create(['role' => 'postulante']);
    $complete = Postulante::query()->create([
        'user_id' => $completeUser->id,
        'visible' => true,
        'ciudad' => 'Biobío',
        'regiones_interes' => ['Biobío'],
        'carrera' => 'Ingeniería Comercial',
        'especialidad' => 'Finanzas',
        'industrias_interes' => ['Banca y servicios financieros'],
        'anios_experiencia' => 18,
        'resumen_profesional' => 'Lideró una transformación financiera regional.',
        'experiencias' => [[
            'cargo' => 'Gerente Finanza', 'empresa' => 'Empresa A', 'area' => 'Gerente Finanza',
            'inicio' => 2008, 'fin' => 2026,
        ]],
    ]);

    $partialUser = User::factory()->create(['role' => 'postulante']);
    $partial = Postulante::query()->create([
        'user_id' => $partialUser->id,
        'visible' => true,
        'ciudad' => 'Metropolitana de Santiago',
        'carrera' => 'Ingeniería Comercial',
        'especialidad' => 'Finanzas',
        'industrias_interes' => ['Banca y servicios financieros'],
        'anios_experiencia' => 12,
        'experiencias' => [[
            'cargo' => 'Gerente Finanza', 'empresa' => 'Empresa B', 'area' => 'Gerente Finanza',
            'inicio' => 2014, 'fin' => 2026,
        ]],
    ]);

    Livewire::actingAs($empresaUser)
        ->test(NuevaBusqueda::class)
        ->set('titulo', 'Liderazgo financiero')
        ->set('cargo', ['Gerente Finanza'])
        ->set('carrera', ['Ingeniería Comercial'])
        ->set('especialidad', 'Finanzas')
        ->set('industria', ['Banca y servicios financieros'])
        ->set('ciudad', ['Biobío'])
        ->set('expMin', 15)
        ->set('palabrasClave', ['transformación'])
        ->call('save')
        ->assertHasNoErrors();

    $busqueda = $empresaUser->empresa->busquedas()->latest('id')->firstOrFail();
    $matches = $busqueda->candidatos()->orderByDesc('criterios_cumplidos')->get();

    expect($matches)->toHaveCount(1)
        ->and($matches->first()->postulante_id)->toBe($complete->id)
        ->and($matches->first()->estado_match)->toBe('cumple')
        ->and($matches->first()->criterios_cumplidos)->toBe(7)
        ->and($matches->first()->criterios_detalle)->toHaveCount(7)
        ->and($matches->contains('postulante_id', $partial->id))->toBeFalse();
});

test('a search with a skills criterion only matches candidates that have one of the skills', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    Empresa::query()->create(['user_id' => $empresaUser->id, 'razon_social' => 'Empresa Skills']);

    $conHabilidad = Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true,
        'habilidades' => ['Python', 'Liderazgo'],
    ]);

    $sinHabilidad = Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true,
        'habilidades' => ['Liderazgo'],
    ]);

    Livewire::actingAs($empresaUser)
        ->test(NuevaBusqueda::class)
        ->set('titulo', 'Perfil técnico')
        ->set('habilidad', ['Python'])
        ->call('save')
        ->assertHasNoErrors();

    $busqueda = $empresaUser->empresa->busquedas()->latest('id')->firstOrFail();
    $matches = $busqueda->candidatos()->get();

    expect($matches)->toHaveCount(1)
        ->and($matches->first()->postulante_id)->toBe($conHabilidad->id)
        ->and($matches->first()->estado_match)->toBe('cumple')
        ->and($matches->contains('postulante_id', $sinHabilidad->id))->toBeFalse();
});

test('the skills criterion is persisted and rejects values outside the catalog', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    Empresa::query()->create(['user_id' => $empresaUser->id, 'razon_social' => 'Empresa Cat']);

    Livewire::actingAs($empresaUser)
        ->test(NuevaBusqueda::class)
        ->set('titulo', 'Con habilidad inválida')
        ->set('habilidad', ['NoExisteEnCatalogo'])
        ->call('save')
        ->assertHasErrors('habilidad.0');

    Livewire::actingAs($empresaUser)
        ->test(NuevaBusqueda::class)
        ->set('titulo', 'Con habilidad válida')
        ->set('habilidad', ['Python'])
        ->call('save')
        ->assertHasNoErrors();

    $busqueda = $empresaUser->empresa->busquedas()->latest('id')->firstOrFail();

    expect($busqueda->criterios['habilidad'] ?? [])->toBe(['Python']);
});

test('the region criterion matches interest regions and treats "Nacional" as any chilean region', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    Empresa::query()->create(['user_id' => $empresaUser->id, 'razon_social' => 'Empresa Región']);

    $nacional = Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true, 'ciudad' => 'Los Lagos', 'regiones_interes' => ['Nacional'],
    ]);
    $biobio = Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true, 'ciudad' => 'Los Lagos', 'regiones_interes' => ['Biobío'],
    ]);
    $internacional = Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true, 'ciudad' => 'Los Lagos', 'regiones_interes' => ['Internacional'],
    ]);

    // Búsqueda por Biobío: calzan quien marcó Biobío y quien marcó Nacional; no el de solo Internacional.
    Livewire::actingAs($empresaUser)
        ->test(NuevaBusqueda::class)
        ->set('titulo', 'Región Biobío')
        ->set('ciudad', ['Biobío'])
        ->call('save')
        ->assertHasNoErrors();

    $ids = $empresaUser->empresa->busquedas()->latest('id')->firstOrFail()->candidatos()->pluck('postulante_id');
    expect($ids->all())->toContain($nacional->id)->toContain($biobio->id)
        ->and($ids->all())->not->toContain($internacional->id);

    // Búsqueda por Internacional: solo calza quien marcó Internacional (Nacional no cubre el extranjero).
    Livewire::actingAs($empresaUser)
        ->test(NuevaBusqueda::class)
        ->set('titulo', 'Región Internacional')
        ->set('ciudad', ['Internacional'])
        ->call('save')
        ->assertHasNoErrors();

    $ids = $empresaUser->empresa->busquedas()->latest('id')->firstOrFail()->candidatos()->pluck('postulante_id');
    expect($ids->all())->toContain($internacional->id)
        ->and($ids->all())->not->toContain($nacional->id)
        ->and($ids->all())->not->toContain($biobio->id);
});

test('new criteria (idioma, nivel de estudios, situación laboral, expectativa de renta) filter candidates', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    Empresa::query()->create(['user_id' => $empresaUser->id, 'razon_social' => 'Empresa Multi']);

    $calza = Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true,
        'situacion_laboral' => 'Buscando trabajo',
        'expectativa_renta' => 2000000,
        'idiomas' => [['idioma' => 'Inglés', 'nivel' => 'Avanzado']],
        'educaciones' => [['nivel' => 'Universitaria', 'situacion' => 'Titulado']],
    ]);

    $noCalza = Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true,
        'situacion_laboral' => 'Jubilado',
        'expectativa_renta' => 5000000,
        'idiomas' => [['idioma' => 'Francés', 'nivel' => 'Básico']],
        'educaciones' => [['nivel' => 'Media', 'situacion' => 'Egresado']],
    ]);

    Livewire::actingAs($empresaUser)
        ->test(NuevaBusqueda::class)
        ->set('titulo', 'Perfil exigente')
        ->set('situacionLaboral', ['Buscando trabajo'])
        ->set('idioma', ['Inglés'])
        ->set('nivelEstudios', ['Universitaria'])
        ->set('rentaMax', 3000000)
        ->call('save')
        ->assertHasNoErrors();

    $ids = $empresaUser->empresa->busquedas()->latest('id')->firstOrFail()->candidatos()->pluck('postulante_id');

    expect($ids->all())->toContain($calza->id)
        ->and($ids->all())->not->toContain($noCalza->id);
});

test('the experience criterion filters by a range (min and max)', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    Empresa::query()->create(['user_id' => $empresaUser->id, 'razon_social' => 'Empresa Rango']);

    $dentro = Postulante::query()->create(['user_id' => User::factory()->create(['role' => 'postulante'])->id, 'visible' => true, 'anios_experiencia' => 15]);
    $muyPoca = Postulante::query()->create(['user_id' => User::factory()->create(['role' => 'postulante'])->id, 'visible' => true, 'anios_experiencia' => 5]);
    $demasiada = Postulante::query()->create(['user_id' => User::factory()->create(['role' => 'postulante'])->id, 'visible' => true, 'anios_experiencia' => 30]);

    Livewire::actingAs($empresaUser)
        ->test(NuevaBusqueda::class)
        ->set('titulo', 'Entre 10 y 20 años')
        ->set('expMin', 10)
        ->set('expMax', 20)
        ->call('save')
        ->assertHasNoErrors();

    $busqueda = $empresaUser->empresa->busquedas()->latest('id')->firstOrFail();

    expect($busqueda->criterios['experiencia'])->toBe(['min' => 10, 'max' => 20]);

    $ids = $busqueda->candidatos()->pluck('postulante_id');
    expect($ids->all())->toContain($dentro->id)
        ->and($ids->all())->not->toContain($muyPoca->id)
        ->and($ids->all())->not->toContain($demasiada->id);
});

test('a postulante can add and remove multiple work experiences', function () {
    $user = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create(['user_id' => $user->id]);

    $component = Livewire::actingAs($user)->test(Ficha::class);

    $component->call('addExperiencia')->call('addExperiencia')->call('addExperiencia');

    expect($component->get('experiencias'))->toHaveCount(4);

    $component->call('removeExperiencia', 1);

    expect($component->get('experiencias'))->toHaveCount(3);
});

test('a postulante can add and remove multiple education entries', function () {
    $user = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create(['user_id' => $user->id]);

    $component = Livewire::actingAs($user)->test(Ficha::class);

    $component->call('addEducacion')->call('addEducacion');

    expect($component->get('educaciones'))->toHaveCount(3);

    $component->call('removeEducacion', 1);

    expect($component->get('educaciones'))->toHaveCount(2);
});

test('a postulante can add and remove multiple languages', function () {
    $user = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create(['user_id' => $user->id]);

    $component = Livewire::actingAs($user)->test(Ficha::class);

    $component->call('addIdioma')->call('addIdioma');

    expect($component->get('idiomas'))->toHaveCount(3);

    $component->call('removeIdioma', 1);

    expect($component->get('idiomas'))->toHaveCount(2);
});

test('unlocking a candidate reveals contact details and consumes a plan quota', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create([
        'user_id' => $empresaUser->id,
        'razon_social' => 'Empresa Activa',
        'estado_activacion' => 'activa',
        'plan_id' => Plan::query()->create([
            'codigo' => 'empresa_test', 'nombre' => 'Empresa Test', 'audiencia' => 'empresa', 'precio_clp' => 1, 'desbloqueos' => 2,
        ])->id,
        'plan_hasta' => now()->addMonth(),
    ]);
    $postulanteUser = User::factory()->create(['role' => 'postulante', 'email' => 'privado@example.com']);
    $postulante = Postulante::query()->create(['user_id' => $postulanteUser->id, 'visible' => true, 'rut' => '1-9', 'telefono' => '+56911111111']);
    $busqueda = $empresa->busquedas()->create(['titulo' => 'Búsqueda', 'criterios' => []]);
    $match = $busqueda->candidatos()->create([
        'postulante_id' => $postulante->id, 'estado_match' => 'cumple',
    ]);

    Livewire::actingAs($empresaUser)
        ->test(App\Livewire\Empresa\Candidato::class, ['match' => $match])
        ->assertDontSee('privado@example.com')
        ->assertSet('desbloqueosDisponibles', 2)
        ->call('desbloquear')
        ->assertSet('desbloqueado', true)
        ->assertSee('privado@example.com');

    expect($match->fresh()->contactado_at)->not->toBeNull()
        ->and($empresa->fresh()->desbloqueosDisponibles())->toBe(1);
});

test('unlocking is blocked when the plan has no available quota', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create([
        'user_id' => $empresaUser->id, 'razon_social' => 'Empresa Sin Cupo', 'estado_activacion' => 'activa',
        'plan_id' => Plan::query()->create(['codigo' => 'empresa_sin_cupo', 'nombre' => 'Sin cupo', 'audiencia' => 'empresa', 'precio_clp' => 1, 'desbloqueos' => 0])->id,
        'plan_hasta' => now()->addMonth(),
    ]);
    $postulante = Postulante::query()->create(['user_id' => User::factory()->create(['role' => 'postulante', 'email' => 'oculto@example.com'])->id, 'visible' => true]);
    $match = $empresa->busquedas()->create(['titulo' => 'B', 'criterios' => []])->candidatos()->create(['postulante_id' => $postulante->id, 'estado_match' => 'cumple']);

    Livewire::actingAs($empresaUser)
        ->test(App\Livewire\Empresa\Candidato::class, ['match' => $match])
        ->call('desbloquear')
        ->assertHasErrors('desbloqueo')
        ->assertSet('desbloqueado', false)
        ->assertDontSee('oculto@example.com');

    expect(App\Models\Desbloqueo::query()->count())->toBe(0);
});

test('multiple values in one criterion include candidates matching any selected value', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    Empresa::query()->create(['user_id' => $empresaUser->id, 'razon_social' => 'Empresa Exigente']);

    foreach (['Biobío', 'Metropolitana de Santiago', 'Los Ríos'] as $ciudad) {
        $user = User::factory()->create(['role' => 'postulante']);
        Postulante::query()->create([
            'user_id' => $user->id,
            'visible' => true,
            'ciudad' => $ciudad,
            'regiones_interes' => [$ciudad],
        ]);
    }

    Livewire::actingAs($empresaUser)
        ->test(NuevaBusqueda::class)
        ->set('titulo', 'Centro sur')
        ->set('ciudad', ['Biobío', 'Metropolitana de Santiago'])
        ->call('save')
        ->assertHasNoErrors();

    $busqueda = $empresaUser->empresa->busquedas()->sole();

    expect($busqueda->criterios['ciudad'])->toBe(['Biobío', 'Metropolitana de Santiago'])
        ->and($busqueda->candidatos)->toHaveCount(2)
        ->and($busqueda->candidatos->pluck('postulante.ciudad')->sort()->values()->all())->toBe(['Biobío', 'Metropolitana de Santiago']);
});

test('a company can edit a search and recalculate its existing results', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $empresaUser->id, 'razon_social' => 'Empresa Editora']);

    foreach (['Biobío', 'Metropolitana de Santiago'] as $ciudad) {
        $user = User::factory()->create(['role' => 'postulante']);
        Postulante::query()->create(['user_id' => $user->id, 'visible' => true, 'ciudad' => $ciudad, 'regiones_interes' => [$ciudad]]);
    }

    Livewire::actingAs($empresaUser)
        ->test(NuevaBusqueda::class)
        ->set('titulo', 'Búsqueda original')
        ->call('save');

    $busqueda = $empresa->busquedas()->sole();
    expect($busqueda->candidatos)->toHaveCount(2);
    $busqueda->candidatos()
        ->whereHas('postulante', fn ($query) => $query->where('ciudad', 'Metropolitana de Santiago'))
        ->sole()
        ->update(['favorito' => true]);

    Livewire::actingAs($empresaUser)
        ->test(NuevaBusqueda::class, ['busqueda' => $busqueda])
        ->assertSet('titulo', 'Búsqueda original')
        ->set('titulo', 'Búsqueda ajustada')
        ->set('ciudad', ['Metropolitana de Santiago'])
        ->call('save')
        ->assertHasNoErrors();

    expect($empresa->busquedas()->count())->toBe(1)
        ->and($busqueda->fresh()->titulo)->toBe('Búsqueda ajustada')
        ->and($busqueda->fresh()->criterios['ciudad'])->toBe(['Metropolitana de Santiago'])
        ->and($busqueda->fresh()->candidatos)->toHaveCount(1)
        ->and($busqueda->fresh()->candidatos->sole()->postulante->ciudad)->toBe('Metropolitana de Santiago')
        ->and($busqueda->fresh()->candidatos->sole()->favorito)->toBeTrue();
});

test('a company can modify search filters from the results sidebar', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $empresaUser->id, 'razon_social' => 'Empresa Lateral', 'estado_activacion' => 'activa']);

    foreach (['Biobío', 'Metropolitana de Santiago'] as $ciudad) {
        $user = User::factory()->create(['role' => 'postulante']);
        Postulante::query()->create(['user_id' => $user->id, 'visible' => true, 'ciudad' => $ciudad, 'regiones_interes' => [$ciudad]]);
    }

    Livewire::actingAs($empresaUser)
        ->test(NuevaBusqueda::class)
        ->set('titulo', 'Búsqueda editable')
        ->call('save');

    $busqueda = $empresa->busquedas()->sole();

    $this->actingAs($empresaUser)
        ->get(route('empresa.resultados', $busqueda))
        ->assertOk()
        ->assertSee('Filtros del proceso')
        ->assertSee('Los resultados se actualizan a medida que cambias los filtros.')
        ->assertSee('Institución de estudio')
        ->assertSee('Universidad de Concepción ( UDEC )')
        ->assertDontSee('Actualizar Filtro');

    Livewire::actingAs($empresaUser)
        ->test(FiltrosBusqueda::class, ['busqueda' => $busqueda])
        ->set('ciudad', ['Metropolitana de Santiago'])
        ->assertHasNoErrors();

    expect($busqueda->fresh()->criterios['ciudad'])->toBe(['Metropolitana de Santiago'])
        ->and($busqueda->fresh()->candidatos)->toHaveCount(1)
        ->and($busqueda->fresh()->candidatos->sole()->postulante->ciudad)->toBe('Metropolitana de Santiago');
});

test('the institution and company criteria match a fragment of any of their records', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $empresaUser->id, 'razon_social' => 'Empresa Uno']);

    $calzaUser = User::factory()->create(['role' => 'postulante']);
    $calza = Postulante::query()->create([
        'user_id' => $calzaUser->id,
        'visible' => true,
        'educaciones' => [['institucion' => 'Universidad de Concepción'], ['institucion' => 'Universidad Adolfo Ibáñez']],
        'experiencias' => [['empresa' => 'Forestal del Biobío'], ['empresa' => 'Codelco Chile']],
    ]);

    $noCalzaUser = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create([
        'user_id' => $noCalzaUser->id,
        'visible' => true,
        'educaciones' => [['institucion' => 'Universidad de Chile']],
        'experiencias' => [['empresa' => 'Codelco Chile']],
    ]);

    Livewire::actingAs($empresaUser)
        ->test(NuevaBusqueda::class)
        ->set('titulo', 'Egresados de Concepción con paso por Codelco')
        ->set('institucion', 'concepción')
        ->set('empresa', 'codelco')
        ->call('save')
        ->assertHasNoErrors();

    $candidatos = $empresa->busquedas()->sole()->candidatos;

    expect($candidatos)->toHaveCount(1)
        ->and($candidatos->sole()->postulante_id)->toBe($calza->id)
        ->and($candidatos->sole()->criterios_cumplidos)->toBe(2);
});

test('criteria without selections are ignored', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    Empresa::query()->create(['user_id' => $empresaUser->id, 'razon_social' => 'Empresa']);

    Livewire::actingAs($empresaUser)
        ->test(NuevaBusqueda::class)
        ->set('titulo', 'Búsqueda amplia')
        ->call('save')
        ->assertHasNoErrors();

    expect($empresaUser->empresa->busquedas)->toHaveCount(1)
        ->and($empresaUser->empresa->busquedas->sole()->criterios['ciudad'])->toBe([]);
});

test('a company cannot edit another company search', function () {
    $ownerUser = User::factory()->create(['role' => 'empresa']);
    $owner = Empresa::query()->create(['user_id' => $ownerUser->id, 'razon_social' => 'Propietaria']);
    $busqueda = $owner->busquedas()->create(['titulo' => 'Privada', 'criterios' => []]);

    $otherUser = User::factory()->create(['role' => 'empresa']);
    Empresa::query()->create(['user_id' => $otherUser->id, 'razon_social' => 'Otra', 'estado_activacion' => 'activa']);

    $this->actingAs($otherUser)
        ->get(route('empresa.busquedas.edit', $busqueda))
        ->assertForbidden();
});

test('the age range criterion keeps only candidates inside the bounds', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $empresaUser->id, 'razon_social' => 'Empresa']);

    $crearPostulante = function (?int $anioNacimiento): Postulante {
        return Postulante::query()->create([
            'user_id' => User::factory()->create(['role' => 'postulante'])->id,
            'visible' => true,
            'anio_nacimiento' => $anioNacimiento,
        ]);
    };

    $anioActual = now()->year;
    $cincuentaYCinco = $crearPostulante($anioActual - 55);
    $sesentaYCinco = $crearPostulante($anioActual - 65);
    $sinEdad = $crearPostulante(null);

    $busqueda = $empresa->busquedas()->create(['titulo' => 'Con rango', 'criterios' => []]);

    Livewire::actingAs($empresaUser)
        ->test(FiltrosBusqueda::class, ['busqueda' => $busqueda])
        ->set('edadMin', 50)
        ->set('edadMax', 60)
        ->assertHasNoErrors();

    $calzados = $busqueda->fresh()->candidatos()->where('estado_match', 'cumple')->pluck('postulante_id');

    expect($calzados)->toContain($cincuentaYCinco->id)
        ->not->toContain($sesentaYCinco->id)
        ->not->toContain($sinEdad->id);
});

test('the age range criterion is not stored when it spans the full bounds', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $empresaUser->id, 'razon_social' => 'Empresa']);
    $busqueda = $empresa->busquedas()->create(['titulo' => 'Sin rango', 'criterios' => []]);

    $limites = CatalogosProfesionales::rangoEdad();

    Livewire::actingAs($empresaUser)
        ->test(FiltrosBusqueda::class, ['busqueda' => $busqueda])
        ->set('edadMin', $limites['min'])
        ->set('edadMax', $limites['max'])
        ->assertHasNoErrors();

    expect($busqueda->fresh()->criterios['edad'])->toBeNull();
});

test('the top of the age range has no upper bound', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $empresaUser->id, 'razon_social' => 'Empresa']);
    $limites = CatalogosProfesionales::rangoEdad();

    $nonagenario = Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true,
        'anio_nacimiento' => now()->year - 90,
    ]);

    $busqueda = $empresa->busquedas()->create(['titulo' => 'Sin tope', 'criterios' => []]);

    Livewire::actingAs($empresaUser)
        ->test(FiltrosBusqueda::class, ['busqueda' => $busqueda])
        ->set('edadMin', 60)
        ->set('edadMax', $limites['max'])
        ->assertHasNoErrors();

    expect($busqueda->fresh()->criterios['edad'])->toBe(['min' => 60, 'max' => null])
        ->and($busqueda->fresh()->candidatos()->where('estado_match', 'cumple')->pluck('postulante_id'))
        ->toContain($nonagenario->id);
});

test('the age range rejects a minimum above the maximum', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $empresaUser->id, 'razon_social' => 'Empresa']);
    $busqueda = $empresa->busquedas()->create(['titulo' => 'Inválida', 'criterios' => []]);

    Livewire::actingAs($empresaUser)
        ->test(FiltrosBusqueda::class, ['busqueda' => $busqueda])
        ->set('edadMax', 55)
        ->set('edadMin', 70)
        ->assertHasErrors(['edadMax' => 'gte']);
});

test('choosing "Otros" for cargo or empresa requires specifying the free-text value', function () {
    $user = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create(['user_id' => $user->id, 'onboarding_paso' => 3, 'onboarding_completado' => false]);

    $experiencia = [
        'cargo' => 'Otros', 'cargo_otro' => '',
        'tipo_trabajo' => 'Jornada completa',
        'empresa' => 'Otros', 'empresa_otro' => '',
        'jerarquia' => 'Jefatura',
        'actividad_empresa' => 'Banca y servicios financieros',
        'inicio_mes' => 1, 'inicio_anio' => 2010,
        'actualmente' => true, 'fin_mes' => null, 'fin_anio' => null,
        'responsabilidades' => 'Lideré el área durante varios años.',
    ];

    $component = Livewire::actingAs($user)->test(Ficha::class)
        ->set('experiencias', [$experiencia])
        ->call('avanzar')
        ->assertHasErrors(['experiencias.0.cargo_otro', 'experiencias.0.empresa_otro']);

    $component
        ->set('experiencias.0.cargo_otro', 'Analista de Riesgo')
        ->set('experiencias.0.empresa_otro', 'Consultora Independiente')
        ->call('avanzar')
        ->assertHasNoErrors();

    expect($user->postulante->fresh()->experiencias[0])
        ->toMatchArray(['cargo' => 'Otros', 'cargo_otro' => 'Analista de Riesgo', 'empresa' => 'Otros', 'empresa_otro' => 'Consultora Independiente'])
        ->and($user->postulante->fresh()->cargo_actual)->toBe('Analista de Riesgo')
        ->and($user->postulante->fresh()->empresa_actual)->toBe('Consultora Independiente');
});

test('a cargo entered as "Otros" free text participates in the matching', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    Empresa::query()->create(['user_id' => $empresaUser->id, 'razon_social' => 'Empresa Otros']);

    $matchUser = User::factory()->create(['role' => 'postulante']);
    $match = Postulante::query()->create([
        'user_id' => $matchUser->id,
        'visible' => true,
        'anios_experiencia' => 20,
        'cargo_actual' => 'Analista Finanzas Corporativas',
        'experiencias' => [[
            'cargo' => 'Otros', 'cargo_otro' => 'Analista Finanzas Corporativas',
            'empresa' => 'Otros', 'empresa_otro' => 'Consultora Independiente',
            'actividad_empresa' => 'Finanzas', 'inicio' => 2010, 'fin' => 2026,
        ]],
    ]);

    $noMatchUser = User::factory()->create(['role' => 'postulante']);
    $noMatch = Postulante::query()->create([
        'user_id' => $noMatchUser->id,
        'visible' => true,
        'anios_experiencia' => 20,
        'cargo_actual' => 'Jefe de Bodega',
        'experiencias' => [[
            'cargo' => 'Otros', 'cargo_otro' => 'Jefe de Bodega',
            'empresa' => 'Otros', 'empresa_otro' => 'Logística Sur',
            'actividad_empresa' => 'Logística / Cadena de suministros', 'inicio' => 2010, 'fin' => 2026,
        ]],
    ]);

    Livewire::actingAs($empresaUser)
        ->test(NuevaBusqueda::class)
        ->set('titulo', 'Perfil financiero')
        ->set('cargo', ['Analista Finanzas'])
        ->call('save')
        ->assertHasNoErrors();

    $busqueda = $empresaUser->empresa->busquedas()->latest('id')->firstOrFail();
    $ids = $busqueda->candidatos()->where('estado_match', 'cumple')->pluck('postulante_id');

    expect($ids)->toContain($match->id)
        ->and($ids)->not->toContain($noMatch->id);
});
