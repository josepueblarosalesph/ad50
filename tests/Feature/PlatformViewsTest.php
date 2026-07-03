<?php

use App\Livewire\Empresa\NuevaBusqueda;
use App\Livewire\Postulante\Busquedas as PostulanteBusquedas;
use App\Livewire\Postulante\Ficha;
use App\Livewire\Postulante\Panel as PostulantePanel;
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
        ->assertSee('id="como" class="hidden"', false)
        ->assertSee('<section class="hidden">', false)
        ->assertSee('ad-welcome-light', false)
        ->assertSee('/images/ad50-logo.png', false)
        ->assertSee('/images/ad50-hero-experiencia.webp', false)
        ->assertSee('href="'.route('login').'"', false)
        ->assertSee('Iniciar sesión')
        ->assertSee('href="'.route('registro').'"', false)
        ->assertSee('Registrarse')
        ->assertSee('href="#empresas"', false)
        ->assertSee('Cómo funciona');

    $landing = file_get_contents(resource_path('views/livewire/landing.blade.php'));

    expect($landing)
        ->toContain('gap-y-3 text-[15px]')
        ->toContain('pt-6 text-[14px]');
});

test('the interface uses the official brand typography and color tokens', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain("--font-sans: 'Nunito'")
        ->toContain('--color-orange-500: #E87722')
        ->toContain('--color-accent: var(--color-orange-600)')
        ->toContain('--color-accent-foreground: #FFFFFF')
        ->toContain('--color-gray-400:   #75787B')
        ->toContain('@custom-variant dark')
        ->toContain('border-color: #5A5F64 !important')
        ->toContain('.dark .ad-welcome-light .ad-chip')
        ->not->toContain("--font-display: 'DM Serif Display'");

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('family=Nunito', false);
});

test('authentication and application shells use the official logo without forcing dark mode', function () {
    $applicationLayout = file_get_contents(resource_path('views/components/layouts/app.blade.php'));
    $applicationStyles = file_get_contents(resource_path('css/app.css'));
    $authLayouts = collect([
        resource_path('views/layouts/auth/simple.blade.php'),
        resource_path('views/layouts/auth/card.blade.php'),
        resource_path('views/layouts/auth/split.blade.php'),
    ])->map(fn (string $path): string => file_get_contents($path))->implode('\n');

    expect($applicationLayout)
        ->toContain('/images/ad50-logo.png')
        ->toContain('class="ad-logo shrink-0"')
        ->and($applicationStyles)
        ->toContain('.dark .ad-logo')
        ->toContain('background-color: #222528')
        ->and($authLayouts)
        ->toContain('/images/ad50-logo.png')
        ->not->toContain('class="dark"');

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('/images/ad50-logo.png', false);

    $this->get(route('registro'))
        ->assertOk()
        ->assertSee('/images/ad50-logo.png', false);
});

test('authenticated postulantes see mi perfil on the home page', function () {
    $user = User::factory()->create(['role' => 'postulante']);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Mi perfil')
        ->assertDontSee('Log in')
        ->assertDontSee('Register');
});

