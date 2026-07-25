<?php

use App\Livewire\Empresa\Postulaciones;
use App\Models\Empresa;
use App\Models\Postulacion;
use App\Models\Postulante;
use App\Models\Publicacion;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/** @return array{0: User, 1: Empresa, 2: Publicacion} */
function empresaConPublicacion(): array
{
    $user = User::factory()->create(['role' => 'empresa']);
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

test('an empresa sees the applicants of its publication with their answers', function () {
    [$user, $empresa, $publicacion] = empresaConPublicacion();
    postularA($publicacion, 'Ana Torres', 'Biobío');
    postularA($publicacion, 'Beto Díaz', 'Valparaíso');

    Livewire::actingAs($user)
        ->test(Postulaciones::class, ['publicacion' => $publicacion])
        ->assertViewHas('totalPostulaciones', 2)
        ->assertSee('Ana Torres')
        ->assertSee('Beto Díaz')
        ->assertSee('¿Por qué te interesa?')
        ->assertSee('Me interesa por la experiencia del equipo.');
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
