@props([
    'campo',
    'orden',
    'direccion' => 'asc',
    // Método del componente Livewire que recibe el campo.
    'accion' => 'ordenarPor',
])

@php($activo = $orden === $campo)

{{-- Encabezado de columna ordenable. La flecha solo se pinta en la columna activa;
     el resto muestra un indicador tenue que aparece al pasar el mouse, para no
     ensuciar la fila con seis flechas compitiendo. --}}
<th {{ $attributes->merge(['class' => 'p-4']) }} aria-sort="{{ $activo ? ($direccion === 'asc' ? 'ascending' : 'descending') : 'none' }}">
    <button
        type="button"
        wire:click="{{ $accion }}('{{ $campo }}')"
        @class(['ad-th-orden group', 'is-active' => $activo])
        title="Ordenar por {{ Str::lower($slot) }}"
    >
        {{ $slot }}
        @if ($activo)
            <flux:icon :name="$direccion === 'asc' ? 'chevron-up' : 'chevron-down'" class="size-3.5 flex-none text-orange-600" />
        @else
            <flux:icon.chevron-up-down class="size-3.5 flex-none text-gray-300 transition group-hover:text-gray-500 dark:text-gray-600" />
        @endif
    </button>
</th>
