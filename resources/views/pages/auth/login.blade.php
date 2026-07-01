<x-layouts.marketing title="Iniciar sesión · AD+50">
    <main class="grid min-h-screen bg-paper lg:grid-cols-[1.08fr_.92fr]">
        <section
            class="relative hidden min-h-screen overflow-hidden bg-ink text-white lg:flex lg:flex-col lg:justify-between"
            style="background-image: linear-gradient(180deg, rgba(52,54,56,.5) 0%, rgba(52,54,56,.76) 48%, rgba(52,54,56,.97) 100%), url('/images/ad50-hero-experiencia.webp'); background-position: 66% center; background-size: cover;"
        >
            <div class="relative z-10 flex items-center justify-between px-10 py-8 xl:px-14 xl:py-10">
                <a href="{{ route('home') }}" aria-label="Volver al inicio de AD+50">
                    <img src="/images/ad50-logo.png" alt="AD+50 Talento Senior" class="h-[74px] w-auto">
                </a>
                <span class="rounded-full border border-white/20 bg-white/10 px-4 py-2 text-[11px] font-extrabold uppercase tracking-[.16em] text-white/75 backdrop-blur-sm">
                    Plataforma de talento
                </span>
            </div>

            <div class="relative z-10 max-w-[680px] px-10 pb-12 xl:px-14 xl:pb-16">
                <span class="inline-flex items-center gap-3 text-[12px] font-extrabold uppercase tracking-[.18em] text-orange-500">
                    <span class="h-px w-8 bg-orange-500"></span>
                    Bienvenido de vuelta
                </span>
                <h1 class="mt-5 text-[52px] leading-[.98] text-white xl:text-[64px]">
                    Tu experiencia sigue en movimiento.
                </h1>
                <p class="mt-6 max-w-[570px] text-[17px] font-medium leading-[1.7] text-white/72 xl:text-[18px]">
                    Accede a tu espacio para actualizar tu trayectoria, revisar oportunidades y continuar construyendo tu próximo desafío.
                </p>

                <div class="mt-10 grid max-w-[570px] grid-cols-3 gap-3 border-t border-white/15 pt-6">
                    @foreach ([
                        ['Tu perfil', 'siempre actualizado'],
                        ['Tus datos', 'bajo tu control'],
                        ['Tu experiencia', 'en el centro'],
                    ] as [$title, $description])
                        <div>
                            <strong class="block text-[13px] font-extrabold text-white">{{ $title }}</strong>
                            <span class="mt-1 block text-[11px] font-semibold leading-[1.4] text-white/50">{{ $description }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="relative flex min-h-screen items-center justify-center px-5 py-8 sm:px-8 lg:px-12 xl:px-20">
            <a href="{{ route('home') }}" class="absolute left-5 top-5 inline-flex items-center gap-2 text-[13px] font-bold text-gray-500 transition hover:text-ink sm:left-8 sm:top-7 lg:left-10" wire:navigate>
                <flux:icon.arrow-left class="size-4" />
                Volver al inicio
            </a>

            <div class="w-full max-w-[470px] pt-14 lg:pt-0">
                <div class="mb-8 flex justify-center lg:hidden">
                    <a href="{{ route('home') }}" class="rounded-[16px] bg-ink px-4" aria-label="Volver al inicio de AD+50">
                        <img src="/images/ad50-logo.png" alt="AD+50 Talento Senior" class="h-[72px] w-auto">
                    </a>
                </div>

                <div class="rounded-[24px] border border-line-2 bg-white p-6 shadow-[var(--shadow-card-lg)] sm:p-9 lg:p-10">
                    <div>
                        <span class="text-[11px] font-extrabold uppercase tracking-[.18em] text-orange-600">Acceso seguro</span>
                        <h2 class="mt-3 text-[38px] text-ink sm:text-[44px]">Ingresa a tu cuenta</h2>
                        <p class="mt-3 text-[14px] font-medium leading-[1.65] text-gray-500">
                            Usa las credenciales con las que creaste tu perfil.
                        </p>
                    </div>

                    <x-auth-session-status class="mt-5 rounded-[10px] border border-green-200 bg-green-50 px-4 py-3 text-left" :status="session('status')" />

                    <div class="mt-7">
                        <x-passkey-verify
                            label="Ingresar con passkey"
                            loading-label="Verificando identidad…"
                            separator="O continúa con tu correo"
                        />
                    </div>

                    <form method="POST" action="{{ route('login.store') }}" class="mt-1 grid gap-5">
                        @csrf

                        <flux:input
                            name="email"
                            label="Correo electrónico"
                            :value="old('email')"
                            type="email"
                            required
                            autofocus
                            autocomplete="email"
                            placeholder="tu@correo.cl"
                        />

                        <div class="relative">
                            <flux:input
                                name="password"
                                label="Contraseña"
                                type="password"
                                required
                                autocomplete="current-password"
                                placeholder="Ingresa tu contraseña"
                                viewable
                            />

                            @if (Route::has('password.request'))
                                <flux:link class="absolute end-0 top-0 text-[12px] font-bold text-orange-600 hover:text-orange-500" :href="route('password.request')" wire:navigate>
                                    ¿Olvidaste tu contraseña?
                                </flux:link>
                            @endif
                        </div>

                        <flux:checkbox name="remember" label="Mantener mi sesión iniciada" :checked="old('remember')" />

                        <button type="submit" class="ad-btn-primary ad-btn-block mt-1" data-test="login-button">
                            Ingresar a mi cuenta
                            <flux:icon.arrow-right class="size-4" />
                        </button>
                    </form>

                    <p class="mt-7 text-center text-[13px] font-medium text-gray-500">
                        ¿Aún no tienes una cuenta?
                        <a href="{{ route('registro') }}" class="font-extrabold text-orange-600 transition hover:text-orange-500" wire:navigate>Crear mi perfil</a>
                    </p>
                </div>

                <p class="mx-auto mt-5 max-w-[390px] text-center text-[11px] font-semibold leading-[1.6] text-gray-400">
                    Tus datos son gestionados de forma segura por AD Consulting y permanecen bajo tu control.
                </p>
            </div>
        </section>
    </main>
</x-layouts.marketing>
