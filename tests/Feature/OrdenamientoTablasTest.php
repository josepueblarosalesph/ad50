<?php

use App\Livewire\Admin\Panel as AdminPanel;
use App\Livewire\Empresa\Equipo;
use App\Livewire\Empresa\Panel as EmpresaPanel;
use App\Livewire\Empresa\Publicaciones;
use App\Models\Empresa;
use App\Models\Plan;
use App\Models\Publicacion;
use App\Models\User;
use Livewire\Livewire;

/** @return array{0: User, 1: Empresa} */
function empresaParaOrdenar(): array
{
    $user = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => 'Empresa '.fake()->unique()->numerify('####'),
        'estado_activacion' => 'activa',
    ]);
    $user->update(['empresa_id' => $empresa->id]);
    hacerEmpresaOperativa($empresa);

    return [$user->fresh(), $empresa->fresh()];
}

test('las publicaciones se ordenan por cualquiera de sus columnas', function () {
    [$user, $empresa] = empresaParaOrdenar();

    foreach ([['Zeta', 'Arica'], ['Alfa', 'Punta Arenas'], ['Media', 'Concepción']] as [$cargo, $comuna]) {
        Publicacion::factory()->create([
            'empresa_id' => $empresa->id,
            'nombre_empresa' => $empresa->razon_social,
            'cargo' => $cargo,
            'comuna' => $comuna,
        ]);
    }

    $cargos = fn ($c): array => $c->viewData('publicaciones')->pluck('cargo')->all();
    $componente = Livewire::actingAs($user)->test(Publicaciones::class);

    $componente->call('ordenarPor', 'cargo');
    expect($cargos($componente))->toBe(['Alfa', 'Media', 'Zeta']);

    $componente->call('ordenarPor', 'cargo');
    expect($cargos($componente))->toBe(['Zeta', 'Media', 'Alfa']);

    // Otra columna reinicia el sentido ascendente.
    $componente->call('ordenarPor', 'comuna');
    expect($componente->get('direccion'))->toBe('asc')
        ->and($cargos($componente))->toBe(['Zeta', 'Media', 'Alfa']); // Arica, Concepción, Punta Arenas
});

test('el equipo se ordena por nombre y por email', function () {
    [$principal, $empresa] = empresaParaOrdenar();

    foreach ([['Zulema', 'zulema@empresa.cl'], ['Ana', 'ana@empresa.cl']] as [$nombre, $email]) {
        User::factory()->create(['role' => 'empresa', 'empresa_id' => $empresa->id, 'name' => $nombre, 'email' => $email]);
    }

    $nombres = fn ($c): array => $c->viewData('adicionales')->pluck('name')->all();
    $componente = Livewire::actingAs($principal)->test(Equipo::class);

    expect($nombres($componente))->toBe(['Ana', 'Zulema']);

    $componente->call('ordenarPor', 'name');
    expect($nombres($componente))->toBe(['Zulema', 'Ana']);

    $componente->call('ordenarPor', 'email');
    expect($nombres($componente))->toBe(['Ana', 'Zulema']);
});

test('el panel de empresa ordena las búsquedas que ya trajo, sin cambiar cuáles son', function () {
    [$user, $empresa] = empresaParaOrdenar();

    // Seis búsquedas: el panel muestra las 5 más recientes.
    foreach (['Uno', 'Dos', 'Tres', 'Cuatro', 'Cinco', 'Seis'] as $i => $titulo) {
        $empresa->busquedas()->create([
            'titulo' => $titulo,
            'criterios' => [],
            'created_at' => now()->subDays(10 - $i),
        ]);
    }

    $componente = Livewire::actingAs($user)->test(EmpresaPanel::class);
    $mostradas = fn ($c): array => $c->viewData('busquedas')->pluck('titulo')->all();

    // "Uno" es la más antigua y queda fuera de las 5 recientes.
    expect($mostradas($componente))->toHaveCount(5)
        ->and($mostradas($componente))->not->toContain('Uno');

    $componente->call('ordenarPor', 'titulo');

    // Al ordenar siguen siendo las mismas 5, solo cambia el orden.
    expect($mostradas($componente))->toBe(['Cinco', 'Cuatro', 'Dos', 'Seis', 'Tres'])
        ->and($mostradas($componente))->not->toContain('Uno');
});

test('el panel de admin ordena las empresas recientes', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $plan = Plan::query()->create([
        'codigo' => 'orden_'.str()->random(6), 'nombre' => 'Plan A',
        'audiencia' => 'empresa', 'precio_clp' => 1000, 'periodo' => 'mensual',
    ]);

    foreach (['Zeta SpA', 'Alfa Ltda'] as $razon) {
        Empresa::query()->create([
            'user_id' => User::factory()->create(['role' => 'empresa'])->id,
            'razon_social' => $razon,
            'estado_activacion' => 'activa',
            'plan_id' => $plan->id,
        ]);
    }

    $razones = fn ($c): array => $c->viewData('empresas')->pluck('razon_social')->all();
    $componente = Livewire::actingAs($admin)->test(AdminPanel::class);

    $componente->call('ordenarPor', 'razon_social');
    expect($razones($componente))->toBe(['Alfa Ltda', 'Zeta SpA']);

    $componente->call('ordenarPor', 'razon_social');
    expect($razones($componente))->toBe(['Zeta SpA', 'Alfa Ltda']);
});

test('una columna no declarada se rechaza en cualquier tabla', function () {
    [$user] = empresaParaOrdenar();

    Livewire::actingAs($user)
        ->test(Publicaciones::class)
        ->call('ordenarPor', 'descripcion')
        ->assertStatus(404);
});
