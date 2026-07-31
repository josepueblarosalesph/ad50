<div class="ad-panel">
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:status>{{ $empresa?->plan?->nombre ?? 'Sin plan' }}</x-slot:status>
    <x-slot:nav><x-nav-empresa activo="panel" /></x-slot:nav>

    <div class="flex items-start justify-between gap-5 mb-6 flex-wrap"><div><h1 class="text-[27px] font-extrabold">Hola, {{ $empresa?->razon_social ?? auth()->user()->name }}</h1><p class="text-[14px] text-gray-500 mt-1.5">Resumen de tu actividad de selección.</p></div><div class="flex flex-wrap gap-3">@if ($puedePublicar)<a wire:navigate href="{{ route('empresa.publicaciones.create') }}" class="ad-btn-primary ad-btn-sm">Nueva publicación</a>@endif<a href="{{ route('empresa.busquedas.create') }}" class="ad-btn-primary ad-btn-sm">Buscar candidatos</a></div></div>

    <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <div class="ad-card p-5">
            <span class="text-[13px] font-semibold text-gray-500">Publicaciones vigentes</span>
            <div class="mt-3 truncate text-[25px] font-extrabold">{{ $publicacionesVigentes }}</div>
        </div>

        {{-- Cupos del plan: sin plan no hay cupo, y `totales` en null son ilimitadas. --}}
        <div class="ad-card p-5">
            <span class="text-[13px] font-semibold text-gray-500">Publicaciones disponibles</span>
            <div class="mt-3 truncate text-[25px] font-extrabold">
                @if (! $tienePlan)
                    —
                @elseif ($publicacionesTotales === null)
                    Ilimitadas
                @else
                    {{ $publicacionesDisponibles }} <span class="text-[16px] font-bold text-gray-400">de {{ $publicacionesTotales }}</span>
                @endif
            </div>
        </div>

        <div class="ad-card p-5">
            <span class="text-[13px] font-semibold text-gray-500">Desbloqueos disponibles (candidatos)</span>
            <div class="mt-3 truncate text-[25px] font-extrabold">
                {{ $desbloqueosDisponibles }} <span class="text-[16px] font-bold text-gray-400">de {{ $desbloqueosTotales }}</span>
            </div>
        </div>

        {{-- Acceso directo al listado de favoritos --}}
        <a wire:navigate href="{{ route('empresa.favoritos') }}" class="ad-card block p-5 transition hover:border-orange-300 hover:shadow-[var(--shadow-card)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500">
            <span class="flex items-center justify-between text-[13px] font-semibold text-gray-500">Candidatos favoritos <flux:icon.arrow-up-right class="size-4 text-gray-400" /></span>
            <div class="mt-3 truncate text-[25px] font-extrabold">{{ $totalFavoritos }}</div>
        </a>
    </div>

    <section class="ad-card overflow-hidden">
        <div class="ad-card-head"><h2 class="text-[16px] font-bold">Mis publicaciones recientes</h2><div class="flex items-center gap-4"><a wire:navigate href="{{ route('empresa.publicaciones.index') }}" class="text-[14px] font-semibold text-gray-500">Ver más</a>@if ($puedePublicar)<a wire:navigate href="{{ route('empresa.publicaciones.create') }}" class="text-[14px] font-semibold text-orange-600">+ Nuevo</a>@endif</div></div>
        <div class="overflow-x-auto"><table class="w-full text-[14px]"><thead><tr class="ad-thead-row">
                            <x-th-ordenable campo="cargo" :orden="$orden" :direccion="$direccion">Publicación</x-th-ordenable>
                            <x-th-ordenable campo="postulaciones" :orden="$orden" :direccion="$direccion">Postulaciones</x-th-ordenable>
                            <x-th-ordenable campo="vigente_hasta" :orden="$orden" :direccion="$direccion">Vigencia</x-th-ordenable>
                            <x-th-ordenable campo="estado" :orden="$orden" :direccion="$direccion">Estado</x-th-ordenable>
                            <th class="p-4"></th>
                        </tr></thead><tbody>
            @forelse ($publicaciones as $publicacion)
                <tr wire:key="publicacion-{{ $publicacion->id }}" class="border-b border-line last:border-0"><td class="p-4"><a wire:navigate href="{{ route('empresa.publicaciones.show', $publicacion) }}" class="rounded-lg font-bold text-ink underline decoration-orange-300 underline-offset-4 transition hover:text-orange-600 hover:decoration-orange-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600">{{ $publicacion->cargo }}</a><p class="mt-1 text-[12px] text-gray-500">{{ $publicacion->comuna }} · {{ $publicacion->modalidad }}</p></td><td class="p-4"><a wire:navigate href="{{ route('empresa.publicaciones.postulaciones', $publicacion) }}" class="inline-flex min-w-9 items-center justify-center rounded-lg px-2 py-1 font-bold text-orange-600 underline decoration-orange-300 underline-offset-4 transition hover:bg-orange-100 hover:decoration-orange-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600" aria-label="Ver las {{ $publicacion->postulaciones_count }} postulaciones de {{ $publicacion->cargo }}">{{ $publicacion->postulaciones_count }}</a></td><td class="p-4 text-gray-600">{{ $publicacion->vigente_hasta?->translatedFormat('d M Y') ?? '—' }}</td><td class="p-4"><span @class(['ad-chip', 'ad-chip-green' => $publicacion->estaVigente()])>{{ $publicacion->estadoLabel() }}</span></td><td class="p-4 text-right"><a wire:navigate href="{{ route('empresa.publicaciones.show', $publicacion) }}" class="font-semibold text-orange-600">Ver</a></td></tr>
            @empty
                <tr><td colspan="5" class="p-8 text-center text-gray-500">Aún no has creado publicaciones.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>
</div>
