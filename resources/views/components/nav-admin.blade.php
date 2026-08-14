@props(['activo' => null])

{{--
    Menú superior de administración. Fuente única, igual que <x-nav-empresa> y
    <x-nav-postulante>: `activo` recibe la clave de la sección actual y null no
    resalta ninguna (las pantallas de cuenta, por ejemplo).
--}}

@php
    $enlaces = [
        'panel' => ['admin.panel', 'Resumen'],
        'empresas' => ['admin.empresas', 'Empresas'],
        'postulantes' => ['admin.postulantes', 'Postulantes'],
        'planes' => ['admin.planes', 'Planes'],
        'catalogos' => ['admin.catalogos', 'Catálogos'],
        'mensajes' => ['admin.mensajes', 'Mensajes'],
    ];

    // Gestionar cuentas ajenas es exclusivo del superadministrador: al admin común no
    // se le muestra el enlace (y el componente igual lo rechaza con 403).
    if (auth()->user()?->esSuperadmin()) {
        $enlaces['usuarios'] = ['admin.usuarios', 'Usuarios'];
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
