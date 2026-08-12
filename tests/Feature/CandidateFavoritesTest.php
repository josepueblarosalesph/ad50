<?php

use App\Livewire\Empresa\Busquedas;
use App\Livewire\Empresa\Candidato;
use App\Livewire\Empresa\FiltroActualizacion;
use App\Livewire\Empresa\Panel;
use App\Livewire\Empresa\Resultados;
use App\Models\Busqueda;
use App\Models\BusquedaCandidato;
use App\Models\Empresa;
use App\Models\Favorito;
use App\Models\Plan;
use App\Models\Postulante;
use App\Models\Publicacion;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;

test('a company can mark and filter favorite candidates within a search', function () {
    [$empresaUser, $busqueda, $matches] = candidateSearchWithMatches();

    Livewire::actingAs($empresaUser)
        ->test(Resultados::class, ['busqueda' => $busqueda])
        ->call('toggleFavorito', $matches[1]->postulante_id)
        ->assertHasNoErrors()
        ->call('mostrar', 'favoritos')
        ->assertSet('filtro', 'favoritos')
        ->assertViewHas('candidatos', fn ($candidatos) => $candidatos->total() === 1);

    $empresa = $empresaUser->empresa;

    expect($empresa->haMarcadoFavorito($matches[1]->postulante_id))->toBeTrue()
        ->and($empresa->haMarcadoFavorito($matches[0]->postulante_id))->toBeFalse();
});

test('candidates can be filtered by how recently their profile was updated', function () {
    [$empresaUser, $busqueda, $matches] = candidateSearchWithMatches();

    // Antigüedad de la última actualización de cada ficha.
    $matches[0]->postulante->update(['updated_at' => now()->subDays(10)]);   // hasta 1 mes
    $matches[1]->postulante->update(['updated_at' => now()->subMonths(2)]);  // entre 1 y 3 meses
    $matches[2]->postulante->update(['updated_at' => now()->subMonths(8)]);  // más de 6 meses

    $component = Livewire::actingAs($empresaUser)->test(Resultados::class, ['busqueda' => $busqueda]);

    $totalPara = fn (string $rango): int => $component->set('actualizacion', $rango)->viewData('candidatos')->total();

    expect($totalPara('todas'))->toBe(3)
        ->and($totalPara('mes'))->toBe(1)
        ->and($totalPara('1a3'))->toBe(1)
        ->and($totalPara('3a6'))->toBe(0)
        ->and($totalPara('mas6'))->toBe(1);

    // El único visible en "hasta 1 mes" es el postulante actualizado hace 10 días.
    $component->set('actualizacion', 'mes')
        ->assertViewHas('candidatos', fn ($candidatos) => $candidatos->pluck('postulante_id')->all() === [$matches[0]->postulante_id]);
});

test('the sidebar recency control drives the results filter through an event', function () {
    [$empresaUser, $busqueda, $matches] = candidateSearchWithMatches();

    $matches[0]->postulante->update(['updated_at' => now()->subDays(5)]);
    $matches[1]->postulante->update(['updated_at' => now()->subMonths(2)]);
    $matches[2]->postulante->update(['updated_at' => now()->subMonths(8)]);

    // El control del menú lateral emite el evento con el rango elegido.
    Livewire::actingAs($empresaUser)
        ->test(FiltroActualizacion::class, ['actual' => 'todas'])
        ->set('actualizacion', 'mes')
        ->assertDispatched('actualizacion-cambiada', valor: 'mes');

    // Resultados reacciona al evento y acota el listado.
    Livewire::actingAs($empresaUser)
        ->test(Resultados::class, ['busqueda' => $busqueda])
        ->dispatch('actualizacion-cambiada', valor: 'mes')
        ->assertSet('actualizacion', 'mes')
        ->assertViewHas('candidatos', fn ($candidatos) => $candidatos->pluck('postulante_id')->all() === [$matches[0]->postulante_id]);
});

