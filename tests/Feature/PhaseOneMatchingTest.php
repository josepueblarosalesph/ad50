<?php

use App\Livewire\Empresa\NuevaBusqueda;
use App\Livewire\Postulante\Ficha;
use App\Models\Empresa;
use App\Models\Plan;
use App\Models\Postulante;
use App\Models\User;
use Livewire\Livewire;

test('a structured search ranks complete matches before partial matches and explains every criterion', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    Empresa::query()->create(['user_id' => $empresaUser->id, 'razon_social' => 'Empresa Uno']);

    $completeUser = User::factory()->create(['role' => 'postulante']);
    $complete = Postulante::query()->create([
        'user_id' => $completeUser->id,
        'visible' => true,
        'ciudad' => 'Concepción',
        'carrera' => 'Ingeniería Civil / Ingeniería Comercial',
        'especialidad' => 'Finanzas',
        'industria' => 'Banca y servicios financieros',
        'anios_experiencia' => 18,
        'resumen_profesional' => 'Lideró una transformación financiera regional.',
        'experiencias' => [[
            'cargo' => 'Finanzas', 'empresa' => 'Empresa A', 'area' => 'Finanzas',
            'inicio' => 2008, 'fin' => 2026,
        ]],
    ]);

    $partialUser = User::factory()->create(['role' => 'postulante']);
    $partial = Postulante::query()->create([
        'user_id' => $partialUser->id,
        'visible' => true,
        'ciudad' => 'Santiago',
        'carrera' => 'Ingeniería Civil / Ingeniería Comercial',
        'especialidad' => 'Finanzas',
        'industria' => 'Banca y servicios financieros',
        'anios_experiencia' => 12,
        'experiencias' => [[
            'cargo' => 'Finanzas', 'empresa' => 'Empresa B', 'area' => 'Finanzas',
            'inicio' => 2014, 'fin' => 2026,
        ]],
    ]);

    Livewire::actingAs($empresaUser)
        ->test(NuevaBusqueda::class)
        ->set('titulo', 'Liderazgo financiero')
        ->set('cargo', 'Finanzas')
        ->set('carrera', 'Ingeniería Civil / Ingeniería Comercial')
        ->set('especialidad', 'Finanzas')
        ->set('industria', 'Banca y servicios financieros')
        ->set('ciudad', 'Concepción')
        ->set('aniosMinimos', 15)
        ->set('palabraClave', 'transformación')
        ->call('save')
        ->assertHasNoErrors();

    $busqueda = $empresaUser->empresa->busquedas()->latest('id')->firstOrFail();
    $matches = $busqueda->candidatos()->orderByDesc('criterios_cumplidos')->get();

    expect($matches)->toHaveCount(2)
        ->and($matches->first()->postulante_id)->toBe($complete->id)
        ->and($matches->first()->estado_match)->toBe('cumple')
        ->and($matches->first()->criterios_cumplidos)->toBe(7)
        ->and($matches->first()->criterios_detalle)->toHaveCount(7)
        ->and($matches->last()->postulante_id)->toBe($partial->id)
        ->and($matches->last()->estado_match)->toBe('parcial');
});

test('a postulante can add and remove multiple work experiences', function () {
    $user = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create(['user_id' => $user->id]);

    $component = Livewire::actingAs($user)->test(Ficha::class);

    $component->call('addExperiencia')->call('addExperiencia')->call('addExperiencia');

    expect($component->get('experiencias'))->toHaveCount(4);

    $component->call('removeExperiencia', 1);

    expect($component->get('experiencias'))->toHaveCount(3);
});

test('a postulante can add and remove multiple education entries', function () {
    $user = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create(['user_id' => $user->id]);

    $component = Livewire::actingAs($user)->test(Ficha::class);

    $component->call('addEducacion')->call('addEducacion');

    expect($component->get('educaciones'))->toHaveCount(3);

    $component->call('removeEducacion', 1);

    expect($component->get('educaciones'))->toHaveCount(2);
});

test('a postulante can add and remove multiple languages', function () {
    $user = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create(['user_id' => $user->id]);

    $component = Livewire::actingAs($user)->test(Ficha::class);

    $component->call('addIdioma')->call('addIdioma');

    expect($component->get('idiomas'))->toHaveCount(3);

    $component->call('removeIdioma', 1);

    expect($component->get('idiomas'))->toHaveCount(2);
});

test('candidate contact details require an active company subscription and access is audited', function () {
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create([
        'user_id' => $empresaUser->id,
        'razon_social' => 'Empresa Activa',
        'plan_id' => Plan::query()->create([
            'codigo' => 'empresa_test', 'nombre' => 'Empresa Test', 'audiencia' => 'empresa', 'precio_clp' => 1,
        ])->id,
        'plan_hasta' => now()->addMonth(),
    ]);
    $postulanteUser = User::factory()->create(['role' => 'postulante', 'email' => 'privado@example.com']);
    $postulante = Postulante::query()->create(['user_id' => $postulanteUser->id, 'rut' => '1-9', 'telefono' => '+56911111111']);
    $busqueda = $empresa->busquedas()->create(['titulo' => 'Búsqueda', 'criterios' => []]);
    $match = $busqueda->candidatos()->create([
        'postulante_id' => $postulante->id, 'estado_match' => 'cumple',
    ]);

    $this->actingAs($empresaUser)
        ->get(route('empresa.candidatos.show', $match))
        ->assertOk()
        ->assertSee('privado@example.com');

    expect($match->fresh()->contactado_at)->not->toBeNull();
});
