@props([
    'model',
    'label',
    'catalogo' => null,
    'opciones' => [],
    'valor' => '',
    'placeholder' => 'Escribe para buscar',
    'error' => null,
    'hideLabel' => false,
    'fijo' => null,
    'fijoAyuda' => null,
])

{{-- `fijo` es la opción de escape del catálogo («Otros» en cargos, «Otra» en empresas).
     Se muestra siempre, al pie de la lista, aunque lo escrito no coincida con nada: con
     30.000 cargos es normal que el propio no esté, y sin esto la lista respondía «Sin
     coincidencias» y la persona quedaba sin salida, teniendo que adivinar que hay que
     borrar y escribir «Otros» para que aparezca el campo de texto libre. Ponerlo solo
     donde existe ese campo: si no, se elige un valor que nadie recoge. --}}

{{-- Con `catalogo` las opciones se descargan de su propia URL en vez de viajar dentro
     del HTML. Importa: cargos son 30.000 valores, o sea 733 KB por instancia, repetidos
     en cada respuesta de Livewire. Así se baja a una descarga que el navegador cachea.
     `opciones` sigue existiendo para listas cortas, donde incrustarlas no duele. --}}
@php($urlCatalogo = $catalogo === null ? null : route('catalogos.mostrar', $catalogo).'?v='.\App\Support\CatalogosProfesionales::version($catalogo))

{{-- wire:ignore.self: si el morph reescribe x-data (porque $valor cambió), Alpine reinicia el scope
     y las directivas hijas quedan apuntando al scope viejo. Los hijos sí se siguen morfeando. --}}
<div
    wire:ignore.self
    wire:key="combobox-{{ $model }}"
    @if ($catalogo !== null) data-catalogo="{{ $catalogo }}" @endif
    class="relative"
    x-data="{
        abierto: false,
        indice: -1,
        consulta: @js($valor),
        opciones: @js(array_values($opciones)),
        fijo: @js($fijo),
        cargando: @js($urlCatalogo !== null),
        async init() {
            if (@js($urlCatalogo) === null) return

            // Una sola descarga por catálogo y por página, compartida por todas las
            // instancias: sin esto, cinco experiencias pedirían cinco veces lo mismo.
            window.catalogosAd50 ??= {}
            window.catalogosAd50[@js($urlCatalogo)] ??= fetch(@js($urlCatalogo), { headers: { Accept: 'application/json' } })
                .then((respuesta) => respuesta.ok ? respuesta.json() : [])
                .catch(() => [])

            this.opciones = await window.catalogosAd50[@js($urlCatalogo)]
            this.cargando = false
        },
        normalizar(texto) {
            return texto.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase().trim()
        },
        get filtradas() {
            const consulta = this.normalizar(this.consulta)

            const coincidencias = consulta === ''
                ? this.opciones
                : this.opciones.filter((opcion) => this.normalizar(opcion).includes(consulta))

            // La fijada se dibuja aparte, al pie: fuera de aquí para no listarla dos veces.
            return coincidencias.filter((opcion) => opcion !== this.fijo).slice(0, 50)
        },
        /** Lo que se ve, en orden: las coincidencias y, al final, la opción de escape. */
        get visibles() {
            return this.fijo === null ? this.filtradas : [...this.filtradas, this.fijo]
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
            const total = this.visibles.length
            if (total === 0) return
            this.abierto = true
            this.indice = (this.indice + paso + total) % total
        },
    }"
    x-on:click.outside="abierto = false"
    x-on:keydown.escape="abierto = false"
    {{-- El texto visible vive en Alpine y el x-data no se vuelve a evaluar (wire:ignore.self
         lo impide a propósito), así que un valor puesto desde el servidor —el autollenado
         desde el CV, por ejemplo— no se vería. Con este evento el componente se resincroniza
         cuando el servidor avisa que cambió algo. --}}
    x-on:sincronizar-comboboxes.window="consulta = $wire.get(@js($model)) ?? ''"
