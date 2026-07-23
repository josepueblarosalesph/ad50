<?php

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

    expect($busqueda->fresh()->criterios)->toBe([])
        ->and($busqueda->fresh()->candidatos)->toHaveCount(0);
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

    // La búsqueda sigue sin materializar nada (el borrador no se guardó).
    expect($busqueda->fresh()->criterios)->toBe([])
        ->and($busqueda->fresh()->candidatos)->toHaveCount(0);
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
