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
        'contenido' => 'Excelente candidato, avanzar a entrevista.',
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
    NotaCandidato::query()->create(['empresa_id' => $match->busqueda->empresa_id, 'postulante_id' => $match->postulante_id, 'contenido' => 'algo']);

    Livewire::actingAs($match->busqueda->empresa->user)
        ->test(Candidato::class, ['match' => $match])
        ->set('nota', '')
        ->call('guardarNota');

    expect(NotaCandidato::query()->count())->toBe(0);
});

test('the results list shows a note indicator only for candidates with a note', function () {
    $match = matchConEmpresa();
    NotaCandidato::query()->create(['empresa_id' => $match->busqueda->empresa_id, 'postulante_id' => $match->postulante_id, 'contenido' => 'Con nota']);

    Livewire::actingAs($match->busqueda->empresa->user)
        ->test(Resultados::class, ['busqueda' => $match->busqueda])
        ->assertSee('Tienes una nota sobre este candidato');

    NotaCandidato::query()->truncate();

    Livewire::actingAs($match->busqueda->empresa->user)
        ->test(Resultados::class, ['busqueda' => $match->busqueda])
        ->assertDontSee('Tienes una nota sobre este candidato');
});
