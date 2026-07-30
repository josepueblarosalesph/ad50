<?php

use App\Livewire\Empresa\DetallePublicacion;
use App\Livewire\Empresa\NuevaPublicacion;
use App\Livewire\Empresa\Publicaciones;
use App\Models\Empresa;
use App\Models\Postulacion;
use App\Models\Postulante;
use App\Models\Publicacion;
use App\Models\User;
use Livewire\Livewire;

/** @return array{0: User, 1: Empresa} */
function empresaConPublicaciones(string $email = 'crud@empresa.cl'): array
{
    $user = User::factory()->create(['role' => 'empresa', 'email' => $email]);
    $empresa = Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => 'Empresa CRUD SpA',
        'estado_activacion' => 'activa',
    ]);
    hacerEmpresaOperativa($empresa);

    return [$user->fresh(), $empresa->fresh()];
}

test('el listado ofrece ver, editar y borrar cada publicación', function () {
    [$user, $empresa] = empresaConPublicaciones();
    $publicacion = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
        'cargo' => 'Jefe de Planta',
    ]);

    $this->actingAs($user)
        ->get(route('empresa.publicaciones.index'))
        ->assertOk()
        ->assertSee('href="'.route('empresa.publicaciones.show', $publicacion).'"', false)
        ->assertSee('href="'.route('empresa.publicaciones.edit', $publicacion).'"', false)
        ->assertSee('Borrar');
});

test('la empresa ve el detalle completo de su publicación', function () {
    [$user, $empresa] = empresaConPublicaciones();
    $publicacion = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
        'cargo' => 'Jefe de Planta',
        'competencias' => ['Liderazgo', 'Seguridad industrial'],
        'preguntas' => ['¿Has liderado plantas productivas?'],
    ]);

    $this->actingAs($user)
        ->get(route('empresa.publicaciones.show', $publicacion))
        ->assertOk()
        ->assertSee('Jefe de Planta')
        ->assertSee('Liderazgo')
        ->assertSee('Seguridad industrial')
        ->assertSee('¿Has liderado plantas productivas?', false)
        ->assertSee('Ver postulantes')
        ->assertSee('Editar')
        ->assertSee('Eliminar');
});

test('la publicación recorre las etapas del proceso y sigue visible mientras está abierta', function () {
    [$user, $empresa] = empresaConPublicaciones();
    $publicacion = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
        'cargo' => 'Jefe de Planta',
    ]);

    // Las etapas del pipeline viven aquí, no en la búsqueda.
    expect(Publicacion::ESTADOS)->toHaveKeys(['publicada', 'long_list', 'short_list', 'entrevistas', 'pausada', 'cerrada', 'cancelada']);

    Livewire::actingAs($user)
        ->test(Publicaciones::class)
        ->call('cambiarEstado', $publicacion->id, 'entrevistas')
        ->assertHasNoErrors();

    expect($publicacion->fresh()->estado)->toBe('entrevistas')
        // Avanzar en el proceso no saca la oferta del portal.
        ->and($publicacion->fresh()->estaVigente())->toBeTrue()
        ->and(Publicacion::vigentes()->whereKey($publicacion->id)->exists())->toBeTrue();

    // Cerrarla sí la retira.
    Livewire::actingAs($user)
        ->test(Publicaciones::class)
        ->call('cambiarEstado', $publicacion->id, 'cerrada');

    expect($publicacion->fresh()->estaVigente())->toBeFalse()
        ->and(Publicacion::vigentes()->whereKey($publicacion->id)->exists())->toBeFalse();

    // Un estado fuera del catálogo se rechaza.
    Livewire::actingAs($user)
        ->test(Publicaciones::class)
        ->call('cambiarEstado', $publicacion->id, 'inventado')
        ->assertStatus(422);
});

test('la sección de publicaciones muestra su menú lateral en listado, detalle y formulario', function () {
    [$user, $empresa] = empresaConPublicaciones();
    $publicacion = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
    ]);

    $rutas = [
        route('empresa.publicaciones.index'),
        route('empresa.publicaciones.show', $publicacion),
        route('empresa.publicaciones.create'),
        route('empresa.publicaciones.edit', $publicacion),
    ];

    foreach ($rutas as $ruta) {
        $this->actingAs($user)->get($ruta)
            ->assertOk()
            // El <aside> lo pinta el layout solo cuando la vista llena el slot `sidebar`.
            ->assertSee('<aside class="relative z-20 hidden border-r', false)
            ->assertSee('Todas las publicaciones');
    }

    // En el formulario de edición el segundo ítem apunta a esa publicación.
    $this->actingAs($user)->get(route('empresa.publicaciones.edit', $publicacion))
        ->assertSee('Editar publicación')
        ->assertDontSee('Nueva publicación');
});

