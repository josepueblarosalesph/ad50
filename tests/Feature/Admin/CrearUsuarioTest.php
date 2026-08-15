<?php

use App\Livewire\Admin\Usuarios as AdminUsuarios;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

/**
 * Alta manual de cuentas desde la pantalla de Usuarios del superadministrador:
 * contraseña incluida y correo ya dado por verificado.
 */
test('the superadmin creates a postulante that can log in right away', function () {
    Notification::fake();

    $superadmin = User::factory()->create(['role' => 'superadmin']);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirCrearUsuario')
        ->set('nuevoRol', 'postulante')
        ->set('nuevoNombres', 'Marta')
        ->set('nuevoApellidos', 'Rojas')
        ->set('nuevoEmail', 'marta@correo.cl')
        ->set('nuevoPassword', 'clave-larga-123')
        ->call('crearUsuario')
        ->assertHasNoErrors();

    $creado = User::query()->where('email', 'marta@correo.cl')->firstOrFail();

    expect($creado->role)->toBe('postulante')
        ->and($creado->name)->toBe('Marta Rojas')
        ->and($creado->hasVerifiedEmail())->toBeTrue()
        ->and(Hash::check('clave-larga-123', $creado->password))->toBeTrue()
        // El postulante nace con su ficha, igual que en el registro.
        ->and($creado->postulante)->not->toBeNull()
        ->and($creado->postulante->onboarding_completado)->toBeFalse();

    // La cuenta no la pidió su titular: no hay nada que confirmar por correo.
    Notification::assertNothingSent();

    // La contraseña entregada es la que abre la sesión.
    auth()->logout();

    $this->post(route('login.store'), [
        'email' => 'marta@correo.cl',
        'password' => 'clave-larga-123',
    ])->assertRedirect();

    $this->assertAuthenticatedAs($creado);
});

test('a verified account created here is never asked to confirm its email', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->set('nuevoRol', 'admin')
        ->set('nuevoNombres', 'Ana')
        ->set('nuevoApellidos', 'Silva')
        ->set('nuevoEmail', 'ana@adconsulting.cl')
        ->set('nuevoPassword', 'clave-larga-123')
        ->call('crearUsuario')
        ->assertHasNoErrors();

    $creado = User::query()->where('email', 'ana@adconsulting.cl')->firstOrFail();

    // El middleware `verified` la deja pasar sin el enlace de por medio.
    $this->actingAs($creado)->get(route('admin.panel'))->assertOk();

    // Y el aviso de "verifica tu correo" ya no le corresponde.
    $this->actingAs($creado)->get(route('verification.notice'))->assertRedirect('/dashboard');
});

test('creating an empresa account also creates its company, already activated', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->set('nuevoRol', 'empresa')
        ->set('nuevoNombres', 'Carlos')
        ->set('nuevoApellidos', 'Vega')
        ->set('nuevoEmail', 'carlos@consultora.cl')
        ->set('nuevoPassword', 'clave-larga-123')
        ->set('nuevaEmpresaId', '')
        ->set('nuevaRazonSocial', 'Consultora Ejemplo SpA')
        ->set('nuevoRut', '76.086.428-5')
        ->set('nuevoTelefono', '+56 9 1234 5678')
        ->call('crearUsuario')
        ->assertHasNoErrors();

    $creado = User::query()->where('email', 'carlos@consultora.cl')->firstOrFail();
    $empresa = Empresa::query()->where('user_id', $creado->id)->firstOrFail();

    expect($creado->role)->toBe('empresa')
        ->and($creado->hasVerifiedEmail())->toBeTrue()
        ->and($empresa->razon_social)->toBe('Consultora Ejemplo SpA')
        ->and($empresa->estaActiva())->toBeTrue()
        ->and($empresa->datosEnviados())->toBeTrue()
        ->and($empresa->activada_por)->toBe($superadmin->id)
        // El plan se contrata aparte: la activación no lo regala.
        ->and($empresa->planVigente())->toBeFalse()
        // El contacto administrador queda enlazado a su empresa.
        ->and($creado->fresh()->empresa_id)->toBe($empresa->id);
});

