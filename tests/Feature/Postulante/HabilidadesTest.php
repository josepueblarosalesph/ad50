<?php

use App\Livewire\Postulante\Ficha;
use App\Models\Postulante;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function fichaConHabilidades(): Testable
{
    $user = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create(['user_id' => $user->id, 'onboarding_completado' => true]);

    return Livewire::actingAs($user)->test(Ficha::class);
}

test('la búsqueda sugiere habilidades del catálogo sin distinguir mayúsculas ni acentos', function () {
    fichaConHabilidades()
        ->set('buscarHabilidad', 'photosh')
        ->call('habilidadesSugeridas')
        ->assertReturned(fn (array $s) => in_array('Adobe Photoshop', $s, true))
        ->set('buscarHabilidad', 'liderazg')
        ->call('habilidadesSugeridas')
        ->assertReturned(fn (array $s) => in_array('Liderazgo', $s, true));
});

test('con menos de dos caracteres no se sugiere nada', function () {
    fichaConHabilidades()
        ->set('buscarHabilidad', 'a')
        ->call('habilidadesSugeridas')
        ->assertReturned(fn (array $s) => $s === []);
});

test('agregar una habilidad del catálogo la selecciona y limpia la búsqueda', function () {
    fichaConHabilidades()
        ->set('buscarHabilidad', 'excel')
        ->call('agregarHabilidad', 'Microsoft Excel')
        ->assertSet('habilidades', ['Microsoft Excel'])
        ->assertSet('buscarHabilidad', '');
});

test('no se agrega una habilidad fuera del catálogo', function () {
    fichaConHabilidades()
        ->call('agregarHabilidad', 'Habilidad Inventada Que No Existe')
        ->assertSet('habilidades', []);
});

test('no se agrega una habilidad ya seleccionada', function () {
    fichaConHabilidades()
        ->call('agregarHabilidad', 'Python')
        ->call('agregarHabilidad', 'Python')
        ->assertSet('habilidades', ['Python']);
});

test('quitar una habilidad la remueve y reindexa', function () {
    fichaConHabilidades()
        ->call('agregarHabilidad', 'Python')
        ->call('agregarHabilidad', 'Java')
        ->call('quitarHabilidad', 0)
        ->assertSet('habilidades', ['Java']);
});
