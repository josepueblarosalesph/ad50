<div class="ad-panel">
    <x-slot:context>Administración</x-slot:context>
    <x-slot:status>Acceso interno</x-slot:status>
    <x-slot:nav><x-nav-admin activo="postulantes" /></x-slot:nav>

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-[27px] font-extrabold">Postulantes</h1>
            <p class="mt-1.5 text-[14px] text-gray-500">
                {{ $totalPostulantes }} {{ $totalPostulantes === 1 ? 'perfil registrado' : 'perfiles registrados' }} ·
                {{ $totalVisibles }} visibles para las empresas
                @if ($totalSinVerificar > 0)
                    · <button type="button" wire:click="$set('verificacion', 'pendientes')" class="font-semibold text-orange-600 underline decoration-orange-200 underline-offset-4">{{ $totalSinVerificar }} sin verificar</button>
                @endif
            </p>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-5 rounded-xl border border-[#BFE6CD] bg-match-100 px-4 py-3 text-[13px] font-bold text-match">{{ session('status') }}</div>
    @endif

    <section class="ad-card mb-5 p-4 md:p-5">
        <div class="grid gap-4 md:grid-cols-4">
            <flux:input wire:model.live.debounce.300ms="buscar" label="Buscar" placeholder="Nombre o correo" icon="magnifying-glass" />

            <div>
                <label for="filtro-visibilidad" class="mb-1.5 block text-[12px] font-bold text-gray-600 dark:text-gray-300">Visibilidad</label>
                <select id="filtro-visibilidad" wire:model.live="visibilidad" class="w-full rounded-lg border border-line-2 bg-white px-3 py-2 text-[13.5px] font-semibold text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500 dark:bg-[#2A2D30]">
                    <option value="todos">Todos</option>
                    <option value="visibles">Visibles</option>
                    <option value="ocultos">Pausados</option>
                </select>
            </div>

            <div>
                <label for="filtro-onboarding" class="mb-1.5 block text-[12px] font-bold text-gray-600 dark:text-gray-300">Ficha</label>
                <select id="filtro-onboarding" wire:model.live="onboarding" class="w-full rounded-lg border border-line-2 bg-white px-3 py-2 text-[13.5px] font-semibold text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500 dark:bg-[#2A2D30]">
                    <option value="todos">Todas</option>
                    <option value="completo">Completada</option>
                    <option value="incompleto">Sin completar</option>
                </select>
            </div>

            <div>
                <label for="filtro-verificacion" class="mb-1.5 block text-[12px] font-bold text-gray-600 dark:text-gray-300">Correo</label>
                <select id="filtro-verificacion" wire:model.live="verificacion" class="w-full rounded-lg border border-line-2 bg-white px-3 py-2 text-[13.5px] font-semibold text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500 dark:bg-[#2A2D30]">
                    <option value="todos">Todos</option>
                    <option value="verificados">Verificados</option>
                    <option value="pendientes">Sin verificar</option>
                </select>
            </div>
        </div>

        @if ($hayFiltros)
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-3">
                <p class="text-[13px] text-gray-500">Mostrando <b class="text-ink">{{ $postulantes->total() }}</b> de {{ $totalPostulantes }} perfiles.</p>
                <button type="button" wire:click="limpiarFiltros" class="ad-btn-ghost ad-btn-sm">Limpiar filtros</button>
            </div>
        @endif
    </section>

    <section class="ad-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-[14px]">
                <thead>
                    <tr class="ad-thead-row">
                        <th class="p-4">Postulante</th>
                        @foreach ([
                            'cargo_actual' => 'Cargo actual',
                            'anios_experiencia' => 'Experiencia',
                            'completitud' => 'Ficha',
                            'created_at' => 'Registro',
                            'actualizacion' => 'Última actualización',
                        ] as $campo => $etiqueta)
                            <x-th-ordenable :campo="$campo" :orden="$orden" :direccion="$direccion">{{ $etiqueta }}</x-th-ordenable>
                        @endforeach
                        <th class="p-4">Estado</th>
                        <th class="p-4 text-right">Cuenta</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($postulantes as $postulante)
                        <tr wire:key="postulante-{{ $postulante->id }}" class="border-b border-line last:border-0">
                            <td class="p-4">
                                <b class="block text-ink">{{ $postulante->user?->name ?? 'Sin nombre' }}</b>
                                <a href="mailto:{{ $postulante->user?->email }}" class="text-[13px] text-gray-500 underline decoration-orange-200 underline-offset-4 hover:text-orange-600">
                                    {{ $postulante->user?->email }}
                                </a>
                            </td>
                            <td class="p-4 text-gray-600">
                                {{ $postulante->cargo_actual ?: '—' }}
                                @if ($postulante->carrera)
                                    <span class="mt-0.5 block text-[12.5px] text-gray-400">{{ $postulante->carrera }}</span>
                                @endif
                            </td>
                            <td class="p-4 text-gray-600">{{ $postulante->anios_experiencia ? $postulante->anios_experiencia.' años' : '—' }}</td>
                            <td class="p-4">
                                <div class="flex items-center gap-2">
                                    <div class="h-1.5 w-16 overflow-hidden rounded-full bg-line">
                                        <div class="h-full bg-gradient-to-r from-orange-500 to-[#F59A53]" style="width: {{ $postulante->completitud ?? 0 }}%"></div>
                                    </div>
                                    <span class="text-[12.5px] font-bold tabular-nums text-gray-600">{{ $postulante->completitud ?? 0 }}%</span>
                                </div>
                                @unless ($postulante->onboarding_completado)
                                    <span class="mt-1 block text-[12px] text-gray-400">Sin completar</span>
                                @endunless
                            </td>
                            <td class="p-4 text-gray-600">{{ $postulante->created_at?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td class="p-4 text-gray-600">{{ $postulante->updated_at?->diffForHumans() ?? '—' }}</td>
                            <td class="p-4">
                                <span @class(['ad-chip ad-chip-sm', 'ad-chip-green' => $postulante->visible])>
                                    {{ $postulante->visible ? 'Visible' : 'Pausado' }}
                                </span>
                            </td>
                            <td class="p-4">
                                @if ($postulante->user?->email_verified_at === null)
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        <span class="ad-chip ad-chip-sm ad-chip-orange">Sin verificar</span>
                                        <x-acciones-verificacion :user="$postulante->user" />
                                    </div>
                                @else
                                    <span class="block text-right text-[12.5px] text-gray-400">Verificada</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-10 text-center">
                                <flux:icon.users class="mx-auto size-8 text-gray-400" />
                                <h2 class="mt-3 font-bold">{{ $hayFiltros ? 'Ningún postulante cumple estos filtros' : 'Todavía no hay postulantes' }}</h2>
                                @if ($hayFiltros)
                                    <button type="button" wire:click="limpiarFiltros" class="ad-btn-ghost ad-btn-sm mt-4">Limpiar filtros</button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($postulantes->hasPages())
        <div class="mt-6">{{ $postulantes->links() }}</div>
    @endif
</div>
