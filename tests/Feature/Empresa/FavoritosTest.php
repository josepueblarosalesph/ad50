<?php

use App\Livewire\Empresa\Favoritos;
use App\Models\Busqueda;
use App\Models\BusquedaCandidato;
use App\Models\Desbloqueo;
use App\Models\Empresa;
use App\Models\Postulante;
use App\Models\Publicacion;
use App\Models\User;
use Livewire\Livewire;

/**
 * Empresa operativa con dos búsquedas y una publicación.
 *
 * @return array{0: User, 1: Empresa, 2: Busqueda, 3: Busqueda, 4: Publicacion}
 */
function empresaConFavoritos(): array
{
    $user = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => 'Empresa Favoritos '.fake()->unique()->numerify('####'),
        'estado_activacion' => 'activa',
    ]);
    hacerEmpresaOperativa($empresa);

    $liderazgo = $empresa->busquedas()->create(['titulo' => 'Liderazgo', 'criterios' => []]);
    $planta = $empresa->busquedas()->create(['titulo' => 'Planta Sur', 'criterios' => []]);
    $publicacion = Publicacion::factory()->create(['empresa_id' => $empresa->id, 'cargo' => 'Jefe de Planta']);

    return [$user->fresh(), $empresa->fresh(), $liderazgo, $planta, $publicacion];
}

/** Crea un candidato y lo marca (o no) como favorito en la búsqueda dada. */
function candidatoEnBusqueda(Busqueda $busqueda, bool $favorito = true, string $cargo = 'Gerente'): BusquedaCandidato
{
    $postulante = Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true,
        'cargo_actual' => $cargo,
    ]);

    return $busqueda->candidatos()->create([
        'postulante_id' => $postulante->id,
        'criterios_cumplidos' => 1,
        'criterios_totales' => 1,
        'estado_match' => 'cumple',
        'favorito' => $favorito,
    ]);
}

test('la lista muestra solo los favoritos de la empresa, una fila por candidato', function () {
    [$user, , $liderazgo, $planta] = empresaConFavoritos();

    $favorito = candidatoEnBusqueda($liderazgo, cargo: 'Gerente de Operaciones');
    candidatoEnBusqueda($liderazgo, favorito: false, cargo: 'No favorito');

    // El mismo candidato marcado también en la segunda búsqueda: sigue siendo una fila.
    $planta->candidatos()->create([
        'postulante_id' => $favorito->postulante_id,
        'criterios_cumplidos' => 1,
        'criterios_totales' => 1,
        'estado_match' => 'cumple',
        'favorito' => true,
    ]);

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->assertViewHas('totalFavoritos', 1)
        ->assertViewHas('candidatos', fn ($candidatos) => $candidatos->total() === 1)
        ->assertSee('Gerente de Operaciones')
        ->assertDontSee('No favorito')
        // Aparecen las dos búsquedas donde está marcado.
        ->assertSee('Liderazgo')
        ->assertSee('Planta Sur');
});

test('los favoritos de otra empresa no se filtran a la lista', function () {
    [$user, , $liderazgo] = empresaConFavoritos();
    candidatoEnBusqueda($liderazgo, cargo: 'Propio');

    [, , $ajena] = empresaConFavoritos();
    candidatoEnBusqueda($ajena, cargo: 'Ajeno');

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->assertViewHas('totalFavoritos', 1)
        ->assertSee('Propio')
        ->assertDontSee('Ajeno');
});

test('un candidato que deja de estar visible desaparece de la lista', function () {
    [$user, , $liderazgo] = empresaConFavoritos();
    $match = candidatoEnBusqueda($liderazgo);

    $match->postulante->update(['visible' => false]);

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->assertViewHas('totalFavoritos', 0);
});

test('el filtro por búsqueda de origen acota la lista', function () {
    [$user, , $liderazgo, $planta] = empresaConFavoritos();
    candidatoEnBusqueda($liderazgo, cargo: 'Solo liderazgo');
    candidatoEnBusqueda($planta, cargo: 'Solo planta');

    $componente = Livewire::actingAs($user)->test(Favoritos::class);

    $componente->assertViewHas('candidatos', fn ($c) => $c->total() === 2);

    $componente->set('busqueda', (string) $liderazgo->id)
        ->assertViewHas('candidatos', fn ($c) => $c->total() === 1)
        ->assertSee('Solo liderazgo')
        ->assertDontSee('Solo planta');
});

test('el filtro por publicación asociada distingue asociados y sin asociar', function () {
    [$user, , $liderazgo, , $publicacion] = empresaConFavoritos();
    $asociado = candidatoEnBusqueda($liderazgo, cargo: 'Con publicacion');
    candidatoEnBusqueda($liderazgo, cargo: 'Sin publicacion');

    $publicacion->candidatos()->attach($asociado->postulante_id, ['busqueda_id' => $liderazgo->id]);

    $componente = Livewire::actingAs($user)->test(Favoritos::class);

    $componente->set('publicacion', (string) $publicacion->id)
        ->assertViewHas('candidatos', fn ($c) => $c->total() === 1)
        ->assertSee('Con publicacion');

    $componente->set('publicacion', 'sin')
        ->assertViewHas('candidatos', fn ($c) => $c->total() === 1)
        ->assertSee('Sin publicacion');
});

