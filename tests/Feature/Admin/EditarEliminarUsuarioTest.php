<?php

use App\Livewire\Admin\Usuarios as AdminUsuarios;
use App\Models\Busqueda;
use App\Models\Empresa;
use App\Models\Pago;
use App\Models\Plan;
use App\Models\Postulante;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

/**
 * Edición y borrado de cuentas desde la pantalla de Usuarios del superadministrador.
 */
function empresaConDuenio(string $razon = 'Retail Andes SpA'): array
{
    $duenio = User::factory()->create(['role' => 'empresa', 'name' => 'Carlos Vega']);
    $empresa = Empresa::query()->create([
        'user_id' => $duenio->id,
        'razon_social' => $razon,
        'estado_activacion' => 'activa',
        'datos_enviados_at' => now(),
    ]);
    $duenio->update(['empresa_id' => $empresa->id]);

    return [$duenio, $empresa];
}

// --- Edición -----------------------------------------------------------------

test('the superadmin edits name, email and password of another account', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $usuario = User::factory()->create(['name' => 'Marta Rojas', 'email' => 'vieja@correo.cl']);
    $passwordOriginal = $usuario->password;

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirEdicionDatos', $usuario->id)
        ->assertSet('editEmail', 'vieja@correo.cl')
        ->set('editNombres', 'Marta')
        ->set('editApellidos', 'Rojas Contreras')
        ->set('editEmail', 'nueva@correo.cl')
        ->set('editPassword', 'clave-larga-123')
        ->call('guardarDatos')
        ->assertHasNoErrors();

    $usuario->refresh();

    expect($usuario->name)->toBe('Marta Rojas Contreras')
        ->and($usuario->nombres)->toBe('Marta')
        ->and($usuario->apellidos)->toBe('Rojas Contreras')
        ->and($usuario->email)->toBe('nueva@correo.cl')
        ->and($usuario->password)->not->toBe($passwordOriginal)
        ->and(Hash::check('clave-larga-123', $usuario->password))->toBeTrue();
});

test('leaving the password blank keeps the current one', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $usuario = User::factory()->create(['email' => 'marta@correo.cl']);
    $passwordOriginal = $usuario->password;

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirEdicionDatos', $usuario->id)
        ->set('editNombres', 'Marta')
        ->set('editApellidos', 'Rojas')
        // editPassword se queda vacío a propósito.
        ->call('guardarDatos')
        ->assertHasNoErrors();

    expect($usuario->fresh()->password)->toBe($passwordOriginal);
});

test('changing the email leaves the account unverified again', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $usuario = User::factory()->create(['email' => 'vieja@correo.cl']);

    expect($usuario->hasVerifiedEmail())->toBeTrue();

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirEdicionDatos', $usuario->id)
        ->set('editNombres', 'Marta')
        ->set('editApellidos', 'Rojas')
        ->set('editEmail', 'nueva@correo.cl')
        ->call('guardarDatos')
        ->assertHasNoErrors();

    // Nadie ha demostrado controlar la dirección nueva.
    expect($usuario->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('editing without touching the email keeps the verification', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $usuario = User::factory()->create(['email' => 'marta@correo.cl']);
    $verificadoOriginal = $usuario->email_verified_at;

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirEdicionDatos', $usuario->id)
        ->set('editNombres', 'Marta')
        ->set('editApellidos', 'Rojas')
        ->call('guardarDatos')
        ->assertHasNoErrors();

    expect($usuario->fresh()->email_verified_at->timestamp)->toBe($verificadoOriginal->timestamp);
});

test('the edited email cannot collide with another account, and a short password is rejected', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    User::factory()->create(['email' => 'ocupado@correo.cl']);
    $usuario = User::factory()->create(['email' => 'marta@correo.cl']);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirEdicionDatos', $usuario->id)
        ->set('editNombres', 'Marta')
        ->set('editEmail', 'ocupado@correo.cl')
        ->set('editPassword', 'corta')
        ->call('guardarDatos')
        ->assertHasErrors(['editEmail', 'editPassword']);

    expect($usuario->fresh()->email)->toBe('marta@correo.cl');
});

