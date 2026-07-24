<div class="ad-panel">
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:status>{{ $empresa?->plan?->nombre ?? 'Sin plan' }}</x-slot:status>
    <x-slot:nav>
        <a href="{{ route('empresa.panel') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-ink bg-orange-100">Mi Panel</a>
        <a wire:navigate href="{{ route('empresa.busquedas.index') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Mis Procesos</a>
        @if (auth()->user()->esPrincipalEmpresa())
            <a wire:navigate href="{{ route('empresa.equipo') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Equipo</a>
        @endif
    </x-slot:nav>

    <div class="flex items-start justify-between gap-5 mb-6 flex-wrap"><div><h1 class="text-[27px] font-extrabold">Hola, {{ $empresa?->razon_social ?? auth()->user()->name }}</h1><p class="text-[14px] text-gray-500 mt-1.5">Resumen de tu actividad de selección.</p></div><a href="{{ route('empresa.busquedas.create') }}" class="ad-btn-primary ad-btn-sm">+ Nuevo proceso</a></div>

    <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        @foreach ([['Procesos activos', $busquedas->whereIn('estado', \App\Models\Busqueda::ESTADOS_ACTIVOS)->count(), 'Procesos vigentes'], ['Candidatos que cumplen', $totalCandidatos, 'Resultados acumulados'], ['Plan', $empresa?->plan?->nombre ?? 'Sin plan', 'Revisa tu suscripción'], ['Vigencia', $empresa?->plan_hasta?->translatedFormat('d M Y') ?? '—', 'Fecha de renovación']] as [$label, $value, $detail])
            <div class="ad-card p-5"><span class="text-[13px] text-gray-500 font-semibold">{{ $label }}</span><div class="text-[25px] font-extrabold mt-3 truncate">{{ $value }}</div><div class="mt-1 text-[13px] font-semibold text-match">{{ $detail }}</div></div>
        @endforeach
    </div>

    <section class="ad-card overflow-hidden">
        <div class="ad-card-head"><h2 class="text-[16px] font-bold">Mis procesos recientes</h2><div class="flex items-center gap-4"><a wire:navigate href="{{ route('empresa.busquedas.index') }}" class="text-[14px] font-semibold text-gray-500">Ver más</a><a wire:navigate href="{{ route('empresa.busquedas.create') }}" class="text-[14px] font-semibold text-orange-600">+ Nuevo</a></div></div>
        <div class="overflow-x-auto"><table class="w-full text-[14px]"><thead><tr class="ad-thead-row"><th class="p-4">Proceso</th><th class="p-4">Candidatos</th><th class="p-4">Estado</th><th class="p-4"></th></tr></thead><tbody>
            @forelse ($busquedas as $busqueda)
                <tr class="border-b border-line last:border-0"><td class="p-4 font-semibold"><a wire:navigate href="{{ route('empresa.resultados', $busqueda) }}" class="rounded-lg text-ink underline decoration-orange-300 underline-offset-4 transition hover:text-orange-600 hover:decoration-orange-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600">{{ $busqueda->titulo }}</a></td><td class="p-4"><a wire:navigate href="{{ route('empresa.resultados', $busqueda) }}" class="inline-flex min-w-9 items-center justify-center rounded-lg px-2 py-1 font-bold text-orange-600 underline decoration-orange-300 underline-offset-4 transition hover:bg-orange-100 hover:decoration-orange-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600" aria-label="Ver los {{ $busqueda->candidatos_count }} candidatos de {{ $busqueda->titulo }}">{{ $busqueda->candidatos_count }}</a></td><td class="p-4"><span @class(['ad-chip', 'ad-chip-green' => $busqueda->estaVigente()])>{{ $busqueda->estadoLabel() }}</span></td><td class="p-4 text-right"><a wire:navigate href="{{ route('empresa.resultados', $busqueda) }}" class="font-semibold text-orange-600">Ver</a></td></tr>
            @empty
                <tr><td colspan="4" class="p-8 text-center text-gray-500">Aún no has creado procesos.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>
</div>
