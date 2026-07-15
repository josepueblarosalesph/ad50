@props([
    'model',
    'label',
    'opciones' => [],
    'seleccion' => [],
    'placeholder' => 'Escribe para buscar',
    'error' => null,
    'descripcion' => null,
    'vacio' => 'Sin coincidencias.',
])

{{-- Combobox de selección múltiple: busca en el catálogo y agrega chips.
     Server-authoritative: cada render rebaquea la selección y las opciones desde Livewire
     (permite que criterios dependientes —p. ej. especialidad según carrera— reaccionen). --}}
<div
    wire:key="multicombo-{{ $model }}"
    class="relative"
    x-data="{
        abierto: false,
        indice: -1,
        consulta: '',
        opciones: @js(array_values($opciones)),
        seleccion: @js(array_values($seleccion)),
        normalizar(texto) {
            return texto.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase().trim()
        },
        get filtradas() {
            const consulta = this.normalizar(this.consulta)

            return this.opciones
                .filter((opcion) => ! this.seleccion.includes(opcion))
                .filter((opcion) => consulta === '' || this.normalizar(opcion).includes(consulta))
                .slice(0, 50)
        },
        agregar(opcion) {
            if (opcion === undefined) return
            if (! this.seleccion.includes(opcion)) this.seleccion.push(opcion)
            this.consulta = ''
            this.abierto = false
            this.indice = -1
            $wire.set('{{ $model }}', this.seleccion)
        },
        quitar(opcion) {
            this.seleccion = this.seleccion.filter((valor) => valor !== opcion)
            $wire.set('{{ $model }}', this.seleccion)
        },
        mover(paso) {
            const total = this.filtradas.length
            if (total === 0) return
            this.abierto = true
            this.indice = (this.indice + paso + total) % total
        },
    }"
    x-on:click.outside="abierto = false"
    x-on:keydown.escape="abierto = false"
>
    <flux:field>
        <flux:label>{{ $label }}</flux:label>
        @if ($descripcion)
            <flux:description>{{ $descripcion }}</flux:description>
        @endif

        <div x-show="seleccion.length" x-cloak class="mb-2 flex flex-wrap gap-1.5">
            <template x-for="opcion in seleccion" :key="opcion">
                <span class="ad-chip ad-chip-orange gap-1 pr-1">
                    <span x-text="opcion"></span>
                    <button type="button" x-on:click="quitar(opcion)" class="rounded-full p-0.5 transition hover:bg-orange-200 dark:hover:bg-white/10" aria-label="Quitar">
                        <flux:icon.x-mark class="size-3" />
                    </button>
                </span>
            </template>
        </div>

        <div class="relative">
            <input
                type="text"
                x-model="consulta"
                x-on:focus="abierto = true"
                x-on:input="abierto = true; indice = -1"
                x-on:keydown.arrow-down.prevent="mover(1)"
                x-on:keydown.arrow-up.prevent="mover(-1)"
                x-on:keydown.enter.prevent="indice >= 0 ? agregar(filtradas[indice]) : (filtradas.length === 1 && agregar(filtradas[0]))"
                data-flux-control
                placeholder="{{ $placeholder }}"
                autocomplete="off"
                role="combobox"
                aria-autocomplete="list"
                x-bind:aria-expanded="abierto"
                class="w-full rounded-lg border border-line-2 bg-white py-2 pl-3 pr-3 text-[14px] text-ink placeholder:text-gray-400 focus:border-orange-400 focus:outline-none dark:bg-[#222528]"
            />
        </div>

        @if ($error)
            <flux:error :name="$error" />
        @endif
    </flux:field>

    {{-- wire:ignore: sin esto el morph elimina los <li> que genera el x-for. --}}
    <ul
        wire:ignore
        x-show="abierto"
        x-cloak
        class="absolute left-0 z-30 mt-1 max-h-64 w-[min(26rem,80vw)] min-w-full overflow-y-auto rounded-xl border border-line-2 bg-white py-1 shadow-xl dark:border-[#5A5F64] dark:bg-[#222528]"
        role="listbox"
    >
        <template x-for="(opcion, i) in filtradas" :key="opcion">
            <li>
                <button
                    type="button"
                    x-on:click="agregar(opcion)"
                    x-on:mouseenter="indice = i"
                    x-bind:class="indice === i
                        ? 'bg-orange-100 text-orange-700 dark:bg-white/10 dark:text-[#F7C59E]'
                        : 'text-ink dark:text-gray-200'"
                    class="block w-full px-3 py-2 text-left text-[13px] transition"
                    x-text="opcion"
                    role="option"
                    x-bind:aria-selected="indice === i"
                ></button>
            </li>
        </template>
        <li x-show="filtradas.length === 0" class="px-3 py-2 text-[13px] text-gray-500">{{ $vacio }}</li>
    </ul>
</div>
