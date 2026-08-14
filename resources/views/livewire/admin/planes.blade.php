<div class="ad-panel">
    <x-slot:context>Administración</x-slot:context>
    <x-slot:status>Acceso interno</x-slot:status>
    <x-slot:nav><x-nav-admin activo="planes" /></x-slot:nav>

    <div class="mb-6">
        <h1 class="text-[27px] font-extrabold">Planes</h1>
        <p class="mt-1.5 text-[14px] text-gray-500">
            Configuración vigente de los planes. Para asignar o extender el plan de una empresa,
            entra a <a href="{{ route('admin.empresas') }}" wire:navigate class="font-semibold text-orange-600 underline decoration-orange-200 underline-offset-4">Empresas</a>.
        </p>
    </div>

    @foreach ([
        ['titulo' => 'Planes para empresas', 'planes' => $planesEmpresa, 'esEmpresa' => true],
        ['titulo' => 'Planes para postulantes', 'planes' => $planesPostulante, 'esEmpresa' => false],
    ] as $grupo)
        <section class="ad-card mb-5 overflow-hidden">
            <div class="border-b border-line p-4 md:px-5">
                <h2 class="text-[16px] font-extrabold">{{ $grupo['titulo'] }}</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-[14px]">
                    <thead>
                        <tr class="ad-thead-row">
                            <th class="p-4">Plan</th>
                            <th class="p-4">Precio</th>
                            <th class="p-4">Cobro</th>
                            <th class="p-4">Desbloqueos</th>
                            <th class="p-4">Publicaciones</th>
                            @if ($grupo['esEmpresa'])
                                <th class="p-4">Empresas suscritas</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($grupo['planes'] as $plan)
                            <tr wire:key="plan-{{ $plan->id }}" class="border-b border-line last:border-0">
                                <td class="p-4">
                                    <b class="block text-ink">{{ $plan->nombre }}</b>
                                    <span class="text-[12.5px] text-gray-400">{{ $plan->codigo }}</span>
                                    @if ($plan->destacado)
                                        <span class="ad-chip ad-chip-sm ad-chip-orange ml-2">Destacado</span>
                                    @endif
                                </td>
                                <td class="p-4 font-semibold tabular-nums text-ink">
                                    {{ rtrim(rtrim(number_format((float) $plan->precio_uf, 2, ',', '.'), '0'), ',') }} UF
                                    <span class="mt-0.5 block text-[12px] font-normal text-gray-400">+ IVA</span>
                                </td>
                                <td class="p-4 text-gray-600">
                                    {{ ucfirst($plan->cobroLabel()) }}
                                    @if ($plan->tieneTopeAnual())
                                        <span class="mt-0.5 block text-[12px] text-gray-400">Hasta {{ $plan->max_contrataciones_anuales }} al año</span>
                                    @endif
                                </td>
                                <td class="p-4 tabular-nums text-gray-600">{{ $plan->desbloqueos ?? 'Ilimitados' }}</td>
                                <td class="p-4 tabular-nums text-gray-600">{{ $plan->publicaciones ?? 'Ilimitadas' }}</td>
                                @if ($grupo['esEmpresa'])
                                    <td class="p-4">
                                        <span @class(['ad-chip ad-chip-sm', 'ad-chip-green' => ($empresasPorPlan[$plan->id] ?? 0) > 0])>
                                            {{ $empresasPorPlan[$plan->id] ?? 0 }}
                                        </span>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-10 text-center">
                                    <flux:icon.credit-card class="mx-auto size-8 text-gray-400" />
                                    <h3 class="mt-3 font-bold">No hay planes configurados para este público</h3>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach
</div>
