@props([
    'model',
    'label',
    'opciones' => [],
    'valor' => '',
    'placeholder' => 'Escribe para buscar',
    'error' => null,
])

<div
    class="relative"
    x-data="{
        abierto: false,
        indice: -1,
        consulta: @js($valor),
        opciones: @js(array_values($opciones)),
        normalizar(texto) {
            return texto.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase().trim()
        },
        get filtradas() {
            const consulta = this.normalizar(this.consulta)

            if (consulta === '') return this.opciones.slice(0, 50)

            return this.opciones.filter((opcion) => this.normalizar(opcion).includes(consulta)).slice(0, 50)
        },
        elegir(opcion) {
            this.consulta = opcion
            this.abierto = false
            this.indice = -1
            $wire.set('{{ $model }}', opcion)
        },
        limpiar() {
            this.consulta = ''
            this.abierto = false
            this.indice = -1
            $wire.set('{{ $model }}', '')
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
        <div class="relative">
            <input
                type="text"
                x-model="consulta"
                x-on:focus="abierto = true"
                x-on:input="abierto = true; indice = -1"
                x-on:keydown.arrow-down.prevent="mover(1)"
                x-on:keydown.arrow-up.prevent="mover(-1)"
                x-on:keydown.enter.prevent="indice >= 0 ? elegir(filtradas[indice]) : (filtradas.length === 1 && elegir(filtradas[0]))"
                data-flux-control
                placeholder="{{ $placeholder }}"
                autocomplete="off"
                role="combobox"
                aria-autocomplete="list"
                x-bind:aria-expanded="abierto"
                class="w-full rounded-lg border border-line-2 bg-white py-2 pl-3 pr-9 text-[14px] text-ink placeholder:text-gray-400 focus:border-orange-400 focus:outline-none dark:bg-[#222528]"
            />
            <button
                type="button"
                x-show="consulta !== ''"
                x-on:click="limpiar()"
                class="absolute inset-y-0 right-0 flex items-center px-2.5 text-gray-500 transition hover:text-ink"
                aria-label="Limpiar {{ $label }}"
            ><flux:icon.x-mark class="size-4" /></button>
        </div>
        @if ($error)
            <flux:error :name="$error" />
        @endif
    </flux:field>

    <ul
        x-show="abierto"
        x-cloak
        class="absolute left-0 z-30 mt-1 max-h-64 w-[min(26rem,80vw)] min-w-full overflow-y-auto rounded-xl border border-line-2 bg-white py-1 shadow-xl dark:border-[#5A5F64] dark:bg-[#222528]"
        role="listbox"
    >
        <template x-for="(opcion, i) in filtradas" :key="opcion">
            <li>
                <button
                    type="button"
                    x-on:click="elegir(opcion)"
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
        <li x-show="filtradas.length === 0" class="px-3 py-2 text-[13px] text-gray-500">Sin coincidencias.</li>
    </ul>
</div>
