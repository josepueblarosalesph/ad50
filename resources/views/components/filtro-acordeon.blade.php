@props([
    'titulo',
    'count' => 0,
])

{{-- Fila de filtro colapsable para el panel de la búsqueda en detalle. El header
     muestra el título + un contador de criterios seleccionados; el cuerpo se
     despliega/oculta con Alpine (x-collapse).

     Nota: NO usamos overflow-hidden en el contenedor porque recortaría el
     dropdown de opciones del combobox/selector (posicionado en absolute). El
     plugin x-collapse ya aplica overflow oculto solo mientras dura la animación. --}}
<div x-data="{ abierto: false }" {{ $attributes->merge(['class' => 'rounded-xl border border-line-2 bg-white transition-colors dark:bg-[#222528]']) }}>
    <button
        type="button"
        x-on:click="abierto = ! abierto"
        x-bind:aria-expanded="abierto ? 'true' : 'false'"
        x-bind:class="abierto ? 'rounded-t-xl' : 'rounded-xl'"
        class="flex w-full items-center justify-between gap-2 px-3 py-2.5 text-left text-[13px] font-semibold text-ink transition hover:bg-paper dark:hover:bg-white/5"
    >
        <span class="flex items-center gap-2">
            {{ $titulo }}
            @if ($count > 0)
                <span class="grid h-[18px] min-w-[18px] place-items-center rounded-full bg-orange-100 px-1 text-[10.5px] font-bold text-orange-600 dark:bg-white/10 dark:text-[#F7C59E]">{{ $count }}</span>
            @endif
        </span>
        <flux:icon.chevron-down class="size-4 flex-none text-gray-400 transition-transform duration-200" x-bind:class="abierto && 'rotate-180'" />
    </button>
    <div x-show="abierto" x-collapse x-cloak class="border-t border-line-2 px-3 pb-3 pt-3">
        {{ $slot }}
    </div>
</div>
