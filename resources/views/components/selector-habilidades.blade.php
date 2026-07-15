@props([
    'habilidades' => [],
    'sugerencias' => [],
    'buscar' => '',
    'max' => 10,
])

<div>
    <div class="relative">
        <flux:input
            wire:model.live.debounce.300ms="buscarHabilidad"
            wire:keydown.enter.prevent="agregarPrimeraHabilidad"
            label="Habilidades"
            maxlength="80"
            autocomplete="off"
            placeholder="Busca un software, herramienta o competencia"
            :description="'¿Qué habilidades te gustaría destacar? (selecciona hasta '.$max.').'"
            :disabled="count($habilidades) >= $max"
        />

        @if (filled($sugerencias))
            <ul class="absolute inset-x-0 top-full z-30 mt-1 max-h-64 overflow-y-auto rounded-xl border border-line-2 bg-white py-1 shadow-xl dark:border-[#5A5F64] dark:bg-[#222528]" role="listbox">
                @foreach ($sugerencias as $sugerencia)
                    <li>
                        <button
                            type="button"
                            wire:key="sug-{{ $loop->index }}"
                            wire:click="agregarHabilidad({{ \Illuminate\Support\Js::from($sugerencia) }})"
                            class="block w-full px-3 py-2 text-left text-[13px] text-ink transition hover:bg-orange-100 hover:text-orange-700 dark:text-gray-200 dark:hover:bg-white/10 dark:hover:text-[#F7C59E]"
                        >{{ $sugerencia }}</button>
                    </li>
                @endforeach
            </ul>
        @elseif (mb_strlen(trim($buscar)) >= 2)
            <div class="absolute inset-x-0 top-full z-30 mt-1 rounded-xl border border-line-2 bg-white px-3 py-2 text-[13px] text-gray-500 shadow-xl dark:border-[#5A5F64] dark:bg-[#222528]">Sin coincidencias en el catálogo.</div>
        @endif
    </div>

    @if (filled($habilidades))
        <div class="mt-2 flex flex-wrap gap-1.5">
            @foreach ($habilidades as $index => $habilidad)
                <span class="ad-chip ad-chip-orange gap-1 pr-1" wire:key="hab-{{ $index }}">
                    {{ $habilidad }}
                    <button
                        type="button"
                        wire:click="quitarHabilidad({{ $index }})"
                        class="rounded-full p-0.5 transition hover:bg-orange-200 dark:hover:bg-white/10"
                        aria-label="Quitar la habilidad {{ $habilidad }}"
                    ><flux:icon.x-mark class="size-3" /></button>
                </span>
            @endforeach
        </div>
    @endif

    <flux:error name="habilidades" />
</div>
