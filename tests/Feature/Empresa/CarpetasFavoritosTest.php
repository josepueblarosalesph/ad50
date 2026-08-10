<?php

use App\Livewire\Empresa\Favoritos;
use App\Models\CarpetaFavoritos;
use App\Models\Empresa;
use App\Models\Favorito;
use App\Models\User;
use Livewire\Livewire;

// La funcionalidad va apagada por omisión (ver config/ad50.php). Estos tests describen
// cómo se comporta publicada, así que la encienden; los de más abajo cubren el apagado.
beforeEach(fn () => config()->set('ad50.funcionalidades.carpetas_favoritos', true));

/** Segundo usuario del equipo de la misma empresa. */
function companeroDeEquipo(Empresa $empresa): User
{
    return User::factory()->create(['role' => 'empresa', 'empresa_id' => $empresa->id]);
}

/** Carpeta de un usuario con los favoritos indicados ya dentro. */
function carpetaCon(User $user, Empresa $empresa, string $nombre, Favorito ...$favoritos): CarpetaFavoritos
{
    $carpeta = CarpetaFavoritos::query()->create([
        'empresa_id' => $empresa->id,
        'user_id' => $user->id,
        'nombre' => $nombre,
    ]);

    $carpeta->favoritos()->attach(collect($favoritos)->pluck('id'));

    return $carpeta;
}

function favoritoDe(Empresa $empresa, int $postulanteId): Favorito
{
    return Favorito::query()->where('empresa_id', $empresa->id)->where('postulante_id', $postulanteId)->firstOrFail();
}

test('un usuario crea una carpeta y la ve en su panel', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    candidatoEnBusqueda($liderazgo, cargo: 'Gerente de Finanzas');

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->set('nuevaCarpeta', 'Finanzas senior')
        ->call('crearCarpeta')
        ->assertHasNoErrors()
        ->assertSet('nuevaCarpeta', '')
        ->assertSee('Finanzas senior');

    $this->assertDatabaseHas('carpetas_favoritos', [
        'empresa_id' => $empresa->id,
        'user_id' => $user->id,
        'nombre' => 'Finanzas senior',
    ]);
});

test('no se permiten dos carpetas con el mismo nombre para la misma persona', function () {
    [$user, $empresa] = empresaConFavoritos();
    carpetaCon($user, $empresa, 'Finanzas senior');

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->set('nuevaCarpeta', 'Finanzas senior')
        ->call('crearCarpeta')
        ->assertHasErrors('nuevaCarpeta');

    expect(CarpetaFavoritos::query()->where('user_id', $user->id)->count())->toBe(1);
});

test('dos personas del mismo equipo pueden usar el mismo nombre de carpeta', function () {
    [$user, $empresa] = empresaConFavoritos();
    $colega = companeroDeEquipo($empresa);
    carpetaCon($user, $empresa, 'Finanzas senior');

    Livewire::actingAs($colega)
        ->test(Favoritos::class)
        ->set('nuevaCarpeta', 'Finanzas senior')
        ->call('crearCarpeta')
        ->assertHasNoErrors();

    expect(CarpetaFavoritos::query()->where('nombre', 'Finanzas senior')->count())->toBe(2);
});

test('las carpetas son de cada usuario y no se ven entre colegas', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $colega = companeroDeEquipo($empresa);
    $match = candidatoEnBusqueda($liderazgo, cargo: 'Gerente de Finanzas');

    carpetaCon($user, $empresa, 'Solo mía', favoritoDe($empresa, $match->postulante_id));

    // El colega ve el favorito compartido, pero no la carpeta ajena.
    Livewire::actingAs($colega)
        ->test(Favoritos::class)
        ->assertSee('Gerente de Finanzas')
        ->assertDontSee('Solo mía')
        ->assertViewHas('carpetas', fn ($carpetas) => $carpetas->isEmpty());
});

test('un candidato puede estar en varias carpetas a la vez', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $match = candidatoEnBusqueda($liderazgo, cargo: 'Gerente de Finanzas');
    $favorito = favoritoDe($empresa, $match->postulante_id);

    $finanzas = carpetaCon($user, $empresa, 'Finanzas senior');
    $gerentes = carpetaCon($user, $empresa, 'Proceso Gerente TI');

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->call('abrirCarpetas', $match->postulante_id)
        ->call('alternarCarpeta', $finanzas->id)
        ->call('alternarCarpeta', $gerentes->id)
        ->assertViewHas('carpetasDelCandidato', fn (array $ids) => count($ids) === 2);

    expect($favorito->carpetas()->pluck('nombre')->sort()->values()->all())
        ->toBe(['Finanzas senior', 'Proceso Gerente TI']);
});

