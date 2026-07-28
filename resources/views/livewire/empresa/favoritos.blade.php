<div class="ad-panel">
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:nav><x-nav-empresa activo="favoritos" /></x-slot:nav>

    <div class="mb-6 flex flex-wrap items-start justify-between gap-5">
        <div>
            <span class="ad-eyebrow">Talent Finder</span>
            <h1 class="mt-3 text-[30px] font-extrabold">Mis favoritos</h1>
            <p class="mt-2 text-[14px] text-gray-500">
                {{ $totalFavoritos }} {{ $totalFavoritos === 1 ? 'candidato guardado' : 'candidatos guardados' }}
                en todas tus búsquedas.
            </p>
        </div>
    </div>

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
            @php($favoritos = $candidato->matches)
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
                        @if ($desbloqueado)
                            <span class="ad-chip ad-chip-green"><flux:icon.lock-open class="size-3.5" /> Desbloqueado</span>
                        @else
                            <span class="ad-chip"><flux:icon.lock-closed class="size-3.5" /> Sin desbloquear</span>
                        @endif

                        <flux:tooltip :content="$candidato->publicacionesAsociadas->isNotEmpty() ? 'Asociado a '.$candidato->publicacionesAsociadas->count().' publicación(es)' : 'Asociar a una publicación'">
                            <button type="button" wire:click="abrirAsociacion({{ $candidato->id }})" wire:loading.attr="disabled" wire:target="abrirAsociacion({{ $candidato->id }})" @class([
                                'grid size-10 flex-none place-items-center rounded-xl border transition disabled:opacity-50',
                                'border-orange-300 bg-orange-100 text-orange-600' => $candidato->publicacionesAsociadas->isNotEmpty(),
                                'border-line-2 bg-white text-gray-400 hover:border-orange-300 hover:text-orange-600 dark:bg-[#2A2D30]' => $candidato->publicacionesAsociadas->isEmpty(),
                            ]) aria-label="Asociar candidato a una publicación">
                                <flux:icon.megaphone class="size-5" />
                            </button>
                        </flux:tooltip>

                        @if ($favoritos->isNotEmpty())
                            <a wire:navigate href="{{ route('empresa.candidatos.show', ['match' => $favoritos->first()->id]) }}" class="ad-btn-primary ad-btn-sm whitespace-nowrap">Ver perfil</a>
                        @endif
                    </div>
                </div>

                {{-- Búsquedas donde está marcado: cada chip permite quitar el favorito ahí. --}}
                <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-line pt-3">
                    <span class="text-[11.5px] font-bold uppercase tracking-[.1em] text-gray-400">Favorito en</span>
                    @foreach ($favoritos as $match)
                        <span wire:key="fav-{{ $candidato->id }}-{{ $match->busqueda_id }}" class="inline-flex items-center gap-1.5 rounded-full border border-orange-300 bg-orange-100 py-1 pl-3 pr-1.5 text-[12.5px] font-bold text-orange-700">
                            {{ $match->busqueda?->titulo ?? 'Búsqueda eliminada' }}
                            <button
                                type="button"
                                wire:click="quitarFavorito({{ $match->busqueda_id }}, {{ $candidato->id }})"
                                wire:loading.attr="disabled"
                                wire:target="quitarFavorito({{ $match->busqueda_id }}, {{ $candidato->id }})"
                                class="grid size-5 place-items-center rounded-full text-orange-600 transition hover:bg-orange-200 hover:text-orange-800 disabled:opacity-50"
                                aria-label="Quitar de favoritos en {{ $match->busqueda?->titulo }}"
                            >
                                <flux:icon.x-mark class="size-3.5" />
                            </button>
                        </span>
                    @endforeach
                </div>

                @if ($candidato->publicacionesAsociadas->isNotEmpty())
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="text-[11.5px] font-bold uppercase tracking-[.1em] text-gray-400">En publicaciones</span>
                        @foreach ($candidato->publicacionesAsociadas as $publicacionAsociada)
                            <a wire:navigate href="{{ route('empresa.publicaciones.show', $publicacionAsociada) }}" class="ad-chip hover:border-orange-300 hover:text-orange-600">
                                <flux:icon.megaphone class="size-3.5" />{{ $publicacionAsociada->cargo }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </article>
        @empty
            <div class="ad-card p-10 text-center">
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
                    <a wire:navigate href="{{ route('empresa.busquedas.index') }}" class="ad-btn-primary ad-btn-sm mt-4">Ir a Talent Finder</a>
                @endif
            </div>
        @endforelse
    </div>

    @if ($candidatos->hasPages())
        <div class="mt-6">{{ $candidatos->links() }}</div>
    @endif

    <x-asociar-publicaciones-modal :publicaciones="$publicacionesAsociables" :asociadas="$publicacionesDelCandidato" />
</div>
