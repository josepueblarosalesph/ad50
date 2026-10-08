<?php

use App\Livewire\Admin\Postulantes as AdminPostulantes;
use App\Models\User;
use Livewire\Livewire;

test('the postulantes listing shows the region and filters by it', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));

    $delSur = postulanteEnRegion('Carmen del Sur', 'Biobío');
    $deSantiago = postulanteEnRegion('Rodrigo Capital', 'Metropolitana de Santiago');

    $componente = Livewire::test(AdminPostulantes::class);

    // La columna nueva muestra la región de cada ficha.
    $componente->assertSee('Biobío')->assertSee('Metropolitana de Santiago');

    $componente->set('region', 'Biobío')
        ->assertSee($delSur->name)
        ->assertDontSee($deSantiago->name);
});

test('the region column can be sorted, and does not steal the default order', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));

    postulanteEnRegion('Carmen del Sur', 'Biobío');
    postulanteEnRegion('Rodrigo Capital', 'Metropolitana de Santiago');

    Livewire::test(AdminPostulantes::class)
        // La columna nueva se añadió después de la primera, que es la que manda el
        // orden inicial en OrdenaListado::ordenPorDefecto().
        ->assertSet('orden', 'created_at')
        ->call('ordenarPor', 'region')
        ->assertSet('orden', 'region')
        ->assertSet('direccion', 'asc')
        // Por los nombres y no por las regiones: el propio filtro imprime las 16
        // regiones del catálogo en su orden, antes de la tabla.
        ->assertSeeInOrder(['Carmen del Sur', 'Rodrigo Capital'])
        // Y el segundo clic invierte el sentido.
        ->call('ordenarPor', 'region')
        ->assertSet('direccion', 'desc')
        ->assertSeeInOrder(['Rodrigo Capital', 'Carmen del Sur']);
});

test('an unknown region in the url falls back to showing every profile', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));

    $delSur = postulanteEnRegion('Carmen del Sur', 'Biobío');

    Livewire::withUrlParams(['region' => 'Patagonia Inventada'])
        ->test(AdminPostulantes::class)
        ->assertSet('region', 'todos')
        ->assertSee($delSur->name);
});

test('clearing the filters also clears the region', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));

    Livewire::test(AdminPostulantes::class)
        ->set('region', 'Biobío')
        ->call('limpiarFiltros')
        ->assertSet('region', 'todos');
});
