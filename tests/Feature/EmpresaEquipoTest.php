<?php

use App\Livewire\Empresa\Equipo;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

/**
 * Crea un contacto administrador con su empresa activa.
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
    hacerEmpresaOperativa($empresa);

    return [$principal->fresh(), $empresa->fresh()];
}

test('el contacto administrador puede agregar contactos usuarios de a uno', function () {
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

test('el menú del panel muestra Equipo solo al contacto administrador', function () {
    [$principal, $empresa] = crearEmpresaConPrincipal();
    $adicional = User::factory()->create([
        'role' => 'empresa',
        'empresa_id' => $empresa->id,
        'email' => 'sin-permisos@empresa.cl',
    ]);

    $vistas = [
        route('empresa.panel'),
        route('empresa.busquedas.index'),
        route('empresa.publicaciones.index'),
        route('empresa.planes'),
    ];

    foreach ($vistas as $url) {
        // El menú es el mismo componente en todas las vistas: siempre lleva las
        // secciones comunes y solo el principal ve el enlace a Equipo.
        $this->actingAs($principal)->get($url)
            ->assertOk()
            ->assertSee('href="'.route('empresa.busquedas.index').'"', false)
            ->assertSee('href="'.route('empresa.publicaciones.index').'"', false)
            ->assertSee('href="'.route('empresa.equipo').'"', false);

        $this->actingAs($adicional)->get($url)
            ->assertOk()
            ->assertSee('href="'.route('empresa.publicaciones.index').'"', false)
            ->assertDontSee('href="'.route('empresa.equipo').'"', false);
    }
});

test('el menú resalta la sección que se está viendo y no las demás', function () {
    [$principal] = crearEmpresaConPrincipal();
    $this->actingAs($principal);

    $html = Blade::render('<x-nav-empresa activo="publicaciones" />');

    // Solo hay una sección marcada como actual, y es la publicada.
    expect(substr_count($html, 'aria-current="page"'))->toBe(1);

    $activo = collect(explode('<a', $html))
        ->first(fn (string $enlace): bool => str_contains($enlace, 'aria-current="page"'));

    expect($activo)->toContain(route('empresa.publicaciones.index'))
        ->toContain('bg-orange-100');
});

test('sin sección activa el menú no marca ninguna', function () {
    [$principal] = crearEmpresaConPrincipal();
    $this->actingAs($principal);

    expect(Blade::render('<x-nav-empresa />'))->not->toContain('aria-current="page"');
});
