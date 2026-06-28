<div>
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:status>{{ $empresa?->plan?->nombre ?? 'Sin plan' }}</x-slot:status>
    <x-slot:nav>
        <a href="{{ route('empresa.panel') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-ink bg-orange-100">Panel</a>
        <a href="{{ route('empresa.busquedas.create') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Búsquedas</a>
        <a href="{{ route('planes') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Mi plan</a>
    </x-slot:nav>
    <x-slot:sidebar>
        <div class="text-[10.5px] tracking-[0.12em] uppercase text-gray-400 font-bold px-2.5 mb-2">Selección</div>
        <a href="{{ route('empresa.panel') }}" class="flex items-center gap-3 text-[14px] font-semibold px-3 py-2.5 rounded-[10px] bg-orange-100 text-orange-600"><flux:icon.squares-2x2 class="size-[18px]" />Panel</a>
        <a href="{{ route('empresa.busquedas.create') }}" class="flex items-center gap-3 text-[14px] font-semibold px-3 py-2.5 rounded-[10px] text-gray-700 hover:bg-paper"><flux:icon.magnifying-glass class="size-[18px]" />Nueva búsqueda</a>
        <div class="text-[10.5px] tracking-[0.12em] uppercase text-gray-400 font-bold px-2.5 mt-5 mb-2">Cuenta</div>
        <a href="{{ route('planes') }}" class="flex items-center gap-3 text-[14px] font-semibold px-3 py-2.5 rounded-[10px] text-gray-700 hover:bg-paper"><flux:icon.credit-card class="size-[18px]" />Suscripción</a>
    </x-slot:sidebar>

    <div class="flex items-start justify-between gap-5 mb-6 flex-wrap"><div><h1 class="text-[27px] font-extrabold">Hola, {{ $empresa?->razon_social ?? auth()->user()->name }}</h1><p class="text-[14px] text-gray-500 mt-1.5">Resumen de tu actividad de selección.</p></div><a href="{{ route('empresa.busquedas.create') }}" class="ad-btn-primary ad-btn-sm">+ Nueva búsqueda</a></div>

    <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        @foreach ([['Búsquedas activas', $busquedas->where('estado', 'activa')->count(), 'Procesos publicados'], ['Candidatos que cumplen', $totalCandidatos, 'Resultados acumulados'], ['Plan', $empresa?->plan?->nombre ?? 'Sin plan', 'Revisa tu suscripción'], ['Vigencia', $empresa?->plan_hasta?->translatedFormat('d M Y') ?? '—', 'Fecha de renovación']] as [$label, $value, $detail])
            <div class="ad-card p-5"><span class="text-[12px] text-gray-500 font-semibold">{{ $label }}</span><div class="text-[25px] font-extrabold mt-3 truncate">{{ $value }}</div><div class="text-[11.5px] text-match font-semibold mt-1">{{ $detail }}</div></div>
        @endforeach
    </div>

    <section class="ad-card overflow-hidden">
        <div class="ad-card-head"><h2 class="text-[16px] font-bold">Mis búsquedas recientes</h2><a href="{{ route('empresa.busquedas.create') }}" class="text-[12.5px] font-semibold text-orange-600">+ Nueva</a></div>
        <div class="overflow-x-auto"><table class="w-full text-[13.5px]"><thead><tr class="text-left text-[11px] uppercase tracking-wider text-gray-400 border-b border-line"><th class="p-4">Búsqueda</th><th class="p-4">Candidatos</th><th class="p-4">Estado</th><th class="p-4"></th></tr></thead><tbody>
            @forelse ($busquedas as $busqueda)
                <tr class="border-b border-line last:border-0"><td class="p-4 font-semibold">{{ $busqueda->titulo }}</td><td class="p-4 text-gray-500">{{ $busqueda->candidatos_count }}</td><td class="p-4"><span class="ad-chip ad-chip-green">{{ ucfirst($busqueda->estado) }}</span></td><td class="p-4 text-right"><a href="{{ route('empresa.resultados', $busqueda) }}" class="font-semibold text-orange-600">Ver</a></td></tr>
            @empty
                <tr><td colspan="4" class="p-8 text-center text-gray-500">Aún no has creado búsquedas.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>
</div>
