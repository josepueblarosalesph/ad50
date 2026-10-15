<?php

use App\Livewire\Admin\Postulantes as AdminPostulantes;
use App\Models\Postulante;
use App\Models\User;
use Livewire\Livewire;

test('the postulantes listing shows each phone number as a tel link', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));

    Postulante::factory()->for(User::factory()->create(['role' => 'postulante']))->create(['telefono' => '+56 9 1234 5678']);

    Livewire::test(AdminPostulantes::class)
        ->assertSee('Teléfono')
        ->assertSee('+56 9 1234 5678')
        ->assertSeeHtml('href="tel:+56912345678"');
});

test('a postulante without a phone shows a dash', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));

    Postulante::factory()->for(User::factory()->create(['role' => 'postulante']))->create(['telefono' => null]);

    Livewire::test(AdminPostulantes::class)
        ->assertDontSeeHtml('href="tel:');
});
