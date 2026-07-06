<div>
    <x-slot:context>Administración</x-slot:context>
    <x-slot:status>Acceso interno</x-slot:status>
    <x-slot:nav>
        <a href="{{ route('admin.panel') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-ink bg-orange-100">Resumen</a>
        <a href="{{ route('admin.empresas') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Empresas</a>
        <a href="#" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Postulantes</a>
    </x-slot:nav>
    <x-slot:sidebar>
        <div class="text-[10.5px] tracking-[0.12em] uppercase text-gray-400 font-bold px-2.5 mb-2">Administración</div>
        @foreach ([['squares-2x2','Resumen', route('admin.panel')], ['building-office-2','Empresas', route('admin.empresas')], ['user','Postulantes', '#'], ['bars-3','Catálogos', '#'], ['credit-card','Suscripciones', '#'], ['shield-check','Seguridad y auditoría', '#']] as [$icon, $label, $href])
            <a href="{{ $href }}" @class(['flex items-center gap-3 text-[14px] font-semibold px-3 py-2.5 rounded-[10px]', 'bg-orange-100 text-orange-600' => $loop->first, 'text-gray-700 hover:bg-paper' => !$loop->first])><flux:icon :name="$icon" class="size-[18px]" />{{ $label }}</a>
        @endforeach
    </x-slot:sidebar>

    <div class="mb-6"><h1 class="text-[27px] font-extrabold">Resumen de la plataforma</h1><p class="text-[14px] text-gray-500 mt-1.5">Estado general de AD+50.</p></div>
    <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        @foreach ([['Empresas activas', $totalEmpresas, $empresasPendientes.' pendientes de revisión'], ['Postulantes', $totalPostulantes, 'Perfiles profesionales'], ['Búsquedas activas', $totalBusquedas, 'Procesos en curso'], ['Coincidencias generadas', $totalCoincidencias, 'Matches acumulados']] as [$label, $value, $detail])
            <div class="ad-card p-5"><div class="flex"><span class="text-[12px] text-gray-500 font-semibold">{{ $label }}</span><span class="ml-auto size-8 rounded-[9px] bg-orange-100 text-orange-600 grid place-items-center"><flux:icon.chart-bar class="size-4" /></span></div><div class="text-[30px] font-extrabold mt-2">{{ number_format($value, 0, ',', '.') }}</div><div class="text-[11.5px] font-semibold text-match mt-1">{{ $detail }}</div></div>
        @endforeach
    </div>

    <div class="grid xl:grid-cols-[1.4fr_0.8fr] gap-5 items-start">
        <section id="empresas" class="ad-card overflow-hidden"><div class="ad-card-head"><h2 class="text-[16px] font-bold">Empresas recientes</h2><span class="text-[12.5px] font-semibold text-orange-600">Ver todas</span></div><div class="overflow-x-auto"><table class="w-full text-[13.5px]"><thead><tr class="text-left text-[11px] uppercase tracking-wider text-gray-400 border-b border-line"><th class="p-4">Empresa</th><th class="p-4">Plan</th><th class="p-4">Estado</th></tr></thead><tbody>
            @forelse ($empresas as $empresa)
                <tr class="border-b border-line last:border-0"><td class="p-4"><b>{{ $empresa->razon_social }}</b><p class="text-[11.5px] text-gray-500 mt-1">{{ $empresa->user->email }}</p></td><td class="p-4 text-gray-500">{{ $empresa->plan?->nombre ?? 'Sin plan' }}</td><td class="p-4"><span @class(['ad-chip', 'ad-chip-green' => $empresa->estado_activacion === 'activa', 'ad-chip-orange' => $empresa->estado_activacion !== 'activa'])>{{ ucfirst($empresa->estado_activacion) }}</span></td></tr>
            @empty
                <tr><td colspan="3" class="p-8 text-center text-gray-500">No hay empresas registradas.</td></tr>
            @endforelse
        </tbody></table></div></section>
        <aside class="space-y-5"><div class="ad-card p-5"><h2 class="font-bold">Catálogos</h2><p class="text-[13px] text-gray-500 mt-2">Industrias, cargos, ciudades y criterios disponibles para el motor de búsqueda.</p><button class="ad-btn-ghost ad-btn-sm ad-btn-block mt-4">Administrar catálogos</button></div><div class="rounded-[14px] bg-ink text-white p-5"><flux:icon.shield-check class="size-6 text-orange-500" /><h2 class="font-bold mt-3">Seguridad y auditoría</h2><p class="text-[12.5px] text-[#cbc7c2] mt-2">Revisa accesos a datos personales y eventos sensibles de la plataforma.</p></div></aside>
    </div>
</div>
