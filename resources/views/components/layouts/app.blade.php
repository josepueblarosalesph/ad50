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

            <div class="flex items-center gap-3">
                <span class="hidden ad-chip ad-chip-green ad-chip-dot sm:inline-flex">{{ $status ?? 'Perfil activo' }}</span>
                <flux:dropdown align="end">
                    <flux:profile :name="auth()->user()?->name ?? 'MF'"
                                  :initials="auth()->user() ? Str::of(auth()->user()->name)->explode(' ')->take(2)->map(fn($p)=>Str::substr($p,0,1))->join('') : 'MF'"
                                  :avatar="false" />
                    <flux:menu>
                        <flux:menu.item icon="user">Mi cuenta</flux:menu.item>
                        <flux:menu.item icon="cog-6-tooth">Configuración</flux:menu.item>
                        <flux:menu.separator />
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" variant="danger">
                                Cerrar sesión
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </div>
    </header>

    {{-- ====== SHELL CON SIDEBAR ====== --}}
    <div @class(['grid min-h-[calc(100vh-65px)]', 'md:grid-cols-[230px_1fr]' => isset($sidebar)])>
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