test('cada tarjeta del detalle enlaza a su sección del formulario de edición', function () {
    [$user, $empresa] = empresaConPublicaciones();
    $publicacion = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
    ]);

    $edicion = route('empresa.publicaciones.edit', $publicacion);

    $respuesta = $this->actingAs($user)
        ->get(route('empresa.publicaciones.show', $publicacion))
        ->assertOk();

    foreach (['descripcion-general', 'requisitos', 'preguntas', 'configuraciones'] as $ancla) {
        $respuesta->assertSee('href="'.$edicion.'#'.$ancla.'"', false);
    }

    // El formulario declara las anclas a las que apuntan esos botones.
    $formulario = $this->actingAs($user)->get($edicion)->assertOk();

    foreach (['descripcion-general', 'requisitos', 'preguntas', 'configuraciones'] as $ancla) {
        $formulario->assertSee('id="'.$ancla.'"', false);
    }
});

test('una empresa no puede ver el detalle de una publicación ajena', function () {
    [, $empresa] = empresaConPublicaciones('duenio@empresa.cl');
    [$intruso] = empresaConPublicaciones('intruso@empresa.cl');
    $ajena = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
    ]);

    $this->actingAs($intruso)
        ->get(route('empresa.publicaciones.show', $ajena))
        ->assertForbidden();
});

test('la empresa edita su publicación y conserva la fecha de vigencia si no la cambia', function () {
    [$user, $empresa] = empresaConPublicaciones();
    $publicacion = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
        'cargo' => 'Cargo original',
        'vigencia_dias' => 30,
        'vigente_hasta' => today()->addDays(10),
        'competencias' => ['Liderazgo', 'Excel'],
        'preguntas' => ['Pregunta original'],
    ]);

    $this->actingAs($user)
        ->get(route('empresa.publicaciones.edit', $publicacion))
        ->assertOk()
        ->assertSee('Editar oferta laboral')
        ->assertSee('Guardar cambios')
        ->assertSee('Cargo original');

    Livewire::actingAs($user)
        ->test(NuevaPublicacion::class, ['publicacion' => $publicacion])
        // El formulario llega hidratado con los datos actuales.
        ->assertSet('cargo', 'Cargo original')
        ->assertSet('competenciasTexto', 'Liderazgo, Excel')
        ->assertSet('preguntas', ['Pregunta original'])
        ->assertSet('vigenciaDias', 30)
        ->set('cargo', 'Cargo actualizado')
        ->set('vacantes', 3)
        ->set('competenciasTexto', 'Liderazgo, Negociación')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertRedirect(route('empresa.publicaciones.show', $publicacion));

    $publicacion->refresh();

    expect($publicacion->cargo)->toBe('Cargo actualizado')
        ->and($publicacion->vacantes)->toBe(3)
        ->and($publicacion->competencias)->toBe(['Liderazgo', 'Negociación'])
        // No se creó un duplicado ni se movió la fecha de término.
        ->and(Publicacion::query()->count())->toBe(1)
        ->and($publicacion->vigente_hasta->toDateString())->toBe(today()->addDays(10)->toDateString());
});

test('cambiar la vigencia al editar recalcula la fecha desde hoy', function () {
    [$user, $empresa] = empresaConPublicaciones();
    $publicacion = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
        'vigencia_dias' => 30,
        'vigente_hasta' => today()->addDays(2),
    ]);

    Livewire::actingAs($user)
        ->test(NuevaPublicacion::class, ['publicacion' => $publicacion])
        ->set('vigenciaDias', 90)
        ->call('guardar')
        ->assertHasNoErrors();

    expect($publicacion->fresh()->vigente_hasta->toDateString())
        ->toBe(today()->addDays(90)->toDateString());
});

test('una empresa no puede editar la publicación de otra empresa', function () {
    [, $empresa] = empresaConPublicaciones('duenio@empresa.cl');
    [$intruso] = empresaConPublicaciones('intruso@empresa.cl');
    $ajena = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
    ]);

    $this->actingAs($intruso)
        ->get(route('empresa.publicaciones.edit', $ajena))
        ->assertForbidden();
});

test('borrar una publicación la manda a papelera y ofrece deshacer', function () {
    [$user, $empresa] = empresaConPublicaciones();
    $publicacion = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
        'cargo' => 'A borrar',
    ]);

    Livewire::actingAs($user)
        ->test(Publicaciones::class)
        ->call('confirmarBorrado', $publicacion->id)
        ->assertSet('borrandoId', $publicacion->id)
        ->set('confirmacionTexto', 'ELIMINAR')
        ->call('borrar')
        ->assertHasNoErrors()
        ->assertSet('eliminadoId', $publicacion->id)
        ->assertSee('Deshacer')
        ->assertSee('30 días');

    expect(Publicacion::query()->whereKey($publicacion->id)->exists())->toBeFalse()
        ->and(Publicacion::withTrashed()->whereKey($publicacion->id)->exists())->toBeTrue();
});

