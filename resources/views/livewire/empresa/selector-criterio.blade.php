<div class="relative" x-data="{ abierto: false }" x-on:click.outside="abierto = false" x-on:keydown.escape="abierto = false">
    <flux:field>
        <flux:label>{{ $etiqueta }}</flux:label>
        @if ($descripcion)
            <flux:description>{{ $descripcion }}</flux:description>
        @endif

        @if (count($seleccion))
            <div class="mb-2 flex flex-wrap gap-1.5">
                @foreach ($seleccion as $valor)
                    <span
                        class="ad-chip ad-chip-orange relative max-w-[160px] shrink-0 gap-1 px-2 py-0.5 pr-1 text-[12px]"
                        wire:key="chip-{{ $campo }}-{{ $loop->index }}"
                        x-data="{ tip: false }"
                        x-on:mouseenter="tip = true"
                        x-on:mouseleave="tip = false"
                    >
                        <span class="truncate">{{ $valor }}</span>
                        <button type="button" wire:click="quitar(@js($valor))" class="shrink-0 rounded-full p-0.5 transition hover:bg-orange-200 dark:hover:bg-white/10" aria-label="Quitar {{ $valor }}">
                            <flux:icon.x-mark class="size-2.5" />
                        </button>
                        <span x-show="tip" x-cloak class="pointer-events-none absolute bottom-full left-0 z-50 mb-1 max-w-[280px] whitespace-normal break-words rounded-md bg-gray-900 px-2 py-1 text-[11px] font-semibold leading-snug text-white shadow-lg dark:bg-gray-100 dark:text-gray-900">{{ $valor }}</span>
                    </span>
                @endforeach
            </div>
        @endif

        <div class="relative">
            <input
                type="text"
                wire:model.live.debounce.300ms="buscar"
                x-on:focus="abierto = true"
                placeholder="Escribe para buscar"
                autocomplete="off"
                role="combobox"
                aria-autocomplete="list"
                x-bind:aria-expanded="abierto"
                class="w-full rounded-lg border border-line-2 bg-white py-2 pl-3 pr-8 text-[14px] text-ink placeholder:text-gray-400 focus:border-orange-400 focus:outline-none dark:bg-[#222528]"
            />
            <div wire:loading.delay wire:target="buscar" class="absolute inset-y-0 right-2.5 flex items-center text-[11px] font-bold text-orange-600">…</div>
        </div>
    </flux:field>

    <ul
        x-show="abierto"
        x-cloak
        class="absolute left-0 z-30 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-line-2 bg-white py-1 shadow-xl dark:border-[#5A5F64] dark:bg-[#222528]"
        role="listbox"
    >
        @forelse ($resultados as $resultado)
            <li wire:key="opt-{{ $campo }}-{{ $loop->index }}">
                <button
                    type="button"
                    wire:click="agregar(@js($resultado['valor']))"
                    x-on:click="abierto = true"
                    class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-[13px] text-ink transition hover:bg-orange-100 hover:text-orange-700 dark:text-gray-200 dark:hover:bg-white/10"
                    role="option"
                >
                    <span class="truncate">{{ $resultado['valor'] }}</span>
                    {{-- Conteo contextual: cuántas fichas quedarían al agregar esta opción
                         manteniendo el resto de los filtros ya elegidos. --}}
                    <span
                        @class([
                            'shrink-0 rounded-full px-1.5 py-0.5 text-[11px] font-bold tabular-nums',
                            'bg-orange-100 text-orange-700 dark:bg-white/10 dark:text-[#F7C59E]' => $resultado['total'] > 0,
                            'bg-gray-100 text-gray-400 dark:bg-white/5' => $resultado['total'] === 0,
                        ])
                        title="Quedan {{ $resultado['total'] }} {{ Str::plural('candidato', $resultado['total']) }} si agregas esta opción a los filtros actuales"
                    >{{ $resultado['total'] }}</span>
                </button>
            </li>
        @empty
            <li class="px-3 py-2 text-[13px] text-gray-500">Sin coincidencias en el catálogo.</li>
        @endforelse
    </ul>
</div>
