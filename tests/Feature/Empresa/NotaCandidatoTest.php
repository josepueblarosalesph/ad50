<?php

use App\Livewire\Empresa\Candidato;
use App\Livewire\Empresa\Resultados;
use App\Models\BusquedaCandidato;
use App\Models\Empresa;
use App\Models\NotaCandidato;
use App\Models\Postulante;
use App\Models\User;
use Livewire\Livewire;

function matchConEmpresa(?Postulante $postulante = null, ?Empresa $empresa = null): BusquedaCandidato
{
    $empresa ??= Empresa::query()->create([
        'user_id' => User::factory()->create(['role' => 'empresa'])->id,
        'razon_social' => 'Empresa Nota', 'estado_activacion' => 'activa',
    ]);
    $postulante ??= Postulante::query()->create(['user_id' => User::factory()->create(['role' => 'postulante'])->id, 'visible' => true]);
    $busqueda = $empresa->busquedas()->create(['titulo' => 'B', 'criterios' => []]);

    return $busqueda->candidatos()->create([
        'postulante_id' => $postulante->id,
        'match_score' => 100, 'criterios_cumplidos' => 0, 'criterios_totales' => 0, 'estado_match' => 'cumple',
    ]);
}

test('a recruiter can save a private note tied to the company and candidate', function () {
    $match = matchConEmpresa();

    Livewire::actingAs($match->busqueda->empresa->user)
        ->test(Candidato::class, ['match' => $match])
        ->assertSet('nota', '')
        ->set('nota', 'Excelente candidato, avanzar a entrevista.')
        ->call('guardarNota')
        ->assertHasNoErrors()
        ->assertSet('notaGuardada', true);

    $this->assertDatabaseHas('notas_candidato', [
        'empresa_id' => $match->busqueda->empresa_id,
        'postulante_id' => $match->postulante_id,
        'user_id' => $match->busqueda->empresa->user_id,
        'contenido' => 'Excelente candidato, avanzar a entrevista.',
        'visibilidad' => 'equipo',
    ]);
});

test('the note persists across different searches of the same company', function () {
    $empresa = Empresa::query()->create(['user_id' => User::factory()->create(['role' => 'empresa'])->id, 'razon_social' => 'E', 'estado_activacion' => 'activa']);
    $postulante = Postulante::query()->create(['user_id' => User::factory()->create(['role' => 'postulante'])->id, 'visible' => true]);

    $match1 = matchConEmpresa($postulante, $empresa);
    Livewire::actingAs($empresa->user)->test(Candidato::class, ['match' => $match1])
        ->set('nota', 'Nota compartida')->call('guardarNota');

    // Otra búsqueda de la MISMA empresa con el MISMO postulante: la nota aparece.
    $match2 = matchConEmpresa($postulante, $empresa->fresh());
    Livewire::actingAs($empresa->user)->test(Candidato::class, ['match' => $match2])
        ->assertSet('nota', 'Nota compartida');

    expect(NotaCandidato::query()->count())->toBe(1);
});

test('saving an empty note removes it', function () {
    $match = matchConEmpresa();
    NotaCandidato::query()->create(['empresa_id' => $match->busqueda->empresa_id, 'postulante_id' => $match->postulante_id, 'user_id' => $match->busqueda->empresa->user_id, 'contenido' => 'algo']);

    Livewire::actingAs($match->busqueda->empresa->user)
        ->test(Candidato::class, ['match' => $match])
        ->set('nota', '')
        ->call('guardarNota');

    expect(NotaCandidato::query()->count())->toBe(0);
});

test('el botón de notas del listado avisa si el candidato tiene alguna', function () {
    $match = matchConEmpresa();
    NotaCandidato::query()->create(['empresa_id' => $match->busqueda->empresa_id, 'postulante_id' => $match->postulante_id, 'user_id' => $match->busqueda->empresa->user_id, 'contenido' => 'Con nota']);

    Livewire::actingAs($match->busqueda->empresa->user)
        ->test(Resultados::class, ['busqueda' => $match->busqueda])
        ->assertSee('Ver notas de este candidato');

    NotaCandidato::query()->truncate();

    // Sin notas el botón sigue estando, pero anuncia que no hay nada escrito.
    Livewire::actingAs($match->busqueda->empresa->user)
        ->test(Resultados::class, ['busqueda' => $match->busqueda])
        ->assertDontSee('Ver notas de este candidato')
        ->assertSee('Sin notas todavía');
});

