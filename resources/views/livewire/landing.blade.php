<div>
    {{-- Hero editorial: la imagen y el mensaje fueron las preferencias más consistentes del test. --}}
    <section class="relative min-h-[760px] overflow-hidden border-b border-line bg-paper lg:min-h-[820px]"
        style="background-image: linear-gradient(90deg, #F6F6F4 0%, rgba(246,246,244,.98) 38%, rgba(246,246,244,.68) 57%, rgba(246,246,244,.05) 78%), url('/images/ad50-hero-experiencia.webp'); background-position: center, 68% center; background-size: cover;">
        <div class="absolute inset-x-0 top-0 h-32 bg-gradient-to-b from-black/65 to-transparent"></div>
        <div class="absolute inset-x-0 top-0 z-20">
            <nav class="mx-auto flex max-w-[1240px] items-center justify-between px-6 py-6 lg:px-10">
                <a href="{{ route('home') }}" class="overflow-hidden rounded-[10px]" aria-label="AD+50 Talento Senior, inicio">
                    <img src="/images/ad50-logo.png" alt="AD+50 Talento Senior" class="h-14 w-auto sm:h-16">
                </a>

                <div class="hidden items-center gap-1 rounded-[14px] border border-white/70 bg-white/75 p-1.5 text-[14px] font-bold text-ink shadow-[0_8px_30px_rgba(52,54,56,.08)] backdrop-blur-md md:flex">
                    <a href="#como" class="rounded-[10px] px-4 py-2.5 transition duration-200 hover:bg-orange-100 hover:text-orange-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500">Cómo funciona</a>
                    <a href="#empresas" class="rounded-[10px] px-4 py-2.5 transition duration-200 hover:bg-orange-100 hover:text-orange-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500">Para empresas</a>
                    <a href="#postulantes" class="rounded-[10px] px-4 py-2.5 transition duration-200 hover:bg-orange-100 hover:text-orange-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500">Para postulantes</a>
                </div>

                @auth
                    <a
                        href="{{ route(auth()->user()->dashboardRouteName()) }}"
                        class="ad-btn-ghost ad-btn-sm bg-paper/70 backdrop-blur"
                    >
                        {{ auth()->user()->dashboardLabel() }}
                    </a>
                @else
                    <a href="{{ route('registro') }}" class="ad-btn-ghost ad-btn-sm bg-paper/70 backdrop-blur">Ingresar</a>
                @endauth
            </nav>
        </div>

        <div class="relative z-10 mx-auto flex min-h-[760px] max-w-[1240px] items-center px-6 pb-24 pt-32 lg:min-h-[820px] lg:px-10">
            <div class="max-w-[670px]">
                <span class="ad-eyebrow">Talento con experiencia</span>
                <h1 class="mt-5 max-w-[640px] text-[52px] leading-[.96] text-ink sm:text-[66px] lg:text-[78px]">
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
                    <a href="{{ route('registro') }}?tipo=postulante" class="ad-btn-ghost bg-paper/70 backdrop-blur">
                        Activar mi perfil
                    </a>
                </div>

                <div class="mt-11 flex max-w-[590px] flex-wrap gap-x-8 gap-y-4 border-t border-line-2 pt-6">
                    @foreach ([
                        ['+50', 'experiencia que suma'],
                        ['1', 'ficha profesional'],
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
    <section class="bg-ink text-white">
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

    {{-- Cómo funciona --}}
    <section id="como" class="mx-auto max-w-[1240px] px-6 py-24 lg:px-10 lg:py-32">
        <div class="grid gap-12 lg:grid-cols-[.75fr_1.25fr] lg:gap-20">
            <div>
                <span class="ad-eyebrow">Cómo funciona</span>
                <h2 class="mt-5 text-[46px] sm:text-[56px]">Menos ruido. Mejores coincidencias.</h2>
                <p class="mt-5 max-w-[440px] text-[17px] leading-[1.7] text-gray-700">
                    Dos perfiles claros y un proceso de selección que pone el foco en lo importante: el ajuste entre
                    la experiencia disponible y el desafío de la empresa.
                </p>
            </div>

            <ol class="border-t border-line-2">
                @foreach ([
                    ['01', 'Parte con información clara y relevante', 'El postulante presenta su trayectoria y la empresa describe el desafío, con datos estructurados que permiten encontrar afinidades reales.'],
                    ['02', 'Define la búsqueda con criterios concretos', 'La empresa decide qué condiciones filtran y cuáles quedan abiertas para ampliar posibilidades.'],
                    ['03', 'Revisa una lista breve y relevante', 'La plataforma muestra únicamente los perfiles que cumplen, ordenados para facilitar la decisión.'],
                ] as [$number, $title, $description])
                    <li class="grid gap-4 border-b border-line-2 py-7 sm:grid-cols-[64px_1fr]">
                        <span class="font-display text-[31px] text-orange-500">{{ $number }}</span>
                        <div>
                            <h3 class="text-[25px] leading-[1.1]">{{ $title }}</h3>
                            <p class="mt-2 text-[15px] leading-[1.65] text-gray-700">{{ $description }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Dos públicos, una identidad --}}
    <section id="empresas" class="border-y border-line bg-white">
        <div class="mx-auto max-w-[1240px] px-6 py-24 lg:px-10 lg:py-28">
            <div class="mb-12 max-w-[720px]">
                <span class="ad-eyebrow">Una plataforma, dos puntos de partida</span>
                <h2 class="mt-5 text-[46px] sm:text-[56px]">La trayectoria encuentra su próximo lugar.</h2>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <article class="rounded-[20px] border border-line-2 bg-paper p-8 sm:p-10">
                    <div class="mb-8 flex items-center justify-between">
                        <span class="grid size-12 place-items-center rounded-[12px] bg-orange-600 text-white shadow-[0_8px_20px_rgba(185,79,8,.2)]">
                            <flux:icon.building-office-2 class="size-6" />
                        </span>
                        <span class="text-[11px] font-extrabold uppercase tracking-[.16em] text-gray-500">Para empresas</span>
                    </div>
                    <h3 class="text-[34px]">Selecciona por evidencia, no por volumen.</h3>
                    <p class="mt-4 text-[16px] leading-[1.7] text-gray-700">Recibe candidatos prefiltrados según los criterios que realmente importan para tu búsqueda.</p>
                    <ul class="my-7 grid gap-3">
                        @foreach (['Lista corta y relevante', 'Criterios de búsqueda transparentes', 'Contacto directo con plan activo'] as $item)
                            <li class="flex gap-3 text-[14px] font-bold text-gray-700">
                                <flux:icon.check class="size-5 shrink-0 text-sage" />{{ $item }}
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('registro') }}?tipo=empresa" class="ad-btn-primary">Crear una búsqueda</a>
                </article>

                <article id="postulantes" class="relative overflow-hidden rounded-[20px] bg-ink p-8 text-white sm:p-10">
                    <div class="absolute -right-20 -top-20 size-64 rounded-full border-[50px] border-orange-500/10"></div>
                    <div class="relative">
                        <div class="mb-8 flex items-center justify-between">
                            <span class="grid size-12 place-items-center rounded-[12px] bg-orange-500 text-white">
                                <flux:icon.user class="size-6" />
                            </span>
                            <span class="text-[11px] font-extrabold uppercase tracking-[.16em] text-white/50">Para postulantes</span>
                        </div>
                        <h3 class="text-[34px] text-white">Haz visible todo lo que sabes hacer.</h3>
                        <p class="mt-4 text-[16px] leading-[1.7] text-white/70">Una ficha profesional activa, actualizable y bajo tu control para aparecer en búsquedas compatibles.</p>
                        <ul class="my-7 grid gap-3">
                            @foreach (['Tu experiencia en un solo lugar', 'Visibilidad siempre bajo tu control', 'Datos protegidos y actualizables'] as $item)
                                <li class="flex gap-3 text-[14px] font-bold text-white/75">
                                    <flux:icon.check class="size-5 shrink-0 text-orange-500" />{{ $item }}
                                </li>
                            @endforeach
                        </ul>
                        <a href="{{ route('registro') }}?tipo=postulante" class="ad-btn-light">Crear mi ficha</a>
                    </div>
                </article>
            </div>
        </div>
    </section>

    {{-- La tarjeta toma la claridad y estructura de la dirección corporativa elegida en el test. --}}
    <section class="overflow-hidden bg-[#E7E7E4]">
        <div class="mx-auto grid max-w-[1240px] items-center gap-12 px-6 py-24 lg:grid-cols-[.8fr_1.2fr] lg:px-10 lg:py-28">
            <div>
                <span class="ad-eyebrow">Decisiones más claras</span>
                <h2 class="mt-5 text-[46px] sm:text-[54px]">Una tarjeta que explica el match.</h2>
                <p class="mt-5 max-w-[430px] text-[16px] leading-[1.75] text-gray-700">
                    La información se presenta con jerarquía, evidencia y aire. El color acompaña la lectura; nunca
                    reemplaza los datos que sostienen una decisión.
                </p>
            </div>

            <article class="rounded-[18px] border border-line-2 bg-white p-6 shadow-[var(--shadow-card-lg)] sm:p-8">
                <div class="flex flex-wrap items-start gap-5">
                    <div class="grid size-16 shrink-0 place-items-center rounded-[14px] bg-sage-100 text-[20px] font-extrabold text-ink">MF</div>
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
                    <a href="{{ route('registro') }}?tipo=empresa" class="ad-btn-primary ad-btn-sm ml-auto">Ver ficha</a>
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
                <a href="{{ route('planes') }}" class="ad-btn bg-ink text-white hover:bg-ink-2">Ver planes</a>
            </div>
        </div>
    </section>

    <footer id="seguridad" class="bg-ink text-white/60">
        <div class="mx-auto max-w-[1240px] px-6 py-14 lg:px-10">
            <div class="flex flex-wrap items-start justify-between gap-10 border-b border-white/10 pb-10">
                <div class="max-w-[390px]">
                    <img src="/images/ad50-logo.png" alt="AD+50 Talento Senior" class="h-20 w-auto rounded-[10px]" loading="lazy">
                    <p class="mt-5 text-[14px] leading-[1.7]">Una iniciativa de AD Consulting para conectar experiencia, criterio y nuevas oportunidades laborales.</p>
                </div>
                <div class="grid grid-cols-2 gap-x-12 gap-y-3 text-[13px] font-bold sm:grid-cols-3">
                    <a href="#como" class="hover:text-white">Cómo funciona</a>
                    <a href="#empresas" class="hover:text-white">Para empresas</a>
                    <a href="#postulantes" class="hover:text-white">Para postulantes</a>
                    <a href="{{ route('planes') }}" class="hover:text-white">Planes</a>
                    <a href="#seguridad" class="hover:text-white">Privacidad</a>
                    <a href="#seguridad" class="hover:text-white">Contacto</a>
                </div>
            </div>
            <div class="flex flex-wrap justify-between gap-3 pt-6 text-[12px]">
                <span>© {{ date('Y') }} AD Consulting · Concepción, Chile</span>
                <span>Plataforma de perfiles y matching de talento</span>
            </div>
        </div>
    </footer>
</div>
