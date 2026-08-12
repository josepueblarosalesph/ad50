{{-- Raíz propia del componente: es lo que hace que los wire:click de dentro funcionen
     estando en el slot `sidebar`, que el layout pinta fuera del componente padre. --}}
<div>
    @include('livewire.empresa.partials.carpetas-favoritos', [
        'prefijo' => $prefijo ?? 'panel',
        'carpeta' => $activa,
        'carpetas' => $carpetas,
    ])
</div>
