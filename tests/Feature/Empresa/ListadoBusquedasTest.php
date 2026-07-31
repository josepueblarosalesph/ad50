<?php

use App\Livewire\Empresa\Busquedas;
use App\Livewire\Empresa\NuevaBusqueda;
use App\Models\Empresa;
use App\Models\User;
use Livewire\Livewire;

/** Empresa con su contacto administrador y un usuario adicional del equipo. */
function equipoConBusquedas(): array
{
    $admin = User::factory()->create(['role' => 'empresa', 'name' => 'Ana Silva']);
    $empresa = Empresa::query()->create([
        'user_id' => $admin->id,
        'razon_social' => 'Empresa Listado',
        'estado_activacion' => 'activa',
    ]);

    $companero = User::factory()->create([
        'role' => 'empresa',
        'name' => 'Luis Rojas',
        'empresa_id' => $empresa->id,
    ]);

    return [$admin, $companero, $empresa];
}

test('el listado muestra la fecha de creación y quién creó cada búsqueda', function () {
    [$admin, $companero, $empresa] = equipoConBusquedas();

    $empresa->busquedas()->create([
        'titulo' => 'Jefatura de operaciones',
        'criterios' => [],
        'user_id' => $companero->id,
        'created_at' => now()->subDays(3),
    ]);

    Livewire::actingAs($admin)
        ->test(Busquedas::class)
        ->assertSee('Creada')
        ->assertSee('Creada por')
        ->assertSee(now()->subDays(3)->translatedFormat('d M Y'))
        ->assertSee('Luis Rojas')
        // La columna de favoritos salió del listado.
        ->assertDontSee('Favoritos');
});

test('una búsqueda sin autor conocido no rompe el listado', function () {
    [$admin, $companero, $empresa] = equipoConBusquedas();

    $busqueda = $empresa->busquedas()->create([
        'titulo' => 'Heredada',
        'criterios' => [],
        'user_id' => $companero->id,
    ]);

    // Si esa persona sale del equipo, la búsqueda queda sin autor pero sigue en pie.
    $companero->delete();

    expect($busqueda->fresh()->user_id)->toBeNull();

    Livewire::actingAs($admin)
        ->test(Busquedas::class)
        ->assertSee('Heredada')
        ->assertDontSee('Luis Rojas');
});

test('crear una búsqueda registra a quien la creó', function () {
    [$admin, $companero, $empresa] = equipoConBusquedas();

    Livewire::actingAs($companero)
        ->test(NuevaBusqueda::class)
        ->set('titulo', 'Controller Senior')
        ->set('cargo', ['Gerente Finanza'])
        ->call('save')
        ->assertHasNoErrors();

    $busqueda = $empresa->busquedas()->firstOrFail();

    expect($busqueda->user_id)->toBe($companero->id)
        ->and($busqueda->creador->name)->toBe('Luis Rojas');

    // Editar no cambia el autor: la búsqueda sigue siendo de quien la creó.
    Livewire::actingAs($admin)
        ->test(NuevaBusqueda::class, ['busqueda' => $busqueda])
        ->set('titulo', 'Controller Senior editado')
        ->call('save')
        ->assertHasNoErrors();

    expect($busqueda->fresh()->user_id)->toBe($companero->id);
});
