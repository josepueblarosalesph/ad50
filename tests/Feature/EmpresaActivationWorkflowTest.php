<?php

use App\Livewire\Admin\Empresas as AdminEmpresas;
use App\Livewire\Empresa\Activacion;
use App\Models\Empresa;
use App\Models\User;
use Livewire\Livewire;

test('an inactive empresa is redirected to the activation form', function () {
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

test('an empresa can submit its details for manual review', function () {
    $user = User::factory()->create([
        'name' => 'Ana Silva',
        'email' => 'ana@empresa.cl',
        'role' => 'empresa',
    ]);
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
        ->set('contactoTecnicoNombre', 'Tomás Pérez')
        ->set('contactoTecnicoEmail', 'tecnico@empresa.cl')
        ->set('contactoTecnicoTelefono', '+56 9 2222 2222')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertSee('Antecedentes recibidos');

    $this->assertDatabaseHas('empresas', [
        'id' => $empresa->id,
        'estado_activacion' => 'pendiente',
        'rut' => '9.842.115-7',
        'contacto_principal_nombre' => 'Ana Silva',
        'contacto_principal_email' => 'ana@empresa.cl',
        'contacto_tecnico_email' => 'tecnico@empresa.cl',
    ]);
});

test('the technical contact is optional and its cargo is stored', function () {
    $user = User::factory()->create(['name' => 'Ana Silva', 'email' => 'ana@empresa.cl', 'role' => 'empresa']);
    $empresa = Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => 'Empresa Sin Técnico SpA',
        'telefono' => '+56 9 1111 1111',
        'estado_activacion' => 'inactiva',
    ]);

    // Sin ningún dato de contacto técnico: debe poder enviarse.
    Livewire::actingAs($user)
        ->test(Activacion::class)
        ->set('rut', '98421157')
        ->set('rubro', 'Servicios profesionales')
        ->set('contactoPrincipalCargo', 'Gerenta de Personas')
        ->call('guardar')
        ->assertHasNoErrors();

    expect($empresa->fresh()->estado_activacion)->toBe('pendiente')
        ->and($empresa->fresh()->contacto_tecnico_nombre)->toBe('');

    // Con contacto técnico incluido su cargo: se guarda.
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

test('an admin can review and activate a pending empresa', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create([
        'user_id' => $empresaUser->id,
        'razon_social' => 'Empresa Revisada SpA',
        'rut' => '9.842.115-7',
        'rubro' => 'Tecnología',
        'estado_activacion' => 'pendiente',
        'contacto_principal_nombre' => 'Ana Silva',
        'contacto_principal_cargo' => 'Gerenta de Personas',
        'contacto_principal_email' => 'ana@empresa.cl',
        'contacto_principal_telefono' => '+56 9 1111 1111',
        'contacto_tecnico_nombre' => 'Tomás Pérez',
        'contacto_tecnico_email' => 'tecnico@empresa.cl',
        'contacto_tecnico_telefono' => '+56 9 2222 2222',
        'datos_enviados_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(AdminEmpresas::class)
        ->assertSee('Empresa Revisada SpA')
        ->assertSee('Habilitar empresa')
        ->call('activar', $empresa->id)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('empresas', [
        'id' => $empresa->id,
        'estado_activacion' => 'activa',
        'activada_por' => $admin->id,
    ]);

    $this->actingAs($empresaUser)
        ->get(route('empresa.panel'))
        ->assertOk();
});

test('non admins cannot access empresa activation reviews', function () {
    $user = User::factory()->create(['role' => 'postulante']);

    $this->actingAs($user)
        ->get(route('admin.empresas'))
        ->assertForbidden();
});
