<?php

use App\Livewire\Empresa\Candidato;
use App\Livewire\Empresa\Favoritos;
use App\Models\Desbloqueo;
use App\Models\Favorito;
use App\Models\NotaCandidato;
use App\Models\User;
use Livewire\Livewire;

// empresaConFavoritos() y candidatoEnBusqueda() viven en tests/Pest.php: las comparte
// CarpetasFavoritosTest, y las funciones de un archivo de test no están garantizadas
// al ejecutar otro por separado.

test('la lista muestra solo los favoritos de la empresa, una fila por candidato', function () {
    [$user, , $liderazgo, $planta] = empresaConFavoritos();

    $favorito = candidatoEnBusqueda($liderazgo, cargo: 'Gerente de Operaciones');
    candidatoEnBusqueda($liderazgo, favorito: false, cargo: 'No favorito');

    // El mismo candidato calza también con la segunda búsqueda: el favorito sigue siendo uno.
    $planta->candidatos()->create([
        'postulante_id' => $favorito->postulante_id,
        'criterios_cumplidos' => 1,
        'criterios_totales' => 1,
        'estado_match' => 'cumple',
    ]);

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->assertViewHas('totalFavoritos', 1)
        ->assertViewHas('candidatos', fn ($candidatos) => $candidatos->total() === 1)
        ->assertSee('Gerente de Operaciones')
        ->assertDontSee('No favorito')
        // La tarjeta ya no rotula la búsqueda de origen: eso vive en el filtro superior.
        ->assertDontSee('Guardado desde');
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

// El test "el filtro por búsqueda de origen acota la lista" se eliminó: ese filtro se
// retiró de Favoritos. La búsqueda desde la que se guardó sigue anotándose en
// `favoritos.busqueda_id` como trazabilidad, pero ya no se ofrece para filtrar.

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
        ->set('publicacion', (string) $publicacion->id)
        ->set('desbloqueo', 'bloqueados')
        ->assertViewHas('hayFiltros', true)
        ->assertViewHas('candidatos', fn ($c) => $c->total() === 1)
        ->call('limpiarFiltros')
        ->assertSet('publicacion', 'todas')
        ->assertSet('desbloqueo', 'todos')
        ->assertViewHas('candidatos', fn ($c) => $c->total() === 2);
});

test('quitar el favorito lo saca de la cuenta de una sola vez', function () {
    [$user, $empresa, $liderazgo, $planta] = empresaConFavoritos();
    $match = candidatoEnBusqueda($liderazgo);

    // Aunque el candidato calce con otra búsqueda, el favorito es uno solo.
    $planta->candidatos()->create([
        'postulante_id' => $match->postulante_id,
        'criterios_cumplidos' => 1,
        'criterios_totales' => 1,
        'estado_match' => 'cumple',
    ]);

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->call('quitarFavorito', $match->postulante_id)
        ->assertHasNoErrors()
        ->assertViewHas('totalFavoritos', 0);

    expect($empresa->haMarcadoFavorito($match->postulante_id))->toBeFalse();
});

test('no se puede quitar un favorito de otra empresa', function () {
    [$user] = empresaConFavoritos();
    [, $otraEmpresa, $ajena] = empresaConFavoritos();
    $match = candidatoEnBusqueda($ajena);

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->call('quitarFavorito', $match->postulante_id)
        ->assertStatus(404);

    expect($otraEmpresa->haMarcadoFavorito($match->postulante_id))->toBeTrue();
});

