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
        // Su identidad principal pasa a ser la de admin: ahí lo deja el login.
        ->and($contacto->dashboardRouteName())->toBe('admin.panel');

    $this->actingAs($contacto)->get(route('empresa.panel'))->assertOk();
    $this->actingAs($contacto)->get(route('admin.panel'))->assertOk();
});
