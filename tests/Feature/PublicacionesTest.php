<?php

use App\Livewire\Empresa\NuevaPublicacion;
use App\Livewire\Empresa\Publicaciones;
use App\Livewire\Postulante\Busquedas as PortalOportunidades;
use App\Livewire\Postulante\DetallePublicacion as DetalleOportunidad;
use App\Models\Empresa;
use App\Models\Postulacion;
use App\Models\Postulante;
use App\Models\Publicacion;
use App\Models\PublicacionCandidato;
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
        ->assertSee('Nueva publicación')
        ->assertSee('Publicación de oportunidades laborales')
        ->assertDontSee('Portal laboral');
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
        ->set('estudiosMinimos', 'Título Profesional')
        ->set('situacionAcademica', 'Titulado/a')
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
        ->set('seleccion.modalidad', ['Híbrida'])
        ->assertSee('Jefe de Finanzas')
        ->assertDontSee('Consultor de Proyectos')
        ->set('seleccion.actividad_empresa', ['Banca y servicios financieros'])
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

test('el portal no publica el sueldo de las ofertas', function () {
    [, $empresa] = empresaHabilitadaParaPublicar('sinsueldo@empresa.cl');
    $postulante = postulanteHabilitadoParaPostular('sinsueldo@ejemplo.cl');

    Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
        'cargo' => 'Gerente Comercial',
        'sueldo' => 3_500_000,
        // Aunque la empresa lo marque como visible, el listado ya no lo muestra.
        'mostrar_sueldo' => true,
    ]);

    Livewire::actingAs($postulante)
        ->test(PortalOportunidades::class)
        ->assertSee('Gerente Comercial')
        ->assertDontSee('3.500.000')
        ->assertDontSee('líquidos aprox.');
});

test('el rango de sueldo filtra las ofertas por su renta', function () {
    [, $empresa] = empresaHabilitadaParaPublicar('rangos@empresa.cl');
    $postulante = postulanteHabilitadoParaPostular('rangos@ejemplo.cl');

    $ofertas = [
        ['Cargo de 1 millon', 1_000_000],
        ['Cargo de 4 millones', 4_000_000],
        ['Cargo de 12 millones', 12_000_000],
        ['Cargo sin renta', null],
    ];

    foreach ($ofertas as [$cargo, $sueldo]) {
        Publicacion::factory()->create([
            'empresa_id' => $empresa->id,
            'nombre_empresa' => $empresa->razon_social,
            'cargo' => $cargo,
            'sueldo' => $sueldo,
        ]);
    }

    $componente = Livewire::actingAs($postulante)->test(PortalOportunidades::class);

    // Sin acotar el rango se ven todas, incluida la que no informó renta.
    $componente->assertViewHas('publicaciones', fn ($p) => $p->total() === 4);

    // Entre 3 y 5 millones: solo la de 4.
    $componente->set('sueldoMin', 3)->set('sueldoMax', 5)
        ->assertSee('Cargo de 4 millones')
        ->assertDontSee('Cargo de 1 millon')
        ->assertDontSee('Cargo de 12 millones')
        ->assertDontSee('Cargo sin renta');

    // Desde 2 millones hacia arriba, sin tope.
    $componente->set('sueldoMin', 2)->set('sueldoMax', 8)
        ->assertSee('Cargo de 4 millones')
        ->assertSee('Cargo de 12 millones')
        ->assertDontSee('Cargo de 1 millon');
});

test('el tope del rango de sueldo incluye todo lo que sea de 8 millones o más', function () {
    [, $empresa] = empresaHabilitadaParaPublicar('tope@empresa.cl');
    $postulante = postulanteHabilitadoParaPostular('tope@ejemplo.cl');

    foreach ([['Justo ocho', 8_000_000], ['Muy sobre el tope', 25_000_000], ['Bajo el tope', 7_000_000]] as [$cargo, $sueldo]) {
        Publicacion::factory()->create([
            'empresa_id' => $empresa->id,
            'nombre_empresa' => $empresa->razon_social,
            'cargo' => $cargo,
            'sueldo' => $sueldo,
        ]);
    }

    Livewire::actingAs($postulante)
        ->test(PortalOportunidades::class)
        ->set('sueldoMin', 8)
        ->set('sueldoMax', 8)
        ->assertSee('Justo ocho')
        ->assertSee('Muy sobre el tope')
        ->assertDontSee('Bajo el tope');
});

