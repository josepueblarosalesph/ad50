@props(['activo' => null, 'publicacion' => null])

{{--
    Menú lateral de la sección Publicaciones, equivalente al de Prospección de
    Candidatos. `activo` recibe la clave del ítem actual (todas, nueva, editar).
    Al editar, el segundo ítem apunta a esa publicación en vez de al formulario
    en blanco, igual que en el menú de búsquedas.
--}}

@php
    $editando = $activo === 'editar' && $publicacion !== null;
    $puedePublicar = auth()->user()?->empresa?->puedePublicar() ?? false;

    $clases = fn (bool $activa): string => $activa
        ? 'flex items-center gap-3 rounded-[10px] bg-orange-100 px-3 py-2.5 text-[14px] font-semibold text-orange-600'
        : 'flex items-center gap-3 rounded-[10px] px-3 py-2.5 text-[14px] font-semibold text-gray-700 hover:bg-paper';
@endphp

<div class="mb-2 px-2.5 text-[12px] font-bold uppercase tracking-[0.12em] text-gray-400">Publicaciones</div>

<a
    wire:navigate
    href="{{ route('empresa.publicaciones.index') }}"
    class="{{ $clases($activo === 'todas') }}"
    @if ($activo === 'todas') aria-current="page" @endif
><flux:icon.bars-3 class="size-[18px]" />Todas las publicaciones</a>

@if ($editando)
    <a
        href="{{ route('empresa.publicaciones.edit', $publicacion) }}"
        class="{{ $clases(true) }}"
        aria-current="page"
    ><flux:icon.pencil-square class="size-[18px]" />Editar publicación</a>
@elseif ($puedePublicar)
    <a
        wire:navigate
        href="{{ route('empresa.publicaciones.create') }}"
        class="{{ $clases($activo === 'nueva') }}"
        @if ($activo === 'nueva') aria-current="page" @endif
    ><flux:icon.plus class="size-[18px]" />Nueva publicación</a>
@else
    {{-- Sin cupo en el plan no se ofrece crear: se ofrece ampliarlo. --}}
    <a
        wire:navigate
        href="{{ route('empresa.planes') }}"
        class="{{ $clases(false) }}"
    ><flux:icon.credit-card class="size-[18px]" />Ampliar plan para publicar</a>
@endif
