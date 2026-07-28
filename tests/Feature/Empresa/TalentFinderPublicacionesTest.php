<?php

use App\Livewire\Empresa\Candidato;
use App\Livewire\Empresa\DetallePublicacion;
use App\Livewire\Empresa\NuevaPublicacion;
use App\Livewire\Empresa\Publicaciones;
use App\Livewire\Empresa\Resultados;
use App\Models\Busqueda;
use App\Models\BusquedaCandidato;
use App\Models\Empresa;
use App\Models\Plan;
use App\Models\Postulante;
use App\Models\Publicacion;
use App\Models\PublicacionCandidato;
use App\Models\User;
use App\Support\CatalogosProfesionales;
use Livewire\Livewire;

/**
 * Empresa operativa con un plan cuyo cupo de publicaciones es `$cupo` (null = ilimitadas).
 *
 * @return array{0: User, 1: Empresa}
 */
function empresaConCupo(?int $cupo): array
{
    $user = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => 'Empresa Cupo '.fake()->unique()->numerify('####'),
        'estado_activacion' => 'activa',
    ]);

    $empresa->update([
        'datos_enviados_at' => now(),
        'plan_id' => Plan::query()->create([
            'codigo' => 'cupo_'.str()->random(8),
            'nombre' => 'Plan con cupo',
            'audiencia' => 'empresa',
            'precio_clp' => 50000,
            'periodo' => 'mensual',
            'desbloqueos' => 10,
            'publicaciones' => $cupo,
        ])->id,
        'plan_hasta' => now()->addMonth(),
    ]);

    return [$user->fresh(), $empresa->fresh()];
}

/** Datos válidos mínimos para crear una publicación desde el formulario. */
function datosPublicacionValidos(): array
{
    return [
        'cargo' => 'Jefe de Operaciones',
        'tipoCargo' => CatalogosProfesionales::tiposTrabajo()[0],
        'vacantes' => 1,
        'descripcion' => str_repeat('Buscamos un profesional con experiencia comprobable en operaciones industriales. ', 4),
        'modalidad' => 'Presencial',
        'pais' => 'Chile',
        'comuna' => 'Providencia',
        'actividadEmpresa' => CatalogosProfesionales::industrias()[0],
        'jerarquia' => CatalogosProfesionales::jerarquias()[0],
        'requisitos' => 'Cinco años de experiencia en cargos equivalentes.',
        'experienciaLaboral' => array_values(CatalogosProfesionales::rangosExperiencia())[0],
        'estudiosMinimos' => CatalogosProfesionales::nivelesEstudio()[0],
        'situacionAcademica' => CatalogosProfesionales::situacionesEstudio()[0],
        'vigenciaDias' => 30,
    ];
}

// ─────────────────────────── Cupo de publicaciones ───────────────────────────

test('el cupo del plan cuenta las publicaciones creadas y no se recupera al cerrar ni eliminar', function () {
    [, $empresa] = empresaConCupo(3);

    expect($empresa->publicacionesTotales())->toBe(3)
        ->and($empresa->publicacionesUsadas())->toBe(0)
        ->and($empresa->publicacionesDisponibles())->toBe(3)
        ->and($empresa->puedePublicar())->toBeTrue();

    $cerrada = Publicacion::factory()->create(['empresa_id' => $empresa->id, 'estado' => 'cerrada']);
    $eliminada = Publicacion::factory()->create(['empresa_id' => $empresa->id]);
    $eliminada->delete();

    // Cerrar y eliminar no devuelven cupo: el consumo es por publicación creada.
    expect($empresa->fresh()->publicacionesUsadas())->toBe(2)
        ->and($empresa->fresh()->publicacionesDisponibles())->toBe(1)
        ->and($cerrada->exists)->toBeTrue();
});

test('un plan sin cupo definido deja publicar sin límite', function () {
    [, $empresa] = empresaConCupo(null);

    Publicacion::factory()->count(5)->create(['empresa_id' => $empresa->id]);

    expect($empresa->fresh()->publicacionesTotales())->toBeNull()
        ->and($empresa->fresh()->publicacionesDisponibles())->toBeNull()
        ->and($empresa->fresh()->tienePublicacionesIlimitadas())->toBeTrue()
        ->and($empresa->fresh()->puedePublicar())->toBeTrue();
});

