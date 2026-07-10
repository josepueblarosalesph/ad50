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
use App\Support\CatalogosProfesionales;
use Livewire\Livewire;

test('the landing page presents the experience-led visual direction', function () {
    Plan::query()->create([
        'codigo' => 'empresa_basic',
        'nombre' => 'Básico',
        'audiencia' => 'empresa',
        'precio_clp' => 0,
        'precio_uf' => 2,
        'periodo' => 'único',
        'features' => ['1 publicación'],
        'recomendacion' => 'Recomendado para búsquedas puntuales',
    ]);
    Plan::query()->create([
        'codigo' => 'empresa_premium',
        'nombre' => 'Premium',
        'audiencia' => 'empresa',
        'precio_clp' => 0,
        'precio_uf' => 45,
        'periodo' => 'anual',
        'destacado' => true,
        'features' => ['Publicaciones ilimitadas'],
        'recomendacion' => 'Recomendado para empresas con altas demandas de ofertas laborales',
    ]);

    $response = $this->get(route('home'));

    $response
        ->assertOk()
        ->assertSeeText('La experiencia no se archiva. Se activa.')
        ->assertSee('min-h-[100svh]', false)
        ->assertSee('text-[44px] leading-[.98] text-ink sm:text-[56px] lg:text-[66px]', false)
        ->assertSee('fixed inset-x-0 top-0 z-40', false)
        ->assertSee('id="landing-mobile-navigation"', false)
        ->assertSee('aria-label="Abrir menú de navegación"', false)
        ->assertSee('p-1.5 text-[15px] font-bold text-ink', false)
        ->assertSee('pastHero: false', false)
        ->assertSee("pastHero ? 'border-white/10 bg-[#252729]/90", false)
        ->assertSee('id="como-postulantes"', false)
        ->assertSee('id="como-empresas"', false)
        ->assertSee('id="quienes-somos"', false)
        ->assertSee('id="planes"', false)
        ->assertSee('Cómo funciona para postulantes')
        ->assertSee('Cómo funciona para empresas')
        ->assertSee('bg-[#252729]', false)
        ->assertSee('lg:text-[72px]', false)
        ->assertSee('from-orange-700 via-orange-500 to-orange-200', false)
        ->assertSee('absolute bottom-10 right-10 z-10', false)
        ->assertSee('Quiénes somos')
        ->assertSee('href="#quienes-somos"', false)
        ->assertDontSee('Acerca de')
        ->assertSee('href="#planes"', false)
        ->assertSee('Planes para empresas')
        ->assertSee('Elige el alcance de tu búsqueda.')
        ->assertSee('2')
        ->assertSee('UF + IVA')
        ->assertSee('Premium')
        ->assertSee('45')
        ->assertSee('Publicaciones ilimitadas')
        ->assertSee('Más elegido')
        ->assertSee('Beneficios para tu proceso de selección')
        ->assertSee('Recibe candidatos compatibles automáticamente')
        ->assertSee('Accede a perfiles y currículums completos')
        ->assertSee('Reduce tiempos de búsqueda')
        ->assertSee('Encuentra talento con experiencia comprobada')
        ->assertSee('Crear tu perfil profesional es gratis.')
        ->assertDontSee('Postulante visible')
        ->assertDontSee('Ver todos los planes')
        ->assertSee('Crea tu perfil profesional')
        ->assertSee('Configura la búsqueda')
        ->assertDontSee('id="como" class="hidden"', false)
        ->assertSee('<section class="hidden">', false)
        ->assertSee('ad-welcome-light', false)
        ->assertSee('/images/ad50-logo.png', false)
        ->assertSee('/images/ad50-hero-profesionales-trabajando.webp', false)
        ->assertSee('href="'.route('login').'"', false)
        ->assertSee('href="'.route('login').'" class="ad-btn-primary ad-btn-sm"', false)
        ->assertSee('Iniciar sesión')
        ->assertSee('href="'.route('registro', ['tipo' => 'postulante']).'"', false)
        ->assertSee('href="'.route('registro', ['tipo' => 'empresa']).'"', false)
        ->assertSee('Registrarse')
        ->assertSee('Postulante')
        ->assertSee('Empresa')
        ->assertSee('Crear mi perfil profesional')
        ->assertSee('href="#como-postulantes"', false)
        ->assertSee('href="#como-empresas"', false)
        ->assertSee('Cómo funciona');

    expect(strpos($response->getContent(), '$20.000'))
        ->toBeLessThan(strpos($response->getContent(), 'id="planes"'));

    $landing = file_get_contents(resource_path('views/livewire/landing.blade.php'));

    expect($landing)
        ->toContain('gap-y-3 text-[15px]')
        ->toContain('pt-6 text-[14px]')
        ->and(strpos($landing, 'href="#quienes-somos"'))->toBeLessThan(strpos($landing, 'aria-label="Elegir cómo funciona AD+50"'))
        ->and(strpos($landing, 'href="#como-empresas"'))->toBeLessThan(strpos($landing, 'href="#como-postulantes"'))
        ->and(strpos($landing, "route('registro', ['tipo' => 'empresa'])"))->toBeLessThan(strpos($landing, "route('registro', ['tipo' => 'postulante'])"))
        ->and(strpos($landing, 'id="quienes-somos"'))->toBeLessThan(strpos($landing, 'id="como-empresas"'))
        ->and(strpos($landing, 'id="como-empresas"'))->toBeLessThan(strpos($landing, 'id="como-postulantes"'))
        ->and(strpos($landing, 'id="como-postulantes"'))->toBeLessThan(strpos($landing, 'id="planes"'));
});