test('authenticated empresas see panel de admin on the home page', function () {
    $user = User::factory()->create(['role' => 'empresa']);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Panel de Admin')
        ->assertDontSee('Log in')
        ->assertDontSee('Register');
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
        ->assertSee('Así se ve tu presencia')
        ->assertSee('Visibilidad del perfil')
        ->assertSee(route('postulante.busquedas'), false)
        ->assertDontSee('Solicitar eliminación de mis datos')
        ->assertDontSee('Mi activación')
        ->assertDontSee('<aside class="hidden border-r', false);

    $this->actingAs($user)->get(route('postulante.ficha'))
        ->assertOk()
        ->assertSee('Mi ficha profesional')
        ->assertSee('href="'.route('postulante.busquedas').'"', false)
        ->assertDontSee(route('postulante.panel').'#coincidencias', false)
        ->assertDontSee('Mi activación')
        ->assertDontSee('Sección 1 de 5')
        ->assertDontSee('Sección 5 de 5');

    $this->actingAs($user)->get(route('postulante.busquedas'))
        ->assertOk()
        ->assertSee('dark:bg-[#222528]', false);

    $ficha = file_get_contents(resource_path('views/livewire/postulante/ficha.blade.php'));

    expect($ficha)
        ->toContain('sticky top-24')
        ->toContain('id="datos-personales" class="ad-card order-1')
        ->toContain('id="experiencia" class="ad-card order-2')
        ->toContain('id="educacion" class="ad-card order-3')
        ->toContain('id="idiomas" class="ad-card order-4')
        ->toContain('id="industrias" class="ad-card order-5')
        ->toContain('id="curriculum" class="ad-card mt-5')
        ->toContain('dark:bg-[#1C2B34]')
        ->toContain('dark:bg-[#202D24]')
        ->toContain('dark:bg-[#2B2532]')
        ->toContain('dark:bg-[#30291D]')
        ->and(strpos($ficha, "'Datos personales'"))->toBeLessThan(strpos($ficha, "'Experiencia'"))
        ->and(strpos($ficha, "'Experiencia'"))->toBeLessThan(strpos($ficha, "'Educación'"))
        ->and(strpos($ficha, "'Educación'"))->toBeLessThan(strpos($ficha, "'Idiomas'"))
        ->and(strpos($ficha, "'Idiomas'"))->toBeLessThan(strpos($ficha, "'Industrias de interés'"))
        ->and(strpos($ficha, 'id="industrias"'))->toBeLessThan(strpos($ficha, 'id="curriculum"'))
        ->and(strpos($ficha, 'id="curriculum"'))->toBeLessThan(strpos($ficha, 'Tú controlas tu información'));
});

