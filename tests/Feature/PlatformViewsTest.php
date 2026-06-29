<?php

use App\Livewire\Empresa\NuevaBusqueda;
use App\Livewire\Postulante\Ficha;
use App\Models\Busqueda;
use App\Models\BusquedaCandidato;
use App\Models\Empresa;
use App\Models\Plan;
use App\Models\Postulante;
use App\Models\User;
use Livewire\Livewire;

test('the landing page presents the experience-led visual direction', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSeeText('La experiencia no se archiva. Se activa.')
        ->assertSee('Una tarjeta que explica el match.')
        ->assertSee('/images/ad50-logo.png', false)
        ->assertSee('/images/ad50-hero-experiencia.webp', false);
});

test('the plans page can be viewed', function () {
    Plan::query()->create([
        'codigo' => 'empresa_basic',
        'nombre' => 'Empresa · Básico',
        'audiencia' => 'empresa',
        'precio_clp' => 89000,
        'features' => ['Una búsqueda activa'],
    ]);

    $this->get(route('planes'))
        ->assertOk()
        ->assertSee('Elige el alcance de tu búsqueda');
});

test('a postulante can view the panel and professional profile', function () {
    $user = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create(['user_id' => $user->id, 'completitud' => 72]);

    $this->actingAs($user)->get(route('postulante.panel'))
        ->assertOk()
        ->assertSee('Así se ve tu presencia');

    $this->actingAs($user)->get(route('postulante.ficha'))
        ->assertOk()
        ->assertSee('Mi ficha profesional');
});

test('a postulante can update every section of the professional profile', function () {
    $user = User::factory()->create([
        'name' => 'Nombre Anterior',
        'email' => 'anterior@example.com',
        'role' => 'postulante',
    ]);
    Postulante::query()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->set('name', 'María José Fuentes')
        ->set('email', 'maria.fuentes@example.com')
        ->set('rut', '9.842.115-6')
        ->set('anioNacimiento', 1971)
        ->set('telefono', '+56 9 5555 1234')
        ->set('linkedin', 'https://linkedin.com/in/maria-fuentes')
        ->set('ciudad', 'Concepción')
        ->set('carrera', 'Ingeniería Comercial')
        ->set('universidad', 'Universidad de Concepción')
        ->set('especialidad', 'Finanzas')
        ->set('postgrado', 'MBA')
        ->set('industria', 'Banca')
        ->set('industria2', 'Forestal')
        ->set('industria3', 'Manufactura')
        ->set('cargoActual', 'Subgerente de Finanzas')
        ->set('empresaActual', 'Empresa de Prueba SpA')
        ->set('experienciaArea', 'Finanzas')
        ->set('experienciaInicio', 2009)
        ->set('experienciaFin', null)
        ->set('aniosExperiencia', 17)
        ->set('resumenProfesional', 'Experiencia liderando equipos financieros.')
        ->set('visible', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('completitud', 100);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'María José Fuentes',
        'email' => 'maria.fuentes@example.com',
    ]);
    $this->assertDatabaseHas('postulantes', [
        'user_id' => $user->id,
        'rut' => '9.842.115-6',
        'carrera' => 'Ingeniería Comercial',
        'universidad' => 'Universidad de Concepción',
        'empresa_actual' => 'Empresa de Prueba SpA',
        'completitud' => 100,
    ]);
});

test('an empresa can view its pages and create a search', function () {
    $user = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => 'Empresa de Prueba SpA',
    ]);
    $postulanteUser = User::factory()->create(['role' => 'postulante']);
    $postulante = Postulante::query()->create([
        'user_id' => $postulanteUser->id,
        'cargo_actual' => 'Gerente de Finanzas',
        'anios_experiencia' => 15,
    ]);
    $busqueda = Busqueda::query()->create([
        'empresa_id' => $empresa->id,
        'titulo' => 'Gerente de Finanzas',
        'criterios' => ['cargo' => 'Finanzas'],
    ]);
    $match = BusquedaCandidato::query()->create([
        'busqueda_id' => $busqueda->id,
        'postulante_id' => $postulante->id,
        'match_score' => 100,
        'criterios_cumplidos' => 3,
        'criterios_totales' => 3,
        'estado_match' => 'cumple',
    ]);

    $this->actingAs($user)->get(route('empresa.panel'))->assertOk();
    $this->actingAs($user)->get(route('empresa.busquedas.create'))->assertOk();
    $this->actingAs($user)->get(route('empresa.resultados', $busqueda))->assertOk();
    $this->actingAs($user)->get(route('empresa.candidatos.show', $match))->assertOk();

    Livewire::actingAs($user)
        ->test(NuevaBusqueda::class)
        ->set('titulo', 'Controller Senior')
        ->set('cargo', 'Controller')
        ->set('industria', 'Manufactura')
        ->set('aniosMinimos', 8)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('busquedas', [
        'empresa_id' => $empresa->id,
        'titulo' => 'Controller Senior',
    ]);
});

test('an admin can view the administration dashboard', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('admin.panel'))
        ->assertOk()
        ->assertSee('Resumen de la plataforma');
});

test('users cannot open dashboards for another role', function () {
    $postulante = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create(['user_id' => $postulante->id]);

    $this->actingAs($postulante)->get(route('empresa.panel'))->assertForbidden();
    $this->actingAs($postulante)->get(route('admin.panel'))->assertForbidden();
});