test('el favorito sobrevive a que se elimine la búsqueda desde la que se guardó', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $match = candidatoEnBusqueda($liderazgo, cargo: 'Sigue guardado');

    $liderazgo->delete();

    expect($empresa->haMarcadoFavorito($match->postulante_id))->toBeTrue();

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->assertViewHas('totalFavoritos', 1)
        ->assertSee('Sigue guardado');
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

test('la tarjeta trae la misma columna de acciones que Prospección de Candidatos', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $match = candidatoEnBusqueda($liderazgo);

    // Sin desbloquear y con cupo: el candado es el botón que desbloquea. Las acciones
    // son iconos, como en Prospección, y no botones rotulados.
    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->assertSeeHtml('desbloquear('.$match->postulante_id.')')
        ->assertSeeHtml('abrirAsociacion('.$match->postulante_id.')')
        ->assertSeeHtml('abrirNotas('.$match->postulante_id.')')
        ->assertSeeHtml('quitarFavorito('.$match->postulante_id.')')
        ->assertSee('Ver perfil');

    Desbloqueo::query()->create([
        'empresa_id' => $empresa->id,
        'postulante_id' => $match->postulante_id,
    ]);

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->assertSee('aria-label="Perfil desbloqueado"', false);
});

test('desde favoritos se navega entre favoritos, no entre los candidatos de una búsqueda', function () {
    [$user, $empresa, $liderazgo, $planta] = empresaConFavoritos();

    // En la misma búsqueda: uno favorito y otro no.
    $favoritoA = candidatoEnBusqueda($liderazgo, cargo: 'Favorito A');
    candidatoEnBusqueda($liderazgo, favorito: false, cargo: 'No favorito');
    // Y un favorito que viene de OTRA búsqueda.
    $favoritoB = candidatoEnBusqueda($planta, cargo: 'Favorito B');

    $detalle = Livewire::actingAs($user)->test(Candidato::class, [
        'match' => $favoritoA,
        'origen' => 'favoritos',
    ]);

    // El conjunto navegable son los 2 favoritos de la cuenta, no los de la búsqueda.
    $detalle->assertSet('totalCandidatos', 2)
        ->assertSet('posicion', 1)
        ->assertSet('siguienteId', $favoritoB->id)
        ->assertSet('anteriorId', null)
        // Vuelve a la lista de favoritos y no ofrece los filtros de la búsqueda.
        ->assertSee('Volver a Mis favoritos')
        ->assertDontSee("cambiarFiltro('favoritos')", escape: false);
});

test('el detalle abierto desde una búsqueda sigue navegando dentro de ella', function () {
    [$user, , $liderazgo, $planta] = empresaConFavoritos();

    $enLiderazgo = candidatoEnBusqueda($liderazgo, cargo: 'De liderazgo');
    candidatoEnBusqueda($liderazgo, favorito: false, cargo: 'Otro de liderazgo');
    candidatoEnBusqueda($planta, cargo: 'De planta');

    Livewire::actingAs($user)
        ->test(Candidato::class, ['match' => $enLiderazgo])
        ->assertSet('origen', 'busqueda')
        // Los 2 de esta búsqueda; el de la otra queda fuera.
        ->assertSet('totalCandidatos', 2)
        ->assertSee("cambiarFiltro('favoritos')", escape: false);
});

test('no se abre como favorito un candidato que no lo es', function () {
    [$user, , $liderazgo] = empresaConFavoritos();
    $noFavorito = candidatoEnBusqueda($liderazgo, favorito: false);

    Livewire::actingAs($user)
        ->test(Candidato::class, ['match' => $noFavorito, 'origen' => 'favoritos'])
        ->assertStatus(404);
});

test('el panel de asociación usa un desplegable con casillas y resume lo elegido', function () {
    [$user, , $liderazgo, , $publicacion] = empresaConFavoritos();
    $match = candidatoEnBusqueda($liderazgo);

    $componente = Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->call('abrirAsociacion', $match->postulante_id);

    // Sin nada elegido: el desplegable invita a elegir y ofrece el buscador.
    $componente
        ->assertSee('Elige una o más publicaciones')
        ->assertSee('Buscar publicación')
        ->assertSeeHtml('type="checkbox"')
        ->assertSeeHtml('toggleAsociacion('.$publicacion->id.')');

    // Con una publicación marcada, el resumen la cuenta sin desplegar la lista.
    $componente->call('toggleAsociacion', $publicacion->id)
        ->assertSee('1 publicación seleccionada')
        ->assertDontSee('Elige una o más publicaciones');
});

