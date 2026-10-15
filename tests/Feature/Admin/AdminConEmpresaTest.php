<?php

use App\Livewire\Admin\Usuarios as AdminUsuarios;
use App\Models\Empresa;
use App\Models\Plan;
use App\Models\User;
use Livewire\Livewire;

/**
 * Un admin de la plataforma que además administra una empresa. El caso nace de promover a
 * admin a un contacto de empresa: el cambio de rol conserva su empresa, y desde entonces
 * necesita moverse entre los dos paneles.
 */
function empresaOperativaDe(User $user, string $razonSocial = 'Empresa del Admin SpA'): Empresa
{
    return Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => $razonSocial,
        'rut' => '76.123.456-7',
        'estado_activacion' => 'activa',
        'datos_enviados_at' => now(),
        'plan_id' => Plan::query()->create([
            'codigo' => 'p_'.str()->random(6), 'nombre' => 'P', 'audiencia' => 'empresa',
            'precio_clp' => 1, 'periodo' => 'mensual', 'desbloqueos' => 5,
        ])->id,
        'plan_hasta' => now()->addMonth(),
    ]);
}

test('un admin con empresa entra a los dos paneles', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    empresaOperativaDe($admin);

    $this->actingAs($admin)->get(route('admin.panel'))->assertOk();
    $this->actingAs($admin)->get(route('empresa.panel'))->assertOk();
    $this->actingAs($admin)->get(route('empresa.busquedas.index'))->assertOk();
});

test('un admin sin empresa sigue fuera del panel de empresa', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('empresa.panel'))->assertForbidden();
    $this->actingAs($admin)->get(route('empresa.planes'))->assertForbidden();
});

test('el admin con empresa sigue las mismas reglas de plan y activación', function () {
    // Sin plan vigente va a contratar, igual que cualquier empresa: los desbloqueos y el
    // tope anual se apoyan en el plan, así que ser admin no lo saltea.
    $admin = User::factory()->create(['role' => 'admin']);
    $empresa = Empresa::query()->create([
        'user_id' => $admin->id,
        'razon_social' => 'Sin Plan SpA',
        'estado_activacion' => 'inactiva',
    ]);

    $this->actingAs($admin)->get(route('empresa.panel'))->assertRedirect(route('empresa.planes'));

    // Con el pago hecho pero sin antecedentes, a completarlos. Se relee la cuenta porque
    // actingAs() reutiliza la instancia y su relación `empresa` quedó cacheada sin plan.
    $empresa->update([
        'plan_id' => Plan::query()->create([
            'codigo' => 'p_'.str()->random(6), 'nombre' => 'P', 'audiencia' => 'empresa',
            'precio_clp' => 1, 'periodo' => 'mensual', 'desbloqueos' => 1,
        ])->id,
        'plan_hasta' => now()->addMonth(),
    ]);

    $this->actingAs($admin->fresh())->get(route('empresa.panel'))->assertRedirect(route('empresa.activacion'));
});

test('el conmutador ofrece el panel en el que no se está', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    empresaOperativaDe($admin);

    // Desde administración se ofrece el de empresa.
    $this->actingAs($admin)->get(route('admin.panel'))
        ->assertOk()
        ->assertSee('Panel de empresa')
        ->assertSeeHtml('href="'.route('empresa.panel').'"');

    // Desde el panel de empresa se ofrece el de administración.
    $this->actingAs($admin)->get(route('empresa.panel'))
        ->assertOk()
        ->assertSee('Panel de administración')
        ->assertSeeHtml('href="'.route('admin.panel').'"');
});

test('el conmutador respeta el gating al apuntar al panel de empresa', function () {
    // Con la empresa a medio activar, el botón lleva a completar en vez de a un redirect.
    $admin = User::factory()->create(['role' => 'admin']);
    Empresa::query()->create([
        'user_id' => $admin->id,
        'razon_social' => 'A Medio Activar SpA',
        'estado_activacion' => 'inactiva',
    ]);

    $this->actingAs($admin)->get(route('admin.panel'))
        ->assertOk()
        ->assertSeeHtml('href="'.route('empresa.planes').'"');
});

test('quien tiene un solo panel no ve el conmutador', function () {
    $adminSolo = User::factory()->create(['role' => 'admin']);
    $this->actingAs($adminSolo)->get(route('admin.panel'))
        ->assertOk()
        ->assertDontSee('Panel de empresa');

    $empresaSola = User::factory()->create(['role' => 'empresa']);
    empresaOperativaDe($empresaSola, 'Empresa a Secas SpA');
    $this->actingAs($empresaSola)->get(route('empresa.panel'))
        ->assertOk()
        ->assertDontSee('Panel de administración');
});

