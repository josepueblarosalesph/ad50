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
        'cupones' => ['admin.cupones', 'Cupones'],
        'mensajes' => ['admin.mensajes', 'Mensajes'],
    ];

    // Exclusivas del superadministrador: al admin común no se le muestran los enlaces (y
    // los componentes igual los rechazan con 403). Los catálogos son la fuente de verdad
    // del matching —renombrar un término desalinea fichas, publicaciones y búsquedas— así
    // que se administran con el mismo criterio que las cuentas ajenas.
    if (auth()->user()?->esSuperadmin()) {
        $enlaces['catalogos'] = ['admin.catalogos', 'Catálogos'];
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
