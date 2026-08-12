<?php

use App\Livewire\Empresa\FiltrosPostulaciones;
use App\Livewire\Empresa\Postulaciones;
use App\Livewire\Empresa\SelectorCriterio;
use App\Models\Busqueda;
use App\Models\Empresa;
use App\Models\NotaCandidato;
use App\Models\Postulacion;
use App\Models\Postulante;
use App\Models\Publicacion;
use App\Models\PublicacionCandidato;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/** @return array{0: User, 1: Empresa, 2: Publicacion} */
function empresaConPublicacion(?string $email = null): array
{
    $user = User::factory()->create(['role' => 'empresa', ...($email ? ['email' => $email] : [])]);
    $empresa = Empresa::query()->create(['user_id' => $user->id, 'razon_social' => 'Empresa Pub', 'estado_activacion' => 'activa']);
    $publicacion = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'cargo' => 'Analista Senior',
        'estado' => 'publicada',
        'preguntas' => ['¿Por qué te interesa?'],
    ]);

    return [$user->fresh(), $empresa, $publicacion];
}

function postularA(Publicacion $publicacion, string $nombre, string $region, string $estado = 'recibida', array $extra = []): Postulacion
{
    $user = User::factory()->create(['role' => 'postulante', 'name' => $nombre]);
    $postulante = Postulante::query()->create(array_merge([
        'user_id' => $user->id,
        'visible' => true,
        'regiones_interes' => [$region],
    ], $extra));

    return Postulacion::query()->create([
        'publicacion_id' => $publicacion->id,
        'postulante_id' => $postulante->id,
        'respuestas' => ['Me interesa por la experiencia del equipo.'],
        'estado' => $estado,
    ]);
}

test('el listado muestra a los postulantes en tarjetas compactas', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    postularA($publicacion, 'Ana Torres', 'Biobío');
    postularA($publicacion, 'Beto Díaz', 'Valparaíso');

    Livewire::actingAs($user)
        ->test(Postulaciones::class, ['publicacion' => $publicacion])
        ->assertViewHas('totalCandidatos', 2)
        ->assertViewHas('totalPostularon', 2)
        ->assertViewHas('totalAgregados', 0)
        ->assertSee('Ana Torres')
        ->assertSee('Beto Díaz')
        // El detalle (respuestas, contacto) ya no estira cada tarjeta.
        ->assertDontSee('Me interesa por la experiencia del equipo.');
});

/** Agrega un candidato a la publicación como lo hace la empresa desde Prospección. */
function agregarA(Publicacion $publicacion, string $nombre, ?Busqueda $busqueda = null): Postulante
{
    $user = User::factory()->create(['role' => 'postulante', 'name' => $nombre]);
    $postulante = Postulante::query()->create(['user_id' => $user->id, 'visible' => true]);

    PublicacionCandidato::query()->create([
        'publicacion_id' => $publicacion->id,
        'postulante_id' => $postulante->id,
        'busqueda_id' => $busqueda?->id,
    ]);

    return $postulante;
}

test('el listado unifica a quienes postularon y a quienes agregó la empresa, marcando el origen', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    postularA($publicacion, 'Ana Postuló', 'Biobío');
    agregarA($publicacion, 'Beto Agregado');

    Livewire::actingAs($user)
        ->test(Postulaciones::class, ['publicacion' => $publicacion])
        ->assertViewHas('totalCandidatos', 2)
        ->assertViewHas('totalPostularon', 1)
        ->assertViewHas('totalAgregados', 1)
        // Las dos personas en una sola lista, cada una con su origen.
        ->assertSee('Ana Postuló')
        // Del agregado sin desbloquear solo se ve el nombre de pila.
        ->assertSee('Beto')
        ->assertDontSee('Beto Agregado')
        ->assertSee('Postuló')
        ->assertSee('Agregado por la empresa')
        // Ahora el agregado también tiene etapa, y arranca en revisión.
        ->assertSee('En revisión');
});

test('quien fue agregado y además postuló ocupa una sola fila con los dos orígenes', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    $postulacion = postularA($publicacion, 'Ana Ambas', 'Biobío');

    PublicacionCandidato::query()->create([
        'publicacion_id' => $publicacion->id,
        'postulante_id' => $postulacion->postulante_id,
    ]);

    Livewire::actingAs($user)
        ->test(Postulaciones::class, ['publicacion' => $publicacion])
        // Una sola persona, contada en ambos orígenes.
        ->assertViewHas('totalCandidatos', 1)
        ->assertViewHas('totalPostularon', 1)
        ->assertViewHas('totalAgregados', 1)
        ->assertViewHas('candidatos', fn ($candidatos): bool => $candidatos->total() === 1);
});

