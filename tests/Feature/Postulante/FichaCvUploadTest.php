<?php

use App\Livewire\Postulante\Ficha;
use App\Models\Postulante;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function completarFichaParaAdjuntarCv(Testable $component): Testable
{
    return $component
        ->set('rut', '9.842.115-7')
        ->set('anioNacimiento', 1971)
        ->set('ciudad', 'Concepción')
        ->set('industria', 'Banca y servicios financieros')
        ->set('educaciones', [[
            'nivel' => 'Universitaria',
            'pais' => 'Chile',
            'institucion' => 'Universidad de Concepción',
            'carrera' => 'Ingeniería Civil / Ingeniería Comercial',
            'mencion' => 'Finanzas',
            'modalidad' => 'Presencial',
            'situacion' => 'Titulado',
            'inicio_anio' => 1989,
            'termino_anio' => 1995,
            'egreso_anio' => null,
        ]])
        ->set('idiomas', [['idioma' => 'Español', 'nivel' => 'Alto']])
        ->set('experiencias', [[
            'cargo' => 'Gerenta de Finanzas',
            'tipo_trabajo' => 'Jornada completa',
            'empresa' => 'Empresa de Prueba SpA',
            'jerarquia' => 'Gerencia / Dirección',
            'actividad_empresa' => 'Banca y servicios financieros',
            'inicio_mes' => 3,
            'inicio_anio' => 2010,
            'actualmente' => true,
            'fin_mes' => null,
            'fin_anio' => null,
            'responsabilidades' => 'Liderazgo del equipo financiero.',
        ]]);
}

test('la ficha muestra el control para adjuntar el curriculum', function () {
    $user = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('postulante.ficha'))
        ->assertOk()
        ->assertSee('Currículum Vitae')
        ->assertSee('wire:model="cv"', false);
});

test('un postulante puede guardar un pdf privado y reemplazar el anterior', function () {
    Storage::fake('local');
    Storage::disk('local')->put('cvs/anterior.pdf', 'anterior');

    $user = User::factory()->create(['role' => 'postulante']);
    $postulante = Postulante::query()->create([
        'user_id' => $user->id,
        'cv_ruta' => 'cvs/anterior.pdf',
    ]);

    $component = completarFichaParaAdjuntarCv(Livewire::actingAs($user)->test(Ficha::class));

    $component
        ->set('cv', UploadedFile::fake()->create('curriculum.pdf', 100, 'application/pdf'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('cv', null);

    $rutaNueva = $postulante->fresh()->cv_ruta;

    expect($rutaNueva)->toStartWith('cvs/')
        ->and($rutaNueva)->not->toBe('cvs/anterior.pdf');

    Storage::disk('local')->assertExists($rutaNueva);
    Storage::disk('local')->assertMissing('cvs/anterior.pdf');
});

test('la ficha rechaza archivos que no son pdf', function () {
    Storage::fake('local');

    $user = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create(['user_id' => $user->id]);

    $component = completarFichaParaAdjuntarCv(Livewire::actingAs($user)->test(Ficha::class));

    $component
        ->set('cv', UploadedFile::fake()->create('curriculum.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'))
        ->call('save')
        ->assertHasErrors(['cv' => ['mimes']]);
});
