<?php

use App\Livewire\Admin\Cupones as AdminCupones;
use App\Models\Cupon;
use App\Models\Empresa;
use App\Models\Pago;
use App\Models\Plan;
use App\Models\Postulante;
use App\Models\User;
use Livewire\Livewire;

/**
 * La pantalla de administración de cupones: quién entra y qué se puede guardar.
 */
function planEmpresaParaCupones(string $nombre = 'AD+50 · Pro'): Plan
{
    return Plan::query()->create([
        'codigo' => 'empresa_admin_'.str()->random(6),
        'nombre' => $nombre,
        'audiencia' => 'empresa',
        'precio_clp' => 0,
        'precio_uf' => '30.00',
        'periodo' => 'mensual',
        'desbloqueos' => 10,
    ]);
}

test('cualquier administrador entra a la pantalla de cupones', function () {
    foreach (['admin', 'superadmin'] as $rol) {
        $admin = User::factory()->create(['role' => $rol]);

        $this->actingAs($admin)->get(route('admin.cupones'))->assertOk();
    }
});

test('quien no es administrador no llega a los cupones', function () {
    $postulante = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create(['user_id' => $postulante->id, 'onboarding_completado' => true]);

    $this->actingAs($postulante)->get(route('admin.cupones'))->assertForbidden();

    $empresaUser = User::factory()->create(['role' => 'empresa']);
    Empresa::query()->create([
        'user_id' => $empresaUser->id,
        'razon_social' => 'Ajena SpA',
        'estado_activacion' => 'activa',
        'datos_enviados_at' => now(),
    ]);

    $this->actingAs($empresaUser)->get(route('admin.cupones'))->assertForbidden();
});

test('el admin crea un cupón y el código queda normalizado', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(AdminCupones::class)
        ->call('abrirNuevo')
        ->set('codigo', ' verano 25 ')
        ->set('descripcion', 'Campaña de lanzamiento')
        ->set('tipo', 'porcentaje')
        ->set('valor', '25')
        ->set('maxUsos', '50')
        ->set('vigenteHasta', now()->addMonth()->toDateString())
        ->call('guardar')
        ->assertHasNoErrors();

    $cupon = Cupon::query()->firstOrFail();

    expect($cupon->codigo)->toBe('VERANO25')
        ->and($cupon->valor)->toBe(25)
        ->and($cupon->max_usos)->toBe(50)
        ->and($cupon->usos)->toBe(0)
        ->and($cupon->activo)->toBeTrue()
        ->and($cupon->plan_id)->toBeNull()
        ->and($cupon->creado_por)->toBe($admin->id);
});

test('un cupón se puede restringir a un plan concreto', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $plan = planEmpresaParaCupones();

    Livewire::actingAs($admin)
        ->test(AdminCupones::class)
        ->set('codigo', 'SOLOPRO')
        ->set('tipo', 'monto')
        ->set('valor', '15000')
        ->set('planId', (string) $plan->id)
        ->call('guardar')
        ->assertHasNoErrors();

    expect(Cupon::query()->firstOrFail())
        ->plan_id->toBe($plan->id)
        ->tipo->toBe('monto')
        ->valor->toBe(15000);
});

test('el código no se puede repetir, aunque cambie el formato', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Cupon::query()->create(['codigo' => 'VERANO25', 'tipo' => 'porcentaje', 'valor' => 10]);

    Livewire::actingAs($admin)
        ->test(AdminCupones::class)
        ->set('codigo', 'verano25')
        ->set('tipo', 'porcentaje')
        ->set('valor', '30')
        ->call('guardar')
        ->assertHasErrors('codigo');

    expect(Cupon::query()->count())->toBe(1);
});

test('un porcentaje mayor a 100 se rechaza', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(AdminCupones::class)
        ->set('codigo', 'IMPOSIBLE')
        ->set('tipo', 'porcentaje')
        ->set('valor', '150')
        ->call('guardar')
        ->assertHasErrors('valor');

    expect(Cupon::query()->count())->toBe(0);
});

test('el 100% sí se permite: es el cupón de cortesía', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(AdminCupones::class)
        ->set('codigo', 'PILOTO')
        ->set('tipo', 'porcentaje')
        ->set('valor', '100')
        ->set('maxUsos', '3')
        ->call('guardar')
        ->assertHasNoErrors();

    expect(Cupon::query()->firstOrFail()->valor)->toBe(100);
});

test('la fecha de término no puede ser anterior a la de inicio', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(AdminCupones::class)
        ->set('codigo', 'ALREVES')
        ->set('tipo', 'porcentaje')
        ->set('valor', '10')
        ->set('vigenteDesde', now()->addMonth()->toDateString())
        ->set('vigenteHasta', now()->toDateString())
        ->call('guardar')
        ->assertHasErrors('vigenteHasta');
});

test('el admin edita un cupón existente', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $cupon = Cupon::query()->create(['codigo' => 'VERANO25', 'tipo' => 'porcentaje', 'valor' => 25]);

    Livewire::actingAs($admin)
        ->test(AdminCupones::class)
        ->call('abrirEdicion', $cupon->id)
        ->assertSet('codigo', 'VERANO25')
        ->assertSet('valor', '25')
        ->set('valor', '40')
        ->call('guardar')
        ->assertHasNoErrors();

    expect($cupon->fresh()->valor)->toBe(40);
});

