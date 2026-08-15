<?php

use App\Livewire\Empresa\Planes;
use App\Models\Cupon;
use App\Models\Empresa;
use App\Models\Pago;
use App\Models\Plan;
use App\Models\User;
use App\Services\ValorUf;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/**
 * El cupón visto desde el checkout: cuánto rebaja, cuándo se gasta y cuándo se rechaza.
 */
beforeEach(function () {
    config()->set('services.flow.api_key', 'APIKEY');
    config()->set('services.flow.secret_key', 'SECRET');
    config()->set('services.flow.base_url', 'https://sandbox.flow.cl/api');

    // UF fija: el precio del plan de prueba queda en 1.392.300 CLP (30 UF + IVA).
    $this->mock(ValorUf::class, fn ($m) => $m->shouldReceive('actual')->andReturn(39000.0));
});

/** @return array{0: User, 1: Empresa, 2: Plan} */
function empresaParaCupones(): array
{
    $user = User::factory()->create(['role' => 'empresa', 'email' => 'cupones@empresa.cl']);
    $empresa = Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => 'Empresa Cupón SpA',
        'estado_activacion' => 'activa',
        'datos_enviados_at' => now(),
        'contacto_principal_email' => 'cupones@empresa.cl',
    ]);
    $plan = Plan::query()->create([
        'codigo' => 'empresa_cupon_'.str()->random(6),
        'nombre' => 'AD+50 · Pro',
        'audiencia' => 'empresa',
        'precio_clp' => 0,
        'precio_uf' => '30.00',
        'periodo' => 'mensual',
        'desbloqueos' => 10,
    ]);

    return [$user, $empresa, $plan];
}

function fakeFlowOk(): void
{
    Http::fake([
        '*/payment/create' => Http::response([
            'url' => 'https://sandbox.flow.cl/app/web/pay.php',
            'token' => 'TOK-CUPON',
            'flowOrder' => 900,
        ]),
    ]);
}

const PRECIO_LISTA = 1392300; // (int) round(30 × 39000 × 1.19)

test('un cupón de porcentaje rebaja lo que se le cobra a Flow', function () {
    fakeFlowOk();
    [$user, $empresa, $plan] = empresaParaCupones();

    Cupon::query()->create(['codigo' => 'VERANO25', 'tipo' => 'porcentaje', 'valor' => 25]);

    Livewire::actingAs($user)
        ->test(Planes::class)
        ->set('codigoCupon', 'verano25') // se teclea en minúsculas
        ->call('aplicarCupon')
        ->assertHasNoErrors()
        ->call('contratar', $plan->id)
        ->assertRedirect('https://sandbox.flow.cl/app/web/pay.php?token=TOK-CUPON');

    $pago = Pago::query()->firstOrFail();
    $esperado = (int) round(PRECIO_LISTA * 0.75);

    expect($pago->amount)->toBe($esperado)
        ->and($pago->descuento)->toBe(PRECIO_LISTA - $esperado)
        ->and($pago->montoBruto())->toBe(PRECIO_LISTA)
        ->and($pago->cupon_id)->not->toBeNull();

    // Lo que se cobra de verdad es el monto rebajado.
    Http::assertSent(fn ($request) => (int) $request['amount'] === $esperado);
});

test('el cupón se gasta al confirmarse el cobro, no al iniciarlo', function () {
    fakeFlowOk();
    [$user, $empresa, $plan] = empresaParaCupones();

    $cupon = Cupon::query()->create(['codigo' => 'VERANO25', 'tipo' => 'porcentaje', 'valor' => 25, 'max_usos' => 5]);

    Livewire::actingAs($user)
        ->test(Planes::class)
        ->set('codigoCupon', 'VERANO25')
        ->call('aplicarCupon')
        ->call('contratar', $plan->id);

    // Todavía está en la pasarela: abandonar aquí no puede gastar cupo.
    expect($cupon->fresh()->usos)->toBe(0);

    $pago = Pago::query()->firstOrFail();

    Http::fake(['*/payment/getStatus*' => Http::response([
        'commerceOrder' => $pago->commerce_order,
        'status' => 2,
        'flowOrder' => 901,
    ])]);

    $this->post(route('pagos.flow.confirmar'), ['token' => 'TOK-CUPON'])->assertOk();

    expect($cupon->fresh()->usos)->toBe(1)
        ->and($pago->fresh()->estado)->toBe('pagado');

    // El webhook se reintenta: el cupón no se gasta dos veces.
    $this->post(route('pagos.flow.confirmar'), ['token' => 'TOK-CUPON'])->assertOk();

    expect($cupon->fresh()->usos)->toBe(1);
});

