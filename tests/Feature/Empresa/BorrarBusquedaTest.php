<?php

use App\Livewire\Empresa\Busquedas;
use App\Models\Busqueda;
use App\Models\Empresa;
use App\Models\User;
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
        ->assertSee('Deshacer')
        ->assertSee('30 días');

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

test('an empresa can change a process state and it is validated', function () {
    $user = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $user->id, 'razon_social' => 'E', 'estado_activacion' => 'activa']);
    $busqueda = $empresa->busquedas()->create(['titulo' => 'Proceso', 'criterios' => [], 'estado' => 'long_list']);

    Livewire::actingAs($user)
        ->test(Busquedas::class)
        ->call('cambiarEstado', $busqueda->id, 'entrevistas')
        ->assertHasNoErrors();

    expect($busqueda->fresh()->estado)->toBe('entrevistas');

    // Un estado fuera del catálogo se rechaza.
    Livewire::actingAs($user)
        ->test(Busquedas::class)
        ->call('cambiarEstado', $busqueda->id, 'inventado')
        ->assertStatus(422);

    expect($busqueda->fresh()->estado)->toBe('entrevistas');
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