test('un rango de sueldo fuera de los límites o invertido se corrige', function () {
    $postulante = postulanteHabilitadoParaPostular('limites@ejemplo.cl');

    Livewire::actingAs($postulante)
        ->test(PortalOportunidades::class)
        ->set('sueldoMin', 99)
        ->assertSet('sueldoMin', 8)
        ->set('sueldoMax', -5)
        ->assertSet('sueldoMax', 8)   // se corrige a los límites y luego se ordena
        ->assertSet('sueldoMin', 0);
});

test('los filtros de catálogo acotan por los campos de la publicación', function () {
    [, $empresa] = empresaHabilitadaParaPublicar('facetas@empresa.cl');
    $postulante = postulanteHabilitadoParaPostular('facetas@ejemplo.cl');

    Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
        'cargo' => 'Gerente de Mina',
        'tipo_cargo' => 'Jornada completa',
        'jerarquia' => 'Gerencia / Dirección',
        'estudios_minimos' => 'Título Profesional',
        'situacion_academica' => 'Titulado/a',
        'idiomas' => ['Inglés', 'Español'],
    ]);
    Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
        'cargo' => 'Asesor por Proyecto',
        'tipo_cargo' => 'Por proyecto',
        'jerarquia' => 'Profesional / Especialista',
        'estudios_minimos' => 'Otro',
        'situacion_academica' => 'Egresado/a',
        'idiomas' => ['Francés'],
    ]);

    $componente = Livewire::actingAs($postulante)->test(PortalOportunidades::class);

    $soloPrimera = [
        'seleccion.tipo_cargo' => ['Jornada completa'],
        'seleccion.jerarquia' => ['Gerencia / Dirección'],
        'seleccion.estudios_minimos' => ['Título Profesional'],
        'seleccion.situacion_academica' => ['Titulado/a'],
        'seleccion.idiomas' => ['Inglés'],
    ];

    foreach ($soloPrimera as $propiedad => $valor) {
        $componente->call('limpiarFiltros')
            ->set($propiedad, $valor)
            ->assertSee('Gerente de Mina')
            ->assertDontSee('Asesor por Proyecto');
    }
});

test('elegir dos valores de un mismo filtro suma resultados', function () {
    [, $empresa] = empresaHabilitadaParaPublicar('union@empresa.cl');
    $postulante = postulanteHabilitadoParaPostular('union@ejemplo.cl');

    foreach ([['Cargo remoto', 'Remota'], ['Cargo hibrido', 'Híbrida'], ['Cargo presencial', 'Presencial']] as [$cargo, $modalidad]) {
        Publicacion::factory()->create([
            'empresa_id' => $empresa->id,
            'nombre_empresa' => $empresa->razon_social,
            'cargo' => $cargo,
            'modalidad' => $modalidad,
        ]);
    }

    Livewire::actingAs($postulante)
        ->test(PortalOportunidades::class)
        ->set('seleccion.modalidad', ['Remota', 'Híbrida'])
        ->assertSee('Cargo remoto')
        ->assertSee('Cargo hibrido')
        ->assertDontSee('Cargo presencial');
});

test('filtros distintos se combinan entre sí', function () {
    [, $empresa] = empresaHabilitadaParaPublicar('combinado@empresa.cl');
    $postulante = postulanteHabilitadoParaPostular('combinado@ejemplo.cl');

    Publicacion::factory()->create([
        'empresa_id' => $empresa->id, 'nombre_empresa' => $empresa->razon_social,
        'cargo' => 'Remoto y por proyecto', 'modalidad' => 'Remota', 'tipo_cargo' => 'Por proyecto',
    ]);
    Publicacion::factory()->create([
        'empresa_id' => $empresa->id, 'nombre_empresa' => $empresa->razon_social,
        'cargo' => 'Remoto jornada completa', 'modalidad' => 'Remota', 'tipo_cargo' => 'Jornada completa',
    ]);

    Livewire::actingAs($postulante)
        ->test(PortalOportunidades::class)
        ->set('seleccion.modalidad', ['Remota'])
        ->set('seleccion.tipo_cargo', ['Por proyecto'])
        ->assertSee('Remoto y por proyecto')
        ->assertDontSee('Remoto jornada completa');
});

