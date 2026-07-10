<div class="ad-panel">

    <x-slot:context>Postulante</x-slot:context>
    <x-slot:nav>
        @unless ($modoOnboarding)
            <a href="{{ route('postulante.panel') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Mi panel</a>
        @endunless
        <a href="{{ route('postulante.ficha') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-ink bg-orange-100">Mi perfil profesional</a>
        @unless ($modoOnboarding)
            <a wire:navigate href="{{ route('postulante.busquedas') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Búsquedas que me incluyen</a>
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

                    ;['datos-personales', 'experiencia', 'educacion', 'idiomas', 'curriculum'].forEach((id) => {
                        const section = document.getElementById(id)
                        if (section) observer.observe(section)
                    })
                "
            >
                <div class="text-[10.5px] tracking-[0.12em] uppercase text-gray-400 font-bold px-2.5 mb-2">Perfil profesional</div>
                <div class="space-y-1.5">
                    @foreach ([
                        ['user', 'Mis Datos', 'datos-personales'],
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

    <form wire:submit="{{ $modoOnboarding ? 'avanzar' : 'save' }}" class="{{ $modoOnboarding ? 'mx-auto max-w-4xl' : '' }}">
        <div class="flex items-start justify-between gap-5 mb-6 flex-wrap">
            <div><h1 class="text-[27px] font-extrabold">{{ $modoOnboarding ? 'Completa tu perfil paso a paso' : 'Mi perfil profesional' }}</h1><p class="text-[14px] text-gray-500 mt-1.5">{{ $modoOnboarding ? 'Guardaremos tu avance cada vez que presiones Siguiente.' : 'Completa tus datos para aparecer en las búsquedas de empresas.' }}</p></div>
            @unless ($modoOnboarding)
                <button type="submit" class="ad-btn-primary ad-btn-sm" wire:loading.attr="disabled" wire:target="save,cv">
                    <span wire:loading.remove wire:target="save">Guardar cambios</span>
                    <span wire:loading wire:target="save">Guardando…</span>
                </button>
            @endunless
        </div>

        @if ($modoOnboarding)
            <div class="ad-card mb-5 p-5">
                <div class="mb-3 flex items-center justify-between gap-4 text-[13px] font-bold"><span>Paso {{ $pasoActual }} de 5</span><span class="text-orange-600">{{ (int) round(($pasoActual / 5) * 100) }}%</span></div>
                <div class="h-2 overflow-hidden rounded-full bg-line"><div class="h-full rounded-full bg-gradient-to-r from-orange-500 to-[#F59A53] transition-all" style="width: {{ ($pasoActual / 5) * 100 }}%"></div></div>
                <div class="mt-4 flex flex-nowrap items-center gap-2 overflow-x-auto text-center text-[11px] font-bold text-gray-500">
                    @foreach (['Mis Datos', 'Experiencia', 'Educación', 'Idiomas', 'CV'] as $numero => $nombrePaso)
                        <span @class(['flex flex-1 items-center justify-center gap-1 whitespace-nowrap rounded-lg px-2 py-1.5', 'bg-orange-100 text-orange-700' => $pasoActual === $numero + 1])>@if ($pasoActual > $numero + 1)<flux:icon.check class="size-3.5 flex-none" />@endif{{ $nombrePaso }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        @if (session('status'))
            <div class="mb-5 rounded-[10px] border border-[#BFE6CD] bg-match-100 px-4 py-3 text-[13px] font-semibold text-match">{{ session('status') }}</div>
        @endif

        <div class="ad-card p-5 mb-5 flex flex-wrap items-center gap-6 {{ $modoOnboarding ? 'hidden' : '' }}">
            <div class="flex-1 min-w-60"><div class="mb-2 flex justify-between text-[13px] font-semibold"><span>Completitud de tu perfil</span><span class="text-orange-600">{{ $completitud }}%</span></div><div class="h-2 rounded-full bg-line overflow-hidden"><div class="h-full bg-gradient-to-r from-orange-500 to-[#F59A53]" style="width: {{ $completitud }}%"></div></div></div>
            <div class="ad-toggle-row min-w-64"><div><b class="text-[13.5px] block">Visibilidad del perfil</b><span class="text-[13px] text-gray-500">{{ $visible ? 'Activo — visible para empresas' : 'Perfil pausado' }}</span></div><flux:switch wire:model.live="visible" /></div>
        </div>

        <div class="flex flex-col">
        <section id="datos-personales" class="ad-card order-1 scroll-mt-24 border-l-[3px] border-l-orange-300 dark:border-l-orange-500 {{ $modoOnboarding && $pasoActual !== 1 ? 'hidden' : '' }}">
            <div class="ad-card-head bg-orange-50/60 dark:bg-orange-50"><div><h2 class="text-[18px] font-extrabold text-orange-700 dark:text-orange-500">Mis datos</h2><p class="mt-1 text-[13px] text-gray-500">Tu identificación, tus formas de contacto y lo que buscas en tu próximo desafío.</p></div></div>
            <div class="space-y-7 p-6">
                <fieldset>
                    <legend class="mb-3 text-[14px] font-extrabold text-ink">Datos personales</legend>
                    <div class="grid gap-4 md:grid-cols-2">
                        <flux:input wire:model="nombres" label="Nombres *" maxlength="50" />
                        <flux:input wire:model="apellidos" label="Apellidos *" maxlength="50" />
                        <flux:field class="md:col-span-2 md:max-w-md">
                            <flux:label>{{ $tipoDocumento === 'pasaporte' ? 'Pasaporte *' : 'RUN *' }}</flux:label>
                            <div class="flex items-start gap-2">
                                <div class="w-[130px] shrink-0">
                                    <flux:select wire:model.live="tipoDocumento">
                                        <flux:select.option value="rut">RUN</flux:select.option>
                                        <flux:select.option value="pasaporte">Pasaporte</flux:select.option>
                                    </flux:select>
                                </div>
                                <flux:input
                                    wire:model.blur.live="rut"
                                    class="flex-1"
                                    placeholder="{{ $tipoDocumento === 'pasaporte' ? 'Ej. AB1234567' : '12.345.678-5' }}"
                                    inputmode="text"
                                    autocomplete="off"
                                />
                            </div>
                            @if ($tipoDocumento === 'rut')
                                <flux:description>Puedes escribirlo sin puntos ni guion; lo formatearemos automáticamente.</flux:description>
                            @endif
                            <flux:error name="rut" />
                        </flux:field>
                    </div>
                </fieldset>

                <fieldset class="border-t border-line pt-6">
                    <legend class="mb-3 text-[14px] font-extrabold text-ink">Datos de contacto</legend>
                    <div class="grid gap-4 md:grid-cols-2">
                        <flux:input wire:model="email" type="email" label="Email *" />
                        <flux:input wire:model="telefono" label="Teléfono *" placeholder="+56 9 5555 1234" />
                        <flux:input wire:model="linkedin" type="url" label="LinkedIn" maxlength="100" placeholder="https://linkedin.com/in/..." />
                        <flux:input wire:model="sitioWeb" type="url" label="Web / portafolio" maxlength="100" placeholder="https://..." />
                    </div>
                </fieldset>

                <fieldset class="border-t border-line pt-6">
                    <legend class="mb-3 text-[14px] font-extrabold text-ink">Acerca de mí</legend>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <flux:input wire:model="titular" label="Titular *" maxlength="100" placeholder="Ej. Gerente de Finanzas con experiencia en transformación y crecimiento" description="Resume tu propuesta profesional en un máximo de 100 caracteres." />
                        </div>
                        <div class="md:col-span-2">
                            <flux:textarea wire:model="resumenProfesional" label="Escribe una breve presentación" maxlength="900" rows="5" placeholder="Resume el valor que aporta el conjunto de tu trayectoria." description="Máximo 900 caracteres." />
                        </div>

                        <x-selector-colapsable
                            titulo="Regiones de interés"
                            descripcion="Marca hasta 5 alternativas ({{ count($regionesInteres) }} de 5)."
                            :seleccion="$regionesInteres"
                            error="regionesInteres"
                        >
                            <flux:checkbox.group wire:model.live="regionesInteres">
                                <div class="max-h-44 space-y-1.5 overflow-y-auto pr-2">
                                    @foreach ($regiones as $opcion)<flux:checkbox wire:key="region-{{ $loop->index }}" :value="$opcion" :label="$opcion" :disabled="count($regionesInteres) >= 5 && ! in_array($opcion, $regionesInteres, true)" />@endforeach
                                </div>
                            </flux:checkbox.group>
                        </x-selector-colapsable>

                        <x-selector-colapsable
                            titulo="Industrias de interés *"
                            descripcion="Marca entre 1 y 5 alternativas ({{ count($industriasInteres) }} de 5)."
                            :seleccion="$industriasInteres"
                            error="industriasInteres"
                        >
                            <flux:checkbox.group wire:model.live="industriasInteres">
                                <div class="max-h-44 space-y-1.5 overflow-y-auto pr-2">
                                    @foreach ($industrias as $opcion)<flux:checkbox wire:key="industria-{{ $loop->index }}" :value="$opcion" :label="$opcion" :disabled="count($industriasInteres) >= 5 && ! in_array($opcion, $industriasInteres, true)" />@endforeach
                                </div>
                            </flux:checkbox.group>
                        </x-selector-colapsable>

                        <x-selector-colapsable
                            class="md:col-span-2"
                            titulo="Modalidad preferida"
                            descripcion="Puedes marcar varias alternativas."
                            :seleccion="$modalidadesTrabajo"
                            error="modalidadesTrabajo"
                        >
                            <flux:checkbox.group wire:model.live="modalidadesTrabajo">
                                <div class="space-y-2">
                                    @foreach ($modalidadesTrabajoPreferidas as $opcion)<flux:checkbox wire:key="modalidad-{{ $loop->index }}" :value="$opcion" :label="$opcion" />@endforeach
                                </div>
                            </flux:checkbox.group>
                        </x-selector-colapsable>

                        <flux:select wire:model="situacionLaboral" label="Situación laboral">
                            <flux:select.option value="">Selecciona una situación</flux:select.option>
                            @foreach ($situacionesLaborales as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
                        </flux:select>
                        <flux:input wire:model="expectativaRenta" type="number" min="0" step="1" label="Expectativa de renta" placeholder="Ej. 2500000" description="Monto en CLP — renta líquida mensual." />
                    </div>
                </fieldset>

                <fieldset class="border-t border-line pt-6">
                    <legend class="mb-3 text-[14px] font-extrabold text-ink">Información adicional</legend>
                    <div class="grid gap-4 md:grid-cols-2">
                        <flux:select wire:model="nacionalidad" label="Nacionalidad *">
                            @foreach ($nacionalidades as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
                        </flux:select>
                        <flux:input wire:model="anioNacimiento" type="number" min="1900" max="{{ now()->year }}" label="Año de nacimiento *" />
                        <flux:input wire:model="aniosExperiencia" type="number" min="0" max="80" step="1" label="Años de experiencia *" />
                        <flux:select wire:model="genero" label="Género *">
                            <flux:select.option value="">Selecciona una opción</flux:select.option>
                            @foreach ($generos as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
                        </flux:select>
                        <flux:select wire:model="ciudad" label="Región *">
                            <flux:select.option value="">Selecciona una región</flux:select.option>
                            @foreach ($regiones as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
                        </flux:select>
                    </div>
                </fieldset>
            </div>
            <div class="flex gap-2 px-6 pb-6 text-[13px] leading-relaxed text-gray-500"><flux:icon.lock-closed class="mt-0.5 size-4 flex-none" />Tu RUN, teléfono y email solo se muestran a empresas con una suscripción activa.</div>
        </section>

        <section id="educacion" class="ad-card order-3 mt-5 scroll-mt-24 border-l-[3px] border-l-orange-300 dark:border-l-orange-500 {{ $modoOnboarding && $pasoActual !== 3 ? 'hidden' : '' }}">
            <div class="ad-card-head flex-wrap gap-4 bg-orange-50/60 dark:bg-orange-50"><div><h2 class="text-[20px] font-extrabold text-orange-700 dark:text-orange-500">Formación académica</h2><p class="mt-1 text-[13px] text-gray-500">Agrega cada etapa de tu formación y completa únicamente los campos aplicables.</p></div><button type="button" wire:click="addEducacion" class="ad-btn-ghost ad-btn-sm"><flux:icon.plus class="size-4" />Agregar educación</button></div>
            <div class="space-y-5 p-6">
                @foreach ($educaciones as $index => $educacion)
                    @php($esEscolar = in_array($educacion['nivel'], $nivelesEscolares, true))
                    <fieldset class="rounded-[14px] border border-line-2 p-5" wire:key="educacion-{{ $index }}">
                        <div class="mb-5 flex items-center justify-between gap-3"><legend class="font-bold">Educación {{ $index + 1 }}</legend>@if (count($educaciones) === 1)<span class="ad-chip ad-chip-orange">Obligatoria</span>@else<button type="button" wire:click="removeEducacion({{ $index }})" class="inline-flex items-center gap-1 text-[13px] font-bold text-[#A93226] dark:text-red-400"><flux:icon.trash class="size-4" />Quitar</button>@endif</div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <flux:select wire:model.live="educaciones.{{ $index }}.nivel" label="Nivel de estudios *">
                                <flux:select.option value="">Selecciona un nivel</flux:select.option>
                                @foreach ($nivelesEstudio as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:input wire:model="educaciones.{{ $index }}.pais" label="País *" placeholder="Chile" />
                            <x-combobox model="educaciones.{{ $index }}.institucion" label="Institución de educación *" :opciones="$instituciones" :valor="$educacion['institucion'] ?? ''" error="educaciones.{{ $index }}.institucion" placeholder="Escribe para buscar" />

                            @if ($educacion['nivel'] !== '' && $esEscolar)
                                <flux:select wire:model.live="educaciones.{{ $index }}.situacion" label="Situación">
                                    <flux:select.option value="">Selecciona una situación</flux:select.option>
                                    @foreach ($situacionesEstudio as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
                                </flux:select>
                                <flux:select wire:model="educaciones.{{ $index }}.egreso_anio" label="Año de egreso{{ ($educacion['situacion'] ?? '') === 'Estudiando' ? '' : ' *' }}">
                                    <flux:select.option value="">Selecciona un año</flux:select.option>
                                    @foreach (range(now()->year, 1900) as $anio)<flux:select.option :value="$anio">{{ $anio }}</flux:select.option>@endforeach
                                </flux:select>
                            @elseif ($educacion['nivel'] !== '')
                                <x-combobox model="educaciones.{{ $index }}.carrera" label="Carrera *" :opciones="$carrerasEstudio" :valor="$educacion['carrera'] ?? ''" error="educaciones.{{ $index }}.carrera" placeholder="Escribe para buscar" />
                                <flux:input wire:model="educaciones.{{ $index }}.mencion" label="Mención" placeholder="Mención o especialidad" />
                                <flux:select wire:model="educaciones.{{ $index }}.modalidad" label="Modalidad de estudios *">
                                    <flux:select.option value="">Selecciona una modalidad</flux:select.option>
                                    @foreach ($modalidadesEstudio as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
                                </flux:select>
                                <flux:select wire:model="educaciones.{{ $index }}.situacion" label="Situación *">
                                    <flux:select.option value="">Selecciona una situación</flux:select.option>
                                    @foreach ($situacionesEstudio as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
                                </flux:select>
                                <div class="grid grid-cols-2 gap-3 md:col-span-2 md:max-w-md">
                                    <flux:select wire:model="educaciones.{{ $index }}.inicio_anio" label="Año de inicio *"><flux:select.option value="">Selecciona</flux:select.option>@foreach (range(now()->year, 1900) as $anio)<flux:select.option :value="$anio">{{ $anio }}</flux:select.option>@endforeach</flux:select>
                                    <flux:select wire:model="educaciones.{{ $index }}.termino_anio" label="Año de término *"><flux:select.option value="">Selecciona</flux:select.option>@foreach (range(now()->year, 1900) as $anio)<flux:select.option :value="$anio">{{ $anio }}</flux:select.option>@endforeach</flux:select>
                                </div>
                            @endif
                        </div>
                    </fieldset>
                @endforeach
            </div>
        </section>

        <section id="idiomas" class="ad-card order-4 mt-5 scroll-mt-24 border-l-[3px] border-l-orange-300 dark:border-l-orange-500 {{ $modoOnboarding && $pasoActual !== 4 ? 'hidden' : '' }}">
            <div class="ad-card-head flex-wrap gap-4 bg-orange-50/60 dark:bg-orange-50"><div><h2 class="text-[20px] font-extrabold text-orange-700 dark:text-orange-500">Idiomas</h2><p class="mt-1 text-[13px] text-gray-500">Selecciona los idiomas que manejas y el nivel alcanzado.</p></div><button type="button" wire:click="addIdioma" class="ad-btn-ghost ad-btn-sm"><flux:icon.plus class="size-4" />Agregar idioma</button></div>
            <div class="space-y-4 p-6">
                @foreach ($idiomas as $index => $idioma)
                    <fieldset class="rounded-[14px] border border-line-2 p-5" wire:key="idioma-{{ $index }}">
                        <div class="mb-4 flex items-center justify-between gap-3"><legend class="font-bold">Idioma {{ $index + 1 }}</legend>@if (count($idiomas) === 1)<span class="ad-chip ad-chip-orange">Obligatorio</span>@else<button type="button" wire:click="removeIdioma({{ $index }})" class="inline-flex items-center gap-1 text-[13px] font-bold text-[#A93226] dark:text-red-400"><flux:icon.trash class="size-4" />Quitar</button>@endif</div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <flux:select wire:model="idiomas.{{ $index }}.idioma" label="Idioma *">
                                <flux:select.option value="">Selecciona un idioma</flux:select.option>
                                @foreach ($idiomasDisponibles as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:select wire:model="idiomas.{{ $index }}.nivel" label="Nivel *">
                                <flux:select.option value="">Selecciona un nivel</flux:select.option>
                                @foreach ($nivelesIdioma as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
                            </flux:select>
                        </div>
                    </fieldset>
                @endforeach
            </div>
        </section>

        <section id="experiencia" class="ad-card order-2 mt-5 scroll-mt-24 border-l-[3px] border-l-orange-300 dark:border-l-orange-500 {{ $modoOnboarding && $pasoActual !== 2 ? 'hidden' : '' }}">
            <div class="ad-card-head flex-wrap gap-4 bg-orange-50/60 dark:bg-orange-50"><div><h2 class="text-[20px] font-extrabold text-orange-700 dark:text-orange-500">Experiencia laboral</h2><p class="mt-1 text-[13px] text-gray-500">Completa tu trayectoria y agrega todas las experiencias que necesites.</p></div><button type="button" wire:click="addExperiencia" class="ad-btn-ghost ad-btn-sm"><flux:icon.plus class="size-4" />Agregar experiencia</button></div>
            <div class="p-6 space-y-5">
                @foreach ($experiencias as $index => $experiencia)
                    <fieldset class="rounded-[14px] border border-line-2 p-5" wire:key="experiencia-{{ $index }}">
                        <div class="mb-5 flex items-center justify-between gap-3"><legend class="font-bold">Experiencia {{ $index + 1 }}</legend>@if (count($experiencias) === 1)<span class="ad-chip ad-chip-orange">Obligatoria</span>@else<button type="button" wire:click="removeExperiencia({{ $index }})" class="inline-flex items-center gap-1 text-[13px] font-bold text-[#A93226] dark:text-red-400"><flux:icon.trash class="size-4" />Quitar</button>@endif</div>
                        <div class="grid md:grid-cols-2 gap-4">
                            <flux:input wire:model="experiencias.{{ $index }}.cargo" label="Cargo u ocupación *" placeholder="Ingresa tu cargo" />
                            <flux:select wire:model="experiencias.{{ $index }}.tipo_trabajo" label="Tipo de trabajo *">
                                @foreach ($tiposTrabajo as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:input wire:model="experiencias.{{ $index }}.empresa" label="Empresa *" placeholder="Nombre de la empresa" />
                            <flux:select wire:model="experiencias.{{ $index }}.jerarquia" label="Jerarquía *">
                                <flux:select.option value="">Selecciona una jerarquía</flux:select.option>
                                @foreach ($jerarquias as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:select wire:model="experiencias.{{ $index }}.actividad_empresa" label="Actividad de la empresa *">
                                <flux:select.option value="">Selecciona una actividad</flux:select.option>
                                @foreach ($industrias as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
                            </flux:select>
                            <div class="space-y-2">
                                <div class="text-[13px] font-medium">Fecha de inicio <span class="text-red-600">*</span></div>
                                <div class="grid grid-cols-[1fr_110px] gap-2">
                                    <flux:select wire:model="experiencias.{{ $index }}.inicio_mes" aria-label="Mes de inicio">
                                        <flux:select.option value="">Mes</flux:select.option>
                                        @foreach ($meses as $numero => $mes)<flux:select.option :value="$numero">{{ $mes }}</flux:select.option>@endforeach
                                    </flux:select>
                                    <flux:select wire:model="experiencias.{{ $index }}.inicio_anio" aria-label="Año de inicio">
                                        <flux:select.option value="">Año</flux:select.option>
                                        @foreach (range(now()->year, 1950) as $anio)<flux:select.option :value="$anio">{{ $anio }}</flux:select.option>@endforeach
                                    </flux:select>
                                </div>
                                <flux:error name="experiencias.{{ $index }}.inicio_mes" />
                                <flux:error name="experiencias.{{ $index }}.inicio_anio" />
                            </div>
                            <div class="md:col-start-2 space-y-3">
                                <flux:checkbox wire:model.live="experiencias.{{ $index }}.actualmente" label="Actualmente trabajando" />
                                @unless ($experiencia['actualmente'])
                                    <div class="space-y-2">
                                        <div class="text-[13px] font-medium">Fecha de término <span class="text-red-600">*</span></div>
                                        <div class="grid grid-cols-[1fr_110px] gap-2">
                                            <flux:select wire:model="experiencias.{{ $index }}.fin_mes" aria-label="Mes de término"><flux:select.option value="">Mes</flux:select.option>@foreach ($meses as $numero => $mes)<flux:select.option :value="$numero">{{ $mes }}</flux:select.option>@endforeach</flux:select>
                                            <flux:select wire:model="experiencias.{{ $index }}.fin_anio" aria-label="Año de término"><flux:select.option value="">Año</flux:select.option>@foreach (range(now()->year, 1950) as $anio)<flux:select.option :value="$anio">{{ $anio }}</flux:select.option>@endforeach</flux:select>
                                        </div>
                                        <flux:error name="experiencias.{{ $index }}.fin_mes" />
                                        <flux:error name="experiencias.{{ $index }}.fin_anio" />
                                    </div>
                                @endunless
                            </div>
                            <div class="md:col-span-2">
                                <flux:textarea wire:model="experiencias.{{ $index }}.responsabilidades" label="Responsabilidades y logros en el cargo *" placeholder="Describe tus principales responsabilidades, resultados y logros." rows="5" />
                                <p class="mt-2 text-[13px] leading-relaxed text-gray-500">Prioriza logros concretos y actividades relacionadas con tu foco laboral actual.</p>
                            </div>
                        </div>
                    </fieldset>
                @endforeach
            </div>
        </section>
        </div>

        <section id="curriculum" class="ad-card mt-5 scroll-mt-24 border-l-[3px] border-l-orange-300 dark:border-l-orange-500 {{ $modoOnboarding && $pasoActual !== 5 ? 'hidden' : '' }}">
            <div class="ad-card-head bg-orange-50/60 dark:bg-orange-50"><div><h2 class="text-[18px] font-extrabold text-orange-700 dark:text-orange-500">Currículum Vitae</h2><p class="mt-1 text-[13px] text-gray-500">Complementa tu perfil profesional con un documento actualizado.</p></div></div>
            <div class="space-y-4 p-6">
                <label for="cv" class="block cursor-pointer rounded-[14px] border-2 border-dashed border-orange-200 bg-orange-50/60 p-6 text-center transition hover:border-orange-400 hover:bg-orange-100 dark:border-orange-700 dark:bg-[#33251D] dark:hover:border-orange-500 dark:hover:bg-[#3D2B20]">
                    <flux:icon.document-arrow-up class="mx-auto size-8 text-orange-700 dark:text-[#F7C59E]" />
                    <span class="mt-3 block text-[14px] font-bold text-ink">Selecciona tu CV en PDF</span>
                    <span class="mt-1 block text-[12px] text-gray-500">Un archivo de hasta 10 MB</span>
                    <span class="ad-btn-ghost ad-btn-sm mt-4">Elegir archivo</span>
                    <input id="cv" type="file" wire:model="cv" accept="application/pdf,.pdf" class="sr-only" />
                </label>

                <div wire:loading wire:target="cv" class="rounded-[10px] border border-blue-200 bg-blue-50 px-4 py-3 text-[13px] font-semibold text-blue-700 dark:border-blue-800 dark:bg-blue-950/40 dark:text-blue-300" role="status">Cargando el archivo…</div>

                @error('cv') <div class="rounded-[10px] border border-red-200 bg-red-50 px-4 py-3 text-[13px] font-semibold text-red-700" role="alert">{{ $message }}</div> @enderror

                @if ($cv)
                    <div class="flex items-center gap-3 rounded-[10px] border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-950/40">
                        <flux:icon.document class="size-5 flex-none text-blue-600 dark:text-blue-300" />
                        <div class="min-w-0"><b class="block truncate text-[13px] text-blue-800 dark:text-blue-200">{{ $cv->getClientOriginalName() }}</b><span class="text-[12px] text-blue-700 dark:text-blue-300">Listo para guardar</span></div>
                    </div>
                @elseif ($cvRutaExistente)
                    <div class="flex items-center gap-3 rounded-[10px] border border-[#BFE6CD] bg-match-100 p-3">
                        <flux:icon.check-circle class="size-5 flex-none text-match" />
                        <div><b class="block text-[13px] text-match">CV guardado</b><span class="text-[12px] text-gray-600">Al elegir otro PDF reemplazarás el archivo actual.</span></div>
                    </div>
                @endif

                <p class="text-[13px] leading-relaxed text-gray-500">Las empresas podrán acceder al CV cuando tengan acceso autorizado a tu perfil.</p>
            </div>
        </section>

        @if ($modoOnboarding)
            <div class="ad-card mt-5 flex flex-wrap items-center justify-between gap-3 p-5">
                <button type="button" wire:click="anterior" class="ad-btn-ghost ad-btn-sm {{ $pasoActual === 1 ? 'invisible' : '' }}" wire:loading.attr="disabled">Anterior</button>
                <div class="flex items-center gap-3">
                    @if ($pasoActual === 5)
                        <button type="button" wire:click="omitir" class="px-3 py-2 text-[14px] font-bold text-gray-500 hover:text-ink" wire:loading.attr="disabled">Completar después</button>
                    @endif
                    <button type="submit" class="ad-btn-primary ad-btn-sm" wire:loading.attr="disabled" wire:target="avanzar,cv">
                        <span wire:loading.remove wire:target="avanzar">{{ $pasoActual === 5 ? 'Finalizar' : 'Guardar y continuar' }}</span>
                        <span wire:loading wire:target="avanzar">Guardando…</span>
                    </button>
                </div>
            </div>
        @else
            <div class="ad-card mt-5 p-5 flex flex-wrap items-center justify-between gap-4"><div class="flex gap-3"><flux:icon.shield-check class="size-6 text-gray-500 flex-none" /><div><b class="text-[14px]">Tú controlas tu información</b><p class="mt-1 text-[13px] text-gray-500">Puedes editarla, pausar tu visibilidad o solicitar su eliminación.</p></div></div><button type="submit" class="ad-btn-primary ad-btn-sm" wire:loading.attr="disabled" wire:target="save,cv">Guardar perfil profesional</button></div>
        @endif
    </form>
</div>
