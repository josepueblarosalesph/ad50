@props(['variante' => 'topbar'])

{{--
    Conmutador entre el panel de administración y el de empresa.

    Solo existe para quien tiene los dos accesos: un admin (o superadmin) con una empresa
    asociada, situación que nace de promover a admin a un contacto de empresa —el cambio
    de rol conserva su empresa (ver Admin\Usuarios::cambiarRol)—. Para todos los demás no
    se dibuja nada.

    El botón siempre ofrece el panel en el que NO se está, deducido de la ruta actual y no
    del slot `context`, que es texto libre. Fuera de ambos panels (Mi cuenta, Ayuda,
    Configuración) se ofrece el de empresa: al de administración ya se llega por el menú.

    El destino de empresa sale de User::rutaPanelEmpresa(), el mismo que usa el login, así
    que respeta el gating de EnsureEmpresaActiva: si a la empresa le falta plan o
    antecedentes, el botón lleva a completarlos en vez de a un redirect encadenado.

    `variante`: 'topbar' para la barra superior, 'menu' para el desplegable móvil.
--}}

@php
    $usuario = auth()->user();
    $tieneAmbos = ($usuario?->esAdmin() ?? false) && $usuario->esEmpresa();

    if ($tieneAmbos) {
        $enEmpresa = request()->routeIs('empresa.*');

        $destino = $enEmpresa ? route('admin.panel') : route($usuario->rutaPanelEmpresa());
        $etiqueta = $enEmpresa ? 'Panel de administración' : 'Panel de empresa';
        $icono = $enEmpresa ? 'shield-check' : 'building-office-2';
    }
@endphp

@if ($tieneAmbos)
    @if ($variante === 'menu')
        <a href="{{ $destino }}" wire:navigate>
            <flux:icon :icon="$icono" class="mr-2 size-4" />Ir al {{ mb_strtolower($etiqueta) }}
        </a>
    @else
        <a
            href="{{ $destino }}"
            wire:navigate
            class="hidden items-center gap-2 rounded-lg border border-line-2 px-3 py-2 text-[13px] font-bold text-gray-600 transition hover:border-orange-300 hover:bg-orange-50 hover:text-orange-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500 md:inline-flex dark:text-gray-300 dark:hover:bg-white/10"
            title="Cambiar al {{ mb_strtolower($etiqueta) }}"
        >
            <flux:icon :icon="$icono" class="size-4" />
            <span class="hidden lg:inline">{{ $etiqueta }}</span>
            <span class="lg:hidden">{{ $enEmpresa ? 'Admin' : 'Empresa' }}</span>
        </a>
    @endif
@endif
