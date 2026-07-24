<?php

use App\Livewire\Empresa\Candidato;
use App\Livewire\Empresa\FiltrosBusqueda;
use App\Livewire\Empresa\Resultados;
use App\Models\Empresa;
use App\Models\Postulante;
use App\Models\User;
use App\Services\MatchingService;
use Livewire\Livewire;

/** @return array{0: User, 1: Empresa} */
function empresaActiva(): array
{
    $user = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => 'Empresa Demo',
        'estado_activacion' => 'activa',
    ]);

    return [$user, $empresa];
}

function postulanteEnRegion(string $region): Postulante
{
    return Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true,
        'regiones_interes' => [$region],
    ]);
}

test('changing a filter previews without persisting the criteria or the pivot', function () {
    [$user, $empresa] = empresaActiva();
    $busqueda = $empresa->busquedas()->create(['titulo' => 'Proceso', 'criterios' => []]);
    postulanteEnRegion('Biobío');

    Livewire::actingAs($user)
        ->test(FiltrosBusqueda::class, ['busqueda' => $busqueda])
        ->set('ciudad', ['Biobío'])
        ->assertHasNoErrors()
        ->assertDispatched('criterios-previsualizados')
        ->assertNotDispatched('criterios-guardados');

    // No se guardan los criterios ni se confirman coincidencias; solo se materializa
    // una fila TEMPORAL para que la previsualización cuente y sea abrible.
    expect($busqueda->fresh()->criterios)->toBe([])
        ->and($busqueda->fresh()->candidatos()->confirmados()->count())->toBe(0)
        ->and($busqueda->fresh()->candidatos()->temporales()->count())->toBe(1);
});

test('clicking save persists the criteria and materialises the matches', function () {
    [$user, $empresa] = empresaActiva();
    $busqueda = $empresa->busquedas()->create(['titulo' => 'Proceso', 'criterios' => []]);
    $calza = postulanteEnRegion('Biobío');
    postulanteEnRegion('Valparaíso');

    Livewire::actingAs($user)
        ->test(FiltrosBusqueda::class, ['busqueda' => $busqueda])
        ->set('ciudad', ['Biobío'])
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertDispatched('criterios-guardados');

    expect($busqueda->fresh()->criterios['ciudad'])->toBe(['Biobío'])
        ->and($busqueda->fresh()->candidatos->pluck('postulante_id')->all())->toBe([$calza->id]);
});

test('discarding restores the saved criteria and leaves preview mode', function () {
    [$user, $empresa] = empresaActiva();
    $busqueda = $empresa->busquedas()->create(['titulo' => 'Proceso', 'criterios' => ['ciudad' => ['Biobío']]]);

    Livewire::actingAs($user)
        ->test(FiltrosBusqueda::class, ['busqueda' => $busqueda])
        ->set('ciudad', ['Valparaíso'])
        ->assertViewHas('sinGuardar', true)
        ->call('descartar')
        ->assertSet('ciudad', ['Biobío'])
        ->assertViewHas('sinGuardar', false)
        ->assertDispatched('criterios-guardados');

    expect($busqueda->fresh()->criterios['ciudad'])->toBe(['Biobío']);
});

test('the second panel instance adopts the draft previewed by the first one', function () {
    [$user, $empresa] = empresaActiva();
    $busqueda = $empresa->busquedas()->create(['titulo' => 'Proceso', 'criterios' => []]);

    // Simula la instancia móvil recibiendo el evento que emitió la de escritorio.
    Livewire::actingAs($user)
        ->test(FiltrosBusqueda::class, ['busqueda' => $busqueda])
        ->call('sincronizarBorrador', ['ciudad' => ['Biobío']])
        ->assertSet('ciudad', ['Biobío'])
        ->assertViewHas('sinGuardar', true);
});

test('the second panel instance drops its draft once the first one saves', function () {
    [$user, $empresa] = empresaActiva();
    $busqueda = $empresa->busquedas()->create(['titulo' => 'Proceso', 'criterios' => []]);

    $panel = Livewire::actingAs($user)
        ->test(FiltrosBusqueda::class, ['busqueda' => $busqueda])
        ->set('ciudad', ['Valparaíso'])
        ->assertViewHas('sinGuardar', true);

    // La otra instancia guardó otros criterios mientras tanto.
    $busqueda->update(['criterios' => ['ciudad' => ['Biobío']]]);

    $panel->call('sincronizarGuardado')
        ->assertSet('ciudad', ['Biobío'])
        ->assertViewHas('sinGuardar', false);
});