test('no se puede bajar el tope por debajo de los usos ya cobrados', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $cupon = Cupon::query()->create(['codigo' => 'USADO', 'tipo' => 'porcentaje', 'valor' => 25, 'max_usos' => 10, 'usos' => 4]);

    Livewire::actingAs($admin)
        ->test(AdminCupones::class)
        ->call('abrirEdicion', $cupon->id)
        ->set('maxUsos', '2')
        ->call('guardar')
        ->assertHasErrors('maxUsos');

    expect($cupon->fresh()->max_usos)->toBe(10);
});

test('desactivar un cupón corta su uso sin borrarlo', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $cupon = Cupon::query()->create(['codigo' => 'VERANO25', 'tipo' => 'porcentaje', 'valor' => 25]);

    Livewire::actingAs($admin)
        ->test(AdminCupones::class)
        ->call('alternarActivo', $cupon->id);

    expect($cupon->fresh()->activo)->toBeFalse()
        ->and($cupon->fresh()->estaVigente())->toBeFalse();

    Livewire::actingAs($admin)
        ->test(AdminCupones::class)
        ->call('alternarActivo', $cupon->id);

    expect($cupon->fresh()->activo)->toBeTrue();
});

test('un cupón sin usar se elimina; uno ya usado solo se desactiva', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $plan = planEmpresaParaCupones();

    $limpio = Cupon::query()->create(['codigo' => 'LIMPIO', 'tipo' => 'porcentaje', 'valor' => 10]);
    $usado = Cupon::query()->create(['codigo' => 'USADO', 'tipo' => 'porcentaje', 'valor' => 10, 'usos' => 1]);

    $empresaUser = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $empresaUser->id, 'razon_social' => 'Con Historial SpA']);
    Pago::query()->create([
        'empresa_id' => $empresa->id,
        'plan_id' => $plan->id,
        'cupon_id' => $usado->id,
        'commerce_order' => 'AD50-HIST',
        'amount' => 1000,
        'descuento' => 500,
        'estado' => 'pagado',
        'pagado_at' => now(),
    ]);

    $componente = Livewire::actingAs($admin)->test(AdminCupones::class);
    $componente->call('eliminar', $limpio->id);
    $componente->call('eliminar', $usado->id);

    expect(Cupon::query()->find($limpio->id))->toBeNull()
        // El historial del pago conserva de dónde salió el descuento.
        ->and(Cupon::query()->find($usado->id))->not->toBeNull()
        ->and($usado->fresh()->activo)->toBeFalse()
        ->and(Pago::query()->firstOrFail()->cupon_id)->toBe($usado->id);
});

test('el filtro por estado separa vigentes, agotados, vencidos y desactivados', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Cupon::query()->create(['codigo' => 'VIGENTE', 'tipo' => 'porcentaje', 'valor' => 10]);
    Cupon::query()->create(['codigo' => 'AGOTADO', 'tipo' => 'porcentaje', 'valor' => 10, 'max_usos' => 2, 'usos' => 2]);
    Cupon::query()->create(['codigo' => 'VENCIDO', 'tipo' => 'porcentaje', 'valor' => 10, 'vigente_hasta' => now()->subDay()]);
    Cupon::query()->create(['codigo' => 'APAGADO', 'tipo' => 'porcentaje', 'valor' => 10, 'activo' => false]);

    $componente = Livewire::actingAs($admin)->test(AdminCupones::class);

    $componente->set('estado', 'vigentes')
        ->assertSee('VIGENTE')->assertDontSee('AGOTADO')->assertDontSee('VENCIDO')->assertDontSee('APAGADO');

    $componente->set('estado', 'agotados')
        ->assertSee('AGOTADO')->assertDontSee('VIGENTE');

    $componente->set('estado', 'vencidos')
        ->assertSee('VENCIDO')->assertDontSee('VIGENTE');

    $componente->set('estado', 'inactivos')
        ->assertSee('APAGADO')->assertDontSee('VIGENTE');
});

test('el buscador encuentra por código escrito en minúsculas', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Cupon::query()->create(['codigo' => 'VERANO25', 'tipo' => 'porcentaje', 'valor' => 10]);
    Cupon::query()->create(['codigo' => 'INVIERNO', 'tipo' => 'porcentaje', 'valor' => 10]);

    Livewire::actingAs($admin)
        ->test(AdminCupones::class)
        ->set('buscar', 'verano')
        ->assertSee('VERANO25')
        ->assertDontSee('INVIERNO');
});

test('el código generado está libre y respeta el formato permitido', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $componente = Livewire::actingAs($admin)
        ->test(AdminCupones::class)
        ->call('generarCodigo');

    $codigo = $componente->get('codigo');

    expect($codigo)->toMatch('/^[A-Z0-9\-_]+$/');

    $componente->set('tipo', 'porcentaje')->set('valor', '10')->call('guardar')->assertHasNoErrors();

    expect(Cupon::query()->firstOrFail()->codigo)->toBe($codigo);
});
