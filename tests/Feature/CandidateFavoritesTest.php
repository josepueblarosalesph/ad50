<?php

use App\Livewire\Empresa\Candidato;
use App\Livewire\Empresa\Panel;
use App\Livewire\Empresa\Resultados;
use App\Livewire\Postulante\Panel as PostulantePanel;
use App\Models\Busqueda;
use App\Models\BusquedaCandidato;
use App\Models\Empresa;
use App\Models\Postulante;
use App\Models\User;
use Illuminate\Support\Str;
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

test('candidate cards show career name and professional summary instead of criteria tags', function () {
    [$empresaUser, $busqueda, $matches] = candidateSearchWithMatches();
    $postulante = $matches[0]->postulante;
    $resumenProfesional = str_repeat('Experiencia ejecutiva. ', 8);

    $postulante->user->update(['name' => 'María José Fuentes']);
    $postulante->update([
        'carrera' => 'Ingeniería Comercial',
        'cargo_actual' => 'Subgerente de Finanzas',
        'resumen_profesional' => $resumenProfesional,
    ]);
    $matches[0]->update(['criterios_detalle' => [[
        'criterio' => 'Experiencia mínima',
        'valor' => '5 años',
        'cumple' => true,
    ]]]);

    Livewire::actingAs($empresaUser)
        ->test(Resultados::class, ['busqueda' => $busqueda])
        ->assertSee('Ingeniería Comercial')
        ->assertSee('María José Fuentes')
        ->assertSee(Str::limit($resumenProfesional, 100, '…'))
        ->assertDontSee($resumenProfesional)
        ->assertDontSee('Perfil profesional #'.$postulante->id)
        ->assertDontSee('Subgerente de Finanzas')
        ->assertDontSee('Experiencia mínima: 5 años')
        ->assertDontSee('Selecciona criterios para filtrar quiénes los cumplen.')
        ->assertDontSee('Contacto disponible')
        ->assertDontSee('Editar filtros')
        ->assertDontSee('Mi plan');
});

test('candidate detail navigation follows the search result ranking', function () {
    [$empresaUser, $busqueda, $matches] = candidateSearchWithMatches();
    $matches[1]->postulante->user->update(['name' => 'María José Fuentes']);
    $matches[1]->postulante->update(['carrera' => 'Ingeniería Comercial']);

    Livewire::actingAs($empresaUser)
        ->test(Candidato::class, ['match' => $matches[1]])
        ->assertSee('María José Fuentes')
        ->assertSee('Ingeniería Comercial')
        ->assertDontSee('Perfil profesional #'.$matches[1]->postulante_id)
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

test('company panel summarizes at most five recent searches', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $empresaUser->id, 'razon_social' => 'Empresa Resumen']);

    foreach (range(1, 7) as $index) {
        $empresa->busquedas()->create(['titulo' => "Búsqueda {$index}", 'criterios' => []]);
    }

    Livewire::actingAs($empresaUser)
        ->test(Panel::class)
        ->assertViewHas('busquedas', fn ($busquedas) => $busquedas->count() === 5)
        ->assertSee('Ver más')
        ->assertSee(route('empresa.busquedas.index'), escape: false);
});

test('postulante panel counts unique companies that favorited the profile', function () {
    $postulanteUser = User::factory()->create(['role' => 'postulante']);
    $postulante = Postulante::query()->create([
        'user_id' => $postulanteUser->id,
        'visible' => true,
    ]);

    foreach ([2, 1] as $favoriteSearches) {
        $empresaUser = User::factory()->create(['role' => 'empresa']);
        $empresa = Empresa::query()->create([
            'user_id' => $empresaUser->id,
            'razon_social' => fake()->unique()->company(),
        ]);

        foreach (range(1, $favoriteSearches) as $index) {
            $busqueda = $empresa->busquedas()->create([
                'titulo' => "Búsqueda {$index}",
                'criterios' => [],
            ]);
            $busqueda->candidatos()->create([
                'postulante_id' => $postulante->id,
                'favorito' => true,
            ]);
        }
    }

    Livewire::actingAs($postulanteUser)
        ->test(PostulantePanel::class)
        ->assertViewHas('empresasInteresadas', 2)
        ->assertSee('Te han visto 2 empresas');
});

test('search criterion tags filter candidates that fulfill every selected criterion', function () {
    [$empresaUser, $busqueda, $matches] = candidateSearchWithMatches();
    addFilterableCriteria($busqueda, $matches);

    Livewire::actingAs($empresaUser)
        ->test(Resultados::class, ['busqueda' => $busqueda])
        ->call('toggleCriterio', 'ciudad')
        ->assertSet('criterios', ['ciudad'])
        ->assertViewHas('candidatos', fn ($candidatos) => $candidatos->total() === 2)
        ->call('toggleCriterio', 'industria')
        ->assertSet('criterios', ['ciudad', 'industria'])
        ->assertViewHas('candidatos', fn ($candidatos) => $candidatos->total() === 1)
        ->call('limpiarCriterios')
        ->assertSet('criterios', [])
        ->assertViewHas('candidatos', fn ($candidatos) => $candidatos->total() === 3);
});

test('candidate detail navigation respects active criterion filters', function () {
    [$empresaUser, $busqueda, $matches] = candidateSearchWithMatches();
    addFilterableCriteria($busqueda, $matches);

    Livewire::withQueryParams(['criterios' => ['ciudad']])
        ->actingAs($empresaUser)
        ->test(Candidato::class, ['match' => $matches[0]])
        ->assertSet('criterios', ['ciudad'])
        ->assertSet('anteriorId', null)
        ->assertSet('siguienteId', $matches[1]->id)
        ->assertSet('totalCandidatos', 2)
        ->assertSee('1 filtro activo')
        ->assertSee('Filtros activos')
        ->assertSee('Región')
        ->assertSee('Metropolitana de Santiago')
        ->assertSee('Estás navegando solo entre candidatos que cumplen todos estos criterios.');
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
            'estado_match' => 'cumple',
        ]);
    })->all();

    return [$empresaUser, $busqueda, $matches];
}

/**
 * @param  array<int, BusquedaCandidato>  $matches
 */
function addFilterableCriteria(Busqueda $busqueda, array $matches): void
{
    $busqueda->update([
        'criterios' => [
            'ciudad' => 'Metropolitana de Santiago',
            'industria' => 'Tecnología de la Información',
        ],
    ]);

    $matches[0]->update(['criterios_detalle' => [
        ['criterio' => 'Industria', 'valor' => 'Tecnología de la Información', 'cumple' => true],
        ['criterio' => 'Región', 'valor' => 'Metropolitana de Santiago', 'cumple' => true],
    ]]);
    $matches[1]->update(['criterios_detalle' => [
        ['criterio' => 'Industria', 'valor' => 'Tecnología de la Información', 'cumple' => false],
        ['criterio' => 'Región', 'valor' => 'Metropolitana de Santiago', 'cumple' => true],
    ]]);
    $matches[2]->update(['criterios_detalle' => [
        ['criterio' => 'Industria', 'valor' => 'Tecnología de la Información', 'cumple' => true],
        ['criterio' => 'Región', 'valor' => 'Metropolitana de Santiago', 'cumple' => false],
    ]]);
}
