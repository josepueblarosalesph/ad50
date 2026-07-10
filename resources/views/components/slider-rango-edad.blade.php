@props([
    'min',
    'max',
    'desde',
    'hasta',
    'modelDesde' => 'edadMin',
    'modelHasta' => 'edadMax',
])

{{-- wire:ignore.self: el morph reescribiría x-data (desde/hasta cambian en cada render) y Alpine
     reiniciaría el scope dejando las directivas hijas apuntando al viejo. --}}
<div
    wire:ignore.self
    wire:key="slider-edad-{{ $modelDesde }}"
    x-data="{
        min: @js((int) $min),
        max: @js((int) $max),
        desde: @js((int) $desde),
        hasta: @js((int) $hasta),
        get recorrido() { return this.max - this.min },
        get porcentajeDesde() { return (this.desde - this.min) * 100 / this.recorrido },
        get porcentajeHasta() { return (this.hasta - this.min) * 100 / this.recorrido },
        get filtrando() { return this.desde > this.min || this.hasta < this.max },
        get etiqueta() {
            if (! this.filtrando) return 'Sin filtrar'

            return this.desde + ' a ' + (this.hasta >= this.max ? this.max + '+' : this.hasta) + ' años'
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
    <div class="flex items-center justify-between gap-2 text-[13px] font-bold text-ink">
        <span id="rango-edad-label">Rango de edad</span>
        <span class="text-[12px] font-bold" x-text="etiqueta" x-bind:class="filtrando ? 'text-orange-600' : 'text-gray-500'"></span>
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
            {{-- Con ambos thumbs en el tope, el input de arriba tapa a este; lo subimos para poder tomarlo. --}}
            x-bind:style="desde >= max ? 'z-index: 2' : ''"
            aria-labelledby="rango-edad-label"
            aria-label="Edad mínima"
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
            aria-labelledby="rango-edad-label"
            aria-label="Edad máxima"
        />
    </div>

    <div class="mt-1 flex justify-between text-[10.5px] font-bold text-gray-400">
        <span>{{ $min }} años</span>
        <span>{{ $max }}+ años</span>
    </div>
</div>
