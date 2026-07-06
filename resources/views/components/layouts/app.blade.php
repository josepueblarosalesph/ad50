<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'AD+50' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-screen bg-paper text-ink transition-colors duration-200">

    {{-- ====== TOPBAR ====== --}}
    <header class="sticky top-0 z-30 border-b border-line bg-white/95 backdrop-blur dark:bg-[#1D2022]/95">
        <div class="flex items-center gap-4 px-4 py-3 md:gap-6 md:px-6">
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" class="ad-logo shrink-0" aria-label="AD+50 Talento Senior">
                    <img src="/images/ad50-logo.png" alt="AD+50 Talento Senior" class="ad-brand-logo">
                </a>
                <span class="text-[12px] font-bold tracking-[0.13em] uppercase text-gray-500
                             border-l border-line-2 pl-3">
                    {{ $context ?? 'Postulante' }}
                </span>
            </div>

            <nav class="hidden gap-1 flex-1 overflow-x-auto md:flex">
                {{ $nav ?? '' }}
            </nav>

            <div class="ml-auto flex shrink-0 items-center gap-3">
                <span class="hidden ad-chip ad-chip-green ad-chip-dot md:inline-flex">{{ $status ?? 'Perfil activo' }}</span>
                <div class="hidden md:block">
                    <flux:dropdown align="end">
                        <flux:profile :name="auth()->user()?->name ?? 'MF'"
                                      :initials="auth()->user() ? Str::of(auth()->user()->name)->explode(' ')->take(2)->map(fn($p)=>Str::substr($p,0,1))->join('') : 'MF'"
                                      :avatar="false" />
                        <flux:menu>
                            <flux:menu.item :href="route('profile.edit')" icon="user">Mi cuenta</flux:menu.item>
                            <flux:menu.item :href="route('appearance.edit')" icon="cog-6-tooth">Configuración</flux:menu.item>
                            <flux:menu.separator />
                            <form method="POST" action="{{ route('logout') }}">@csrf
                                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" variant="danger">
                                    Cerrar sesión
                                </flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                </div>
                @if (isset($nav))
                    <x-mobile-menu id="application-mobile-navigation">
                        <div class="mb-2 flex items-center gap-3 rounded-xl bg-paper px-4 py-3 dark:bg-white/10">
                            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-orange-100 text-[14px] font-extrabold text-orange-700">
                                {{ auth()->user() ? Str::of(auth()->user()->name)->explode(' ')->take(2)->map(fn ($part) => Str::substr($part, 0, 1))->join('') : 'MF' }}
                            </span>
                            <div class="min-w-0">
                                <strong class="block truncate text-[15px]">{{ auth()->user()?->name }}</strong>
                                <span class="block truncate text-[12px] font-semibold text-gray-500">{{ auth()->user()?->email }}</span>
                            </div>
                        </div>
                        {{ $nav }}
                        <div class="my-2 h-px bg-line"></div>
                        <a href="{{ route('profile.edit') }}"><flux:icon.user class="mr-2 size-4" />Mi cuenta</a>
                        <a href="{{ route('appearance.edit') }}"><flux:icon.cog-6-tooth class="mr-2 size-4" />Configuración</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-[#A93226] dark:text-red-400"><flux:icon.arrow-right-start-on-rectangle class="mr-2 size-4" />Cerrar sesión</button>
                        </form>
                    </x-mobile-menu>
                @endif
            </div>
        </div>
    </header>

    {{-- ====== SHELL CON SIDEBAR ====== --}}
    <div @class(['grid min-h-[calc(100vh-65px)]', 'md:grid-cols-[260px_1fr]' => isset($sidebar)])>
        @isset($sidebar)
            <aside class="hidden border-r border-line bg-white p-4 dark:bg-[#1D2022] md:block">
                {{ $sidebar }}
            </aside>
        @endisset

        <main class="min-w-0 p-4 md:p-8">
            {{ $slot }}
        </main>
    </div>

    @fluxScripts
</body>
</html>
