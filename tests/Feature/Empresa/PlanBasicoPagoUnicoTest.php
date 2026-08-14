<?php

use App\Livewire\Empresa\Planes;
use App\Livewire\Landing;
use App\Models\Empresa;
use App\Models\Pago;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

/** Plan Básico tal como lo deja el seeder: pago único, hasta 3 al año. */
function planBasico(): Plan
{
    return Plan::query()->create([
        'codigo' => 'empresa_basic_'.str()->random(6),
        'nombre' => 'Básico',
        'audiencia' => 'empresa',
        'precio_clp' => 0,
        'precio_uf' => 5,
        'periodo' => 'anual',
        'pago_unico' => true,
        'max_contrataciones_anuales' => 3,
        'desbloqueos' => 10,
        'publicaciones' => 5,
    ]);
}

/** Empresa recién registrada, sin plan ni cupos. */
function empresaSinPlan(): array
{
    $user = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => 'Empresa Planes '.fake()->unique()->numerify('####'),
        'estado_activacion' => 'activa',
        'datos_enviados_at' => now(),
    ]);

    return [$user->fresh(), $empresa->fresh()];
}

/** Simula una compra confirmada del plan en la fecha indicada. */
function contratacionPagada(Empresa $empresa, Plan $plan, ?string $cuando = null): Pago
{
    $pago = Pago::query()->create([
        'empresa_id' => $empresa->id,
        'plan_id' => $plan->id,
        'commerce_order' => 'AD50-'.fake()->unique()->numerify('######'),
        'amount' => 100000,
        'currency' => 'CLP',
        'estado' => 'pagado',
        'pagado_at' => $cuando === null ? now() : Carbon::parse($cuando),
    ]);

    $empresa->update([
        'plan_id' => $plan->id,
        'plan_hasta' => $plan->vigenciaDesde($empresa->plan_hasta),
    ]);
    $empresa->refresh()->acumularCupos($plan);

    return $pago;
}

test('el plan básico se presenta como pago único y no como suscripción', function () {
    $plan = planBasico();

    expect($plan->esPagoUnico())->toBeTrue()
        ->and($plan->tieneTopeAnual())->toBeTrue()
        ->and($plan->periodoLabel())->toBe('pago único')
        // Pago único no significa sin caducidad: da acceso por un año.
        ->and($plan->vigenciaDesde()->toDateString())->toBe(now()->addYear()->toDateString());
});

test('las vistas públicas anuncian el Básico como pago único y no como plan anual', function () {
    // `periodo` sigue siendo 'anual' porque esa es su vigencia: decidir la etiqueta solo
    // por ese campo es justo lo que hacía que el Básico se anunciara como suscripción.
    $basico = planBasico();

    expect($basico->cobroLabel())->toBe('pago único');

    Livewire::test(App\Livewire\Planes::class)
        ->assertSee('pago único')
        ->assertDontSee('plan anual');

    Livewire::test(Landing::class)
        ->assertSee('pago único')
        ->assertDontSee('plan anual');
});

test('un plan que sí es suscripción anual conserva su etiqueta', function () {
    $profesional = Plan::query()->create([
        'codigo' => 'empresa_pro_'.str()->random(6),
        'nombre' => 'Profesional',
        'audiencia' => 'empresa',
        'precio_clp' => 0,
        'precio_uf' => 30,
        'periodo' => 'anual',
        'pago_unico' => false,
        'desbloqueos' => 50,
        'publicaciones' => 30,
    ]);

    expect($profesional->cobroLabel())->toBe('plan anual');

    Livewire::test(App\Livewire\Planes::class)->assertSee('plan anual');
});

test('cada contratación suma sus cupos en vez de reemplazarlos', function () {
    [, $empresa] = empresaSinPlan();
    $plan = planBasico();

    expect($empresa->desbloqueosTotales())->toBe(0);

    contratacionPagada($empresa, $plan);
    expect($empresa->fresh()->desbloqueosTotales())->toBe(10)
        ->and($empresa->fresh()->publicacionesTotales())->toBe(5);

    contratacionPagada($empresa->fresh(), $plan);
    expect($empresa->fresh()->desbloqueosTotales())->toBe(20)
        ->and($empresa->fresh()->publicacionesTotales())->toBe(10);

    contratacionPagada($empresa->fresh(), $plan);
    expect($empresa->fresh()->desbloqueosTotales())->toBe(30)
        ->and($empresa->fresh()->publicacionesTotales())->toBe(15);
});

test('cada contratación extiende la vigencia un año más', function () {
    [, $empresa] = empresaSinPlan();
    $plan = planBasico();

    contratacionPagada($empresa, $plan);
    expect($empresa->fresh()->plan_hasta->toDateString())->toBe(now()->addYear()->toDateString());

    contratacionPagada($empresa->fresh(), $plan);
    expect($empresa->fresh()->plan_hasta->toDateString())->toBe(now()->addYears(2)->toDateString());
});