test('los filtros de origen y de etapa son independientes y se combinan', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    postularA($publicacion, 'Ana Postuló', 'Biobío');
    agregarA($publicacion, 'Beto Agregado');

    $componente = Livewire::actingAs($user)->test(Postulaciones::class, ['publicacion' => $publicacion]);

    $componente->set('origen', 'agregados')
        ->assertSee('Beto')
        ->assertDontSee('Ana Postuló');

    $componente->set('origen', 'postularon')
        ->assertSee('Ana Postuló')
        ->assertDontSee('Beto');

    // Los dos ejes se combinan: quien postuló llega en "recibida", así que buscar
    // agregados en esa etapa no devuelve a nadie.
    $componente->set('origen', 'agregados')->set('estado', 'recibida')
        ->assertDontSee('Ana Postuló')
        ->assertDontSee('Beto');

    // Y el agregado sí aparece en la etapa en que arranca.
    $componente->set('estado', 'en_revision')->assertSee('Beto');
});

test('el detalle de un agregado no muestra contacto ni respuestas', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    $agregado = agregarA($publicacion, 'Beto Agregado');
    $agregado->update(['telefono' => '+56 9 1111 1111']);

    Livewire::actingAs($user)
        ->test(Postulaciones::class, ['publicacion' => $publicacion])
        ->call('verDetalle', $agregado->id)
        ->assertSet('detalleId', $agregado->id)
        ->assertSee('Agregado por la empresa el')
        ->assertSee('sus datos de contacto se ven al desbloquear el perfil')
        ->assertDontSee('+56 9 1111 1111')
        ->assertDontSee('¿Por qué te interesa?');
});

test('al hacer clic en el nombre se abre el detalle del postulante', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    $postulacion = postularA($publicacion, 'Ana Torres', 'Biobío');
    postularA($publicacion, 'Beto Díaz', 'Valparaíso');

    Livewire::actingAs($user)
        ->test(Postulaciones::class, ['publicacion' => $publicacion])
        ->call('verDetalle', $postulacion->postulante_id)
        ->assertSet('detalleId', $postulacion->postulante_id)
        // El detalle trae contacto, respuestas y perfil.
        ->assertSee('¿Por qué te interesa?')
        ->assertSee('Me interesa por la experiencia del equipo.')
        ->assertSee('Ana Torres')
        ->call('cerrarDetalle')
        ->assertSet('detalleId', null);
});

test('no se puede abrir el detalle de una postulación de otra publicación', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    [, , $otraPublicacion] = empresaConPublicacion('otra@empresa.cl');
    $ajena = postularA($otraPublicacion, 'Ajeno', 'Maule');

    Livewire::actingAs($user)
        ->test(Postulaciones::class, ['publicacion' => $publicacion])
        ->call('verDetalle', $ajena->postulante_id)
        ->assertStatus(404);
});

test('an empresa can change the estado of an application', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    $postulacion = postularA($publicacion, 'Ana Torres', 'Biobío');

    Livewire::actingAs($user)
        ->test(Postulaciones::class, ['publicacion' => $publicacion])
        ->call('cambiarEstado', $postulacion->postulante_id, 'seleccionada')
        ->assertHasNoErrors();

    expect($postulacion->fresh()->estado)->toBe('seleccionada');

    // Un estado inválido se rechaza.
    Livewire::actingAs($user)
        ->test(Postulaciones::class, ['publicacion' => $publicacion])
        ->call('cambiarEstado', $postulacion->postulante_id, 'inventado')
        ->assertStatus(422);
});

test('downloading an applicant CV does not consume unlock credits', function () {
    Storage::fake('local');
    Storage::disk('local')->put('cvs/curriculum.pdf', '%PDF-1.4 prueba');

    [$user, $empresa, $publicacion] = empresaConPublicacion();
    $postulacion = postularA($publicacion, 'Ana Torres', 'Biobío', 'enviada', ['cv_ruta' => 'cvs/curriculum.pdf']);

    // La empresa no tiene plan ni desbloqueos; aun así puede descargar el CV.
    Livewire::actingAs($user)
        ->test(Postulaciones::class, ['publicacion' => $publicacion])
        ->call('descargarCv', $postulacion->id)
        ->assertFileDownloaded('cv-postulante-'.$postulacion->postulante_id.'.pdf');

    expect($empresa->desbloqueos()->count())->toBe(0);
});

