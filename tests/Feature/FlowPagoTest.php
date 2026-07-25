<?php

use App\Livewire\Empresa\Planes;
use App\Models\Empresa;
use App\Models\Pago;
use App\Models\Plan;
use App\Models\User;
use App\Services\FlowService;
use App\Services\ValorUf;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('services.flow.api_key', 'APIKEY');
    config()->set('services.flow.secret_key', 'SECRET');
    config()->set('services.flow.base_url', 'https://sandbox.flow.cl/api');
});

/** @return array{0: User, 1: Empresa, 2: Plan} */
function empresaConPlanEmpresa(): array
{
    $user = User::factory()->create(['role' => 'empresa', 'email' => 'pagos@empresa.cl']);
    $empresa = Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => 'Empresa Pago SpA',
        'estado_activacion' => 'activa',
        'datos_enviados_at' => now(),
        'contacto_principal_email' => 'pagos@empresa.cl',
    ]);
    $plan = Plan::query()->create([
        'codigo' => 'empresa_test_'.str()->random(6),
        'nombre' => 'AD+50 · Pro',
        'audiencia' => 'empresa',
        'precio_clp' => 0,
        'precio_uf' => '30.00',
        'periodo' => 'mensual',
        'desbloqueos' => 10,
    ]);

    return [$user, $empresa, $plan];
}

test('la firma es HMAC-SHA256 de los parametros ordenados por nombre', function () {
    $firma = (new FlowService)->firmar(['b' => '2', 'a' => '1', 'c' => '3']);

    expect($firma)->toBe(hash_hmac('sha256', 'a1b2c3', 'SECRET'));
});

test('precioClp convierte UF a CLP con IVA de 19%', function () {
    $plan = new Plan(['precio_uf' => '30.00']);

    expect($plan->precioClp(39000.0))->toBe((int) round(30 * 39000 * 1.19)); // 1.392.300
});

test('contratar cobra el CLP calculado desde la UF y redirige a Flow', function () {
    $this->mock(ValorUf::class, fn ($m) => $m->shouldReceive('actual')->andReturn(39000.0));

    Http::fake([
        '*/payment/create' => Http::response([
            'url' => 'https://sandbox.flow.cl/app/web/pay.php',
            'token' => 'TOK123',
            'flowOrder' => 555,
        ]),
    ]);

    [$user, $empresa, $plan] = empresaConPlanEmpresa();

    Livewire::actingAs($user)
        ->test(Planes::class)
        ->call('contratar', $plan->id)
        ->assertRedirect('https://sandbox.flow.cl/app/web/pay.php?token=TOK123');

    $pago = Pago::query()->first();

    expect($pago)->not->toBeNull()
        ->and($pago->estado)->toBe('pendiente')
        ->and($pago->amount)->toBe((int) round(30 * 39000 * 1.19))
        ->and($pago->flow_token)->toBe('TOK123')
        ->and($pago->flow_order)->toBe(555)
        ->and($pago->commerce_order)->toBe('AD50-'.$pago->id);

    Http::assertSent(fn ($request) => $request->url() === 'https://sandbox.flow.cl/api/payment/create'
        && ! empty($request['s'])
        && (int) $request['amount'] === (int) round(30 * 39000 * 1.19));
});

test('todos los planes muestran el mismo boton naranja', function () {
    [$user] = empresaConPlanEmpresa();

    Plan::query()->create([
        'codigo' => 'empresa_otro_'.str()->random(6),
        'nombre' => 'AD+50 · Básico',
        'audiencia' => 'empresa',
        'precio_clp' => 0,
        'precio_uf' => '20.00',
        'periodo' => 'mensual',
        'desbloqueos' => 5,
        'destacado' => false,
    ]);

    Livewire::actingAs($user)
        ->test(Planes::class)
        ->assertSeeHtml('class="ad-btn-primary ad-btn-sm ad-btn-block justify-center"')
        ->assertDontSeeHtml('class="ad-btn-ghost ad-btn-sm ad-btn-block justify-center"');
});

test('el webhook de Flow confirma el pago y activa la suscripcion de la empresa', function () {
    [$user, $empresa, $plan] = empresaConPlanEmpresa();
    $pago = Pago::query()->create([
        'empresa_id' => $empresa->id,
        'plan_id' => $plan->id,
        'commerce_order' => 'AD50-999',
        'flow_token' => 'TOK',
        'amount' => 90000,
        'estado' => 'pendiente',
    ]);

    Http::fake([
        '*/payment/getStatus*' => Http::response([
            'commerceOrder' => 'AD50-999',
            'status' => 2,
            'flowOrder' => 777,
        ]),
    ]);

    $this->post(route('pagos.flow.confirmar'), ['token' => 'TOK'])->assertOk();

    expect($pago->fresh()->estado)->toBe('pagado')
        ->and($pago->fresh()->pagado_at)->not->toBeNull()
        ->and($empresa->fresh()->plan_id)->toBe($plan->id)
        ->and($empresa->fresh()->planVigente())->toBeTrue();
});

test('el retorno de un pago confirmado envia a completar los datos pendientes', function () {
    [$user, $empresa, $plan] = empresaConPlanEmpresa();
    $empresa->update(['datos_enviados_at' => null]);

    Pago::query()->create([
        'empresa_id' => $empresa->id,
        'plan_id' => $plan->id,
        'commerce_order' => 'AD50-998',
        'flow_token' => 'TOK-RETORNO',
        'amount' => 90000,
        'estado' => 'pendiente',
    ]);

    Http::fake([
        '*/payment/getStatus*' => Http::response([
            'commerceOrder' => 'AD50-998',
            'status' => 2,
            'flowOrder' => 776,
        ]),
    ]);

    $this->get(route('pagos.flow.retorno', ['token' => 'TOK-RETORNO']))
        ->assertRedirect(route('empresa.activacion'));
});

test('el webhook es idempotente y no vuelve a extender la vigencia', function () {
    [$user, $empresa, $plan] = empresaConPlanEmpresa();
    $pago = Pago::query()->create([
        'empresa_id' => $empresa->id, 'plan_id' => $plan->id,
        'commerce_order' => 'AD50-1000', 'flow_token' => 'TOK2', 'amount' => 90000, 'estado' => 'pendiente',
    ]);

    Http::fake(['*/payment/getStatus*' => Http::response(['commerceOrder' => 'AD50-1000', 'status' => 2, 'flowOrder' => 1])]);

    $this->post(route('pagos.flow.confirmar'), ['token' => 'TOK2'])->assertOk();
    $vigencia = $empresa->fresh()->plan_hasta;

    $this->post(route('pagos.flow.confirmar'), ['token' => 'TOK2'])->assertOk();

    expect($empresa->fresh()->plan_hasta->toDateString())->toBe($vigencia->toDateString());
});

test('un pago rechazado no activa ningun plan', function () {
    [$user, $empresa, $plan] = empresaConPlanEmpresa();
    $pago = Pago::query()->create([
        'empresa_id' => $empresa->id, 'plan_id' => $plan->id,
        'commerce_order' => 'AD50-2000', 'flow_token' => 'TOK3', 'amount' => 90000, 'estado' => 'pendiente',
    ]);

    Http::fake(['*/payment/getStatus*' => Http::response(['commerceOrder' => 'AD50-2000', 'status' => 3])]);

    $this->post(route('pagos.flow.confirmar'), ['token' => 'TOK3'])->assertOk();

    expect($pago->fresh()->estado)->toBe('rechazado')
        ->and($empresa->fresh()->plan_id)->toBeNull();
});
