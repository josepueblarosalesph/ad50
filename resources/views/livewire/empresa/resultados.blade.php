<div>
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:nav>
        <a href="{{ route('empresa.panel') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Mi Panel</a>
        <a wire:navigate href="{{ route('empresa.busquedas.index') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-ink bg-orange-100">Mis Procesos</a>
        @if (auth()->user()->esPrincipalEmpresa())
            <a wire:navigate href="{{ route('empresa.equipo') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Equipo</a>
        @endif
    </x-slot:nav>
    <x-slot:sidebar>
        <div class="sticky top-24 space-y-3">
            <livewire:empresa.filtro-actualizacion :actual="$actualizacion" wire:key="actualizacion-desktop" />
            <livewire:empresa.filtros-busqueda :busqueda="$busqueda" wire:key="filtros-desktop" />
        </div>
    </x-slot:sidebar>

    <div>
    <a wire:navigate href="{{ route('empresa.busquedas.index') }}" class="ad-btn-ghost ad-btn-sm mb-4 inline-flex items-center gap-2">
        <flux:icon.arrow-left class="size-4" />
        Volver a Procesos
    </a>

    {{-- Filtros en móvil: el sidebar del layout se oculta bajo md, así que aquí van colapsables. --}}
    <details class="group mb-4 rounded-xl border border-line-2 bg-white dark:bg-[#222528] md:hidden">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-4 py-3 text-[14px] font-bold text-ink">
            <span class="inline-flex items-center gap-2"><flux:icon.funnel class="size-4 text-orange-500" />Filtros</span>
            <flux:icon.chevron-down class="size-4 text-gray-400 transition group-open:rotate-180" />
        </summary>
        <div class="space-y-3 border-t border-line px-3 pb-3 pt-3">
            <livewire:empresa.filtro-actualizacion :actual="$actualizacion" wire:key="actualizacion-movil" />
            <livewire:empresa.filtros-busqueda :busqueda="$busqueda" lazy wire:key="filtros-movil" />
        </div>
    </details>

    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            @if ($editandoTitulo)
                <form wire:submit="guardarTitulo" class="flex flex-wrap items-center gap-2">
                    <flux:input wire:model="tituloEditado" class="max-w-md" placeholder="Nombre del proceso" autofocus />
                    <button type="submit" class="ad-btn-primary ad-btn-sm" wire:loading.attr="disabled" wire:target="guardarTitulo">Guardar</button>
                    <button type="button" wire:click="cancelarTitulo" class="ad-btn-ghost ad-btn-sm">Cancelar</button>
                </form>
                @error('tituloEditado')<p class="mt-1.5 text-[13px] font-semibold text-[#A93226] dark:text-red-400">{{ $message }}</p>@enderror
            @else
                <div class="flex items-center gap-2">
                    <h1 class="text-[25px] font-extrabold">{{ $busqueda->titulo }}</h1>
                    <button type="button" wire:click="editarTitulo" class="rounded-lg p-1.5 text-gray-400 transition hover:bg-orange-100 hover:text-orange-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500" aria-label="Editar el nombre del proceso" title="Editar nombre">
                        <flux:icon.pencil-square class="size-5" />
                    </button>
                </div>
            @endif
            <p class="mt-1.5 text-[14px] text-gray-500">Candidatos ordenados por nivel de coincidencia.</p>
        </div>

        <div class="flex flex-col items-start gap-1.5 sm:items-end">
            <div class="inline-flex rounded-xl border border-line-2 bg-white p-1 transition-colors dark:bg-[#222528]" aria-label="Filtrar candidatos">
                <button type="button" wire:click="mostrar('todos')" @class(['rounded-lg px-4 py-2 text-[13px] font-bold transition', 'bg-ink text-white dark:bg-orange-600 dark:text-white' => $filtro === 'todos', 'text-gray-500 hover:text-ink dark:hover:bg-white/5' => $filtro !== 'todos'])>Todos <span class="ml-1 opacity-70">{{ $totalCandidatos }}</span></button>
                <button type="button" wire:click="mostrar('favoritos')" @class(['rounded-lg px-4 py-2 text-[13px] font-bold transition', 'bg-orange-600 text-white' => $filtro === 'favoritos', 'text-gray-500 hover:text-orange-600' => $filtro !== 'favoritos'])><flux:icon.star class="inline size-4" /> Favoritos <span class="ml-1 opacity-70">{{ $totalFavoritos }}</span></button>
            </div>
            <p class="max-w-xs text-[13px] text-gray-500 sm:text-right">@if ($criterios !== [])Mostrando {{ $candidatos->total() }} que cumplen los filtros seleccionados.@else Marca perfiles para construir tu selección sin salir del listado.@endif</p>
            @if ($planVigente)
                <p class="inline-flex items-center gap-1 text-[12px] font-bold text-orange-600"><flux:icon.lock-open class="size-3.5" />{{ $desbloqueosDisponibles }} {{ $desbloqueosDisponibles === 1 ? 'desbloqueo disponible' : 'desbloqueos disponibles' }}</p>
            @endif
        </div>
    </div>

    @if (session('desbloqueo_error'))
        <div class="mb-4 flex items-center gap-2 rounded-xl border border-[#E7B6AE] bg-[#FBEDEA] px-4 py-3 text-[13px] font-semibold text-[#A93226] dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
            <flux:icon.exclamation-triangle class="size-4 flex-none" />{{ session('desbloqueo_error') }}
        </div>
    @endif


    <div class="space-y-3">
        @forelse ($candidatos as $match)
            <article wire:key="candidato-{{ $match->postulante_id }}" class="ad-card relative overflow-hidden p-4 md:p-5">
                <div class="absolute inset-y-0 left-0 w-1 bg-orange-500"></div>
                <div class="grid items-stretch gap-4 md:grid-cols-[minmax(0,1fr)_auto] md:gap-0">
                    <div class="flex min-w-0 items-center gap-4 md:pr-6">
                        <div class="grid size-12 flex-none place-items-center rounded-[12px] bg-sage-100 text-ink" aria-hidden="true"><flux:icon.user class="size-5" /></div>
                        <div class="min-w-0">
                            <p class="truncate text-[11px] font-extrabold uppercase tracking-[.14em] text-gray-400">{{ $match->postulante->carrera ?: 'Carrera no informada' }}</p>
                            <div class="mt-0.5 flex items-center gap-1.5">
                                @php($nombreCandidato = in_array($match->postulante_id, $postulantesDesbloqueados) ? $match->postulante->user->name : ($match->postulante->user->nombres ?: \Illuminate\Support\Str::before($match->postulante->user->name, ' ')))
                                <h2 class="truncate text-[20px] font-extrabold text-ink">
                                    @if ($match->exists)
                                        <a wire:navigate href="{{ route('empresa.candidatos.show', ['match' => $match, 'filtro' => $filtro, 'criterios' => $criterios]) }}" class="rounded decoration-orange-300 decoration-2 underline-offset-4 transition hover:text-orange-600 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500">{{ $nombreCandidato }}</a>
                                    @else
                                        {{ $nombreCandidato }}
                                    @endif
                                </h2>
                                @if (in_array($match->postulante_id, $postulantesConNota))
                                    <flux:tooltip content="Tienes una nota sobre este candidato">
                                        <span class="grid size-6 flex-none place-items-center rounded-full bg-orange-100 text-orange-600" aria-label="Tiene una nota"><flux:icon.pencil-square class="size-4" /></span>
                                    </flux:tooltip>
                                @endif
                            </div>
                            @php($ultimaExp = $match->postulante->ultimaExperiencia())
                            @if ($ultimaExp)
                                <p class="mt-1 flex items-center gap-1.5 text-[13px] text-gray-600 dark:text-gray-300">
                                    <flux:icon.briefcase class="size-3.5 flex-none text-gray-400" />
                                    <span class="truncate"><span class="font-semibold text-ink">{{ $ultimaExp['cargo'] }}</span>@if ($ultimaExp['empresa']) · {{ $ultimaExp['empresa'] }}@endif@if ($ultimaExp['duracion']) · {{ $ultimaExp['duracion'] }}@endif</span>
                                </p>
                            @endif
                            <p class="mt-2 max-w-4xl text-[13px] leading-relaxed text-gray-500">{{ Str::limit($match->postulante->resumen_profesional ?: 'Sin descripción profesional disponible.', 100, '…') }}</p>
                            @if ($match->postulante->updated_at)
                                <p class="mt-1.5 flex items-center gap-1.5 text-[11.5px] text-gray-400">
                                    <flux:icon.clock class="size-3.5 flex-none" />
                                    Ficha actualizada el {{ $match->postulante->updated_at->translatedFormat('d M Y') }}
                                </p>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 border-t border-line pt-3 md:min-w-44 md:flex-col md:items-end md:justify-center md:gap-2.5 md:border-l md:border-t-0 md:pl-4 md:pt-0">
                        <div class="flex items-center gap-2">
                            @if ($match->exists)
                                @if (in_array($match->postulante_id, $postulantesDesbloqueados))
                                    <flux:tooltip content="Perfil desbloqueado">
                                        <span class="grid size-10 flex-none place-items-center rounded-xl border border-[#BFE6CD] bg-match-100 text-match" aria-label="Perfil desbloqueado"><flux:icon.lock-open class="size-5" /></span>
                                    </flux:tooltip>
                                @elseif ($planVigente && $desbloqueosDisponibles > 0)
                                    <flux:tooltip content="Desbloquear perfil (usa 1 desbloqueo)">
                                        <button type="button" wire:click="desbloquear({{ $match->postulante_id }})" wire:confirm="Desbloquear este perfil descontará 1 desbloqueo de tu plan. ¿Continuar?" wire:loading.attr="disabled" wire:target="desbloquear({{ $match->postulante_id }})" class="grid size-10 flex-none place-items-center rounded-xl border border-line-2 bg-white text-gray-400 transition hover:border-orange-300 hover:text-orange-600 disabled:opacity-50 dark:bg-[#2A2D30]" aria-label="Desbloquear perfil de {{ $nombreCandidato }}"><flux:icon.lock-closed class="size-5" /></button>
                                    </flux:tooltip>
                                @else
                                    <flux:tooltip :content="$planVigente ? 'Sin desbloqueos disponibles en tu plan' : 'Necesitas una suscripción activa para desbloquear'">
                                        <span class="grid size-10 flex-none place-items-center rounded-xl border border-line-2 bg-paper text-gray-300 dark:bg-[#222528]" aria-label="Perfil bloqueado"><flux:icon.lock-closed class="size-5" /></span>
                                    </flux:tooltip>
                                @endif
                                <flux:tooltip :content="$match->favorito ? 'Quitar de favoritos' : 'Guardar como favorito'">
                                    <button type="button" wire:click="toggleFavorito({{ $match->id }})" wire:loading.attr="disabled" wire:target="toggleFavorito({{ $match->id }})" @class(['grid size-10 flex-none place-items-center rounded-xl border transition disabled:opacity-50', 'border-orange-300 bg-orange-100 text-orange-600' => $match->favorito, 'border-line-2 bg-white text-gray-400 hover:border-orange-300 hover:text-orange-600 dark:bg-[#2A2D30] dark:hover:bg-orange-100' => ! $match->favorito]) aria-label="{{ $match->favorito ? 'Quitar candidato de favoritos' : 'Guardar candidato como favorito' }}" aria-pressed="{{ $match->favorito ? 'true' : 'false' }}"><flux:icon.star variant="solid" class="size-5" /></button>
                                </flux:tooltip>
                                <a wire:navigate href="{{ route('empresa.candidatos.show', ['match' => $match, 'filtro' => $filtro, 'criterios' => $criterios]) }}" class="ad-btn-primary ad-btn-sm whitespace-nowrap">Ver perfil</a>
                            @else
                                <flux:tooltip content="Guarda el filtro para poder abrir y marcar este candidato">
                                    <span class="rounded-lg bg-sage-100 px-2.5 py-1.5 text-[11.5px] font-bold uppercase tracking-[.1em] text-gray-500">Nuevo con este filtro</span>
                                </flux:tooltip>
                            @endif
                        </div>

                        {{-- Accesos rápidos para candidatos desbloqueados: CV, notas y LinkedIn. --}}
                        @if ($match->exists && in_array($match->postulante_id, $postulantesDesbloqueados))
                            <div class="flex items-center gap-1.5">
                                @if (in_array($match->postulante_id, $postulantesConCv))
                                    <flux:tooltip content="Descargar CV">
                                        <button type="button" wire:click="descargarCv({{ $match->postulante_id }})" wire:loading.attr="disabled" wire:target="descargarCv({{ $match->postulante_id }})" class="grid size-9 flex-none place-items-center rounded-lg border border-line-2 bg-white text-gray-500 transition hover:border-orange-300 hover:text-orange-600 disabled:opacity-50 dark:bg-[#2A2D30]" aria-label="Descargar CV de {{ $nombreCandidato }}">
                                            <flux:icon.arrow-down-tray class="size-4" />
                                        </button>
                                    </flux:tooltip>
                                @endif
                                <flux:tooltip content="Notas del candidato">
                                    <a wire:navigate href="{{ route('empresa.candidatos.show', ['match' => $match, 'filtro' => $filtro, 'criterios' => $criterios]) }}#notas" class="grid size-9 flex-none place-items-center rounded-lg border border-line-2 bg-white text-gray-500 transition hover:border-orange-300 hover:text-orange-600 dark:bg-[#2A2D30]" aria-label="Ver notas de {{ $nombreCandidato }}">
                                        <flux:icon.pencil-square class="size-4" />
                                    </a>
                                </flux:tooltip>
                                @if (filled($match->postulante->linkedin))
                                    <flux:tooltip content="Abrir LinkedIn">
                                        <a href="{{ $match->postulante->linkedin }}" target="_blank" rel="noopener noreferrer" class="grid size-9 flex-none place-items-center rounded-lg border border-line-2 bg-white text-gray-500 transition hover:border-orange-300 hover:text-orange-600 dark:bg-[#2A2D30]" aria-label="Abrir LinkedIn de {{ $nombreCandidato }}">
                                            <flux:icon.link class="size-4" />
                                        </a>
                                    </flux:tooltip>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="ad-card p-10 text-center"><flux:icon.magnifying-glass class="size-8 text-gray-400 mx-auto" /><h2 class="font-bold mt-3">{{ $criterios !== [] ? 'Ningún candidato cumple esta combinación' : 'Aún no hay coincidencias' }}</h2><p class="text-[13px] text-gray-500 mt-2">{{ $criterios !== [] ? 'Quita uno de los filtros para ampliar los resultados.' : 'Prueba ampliando los criterios del proceso.' }}</p>@if ($criterios !== [])<button type="button" wire:click="limpiarCriterios" class="ad-btn-ghost ad-btn-sm mt-4">Limpiar filtros</button>@endif</div>
        @endforelse
    </div>

    @if ($candidatos->hasPages())
        <div class="mt-6">{{ $candidatos->links() }}</div>
    @endif
    </div>
</div>
