<div class="pb-5 pr-1">
    <div class="mb-3 flex items-center justify-between gap-2 px-1">
        <span class="text-[11px] font-bold uppercase tracking-[0.12em] text-gray-400">Filtrar postulantes</span>
        <span wire:loading class="text-[11px] font-bold text-orange-600">Filtrando…</span>
    </div>
    <div class="space-y-2">
        @foreach ($grupos as [$label, $model, $campo])
            <x-filtro-acordeon :titulo="$label" :count="count((array) ($$model ?? []))">
                <livewire:empresa.selector-criterio wire:model="{{ $model }}" campo="{{ $campo }}" etiqueta="{{ $label }}" :mostrar-etiqueta="false" :criterios="$criteriosActuales" wire:key="post-sel-{{ $model }}" />
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

        <x-filtro-acordeon titulo="Expectativa de renta" :count="($rentaMin > $limitesRenta['min'] || $rentaMax < $limitesRenta['max']) ? 1 : 0">
            <x-slider-rango-sueldo
                clave="postulaciones"
                :hide-label="true"
                :min="$limitesRenta['min']"
                :max="$limitesRenta['max']"
                :desde="$rentaMin"
                :hasta="$rentaMax"
                model-desde="rentaMin"
                model-hasta="rentaMax"
            />
        </x-filtro-acordeon>

        <x-filtro-acordeon titulo="Rango de edad" :count="($edadMin > $limitesEdad['min'] || $edadMax < $limitesEdad['max']) ? 1 : 0">
            <x-slider-rango-edad :hide-label="true" :min="$limitesEdad['min']" :max="$limitesEdad['max']" :desde="$edadMin" :hasta="$edadMax" />
        </x-filtro-acordeon>

        <x-filtro-acordeon titulo="Palabras clave" :count="count($palabrasClave)">
            <x-palabras-clave :palabras="$palabrasClave" :hide-label="true" />
        </x-filtro-acordeon>
    </div>

    <div class="mt-3 border-t border-line pt-3">
        <button type="button" wire:click="limpiar" class="ad-btn-ghost ad-btn-sm w-full justify-center">Limpiar filtros</button>
    </div>
</div>