test('un valor fuera del catálogo se descarta del filtro', function () {
    $postulante = postulanteHabilitadoParaPostular('invalido@ejemplo.cl');

    Livewire::actingAs($postulante)
        ->test(PortalOportunidades::class)
        ->set('seleccion.modalidad', ['Remota', 'ValorInventado'])
        ->assertSet('seleccion.modalidad', ['Remota']);
});

test('limpiar filtros deja el listado sin restricciones', function () {
    [, $empresa] = empresaHabilitadaParaPublicar('limpiar@empresa.cl');
    $postulante = postulanteHabilitadoParaPostular('limpiar@ejemplo.cl');

    Publicacion::factory()->create([
        'empresa_id' => $empresa->id, 'nombre_empresa' => $empresa->razon_social,
        'cargo' => 'Unica oferta', 'modalidad' => 'Presencial', 'sueldo' => 2_000_000,
    ]);

    Livewire::actingAs($postulante)
        ->test(PortalOportunidades::class)
        ->set('seleccion.modalidad', ['Remota'])
        ->set('sueldoMin', 5)
        ->set('empleoInclusivo', true)
        ->assertViewHas('filtrosActivos', 3)
        ->assertDontSee('Unica oferta')
        ->call('limpiarFiltros')
        ->assertViewHas('filtrosActivos', 0)
        ->assertSee('Unica oferta');
});

test('el postulante entra al detalle de una oferta desde el listado', function () {
    [, $empresa] = empresaHabilitadaParaPublicar('detalle@empresa.cl');
    $postulante = postulanteHabilitadoParaPostular('detalle@ejemplo.cl');

    $publicacion = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
        'cargo' => 'Jefe de Mantenimiento',
        'descripcion' => str_repeat('Detalle completo de la oferta. ', 8),
        'competencias' => ['Liderazgo'],
        'preguntas' => ['¿Por qué te interesa?'],
    ]);

    // El listado enlaza al detalle...
    $this->actingAs($postulante)
        ->get(route('postulante.busquedas'))
        ->assertOk()
        ->assertSee('href="'.route('postulante.publicaciones.show', $publicacion).'"', false);

    // ...y el detalle muestra la oferta completa con la opción de postular.
    $this->actingAs($postulante)
        ->get(route('postulante.publicaciones.show', $publicacion))
        ->assertOk()
        ->assertSee('Jefe de Mantenimiento')
        ->assertSee('Detalle completo de la oferta.')
        ->assertSee('Liderazgo')
        ->assertSee('¿Por qué te interesa?', false)
        ->assertSee('Postular');
});

test('el detalle permite postular y deja de ofrecerlo después', function () {
    [, $empresa] = empresaHabilitadaParaPublicar('postuladetalle@empresa.cl');
    $postulanteUser = postulanteHabilitadoParaPostular('postuladetalle@ejemplo.cl');

    $publicacion = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
        'cargo' => 'Analista Senior',
        'preguntas' => ['¿Cuál es tu disponibilidad?'],
    ]);

    Livewire::actingAs($postulanteUser)
        ->test(DetalleOportunidad::class, ['publicacion' => $publicacion])
        ->assertViewHas('yaPostulo', false)
        ->call('abrirPostulacion', $publicacion->id)
        ->set('respuestas.0', 'Inmediata')
        ->call('postular')
        ->assertHasNoErrors()
        ->assertViewHas('yaPostulo', true);

    expect(Postulacion::query()->where('publicacion_id', $publicacion->id)->count())->toBe(1);
});

