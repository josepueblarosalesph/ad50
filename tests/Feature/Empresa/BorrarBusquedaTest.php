<?php

use App\Livewire\Empresa\Busquedas;
use App\Models\Busqueda;
use App\Models\Empresa;
use App\Models\User;
use Livewire\Livewire;

test('an empresa can delete its own search', function () {
    $user = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $user->id, 'razon_social' => 'E', 'estado_activacion' => 'activa']);
    $busqueda = $empresa->busquedas()->create(['titulo' => 'A borrar', 'criterios' => []]);

    Livewire::actingAs($user)
        ->test(Busquedas::class)
        ->call('borrar', $busqueda->id)
        ->assertHasNoErrors();

    expect(Busqueda::query()->whereKey($busqueda->id)->exists())->toBeFalse();
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

    Livewire::actingAs($user)
        ->test(Busquedas::class)
        ->call('borrar', $ajena->id)
        ->assertForbidden();

    expect(Busqueda::query()->whereKey($ajena->id)->exists())->toBeTrue();
});
