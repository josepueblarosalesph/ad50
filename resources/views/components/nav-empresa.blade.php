@props(['activo' => null])

{{--
    Menú superior del panel de empresa. Fuente única: agregar una sección aquí la
    publica en todas las vistas a la vez. `activo` recibe la clave de la sección
    actual (panel, busquedas, publicaciones, equipo); null no resalta ninguna.
--}}

@php
    $enlaces = [
        'panel' => ['empresa.panel', 'Mi Panel'],
        'busquedas' => ['empresa.busquedas.index', 'Talent Finder'],
        'favoritos' => ['empresa.favoritos', 'Mis favoritos'],
        'publicaciones' => ['empresa.publicaciones.index', 'Publicaciones'],
    ];

    // Solo el contacto administrador gestiona el equipo (Equipo::mount() aborta 403 al resto).
    if (auth()->user()?->esPrincipalEmpresa()) {
        $enlaces['equipo'] = ['empresa.equipo', 'Equipo'];
    }
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
