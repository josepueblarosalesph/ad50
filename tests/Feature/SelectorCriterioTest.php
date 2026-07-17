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

test('the candidate count is hidden while temporarily disabled', function () {
    foreach ([['Biobío'], ['Biobío'], ['Valparaíso']] as $regiones) {
        Postulante::query()->create([
            'user_id' => User::factory()->create(['role' => 'postulante'])->id,
            'visible' => true, 'regiones_interes' => $regiones,
        ]);
    }

    Livewire::test(SelectorCriterio::class, ['campo' => 'ciudad'])
        ->set('buscar', 'Biobío')
        ->assertSee('Biobío')
        ->assertDontSee('candidatos disponibles');
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
        ->assertSet('carrera', ['Ingeniería Comercial']);

    expect($busqueda->fresh()->criterios['carrera'])->toBe(['Ingeniería Comercial'])
        ->and($busqueda->fresh()->candidatos()->count())->toBe(1);
});
