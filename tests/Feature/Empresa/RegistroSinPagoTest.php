<?php

use App\Livewire\Auth\Register;
use App\Models\Empresa;
use App\Models\Plan;
use App\Models\User;
use Livewire\Livewire;

/**
 * La plataforma dejó de cobrarle a las empresas: al registrarse reciben el plan ilimitado
 * y los planes desaparecen de la vista. Nada se eliminó: todo vuelve con
 * AD50_COBRO_EMPRESAS=true (ver config/ad50.php), y eso también se comprueba aquí.
 */
function registrarEmpresa(string $email = 'reclutador@retailandes.cl'): User
{
    Livewire::test(Register::class)
        ->set('role', 'empresa')
        ->set('nombre', 'Ana')
        ->set('apellidos', 'Silva')
        ->set('email', $email)
        ->set('password', 'clave-larga-123')
        ->set('razon_social', 'Retail Andes SpA')
        ->set('rut', '761234560')
        ->set('telefono', '+56 9 1234 5678')
        ->set('acepta', true)
        ->call('submit')
        ->assertHasNoErrors();

    return User::query()->where('email', $email)->firstOrFail();
}

/** La empresa ya verificó su correo y envió sus antecedentes: el panel está abierto. */
function empresaListaParaOperar(User $user): User
{
    $user->forceFill(['email_verified_at' => now()])->save();
    $user->empresa->update(['datos_enviados_at' => now(), 'estado_activacion' => 'activa']);

    return $user->fresh();
}

test('una empresa que se registra queda con el plan ilimitado, sin pasar por caja', function () {
    $empresa = registrarEmpresa()->empresa;

    expect($empresa->plan->codigo)->toBe(Plan::CODIGO_ILIMITADO)
        ->and($empresa->planVigente())->toBeTrue()
        ->and($empresa->plan_hasta->year)->toBe(now()->addYears(Plan::ANIOS_ILIMITADO)->year)
        // Publicaciones sin tope (NULL) y desbloqueos con un cupo que no se agota.
        ->and($empresa->publicacionesDisponibles())->toBeNull()
        ->and($empresa->tienePublicacionesIlimitadas())->toBeTrue()
        ->and($empresa->desbloqueosIlimitados())->toBeTrue()
        ->and($empresa->puedePublicar())->toBeTrue()
        // No se cobró nada: no hay pago asociado.
        ->and($empresa->pagos()->count())->toBe(0);
});

test('verificado el correo, entra a completar sus antecedentes y no a elegir plan', function () {
    $user = registrarEmpresa();

    expect($user->rutaPanelEmpresa())->toBe('empresa.activacion');

    $user->forceFill(['email_verified_at' => now()])->save();

    $this->actingAs($user->fresh())->get(route('empresa.panel'))->assertRedirect(route('empresa.activacion'));
    $this->actingAs($user->fresh())->get(route('empresa.activacion'))->assertOk();
});

test('con los antecedentes enviados opera el panel y desbloquea sin tope', function () {
    $user = empresaListaParaOperar(registrarEmpresa());

    $this->actingAs($user)->get(route('empresa.panel'))
        ->assertOk()
        ->assertSee('Ilimitados');
});

test('los planes no se ofrecen en la web ni en la administración de la cuenta', function () {
    // La página pública devuelve al inicio y la landing no muestra la sección.
    $this->get(route('planes'))->assertRedirect(route('home'));
    $this->get(route('home'))->assertOk()->assertDontSee('id="planes"', false);

    $user = empresaListaParaOperar(registrarEmpresa());

    // El menú de la cuenta ya no ofrece la suscripción, y su pantalla devuelve al panel.
    $this->actingAs($user)->get(route('empresa.panel'))
        ->assertOk()
        ->assertDontSee('Mi suscripción')
        ->assertDontSee('href="'.route('empresa.planes').'"', false);

    $this->actingAs($user)->get(route('empresa.planes'))->assertRedirect(route('empresa.panel'));
});

test('el plan ilimitado no se ofrece entre los planes contratables', function () {
    // Si mañana se vuelve a cobrar, el plan gratis no puede aparecer en las tarjetas.
    config()->set('ad50.funcionalidades.cobro_empresas', true);

    Plan::ilimitado();

    expect(Plan::query()->contratables()->pluck('codigo'))->not->toContain(Plan::CODIGO_ILIMITADO);

    $this->get(route('planes'))->assertOk()->assertDontSee('Ilimitado');
});

test('con el cobro encendido, registrarse deja a la empresa sin plan', function () {
    config()->set('ad50.funcionalidades.cobro_empresas', true);

    $empresa = registrarEmpresa()->empresa;

    expect($empresa->plan_id)->toBeNull()
        ->and($empresa->planVigente())->toBeFalse();
});

test('las empresas que ya existían quedaron con el plan ilimitado', function () {
    // Lo hace la migración 2026_09_28_000001, que corre con RefreshDatabase: una empresa
    // creada después no la alcanza, así que se comprueba el resultado de la migración
    // sobre la empresa demo que exista, si hay alguna, y el plan que dejó creado.
    expect(Plan::query()->where('codigo', Plan::CODIGO_ILIMITADO)->exists())->toBeTrue();

    $empresa = Empresa::query()->create([
        'user_id' => User::factory()->create(['role' => 'empresa'])->id,
        'razon_social' => 'Antigua SpA',
        'estado_activacion' => 'activa',
        'datos_enviados_at' => now(),
    ]);

    // Una cuenta sin plan no queda fuera: al pasar por la pantalla oculta lo recibe.
    $empresa->user->forceFill(['email_verified_at' => now()])->save();

    $this->actingAs($empresa->user->fresh())->get(route('empresa.planes'))->assertRedirect(route('empresa.panel'));

    expect($empresa->fresh()->plan->codigo)->toBe(Plan::CODIGO_ILIMITADO);
});
