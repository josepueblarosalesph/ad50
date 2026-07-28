<?php

use App\Livewire\Empresa\FiltrosBusqueda;
use App\Livewire\Empresa\SelectorCriterio;
use App\Models\Empresa;
use App\Models\Postulante;
use App\Models\User;
use App\Support\CatalogosProfesionales;
use Livewire\Livewire;

/** Crea una ficha visible con las regiones de interés indicadas. */
function fichaEnRegiones(array $regiones): Postulante
{
    return Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true,
        'regiones_interes' => $regiones,
    ]);
}

test('server-side search filters the catalog and lists options', function () {
    fichaEnRegiones(['Biobío']);
    fichaEnRegiones(['Valparaíso']);

    Livewire::test(SelectorCriterio::class, ['campo' => 'ciudad', 'etiqueta' => 'Región'])
        ->assertSee('Biobío')
        ->assertSee('Valparaíso')
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

test('hidden fichas are not counted, so their option is not offered', function () {
    Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => false, 'regiones_interes' => ['Biobío'],
    ]);

    Livewire::test(SelectorCriterio::class, ['campo' => 'ciudad'])
        ->set('buscar', 'Biobío')
        ->assertViewHas('resultados', [])
        ->assertSee('Ninguna opción tiene candidatos con los filtros actuales');
});

test('las opciones sin candidatos no se listan', function () {
    fichaEnRegiones(['Biobío']);

    Livewire::test(SelectorCriterio::class, ['campo' => 'ciudad'])
        ->assertSee('Biobío')
        // El resto del catálogo de regiones no tiene fichas: no debe ofrecerse.
        ->assertDontSee('Valparaíso')
        ->assertDontSee('Antofagasta')
        ->assertViewHas('resultados', fn (array $resultados): bool => count($resultados) === 1
            && $resultados[0]['valor'] === 'Biobío');
});

test('una opción aparece sola en cuanto entra un candidato nuevo', function () {
    fichaEnRegiones(['Biobío']);

    $selector = Livewire::test(SelectorCriterio::class, ['campo' => 'ciudad']);
    $selector->assertDontSee('Valparaíso');

    // Al guardar una ficha se invalidan los conteos cacheados (Postulante::booted).
    fichaEnRegiones(['Valparaíso']);

    $selector->set('buscar', 'Valpara')
        ->assertSee('Valparaíso')
        ->assertSee('Quedan 1 candidato si agregas', escape: false);
});

test('una opción deja de ofrecerse cuando su último candidato se oculta', function () {
    $ficha = fichaEnRegiones(['Biobío']);
    fichaEnRegiones(['Valparaíso']);

    $selector = Livewire::test(SelectorCriterio::class, ['campo' => 'ciudad']);
    $selector->assertSee('Biobío');

    $ficha->update(['visible' => false]);

    $selector->set('buscar', 'Biobío')
        ->assertViewHas('resultados', [])
        ->assertSee('Ninguna opción tiene candidatos con los filtros actuales');
});

test('las opciones se listan de mayor a menor cantidad de candidatos', function () {
    // Valparaíso: 3 fichas · Biobío: 2 · Maule: 1.
    foreach ([['Valparaíso'], ['Valparaíso'], ['Valparaíso'], ['Biobío'], ['Biobío'], ['Maule']] as $regiones) {
        fichaEnRegiones($regiones);
    }

    Livewire::test(SelectorCriterio::class, ['campo' => 'ciudad'])
        ->assertViewHas('resultados', fn (array $resultados): bool => collect($resultados)
            ->map(fn (array $opcion): array => [$opcion['valor'], $opcion['total']])
            ->all() === [['Valparaíso', 3], ['Biobío', 2], ['Maule', 1]])
        // El orden también se refleja en el desplegable.
        ->assertSeeInOrder(['Valparaíso', 'Biobío', 'Maule']);
});

test('entre opciones con la misma cantidad el orden es alfabético', function () {
    fichaEnRegiones(['Biobío']);
    fichaEnRegiones(['Antofagasta']);
    fichaEnRegiones(['Maule']);

    Livewire::test(SelectorCriterio::class, ['campo' => 'ciudad'])
        ->assertViewHas('resultados', fn (array $resultados): bool => collect($resultados)
            ->pluck('valor')
            ->all() === ['Antofagasta', 'Biobío', 'Maule']);
});

test('el recorte a 50 opciones no gasta el cupo en opciones vacías', function () {
    // "Otros" está al final del catálogo de cargos, muy por detrás de la opción 50.
    $catalogo = CatalogosProfesionales::cargos();
    $ultimo = $catalogo[count($catalogo) - 1];

    expect(array_search($ultimo, $catalogo, true))->toBeGreaterThan(50);

    Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true,
        'cargo_actual' => $ultimo,
    ]);

    Livewire::test(SelectorCriterio::class, ['campo' => 'cargo'])
        ->assertViewHas('resultados', fn (array $resultados): bool => collect($resultados)->contains('valor', $ultimo));
});

test('los filtros ya elegidos acotan qué opciones siguen disponibles', function () {
    Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true, 'genero' => 'Femenino', 'regiones_interes' => ['Biobío'],
    ]);
    Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true, 'genero' => 'Masculino', 'regiones_interes' => ['Valparaíso'],
    ]);

    // Sin filtros ambos géneros tienen candidatos.
    Livewire::test(SelectorCriterio::class, ['campo' => 'genero'])
        ->assertSee('Femenino')
        ->assertSee('Masculino');

    // Acotando a Biobío, solo queda el género de esa ficha.
    Livewire::test(SelectorCriterio::class, ['campo' => 'genero', 'criterios' => ['ciudad' => ['Biobío']]])
        ->assertSee('Femenino')
        ->assertDontSee('Masculino');
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