test('the side filters narrow the applicants by profile', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    postularA($publicacion, 'Ana Torres', 'Biobío');
    postularA($publicacion, 'Beto Díaz', 'Valparaíso');

    Livewire::actingAs($user)
        ->test(Postulaciones::class, ['publicacion' => $publicacion])
        ->call('filtrar', ['ciudad' => ['Biobío']])
        ->assertViewHas('totalFiltradas', 1)
        ->assertSee('Ana Torres')
        ->assertDontSee('Beto Díaz');
});

test('the estado filter narrows by application status', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    postularA($publicacion, 'Ana Torres', 'Biobío', 'seleccionada');
    postularA($publicacion, 'Beto Díaz', 'Valparaíso', 'recibida');

    Livewire::actingAs($user)
        ->test(Postulaciones::class, ['publicacion' => $publicacion])
        ->set('estado', 'seleccionada')
        ->assertSee('Ana Torres')
        ->assertDontSee('Beto Díaz');
});

test('an empresa cannot see the applicants of another company publication', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();

    $otroUser = User::factory()->create(['role' => 'empresa']);
    Empresa::query()->create(['user_id' => $otroUser->id, 'razon_social' => 'Otra', 'estado_activacion' => 'activa']);

    Livewire::actingAs($otroUser)
        ->test(Postulaciones::class, ['publicacion' => $publicacion])
        ->assertForbidden();
});

test('el filtro de renta acota por rango, no solo por un tope', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    postularA($publicacion, 'Pide poco', 'Biobío', extra: ['expectativa_renta' => 1_000_000]);
    postularA($publicacion, 'Pide medio', 'Biobío', extra: ['expectativa_renta' => 4_000_000]);
    postularA($publicacion, 'Pide mucho', 'Biobío', extra: ['expectativa_renta' => 12_000_000]);

    $componente = Livewire::actingAs($user)->test(Postulaciones::class, ['publicacion' => $publicacion]);

    // Entre 3 y 5 millones: solo el del medio.
    $componente->call('filtrar', ['renta' => ['min' => 3_000_000, 'max' => 5_000_000]])
        ->assertViewHas('totalFiltradas', 1)
        ->assertSee('Pide medio')
        ->assertDontSee('Pide poco')
        ->assertDontSee('Pide mucho');

    // Sin tope superior, "o más" incluye al que está sobre el máximo del deslizador.
    $componente->call('filtrar', ['renta' => ['min' => 4_000_000, 'max' => null]])
        ->assertViewHas('totalFiltradas', 2)
        ->assertSee('Pide medio')
        ->assertSee('Pide mucho');

    // Solo con tope superior se comporta como el filtro antiguo.
    $componente->call('filtrar', ['renta' => ['min' => 0, 'max' => 2_000_000]])
        ->assertViewHas('totalFiltradas', 1)
        ->assertSee('Pide poco');
});

test('el panel arma el criterio de renta en pesos desde los intervalos del deslizador', function () {
    [$user] = empresaConPublicacion();

    Livewire::actingAs($user)
        ->test(FiltrosPostulaciones::class)
        ->set('rentaMin', 2)
        ->set('rentaMax', 5)
        // El deslizador trabaja en millones; el criterio viaja en pesos.
        ->assertDispatched('criterios-postulaciones', fn (string $e, array $p): bool => $p['criterios']['renta'] === ['min' => 2_000_000, 'max' => 5_000_000]);

    // Cubriendo todo el recorrido no se filtra por renta.
    Livewire::actingAs($user)
        ->test(FiltrosPostulaciones::class)
        ->set('rentaMin', 0)
        ->set('rentaMax', 8)
        ->assertDispatched('criterios-postulaciones', fn (string $e, array $p): bool => $p['criterios']['renta'] === null);
});

test('el nombre muestra un indicador mientras se abre el detalle', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    $postulacion = postularA($publicacion, 'Ana Torres', 'Biobío');

    Livewire::actingAs($user)
        ->test(Postulaciones::class, ['publicacion' => $publicacion])
        // El botón se deshabilita y aparece el spinner mientras viaja la petición.
        ->assertSee('wire:target="verDetalle('.$postulacion->postulante_id.')"', false)
        ->assertSee('animate-spin', false);
});

