@props([
    'heading' => '',
    'subheading' => '',
    // "Mi cuenta" es una pantalla única y no lleva menú lateral; Configuración sí, porque
    // agrupa Seguridad y Apariencia.
    'conNavegacion' => true,
])

<div class="flex items-start max-md:flex-col">
    @if ($conNavegacion)
        <div class="me-10 w-full pb-4 md:w-[220px]">
            <flux:navlist aria-label="Configuración">
                <flux:navlist.item :href="route('security.edit')" wire:navigate>Seguridad</flux:navlist.item>
                <flux:navlist.item :href="route('appearance.edit')" wire:navigate>Apariencia</flux:navlist.item>
            </flux:navlist>
        </div>

        <flux:separator class="md:hidden" />
    @endif

    <div @class(['flex-1 self-stretch', 'max-md:pt-6' => $conNavegacion])>
        @if (($heading ?? '') !== '')<h2 class="text-[18px] font-extrabold">{{ $heading }}</h2>@endif
        @if (($subheading ?? '') !== '')<p class="mt-1 text-[14px] text-gray-500">{{ $subheading }}</p>@endif

        <div class="mt-5 w-full max-w-2xl">
            {{ $slot }}
        </div>
    </div>
</div>
