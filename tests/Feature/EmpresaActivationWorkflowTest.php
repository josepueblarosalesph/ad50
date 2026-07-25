<?php

use App\Livewire\Admin\Empresas as AdminEmpresas;
use App\Livewire\Empresa\Activacion;
use App\Models\Empresa;
use App\Models\Plan;
use App\Models\User;
use Livewire\Livewire;

test('an empresa without a paid plan is redirected to choose a plan before entering data', function () {
    $user = User::factory()->create(['role' => 'empresa']);
    Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => 'Empresa Pendiente SpA',
        'estado_activacion' => 'inactiva',
    ]);

    $this->actingAs($user)
        ->get(route('empresa.panel'))
        ->assertRedirect(route('empresa.planes'));

    $this->actingAs($user)
        ->get(route('empresa.activacion'))
        ->assertRedirect(route('empresa.planes'));

    $this->actingAs($user)
        ->get(route('empresa.planes'))
        ->assertOk()
        ->assertSee('Primer paso para activar tu cuenta');
});

test('an empresa completes its data after paying and is sent to the panel', function () {
    $user = User::factory()->create(['name' => 'Ana Silva', 'email' => 'ana@empresa.cl', 'role' => 'empresa']);
    $empresa = Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => 'Empresa Pendiente SpA',
        'telefono' => '+56 9 1111 1111',
        'estado_activacion' => 'inactiva',
        'plan_id' => Plan::query()->create([
            'codigo' => 'p_'.str()->random(6), 'nombre' => 'P', 'audiencia' => 'empresa', 'precio_clp' => 1, 'periodo' => 'mensual', 'desbloqueos' => 1,
        ])->id,
        'plan_hasta' => now()->addMonth(),
    ]);

    Livewire::actingAs($user)
        ->test(Activacion::class)
        ->set('rut', '98421157')
        ->set('rubro', 'Servicios profesionales')
        ->set('contactoPrincipalCargo', 'Gerenta de Personas')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertRedirect(route('empresa.panel'));

    $this->assertDatabaseHas('empresas', [
        'id' => $empresa->id,
        'estado_activacion' => 'activa',
        'rut' => '9.842.115-7',
        'contacto_principal_nombre' => 'Ana Silva',
        'contacto_principal_email' => 'ana@empresa.cl',
    ]);
    expect($empresa->fresh()->datos_enviados_at)->not->toBeNull();
});

test('the administrator can enable all three contact users during onboarding', function () {
    $user = User::factory()->create(['name' => 'Ana Silva', 'email' => 'ana@empresa.cl', 'role' => 'empresa']);
    $empresa = Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => 'Empresa Sin Técnico SpA',
        'telefono' => '+56 9 1111 1111',
        'estado_activacion' => 'inactiva',
        'plan_id' => Plan::query()->create([
            'codigo' => 'p_'.str()->random(6), 'nombre' => 'P', 'audiencia' => 'empresa', 'precio_clp' => 1, 'periodo' => 'mensual', 'desbloqueos' => 1,
        ])->id,
        'plan_hasta' => now()->addMonth(),
    ]);

    Livewire::actingAs($user)
        ->test(Activacion::class)
        ->set('rut', '98421157')
        ->set('rubro', 'Servicios profesionales')
        ->set('contactoPrincipalCargo', 'Gerenta de Personas')
        ->set('usuarios.0.nombre', 'Tomás')
        ->set('usuarios.0.apellidos', 'Pérez')
        ->set('usuarios.0.email', 'tomas@empresa.cl')
        ->set('usuarios.0.password', 'secreto123')
        ->set('usuarios.1.nombre', 'Carla')
        ->set('usuarios.1.apellidos', 'Rojas')
        ->set('usuarios.1.email', 'carla@empresa.cl')
        ->set('usuarios.1.password', 'secreto456')
        ->set('usuarios.2.nombre', 'Diego')
        ->set('usuarios.2.apellidos', 'Soto')
        ->set('usuarios.2.email', 'diego@empresa.cl')
        ->set('usuarios.2.password', 'secreto789')
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('users', [
        'name' => 'Tomás Pérez',
        'email' => 'tomas@empresa.cl',
        'role' => 'empresa',
        'empresa_id' => $empresa->id,
    ]);

    expect($empresa->fresh()->usuariosAdicionales()->count())->toBe(3);
});

test('an empresa with data but without a paid plan is sent to choose a plan', function () {
    $user = User::factory()->create(['role' => 'empresa']);
    Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => 'Empresa Con Datos',
        'estado_activacion' => 'activa',
        'datos_enviados_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('empresa.panel'))
        ->assertRedirect(route('empresa.planes'));
});

test('an empresa with data and a paid plan reaches the panel', function () {
    $user = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => 'Empresa Operativa',
        'estado_activacion' => 'activa',
    ]);
    hacerEmpresaOperativa($empresa);

    $this->actingAs($user)
        ->get(route('empresa.panel'))
        ->assertOk();
});

test('an empresa with a paid plan but no submitted data does not loop', function () {
    $user = User::factory()->create(['role' => 'empresa']);
    Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => 'Plan Sin Datos SpA',
        'estado_activacion' => 'activa',
        'plan_id' => Plan::query()->create([
            'codigo' => 'p_'.str()->random(6), 'nombre' => 'P', 'audiencia' => 'empresa', 'precio_clp' => 1, 'periodo' => 'mensual', 'desbloqueos' => 1,
        ])->id,
        'plan_hasta' => now()->addMonth(),
        // datos_enviados_at nulo a propósito.
    ]);

    // Tras pagar, la activación se muestra y el panel exige completar los datos.
    $this->actingAs($user)->get(route('empresa.activacion'))->assertOk();
    $this->actingAs($user)->get(route('empresa.panel'))->assertRedirect(route('empresa.activacion'));
});

test('an admin can mark an empresa as active', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create([
        'user_id' => $empresaUser->id,
        'razon_social' => 'Empresa Revisada SpA',
        'rut' => '9.842.115-7',
        'estado_activacion' => 'pendiente',
        'datos_enviados_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(AdminEmpresas::class)
        ->assertSee('Empresa Revisada SpA')
        ->call('activar', $empresa->id)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('empresas', [
        'id' => $empresa->id,
        'estado_activacion' => 'activa',
        'activada_por' => $admin->id,
    ]);
});

test('non admins cannot access empresa activation reviews', function () {
    $user = User::factory()->create(['role' => 'postulante']);

    $this->actingAs($user)
        ->get(route('admin.empresas'))
        ->assertForbidden();
});