test('the interface uses the official brand typography and color tokens', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain("--font-sans: 'Nunito'")
        ->toContain('--color-orange-500: #E87722')
        ->toContain('--color-accent: var(--color-orange-600)')
        ->toContain('--color-accent-foreground: #FFFFFF')
        ->toContain('--ad-accent-text: #F7C59E')
        ->toContain('--ad-accent-surface: #33251D')
        ->toContain('.dark [class~="text-orange-600"]')
        ->toContain('.dark [class~="text-orange-700"]')
        ->toContain('.dark .ad-welcome-light [class~="text-orange-600"]')
        ->toContain('--color-gray-400:   #75787B')
        ->toContain('body       { background: var(--color-paper); font-size: 18px; line-height: 1.6; }')
        ->toContain('body [class~="text-[10px]"]')
        ->toContain('body [class~="text-[14px]"]')
        ->toContain('[data-flux-control]:not([data-flux-checkbox]):not([data-flux-switch])')
        ->toContain('min-height: 44px')
        ->toContain('@custom-variant dark')
        ->toContain('[x-cloak] { display: none !important; }')
        ->toContain('border-color: #5A5F64 !important')
        ->toContain('.dark .ad-welcome-light .ad-chip')
        ->not->toContain("--font-display: 'DM Serif Display'");

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('family=Nunito', false);
});

test('the reusable mobile menu is accessible and responsive', function () {
    $menu = file_get_contents(resource_path('views/components/mobile-menu.blade.php'));

    expect($menu)
        ->toContain('<flux:icon.bars-3')
        ->toContain('<flux:icon.x-mark')
        ->toContain('x-bind:aria-expanded="open"')
        ->toContain('x-on:keydown.escape.window="open = false"')
        ->toContain('x-on:click.outside="open = false"')
        ->toContain("'md:hidden' => \$breakpoint === 'md'")
        ->toContain("'lg:hidden' => \$breakpoint === 'lg'");
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
        ->toContain('class="ml-auto flex shrink-0 items-center gap-3"')
        ->toContain('class="hidden md:block"')
        ->toContain('<x-mobile-menu id="application-mobile-navigation">')
        ->toContain("route('profile.edit')")
        ->toContain("route('appearance.edit')")
        ->toContain("'md:grid-cols-[260px_1fr]' => isset(\$sidebar)")
        ->and($applicationStyles)
        ->toContain('.dark .ad-logo')
        ->toContain('background-color: #222528')
        ->and($authLayouts)
        ->toContain('/images/ad50-logo.png')
        ->not->toContain('class="dark"');

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('/images/ad50-logo.png', false)
        ->assertSee('ad-auth-back', false)
        ->assertSee('absolute right-5 top-5', false)
        ->assertSee('Volver al inicio')
        ->assertDontSee('Acceso seguro');

    $this->get(route('registro'))
        ->assertOk()
        ->assertSee('/images/ad50-logo.png', false)
        ->assertSee('ad-auth-back', false)
        ->assertSee('absolute right-5 top-5', false)
        ->assertSee('Volver al inicio')
        ->assertDontSee('Registro seguro');
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
        'nombre' => 'Básico',
        'audiencia' => 'empresa',
        'precio_clp' => 0,
        'precio_uf' => 2,
        'periodo' => 'único',
        'features' => ['1 publicación', 'Match inteligente (candidatos que más se acercan al perfil buscado)', '5 accesos a perfiles completos o desbloqueos de CV'],
        'recomendacion' => 'Recomendado para búsquedas puntuales',
    ]);
    Plan::query()->create([
        'codigo' => 'empresa_pro',
        'nombre' => 'Profesional',
        'audiencia' => 'empresa',
        'precio_clp' => 0,
        'precio_uf' => 30,
        'periodo' => 'anual',
        'features' => ['30 publicaciones', '15 accesos a perfiles completos o desbloqueos de CV', 'Soporte técnico'],
        'recomendacion' => 'Recomendado para múltiples búsquedas',
    ]);
    Plan::query()->create([
        'codigo' => 'empresa_premium',
        'nombre' => 'Premium',
        'audiencia' => 'empresa',
        'precio_clp' => 0,
        'precio_uf' => 45,
        'periodo' => 'anual',
        'destacado' => true,
        'features' => ['Publicaciones ilimitadas', '100 accesos a perfiles completos o desbloqueos de CV', 'Soporte técnico'],
        'recomendacion' => 'Recomendado para empresas con altas demandas de ofertas laborales',
    ]);

    $this->get(route('planes'))
        ->assertOk()
        ->assertSee('Elige el alcance de tu búsqueda')
        ->assertSee('Volver al inicio')
        ->assertSee('id="company-plans-mobile-navigation"', false)
        ->assertSee('class="ad-btn-ghost ad-btn-sm gap-2"', false)
        ->assertSee('Básico')
        ->assertSee('Profesional')
        ->assertSee('Premium')
        ->assertSee('2')
        ->assertSee('30')
        ->assertSee('45')
        ->assertSee('UF + IVA')
        ->assertSee('Más elegido')
        ->assertSee('Recibe candidatos compatibles automáticamente')
        ->assertSee('Encuentra talento con experiencia comprobada')
        ->assertDontSee('$20.000')
        ->assertDontSee('Planes para postulantes');

    expect(file_get_contents(resource_path('views/livewire/planes.blade.php')))
        ->toContain('<flux:icon.arrow-left class="size-4" />');
});

