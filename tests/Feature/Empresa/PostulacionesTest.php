<?php

use App\Livewire\Empresa\FiltrosPostulaciones;
use App\Livewire\Empresa\Postulaciones;
use App\Models\Empresa;
use App\Models\Postulacion;
use App\Models\Postulante;
use App\Models\Publicacion;
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

function postularA(Publicacion $publicacion, string $nombre, string $region, string $estado = 'enviada', array $extra = []): Postulacion
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
        ->assertViewHas('totalPostulaciones', 2)
        ->assertSee('Ana Torres')
        ->assertSee('Beto Díaz')
        // El detalle (respuestas, contacto) ya no estira cada tarjeta.
        ->assertDontSee('Me interesa por la experiencia del equipo.');
});

test('al hacer clic en el nombre se abre el detalle del postulante', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    $postulacion = postularA($publicacion, 'Ana Torres', 'Biobío');
    postularA($publicacion, 'Beto Díaz', 'Valparaíso');

    Livewire::actingAs($user)
        ->test(Postulaciones::class, ['publicacion' => $publicacion])
        ->call('verDetalle', $postulacion->id)
        ->assertSet('detalleId', $postulacion->id)
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
        ->call('verDetalle', $ajena->id)
        ->assertStatus(404);
});

test('an empresa can change the estado of an application', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    $postulacion = postularA($publicacion, 'Ana Torres', 'Biobío');

    Livewire::actingAs($user)
        ->test(Postulaciones::class, ['publicacion' => $publicacion])
        ->call('cambiarEstado', $postulacion->id, 'seleccionada')
        ->assertHasNoErrors();

    expect($postulacion->fresh()->estado)->toBe('seleccionada');

    // Un estado inválido se rechaza.
    Livewire::actingAs($user)
        ->test(Postulaciones::class, ['publicacion' => $publicacion])
        ->call('cambiarEstado', $postulacion->id, 'inventado')
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
    postularA($publicacion, 'Beto Díaz', 'Valparaíso', 'enviada');

    Livewire::actingAs($user)
        ->test(Postulaciones::class, ['publicacion' => $publicacion])
        ->call('mostrarEstado', 'seleccionada')
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
        ->assertSee('wire:target="verDetalle('.$postulacion->id.')"', false)
        ->assertSee('animate-spin', false);
});