test('el listado abre las notas del candidato en un panel de lectura rápida', function () {
    [$admin, $colega, $match] = equipoConCandidato();

    Livewire::actingAs($colega)->test(Candidato::class, ['match' => $match])
        ->set('nota', 'Compartida por Beto')->set('visibilidad', 'equipo')->call('guardarNota');

    Livewire::actingAs($admin)->test(Candidato::class, ['match' => $match])
        ->set('nota', 'Privada de Ana')->set('visibilidad', 'privada')->call('guardarNota');

    // Ana abre el panel y lee la suya y la que su colega compartió.
    Livewire::actingAs($admin)
        ->test(Resultados::class, ['busqueda' => $match->busqueda])
        ->call('abrirNotas', $match->postulante_id)
        ->assertSet('notasPostulanteId', $match->postulante_id)
        ->assertSee('Notas del candidato')
        ->assertSee('Privada de Ana')
        ->assertSee('Compartida por Beto')
        ->assertSee('Beto Colega')
        ->call('cerrarNotas')
        ->assertSet('notasPostulanteId', null);

    // Beto no ve la nota privada de Ana.
    Livewire::actingAs($colega)
        ->test(Resultados::class, ['busqueda' => $match->busqueda])
        ->call('abrirNotas', $match->postulante_id)
        ->assertSee('Compartida por Beto')
        ->assertDontSee('Privada de Ana');
});

test('no se pueden abrir las notas de un candidato ajeno a la búsqueda', function () {
    [$admin, , $match] = equipoConCandidato();
    $ajeno = matchConEmpresa();

    Livewire::actingAs($admin)
        ->test(Resultados::class, ['busqueda' => $match->busqueda])
        ->call('abrirNotas', $ajeno->postulante_id)
        ->assertStatus(404);
});

/**
 * Empresa con dos usuarios (administrador + un colega de Equipo) y un candidato en común.
 *
 * @return array{0: User, 1: User, 2: BusquedaCandidato}
 */
function equipoConCandidato(): array
{
    $admin = User::factory()->create(['role' => 'empresa', 'name' => 'Ana Admin']);
    $empresa = Empresa::query()->create([
        'user_id' => $admin->id, 'razon_social' => 'Equipo SpA', 'estado_activacion' => 'activa',
    ]);
    $colega = User::factory()->create(['role' => 'empresa', 'name' => 'Beto Colega', 'empresa_id' => $empresa->id]);

    return [$admin->fresh(), $colega->fresh(), matchConEmpresa(null, $empresa->fresh())];
}

test('cada usuario del equipo tiene su propia nota: una no pisa a la otra', function () {
    [$admin, $colega, $match] = equipoConCandidato();

    Livewire::actingAs($admin)->test(Candidato::class, ['match' => $match])
        ->set('nota', 'Nota de Ana')->call('guardarNota')->assertHasNoErrors();

    // El colega abre al mismo candidato: su campo está vacío, no hereda la de Ana.
    Livewire::actingAs($colega)->test(Candidato::class, ['match' => $match])
        ->assertSet('nota', '')
        ->set('nota', 'Nota de Beto')->call('guardarNota')->assertHasNoErrors();

    expect(NotaCandidato::query()->count())->toBe(2);

    // Y cada uno sigue viendo la suya al volver.
    Livewire::actingAs($admin)->test(Candidato::class, ['match' => $match])->assertSet('nota', 'Nota de Ana');
    Livewire::actingAs($colega)->test(Candidato::class, ['match' => $match])->assertSet('nota', 'Nota de Beto');
});