test('the candidate plans page no longer exists', function () {
    $this->get('/planes/postulantes')->assertNotFound();
});

test('a postulante can view the panel and professional profile', function () {
    $user = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create(['user_id' => $user->id, 'completitud' => 72]);

    $this->actingAs($user)->get(route('postulante.panel'))
        ->assertOk()
        ->assertSee('id="application-mobile-navigation"', false)
        ->assertSee('aria-label="Navegación móvil"', false)
        ->assertSee($user->email)
        ->assertSee('Mi cuenta')
        ->assertSee('Configuración')
        ->assertSee('Cerrar sesión')
        ->assertSee('Así se ve tu presencia')
        ->assertSee('Visibilidad del perfil')
        ->assertSee(route('postulante.busquedas'), false)
        ->assertDontSee('Solicitar eliminación de mis datos')
        ->assertDontSee('Mi activación')
        ->assertDontSee('<aside class="hidden border-r', false);

    $this->actingAs($user)->get(route('postulante.ficha'))
        ->assertOk()
        ->assertSee('Mi perfil profesional')
        ->assertSee('Género')
        ->assertSee('Masculino')
        ->assertSee('Femenino')
        ->assertSee('Prefiero no Informar')
        ->assertDontSee('No binario')
        ->assertSee('Titular *')
        ->assertSee('maxlength="100"', false)
        ->assertSee('Medio')
        ->assertSee('Alto')
        ->assertDontSee('>Bajo<', false)
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
        ->toContain("x-data=\"{ activeSection: 'datos-personales' }\"")
        ->toContain('new IntersectionObserver')
        ->toContain("rootMargin: '-20% 0px -65% 0px'")
        ->toContain("x-bind:aria-current=\"activeSection === '{{ \$anchor }}' ? 'location' : null\"")
        ->toContain('border-orange-500 bg-orange-100 text-orange-700 shadow-sm')
        ->toContain('border-line-2 bg-white text-gray-700')
        ->toContain('id="datos-personales" class="ad-card order-1')
        ->toContain('id="experiencia" class="ad-card order-2')
        ->toContain('id="educacion" class="ad-card order-3')
        ->toContain('id="idiomas" class="ad-card order-4')
        ->toContain('id="curriculum" class="ad-card mt-5')
        ->toContain('Regiones de interés')
        ->toContain('Industrias de interés')
        ->toContain('Modalidad preferida')
        ->toContain('Situación laboral')
        ->toContain('Expectativa de renta')
        ->toContain('Nacionalidad *')
        ->toContain('Años de experiencia *')
        ->toContain('Escribe una breve presentación')
        ->not->toContain('id="intereses"')
        ->and(strpos($ficha, "'Mis Datos'"))->toBeLessThan(strpos($ficha, "'Experiencia'"))
        ->and(strpos($ficha, "'Experiencia'"))->toBeLessThan(strpos($ficha, "'Educación'"))
        ->and(strpos($ficha, "'Educación'"))->toBeLessThan(strpos($ficha, "'Idiomas'"))
        ->and(strpos($ficha, 'id="curriculum"'))->toBeLessThan(strpos($ficha, 'Tú controlas tu información'));

    expect(CatalogosProfesionales::generos())->toBe([
        'Masculino',
        'Femenino',
        'Prefiero no Informar',
    ]);

    expect(substr_count($ficha, 'border-l-orange-300 dark:border-l-orange-500'))->toBe(5);
    expect(substr_count($ficha, 'bg-orange-50/60 dark:bg-orange-50'))->toBe(5);
    expect(substr_count($ficha, 'text-orange-700 dark:text-orange-500'))->toBe(5);
    expect($ficha)
        ->toContain('border-dashed border-orange-200 bg-orange-50/60')
        ->toContain('text-orange-700 dark:text-[#F7C59E]')
        ->not->toContain('bg-[#FCFBFD]')
        ->not->toContain('dark:bg-[#252129]');
});