test('a la cuarta contratación en 12 meses se bloquea', function () {
    [$user, $empresa] = empresaSinPlan();
    $plan = planBasico();

    expect($empresa->contratacionesRestantes($plan))->toBe(3);

    contratacionPagada($empresa, $plan, '-10 months');
    contratacionPagada($empresa->fresh(), $plan, '-6 months');
    expect($empresa->fresh()->contratacionesRestantes($plan))->toBe(1)
        ->and($empresa->fresh()->puedeContratar($plan))->toBeTrue();

    contratacionPagada($empresa->fresh(), $plan, '-1 month');
    expect($empresa->fresh()->contratacionesRestantes($plan))->toBe(0)
        ->and($empresa->fresh()->puedeContratar($plan))->toBeFalse();

    // El intento se corta antes de crear el pago: el id del plan llega del cliente.
    Livewire::actingAs($user->fresh())
        ->test(Planes::class)
        ->call('contratar', $plan->id)
        ->assertHasErrors('pago');

    expect(Pago::query()->where('empresa_id', $empresa->id)->count())->toBe(3);
});

test('la ventana es móvil: al cumplir el año, la compra más antigua deja de contar', function () {
    [, $empresa] = empresaSinPlan();
    $plan = planBasico();

    // Tres compras, pero la primera ya tiene más de 12 meses.
    contratacionPagada($empresa, $plan, '-13 months');
    contratacionPagada($empresa->fresh(), $plan, '-6 months');
    contratacionPagada($empresa->fresh(), $plan, '-1 month');

    expect($empresa->fresh()->contratacionesRestantes($plan))->toBe(1)
        ->and($empresa->fresh()->puedeContratar($plan))->toBeTrue();
});

test('la fecha de liberación es el aniversario de la compra más antigua vigente', function () {
    [, $empresa] = empresaSinPlan();
    $plan = planBasico();

    contratacionPagada($empresa, $plan, '-8 months');
    contratacionPagada($empresa->fresh(), $plan, '-4 months');
    contratacionPagada($empresa->fresh(), $plan, '-1 month');

    expect($empresa->fresh()->proximaLiberacionDeCupo($plan)->toDateString())
        ->toBe(now()->subMonths(8)->addYear()->toDateString());
});

test('un pago sin confirmar no gasta cupo', function () {
    [, $empresa] = empresaSinPlan();
    $plan = planBasico();

    Pago::query()->create([
        'empresa_id' => $empresa->id,
        'plan_id' => $plan->id,
        'commerce_order' => 'AD50-pendiente',
        'amount' => 100000,
        'currency' => 'CLP',
        'estado' => 'pendiente',
    ]);

    expect($empresa->fresh()->contratacionesRestantes($plan))->toBe(3);
});

test('los planes sin tope no limitan las contrataciones', function () {
    [, $empresa] = empresaSinPlan();
    $pro = Plan::query()->create([
        'codigo' => 'empresa_pro_'.str()->random(6),
        'nombre' => 'Pro',
        'audiencia' => 'empresa',
        'precio_clp' => 0,
        'precio_uf' => 30,
        'periodo' => 'anual',
        'desbloqueos' => 50,
        'publicaciones' => 30,
    ]);

    foreach (range(1, 4) as $i) {
        contratacionPagada($empresa->fresh(), $pro);
    }

    expect($pro->esPagoUnico())->toBeFalse()
        ->and($empresa->fresh()->contratacionesRestantes($pro))->toBeNull()
        ->and($empresa->fresh()->puedeContratar($pro))->toBeTrue()
        ->and($empresa->fresh()->desbloqueosTotales())->toBe(200);
});

test('un plan con publicaciones ilimitadas deja a la empresa ilimitada para siempre', function () {
    [, $empresa] = empresaSinPlan();
    $basico = planBasico();
    $ilimitado = Plan::query()->create([
        'codigo' => 'empresa_premium_'.str()->random(6),
        'nombre' => 'Premium',
        'audiencia' => 'empresa',
        'precio_clp' => 0,
        'precio_uf' => 45,
        'periodo' => 'anual',
        'desbloqueos' => 100,
        'publicaciones' => null,
    ]);

    contratacionPagada($empresa, $ilimitado);
    expect($empresa->fresh()->publicacionesTotales())->toBeNull();

    // Comprar después uno acotado no le quita lo ilimitado que ya pagó.
    contratacionPagada($empresa->fresh(), $basico);
    expect($empresa->fresh()->publicacionesTotales())->toBeNull()
        ->and($empresa->fresh()->desbloqueosTotales())->toBe(110);
});

test('la pantalla de planes avisa cuántas contrataciones quedan', function () {
    [$user, $empresa] = empresaSinPlan();
    $plan = planBasico();

    Livewire::actingAs($user)
        ->test(Planes::class)
        ->assertSee('Pago único · acceso por un año')
        ->assertSee('Te quedan')
        ->assertSee('3');

    contratacionPagada($empresa, $plan);
    contratacionPagada($empresa->fresh(), $plan);
    contratacionPagada($empresa->fresh(), $plan);

    Livewire::actingAs($user->fresh())
        ->test(Planes::class)
        ->assertSee('Alcanzaste las 3 contrataciones permitidas en 12 meses.')
        ->assertSee('Sin cupos disponibles');
});