test('desde favoritos se desbloquea el perfil de un candidato', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $match = candidatoEnBusqueda($liderazgo);

    $componente = Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->call('desbloquear', $match->postulante_id)
        ->assertHasNoErrors();

    expect($empresa->fresh()->haDesbloqueado($match->postulante_id))->toBeTrue()
        ->and($empresa->fresh()->desbloqueosUsados())->toBe(1)
        // Queda la marca de contacto en la coincidencia, igual que al desbloquear desde la búsqueda.
        ->and($match->fresh()->contactado_at)->not->toBeNull();

    // Repetir no vuelve a cobrar el cupo y la tarjeta pasa a mostrarse desbloqueada.
    $componente->call('desbloquear', $match->postulante_id)
        ->assertSee('aria-label="Perfil desbloqueado"', false);

    expect($empresa->fresh()->desbloqueosUsados())->toBe(1);
});

test('sin cupo ni plan vigente el desbloqueo desde favoritos avisa y no cobra', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $match = candidatoEnBusqueda($liderazgo);

    // El plan vence: ya no se puede desbloquear.
    $empresa->update(['plan_hasta' => now()->subDay()]);

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->call('desbloquear', $match->postulante_id)
        ->assertSee('Necesitas una suscripción activa para desbloquear perfiles.')
        // Sin poder desbloquear, el candado vuelve a ser un indicador.
        ->assertSee('aria-label="Perfil sin desbloquear"', false);

    expect($empresa->fresh()->desbloqueosUsados())->toBe(0);

    // Con el plan al día pero sin cupo, el aviso cambia y tampoco se crea el desbloqueo.
    // El cupo se vacía en la empresa, no en el plan: ahí es donde se acumula.
    $empresa->update(['plan_hasta' => now()->addMonth(), 'desbloqueos_cupo' => 0]);

    // Instancia nueva: la anterior trae cargados la empresa y su plan con los valores viejos.
    Livewire::actingAs($user->fresh())
        ->test(Favoritos::class)
        ->call('desbloquear', $match->postulante_id)
        ->assertSee('No te quedan desbloqueos disponibles en tu plan.');

    expect($empresa->fresh()->desbloqueosUsados())->toBe(0);
});

test('no se puede desbloquear desde favoritos a alguien que no es favorito', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $noFavorito = candidatoEnBusqueda($liderazgo, favorito: false);

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->call('desbloquear', $noFavorito->postulante_id)
        ->assertStatus(404);

    expect($empresa->fresh()->desbloqueosUsados())->toBe(0);
});

/** Nota de un usuario sobre un candidato. */
function notaSobre(int $empresaId, int $postulanteId, User $autor, string $contenido, string $visibilidad = 'equipo'): NotaCandidato
{
    return NotaCandidato::query()->create([
        'empresa_id' => $empresaId,
        'postulante_id' => $postulanteId,
        'user_id' => $autor->id,
        'contenido' => $contenido,
        'visibilidad' => $visibilidad,
    ]);
}

test('la tarjeta muestra el texto de la nota, no solo un icono', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $match = candidatoEnBusqueda($liderazgo, cargo: 'Gerente de Finanzas');

    notaSobre($empresa->id, $match->postulante_id, $user, 'Muy buen manejo de equipos grandes.');

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->assertSee('Muy buen manejo de equipos grandes.')
        // La nota propia sin nada que matizar no lleva pie: ni autor, ni "Privada",
        // ni "+N más". El texto habla solo.
        ->assertViewHas('notasPorCandidato', fn (array $notas) => $notas[$match->postulante_id] === [
            'contenido' => 'Muy buen manejo de equipos grandes.',
            'autor' => null,
            'privada' => false,
            'otras' => 0,
        ]);
});