test('keeping its own email is not a collision with itself', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $usuario = User::factory()->create(['email' => 'marta@correo.cl']);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirEdicionDatos', $usuario->id)
        ->set('editNombres', 'Marta')
        ->set('editApellidos', 'Rojas')
        ->call('guardarDatos')
        ->assertHasNoErrors();
});

test('the superadmin cannot edit its own account from here', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin', 'email' => 'jefe@ad50.cl']);

    // Su cuenta se gestiona en Mi cuenta, donde cambiar la clave pide la actual.
    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirEdicionDatos', $superadmin->id)
        ->assertForbidden();
});

test('a plain admin cannot edit accounts', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(AdminUsuarios::class)
        ->assertForbidden();
});

// --- Borrado -----------------------------------------------------------------

test('the superadmin deletes a postulante account and its ficha goes with it', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $usuario = User::factory()->create(['role' => 'postulante', 'name' => 'Marta Rojas']);
    $ficha = Postulante::query()->create(['user_id' => $usuario->id]);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirEliminar', $usuario->id)
        ->set('confirmacionTexto', 'ELIMINAR')
        ->call('eliminar')
        ->assertHasNoErrors();

    expect(User::query()->find($usuario->id))->toBeNull()
        ->and(Postulante::query()->find($ficha->id))->toBeNull();
});

test('deleting requires typing ELIMINAR', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $usuario = User::factory()->create();

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirEliminar', $usuario->id)
        ->set('confirmacionTexto', 'si')
        ->call('eliminar')
        ->assertHasErrors('confirmacionTexto');

    expect(User::query()->find($usuario->id))->not->toBeNull();
});

test('the superadmin cannot delete its own account', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirEliminar', $superadmin->id)
        ->assertForbidden();

    expect(User::query()->find($superadmin->id))->not->toBeNull();
});

test('the confirmation spells out that deleting an owner takes the whole company', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    [$duenio, $empresa] = empresaConDuenio('Retail Andes SpA');
    Busqueda::query()->create(['empresa_id' => $empresa->id, 'titulo' => 'Jefe de Operaciones']);

    $componente = Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirEliminar', $duenio->id);

    $arrastra = implode(' ', $componente->get('eliminandoArrastra'));

    expect($arrastra)->toContain('Retail Andes SpA')
        ->toContain('1 búsqueda');
});

test('deleting the only contact of a company deletes the company with it', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    [$duenio, $empresa] = empresaConDuenio();
    Busqueda::query()->create(['empresa_id' => $empresa->id, 'titulo' => 'Jefe de Operaciones']);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirEliminar', $duenio->id)
        ->set('confirmacionTexto', 'ELIMINAR')
        ->call('eliminar');

    expect(User::query()->find($duenio->id))->toBeNull()
        ->and(Empresa::query()->find($empresa->id))->toBeNull()
        ->and(Busqueda::query()->where('empresa_id', $empresa->id)->count())->toBe(0);
});

test('a company with another member survives: it changes hands instead of dying', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    [$duenio, $empresa] = empresaConDuenio();

    $companiero = User::factory()->create([
        'role' => 'empresa',
        'name' => 'Paula Díaz',
        'empresa_id' => $empresa->id,
    ]);

    $componente = Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirEliminar', $duenio->id)
        ->assertSet('eliminandoTraspaso', 'Paula Díaz');

    $componente->set('confirmacionTexto', 'ELIMINAR')->call('eliminar');

    expect(User::query()->find($duenio->id))->toBeNull()
        // La empresa no se va con él: pasa a manos del compañero.
        ->and(Empresa::query()->find($empresa->id))->not->toBeNull()
        ->and($empresa->fresh()->user_id)->toBe($companiero->id)
        ->and(User::query()->find($companiero->id))->not->toBeNull();
});

