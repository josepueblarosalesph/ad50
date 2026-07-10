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
        'carrera' => 'Ingeniería Civil / Ingeniería Comercial',
        'especialidad' => 'Finanzas',
        'industrias_interes' => ['Banca y servicios financieros'],
        'anios_experiencia' => 18,
        'resumen_profesional' => 'Lideró una transformación financiera regional.',
        'experiencias' => [[
            'cargo' => 'Finanzas', 'empresa' => 'Empresa A', 'area' => 'Finanzas',
            'inicio' => 2008, 'fin' => 2026,
        ]],
    ]);

    $partialUser = User::factory()->create(['role' => 'postulante']);
    $partial = Postulante::query()->create([
        'user_id' => $partialUser->id,
        'visible' => true,
        'ciudad' => 'Metropolitana de Santiago',
        'carrera' => 'Ingeniería Civil / Ingeniería Comercial',
        'especialidad' => 'Finanzas',
        'industrias_interes' => ['Banca y servicios financieros'],
        'anios_experiencia' => 12,
        'experiencias' => [[
            'cargo' => 'Finanzas', 'empresa' => 'Empresa B', 'area' => 'Finanzas',
            'inicio' => 2014, 'fin' => 2026,
        ]],
    ]);

    Livewire::actingAs($empresaUser)
        ->test(NuevaBusqueda::class)
        ->set('titulo', 'Liderazgo financiero')
        ->set('cargo', ['Finanzas'])
        ->set('carrera', ['Ingeniería Civil / Ingeniería Comercial'])
        ->set('especialidad', ['Finanzas'])
        ->set('industria', ['Banca y servicios financieros'])
        ->set('ciudad', ['Biobío'])
        ->set('aniosMinimos', 15)
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

test('candidate contact details require an active company subscription and access is audited', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create([
        'user_id' => $empresaUser->id,
        'razon_social' => 'Empresa Activa',
        'estado_activacion' => 'activa',
        'plan_id' => Plan::query()->create([
            'codigo' => 'empresa_test', 'nombre' => 'Empresa Test', 'audiencia' => 'empresa', 'precio_clp' => 1,
        ])->id,
        'plan_hasta' => now()->addMonth(),
    ]);
    $postulanteUser = User::factory()->create(['role' => 'postulante', 'email' => 'privado@example.com']);
    $postulante = Postulante::query()->create(['user_id' => $postulanteUser->id, 'rut' => '1-9', 'telefono' => '+56911111111']);
    $busqueda = $empresa->busquedas()->create(['titulo' => 'Búsqueda', 'criterios' => []]);
    $match = $busqueda->candidatos()->create([
        'postulante_id' => $postulante->id, 'estado_match' => 'cumple',
    ]);

    $this->actingAs($empresaUser)
        ->get(route('empresa.candidatos.show', $match))
        ->assertOk()
        ->assertSee('privado@example.com');

    expect($match->fresh()->contactado_at)->not->toBeNull();
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
        Postulante::query()->create(['user_id' => $user->id, 'visible' => true, 'ciudad' => $ciudad]);
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
        Postulante::query()->create(['user_id' => $user->id, 'visible' => true, 'ciudad' => $ciudad]);
    }

    Livewire::actingAs($empresaUser)
        ->test(NuevaBusqueda::class)
        ->set('titulo', 'Búsqueda editable')
        ->call('save');

    $busqueda = $empresa->busquedas()->sole();

    $this->actingAs($empresaUser)
        ->get(route('empresa.resultados', $busqueda))
        ->assertOk()
        ->assertSee('Filtros de búsqueda')
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