test('un candidato sin notas no pinta el bloque', function () {
    [$user, , $liderazgo] = empresaConFavoritos();
    candidatoEnBusqueda($liderazgo, cargo: 'Gerente de Finanzas');

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->assertSee('Gerente de Finanzas')
        ->assertViewHas('notasPorCandidato', fn (array $notas) => $notas === []);
});

test('en la tarjeta manda la nota propia sobre la del equipo, y el resto se cuenta', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $colega = User::factory()->create(['role' => 'empresa', 'empresa_id' => $empresa->id]);
    $match = candidatoEnBusqueda($liderazgo);

    // La del colega es más reciente, pero en la tarjeta pesa más la propia.
    notaSobre($empresa->id, $match->postulante_id, $user, 'Lo entrevisté en 2024.');
    notaSobre($empresa->id, $match->postulante_id, $colega, 'Me lo recomendó un proveedor.');

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->assertSee('Lo entrevisté en 2024.')
        ->assertDontSee('Me lo recomendó un proveedor.')
        ->assertSee('+1 más');
});

test('sin nota propia se muestra la del equipo, rotulada con su autor', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $colega = User::factory()->create(['role' => 'empresa', 'empresa_id' => $empresa->id, 'name' => 'Ana Torres']);
    $match = candidatoEnBusqueda($liderazgo);

    notaSobre($empresa->id, $match->postulante_id, $colega, 'Disponible desde marzo.');

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->assertSee('Disponible desde marzo.')
        // El autor solo se rotula cuando la nota es de otra persona del equipo.
        ->assertSee('Ana Torres');
});

test('la nota privada de un colega no se filtra a la tarjeta', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $colega = User::factory()->create(['role' => 'empresa', 'empresa_id' => $empresa->id]);
    $match = candidatoEnBusqueda($liderazgo, cargo: 'Gerente de Finanzas');

    notaSobre($empresa->id, $match->postulante_id, $colega, 'Reserva mía sobre su sueldo.', 'privada');

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->assertSee('Gerente de Finanzas')
        ->assertDontSee('Reserva mía sobre su sueldo.')
        ->assertViewHas('notasPorCandidato', fn (array $notas) => $notas === []);
});

test('la propia nota privada sí se ve, marcada como privada', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $match = candidatoEnBusqueda($liderazgo);

    notaSobre($empresa->id, $match->postulante_id, $user, 'Pretensión de renta alta.', 'privada');

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->assertSee('Pretensión de renta alta.')
        ->assertSee('Privada');
});

test('desde la tarjeta se abre el panel con todas las notas legibles', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $colega = User::factory()->create(['role' => 'empresa', 'empresa_id' => $empresa->id]);
    $match = candidatoEnBusqueda($liderazgo);

    notaSobre($empresa->id, $match->postulante_id, $user, 'Lo entrevisté en 2024.');
    notaSobre($empresa->id, $match->postulante_id, $colega, 'Me lo recomendó un proveedor.');

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->call('abrirNotas', $match->postulante_id)
        ->assertSet('notasPostulanteId', $match->postulante_id)
        ->assertViewHas('notasDelCandidato', fn ($notas) => $notas->count() === 2)
        // En el panel sí aparece la del colega.
        ->assertSee('Me lo recomendó un proveedor.');
});

test('no se pueden abrir las notas de un candidato que no es favorito', function () {
    [$user, , $liderazgo] = empresaConFavoritos();
    $noFavorito = candidatoEnBusqueda($liderazgo, favorito: false);

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->call('abrirNotas', $noFavorito->postulante_id)
        ->assertStatus(404);
});