test('los filtros solo ofrecen datos de quienes postularon a esa publicación', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();

    // Postuló alguien de Biobío...
    postularA($publicacion, 'Ana Torres', 'Biobío');

    // ...y existe otro postulante de Maule que NO postuló a esta publicación.
    $ajeno = Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true,
        'regiones_interes' => ['Maule'],
    ]);

    Livewire::actingAs($user)
        ->test(SelectorCriterio::class, [
            'campo' => 'ciudad',
            'publicacionId' => $publicacion->id,
        ])
        ->assertSee('Biobío')
        ->assertDontSee('Maule');

    // Sin acotar a la publicación, el universo es toda la plataforma.
    Livewire::actingAs($user)
        ->test(SelectorCriterio::class, ['campo' => 'ciudad'])
        ->assertSee('Biobío')
        ->assertSee('Maule');

    expect($ajeno->exists)->toBeTrue();
});

test('el conteo del filtro cuenta solo postulantes de la publicación', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    postularA($publicacion, 'Ana Torres', 'Biobío');
    postularA($publicacion, 'Beto Díaz', 'Biobío');

    // Dos postulantes más de Biobío, ajenos a esta publicación.
    foreach (range(1, 2) as $i) {
        Postulante::query()->create([
            'user_id' => User::factory()->create(['role' => 'postulante'])->id,
            'visible' => true,
            'regiones_interes' => ['Biobío'],
        ]);
    }

    Livewire::actingAs($user)
        ->test(SelectorCriterio::class, [
            'campo' => 'ciudad',
            'publicacionId' => $publicacion->id,
        ])
        // 2, no 4: el universo son los postulantes de la oferta.
        ->assertSee('Quedan 2 candidatos si agregas', escape: false);
});

test('al agregado sin desbloquear solo se le ve el nombre de pila', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    $agregado = agregarA($publicacion, 'Beatriz Contreras Rojas');
    $agregado->update(['telefono' => '+56 9 1111 1111']);

    $componente = Livewire::actingAs($user)->test(Postulaciones::class, ['publicacion' => $publicacion]);

    $componente->assertSee('Beatriz')
        ->assertDontSee('Beatriz Contreras Rojas')
        ->assertSee('Perfil sin desbloquear');

    // Tampoco en el detalle, junto con el contacto.
    $componente->call('verDetalle', $agregado->id)
        ->assertDontSee('Beatriz Contreras Rojas')
        ->assertDontSee('+56 9 1111 1111');
});

test('al desbloquear el perfil se muestra el nombre completo del agregado', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    $agregado = agregarA($publicacion, 'Beatriz Contreras Rojas');
    $agregado->update(['telefono' => '+56 9 1111 1111']);

    $empresa->desbloqueos()->create(['postulante_id' => $agregado->id]);

    Livewire::actingAs($user)->test(Postulaciones::class, ['publicacion' => $publicacion])
        ->assertSee('Beatriz Contreras Rojas')
        ->assertDontSee('Perfil sin desbloquear')
        // Con el perfil abierto, el contacto también se muestra.
        ->call('verDetalle', $agregado->id)
        ->assertSee('+56 9 1111 1111');
});

test('quien postuló se identifica completo aunque no esté desbloqueado', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    postularA($publicacion, 'Ana Torres Vega', 'Biobío');

    Livewire::actingAs($user)->test(Postulaciones::class, ['publicacion' => $publicacion])
        ->assertSee('Ana Torres Vega')
        ->assertDontSee('Perfil sin desbloquear');
});

test('al agregado que además postuló se le ve el nombre completo', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    $postulacion = postularA($publicacion, 'Ana Torres Vega', 'Biobío');

    PublicacionCandidato::query()->create([
        'publicacion_id' => $publicacion->id,
        'postulante_id' => $postulacion->postulante_id,
    ]);

    Livewire::actingAs($user)->test(Postulaciones::class, ['publicacion' => $publicacion])
        ->assertSee('Ana Torres Vega')
        ->assertDontSee('Perfil sin desbloquear');
});

