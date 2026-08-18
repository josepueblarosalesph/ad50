<?php

use App\Livewire\Admin\Catalogos;
use App\Models\Empresa;
use App\Models\Postulante;
use App\Models\TerminoCatalogo;
use App\Models\User;
use App\Services\UsoDeTerminos;
use App\Support\CatalogosProfesionales;
use Livewire\Livewire;

/**
 * Los catálogos los administra solo el superadministrador: son la fuente de verdad contra
 * la que validan la ficha y el formulario de búsqueda, y el matching compara por igualdad
 * exacta (ver Admin\Catalogos).
 */
function adminDeCatalogos(): User
{
    return User::factory()->create(['role' => 'superadmin']);
}

function termino(string $catalogo, string $valor): TerminoCatalogo
{
    return TerminoCatalogo::query()->firstOrCreate(['catalogo' => $catalogo, 'valor' => $valor], ['orden' => 999]);
}

test('el catálogo se administra desde la base y alimenta los formularios', function () {
    $admin = adminDeCatalogos();

    Livewire::actingAs($admin)
        ->test(Catalogos::class)
        ->set('catalogo', 'industria')
        ->call('abrirNuevo')
        ->set('valor', 'Industria Inventada')
        ->call('guardar')
        ->assertHasNoErrors();

    // El término nuevo queda disponible en toda la plataforma.
    expect(CatalogosProfesionales::industrias())->toContain('Industria Inventada');
});

test('no se permite repetir un término dentro del mismo catálogo', function () {
    $admin = adminDeCatalogos();
    termino('industria', 'Minería');

    Livewire::actingAs($admin)
        ->test(Catalogos::class)
        ->set('catalogo', 'industria')
        ->call('abrirNuevo')
        ->set('valor', 'Minería')
        ->call('guardar')
        ->assertHasErrors('valor');
});

test('un término sin uso se elimina, pero solo tras confirmar', function () {
    $admin = adminDeCatalogos();
    $sinUso = termino('industria', 'Industria Sin Uso');

    $componente = Livewire::actingAs($admin)->test(Catalogos::class)->set('catalogo', 'industria');

    // La confirmación no trae impedimento: el término no se usa en ninguna parte.
    $componente->call('confirmarBorrado', $sinUso->id)
        ->assertSet('borrandoValor', 'Industria Sin Uso')
        ->assertSet('bloqueo', '')
        ->assertSee('¿Estás seguro de que deseas eliminarlo?', escape: false);

    // Sigue existiendo mientras no se confirme.
    expect(TerminoCatalogo::query()->whereKey($sinUso->id)->exists())->toBeTrue();

    $componente->call('borrar')->assertHasNoErrors();

    expect(TerminoCatalogo::query()->whereKey($sinUso->id)->exists())->toBeFalse();
});

test('un término en uso no se puede eliminar y se explica dónde se usa', function () {
    $admin = adminDeCatalogos();
    $enUso = termino('industria', 'Industria Usada');

    // Una ficha declara esa industria entre sus intereses.
    Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true,
        'industrias_interes' => ['Industria Usada'],
    ]);

    Livewire::actingAs($admin)
        ->test(Catalogos::class)
        ->set('catalogo', 'industria')
        ->call('confirmarBorrado', $enUso->id)
        ->assertSee('No se puede eliminar porque está siendo usado')
        ->assertSee('industrias de interés de postulantes')
        // Sin botón de confirmar: la acción queda bloqueada.
        ->assertDontSee('¿Estás seguro de que deseas eliminarlo?', escape: false)
        ->call('borrar')
        ->assertStatus(422);

    expect(TerminoCatalogo::query()->whereKey($enUso->id)->exists())->toBeTrue();
});

test('un término en uso tampoco se puede editar', function () {
    $admin = adminDeCatalogos();
    $enUso = termino('cargo', 'Cargo Usado');

    Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true,
        'cargo_actual' => 'Cargo Usado',
    ]);

    Livewire::actingAs($admin)
        ->test(Catalogos::class)
        ->set('catalogo', 'cargo')
        ->call('abrirEdicion', $enUso->id)
        ->assertSee('No se puede editar porque está siendo usado')
        ->set('valor', 'Otro nombre')
        ->call('guardar')
        ->assertStatus(422);

    expect($enUso->fresh()->valor)->toBe('Cargo Usado');
});

test('un término sin uso sí se puede renombrar', function () {
    $admin = adminDeCatalogos();
    $libre = termino('industria', 'Nombre Viejo');

    Livewire::actingAs($admin)
        ->test(Catalogos::class)
        ->set('catalogo', 'industria')
        ->call('abrirEdicion', $libre->id)
        ->assertSet('bloqueo', '')
        ->set('valor', 'Nombre Nuevo')
        ->call('guardar')
        ->assertHasNoErrors();

    expect($libre->fresh()->valor)->toBe('Nombre Nuevo');
});

test('la verificación de uso reconoce los distintos lugares donde queda guardado', function () {
    $uso = app(UsoDeTerminos::class);

    $postulante = Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true,
        // Dentro de una lista JSON de objetos.
        'experiencias' => [['cargo' => 'Cargo En Experiencia', 'empresa' => 'Alguna']],
    ]);

    expect($uso->estaEnUso('cargo', 'Cargo En Experiencia'))->toBeTrue()
        ->and($uso->estaEnUso('cargo', 'Cargo Que Nadie Usa'))->toBeFalse();

    // Y dentro de los criterios guardados de una búsqueda.
    $empresa = Empresa::query()->create([
        'user_id' => User::factory()->create(['role' => 'empresa'])->id,
        'razon_social' => 'E',
        'estado_activacion' => 'activa',
    ]);
    $empresa->busquedas()->create(['titulo' => 'B', 'criterios' => ['industria' => ['Industria En Criterio']]]);

    expect($uso->estaEnUso('industria', 'Industria En Criterio'))->toBeTrue();

    // Una búsqueda en papelera ya no cuenta como uso vigente.
    $empresa->busquedas()->first()->delete();

    expect($uso->estaEnUso('industria', 'Industria En Criterio'))->toBeFalse()
        ->and($postulante->exists)->toBeTrue();
});

test('solo el superadministrador entra a los catálogos', function () {
    // El admin común entra en la lista: administrar catálogos dejó de estar a su alcance.
    foreach (['postulante', 'empresa', 'admin'] as $rol) {
        $this->actingAs(User::factory()->create(['role' => $rol]))
            ->get(route('admin.catalogos'))
            ->assertForbidden();
    }

    $this->actingAs(User::factory()->create(['role' => 'superadmin']))
        ->get(route('admin.catalogos'))
        ->assertOk();
});
