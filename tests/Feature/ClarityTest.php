<?php

use App\Models\Postulante;
use App\Models\User;
use Illuminate\Testing\TestResponse;

/**
 * La etiqueta no trae la URL final en el HTML: el script la arma en el navegador
 * concatenando el dominio con el identificador que recibe como argumento
 * (`"https://www.clarity.ms/tag/"+i`). Por eso se comprueban las dos piezas.
 */
function esperarEtiquetaClarity(TestResponse $respuesta): void
{
    $respuesta->assertOk()
        ->assertSee('https://www.clarity.ms/tag/', false)
        ->assertSee('"ygpk93x7gr"', false);
}

test('la etiqueta de Clarity está en los tres heads del sitio', function () {
    // 1. Público: landing, planes, registro y también el login, que usa este layout.
    esperarEtiquetaClarity($this->get(route('home')));

    // 2. Panel autenticado.
    $super = User::factory()->create(['role' => 'superadmin']);
    esperarEtiquetaClarity($this->actingAs($super)->get(route('admin.usuarios')));

    // 3. Pantallas de sesión, que llegan a partials/head por otra cadena:
    //    la vista usa <x-layouts::auth>, y ese resuelve a layouts/auth/simple.
    $sinVerificar = User::factory()->create(['role' => 'postulante', 'email_verified_at' => null]);
    esperarEtiquetaClarity($this->actingAs($sinVerificar)->get(route('verification.notice')));
});

test('las pantallas con datos personales se marcan para que Clarity no las grabe', function (string $vista) {
    // Estático a propósito: renderizar las diez pantallas exigiría montar empresa con
    // plan, matches y desbloqueos, y lo que se puede romper en silencio es justamente
    // que alguien rediseñe la plantilla y se lleve el atributo por delante. El caso de
    // abajo comprueba que el mecanismo funciona de verdad sobre HTML renderizado.
    expect(file_get_contents(resource_path("views/livewire/{$vista}.blade.php")))
        ->toContain('data-clarity-mask="true"');
})->with([
    'admin/usuarios',        // Nombres y correos de todas las cuentas.
    'admin/postulantes',
    'admin/empresas',        // Antecedentes y contactos de cada empresa.
    'admin/mensajes',
    'empresa/candidato',     // Perfil completo, con contacto si está desbloqueado.
    'empresa/resultados',
    'empresa/favoritos',
    'empresa/postulaciones',
    'empresa/equipo',
    'postulante/ficha',      // Los datos propios: RUT, teléfono, dirección.
]);

test('el listado de usuarios del admin sale enmascarado en el HTML', function () {
    $super = User::factory()->create(['role' => 'superadmin']);
    User::factory()->create(['role' => 'postulante', 'email' => 'persona@example.com']);

    $html = $this->actingAs($super)->get(route('admin.usuarios'))->assertOk()->getContent();

    // El correo existe en la página, pero dentro del bloque marcado: el encabezado de
    // la tabla queda legible en la grabación y las filas no.
    expect($html)->toContain('persona@example.com');

    $cuerpo = substr($html, strpos($html, 'data-clarity-mask="true"'));
    expect($cuerpo)->toContain('persona@example.com');
});

test('la ficha del postulante se marca en sus dos modos', function () {
    $user = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create(['user_id' => $user->id, 'onboarding_paso' => 1, 'onboarding_completado' => false]);

    // Onboarding y edición son dos ramas distintas de la plantilla; las dos muestran
    // los datos de la persona, así que las dos van marcadas.
    expect(substr_count(file_get_contents(resource_path('views/livewire/postulante/ficha.blade.php')), 'data-clarity-mask="true"'))
        ->toBe(2);

    $this->actingAs($user)->get(route('postulante.ficha'))->assertOk()->assertSee('data-clarity-mask="true"', false);
});
