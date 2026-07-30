@props(['clase' => 'size-4'])

{{-- Indicador de carga. `aria-hidden` porque el estado se anuncia con aria-busy en el
     elemento que lo contiene; el círculo por sí solo no aporta nada al lector. --}}
<svg {{ $attributes->merge(['class' => 'animate-spin '.$clase]) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4z" />
</svg>
