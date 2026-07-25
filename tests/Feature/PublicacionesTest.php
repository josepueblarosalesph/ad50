<?php

use App\Livewire\Empresa\NuevaPublicacion;
use App\Livewire\Empresa\Publicaciones;
use App\Livewire\Postulante\Busquedas as PortalOportunidades;
use App\Models\Empresa;
use App\Models\Postulacion;
use App\Models\Postulante;
use App\Models\Publicacion;
use App\Models\User;
use Livewire\Livewire;

/** @return array{0: User, 1: Empresa} */
function empresaHabilitadaParaPublicar(string $email = 'publicador@empresa.cl'): array
{
    $user = User::factory()->create(['role' => 'empresa', 'email' => $email]);
    $empresa = Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => 'Empresa Publicadora SpA',
        'estado_activacion' => 'activa',
    ]);
    hacerEmpresaOperativa($empresa);

    return [$user->fresh(), $empresa->fresh()];
}

function postulanteHabilitadoParaPostular(string $email = 'postulante@ejemplo.cl'): User
{
    $user = User::factory()->create(['role' => 'postulante', 'email' => $email]);
    Postulante::factory()->create([
        'user_id' => $user->id,
        'onboarding_completado' => true,
        'onboarding_paso' => 6,
    ]);

    return $user;
}

test('una empresa puede acceder a la pestaña publicaciones', function () {
    [$user] = empresaHabilitadaParaPublicar();

    $this->actingAs($user)
        ->get(route('empresa.panel'))
        ->assertOk()
        ->assertSee('Publicaciones')
        ->assertSee('href="'.route('empresa.publicaciones.index').'"', false)
        ->assertSee('href="'.route('empresa.publicaciones.create').'" class="ad-btn-primary ad-btn-sm"', false)
        ->assertSee('href="'.route('empresa.busquedas.create').'" class="ad-btn-primary ad-btn-sm"', false);

    $this->actingAs($user)
        ->get(route('empresa.publicaciones.index'))
        ->assertOk()
        ->assertSee('Nueva publicación');
});

test('una empresa crea una publicación con los campos del formulario', function () {
    [$user, $empresa] = empresaHabilitadaParaPublicar();

    Livewire::actingAs($user)
        ->test(NuevaPublicacion::class)
        ->set('cargo', 'Gerente de Operaciones')
        ->set('tipoCargo', 'Jornada completa')
        ->set('vacantes', 2)
        ->set('descripcion', str_repeat('Liderará equipos y proyectos estratégicos de la organización. ', 4))
        ->set('modalidad', 'Híbrida')
        ->set('comuna', 'Concepción')
        ->set('actividadEmpresa', 'Servicios Profesionales (Auditoría / Consultoría / Legales)')
        ->set('jerarquia', 'Gerencia / Dirección')
        ->set('sueldo', 3500000)
        ->set('mostrarSueldo', true)
        ->set('requisitos', 'Experiencia liderando equipos multidisciplinarios y controlando presupuestos.')
        ->set('experienciaLaboral', '10 años o más')
        ->set('estudiosMinimos', 'Universitaria')
        ->set('situacionAcademica', 'Titulado')
        ->set('competenciasTexto', 'Liderazgo, Planificación, Liderazgo')
        ->set('idiomas', ['Español', 'Inglés'])
        ->set('preguntas', ['¿Por qué te interesa este cargo?'])
        ->set('empleoInclusivo', true)
        ->set('vigenciaDias', 60)
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertRedirect(route('empresa.publicaciones.index'));

    $publicacion = Publicacion::query()->sole();

    expect($publicacion->empresa_id)->toBe($empresa->id)
        ->and($publicacion->nombre_empresa)->toBe('Empresa Publicadora SpA')
        ->and($publicacion->competencias)->toBe(['Liderazgo', 'Planificación'])
        ->and($publicacion->preguntas)->toBe(['¿Por qué te interesa este cargo?'])
        ->and($publicacion->vigente_hasta->toDateString())->toBe(today()->addDays(60)->toDateString());
});

test('el portal muestra solo publicaciones vigentes y permite filtrarlas', function () {
    [$empresaUser, $empresa] = empresaHabilitadaParaPublicar();
    $postulante = postulanteHabilitadoParaPostular();

    Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
        'cargo' => 'Jefe de Finanzas',
        'modalidad' => 'Híbrida',
        'comuna' => 'Concepción',
        'actividad_empresa' => 'Banca y servicios financieros',
    ]);
    Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
        'cargo' => 'Consultor de Proyectos',
        'modalidad' => 'Remota',
        'comuna' => 'Santiago',
    ]);
    Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
        'cargo' => 'Oferta vencida',
        'vigente_hasta' => today()->subDay(),
    ]);

    Livewire::actingAs($postulante)
        ->test(PortalOportunidades::class)
        ->assertSee('Jefe de Finanzas')
        ->assertSee('Consultor de Proyectos')
        ->assertDontSee('Oferta vencida')
        ->set('modalidad', 'Híbrida')
        ->assertSee('Jefe de Finanzas')
        ->assertDontSee('Consultor de Proyectos')
        ->set('actividad', 'Banca y servicios financieros')
        ->assertSee('Jefe de Finanzas');
});

test('un postulante responde preguntas y postula una sola vez', function () {
    [$empresaUser, $empresa] = empresaHabilitadaParaPublicar();
    $user = postulanteHabilitadoParaPostular();
    $publicacion = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
        'preguntas' => ['¿Por qué te interesa esta oportunidad?'],
    ]);

    Livewire::actingAs($user)
        ->test(PortalOportunidades::class)
        ->call('abrirPostulacion', $publicacion->id)
        ->set('respuestas.0', 'Quiero aportar mi experiencia y continuar desarrollándome.')
        ->call('postular')
        ->assertHasNoErrors()
        ->assertSee('Postulación enviada');

    expect(Postulacion::query()->count())->toBe(1)
        ->and(Postulacion::query()->first()->respuestas)->toBe([
            'Quiero aportar mi experiencia y continuar desarrollándome.',
        ]);

    Livewire::actingAs($user)
        ->test(PortalOportunidades::class)
        ->call('abrirPostulacion', $publicacion->id);

    expect(Postulacion::query()->count())->toBe(1);
});

test('una empresa no puede cambiar publicaciones de otra empresa', function () {
    [$owner, $empresa] = empresaHabilitadaParaPublicar('owner@empresa.cl');
    [$intruso] = empresaHabilitadaParaPublicar('intruso@empresa.cl');
    $publicacion = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
    ]);

    Livewire::actingAs($intruso)
        ->test(Publicaciones::class)
        ->call('cambiarEstado', $publicacion->id, 'cerrada')
        ->assertForbidden();

    expect($publicacion->fresh()->estado)->toBe('publicada');
});
