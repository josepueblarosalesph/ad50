<?php

use App\Livewire\Empresa\Candidato;
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
