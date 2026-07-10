@props([
    'titulo',
    'descripcion' => null,
    'seleccion' => [],
    'error' => null,
])

@php($conError = $error !== null && $errors->has($error))

{{-- El wire:key cambia al aparecer un error para que Livewire reemplace el nodo y Alpine reabra el panel. --}}
<div
    {{ $attributes->class(['relative self-start rounded-xl border', 'border-line-2' => ! $conError, 'border-red-400' => $conError]) }}
    wire:key="selector-{{ $error ?? Str::slug($titulo) }}-{{ $conError ? 'error' : 'ok' }}"
    x-data="{ abierto: @js($conError) }"
    x-on:click.outside="abierto = false"
    x-on:keydown.escape.window="abierto = false"
>
    <button
        type="button"
        x-on:click="abierto = ! abierto"
        x-bind:aria-expanded="abierto"
        class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left transition hover:bg-orange-50/60 dark:hover:bg-white/5"
    >
        <span class="flex-none text-[13px] font-bold text-ink">{{ $titulo }}</span>
        @if (filled($seleccion))
            <span class="inline-flex size-5 flex-none items-center justify-center rounded-full bg-orange-100 text-[11px] font-bold leading-none text-orange-600 dark:bg-orange-500/15 dark:text-orange-300">{{ count($seleccion) }}</span>
        @endif
        <span class="min-w-0 flex-1 truncate text-[12px] text-gray-500">{{ filled($seleccion) ? implode(' · ', $seleccion) : 'Sin selección' }}</span>
        <flux:icon.chevron-down class="size-4 flex-none text-gray-500 transition" x-bind:class="abierto && 'rotate-180'" />
    </button>

    <div
        x-show="abierto"
        x-cloak
        class="absolute inset-x-0 top-full z-30 mt-1 rounded-xl border border-line-2 bg-white p-3 shadow-lg dark:bg-[#25282A]"
    >
        @if ($descripcion)
            <p class="mb-2 text-[12px] text-gray-500">{{ $descripcion }}</p>
        @endif

        {{ $slot }}

        @if ($error)
            <flux:error :name="$error" />
        @endif
    </div>
</div>
