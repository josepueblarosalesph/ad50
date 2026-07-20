<?php

use App\Livewire\Empresa\FiltrosBusqueda;
use App\Livewire\Empresa\SelectorCriterio;
use App\Models\Empresa;
use App\Models\Postulante;
use App\Models\User;
use Livewire\Livewire;

test('server-side search filters the catalog and lists options', function () {
    Livewire::test(SelectorCriterio::class, ['campo' => 'ciudad', 'etiqueta' => 'Región'])
        ->assertSee('Nacional')
        ->set('buscar', 'biob')
        ->assertSee('Biobío')
        ->assertDontSee('Valparaíso');
});

test('adding and removing values updates the selection and notifies the parent', function () {
    $component = Livewire::test(SelectorCriterio::class, ['campo' => 'ciudad', 'etiqueta' => 'Región'])
        ->call('agregar', 'Biobío')
        ->assertSet('seleccion', ['Biobío'])
        ->assertSet('buscar', '')
        ->assertDispatched('criterio-actualizado', campo: 'ciudad', valores: ['Biobío']);

    // Ya no debe ofrecer la opción seleccionada.
    $component->set('buscar', 'biob')->assertDontSee('>Biobío<');

    $component->call('quitar', 'Biobío')
        ->assertSet('seleccion', [])
        ->assertDispatched('criterio-actualizado', campo: 'ciudad', valores: []);
});

test('a value outside the catalog is ignored', function () {
    Livewire::test(SelectorCriterio::class, ['campo' => 'ciudad'])
        ->call('agregar', 'ValorInventado')
        ->assertSet('seleccion', []);
});

test('each option shows how many candidates would remain if it were added', function () {
    foreach ([['Biobío'], ['Biobío'], ['Valparaíso']] as $regiones) {
        Postulante::query()->create([
            'user_id' => User::factory()->create(['role' => 'postulante'])->id,
            'visible' => true, 'regiones_interes' => $regiones,
        ]);
    }

    Livewire::test(SelectorCriterio::class, ['campo' => 'ciudad'])
        ->set('buscar', 'Biobío')
        ->assertSee('Biobío')
        ->assertSee('Quedan 2 candidatos si agregas', escape: false);
});

test('the count label stays singular for a single candidate', function () {
    Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true, 'regiones_interes' => ['Valparaíso'],
    ]);

    Livewire::test(SelectorCriterio::class, ['campo' => 'ciudad'])
        ->set('buscar', 'Valpara')
        ->assertSee('Quedan 1 candidato si agregas', escape: false);
});

test('hidden fichas are not counted', function () {
    Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => false, 'regiones_interes' => ['Biobío'],
    ]);

    Livewire::test(SelectorCriterio::class, ['campo' => 'ciudad'])
        ->set('buscar', 'Biobío')
        ->assertSee('Quedan 0 candidatos si agregas', escape: false);
});

test('the count reflects the criteria it receives', function () {
    Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true, 'carrera' => 'Ingeniería Comercial', 'regiones_interes' => ['Biobío'],
    ]);
    Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true, 'carrera' => 'Ingeniería Comercial', 'regiones_interes' => ['Valparaíso'],
    ]);

    Livewire::test(SelectorCriterio::class, ['campo' => 'carrera', 'criterios' => []])
        ->set('buscar', 'Ingeniería Comercial')
        ->assertSee('Quedan 2 candidatos si agregas', escape: false);

    Livewire::test(SelectorCriterio::class, ['campo' => 'carrera', 'criterios' => ['ciudad' => ['Biobío']]])
        ->set('buscar', 'Ingeniería Comercial')
        ->assertSee('Quedan 1 candidato si agregas', escape: false);
});

test('the selector recalculates its counts when the parent announces new criteria', function () {
    Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true, 'genero' => 'Femenino', 'regiones_interes' => ['Biobío'],
    ]);
    Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true, 'genero' => 'Femenino', 'regiones_interes' => ['Valparaíso'],
    ]);

    Livewire::test(SelectorCriterio::class, ['campo' => 'genero'])
        ->set('buscar', 'Femenino')
        ->assertSee('Quedan 2 candidatos si agregas', escape: false)
        // Livewire no re-envía parámetros a un hijo ya montado: el panel anuncia por evento.
        ->dispatch('criterios-previsualizados', criterios: ['ciudad' => ['Biobío']])
        ->assertSet('criterios', ['ciudad' => ['Biobío']])
        ->assertSee('Quedan 1 candidato si agregas', escape: false);
});

test('the filters panel announces its criteria on every change', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $empresaUser->id, 'razon_social' => 'Empresa Z', 'estado_activacion' => 'activa']);
    $busqueda = $empresa->busquedas()->create(['titulo' => 'B', 'criterios' => []]);

    Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true, 'regiones_interes' => ['Biobío'],
    ]);

    $panel = Livewire::actingAs($empresaUser)->test(FiltrosBusqueda::class, ['busqueda' => $busqueda]);

    // Al cambiar un criterio.
    $panel->set('ciudad', ['Biobío'])
        ->assertDispatched('criterios-previsualizados', fn (string $evento, array $params): bool => $params['criterios']['ciudad'] === ['Biobío']);

    // Al guardar: los selectores deben quedar con los criterios recién persistidos.
    $panel->call('guardar')
        ->assertDispatched('criterios-previsualizados', fn (string $evento, array $params): bool => $params['criterios']['ciudad'] === ['Biobío']);

    // Y al descartar, con los criterios revertidos (no con el borrador desechado).
    $panel->set('ciudad', ['Biobío', 'Valparaíso'])
        ->call('descartar')
        ->assertDispatched('criterios-previsualizados', fn (string $evento, array $params): bool => $params['criterios']['ciudad'] === ['Biobío']);
});

test('the selector feeds its selection back to the parent filters component', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $empresaUser->id, 'razon_social' => 'Empresa X', 'estado_activacion' => 'activa']);
    $busqueda = $empresa->busquedas()->create(['titulo' => 'B', 'criterios' => []]);

    Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true, 'regiones_interes' => ['Biobío'],
    ]);

    Livewire::actingAs($empresaUser)
        ->test(FiltrosBusqueda::class, ['busqueda' => $busqueda])
        ->set('ciudad', ['Biobío'])
        ->call('guardar')
        ->assertHasNoErrors();

    expect($busqueda->fresh()->criterios['ciudad'])->toBe(['Biobío'])
        ->and($busqueda->fresh()->candidatos)->toHaveCount(1);
});

test('the parent filters apply a multi-select criterion from the selector event', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $empresaUser->id, 'razon_social' => 'Empresa Y', 'estado_activacion' => 'activa']);
    $busqueda = $empresa->busquedas()->create(['titulo' => 'B', 'criterios' => []]);

    Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true, 'carrera' => 'Ingeniería Comercial',
    ]);
    Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true, 'carrera' => 'Periodismo',
    ]);

    // Simula el evento que emite el selector hijo al agregar un tag (campo snake_case).
    Livewire::actingAs($empresaUser)
        ->test(FiltrosBusqueda::class, ['busqueda' => $busqueda])
        ->call('aplicarDesdeSelector', 'carrera', ['Ingeniería Comercial'])
        ->assertHasNoErrors()
        ->assertSet('carrera', ['Ingeniería Comercial'])
        ->call('guardar');

    expect($busqueda->fresh()->criterios['carrera'])->toBe(['Ingeniería Comercial'])
        ->and($busqueda->fresh()->candidatos()->count())->toBe(1);
});
