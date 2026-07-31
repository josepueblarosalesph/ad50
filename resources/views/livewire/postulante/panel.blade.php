<div class="ad-panel">

<x-slot:context>Postulante</x-slot:context>

<x-slot:nav><x-nav-postulante activo="panel" /></x-slot:nav>

{{-- ====== Header ====== --}}
<div class="flex items-start justify-between gap-5 mb-6 flex-wrap">
    <div>
        <h1 class="text-[27px] font-extrabold tracking-[-0.02em]">Hola, {{ Str::of(auth()->user()->name)->before(' ') }}</h1>
        <p class="text-[14px] text-gray-500 mt-1.5">Así se ve tu presencia en AD+50</p>
    </div>
    @php($completitud = $postulante?->completitud ?? 0)
    <div class="flex flex-wrap items-center gap-3">
        {{-- La completitud solo se muestra mientras haya algo que completar: con el perfil
             al 100% la barra no aporta y ocupaba una tarjeta entera. --}}
        @if ($completitud < 100)
            <a href="{{ route('postulante.ficha') }}" class="ad-toggle-row gap-2.5 py-2 transition hover:border-orange-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500" title="Completa tu perfil para llegar al 100%">
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
        <a href="{{ route('postulante.ficha') }}" class="ad-btn-primary ad-btn-sm">Editar mi perfil</a>
    </div>
</div>

{{-- ====== Oportunidades vigentes ====== --}}
<div id="oportunidades" class="ad-card">
        <div class="ad-card-head">
            <h3 class="text-[16px] font-bold">Oportunidades disponibles</h3>
            <a wire:navigate href="{{ route('postulante.busquedas') }}" class="text-[14px] font-bold text-orange-600 hover:text-orange-700">Ver más oportunidades</a>
        </div>
        <div class="divide-y divide-line px-5">
            @forelse ($publicaciones as $publicacion)
                <div wire:key="oportunidad-{{ $publicacion->id }}" class="flex flex-wrap items-center justify-between gap-3 py-3.5">
                    <div class="min-w-0">
                        <a wire:navigate href="{{ route('postulante.publicaciones.show', $publicacion) }}" class="block truncate text-[14px] font-bold text-ink underline decoration-orange-300 underline-offset-4 transition hover:text-orange-600 hover:decoration-orange-600">
                            {{ $publicacion->cargo }}
                        </a>
                        <span class="mt-0.5 block truncate text-[13px] text-gray-500">
                            {{ $publicacion->nombre_empresa }} · {{ $publicacion->comuna }} · {{ $publicacion->modalidad }}
                        </span>
                    </div>
                    @if ($publicacion->postulada_en)
                        <span class="ad-chip ad-chip-green ad-chip-sm flex-none"><flux:icon.check class="size-3.5" />Postulaste el {{ $publicacion->postulada_en->translatedFormat('d M Y') }}</span>
                    @else
                        <button type="button" wire:click="abrirPostulacion({{ $publicacion->id }})" wire:loading.attr="disabled" wire:target="abrirPostulacion({{ $publicacion->id }})" class="ad-btn-primary ad-btn-sm flex-none">Postular</button>
                    @endif
                </div>
            @empty
                <p class="py-6 text-center text-[14px] text-gray-500">Por ahora no hay ofertas vigentes.</p>
            @endforelse
        </div>
</div>

<x-postular-modal :publicacion="$publicacionSeleccionada" />

@if ($postulante?->updated_at?->lt(now()->subMonths(6)))
    <div class="mt-5 rounded-[14px] border border-orange-200 bg-orange-50 p-5"><b class="text-[14px]">¿Cambió tu trayectoria?</b><p class="mt-1 text-[13px] text-gray-700">Actualiza tu perfil profesional para seguir apareciendo en búsquedas relevantes.</p><a href="{{ route('postulante.ficha') }}" class="ad-btn-ghost ad-btn-sm mt-3">Revisar mi perfil profesional</a></div>
@endif

</div>
