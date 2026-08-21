<?php

use App\Models\User;
use App\Support\CatalogosProfesionales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Los comboboxes descargan su catálogo de esta URL en vez de llevarlo dentro del HTML.
 * El de cargos son 30.000 valores: incrustado pesaba 733 KB por instancia y se reenviaba
 * en cada respuesta de Livewire.
 */
it('entrega el catálogo como lista JSON', function (): void {
    $respuesta = $this->actingAs(User::factory()->create())->getJson('/catalogos/cargo');

    $respuesta->assertOk();

    $valores = $respuesta->json();

    expect($valores)->toBeArray()
        ->and($valores)->not->toBeEmpty()
        ->and($valores[0])->toBe(CatalogosProfesionales::cargos()[0])
        ->and(count($valores))->toBe(count(CatalogosProfesionales::cargos()));
});

it('se puede cachear en el navegador', function (): void {
    $respuesta = $this->actingAs(User::factory()->create())->get('/catalogos/institucion');

    $respuesta->assertOk();

    expect($respuesta->headers->get('Cache-Control'))->toContain('max-age')
        ->and($respuesta->headers->get('Cache-Control'))->toContain('public')
        ->and($respuesta->headers->get('ETag'))->not->toBeNull();
});

it('responde 404 a un catálogo que no existe', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/catalogos/inventado')
        ->assertNotFound();
});

it('no se expone a quien no ha iniciado sesión', function (): void {
    $this->get('/catalogos/cargo')->assertRedirect(route('login'));
});

/**
 * La versión va en la URL con que el combobox pide el catálogo: si un admin edita los
 * términos tiene que cambiar, o el navegador seguiría sirviendo su copia vieja.
 */
it('cambia la versión cuando cambia el contenido del catálogo', function (): void {
    $antes = CatalogosProfesionales::version('institucion');

    DB::table('terminos_catalogo')->insert([
        'catalogo' => 'institucion',
        'valor' => 'Universidad Recién Agregada',
        'orden' => 9999,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    CatalogosProfesionales::olvidar('institucion');

    expect(CatalogosProfesionales::version('institucion'))->not->toBe($antes);
});
