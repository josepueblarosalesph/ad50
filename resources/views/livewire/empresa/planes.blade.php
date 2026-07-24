<div class="ad-panel">
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:status>{{ $planActual?->nombre ?? 'Sin plan' }}</x-slot:status>
    <x-slot:nav>
        <a wire:navigate href="{{ route('empresa.panel') }}" class="rounded-lg px-3.5 py-2 text-[13.5px] font-semibold text-gray-500 hover:text-ink">Mi Panel</a>
        <a wire:navigate href="{{ route('empresa.busquedas.index') }}" class="rounded-lg px-3.5 py-2 text-[13.5px] font-semibold text-gray-500 hover:text-ink">Mis Procesos</a>
        <a wire:navigate href="{{ route('empresa.planes') }}" class="rounded-lg bg-orange-100 px-3.5 py-2 text-[13.5px] font-semibold text-ink">Planes</a>
        @if (auth()->user()->esPrincipalEmpresa())
            <a wire:navigate href="{{ route('empresa.equipo') }}" class="rounded-lg px-3.5 py-2 text-[13.5px] font-semibold text-gray-500 hover:text-ink">Equipo</a>
        @endif
    </x-slot:nav>

    <div class="mx-auto max-w-5xl">
        <div class="mb-6">
            <span class="ad-eyebrow">Suscripción</span>
            <h1 class="mt-3 text-[27px] font-extrabold">Planes para tu empresa</h1>
            <p class="mt-1.5 text-[14px] text-gray-500">Contrata un plan para desbloquear perfiles y acceder a los datos de contacto de tus candidatos.</p>
        </div>

        @if (session('status'))
            <div class="mb-5 rounded-xl border border-[#BFE6CD] bg-match-100 px-4 py-3 text-[13px] font-bold text-match">{{ session('status') }}</div>
        @endif
        @if (session('error_pago'))
            <div class="mb-5 rounded-xl border border-[#E7B6AE] bg-[#FBEDEA] px-4 py-3 text-[13px] font-semibold text-[#A93226] dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">{{ session('error_pago') }}</div>
        @endif
        @error('pago')<div class="mb-5 rounded-xl border border-[#E7B6AE] bg-[#FBEDEA] px-4 py-3 text-[13px] font-semibold text-[#A93226] dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">{{ $message }}</div>@enderror

        @if ($planVigente && $planActual)
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[#BFE6CD] bg-match-100/60 px-5 py-4">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 flex-none place-items-center rounded-xl bg-match-100 text-match"><flux:icon.check-badge class="size-5" /></span>
                    <div>
                        <p class="text-[14px] font-extrabold text-ink">Plan activo: {{ $planActual->nombre }}</p>
                        <p class="text-[13px] text-gray-600">Vigente hasta el {{ $empresa->plan_hasta?->translatedFormat('d M Y') }} · {{ $empresa->desbloqueosDisponibles() }} desbloqueos disponibles</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid gap-5 lg:grid-cols-3">
            @foreach ($planes as $plan)
                <article wire:key="plan-{{ $plan->id }}" @class(['ad-card relative flex flex-col p-6', 'border-2 border-orange-500' => $plan->destacado])>
                    @if ($plan->destacado)
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-orange-600 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-white">Más elegido</span>
                    @endif
                    <h2 class="text-[18px] font-extrabold">{{ \Illuminate\Support\Str::after($plan->nombre, '· ') }}</h2>
                    <div class="mt-3 text-[30px] font-extrabold">${{ number_format((int) $plan->precio_clp, 0, ',', '.') }} <small class="text-[12px] font-semibold text-gray-500">CLP</small></div>
                    <p class="mb-5 text-[12.5px] text-gray-500">{{ $plan->periodo === 'anual' ? 'por año' : 'por mes' }}</p>

                    <ul class="mb-6 flex-1 space-y-2.5">
                        @foreach ($plan->features ?? [] as $feature)
                            <li wire:key="plan-{{ $plan->id }}-f-{{ $loop->index }}" class="flex gap-2.5 text-[13.5px] text-gray-700 dark:text-gray-300"><flux:icon.check class="mt-0.5 size-4 flex-none text-match" />{{ $feature }}</li>
                        @endforeach
                    </ul>

                    <button
                        type="button"
                        wire:click="contratar({{ $plan->id }})"
                        wire:loading.attr="disabled"
                        wire:target="contratar({{ $plan->id }})"
                        class="{{ $plan->destacado ? 'ad-btn-primary' : 'ad-btn-ghost' }} ad-btn-sm ad-btn-block justify-center"
                    >
                        <span wire:loading.remove wire:target="contratar({{ $plan->id }})">{{ $planVigente ? 'Renovar / cambiar' : 'Contratar' }}</span>
                        <span wire:loading wire:target="contratar({{ $plan->id }})">Redirigiendo a Flow…</span>
                    </button>
                </article>
            @endforeach
        </div>

        <p class="mt-6 flex items-center justify-center gap-2 text-[12px] text-gray-400">
            <flux:icon.lock-closed class="size-3.5" />
            Pago seguro procesado por Flow.
        </p>
    </div>
</div>
