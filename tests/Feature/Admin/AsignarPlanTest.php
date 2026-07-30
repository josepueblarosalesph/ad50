<?php

use App\Livewire\Admin\Empresas;
use App\Models\Empresa;
use App\Models\Plan;
use App\Models\User;
use Livewire\Livewire;

/** @return array{0: User, 1: Empresa, 2: Plan} */
function adminConEmpresaSinPlan(): array
{
    $admin = User::factory()->create(['role' => 'admin']);
    $empresa = Empresa::query()->create([
        'user_id' => User::factory()->create(['role' => 'empresa'])->id,
        'razon_social' => 'Empresa Sin Plan SpA',
        'estado_activacion' => 'activa',
    ]);
    $plan = Plan::query()->create([
        'codigo' => 'manual_'.str()->random(6),
        'nombre' => 'Profesional',
        'audiencia' => 'empresa',
        'precio_clp' => 500000,
        'precio_uf' => 30,
        'periodo' => 'anual',
        'desbloqueos' => 50,
    ]);

    return [$admin, $empresa, $plan];
}

test('el admin asigna un plan a una empresa a mano', function () {
    [$admin, $empresa, $plan] = adminConEmpresaSinPlan();

    expect($empresa->planVigente())->toBeFalse();

    Livewire::actingAs($admin)
        ->test(Empresas::class)
        ->call('abrirAsignacion', $empresa->id)
        ->assertSet('asignandoId', $empresa->id)
        ->assertSet('asignandoRazonSocial', 'Empresa Sin Plan SpA')
        // Al elegir el plan se propone la vigencia según su período (anual).
        ->set('planSeleccionado', $plan->id)
        ->assertSet('vigenciaHasta', now()->addYear()->format('Y-m-d'))
        ->call('asignarPlan')
        ->assertHasNoErrors();

    $empresa->refresh();

    expect($empresa->plan_id)->toBe($plan->id)
        ->and($empresa->planVigente())->toBeTrue()
        ->and($empresa->plan_hasta->format('Y-m-d'))->toBe(now()->addYear()->format('Y-m-d'));
});

test('la vigencia se puede ajustar a mano', function () {
    [$admin, $empresa, $plan] = adminConEmpresaSinPlan();
    $hasta = now()->addMonths(3)->format('Y-m-d');

    Livewire::actingAs($admin)
        ->test(Empresas::class)
        ->call('abrirAsignacion', $empresa->id)
        ->set('planSeleccionado', $plan->id)
        ->set('vigenciaHasta', $hasta)
        ->call('asignarPlan')
        ->assertHasNoErrors();

    expect($empresa->fresh()->plan_hasta->format('Y-m-d'))->toBe($hasta);
});

test('cambiar el plan de una empresa vigente extiende desde su vigencia actual', function () {
    [$admin, $empresa, $plan] = adminConEmpresaSinPlan();
    $empresa->update(['plan_id' => $plan->id, 'plan_hasta' => now()->addMonths(6)]);

    Livewire::actingAs($admin)
        ->test(Empresas::class)
        ->call('abrirAsignacion', $empresa->id)
        // Se parte de la vigencia guardada...
        ->assertSet('vigenciaHasta', now()->addMonths(6)->format('Y-m-d'))
        ->set('planSeleccionado', $plan->id)
        // ...y el período del plan se suma a esa fecha, no a hoy.
        ->assertSet('vigenciaHasta', now()->addMonths(6)->addYear()->format('Y-m-d'));
});

test('no se acepta un plan de postulantes ni una vigencia pasada', function () {
    [$admin, $empresa] = adminConEmpresaSinPlan();
    $planPostulante = Plan::query()->create([
        'codigo' => 'post_'.str()->random(6),
        'nombre' => 'Plan postulante',
        'audiencia' => 'postulante',
        'precio_clp' => 0,
        'periodo' => 'mensual',
    ]);

    $componente = Livewire::actingAs($admin)->test(Empresas::class)->call('abrirAsignacion', $empresa->id);

    $componente->set('planSeleccionado', $planPostulante->id)
        ->set('vigenciaHasta', now()->addYear()->format('Y-m-d'))
        ->call('asignarPlan')
        ->assertHasErrors('planSeleccionado');

    $componente->set('vigenciaHasta', now()->subDay()->format('Y-m-d'))
        ->call('asignarPlan')
        ->assertHasErrors('vigenciaHasta');

    expect($empresa->fresh()->plan_id)->toBeNull();
});

test('el admin puede dejar a una empresa sin plan', function () {
    [$admin, $empresa, $plan] = adminConEmpresaSinPlan();
    $empresa->update(['plan_id' => $plan->id, 'plan_hasta' => now()->addYear()]);

    Livewire::actingAs($admin)
        ->test(Empresas::class)
        ->call('quitarPlan', $empresa->id)
        ->assertHasNoErrors();

    $empresa->refresh();

    expect($empresa->plan_id)->toBeNull()
        ->and($empresa->planVigente())->toBeFalse();
});

test('un usuario que no es admin no puede asignar planes', function () {
    [, $empresa] = adminConEmpresaSinPlan();
    $empresaUser = User::factory()->create(['role' => 'empresa']);

    Livewire::actingAs($empresaUser)
        ->test(Empresas::class)
        ->assertForbidden();

    expect($empresa->fresh()->plan_id)->toBeNull();
});
