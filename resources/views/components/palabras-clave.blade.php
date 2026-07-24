@props([
    'palabras' => [],
    'placeholder' => 'Escribe y presiona Enter',
    'descripcion' => null,
    'ayuda' => null,
    'hideLabel' => false,
])

<div>
    <flux:field>
        @unless ($hideLabel)
            <div class="flex items-center gap-1.5">
                <flux:label>Palabra clave</flux:label>
                @if ($ayuda)
                    <flux:tooltip :content="$ayuda">
                        <flux:icon.information-circle class="size-4 cursor-help text-gray-400 hover:text-gray-500" />
                    </flux:tooltip>
                @endif
            </div>
        @endunless

        @if ($descripcion)
            <flux:description>{{ $descripcion }}</flux:description>
        @endif

        <flux:input
            wire:model="nuevaPalabraClave"
            wire:keydown.enter.prevent="agregarPalabraClave"
            maxlength="100"
            :placeholder="$placeholder"
        />
    </flux:field>

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
