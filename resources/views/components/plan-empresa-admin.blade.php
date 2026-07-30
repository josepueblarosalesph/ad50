@props(['empresa'])

{{-- Estado del plan de una empresa, con las acciones del administrador. --}}
@php($vigente = $empresa->planVigente())

<div class="flex flex-wrap items-center gap-2">
    @if ($empresa->plan)
        <span @class(['ad-chip ad-chip-sm', 'ad-chip-green' => $vigente])>
            {{ $empresa->plan->nombre }}
            @if ($empresa->plan_hasta)
                · {{ $vigente ? 'hasta' : 'venció el' }} {{ $empresa->plan_hasta->translatedFormat('d M Y') }}
            @endif
        </span>
    @else
        <span class="ad-chip ad-chip-sm">Sin plan</span>
    @endif

    <button type="button" wire:click="abrirAsignacion({{ $empresa->id }})" class="text-[12.5px] font-bold text-orange-600 underline underline-offset-2 hover:text-orange-700">
        {{ $empresa->plan ? 'Cambiar plan' : 'Asignar plan' }}
    </button>

    @if ($empresa->plan)
        <button
            type="button"
            wire:click="quitarPlan({{ $empresa->id }})"
            wire:confirm="La empresa quedará sin plan vigente y perderá el acceso a su panel. ¿Continuar?"
            class="text-[12.5px] font-bold text-gray-500 underline underline-offset-2 hover:text-[#A93226]"
        >Quitar</button>
    @endif
</div>
