<div class="min-h-screen bg-paper text-ink">
    {{-- Header --}}
    <header class="border-b border-line bg-white dark:bg-[#1D2022]">
        <div class="mx-auto flex max-w-[1240px] items-center justify-between gap-4 px-6 py-4 lg:px-10">
            <a href="{{ route('home') }}" class="ad-logo" aria-label="AD+50 Talento Senior"><img src="/images/ad50-logo.png" alt="AD+50 Talento Senior" class="ad-brand-logo"></a>
            <div class="hidden flex-wrap items-center justify-end gap-2 md:flex sm:gap-3">
                <a href="{{ route('home') }}" class="ad-btn-ghost ad-btn-sm gap-2">
                    <flux:icon.arrow-left class="size-4" />
                    <span>Volver al inicio</span>
                </a>
                <a href="{{ route('registro', ['tipo' => 'empresa']) }}" class="ad-btn-primary ad-btn-sm">Crear cuenta</a>
            </div>
            <x-mobile-menu id="quienes-somos-mobile-navigation">
                <a href="{{ route('home') }}"><flux:icon.arrow-left class="mr-2 size-4" />Volver al inicio</a>
                <a href="{{ route('planes') }}">Planes</a>
                <a href="{{ route('registro', ['tipo' => 'empresa']) }}">Crear cuenta</a>
            </x-mobile-menu>
        </div>
    </header>

    {{-- Hero --}}
    <section class="relative isolate overflow-hidden border-b border-line bg-gradient-to-b from-orange-50/60 to-paper">
        <div class="pointer-events-none absolute -right-40 -top-48 size-[620px] rounded-full bg-orange-500/[.05] blur-[130px]"></div>
        <div class="relative mx-auto max-w-[1240px] px-6 py-24 lg:px-10 lg:py-28">
            <div class="mx-auto max-w-[820px] text-center">
                <span class="ad-eyebrow justify-center">Quiénes somos</span>
                <h1 class="mt-6 text-[44px] leading-[1.02] sm:text-[58px]">El futuro del trabajo también se construye con <span class="text-orange-500">experiencia</span>.</h1>
                <p class="mx-auto mt-7 max-w-[640px] text-[19px] leading-[1.75] text-gray-700">
                    AD+50 es una iniciativa de <strong class="font-extrabold text-ink">AD Consulting</strong> creada para instalar una mirada estratégica sobre el talento senior: conectamos a profesionales con amplia trayectoria con organizaciones que valoran el criterio, el oficio y la capacidad de seguir aportando.
                </p>
                <p class="mx-auto mt-4 max-w-[560px] text-[16px] font-semibold text-gray-500">
                    «El talento está cambiando, las empresas no. El problema no es la edad, es cómo la gestionamos.»
                </p>
            </div>
        </div>
    </section>

    {{-- El desafío --}}
    <section class="border-b border-line bg-paper">
        <div class="mx-auto max-w-[1240px] px-6 py-24 lg:px-10 lg:py-28">
            <div class="mx-auto max-w-[760px] text-center">
                <span class="ad-eyebrow justify-center">El desafío</span>
                <h2 class="mt-6 text-[38px] leading-[1.05] sm:text-[46px]">Chile está envejeciendo y su talento también.</h2>
                <p class="mt-6 text-[18px] leading-[1.75] text-gray-700">
                    Vivimos un cambio demográfico profundo. La experiencia acumulada por miles de profesionales sigue siendo valiosa, pero las organizaciones aún no cuentan con las herramientas para gestionarla y aprovecharla.
                </p>
            </div>

            <div class="mx-auto mt-16 grid max-w-[980px] gap-y-10 sm:grid-cols-3 sm:gap-0 sm:divide-x sm:divide-line">
                @foreach ([
                    ['users', 'La fuerza laboral está envejeciendo', 'Cada año más personas superan los 50 y siguen con plena capacidad y voluntad de aportar.'],
                    ['arrow-trending-down', 'Se pierde experiencia clave', 'Conocimiento, criterio y redes que costó décadas construir salen de las organizaciones.'],
                    ['exclamation-triangle', 'Las empresas no están preparadas', 'Faltan procesos, cultura y políticas para incorporar y retener al talento senior.'],
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

    {{-- Impacto en tres actores --}}
    <section class="border-b border-line bg-gradient-to-b from-orange-50/50 to-paper">
        <div class="mx-auto max-w-[1240px] px-6 py-24 lg:px-10 lg:py-28">
            <div class="mx-auto max-w-[760px] text-center">
                <span class="ad-eyebrow justify-center">Por qué importa</span>
                <h2 class="mt-6 text-[38px] leading-[1.05] sm:text-[46px]">Cuando la experiencia se desperdicia, <span class="text-orange-500">todos pierden</span>.</h2>
            </div>

            <div class="mt-16 grid gap-6 lg:grid-cols-3">
                @foreach ([
                    ['building-office-2', 'La empresa', 'Pierde productividad, conocimiento y continuidad operacional.'],
                    ['user', 'El profesional +50', 'Pierde vigencia, ingresos y trayectoria.'],
                    ['globe-americas', 'La sociedad', 'Pierde competitividad y sostenibilidad económica.'],
                ] as [$icon, $title, $description])
                    <article class="ad-card p-8">
                        <span class="grid size-12 place-items-center rounded-[13px] bg-orange-100 text-orange-700">
                            <flux:icon :name="$icon" class="size-6" />
                        </span>
                        <h3 class="mt-5 text-[22px] leading-[1.15] text-ink">{{ $title }}</h3>
                        <p class="mt-2.5 text-[15px] leading-[1.7] text-gray-600 dark:text-gray-300">{{ $description }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Nuestra propuesta · cinco líneas de acción --}}
    <section class="border-b border-line bg-paper">
        <div class="mx-auto max-w-[1240px] px-6 py-24 lg:px-10 lg:py-28">
            <div class="mx-auto max-w-[760px] text-center">
                <span class="ad-eyebrow justify-center">Nuestra propuesta</span>
                <h2 class="mt-6 text-[38px] leading-[1.05] sm:text-[46px]">Cinco líneas de acción para gestionar el talento senior.</h2>
                <p class="mt-6 text-[18px] leading-[1.75] text-gray-700">
                    Abordamos el reclutamiento especializado, el entrenamiento continuo, la actualización de competencias y la consultoría para la convivencia intergeneracional.
                </p>
            </div>

            <div class="mt-16 grid gap-6 md:grid-cols-2">
                @foreach ([
                    ['arrow-trending-up', '01', 'Empleabilidad y reinvención +50', 'Plataforma para empleabilidad, outplacement y mentoría especializada — el corazón de AD+50, donde profesionales y empresas se encuentran.'],
                    ['cpu-chip', '02', 'Actualización digital e inteligencia artificial', 'Academia para el desarrollo de competencias digitales y el dominio de las nuevas herramientas laborales.'],
                    ['user-group', '03', 'Integración intergeneracional', 'Consultoría cultural y gestión de la inclusión etaria para que distintas generaciones colaboren.'],
                    ['briefcase', '04', 'Reclutamiento senior', 'Esquemas flexibles de contratación adaptados a las necesidades operacionales de cada organización.'],
                    ['sparkles', '05', 'SelloMayor', 'Estrategias de adaptación organizacional ante los desafíos demográficos y el cambio en la fuerza laboral.'],
                ] as [$icon, $number, $title, $description])
                    <article class="ad-card group flex gap-6 p-8 transition duration-300 hover:-translate-y-1 hover:border-orange-200 hover:shadow-[var(--shadow-card-lg)] {{ $loop->last ? 'md:col-span-2' : '' }}">
                        <div class="flex flex-col items-center gap-3">
                            <span class="grid size-14 place-items-center rounded-full border border-orange-200 bg-white font-display text-[20px] font-black text-orange-600 shadow-[var(--shadow-card)]">{{ $number }}</span>
                            <flux:icon :name="$icon" class="size-6 text-orange-500" />
                        </div>
                        <div>
                            <h3 class="text-[22px] leading-[1.15] text-ink">{{ $title }}</h3>
                            <p class="mt-2.5 text-[15px] leading-[1.7] text-gray-600 dark:text-gray-300">{{ $description }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Equipo fundador --}}
    <section class="border-b border-line bg-gradient-to-b from-orange-50/50 to-paper">
        <div class="mx-auto max-w-[1240px] px-6 py-24 lg:px-10 lg:py-28">
            <div class="mx-auto max-w-[760px] text-center">
                <span class="ad-eyebrow justify-center">Equipo fundador</span>
                <h2 class="mt-6 text-[38px] leading-[1.05] sm:text-[46px]">Personas con trayectoria detrás de la iniciativa.</h2>
            </div>

            <div class="mx-auto mt-16 grid max-w-[1040px] gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Gian Piero Lavezzo', 'Socio Gerente', 'Psicólogo · Executive MBA'],
                    ['Ariela Dymensztain', 'Socia Directora', 'Psicóloga · Executive Coach'],
                    ['Nora Au', 'Directora', 'Ingeniera Senior · Directorios y ONGs'],
                    ['Patricia Palacios', 'Directora', 'Abogada · Directorios y ONGs'],
                ] as [$nombre, $cargo, $credenciales])
                    <article class="ad-card p-7 text-center">
                        <div class="mx-auto grid size-16 place-items-center rounded-full bg-orange-100 text-[20px] font-extrabold text-orange-700 ring-1 ring-orange-200">
                            {{ \Illuminate\Support\Str::of($nombre)->explode(' ')->map(fn ($p) => \Illuminate\Support\Str::substr($p, 0, 1))->take(2)->implode('') }}
                        </div>
                        <h3 class="mt-5 text-[18px] leading-[1.2] text-ink">{{ $nombre }}</h3>
                        <p class="mt-1 text-[13px] font-extrabold uppercase tracking-[.12em] text-orange-600">{{ $cargo }}</p>
                        <p class="mt-2 text-[13.5px] leading-[1.55] text-gray-500">{{ $credenciales }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Contacto + CTA --}}
    <section class="bg-orange-500 text-white">
        <div class="mx-auto max-w-[1240px] px-6 py-20 lg:px-10">
            <div class="grid gap-12 lg:grid-cols-[1fr_1fr] lg:items-center">
                <div>
                    <span class="text-[11px] font-extrabold uppercase tracking-[.18em] text-white/70">Conversemos</span>
                    <h2 class="mt-3 text-[38px] leading-[1.05] text-white sm:text-[46px]">Sumemos experiencia a tu organización.</h2>
                    <p class="mt-5 max-w-[480px] text-[17px] leading-[1.7] text-white/90">
                        Escríbenos para conocer cómo AD+50 y AD Consulting pueden acompañar a tu empresa o impulsar tu próxima etapa profesional.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('registro', ['tipo' => 'empresa']) }}" class="inline-flex items-center gap-2 rounded-[12px] bg-white px-6 py-3 font-bold text-orange-600 transition hover:bg-orange-50">
                            Crear cuenta
                            <flux:icon.arrow-right class="size-4" />
                        </a>
                        <a href="{{ route('planes') }}" class="inline-flex items-center gap-2 rounded-[12px] border border-white/70 px-6 py-3 font-bold text-white transition hover:bg-white/10">Ver planes</a>
                    </div>
                </div>

                <ul class="grid gap-4 text-[15px]">
                    <li class="flex items-start gap-4 rounded-[16px] bg-white/10 p-5">
                        <flux:icon.envelope class="mt-0.5 size-6 flex-none text-white" />
                        <div>
                            <p class="text-[12px] font-extrabold uppercase tracking-[.14em] text-white/70">Correo</p>
                            <a href="mailto:contacto@adconsulting.cl" class="mt-1 block font-bold hover:underline">contacto@adconsulting.cl</a>
                        </div>
                    </li>
                    <li class="flex items-start gap-4 rounded-[16px] bg-white/10 p-5">
                        <flux:icon.chat-bubble-left-right class="mt-0.5 size-6 flex-none text-white" />
                        <div>
                            <p class="text-[12px] font-extrabold uppercase tracking-[.14em] text-white/70">WhatsApp</p>
                            <a href="https://wa.me/56984722932" class="mt-1 block font-bold hover:underline">+56 9 8472 2932</a>
                        </div>
                    </li>
                    <li class="flex items-start gap-4 rounded-[16px] bg-white/10 p-5">
                        <flux:icon.map-pin class="mt-0.5 size-6 flex-none text-white" />
                        <div>
                            <p class="text-[12px] font-extrabold uppercase tracking-[.14em] text-white/70">Dirección</p>
                            <p class="mt-1 font-bold">San Martín 553, of. 1004 · Edificio Millenium II, Concepción, Chile</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="bg-[#4C4F51] text-white/85">
        <div class="mx-auto max-w-[1240px] px-6 py-14 lg:px-10">
            <div class="flex flex-wrap items-start justify-between gap-10 border-b border-white/10 pb-10">
                <div class="max-w-[390px]">
                    <img src="/images/ad50-logo.png" alt="AD+50 Talento Senior" class="h-20 w-auto rounded-[10px]" loading="lazy">
                    <p class="mt-5 text-[15px] leading-[1.7]">Una iniciativa de AD Consulting para conectar experiencia, criterio y nuevas oportunidades laborales.</p>
                </div>
                <div class="grid grid-cols-2 gap-x-12 gap-y-3 text-[15px] font-bold sm:grid-cols-2">
                    <a href="{{ route('home') }}" class="hover:text-white">Inicio</a>
                    <a href="{{ route('planes') }}" class="hover:text-white">Planes</a>
                    <a href="{{ route('registro', ['tipo' => 'empresa']) }}" class="hover:text-white">Crear cuenta</a>
                    <a href="mailto:contacto@adconsulting.cl" class="hover:text-white">Contacto</a>
                </div>
            </div>
            <div class="flex flex-wrap justify-between gap-3 pt-6 text-[14px]">
                <span>© {{ date('Y') }} AD Consulting · Concepción, Chile</span>
                <span>Plataforma de perfiles y matching de talento</span>
            </div>
        </div>
    </footer>
</div>