test('las notas de otra empresa sobre el mismo candidato no se ven', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $match = candidatoEnBusqueda($liderazgo, cargo: 'Gerente de Finanzas');

    [$ajeno, $otraEmpresa] = empresaConFavoritos();
    notaSobre($otraEmpresa->id, $match->postulante_id, $ajeno, 'Nota de la competencia.');

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->assertSee('Gerente de Finanzas')
        ->assertDontSee('Nota de la competencia.');
});

test('el panel de criterios acota los favoritos con el mismo motor del matching', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();

    $calza = candidatoEnBusqueda($liderazgo, cargo: 'Gerente Finanza');
    $calza->postulante->update(['ciudad' => 'Biobío', 'regiones_interes' => ['Biobío']]);

    $noCalza = candidatoEnBusqueda($liderazgo, cargo: 'Contador');
    $noCalza->postulante->update(['ciudad' => 'Maule', 'regiones_interes' => ['Maule']]);

    $componente = Livewire::actingAs($user)->test(Favoritos::class);

    $componente->assertViewHas('candidatos', fn ($c) => $c->total() === 2);

    // Los criterios llegan por el mismo evento que emite el panel lateral compartido.
    $componente->dispatch('criterios-postulaciones', criterios: ['ciudad' => ['Biobío']])
        ->assertViewHas('candidatos', fn ($c) => $c->total() === 1)
        ->assertViewHas('hayCriterios', true)
        ->assertSee('Gerente Finanza')
        ->assertDontSee('Contador');

    // Sin criterios vuelven los dos.
    $componente->dispatch('criterios-postulaciones', criterios: [])
        ->assertViewHas('candidatos', fn ($c) => $c->total() === 2);
});

test('los criterios se combinan con los filtros propios de favoritos', function () {
    [$user, $empresa, $liderazgo, $planta] = empresaConFavoritos();

    $enLiderazgo = candidatoEnBusqueda($liderazgo, cargo: 'Gerente Finanza');
    $enLiderazgo->postulante->update(['ciudad' => 'Biobío', 'regiones_interes' => ['Biobío']]);

    $enPlanta = candidatoEnBusqueda($planta, cargo: 'Jefe de Turno');
    $enPlanta->postulante->update(['ciudad' => 'Biobío', 'regiones_interes' => ['Biobío']]);

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->dispatch('criterios-postulaciones', criterios: ['ciudad' => ['Biobío']])
        ->assertViewHas('candidatos', fn ($c) => $c->total() === 2)
        // Y encima el filtro por estado de desbloqueo.
        ->set('desbloqueo', 'desbloqueados')
        ->assertViewHas('candidatos', fn ($c) => $c->total() === 0);
});

test('el favorito conserva el botón de perfil aunque su búsqueda esté en la papelera', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $match = candidatoEnBusqueda($liderazgo, cargo: 'Gerente de Finanzas');

    // Antes de borrar la búsqueda, el enlace está.
    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->assertViewHas('candidatos', fn ($c) => $c->first()->match_visible_id !== null)
        ->assertSee('Ver perfil');

    // El favorito es de la cuenta y sobrevive al borrado de su búsqueda; el enlace al
    // perfil también tiene que sobrevivir, o el candidato queda inalcanzable.
    $liderazgo->delete();

    Livewire::actingAs($user)
        ->test(Favoritos::class)
        ->assertViewHas('candidatos', fn ($c) => $c->first()->match_visible_id === $match->id)
        ->assertSee('Ver perfil');
});

test('la ficha se abre desde un favorito cuya búsqueda está en la papelera', function () {
    [$user, $empresa, $liderazgo] = empresaConFavoritos();
    $match = candidatoEnBusqueda($liderazgo, cargo: 'Gerente de Finanzas');

    $liderazgo->delete();

    // La relación con la búsqueda tiene que resolver aunque esté en la papelera: si no,
    // la comprobación de a qué empresa pertenece reventaría al abrir la ficha.
    $this->actingAs($user)
        ->get(route('empresa.candidatos.show', ['match' => $match->id, 'origen' => 'favoritos']))
        ->assertOk();
});
