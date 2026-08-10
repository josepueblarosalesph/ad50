<div class="ad-panel">
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:nav><x-nav-empresa activo="favoritos" /></x-slot:nav>
    @if ($carpetasVisibles)
        <x-slot:sidebar>
            <div class="sticky top-24">
                @include('livewire.empresa.partials.carpetas-favoritos', ['prefijo' => 'escritorio'])
            </div>
        </x-slot:sidebar>

        {{-- El sidebar del layout se oculta bajo md: en móvil las carpetas van plegadas. --}}
        <details class="group mb-4 rounded-xl border border-line-2 bg-white dark:bg-[#222528] md:hidden">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-4 py-3 text-[14px] font-bold text-ink">
                <span class="inline-flex items-center gap-2">
                    <flux:icon.folder class="size-4 text-orange-500" />
                    {{ $carpetaActiva?->nombre ?? ($carpeta === 'sin' ? 'Sin carpeta' : 'Mis carpetas') }}
                </span>
                <flux:icon.chevron-down class="size-4 text-gray-400 transition group-open:rotate-180" />
            </summary>
            <div class="border-t border-line px-3 pb-3 pt-3">
                @include('livewire.empresa.partials.carpetas-favoritos', ['prefijo' => 'movil'])
            </div>
        </details>
    @endif

    <div class="mb-6 flex flex-wrap items-start justify-between gap-5">
        <div>
            <h1 class="text-[30px] font-extrabold">{{ $carpetaActiva?->nombre ?? 'Mis favoritos' }}</h1>
            <p class="mt-2 text-[14px] text-gray-500">
                @if ($carpetaActiva)
                    {{ $carpetaActiva->favoritos_count }}
                    {{ $carpetaActiva->favoritos_count === 1 ? 'candidato agrupado' : 'candidatos agrupados' }}
                    en esta carpeta, de {{ $totalFavoritos }} guardados en tu cuenta.
                @elseif ($carpeta === 'sin')
                    {{ $sinCarpeta }} {{ $sinCarpeta === 1 ? 'candidato guardado aún sin carpeta' : 'candidatos guardados aún sin carpeta' }},
                    de {{ $totalFavoritos }} en tu cuenta.
                @else
                    {{ $totalFavoritos }} {{ $totalFavoritos === 1 ? 'candidato guardado' : 'candidatos guardados' }}
                    en tu cuenta.
                @endif
            </p>
        </div>
    </div>

    @if (session('desbloqueo_error'))
        <div class="mb-4 flex items-center gap-2 rounded-xl border border-[#E7B6AE] bg-[#FBEDEA] px-4 py-3 text-[13px] font-semibold text-[#A93226] dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
            <flux:icon.exclamation-triangle class="size-4 flex-none" />{{ session('desbloqueo_error') }}
        </div>
    @endif

    {{-- Filtros --}}
    <section class="ad-card mb-5 p-4 md:p-5">
        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <label for="filtro-busqueda" class="mb-1.5 block text-[12px] font-bold text-gray-600 dark:text-gray-300">Búsqueda de origen</label>
                <select id="filtro-busqueda" wire:model.live="busqueda" class="w-full rounded-lg border border-line-2 bg-white px-3 py-2 text-[13.5px] font-semibold text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500 dark:bg-[#2A2D30]">
                    <option value="todas">Todas las búsquedas</option>
                    @foreach ($busquedasDisponibles as $id => $titulo)
                        <option value="{{ $id }}">{{ $titulo }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filtro-publicacion" class="mb-1.5 block text-[12px] font-bold text-gray-600 dark:text-gray-300">Publicación asociada</label>
                <select id="filtro-publicacion" wire:model.live="publicacion" class="w-full rounded-lg border border-line-2 bg-white px-3 py-2 text-[13.5px] font-semibold text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500 dark:bg-[#2A2D30]">
                    <option value="todas">Todas</option>
                    <option value="sin">Sin publicación asociada</option>
                    @foreach ($publicacionesDisponibles as $id => $cargo)
                        <option value="{{ $id }}">{{ $cargo }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filtro-desbloqueo" class="mb-1.5 block text-[12px] font-bold text-gray-600 dark:text-gray-300">Estado del perfil</label>
                <select id="filtro-desbloqueo" wire:model.live="desbloqueo" class="w-full rounded-lg border border-line-2 bg-white px-3 py-2 text-[13.5px] font-semibold text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500 dark:bg-[#2A2D30]">
                    <option value="todos">Todos</option>
                    <option value="desbloqueados">Desbloqueados</option>
                    <option value="bloqueados">Sin desbloquear</option>
                </select>
            </div>
        </div>

        @if ($hayFiltros)
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-3">
                <p class="text-[13px] text-gray-500">
                    Mostrando <b class="text-ink">{{ $candidatos->total() }}</b> de {{ $totalFavoritos }} favoritos.
                </p>
                <button type="button" wire:click="limpiarFiltros" class="ad-btn-ghost ad-btn-sm">Limpiar filtros</button>
            </div>
        @endif
    </section>

    {{-- Listado: una fila por candidato --}}
    <div class="space-y-3">
        @forelse ($candidatos as $candidato)
            @php($desbloqueado = in_array($candidato->id, $postulantesDesbloqueados, true))
            @php($ultimaExp = $candidato->ultimaExperiencia())

            <article wire:key="favorito-{{ $candidato->id }}" class="ad-card overflow-hidden p-4 md:p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex min-w-0 items-start gap-4">
                        <div class="grid size-12 flex-none place-items-center rounded-[12px] bg-orange-100 text-orange-600" aria-hidden="true">
                            <flux:icon.star variant="solid" class="size-5" />
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-[11px] font-extrabold uppercase tracking-[.14em] text-gray-400">{{ $candidato->carrera ?: 'Carrera no informada' }}</p>
                            <h2 class="mt-1 truncate text-[16px] font-extrabold text-ink">{{ $ultimaExp['cargo'] ?? ($candidato->cargo_actual ?: 'Candidato #'.$candidato->id) }}</h2>
                            <p class="mt-1 truncate text-[13px] text-gray-500">
                                {{ collect([
                                    $ultimaExp['empresa'] ?? null,
                                    $candidato->anios_experiencia ? $candidato->anios_experiencia.' años de experiencia' : null,
                                ])->filter()->implode(' · ') ?: 'Sin experiencia informada' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-none flex-wrap items-center gap-2">
                        {{-- El candado es la acción: con cupo del plan desbloquea el perfil aquí
                             mismo; sin cupo (o ya desbloqueado) queda como indicador de estado. --}}
                        @if ($desbloqueado)
                            <flux:tooltip content="Perfil desbloqueado">
                                <span class="grid size-10 flex-none place-items-center rounded-xl border border-[#BFE6CD] bg-match-100 text-match" aria-label="Perfil desbloqueado">
                                    <flux:icon.lock-open class="size-5" />
                                </span>
                            </flux:tooltip>
                        @elseif ($planVigente && $desbloqueosDisponibles > 0)
                            <flux:tooltip content="Desbloquear perfil (usa 1 desbloqueo)">
                                <button type="button" wire:click="desbloquear({{ $candidato->id }})" wire:confirm="Desbloquear este perfil descontará 1 desbloqueo de tu plan. ¿Continuar?" wire:loading.attr="disabled" wire:target="desbloquear({{ $candidato->id }})" class="grid size-10 flex-none place-items-center rounded-xl border border-line-2 bg-white text-gray-400 transition hover:border-orange-300 hover:text-orange-600 disabled:opacity-50 dark:bg-[#2A2D30]" aria-label="Desbloquear perfil del candidato">
                                    <flux:icon.lock-closed class="size-5" />
                                </button>
                            </flux:tooltip>
                        @else
                            <flux:tooltip :content="$planVigente ? 'Sin desbloqueos disponibles en tu plan' : 'Necesitas una suscripción activa para desbloquear'">
                                <span class="grid size-10 flex-none place-items-center rounded-xl border border-line-2 bg-paper text-gray-400 dark:bg-[#222528]" aria-label="Perfil sin desbloquear">
                                    <flux:icon.lock-closed class="size-5" />
                                </span>
                            </flux:tooltip>
                        @endif

                        @php($enCarpetas = $carpetasPorCandidato[$candidato->id] ?? [])
                        @if ($carpetasVisibles)
                        <button type="button" wire:click="abrirCarpetas({{ $candidato->id }})" wire:loading.attr="disabled" wire:target="abrirCarpetas({{ $candidato->id }})" @class([
                            'ad-btn-sm inline-flex items-center gap-2 whitespace-nowrap rounded-xl border font-bold transition disabled:opacity-50',
                            'border-orange-300 bg-orange-100 text-orange-600' => $enCarpetas !== [],
                            'border-line-2 bg-white text-gray-500 hover:border-orange-300 hover:text-orange-600 dark:bg-[#2A2D30]' => $enCarpetas === [],
                        ])>
                            <flux:icon.folder class="size-4" />
                            Carpetas
                            @if ($enCarpetas !== [])
                                <span class="grid min-w-[18px] place-items-center rounded-full bg-orange-600 px-1 text-[10px] font-bold text-white">{{ count($enCarpetas) }}</span>
                            @endif
                        </button>
                        @endif

                        @php($asociadas = $candidato->publicacionesAsociadas->count())
                        <button type="button" wire:click="abrirAsociacion({{ $candidato->id }})" wire:loading.attr="disabled" wire:target="abrirAsociacion({{ $candidato->id }})" @class([
                            'ad-btn-sm inline-flex items-center gap-2 whitespace-nowrap rounded-xl border font-bold transition disabled:opacity-50',
                            'border-orange-300 bg-orange-100 text-orange-600' => $asociadas > 0,
                            'border-line-2 bg-white text-gray-500 hover:border-orange-300 hover:text-orange-600 dark:bg-[#2A2D30]' => $asociadas === 0,
                        ])>
                            <flux:icon.megaphone class="size-4" />
                            Asociar a publicación
                            @if ($asociadas > 0)
                                <span class="grid min-w-[18px] place-items-center rounded-full bg-orange-600 px-1 text-[10px] font-bold text-white">{{ $asociadas }}</span>
                            @endif
                        </button>

                        @if ($candidato->match_visible_id)
                            <a wire:navigate href="{{ route('empresa.candidatos.show', ['match' => $candidato->match_visible_id, 'origen' => 'favoritos']) }}" class="ad-btn-primary ad-btn-sm whitespace-nowrap">Ver perfil</a>
                        @endif
                    </div>
                </div>

                {{-- Pie de la tarjeta: las publicaciones asociadas a la izquierda y la acción
                     a la derecha. Van en la misma fila porque, sin la búsqueda de origen, una
                     fila propia para el botón dejaba una franja vacía sobre las publicaciones. --}}
                <div class="mt-4 flex flex-wrap items-center gap-x-2 gap-y-1.5 border-t border-line pt-3">
                    @if ($carpetasVisibles && $enCarpetas !== [])
                        <span class="text-[11.5px] font-bold uppercase tracking-[.1em] text-gray-400">En carpetas</span>
                        @foreach ($enCarpetas as $nombreCarpeta)
                            <span class="ad-chip ad-chip-sm"><flux:icon.folder class="size-3.5" />{{ $nombreCarpeta }}</span>
                        @endforeach
                    @endif

                    @if ($candidato->publicacionesAsociadas->isNotEmpty())
                        <span class="text-[11.5px] font-bold uppercase tracking-[.1em] text-gray-400">En publicaciones</span>
                        @foreach ($candidato->publicacionesAsociadas as $publicacionAsociada)
                            <a wire:navigate href="{{ route('empresa.publicaciones.show', $publicacionAsociada) }}" class="ad-chip hover:border-orange-300 hover:text-orange-600">
                                <flux:icon.megaphone class="size-3.5" />{{ $publicacionAsociada->cargo }}
                            </a>
                        @endforeach
                    @endif

                    <button
                        type="button"
                        wire:click="quitarFavorito({{ $candidato->id }})"
                        wire:loading.attr="disabled"
                        wire:target="quitarFavorito({{ $candidato->id }})"
                        class="ms-auto inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-[12.5px] font-bold text-gray-500 transition hover:bg-orange-100 hover:text-orange-700 disabled:opacity-50"
                    >
                        <flux:icon.x-mark class="size-3.5" />Quitar de favoritos
                    </button>
                </div>
            </article>
        @empty
            <div class="ad-card p-10 text-center">
                @if ($carpetaActiva && ! $hayFiltros)
                    {{-- Carpeta recién creada: se llena desde la propia lista, no desde las búsquedas. --}}
                    <flux:icon.folder-open class="mx-auto size-8 text-gray-400" />
                    <h2 class="mt-3 font-bold">La carpeta «{{ $carpetaActiva->nombre }}» está vacía</h2>
                    <p class="mt-2 text-[13px] text-gray-500">Abre «Carpetas» en cualquier candidato guardado para agruparlo aquí.</p>
                    <button type="button" wire:click="verCarpeta('todas')" class="ad-btn-primary ad-btn-sm mt-4">Ver todos mis favoritos</button>
                @else
                    <flux:icon.star class="mx-auto size-8 text-gray-400" />
                    <h2 class="mt-3 font-bold">{{ $hayFiltros ? 'Ningún favorito cumple estos filtros' : 'Aún no guardas favoritos' }}</h2>
                    <p class="mt-2 text-[13px] text-gray-500">
                        {{ $hayFiltros
                            ? 'Prueba ampliando los filtros para ver más candidatos.'
                            : 'Marca candidatos con la estrella desde los resultados de una búsqueda y aparecerán aquí.' }}
                    </p>
                    @if ($hayFiltros)
                        <button type="button" wire:click="limpiarFiltros" class="ad-btn-ghost ad-btn-sm mt-4">Limpiar filtros</button>
                    @else
                        <a wire:navigate href="{{ route('empresa.busquedas.index') }}" class="ad-btn-primary ad-btn-sm mt-4">Ir a Prospección de Candidatos</a>
                    @endif
                @endif
            </div>
        @endforelse
    </div>

    @if ($candidatos->hasPages())
        <div class="mt-6">{{ $candidatos->links() }}</div>
    @endif

    <x-asociar-publicaciones-modal :publicaciones="$publicacionesAsociables" :asociadas="$publicacionesDelCandidato" />
    @if ($carpetasVisibles)
        <x-organizar-carpetas-modal :carpetas="$carpetas" :asignadas="$carpetasDelCandidato" />
    @endif
</div>
