<div class="ad-panel">
    <x-slot:context>Postulante</x-slot:context>
    <x-slot:nav><x-nav-postulante activo="busquedas" /></x-slot:nav>
    <x-slot:sidebar>
        <div class="sticky top-24">
            @include('livewire.postulante.partials.filtros-oportunidades', ['prefijo' => 'escritorio'])
        </div>
    </x-slot:sidebar>

    @if (session('status'))
        <div class="mb-5 rounded-xl border border-[#BFE6CD] bg-match-100 px-4 py-3 text-[13px] font-bold text-match">{{ session('status') }}</div>
    @endif

    {{-- Encabezado: es la pantalla de entrada del postulante, así que aquí viven la
         completitud del perfil y el interruptor de visibilidad. --}}
    @php($completitud = $postulante?->completitud ?? 0)
    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-[27px] font-extrabold">Oportunidades</h1>
            <p class="mt-1.5 text-[14px] text-gray-500">
                {{ $publicaciones->total() }}
                {{ $publicaciones->total() === 1 ? 'oferta vigente' : 'ofertas vigentes' }}{{ $filtrosActivos > 0 ? ' con los filtros aplicados' : '' }}.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @if ($completitud < 100)
                <a wire:navigate href="{{ route('postulante.ficha') }}" class="ad-toggle-row gap-2.5 py-2 transition hover:border-orange-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500" title="Completa tu perfil para llegar al 100%">
                    <span class="text-[13px] font-bold text-gray-500">Perfil</span>
                    <span class="block h-1.5 w-16 flex-none overflow-hidden rounded-full bg-line">
                        <span class="block h-full bg-gradient-to-r from-orange-500 to-[#F59A53]" style="width: {{ $completitud }}%"></span>
                    </span>
                    <span class="text-[13px] font-extrabold text-ink">{{ $completitud }}%</span>
                </a>
            @endif

            <div class="ad-toggle-row py-2">
                <div><b class="block text-[13px]">{{ $postulante?->visible ? 'Visible para reclutadores' : 'Perfil pausado' }}</b></div>
                <flux:switch wire:click="toggleVisibilidad" :checked="$postulante?->visible ?? false" aria-label="Cambiar visibilidad del perfil" />
            </div>
        </div>
    </div>

    {{-- Ficha sin tocar hace medio año: recordatorio para que siga apareciendo en búsquedas. --}}
    @if ($postulante?->updated_at?->lt(now()->subMonths(6)))
        <div class="mb-5 rounded-[14px] border border-orange-200 bg-orange-50 p-5">
            <b class="text-[14px]">¿Cambió tu trayectoria?</b>
            <p class="mt-1 text-[13px] text-gray-700">Actualiza tu perfil profesional para seguir apareciendo en búsquedas relevantes.</p>
            <a wire:navigate href="{{ route('postulante.ficha') }}" class="ad-btn-ghost ad-btn-sm mt-3">Revisar mi perfil profesional</a>
        </div>
    @endif

    {{-- En móvil el mismo panel va plegado sobre el listado. --}}
    <details class="group mb-4 rounded-xl border border-line-2 bg-white dark:bg-[#222528] md:hidden">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-4 py-3 text-[14px] font-bold text-ink">
            <span class="inline-flex items-center gap-2">
                <flux:icon.funnel class="size-4 text-orange-500" />
                Filtrar ofertas
                @if ($filtrosActivos > 0)
                    <span class="grid h-[18px] min-w-[18px] place-items-center rounded-full bg-orange-100 px-1 text-[10.5px] font-bold text-orange-600">{{ $filtrosActivos }}</span>
                @endif
            </span>
            <flux:icon.chevron-down class="size-4 text-gray-400 transition group-open:rotate-180" />
        </summary>
        <div class="border-t border-line px-3 pb-1 pt-3">
            @include('livewire.postulante.partials.filtros-oportunidades', ['prefijo' => 'movil'])
        </div>
    </details>

    <div class="space-y-3">
        @forelse ($publicaciones as $publicacion)
            <article wire:key="publicacion-{{ $publicacion->id }}" class="ad-card p-4 md:p-5">
                <div class="flex flex-col justify-between gap-3 lg:flex-row lg:items-start">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="ad-chip ad-chip-sm ad-chip-orange">{{ $publicacion->modalidad }}</span>
                            @if ($publicacion->empleo_inclusivo)<span class="ad-chip ad-chip-sm ad-chip-green">Empleo inclusivo</span>@endif
                            <span class="text-[12px] text-gray-400">Publicado {{ $publicacion->created_at->diffForHumans() }}</span>
                        </div>
                        <h2 class="mt-2 text-[18px] font-extrabold">
                            <a wire:navigate href="{{ route('postulante.publicaciones.show', $publicacion) }}" class="text-ink underline decoration-orange-300 underline-offset-4 transition hover:text-orange-600 hover:decoration-orange-600">{{ $publicacion->cargo }}</a>
                        </h2>
                        <p class="mt-0.5 text-[13.5px] font-semibold text-gray-600">{{ $publicacion->nombre_empresa }} · {{ $publicacion->comuna }}, {{ $publicacion->pais }}</p>
                        <p class="mt-2 line-clamp-2 text-[13.5px] leading-relaxed text-gray-600">{{ $publicacion->descripcion }}</p>
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-[12.5px] text-gray-500">
                            <span><b class="text-ink">{{ $publicacion->tipo_cargo }}</b></span>
                            <span>{{ $publicacion->jerarquia }}</span>
                            <span>{{ $publicacion->experiencia_laboral }}</span>
                        </div>
                        @if ($publicacion->competencias)
                            {{-- Se muestran las primeras y el resto se resume, para que una oferta
                                 con muchas competencias no estire la tarjeta. --}}
                            @php($visibles = array_slice($publicacion->competencias, 0, 6))
                            @php($restantes = count($publicacion->competencias) - count($visibles))
                            <div class="mt-2.5 flex flex-wrap gap-1.5">
                                @foreach ($visibles as $competencia)<span wire:key="competencia-{{ $publicacion->id }}-{{ $loop->index }}" class="ad-chip ad-chip-sm">{{ $competencia }}</span>@endforeach
                                @if ($restantes > 0)
                                    <span class="ad-chip ad-chip-sm ad-chip-gray" title="{{ implode(' · ', array_slice($publicacion->competencias, 6)) }}">+{{ $restantes }}</span>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="flex flex-none flex-col items-stretch gap-2 lg:min-w-40 lg:items-end">
                        @if ($publicacion->postulada_en)
                            <span class="ad-chip ad-chip-green ad-chip-sm justify-center"><flux:icon.check class="size-4" />Postulación enviada</span>
                            <span class="text-[12px] text-gray-500 lg:text-right">Postulaste el {{ $publicacion->postulada_en->translatedFormat('d M Y') }}</span>
                        @else
                            <button type="button" wire:click="abrirPostulacion({{ $publicacion->id }})" class="ad-btn-primary ad-btn-sm justify-center">Postular</button>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="ad-card p-10 text-center">
                <flux:icon.magnifying-glass class="mx-auto size-8 text-gray-400" />
                <h2 class="mt-3 font-bold">No encontramos publicaciones</h2>
                <p class="mt-2 text-[13px] text-gray-500">Prueba quitando algunos filtros o vuelve más adelante.</p>
            </div>
        @endforelse
    </div>

    @if ($publicaciones->hasPages())
        <div class="mt-6">{{ $publicaciones->links() }}</div>
    @endif

    <x-postular-modal :publicacion="$publicacionSeleccionada" />
</div>
