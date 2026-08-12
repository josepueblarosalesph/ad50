<?php

use App\Livewire\Empresa\CarpetasFavoritos;
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

    // Borrar ocurre en el panel de la barra lateral, que avisa al listado por evento.
    Livewire::actingAs($user)
        ->test(CarpetasFavoritos::class, ['activa' => (string) $carpeta->id])
        ->call('eliminarCarpeta', $carpeta->id)
        ->assertSet('activa', 'todas')
        ->assertDispatched('carpeta-seleccionada', carpeta: 'todas');

    // Y el listado, al recibirlo, vuelve a mostrarlo todo.
    Livewire::actingAs($user)
        ->test(Favoritos::class, ['carpeta' => 'todas'])
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
        // Crear ya no es un formulario fijo al pie: es el "+" de la cabecera.
        ->toContain('aria-label="Crear una carpeta nueva"')
        ->toContain('abrirNuevaCarpeta')
        // El panel se pinta en la barra lateral y en el plegable móvil, así que la
        // carpeta y el "+" aparecen dos veces.
        ->and(substr_count($html, 'Finanzas senior'))->toBeGreaterThanOrEqual(2)
        ->and(substr_count($html, 'abrirNuevaCarpeta'))->toBeGreaterThanOrEqual(2)
        // El popup, en cambio, se monta una sola vez: lo pinta el listado, no el panel.
        ->and(substr_count($html, 'id="nueva-carpeta-nombre"'))->toBe(1);
});

test('el "+" abre el popup de nueva carpeta y desde ahí se crea', function () {
    [$user, $empresa] = empresaConFavoritos();

    // El "+" vive en el panel lateral, que pide al listado que abra el popup: el popup
    // se monta una sola vez, y el panel se pinta dos (escritorio y móvil).
    Livewire::actingAs($user)
        ->test(CarpetasFavoritos::class)
        ->call('abrirNuevaCarpeta')
        ->assertDispatched('abrir-nueva-carpeta');

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->call('abrirNuevaCarpeta')
        ->assertSet('nuevaCarpeta', '')
        ->set('nuevaCarpeta', 'Finanzas senior')
        ->call('crearCarpeta')
        ->assertHasNoErrors()
        ->assertSet('nuevaCarpeta', '')
        ->assertSee('Finanzas senior');

    $this->assertDatabaseHas('carpetas_favoritos', [
        'user_id' => $user->id,
        'nombre' => 'Finanzas senior',
    ]);
});

test('cancelar el popup no deja el nombre a medio escribir', function () {
    [$user, $empresa] = empresaConFavoritos();

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->call('abrirNuevaCarpeta')
        ->set('nuevaCarpeta', 'A medias')
        ->call('cerrarNuevaCarpeta')
        ->assertSet('nuevaCarpeta', '');

    expect(CarpetaFavoritos::query()->count())->toBe(0);
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

test('los botones del panel lateral quedan dentro de una raíz Livewire, no huérfanos', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $match = candidatoEnBusqueda($liderazgo, cargo: 'Gerente de Finanzas');
    carpetaCon($user, $empresa, 'Finanzas senior', favoritoDe($empresa, $match->postulante_id));

    $html = $this->actingAs($user)->get(route('empresa.favoritos'))->assertOk()->getContent();

    // El layout pinta el slot `sidebar` fuera de la raíz del componente que lo declara.
    // Si el panel fuera un simple parcial, sus wire:click no los enlazaría nadie y no
    // harían nada. Aquí se comprueba que cada wire:click del panel cae dentro de algún
    // elemento con wire:id, es decir, dentro de un componente Livewire montado.
    $raices = [];
    preg_match_all('/wire:id="[^"]+"/', $html, $coincidencias, PREG_OFFSET_CAPTURE);
    foreach ($coincidencias[0] as [$_, $posicion]) {
        $raices[] = $posicion;
    }

    expect($raices)->not->toBeEmpty();

    foreach (['verCarpeta(', 'abrirNuevaCarpeta', 'eliminarCarpeta(', 'editarCarpeta('] as $accion) {
        $posicion = strpos($html, 'wire:click="'.$accion);

        expect($posicion)->not->toBeFalse("«{$accion}» debería estar en el HTML");

        // Hay al menos una raíz de componente declarada antes de la acción.
        expect(collect($raices)->filter(fn (int $inicio): bool => $inicio < $posicion))
            ->not->toBeEmpty("«{$accion}» quedó fuera de toda raíz Livewire: no funcionaría");
    }
});

test('las carpetas creadas se listan plegadas y sin mensaje de vacío', function () {
    [$user, $empresa] = empresaConFavoritos();

    // Sin carpetas: ni bloque plegable ni el texto explicativo que había antes.
    $sinCarpetas = Livewire::actingAs($user)->test(CarpetasFavoritos::class)->html();

    expect($sinCarpetas)
        ->not->toContain('Aún no tienes carpetas')
        ->not->toContain('lista-carpetas')
        // Las dos vistas fijas sí están siempre.
        ->toContain('Todos los favoritos')
        ->toContain('Sin carpeta');

    carpetaCon($user, $empresa, 'Finanzas senior');
    carpetaCon($user, $empresa, 'Proceso Gerente TI');

    $conCarpetas = Livewire::actingAs($user)->test(CarpetasFavoritos::class)->html();

    expect($conCarpetas)
        ->toContain('2 carpetas')
        // Arranca plegado: Alpine parte en false mientras la activa no sea una carpeta.
        ->toContain('abierto: false');
});

test('entrar en una carpeta deja la lista desplegada para no esconder dónde estás', function () {
    [$user, $empresa] = empresaConFavoritos();
    $carpeta = carpetaCon($user, $empresa, 'Finanzas senior');

    $html = Livewire::actingAs($user)
        ->test(CarpetasFavoritos::class, ['activa' => (string) $carpeta->id])
        ->html();

    expect($html)->toContain('abierto: true');
});
