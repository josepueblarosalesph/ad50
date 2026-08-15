<div class="ad-panel">
    <x-slot:context>Administración</x-slot:context>
    <x-slot:nav><x-nav-admin activo="cupones" /></x-slot:nav>

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-[27px] font-extrabold">Cupones</h1>
            <p class="mt-1.5 text-[14px] text-gray-500">
                {{ $totalCupones }} {{ $totalCupones === 1 ? 'cupón creado' : 'cupones creados' }} · el descuento se aplica sobre el precio del plan al contratar
            </p>
        </div>

        <button type="button" wire:click="abrirNuevo" class="ad-btn-primary ad-btn-sm">
            <flux:icon.ticket class="size-4" />
            Crear cupón
        </button>
    </div>

    @if (session('status'))
        <div class="ad-card mb-5 border-l-4 border-l-match p-4 text-[14px] font-semibold text-ink">
            {{ session('status') }}
        </div>
    @endif

    <section class="ad-card mb-5 p-4 md:p-5">
        <div class="grid gap-4 md:grid-cols-2">
            <flux:input wire:model.live.debounce.300ms="buscar" label="Buscar" placeholder="Código o descripción" icon="magnifying-glass" />

            <x-campo-select id="filtro-estado" label="Estado" wire:model.live="estado">
                <option value="todos">Todos</option>
                <option value="vigentes">Vigentes hoy</option>
                <option value="agotados">Agotados</option>
                <option value="vencidos">Vencidos</option>
                <option value="inactivos">Desactivados</option>
            </x-campo-select>
        </div>

        @if ($hayFiltros)
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-3">
                <p class="text-[13px] text-gray-500">Mostrando <b class="text-ink">{{ $cupones->total() }}</b> de {{ $totalCupones }} cupones.</p>
                <button type="button" wire:click="limpiarFiltros" class="ad-btn-ghost ad-btn-sm">Limpiar filtros</button>
            </div>
        @endif
    </section>

    <section class="ad-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-[14px]">
                <thead>
                    <tr class="ad-thead-row">
                        <x-th-ordenable campo="codigo" :orden="$orden" :direccion="$direccion">Código</x-th-ordenable>
                        <th class="p-4">Descuento</th>
                        <th class="p-4">Plan</th>
                        <x-th-ordenable campo="usos" :orden="$orden" :direccion="$direccion">Usos</x-th-ordenable>
                        <x-th-ordenable campo="vigente_hasta" :orden="$orden" :direccion="$direccion">Vigencia</x-th-ordenable>
                        <th class="p-4">Estado</th>
                        <th class="p-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cupones as $cupon)
                        <tr wire:key="cupon-{{ $cupon->id }}" class="border-b border-line last:border-0">
                            <td class="p-4">
                                <b class="block font-mono text-ink">{{ $cupon->codigo }}</b>
                                @if ($cupon->descripcion)
                                    <span class="text-[13px] text-gray-500">{{ $cupon->descripcion }}</span>
                                @endif
                            </td>
                            <td class="p-4">
                                <b class="text-ink">{{ $cupon->valorLabel() }}</b>
                                @if ($cupon->esPorcentaje() && $cupon->valor === 100)
                                    <span class="mt-1 block text-[12px] font-semibold text-orange-600">Plan gratis</span>
                                @endif
                            </td>
                            <td class="p-4 text-gray-600">
                                {{ $cupon->plan?->nombre ?? 'Cualquier plan' }}
                            </td>
                            <td class="p-4 text-gray-600">
                                {{ $cupon->usos }}{{ $cupon->max_usos !== null ? ' / '.$cupon->max_usos : '' }}
                                @if ($cupon->uso_unico_por_empresa)
                                    <span class="mt-1 block text-[12px] text-gray-400">1 por empresa</span>
                                @endif
                            </td>
                            <td class="p-4 text-gray-600">
                                @if ($cupon->vigente_desde === null && $cupon->vigente_hasta === null)
                                    <span class="text-gray-400">Sin límite</span>
                                @else
                                    {{ $cupon->vigente_desde?->translatedFormat('d M Y') ?? '—' }}
                                    →
                                    {{ $cupon->vigente_hasta?->translatedFormat('d M Y') ?? '—' }}
                                @endif
                            </td>
                            <td class="p-4">
                                @if (! $cupon->activo)
                                    <span class="ad-chip ad-chip-sm ad-chip-gray">Desactivado</span>
                                @elseif ($cupon->estaAgotado())
                                    <span class="ad-chip ad-chip-sm ad-chip-gray">Agotado</span>
                                @elseif (! $cupon->enVentana())
                                    <span class="ad-chip ad-chip-sm ad-chip-gray">Fuera de fecha</span>
                                @else
                                    <span class="ad-chip ad-chip-sm ad-chip-green">Vigente</span>
                                @endif
                            </td>
                            <td class="p-4">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <button type="button" wire:click="abrirEdicion({{ $cupon->id }})" class="ad-btn-ghost ad-btn-sm">Editar</button>
                                    <button type="button" wire:click="alternarActivo({{ $cupon->id }})" class="ad-btn-ghost ad-btn-sm">
                                        {{ $cupon->activo ? 'Desactivar' : 'Activar' }}
                                    </button>
                                    @if ($cupon->usos === 0)
                                        <button
                                            type="button"
                                            wire:click="eliminar({{ $cupon->id }})"
                                            wire:confirm="¿Eliminar el cupón {{ $cupon->codigo }}? Nunca se usó, así que se borra del todo."
                                            class="ad-btn-ghost ad-btn-sm text-[#A93226]"
                                        >Eliminar</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-10 text-center">
                                <flux:icon.ticket class="mx-auto size-8 text-gray-400" />
                                <h2 class="mt-3 font-bold">{{ $hayFiltros ? 'Ningún cupón cumple estos filtros' : 'Todavía no hay cupones' }}</h2>
                                <p class="mt-1.5 text-[13.5px] text-gray-500">
                                    {{ $hayFiltros ? '' : 'Un cupón rebaja el precio del plan al contratarlo, sin tocar el precio de lista.' }}
                                </p>
                                @if ($hayFiltros)
                                    <button type="button" wire:click="limpiarFiltros" class="ad-btn-ghost ad-btn-sm mt-4">Limpiar filtros</button>
                                @else
                                    <button type="button" wire:click="abrirNuevo" class="ad-btn-primary ad-btn-sm mt-4">Crear el primero</button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($cupones->hasPages())
        <div class="mt-6">{{ $cupones->links() }}</div>
    @endif

    <flux:modal name="editar-cupon" class="max-w-xl">
        <form wire:submit="guardar" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editandoId === null ? 'Crear cupón' : 'Editar cupón' }}</flux:heading>
                <flux:text class="mt-1">El descuento se calcula sobre el precio en pesos del plan (UF del día + IVA).</flux:text>
            </div>

            <div>
                <flux:input wire:model="codigo" label="Código" placeholder="AD50-VERANO25" />
                <button type="button" wire:click="generarCodigo" class="ad-btn-ghost ad-btn-sm mt-2">Generar un código</button>
            </div>

            <flux:input wire:model="descripcion" label="Descripción (opcional)" placeholder="Campaña de lanzamiento, convenio CCS…" />

            <div class="grid gap-4 sm:grid-cols-2">
                <x-campo-select id="cupon-tipo" label="Tipo de descuento" error="tipo" wire:model.live="tipo">
                    @foreach (\App\Models\Cupon::TIPOS as $clave => $etiqueta)
                        <option value="{{ $clave }}">{{ $etiqueta }}</option>
                    @endforeach
                </x-campo-select>

                <flux:input
                    wire:model="valor"
                    type="number"
                    :label="$tipo === 'porcentaje' ? 'Porcentaje a descontar' : 'Pesos a descontar'"
                    :placeholder="$tipo === 'porcentaje' ? '20' : '15000'"
                />
            </div>

            @if ($tipo === 'porcentaje' && (int) $valor === 100)
                <div class="rounded-lg border border-orange-200 bg-orange-50 p-3.5 text-[12.5px] font-semibold text-ink dark:bg-[#2A2D30] dark:text-gray-200">
                    Con 100% el plan queda gratis: no pasa por la pasarela y se activa de inmediato.
                    Conviene ponerle un máximo de usos y una fecha de término.
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <x-campo-select id="cupon-plan" label="Plan al que aplica" error="planId" wire:model="planId">
                    <option value="">Cualquier plan</option>
                    @foreach ($planes as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->nombre }}</option>
                    @endforeach
                </x-campo-select>

                <flux:input wire:model="maxUsos" type="number" label="Máximo de usos" placeholder="Sin límite" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="vigenteDesde" type="date" label="Vigente desde (opcional)" />
                <flux:input wire:model="vigenteHasta" type="date" label="Vigente hasta (opcional)" />
            </div>

            <div class="space-y-3">
                <flux:checkbox wire:model="usoUnicoPorEmpresa" label="Una sola vez por empresa" />
                <flux:checkbox wire:model="activo" label="Activo (se puede usar al contratar)" />
            </div>

            <div class="rounded-lg border border-line-2 bg-gray-50 p-3.5 text-[12.5px] text-gray-600 dark:bg-[#2A2D30] dark:text-gray-300">
                Los usos se cuentan solo cuando el cobro queda confirmado: un pago abandonado
                en la pasarela no gasta cupo del cupón.
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost" type="button">Cancelar</flux:button></flux:modal.close>
                <flux:button variant="primary" type="submit" wire:loading.attr="disabled" wire:target="guardar">
                    {{ $editandoId === null ? 'Crear cupón' : 'Guardar cambios' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
