<div class="ad-panel">

    <x-slot:context>Postulante</x-slot:context>
    <x-slot:nav>
        @unless ($modoOnboarding)
            <a href="{{ route('postulante.panel') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Mi panel</a>
        @endunless
        <a href="{{ route('postulante.ficha') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-ink bg-orange-100">Mi perfil</a>
        @unless ($modoOnboarding)
            <a wire:navigate href="{{ route('postulante.busquedas') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Procesos que me incluyen</a>
        @endunless
    </x-slot:nav>
    @unless ($modoOnboarding)
        <x-slot:sidebar>
            <div
                class="sticky top-24 max-h-[calc(100vh-7rem)] overflow-y-auto pb-4"
                x-data="{ activeSection: 'datos-personales' }"
                x-init="
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach((entry) => {
                            if (entry.isIntersecting) activeSection = entry.target.id
                        })
                    }, { rootMargin: '-20% 0px -65% 0px' })

                    ;['datos-personales', 'acerca-de-mi', 'experiencia', 'educacion', 'idiomas', 'curriculum'].forEach((id) => {
                        const section = document.getElementById(id)
                        if (section) observer.observe(section)
                    })
                "
            >
                <div class="text-[10.5px] tracking-[0.12em] uppercase text-gray-400 font-bold px-2.5 mb-2">Mi perfil</div>
                <div class="space-y-1.5">
                    @foreach ([
                        ['user', 'Mis datos', 'datos-personales'],
                        ['sparkles', 'Acerca de mí', 'acerca-de-mi'],
                        ['briefcase', 'Experiencia', 'experiencia'],
                        ['academic-cap', 'Educación', 'educacion'],
                        ['language', 'Idiomas', 'idiomas'],
                        ['document', 'Currículum Vitae', 'curriculum'],
                    ] as [$icon, $label, $anchor])
                        <a
                            href="#{{ $anchor }}"
                            x-on:click="activeSection = '{{ $anchor }}'"
                            x-bind:aria-current="activeSection === '{{ $anchor }}' ? 'location' : null"
                            x-bind:class="activeSection === '{{ $anchor }}'
                                ? 'border-orange-500 bg-orange-100 text-orange-700 shadow-sm dark:border-orange-500 dark:bg-[#33251D] dark:text-[#F7C59E]'
                                : 'border-line-2 bg-white text-gray-700 hover:border-orange-200 hover:bg-orange-50 dark:bg-[#25282A] dark:text-gray-300 dark:hover:bg-white/10'"
                            class="flex items-center gap-3 rounded-[10px] border px-3 py-2.5 text-[14px] font-bold transition"
                        ><flux:icon :name="$icon" class="size-[18px]" />{{ $label }}</a>
                    @endforeach
                </div>
            </div>
        </x-slot:sidebar>
    @endunless
    @php($esEditor = ! $modoOnboarding)
    <{{ $esEditor ? 'div' : 'form' }}@unless ($esEditor) wire:submit="avanzar"@endunless class="{{ $modoOnboarding ? 'mx-auto max-w-4xl' : '' }}">
        @php($perfilCasiListo = $completitud >= 80)
        <div class="mb-6 flex flex-wrap items-start justify-between gap-x-4 gap-y-2">
            <div>
                <h1 class="text-[27px] font-extrabold">{{ $modoOnboarding ? 'Completa tu perfil paso a paso' : 'Mi perfil profesional' }}</h1>
                <p class="text-[14px] text-gray-500 mt-1.5">{{ $modoOnboarding ? 'Guardaremos tu avance cada vez que presiones Siguiente.' : 'Este es el resumen de tu perfil. Edita cada sección cuando lo necesites.' }}</p>
            </div>
            @if (! $modoOnboarding && $perfilCasiListo)
                <span class="mt-1 inline-flex flex-none items-center gap-1.5 rounded-full border border-line-2 bg-white/60 px-3 py-1.5 text-[12px] font-medium text-gray-500 dark:bg-white/5">Perfil {{ $completitud }}% completo</span>
            @endif
        </div>

        @if ($modoOnboarding)
            <div class="ad-card mb-5 p-5">
                <div class="mb-3 flex items-center justify-between gap-4 text-[13px] font-bold"><span>Paso {{ $pasoActual }} de 6</span><span class="text-orange-600">{{ (int) round(($pasoActual / 6) * 100) }}%</span></div>
                <div class="h-2 overflow-hidden rounded-full bg-line"><div class="h-full rounded-full bg-gradient-to-r from-orange-500 to-[#F59A53] transition-all" style="width: {{ ($pasoActual / 6) * 100 }}%"></div></div>
                <div class="mt-4 flex flex-nowrap items-center gap-2 overflow-x-auto text-center text-[11px] font-bold text-gray-500">
                    @foreach (['Mis datos', 'Acerca de mí', 'Experiencia', 'Educación', 'Idiomas', 'CV'] as $numero => $nombrePaso)
                        <span @class(['flex flex-1 items-center justify-center gap-1 whitespace-nowrap rounded-lg px-2 py-1.5', 'bg-orange-100 text-orange-700' => $pasoActual === $numero + 1])>@if ($pasoActual > $numero + 1)<flux:icon.check class="size-3.5 flex-none" />@endif{{ $nombrePaso }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        @if (session('status'))
            <div class="mb-5 rounded-[10px] border border-[#BFE6CD] bg-match-100 px-4 py-3 text-[13px] font-semibold text-match" role="status">{{ session('status') }}</div>
        @endif

        {{-- Cuando el perfil ya supera el 80% la barra desaparece: el avance se muestra como chip pequeño junto al título. --}}
        @unless ($modoOnboarding || $perfilCasiListo)
            <div class="ad-card mb-5 flex flex-wrap items-center gap-6 p-5">
                <div class="flex-1 min-w-60">
                    <div class="mb-2 flex justify-between text-[13px] font-semibold"><span>Completitud de tu perfil</span><span class="text-orange-600">{{ $completitud }}%</span></div>
                    <div class="h-2 rounded-full bg-line overflow-hidden"><div class="h-full bg-gradient-to-r from-orange-500 to-[#F59A53]" style="width: {{ $completitud }}%"></div></div>
                </div>
            </div>
        @endunless

        @if ($modoOnboarding)
        {{-- Flujo paso a paso: secciones editables en línea --}}
        <div class="flex flex-col">
            <section id="datos-personales" class="ad-card order-1 scroll-mt-24 border-l-[3px] border-l-orange-300 dark:border-l-orange-500 {{ $pasoActual !== 1 ? 'hidden' : '' }}">
                <div class="ad-card-head bg-orange-50/60 dark:bg-orange-50"><div><h2 class="text-[18px] font-extrabold text-orange-700 dark:text-orange-500">Mis datos</h2><p class="mt-1 text-[13px] text-gray-500">Tu identificación, tus formas de contacto y tu información personal.</p></div></div>
                <div class="space-y-7 p-6">@include('livewire.postulante.partials.form-datos')</div>
                <div class="flex gap-2 px-6 pb-6 text-[13px] leading-relaxed text-gray-500"><flux:icon.lock-closed class="mt-0.5 size-4 flex-none" />Tu RUN, teléfono y email solo se muestran a empresas con una suscripción activa.</div>
            </section>

            <section id="acerca-de-mi" class="ad-card order-2 mt-5 scroll-mt-24 border-l-[3px] border-l-orange-300 dark:border-l-orange-500 {{ $pasoActual !== 2 ? 'hidden' : '' }}">
                <div class="ad-card-head bg-orange-50/60 dark:bg-orange-50"><div><h2 class="text-[18px] font-extrabold text-orange-700 dark:text-orange-500">Acerca de mí</h2><p class="mt-1 text-[13px] text-gray-500">Tu titular es la primera información que verán las empresas de ti.</p></div></div>
                <div class="space-y-7 p-6">@include('livewire.postulante.partials.form-acerca')</div>
            </section>

            <section id="experiencia" class="ad-card order-3 mt-5 scroll-mt-24 border-l-[3px] border-l-orange-300 dark:border-l-orange-500 {{ $pasoActual !== 3 ? 'hidden' : '' }}">
                <div class="ad-card-head flex-wrap gap-4 bg-orange-50/60 dark:bg-orange-50"><div><h2 class="text-[20px] font-extrabold text-orange-700 dark:text-orange-500">Experiencia laboral</h2><p class="mt-1 text-[13px] text-gray-500">Obligatoria: necesitas al menos una experiencia para aparecer en los procesos. Puedes agregar hasta 5.</p></div><button type="button" wire:click="addExperiencia" @disabled(count($experiencias) >= 5) class="ad-btn-ghost ad-btn-sm disabled:cursor-not-allowed disabled:opacity-50"><flux:icon.plus class="size-4" />Agregar experiencia</button></div>
                <div class="p-6 space-y-5">@include('livewire.postulante.partials.form-experiencia')</div>
            </section>

            <section id="educacion" class="ad-card order-4 mt-5 scroll-mt-24 border-l-[3px] border-l-orange-300 dark:border-l-orange-500 {{ $pasoActual !== 4 ? 'hidden' : '' }}">
                <div class="ad-card-head flex-wrap gap-4 bg-orange-50/60 dark:bg-orange-50"><div><h2 class="text-[20px] font-extrabold text-orange-700 dark:text-orange-500">Formación académica</h2><p class="mt-1 text-[13px] text-gray-500">Obligatoria: necesitas al menos una formación para aparecer en los procesos.</p></div><button type="button" wire:click="addEducacion" class="ad-btn-ghost ad-btn-sm"><flux:icon.plus class="size-4" />Agregar educación</button></div>
                <div class="space-y-5 p-6">@include('livewire.postulante.partials.form-educacion')</div>
            </section>

            <section id="idiomas" class="ad-card order-5 mt-5 scroll-mt-24 border-l-[3px] border-l-orange-300 dark:border-l-orange-500 {{ $pasoActual !== 5 ? 'hidden' : '' }}">
                <div class="ad-card-head flex-wrap gap-4 bg-orange-50/60 dark:bg-orange-50"><div><h2 class="text-[20px] font-extrabold text-orange-700 dark:text-orange-500">Idiomas</h2><p class="mt-1 text-[13px] text-gray-500">Opcional. Agrega los idiomas que manejas y su nivel.</p></div><button type="button" wire:click="addIdioma" class="ad-btn-ghost ad-btn-sm"><flux:icon.plus class="size-4" />Agregar idioma</button></div>
                <div class="space-y-4 p-6">@include('livewire.postulante.partials.form-idiomas')</div>
            </section>
        </div>

        <section id="curriculum" class="ad-card mt-5 scroll-mt-24 border-l-[3px] border-l-orange-300 dark:border-l-orange-500 {{ $pasoActual !== 6 ? 'hidden' : '' }}">
            <div class="ad-card-head bg-orange-50/60 dark:bg-orange-50"><div><h2 class="text-[18px] font-extrabold text-orange-700 dark:text-orange-500">Currículum Vitae</h2><p class="mt-1 text-[13px] text-gray-500">Complementa tu perfil profesional con un documento actualizado.</p></div></div>
            <div class="space-y-4 p-6">@include('livewire.postulante.partials.form-curriculum')</div>
        </section>

        <div class="ad-card mt-5 flex flex-wrap items-center justify-between gap-3 p-5">
            <button type="button" wire:click="anterior" class="ad-btn-ghost ad-btn-sm {{ $pasoActual === 1 ? 'invisible' : '' }}" wire:loading.attr="disabled">Anterior</button>
            <div class="flex items-center gap-3">
                @if ($pasoActual === 6)
                    <button type="button" wire:click="omitir" class="px-3 py-2 text-[14px] font-bold text-gray-500 hover:text-ink" wire:loading.attr="disabled">Completar después</button>
                @endif
                <button type="submit" class="ad-btn-primary ad-btn-sm" wire:loading.attr="disabled" wire:target="avanzar,cv">
                    <span wire:loading.remove wire:target="avanzar">{{ $pasoActual === 6 ? 'Finalizar' : 'Guardar y continuar' }}</span>
                    <span wire:loading wire:target="avanzar">Guardando…</span>
                </button>
            </div>
        </div>

        @else
        {{-- Editor: resumen de solo lectura; "Editar" abre el modal de la sección. --}}
        <div class="flex flex-col">
        <section id="datos-personales" class="ad-card order-1 scroll-mt-24 border-l-[3px] border-l-orange-300 dark:border-l-orange-500">
            <div class="ad-card-head bg-orange-50/60 dark:bg-orange-50"><div><h2 class="text-[18px] font-extrabold text-orange-700 dark:text-orange-500">Mis datos</h2><p class="mt-1 text-[13px] text-gray-500">Identificación, contacto e información personal.</p></div><button type="button" wire:click="editarSeccion('datos')" class="ad-btn-ghost ad-btn-sm"><flux:icon.pencil-square class="size-4" />Editar</button></div>
                <dl class="grid gap-x-8 gap-y-4 p-6 sm:grid-cols-2">
                    <x-ficha-dato label="Nombre completo">{{ trim($nombres.' '.$apellidos) ?: '—' }}</x-ficha-dato>
                    <x-ficha-dato label="{{ $tipoDocumento === 'pasaporte' ? 'Pasaporte' : 'RUN' }}">{{ $rut ?: '—' }}</x-ficha-dato>
                    <x-ficha-dato label="Email">{{ $email ?: '—' }}</x-ficha-dato>
                    <x-ficha-dato label="Teléfono">{{ $telefono ?: '—' }}</x-ficha-dato>
                    <x-ficha-dato label="LinkedIn">@if ($linkedin)<a href="{{ $linkedin }}" target="_blank" rel="noopener" class="font-semibold text-orange-600 hover:underline">Ver perfil</a>@else—@endif</x-ficha-dato>
                    <x-ficha-dato label="Web / portafolio">@if ($sitioWeb)<a href="{{ $sitioWeb }}" target="_blank" rel="noopener" class="font-semibold text-orange-600 hover:underline">Abrir enlace</a>@else—@endif</x-ficha-dato>
                    <x-ficha-dato label="Nacionalidad">{{ $nacionalidad ?: '—' }}</x-ficha-dato>
                    <x-ficha-dato label="Año de nacimiento">{{ $anioNacimiento ?: '—' }}</x-ficha-dato>
                    <x-ficha-dato label="Años de experiencia">{{ $aniosExperiencia !== null ? $aniosExperiencia : '—' }}</x-ficha-dato>
                    <x-ficha-dato label="Género">{{ $genero ?: '—' }}</x-ficha-dato>
                    <x-ficha-dato label="Lugar de residencia">{{ $ciudad ?: '—' }}</x-ficha-dato>
                </dl>
        </section>
        <section id="acerca-de-mi" class="ad-card order-2 mt-5 scroll-mt-24 border-l-[3px] border-l-orange-300 dark:border-l-orange-500">
            <div class="ad-card-head bg-orange-50/60 dark:bg-orange-50"><div><h2 class="text-[18px] font-extrabold text-orange-700 dark:text-orange-500">Acerca de mí</h2><p class="mt-1 text-[13px] text-gray-500">Tu propuesta profesional, habilidades e intereses.</p></div><button type="button" wire:click="editarSeccion('acerca')" class="ad-btn-ghost ad-btn-sm"><flux:icon.pencil-square class="size-4" />Editar</button></div>
                <div class="space-y-5 p-6">
                    <div>
                        <h3 class="text-[16px] font-bold text-ink">{{ $titular ?: 'Sin titular' }}</h3>
                        <p class="mt-2 text-[14px] leading-relaxed text-gray-600 dark:text-gray-300">{{ $resumenProfesional ?: 'Aún no has escrito una presentación.' }}</p>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div><dt class="mb-1.5 text-[11px] font-bold uppercase tracking-wide text-gray-400">Habilidades</dt><div class="flex flex-wrap gap-1.5">@forelse ($habilidades as $item)<span class="ad-tag">{{ $item }}</span>@empty<span class="text-[13px] text-gray-500">Sin habilidades</span>@endforelse</div></div>
                        <div><dt class="mb-1.5 text-[11px] font-bold uppercase tracking-wide text-gray-400">Industrias de interés</dt><div class="flex flex-wrap gap-1.5">@forelse ($industriasInteres as $item)<span class="ad-tag">{{ $item }}</span>@empty<span class="text-[13px] text-gray-500">Sin industrias</span>@endforelse</div></div>
                        <div><dt class="mb-1.5 text-[11px] font-bold uppercase tracking-wide text-gray-400">Regiones de interés</dt><div class="flex flex-wrap gap-1.5">@forelse ($regionesInteres as $item)<span class="ad-tag">{{ $item }}</span>@empty<span class="text-[13px] text-gray-500">Sin regiones</span>@endforelse</div></div>
                        <div><dt class="mb-1.5 text-[11px] font-bold uppercase tracking-wide text-gray-400">Modalidad preferida</dt><div class="flex flex-wrap gap-1.5">@forelse ($modalidadesTrabajo as $item)<span class="ad-tag">{{ $item }}</span>@empty<span class="text-[13px] text-gray-500">Sin preferencia</span>@endforelse</div></div>
                        <x-ficha-dato label="Situación laboral">{{ $situacionLaboral ?: '—' }}</x-ficha-dato>
                        <x-ficha-dato label="Expectativa de renta">{{ $expectativaRenta ? '$'.number_format($expectativaRenta, 0, ',', '.') : '—' }}</x-ficha-dato>
                    </div>
                </div>
        </section>
        <section id="experiencia" class="ad-card order-3 mt-5 scroll-mt-24 border-l-[3px] border-l-orange-300 dark:border-l-orange-500">
            <div class="ad-card-head bg-orange-50/60 dark:bg-orange-50"><div><h2 class="text-[20px] font-extrabold text-orange-700 dark:text-orange-500">Experiencia laboral</h2><p class="mt-1 text-[13px] text-gray-500">Tu trayectoria profesional.</p></div><button type="button" wire:click="editarSeccion('experiencia')" class="ad-btn-ghost ad-btn-sm"><flux:icon.pencil-square class="size-4" />Editar</button></div>
                <div class="space-y-4 p-6">
                    @forelse ($experiencias as $exp)
                        <div class="rounded-[12px] border border-line-2 p-4">
                            <div class="flex flex-wrap items-baseline justify-between gap-2"><h3 class="text-[15px] font-bold text-ink">{{ (($exp['cargo'] ?? '') === 'Otros' ? ($exp['cargo_otro'] ?? '') : $exp['cargo']) ?: 'Cargo sin nombre' }}</h3><span class="text-[12px] font-semibold text-gray-500">{{ ($meses[$exp['inicio_mes']] ?? '') }} {{ $exp['inicio_anio'] }} — {{ $exp['actualmente'] ? 'Actualidad' : trim(($meses[$exp['fin_mes']] ?? '').' '.$exp['fin_anio']) }}</span></div>
                            <p class="mt-0.5 text-[13px] font-semibold text-orange-600">{{ ($exp['empresa'] ?? '') === 'Otros' ? ($exp['empresa_otro'] ?? '') : $exp['empresa'] }}</p>
                            @if (filled($exp['responsabilidades']))<p class="mt-2 text-[13px] leading-relaxed text-gray-600 dark:text-gray-300">{{ \Illuminate\Support\Str::limit($exp['responsabilidades'], 220) }}</p>@endif
                        </div>
                    @empty
                        <p class="text-[13px] text-gray-500">Aún no has agregado experiencia.</p>
                    @endforelse
                </div>
        </section>
        <section id="educacion" class="ad-card order-4 mt-5 scroll-mt-24 border-l-[3px] border-l-orange-300 dark:border-l-orange-500">
            <div class="ad-card-head bg-orange-50/60 dark:bg-orange-50"><div><h2 class="text-[20px] font-extrabold text-orange-700 dark:text-orange-500">Formación académica</h2><p class="mt-1 text-[13px] text-gray-500">Tus estudios y titulaciones.</p></div><button type="button" wire:click="editarSeccion('educacion')" class="ad-btn-ghost ad-btn-sm"><flux:icon.pencil-square class="size-4" />Editar</button></div>
                <div class="space-y-4 p-6">
                    @forelse ($educaciones as $edu)
                        <div class="rounded-[12px] border border-line-2 p-4">
                            <div class="flex flex-wrap items-baseline justify-between gap-2"><h3 class="text-[15px] font-bold text-ink">{{ $edu['institucion'] ?: 'Institución' }}</h3><span class="text-[12px] font-semibold text-gray-500">{{ $edu['nivel'] }}</span></div>
                            @if (filled($edu['carrera']))<p class="mt-0.5 text-[13px] text-gray-600 dark:text-gray-300">{{ $edu['carrera'] }}{{ filled($edu['mencion']) ? ' · '.$edu['mencion'] : '' }}</p>@endif
                        </div>
                    @empty
                        <p class="text-[13px] text-gray-500">Aún no has agregado formación.</p>
                    @endforelse
                </div>
        </section>
        <section id="idiomas" class="ad-card order-5 mt-5 scroll-mt-24 border-l-[3px] border-l-orange-300 dark:border-l-orange-500">
            <div class="ad-card-head bg-orange-50/60 dark:bg-orange-50"><div><h2 class="text-[20px] font-extrabold text-orange-700 dark:text-orange-500">Idiomas</h2><p class="mt-1 text-[13px] text-gray-500">Idiomas que manejas y su nivel.</p></div><button type="button" wire:click="editarSeccion('idiomas')" class="ad-btn-ghost ad-btn-sm"><flux:icon.pencil-square class="size-4" />Editar</button></div>
                <div class="flex flex-wrap gap-2 p-6">
                    @forelse ($idiomas as $idi)
                        @if (filled($idi['idioma']))<span class="ad-tag">{{ $idi['idioma'] }}@if (filled($idi['nivel'])) · {{ $idi['nivel'] }}@endif</span>@endif
                    @empty
                        <p class="text-[13px] text-gray-500">Sin idiomas.</p>
                    @endforelse
                </div>
        </section>
        </div>

        <section id="curriculum" class="ad-card mt-5 scroll-mt-24 border-l-[3px] border-l-orange-300 dark:border-l-orange-500">
            <div class="ad-card-head bg-orange-50/60 dark:bg-orange-50"><div><h2 class="text-[18px] font-extrabold text-orange-700 dark:text-orange-500">Currículum Vitae</h2><p class="mt-1 text-[13px] text-gray-500">Tu CV en PDF.</p></div><button type="button" wire:click="editarSeccion('curriculum')" class="ad-btn-ghost ad-btn-sm"><flux:icon.pencil-square class="size-4" />Editar</button></div>
            <div class="p-6">
                @if ($cvRutaExistente)
                    <div class="flex items-center gap-3 rounded-[10px] border border-[#BFE6CD] bg-match-100 p-3"><flux:icon.document class="size-5 flex-none text-match" /><div><b class="block text-[13px] text-match">CV cargado</b><span class="text-[12px] text-gray-600">Disponible para empresas con acceso autorizado.</span></div></div>
                @else
                    <p class="text-[13px] text-gray-500">Aún no has subido tu CV.</p>
                @endif
            </div>
        </section>

        {{-- Modal de edición único: solo la sección activa monta su formulario. --}}
        <flux:modal name="editor" class="w-full max-w-3xl" wire:close="cancelarEdicion">
            <div class="space-y-6">
                <flux:heading size="lg">
                    @switch ($seccionEditando)
                        @case ('datos') Editar mis datos @break
                        @case ('acerca') Editar acerca de mí @break
                        @case ('experiencia') Editar experiencia @break
                        @case ('educacion') Editar formación @break
                        @case ('idiomas') Editar idiomas @break
                        @case ('curriculum') Actualizar currículum @break
                    @endswitch
                </flux:heading>

                @if ($errors->any())
                    <div role="alert" class="rounded-[10px] border border-red-300 bg-red-50 px-4 py-3 text-[13px] text-red-700 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300">
                        <b class="mb-1 block">Revisa estos campos antes de guardar:</b>
                        <ul class="list-disc space-y-0.5 pl-5">@foreach ($errors->all() as $mensaje)<li>{{ $mensaje }}</li>@endforeach</ul>
                    </div>
                @endif

                <div class="space-y-5">
                    @switch ($seccionEditando)
                        @case ('datos') @include('livewire.postulante.partials.form-datos') @break
                        @case ('acerca') @include('livewire.postulante.partials.form-acerca') @break
                        @case ('experiencia')
                            <div class="flex justify-end"><button type="button" wire:click="addExperiencia" @disabled(count($experiencias) >= 5) class="ad-btn-ghost ad-btn-sm disabled:cursor-not-allowed disabled:opacity-50"><flux:icon.plus class="size-4" />Agregar experiencia</button></div>
                            @include('livewire.postulante.partials.form-experiencia')
                            @break
                        @case ('educacion')
                            <div class="flex justify-end"><button type="button" wire:click="addEducacion" class="ad-btn-ghost ad-btn-sm"><flux:icon.plus class="size-4" />Agregar educación</button></div>
                            @include('livewire.postulante.partials.form-educacion')
                            @break
                        @case ('idiomas')
                            <div class="flex justify-end"><button type="button" wire:click="addIdioma" class="ad-btn-ghost ad-btn-sm"><flux:icon.plus class="size-4" />Agregar idioma</button></div>
                            @include('livewire.postulante.partials.form-idiomas')
                            @break
                        @case ('curriculum') @include('livewire.postulante.partials.form-curriculum') @break
                    @endswitch
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close><flux:button variant="ghost">Cancelar</flux:button></flux:modal.close>
                    <flux:button variant="primary" wire:click="guardarSeccion('{{ $seccionEditando }}')" wire:loading.attr="disabled" wire:target="guardarSeccion">Guardar cambios</flux:button>
                </div>
            </div>
        </flux:modal>

        @endif
    </{{ $esEditor ? 'div' : 'form' }}>
</div>
