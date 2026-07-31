<div>
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:nav><x-nav-empresa activo="publicaciones" /></x-slot:nav>
    <x-slot:sidebar>
        <div class="sticky top-24">
            <livewire:empresa.filtros-postulaciones :publicacion-id="$publicacion->id" wire:key="filtros-postulaciones-desktop-{{ $publicacion->id }}" />
        </div>
    </x-slot:sidebar>

    <div>
        <a wire:navigate href="{{ route('empresa.publicaciones.index') }}" class="ad-btn-ghost ad-btn-sm mb-4 inline-flex items-center gap-2">
            <flux:icon.arrow-left class="size-4" />
            Volver a Publicaciones
        </a>

        {{-- Filtros en móvil --}}
        <details class="group mb-4 rounded-xl border border-line-2 bg-white dark:bg-[#222528] md:hidden">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-4 py-3 text-[14px] font-bold text-ink">
                <span class="inline-flex items-center gap-2"><flux:icon.funnel class="size-4 text-orange-500" />Filtrar postulantes</span>
                <flux:icon.chevron-down class="size-4 text-gray-400 transition group-open:rotate-180" />
            </summary>
            <div class="border-t border-line px-3 pb-3 pt-3">
                <livewire:empresa.filtros-postulaciones :publicacion-id="$publicacion->id" lazy wire:key="filtros-postulaciones-movil-{{ $publicacion->id }}" />
            </div>
        </details>

        <div class="mb-5">
            <h1 class="text-[25px] font-extrabold">{{ $publicacion->cargo }}</h1>
            <p class="mt-1.5 text-[14px] text-gray-500">
                {{ $totalCandidatos }} {{ $totalCandidatos === 1 ? 'persona' : 'personas' }} en esta publicación:
                <b class="text-ink">{{ $totalPostularon }}</b> {{ $totalPostularon === 1 ? 'postuló' : 'postularon' }}
                y <b class="text-ink">{{ $totalAgregados }}</b> {{ $totalAgregados === 1 ? 'fue agregada' : 'fueron agregadas' }} por la empresa.
            </p>
        </div>

        {{-- Filtro por estado de la postulación, más un chip por el otro origen. --}}
        <div class="mb-5 flex flex-wrap gap-2">
            @php($estadoChips = array_merge(['todas' => 'Todas'], $estados, [$filtroAgregados => 'Agregados']))
            @foreach ($estadoChips as $valor => $etiqueta)
                @php($conteo = match ($valor) {
                    'todas' => $totalCandidatos,
                    $filtroAgregados => $totalAgregados,
                    default => (int) ($conteoPorEstado[$valor] ?? 0),
                })
                <button type="button" wire:click="mostrarEstado('{{ $valor }}')" @class([
                    'inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-[13px] font-bold transition',
                    'border-orange-300 bg-orange-100 text-orange-700' => $estado === $valor,
                    'border-line-2 bg-white text-gray-500 hover:text-ink dark:bg-[#222528]' => $estado !== $valor,
                ])>
                    {{ $etiqueta }} <span class="opacity-70">{{ $conteo }}</span>
                </button>
            @endforeach
        </div>

        @if ($criterios !== null && $totalFiltradas < $totalCandidatos)
            <p class="mb-4 text-[13px] text-gray-500">Mostrando <b class="text-ink">{{ $totalFiltradas }}</b> que cumplen los filtros seleccionados.</p>
        @endif

        <div class="space-y-3">
            @forelse ($candidatos as $candidato)
                @php($postulante = $candidato->postulante)
                @php($ultimaExp = $postulante->ultimaExperiencia())
                {{-- Misma tarjeta que el listado de Prospección de Candidatos, más lo propio
                     de una publicación: el origen de la persona y el estado de su postulación. --}}
                <article wire:key="{{ $candidato->clave() }}" class="ad-card relative overflow-hidden p-4 md:p-5">
                    <div class="absolute inset-y-0 left-0 w-1 bg-orange-500"></div>
                    <div class="grid items-stretch gap-4 md:grid-cols-[minmax(0,1fr)_auto] md:gap-0">
                        <div class="flex min-w-0 items-center gap-4 md:pr-6">
                            <div class="grid size-12 flex-none place-items-center rounded-[12px] bg-sage-100 text-ink" aria-hidden="true"><flux:icon.user class="size-5" /></div>
                            <div class="min-w-0">
                                <p class="truncate text-[11px] font-extrabold uppercase tracking-[.14em] text-gray-400">{{ $postulante->carrera ?: 'Carrera no informada' }}</p>
                                {{-- El nombre abre el perfil completo sin salir del listado. Mientras
                                     viaja la petición se marca la fila como ocupada y aparece el
                                     indicador, para que el clic no parezca no haber hecho nada. --}}
                                <h2 class="mt-0.5 text-[20px] font-extrabold text-ink">
                                    <button
                                        type="button"
                                        wire:click="verDetalle({{ $postulante->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="verDetalle({{ $postulante->id }})"
                                        x-bind:aria-busy="$wire.detalleId === {{ $postulante->id }} ? 'false' : null"
                                        class="flex max-w-full items-center gap-2 rounded text-left transition hover:text-orange-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600 disabled:opacity-60"
                                    >
                                        <span class="truncate decoration-orange-300 decoration-2 underline-offset-4 hover:underline">{{ $candidato->nombre() }}</span>
                                        <x-spinner
                                            wire:loading
                                            wire:target="verDetalle({{ $postulante->id }})"
                                            class="size-4 flex-none text-orange-500"
                                        />
                                    </button>
                                </h2>

                                {{-- Origen: de dónde salió esta persona. Los dos no se excluyen. --}}
                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                    @if ($candidato->postulo())
                                        <span class="ad-chip ad-chip-sm ad-chip-green"><flux:icon.paper-airplane class="size-3.5" />Postuló</span>
                                    @endif
                                    @if ($candidato->agregado())
                                        <flux:tooltip :content="$candidato->busquedaDeOrigen() ? 'Agregado desde la búsqueda «'.$candidato->busquedaDeOrigen().'»' : 'Agregado por la empresa desde Prospección de Candidatos'">
                                            <span class="ad-chip ad-chip-sm"><flux:icon.user-plus class="size-3.5" />Agregado por la empresa</span>
                                        </flux:tooltip>
                                    @endif
                                </div>

                                @if ($ultimaExp)
                                    <p class="mt-1.5 flex items-center gap-1.5 text-[13px] text-gray-600 dark:text-gray-300">
                                        <flux:icon.briefcase class="size-3.5 flex-none text-gray-400" />
                                        <span class="truncate"><span class="font-semibold text-ink">{{ $ultimaExp['cargo'] }}</span>@if ($ultimaExp['empresa']) · {{ $ultimaExp['empresa'] }}@endif@if ($ultimaExp['duracion']) · {{ $ultimaExp['duracion'] }}@endif</span>
                                    </p>
                                @endif

                                <p class="mt-2 max-w-4xl text-[13px] leading-relaxed text-gray-500">{{ Str::limit($postulante->resumen_profesional ?: 'Sin descripción profesional disponible.', 100, '…') }}</p>

                                @if ($candidato->fecha())
                                    <p class="mt-1.5 flex items-center gap-1.5 text-[11.5px] text-gray-400">
                                        <flux:icon.clock class="size-3.5 flex-none" />
                                        {{ $candidato->postulo() ? 'Postuló' : 'Agregado' }} el {{ $candidato->fecha()->translatedFormat('d M Y') }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 border-t border-line pt-3 md:min-w-44 md:flex-col md:items-end md:justify-center md:gap-2.5 md:border-l md:border-t-0 md:pl-4 md:pt-0">
                            <div class="flex items-center gap-2">
                                {{-- Estado de la identidad. Quien postuló entregó sus datos a esta
                                     oferta; a quien solo fue agregado se le abre el perfil desde
                                     Prospección, que es donde se gastan los desbloqueos. --}}
                                @if ($candidato->datosIdentificados())
                                    <flux:tooltip :content="$candidato->postulo() ? 'Postuló: sus datos están a la vista' : 'Perfil desbloqueado'">
                                        <span class="grid size-10 flex-none place-items-center rounded-xl border border-[#BFE6CD] bg-match-100 text-match" aria-label="Datos del candidato visibles"><flux:icon.lock-open class="size-5" /></span>
                                    </flux:tooltip>
                                @else
                                    <flux:tooltip content="Desbloquea el perfil desde Prospección de Candidatos para ver su nombre completo y su contacto">
                                        <span class="grid size-10 flex-none place-items-center rounded-xl border border-line-2 bg-paper text-gray-300 dark:bg-[#222528]" aria-label="Perfil sin desbloquear"><flux:icon.lock-closed class="size-5" /></span>
                                    </flux:tooltip>
                                @endif

                                @php($esFavorito = in_array($postulante->id, $postulantesFavoritos, true))
                                <flux:tooltip :content="$esFavorito ? 'Quitar de favoritos' : 'Guardar como favorito'">
                                    <button type="button" wire:click="toggleFavorito({{ $postulante->id }})" wire:loading.attr="disabled" wire:target="toggleFavorito({{ $postulante->id }})" @class(['grid size-10 flex-none place-items-center rounded-xl border transition disabled:opacity-50', 'border-orange-300 bg-orange-100 text-orange-600' => $esFavorito, 'border-line-2 bg-white text-gray-400 hover:border-orange-300 hover:text-orange-600 dark:bg-[#2A2D30] dark:hover:bg-orange-100' => ! $esFavorito]) aria-label="{{ $esFavorito ? 'Quitar candidato de favoritos' : 'Guardar candidato como favorito' }}" aria-pressed="{{ $esFavorito ? 'true' : 'false' }}"><flux:icon.star variant="solid" class="size-5" /></button>
                                </flux:tooltip>

                                @php($asociadas = $asociacionesPorPostulante[$postulante->id] ?? 0)
                                <flux:tooltip :content="$asociadas > 0 ? 'Asociado a '.$asociadas.' publicación(es)' : 'Asociar a una publicación'">
                                    <button type="button" wire:click="abrirAsociacion({{ $postulante->id }})" wire:loading.attr="disabled" wire:target="abrirAsociacion({{ $postulante->id }})" @class(['relative grid size-10 flex-none place-items-center rounded-xl border transition disabled:opacity-50', 'border-orange-300 bg-orange-100 text-orange-600' => $asociadas > 0, 'border-line-2 bg-white text-gray-400 hover:border-orange-300 hover:text-orange-600 dark:bg-[#2A2D30]' => $asociadas === 0]) aria-label="Asociar a {{ $candidato->nombre() }} a una publicación">
                                        <flux:icon.megaphone class="size-5" />
                                        @if ($asociadas > 0)
                                            <span class="absolute -right-1 -top-1 grid min-w-[18px] place-items-center rounded-full bg-orange-600 px-1 text-[10px] font-bold text-white">{{ $asociadas }}</span>
                                        @endif
                                    </button>
                                </flux:tooltip>

                                @php($tieneNota = in_array($postulante->id, $postulantesConNota))
                                <flux:tooltip :content="$tieneNota ? 'Ver notas de este candidato' : 'Sin notas todavía'">
                                    <button type="button" wire:click="abrirNotas({{ $postulante->id }})" wire:loading.attr="disabled" wire:target="abrirNotas({{ $postulante->id }})" @class(['grid size-10 flex-none place-items-center rounded-xl border transition disabled:opacity-50', 'border-orange-300 bg-orange-100 text-orange-600' => $tieneNota, 'border-line-2 bg-white text-gray-400 hover:border-orange-300 hover:text-orange-600 dark:bg-[#2A2D30]' => ! $tieneNota]) aria-label="Ver notas de {{ $candidato->nombre() }}">
                                        <flux:icon.pencil-square class="size-5" />
                                    </button>
                                </flux:tooltip>

                                <button type="button" wire:click="verDetalle({{ $postulante->id }})" wire:loading.attr="disabled" wire:target="verDetalle({{ $postulante->id }})" class="ad-btn-primary ad-btn-sm whitespace-nowrap">Ver perfil</button>
                            </div>

                            <div class="flex items-center gap-2">
                                {{-- El CV se descarga solo de quien postuló: es material que entregó a esta oferta. --}}
                                @if ($candidato->postulo() && $postulante->cv_ruta)
                                    <flux:tooltip content="Descargar CV">
                                        <button type="button" wire:click="descargarCv({{ $candidato->postulacion->id }})" wire:loading.attr="disabled" wire:target="descargarCv({{ $candidato->postulacion->id }})" class="grid size-9 flex-none place-items-center rounded-lg border border-line-2 bg-white text-gray-500 transition hover:border-orange-300 hover:text-orange-600 disabled:opacity-50 dark:bg-[#2A2D30]" aria-label="Descargar CV de {{ $candidato->nombre() }}">
                                            <flux:icon.arrow-down-tray class="size-4" />
                                        </button>
                                    </flux:tooltip>
                                @endif

                                @if ($candidato->postulo())
                                    <select
                                        wire:key="estado-{{ $candidato->postulacion->id }}"
                                        wire:change="cambiarEstado({{ $candidato->postulacion->id }}, $event.target.value)"
                                        aria-label="Estado de la postulación de {{ $candidato->nombre() }}"
                                        @class([
                                            'rounded-lg border px-2.5 py-1.5 text-[13px] font-bold focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500',
                                            'border-[#BFE6CD] bg-match-100 text-match' => $candidato->estado() === 'seleccionada',
                                            'border-[#E7B6AE] bg-[#FBEDEA] text-[#A93226]' => $candidato->estado() === 'descartada',
                                            'border-line-2 bg-paper text-gray-600' => ! in_array($candidato->estado(), ['seleccionada', 'descartada'], true),
                                        ])
                                    >
                                        @foreach ($estados as $valor => $etiqueta)
                                            <option value="{{ $valor }}" @selected($candidato->estado() === $valor)>{{ $etiqueta }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    {{-- Sin postulación no hay estado que gestionar: el flujo de revisión
                                         empieza cuando la persona postula. --}}
                                    <span class="rounded-lg border border-dashed border-line-2 px-2.5 py-1.5 text-[13px] font-bold text-gray-400">Sin postulación</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="ad-card p-10 text-center">
                    <flux:icon.users class="mx-auto size-8 text-gray-400" />
                    <h2 class="mt-3 font-bold">{{ ($criterios !== null || $estado !== 'todas') ? 'Nadie cumple este filtro' : 'Aún no hay postulantes' }}</h2>
                    <p class="mt-2 text-[13px] text-gray-500">{{ ($criterios !== null || $estado !== 'todas') ? 'Ajusta los filtros para ver más personas.' : 'Aquí aparecerá quien postule a esta publicación y quien agregues desde Prospección de Candidatos.' }}</p>
                </div>
            @endforelse
        </div>

        @if ($candidatos->hasPages())
            <div class="mt-6">{{ $candidatos->links() }}</div>
        @endif
    </div>

    <x-asociar-publicaciones-modal :publicaciones="$publicacionesAsociables" :asociadas="$publicacionesDelCandidato" />

    <x-notas-candidato-modal :notas="$notasDelCandidato" />

    {{-- Perfil completo del postulante: lo que antes estiraba cada tarjeta. --}}
    <flux:modal name="detalle-postulante" class="max-w-2xl" wire:close="cerrarDetalle">
        @if ($detalle)
            @php($p = $detalle->postulante)
            <div class="space-y-5">
                <div>
                    <p class="text-[11px] font-extrabold uppercase tracking-[.14em] text-gray-400">{{ $p->carrera ?: 'Carrera no informada' }}</p>
                    <flux:heading size="lg">{{ $detalle->nombre() }}</flux:heading>
                    <flux:text class="mt-1">
                        {{ collect([$p->cargo_actual, $p->empresa_actual, $p->anios_experiencia ? $p->anios_experiencia.' años de experiencia' : null])->filter()->implode(' · ') ?: 'Sin experiencia informada' }}
                    </flux:text>
                    <p class="mt-1 text-[12px] text-gray-400">
                        @if ($detalle->postulo())
                            Postuló el {{ $detalle->postulacion->created_at->translatedFormat('d M Y') }}
                        @else
                            Agregado por la empresa el {{ $detalle->fecha()?->translatedFormat('d M Y') }}{{ $detalle->busquedaDeOrigen() ? ' desde «'.$detalle->busquedaDeOrigen().'»' : '' }}
                        @endif
                    </p>
                </div>

                @if ($detalle->datosIdentificados())
                    {{-- Contacto a la vista: o postuló (entregó sus datos a esta oferta) o la
                         empresa gastó un desbloqueo en abrir el perfil. --}}
                    <div class="flex flex-wrap gap-x-4 gap-y-1.5 rounded-xl bg-paper p-4 text-[13px] text-gray-600 dark:bg-white/5 dark:text-gray-300">
                        @if ($p->rut)<span class="inline-flex items-center gap-1.5"><flux:icon.identification class="size-4 text-gray-400" />{{ $p->rut }}</span>@endif
                        @if ($p->telefono)<span class="inline-flex items-center gap-1.5"><flux:icon.phone class="size-4 text-gray-400" />{{ $p->telefono }}</span>@endif
                        @if ($p->user?->email)<a href="mailto:{{ $p->user->email }}" class="inline-flex items-center gap-1.5 hover:text-orange-600"><flux:icon.envelope class="size-4 text-gray-400" />{{ $p->user->email }}</a>@endif
                        @if ($p->linkedin)<a href="{{ $p->linkedin }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 hover:text-orange-600"><flux:icon.link class="size-4 text-gray-400" />LinkedIn</a>@endif
                    </div>
                @else
                    {{-- Quien no postuló no entregó sus datos a esta oferta: el contacto sigue
                         sujeto al desbloqueo del perfil, como en Prospección de Candidatos. --}}
                    <p class="flex items-start gap-2 rounded-xl border border-dashed border-line-2 p-4 text-[13px] text-gray-500">
                        <flux:icon.lock-closed class="mt-0.5 size-4 flex-none text-gray-400" />
                        Esta persona no postuló a la oferta: su nombre completo y sus datos de contacto se ven al desbloquear el perfil desde Prospección de Candidatos.
                    </p>
                @endif

                @if ($p->resumen_profesional)
                    <div>
                        <p class="text-[11px] font-extrabold uppercase tracking-wide text-gray-400">Acerca de</p>
                        <p class="mt-1.5 text-[13.5px] leading-relaxed text-gray-600 dark:text-gray-300">{{ $p->resumen_profesional }}</p>
                    </div>
                @endif

                @if (filled($p->experiencias))
                    <div>
                        <p class="text-[11px] font-extrabold uppercase tracking-wide text-gray-400">Experiencia</p>
                        <ul class="mt-1.5 space-y-2">
                            @foreach (array_slice($p->experiencias, 0, 4) as $exp)
                                <li class="text-[13.5px] text-gray-600 dark:text-gray-300">
                                    <b class="text-ink">{{ ($exp['cargo'] ?? '') === 'Otros' ? ($exp['cargo_otro'] ?? 'Otros') : ($exp['cargo'] ?? '—') }}</b>
                                    {{ filled($exp['empresa'] ?? null) ? ' · '.$exp['empresa'] : '' }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (filled($p->habilidades))
                    <div>
                        <p class="text-[11px] font-extrabold uppercase tracking-wide text-gray-400">Habilidades</p>
                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            @foreach (array_slice($p->habilidades, 0, 12) as $habilidad)<span class="ad-tag">{{ $habilidad }}</span>@endforeach
                        </div>
                    </div>
                @endif

                {{-- Las respuestas solo existen si la persona postuló. --}}
                @if ($detalle->postulo() && filled($publicacion->preguntas))
                    <div class="rounded-xl border border-line-2 p-4">
                        <p class="text-[11px] font-extrabold uppercase tracking-wide text-gray-400">Respuestas</p>
                        <div class="mt-2 space-y-3">
                            @foreach ($publicacion->preguntas as $i => $pregunta)
                                <div>
                                    <p class="text-[12.5px] font-bold text-ink">{{ $pregunta }}</p>
                                    <p class="mt-0.5 text-[13px] leading-relaxed text-gray-600 dark:text-gray-300">{{ $detalle->postulacion->respuestas[$i] ?? '—' }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex justify-end gap-2 border-t border-line pt-4">
                    @if ($detalle->postulo() && $p->cv_ruta)
                        <flux:button variant="ghost" wire:click="descargarCv({{ $detalle->postulacion->id }})" icon="arrow-down-tray">Descargar CV</flux:button>
                    @endif
                    <flux:modal.close><flux:button variant="primary">Cerrar</flux:button></flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
