<div class="min-h-screen bg-paper">
    <header class="border-b border-line bg-white">
        <div class="max-w-[1180px] mx-auto px-6 md:px-11 py-4 flex items-center justify-between gap-4">
            <a href="{{ route('home') }}" class="inline-flex items-center rounded-[11px] border border-line-2 bg-white px-3 py-2 font-black text-gray-400">AD<span class="text-orange-500">+</span>50</a>
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" class="text-[13px] font-semibold text-gray-500">Volver al inicio</a>
                <a href="{{ route('registro', ['tipo' => 'empresa']) }}" class="ad-btn-primary ad-btn-sm">Crear cuenta</a>
            </div>
        </div>
    </header>

    <main class="max-w-[1180px] mx-auto px-6 md:px-11 py-16">
        <div class="max-w-2xl mx-auto text-center mb-12">
            <span class="ad-eyebrow">Planes para empresas</span>
            <h1 class="text-[34px] md:text-[42px] font-extrabold tracking-[-0.02em] mt-3">Elige el alcance de tu búsqueda</h1>
            <p class="text-gray-500 mt-4">Todos los planes incluyen filtrado automático y protección de los datos de candidatos.</p>
        </div>

        <div class="grid lg:grid-cols-3 gap-5 items-stretch">
            @foreach ($planes as $plan)
                <article @class(['ad-card p-7 flex flex-col relative', 'border-2 border-orange-500 shadow-card-lg' => $plan->destacado])>
                    @if ($plan->destacado)
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-orange-600 text-white px-3 py-1 text-[11px] font-bold uppercase tracking-wide">Más elegido</span>
                    @endif
                    <h2 class="text-[19px] font-extrabold">{{ Str::after($plan->nombre, '· ') }}</h2>
                    <div class="text-[36px] font-extrabold mt-4">${{ number_format($plan->precio_clp, 0, ',', '.') }} <small class="text-[13px] text-gray-500 font-semibold">CLP</small></div>
                    <p class="text-[12.5px] text-gray-500 mb-6">por {{ $plan->periodo }}</p>
                    <ul class="space-y-3 flex-1 mb-7">
                        @foreach ($plan->features ?? [] as $feature)
                            <li class="flex gap-2.5 text-[13.5px] text-gray-700"><flux:icon.check class="size-4 text-match flex-none mt-0.5" />{{ $feature }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('registro', ['tipo' => 'empresa']) }}" class="{{ $plan->destacado ? 'ad-btn-primary' : 'ad-btn-ghost' }} ad-btn-sm ad-btn-block">Elegir {{ Str::after($plan->nombre, '· ') }}</a>
                </article>
            @endforeach

            <article class="ad-card p-7 flex flex-col">
                <h2 class="text-[19px] font-extrabold">Enterprise</h2>
                <div class="text-[30px] font-extrabold mt-4">A medida</div>
                <p class="text-[12.5px] text-gray-500 mb-6">para equipos y procesos de alto volumen</p>
                <ul class="space-y-3 flex-1 mb-7">
                    @foreach (['Búsquedas ilimitadas', 'Múltiples usuarios y áreas', 'Soporte prioritario', 'Onboarding dedicado'] as $feature)
                        <li class="flex gap-2.5 text-[13.5px] text-gray-700"><flux:icon.check class="size-4 text-match flex-none mt-0.5" />{{ $feature }}</li>
                    @endforeach
                </ul>
                <a href="mailto:contacto@adconsulting.cl" class="ad-btn-ghost ad-btn-sm ad-btn-block">Hablar con AD Consulting</a>
            </article>
        </div>

        @if ($planPostulante)
            <div class="ad-card p-5 mt-6 flex flex-wrap items-center justify-between gap-4">
                <div><b class="text-[14px]">¿Eres postulante?</b><p class="text-[12.5px] text-gray-500 mt-1">Activa tu ficha con un pago único de ${{ number_format($planPostulante->precio_clp, 0, ',', '.') }} CLP. Sin renovación.</p></div>
                <a href="{{ route('registro', ['tipo' => 'postulante']) }}" class="ad-btn-ghost ad-btn-sm">Crear mi perfil</a>
            </div>
        @endif
    </main>
</div>
