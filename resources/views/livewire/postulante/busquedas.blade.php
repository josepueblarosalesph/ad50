<div class="ad-panel">
    <x-slot:context>Postulante</x-slot:context>

    <x-slot:nav>
        <a wire:navigate href="{{ route('postulante.panel') }}" class="rounded-lg px-3.5 py-2 text-[13.5px] font-semibold text-gray-500 hover:text-ink">Mi panel</a>
        <a wire:navigate href="{{ route('postulante.ficha') }}" class="rounded-lg px-3.5 py-2 text-[13.5px] font-semibold text-gray-500 hover:text-ink">Mi ficha</a>
        <a wire:navigate href="{{ route('postulante.busquedas') }}" class="rounded-lg bg-orange-100 px-3.5 py-2 text-[13.5px] font-semibold text-ink">Búsquedas que me incluyen</a>
    </x-slot:nav>

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a wire:navigate href="{{ route('postulante.panel') }}" class="mb-4 inline-flex items-center gap-2 text-[13px] font-bold text-gray-500 hover:text-ink"><flux:icon.arrow-left class="size-4" />Volver a mi panel</a>
            <h1 class="text-[27px] font-extrabold tracking-[-0.02em]">Búsquedas que me incluyen</h1>
            <p class="mt-1.5 text-[14px] text-gray-500">Procesos donde tu experiencia coincide con los criterios definidos.</p>
        </div>
        <span class="ad-chip ad-chip-green ad-chip-dot">{{ $matches->total() }} coincidencias</span>
    </div>

    <div class="space-y-3">
        @forelse ($matches as $match)
            <article wire:key="busqueda-{{ $match->id }}" class="ad-card p-5 md:p-6">
                <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div class="min-w-0">
                        <p class="text-[11px] font-extrabold uppercase tracking-[.14em] text-gray-400">Oportunidad profesional</p>
                        <h2 class="mt-1 text-[20px] font-bold text-ink">{{ $match->busqueda->titulo }}</h2>
                        <p class="mt-2 text-[13px] text-gray-500">Coincidencia detectada {{ $match->created_at->diffForHumans() }}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($match->criterios_detalle ?? [] as $detalle)
                                @if ($detalle['cumple'] ?? false)
                                    <span class="ad-chip ad-chip-green"><flux:icon.check class="size-4" />{{ $detalle['criterio'] }}: {{ $detalle['valor'] }}</span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    <span class="ad-chip ad-chip-green self-start md:self-auto"><flux:icon.check class="size-4" />Cumples {{ $match->criterios_cumplidos }} de {{ $match->criterios_totales }}</span>
                </div>
            </article>
        @empty
            <div class="ad-card p-10 text-center">
                <flux:icon.magnifying-glass class="mx-auto size-8 text-gray-400" />
                <h2 class="mt-3 font-bold">Aún no apareces en búsquedas</h2>
                <p class="mt-2 text-[13px] text-gray-500">Mantén tu ficha completa y visible para aparecer en nuevas coincidencias.</p>
            </div>
        @endforelse
    </div>

    @if ($matches->hasPages())
        <div class="mt-6">{{ $matches->links() }}</div>
    @endif

    <div class="mt-6 flex items-start gap-2 rounded-xl border border-line-2 bg-white p-4 text-[13px] text-gray-500 transition-colors dark:bg-[#222528]">
        <flux:icon.lock-closed class="mt-0.5 size-4 flex-none text-gray-400" />
        <span>Por privacidad, no mostramos la identidad de la empresa mientras el contacto no haya sido habilitado.</span>
    </div>
</div>