test('volver a marcar la misma carpeta saca al candidato de ella', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $match = candidatoEnBusqueda($liderazgo);
    $favorito = favoritoDe($empresa, $match->postulante_id);
    $carpeta = carpetaCon($user, $empresa, 'Finanzas senior', $favorito);

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->call('abrirCarpetas', $match->postulante_id)
        ->call('alternarCarpeta', $carpeta->id);

    expect($favorito->carpetas()->count())->toBe(0)
        // Sacarlo de la carpeta no lo quita de favoritos.
        ->and(Favorito::query()->whereKey($favorito->id)->exists())->toBeTrue();
});

test('la carpeta activa acota el listado y "sin carpeta" muestra el resto', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $agrupado = candidatoEnBusqueda($liderazgo, cargo: 'Gerente Agrupado');
    candidatoEnBusqueda($liderazgo, cargo: 'Gerente Suelto');

    $carpeta = carpetaCon($user, $empresa, 'Finanzas senior', favoritoDe($empresa, $agrupado->postulante_id));

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->call('verCarpeta', (string) $carpeta->id)
        ->assertSee('Gerente Agrupado')
        ->assertDontSee('Gerente Suelto')
        ->call('verCarpeta', 'sin')
        ->assertSee('Gerente Suelto')
        ->assertDontSee('Gerente Agrupado')
        ->call('verCarpeta', 'todas')
        ->assertSee('Gerente Agrupado')
        ->assertSee('Gerente Suelto');
});

test('el contador de cada carpeta ignora a quien pausó su perfil', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $visible = candidatoEnBusqueda($liderazgo, cargo: 'Visible');
    $pausado = candidatoEnBusqueda($liderazgo, cargo: 'Pausado');

    $carpeta = carpetaCon(
        $user,
        $empresa,
        'Finanzas senior',
        favoritoDe($empresa, $visible->postulante_id),
        favoritoDe($empresa, $pausado->postulante_id),
    );

    $pausado->postulante->update(['visible' => false]);

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->assertViewHas('carpetas', fn ($carpetas) => $carpetas->firstWhere('id', $carpeta->id)->favoritos_count === 1);
});

test('renombrar una carpeta conserva a sus candidatos', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $match = candidatoEnBusqueda($liderazgo);
    $carpeta = carpetaCon($user, $empresa, 'Nombre viejo', favoritoDe($empresa, $match->postulante_id));

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->call('editarCarpeta', $carpeta->id)
        ->assertSet('nombreEnEdicion', 'Nombre viejo')
        ->set('nombreEnEdicion', 'Nombre nuevo')
        ->call('renombrarCarpeta')
        ->assertHasNoErrors()
        ->assertSet('carpetaEnEdicionId', null)
        ->assertSee('Nombre nuevo');

    expect($carpeta->fresh()->nombre)->toBe('Nombre nuevo')
        ->and($carpeta->favoritos()->count())->toBe(1);
});

test('eliminar la carpeta que se está viendo no borra los favoritos y vuelve a la lista completa', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $match = candidatoEnBusqueda($liderazgo, cargo: 'Gerente de Finanzas');
    $carpeta = carpetaCon($user, $empresa, 'Finanzas senior', favoritoDe($empresa, $match->postulante_id));

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->call('verCarpeta', (string) $carpeta->id)
        ->call('eliminarCarpeta', $carpeta->id)
        ->assertSet('carpeta', 'todas')
        ->assertSee('Gerente de Finanzas');

    expect(CarpetaFavoritos::query()->whereKey($carpeta->id)->exists())->toBeFalse()
        ->and(Favorito::query()->where('empresa_id', $empresa->id)->count())->toBe(1);
});

test('quitar el favorito lo saca también de las carpetas', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $match = candidatoEnBusqueda($liderazgo);
    $carpeta = carpetaCon($user, $empresa, 'Finanzas senior', favoritoDe($empresa, $match->postulante_id));

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->call('quitarFavorito', $match->postulante_id);

    expect($carpeta->favoritos()->count())->toBe(0)
        ->and(CarpetaFavoritos::query()->whereKey($carpeta->id)->exists())->toBeTrue();
});

test('no se puede tocar la carpeta de otra persona', function () {
    [$user, $empresa] = empresaConFavoritos();
    $ajena = carpetaCon(companeroDeEquipo($empresa), $empresa, 'Ajena');

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->call('eliminarCarpeta', $ajena->id)
        ->assertStatus(404);

    expect(CarpetaFavoritos::query()->whereKey($ajena->id)->exists())->toBeTrue();
});

test('no se puede agrupar a un candidato que no es favorito de la empresa', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $noFavorito = candidatoEnBusqueda($liderazgo, favorito: false);
    carpetaCon($user, $empresa, 'Finanzas senior');

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->call('abrirCarpetas', $noFavorito->postulante_id)
        ->assertStatus(404);
});

test('el filtro de carpeta cae a "todas" si la carpeta de la URL no es propia', function () {
    [$user, $empresa] = empresaConFavoritos();
    $ajena = carpetaCon(companeroDeEquipo($empresa), $empresa, 'Ajena');

    Livewire::withQueryParams(['carpeta' => (string) $ajena->id])
        ->actingAs($user)
        ->test(Favoritos::class)
        ->assertSet('carpeta', 'todas');
});