>
    <flux:field>
        @unless ($hideLabel)<flux:label>{{ $label }}</flux:label>@endunless
        <div class="relative">
            <input
                type="text"
                x-model="consulta"
                x-on:focus="abierto = true"
                x-on:input="abierto = true; indice = -1"
                x-on:keydown.arrow-down.prevent="mover(1)"
                x-on:keydown.arrow-up.prevent="mover(-1)"
                x-on:keydown.enter.prevent="indice >= 0 ? elegir(visibles[indice]) : (filtradas.length === 1 && elegir(filtradas[0]))"
                data-flux-control
                placeholder="{{ $placeholder }}"
                autocomplete="off"
                role="combobox"
                aria-autocomplete="list"
                x-bind:aria-expanded="abierto"
                class="w-full rounded-lg border border-line-2 bg-white py-2 pl-3 pr-9 text-[14px] text-ink placeholder:text-gray-400 focus:border-orange-400 focus:outline-none dark:bg-[#222528]"
            />
            {{-- wire:ignore: el morph de Livewire borra el display:none que pone x-show. --}}
            <button
                wire:ignore
                type="button"
                x-show="consulta !== ''"
                x-cloak
                x-on:click="limpiar()"
                class="absolute inset-y-0 right-0 flex items-center px-2.5 text-gray-500 transition hover:text-ink"
                aria-label="Limpiar {{ $label }}"
            ><flux:icon.x-mark class="size-4" /></button>
        </div>
        @if ($error)
            <flux:error :name="$error" />
        @endif
    </flux:field>

    {{-- wire:ignore: sin esto el morph elimina los <li> que genera el x-for y la lista queda vacía. --}}
    <ul
        wire:ignore
        x-show="abierto"
        x-cloak
        {{-- `w-max` hace que la lista se ajuste a la opción más larga en vez de partirla,
             acotada por `max-w` para no desbordar la ventana; `min-w-full` evita que quede
             más angosta que el campo. --}}
        class="absolute left-0 z-30 mt-1 max-h-64 w-max min-w-full max-w-[min(38rem,90vw)] overflow-y-auto rounded-xl border border-line-2 bg-white py-1 shadow-xl dark:border-[#5A5F64] dark:bg-[#222528]"
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
                    {{-- Si aun así una opción excede el ancho, se envuelve en vez de cortarse. --}}
                    class="block w-full break-words px-3 py-2 text-left text-[13px] leading-snug transition"
                    x-text="opcion"
                    role="option"
                    x-bind:aria-selected="indice === i"
                ></button>
            </li>
        </template>
        <li x-show="cargando" class="px-3 py-2 text-[13px] text-gray-500">Cargando opciones…</li>
        <li x-show="! cargando && filtradas.length === 0" class="px-3 py-2 text-[13px] text-gray-500">Sin coincidencias.</li>

        {{-- La opción de escape, siempre al pie y separada, para que no se pierda entre
             las coincidencias ni desaparezca cuando no hay ninguna. Va dentro de
             `visibles`, así que el teclado la alcanza como a cualquier otra. --}}
        @if ($fijo !== null)
            {{-- `sticky`: la lista muestra hasta 50 coincidencias con scroll, así que al pie
                 «normal» habría que bajar para verla. Pegada al borde inferior está a la
                 vista siempre, que es de lo que se trata. --}}
            <li x-show="! cargando" class="sticky bottom-0 z-10 mt-1 border-t border-line bg-white pt-1 dark:border-[#5A5F64] dark:bg-[#222528]">
                <button
                    type="button"
                    x-on:click="elegir(fijo)"
                    x-on:mouseenter="indice = filtradas.length"
                    x-bind:class="indice === filtradas.length
                        ? 'bg-orange-100 text-orange-700 dark:bg-white/10 dark:text-[#F7C59E]'
                        : 'text-ink dark:text-gray-200'"
                    class="block w-full break-words px-3 py-2 text-left text-[13px] leading-snug transition"
                    role="option"
                    x-bind:aria-selected="indice === filtradas.length"
                >
                    <span class="font-semibold" x-text="fijo"></span>
                    @if ($fijoAyuda !== null)
                        <span class="mt-0.5 block text-[12px] font-normal text-gray-500">{{ $fijoAyuda }}</span>
                    @endif
                </button>
            </li>
        @endif
    </ul>
</div>
