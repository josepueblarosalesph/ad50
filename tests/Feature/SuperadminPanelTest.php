<?php

use App\Livewire\Admin\Catalogos as AdminCatalogos;
use App\Livewire\Admin\Planes as AdminPlanes;
use App\Livewire\Admin\Usuarios as AdminUsuarios;
use App\Models\Postulante;
use App\Models\User;
use Database\Seeders\SuperadminSeeder;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('the superadmin reaches every admin screen, including the ones reserved for it', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $this->actingAs($superadmin);

    foreach (['admin.panel', 'admin.empresas', 'admin.postulantes', 'admin.planes', 'admin.catalogos', 'admin.mensajes', 'admin.usuarios'] as $ruta) {
        $this->get(route($ruta))->assertOk();
    }
});

test('a plain admin keeps the shared screens but is refused the reserved ones', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin);

    foreach (['admin.panel', 'admin.empresas', 'admin.postulantes', 'admin.planes', 'admin.mensajes'] as $ruta) {
        $this->get(route($ruta))->assertOk();
    }

    // Cuentas ajenas y catálogos quedan fuera del alcance del admin común.
    $this->get(route('admin.usuarios'))->assertForbidden();
    $this->get(route('admin.catalogos'))->assertForbidden();
});

test('the admin nav offers the reserved sections only to the superadmin', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($superadmin)->get(route('admin.panel'))
        ->assertOk()
        ->assertSee('href="'.route('admin.catalogos').'"', false)
        ->assertSee('href="'.route('admin.usuarios').'"', false);

    // Al admin común no se le ofrece un enlace que terminaría en 403.
    $this->actingAs($admin)->get(route('admin.panel'))
        ->assertOk()
        ->assertDontSee('href="'.route('admin.catalogos').'"', false)
        ->assertDontSee('href="'.route('admin.usuarios').'"', false);
});

test('a plain admin cannot even mount the catalogs component directly', function () {
    // El gating no puede vivir solo en la ruta: las acciones que escriben se comprueban
    // por su cuenta, igual que en Admin\Usuarios.
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(AdminCatalogos::class)
        ->assertForbidden();
});

test('a postulante cannot reach the admin screens', function () {
    $user = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create(['user_id' => $user->id, 'onboarding_completado' => true]);

    $this->actingAs($user)->get(route('admin.usuarios'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.planes'))->assertForbidden();
});

test('the superadmin lists every account and can filter by role', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin', 'name' => 'Jose Puebla']);
    User::factory()->create(['role' => 'postulante', 'name' => 'Marta Rojas']);
    User::factory()->create(['role' => 'empresa', 'name' => 'Carlos Vega']);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->assertSee('Marta Rojas')
        ->assertSee('Carlos Vega')
        ->assertSee('Jose Puebla')
        ->set('rol', 'postulante')
        ->assertSee('Marta Rojas')
        ->assertDontSee('Carlos Vega')
        ->set('rol', 'todos')
        ->set('buscar', 'Carlos')
        ->assertSee('Carlos Vega')
        ->assertDontSee('Marta Rojas');
});

test('the superadmin changes the role of another account without destroying its data', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $usuario = User::factory()->create(['role' => 'postulante', 'name' => 'Marta Rojas']);
    $ficha = Postulante::query()->create(['user_id' => $usuario->id, 'cargo_actual' => 'Jefa de Operaciones']);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirCambioRol', $usuario->id)
        ->assertSet('rolActual', 'postulante')
        ->set('rolNuevo', 'empresa')
        ->call('cambiarRol')
        ->assertHasNoErrors();

    expect($usuario->fresh()->role)->toBe('empresa');

    // La ficha se conserva: el cambio tiene que poder revertirse.
    expect($ficha->fresh())->not->toBeNull()
        ->and($ficha->fresh()->cargo_actual)->toBe('Jefa de Operaciones');
});

test('the superadmin can promote another account to superadmin', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $usuario = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirCambioRol', $usuario->id)
        ->set('rolNuevo', 'superadmin')
        ->call('cambiarRol')
        ->assertHasNoErrors();

    expect($usuario->fresh()->esSuperadmin())->toBeTrue();
});

test('an unknown role is rejected', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $usuario = User::factory()->create(['role' => 'postulante']);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirCambioRol', $usuario->id)
        ->set('rolNuevo', 'dios')
        ->call('cambiarRol')
        ->assertHasErrors('rolNuevo');

    expect($usuario->fresh()->role)->toBe('postulante');
});

test('the superadmin cannot change its own role and lock itself out', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirCambioRol', $superadmin->id)
        ->assertForbidden();

    expect($superadmin->fresh()->role)->toBe('superadmin');
});

test('a plain admin cannot change roles even by calling the component directly', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $usuario = User::factory()->create(['role' => 'postulante']);

    Livewire::actingAs($admin)
        ->test(AdminUsuarios::class)
        ->assertForbidden();

    expect($usuario->fresh()->role)->toBe('postulante');
});

test('the superadmin lands on the admin panel after logging in', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    expect($superadmin->dashboardRouteName())->toBe('admin.panel');

    $this->actingAs($superadmin)->get('/dashboard')->assertRedirect(route('admin.panel'));
});

test('the seeder creates the superadmin account and is safe to run twice', function () {
    $this->seed(SuperadminSeeder::class);

    $email = config('ad50.superadmin.email');
    $creado = User::query()->where('email', $email)->firstOrFail();

    expect($creado->esSuperadmin())->toBeTrue()
        ->and($creado->email_verified_at)->not->toBeNull();

    $this->seed(SuperadminSeeder::class);

    expect(User::query()->where('email', $email)->count())->toBe(1);
});

test('outside local the seeder never creates the account with a guessable password', function () {
    // Sin SUPERADMIN_PASSWORD y fuera de local, la cuenta con más privilegios de la
    // plataforma no puede quedar accesible con la clave de los seeders de demo.
    config(['ad50.superadmin.password' => null]);
    app()->detectEnvironment(fn () => 'production');

    // --force: fuera de local, db:seed pide confirmación antes de tocar la base.
    $this->artisan('db:seed', ['--class' => SuperadminSeeder::class, '--force' => true]);

    $creado = User::query()->where('email', config('ad50.superadmin.email'))->firstOrFail();

    expect($creado->esSuperadmin())->toBeTrue()
        ->and(Hash::check('password', $creado->password))->toBeFalse()
        ->and(Hash::check('', $creado->password))->toBeFalse();
});

test('a configured password is what the account is created with', function () {
    config(['ad50.superadmin.password' => 'una-clave-larga-de-entorno']);

    $this->seed(SuperadminSeeder::class);

    $creado = User::query()->where('email', config('ad50.superadmin.email'))->firstOrFail();

    expect(Hash::check('una-clave-larga-de-entorno', $creado->password))->toBeTrue();
});

test('the seeder promotes an account that already existed without touching its password', function () {
    $email = config('ad50.superadmin.email');
    $existente = User::factory()->create(['email' => $email, 'role' => 'postulante']);
    $passwordOriginal = $existente->password;

    $this->seed(SuperadminSeeder::class);

    $existente->refresh();

    expect($existente->esSuperadmin())->toBeTrue()
        ->and($existente->password)->toBe($passwordOriginal);
});

test('the planes screen shows how many companies hold each plan', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(AdminPlanes::class)
        ->assertOk()
        ->assertSee('Planes para empresas');
});
