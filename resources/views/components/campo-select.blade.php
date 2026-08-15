@props(['label', 'id', 'error' => null])

{{--
    Select con etiqueta, para las barras de filtros y los formularios donde convive con
    <flux:input>.

    Por qué existe: al poner un <select> junto a un <flux:input> en una grilla, los dos
    controles no arrancaban a la misma altura ni tenían el mismo tamaño de texto.

    La clave está en que este proyecto NO deja que Flux mande en el tamaño de los
    formularios. En app.css, fuera de toda `@layer` (y por tanto por encima de cualquier
    utilidad de Tailwind), hay tres reglas que normalizan los controles para un público
    50+:

        [data-flux-control]:not(checkbox):not(switch) { min-height: 44px; font-size: 16px }
        [data-flux-label]                             { font-size: 15px; line-height: 1.45 }
        .dark [data-flux-control]...                  { fondo, borde y foco naranja }

    Los 44 px son el objetivo táctil accesible y los 16 px evitan que iOS haga zoom al
    enfocar. Por eso un <flux:input> mide 44 px y no los 40 de su propia clase `h-10`:
    la regla del proyecto le gana.

    De ahí que aquí se marque el <label> y el <select> con esos mismos atributos en vez
    de copiar los números en clases. Copiarlos es justo lo que produjo el desajuste: son
    dos sitios que hay que acordarse de mover a la vez. Con los atributos, cualquier
    cambio futuro en el tamaño de los formularios alcanza a este componente solo. Además
    no hay clase que valga para el tamaño: `text-[15px]` está interceptado más arriba en
    app.css y se reescribe a 16px/1.6.

    Se mantiene un <label for> nativo (y no <flux:label>, que emite un custom element
    <ui-label>) porque la asociación etiqueta-control tiene que seguir funcionando con un
    <select> normal, sin depender del JS de Flux. `inline-flex` no es capricho: <ui-label>
    es una caja en línea, así que su altura la infla el *strut* del contenedor (body va a
    18 px con line-height 1.6). Una etiqueta `block` mide menos y deja el control más
    arriba que el de Flux.

    `error` recibe el nombre del campo validado. El mensaje se pinta dentro del mismo <div>
    a propósito: en una grilla este <div> es la celda, y sacar el error fuera lo convertiría
    en una celda más y descuadraría la fila entera.
--}}

<div>
    <label for="{{ $id }}" data-flux-label class="mb-3 inline-flex items-center font-medium text-ink">{{ $label }}</label>

    <select
        id="{{ $id }}"
        data-flux-control
        {{ $attributes->class('w-full rounded-lg border border-line-2 bg-white px-3 font-semibold text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500 dark:bg-[#2A2D30]') }}
    >
        {{ $slot }}
    </select>

    @if ($error)
        @error($error)
            <p class="mt-1.5 text-[12.5px] font-semibold text-[#A93226] dark:text-red-400">{{ $message }}</p>
        @enderror
    @endif
</div>
