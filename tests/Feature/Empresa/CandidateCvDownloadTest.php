<?php

use App\Livewire\Empresa\Candidato;
use App\Livewire\Empresa\Resultados;
use App\Models\Empresa;
use App\Models\Plan;
use App\Models\Postulante;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function crearMatchConCvParaEmpresa(bool $planActivo = true): array
{
    $plan = Plan::query()->create([
        'codigo' => 'empresa_cv_'.str()->random(8),
        'nombre' => 'Empresa CV',
        'audiencia' => 'empresa',
        'precio_clp' => 1,
        'desbloqueos' => 5,
    ]);
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create([
        'user_id' => $empresaUser->id,
        'razon_social' => 'Empresa de Prueba',
        'plan_id' => $planActivo ? $plan->id : null,
        'plan_hasta' => $planActivo ? now()->addMonth() : null,
    ]);
    $postulanteUser = User::factory()->create(['role' => 'postulante']);
    $postulante = Postulante::query()->create([
        'user_id' => $postulanteUser->id,
        'visible' => true,
        'cv_ruta' => 'cvs/curriculum.pdf',
    ]);
    $busqueda = $empresa->busquedas()->create(['titulo' => 'Búsqueda de prueba', 'criterios' => []]);
    $match = $busqueda->candidatos()->create([
        'postulante_id' => $postulante->id,
        'estado_match' => 'cumple',
    ]);

    return [$empresaUser, $match];
}

test('una empresa con acceso puede desbloquear y descargar el cv privado de un candidato', function () {
    Storage::fake('local');
    Storage::disk('local')->put('cvs/curriculum.pdf', '%PDF-1.4 archivo de prueba');
    [$empresaUser, $match] = crearMatchConCvParaEmpresa();

    Livewire::actingAs($empresaUser)
        ->test(Candidato::class, ['match' => $match])
        ->assertDontSee('Descargar CV en PDF')
        ->call('desbloquear')
        ->assertSet('desbloqueado', true)
        ->assertSee('Descargar CV en PDF')
        ->call('descargarCv')
        ->assertFileDownloaded('cv-postulante-'.$match->postulante_id.'.pdf');
});

test('una empresa sin suscripcion activa no puede descargar el cv', function () {
    Storage::fake('local');
    Storage::disk('local')->put('cvs/curriculum.pdf', '%PDF-1.4 archivo de prueba');
    [$empresaUser, $match] = crearMatchConCvParaEmpresa(planActivo: false);

    Livewire::actingAs($empresaUser)
        ->test(Candidato::class, ['match' => $match])
        ->assertDontSee('Descargar CV en PDF')
        ->set('puedeVerContacto', true)
        ->call('descargarCv')
        ->assertForbidden();
});

test('desde el listado se puede descargar el cv de un candidato desbloqueado', function () {
    Storage::fake('local');
    Storage::disk('local')->put('cvs/curriculum.pdf', '%PDF-1.4 archivo de prueba');
    [$empresaUser, $match] = crearMatchConCvParaEmpresa();

    $empresaUser->empresa->desbloqueos()->create(['postulante_id' => $match->postulante_id]);

    Livewire::actingAs($empresaUser)
        ->test(Resultados::class, ['busqueda' => $match->busqueda])
        ->call('descargarCv', $match->postulante_id)
        ->assertFileDownloaded('cv-postulante-'.$match->postulante_id.'.pdf');
});

test('desde el listado no se puede descargar el cv de un candidato sin desbloquear', function () {
    Storage::fake('local');
    Storage::disk('local')->put('cvs/curriculum.pdf', '%PDF-1.4 archivo de prueba');
    [$empresaUser, $match] = crearMatchConCvParaEmpresa();

    Livewire::actingAs($empresaUser)
        ->test(Resultados::class, ['busqueda' => $match->busqueda])
        ->call('descargarCv', $match->postulante_id)
        ->assertForbidden();
});

test('desde el listado se puede desbloquear un perfil consumiendo un cupo del plan', function () {
    [$empresaUser, $match] = crearMatchConCvParaEmpresa();

    expect($empresaUser->empresa->haDesbloqueado($match->postulante_id))->toBeFalse();

    Livewire::actingAs($empresaUser)
        ->test(Resultados::class, ['busqueda' => $match->busqueda])
        ->call('desbloquear', $match->postulante_id)
        ->assertHasNoErrors();

    expect($empresaUser->empresa->haDesbloqueado($match->postulante_id))->toBeTrue()
        ->and($match->fresh()->contactado_at)->not->toBeNull();
});

test('desde el listado no se puede desbloquear sin suscripcion activa', function () {
    [$empresaUser, $match] = crearMatchConCvParaEmpresa(planActivo: false);

    Livewire::actingAs($empresaUser)
        ->test(Resultados::class, ['busqueda' => $match->busqueda])
        ->call('desbloquear', $match->postulante_id);

    // Sin plan vigente no se consume ningún cupo ni se registra el desbloqueo.
    expect($empresaUser->empresa->haDesbloqueado($match->postulante_id))->toBeFalse()
        ->and($empresaUser->empresa->desbloqueos()->count())->toBe(0);
});

test('el listado muestra el candado abierto o cerrado segun el estado de desbloqueo', function () {
    [$empresaUser, $match] = crearMatchConCvParaEmpresa();

    // Bloqueado: botón para desbloquear.
    Livewire::actingAs($empresaUser)
        ->test(Resultados::class, ['busqueda' => $match->busqueda])
        ->assertSeeHtml('desbloquear('.$match->postulante_id.')');

    $empresaUser->empresa->desbloqueos()->create(['postulante_id' => $match->postulante_id]);

    // Desbloqueado: ya no ofrece la acción de desbloquear.
    Livewire::actingAs($empresaUser)
        ->test(Resultados::class, ['busqueda' => $match->busqueda])
        ->assertDontSeeHtml('desbloquear('.$match->postulante_id.')')
        ->assertSee('Perfil desbloqueado');
});

test('el listado muestra accesos rapidos de cv, notas y linkedin solo al desbloquear', function () {
    Storage::fake('local');
    Storage::disk('local')->put('cvs/curriculum.pdf', '%PDF-1.4 archivo de prueba');
    [$empresaUser, $match] = crearMatchConCvParaEmpresa();
    $match->postulante->update(['linkedin' => 'https://linkedin.com/in/candidato']);

    // Sin desbloquear: no aparecen los accesos rápidos.
    Livewire::actingAs($empresaUser)
        ->test(Resultados::class, ['busqueda' => $match->busqueda])
        ->assertDontSeeHtml('descargarCv('.$match->postulante_id.')')
        ->assertDontSee('https://linkedin.com/in/candidato');

    $empresaUser->empresa->desbloqueos()->create(['postulante_id' => $match->postulante_id]);

    // Desbloqueado: aparecen CV, notas y LinkedIn.
    Livewire::actingAs($empresaUser)
        ->test(Resultados::class, ['busqueda' => $match->busqueda])
        ->assertSeeHtml('descargarCv('.$match->postulante_id.')')
        ->assertSee('https://linkedin.com/in/candidato')
        ->assertSee('#notas');
});
