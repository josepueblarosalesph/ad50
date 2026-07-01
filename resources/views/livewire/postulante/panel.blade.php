<div>

<x-slot:context>Postulante</x-slot:context>

<x-slot:nav>
    @php($nav = [
        ['label' => 'Mi panel', 'href' => route('postulante.panel'), 'active' => true],
        ['label' => 'Mi ficha', 'href' => route('postulante.ficha')],
        ['label' => 'Búsquedas que me incluyen', 'href' => '#coincidencias'],
        ['label' => 'Mi activación', 'href' => route('planes')],
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

<x-slot:sidebar>
    <div class="text-[10.5px] tracking-[0.12em] uppercase text-gray-400 font-bold px-2.5 mb-2">Mi cuenta</div>
    <a href="#" class="flex items-center gap-3 text-[14px] font-semibold px-3 py-2.5 rounded-[10px] bg-orange-100 text-orange-600">
        <flux:icon.squares-2x2 class="size-[18px]" /> Mi panel
    </a>
    <a href="{{ route('postulante.ficha') }}" class="flex items-center gap-3 text-[14px] font-semibold px-3 py-2.5 rounded-[10px] text-gray-700 hover:bg-paper hover:text-ink">
        <flux:icon.user class="size-[18px] text-gray-400" /> Mi ficha
    </a>
    <a href="#coincidencias" class="flex items-center gap-3 text-[14px] font-semibold px-3 py-2.5 rounded-[10px] text-gray-700 hover:bg-paper hover:text-ink">
        <flux:icon.bars-3 class="size-[18px] text-gray-400" /> Búsquedas que me incluyen
    </a>
    <div class="text-[10.5px] tracking-[0.12em] uppercase text-gray-400 font-bold px-2.5 mt-5 mb-2">Cuenta</div>
    <a href="{{ route('planes') }}" class="flex items-center gap-3 text-[14px] font-semibold px-3 py-2.5 rounded-[10px] text-gray-700 hover:bg-paper hover:text-ink">
        <flux:icon.credit-card class="size-[18px] text-gray-400" /> Mi activación
    </a>
</x-slot:sidebar>

{{-- ====== Header ====== --}}
<div class="flex items-start justify-between gap-5 mb-6 flex-wrap">
    <div>
        <h1 class="text-[27px] font-extrabold tracking-[-0.02em]">Hola, {{ Str::of(auth()->user()->name)->before(' ') }}</h1>
        <p class="text-[14px] text-gray-500 mt-1.5">Así se ve tu presencia en AD+50</p>
    </div>
    <a href="{{ route('postulante.ficha') }}" class="ad-btn-primary ad-btn-sm">Editar mi ficha</a>
</div>

{{-- ====== Stats ====== --}}
<div class="grid md:grid-cols-3 gap-4 mb-6">
    <div class="ad-card p-5">
        <div class="flex items-center">
            <span class="text-[12px] text-gray-500 font-semibold">Completitud de ficha</span>
            <span class="ml-auto size-8 rounded-[9px] bg-orange-100 text-orange-600 grid place-items-center">
                <flux:icon.user class="size-4" />
            </span>
        </div>
        <div class="text-[30px] font-extrabold tracking-[-0.02em] mt-2">{{ $postulante?->completitud ?? 0 }}%</div>
        <div class="h-2 rounded-full bg-line overflow-hidden mt-2">
            <div class="h-full bg-gradient-to-r from-orange-500 to-[#F59A53]" style="width: {{ $postulante?->completitud ?? 0 }}%"></div>
        </div>
        <div class="text-[11.5px] font-semibold text-match mt-2">Completa experiencia 2 para llegar a 100%</div>
    </div>

    <div class="ad-card p-5">
        <div class="flex items-center">
            <span class="text-[12px] text-gray-500 font-semibold">Búsquedas que me incluyen</span>
            <span class="ml-auto size-8 rounded-[9px] bg-orange-100 text-orange-600 grid place-items-center">
                <flux:icon.check class="size-4" />
            </span>
        </div>
        <div class="text-[30px] font-extrabold tracking-[-0.02em] mt-2">{{ $totalMatches }}</div>
        <div class="text-[11.5px] font-semibold text-match mt-1">+2 este mes</div>
    </div>

    <div class="ad-card p-5">
        <div class="flex items-center">
            <span class="text-[12px] text-gray-500 font-semibold">Activación de ficha</span>
            <span class="ml-auto size-8 rounded-[9px] bg-orange-100 text-orange-600 grid place-items-center">
                <flux:icon.check-circle class="size-4" />
            </span>
        </div>
        <div class="text-[22px] font-extrabold tracking-[-0.02em] mt-3">Pago único</div>
        <div class="text-[11.5px] font-semibold text-match mt-1">Sin renovaciones ni cobros adicionales</div>
    </div>
</div>

{{-- ====== Dos columnas ====== --}}
<div class="grid lg:grid-cols-2 gap-5 items-start">

    {{-- Matches --}}
    <div id="coincidencias" class="ad-card">
        <div class="ad-card-head">
            <h3 class="text-[16px] font-bold">Búsquedas que me incluyen</h3>
            <span class="ad-chip ad-chip-green ad-chip-dot">{{ $totalMatches }} coincidencias</span>
        </div>
        <div class="p-5 pt-2 space-y-2.5">
            @forelse ($matches as $m)
                <div class="ad-toggle-row">
                    <div>
                        <b class="text-[13.5px] block">{{ $m->busqueda->titulo }}</b>
                        <span class="text-[12px] text-gray-500">
                            Cumples {{ $m->criterios_cumplidos }} de {{ $m->criterios_totales }} criterios · {{ $m->created_at->diffForHumans() }}
                        </span>
                    </div>
                    @if ($m->estado_match === 'cumple')
                        <span class="ad-chip ad-chip-green">Cumple</span>
                    @else
                        <span class="ad-chip">Parcial</span>
                    @endif
                </div>
            @empty
                <p class="text-[13px] text-gray-500 text-center py-6">Aún no apareces en búsquedas.</p>
            @endforelse

            <div class="text-[11.5px] text-gray-500 mt-3 flex gap-2 items-start">
                <flux:icon.lock-closed class="size-3.5 text-gray-400 flex-none mt-0.5" />
                <span>Por privacidad, el nombre de la empresa se muestra solo como rubro hasta que te contactan.</span>
            </div>
        </div>
    </div>

    {{-- Lateral suscripción + acciones --}}
    <div>
        @if ($postulante?->updated_at?->lt(now()->subMonths(6)))
            <div class="mb-4 rounded-[14px] border border-orange-200 bg-orange-50 p-5"><b class="text-[14px]">¿Cambió tu trayectoria?</b><p class="mt-1 text-[12.5px] text-gray-700">Actualiza tu ficha para seguir apareciendo en búsquedas relevantes.</p><a href="{{ route('postulante.ficha') }}" class="ad-btn-ghost ad-btn-sm mt-3">Revisar mi ficha</a></div>
        @endif
        <div class="ad-card ad-card-pad mb-4">
            <div class="flex items-center justify-between gap-3 mb-3.5">
                <b class="text-[15px]">Activación de mi ficha</b>
                <span class="ad-chip ad-chip-green ad-chip-dot">Activa</span>
            </div>
            <div class="text-[13.5px] text-gray-700 leading-[1.7]">
                Pago único · visible en búsquedas mientras mantengas tu perfil activo.
            </div>
            <div class="text-[11.5px] text-gray-500 mt-2.5 text-center">
                No hay renovación. Si pausas tu ficha, tus datos no se borran.
            </div>
        </div>

        <div class="ad-toggle-row mb-3.5">
            <div>
                <b class="text-[13.5px] block">Visibilidad del perfil</b>
                <span class="text-[12px] text-gray-500">
                    {{ $postulante?->visible ? 'Activo — apareces en búsquedas' : 'Inactivo — no apareces' }}
                </span>
            </div>
            <flux:switch wire:click="toggleVisibilidad" :checked="$postulante?->visible ?? false" />
        </div>

        <flux:modal.trigger name="confirmar-eliminacion">
            <button class="ad-btn ad-btn-sm ad-btn-block bg-[#FBE9E7] text-[#C0392B]">
                Solicitar eliminación de mis datos
            </button>
        </flux:modal.trigger>

        <flux:modal name="confirmar-eliminacion" class="max-w-md">
            <flux:heading size="lg">¿Eliminar tus datos?</flux:heading>
            <flux:text class="mt-2">
                Pediremos al equipo de AD Consulting que dé de baja tu ficha y todos tus datos personales,
                conforme a la Ley 21.719. Esta acción es definitiva.
            </flux:text>
            <div class="flex gap-2 justify-end mt-6">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button variant="danger">Confirmar solicitud</flux:button>
            </div>
        </flux:modal>
    </div>
</div>

</div>