test('un cupón del 100% activa el plan sin pasar por la pasarela', function () {
    Http::fake(); // cualquier llamada saliente haría fallar la aserción de más abajo
    [$user, $empresa, $plan] = empresaParaCupones();

    $cupon = Cupon::query()->create(['codigo' => 'PILOTO', 'tipo' => 'porcentaje', 'valor' => 100, 'max_usos' => 1]);

    Livewire::actingAs($user)
        ->test(Planes::class)
        ->set('codigoCupon', 'PILOTO')
        ->call('aplicarCupon')
        ->assertHasNoErrors()
        ->call('contratar', $plan->id)
        ->assertRedirect(route('empresa.panel'));

    $pago = Pago::query()->firstOrFail();

    expect($pago->estado)->toBe('pagado')
        ->and($pago->amount)->toBe(0)
        ->and($pago->descuento)->toBe(PRECIO_LISTA)
        ->and($pago->esCortesia())->toBeTrue()
        ->and($pago->pagado_at)->not->toBeNull()
        ->and($pago->commerce_order)->toBe('AD50-'.$pago->id)
        // El plan queda activo de inmediato.
        ->and($empresa->fresh()->planVigente())->toBeTrue()
        ->and($empresa->fresh()->plan_id)->toBe($plan->id)
        ->and($cupon->fresh()->usos)->toBe(1);

    Http::assertNothingSent();
});

test('un cupón de monto fijo nunca deja el cobro en negativo', function () {
    Http::fake();
    [$user, $empresa, $plan] = empresaParaCupones();

    // Más pesos de descuento que el precio del plan.
    Cupon::query()->create(['codigo' => 'GRANDE', 'tipo' => 'monto', 'valor' => 5_000_000]);

    Livewire::actingAs($user)
        ->test(Planes::class)
        ->set('codigoCupon', 'GRANDE')
        ->call('aplicarCupon')
        ->call('contratar', $plan->id)
        ->assertRedirect(route('empresa.panel'));

    expect(Pago::query()->firstOrFail()->amount)->toBe(0);
});

test('la cortesía cuenta para el tope anual de contrataciones', function () {
    Http::fake();
    [$user, $empresa, $plan] = empresaParaCupones();
    $plan->update(['max_contrataciones_anuales' => 1, 'pago_unico' => true]);

    Cupon::query()->create(['codigo' => 'PILOTO', 'tipo' => 'porcentaje', 'valor' => 100, 'uso_unico_por_empresa' => false]);

    Livewire::actingAs($user)
        ->test(Planes::class)
        ->set('codigoCupon', 'PILOTO')
        ->call('aplicarCupon')
        ->call('contratar', $plan->id);

    // Un cupón del 100% no puede ser la puerta para saltarse el tope del plan.
    expect($empresa->fresh()->puedeContratar($plan->fresh()))->toBeFalse();
});

test('un cupón vencido, desactivado o agotado no se aplica', function () {
    [$user] = empresaParaCupones();

    Cupon::query()->create(['codigo' => 'VENCIDO', 'tipo' => 'porcentaje', 'valor' => 10, 'vigente_hasta' => now()->subDay()]);
    Cupon::query()->create(['codigo' => 'APAGADO', 'tipo' => 'porcentaje', 'valor' => 10, 'activo' => false]);
    Cupon::query()->create(['codigo' => 'AGOTADO', 'tipo' => 'porcentaje', 'valor' => 10, 'max_usos' => 2, 'usos' => 2]);
    Cupon::query()->create(['codigo' => 'FUTURO', 'tipo' => 'porcentaje', 'valor' => 10, 'vigente_desde' => now()->addWeek()]);

    $componente = Livewire::actingAs($user)->test(Planes::class);

    foreach (['VENCIDO', 'APAGADO', 'AGOTADO', 'FUTURO', 'NOEXISTE'] as $codigo) {
        $componente->set('codigoCupon', $codigo)
            ->call('aplicarCupon')
            ->assertHasErrors('codigoCupon')
            ->assertSet('cuponAplicadoId', null);
    }
});

