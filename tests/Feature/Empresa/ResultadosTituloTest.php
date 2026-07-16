<?php

use App\Livewire\Empresa\Resultados;
use App\Models\Empresa;
use App\Models\User;
use Livewire\Livewire;

test('an empresa can rename a search from the results page', function () {
    $user = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $user->id, 'razon_social' => 'Empresa', 'estado_activacion' => 'activa']);
    $busqueda = $empresa->busquedas()->create(['titulo' => 'Nombre viejo', 'criterios' => []]);

    Livewire::actingAs($user)
        ->test(Resultados::class, ['busqueda' => $busqueda])
        ->call('editarTitulo')
        ->assertSet('editandoTitulo', true)
        ->assertSet('tituloEditado', 'Nombre viejo')
        ->set('tituloEditado', 'Nombre nuevo')
        ->call('guardarTitulo')
        ->assertHasNoErrors()
        ->assertSet('editandoTitulo', false);

    expect($busqueda->fresh()->titulo)->toBe('Nombre nuevo');
});

test('the search name cannot be renamed to blank', function () {
    $user = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $user->id, 'razon_social' => 'Empresa', 'estado_activacion' => 'activa']);
    $busqueda = $empresa->busquedas()->create(['titulo' => 'Nombre viejo', 'criterios' => []]);

    Livewire::actingAs($user)
        ->test(Resultados::class, ['busqueda' => $busqueda])
        ->call('editarTitulo')
        ->set('tituloEditado', '')
        ->call('guardarTitulo')
        ->assertHasErrors(['tituloEditado' => 'required']);

    expect($busqueda->fresh()->titulo)->toBe('Nombre viejo');
});
