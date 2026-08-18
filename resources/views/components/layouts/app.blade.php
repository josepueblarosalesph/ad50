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
                    {{ $context ?? 'AD+50' }}
                </span>
            </div>

            <nav class="hidden gap-1 flex-1 overflow-x-auto md:flex">
                {{ $nav ?? '' }}
            </nav>

            <div class="ml-auto flex shrink-0 items-center gap-3">
                {{-- Solo se dibuja para quien tiene los dos accesos (admin con empresa). --}}
                <x-conmutador-paneles />

                {{-- Visible también en móvil: enterarse de que llegó un mensaje no puede
                     depender del ancho de la pantalla. --}}
                <x-avisos-admin />

                <div class="hidden md:block">
                    <flux:dropdown align="end">
                        <flux:profile :name="auth()->user()?->name ?? 'MF'"
                                      :initials="auth()->user() ? Str::of(auth()->user()->name)->explode(' ')->take(2)->map(fn($p)=>Str::substr($p,0,1))->join('') : 'MF'"
                                      :avatar="false" />
                        <flux:menu>
                            <flux:menu.item :href="route('profile.edit')" icon="user">Mi cuenta</flux:menu.item>
                            @if (auth()->user()?->esEmpresa())
                                @if (auth()->user()->esPrincipalEmpresa())
                                    <flux:menu.item :href="route('empresa.equipo')" icon="users">Administración de usuarios</flux:menu.item>
                                @endif
                                <flux:menu.item :href="route('empresa.planes')" icon="credit-card">Mi suscripción</flux:menu.item>
                            @endif
                            <flux:menu.item :href="route('appearance.edit')" icon="cog-6-tooth">Configuración</flux:menu.item>
                            {{-- Ayuda vive aquí y no en el menú superior: se busca cuando
                                 hace falta, no es parte del trabajo diario. --}}
                            <flux:menu.item :href="route('ayuda')" icon="question-mark-circle">Ayuda y contacto</flux:menu.item>
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
                        <x-conmutador-paneles variante="menu" />
                        <a href="{{ route('profile.edit') }}"><flux:icon.user class="mr-2 size-4" />Mi cuenta</a>
                        @if (auth()->user()?->esEmpresa())
                            @if (auth()->user()->esPrincipalEmpresa())
                                <a href="{{ route('empresa.equipo') }}"><flux:icon.users class="mr-2 size-4" />Administración de usuarios</a>
                            @endif
                            <a href="{{ route('empresa.planes') }}"><flux:icon.credit-card class="mr-2 size-4" />Mi suscripción</a>
                        @endif
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

    {{-- ====== SHELL CON SIDEBAR ======
         El menú lateral se puede plegar para ganar ancho de contenido. La preferencia
         se guarda en localStorage (no en sesión) para que se mantenga al navegar sin
         costar un viaje al servidor. Solo aplica en escritorio: en móvil el sidebar ya
         está oculto y sus filtros viven en un desplegable dentro del contenido. --}}
    <div
        @isset($sidebar)
            x-data="{ plegado: localStorage.getItem('ad-sidebar-plegado') === '1' }"
            x-effect="localStorage.setItem('ad-sidebar-plegado', plegado ? '1' : '0')"
            {{-- El ancho sale de una variable CSS con el valor desplegado por defecto: el
                 servidor ya pinta el layout correcto y Alpine solo la baja si está plegado,
                 así no hay salto de columnas mientras carga. --}}
            x-bind:style="plegado ? '--ad-sidebar: 56px' : ''"
        @endisset
        @class(['grid min-h-[calc(100vh-65px)]', 'md:grid-cols-[var(--ad-sidebar,260px)_1fr]' => isset($sidebar)])
    >
        @isset($sidebar)
            {{-- `relative z-20` es necesario, no decorativo: los paneles de filtros usan un
                 contenedor `sticky`, y `position: sticky` crea un contexto de apilamiento
                 propio. Sin elevar el <aside>, el z-index de un desplegable solo compite
                 dentro del menú y el contenido —que va después en el DOM— lo tapa al
                 desbordarse hacia la derecha. Queda bajo el header, que es z-30. --}}
            <aside class="relative z-20 hidden border-r border-line bg-white px-3 py-4 dark:bg-[#1D2022] md:block">
                <div class="mb-2 flex" x-bind:class="plegado ? 'justify-center' : 'justify-end'">
                    <button
                        type="button"
                        x-on:click="plegado = ! plegado"
                        class="grid size-8 place-items-center rounded-lg text-gray-400 transition hover:bg-paper hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500 dark:hover:bg-white/5"
                        x-bind:aria-label="plegado ? 'Mostrar el menú lateral' : 'Plegar el menú lateral'"
                        x-bind:aria-expanded="plegado ? 'false' : 'true'"
                        aria-controls="menu-lateral"
                    >
                        <flux:icon.chevron-double-left class="size-4" x-bind:class="plegado && 'hidden'" />
                        <flux:icon.chevron-double-right class="hidden size-4" x-bind:class="plegado && '!block'" />
                    </button>
                </div>

                {{-- Visible por defecto; Alpine lo esconde solo si la preferencia es plegado. --}}
                <div id="menu-lateral" x-bind:class="plegado && 'hidden'">
                    {{ $sidebar }}
                </div>
            </aside>
        @endisset

        <main class="min-w-0 p-4 md:p-8">
            {{ $slot }}
        </main>
    </div>

    @fluxScripts
</body>
</html>
