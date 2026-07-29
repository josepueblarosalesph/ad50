<div class="ad-panel">
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:nav><x-nav-empresa activo="publicaciones" /></x-slot:nav>

    <div class="mx-auto max-w-5xl">
        <a wire:navigate href="{{ route('empresa.publicaciones.index') }}" class="mb-4 inline-flex items-center gap-2 text-[13px] font-bold text-gray-500 hover:text-ink"><flux:icon.arrow-left class="size-4" />Volver a publicaciones</a>

        @if (session('status'))
            <div class="mb-5 rounded-xl border border-[#BFE6CD] bg-match-100 px-4 py-3 text-[13px] font-bold text-match">{{ session('status') }}</div>
        @endif

        {{-- Encabezado con las acciones: ver postulantes, editar y eliminar. --}}
        <div class="mb-6 flex flex-wrap items-start justify-between gap-5">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span @class([
                        'ad-chip',
                        'ad-chip-green' => $publicacion->estaVigente(),
                        'ad-chip-orange' => ! $publicacion->estaVigente(),
                    ])>{{ $publicacion->estadoLabel() }}</span>
                    <span class="ad-chip">{{ $publicacion->modalidad }}</span>
                    @if ($publicacion->empleo_inclusivo)<span class="ad-chip ad-chip-green">Empleo inclusivo</span>@endif
                </div>
                <h1 class="mt-3 text-[30px] font-extrabold">{{ $publicacion->cargo }}</h1>
                <p class="mt-2 text-[14px] text-gray-500">{{ $publicacion->nombre_empresa }} · {{ $publicacion->comuna }}, {{ $publicacion->pais }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a wire:navigate href="{{ route('empresa.publicaciones.postulaciones', $publicacion) }}" class="ad-btn-primary ad-btn-sm whitespace-nowrap"><flux:icon.users class="size-4" />Ver postulantes</a>
                <a wire:navigate href="{{ route('empresa.publicaciones.edit', $publicacion) }}" class="ad-btn-ghost ad-btn-sm whitespace-nowrap"><flux:icon.pencil-square class="size-4" />Editar</a>
                <button type="button" wire:click="confirmarBorrado" class="ad-btn-ghost ad-btn-sm whitespace-nowrap text-[#A93226] dark:text-red-400"><flux:icon.trash class="size-4" />Eliminar</button>
            </div>
        </div>

        {{-- Resumen rápido --}}
        <section class="ad-card mb-6 grid gap-5 p-6 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="text-[12px] font-bold uppercase tracking-[0.1em] text-gray-400">Postulaciones</p>
                <a wire:navigate href="{{ route('empresa.publicaciones.postulaciones', $publicacion) }}" class="mt-1 block text-[24px] font-extrabold text-orange-600 hover:text-orange-700">{{ $totalPostulaciones }}</a>
            </div>
            <div>
                <p class="text-[12px] font-bold uppercase tracking-[0.1em] text-gray-400">Vacantes</p>
                <p class="mt-1 text-[24px] font-extrabold text-ink">{{ $publicacion->vacantes }}</p>
            </div>
            <div>
                <p class="text-[12px] font-bold uppercase tracking-[0.1em] text-gray-400">Publicada</p>
                <p class="mt-1 text-[14px] font-bold text-ink">{{ $publicacion->created_at->translatedFormat('d M Y') }}</p>
            </div>
            <div>
                <p class="text-[12px] font-bold uppercase tracking-[0.1em] text-gray-400">Vigente hasta</p>
                <p class="mt-1 text-[14px] font-bold text-ink">{{ $publicacion->vigente_hasta->translatedFormat('d M Y') }}</p>
                <p class="mt-0.5 text-[12px] text-gray-500">{{ $publicacion->vigencia_dias }} días de vigencia</p>
            </div>
        </section>

        {{-- Candidatos que la empresa asoció a esta publicación desde Prospección de Candidatos. --}}
        <section class="ad-card mb-6">
            <div class="ad-card-head flex-wrap gap-3">
                <h2 class="text-[16px] font-bold">Candidatos prospectados</h2>
                <span class="text-[13px] font-semibold text-gray-500">{{ $candidatosAsociados->count() }} asociado(s)</span>
            </div>
            <div class="divide-y divide-line px-6">
                @forelse ($candidatosAsociados as $candidato)
                    <div wire:key="asociado-{{ $candidato->id }}" class="flex flex-wrap items-center justify-between gap-3 py-4">
                        <div class="min-w-0">
                            <p class="truncate text-[14px] font-bold text-ink">{{ $candidato->cargo_actual ?: 'Candidato #'.$candidato->id }}</p>
                            <p class="mt-1 truncate text-[13px] text-gray-500">
                                {{ collect([$candidato->carrera, $candidato->anios_experiencia ? $candidato->anios_experiencia.' años de experiencia' : null])->filter()->implode(' · ') }}
                            </p>
                        </div>
                        <button type="button" wire:click="quitarCandidato({{ $candidato->id }})" wire:loading.attr="disabled" wire:target="quitarCandidato({{ $candidato->id }})" class="ad-btn-ghost ad-btn-sm whitespace-nowrap disabled:opacity-50">
                            Quitar
                        </button>
                    </div>
                @empty
                    <p class="py-6 text-center text-[13px] text-gray-500">
                        Aún no asocias candidatos. Búscalos en
                        <a wire:navigate href="{{ route('empresa.busquedas.index') }}" class="font-bold text-orange-600 underline underline-offset-2">Prospección de Candidatos</a>
                        y asócialos desde sus resultados.
                    </p>
                @endforelse
            </div>
        </section>

        <section class="ad-card mb-6">
            <div class="ad-card-head flex-wrap gap-3">
                <div><h2 class="text-[19px] font-extrabold">Estado de la publicación</h2><p class="mt-1 text-[13px] text-gray-500">Controla si la oferta sigue visible en el portal de postulantes.</p></div>
                <select
                    wire:change="cambiarEstado($event.target.value)"
                    aria-label="Estado de la publicación"
                    @class([
                        'rounded-lg border px-2.5 py-1.5 text-[13px] font-bold focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500',
                        'border-[#BFE6CD] bg-match-100 text-match' => $publicacion->estaVigente(),
                        'border-line-2 bg-paper text-gray-600' => ! $publicacion->estaVigente(),
                    ])
                >
                    @foreach ($estados as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected($publicacion->estado === $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
        </section>

        <section class="ad-card mb-6">
            <div class="ad-card-head"><div><h2 class="text-[19px] font-extrabold">Descripción general</h2><p class="mt-1 text-[13px] text-gray-500">Información principal que ve el postulante.</p></div></div>
            <dl class="grid gap-5 p-6 md:grid-cols-2">
                <div><dt class="text-[12px] font-bold uppercase tracking-[0.1em] text-gray-400">Tipo de cargo</dt><dd class="mt-1 text-[14px] text-ink">{{ $publicacion->tipo_cargo }}</dd></div>
                <div><dt class="text-[12px] font-bold uppercase tracking-[0.1em] text-gray-400">Jerarquía</dt><dd class="mt-1 text-[14px] text-ink">{{ $publicacion->jerarquia }}</dd></div>
                <div><dt class="text-[12px] font-bold uppercase tracking-[0.1em] text-gray-400">Actividad de la empresa</dt><dd class="mt-1 text-[14px] text-ink">{{ $publicacion->actividad_empresa }}</dd></div>
                <div><dt class="text-[12px] font-bold uppercase tracking-[0.1em] text-gray-400">Ubicación</dt><dd class="mt-1 text-[14px] text-ink">{{ $publicacion->comuna }}, {{ $publicacion->pais }} · {{ $publicacion->modalidad }}</dd></div>
                <div>
                    <dt class="text-[12px] font-bold uppercase tracking-[0.1em] text-gray-400">Sueldo</dt>
                    <dd class="mt-1 text-[14px] text-ink">
                        @if ($publicacion->sueldo)
                            ${{ number_format($publicacion->sueldo, 0, ',', '.') }} líquidos aprox.
                            {{-- El portal ya no publica el monto: solo se usa para que el
                                 postulante pueda filtrar por rango de renta. --}}
                            <span class="text-[13px] text-gray-500">(no se muestra en el portal; sirve para el filtro por rango)</span>
                        @else
                            <span class="text-gray-500">No informado</span>
                        @endif
                    </dd>
                </div>
                <div class="md:col-span-2"><dt class="text-[12px] font-bold uppercase tracking-[0.1em] text-gray-400">Descripción de la vacante</dt><dd class="mt-1 whitespace-pre-line text-[14px] leading-relaxed text-gray-600">{{ $publicacion->descripcion }}</dd></div>
            </dl>
        </section>

        <section class="ad-card mb-6">
            <div class="ad-card-head"><div><h2 class="text-[19px] font-extrabold">Requisitos</h2><p class="mt-1 text-[13px] text-gray-500">Perfil mínimo solicitado.</p></div></div>
            <dl class="grid gap-5 p-6 md:grid-cols-2">
                <div><dt class="text-[12px] font-bold uppercase tracking-[0.1em] text-gray-400">Experiencia laboral</dt><dd class="mt-1 text-[14px] text-ink">{{ $publicacion->experiencia_laboral }}</dd></div>
                <div><dt class="text-[12px] font-bold uppercase tracking-[0.1em] text-gray-400">Estudios mínimos</dt><dd class="mt-1 text-[14px] text-ink">{{ $publicacion->estudios_minimos }} · {{ $publicacion->situacion_academica }}</dd></div>
                <div class="md:col-span-2"><dt class="text-[12px] font-bold uppercase tracking-[0.1em] text-gray-400">Descripción de requisitos</dt><dd class="mt-1 whitespace-pre-line text-[14px] leading-relaxed text-gray-600">{{ $publicacion->requisitos }}</dd></div>
                <div class="md:col-span-2">
                    <dt class="text-[12px] font-bold uppercase tracking-[0.1em] text-gray-400">Competencias</dt>
                    <dd class="mt-2 flex flex-wrap gap-2">
                        @forelse ($publicacion->competencias ?? [] as $competencia)
                            <span wire:key="competencia-{{ $loop->index }}" class="ad-chip">{{ $competencia }}</span>
                        @empty
                            <span class="text-[14px] text-gray-500">Sin competencias declaradas.</span>
                        @endforelse
                    </dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="text-[12px] font-bold uppercase tracking-[0.1em] text-gray-400">Idiomas</dt>
                    <dd class="mt-2 flex flex-wrap gap-2">
                        @forelse ($publicacion->idiomas ?? [] as $idioma)
                            <span wire:key="idioma-{{ $loop->index }}" class="ad-chip">{{ $idioma }}</span>
                        @empty
                            <span class="text-[14px] text-gray-500">Sin idiomas requeridos.</span>
                        @endforelse
                    </dd>
                </div>
            </dl>
        </section>

        <section class="ad-card mb-6">
            <div class="ad-card-head"><div><h2 class="text-[19px] font-extrabold">Preguntas generales</h2><p class="mt-1 text-[13px] text-gray-500">Los postulantes las responden al enviar su postulación.</p></div></div>
            <div class="p-6">
                @forelse ($publicacion->preguntas ?? [] as $pregunta)
                    <p wire:key="pregunta-{{ $loop->index }}" class="border-b border-line py-3 text-[14px] text-ink last:border-0 last:pb-0 first:pt-0">{{ $loop->iteration }}. {{ $pregunta }}</p>
                @empty
                    <p class="text-[13px] text-gray-500">No agregaste preguntas adicionales.</p>
                @endforelse
            </div>
        </section>

        <section class="ad-card mb-6">
            <div class="ad-card-head"><div><h2 class="text-[19px] font-extrabold">Configuraciones</h2></div></div>
            <dl class="grid gap-5 p-6 md:grid-cols-2">
                @foreach ([
                    'Empleo inclusivo' => $publicacion->empleo_inclusivo,
                    'Postulación fácil' => $publicacion->postulacion_facil,
                    'Notificación de postulaciones' => $publicacion->notificar_postulaciones,
                    'Evaluación online' => $publicacion->evaluacion_online,
                    'Evaluación manual' => $publicacion->evaluacion_manual,
                ] as $etiqueta => $activo)
                    <div wire:key="config-{{ $loop->index }}" class="flex items-center gap-2">
                        @if ($activo)
                            <flux:icon.check-circle variant="solid" class="size-5 flex-none text-match" />
                        @else
                            <flux:icon.x-circle class="size-5 flex-none text-gray-400" />
                        @endif
                        <dt class="text-[14px] text-ink">{{ $etiqueta }}</dt>
                        <dd class="sr-only">{{ $activo ? 'Activado' : 'Desactivado' }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    </div>

    {{-- Confirmación de borrado: reemplaza el confirm nativo del navegador. --}}
    <flux:modal name="borrar-publicacion" class="max-w-lg" wire:close="$set('confirmacionTexto', '')">
        <div class="space-y-4">
            <div class="flex items-start gap-3">
                <span class="grid size-10 flex-none place-items-center rounded-xl bg-red-100 text-[#A93226] dark:bg-red-950/40 dark:text-red-400"><flux:icon.trash class="size-5" /></span>
                <div class="min-w-0">
                    <flux:heading size="lg">Eliminar publicación</flux:heading>
                    <flux:text class="mt-1 truncate">«{{ $publicacion->cargo }}»</flux:text>
                </div>
            </div>

            <flux:text>La oferta dejará de estar visible en el portal y se archivarán las {{ $totalPostulaciones }} postulaciones que recibió. Podrás deshacer esta acción desde el listado.</flux:text>

            <flux:text>Para confirmar, escribe <strong class="font-bold text-ink">ELIMINAR</strong> en el siguiente cuadro y haz clic en Aceptar.</flux:text>

            <flux:input wire:model.live.debounce.200ms="confirmacionTexto" placeholder="ELIMINAR" autocomplete="off" autofocus />
            @error('confirmacionTexto')<flux:text class="text-[#A93226] dark:text-red-400">{{ $message }}</flux:text>@enderror

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">Cancelar</flux:button></flux:modal.close>
                <flux:button variant="danger" wire:click="borrar" wire:loading.attr="disabled" wire:target="borrar" :disabled="mb_strtoupper(trim($confirmacionTexto)) !== 'ELIMINAR'">Aceptar</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
