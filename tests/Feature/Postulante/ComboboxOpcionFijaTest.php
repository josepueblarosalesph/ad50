<?php

use App\Livewire\Postulante\Ficha;
use App\Models\Postulante;
use App\Models\User;
use Livewire\Livewire;

/** Postulante detenido en el paso 3 del onboarding, que es el de experiencia laboral. */
function postulanteEnPasoDeExperiencia(): User
{
    $user = User::factory()->create(['role' => 'postulante']);

    Postulante::query()->create([
        'user_id' => $user->id,
        'onboarding_paso' => 3,
        'onboarding_completado' => false,
    ]);

    return $user;
}

test('el combobox ofrece la opción de escape fija cuando se le pasa', function () {
    $vista = $this->blade(
        '<x-combobox model="cargo" label="Cargo" catalogo="cargo" fijo="Otros" fijo-ayuda="Mi cargo no está en la lista" />'
    );

    // Se dibuja aparte del x-for, así que sobrevive a que no haya ninguna coincidencia.
    $vista->assertSee('elegir(fijo)', false);
    $vista->assertSee("fijo: 'Otros'", false);
    $vista->assertSee('Mi cargo no está en la lista');
});

test('sin la opción fija el combobox queda como estaba', function () {
    $vista = $this->blade('<x-combobox model="carrera" label="Carrera" catalogo="carrera" />');

    $vista->assertDontSee('elegir(fijo)', false);
    $vista->assertSee('fijo: null', false);
});

test('la opción fija se excluye de las coincidencias para no listarla dos veces', function () {
    $vista = $this->blade('<x-combobox model="cargo" label="Cargo" catalogo="cargo" fijo="Otros" />');

    // «Otros» es el primer valor del catálogo de cargos: sin este filtro aparecería
    // dentro de la lista y otra vez al pie.
    $vista->assertSee('coincidencias.filter((opcion) => opcion !== this.fijo)', false);
});

test('el formulario de experiencia fija Otros en cargo y Otra en empresa', function () {
    $user = postulanteEnPasoDeExperiencia();

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->assertSet('pasoActual', 3)
        // Son los dos campos que tienen debajo un cuadro de texto que recoge el valor;
        // fijar la opción donde no lo hay dejaría elegir algo que nadie guarda.
        ->assertSee('Mi cargo no está en la lista')
        ->assertSee('Mi empresa no está en la lista')
        ->assertSee("fijo: 'Otros'", false)
        ->assertSee("fijo: 'Otra'", false);
});

test('elegir Otros abre el cuadro de texto para escribir el cargo', function () {
    $user = postulanteEnPasoDeExperiencia();

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->assertDontSee('Especifica el cargo u ocupación')
        ->set('experiencias.0.cargo', 'Otros')
        ->assertSee('Especifica el cargo u ocupación');
});