test('una nota compartida la lee el equipo; una privada no sale del autor', function () {
    [$admin, $colega, $match] = equipoConCandidato();

    Livewire::actingAs($admin)->test(Candidato::class, ['match' => $match])
        ->set('nota', 'Esto lo comparto')
        ->set('visibilidad', 'equipo')
        ->call('guardarNota');

    // El colega la ve en el bloque de notas del equipo, con el nombre del autor.
    Livewire::actingAs($colega)->test(Candidato::class, ['match' => $match])
        ->assertViewHas('notasDelEquipo', fn ($notas): bool => $notas->count() === 1)
        ->assertSee('Esto lo comparto')
        ->assertSee('Ana Admin');

    // Ana la pasa a privada: desaparece para el colega.
    Livewire::actingAs($admin)->test(Candidato::class, ['match' => $match])
        ->set('visibilidad', 'privada')
        ->call('guardarNota');

    Livewire::actingAs($colega)->test(Candidato::class, ['match' => $match])
        ->assertViewHas('notasDelEquipo', fn ($notas): bool => $notas->isEmpty())
        ->assertDontSee('Esto lo comparto');

    // Pero Ana la sigue teniendo.
    Livewire::actingAs($admin)->test(Candidato::class, ['match' => $match])
        ->assertSet('nota', 'Esto lo comparto')
        ->assertSet('visibilidad', 'privada');
});

test('la nota propia no se repite en el bloque de notas del equipo', function () {
    [$admin, , $match] = equipoConCandidato();

    Livewire::actingAs($admin)->test(Candidato::class, ['match' => $match])
        ->set('nota', 'La mía')->call('guardarNota');

    Livewire::actingAs($admin)->test(Candidato::class, ['match' => $match])
        ->assertViewHas('notasDelEquipo', fn ($notas): bool => $notas->isEmpty());
});

test('una visibilidad fuera del catálogo se rechaza', function () {
    [$admin, , $match] = equipoConCandidato();

    Livewire::actingAs($admin)->test(Candidato::class, ['match' => $match])
        ->set('nota', 'Algo')
        ->set('visibilidad', 'inventada')
        ->call('guardarNota')
        ->assertHasErrors('visibilidad');

    expect(NotaCandidato::query()->count())->toBe(0);
});

test('el indicador del listado solo cuenta las notas que ese usuario puede ver', function () {
    [$admin, $colega, $match] = equipoConCandidato();

    Livewire::actingAs($admin)->test(Candidato::class, ['match' => $match])
        ->set('nota', 'Privada de Ana')
        ->set('visibilidad', 'privada')
        ->call('guardarNota');

    // Ana ve el aviso; el colega no, porque la única nota es privada de Ana.
    Livewire::actingAs($admin)->test(Resultados::class, ['busqueda' => $match->busqueda])
        ->assertSee('Ver notas de este candidato');

    Livewire::actingAs($colega)->test(Resultados::class, ['busqueda' => $match->busqueda])
        ->assertDontSee('Ver notas de este candidato')
        ->assertSee('Sin notas todavía');
});

test('al sacar a alguien del equipo se van sus notas privadas y quedan las compartidas', function () {
    [$admin, $colega, $match] = equipoConCandidato();

    Livewire::actingAs($colega)->test(Candidato::class, ['match' => $match])
        ->set('nota', 'Compartida de Beto')->set('visibilidad', 'equipo')->call('guardarNota');

    $otroMatch = matchConEmpresa(null, $match->busqueda->empresa);
    Livewire::actingAs($colega)->test(Candidato::class, ['match' => $otroMatch])
        ->set('nota', 'Privada de Beto')->set('visibilidad', 'privada')->call('guardarNota');

    $colega->delete();

    expect(NotaCandidato::query()->where('contenido', 'Privada de Beto')->exists())->toBeFalse()
        ->and(NotaCandidato::query()->where('contenido', 'Compartida de Beto')->exists())->toBeTrue();

    // La compartida sigue visible para el equipo, atribuida a un usuario que ya no está.
    Livewire::actingAs($admin)->test(Candidato::class, ['match' => $match])
        ->assertSee('Compartida de Beto')
        ->assertSee('Usuario eliminado');
});
