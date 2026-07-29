{{--
    Panel de filtros del portal de oportunidades. Se renderiza dos veces (menú lateral en
    escritorio y desplegable en móvil), por eso todo lleva `$prefijo` en el wire:key.

    Cada filtro acota por una columna real de `publicaciones`, así lo que se ofrece aquí
    siempre existe en las ofertas que publican las empresas.
--}}
@php($prefijo = $prefijo ?? 'escritorio')

<div class="pb-4">
    <div class="mb-3 flex items-center justify-between gap-2 px-1">
        <span class="text-[11px] font-bold uppercase tracking-[0.12em] text-gray-400">Filtrar ofertas</span>
        <span wire:loading class="text-[11px] font-bold text-orange-600">Actualizando…</span>
    </div>

    <div class="space-y-2">
        <div class="rounded-xl border border-line-2 bg-white p-3 dark:bg-[#222528]">
            <flux:input wire:model.live.debounce.300ms="buscar" size="sm" placeholder="Cargo o palabra clave" icon="magnifying-glass" aria-label="Buscar por cargo o palabra clave" />
            <flux:input wire:model.live.debounce.300ms="comuna" size="sm" class="mt-2" placeholder="Comuna (ej. Concepción)" aria-label="Filtrar por comuna" />
        </div>

        @foreach ($filtros as $campo => $filtro)
            <x-filtro-acordeon
                wire:key="filtro-{{ $prefijo }}-{{ $campo }}"
                :titulo="$filtro['etiqueta']"
                :count="count($seleccion[$campo] ?? [])"
            >
                <div class="max-h-56 space-y-1 overflow-y-auto pr-1">
                    @foreach ($filtro['opciones'] as $opcion)
                        <label class="flex cursor-pointer items-start gap-2 rounded-lg px-1.5 py-1 text-[13px] text-gray-700 transition hover:bg-paper dark:text-gray-200 dark:hover:bg-white/5">
                            <input
                                type="checkbox"
                                wire:model.live="seleccion.{{ $campo }}"
                                value="{{ $opcion }}"
                                class="mt-0.5 size-4 flex-none rounded border-line-2 text-orange-600 focus:ring-orange-500"
                            />
                            <span>{{ $opcion }}</span>
                        </label>
                    @endforeach
                </div>
            </x-filtro-acordeon>
        @endforeach

        <x-filtro-acordeon
            wire:key="filtro-{{ $prefijo }}-sueldo"
            titulo="Renta bruta mensual"
            :count="($sueldoMin > $limitesSueldo['min'] || $sueldoMax < $limitesSueldo['max']) ? 1 : 0"
        >
            <x-slider-rango-sueldo
                :clave="$prefijo"
                :hide-label="true"
                :min="$limitesSueldo['min']"
                :max="$limitesSueldo['max']"
                :desde="$sueldoMin"
                :hasta="$sueldoMax"
            />
            <p class="mt-2 text-[11px] leading-relaxed text-gray-500">
                Al acotar el rango solo se muestran ofertas que informaron su renta.
            </p>
        </x-filtro-acordeon>

        <label class="flex cursor-pointer items-center justify-between gap-2 rounded-xl border border-line-2 bg-white px-3 py-2.5 text-[13px] font-semibold text-ink dark:bg-[#222528]">
            Solo empleo inclusivo
            <input type="checkbox" wire:model.live="empleoInclusivo" class="size-4 rounded border-line-2 text-orange-600 focus:ring-orange-500" />
        </label>

        @if ($filtrosActivos > 0)
            <button type="button" wire:click="limpiarFiltros" class="ad-btn-ghost ad-btn-sm w-full justify-center">
                Limpiar {{ $filtrosActivos }} {{ $filtrosActivos === 1 ? 'filtro' : 'filtros' }}
            </button>
        @endif
    </div>
</div>