test('con el cupo agotado el formulario de nueva publicación redirige al listado', function () {
    [$user, $empresa] = empresaConCupo(1);
    Publicacion::factory()->create(['empresa_id' => $empresa->id]);

    expect($empresa->fresh()->puedePublicar())->toBeFalse();

    Livewire::actingAs($user)
        ->test(NuevaPublicacion::class)
        ->assertRedirect(route('empresa.publicaciones.index'));

    expect(session('publicacion_error'))->toContain('máximo de publicaciones');
});

test('si el cupo se agota con el formulario abierto, guardar no crea la publicación', function () {
    [$user, $empresa] = empresaConCupo(1);

    // El formulario se abre con cupo disponible.
    $componente = Livewire::actingAs($user)
        ->test(NuevaPublicacion::class)
        ->set(datosPublicacionValidos());

    // Otro usuario del equipo consume la última publicación antes de este envío.
    Publicacion::factory()->create(['empresa_id' => $empresa->id, 'cargo' => 'Ya publicada']);

    $componente->call('guardar')->assertRedirect(route('empresa.publicaciones.index'));

    expect(Publicacion::query()->where('empresa_id', $empresa->id)->count())->toBe(1);
});

test('con cupo disponible la publicación se crea normalmente', function () {
    [$user, $empresa] = empresaConCupo(2);

    Livewire::actingAs($user)
        ->test(NuevaPublicacion::class)
        ->set(datosPublicacionValidos())
        ->call('guardar')
        ->assertHasNoErrors();

    expect(Publicacion::query()->where('empresa_id', $empresa->id)->count())->toBe(1)
        ->and($empresa->fresh()->publicacionesDisponibles())->toBe(1);
});

test('el listado muestra el cupo usado y esconde el botón de publicar cuando se agota', function () {
    [$user, $empresa] = empresaConCupo(2);
    Publicacion::factory()->create(['empresa_id' => $empresa->id]);

    Livewire::actingAs($user)
        ->test(Publicaciones::class)
        ->assertViewHas('publicacionesUsadas', 1)
        ->assertViewHas('publicacionesDisponibles', 1)
        ->assertViewHas('puedePublicar', true)
        ->assertSee('Usaste 1 de 2 publicaciones');

    Publicacion::factory()->create(['empresa_id' => $empresa->id]);

    Livewire::actingAs($user)
        ->test(Publicaciones::class)
        ->assertViewHas('puedePublicar', false)
        ->assertSee('Ampliar plan para publicar')
        ->assertDontSee('href="'.route('empresa.publicaciones.create').'"', false);
});

// ──────────────── Asociación de candidatos a publicaciones ────────────────

/**
 * Empresa operativa con una búsqueda, un candidato que calza y una publicación.
 *
 * @return array{0: User, 1: Busqueda, 2: BusquedaCandidato, 3: Publicacion}
 */
function talentFinderConPublicacion(): array
{
    [$user, $empresa] = empresaConCupo(null);

    $busqueda = $empresa->busquedas()->create([
        'titulo' => 'Búsqueda de liderazgo',
        'criterios' => [],
    ]);

    $postulante = Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true,
        'cargo_actual' => 'Gerente de Operaciones',
    ]);

    $match = $busqueda->candidatos()->create([
        'postulante_id' => $postulante->id,
        'criterios_cumplidos' => 1,
        'criterios_totales' => 1,
        'estado_match' => 'cumple',
    ]);

    $publicacion = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'cargo' => 'Jefe de Planta',
        'estado' => 'publicada',
    ]);

    return [$user, $busqueda, $match, $publicacion];
}

test('desde los resultados se asocia y desasocia un candidato a una publicación', function () {
    [$user, $busqueda, $match, $publicacion] = talentFinderConPublicacion();

    $componente = Livewire::actingAs($user)
        ->test(Resultados::class, ['busqueda' => $busqueda])
        ->call('abrirAsociacion', $match->postulante_id)
        ->assertSet('asociandoPostulanteId', $match->postulante_id)
        ->call('toggleAsociacion', $publicacion->id)
        ->assertHasNoErrors();

    expect($publicacion->candidatos()->pluck('postulantes.id')->all())->toBe([$match->postulante_id]);

    // La búsqueda de origen queda registrada solo como trazabilidad.
    expect(PublicacionCandidato::query()->sole()->busqueda_id)->toBe($busqueda->id);

    $componente->call('toggleAsociacion', $publicacion->id);

    expect($publicacion->candidatos()->count())->toBe(0);
});

