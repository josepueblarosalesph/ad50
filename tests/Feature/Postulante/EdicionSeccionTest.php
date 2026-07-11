<?php

use App\Livewire\Postulante\Ficha;
use App\Models\Empresa;
use App\Models\Postulante;
use App\Models\User;
use Livewire\Livewire;

/** Postulante con onboarding completo → la ficha se muestra en modo editor (solo lectura + modales). */
function postulanteEnEditor(array $overrides = []): User
{
    $user = User::factory()->create(['role' => 'postulante', 'nombres' => 'Ana', 'apellidos' => 'Silva']);

    Postulante::query()->create(array_merge([
        'user_id' => $user->id,
        'onboarding_completado' => true,
        'onboarding_paso' => 6,
        'visible' => true,
        'rut' => '9.842.115-7',
        'anio_nacimiento' => 1971,
        'anios_experiencia' => 20,
        'genero' => 'Femenino',
        'ciudad' => 'Biobío',
        'titular' => 'Gerenta de Finanzas',
        'industrias_interes' => ['Banca y servicios financieros'],
        'carrera' => 'Ingeniería Civil / Ingeniería Comercial',
    ], $overrides));

    return $user;
}

test('the completed profile renders in read-only editor mode', function () {
    Livewire::actingAs(postulanteEnEditor())
        ->test(Ficha::class)
        ->assertSet('modoOnboarding', false)
        ->assertSee('Mi perfil profesional')
        ->assertSee('Editar')
        ->assertSee('Gerenta de Finanzas');
});

test('the editor loads a section form only after opening its modal (lighter DOM)', function () {
    Livewire::actingAs(postulanteEnEditor())
        ->test(Ficha::class)
        // Sin abrir ningún modal, los formularios de entrada no están en el DOM.
        ->assertSet('seccionEditando', '')
        ->assertDontSee('Escribe una breve presentación')
        ->call('editarSeccion', 'acerca')
        ->assertSet('seccionEditando', 'acerca')
        ->assertSee('Escribe una breve presentación')
        // Otra sección no se renderiza a la vez.
        ->assertDontSee('Nivel de estudios *');
});

test('the full inline edit flow saves the section and returns to read-only', function () {
    $user = postulanteEnEditor();

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->call('editarSeccion', 'acerca')
        ->assertSet('seccionEditando', 'acerca')
        ->set('titular', 'Nuevo titular guardado')
        ->call('guardarSeccion', 'acerca')
        ->assertHasNoErrors()
        ->assertSet('seccionEditando', '');

    expect($user->postulante->fresh()->titular)->toBe('Nuevo titular guardado');
});

test('cancel discards unsaved changes and reloads from the database', function () {
    $user = postulanteEnEditor();

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->call('editarSeccion', 'acerca')
        ->set('titular', 'Cambio que no debe guardarse')
        ->call('cancelarEdicion')
        ->assertSet('seccionEditando', '')
        ->assertSet('titular', 'Gerenta de Finanzas');

    expect($user->postulante->fresh()->titular)->toBe('Gerenta de Finanzas');
});

test('editing the "acerca de mí" section from a modal persists only that section', function () {
    $user = postulanteEnEditor();

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->set('titular', 'Directora Financiera con foco en transformación')
        ->set('industriasInteres', ['Banca y servicios financieros', 'Minería'])
        ->call('guardarSeccion', 'acerca')
        ->assertHasNoErrors();

    expect($user->postulante->fresh())
        ->titular->toBe('Directora Financiera con foco en transformación')
        ->industrias_interes->toBe(['Banca y servicios financieros', 'Minería']);
});

test('an invalid section keeps its errors so the modal stays open', function () {
    $user = postulanteEnEditor();

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->set('titular', str_repeat('a', 101))
        ->call('guardarSeccion', 'acerca')
        ->assertHasErrors('titular');

    // No se persistió el cambio inválido.
    expect($user->postulante->fresh()->titular)->toBe('Gerenta de Finanzas');
});

test('toggling visibility from the editor persists immediately without a global save', function () {
    $user = postulanteEnEditor();

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->set('visible', false);

    expect($user->postulante->fresh()->visible)->toBeFalse();
});

test('saving a section re-syncs the matching for active searches', function () {
    $user = postulanteEnEditor([
        'especialidad' => 'Finanzas',
        'anios_experiencia' => 18,
        'experiencias' => [[
            'cargo' => 'Finanzas', 'empresa' => 'Empresa A', 'area' => 'Finanzas',
            'inicio' => 2006, 'fin' => 2026,
        ]],
    ]);

    $empresaUser = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $empresaUser->id, 'razon_social' => 'Empresa']);
    $busqueda = $empresa->busquedas()->create([
        'titulo' => 'Finanzas senior',
        'estado' => 'activa',
        'criterios' => ['carrera' => ['Ingeniería Civil / Ingeniería Comercial']],
    ]);

    // Antes de tocar la ficha no hay coincidencias materializadas para esta búsqueda.
    expect($busqueda->candidatos()->count())->toBe(0);

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->set('titular', 'Actualizo mi titular')
        ->call('guardarSeccion', 'acerca')
        ->assertHasNoErrors();

    expect($busqueda->fresh()->candidatos()->where('estado_match', 'cumple')->pluck('postulante_id'))
        ->toContain($user->postulante->id);
});
