@props(['activo' => null])

{{--
    Menú superior del panel de postulante. Fuente única, igual que <x-nav-empresa>:
    `activo` recibe la clave de la sección actual (panel, ficha, busquedas,
    postulaciones); null no resalta ninguna.
--}}

@php
    $enlaces = [
        'panel' => ['postulante.panel', 'Mi panel'],
        'ficha' => ['postulante.ficha', 'Mi perfil'],
        'busquedas' => ['postulante.busquedas', 'Oportunidades'],
        'postulaciones' => ['postulante.postulaciones', 'Mis postulaciones'],
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