test('el filtro por estado de desbloqueo separa los perfiles', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $desbloqueado = candidatoEnBusqueda($liderazgo, cargo: 'Perfil abierto');
    candidatoEnBusqueda($liderazgo, cargo: 'Perfil cerrado');

    Desbloqueo::query()->create([
        'empresa_id' => $empresa->id,
        'postulante_id' => $desbloqueado->postulante_id,
    ]);

    $componente = Livewire::actingAs($user)->test(Favoritos::class);

    $componente->set('desbloqueo', 'desbloqueados')
        ->assertViewHas('candidatos', fn ($c) => $c->total() === 1)
        ->assertSee('Perfil abierto');

    $componente->set('desbloqueo', 'bloqueados')
        ->assertViewHas('candidatos', fn ($c) => $c->total() === 1)
        ->assertSee('Perfil cerrado');
});

test('los filtros se combinan y se pueden limpiar de una vez', function () {
    [$user, , $liderazgo, $planta, $publicacion] = empresaConFavoritos();
    $calza = candidatoEnBusqueda($liderazgo, cargo: 'Calza todo');
    candidatoEnBusqueda($planta, cargo: 'Otra busqueda');

    $publicacion->candidatos()->attach($calza->postulante_id, ['busqueda_id' => $liderazgo->id]);

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->set('busqueda', (string) $liderazgo->id)
        ->set('publicacion', (string) $publicacion->id)
        ->set('desbloqueo', 'bloqueados')
        ->assertViewHas('hayFiltros', true)
        ->assertViewHas('candidatos', fn ($c) => $c->total() === 1)
        ->call('limpiarFiltros')
        ->assertSet('busqueda', 'todas')
        ->assertSet('publicacion', 'todas')
        ->assertSet('desbloqueo', 'todos')
        ->assertViewHas('candidatos', fn ($c) => $c->total() === 2);
});

test('quitar el favorito lo saca de esa búsqueda pero lo conserva en las demás', function () {
    [$user, , $liderazgo, $planta] = empresaConFavoritos();
    $match = candidatoEnBusqueda($liderazgo);
    $otro = $planta->candidatos()->create([
        'postulante_id' => $match->postulante_id,
        'criterios_cumplidos' => 1,
        'criterios_totales' => 1,
        'estado_match' => 'cumple',
        'favorito' => true,
    ]);

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->call('quitarFavorito', $liderazgo->id, $match->postulante_id)
        ->assertHasNoErrors()
        // Sigue en la lista porque continúa marcado en la otra búsqueda.
        ->assertViewHas('totalFavoritos', 1);

    expect($match->fresh()->favorito)->toBeFalse()
        ->and($otro->fresh()->favorito)->toBeTrue();

    // Al quitarlo de la segunda, desaparece de la lista.
    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->call('quitarFavorito', $planta->id, $match->postulante_id)
        ->assertViewHas('totalFavoritos', 0);
});

test('no se puede quitar el favorito de una búsqueda de otra empresa', function () {
    [$user] = empresaConFavoritos();
    [, , $ajena] = empresaConFavoritos();
    $match = candidatoEnBusqueda($ajena);

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->call('quitarFavorito', $ajena->id, $match->postulante_id)
        ->assertStatus(404);

    expect($match->fresh()->favorito)->toBeTrue();
});

test('desde favoritos se asocia un candidato a una publicación', function () {
    [$user, , $liderazgo, , $publicacion] = empresaConFavoritos();
    $match = candidatoEnBusqueda($liderazgo);

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->call('abrirAsociacion', $match->postulante_id)
        ->call('toggleAsociacion', $publicacion->id)
        ->assertHasNoErrors();

    expect($publicacion->candidatos()->count())->toBe(1);
});

test('no se puede asociar desde favoritos a alguien que no es favorito', function () {
    [$user, , $liderazgo, , $publicacion] = empresaConFavoritos();
    $noFavorito = candidatoEnBusqueda($liderazgo, favorito: false);

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->call('abrirAsociacion', $noFavorito->postulante_id)
        ->assertStatus(404);

    expect($publicacion->candidatos()->count())->toBe(0);
});

test('el menú de empresa incluye Favoritos', function () {
    [$user, , $liderazgo] = empresaConFavoritos();
    candidatoEnBusqueda($liderazgo);

    $this->actingAs($user)
        ->get(route('empresa.favoritos'))
        ->assertOk()
        ->assertSee('href="'.route('empresa.favoritos').'"', false)
        ->assertSee('Mis favoritos');  // encabezado de la página
});

test('un postulante no puede entrar a los favoritos de una empresa', function () {
    $postulante = User::factory()->create(['role' => 'postulante']);

    $this->actingAs($postulante)
        ->get(route('empresa.favoritos'))
        ->assertForbidden();
});

test('la tarjeta muestra el candado como icono y el botón rotulado de asociar', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $match = candidatoEnBusqueda($liderazgo);

    // Sin desbloquear: candado cerrado, sin la etiqueta de texto.
    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->assertSee('aria-label="Perfil sin desbloquear"', false)
        ->assertSee('Asociar a publicación');

    Desbloqueo::query()->create([
        'empresa_id' => $empresa->id,
        'postulante_id' => $match->postulante_id,
    ]);

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->assertSee('aria-label="Perfil desbloqueado"', false);
});
