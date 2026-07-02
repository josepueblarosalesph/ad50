<div>
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:nav>
        <a href="{{ route('empresa.panel') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Panel</a>
        <a href="{{ route('empresa.busquedas.create') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-ink bg-orange-100">Búsquedas</a>
        <a href="{{ route('planes') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Mi plan</a>
    </x-slot:nav>
    <x-slot:sidebar>
        <div class="text-[10.5px] tracking-[0.12em] uppercase text-gray-400 font-bold px-2.5 mb-2">Búsqueda actual</div>
        <a href="{{ route('empresa.resultados', $busqueda) }}" class="flex items-center gap-3 text-[14px] font-semibold px-3 py-2.5 rounded-[10px] bg-orange-100 text-orange-600"><flux:icon.bars-3 class="size-[18px]" />Resultados</a>
        <a href="{{ route('empresa.busquedas.create') }}" class="flex items-center gap-3 text-[14px] font-semibold px-3 py-2.5 rounded-[10px] text-gray-700 hover:bg-paper"><flux:icon.cog-6-tooth class="size-[18px]" />Ajustar criterios</a>
    </x-slot:sidebar>

    <div class="flex items-start justify-between gap-5 mb-6 flex-wrap"><div><h1 class="text-[25px] font-extrabold">{{ $busqueda->titulo }}</h1><p class="text-[14px] text-gray-500 mt-1.5">Candidatos ordenados por nivel de coincidencia.</p></div><a href="{{ route('empresa.busquedas.create') }}" class="ad-btn-ghost ad-btn-sm">Ajustar criterios</a></div>

    <div class="rounded-[16px] bg-ink p-4 text-white md:px-5 mb-5 flex flex-wrap items-center gap-3"><b class="text-[15px]">Encontramos <span class="text-orange-500">{{ $totalCandidatos }} candidatos</span></b><div class="flex flex-wrap gap-2 ml-auto">@foreach (array_filter($busqueda->criterios ?? []) as $key => $value)<span class="ad-chip border-white/15 bg-white/10 text-white">{{ str_replace('_', ' ', ucfirst($key)) }}: {{ $value }}</span>@endforeach</div></div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="inline-flex rounded-xl border border-line-2 bg-white p-1" aria-label="Filtrar candidatos">
            <button type="button" wire:click="mostrar('todos')" @class(['rounded-lg px-4 py-2 text-[13px] font-bold transition', 'bg-ink text-white' => $filtro === 'todos', 'text-gray-500 hover:text-ink' => $filtro !== 'todos'])>Todos <span class="ml-1 opacity-70">{{ $totalCandidatos }}</span></button>
            <button type="button" wire:click="mostrar('favoritos')" @class(['rounded-lg px-4 py-2 text-[13px] font-bold transition', 'bg-orange-600 text-white' => $filtro === 'favoritos', 'text-gray-500 hover:text-orange-600' => $filtro !== 'favoritos'])><flux:icon.star class="inline size-4" /> Favoritos <span class="ml-1 opacity-70">{{ $totalFavoritos }}</span></button>
        </div>
        <p class="text-[13px] text-gray-500">Marca perfiles para construir tu selección sin salir del listado.</p>
    </div>

    <div class="space-y-3">
        @forelse ($candidatos as $match)
            <article wire:key="candidato-{{ $match->id }}" class="ad-card relative overflow-hidden p-5 md:p-6">
                <div class="absolute inset-y-0 left-0 w-1 bg-orange-500"></div>
                <div class="grid items-center gap-5 md:grid-cols-[auto_1fr_auto]">
                    <div class="grid size-14 place-items-center rounded-[13px] bg-sage-100 text-lg font-extrabold text-ink" aria-hidden="true"><flux:icon.user class="size-6" /></div>
                    <div class="min-w-0">
                        <p class="text-[11px] font-extrabold uppercase tracking-[.14em] text-gray-400">Perfil profesional #{{ $match->postulante->id }}</p>
                        <h2 class="mt-1 text-[22px]">{{ $match->postulante->cargo_actual ?: 'Perfil profesional' }}</h2>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($match->criterios_detalle ?? [] as $detalle)
                                <span @class(['ad-chip', 'ad-chip-green' => $detalle['cumple'], 'text-gray-700' => ! $detalle['cumple']])>
                                    <flux:icon :name="$detalle['cumple'] ? 'check' : 'x-mark'" class="size-4" />
                                    {{ $detalle['criterio'] }}: {{ $detalle['valor'] }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    <div class="border-t border-line pt-4 md:border-l md:border-t-0 md:pl-6 md:pt-0 md:text-right">
                        <span @class(['ad-chip', 'ad-chip-green' => $match->estado_match === 'cumple'])><flux:icon :name="$match->estado_match === 'cumple' ? 'check' : 'minus'" class="size-4" />{{ $match->estado_match === 'cumple' ? 'Cumple' : 'Parcial — cumple' }} {{ $match->criterios_cumplidos }} de {{ $match->criterios_totales }}</span>
                        <p class="mt-2 text-[13px] font-semibold text-match">Contacto disponible</p>
                        <div class="mt-3 flex items-center gap-2 md:justify-end">
                            <flux:tooltip :content="$match->favorito ? 'Quitar de favoritos' : 'Guardar como favorito'">
                                <button type="button" wire:click="toggleFavorito({{ $match->id }})" wire:loading.attr="disabled" wire:target="toggleFavorito({{ $match->id }})" @class(['grid size-10 place-items-center rounded-xl border transition disabled:opacity-50', 'border-orange-300 bg-orange-100 text-orange-600' => $match->favorito, 'border-line-2 bg-white text-gray-400 hover:border-orange-300 hover:text-orange-600' => ! $match->favorito]) aria-label="{{ $match->favorito ? 'Quitar candidato de favoritos' : 'Guardar candidato como favorito' }}" aria-pressed="{{ $match->favorito ? 'true' : 'false' }}"><flux:icon.star variant="solid" class="size-5" /></button>
                            </flux:tooltip>
                            <a wire:navigate href="{{ route('empresa.candidatos.show', ['match' => $match, 'filtro' => $filtro]) }}" class="ad-btn-primary ad-btn-sm">Ver ficha</a>
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="ad-card p-10 text-center"><flux:icon.magnifying-glass class="size-8 text-gray-400 mx-auto" /><h2 class="font-bold mt-3">Aún no hay coincidencias</h2><p class="text-[13px] text-gray-500 mt-2">Prueba ampliando los criterios de búsqueda.</p></div>
        @endforelse
    </div>

    @if ($candidatos->hasPages())
        <div class="mt-6">{{ $candidatos->links() }}</div>
    @endif
</div>
