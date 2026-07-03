<div class="ad-welcome-light">
    {{-- Hero editorial: la imagen y el mensaje fueron las preferencias más consistentes del test. --}}
    <section class="ad-light-surface relative min-h-[760px] overflow-hidden border-b border-line bg-paper lg:min-h-[820px]"
        style="background-image: linear-gradient(90deg, #F6F6F4 0%, rgba(246,246,244,.98) 38%, rgba(246,246,244,.68) 57%, rgba(246,246,244,.05) 78%), url('/images/ad50-hero-experiencia.webp'); background-position: center, 68% center; background-size: cover;">
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

                <div class="hidden items-center gap-1 rounded-[14px] border border-white/70 bg-white/75 p-1.5 text-[15px] font-bold text-ink shadow-[0_8px_30px_rgba(52,54,56,.08)] backdrop-blur-md md:flex">
                    <a href="#quienes-somos" class="rounded-[10px] px-4 py-2.5 transition duration-200 hover:bg-orange-100 hover:text-orange-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500">Quiénes somos</a>
                    <flux:dropdown position="bottom" align="start">
                        <button type="button" class="inline-flex items-center gap-1.5 rounded-[10px] px-4 py-2.5 transition duration-200 hover:bg-orange-100 hover:text-orange-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500" aria-label="Elegir cómo funciona AD+50">
                            Cómo funciona
                            <flux:icon.chevron-down class="size-4" />
                        </button>
                        <flux:menu>
                            <flux:menu.item href="#como-postulantes" icon="user">Postulantes</flux:menu.item>
                            <flux:menu.item href="#como-empresas" icon="building-office-2">Empresas</flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                    <a href="#planes" class="rounded-[10px] px-4 py-2.5 transition duration-200 hover:bg-orange-100 hover:text-orange-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500">Planes</a>
                </div>

                @auth
                    <a
                        href="{{ route(auth()->user()->dashboardRouteName()) }}"
                        class="ad-btn-ghost ad-btn-sm bg-paper/70 backdrop-blur"
                    >
                        {{ auth()->user()->dashboardLabel() }}
                    </a>
                @else
                    <div class="flex items-center gap-2">
                        <a href="{{ route('login') }}" class="ad-btn-primary ad-btn-sm">Iniciar sesión</a>
                        <flux:dropdown position="bottom" align="end">
                            <button type="button" class="ad-btn-primary ad-btn-sm" aria-label="Elegir tipo de registro">
                                Registrarse
                                <flux:icon.chevron-down class="size-4" />
                            </button>
                            <flux:menu>
                                <flux:menu.item :href="route('registro', ['tipo' => 'postulante'])" icon="user" wire:navigate>
                                    Postulante
                                </flux:menu.item>
                                <flux:menu.item :href="route('registro', ['tipo' => 'empresa'])" icon="building-office-2" wire:navigate>
                                    Empresa
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                @endauth
            </nav>
        </div>

        <div class="relative z-10 mx-auto flex min-h-[760px] max-w-[1240px] items-center px-6 pb-24 pt-32 lg:min-h-[820px] lg:px-10">
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
                        <flux:icon.arrow-right class="size-4" />
                    </a>
                    <a href="{{ route('registro') }}?tipo=postulante" class="ad-btn-primary">
                        Crear mi perfil
                    </a>
                </div>

                <div class="mt-11 flex max-w-[590px] flex-wrap gap-x-8 gap-y-4 border-t border-line-2 pt-6">
                    @foreach ([
                        ['+50', 'experiencia que suma'],
                        ['1', 'perfil profesional'],
                        ['100%', 'datos bajo control'],
                    ] as [$value, $label])
                        <div class="min-w-[120px]">
                            <strong class="block text-[24px] font-extrabold text-ink">{{ $value }}</strong>
                            <span class="text-[12px] font-bold uppercase tracking-[.08em] text-gray-500">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="absolute bottom-6 right-6 z-10 hidden max-w-[230px] rounded-[14px] border border-white/50 bg-white/80 p-4 shadow-[var(--shadow-card)] backdrop-blur-md lg:block">
            <p class="font-display text-[19px] leading-[1.15] text-ink">Trayectorias reales. Nuevas oportunidades.</p>
            <p class="mt-2 text-[12px] font-bold uppercase tracking-[.12em] text-orange-600">AD Consulting · Chile</p>
        </div>
    </section>

    {{-- Franja de confianza --}}
    <section class="bg-[#75787B] text-white">
        <div class="mx-auto grid max-w-[1240px] gap-6 px-6 py-7 md:grid-cols-[1.4fr_1fr_1fr] md:items-center lg:px-10">
            <p class="font-display text-[24px] leading-tight">El futuro del trabajo también se construye con experiencia.</p>
            <div class="flex items-center gap-3 text-[13px] font-bold text-white/70">
                <flux:icon.shield-check class="size-5 text-orange-500" />
                Datos gestionados por AD Consulting
            </div>
            <div class="flex items-center gap-3 text-[13px] font-bold text-white/70">
                <flux:icon.check-circle class="size-5 text-orange-500" />
                Matching según criterios reales
            </div>
        </div>
    </section>

    {{-- Quiénes somos --}}
    <section id="quienes-somos" class="scroll-mt-24 border-b border-line bg-paper">
        <div class="mx-auto grid max-w-[1240px] items-center gap-12 px-6 py-24 lg:grid-cols-[.9fr_1.1fr] lg:gap-20 lg:px-10 lg:py-28">
            <div>
                <span class="ad-eyebrow">Quiénes somos</span>
                <h2 class="mt-5 max-w-[560px] text-[46px] sm:text-[56px]">Experiencia que abre nuevas oportunidades.</h2>
                <p class="mt-5 max-w-[560px] text-[17px] leading-[1.75] text-gray-700">
                    AD+50 es una iniciativa de AD Consulting creada para conectar profesionales con amplia trayectoria y organizaciones que valoran el criterio, el oficio y la capacidad de seguir aportando.
                </p>
                <p class="mt-4 max-w-[560px] text-[15px] leading-[1.75] text-gray-500">
                    Diseñamos una experiencia clara y respetuosa para que cada perfil sea evaluado por su experiencia real y cada empresa pueda tomar decisiones con información relevante.
                </p>
            </div>

            <div class="rounded-[22px] border border-line-2 bg-white p-6 shadow-[var(--shadow-card)] sm:p-8">
                <div class="mb-7 flex items-center gap-4 border-b border-line pb-6">
                    <span class="grid size-12 shrink-0 place-items-center rounded-[12px] bg-orange-100 text-orange-700">
                        <flux:icon.sparkles class="size-6" />
                    </span>
                    <div>
                        <p class="text-[11px] font-extrabold uppercase tracking-[.16em] text-orange-600">Nuestro enfoque</p>
                        <h3 class="mt-1 text-[25px]">Talento senior en el centro.</h3>
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-3">
                    @foreach ([
                        ['academic-cap', 'Trayectoria primero', 'La experiencia se presenta con contexto y evidencia.'],
                        ['scale', 'Decisiones con criterio', 'El matching prioriza afinidad por sobre volumen.'],
                        ['shield-check', 'Datos bajo control', 'Cada persona administra su información y visibilidad.'],
                    ] as [$icon, $title, $description])
                        <div>
                            <flux:icon :name="$icon" class="size-5 text-orange-500" />
                            <h4 class="mt-3 text-[15px] font-extrabold text-ink">{{ $title }}</h4>
                            <p class="mt-2 text-[13px] leading-[1.6] text-gray-500">{{ $description }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Cómo funciona para postulantes --}}
    <section id="como-postulantes" class="scroll-mt-24 border-y border-line bg-white">
        <div class="mx-auto max-w-[1240px] px-6 py-24 lg:px-10 lg:py-28">
            <div class="grid items-start gap-12 lg:grid-cols-[.78fr_1.22fr] lg:gap-20">
                <div class="lg:sticky lg:top-28">
                    <span class="ad-eyebrow">Cómo funciona para postulantes</span>
                    <h2 class="mt-5 text-[46px] sm:text-[56px]">Tu experiencia encuentra nuevas oportunidades.</h2>
                    <p class="mt-5 max-w-[470px] text-[17px] leading-[1.7] text-gray-700">
                        Construye un perfil profesional claro, decide cuándo estará visible y participa automáticamente en búsquedas compatibles con tu trayectoria.
                    </p>
                    <a href="{{ route('registro', ['tipo' => 'postulante']) }}" class="ad-btn-primary mt-8">
                        Crear mi perfil profesional
                        <flux:icon.arrow-right class="size-4" />
                    </a>
                </div>

                <ol class="grid gap-4">
                    @foreach ([
                        ['01', 'Crea tu perfil profesional', 'Reúne tu formación, experiencia, industrias e idiomas en un perfil único que puedes mantener actualizado.'],
                        ['02', 'Tú decides cuándo estar visible', 'Activa o pausa tu perfil y conserva el control sobre tus datos personales durante todo el proceso.'],
                        ['03', 'Aparece en búsquedas compatibles', 'Cuando tu experiencia cumple los criterios de una empresa, tu perfil se incorpora a una lista relevante.'],
                    ] as [$number, $title, $description])
                        <li class="ad-postulante-card relative overflow-hidden rounded-[18px] border border-orange-200 p-6 sm:p-7">
                            <div class="relative grid gap-4 sm:grid-cols-[64px_1fr]">
                                <span class="font-display text-[31px] text-orange-500">{{ $number }}</span>
                                <div>
                                    <h3 class="text-[25px] leading-[1.1] text-ink">{{ $title }}</h3>
                                    <p class="mt-2 text-[15px] leading-[1.65] text-gray-700">{{ $description }}</p>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </section>

    {{-- Cómo funciona para empresas --}}
    <section id="como-empresas" class="scroll-mt-24 bg-[#343638] text-white">
        <div class="mx-auto max-w-[1240px] px-6 py-24 lg:px-10 lg:py-28">
            <div class="grid gap-12 lg:grid-cols-[.85fr_1.15fr] lg:gap-20">
                <div>
                    <span class="inline-flex items-center gap-2 text-[12px] font-extrabold uppercase tracking-[.18em] text-orange-200">
                        <span class="h-px w-7 bg-orange-500"></span>
                        Cómo funciona para empresas
                    </span>
                    <h2 class="mt-5 max-w-[520px] text-[46px] text-white sm:text-[56px]">Menos volumen. Más evidencia para decidir.</h2>
                    <p class="mt-5 max-w-[490px] text-[17px] leading-[1.7] text-white/70">
                        Define el desafío con criterios concretos y revisa únicamente profesionales cuya experiencia responde a lo que estás buscando.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('registro', ['tipo' => 'empresa']) }}" class="ad-btn-primary">
                            Registrar mi empresa
                            <flux:icon.arrow-right class="size-4" />
                        </a>
                        <a href="{{ route('planes') }}" class="ad-btn-light">Ver planes</a>
                    </div>
                </div>

                <ol class="grid gap-4 sm:grid-cols-3 lg:grid-cols-1">
                    @foreach ([
                        ['01', 'Configura la búsqueda', 'Define cargo, experiencia, industria y ubicación según las necesidades reales del desafío.'],
                        ['02', 'Recibe una lista relevante', 'AD+50 muestra profesionales que cumplen los criterios, reduciendo revisión manual y ruido.'],
                        ['03', 'Evalúa y contacta', 'Compara perfiles profesionales, guarda favoritos y accede a sus datos de contacto con un plan activo.'],
                    ] as [$number, $title, $description])
                        <li class="rounded-[18px] border border-white/15 bg-white/[.06] p-6 backdrop-blur-sm sm:p-7">
                            <div class="grid gap-4 lg:grid-cols-[64px_1fr]">
                                <span class="font-display text-[31px] text-orange-200">{{ $number }}</span>
                                <div>
                                    <h3 class="text-[24px] leading-[1.1] text-white">{{ $title }}</h3>
                                    <p class="mt-2 text-[14px] leading-[1.65] text-white/65">{{ $description }}</p>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </section>

    {{-- Planes --}}
    <section id="planes" class="scroll-mt-24 border-t border-line bg-white">
        <div class="mx-auto max-w-[1240px] px-6 py-24 lg:px-10 lg:py-28">
            <div class="mb-12 flex flex-col items-start justify-between gap-6 md:flex-row md:items-end">
                <div class="max-w-[720px]">
                    <span class="ad-eyebrow">Planes AD+50</span>
                    <h2 class="mt-5 text-[46px] sm:text-[56px]">Elige cómo quieres participar.</h2>
                    <p class="mt-5 max-w-[620px] text-[17px] leading-[1.7] text-gray-700">
                        Activa tu perfil profesional o selecciona el alcance que tu empresa necesita para encontrar y contactar talento senior.
                    </p>
                </div>
                <a href="{{ route('planes') }}" class="ad-btn-ghost ad-btn-sm">
                    Ver todos los planes
                    <flux:icon.arrow-right class="size-4" />
                </a>
            </div>

            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($planes as $plan)
                    <article @class([
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
                                    {{ $plan->audiencia === 'empresa' ? 'Para empresas' : 'Para postulantes' }}
                                </span>
                                <h3 class="mt-2 text-[24px]">{{ Str::after($plan->nombre, '· ') }}</h3>
                            </div>
                            <span class="grid size-10 shrink-0 place-items-center rounded-[11px] bg-orange-100 text-orange-700">
                                <flux:icon :name="$plan->audiencia === 'empresa' ? 'building-office-2' : 'user'" class="size-5" />
                            </span>
                        </div>

                        <div class="mt-6 text-[34px] font-extrabold text-ink">
                            ${{ number_format($plan->precio_clp, 0, ',', '.') }}
                            <small class="text-[12px] font-bold text-gray-500">CLP</small>
                        </div>
                        <p class="mt-1 text-[12px] font-semibold text-gray-500">{{ $plan->periodo === 'único' ? 'pago único' : 'por '.$plan->periodo }}</p>

                        <ul class="my-6 grid flex-1 gap-3">
                            @foreach ($plan->features ?? [] as $feature)
                                <li class="flex gap-2.5 text-[13.5px] font-semibold text-gray-700">
                                    <flux:icon.check class="mt-0.5 size-4 shrink-0 text-match" />
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>

                        <a href="{{ route('registro', ['tipo' => $plan->audiencia]) }}" class="{{ $plan->destacado ? 'ad-btn-primary' : 'ad-btn-ghost' }} ad-btn-sm ad-btn-block">
                            {{ $plan->audiencia === 'empresa' ? 'Elegir este plan' : 'Crear mi perfil' }}
                        </a>
                    </article>
                @empty
                    <div class="rounded-[18px] border border-line-2 bg-paper p-7 md:col-span-2 lg:col-span-3">
                        <p class="font-bold text-ink">Estamos preparando nuestros planes.</p>
                        <p class="mt-2 text-[14px] text-gray-500">Contáctanos para conocer las alternativas disponibles.</p>
                    </div>
                @endforelse
            </div>
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
            <div class="max-w-[700px]">
                <span class="text-[11px] font-extrabold uppercase tracking-[.18em] text-white/70">El siguiente desafío empieza aquí</span>
                <h2 class="mt-3 text-[40px] text-white sm:text-[48px]">Experiencia lista para entrar en acción.</h2>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('registro') }}" class="ad-btn-light">Crear una cuenta</a>
                <a href="{{ route('planes') }}" class="ad-btn bg-[#343638] text-white hover:bg-[#4C4F51]">Ver planes</a>
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