test('candidate cards show career name and professional summary instead of criteria tags', function () {
    [$empresaUser, $busqueda, $matches] = candidateSearchWithMatches();
    $postulante = $matches[0]->postulante;
    $resumenProfesional = str_repeat('Experiencia ejecutiva. ', 8);

    $postulante->user->update(['name' => 'María José Fuentes', 'nombres' => 'María José', 'apellidos' => 'Fuentes']);
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
        ->assertSee('María José')
        ->assertDontSee('Fuentes')
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
    $matches[1]->postulante->user->update(['name' => 'María José Fuentes', 'nombres' => 'María José', 'apellidos' => 'Fuentes']);
    $matches[1]->postulante->update(['carrera' => 'Ingeniería Comercial']);

    Livewire::actingAs($empresaUser)
        ->test(Candidato::class, ['match' => $matches[1]])
        ->assertSee('María José')
        ->assertDontSee('Fuentes')
        ->assertSee('Ingeniería Comercial')
        ->assertDontSee('Perfil profesional #'.$matches[1]->postulante_id)
        ->assertSet('anteriorId', $matches[0]->id)
        ->assertSet('siguienteId', $matches[2]->id)
        ->assertSet('posicion', 2)
        ->assertSet('totalCandidatos', 3)
        ->call('toggleFavorito')
        ->assertSet('esFavorito', true);

    expect($matches[1]->busqueda->empresa->haMarcadoFavorito($matches[1]->postulante_id))->toBeTrue();
});

test('candidate detail preserves the favorites filter while navigating and returning to results', function () {
    [$empresaUser, $busqueda, $matches] = candidateSearchWithMatches();
    marcarFavorito($matches[0]);
    marcarFavorito($matches[2]);

    Livewire::withQueryParams(['filtro' => 'favoritos'])
        ->actingAs($empresaUser)
        ->test(Candidato::class, ['match' => $matches[0]])
        ->assertSet('filtro', 'favoritos')
        ->assertSet('anteriorId', null)
        ->assertSet('siguienteId', $matches[2]->id)
        ->assertSet('posicion', 1)
        ->assertSet('totalCandidatos', 2)
        ->assertSee("cambiarFiltro('favoritos')", escape: false)
        ->assertSee(route('empresa.candidatos.show', [
            'match' => $matches[2],
            'filtro' => 'favoritos',
        ]), escape: false)
        ->assertSee(route('empresa.resultados', [
            'busqueda' => $busqueda,
            'filtro' => 'favoritos',
        ]), escape: false);
});

test('changing the filter from the candidate detail recomputes the navigation', function () {
    [$empresaUser, $busqueda, $matches] = candidateSearchWithMatches();
    marcarFavorito($matches[0]);
    marcarFavorito($matches[2]);

    // Estando en "todos" sobre un candidato favorito, cambiar a "favoritos" mantiene el candidato
    // y recalcula el set (2 favoritos).
    Livewire::actingAs($empresaUser)
        ->test(Candidato::class, ['match' => $matches[0]])
        ->assertSet('filtro', 'todos')
        ->assertSet('totalCandidatos', 3)
        ->call('cambiarFiltro', 'favoritos')
        ->assertSet('filtro', 'favoritos')
        ->assertSet('totalCandidatos', 2)
        ->assertSet('siguienteId', $matches[2]->id);
});

test('changing to favorites on a non-favorite candidate redirects to the first favorite', function () {
    [$empresaUser, $busqueda, $matches] = candidateSearchWithMatches();
    marcarFavorito($matches[2]);

    // matches[0] no es favorito: al cambiar a "favoritos" salta al primer favorito.
    Livewire::actingAs($empresaUser)
        ->test(Candidato::class, ['match' => $matches[0]])
        ->call('cambiarFiltro', 'favoritos')
        ->assertRedirect(route('empresa.candidatos.show', ['match' => $matches[2]->id, 'filtro' => 'favoritos']));
});

test('a company cannot favorite a candidate from another company search', function () {
    [$empresaUser, $busqueda] = candidateSearchWithMatches();
    [, , $foreignMatches] = candidateSearchWithMatches();

    Livewire::actingAs($empresaUser)
        ->test(Resultados::class, ['busqueda' => $busqueda])
        // El candidato no pertenece a esta búsqueda: no se puede guardar desde aquí.
        ->call('toggleFavorito', $foreignMatches[0]->postulante_id)
        ->assertNotFound();

    expect($empresaUser->empresa->haMarcadoFavorito($foreignMatches[0]->postulante_id))->toBeFalse()
        ->and($foreignMatches[0]->busqueda->empresa->haMarcadoFavorito($foreignMatches[0]->postulante_id))->toBeFalse();
});