test('un cupón restringido a otro plan se rechaza al contratar', function () {
    Http::fake();
    [$user, $empresa, $plan] = empresaParaCupones();

    $otro = Plan::query()->create([
        'codigo' => 'empresa_otro_'.str()->random(6),
        'nombre' => 'AD+50 · Básico',
        'audiencia' => 'empresa',
        'precio_clp' => 0,
        'precio_uf' => '5.00',
        'periodo' => 'anual',
        'desbloqueos' => 3,
    ]);

    Cupon::query()->create(['codigo' => 'SOLOBASICO', 'tipo' => 'porcentaje', 'valor' => 50, 'plan_id' => $otro->id]);

    Livewire::actingAs($user)
        ->test(Planes::class)
        ->set('codigoCupon', 'SOLOBASICO')
        // Se acepta al aplicarlo: todavía no se sabe qué plan va a elegir.
        ->call('aplicarCupon')
        ->assertHasNoErrors()
        // Pero no sirve para este plan, y no se cobra nada a medias.
        ->call('contratar', $plan->id)
        ->assertHasErrors('codigoCupon')
        ->assertNoRedirect();

    expect(Pago::query()->count())->toBe(0);
});

test('un cupón de un solo uso por empresa no se repite', function () {
    [$user, $empresa, $plan] = empresaParaCupones();

    $cupon = Cupon::query()->create(['codigo' => 'UNAVEZ', 'tipo' => 'porcentaje', 'valor' => 10, 'uso_unico_por_empresa' => true]);

    // Contratación anterior ya cobrada con ese cupón.
    Pago::query()->create([
        'empresa_id' => $empresa->id,
        'plan_id' => $plan->id,
        'cupon_id' => $cupon->id,
        'commerce_order' => 'AD50-PREVIO',
        'amount' => 1000,
        'descuento' => 500,
        'estado' => 'pagado',
        'pagado_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(Planes::class)
        ->set('codigoCupon', 'UNAVEZ')
        ->call('aplicarCupon')
        ->assertHasErrors('codigoCupon');
});

test('el tope de usos se respeta aunque dos cobros se confirmen a la vez', function () {
    $cupon = Cupon::query()->create(['codigo' => 'ULTIMO', 'tipo' => 'porcentaje', 'valor' => 10, 'max_usos' => 1]);

    expect($cupon->registrarUso())->toBeTrue()
        ->and($cupon->registrarUso())->toBeFalse()
        ->and($cupon->fresh()->usos)->toBe(1);
});

test('sin tope de usos el cupón se puede seguir usando', function () {
    $cupon = Cupon::query()->create(['codigo' => 'ABIERTO', 'tipo' => 'porcentaje', 'valor' => 10]);

    expect($cupon->registrarUso())->toBeTrue()
        ->and($cupon->registrarUso())->toBeTrue()
        ->and($cupon->fresh()->usos)->toBe(2);
});

test('quitar el cupón devuelve los precios de lista', function () {
    [$user, $empresa, $plan] = empresaParaCupones();

    Cupon::query()->create(['codigo' => 'VERANO25', 'tipo' => 'porcentaje', 'valor' => 25]);

    Livewire::actingAs($user)
        ->test(Planes::class)
        ->set('codigoCupon', 'VERANO25')
        ->call('aplicarCupon')
        ->assertSet('cuponAplicadoId', fn ($id) => $id !== null)
        ->call('quitarCupon')
        ->assertSet('cuponAplicadoId', null)
        ->assertSet('codigoCupon', '');
});

test('sin cupón el precio y el flujo de pago no cambian', function () {
    fakeFlowOk();
    [$user, $empresa, $plan] = empresaParaCupones();

    Livewire::actingAs($user)
        ->test(Planes::class)
        ->call('contratar', $plan->id)
        ->assertRedirect('https://sandbox.flow.cl/app/web/pay.php?token=TOK-CUPON');

    $pago = Pago::query()->firstOrFail();

    expect($pago->amount)->toBe(PRECIO_LISTA)
        ->and($pago->descuento)->toBe(0)
        ->and($pago->cupon_id)->toBeNull();
});
