<?php

use App\Livewire\Admin\Empresas as AdminEmpresas;
use App\Livewire\Empresa\Activacion;
use App\Models\Empresa;
use App\Models\User;
use Livewire\Livewire;

test('an empresa without data is redirected to the activation form', function () {
    $user = User::factory()->create(['role' => 'empresa']);
    Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => 'Empresa Pendiente SpA',
        'estado_activacion' => 'inactiva',
    ]);

    $this->actingAs($user)
        ->get(route('empresa.panel'))
        ->assertRedirect(route('empresa.activacion'));

    $this->actingAs($user)
        ->get(route('empresa.activacion'))
        ->assertOk()
        ->assertSee('Contacto principal')
        ->assertSee('Contacto técnico');
});

test('submitting the data self-activates and sends the empresa to choose a plan', function () {
    $user = User::factory()->create(['name' => 'Ana Silva', 'email' => 'ana@empresa.cl', 'role' => 'empresa']);
    $empresa = Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => 'Empresa Pendiente SpA',
        'telefono' => '+56 9 1111 1111',
        'estado_activacion' => 'inactiva',
    ]);

    Livewire::actingAs($user)
        ->test(Activacion::class)
        ->set('rut', '98421157')
        ->set('rubro', 'Servicios profesionales')
        ->set('contactoPrincipalCargo', 'Gerenta de Personas')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertRedirect(route('empresa.planes'));

    $this->assertDatabaseHas('empresas', [
        'id' => $empresa->id,
        'estado_activacion' => 'activa',
        'rut' => '9.842.115-7',
        'contacto_principal_nombre' => 'Ana Silva',
        'contacto_principal_email' => 'ana@empresa.cl',
    ]);
    expect($empresa->fresh()->datos_enviados_at)->not->toBeNull();
});

test('the technical contact is optional and its cargo is stored', function () {
    $user = User::factory()->create(['name' => 'Ana Silva', 'email' => 'ana@empresa.cl', 'role' => 'empresa']);
    $empresa = Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => 'Empresa Sin Técnico SpA',
        'telefono' => '+56 9 1111 1111',
        'estado_activacion' => 'inactiva',
    ]);

    Livewire::actingAs($user)
        ->test(Activacion::class)
        ->set('rut', '98421157')
        ->set('rubro', 'Servicios profesionales')
        ->set('contactoPrincipalCargo', 'Gerenta de Personas')
        ->call('guardar')
        ->assertHasNoErrors();

    expect($empresa->fresh()->datos_enviados_at)->not->toBeNull()
        ->and($empresa->fresh()->contacto_tecnico_nombre)->toBe('');

    Livewire::actingAs($user)
        ->test(Activacion::class)
        ->set('rut', '98421157')
        ->set('rubro', 'Servicios profesionales')
        ->set('contactoPrincipalCargo', 'Gerenta de Personas')
        ->set('contactoTecnicoNombre', 'Tomás Pérez')
        ->set('contactoTecnicoCargo', 'Jefe de TI')
        ->set('contactoTecnicoEmail', 'ti@empresa.cl')
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('empresas', [
        'id' => $empresa->id,
        'contacto_tecnico_nombre' => 'Tomás Pérez',
        'contacto_tecnico_cargo' => 'Jefe de TI',
        'contacto_tecnico_email' => 'ti@empresa.cl',
    ]);
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