test('a lone owner is deleted along with the company and its payment history', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    [$duenio, $empresa] = empresaConDuenio('Retail Andes SpA');

    $plan = Plan::query()->create([
        'codigo' => 'empresa_pago_'.str()->random(6),
        'nombre' => 'AD+50 · Pro',
        'audiencia' => 'empresa',
        'precio_clp' => 0,
        'precio_uf' => '30.00',
        'periodo' => 'mensual',
        'desbloqueos' => 10,
    ]);

    Pago::query()->create([
        'empresa_id' => $empresa->id,
        'plan_id' => $plan->id,
        'commerce_order' => 'AD50-PAGADO',
        'amount' => 1392300,
        'estado' => 'pagado',
        'pagado_at' => now(),
    ]);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirEliminar', $duenio->id)
        // Nada bloquea el borrado, pero los pagos que se pierden se anuncian aparte.
        ->assertSet('eliminandoPagosConfirmados', 1)
        ->assertSee('1 pago confirmado')
        ->set('confirmacionTexto', 'ELIMINAR')
        ->call('eliminar')
        ->assertHasNoErrors();

    expect(User::query()->find($duenio->id))->toBeNull()
        ->and(Empresa::query()->find($empresa->id))->toBeNull()
        // El historial de cobros se va con la empresa, que es lo pedido.
        ->and(Pago::query()->count())->toBe(0);
});

test('no payment warning is shown when the company survives the deletion', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    [$duenio, $empresa] = empresaConDuenio();
    $plan = Plan::query()->create([
        'codigo' => 'empresa_pago_'.str()->random(6),
        'nombre' => 'AD+50 · Pro', 'audiencia' => 'empresa',
        'precio_clp' => 0, 'precio_uf' => '30.00', 'periodo' => 'mensual', 'desbloqueos' => 10,
    ]);
    Pago::query()->create([
        'empresa_id' => $empresa->id, 'plan_id' => $plan->id,
        'commerce_order' => 'AD50-VIVE', 'amount' => 1000, 'estado' => 'pagado', 'pagado_at' => now(),
    ]);

    User::factory()->create(['role' => 'empresa', 'name' => 'Paula Díaz', 'empresa_id' => $empresa->id]);

    // Con heredero la empresa no se va, así que tampoco se van sus pagos: avisar de
    // una pérdida que no ocurre solo entrena a ignorar el aviso.
    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirEliminar', $duenio->id)
        ->assertSet('eliminandoPagosConfirmados', 0)
        ->assertDontSee('pago confirmado');
});

test('a paid company can lose its owner once someone else can take over', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    [$duenio, $empresa] = empresaConDuenio();
    $plan = Plan::query()->create([
        'codigo' => 'empresa_pago_'.str()->random(6),
        'nombre' => 'AD+50 · Pro', 'audiencia' => 'empresa',
        'precio_clp' => 0, 'precio_uf' => '30.00', 'periodo' => 'mensual', 'desbloqueos' => 10,
    ]);
    Pago::query()->create([
        'empresa_id' => $empresa->id, 'plan_id' => $plan->id,
        'commerce_order' => 'AD50-PAGADO2', 'amount' => 1000, 'estado' => 'pagado', 'pagado_at' => now(),
    ]);

    User::factory()->create(['role' => 'empresa', 'name' => 'Paula Díaz', 'empresa_id' => $empresa->id]);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->call('abrirEliminar', $duenio->id)
        ->set('confirmacionTexto', 'ELIMINAR')
        ->call('eliminar');

    // El historial de pagos se conserva porque la empresa se conserva.
    expect(User::query()->find($duenio->id))->toBeNull()
        ->and(Pago::query()->count())->toBe(1)
        ->and(Empresa::query()->find($empresa->id))->not->toBeNull();
});

test('the row actions carry an accessible name', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    User::factory()->create(['name' => 'Marta Rojas']);

    Livewire::actingAs($superadmin)
        ->test(AdminUsuarios::class)
        ->assertSeeHtml('aria-label="Editar los datos de Marta Rojas"')
        ->assertSeeHtml('aria-label="Eliminar la cuenta de Marta Rojas"');
});
