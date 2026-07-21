@props([
    'palabras' => [],
    'placeholder' => 'Escribe y presiona Enter',
    'descripcion' => null,
    'hideLabel' => false,
])

<div>
    <flux:input
        wire:model="nuevaPalabraClave"
        wire:keydown.enter.prevent="agregarPalabraClave"
        :label="$hideLabel ? null : 'Palabra clave'"
        maxlength="100"
        :placeholder="$placeholder"
        :description="$descripcion"
    />

    @if (filled($palabras))
        <div class="mt-2 flex flex-wrap gap-1">
            @foreach ($palabras as $index => $palabra)
                <span class="ad-chip ad-chip-orange gap-1 px-1.5 py-0 pr-0.5 text-[10.5px]" wire:key="palabra-{{ $index }}">
                    {{ $palabra }}
                    <button
                        type="button"
                        wire:click="quitarPalabraClave({{ $index }})"
                        class="rounded-full p-0.5 transition hover:bg-orange-200 dark:hover:bg-white/10"
                        aria-label="Quitar la palabra clave {{ $palabra }}"
                    ><flux:icon.x-mark class="size-2.5" /></button>
                </span>
            @endforeach
        </div>
    @endif

    <flux:error name="palabrasClave" />
</div>
