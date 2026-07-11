<div class="ad-welcome-light">
    {{-- Hero editorial: la imagen y el mensaje fueron las preferencias más consistentes del test. --}}
    <section class="ad-light-surface relative min-h-[100svh] overflow-hidden border-b border-line bg-paper"
        style="background-image: linear-gradient(90deg, #F6F6F4 0%, rgba(246,246,244,.98) 38%, rgba(246,246,244,.68) 57%, rgba(246,246,244,.05) 78%), url('/images/ad50-hero-profesionales-trabajando.webp'); background-position: center, 68% center; background-size: cover;">
        <div class="absolute inset-x-0 top-0 h-32 bg-gradient-to-b from-black/65 to-transparent"></div>
        <div
            x-data="{ pastHero: false }"
            x-init="const hero = $el.closest('section'); const observer = new IntersectionObserver(([entry]) => pastHero = ! entry.isIntersecting); observer.observe(hero)"
            :class="pastHero ? 'border-white/10 bg-[#252729]/90 shadow-[0_10px_30px_rgba(0,0,0,.12)] backdrop-blur-xl' : 'border-transparent bg-transparent shadow-none'"
            class="fixed inset-x-0 top-0 z-40 border-b transition-[background-color,border-color,box-shadow,backdrop-filter] duration-300"
        >
            <nav class="mx-auto flex max-w-[1240px] items-center justify-between px-6 py-3 lg:px-10">
                <a href="{{ route('home') }}" class="overflow-hidden rounded-[10px]" aria-label="AD+50 Talento Senior, inicio">
                    <img src="/images/ad50-logo.png" alt="AD+50 Talento Senior" class="h-14 w-auto sm:h-16">
                </a>

                <div class="hidden items-center gap-1 rounded-[14px] border border-white/70 bg-white/75 p-1.5 text-[15px] font-bold text-ink shadow-[0_8px_30px_rgba(52,54,56,.08)] backdrop-blur-md lg:flex">
                    <a href="#quienes-somos" class="rounded-[10px] px-4 py-2.5 transition duration-200 hover:bg-orange-100 hover:text-orange-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500">Quiénes somos</a>
                    <flux:dropdown position="bottom" align="start">
                        <button type="button" class="inline-flex items-center gap-1.5 rounded-[10px] px-4 py-2.5 transition duration-200 hover:bg-orange-100 hover:text-orange-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500" aria-label="Elegir cómo funciona AD+50">
                            Cómo funciona
                            <flux:icon.chevron-down class="size-4" />
                        </button>
                        <flux:menu>
                            <flux:menu.item href="#como-empresas" icon="building-office-2">Empresas</flux:menu.item>
                            <flux:menu.item href="#como-postulantes" icon="user">Postulantes</flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                    <a href="#planes" class="rounded-[10px] px-4 py-2.5 transition duration-200 hover:bg-orange-100 hover:text-orange-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500">Planes</a>
                </div>

                @auth
                    <a
                        href="{{ route(auth()->user()->dashboardRouteName()) }}"
                        class="ad-btn-ghost ad-btn-sm hidden bg-paper/70 backdrop-blur lg:inline-flex"
                    >
                        {{ auth()->user()->dashboardLabel() }}
                    </a>
                @else
                    <div class="hidden items-center gap-2 lg:flex">
                        <a href="{{ route('login') }}" class="ad-btn-primary ad-btn-sm">Iniciar sesión</a>
                        <flux:dropdown position="bottom" align="end">
                            <button type="button" class="ad-btn-primary ad-btn-sm" aria-label="Elegir tipo de registro">
                                Registrarse
                                <flux:icon.chevron-down class="size-4" />
                            </button>
                            <flux:menu>
                                <flux:menu.item :href="route('registro', ['tipo' => 'empresa'])" icon="building-office-2" wire:navigate>
                                    Empresa
                                </flux:menu.item>
                                <flux:menu.item :href="route('registro', ['tipo' => 'postulante'])" icon="user" wire:navigate>
                                    Postulante
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                @endauth

                <x-mobile-menu breakpoint="lg" id="landing-mobile-navigation" panel-class="bg-white/95 backdrop-blur-xl">
                    <a href="#quienes-somos">Quiénes somos</a>
                    <a href="#como-empresas"><flux:icon.building-office-2 class="mr-2 size-4" />Cómo funciona para empresas</a>
                    <a href="#como-postulantes"><flux:icon.user class="mr-2 size-4" />Cómo funciona para postulantes</a>
                    <a href="#planes">Planes</a>
                    <div class="my-2 h-px bg-line"></div>
                    @auth
                        <a href="{{ route(auth()->user()->dashboardRouteName()) }}">{{ auth()->user()->dashboardLabel() }}</a>
                    @else
                        <a href="{{ route('login') }}">Iniciar sesión</a>
                        <a href="{{ route('registro', ['tipo' => 'empresa']) }}"><flux:icon.building-office-2 class="mr-2 size-4" />Registrarse como empresa</a>
                        <a href="{{ route('registro', ['tipo' => 'postulante']) }}"><flux:icon.user class="mr-2 size-4" />Registrarse como postulante</a>
                    @endauth
                </x-mobile-menu>
            </nav>
        </div>

        <div class="relative z-10 mx-auto flex min-h-[100svh] max-w-[1240px] items-center px-6 pb-10 pt-28 lg:px-10 lg:pb-12 lg:pt-28">
            <div class="max-w-[670px]">
                <span class="ad-eyebrow">Talento con experiencia</span>
                <h1 class="mt-5 max-w-[640px] text-[44px] leading-[.98] text-ink sm:text-[56px] lg:text-[66px]">
                    La experiencia no se archiva. <span class="text-orange-500">Se activa.</span>
                </h1>
                <p class="mt-7 max-w-[590px] text-[18px] font-medium leading-[1.7] text-gray-700 lg:text-[20px]">
                    AD+50 conecta la trayectoria de profesionales experimentados con empresas que buscan criterio,
                    oficio y capacidad para asumir el próximo desafío.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('registro') }}?tipo=empresa" class="ad-btn-primary">
                        Encontrar talento
                    </a>
                    <a href="{{ route('registro') }}?tipo=postulante" class="ad-btn-primary">
                        Crear mi perfil profesional
                    </a>
                </div>
            </div>
        </div>

        <div class="absolute bottom-10 right-10 z-10 hidden max-w-[230px] rounded-[14px] border border-white/50 bg-white/80 p-4 shadow-[var(--shadow-card)] backdrop-blur-md lg:block">
            <p class="font-display text-[19px] leading-[1.15] text-ink">Trayectorias reales. Nuevas oportunidades.</p>
            <p class="mt-2 text-[12px] font-bold uppercase tracking-[.12em] text-orange-600">AD Consulting · Chile</p>
        </div>
    </section>

    {{-- Quiénes somos · intro editorial centrada + pilares --}}
    <section id="quienes-somos" class="scroll-mt-24 border-b border-line bg-paper">
        <div class="mx-auto max-w-[1240px] px-6 py-24 lg:px-10 lg:py-28">
            <div class="mx-auto max-w-[760px] text-center">
                <span class="ad-eyebrow justify-center">Quiénes somos</span>
                <h2 class="mt-6 text-[46px] leading-[1.02] sm:text-[56px]">Experiencia que abre <span class="text-orange-500">nuevas oportunidades</span>.</h2>
                <p class="mt-6 text-[18px] leading-[1.75] text-gray-700">
                    AD+50 es una iniciativa de AD Consulting creada para conectar profesionales con amplia trayectoria y organizaciones que valoran el criterio, el oficio y la capacidad de seguir aportando.
                </p>
                <p class="mx-auto mt-4 max-w-[620px] text-[15px] leading-[1.7] text-gray-500">
                    Diseñamos una experiencia clara y respetuosa para que cada perfil sea evaluado por su experiencia real y cada empresa pueda tomar decisiones con información relevante.
                </p>
                <div class="mt-8 flex justify-center">
                    <a href="{{ route('quienes-somos') }}" class="ad-btn-ghost" wire:navigate>
                        Conoce más sobre AD+50
                        <flux:icon.arrow-right class="size-4" />
                    </a>
                </div>
            </div>

            <div class="mx-auto mt-16 grid max-w-[980px] gap-y-10 sm:grid-cols-3 sm:gap-0 sm:divide-x sm:divide-line">
                @foreach ([
                    ['academic-cap', 'Trayectoria primero', 'La experiencia se presenta con contexto y evidencia.'],
                    ['scale', 'Decisiones con criterio', 'El matching prioriza afinidad por sobre volumen.'],
                    ['shield-check', 'Datos bajo control', 'Cada persona administra su información y visibilidad.'],
                ] as [$icon, $title, $description])
                    <div class="sm:px-8 sm:first:pl-0 sm:last:pr-0">
                        <span class="grid size-12 place-items-center rounded-[13px] bg-orange-100 text-orange-700">
                            <flux:icon :name="$icon" class="size-6" />
                        </span>
                        <h3 class="mt-5 text-[20px] leading-[1.15] text-ink">{{ $title }}</h3>
                        <p class="mt-2 text-[15px] leading-[1.65] text-gray-500">{{ $description }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Cómo funciona para empresas --}}
    <section id="como-empresas" class="relative isolate scroll-mt-24 overflow-hidden bg-gradient-to-b from-orange-50/60 to-paper text-ink">
        <div class="pointer-events-none absolute -right-40 -top-48 size-[620px] rounded-full bg-orange-500/[.05] blur-[130px]"></div>
        <div class="pointer-events-none absolute -bottom-56 -left-40 size-[560px] rounded-full border-[90px] border-orange-500/[.03]"></div>
        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-orange-700 via-orange-500 to-orange-200"></div>

        <div class="relative mx-auto max-w-[1240px] px-6 py-28 lg:px-10 lg:py-36">
            <div class="grid gap-14 lg:grid-cols-[1.02fr_.98fr] lg:items-center lg:gap-24">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full border border-orange-200 bg-orange-50 px-4 py-2 text-[12px] font-extrabold uppercase tracking-[.18em] text-orange-600 shadow-[0_8px_30px_rgba(232,119,34,.1)]">
                        <flux:icon.building-office-2 class="size-4" />
                        Cómo funciona para empresas
                    </span>
                    <h2 class="mt-7 max-w-[610px] text-[50px] leading-[.98] text-ink sm:text-[64px] lg:text-[72px]">Menos volumen. <span class="text-orange-500">Más evidencia</span> para decidir.</h2>
                    <p class="mt-7 max-w-[560px] text-[18px] font-medium leading-[1.75] text-gray-700">
                        Define el desafío con criterios concretos y revisa únicamente profesionales cuya experiencia responde a lo que estás buscando.
                    </p>
                    <div class="mt-9 flex flex-wrap gap-3">
                        <a href="{{ route('registro', ['tipo' => 'empresa']) }}" class="ad-btn-primary">
                            Registrar mi empresa
                            <flux:icon.arrow-right class="size-4" />
                        </a>
                        <a href="{{ route('planes') }}" class="ad-btn-ghost">Ver planes</a>
                    </div>

                    
                </div>

                <ol class="relative">
                    @foreach ([
                        ['adjustments-horizontal', '01', 'Configura la búsqueda', 'Define cargo, experiencia, industria y ubicación según las necesidades reales del desafío.'],
                        ['user-group', '02', 'Recibe una lista relevante', 'AD+50 muestra profesionales que cumplen los criterios, reduciendo revisión manual y ruido.'],
                        ['check-badge', '03', 'Evalúa y contacta', 'Compara perfiles profesionales, guarda favoritos y accede a sus datos de contacto con un plan activo.'],
                    ] as [$icon, $number, $title, $description])
                        <li class="group relative grid grid-cols-[64px_1fr] gap-6 pb-9 last:pb-0">
                            @unless ($loop->last)
                                <span aria-hidden="true" class="absolute bottom-1 left-8 top-16 w-px -translate-x-1/2 bg-gradient-to-b from-orange-300 to-orange-200/30"></span>
                            @endunless
                            <span class="relative z-10 grid size-16 place-items-center rounded-full border border-orange-200 bg-white font-display text-[24px] font-black text-orange-600 shadow-[var(--shadow-card)] transition duration-300 group-hover:-translate-y-0.5 group-hover:shadow-[var(--shadow-card-lg)]">
                                {{ $number }}
                            </span>
                            <div class="pb-3 pt-2.5">
                                <div class="flex items-center gap-2.5">
                                    <flux:icon :name="$icon" class="size-5 text-orange-500" />
                                    <h3 class="text-[24px] leading-[1.1] text-ink">{{ $title }}</h3>
                                </div>
                                <p class="mt-2.5 text-[15px] leading-[1.7] text-gray-700">{{ $description }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </section>

    {{-- Cómo funciona para postulantes --}}
    <section id="como-postulantes" class="scroll-mt-24 border-y border-line bg-white">
        <div class="mx-auto max-w-[1240px] px-6 py-24 lg:px-10 lg:py-28">
            <div class="flex flex-col gap-8 md:flex-row md:items-end md:justify-between">
                <div class="max-w-[620px]">
                    <span class="inline-flex items-center gap-2 rounded-full border border-orange-200 bg-orange-50 px-4 py-2 text-[12px] font-extrabold uppercase tracking-[.18em] text-orange-600">
                        <flux:icon.user class="size-4" />
                        Cómo funciona para postulantes
                    </span>
                    <h2 class="mt-7 text-[46px] leading-[.98] sm:text-[56px]">Tu experiencia encuentra <span class="text-orange-500">nuevas oportunidades</span>.</h2>
                    <p class="mt-5 text-[17px] leading-[1.7] text-gray-700">
                        Construye un perfil profesional claro, decide cuándo estará visible y participa automáticamente en búsquedas compatibles con tu trayectoria.
                    </p>
                </div>
                <div class="shrink-0">
                    <a href="{{ route('registro', ['tipo' => 'postulante']) }}" class="ad-btn-primary">
                        Crear mi perfil profesional
                        <flux:icon.arrow-right class="size-4" />
                    </a>
                    <p class="mt-3 text-[12px] font-semibold text-gray-400">Crear tu perfil profesional es gratis.</p>
                </div>
            </div>

            <ol class="mt-14 grid gap-5 sm:grid-cols-3">
                @foreach ([
                    ['identification', '01', 'Crea tu perfil profesional', 'Reúne tu formación, experiencia, industrias e idiomas en un perfil único que puedes mantener actualizado.'],
                    ['eye', '02', 'Tú decides cuándo estar visible', 'Activa o pausa tu perfil y conserva el control sobre tus datos personales durante todo el proceso.'],
                    ['briefcase', '03', 'Aparece en búsquedas compatibles', 'Cuando tu experiencia cumple los criterios de una empresa, tu perfil se incorpora a una lista relevante.'],
                ] as [$icon, $number, $title, $description])
                    <li class="group relative flex flex-col overflow-hidden rounded-[22px] border border-line-2 bg-paper p-7 transition duration-300 hover:-translate-y-1 hover:border-orange-200 hover:bg-white hover:shadow-[var(--shadow-card-lg)] sm:p-8">
                        <div class="flex items-start justify-between">
                            <span class="grid size-12 place-items-center rounded-[13px] bg-orange-100 text-orange-700"><flux:icon :name="$icon" class="size-6" /></span>
                            <span class="font-display text-[46px] font-black leading-none text-orange-500/20">{{ $number }}</span>
                        </div>
                        <h3 class="mt-6 text-[22px] leading-[1.12] text-ink">{{ $title }}</h3>
                        <p class="mt-2.5 text-[15px] leading-[1.65] text-gray-500">{{ $description }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Planes --}}
    <section id="planes" class="scroll-mt-24 border-t border-line bg-white">
        <div class="mx-auto max-w-[1240px] px-6 py-24 lg:px-10 lg:py-28">
            <div class="mb-12 flex flex-col items-start justify-between gap-6 md:flex-row md:items-end">
                <div class="max-w-[720px]">
                    <span class="ad-eyebrow">Planes para empresas</span>
                    <h2 class="mt-5 text-[46px] sm:text-[56px]">Elige el alcance de tu búsqueda.</h2>
                    <p class="mt-5 max-w-[620px] text-[17px] leading-[1.7] text-gray-700">
                        Publica tus vacantes, recibe candidatos compatibles mediante nuestro sistema de matching y accede a los currículums que mejor se ajustan a tu búsqueda.
                    </p>
                </div>
            </div>

            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($planes as $plan)
                    <article wire:key="landing-plan-{{ $plan->id }}" @class([
                        'relative flex flex-col rounded-[20px] border bg-paper p-7',
                        'border-2 border-orange-500 shadow-[var(--shadow-card-lg)]' => $plan->destacado,
                        'border-line-2 shadow-[var(--shadow-card)]' => ! $plan->destacado,
                    ])>
                        @if ($plan->destacado)
                            <span class="absolute -top-3 left-6 rounded-full bg-orange-600 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-white">Más elegido</span>
                        @endif

                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <span class="text-[11px] font-extrabold uppercase tracking-[.14em] text-orange-600">
                                    Para empresas
                                </span>
                                <h3 class="mt-2 text-[24px]">{{ Str::after($plan->nombre, '· ') }}</h3>
                            </div>
                            <span class="grid size-10 shrink-0 place-items-center rounded-[11px] bg-orange-100 text-orange-700">
                                <flux:icon.building-office-2 class="size-5" />
                            </span>
                        </div>

                        <div class="mt-6 text-[34px] font-extrabold text-ink">
                            {{ number_format((float) $plan->precio_uf, 0, ',', '.') }}
                            <small class="text-[12px] font-bold text-gray-500">UF + IVA</small>
                        </div>
                        <p class="mt-1 text-[12px] font-semibold text-gray-500">{{ $plan->periodo === 'anual' ? 'plan anual' : 'pago único' }}</p>

                        <ul class="my-6 grid flex-1 gap-3">
                            @foreach ($plan->features ?? [] as $feature)
                                <li wire:key="landing-plan-{{ $plan->id }}-feature-{{ $loop->index }}" class="flex gap-2.5 text-[13.5px] font-semibold text-gray-700">
                                    <flux:icon.check class="mt-0.5 size-4 shrink-0 text-match" />
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>

                        <p class="mb-6 rounded-xl bg-orange-50 px-4 py-3 text-[13px] font-bold text-orange-700">{{ $plan->recomendacion }}</p>

                        <a href="{{ route('registro', ['tipo' => 'empresa']) }}" class="{{ $plan->destacado ? 'ad-btn-primary' : 'ad-btn-ghost' }} ad-btn-sm ad-btn-block">
                            Elegir este plan
                        </a>
                    </article>
                @empty
                    <div class="rounded-[18px] border border-line-2 bg-paper p-7 md:col-span-2 lg:col-span-3">
                        <p class="font-bold text-ink">Estamos preparando nuestros planes.</p>
                        <p class="mt-2 text-[14px] text-gray-500">Contáctanos para conocer las alternativas disponibles.</p>
                    </div>
                @endforelse

            </div>

            <x-plan-benefits class="mt-10" />
        </div>
    </section>

    {{-- Confiaron en nosotros --}}
    <section id="confiaron" class="border-y border-line bg-paper">
        <div class="mx-auto max-w-[1240px] px-6 py-14 lg:px-10 lg:py-16">
            <p class="text-center text-[12px] font-extrabold uppercase tracking-[.2em] text-gray-500">Confiaron en nosotros</p>
            <ul class="mt-10 grid grid-cols-2 items-center gap-x-8 gap-y-10 sm:grid-cols-3 lg:grid-cols-6 lg:gap-x-12">
                @foreach ([
                    ['Microsoft', 'microsoft'],
                    ['IBM', 'ibm'],
                    ['Siemens', 'siemens'],
                    ['DHL', 'dhl'],
                    ['SAP', 'sap'],
                    ['Oracle', 'oracle'],
                ] as [$marca, $slug])
                    <li wire:key="trusted-brand-{{ $slug }}" class="flex h-14 items-center justify-center">
                        <img
                            src="https://cdn.jsdelivr.net/npm/simple-icons@latest/icons/{{ $slug }}.svg"
                            alt="{{ $marca }}"
                            class="max-h-12 w-auto max-w-[144px] opacity-45 grayscale transition duration-300 hover:opacity-80 lg:max-w-[160px]"
                            loading="lazy"
                            decoding="async"
                        >
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- La tarjeta toma la claridad y estructura de la dirección corporativa elegida en el test. --}}
    <section class="hidden">
        <div class="mx-auto grid max-w-[1240px] items-center gap-12 px-6 py-24 lg:grid-cols-[.8fr_1.2fr] lg:px-10 lg:py-28">
            <div>
                <span class="ad-eyebrow">Decisiones más claras</span>
                <h2 class="mt-5 text-[46px] sm:text-[54px]">Una tarjeta que explica el match.</h2>
                <p class="mt-5 max-w-[430px] text-[16px] leading-[1.75] text-gray-700">
                    La información se presenta con jerarquía, evidencia y aire. El color acompaña la lectura; nunca
                    reemplaza los datos que sostienen una decisión.
                </p>
            </div>

            <article class="ad-match-preview rounded-[18px] border border-line-2 bg-white p-6 sm:p-8">
                <div class="flex flex-wrap items-start gap-5">
                    <div class="grid size-16 shrink-0 place-items-center rounded-[14px] bg-orange-100 text-[20px] font-extrabold text-orange-700 ring-1 ring-orange-200">MF</div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-extrabold uppercase tracking-[.15em] text-gray-400">Perfil profesional #208</p>
                                <h3 class="mt-1 text-[25px]">Gerencia de finanzas</h3>
                            </div>
                            <span class="ad-chip ad-chip-green"><flux:icon.check class="size-4" /> Cumple 5 de 5</span>
                        </div>
                        <p class="mt-3 text-[14px] font-semibold text-gray-700">18 años liderando equipos financieros y procesos de transformación.</p>
                    </div>
                </div>
                <div class="my-6 h-px bg-line"></div>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach (['Banca', '18 años', 'Concepción', 'MBA'] as $tag)
                        <span class="ad-chip">{{ $tag }}</span>
                    @endforeach
                    <a href="{{ route('registro') }}?tipo=empresa" class="ad-btn-primary ad-btn-sm ml-auto">Ver perfil profesional</a>
                </div>
            </article>
        </div>
    </section>

    <section class="bg-orange-500 text-white">
        <div class="mx-auto flex max-w-[1240px] flex-col items-start justify-between gap-8 px-6 py-16 md:flex-row md:items-center lg:px-10">
            <div class="max-w-[900px]">
                <span class="text-[11px] font-extrabold uppercase tracking-[.18em] text-white/70">El siguiente desafío empieza aquí</span>
                <h2 class="mt-3 text-[40px] text-white sm:text-[48px]">Experiencia lista para entrar en acción.</h2>
            </div>
            
        </div>
    </section>

    <footer id="seguridad" class="bg-[#4C4F51] text-white/85">
        <div class="mx-auto max-w-[1240px] px-6 py-14 lg:px-10">
            <div class="flex flex-wrap items-start justify-between gap-10 border-b border-white/10 pb-10">
                <div class="max-w-[390px]">
                    <img src="/images/ad50-logo.png" alt="AD+50 Talento Senior" class="h-20 w-auto rounded-[10px]" loading="lazy">
                    <p class="mt-5 text-[15px] leading-[1.7]">Una iniciativa de AD Consulting para conectar experiencia, criterio y nuevas oportunidades laborales.</p>
                </div>
                <div class="grid grid-cols-2 gap-x-12 gap-y-3 text-[15px] font-bold sm:grid-cols-3">
                    <a href="#como-postulantes" class="hover:text-white">Cómo funciona</a>
                    <a href="#como-empresas" class="hover:text-white">Para empresas</a>
                    <a href="#como-postulantes" class="hover:text-white">Para postulantes</a>
                    <a href="#quienes-somos" class="hover:text-white">Quiénes somos</a>
                    <a href="#planes" class="hover:text-white">Planes</a>
                    <a href="#seguridad" class="hover:text-white">Privacidad</a>
                    <a href="#seguridad" class="hover:text-white">Contacto</a>
                </div>
            </div>
            <div class="flex flex-wrap justify-between gap-3 pt-6 text-[14px]">
                <span>© {{ date('Y') }} AD Consulting · Concepción, Chile</span>
                <span>Plataforma de perfiles y matching de talento</span>
            </div>
        </div>
    </footer>
</div>