test('las tarjetas del listado traen las mismas acciones que Prospección', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    $postulacion = postularA($publicacion, 'Ana Torres Vega', 'Biobío');
    $postulante = $postulacion->postulante;

    Livewire::actingAs($user)
        ->test(Postulaciones::class, ['publicacion' => $publicacion])
        // Favorito, asociar a otra publicación, notas y ficha completa.
        ->assertSeeHtml('toggleFavorito('.$postulante->id.')')
        ->assertSeeHtml('abrirAsociacion('.$postulante->id.')')
        ->assertSeeHtml('abrirNotas('.$postulante->id.')')
        ->assertSeeHtml('verDetalle('.$postulante->id.')')
        // Y lo propio de una publicación: el combobox de etapa. Va por id de postulante
        // y no de postulación, porque ahora también lo tienen los agregados.
        ->assertSeeHtml('cambiarEstado('.$postulante->id.', $event.target.value)')
        ->assertSee('Postuló');
});

test('desde el listado de una publicación se guarda al candidato como favorito', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    $postulante = postularA($publicacion, 'Ana Torres Vega', 'Biobío')->postulante;

    $componente = Livewire::actingAs($user)
        ->test(Postulaciones::class, ['publicacion' => $publicacion])
        ->call('toggleFavorito', $postulante->id);

    expect($empresa->fresh()->haMarcadoFavorito($postulante->id))->toBeTrue();

    $componente->call('toggleFavorito', $postulante->id);

    expect($empresa->fresh()->haMarcadoFavorito($postulante->id))->toBeFalse();
});

test('el listado de una publicación abre las notas del candidato en el panel rápido', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    $postulante = postularA($publicacion, 'Ana Torres Vega', 'Biobío')->postulante;

    NotaCandidato::query()->create([
        'empresa_id' => $empresa->id,
        'postulante_id' => $postulante->id,
        'user_id' => $user->id,
        'contenido' => 'Muy buena entrevista telefónica',
    ]);

    Livewire::actingAs($user)
        ->test(Postulaciones::class, ['publicacion' => $publicacion])
        ->assertSee('Ver notas de este candidato')
        ->call('abrirNotas', $postulante->id)
        ->assertSee('Muy buena entrevista telefónica')
        ->call('cerrarNotas')
        ->assertSet('notasPostulanteId', null);
});

test('no se pueden accionar candidatos ajenos a la publicación', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    [, , $otraPublicacion] = empresaConPublicacion('otra@empresa.cl');
    $ajeno = postularA($otraPublicacion, 'Beto Ajeno', 'Biobío')->postulante;

    Livewire::actingAs($user)
        ->test(Postulaciones::class, ['publicacion' => $publicacion])
        ->call('toggleFavorito', $ajeno->id)
        ->assertStatus(404);

    Livewire::actingAs($user)
        ->test(Postulaciones::class, ['publicacion' => $publicacion])
        ->call('abrirNotas', $ajeno->id)
        ->assertStatus(404);
});

test('el candidato agregado por la empresa arranca en revisión y se le puede cambiar la etapa', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    $agregado = agregarA($publicacion, 'Beto Agregado');

    $asociacion = PublicacionCandidato::query()
        ->where('publicacion_id', $publicacion->id)
        ->where('postulante_id', $agregado->id)
        ->firstOrFail();

    expect($asociacion->estado)->toBe('en_revision');

    // Y se mueve de etapa como cualquier otro, sin haber postulado nunca.
    Livewire::actingAs($user)
        ->test(Postulaciones::class, ['publicacion' => $publicacion])
        ->call('cambiarEstado', $agregado->id, 'seleccionada')
        ->assertHasNoErrors();

    expect($asociacion->fresh()->estado)->toBe('seleccionada')
        ->and(Postulacion::query()->count())->toBe(0);
});

test('el estado "Recibida" reemplaza a "Enviada" en el listado', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    postularA($publicacion, 'Ana Postuló', 'Biobío');

    Livewire::actingAs($user)
        ->test(Postulaciones::class, ['publicacion' => $publicacion])
        ->assertSee('Recibida')
        ->assertDontSee('Enviada');
});

test('los filtros se ofrecen como desplegables y no como filas de chips', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    postularA($publicacion, 'Ana Postuló', 'Biobío');
    agregarA($publicacion, 'Beto Agregado');

    $html = Livewire::actingAs($user)
        ->test(Postulaciones::class, ['publicacion' => $publicacion])
        ->html();

    expect($html)
        ->toContain('id="filtro-origen"')
        ->toContain('id="filtro-etapa"')
        // El conteo viaja dentro de cada opción para no perder la información.
        ->toContain('Recibidos (1)')
        ->toContain('Agregados (1)')
        ->toContain('En revisión (1)');
});