test('promover a admin a un contacto de empresa le deja los dos paneles', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $contacto = User::factory()->create(['role' => 'empresa', 'name' => 'Marta Rojas']);
    $empresa = empresaOperativaDe($contacto, 'Promovida SpA');

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirCambioRol', $contacto->id)
        ->set('rolNuevo', 'admin')
        ->call('cambiarRol')
        ->assertHasNoErrors();

    $contacto->refresh();

    expect($contacto->role)->toBe('admin')
        ->and($contacto->esAdmin())->toBeTrue()
        ->and($contacto->esEmpresa())->toBeTrue()
        // Conserva la propiedad de su empresa, así que sigue mandando en su equipo.
        ->and($contacto->empresa?->id)->toBe($empresa->id)
        ->and($contacto->esPrincipalEmpresa())->toBeTrue()
        // Su identidad principal pasa a ser la de admin (el login general igual lo lleva
        // a su empresa, ver LoginResponse).
        ->and($contacto->dashboardRouteName())->toBe('admin.panel');

    $this->actingAs($contacto)->get(route('empresa.panel'))->assertOk();
    $this->actingAs($contacto)->get(route('admin.panel'))->assertOk();
});

test('el superadmin asocia una empresa a un admin que no tenía', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $admin = User::factory()->create(['role' => 'admin']);
    $empresa = empresaOperativaDe(User::factory()->create(['role' => 'empresa']));

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirCambioRol', $admin->id)
        ->set('rolNuevo', AdminUsuarios::ADMIN_EMPRESA)
        ->set('empresaAsociadaId', (string) $empresa->id)
        ->call('cambiarRol')
        ->assertHasNoErrors();

    $admin->refresh();
    expect($admin->role)->toBe('admin')
        ->and($admin->empresa_id)->toBe($empresa->id)
        ->and($admin->esEmpresa())->toBeTrue();

    $this->actingAs($admin)->get(route('admin.panel'))->assertOk();
    $this->actingAs($admin)->get(route('empresa.panel'))->assertOk();
});

test('el superadmin deja a un postulante como admin y empresa a la vez', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $persona = User::factory()->create(['role' => 'postulante']);
    $empresa = empresaOperativaDe(User::factory()->create(['role' => 'empresa']));

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirCambioRol', $persona->id)
        ->set('rolNuevo', AdminUsuarios::ADMIN_EMPRESA)
        ->set('empresaAsociadaId', (string) $empresa->id)
        ->call('cambiarRol')
        ->assertHasNoErrors();

    $persona->refresh();
    expect($persona->role)->toBe('admin')
        ->and($persona->empresa_id)->toBe($empresa->id);
});

test('quitar la empresa asociada saca al admin del panel de empresa', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $empresa = empresaOperativaDe(User::factory()->create(['role' => 'empresa']));
    $admin = User::factory()->create(['role' => 'admin', 'empresa_id' => $empresa->id]);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirCambioRol', $admin->id)
        ->assertSet('rolNuevo', AdminUsuarios::ADMIN_EMPRESA)
        ->assertSet('empresaAsociadaId', (string) $empresa->id)
        ->set('rolNuevo', 'admin')
        ->call('cambiarRol');

    expect($admin->fresh()->empresa_id)->toBeNull();
    $this->actingAs($admin->fresh())->get(route('empresa.panel'))->assertForbidden();
});

test('asociar una empresa respeta su cupo de usuarios adicionales', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $empresa = empresaOperativaDe(User::factory()->create(['role' => 'empresa']));
    User::factory()->count(Empresa::MAX_USUARIOS_ADICIONALES)->create(['role' => 'empresa', 'empresa_id' => $empresa->id]);
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirCambioRol', $admin->id)
        ->set('rolNuevo', AdminUsuarios::ADMIN_EMPRESA)
        ->set('empresaAsociadaId', (string) $empresa->id)
        ->call('cambiarRol')
        ->assertHasErrors('empresaAsociadaId');

    expect($admin->fresh()->empresa_id)->toBeNull();
});

test('al dueño de una empresa no se le toca la membresía al cambiarle el rol', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $duena = User::factory()->create(['role' => 'empresa']);
    $empresa = empresaOperativaDe($duena);
    $membresiaOriginal = $duena->fresh()->empresa_id;

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirCambioRol', $duena->id)
        ->assertSet('empresaPropia', $empresa->razon_social)
        ->set('rolNuevo', 'admin')
        ->call('cambiarRol');

    $duena->refresh();
    expect($duena->role)->toBe('admin')
        ->and($duena->empresa_id)->toBe($membresiaOriginal)
        ->and($duena->esEmpresa())->toBeTrue();
});

