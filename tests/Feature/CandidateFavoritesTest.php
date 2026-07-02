<?php

use App\Livewire\Empresa\Candidato;
use App\Livewire\Empresa\Panel;
use App\Livewire\Empresa\Resultados;
use App\Models\Busqueda;
use App\Models\BusquedaCandidato;
use App\Models\Empresa;
use App\Models\Postulante;
use App\Models\User;
use Livewire\Livewire;

test('a company can mark and filter favorite candidates within a search', function () {
    [$empresaUser, $busqueda, $matches] = candidateSearchWithMatches();

    Livewire::actingAs($empresaUser)
        ->test(Resultados::class, ['busqueda' => $busqueda])
        ->call('toggleFavorito', $matches[1]->id)
        ->assertHasNoErrors()
        ->call('mostrar', 'favoritos')
        ->assertSet('filtro', 'favoritos')
        ->assertViewHas('candidatos', fn ($candidatos) => $candidatos->total() === 1);

    expect($matches[1]->fresh()->favorito)->toBeTrue()
        ->and($matches[0]->fresh()->favorito)->toBeFalse();
});

test('candidate detail navigation follows the search result ranking', function () {
    [$empresaUser, $busqueda, $matches] = candidateSearchWithMatches();

    Livewire::actingAs($empresaUser)
        ->test(Candidato::class, ['match' => $matches[1]])
        ->assertSet('anteriorId', $matches[0]->id)
        ->assertSet('siguienteId', $matches[2]->id)
        ->assertSet('posicion', 2)
        ->assertSet('totalCandidatos', 3)
        ->call('toggleFavorito')
        ->assertSet('match.favorito', true);

    expect($matches[1]->fresh()->favorito)->toBeTrue();
});

test('candidate detail preserves the favorites filter while navigating and returning to results', function () {
    [$empresaUser, $busqueda, $matches] = candidateSearchWithMatches();
    $matches[0]->update(['favorito' => true]);
    $matches[2]->update(['favorito' => true]);

    Livewire::withQueryParams(['filtro' => 'favoritos'])
        ->actingAs($empresaUser)
        ->test(Candidato::class, ['match' => $matches[0]])
        ->assertSet('filtro', 'favoritos')
        ->assertSet('anteriorId', null)
        ->assertSet('siguienteId', $matches[2]->id)
        ->assertSet('posicion', 1)
        ->assertSet('totalCandidatos', 2)
        ->assertSee('Revisando favoritos')
        ->assertSee(route('empresa.candidatos.show', [
            'match' => $matches[2],
            'filtro' => 'favoritos',
        ]), escape: false)
        ->assertSee(route('empresa.resultados', [
            'busqueda' => $busqueda,
            'filtro' => 'favoritos',
        ]), escape: false);
});

test('a company cannot favorite a candidate from another company search', function () {
    [$empresaUser, $busqueda] = candidateSearchWithMatches();
    [, , $foreignMatches] = candidateSearchWithMatches();

    Livewire::actingAs($empresaUser)
        ->test(Resultados::class, ['busqueda' => $busqueda])
        ->call('toggleFavorito', $foreignMatches[0]->id)
        ->assertNotFound();

    expect($foreignMatches[0]->fresh()->favorito)->toBeFalse();
});

test('candidate totals in the company panel link to their search results', function () {
    [$empresaUser, $busqueda] = candidateSearchWithMatches();

    Livewire::actingAs($empresaUser)
        ->test(Panel::class)
        ->assertSeeHtml('aria-label="Ver los 3 candidatos de Búsqueda de liderazgo"')
        ->assertSeeHtml('href="'.route('empresa.resultados', $busqueda).'"');
});

/**
 * @return array{User, Busqueda, array<int, BusquedaCandidato>}
 */
function candidateSearchWithMatches(): array
{
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create([
        'user_id' => $empresaUser->id,
        'razon_social' => 'Empresa '.fake()->unique()->numerify('####'),
    ]);
    $busqueda = $empresa->busquedas()->create([
        'titulo' => 'Búsqueda de liderazgo',
        'criterios' => [],
    ]);

    $matches = collect([3, 2, 1])->map(function (int $criterios) use ($busqueda): BusquedaCandidato {
        $postulanteUser = User::factory()->create(['role' => 'postulante']);
        $postulante = Postulante::query()->create([
            'user_id' => $postulanteUser->id,
            'visible' => true,
            'cargo_actual' => 'Perfil '.$criterios,
        ]);

        return $busqueda->candidatos()->create([
            'postulante_id' => $postulante->id,
            'criterios_cumplidos' => $criterios,
            'criterios_totales' => 3,
            'estado_match' => $criterios === 3 ? 'cumple' : 'parcial',
        ]);
    })->all();

    return [$empresaUser, $busqueda, $matches];
}
