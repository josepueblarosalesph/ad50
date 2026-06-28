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

    <div class="rounded-[14px] bg-ink text-white p-4 md:px-5 mb-5 flex flex-wrap items-center gap-3"><b class="text-[15px]">Encontramos <span class="text-orange-500">{{ $candidatos->count() }} candidatos</span></b><div class="flex flex-wrap gap-2 ml-auto">@foreach (array_filter($busqueda->criterios ?? []) as $key => $value)<span class="ad-chip border-white/15 bg-white/10 text-white">{{ str_replace('_', ' ', ucfirst($key)) }}: {{ $value }}</span>@endforeach</div></div>

    <div class="space-y-3">
        @forelse ($candidatos as $match)
            <article class="ad-card p-5 grid md:grid-cols-[auto_1fr_auto] gap-5 items-center">
                <div class="size-14 rounded-full bg-orange-100 text-orange-600 grid place-items-center font-extrabold text-lg">{{ $match->postulante->user->initials() }}</div>
                <div><h2 class="text-[16px] font-bold">{{ $match->postulante->user->initials() }} · perfil #{{ $match->postulante->id }}</h2><p class="text-[13.5px] text-gray-700 font-semibold mt-1">{{ $match->postulante->cargo_actual ?: 'Perfil profesional' }}</p><div class="flex flex-wrap gap-2 mt-3"><span class="ad-chip">{{ $match->postulante->industria ?: 'Industria abierta' }}</span><span class="ad-chip">{{ $match->postulante->anios_experiencia }} años</span><span class="ad-chip">{{ $match->postulante->ciudad ?: 'Chile' }}</span></div></div>
                <div class="md:text-right"><span class="ad-chip ad-chip-green"><flux:icon.check class="size-4" />Cumple {{ $match->criterios_cumplidos }} de {{ $match->criterios_totales }}</span><p class="text-[11.5px] text-match mt-2">Contacto disponible</p><a href="{{ route('empresa.candidatos.show', $match) }}" class="ad-btn-primary ad-btn-sm mt-3">Ver ficha</a></div>
            </article>
        @empty
            <div class="ad-card p-10 text-center"><flux:icon.magnifying-glass class="size-8 text-gray-400 mx-auto" /><h2 class="font-bold mt-3">Aún no hay coincidencias</h2><p class="text-[13px] text-gray-500 mt-2">Prueba ampliando los criterios de búsqueda.</p></div>
        @endforelse
    </div>
</div>
