<div>
    <x-slot:context>Administración</x-slot:context>
    <x-slot:status>Acceso interno</x-slot:status>
    <x-slot:nav><x-nav-admin activo="panel" /></x-slot:nav>

    {{-- Sin <x-slot:sidebar>: repetía el menú superior y cuatro de sus seis enlaces no
         iban a ninguna parte (href="#"), incluidas dos secciones que no existen. En el
         resto del panel la barra lateral es contextual —filtros de una pantalla—, nunca
         una segunda navegación; aquí la navegación la lleva <x-nav-admin>, y punto. --}}

    <div class="mb-6"><h1 class="text-[27px] font-extrabold">Resumen de la plataforma</h1><p class="text-[14px] text-gray-500 mt-1.5">Estado general de AD+50.</p></div>
    <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        @foreach ([['Empresas activas', $totalEmpresas, $empresasPendientes.' pendientes de revisión'], ['Postulantes', $totalPostulantes, 'Perfiles profesionales'], ['Búsquedas', $totalBusquedas, 'Configuraciones de filtros guardadas'], ['Coincidencias generadas', $totalCoincidencias, 'Matches acumulados']] as [$label, $value, $detail])
            <div class="ad-card p-5"><div class="flex"><span class="text-[12px] text-gray-500 font-semibold">{{ $label }}</span><span class="ml-auto size-8 rounded-[9px] bg-orange-100 text-orange-600 grid place-items-center"><flux:icon.chart-bar class="size-4" /></span></div><div class="text-[30px] font-extrabold mt-2">{{ number_format($value, 0, ',', '.') }}</div><div class="text-[11.5px] font-semibold text-match mt-1">{{ $detail }}</div></div>
        @endforeach
    </div>

    <div class="grid xl:grid-cols-[1.4fr_0.8fr] gap-5 items-start">
        <section id="empresas" class="ad-card overflow-hidden"><div class="ad-card-head"><h2 class="text-[16px] font-bold">Empresas recientes</h2><a href="{{ route('admin.empresas') }}" wire:navigate class="text-[12.5px] font-semibold text-orange-600 hover:text-orange-500">Ver todas</a></div><div class="overflow-x-auto"><table class="w-full text-[13.5px]"><thead><tr class="ad-thead-row">
                            <x-th-ordenable campo="razon_social" :orden="$orden" :direccion="$direccion">Empresa</x-th-ordenable>
                            <x-th-ordenable campo="plan" :orden="$orden" :direccion="$direccion">Plan</x-th-ordenable>
                            <x-th-ordenable campo="estado" :orden="$orden" :direccion="$direccion">Estado</x-th-ordenable>
                        </tr></thead><tbody>
            @forelse ($empresas as $empresa)
                <tr class="border-b border-line last:border-0"><td class="p-4"><b>{{ $empresa->razon_social }}</b><p class="text-[11.5px] text-gray-500 mt-1">{{ $empresa->user->email }}</p></td><td class="p-4 text-gray-500">{{ $empresa->plan?->nombre ?? 'Sin plan' }}</td><td class="p-4"><span @class(['ad-chip', 'ad-chip-green' => $empresa->estado_activacion === 'activa', 'ad-chip-orange' => $empresa->estado_activacion !== 'activa'])>{{ ucfirst($empresa->estado_activacion) }}</span></td></tr>
            @empty
                <tr><td colspan="3" class="p-8 text-center text-gray-500">No hay empresas registradas.</td></tr>
            @endforelse
        </tbody></table></div></section>
        <aside class="space-y-5">
            {{-- Igual que el enlace del menú (ver <x-nav-admin>): administrar catálogos es
                 exclusivo del superadministrador, así que al admin común tampoco se le
                 ofrece el atajo desde aquí. --}}
            @if (auth()->user()?->esSuperadmin())
                <div class="ad-card p-5">
                    <h2 class="font-bold">Catálogos</h2>
                    <p class="mt-2 text-[13px] text-gray-500">Industrias, cargos, ciudades y criterios disponibles para el motor de búsqueda.</p>
                    <a href="{{ route('admin.catalogos') }}" wire:navigate class="ad-btn-ghost ad-btn-sm ad-btn-block mt-4">Administrar catálogos</a>
                </div>
            @endif

            {{-- Antes había aquí una tarjeta de «Seguridad y auditoría» que no llevaba a
                 ninguna parte porque esa pantalla no existe. En su lugar va la bandeja de
                 mensajes, que sí existe y sí necesita atención. --}}
            <a href="{{ route('admin.mensajes') }}" wire:navigate class="block rounded-[14px] bg-ink p-5 text-white transition hover:-translate-y-0.5">
                <div class="flex items-start justify-between gap-3">
                    <flux:icon.inbox class="size-6 text-orange-500" />
                    @if ($mensajesPendientes > 0)
                        <span class="rounded-full bg-orange-600 px-2.5 py-0.5 text-[12px] font-extrabold text-white">{{ $mensajesPendientes }}</span>
                    @endif
                </div>
                <h2 class="mt-3 font-bold">Mensajes de contacto</h2>
                <p class="mt-2 text-[12.5px] text-[#cbc7c2]">
                    @if ($mensajesPendientes > 0)
                        {{ $mensajesPendientes === 1 ? 'Hay 1 mensaje sin responder.' : "Hay {$mensajesPendientes} mensajes sin responder." }}
                    @else
                        No hay mensajes pendientes de respuesta.
                    @endif
                </p>
            </a>
        </aside>
    </div>
</div>
