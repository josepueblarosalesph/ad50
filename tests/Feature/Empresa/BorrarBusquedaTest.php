<?php

use App\Livewire\Empresa\Busquedas;
use App\Models\Busqueda;
use App\Models\Empresa;
use App\Models\Postulante;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

test('an empresa can delete its own search after typing ELIMINAR', function () {
    $user = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $user->id, 'razon_social' => 'E', 'estado_activacion' => 'activa']);
    $busqueda = $empresa->busquedas()->create(['titulo' => 'A borrar', 'criterios' => []]);

    Livewire::actingAs($user)
        ->test(Busquedas::class)
        ->call('confirmarBorrado', $busqueda->id)
        ->assertSet('borrandoId', $busqueda->id)
        ->set('confirmacionTexto', 'ELIMINAR')
        ->call('borrar')
        ->assertHasNoErrors();

    expect(Busqueda::query()->whereKey($busqueda->id)->exists())->toBeFalse();
});

test('deleting soft-deletes the process and offers undo with the 30-day notice', function () {
    $user = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $user->id, 'razon_social' => 'E', 'estado_activacion' => 'activa']);
    $busqueda = $empresa->busquedas()->create(['titulo' => 'A borrar', 'criterios' => []]);

    Livewire::actingAs($user)
        ->test(Busquedas::class)
        ->call('confirmarBorrado', $busqueda->id)
        ->set('confirmacionTexto', 'ELIMINAR')
        ->call('borrar')
        ->assertSet('eliminadoId', $busqueda->id)
        ->assertSet('eliminadoTitulo', 'A borrar')
        // El aviso nombra la búsqueda borrada, ofrece deshacer y explica la retención.
        ->assertSee('Eliminaste la búsqueda')
        ->assertSee('A borrar')
        ->assertSee('Deshacer')
        ->assertSee('se eliminará en forma definitiva en los siguientes 30 días');

    // Queda en papelera: fuera de las consultas normales, pero recuperable.
    expect(Busqueda::query()->whereKey($busqueda->id)->exists())->toBeFalse()
        ->and(Busqueda::withTrashed()->whereKey($busqueda->id)->exists())->toBeTrue()
        ->and($busqueda->fresh()->trashed())->toBeTrue();
});

test('undo restores a soft-deleted process', function () {
    $user = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $user->id, 'razon_social' => 'E', 'estado_activacion' => 'activa']);
    $busqueda = $empresa->busquedas()->create(['titulo' => 'A borrar', 'criterios' => []]);

    Livewire::actingAs($user)
        ->test(Busquedas::class)
        ->call('confirmarBorrado', $busqueda->id)
        ->set('confirmacionTexto', 'ELIMINAR')
        ->call('borrar')
        ->call('restaurar')
        ->assertSet('eliminadoId', null);

    expect(Busqueda::query()->whereKey($busqueda->id)->exists())->toBeTrue();
});

test('the purge command permanently deletes processes trashed over 30 days ago', function () {
    $user = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $user->id, 'razon_social' => 'E', 'estado_activacion' => 'activa']);
    $vieja = $empresa->busquedas()->create(['titulo' => 'Vieja', 'criterios' => []]);
    $reciente = $empresa->busquedas()->create(['titulo' => 'Reciente', 'criterios' => []]);

    $vieja->delete();
    $vieja->forceFill(['deleted_at' => now()->subDays(31)])->saveQuietly();
    $reciente->delete();

    test()->artisan('busquedas:purgar-eliminadas')->assertSuccessful();

    expect(Busqueda::withTrashed()->whereKey($vieja->id)->exists())->toBeFalse()
        ->and(Busqueda::withTrashed()->whereKey($reciente->id)->exists())->toBeTrue();
});

test('deleting requires typing ELIMINAR', function () {
    $user = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $user->id, 'razon_social' => 'E', 'estado_activacion' => 'activa']);
    $busqueda = $empresa->busquedas()->create(['titulo' => 'A borrar', 'criterios' => []]);

    Livewire::actingAs($user)
        ->test(Busquedas::class)
        ->call('confirmarBorrado', $busqueda->id)
        ->set('confirmacionTexto', 'borrar')
        ->call('borrar')
        ->assertHasErrors('confirmacionTexto');

    expect(Busqueda::query()->whereKey($busqueda->id)->exists())->toBeTrue();
});

test('la búsqueda ya no tiene estado ni etapa: es solo una configuración de filtros', function () {
    $user = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $user->id, 'razon_social' => 'E', 'estado_activacion' => 'activa']);
    $empresa->busquedas()->create(['titulo' => 'Proceso', 'criterios' => []]);

    // Ni la columna en la base ni el control en el listado.
    expect(Schema::hasColumn('busquedas', 'estado'))->toBeFalse();

    Livewire::actingAs($user)
        ->test(Busquedas::class)
        ->assertDontSee('Estado de la búsqueda')
        ->assertDontSee('Long List')
        ->assertDontSee('Fecha de creación');
});

