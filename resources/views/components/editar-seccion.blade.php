@props(['publicacion', 'seccion', 'ancla' => null])

{{--
    Botón "Editar" de una tarjeta del detalle de la publicación. Lleva al
    formulario de edición y salta directo a la sección equivalente mediante el
    ancla, así la empresa no tiene que buscarla dentro del formulario completo.
    Sin wire:navigate: la navegación normal del navegador es la que respeta el #.
--}}
<a
    href="{{ route('empresa.publicaciones.edit', $publicacion).($ancla ? '#'.$ancla : '') }}"
    class="ad-btn-ghost ad-btn-sm flex-none whitespace-nowrap text-[14px]"
    aria-label="Editar {{ $seccion }}"
>
    <flux:icon.pencil-square class="size-4" />Editar
</a>