test('an empresa account can be attached to a company that already exists', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $duenio = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create([
        'user_id' => $duenio->id,
        'razon_social' => 'Retail Andes SpA',
        'rut' => '76.086.428-5',
        'estado_activacion' => 'activa',
    ]);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->set('nuevoRol', 'empresa')
        ->set('nuevoNombres', 'Paula')
        ->set('nuevoApellidos', 'Díaz')
        ->set('nuevoEmail', 'paula@retailandes.cl')
        ->set('nuevoPassword', 'clave-larga-123')
        ->set('nuevaEmpresaId', (string) $empresa->id)
        ->call('crearUsuario')
        ->assertHasNoErrors();

    $creado = User::query()->where('email', 'paula@retailandes.cl')->firstOrFail();

    expect($creado->empresa_id)->toBe($empresa->id)
        // Se suma al equipo: no aparece una segunda empresa.
        ->and(Empresa::query()->count())->toBe(1)
        ->and($creado->esPrincipalEmpresa())->toBeFalse();
});

test('a company that is already full rejects one more user', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $duenio = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create([
        'user_id' => $duenio->id,
        'razon_social' => 'Retail Andes SpA',
        'rut' => '76.086.428-5',
        'estado_activacion' => 'activa',
    ]);

    User::factory()->count(Empresa::MAX_USUARIOS_ADICIONALES)->create([
        'role' => 'empresa',
        'empresa_id' => $empresa->id,
    ]);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->set('nuevoRol', 'empresa')
        ->set('nuevoNombres', 'Paula')
        ->set('nuevoApellidos', 'Díaz')
        ->set('nuevoEmail', 'paula@retailandes.cl')
        ->set('nuevoPassword', 'clave-larga-123')
        ->set('nuevaEmpresaId', (string) $empresa->id)
        ->call('crearUsuario')
        ->assertHasErrors('nuevaEmpresaId');

    expect(User::query()->where('email', 'paula@retailandes.cl')->exists())->toBeFalse();
});

test('the form rejects a duplicated email, a short password and an invalid company rut', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    User::factory()->create(['email' => 'ocupado@correo.cl']);

    $componente = Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->set('nuevoRol', 'postulante')
        ->set('nuevoNombres', 'Marta')
        ->set('nuevoApellidos', 'Rojas')
        ->set('nuevoEmail', 'ocupado@correo.cl')
        ->set('nuevoPassword', 'corta')
        ->call('crearUsuario')
        ->assertHasErrors(['nuevoEmail', 'nuevoPassword']);

    $componente
        ->set('nuevoRol', 'empresa')
        ->set('nuevoEmail', 'nuevo@consultora.cl')
        ->set('nuevoPassword', 'clave-larga-123')
        ->set('nuevaEmpresaId', '')
        ->set('nuevaRazonSocial', 'Consultora Ejemplo SpA')
        ->set('nuevoRut', '76.086.428-1') // dígito verificador que no calza
        ->set('nuevoTelefono', '+56 9 1234 5678')
        ->call('crearUsuario')
        ->assertHasErrors('nuevoRut');

    expect(User::query()->where('email', 'nuevo@consultora.cl')->exists())->toBeFalse()
        ->and(Empresa::query()->count())->toBe(0);
});

test('a plain admin cannot create accounts', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    // La pantalla completa es del superadministrador: ni siquiera llega al formulario.
    Livewire::actingAs($admin)
        ->test(AdminUsuarios::class)
        ->assertForbidden();

    expect(User::query()->count())->toBe(1);
});

test('the generated password is long enough to pass validation', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $componente = Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('generarPassword');

    expect(mb_strlen($componente->get('nuevoPassword')))->toBeGreaterThanOrEqual(8);
});