test('an empresa cannot delete another company search', function () {
    $otraEmpresa = Empresa::query()->create(['user_id' => User::factory()->create(['role' => 'empresa'])->id, 'razon_social' => 'Otra', 'estado_activacion' => 'activa']);
    $ajena = $otraEmpresa->busquedas()->create(['titulo' => 'Ajena', 'criterios' => []]);

    $user = User::factory()->create(['role' => 'empresa']);
    Empresa::query()->create(['user_id' => $user->id, 'razon_social' => 'Mía', 'estado_activacion' => 'activa']);

    // No puede ni abrir la confirmación de un proceso ajeno.
    Livewire::actingAs($user)
        ->test(Busquedas::class)
        ->call('confirmarBorrado', $ajena->id)
        ->assertForbidden();

    expect(Busqueda::query()->whereKey($ajena->id)->exists())->toBeTrue();
});

test('el listado ordena por la columna elegida e invierte al repetirla', function () {
    $user = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $user->id, 'razon_social' => 'E', 'estado_activacion' => 'activa']);

    foreach (['Charlie', 'Alfa', 'Bravo'] as $i => $titulo) {
        // Fechas distintas: sin ellas el `latest()` empata y el orden inicial no es comprobable.
        $empresa->busquedas()->create(['titulo' => $titulo, 'criterios' => [], 'created_at' => now()->subDays(3 - $i)]);
    }

    $titulos = fn ($componente): array => $componente->viewData('busquedas')->pluck('titulo')->all();

    $componente = Livewire::actingAs($user)->test(Busquedas::class);

    // Sin columna elegida manda el `latest()` de la consulta: lo más reciente primero.
    expect($componente->get('orden'))->toBe('')
        ->and($titulos($componente))->toBe(['Bravo', 'Alfa', 'Charlie']);

    // Al elegir el título parte ascendente...
    $componente->call('ordenarPor', 'titulo');
    expect($componente->get('direccion'))->toBe('asc')
        ->and($titulos($componente))->toBe(['Alfa', 'Bravo', 'Charlie']);

    // ...y repetir la misma columna invierte el sentido.
    $componente->call('ordenarPor', 'titulo');
    expect($componente->get('direccion'))->toBe('desc')
        ->and($titulos($componente))->toBe(['Charlie', 'Bravo', 'Alfa']);
});

test('se puede ordenar por el conteo de candidatos', function () {
    $user = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $user->id, 'razon_social' => 'E', 'estado_activacion' => 'activa']);

    foreach (['Con dos' => 2, 'Sin nadie' => 0, 'Con uno' => 1] as $titulo => $cantidad) {
        $busqueda = $empresa->busquedas()->create(['titulo' => $titulo, 'criterios' => []]);

        // Ojo: range(1, 0) devuelve [1, 0] en PHP, así que el conteo se hace con un for.
        for ($i = 0; $i < $cantidad; $i++) {
            $postulante = Postulante::query()->create([
                'user_id' => User::factory()->create(['role' => 'postulante'])->id,
                'visible' => true,
            ]);
            $busqueda->candidatos()->create(['postulante_id' => $postulante->id, 'estado_match' => 'cumple']);
        }
    }

    // Un conteo parte descendente: lo útil es ver primero las que tienen más candidatos.
    $componente = Livewire::actingAs($user)->test(Busquedas::class)->call('ordenarPor', 'candidatos');

    expect($componente->get('direccion'))->toBe('desc')
        ->and($componente->viewData('busquedas')->pluck('titulo')->all())
        ->toBe(['Con dos', 'Con uno', 'Sin nadie']);

    $componente->call('ordenarPor', 'candidatos');

    expect($componente->viewData('busquedas')->pluck('titulo')->all())
        ->toBe(['Sin nadie', 'Con uno', 'Con dos']);
});

test('una columna que no es ordenable se rechaza', function () {
    $user = User::factory()->create(['role' => 'empresa']);
    Empresa::query()->create(['user_id' => $user->id, 'razon_social' => 'E', 'estado_activacion' => 'activa']);

    Livewire::actingAs($user)
        ->test(Busquedas::class)
        ->call('ordenarPor', 'criterios')
        ->assertStatus(404);
});

test('el encabezado marca la columna activa y su sentido', function () {
    $user = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $user->id, 'razon_social' => 'E', 'estado_activacion' => 'activa']);
    $empresa->busquedas()->create(['titulo' => 'Una', 'criterios' => []]);

    Livewire::actingAs($user)
        ->test(Busquedas::class)
        ->call('ordenarPor', 'titulo')
        // La columna ordenada informa el sentido a lectores de pantalla.
        ->assertSee('aria-sort="ascending"', false)
        ->assertSee('aria-sort="none"', false)
        ->assertSee('Favoritos');
});
