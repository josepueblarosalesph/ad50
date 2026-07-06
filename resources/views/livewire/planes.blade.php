<div class="min-h-screen bg-paper">
    <header class="border-b border-line bg-white dark:bg-[#1D2022]">
        <div class="max-w-[1180px] mx-auto px-6 md:px-11 py-4 flex items-center justify-between gap-4">
            <a href="{{ route('home') }}" class="ad-logo ad-logo-panel" aria-label="AD+50 Talento Senior"><img src="/images/ad50-logo.png" alt="AD+50 Talento Senior" class="ad-brand-logo"></a>
            <div class="flex flex-wrap items-center justify-end gap-2 sm:gap-3">
                <a href="{{ route('home') }}" class="ad-btn-ghost ad-btn-sm gap-2">
                    <flux:icon.arrow-left class="size-4" />
                    <span class="hidden sm:inline">Volver al inicio</span>
                    <span class="sr-only sm:hidden">Volver al inicio</span>
                </a>
                <a href="{{ route('planes.postulantes') }}" class="ad-btn-ghost ad-btn-sm gap-2">
                    <flux:icon.user class="size-4" />
                    <span class="hidden md:inline">Planes para postulantes</span>
                    <span class="md:hidden">Postulantes</span>
                </a>
                <a href="{{ route('registro', ['tipo' => 'empresa']) }}" class="ad-btn-primary ad-btn-sm">Crear cuenta</a>
            </div>
        </div>
    </header>

    <main class="max-w-[1180px] mx-auto px-6 md:px-11 py-16">
        <div class="max-w-2xl mx-auto text-center mb-12">
            <span class="ad-eyebrow">Planes para empresas</span>
            <h1 class="text-[34px] md:text-[42px] font-extrabold tracking-[-0.02em] mt-3">Elige el alcance de tu búsqueda</h1>
            <p class="text-gray-500 mt-4">Publica tus vacantes, recibe candidatos compatibles mediante nuestro sistema de matching y accede a los currículums de quienes mejor se ajustan a tu búsqueda.</p>
        </div>

        <div class="grid lg:grid-cols-3 gap-5 items-stretch">
            @foreach ($planes as $plan)
                <article wire:key="empresa-plan-{{ $plan->id }}" @class(['ad-card p-7 flex flex-col relative', 'border-2 border-orange-500 shadow-card-lg' => $plan->destacado])>
                    @if ($plan->destacado)
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-orange-600 text-white px-3 py-1 text-[11px] font-bold uppercase tracking-wide">Más elegido</span>
                    @endif
                    <h2 class="text-[19px] font-extrabold">{{ Str::after($plan->nombre, '· ') }}</h2>
                    <div class="text-[36px] font-extrabold mt-4">{{ number_format((float) $plan->precio_uf, 0, ',', '.') }} <small class="text-[13px] text-gray-500 font-semibold">UF + IVA</small></div>
                    <p class="text-[12.5px] text-gray-500 mb-6">{{ $plan->periodo === 'anual' ? 'plan anual' : 'pago único' }}</p>
                    <ul class="space-y-3 flex-1 mb-7">
                        @foreach ($plan->features ?? [] as $feature)
                            <li wire:key="empresa-plan-{{ $plan->id }}-feature-{{ $loop->index }}" class="flex gap-2.5 text-[13.5px] text-gray-700"><flux:icon.check class="size-4 text-match flex-none mt-0.5" />{{ $feature }}</li>
                        @endforeach
                    </ul>
                    <p class="mb-6 rounded-xl bg-orange-50 px-4 py-3 text-[13px] font-bold text-orange-700 dark:bg-[#33251D] dark:text-[#F7C59E]">{{ $plan->recomendacion }}</p>
                    <a href="{{ route('registro', ['tipo' => 'empresa']) }}" class="{{ $plan->destacado ? 'ad-btn-primary' : 'ad-btn-ghost' }} ad-btn-sm ad-btn-block">Elegir {{ Str::after($plan->nombre, '· ') }}</a>
                </article>
            @endforeach
        </div>

        <x-plan-benefits class="mt-10" />
    </main>
</div>
