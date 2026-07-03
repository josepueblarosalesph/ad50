<div>
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:nav>
        <a href="{{ route('empresa.panel') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Panel</a>
        <a wire:navigate href="{{ route('empresa.busquedas.index') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-ink bg-orange-100">Búsquedas</a>
        <a wire:navigate href="{{ route('empresa.busquedas.create') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Nueva búsqueda</a>
    </x-slot:nav>
    <x-slot:sidebar>
        <div class="sticky top-24 max-h-[calc(100vh-7rem)] overflow-y-auto"><livewire:empresa.filtros-busqueda :busqueda="$busqueda" /></div>
    </x-slot:sidebar>

    <div class="mb-6"><h1 class="text-[25px] font-extrabold">{{ $busqueda->titulo }}</h1><p class="mt-1.5 text-[14px] text-gray-500">Candidatos ordenados por nivel de coincidencia.</p></div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="inline-flex rounded-xl border border-line-2 bg-white p-1 transition-colors dark:bg-[#222528]" aria-label="Filtrar candidatos">
            <button type="button" wire:click="mostrar('todos')" @class(['rounded-lg px-4 py-2 text-[13px] font-bold transition', 'bg-ink text-white dark:bg-orange-600 dark:text-white' => $filtro === 'todos', 'text-gray-500 hover:text-ink dark:hover:bg-white/5' => $filtro !== 'todos'])>Todos <span class="ml-1 opacity-70">{{ $totalCandidatos }}</span></button>
            <button type="button" wire:click="mostrar('favoritos')" @class(['rounded-lg px-4 py-2 text-[13px] font-bold transition', 'bg-orange-600 text-white' => $filtro === 'favoritos', 'text-gray-500 hover:text-orange-600' => $filtro !== 'favoritos'])><flux:icon.star class="inline size-4" /> Favoritos <span class="ml-1 opacity-70">{{ $totalFavoritos }}</span></button>
        </div>
        <p class="text-[13px] text-gray-500">@if ($criterios !== [])Mostrando {{ $candidatos->total() }} que cumplen los filtros seleccionados.@else Marca perfiles para construir tu selección sin salir del listado.@endif</p>
    </div>

    <div class="space-y-3">
        @forelse ($candidatos as $match)
            <article wire:key="candidato-{{ $match->id }}" class="ad-card relative overflow-hidden p-4 md:p-5">
                <div class="absolute inset-y-0 left-0 w-1 bg-orange-500"></div>
                <div class="grid items-stretch gap-4 md:grid-cols-[minmax(0,1fr)_auto] md:gap-0">
                    <div class="flex min-w-0 items-center gap-4 md:pr-6">
                        <div class="grid size-12 flex-none place-items-center rounded-[12px] bg-sage-100 text-ink" aria-hidden="true"><flux:icon.user class="size-5" /></div>
                        <div class="min-w-0">
                            <p class="truncate text-[11px] font-extrabold uppercase tracking-[.14em] text-gray-400">{{ $match->postulante->carrera ?: 'Carrera no informada' }}</p>
                            <h2 class="mt-0.5 truncate text-[20px] font-extrabold text-ink">{{ $match->postulante->user->name }}</h2>
                            <p class="mt-2 max-w-4xl text-[13px] leading-relaxed text-gray-500">{{ Str::limit($match->postulante->resumen_profesional ?: 'Sin descripción profesional disponible.', 100, '…') }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 border-t border-line pt-3 md:min-w-44 md:flex-col md:items-end md:justify-center md:border-l md:border-t-0 md:pl-4 md:pt-0">
                        <span @class(['ad-chip whitespace-nowrap', 'ad-chip-green' => $match->estado_match === 'cumple'])><flux:icon :name="$match->estado_match === 'cumple' ? 'check' : 'minus'" class="size-4" />{{ $match->estado_match === 'cumple' ? 'Cumple' : 'Parcial — cumple' }} {{ $match->criterios_cumplidos }} de {{ $match->criterios_totales }}</span>
                        <div class="flex items-center gap-2">
                            <flux:tooltip :content="$match->favorito ? 'Quitar de favoritos' : 'Guardar como favorito'">
                                <button type="button" wire:click="toggleFavorito({{ $match->id }})" wire:loading.attr="disabled" wire:target="toggleFavorito({{ $match->id }})" @class(['grid size-10 flex-none place-items-center rounded-xl border transition disabled:opacity-50', 'border-orange-300 bg-orange-100 text-orange-600' => $match->favorito, 'border-line-2 bg-white text-gray-400 hover:border-orange-300 hover:text-orange-600 dark:bg-[#2A2D30] dark:hover:bg-orange-100' => ! $match->favorito]) aria-label="{{ $match->favorito ? 'Quitar candidato de favoritos' : 'Guardar candidato como favorito' }}" aria-pressed="{{ $match->favorito ? 'true' : 'false' }}"><flux:icon.star variant="solid" class="size-5" /></button>
                            </flux:tooltip>
                            <a wire:navigate href="{{ route('empresa.candidatos.show', ['match' => $match, 'filtro' => $filtro, 'criterios' => $criterios]) }}" class="ad-btn-primary ad-btn-sm whitespace-nowrap">Ver ficha</a>
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="ad-card p-10 text-center"><flux:icon.magnifying-glass class="size-8 text-gray-400 mx-auto" /><h2 class="font-bold mt-3">{{ $criterios !== [] ? 'Ningún candidato cumple esta combinación' : 'Aún no hay coincidencias' }}</h2><p class="text-[13px] text-gray-500 mt-2">{{ $criterios !== [] ? 'Quita uno de los filtros para ampliar los resultados.' : 'Prueba ampliando los criterios de búsqueda.' }}</p>@if ($criterios !== [])<button type="button" wire:click="limpiarCriterios" class="ad-btn-ghost ad-btn-sm mt-4">Limpiar filtros</button>@endif</div>
        @endforelse
    </div>

    @if ($candidatos->hasPages())
        <div class="mt-6">{{ $candidatos->links() }}</div>
    @endif
</div>