test('borrar exige escribir ELIMINAR', function () {
    [$user, $empresa] = empresaConPublicaciones();
    $publicacion = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
    ]);

    Livewire::actingAs($user)
        ->test(Publicaciones::class)
        ->call('confirmarBorrado', $publicacion->id)
        ->set('confirmacionTexto', 'borrar')
        ->call('borrar')
        ->assertHasErrors('confirmacionTexto');

    expect(Publicacion::query()->whereKey($publicacion->id)->exists())->toBeTrue();
});

test('deshacer restaura la publicación eliminada', function () {
    [$user, $empresa] = empresaConPublicaciones();
    $publicacion = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
    ]);

    Livewire::actingAs($user)
        ->test(Publicaciones::class)
        ->call('confirmarBorrado', $publicacion->id)
        ->set('confirmacionTexto', 'ELIMINAR')
        ->call('borrar')
        ->call('restaurar')
        ->assertSet('eliminadoId', null);

    expect(Publicacion::query()->whereKey($publicacion->id)->exists())->toBeTrue();
});

test('también se puede borrar desde el detalle y el listado ofrece deshacer', function () {
    [$user, $empresa] = empresaConPublicaciones();
    $publicacion = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
        'cargo' => 'Borrada desde el detalle',
    ]);

    Livewire::actingAs($user)
        ->test(DetallePublicacion::class, ['publicacion' => $publicacion])
        ->call('confirmarBorrado')
        ->set('confirmacionTexto', 'ELIMINAR')
        ->call('borrar')
        ->assertHasNoErrors()
        ->assertRedirect(route('empresa.publicaciones.index'));

    expect(Publicacion::withTrashed()->whereKey($publicacion->id)->first()->trashed())->toBeTrue();

    // El listado recoge el aviso de la sesión y permite deshacer.
    Livewire::actingAs($user)
        ->test(Publicaciones::class)
        ->assertSet('eliminadoId', $publicacion->id)
        ->assertSee('Borrada desde el detalle')
        ->call('restaurar');

    expect(Publicacion::query()->whereKey($publicacion->id)->exists())->toBeTrue();
});

test('una empresa no puede borrar la publicación de otra empresa', function () {
    [, $empresa] = empresaConPublicaciones('duenio@empresa.cl');
    [$intruso] = empresaConPublicaciones('intruso@empresa.cl');
    $ajena = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
    ]);

    Livewire::actingAs($intruso)
        ->test(Publicaciones::class)
        ->call('confirmarBorrado', $ajena->id)
        ->assertForbidden();

    expect(Publicacion::query()->whereKey($ajena->id)->exists())->toBeTrue();
});

test('una publicación en papelera desaparece del portal de postulantes', function () {
    [, $empresa] = empresaConPublicaciones();
    $publicacion = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
        'cargo' => 'Oferta retirada',
    ]);

    expect(Publicacion::query()->vigentes()->count())->toBe(1);

    $publicacion->delete();

    expect(Publicacion::query()->vigentes()->count())->toBe(0);
});

test('el comando purga las publicaciones en papelera con más de 30 días', function () {
    [, $empresa] = empresaConPublicaciones();
    $postulante = Postulante::factory()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
    ]);

    $vieja = Publicacion::factory()->create(['empresa_id' => $empresa->id, 'nombre_empresa' => $empresa->razon_social]);
    $reciente = Publicacion::factory()->create(['empresa_id' => $empresa->id, 'nombre_empresa' => $empresa->razon_social]);

    Postulacion::query()->create(['publicacion_id' => $vieja->id, 'postulante_id' => $postulante->id]);

    $vieja->delete();
    $vieja->forceFill(['deleted_at' => now()->subDays(31)])->saveQuietly();
    $reciente->delete();

    test()->artisan('publicaciones:purgar-eliminadas')->assertSuccessful();

    expect(Publicacion::withTrashed()->whereKey($vieja->id)->exists())->toBeFalse()
        ->and(Publicacion::withTrashed()->whereKey($reciente->id)->exists())->toBeTrue()
        // La FK en cascada se llevó también sus postulaciones.
        ->and(Postulacion::query()->where('publicacion_id', $vieja->id)->exists())->toBeFalse();
});

test('cambiar el estado desde el detalle queda validado', function () {
    [$user, $empresa] = empresaConPublicaciones();
    $publicacion = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
    ]);

    Livewire::actingAs($user)
        ->test(DetallePublicacion::class, ['publicacion' => $publicacion])
        ->call('cambiarEstado', 'pausada')
        ->assertHasNoErrors();

    expect($publicacion->fresh()->estado)->toBe('pausada');

    Livewire::actingAs($user)
        ->test(DetallePublicacion::class, ['publicacion' => $publicacion])
        ->call('cambiarEstado', 'inventado')
        ->assertStatus(422);

    expect($publicacion->fresh()->estado)->toBe('pausada');
});
