<div class="ad-panel">

<x-slot:context>Postulante</x-slot:context>

<x-slot:nav><x-nav-postulante activo="panel" /></x-slot:nav>

{{-- ====== Header ====== --}}
<div class="flex items-start justify-between gap-5 mb-6 flex-wrap">
    <div>
        <h1 class="text-[27px] font-extrabold tracking-[-0.02em]">Hola, {{ Str::of(auth()->user()->name)->before(' ') }}</h1>
        <p class="text-[14px] text-gray-500 mt-1.5">Así se ve tu presencia en AD+50</p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <div class="ad-toggle-row py-2">
            <div><b class="block text-[13px]">{{ $postulante?->visible ? 'Visible para reclutadores' : 'Perfil pausado' }}</b></div>
            <flux:switch wire:click="toggleVisibilidad" :checked="$postulante?->visible ?? false" aria-label="Cambiar visibilidad del perfil" />
        </div>
        <a href="{{ route('postulante.ficha') }}" class="ad-btn-primary ad-btn-sm">Editar mi perfil</a>
    </div>
</div>

{{-- ====== Completitud del perfil ====== --}}
@php($completitud = $postulante?->completitud ?? 0)
<div class="ad-card mb-6 p-5">
    <div class="flex flex-wrap items-center gap-x-5 gap-y-4">
        <span class="grid size-11 flex-none place-items-center rounded-[12px] bg-orange-100 text-orange-600">
            <flux:icon.user class="size-5" />
        </span>

        <div class="min-w-[220px] flex-1">
            <div class="flex items-baseline justify-between gap-3">
                <span class="text-[13px] font-semibold text-gray-500">Completitud del perfil</span>
                <span class="text-[26px] font-extrabold leading-none tracking-[-0.02em]">{{ $completitud }}%</span>
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-line">
                <div class="h-full bg-gradient-to-r from-orange-500 to-[#F59A53]" style="width: {{ $completitud }}%"></div>
            </div>
            <p @class(['mt-2 text-[13px] font-semibold', 'text-match' => $completitud >= 100, 'text-gray-500' => $completitud < 100])>
                {{ $completitud >= 100 ? '¡Tu perfil está completo!' : 'Completa tu perfil para llegar a 100%' }}
            </p>
        </div>

        @if ($completitud < 100)
            <a href="{{ route('postulante.ficha') }}" class="ad-btn-ghost ad-btn-sm flex-none">Completar perfil</a>
        @endif
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
                    @if ($publicacion->postulada)
                        <span class="ad-chip ad-chip-green ad-chip-sm flex-none"><flux:icon.check class="size-3.5" />Postulaste</span>
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