test('a postulante cannot select more than five regions or industries', function () {
    $user = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->set('regionesInteres', array_slice(CatalogosProfesionales::regiones(), 0, 6))
        ->set('industriasInteres', array_slice(CatalogosProfesionales::industrias(), 0, 6))
        ->call('save')
        ->assertHasErrors(['regionesInteres' => 'max', 'industriasInteres' => 'max']);
});

test('a postulante cannot save a gender outside the available options', function () {
    $user = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(Ficha::class)
        ->set('genero', 'Otro')
        ->call('save')
        ->assertHasErrors(['genero' => 'in']);
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
        ->set('nombres', 'María José')
        ->set('apellidos', 'Fuentes')
        ->set('email', 'maria.fuentes@example.com')
        ->set('rut', '98421157')
        ->set('anioNacimiento', 1971)
        ->set('genero', 'Femenino')
        ->set('nacionalidad', 'Chilena')
        ->set('aniosExperiencia', 17)
        ->set('telefono', '+56 9 5555 1234')
        ->set('linkedin', 'https://linkedin.com/in/maria-fuentes')
        ->set('sitioWeb', 'https://mariafuentes.cl')
        ->set('situacionLaboral', 'Trabajando actualmente')
        ->set('expectativaRenta', 2500000)
        ->set('ciudad', 'Biobío')
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
            ['idioma' => 'Español', 'nivel' => 'Avanzado'],
            ['idioma' => 'Inglés', 'nivel' => 'Intermedio'],
        ])
        ->set('industriasInteres', ['Banca y servicios financieros', 'Forestal / Papelera', 'Tecnología de la Información'])
        ->set('regionesInteres', ['Biobío', 'Ñuble', 'La Araucanía'])
        ->set('modalidadesTrabajo', ['Jornada Parcial', 'Honorarios'])
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
        ->set('titular', str_repeat('a', 101))
        ->call('save')
        ->assertHasErrors(['titular' => 'max'])
        ->set('titular', 'Gerenta de Finanzas y transformación empresarial')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('completitud', 100);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'María José Fuentes',
        'nombres' => 'María José',
        'apellidos' => 'Fuentes',
        'email' => 'maria.fuentes@example.com',
    ]);
    $this->assertDatabaseHas('postulantes', [
        'user_id' => $user->id,
        'rut' => '9.842.115-7',
        'genero' => 'Femenino',
        'titular' => 'Gerenta de Finanzas y transformación empresarial',
        'regiones_interes' => json_encode(['Biobío', 'Ñuble', 'La Araucanía']),
        'industrias_interes' => json_encode(['Banca y servicios financieros', 'Forestal / Papelera', 'Tecnología de la Información']),
        'modalidad_trabajo' => json_encode(['Jornada Parcial', 'Honorarios']),
        'nacionalidad' => 'Chilena',
        'situacion_laboral' => 'Trabajando actualmente',
        'expectativa_renta' => 2500000,
        'anios_experiencia' => 17,
        'sitio_web' => 'https://mariafuentes.cl',
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
            ['idioma' => 'Español', 'nivel' => 'Avanzado'],
            ['idioma' => 'Inglés', 'nivel' => 'Intermedio'],
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
        'estado_activacion' => 'activa',
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
    $this->actingAs($user)->get(route('empresa.candidatos.show', $match))
        ->assertOk()
        ->assertSee('ad-candidate-value', false)
        ->assertSee('ad-candidate-toolbar', false)
        ->assertSee('ad-candidate-sidebar-active', false)
        ->assertSee('ad-favorite-button', false)
        ->assertSee('Intereses')
        ->assertSee('15 años de experiencia');

    Livewire::actingAs($user)
        ->test(NuevaBusqueda::class)
        ->set('titulo', 'Controller Senior')
        ->set('cargo', ['Control de Gestión'])
        ->set('industria', ['Forestal / Papelera'])
        ->set('aniosMinimos', 5)
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
