<div id="filtros-busqueda" class="pb-5 pr-1">
    <div class="mb-3 flex items-center justify-between gap-2 px-1">
        <span class="text-[11px] font-bold uppercase tracking-[0.12em] text-gray-400">Filtros del proceso</span>
        <span wire:loading class="text-[11px] font-bold text-orange-600">Actualizando…</span>
    </div>
    <div class="space-y-2">
        @foreach ($grupos as [$label, $model, $campo])
            <x-filtro-acordeon :titulo="$label" :count="count((array) ($$model ?? []))">
                <livewire:empresa.selector-criterio wire:model="{{ $model }}" campo="{{ $campo }}" etiqueta="{{ $label }}" :mostrar-etiqueta="false" :criterios="$criteriosActuales" wire:key="filtro-sel-{{ $model }}" />
            </x-filtro-acordeon>
        @endforeach

        <x-filtro-acordeon titulo="Especialidad o mención" :count="filled($especialidad) ? 1 : 0">
            <flux:input wire:model.live.debounce.500ms="especialidad" placeholder="Ej. Finanzas corporativas" />
        </x-filtro-acordeon>

        <x-filtro-acordeon titulo="Institución de estudio" :count="filled($institucion) ? 1 : 0">
            <x-combobox model="institucion" label="Institución de estudio" :hide-label="true" :opciones="$instituciones" :valor="$institucion" placeholder="Escribe para buscar" />
        </x-filtro-acordeon>

        <x-filtro-acordeon titulo="Empresa" :count="filled($empresa) ? 1 : 0">
            <x-combobox model="empresa" label="Empresa" :hide-label="true" :opciones="$empresas" :valor="$empresa" placeholder="Escribe para buscar" />
        </x-filtro-acordeon>

        <x-filtro-acordeon titulo="Años de experiencia" :count="($expMin > $limitesExperiencia['min'] || $expMax < $limitesExperiencia['max']) ? 1 : 0">
            <x-slider-rango-edad label="Años de experiencia" :hide-label="true" :min="$limitesExperiencia['min']" :max="$limitesExperiencia['max']" :desde="$expMin" :hasta="$expMax" model-desde="expMin" model-hasta="expMax" />
        </x-filtro-acordeon>

        <x-filtro-acordeon titulo="Expectativa de renta" :count="$rentaMax > 0 ? 1 : 0">
            <div class="flex items-center justify-end">
                <span @class(['text-[12px] font-bold', 'text-orange-600' => $rentaMax > 0, 'text-gray-500' => $rentaMax === 0])>{{ $rentaMax > 0 ? '$'.number_format($rentaMax, 0, ',', '.').' o menos' : 'Sin filtrar' }}</span>
            </div>
            <input
                id="renta-max"
                type="range"
                wire:model.live.debounce.400ms="rentaMax"
                min="0"
                max="8000000"
                step="200000"
                class="mt-3 w-full accent-orange-500"
            />
            <div class="mt-1 flex justify-between text-[10.5px] font-bold text-gray-400"><span>Sin filtrar</span><span>$8.000.000</span></div>
        </x-filtro-acordeon>

        <x-filtro-acordeon titulo="Rango de edad" :count="($edadMin > $limitesEdad['min'] || $edadMax < $limitesEdad['max']) ? 1 : 0">
            <x-slider-rango-edad
                :hide-label="true"
                :min="$limitesEdad['min']"
                :max="$limitesEdad['max']"
                :desde="$edadMin"
                :hasta="$edadMax"
            />
        </x-filtro-acordeon>

        <x-filtro-acordeon titulo="Palabras clave" :count="count($palabrasClave)">
            <x-palabras-clave :palabras="$palabrasClave" :hide-label="true" />
        </x-filtro-acordeon>

        <p class="px-1 text-[11.5px] leading-relaxed text-gray-500">Los resultados se actualizan a medida que cambias los filtros, pero solo quedan guardados al usar el botón Guardar filtro.</p>
    </div>

    {{-- Barra de guardado: los cambios solo se persisten aquí. --}}
    <div class="sticky bottom-0 z-10 -mx-1 mt-3 border-t border-line bg-white/95 px-1 pb-1 pt-3 backdrop-blur dark:bg-[#1B1E20]/95">
        @if ($sinGuardar)
            <p class="mb-2 flex items-center gap-1.5 text-[11.5px] font-bold text-orange-600">
                <flux:icon.exclamation-circle class="size-4" />
                Cambios sin guardar
            </p>
        @endif
        <div class="flex items-center gap-2">
            <button
                type="button"
                wire:click="guardar"
                wire:loading.attr="disabled"
                wire:target="guardar"
                @disabled(! $sinGuardar)
                class="ad-btn-primary ad-btn-sm flex-1 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="guardar">Guardar filtro</span>
                <span wire:loading wire:target="guardar">Guardando…</span>
            </button>
            @if ($sinGuardar)
                <button type="button" wire:click="descartar" wire:loading.attr="disabled" wire:target="descartar" class="ad-btn-ghost ad-btn-sm">Descartar</button>
            @endif
        </div>
    </div>
</div>
