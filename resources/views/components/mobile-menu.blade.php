@props([
    'breakpoint' => 'md',
    'label' => 'Abrir menú de navegación',
    'panelClass' => '',
])

<div
    x-data="{ open: false }"
    x-on:keydown.escape.window="open = false"
    @class([
        'relative',
        'md:hidden' => $breakpoint === 'md',
        'lg:hidden' => $breakpoint === 'lg',
    ])
>
    <button
        type="button"
        x-on:click="open = ! open"
        x-bind:aria-expanded="open"
        aria-controls="{{ $attributes->get('id', 'mobile-navigation') }}"
        aria-label="{{ $label }}"
        class="grid size-11 place-items-center rounded-xl border border-line-2 bg-white/90 text-ink shadow-sm backdrop-blur transition hover:border-orange-300 hover:bg-orange-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500 dark:bg-[#25282A] dark:text-white"
    >
        <flux:icon.bars-3 x-show="! open" class="size-6" />
        <flux:icon.x-mark x-show="open" x-cloak class="size-6" />
    </button>

    <div
        id="{{ $attributes->get('id', 'mobile-navigation') }}"
        x-show="open"
        x-cloak
        x-transition.origin.top.right
        x-on:click.outside="open = false"
        class="absolute right-0 top-[calc(100%+0.75rem)] z-50 w-[min(22rem,calc(100vw-2rem))] rounded-2xl border border-line-2 bg-white p-3 text-ink shadow-[0_22px_55px_rgba(0,0,0,.18)] dark:bg-[#25282A] dark:text-white {{ $panelClass }}"
    >
        <nav
            aria-label="Navegación móvil"
            x-on:click="if ($event.target.closest('a')) open = false"
            class="grid gap-1 [&_a]:flex [&_a]:min-h-11 [&_a]:w-full [&_a]:items-center [&_a]:rounded-xl [&_a]:px-4 [&_a]:py-2.5 [&_a]:text-[15px] [&_a]:font-bold [&_a]:transition [&_a:hover]:bg-orange-50 [&_a:hover]:text-orange-700 dark:[&_a:hover]:bg-white/10 dark:[&_a:hover]:text-orange-300"
        >
            {{ $slot }}
        </nav>
    </div>
</div>
