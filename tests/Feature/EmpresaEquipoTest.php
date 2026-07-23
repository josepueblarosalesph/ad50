<?php

use App\Livewire\Empresa\Equipo;
use App\Models\Empresa;
use App\Models\User;
use Livewire\Livewire;

/**
 * Crea un contacto principal (dueño) con su empresa activa.
 *
 * @return array{0: User, 1: Empresa}
 */
function crearEmpresaConPrincipal(): array
{
    $principal = User::factory()->create(['role' => 'empresa', 'email' => 'ana@empresa.cl']);
    $empresa = Empresa::query()->create([
        'user_id' => $principal->id,
        'razon_social' => 'Empresa Equipo SpA',
        'estado_activacion' => 'activa',
    ]);
    $principal->update(['empresa_id' => $empresa->id]);

    return [$principal->fresh(), $empresa->fresh()];
}

test('el contacto principal puede agregar usuarios adicionales de a uno', function () {
    [$principal, $empresa] = crearEmpresaConPrincipal();

    Livewire::actingAs($principal)
        ->test(Equipo::class)
        ->set('nombre', 'Carlos')
        ->set('apellidos', 'Reyes')
        ->set('email', 'carlos@empresa.cl')
        ->set('password', 'secreto123')
        ->call('agregar')
        ->assertHasNoErrors();

    $nuevo = User::query()->where('email', 'carlos@empresa.cl')->first();

    expect($nuevo)->not->toBeNull()
        ->and($nuevo->role)->toBe('empresa')
        ->and($nuevo->empresa_id)->toBe($empresa->id)
        ->and($nuevo->esPrincipalEmpresa())->toBeFalse()
        ->and($empresa->usuariosAdicionales()->count())->toBe(1);
});

test('no se pueden agregar más de tres usuarios adicionales', function () {
    [$principal, $empresa] = crearEmpresaConPrincipal();

    foreach (['a', 'b', 'c'] as $i) {
        User::factory()->create(['role' => 'empresa', 'empresa_id' => $empresa->id, 'email' => "u{$i}@empresa.cl"]);
    }

    expect($empresa->fresh()->puedeAgregarUsuario())->toBeFalse();

    Livewire::actingAs($principal)
        ->test(Equipo::class)
        ->set('nombre', 'Cuarto')
        ->set('apellidos', 'Usuario')
        ->set('email', 'cuarto@empresa.cl')
        ->set('password', 'secreto123')
        ->call('agregar')
        ->assertHasErrors('email');

    expect(User::query()->where('email', 'cuarto@empresa.cl')->exists())->toBeFalse()
        ->and($empresa->usuariosAdicionales()->count())->toBe(3);
});

test('un usuario adicional accede al panel pero no gestiona el equipo', function () {
    [$principal, $empresa] = crearEmpresaConPrincipal();
    $adicional = User::factory()->create(['role' => 'empresa', 'empresa_id' => $empresa->id]);

    // Accede al panel de empresa (empresa activa vía empresa_id).
    $this->actingAs($adicional)
        ->get(route('empresa.panel'))
        ->assertOk();

    // No puede entrar a la gestión de equipo.
    $this->actingAs($adicional)
        ->get(route('empresa.equipo'))
        ->assertForbidden();
});

test('el principal puede eliminar un usuario adicional pero nunca a sí mismo', function () {
    [$principal, $empresa] = crearEmpresaConPrincipal();
    $adicional = User::factory()->create(['role' => 'empresa', 'empresa_id' => $empresa->id]);

    Livewire::actingAs($principal)
        ->test(Equipo::class)
        ->call('eliminar', $adicional->id)
        ->assertHasNoErrors();

    expect(User::query()->whereKey($adicional->id)->exists())->toBeFalse();

    // Intentar eliminar al principal no lo borra.
    Livewire::actingAs($principal)
        ->test(Equipo::class)
        ->call('eliminar', $principal->id);

    expect(User::query()->whereKey($principal->id)->exists())->toBeTrue();
});
