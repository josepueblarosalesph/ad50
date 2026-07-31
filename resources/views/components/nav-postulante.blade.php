@props(['activo' => null])

{{--
    Menú superior del postulante. Fuente única, igual que <x-nav-empresa>: `activo`
    recibe la clave de la sección actual (busquedas, postulaciones, ficha); null no
    resalta ninguna. Oportunidades va primero: es la pantalla de entrada.
--}}

@php
    $enlaces = [
        'busquedas' => ['postulante.busquedas', 'Oportunidades'],
        'postulaciones' => ['postulante.postulaciones', 'Mis postulaciones'],
        'ficha' => ['postulante.ficha', 'Mi perfil'],
    ];
@endphp

@foreach ($enlaces as $clave => [$ruta, $etiqueta])
    <a
        wire:navigate
        href="{{ route($ruta) }}"
        @class([
            'rounded-lg px-3.5 py-2 text-[13.5px] font-semibold transition',
            'bg-orange-100 text-ink' => $activo === $clave,
            'text-gray-500 hover:text-ink' => $activo !== $clave,
        ])
        @if ($activo === $clave) aria-current="page" @endif
    >{{ $etiqueta }}</a>
@endforeach