test('el superadmin crea un admin que además opera una empresa nueva', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->set('nuevoRol', AdminUsuarios::ADMIN_EMPRESA)
        ->set('nuevoNombres', 'Ana')
        ->set('nuevoApellidos', 'Soto')
        ->set('nuevoEmail', 'ana@ad50.cl')
        ->set('nuevoPassword', 'secreto123')
        ->set('nuevaRazonSocial', 'Ana Consultores SpA')
        ->set('nuevoRut', '76.123.456-0')
        ->set('nuevoTelefono', '+56 9 1111 2222')
        ->call('crearUsuario')
        ->assertHasNoErrors();

    $ana = User::query()->where('email', 'ana@ad50.cl')->firstOrFail();
    expect($ana->role)->toBe('admin')
        ->and($ana->empresa?->razon_social)->toBe('Ana Consultores SpA')
        ->and($ana->esEmpresa())->toBeTrue();
});

test('Administrador + Empresa exige elegir la empresa', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirCambioRol', $admin->id)
        ->set('rolNuevo', AdminUsuarios::ADMIN_EMPRESA)
        ->call('cambiarRol')
        ->assertHasErrors(['empresaAsociadaId' => 'required']);
});

test('un administrador a secas se crea sin empresa', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->set('nuevoRol', 'admin')
        ->set('nuevoNombres', 'Luis')
        ->set('nuevoApellidos', 'Pérez')
        ->set('nuevoEmail', 'luis@ad50.cl')
        ->set('nuevoPassword', 'secreto123')
        ->call('crearUsuario')
        ->assertHasNoErrors();

    expect(User::query()->where('email', 'luis@ad50.cl')->firstOrFail()->esEmpresa())->toBeFalse();
});

test('al pasar a Administrador + Empresa se puede crear la empresa ahí mismo', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $empresaAnterior = empresaOperativaDe(User::factory()->create(['role' => 'empresa']));
    $admin = User::factory()->create(['role' => 'admin', 'empresa_id' => $empresaAnterior->id]);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirCambioRol', $admin->id)
        ->set('rolNuevo', AdminUsuarios::ADMIN_EMPRESA)
        ->set('empresaAsociadaId', AdminUsuarios::EMPRESA_NUEVA)
        ->set('cambioRazonSocial', 'Nueva Consultora SpA')
        ->set('cambioRut', '76.086.428-5')
        ->set('cambioTelefono', '+56 9 1234 5678')
        ->call('cambiarRol')
        ->assertHasNoErrors();

    $admin->refresh();
    $empresa = Empresa::query()->where('razon_social', 'Nueva Consultora SpA')->firstOrFail();

    expect($admin->role)->toBe('admin')
        ->and($empresa->user_id)->toBe($admin->id)
        ->and($empresa->estado_activacion)->toBe('activa')
        ->and($admin->empresa_id)->toBe($empresa->id)
        ->and($admin->esPrincipalEmpresa())->toBeTrue();
});

test('crear la empresa al cambiar el tipo exige sus antecedentes', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirCambioRol', $admin->id)
        ->set('rolNuevo', AdminUsuarios::ADMIN_EMPRESA)
        ->set('empresaAsociadaId', AdminUsuarios::EMPRESA_NUEVA)
        ->set('cambioRut', '11.111.111-2')
        ->call('cambiarRol')
        ->assertHasErrors(['cambioRazonSocial', 'cambioRut', 'cambioTelefono'])
        ->assertHasNoErrors('empresaAsociadaId');

    expect(Empresa::query()->where('user_id', $admin->id)->exists())->toBeFalse();
});

test('las empresas con la misma razón social se distinguen en el selector', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $una = empresaOperativaDe(User::factory()->create(['role' => 'empresa', 'email' => 'una@test.cl']), 'Repetida SpA');
    $otra = empresaOperativaDe(User::factory()->create(['role' => 'empresa', 'email' => 'otra@test.cl']), 'Repetida SpA');

    expect($una->etiquetaParaSelector())->toBe('Repetida SpA · 76.123.456-7 · una@test.cl')
        ->and($otra->etiquetaParaSelector())->toContain('otra@test.cl');

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->set('nuevoRol', 'empresa')
        ->assertSee('Repetida SpA · 76.123.456-7 · una@test.cl')
        ->assertSee('Repetida SpA · 76.123.456-7 · otra@test.cl');
});

test('el admin con empresa entra por el login general a su panel de empresa', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    empresaOperativaDe($admin);

    $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('empresa.panel', absolute: false));
});

test('el admin con empresa que entra por /admin llega a la administración', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    empresaOperativaDe($admin);

    $this->get(route('admin.panel'))->assertRedirect(route('login'));

    $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('admin.panel'));
});

test('el admin sin empresa sigue entrando a la administración por el login general', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('admin.panel', absolute: false));
});
