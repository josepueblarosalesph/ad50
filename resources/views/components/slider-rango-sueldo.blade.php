@props([
    'min',
    'max',
    'desde',
    'hasta',
    'modelDesde' => 'sueldoMin',
    'modelHasta' => 'sueldoMax',
    'label' => 'Renta bruta mensual',
    // Distingue instancias del mismo deslizador (p. ej. el panel de escritorio y el de
    // móvil) para que no compartan wire:key.
    'clave' => null,
    'hideLabel' => false,
])

{{--
    Deslizador de dos topes para el rango de sueldo. Trabaja en MILLONES de pesos (un
    punto = un intervalo de $1.000.000), así el paso es entero y no hay que arrastrar
    millones en el input. El tope superior significa "o más".

    wire:ignore.self: el morph reescribiría x-data (desde/hasta cambian en cada render)
    y Alpine reiniciaría el scope dejando las directivas hijas apuntando al viejo.
--}}
<div
    wire:ignore.self
    wire:key="slider-sueldo-{{ $clave ?? $modelDesde }}"
    x-data="{
        min: @js((int) $min),
        max: @js((int) $max),
        desde: @js((int) $desde),
        hasta: @js((int) $hasta),
        get recorrido() { return this.max - this.min },
        get porcentajeDesde() { return (this.desde - this.min) * 100 / this.recorrido },
        get porcentajeHasta() { return (this.hasta - this.min) * 100 / this.recorrido },
        get filtrando() { return this.desde > this.min || this.hasta < this.max },
        pesos(millones) { return '$' + (millones * 1000000).toLocaleString('es-CL') },
        get etiqueta() {
            if (! this.filtrando) return 'Sin filtrar'
            if (this.desde >= this.max) return this.pesos(this.max) + ' o más'
            if (this.hasta >= this.max) return 'Desde ' + this.pesos(this.desde)
            if (this.desde === this.min) return 'Hasta ' + this.pesos(this.hasta)

            return this.pesos(this.desde) + ' a ' + this.pesos(this.hasta)
        },
        aplicarDesde() {
            this.desde = Math.min(this.desde, this.hasta)
            $wire.set('{{ $modelDesde }}', this.desde)
        },
        aplicarHasta() {
            this.hasta = Math.max(this.hasta, this.desde)
            $wire.set('{{ $modelHasta }}', this.hasta)
        },
    }"
>
    <div class="flex items-center justify-between gap-2 text-sm font-medium text-zinc-800 dark:text-white">
        <span id="rango-{{ $clave ?? $modelDesde }}-label" @class(['text-[13px]', 'sr-only' => $hideLabel])>{{ $label }}</span>
        <span class="ms-auto text-[12px] font-bold" x-text="etiqueta" x-bind:class="filtrando ? 'text-orange-600' : 'text-gray-500'"></span>
    </div>

    <div class="relative mt-4 h-5">
        <div class="absolute inset-x-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-line-2"></div>
        <div
            class="absolute top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-orange-500"
            x-bind:style="`left: ${porcentajeDesde}%; right: ${100 - porcentajeHasta}%`"
        ></div>

        <input
            type="range"
            class="ad-range-dual"
            min="{{ $min }}"
            max="{{ $max }}"
            step="1"
            x-model.number="desde"
            x-on:input="desde = Math.min(desde, hasta)"
            x-on:change="aplicarDesde()"
            {{-- Con ambos topes arriba, este input queda tapado; lo subimos para poder tomarlo. --}}
            x-bind:style="desde >= max ? 'z-index: 2' : ''"
            aria-labelledby="rango-{{ $clave ?? $modelDesde }}-label"
            aria-label="{{ $label }} — mínimo"
        />
        <input
            type="range"
            class="ad-range-dual"
            min="{{ $min }}"
            max="{{ $max }}"
            step="1"
            x-model.number="hasta"
            x-on:input="hasta = Math.max(hasta, desde)"
            x-on:change="aplicarHasta()"
            aria-labelledby="rango-{{ $clave ?? $modelDesde }}-label"
            aria-label="{{ $label }} — máximo"
        />
    </div>

    <div class="mt-1 flex justify-between text-[10.5px] font-bold text-gray-400">
        <span>Sin mínimo</span>
        <span>${{ number_format($max * 1000000, 0, ',', '.') }}+</span>
    </div>
</div>