test('the postulante panel summarizes three searches and the searches page lists them all', function () {
    $postulanteUser = User::factory()->create(['role' => 'postulante']);
    $postulante = Postulante::query()->create(['user_id' => $postulanteUser->id, 'visible' => true]);
    $empresaUser = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create(['user_id' => $empresaUser->id, 'razon_social' => 'Empresa']);

    foreach (range(1, 5) as $index) {
        $busqueda = $empresa->busquedas()->create(['titulo' => "Búsqueda {$index}", 'criterios' => []]);
        $busqueda->candidatos()->create([
            'postulante_id' => $postulante->id,
            'estado_match' => 'cumple',
            'criterios_cumplidos' => 1,
            'criterios_totales' => 1,
        ]);
    }

    Livewire::actingAs($postulanteUser)
        ->test(PostulantePanel::class)
        ->assertViewHas('matches', fn ($matches) => $matches->count() === 3)
        ->assertViewHas('totalMatches', 5)
        ->assertSee('Ver más');

    Livewire::actingAs($postulanteUser)
        ->test(PostulanteBusquedas::class)
        ->assertViewHas('matches', fn ($matches) => $matches->total() === 5)
        ->assertSee('Búsqueda 1')
        ->assertSee('Búsqueda 5');
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
        ->set('rut', '98421157')
        ->set('anioNacimiento', 1971)
        ->set('telefono', '+56 9 5555 1234')
        ->set('linkedin', 'https://linkedin.com/in/maria-fuentes')
        ->set('ciudad', 'Concepción')
        ->set('carrera', 'Ingeniería Civil / Ingeniería Comercial')
        ->set('universidad', 'Universidad de Concepción')
        ->set('especialidad', 'Finanzas')
        ->set('postgrado', 'MBA')
        ->set('educaciones', [
            [
                'nivel' => 'Básica',
                'pais' => 'Chile',
                'institucion' => 'Colegio de Prueba',
                'carrera' => null,
                'mencion' => null,
                'modalidad' => null,
                'situacion' => null,
                'inicio_anio' => null,
                'termino_anio' => null,
                'egreso_anio' => 1983,
            ],
            [
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
            ],
        ])
        ->set('idiomas', [
            ['idioma' => 'Español', 'nivel' => 'Alto'],
            ['idioma' => 'Inglés', 'nivel' => 'Medio'],
        ])
        ->set('industria', 'Banca y servicios financieros')
        ->set('industria2', 'Forestal / Papelera')
        ->set('industria3', 'Tecnología de la Información')
        ->set('experiencias', [[
            'cargo' => 'Gerenta de Finanzas',
            'tipo_trabajo' => 'Jornada completa',
            'empresa' => 'Empresa de Prueba SpA',
            'jerarquia' => 'Gerencia / Dirección',
            'actividad_empresa' => 'Banca y servicios financieros',
            'inicio_mes' => 3,
            'inicio_anio' => now()->year - 17,
            'actualmente' => true,
            'fin_mes' => null,
            'fin_anio' => null,
            'responsabilidades' => 'Liderazgo del equipo financiero y control de gestión.',
        ]])
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
        'rut' => '9.842.115-7',
        'carrera' => 'Ingeniería Civil / Ingeniería Comercial',
        'universidad' => 'Universidad de Concepción',
        'empresa_actual' => 'Empresa de Prueba SpA',
        'completitud' => 100,
    ]);

    $experiencia = $user->postulante->fresh()->experiencias[0];
    $educaciones = $user->postulante->fresh()->educaciones;
    $idiomas = $user->postulante->fresh()->idiomas;

    expect($experiencia)
        ->toMatchArray([
            'cargo' => 'Gerenta de Finanzas',
            'tipo_trabajo' => 'Jornada completa',
            'jerarquia' => 'Gerencia / Dirección',
            'actividad_empresa' => 'Banca y servicios financieros',
            'inicio_mes' => 3,
            'actualmente' => true,
            'responsabilidades' => 'Liderazgo del equipo financiero y control de gestión.',
        ])
        ->and($educaciones)->toHaveCount(2)
        ->and($educaciones[0])->toMatchArray([
            'nivel' => 'Básica',
            'institucion' => 'Colegio de Prueba',
            'egreso_anio' => 1983,
        ])
        ->and($educaciones[1])->toMatchArray([
            'nivel' => 'Universitaria',
            'modalidad' => 'Presencial',
            'situacion' => 'Titulado',
        ])
        ->and($idiomas)->toBe([
            ['idioma' => 'Español', 'nivel' => 'Alto'],
            ['idioma' => 'Inglés', 'nivel' => 'Medio'],
        ]);
});

test('the professional profile formats and validates rut on blur', function () {
    $user = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->set('rut', '123456785')
        ->assertSet('rut', '12.345.678-5')
        ->assertHasNoErrors('rut')
        ->set('rut', '123456789')
        ->assertHasErrors('rut');
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

    $this->actingAs($user)->get(route('empresa.panel'))
        ->assertOk()
        ->assertSee('href="'.route('empresa.busquedas.index').'"', false)
        ->assertSee('href="'.route('empresa.busquedas.create').'"', false)
        ->assertDontSee('href="'.route('planes').'"', false)
        ->assertDontSee('Suscripción')
        ->assertDontSee('<aside class="hidden border-r', false);
    $this->actingAs($user)->get(route('empresa.busquedas.index'))
        ->assertOk()
        ->assertSee('Mis búsquedas')
        ->assertSee('Gerente de Finanzas');
    $this->actingAs($user)->get(route('empresa.busquedas.create'))->assertOk();
    $this->actingAs($user)->get(route('empresa.resultados', $busqueda))->assertOk();
    $this->actingAs($user)->get(route('empresa.candidatos.show', $match))->assertOk();

    Livewire::actingAs($user)
        ->test(NuevaBusqueda::class)
        ->set('titulo', 'Controller Senior')
        ->set('cargo', ['Control de Gestión'])
        ->set('industria', ['Forestal / Papelera'])
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
    $this->actingAs($postulante)->get(route('empresa.busquedas.index'))->assertForbidden();
    $this->actingAs($postulante)->get(route('admin.panel'))->assertForbidden();
});