test('candidate totals in the searches listing link to their search results', function () {
    [$empresaUser, $busqueda] = candidateSearchWithMatches();

    Livewire::actingAs($empresaUser)
        ->test(Busquedas::class)
        ->assertViewHas('busquedas', fn ($busquedas) => $busquedas->first()->candidatos_count === 3)
        ->assertSeeHtml('href="'.route('empresa.resultados', $busqueda).'"');
});

test('el resumen del panel muestra publicaciones, desbloqueos y favoritos', function () {
    [$empresaUser, $busqueda, $matches] = candidateSearchWithMatches();

    $empresa = $empresaUser->empresa;
    darPlanA($empresa, Plan::query()->create([
        'codigo' => 'empresa_resumen',
        'nombre' => 'AD+50 · Resumen',
        'audiencia' => 'empresa',
        'precio_clp' => 50000,
        'publicaciones' => 5,
        'desbloqueos' => 4,
    ]));

    // Dos publicaciones vigentes y una cerrada: el cupo se consume con las tres.
    Publicacion::factory()->count(2)->create(['empresa_id' => $empresa->id]);
    Publicacion::factory()->create(['empresa_id' => $empresa->id, 'estado' => 'cerrada']);

    $empresa->desbloqueos()->create(['postulante_id' => $matches[0]->postulante_id]);

    $empresa->alternarFavorito($matches[0]->postulante_id, $busqueda->id);
    $empresa->alternarFavorito($matches[1]->postulante_id, $busqueda->id);

    Livewire::actingAs($empresaUser)
        ->test(Panel::class)
        ->assertViewHas('publicacionesVigentes', 2)
        ->assertViewHas('publicacionesDisponibles', 2)
        ->assertViewHas('publicacionesTotales', 5)
        // Uno de los 4 desbloqueos del plan ya está usado: quedan 3.
        ->assertViewHas('desbloqueosDisponibles', 3)
        ->assertViewHas('desbloqueosTotales', 4)
        ->assertViewHas('totalFavoritos', 2)
        ->assertSee('Publicaciones vigentes')
        ->assertSee('Publicaciones disponibles')
        ->assertSee('Desbloqueos disponibles (candidatos)')
        // El número de favoritos lleva a su listado.
        ->assertSee(route('empresa.favoritos'), escape: false);
});

test('sin plan el resumen no inventa cupos', function () {
    [$empresaUser] = candidateSearchWithMatches();

    Livewire::actingAs($empresaUser)
        ->test(Panel::class)
        ->assertViewHas('tienePlan', false)
        ->assertViewHas('publicacionesTotales', null)
        ->assertViewHas('desbloqueosTotales', 0)
        // Sin cupo que mostrar, la tarjeta queda en «—»: nunca en «Ilimitadas».
        ->assertDontSee('Ilimitadas');
});

test('el panel invita a buscar candidatos en vez de a crear una búsqueda', function () {
    [$empresaUser] = candidateSearchWithMatches();

    Livewire::actingAs($empresaUser)
        ->test(Panel::class)
        ->assertSee('Buscar candidatos')
        ->assertDontSee('Nueva búsqueda')
        ->assertSee(route('empresa.busquedas.create'), escape: false);
});

test('company panel summarizes at most five recent publications', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $empresaUser->id, 'razon_social' => 'Empresa Resumen']);

    Publicacion::factory()->count(7)->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
    ]);

    Livewire::actingAs($empresaUser)
        ->test(Panel::class)
        ->assertViewHas('publicaciones', fn ($publicaciones) => $publicaciones->count() === 5)
        ->assertSee('Mis publicaciones recientes')
        ->assertSee('Ver más')
        ->assertSee(route('empresa.publicaciones.index'), escape: false);
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
/** Guarda al candidato de un match en los favoritos de la empresa dueña de la búsqueda. */
function marcarFavorito(BusquedaCandidato $match): Favorito
{
    return Favorito::query()->firstOrCreate([
        'empresa_id' => $match->busqueda->empresa_id,
        'postulante_id' => $match->postulante_id,
    ], ['busqueda_id' => $match->busqueda_id]);
}

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