test('the results list shows the preview matches before they are saved', function () {
    [$user, $empresa] = empresaActiva();
    $busqueda = $empresa->busquedas()->create(['titulo' => 'Proceso', 'criterios' => []]);
    $biobio = postulanteEnRegion('Biobío');
    $valparaiso = postulanteEnRegion('Valparaíso');

    Livewire::actingAs($user)
        ->test(Resultados::class, ['busqueda' => $busqueda])
        ->assertViewHas('totalCandidatos', 0)
        ->call('previsualizar', ['ciudad' => ['Biobío']])
        ->assertViewHas('previsualizando', true)
        ->assertViewHas('totalCandidatos', 1)
        ->assertViewHas('candidatos', fn ($candidatos): bool => $candidatos->pluck('postulante_id')->all() === [$biobio->id]);

    expect($busqueda->fresh()->candidatos)->toHaveCount(0)
        ->and($valparaiso->fresh()->visible)->toBeTrue();
});

test('el borrador de filtros persiste al volver a montar el listado', function () {
    [$user, $empresa] = empresaActiva();
    $busqueda = $empresa->busquedas()->create(['titulo' => 'Proceso', 'criterios' => []]);
    $biobio = postulanteEnRegion('Biobío');
    postulanteEnRegion('Valparaíso');

    // Aplica un filtro sin guardar (previsualiza y persiste el borrador).
    Livewire::actingAs($user)
        ->test(FiltrosBusqueda::class, ['busqueda' => $busqueda])
        ->set('ciudad', ['Biobío'])
        ->assertHasNoErrors();

    // Al volver, el listado recién montado conserva la previsualización.
    Livewire::actingAs($user)
        ->test(Resultados::class, ['busqueda' => $busqueda])
        ->assertViewHas('previsualizando', true)
        ->assertViewHas('totalCandidatos', 1)
        ->assertViewHas('candidatos', fn ($candidatos): bool => $candidatos->pluck('postulante_id')->all() === [$biobio->id]);

    // Y el panel de filtros recién montado recuerda la selección como pendiente.
    Livewire::actingAs($user)
        ->test(FiltrosBusqueda::class, ['busqueda' => $busqueda])
        ->assertSet('ciudad', ['Biobío'])
        ->assertViewHas('sinGuardar', true);

    // El borrador no se guardó: sin criterios ni coincidencias confirmadas.
    expect($busqueda->fresh()->criterios)->toBe([])
        ->and($busqueda->fresh()->candidatos()->confirmados()->count())->toBe(0);
});

test('descartar limpia el borrador persistido y sale de la previsualizacion', function () {
    [$user, $empresa] = empresaActiva();
    $busqueda = $empresa->busquedas()->create(['titulo' => 'Proceso', 'criterios' => []]);
    postulanteEnRegion('Biobío');

    Livewire::actingAs($user)
        ->test(FiltrosBusqueda::class, ['busqueda' => $busqueda])
        ->set('ciudad', ['Biobío'])
        ->call('descartar');

    Livewire::actingAs($user)
        ->test(Resultados::class, ['busqueda' => $busqueda])
        ->assertViewHas('previsualizando', false);
});

test('guardar limpia el borrador persistido', function () {
    [$user, $empresa] = empresaActiva();
    $busqueda = $empresa->busquedas()->create(['titulo' => 'Proceso', 'criterios' => []]);
    postulanteEnRegion('Biobío');

    Livewire::actingAs($user)
        ->test(FiltrosBusqueda::class, ['busqueda' => $busqueda])
        ->set('ciudad', ['Biobío'])
        ->call('guardar');

    // Ya guardado: montar el listado no debe entrar en modo previsualización.
    Livewire::actingAs($user)
        ->test(Resultados::class, ['busqueda' => $busqueda])
        ->assertViewHas('previsualizando', false);
});

test('el detalle del candidato usa el total del borrador en previsualizacion, no el guardado', function () {
    [$user, $empresa] = empresaActiva();
    $busqueda = $empresa->busquedas()->create(['titulo' => 'Proceso', 'criterios' => []]);
    $biobio = postulanteEnRegion('Biobío');
    postulanteEnRegion('Valparaíso');

    // Con criterios vacíos, los dos postulantes quedan guardados como coincidencias.
    app(MatchingService::class)->sincronizar($busqueda->fresh());
    expect($busqueda->fresh()->candidatos()->count())->toBe(2);

    // Borrador que reduce a solo Biobío (se persiste en sesión).
    Livewire::actingAs($user)
        ->test(FiltrosBusqueda::class, ['busqueda' => $busqueda])
        ->set('ciudad', ['Biobío']);

    $matchBiobio = $busqueda->candidatos()->where('postulante_id', $biobio->id)->first();

    // Al abrir el candidato, la navegación refleja el borrador (1 de 1), no el guardado (1 de 2).
    Livewire::actingAs($user)
        ->test(Candidato::class, ['match' => $matchBiobio])
        ->assertSet('totalCandidatos', 1)
        ->assertSet('posicion', 1)
        ->assertSet('anteriorId', null)
        ->assertSet('siguienteId', null);
});

