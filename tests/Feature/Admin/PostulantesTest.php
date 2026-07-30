<?php

use App\Livewire\Admin\Postulantes;
use App\Models\Postulante;
use App\Models\User;
use Livewire\Livewire;

function postulanteDe(string $nombre, string $email, array $atributos = []): Postulante
{
    $user = User::factory()->create(['role' => 'postulante', 'name' => $nombre, 'email' => $email]);

    return Postulante::query()->create([...['user_id' => $user->id, 'visible' => true], ...$atributos]);
}

test('el admin ve todos los postulantes de la plataforma', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    postulanteDe('Ana Rojas', 'ana@ejemplo.cl', ['cargo_actual' => 'Jefa de Finanzas']);
    postulanteDe('Luis Soto', 'luis@ejemplo.cl', ['visible' => false]);

    Livewire::actingAs($admin)
        ->test(Postulantes::class)
        ->assertViewHas('totalPostulantes', 2)
        ->assertViewHas('totalVisibles', 1)
        ->assertSee('Ana Rojas')
        ->assertSee('Jefa de Finanzas')
        ->assertSee('Luis Soto')
        ->assertSee('Pausado');
});

test('se puede buscar por nombre o correo', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    postulanteDe('Ana Rojas', 'ana@ejemplo.cl');
    postulanteDe('Luis Soto', 'luis@otrodominio.cl');

    $componente = Livewire::actingAs($admin)->test(Postulantes::class);

    $componente->set('buscar', 'Rojas')->assertSee('Ana Rojas')->assertDontSee('Luis Soto');
    $componente->set('buscar', 'otrodominio')->assertSee('Luis Soto')->assertDontSee('Ana Rojas');
});

test('los filtros de visibilidad y de ficha acotan el listado', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    postulanteDe('Visible Completa', 'v@ejemplo.cl', ['visible' => true, 'onboarding_completado' => true]);
    postulanteDe('Pausado Incompleto', 'p@ejemplo.cl', ['visible' => false, 'onboarding_completado' => false]);

    $componente = Livewire::actingAs($admin)->test(Postulantes::class);

    $componente->set('visibilidad', 'visibles')->assertSee('Visible Completa')->assertDontSee('Pausado Incompleto');
    $componente->call('limpiarFiltros')->set('onboarding', 'incompleto')
        ->assertSee('Pausado Incompleto')->assertDontSee('Visible Completa');
});

test('el listado se ordena por sus columnas', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    postulanteDe('Poca', 'poca@ejemplo.cl', ['anios_experiencia' => 3]);
    postulanteDe('Mucha', 'mucha@ejemplo.cl', ['anios_experiencia' => 30]);

    $nombres = fn ($c): array => $c->viewData('postulantes')->pluck('user.name')->all();
    $componente = Livewire::actingAs($admin)->test(Postulantes::class);

    // La experiencia parte descendente: primero quien tiene más.
    $componente->call('ordenarPor', 'anios_experiencia');
    expect($nombres($componente))->toBe(['Mucha', 'Poca']);

    $componente->call('ordenarPor', 'anios_experiencia');
    expect($nombres($componente))->toBe(['Poca', 'Mucha']);
});

test('el menú de admin aparece en todas sus vistas, incluidas las de cuenta', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    foreach ([route('admin.panel'), route('admin.empresas'), route('admin.postulantes'), route('profile.edit'), route('appearance.edit')] as $url) {
        $this->actingAs($admin)->get($url)
            ->assertOk()
            ->assertSee('href="'.route('admin.panel').'"', false)
            ->assertSee('href="'.route('admin.empresas').'"', false)
            ->assertSee('href="'.route('admin.postulantes').'"', false);
    }
});

test('las pantallas de cuenta rotulan al admin como Administrador', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    // Antes heredaban el rótulo por defecto y decían "Postulante".
    $this->actingAs($admin)->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Administrador')
        ->assertDontSee('>Postulante<', false);
});

test('un usuario que no es admin no entra al listado', function () {
    foreach (['postulante', 'empresa'] as $rol) {
        $this->actingAs(User::factory()->create(['role' => $rol]))
            ->get(route('admin.postulantes'))
            ->assertForbidden();
    }
});
