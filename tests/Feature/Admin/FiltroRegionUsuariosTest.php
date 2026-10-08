<?php

use App\Livewire\Admin\Usuarios as AdminUsuarios;
use App\Models\Postulante;
use App\Models\User;
use Livewire\Livewire;

/**
 * La región de residencia vive en `postulantes.ciudad` (la columna guarda regiones
 * desde la migración 2026_07_09_000005), así que el filtro del listado de usuarios
 * solo puede alcanzar a las cuentas con ficha de postulante.
 */
function postulanteEnRegion(string $nombre, string $region): User
{
    $user = User::factory()->create(['role' => 'postulante', 'name' => $nombre]);

    Postulante::factory()->create([
        'user_id' => $user->id,
        'ciudad' => $region,
        'onboarding_completado' => true,
    ]);

    return $user;
}

test('the users listing shows each account region and filters by it', function () {
    $this->actingAs(User::factory()->create(['role' => 'superadmin']));

    $delSur = postulanteEnRegion('Carmen del Sur', 'Biobío');
    $deSantiago = postulanteEnRegion('Rodrigo Capital', 'Metropolitana de Santiago');

    $componente = Livewire::test(AdminUsuarios::class);

    // La columna nueva muestra la región de cada ficha.
    $componente->assertSee('Biobío')->assertSee('Metropolitana de Santiago');

    $componente->set('region', 'Biobío')
        ->assertSee($delSur->name)
        ->assertDontSee($deSantiago->name);
});

test('filtering by region leaves out the accounts without a postulante profile', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $this->actingAs($superadmin);

    $delSur = postulanteEnRegion('Carmen del Sur', 'Biobío');

    Livewire::test(AdminUsuarios::class)
        ->set('region', 'Biobío')
        ->assertSee($delSur->name)
        ->assertDontSee($superadmin->name);
});

test('an unknown region in the url falls back to showing everybody', function () {
    $this->actingAs(User::factory()->create(['role' => 'superadmin']));

    $delSur = postulanteEnRegion('Carmen del Sur', 'Biobío');

    Livewire::withUrlParams(['region' => 'Patagonia Inventada'])
        ->test(AdminUsuarios::class)
        ->assertSet('region', 'todos')
        ->assertSee($delSur->name);
});

test('clearing the filters also clears the region', function () {
    $this->actingAs(User::factory()->create(['role' => 'superadmin']));

    Livewire::test(AdminUsuarios::class)
        ->set('region', 'Biobío')
        ->call('limpiarFiltros')
        ->assertSet('region', 'todos');
});
