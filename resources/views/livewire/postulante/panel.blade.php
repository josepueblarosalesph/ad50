<div class="ad-panel">

<x-slot:context>Postulante</x-slot:context>

<x-slot:nav>
    @php($nav = [
        ['label' => 'Mi panel', 'href' => route('postulante.panel'), 'active' => true],
        ['label' => 'Mi perfil', 'href' => route('postulante.ficha')],
        ['label' => 'Búsquedas que me incluyen', 'href' => route('postulante.busquedas')],
    ])
    @foreach ($nav as $item)
        <a href="{{ $item['href'] }}"
           @class([
               'text-[13.5px] font-semibold px-3.5 py-2 rounded-lg',
               'text-ink bg-orange-100' => $item['active'] ?? false,
               'text-gray-500 hover:text-ink' => !($item['active'] ?? false),
           ])>{{ $item['label'] }}</a>
    @endforeach
</x-slot:nav>

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

{{-- ====== Stats ====== --}}
<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <div class="ad-card p-5">
        <div class="flex items-center">
            <span class="text-[13px] text-gray-500 font-semibold">Completitud del perfil</span>
            <span class="ml-auto size-8 rounded-[9px] bg-orange-100 text-orange-600 grid place-items-center">
                <flux:icon.user class="size-4" />
            </span>
        </div>
        <div class="text-[30px] font-extrabold tracking-[-0.02em] mt-2">{{ $postulante?->completitud ?? 0 }}%</div>
        <div class="h-2 rounded-full bg-line overflow-hidden mt-2">
            <div class="h-full bg-gradient-to-r from-orange-500 to-[#F59A53]" style="width: {{ $postulante?->completitud ?? 0 }}%"></div>
        </div>
        @if (($postulante?->completitud ?? 0) >= 100)
            <div class="mt-2 text-[13px] font-semibold text-match">¡Tu perfil está completo!</div>
        @else
            <div class="mt-2 text-[13px] font-semibold text-match">Completa tu perfil para llegar a 100%</div>
        @endif
    </div>

    <div class="ad-card p-5">
        <div class="flex items-center">
            <span class="text-[13px] text-gray-500 font-semibold">Búsquedas que me incluyen</span>
            <span class="ml-auto size-8 rounded-[9px] bg-orange-100 text-orange-600 grid place-items-center">
                <flux:icon.check class="size-4" />
            </span>
        </div>
        <div class="text-[30px] font-extrabold tracking-[-0.02em] mt-2">{{ $totalMatches }}</div>
        <div class="mt-1 text-[13px] font-semibold text-match">+2 este mes</div>
    </div>

    <div class="ad-card p-5">
        <div class="flex items-center">
            <span class="text-[13px] text-gray-500 font-semibold">Interés en tu perfil</span>
            <span class="ml-auto size-8 rounded-[9px] bg-orange-100 text-orange-600 grid place-items-center">
                <flux:icon.star variant="solid" class="size-4" />
            </span>
        </div>
        <div class="text-[30px] font-extrabold tracking-[-0.02em] mt-2">{{ $empresasInteresadas }}</div>
        <div class="mt-1 text-[13px] font-semibold text-match">Te han visto {{ $empresasInteresadas }} {{ $empresasInteresadas === 1 ? 'empresa' : 'empresas' }}</div>
    </div>

    <div class="ad-card p-5">
        <div class="flex items-center">
            <span class="text-[13px] text-gray-500 font-semibold">Activación del perfil</span>
            <span class="ml-auto size-8 rounded-[9px] bg-orange-100 text-orange-600 grid place-items-center">
                <flux:icon.check-circle class="size-4" />
            </span>
        </div>
        <div class="text-[22px] font-extrabold tracking-[-0.02em] mt-3">Pago único</div>
        <div class="mt-1 text-[13px] font-semibold text-match">Sin renovaciones ni cobros adicionales</div>
    </div>
</div>

{{-- ====== Coincidencias recientes ====== --}}
<div id="coincidencias" class="ad-card">
        <div class="ad-card-head">
            <h3 class="text-[16px] font-bold">Búsquedas que me incluyen</h3>
            <div class="flex items-center gap-3"><span class="ad-chip ad-chip-green ad-chip-dot">{{ $totalMatches }} coincidencias</span><a wire:navigate href="{{ route('postulante.busquedas') }}" class="text-[14px] font-bold text-orange-600 hover:text-orange-700">Ver más</a></div>
        </div>
        <div class="p-5 pt-2 space-y-2.5">
            @forelse ($matches as $m)
                <div class="ad-toggle-row">
                    <div>
                        <b class="text-[13.5px] block">{{ $m->busqueda->titulo }}</b>
                        <span class="text-[13px] text-gray-500">
                            Cumples {{ $m->criterios_cumplidos }} de {{ $m->criterios_totales }} criterios · {{ $m->created_at->diffForHumans() }}
                        </span>
                    </div>
                    @if ($m->estado_match !== 'cumple')
                        <span class="ad-chip">Parcial</span>
                    @endif
                </div>
            @empty
                <p class="py-6 text-center text-[14px] text-gray-500">Aún no apareces en búsquedas.</p>
            @endforelse

            <div class="mt-3 flex items-start gap-2 text-[13px] text-gray-500">
                <flux:icon.lock-closed class="size-3.5 text-gray-400 flex-none mt-0.5" />
                <span>Por privacidad, el nombre de la empresa se muestra solo como rubro hasta que te contactan.</span>
            </div>
        </div>
</div>

@if ($postulante?->updated_at?->lt(now()->subMonths(6)))
    <div class="mt-5 rounded-[14px] border border-orange-200 bg-orange-50 p-5"><b class="text-[14px]">¿Cambió tu trayectoria?</b><p class="mt-1 text-[13px] text-gray-700">Actualiza tu perfil profesional para seguir apareciendo en búsquedas relevantes.</p><a href="{{ route('postulante.ficha') }}" class="ad-btn-ghost ad-btn-sm mt-3">Revisar mi perfil profesional</a></div>
@endif

</div>