test('una oferta cerrada o vencida no tiene detalle público', function () {
    [, $empresa] = empresaHabilitadaParaPublicar('cerrada@empresa.cl');
    $postulante = postulanteHabilitadoParaPostular('cerrada@ejemplo.cl');

    $vencida = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
        'vigente_hasta' => today()->subDay(),
    ]);
    $cerrada = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
        'estado' => 'cerrada',
    ]);

    foreach ([$vencida, $cerrada] as $publicacion) {
        $this->actingAs($postulante)
            ->get(route('postulante.publicaciones.show', $publicacion))
            ->assertNotFound();
    }
});

test('el panel lista las ofertas vigentes y permite postular sin salir', function () {
    [, $empresa] = empresaHabilitadaParaPublicar('panel@empresa.cl');
    $postulanteUser = postulanteHabilitadoParaPostular('panel@ejemplo.cl');

    $publicacion = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
        'cargo' => 'Supervisor de Turno',
        'preguntas' => [],
    ]);
    Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
        'cargo' => 'Oferta vencida',
        'vigente_hasta' => today()->subDay(),
    ]);

    Livewire::actingAs($postulanteUser)
        ->test(PortalOportunidades::class)
        ->assertSee('Supervisor de Turno')
        ->assertDontSee('Oferta vencida')
        ->assertSee('href="'.route('postulante.publicaciones.show', $publicacion).'"', false)
        ->call('abrirPostulacion', $publicacion->id)
        ->call('postular')
        ->assertHasNoErrors()
        // Ya postulada, el panel deja de ofrecer el botón y muestra cuándo fue.
        ->assertSee('Postulaste el '.today()->translatedFormat('d M Y'));

    expect(Postulacion::query()->where('publicacion_id', $publicacion->id)->count())->toBe(1);
});

/** Postulante nuevo, listo para postular o para ser agregado a mano. */
function postulanteDePrueba(string $nombre): Postulante
{
    return Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante', 'name' => $nombre])->id,
        'visible' => true,
    ]);
}

test('el listado de publicaciones cuenta candidatos: quienes postularon más los agregados a mano', function () {
    [$user, $empresa] = empresaHabilitadaParaPublicar('conteo@empresa.cl');
    $publicacion = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
    ]);

    $postulo = postulanteDePrueba('Ana Postuló');
    Postulacion::query()->create(['publicacion_id' => $publicacion->id, 'postulante_id' => $postulo->id]);

    PublicacionCandidato::query()->create([
        'publicacion_id' => $publicacion->id,
        'postulante_id' => postulanteDePrueba('Beto Agregado')->id,
    ]);

    // Quien postuló y además fue agregado a mano es una sola persona, no dos.
    PublicacionCandidato::query()->create([
        'publicacion_id' => $publicacion->id,
        'postulante_id' => $postulo->id,
    ]);

    // Un agregado que ocultó su ficha deja de contar, igual que en el detalle.
    $oculta = postulanteDePrueba('Carla Oculta');
    PublicacionCandidato::query()->create(['publicacion_id' => $publicacion->id, 'postulante_id' => $oculta->id]);
    $oculta->update(['visible' => false]);

    Livewire::actingAs($user)
        ->test(Publicaciones::class)
        ->assertViewHas('publicaciones', fn ($publicaciones): bool => $publicaciones->first()->candidatos_count === 2)
        ->assertSee('Candidatos')
        ->assertDontSee('Postulaciones');
});

test('el listado de publicaciones se puede ordenar por cantidad de candidatos', function () {
    [$user, $empresa] = empresaHabilitadaParaPublicar('orden@empresa.cl');
    $vacia = Publicacion::factory()->create(['empresa_id' => $empresa->id, 'cargo' => 'Sin candidatos']);
    $poblada = Publicacion::factory()->create(['empresa_id' => $empresa->id, 'cargo' => 'Con candidatos']);

    Postulacion::query()->create([
        'publicacion_id' => $poblada->id,
        'postulante_id' => postulanteDePrueba('Ana Postuló')->id,
    ]);
    PublicacionCandidato::query()->create([
        'publicacion_id' => $poblada->id,
        'postulante_id' => postulanteDePrueba('Beto Agregado')->id,
    ]);

    Livewire::actingAs($user)
        ->test(Publicaciones::class)
        ->call('ordenarPor', 'candidatos')
        ->assertViewHas('publicaciones', fn ($publicaciones): bool => $publicaciones->pluck('id')->all() === [$poblada->id, $vacia->id])
        ->call('ordenarPor', 'candidatos')
        ->assertViewHas('publicaciones', fn ($publicaciones): bool => $publicaciones->pluck('id')->all() === [$vacia->id, $poblada->id]);
});

