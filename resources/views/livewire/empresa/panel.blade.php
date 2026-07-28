<div class="ad-panel">
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:status>{{ $empresa?->plan?->nombre ?? 'Sin plan' }}</x-slot:status>
    <x-slot:nav><x-nav-empresa activo="panel" /></x-slot:nav>

    <div class="flex items-start justify-between gap-5 mb-6 flex-wrap"><div><h1 class="text-[27px] font-extrabold">Hola, {{ $empresa?->razon_social ?? auth()->user()->name }}</h1><p class="text-[14px] text-gray-500 mt-1.5">Resumen de tu actividad de selección.</p></div><div class="flex flex-wrap gap-3">@if ($puedePublicar)<a wire:navigate href="{{ route('empresa.publicaciones.create') }}" class="ad-btn-primary ad-btn-sm">+ Nueva publicación</a>@endif<a href="{{ route('empresa.busquedas.create') }}" class="ad-btn-primary ad-btn-sm">+ Nueva búsqueda</a></div></div>

    <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        @foreach ([['Búsquedas activas', $busquedas->whereIn('estado', \App\Models\Busqueda::ESTADOS_ACTIVOS)->count(), 'Búsquedas vigentes'], ['Candidatos que cumplen', $totalCandidatos, 'Resultados acumulados']] as [$label, $value, $detail])
            <div class="ad-card p-5"><span class="text-[13px] text-gray-500 font-semibold">{{ $label }}</span><div class="text-[25px] font-extrabold mt-3 truncate">{{ $value }}</div><div class="mt-1 text-[13px] font-semibold text-match">{{ $detail }}</div></div>
        @endforeach

        {{-- Acceso directo a la suscripción --}}
        <a wire:navigate href="{{ route('empresa.planes') }}" class="ad-card block p-5 transition hover:border-orange-300 hover:shadow-[var(--shadow-card)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500">
            <span class="flex items-center justify-between text-[13px] font-semibold text-gray-500">Plan <flux:icon.arrow-up-right class="size-4 text-gray-400" /></span>
            <div class="mt-3 truncate text-[25px] font-extrabold">{{ $empresa?->plan?->nombre ?? 'Sin plan' }}</div>
            <div class="mt-1 text-[13px] font-semibold text-orange-600">Ver planes y suscripción</div>
        </a>

        <div class="ad-card p-5"><span class="text-[13px] text-gray-500 font-semibold">Vigencia</span><div class="text-[25px] font-extrabold mt-3 truncate">{{ $empresa?->plan_hasta?->translatedFormat('d M Y') ?? '—' }}</div><div class="mt-1 text-[13px] font-semibold text-match">Fecha de renovación</div></div>
    </div>

    <section class="ad-card overflow-hidden">
        <div class="ad-card-head"><h2 class="text-[16px] font-bold">Mis búsquedas recientes</h2><div class="flex items-center gap-4"><a wire:navigate href="{{ route('empresa.busquedas.index') }}" class="text-[14px] font-semibold text-gray-500">Ver más</a><a wire:navigate href="{{ route('empresa.busquedas.create') }}" class="text-[14px] font-semibold text-orange-600">+ Nuevo</a></div></div>
        <div class="overflow-x-auto"><table class="w-full text-[14px]"><thead><tr class="ad-thead-row"><th class="p-4">Búsqueda</th><th class="p-4">Candidatos</th><th class="p-4">Estado</th><th class="p-4"></th></tr></thead><tbody>
            @forelse ($busquedas as $busqueda)
                <tr class="border-b border-line last:border-0"><td class="p-4 font-semibold"><a wire:navigate href="{{ route('empresa.resultados', $busqueda) }}" class="rounded-lg text-ink underline decoration-orange-300 underline-offset-4 transition hover:text-orange-600 hover:decoration-orange-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600">{{ $busqueda->titulo }}</a></td><td class="p-4"><a wire:navigate href="{{ route('empresa.resultados', $busqueda) }}" class="inline-flex min-w-9 items-center justify-center rounded-lg px-2 py-1 font-bold text-orange-600 underline decoration-orange-300 underline-offset-4 transition hover:bg-orange-100 hover:decoration-orange-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600" aria-label="Ver los {{ $busqueda->candidatos_count }} candidatos de {{ $busqueda->titulo }}">{{ $busqueda->candidatos_count }}</a></td><td class="p-4"><span @class(['ad-chip', 'ad-chip-green' => $busqueda->estaVigente()])>{{ $busqueda->estadoLabel() }}</span></td><td class="p-4 text-right"><a wire:navigate href="{{ route('empresa.resultados', $busqueda) }}" class="font-semibold text-orange-600">Ver</a></td></tr>
            @empty
                <tr><td colspan="4" class="p-8 text-center text-gray-500">Aún no has creado búsquedas.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>
</div>