test('la página completa pinta el panel de carpetas en la barra lateral y en el plegable móvil', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $match = candidatoEnBusqueda($liderazgo, cargo: 'Gerente de Finanzas');
    carpetaCon($user, $empresa, 'Finanzas senior', favoritoDe($empresa, $match->postulante_id));

    // Es un GET real y no un test de componente: así se ejerce el slot `sidebar` del
    // layout, que el componente por sí solo no monta.
    $html = $this->actingAs($user)->get(route('empresa.favoritos'))->assertOk()->getContent();

    expect($html)
        ->toContain('Mis carpetas')
        ->toContain('escritorio-nueva-carpeta')   // panel de la barra lateral
        ->toContain('movil-nueva-carpeta')        // panel del plegable en móvil
        // El panel se pinta en ambos sitios, así que la carpeta aparece dos veces.
        ->and(substr_count($html, 'Finanzas senior'))->toBeGreaterThanOrEqual(2);
});

test('el encabezado y el título cambian al entrar en una carpeta', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $match = candidatoEnBusqueda($liderazgo, cargo: 'Gerente de Finanzas');
    $carpeta = carpetaCon($user, $empresa, 'Finanzas senior', favoritoDe($empresa, $match->postulante_id));

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->assertSee('Mis favoritos')
        ->call('verCarpeta', (string) $carpeta->id)
        ->assertSee('candidato agrupado')
        ->assertDontSee('Mis favoritos');
});

test('el tope de carpetas por usuario se respeta', function () {
    [$user, $empresa] = empresaConFavoritos();

    for ($i = 1; $i <= CarpetaFavoritos::MAXIMO_POR_USUARIO; $i++) {
        carpetaCon($user, $empresa, 'Carpeta '.$i);
    }

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->set('nuevaCarpeta', 'Una más')
        ->call('crearCarpeta')
        ->assertHasErrors('nuevaCarpeta');

    expect(CarpetaFavoritos::query()->where('user_id', $user->id)->count())
        ->toBe(CarpetaFavoritos::MAXIMO_POR_USUARIO);
});

test('con la funcionalidad apagada no se ve nada de carpetas en Favoritos', function () {
    config()->set('ad50.funcionalidades.carpetas_favoritos', false);

    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $match = candidatoEnBusqueda($liderazgo, cargo: 'Gerente de Finanzas');
    // Aunque ya existieran carpetas creadas antes de apagarla, no deben asomar.
    carpetaCon($user, $empresa, 'Finanzas senior', favoritoDe($empresa, $match->postulante_id));

    $html = $this->actingAs($user)->get(route('empresa.favoritos'))->assertOk()->getContent();

    expect($html)
        ->not->toContain('Mis carpetas')
        ->not->toContain('Finanzas senior')
        ->not->toContain('Nueva carpeta')
        ->not->toContain('En carpetas')
        ->not->toContain('Agrupar en carpetas')
        // El resto de la pantalla sigue funcionando igual.
        ->toContain('Mis favoritos')
        ->toContain('Gerente de Finanzas');
});

test('con la funcionalidad apagada las acciones de carpetas responden 404', function () {
    config()->set('ad50.funcionalidades.carpetas_favoritos', false);

    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $match = candidatoEnBusqueda($liderazgo);
    $carpeta = carpetaCon($user, $empresa, 'Finanzas senior');

    // Esconder los botones no basta: los ids viajan desde el cliente.
    foreach ([
        ['crearCarpeta', []],
        ['editarCarpeta', [$carpeta->id]],
        ['eliminarCarpeta', [$carpeta->id]],
        ['alternarCarpeta', [$carpeta->id]],
        ['abrirCarpetas', [$match->postulante_id]],
        ['verCarpeta', [(string) $carpeta->id]],
    ] as [$accion, $argumentos]) {
        Livewire::actingAs($user)
            ->test(Favoritos::class)
            ->call($accion, ...$argumentos)
            ->assertStatus(404, "«{$accion}» debería responder 404 con la funcionalidad apagada");
    }

    expect(CarpetaFavoritos::query()->where('user_id', $user->id)->count())->toBe(1);
});

test('con la funcionalidad apagada un ?carpeta=sin en la URL no delata nada', function () {
    config()->set('ad50.funcionalidades.carpetas_favoritos', false);

    [$user, , $liderazgo] = empresaConFavoritos();
    candidatoEnBusqueda($liderazgo, cargo: 'Gerente de Finanzas');

    Livewire::withQueryParams(['carpeta' => 'sin'])
        ->actingAs($user)
        ->test(Favoritos::class)
        ->assertSet('carpeta', 'todas')
        ->assertSee('Gerente de Finanzas')
        ->assertDontSee('sin carpeta');
});
