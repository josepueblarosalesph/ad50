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

    // Un solo texto explica que elegir plan es obligatorio y qué viene después del pago.
    $this->actingAs($user)
        ->get(route('empresa.planes'))
        ->assertOk()
        ->assertSee('Debes seleccionar un plan para continuar.')
        ->assertSee('deberás completar algunos datos de tu empresa');
});

test('the welcome banner can be dismissed and remembers it per empresa', function () {
    $user = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => 'Recién Pagada SpA',
        'estado_activacion' => 'inactiva',
        'plan_id' => Plan::query()->create([
            'codigo' => 'p_'.str()->random(6), 'nombre' => 'P', 'audiencia' => 'empresa', 'precio_clp' => 1, 'periodo' => 'mensual', 'desbloqueos' => 1,
        ])->id,
        'plan_hasta' => now()->addMonth(),
    ]);

    // La llave de localStorage cuelga del id de la empresa: cerrar la bienvenida en una
    // cuenta no debe ocultársela a otra que use el mismo navegador.
    $this->actingAs($user)
        ->get(route('empresa.activacion'))
        ->assertOk()
        ->assertSee('¡Bienvenido!')
        ->assertSee('aria-label="Cerrar la bienvenida"', false)
        ->assertSee("ad-bienvenida-activacion-{$empresa->id}", false);
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

test('an autofilled password alone does not block the onboarding', function () {
    // Caso reportado: el gestor de contraseñas del navegador rellenó la «contraseña
    // temporal» del Usuario 1 mientras la persona autocompletaba los campos de más
    // arriba. La empresa no quería agregar usuarios, pero el formulario le exigía nombre,
    // apellidos y correo para ese usuario fantasma y no la dejaba avanzar.
    $user = User::factory()->create(['name' => 'Ana Silva', 'email' => 'ana@empresa.cl', 'role' => 'empresa']);
    $empresa = Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => 'Sin Usuarios SpA',
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
        ->set('usuarios.0.password', 'clave-que-metio-el-navegador')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertRedirect(route('empresa.panel'));

    // La empresa queda activa y sin usuarios adicionales: la contraseña fantasma se ignora.
    expect($empresa->fresh()->estaActiva())->toBeTrue();
    expect($empresa->fresh()->usuariosAdicionales()->count())->toBe(0);
});

test('a half-filled user is still rejected, with a message that names the way out', function () {
    // El descarte anterior no puede tapar el error legítimo: si la persona sí empezó a
    // describir a un usuario, faltarle datos tiene que seguir deteniendo el envío.
    $user = User::factory()->create(['name' => 'Ana Silva', 'email' => 'ana@empresa.cl', 'role' => 'empresa']);
    Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => 'Medio Llena SpA',
        'telefono' => '+56 9 1111 1111',
        'estado_activacion' => 'inactiva',
        'plan_id' => Plan::query()->create([
            'codigo' => 'p_'.str()->random(6), 'nombre' => 'P', 'audiencia' => 'empresa', 'precio_clp' => 1, 'periodo' => 'mensual', 'desbloqueos' => 1,
        ])->id,
        'plan_hasta' => now()->addMonth(),
    ]);

    $componente = Livewire::actingAs($user)
        ->test(Activacion::class)
        ->set('rut', '98421157')
        ->set('rubro', 'Servicios profesionales')
        ->set('contactoPrincipalCargo', 'Gerenta de Personas')
        ->set('usuarios.0.nombre', 'Tomás')
        ->set('usuarios.0.password', 'secreto123')
        ->call('guardar')
        ->assertHasErrors(['usuarios.0.apellidos', 'usuarios.0.email']);

    // El mensaje ya no enumera llaves crudas del arreglo («usuarios.0.apellidos …»).
    $mensaje = $componente->errors()->first('usuarios.0.email');
    expect($mensaje)->toBe('Escribe el correo de este usuario, o deja su ficha completamente en blanco si no quieres crearlo.');
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