test('un candidato puede quedar asociado a varias publicaciones a la vez', function () {
    [$user, $busqueda, $match, $publicacion] = talentFinderConPublicacion();
    $otra = Publicacion::factory()->create([
        'empresa_id' => $busqueda->empresa_id,
        'cargo' => 'Subgerente de Planta',
    ]);

    Livewire::actingAs($user)
        ->test(Resultados::class, ['busqueda' => $busqueda])
        ->call('abrirAsociacion', $match->postulante_id)
        ->call('toggleAsociacion', $publicacion->id)
        ->call('toggleAsociacion', $otra->id)
        ->assertViewHas('publicacionesDelCandidato', fn (array $ids): bool => count($ids) === 2);

    expect(Postulante::query()->find($match->postulante_id)->publicacionesAsociadas()->count())->toBe(2);
});

test('la asociación sobrevive a que el candidato deje de calzar con la búsqueda', function () {
    [$user, $busqueda, $match, $publicacion] = talentFinderConPublicacion();

    Livewire::actingAs($user)
        ->test(Resultados::class, ['busqueda' => $busqueda])
        ->call('abrirAsociacion', $match->postulante_id)
        ->call('toggleAsociacion', $publicacion->id);

    // El matching elimina la fila pivote cuando el candidato deja de cumplir.
    $match->delete();

    expect($publicacion->fresh()->candidatos()->pluck('postulantes.id')->all())
        ->toBe([$match->postulante_id]);
});

test('no se puede asociar un candidato ajeno a la búsqueda', function () {
    [$user, $busqueda, , $publicacion] = talentFinderConPublicacion();
    $ajeno = Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true,
    ]);

    Livewire::actingAs($user)
        ->test(Resultados::class, ['busqueda' => $busqueda])
        ->call('abrirAsociacion', $ajeno->id)
        ->assertStatus(404);

    expect(PublicacionCandidato::query()->count())->toBe(0)
        ->and($publicacion->candidatos()->count())->toBe(0);
});

test('no se puede asociar a una publicación de otra empresa', function () {
    [$user, $busqueda, $match] = talentFinderConPublicacion();
    [, $otraEmpresa] = empresaConCupo(null);
    $ajena = Publicacion::factory()->create(['empresa_id' => $otraEmpresa->id]);

    Livewire::actingAs($user)
        ->test(Resultados::class, ['busqueda' => $busqueda])
        ->call('abrirAsociacion', $match->postulante_id)
        ->call('toggleAsociacion', $ajena->id)
        ->assertStatus(404);

    expect(PublicacionCandidato::query()->count())->toBe(0);
});

test('desde el detalle del candidato se asocia a una publicación', function () {
    [$user, , $match, $publicacion] = talentFinderConPublicacion();

    Livewire::actingAs($user)
        ->test(Candidato::class, ['match' => $match])
        ->assertViewHas('totalAsociaciones', 0)
        ->call('abrirAsociacion', $match->postulante_id)
        ->call('toggleAsociacion', $publicacion->id)
        ->assertHasNoErrors();

    expect($publicacion->candidatos()->count())->toBe(1);
});

test('el detalle de la publicación lista sus candidatos asociados y permite quitarlos', function () {
    [$user, $busqueda, $match, $publicacion] = talentFinderConPublicacion();
    $publicacion->candidatos()->attach($match->postulante_id, ['busqueda_id' => $busqueda->id]);

    Livewire::actingAs($user)
        ->test(DetallePublicacion::class, ['publicacion' => $publicacion])
        ->assertViewHas('candidatosAsociados', fn ($candidatos) => $candidatos->count() === 1)
        ->assertSee('Gerente de Operaciones')
        ->call('quitarCandidato', $match->postulante_id)
        ->assertViewHas('candidatosAsociados', fn ($candidatos) => $candidatos->count() === 0);

    expect(PublicacionCandidato::query()->count())->toBe(0);
});

test('eliminar la publicación arrastra sus asociaciones y eliminar la búsqueda no', function () {
    [$user, $busqueda, $match, $publicacion] = talentFinderConPublicacion();
    $publicacion->candidatos()->attach($match->postulante_id, ['busqueda_id' => $busqueda->id]);

    // Borrar la búsqueda solo anula la trazabilidad; la asociación se mantiene.
    $busqueda->forceDelete();

    expect(PublicacionCandidato::query()->count())->toBe(1)
        ->and(PublicacionCandidato::query()->sole()->busqueda_id)->toBeNull();

    // Borrar la publicación en firme sí elimina la asociación.
    $publicacion->forceDelete();

    expect(PublicacionCandidato::query()->count())->toBe(0);
});