test('el listado de oportunidades muestra la fecha en que el postulante postuló', function () {
    [, $empresa] = empresaHabilitadaParaPublicar('fechas@empresa.cl');
    $postulanteUser = postulanteHabilitadoParaPostular('fechas@ejemplo.cl');

    $postulada = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
        'cargo' => 'Jefe de Bodega',
    ]);
    $sinPostular = Publicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'nombre_empresa' => $empresa->razon_social,
        'cargo' => 'Analista de Compras',
    ]);

    // `created_at` no es fillable: se fuerza para simular una postulación de hace días.
    Postulacion::query()->create([
        'publicacion_id' => $postulada->id,
        'postulante_id' => $postulanteUser->postulante->id,
    ])->forceFill(['created_at' => now()->subDays(5)])->save();

    $fecha = now()->subDays(5)->translatedFormat('d M Y');

    // En Oportunidades y en el panel se ve la misma fecha; la oferta sin postular no la trae.
    Livewire::actingAs($postulanteUser)
        ->test(PortalOportunidades::class)
        ->assertViewHas('publicaciones', fn ($publicaciones): bool => $publicaciones
            ->firstWhere('id', $postulada->id)->postulada_en->isSameDay(now()->subDays(5))
            && $publicaciones->firstWhere('id', $sinPostular->id)->postulada_en === null)
        ->assertSee('Postulaste el '.$fecha);

    Livewire::actingAs($postulanteUser)
        ->test(PortalOportunidades::class)
        ->assertSee('Postulaste el '.$fecha)
        ->assertSee('Analista de Compras');
});

test('el listado de oportunidades no filtra por el perfil del postulante', function () {
    [, $empresa] = empresaHabilitadaParaPublicar('abierto@empresa.cl');

    // Postulante con un perfil muy acotado: junior, una sola industria y otra región.
    $user = User::factory()->create(['role' => 'postulante', 'email' => 'junior@ejemplo.cl']);
    Postulante::factory()->create([
        'user_id' => $user->id,
        'onboarding_completado' => true,
        'onboarding_paso' => 6,
        'anios_experiencia' => 1,
        'industrias_interes' => ['Tecnología de la Información'],
        'regiones_interes' => ['Biobío'],
    ]);

    // Ofertas que no calzan con nada de ese perfil, incluida una sin sueldo informado.
    $ofertas = [
        ['cargo' => 'Gerente General', 'experiencia_laboral' => '10 años o más', 'jerarquia' => 'Gerencia / Dirección', 'comuna' => 'Santiago', 'actividad_empresa' => 'Minería', 'sueldo' => 9_000_000],
        ['cargo' => 'Operario de Planta', 'experiencia_laboral' => 'Sin experiencia', 'comuna' => 'Antofagasta', 'actividad_empresa' => 'Forestal / Papelera', 'sueldo' => null, 'mostrar_sueldo' => false],
    ];

    foreach ($ofertas as $oferta) {
        Publicacion::factory()->create([...$oferta, 'empresa_id' => $empresa->id, 'nombre_empresa' => $empresa->razon_social]);
    }

    // Las dos aparecen: el único recorte del listado es la vigencia de la oferta.
    Livewire::actingAs($user)
        ->test(PortalOportunidades::class)
        ->assertViewHas('publicaciones', fn ($publicaciones): bool => $publicaciones->total() === 2)
        ->assertSee('Oportunidades')
        ->assertDontSee('para tu experiencia')
        ->assertSee('Gerente General')
        ->assertSee('Operario de Planta');

    // Y lo mismo en el resumen del panel.
    Livewire::actingAs($user)
        ->test(PortalOportunidades::class)
        ->assertSee('Gerente General')
        ->assertSee('Operario de Planta');
});