test('sin borrador el detalle del candidato usa el total guardado', function () {
    [$user, $empresa] = empresaActiva();
    $busqueda = $empresa->busquedas()->create(['titulo' => 'Proceso', 'criterios' => []]);
    $biobio = postulanteEnRegion('Biobío');
    postulanteEnRegion('Valparaíso');

    app(MatchingService::class)->sincronizar($busqueda->fresh());

    $matchBiobio = $busqueda->candidatos()->where('postulante_id', $biobio->id)->first();

    Livewire::actingAs($user)
        ->test(Candidato::class, ['match' => $matchBiobio])
        ->assertSet('totalCandidatos', 2);
});

test('la previsualizacion materializa perfiles nuevos abribles y contables, sin contarlos como confirmados', function () {
    [$user, $empresa] = empresaActiva();
    $busqueda = $empresa->busquedas()->create(['titulo' => 'Proceso', 'criterios' => ['ciudad' => ['Biobío']]]);
    postulanteEnRegion('Biobío');
    $valpo = postulanteEnRegion('Valparaíso');
    app(MatchingService::class)->sincronizar($busqueda->fresh());

    expect($busqueda->candidatos()->confirmados()->count())->toBe(1);

    // Borrador que amplía a Valparaíso: un perfil que no estaba en el proceso.
    Livewire::actingAs($user)
        ->test(FiltrosBusqueda::class, ['busqueda' => $busqueda])
        ->set('ciudad', ['Biobío', 'Valparaíso']);

    // Valparaíso queda como coincidencia TEMPORAL (abrible), sin contar como confirmada.
    $matchValpo = $busqueda->candidatos()->temporales()->where('postulante_id', $valpo->id)->first();
    expect($matchValpo)->not->toBeNull()
        ->and($busqueda->candidatos()->confirmados()->count())->toBe(1);

    // Su detalle se abre y cuenta 2 de 2 (Biobío + Valparaíso del borrador).
    Livewire::actingAs($user)
        ->test(Candidato::class, ['match' => $matchValpo])
        ->assertSet('totalCandidatos', 2);

    // El panel de empresa NO cuenta el temporal (sigue en 1 confirmado).
    Livewire::actingAs($user)
        ->test(\App\Livewire\Empresa\Panel::class)
        ->assertViewHas('totalCandidatos', 1);
});

test('descartar elimina las coincidencias temporales de la previsualizacion', function () {
    [$user, $empresa] = empresaActiva();
    $busqueda = $empresa->busquedas()->create(['titulo' => 'Proceso', 'criterios' => []]);
    postulanteEnRegion('Biobío');

    $panel = Livewire::actingAs($user)
        ->test(FiltrosBusqueda::class, ['busqueda' => $busqueda])
        ->set('ciudad', ['Biobío']);

    expect($busqueda->candidatos()->temporales()->count())->toBe(1);

    $panel->call('descartar');

    expect($busqueda->candidatos()->temporales()->count())->toBe(0);
});

test('guardar confirma las coincidencias del borrador y limpia las temporales', function () {
    [$user, $empresa] = empresaActiva();
    $busqueda = $empresa->busquedas()->create(['titulo' => 'Proceso', 'criterios' => []]);
    $biobio = postulanteEnRegion('Biobío');
    postulanteEnRegion('Valparaíso');

    $panel = Livewire::actingAs($user)
        ->test(FiltrosBusqueda::class, ['busqueda' => $busqueda])
        ->set('ciudad', ['Biobío']);

    expect($busqueda->candidatos()->temporales()->count())->toBe(1)
        ->and($busqueda->candidatos()->confirmados()->count())->toBe(0);

    $panel->call('guardar');

    expect($busqueda->candidatos()->temporales()->count())->toBe(0)
        ->and($busqueda->candidatos()->confirmados()->pluck('postulante_id')->all())->toBe([$biobio->id]);
});

test('saved matches keep their pivot row while previewing so favourites survive', function () {
    [$user, $empresa] = empresaActiva();
    $busqueda = $empresa->busquedas()->create(['titulo' => 'Proceso', 'criterios' => ['ciudad' => ['Biobío']]]);
    $biobio = postulanteEnRegion('Biobío');

    app(MatchingService::class)->sincronizar($busqueda->fresh());
    $busqueda->candidatos()->where('postulante_id', $biobio->id)->update(['favorito' => true]);

    Livewire::actingAs($user)
        ->test(Resultados::class, ['busqueda' => $busqueda])
        ->call('previsualizar', ['ciudad' => ['Biobío'], 'genero' => []])
        ->assertViewHas('totalFavoritos', 1)
        ->assertViewHas('candidatos', fn ($candidatos): bool => $candidatos->first()->exists);
});
